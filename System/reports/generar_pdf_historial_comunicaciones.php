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
    // Si no existe, usar placeholder
    $imagenBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#007bff"/><text x="50%" y="50%" fill="white" text-anchor="middle" dy=".3em" font-size="20">LOGO</text></svg>');
}

// Obtener rol del usuario desde sesión
session_start();
$rolUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : "Desconocido";
$roles = [1 => "Administrador", 2 => "Coordinador Marketing", 3 => "Tutor", 4 => "Finanzas"];
$nombreRol = isset($roles[$rolUsuario]) ? $roles[$rolUsuario] : "Usuario del Sistema";

// Obtener los datos filtrados desde la tabla
$datosHistorial = isset($_POST['datosHistorial']) ? json_decode($_POST['datosHistorial'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosHistorial)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de comunicaciones.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosHistorial);
$tipos = ['EMAIL' => 0, 'WHATSAPP' => 0, 'SMS' => 0];
$estados = ['Enviado' => 0, 'Entregado' => 0, 'Leído' => 0, 'Fallido' => 0, 'Pendiente' => 0];
$costo_total = 0.0;

foreach ($datosHistorial as $row) {
    $tipo = strtoupper(trim($row[1]));
    if (isset($tipos[$tipo])) {
        $tipos[$tipo]++;
    }
    
    $estado = ucfirst(trim($row[5]));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Extraer costo (columna 8)
    $costo_str = trim($row[8]);
    if (preg_match('/[\d,.]+/', $costo_str, $matches)) {
        $costo_valor = floatval(str_replace(',', '.', str_replace(['S/', ' '], '', $matches[0])));
        $costo_total += $costo_valor;
    }
}

$tasa_entrega = $total_registros > 0 ? number_format(($estados['Entregado'] + $estados['Leído']) / $total_registros * 100, 1) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-comunicaciones {
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

    #tabla-comunicaciones td, #tabla-comunicaciones th {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 9px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-comunicaciones th {
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
        border: 1px solid #666;
    }

    .estadisticas-resumen table {
        width: 100%;
        border-collapse: collapse;
    }

    .estadisticas-resumen td {
        padding: 5px;
        font-size: 10px;
        text-align: center;
        border-right: 1px solid #999;
    }

    .estadisticas-resumen td:last-child {
        border-right: none;
    }

    .stat-numero {
        font-size: 14px;
        font-weight: bold;
        color: #667eea;
        display: block;
    }

    .stat-label {
        font-size: 8px;
        color: #666;
    }

    .badge-tipo {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: bold;
        color: white;
    }

    .tipo-email { background-color: #dc3545; }
    .tipo-whatsapp { background-color: #25d366; }
    .tipo-sms { background-color: #007bff; }

    .badge-estado {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: bold;
        color: white;
    }

    .estado-pendiente { background-color: #6c757d; }
    .estado-enviado { background-color: #007bff; }
    .estado-entregado { background-color: #28a745; }
    .estado-leído { background-color: #17a2b8; }
    .estado-leido { background-color: #17a2b8; }
    .estado-fallido { background-color: #dc3545; }

    .mensaje-truncado {
        font-size: 8px;
        color: #6c757d;
        max-height: 30px;
        overflow: hidden;
    }

    .destinatario-info {
        font-size: 9px;
        color: #495057;
    }

    .destinatario-tipo {
        font-size: 7px;
        padding: 1px 4px;
        border-radius: 4px;
        background-color: #e3f2fd;
        color: #1565c0;
        font-weight: bold;
    }

    .costo-texto {
        font-family: "Courier New", monospace;
        font-size: 8px;
        color: #28a745;
        font-weight: bold;
    }

    .tiempo-entrega {
        font-size: 8px;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: bold;
    }

    .entrega-rapida {
        background-color: #d4edda;
        color: #155724;
    }

    .entrega-normal {
        background-color: #fff3cd;
        color: #856404;
    }

    .entrega-lenta {
        background-color: #f8d7da;
        color: #721c24;
    }

    .col-id { width: 3%; }
    .col-tipo { width: 7%; }
    .col-mensaje { width: 20%; }
    .col-destinatario { width: 18%; }
    .col-plantilla { width: 10%; }
    .col-estado { width: 8%; }
    .col-fecha { width: 12%; }
    .col-tiempo { width: 8%; }
    .col-costo { width: 7%; }
    .col-observaciones { width: 7%; }
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
                <h4>HISTORIAL DE COMUNICACIONES</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Resumidas**
$html .= '<div class="estadisticas-resumen">
    <table>
        <tr>
            <td>
                <span class="stat-numero">' . $total_registros . '</span>
                <span class="stat-label">Total Mensajes</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $tipos['EMAIL'] . '</span>
                <span class="stat-label">Email</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #25d366;">' . $tipos['WHATSAPP'] . '</span>
                <span class="stat-label">WhatsApp</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #007bff;">' . $tipos['SMS'] . '</span>
                <span class="stat-label">SMS</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">S/ ' . number_format($costo_total, 2) . '</span>
                <span class="stat-label">Costo Total</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $tasa_entrega . '%</span>
                <span class="stat-label">Tasa Entrega</span>
            </td>
        </tr>
    </table>
</div>';

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE MENSAJES ENVIADOS</div>';
$html .= '<table id="tabla-comunicaciones">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-mensaje">Mensaje</th>
            <th class="col-destinatario">Destinatario</th>
            <th class="col-plantilla">Plantilla</th>
            <th class="col-estado">Estado</th>
            <th class="col-fecha">Fecha Envío</th>
            <th class="col-tiempo">T. Entrega</th>
            <th class="col-costo">Costo</th>
            <th class="col-observaciones">Observaciones</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosHistorial as $row) {
    // Determinar clase de tipo
    $tipo = strtolower(trim($row[1]));
    $tipoClass = 'tipo-' . $tipo;
    
    // Determinar clase de estado
    $estado = strtolower(trim($row[5]));
    $estadoClass = 'estado-' . $estado;
    
    // Clasificar tiempo de entrega
    $tiempoEntrega = trim($row[7]);
    $tiempoClass = '';
    if ($tiempoEntrega != '-' && !empty($tiempoEntrega)) {
        if (strpos($tiempoEntrega, 'min') !== false) {
            $minutos = (int)filter_var($tiempoEntrega, FILTER_SANITIZE_NUMBER_INT);
            if ($minutos < 5) $tiempoClass = 'entrega-rapida';
            elseif ($minutos < 30) $tiempoClass = 'entrega-normal';
            else $tiempoClass = 'entrega-lenta';
        } else {
            $tiempoClass = 'entrega-lenta';
        }
    }
    
    // Truncar mensaje
    $mensaje = $row[2];
    if (strlen($mensaje) > 100) {
        $mensaje = substr($mensaje, 0, 97) . '...';
    }
    
    // Truncar destinatario
    $destinatario = $row[3];
    if (strlen($destinatario) > 80) {
        $destinatario = substr($destinatario, 0, 77) . '...';
    }
    
    // Extraer tipo de destinatario
    $tipoDestinatario = 'Directo';
    if (strpos($destinatario, 'Lead') !== false) $tipoDestinatario = 'Lead';
    elseif (strpos($destinatario, 'Apoderado') !== false) $tipoDestinatario = 'Apoderado';
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-tipo" style="text-align: center;">
            <span class="badge-tipo ' . $tipoClass . '">' . strtoupper(htmlspecialchars($row[1])) . '</span>
        </td>
        <td class="col-mensaje">
            <div class="mensaje-truncado">' . htmlspecialchars($mensaje) . '</div>
        </td>
        <td class="col-destinatario">
            <span class="destinatario-tipo">' . $tipoDestinatario . '</span><br>
            <div class="destinatario-info">' . htmlspecialchars($destinatario) . '</div>
        </td>
        <td class="col-plantilla" style="font-size: 8px;">' . htmlspecialchars($row[4]) . '</td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars($row[5])) . '</span>
        </td>
        <td class="col-fecha" style="font-size: 8px;">' . htmlspecialchars($row[6]) . '</td>
        <td class="col-tiempo" style="text-align: center;">' . 
            ($tiempoClass ? '<span class="tiempo-entrega ' . $tiempoClass . '">' . htmlspecialchars($tiempoEntrega) . '</span>' : 
            htmlspecialchars($tiempoEntrega)) . 
        '</td>
        <td class="col-costo" style="text-align: right;">
            <span class="costo-texto">' . htmlspecialchars($row[8]) . '</span>
        </td>
        <td class="col-observaciones" style="font-size: 7px; color: #6c757d;">' . htmlspecialchars(substr($row[9], 0, 40)) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Entregados:</strong> ' . $estados['Entregado'] . ' | 
    <strong>Leídos:</strong> ' . $estados['Leído'] . ' | 
    <strong>Fallidos:</strong> ' . $estados['Fallido'] . '
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
$filename = 'Reporte_Historial_Comunicaciones_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>