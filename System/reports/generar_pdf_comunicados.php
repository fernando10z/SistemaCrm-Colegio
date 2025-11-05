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
    die("<script>window.alert('No hay registros disponibles para generar el reporte de comunicados.'); window.close();</script>");
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

    #tabla-cabecera, #tabla-comunicados {
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

    #tabla-comunicados td, #tabla-comunicados th {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 8px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-comunicados th {
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

    /* Estilos para estados */
    .estado-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 500;
        color: white;
        display: inline-block;
    }

    .estado-pendiente { background-color: #6c757d; }
    .estado-enviado { background-color: #007bff; }
    .estado-entregado { background-color: #28a745; }
    .estado-leido { background-color: #17a2b8; }
    .estado-fallido { background-color: #dc3545; }

    /* Estilos para destinatario */
    .destinatario-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: 500;
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
    }

    /* Estilos para información */
    .mensaje-asunto {
        font-weight: bold;
        color: #2c3e50;
        font-size: 9px;
        margin-bottom: 2px;
    }

    .mensaje-preview {
        font-size: 7px;
        color: #6c757d;
        font-style: italic;
        line-height: 1.3;
    }

    .destinatario-nombre {
        font-weight: 500;
        color: #495057;
        font-size: 8px;
    }

    .destinatario-contacto {
        font-family: "Courier New", monospace;
        font-size: 7px;
        color: #6c757d;
    }

    .plantilla-info {
        font-size: 7px;
        color: #6c757d;
        background-color: #f8f9fa;
        padding: 1px 3px;
        border-radius: 3px;
    }

    .fecha-envio {
        font-size: 7px;
        color: #6c757d;
    }

    .costo-mensaje {
        font-family: "Courier New", monospace;
        font-size: 7px;
        font-weight: bold;
        color: #28a745;
    }

    /* Anchos de columnas (sin Error) */
    .col-id { width: 5%; text-align: center; }
    .col-tipo { width: 8%; text-align: center; }
    .col-mensaje { width: 22%; }
    .col-destinatario { width: 18%; }
    .col-plantilla { width: 10%; }
    .col-estado { width: 9%; text-align: center; }
    .col-fecha-envio { width: 12%; }
    .col-entrega { width: 10%; }
    .col-costo { width: 6%; text-align: right; }
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
                <h4>REPORTE DE COMUNICADOS</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas de resumen**
$totalComunicados = count($filteredData);
$totalEmails = 0;
$totalWhatsapp = 0;
$totalSms = 0;
$pendientes = 0;
$enviados = 0;
$entregados = 0;
$leidos = 0;
$fallidos = 0;
$costoTotal = 0;

foreach ($filteredData as $row) {
    $tipo = strtolower(trim(strip_tags($row[1])));
    $estado = strtolower(trim(strip_tags($row[5])));
    $costo = strip_tags($row[8]);
    
    // Contar por tipo
    if (strpos($tipo, 'email') !== false) $totalEmails++;
    if (strpos($tipo, 'whatsapp') !== false) $totalWhatsapp++;
    if (strpos($tipo, 'sms') !== false) $totalSms++;
    
    // Contar por estado
    if ($estado == 'pendiente') $pendientes++;
    if ($estado == 'enviado') $enviados++;
    if ($estado == 'entregado') $entregados++;
    if ($estado == 'leído' || $estado == 'leido') $leidos++;
    if ($estado == 'fallido') $fallidos++;
    
    // Sumar costos
    if (strpos($costo, 'S/') !== false) {
        $costoNumerico = floatval(str_replace(['S/', ','], ['', '.'], $costo));
        $costoTotal += $costoNumerico;
    }
}

$html .= '<div class="estadisticas-resumen">
    <strong>RESUMEN ESTADÍSTICO:</strong> 
    Total: ' . $totalComunicados . ' mensajes | 
    Emails: ' . $totalEmails . ' | 
    WhatsApp: ' . $totalWhatsapp . ' | 
    SMS: ' . $totalSms . ' | 
    Pendientes: ' . $pendientes . ' | 
    Enviados: ' . $enviados . ' | 
    Entregados: ' . $entregados . ' | 
    Leídos: ' . $leidos . ' | 
    Fallidos: ' . $fallidos . ' | 
    Costo Total: S/ ' . number_format($costoTotal, 4) . '
</div>';

// **Sección de listado de comunicados**
$html .= '<div class="seccion-titulo">LISTADO DETALLADO DE COMUNICADOS ENVIADOS</div>';
$html .= '<table id="tabla-comunicados">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-mensaje">Mensaje</th>
            <th class="col-destinatario">Destinatario</th>
            <th class="col-plantilla">Plantilla</th>
            <th class="col-estado">Estado</th>
            <th class="col-fecha-envio">Fecha Envío</th>
            <th class="col-entrega">Entrega</th>
            <th class="col-costo">Costo</th>
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
    
    // Mensaje - extraer asunto y preview del HTML
    $mensajeHTML = $row[2];
    $asunto = '';
    $preview = '';
    
    // Extraer el asunto (dentro de span.mensaje-asunto)
    if (preg_match('/<span class=["\']mensaje-asunto["\']>(.*?)<\/span>/s', $mensajeHTML, $matches)) {
        $asunto = trim(strip_tags($matches[1]));
    }
    
    // Extraer el preview (dentro de span.mensaje-preview)
    if (preg_match('/<span class=["\']mensaje-preview["\']>(.*?)<\/span>/s', $mensajeHTML, $matches)) {
        $preview = trim(strip_tags($matches[1]));
        // Eliminar los puntos suspensivos finales si existen
        $preview = rtrim($preview, '.');
    }
    
    // Si no se encontró asunto mediante regex, intentar extraer todo el texto
    if (empty($asunto)) {
        $mensajeTexto = strip_tags($mensajeHTML);
        $lineas = array_filter(array_map('trim', explode("\n", $mensajeTexto)));
        if (!empty($lineas)) {
            $asunto = $lineas[0];
            if (count($lineas) > 1) {
                $preview = $lineas[1];
            }
        }
    }
    
    // Si aún no hay asunto, usar texto genérico
    if (empty($asunto)) {
        $asunto = 'Sin asunto';
    }
    
    // Limitar longitud del asunto
    if (strlen($asunto) > 50) {
        $asunto = substr($asunto, 0, 47) . '...';
    }
    
    // Limitar longitud del preview
    if (strlen($preview) > 80) {
        $preview = substr($preview, 0, 77) . '...';
    }
    
    // Destinatario
    $destinatarioHTML = $row[3];
    $tipoDestinatario = '';
    $nombreDestinatario = '';
    $contactoDestinatario = '';
    
    // Extraer tipo destinatario (dentro de span.badge-destinatario)
    if (preg_match('/<span class=["\']badge badge-destinatario["\']>(.*?)<\/span>/s', $destinatarioHTML, $matches)) {
        $tipoDestinatario = trim(strip_tags($matches[1]));
    }
    
    // Extraer nombre destinatario (dentro de span.destinatario-nombre)
    if (preg_match('/<span class=["\']destinatario-nombre["\']>(.*?)<\/span>/s', $destinatarioHTML, $matches)) {
        $nombreDestinatario = trim(strip_tags($matches[1]));
    }
    
    // Extraer contacto destinatario (dentro de span.destinatario-contacto)
    if (preg_match('/<span class=["\']destinatario-contacto["\']>(.*?)<\/span>/s', $destinatarioHTML, $matches)) {
        $contactoDestinatario = trim(strip_tags($matches[1]));
    }
    
    // Si no se encontró nombre, extraer todo el texto
    if (empty($nombreDestinatario)) {
        $destinatarioTexto = strip_tags($destinatarioHTML);
        $lineas = array_filter(array_map('trim', explode("\n", $destinatarioTexto)));
        foreach ($lineas as $linea) {
            if (!in_array($linea, ['Lead', 'Apoderado', 'Directo']) && empty($nombreDestinatario)) {
                $nombreDestinatario = $linea;
            }
        }
    }
    
    if (empty($nombreDestinatario)) {
        $nombreDestinatario = 'No especificado';
    }
    
    // Limitar longitud
    if (strlen($nombreDestinatario) > 35) {
        $nombreDestinatario = substr($nombreDestinatario, 0, 32) . '...';
    }
    
    if (strlen($contactoDestinatario) > 30) {
        $contactoDestinatario = substr($contactoDestinatario, 0, 27) . '...';
    }
    
    // Plantilla
    $plantilla = strip_tags($row[4]);
    
    // Estado
    $estado = strip_tags($row[5]);
    $estadoLower = strtolower(trim($estado));
    $estadoClass = 'estado-' . $estadoLower;
    
    // Fechas
    $fechaEnvio = strip_tags($row[6]);
    $fechaEntrega = strip_tags($row[7]);
    
    // Costo
    $costo = strip_tags($row[8]);
    
    $html .= '<tr>
        <td class="col-id">' . $id . '</td>
        <td class="col-tipo">
            ' . ($tipoClass ? '<span class="tipo-badge ' . $tipoClass . '">' . htmlspecialchars($tipo) . '</span>' : htmlspecialchars($tipo)) . '
        </td>
        <td class="col-mensaje">
            <div class="mensaje-asunto">' . htmlspecialchars($asunto) . '</div>
            ' . ($preview ? '<div class="mensaje-preview">' . htmlspecialchars($preview) . '</div>' : '') . '
        </td>
        <td class="col-destinatario">
            ' . ($tipoDestinatario ? '<span class="destinatario-badge">' . htmlspecialchars($tipoDestinatario) . '</span><br>' : '') . '
            <span class="destinatario-nombre">' . htmlspecialchars($nombreDestinatario) . '</span>
            ' . ($contactoDestinatario ? '<br><span class="destinatario-contacto">' . htmlspecialchars($contactoDestinatario) . '</span>' : '') . '
        </td>
        <td class="col-plantilla">
            ' . ($plantilla != 'Manual' && $plantilla != '-' ? '<span class="plantilla-info">' . htmlspecialchars($plantilla) . '</span>' : '<span style="font-size:7px;color:#999;">Manual</span>') . '
        </td>
        <td class="col-estado">
            <span class="estado-badge ' . $estadoClass . '">' . htmlspecialchars(ucfirst($estado)) . '</span>
        </td>
        <td class="col-fecha-envio">
            <span class="fecha-envio">' . htmlspecialchars($fechaEnvio) . '</span>
        </td>
        <td class="col-entrega">
            <span class="fecha-envio">' . htmlspecialchars($fechaEntrega) . '</span>
        </td>
        <td class="col-costo">
            ' . ($costo != 'Gratis' && $costo != '-' ? '<span class="costo-mensaje">' . htmlspecialchars($costo) . '</span>' : '<span style="font-size:7px;color:#999;">Gratis</span>') . '
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $totalComunicados . ' comunicados | 
    <strong>Costo total:</strong> S/ ' . number_format($costoTotal, 4) . '
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
$filename = 'Reporte_Comunicados_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>