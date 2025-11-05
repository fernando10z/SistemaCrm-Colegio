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
    die("<script>window.alert('No hay registros disponibles para generar el reporte de interacciones.');</script>");
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

    #tabla-cabecera, #tabla-interacciones {
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

    #tabla-interacciones td, #tabla-interacciones th {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 9px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-interacciones th {
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

    /* Estilos para estados */
    .estado-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }

    .estado-programado { background-color: #17a2b8; }
    .estado-realizado { background-color: #28a745; }
    .estado-reagendado { background-color: #ffc107; color: #000; }
    .estado-cancelado { background-color: #dc3545; }

    /* Estilos para resultados */
    .resultado-badge {
        padding: 2px 6px;
        border-radius: 6px;
        font-size: 8px;
        font-weight: 500;
    }

    .resultado-exitoso { background-color: #d4edda; color: #155724; }
    .resultado-sin_respuesta { background-color: #fff3cd; color: #856404; }
    .resultado-reagendar { background-color: #d1ecf1; color: #0c5460; }
    .resultado-no_interesado { background-color: #f8d7da; color: #721c24; }
    .resultado-convertido { background-color: #d4edda; color: #155724; font-weight: bold; }

    /* Estilos para tipo de interacción */
    .tipo-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 600;
        color: white;
        display: inline-block;
    }

    /* Estilos para fechas */
    .fecha-info {
        font-size: 9px;
    }

    .fecha-vencida {
        color: #dc3545;
        font-weight: bold;
    }

    .fecha-hoy {
        color: #fd7e14;
        font-weight: bold;
    }

    .fecha-mañana {
        color: #17a2b8;
        font-weight: bold;
    }

    /* Estilos para información */
    .interaccion-asunto {
        font-weight: bold;
        color: #2c3e50;
        font-size: 9px;
    }

    .interaccion-descripcion {
        font-size: 8px;
        color: #6c757d;
        font-style: italic;
    }

    .contacto-principal {
        font-weight: 600;
        color: #2c3e50;
        font-size: 9px;
    }

    .contacto-secundario {
        font-size: 8px;
        color: #6c757d;
    }

    .usuario-responsable {
        font-size: 8px;
        color: #6c757d;
        font-style: italic;
    }

    .seguimiento-badge {
        background-color: #fff3cd;
        color: #856404;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .seguimiento-vencido {
        background-color: #f8d7da;
        color: #721c24;
    }

    /* Anchos de columnas */
    .col-id { width: 4%; text-align: center; }
    .col-interaccion { width: 15%; }
    .col-tipo { width: 8%; }
    .col-contacto { width: 13%; }
    .col-fecha { width: 10%; }
    .col-estado { width: 8%; }
    .col-resultado { width: 8%; }
    .col-duracion { width: 7%; text-align: center; }
    .col-seguimiento { width: 8%; }
    .col-responsable { width: 10%; }
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
                <h4>REPORTE DE INTERACCIONES</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas de resumen**
$totalInteracciones = count($filteredData);
$programadas = 0;
$realizadas = 0;
$reagendadas = 0;
$canceladas = 0;
$exitosas = 0;
$vencidas = 0;

foreach ($filteredData as $row) {
    $estado = strtolower(trim($row[5])); // columna estado
    $resultado = strtolower(trim($row[6])); // columna resultado
    
    if ($estado == 'programado') $programadas++;
    if ($estado == 'realizado') $realizadas++;
    if ($estado == 'reagendado') $reagendadas++;
    if ($estado == 'cancelado') $canceladas++;
    
    if (strpos($resultado, 'exitoso') !== false) $exitosas++;
}

$html .= '<div class="estadisticas-resumen">
    <strong>RESUMEN ESTADÍSTICO:</strong> 
    Total: ' . $totalInteracciones . ' interacciones | 
    Programadas: ' . $programadas . ' | 
    Realizadas: ' . $realizadas . ' | 
    Reagendadas: ' . $reagendadas . ' | 
    Canceladas: ' . $canceladas . ' | 
    Exitosas: ' . $exitosas . '
</div>';

// **Sección de listado de interacciones**
$html .= '<div class="seccion-titulo">LISTADO DETALLADO DE INTERACCIONES</div>';
$html .= '<table id="tabla-interacciones">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-interaccion">Interacción</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-contacto">Contacto</th>
            <th class="col-fecha">Fecha/Hora</th>
            <th class="col-estado">Estado</th>
            <th class="col-resultado">Resultado</th>
            <th class="col-duracion">Duración</th>
            <th class="col-seguimiento">Seguimiento</th>
            <th class="col-responsable">Responsable</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Procesar datos
    $id = htmlspecialchars($row[0]);
    
    // Interacción - extraer solo texto
    $interaccion = strip_tags($row[1]);
    $asunto = '';
    $descripcion = '';
    
    // Intentar separar asunto de descripción
    if (preg_match('/(.*?)(?:Sin descripción)?$/s', $interaccion, $matches)) {
        $asunto = trim($matches[1]);
        $descripcion = (strpos($interaccion, 'Sin descripción') !== false) ? 'Sin descripción' : '';
    } else {
        $asunto = $interaccion;
    }
    
    // Limitar longitud
    if (strlen($asunto) > 50) {
        $asunto = substr($asunto, 0, 47) . '...';
    }
    
    // Tipo - extraer solo el nombre
    $tipo = strip_tags($row[2]);
    
    // Contacto - extraer texto limpio
    $contacto = strip_tags($row[3]);
    
    // Fecha
    $fecha = strip_tags($row[4]);
    $fecha_class = '';
    
    if (stripos($fecha, 'HOY') !== false) {
        $fecha_class = 'fecha-hoy';
    } elseif (stripos($fecha, 'MAÑANA') !== false) {
        $fecha_class = 'fecha-mañana';
    } elseif (stripos($fecha, 'class="fecha-vencida"') !== false) {
        $fecha_class = 'fecha-vencida';
    }
    
    // Estado
    $estado = strip_tags($row[5]);
    $estadoLower = strtolower(trim($estado));
    $estadoClass = 'estado-' . $estadoLower;
    
    // Resultado
    $resultado = strip_tags($row[6]);
    $resultadoLower = str_replace(' ', '_', strtolower(trim($resultado)));
    $resultadoClass = 'resultado-' . $resultadoLower;
    if ($resultado == 'Pendiente') {
        $resultado = '-';
        $resultadoClass = '';
    }
    
    // Duración
    $duracion = strip_tags($row[7]);
    
    // Seguimiento
    $seguimiento = strip_tags($row[8]);
    $seguimientoClass = '';
    if (stripos($seguimiento, 'Pendiente') !== false) {
        $seguimientoClass = 'seguimiento-vencido';
    } elseif ($seguimiento != 'No requerido') {
        $seguimientoClass = 'seguimiento-badge';
    }
    
    // Responsable
    $responsable = strip_tags($row[9]);
    
    $html .= '<tr>
        <td class="col-id">' . $id . '</td>
        <td class="col-interaccion">
            <div class="interaccion-asunto">' . htmlspecialchars($asunto) . '</div>
            ' . ($descripcion ? '<div class="interaccion-descripcion">' . htmlspecialchars($descripcion) . '</div>' : '') . '
        </td>
        <td class="col-tipo">' . htmlspecialchars($tipo) . '</td>
        <td class="col-contacto">
            <div class="contacto-principal">' . htmlspecialchars($contacto) . '</div>
        </td>
        <td class="col-fecha">
            <div class="fecha-info ' . $fecha_class . '">' . htmlspecialchars($fecha) . '</div>
        </td>
        <td class="col-estado">
            <span class="estado-badge ' . $estadoClass . '">' . htmlspecialchars(ucfirst($estado)) . '</span>
        </td>
        <td class="col-resultado">
            ' . ($resultadoClass ? '<span class="resultado-badge ' . $resultadoClass . '">' . htmlspecialchars($resultado) . '</span>' : htmlspecialchars($resultado)) . '
        </td>
        <td class="col-duracion">' . htmlspecialchars($duracion) . '</td>
        <td class="col-seguimiento">
            ' . ($seguimientoClass ? '<span class="' . $seguimientoClass . '">' . htmlspecialchars($seguimiento) . '</span>' : htmlspecialchars($seguimiento)) . '
        </td>
        <td class="col-responsable">
            <div class="usuario-responsable">' . htmlspecialchars($responsable) . '</div>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $totalInteracciones . ' interacciones
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
$filename = 'Reporte_Interacciones_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>