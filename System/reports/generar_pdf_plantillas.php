<?php
require_once '../vendor/autoload.php';
require_once '../bd/conexion.php';
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Lima');

// Obtener datos del sistema desde configuración
$configQuery = "SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('nombre_institucion', 'email_principal', 'telefono_principal', 'imagen')";
$configResult = $conn->query($configQuery);

$config = [
    'nombre_institucion' => 'Sistema CRM',
    'email_principal' => 'info@sistema.com',
    'telefono_principal' => '+51 000000000',
    'imagen' => 'assets/images/logocolgio.jpeg'
];

if ($configResult && $configResult->num_rows > 0) {
    while ($row = $configResult->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }
}

// Construir ruta completa de la imagen
$rutaImagen = '../' . $config['imagen']; 

// Convertir imagen a base64 para DomPDF
$imagenBase64 = '';
if (file_exists($rutaImagen)) {
    $imageData = file_get_contents($rutaImagen);
    $imagenBase64 = 'data:image/' . pathinfo($rutaImagen, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
} else {
    $imagenBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#007bff"/><text x="50%" y="50%" fill="white" text-anchor="middle" dy=".3em" font-size="20">LOGO</text></svg>');
}

// Obtener rol del usuario desde sesión
session_start();
$rolUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : "Desconocido";
$roles = [1 => "Administrador", 2 => "Coordinador Marketing", 3 => "Tutor", 4 => "Finanzas"];
$nombreRol = isset($roles[$rolUsuario]) ? $roles[$rolUsuario] : "Usuario del Sistema";

// Obtener los datos filtrados desde la tabla
$filteredData = isset($_POST['filteredData']) ? json_decode($_POST['filteredData'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($filteredData)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de plantillas.'); window.close();</script>");
}

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-plantillas {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    #tabla-cabecera {
        text-align: center;
        letter-spacing: 0.5px;
        color: #333;
    }

    #tabla-cabecera h3 {
        font-size: 18px;
        margin-bottom: 2px;
        color: #444;
    }

    .logo-empresa {
        border: 1px solid #666;
        border-radius: 20px;
        text-align: center;
        padding: 12px;
        display: inline-block;
    }

    .info-empresa {
        font-size: 13px;
        color: #666;
    }

    .reporte-titulo {
        border: 1px solid #666;
        border-radius: 20px;
        text-align: center;
        padding: 12px;
        display: inline-block;
        background-color: #f8f9fa;
    }

    #tabla-plantillas td, #tabla-plantillas th {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 8px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-plantillas th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .seccion-titulo {
        background-color: #f2f2f2;
        padding: 15px;
        margin: 25px 0 15px 0;
        font-weight: bold;
        border-radius: 5px;
        font-size: 14px;
        text-align: center;
    }

    .pie-pagina {
        margin-top: 25px;
        padding: 12px;
        font-size: 11px;
        border: 0.5px solid #333;
        border-radius: 10px;
        text-align: center;
        background-color: #f8f9fa;
    }

    .estadisticas-resumen {
        background-color: #e9ecef;
        padding: 10px;
        margin: 15px 0;
        border-radius: 5px;
        font-size: 11px;
    }

    /* Estilos para tipos */
    .tipo-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 600;
        color: white;
        display: inline-block;
    }

    .tipo-email { background-color: #dc3545; }
    .tipo-whatsapp { background-color: #25d366; }
    .tipo-sms { background-color: #007bff; }

    /* Estilos para categorías */
    .categoria-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 500;
        color: white;
        display: inline-block;
    }

    .categoria-cumpleanos { background-color: #e83e8c; }
    .categoria-evento { background-color: #6f42c1; }
    .categoria-recordatorio { background-color: #fd7e14; }
    .categoria-general { background-color: #6c757d; }
    .categoria-bienvenida { background-color: #20c997; }

    /* Estilos para programación */
    .programacion-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 7px;
        font-weight: bold;
        display: inline-block;
    }

    .programacion-activa { background-color: #28a745; color: white; }
    .programacion-programada { background-color: #ffc107; color: #856404; }
    .programacion-manual { background-color: #6c757d; color: white; }

    /* Estilos para información */
    .plantilla-nombre {
        font-weight: bold;
        color: #2c3e50;
        font-size: 9px;
        margin-bottom: 2px;
    }

    .plantilla-asunto {
        font-size: 7px;
        color: #6c757d;
        font-style: italic;
        line-height: 1.3;
    }

    .plantilla-preview {
        font-size: 7px;
        color: #6c757d;
        background-color: #f8f9fa;
        padding: 1px 3px;
        border-radius: 2px;
    }

    .variables-info {
        font-size: 7px;
        padding: 1px 3px;
        border-radius: 3px;
        background-color: #e8f4fd;
        color: #0c5460;
    }

    .uso-stats {
        font-size: 7px;
        color: #495057;
        line-height: 1.4;
    }

    .stat-principal {
        font-weight: bold;
        display: block;
    }

    .stat-secundario {
        color: #6c757d;
        display: block;
    }

    .frecuencia-info {
        font-size: 7px;
        padding: 2px 4px;
        border-radius: 4px;
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
    }

    .estado-activo {
        color: #28a745;
        font-weight: bold;
        font-size: 8px;
    }

    .estado-inactivo {
        color: #dc3545;
        font-weight: bold;
        font-size: 8px;
    }

    .fecha-uso {
        font-size: 7px;
        color: #6c757d;
    }

    /* Anchos de columnas */
    .col-id { width: 4%; text-align: center; }
    .col-tipo { width: 7%; text-align: center; }
    .col-plantilla { width: 20%; }
    .col-categoria { width: 9%; text-align: center; }
    .col-variables { width: 10%; }
    .col-estadisticas { width: 11%; }
    .col-programacion { width: 9%; text-align: center; }
    .col-frecuencia { width: 10%; }
    .col-estado { width: 7%; text-align: center; }
    .col-ultimo-uso { width: 8%; }
</style>';

// **Cabecera del reporte**
$html .= '<table id="tabla-cabecera">
    <tr>
        <td class="logo-empresa" style="width: 30%;">
            <img src="' . $imagenBase64 . '" alt="Logo" style="max-width: 100px; max-height: 100px; object-fit: contain;">
        </td>
        <td style="width: 40%;">
            <h3>' . htmlspecialchars($config['nombre_institucion']) . '</h3>
            <div class="info-empresa">Sistema de Gestión CRM Escolar</div>
            <div class="info-empresa">' . htmlspecialchars($config['email_principal']) . '</div>
            <div class="info-empresa">Telf. ' . htmlspecialchars($config['telefono_principal']) . '</div>
        </td>
        <td style="width: 30%;">
            <div class="reporte-titulo">
                <h4>REPORTE DE PLANTILLAS</h4>
                <h4>MENSAJES RECURRENTES</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas de resumen**
$totalPlantillas = count($filteredData);
$totalEmails = 0;
$totalWhatsapp = 0;
$totalSms = 0;
$activas = 0;
$inactivas = 0;
$totalUsos = 0;
$programacionActiva = 0;
$programacionProgramada = 0;
$programacionManual = 0;

// Contadores por categoría
$cumpleanos = 0;
$eventos = 0;
$recordatorios = 0;
$general = 0;
$bienvenida = 0;

foreach ($filteredData as $row) {
    $tipo = strtolower(trim(strip_tags($row[1])));
    $categoria = strtolower(trim(strip_tags($row[3])));
    $estadisticas = strip_tags($row[5]);
    $programacion = strtolower(trim(strip_tags($row[6])));
    $estado = strip_tags($row[8]);
    
    // Contar por tipo
    if (strpos($tipo, 'email') !== false) $totalEmails++;
    if (strpos($tipo, 'whatsapp') !== false) $totalWhatsapp++;
    if (strpos($tipo, 'sms') !== false) $totalSms++;
    
    // Contar por estado
    if (strpos(strtolower($estado), 'activa') !== false) $activas++;
    if (strpos(strtolower($estado), 'inactiva') !== false) $inactivas++;
    
    // Contar por programación
    if ($programacion == 'activa') $programacionActiva++;
    if ($programacion == 'programada') $programacionProgramada++;
    if ($programacion == 'manual') $programacionManual++;
    
    // Contar por categoría
    if ($categoria == 'cumpleaños' || $categoria == 'cumpleanos') $cumpleanos++;
    if ($categoria == 'evento') $eventos++;
    if ($categoria == 'recordatorio') $recordatorios++;
    if ($categoria == 'general') $general++;
    if ($categoria == 'bienvenida') $bienvenida++;
    
    // Sumar usos totales (extraer del texto)
    if (preg_match('/(\d+)\s+total/i', $estadisticas, $matches)) {
        $totalUsos += intval($matches[1]);
    }
}

$html .= '<div class="estadisticas-resumen">
    <strong>RESUMEN ESTADÍSTICO:</strong> 
    Total: ' . $totalPlantillas . ' plantillas | 
    Emails: ' . $totalEmails . ' | 
    WhatsApp: ' . $totalWhatsapp . ' | 
    SMS: ' . $totalSms . ' | 
    Activas: ' . $activas . ' | 
    Inactivas: ' . $inactivas . ' | 
    Total Usos: ' . number_format($totalUsos) . ' <br>
    <strong>Por Categoría:</strong> 
    Cumpleaños: ' . $cumpleanos . ' | 
    Eventos: ' . $eventos . ' | 
    Recordatorios: ' . $recordatorios . ' | 
    General: ' . $general . ' | 
    Bienvenida: ' . $bienvenida . ' <br>
    <strong>Programación:</strong> 
    Activa: ' . $programacionActiva . ' | 
    Programada: ' . $programacionProgramada . ' | 
    Manual: ' . $programacionManual . '
</div>';

// **Sección de listado de plantillas**
$html .= '<div class="seccion-titulo">LISTADO DETALLADO DE PLANTILLAS DE MENSAJES</div>';
$html .= '<table id="tabla-plantillas">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-plantilla">Plantilla</th>
            <th class="col-variables">Variables</th>
            <th class="col-estadisticas">Estadísticas</th>
            <th class="col-programacion">Programación</th>
            <th class="col-frecuencia">Frecuencia</th>
            <th class="col-estado">Estado</th>
            <th class="col-ultimo-uso">Último Uso</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Procesar datos - limpiar HTML y extraer texto
    $id = htmlspecialchars(strip_tags($row[0]));
    
    // Tipo
    $tipo = strtoupper(strip_tags($row[1]));
    $tipoClass = '';
    if (strpos(strtolower($tipo), 'email') !== false) $tipoClass = 'tipo-email';
    if (strpos(strtolower($tipo), 'whatsapp') !== false) $tipoClass = 'tipo-whatsapp';
    if (strpos(strtolower($tipo), 'sms') !== false) $tipoClass = 'tipo-sms';
    
    // Plantilla - extraer nombre, asunto y preview del HTML
    $plantillaHTML = $row[2];
    $nombre = '';
    $asunto = '';
    $preview = '';
    
    // Extraer el nombre (dentro de span.plantilla-nombre)
    if (preg_match('/<span class=["\']plantilla-nombre["\']>(.*?)<\/span>/s', $plantillaHTML, $matches)) {
        $nombre = trim(strip_tags($matches[1]));
    }
    
    // Extraer el asunto (dentro de span.plantilla-asunto)
    if (preg_match('/<span class=["\']plantilla-asunto["\']>(.*?)<\/span>/s', $plantillaHTML, $matches)) {
        $asunto = trim(strip_tags($matches[1]));
    }
    
    // Extraer el preview (dentro de span.plantilla-preview)
    if (preg_match('/<span class=["\']plantilla-preview["\']>(.*?)<\/span>/s', $plantillaHTML, $matches)) {
        $preview = trim(strip_tags($matches[1]));
        $preview = rtrim($preview, '.');
    }
    
    // Si no se encontró mediante regex, extraer todo el texto
    if (empty($nombre)) {
        $textoLimpio = strip_tags($plantillaHTML);
        $lineas = array_filter(array_map('trim', explode("\n", $textoLimpio)));
        if (!empty($lineas)) {
            $nombre = $lineas[0];
            if (count($lineas) > 1) $asunto = $lineas[1];
            if (count($lineas) > 2) $preview = $lineas[2];
        }
    }
    
    if (empty($nombre)) {
        $nombre = 'Sin nombre';
    }
    
    // Limitar longitudes
    if (strlen($nombre) > 40) {
        $nombre = substr($nombre, 0, 37) . '...';
    }
    if (strlen($preview) > 60) {
        $preview = substr($preview, 0, 57) . '...';
    }
    
    // Categoría
    $categoria = strip_tags($row[3]);
    $categoriaLower = strtolower(trim($categoria));
    $categoriaClass = 'categoria-' . $categoriaLower;
    
    // Variables
    $variables = strip_tags($row[4]);
    if (strlen($variables) > 30) {
        $variables = substr($variables, 0, 27) . '...';
    }
    
    // Estadísticas - extraer y formatear
    $estadisticasHTML = $row[5];
    $estadisticas = strip_tags($estadisticasHTML);
    $estadisticas = str_replace("\n", ' | ', trim($estadisticas));
    
    // Programación
    $programacion = strip_tags($row[6]);
    $programacionLower = strtolower(trim($programacion));
    $programacionClass = 'programacion-' . $programacionLower;
    
    // Frecuencia
    $frecuencia = strip_tags($row[7]);
    
    // Estado
    $estado = strip_tags($row[8]);
    $estadoClass = strpos(strtolower($estado), 'activa') !== false ? 'estado-activo' : 'estado-inactivo';
    
    // Último uso
    $ultimoUso = strip_tags($row[9]);
    
    $html .= '<tr>
        <td class="col-id">' . $id . '</td>
        <td class="col-tipo">
            ' . ($tipoClass ? '<span class="tipo-badge ' . $tipoClass . '">' . htmlspecialchars($tipo) . '</span>' : htmlspecialchars($tipo)) . '
        </td>
        <td class="col-plantilla">
            <div class="plantilla-nombre">' . htmlspecialchars($nombre) . '</div>
            ' . ($asunto ? '<div class="plantilla-asunto">' . htmlspecialchars($asunto) . '</div>' : '') . '
            ' . ($preview ? '<div class="plantilla-preview">' . htmlspecialchars($preview) . '</div>' : '') . '
        </td>
        <td class="col-variables">
            <span class="variables-info">' . htmlspecialchars($variables) . '</span>
        </td>
        <td class="col-estadisticas">
            <div class="uso-stats">' . htmlspecialchars($estadisticas) . '</div>
        </td>
        <td class="col-programacion">
            <span class="programacion-badge ' . $programacionClass . '">' . htmlspecialchars(ucfirst($programacion)) . '</span>
        </td>
        <td class="col-frecuencia">
            <span class="frecuencia-info">' . htmlspecialchars($frecuencia) . '</span>
        </td>
        <td class="col-estado">
            <span class="' . $estadoClass . '">' . htmlspecialchars($estado) . '</span>
        </td>
        <td class="col-ultimo-uso">
            <span class="fecha-uso">' . htmlspecialchars($ultimoUso) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $totalPlantillas . ' plantillas | 
    <strong>Total de usos acumulados:</strong> ' . number_format($totalUsos) . ' mensajes enviados
</div>';

// **Configurar DomPDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para más columnas
$dompdf->render();

// **Enviar PDF al navegador**
$filename = 'Reporte_Plantillas_Mensajes_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>