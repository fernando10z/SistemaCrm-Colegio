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
    die("<script>window.alert('No hay registros disponibles para generar el reporte de leads.');</script>");
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

    #tabla-cabecera, #tabla-leads {
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

    #tabla-leads td, #tabla-leads th {
        border: 0.5px solid #333;
        padding: 7px;
        font-size: 10px;
        text-align: left;
        vertical-align: middle;
    }

    #tabla-leads th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .badge-prioridad {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }

    .prioridad-baja { 
        background-color: #a8e6cf; 
        color: #155724;
    }

    .prioridad-media { 
        background-color: #ffeaa7; 
        color: #856404;
    }

    .prioridad-alta { 
        background-color: #ffcca8; 
        color: #721c24;
    }

    .prioridad-urgente { 
        background-color: #ff9999; 
        color: #721c24;
        font-weight: 900;
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

    .badge-estado {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }

    .estado-nuevo { background-color: #17a2b8; }
    .estado-contactado { background-color: #007bff; }
    .estado-interesado { background-color: #28a745; }
    .estado-matriculado { background-color: #28a745; }
    .estado-perdido { background-color: #dc3545; }
    .estado-default { background-color: #6c757d; }

    .interes-container {
        text-align: center;
        padding: 3px 0;
    }

    .interes-barras {
        font-size: 14px;
        letter-spacing: 1px;
        color: #ffc107;
        font-weight: bold;
    }

    .interes-puntaje {
        display: block;
        font-size: 9px;
        color: #666;
        margin-top: 2px;
        font-weight: bold;
    }

    .telefono-info {
        font-family: "Courier New", monospace;
        font-size: 9px;
        color: #495057;
    }

    .codigo-lead {
        font-family: "Courier New", monospace;
        font-size: 9px;
        font-weight: bold;
        color: #007bff;
    }

    .estudiante-info {
        font-size: 10px;
    }

    .estudiante-nombre {
        font-weight: bold;
        color: #495057;
    }

    .col-id { width: 4%; }
    .col-codigo { width: 7%; }
    .col-estudiante { width: 15%; }
    .col-contacto { width: 13%; }
    .col-telefono { width: 10%; }
    .col-grado { width: 8%; }
    .col-canal { width: 9%; }
    .col-estado { width: 9%; }
    .col-interes { width: 10%; }
    .col-responsable { width: 10%; }
    .col-fecha { width: 5%; }
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
                <h4>REPORTE DE LEADS FILTRADOS</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Función para generar barras de interés** (más compatible que estrellas)
function generarBarrasInteres($puntaje) {
    // Convertir puntaje de 0-100 a escala de 0-5 barras
    $barras = round(($puntaje / 100) * 5);
    $html = '';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $barras) {
            $html .= '&#9632;'; // Cuadrado lleno (■)
        } else {
            $html .= '&#9634;'; // Cuadrado vacío (▢)
        }
    }
    
    return $html;
}

// **Sección de listado de leads**
$html .= '<div class="seccion-titulo">LISTADO DETALLADO DE LEADS</div>';
$html .= '<table id="tabla-leads">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-codigo">Código</th>
            <th class="col-estudiante">Estudiante</th>
            <th class="col-contacto">Contacto</th>
            <th class="col-telefono">Grado</th>
            <th class="col-grado">Canal</th>
            <th class="col-canal">Estado</th>
            <th class="col-estado">Prioridad</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Extraer datos del array
    $id = htmlspecialchars($row[0] ?? '');
    $codigo = htmlspecialchars($row[1] ?? '');
    $estudiante = htmlspecialchars($row[2] ?? '');
    $contacto = htmlspecialchars($row[3] ?? '');
    $telefono = htmlspecialchars($row[4] ?? '');
    $grado = htmlspecialchars($row[5] ?? '');
    $canal = htmlspecialchars($row[6] ?? '');
    $estado = htmlspecialchars($row[7] ?? '');
    
    // INTERÉS: Extraer puntaje numérico
    $interes_raw = $row[9] ?? 0;
    
    // Si viene como HTML, extraer el puntaje
    if (is_string($interes_raw) && (strpos($interes_raw, 'estrella') !== false || strpos($interes_raw, 'star') !== false)) {
        preg_match_all('/estrella-activa|star-active/i', $interes_raw, $matches);
        $puntaje_interes = count($matches[0]) * 20;
    } else {
        $puntaje_interes = intval($interes_raw);
    }
    
    $responsable = htmlspecialchars($row[10] ?? '');
    $fecha_registro = htmlspecialchars($row[12] ?? '');
    
    // Generar barras de interés
    $barras_html = generarBarrasInteres($puntaje_interes);
    
    // Determinar clase de estado
    $estado_lower = strtolower($estado);
    $estadoClass = 'estado-default';
    if (strpos($estado_lower, 'BAJA') !== false) $estadoClass = 'estado-nuevo';
    elseif (strpos($estado_lower, 'ALTA') !== false) $estadoClass = 'estado-contactado';
    elseif (strpos($estado_lower, 'URGENTE') !== false) $estadoClass = 'estado-interesado';
    elseif (strpos($estado_lower, 'MEDIA') !== false) $estadoClass = 'estado-matriculado';
    elseif (strpos($estado_lower, 'perdido') !== false || strpos($estado_lower, 'rechazado') !== false) $estadoClass = 'estado-perdido';
    
    // NUEVA SECCIÓN: Determinar clase de prioridad
    $prioridad_lower = strtolower($estado);
    $prioridadClass = 'prioridad-media'; // Default
    if (strpos($prioridad_lower, 'BAJA') !== false) {
        $prioridadClass = 'prioridad-baja';
    } elseif (strpos($prioridad_lower, 'ALTA') !== false) {
        $prioridadClass = 'prioridad-alta';
    } elseif (strpos($prioridad_lower, 'URGENTE') !== false) {
        $prioridadClass = 'prioridad-urgente';
    } elseif (strpos($prioridad_lower, 'MEDIA') !== false) {
        $prioridadClass = 'prioridad-media';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align:center;">' . $id . '</td>
        <td class="col-codigo" style="text-align:center;">
            <span class="codigo-lead">' . $codigo . '</span>
        </td>
        <td class="col-estudiante">
            <div class="estudiante-info">
                <div class="estudiante-nombre">' . $estudiante . '</div>
            </div>
        </td>
        <td class="col-contacto" style="font-size: 9px;">' . $contacto . '</td>
        <td class="col-telefono" style="text-align:center;">
            <span class="telefono-info">' . $telefono . '</span>
        </td>
        <td class="col-grado" style="text-align:center; font-size:9px;">' . $grado . '</td>
        <td class="col-canal" style="text-align:center; font-size:9px;">' . $canal . '</td>
        <td class="col-estado" style="text-align:center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper($estado) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . count($filteredData) . '
</div>';

// **Configurar DomPDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// **Enviar PDF al navegador**
$filename = 'Reporte_Leads_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>