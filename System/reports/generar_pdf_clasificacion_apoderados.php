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
$datosClasificacion = isset($_POST['datosClasificacion']) ? json_decode($_POST['datosClasificacion'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosClasificacion)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de clasificación.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosClasificacion);
$categorias = [];
$compromisos = ['Alto' => 0, 'Medio' => 0, 'Bajo' => 0];
$participaciones = ['Muy activo' => 0, 'Activo' => 0, 'Poco activo' => 0, 'Inactivo' => 0];
$puntuacion_total = 0;
$sin_interaccion = 0;

foreach ($datosClasificacion as $row) {
    // Contar categorías (columna 3)
    $categoria = trim($row[3]);
    if (!isset($categorias[$categoria])) {
        $categorias[$categoria] = 0;
    }
    $categorias[$categoria]++;
    
    // Contar compromisos (columna 4)
    $compromiso = ucfirst(trim($row[4]));
    if (isset($compromisos[$compromiso])) {
        $compromisos[$compromiso]++;
    }
    
    // Contar participaciones (columna 5)
    $participacion = ucfirst(trim(str_replace('_', ' ', $row[5])));
    if (isset($participaciones[$participacion])) {
        $participaciones[$participacion]++;
    }
    
    // Sumar puntuaciones (columna 6)
    $puntuacion = floatval(str_replace('%', '', trim($row[6])));
    $puntuacion_total += $puntuacion;
    
    // Contar sin interacción (columna 8)
    if (stripos($row[8], 'Sin interacciones') !== false) {
        $sin_interaccion++;
    }
}

$puntuacion_promedio = $total_registros > 0 ? number_format($puntuacion_total / $total_registros, 1) : 0;

// Obtener top 3 categorías
arsort($categorias);
$top_categorias = array_slice($categorias, 0, 3, true);

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-clasificacion {
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

    #tabla-clasificacion td, #tabla-clasificacion th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-clasificacion th {
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
        font-size: 8px;
        text-align: center;
        border-right: 1px solid #999;
    }

    .estadisticas-resumen td:last-child {
        border-right: none;
    }

    .stat-numero {
        font-size: 12px;
        font-weight: bold;
        color: #667eea;
        display: block;
    }

    .stat-label {
        font-size: 7px;
        color: #666;
    }

    .badge-categoria {
        padding: 2px 5px;
        border-radius: 8px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .categoria-colaborador-estrella { background-color: #28a745; }
    .categoria-comprometido { background-color: #17a2b8; }
    .categoria-muy-participativo { background-color: #6f42c1; }
    .categoria-regular { background-color: #6c757d; }
    .categoria-bajo-compromiso { background-color: #fd7e14; }
    .categoria-inactivo { background-color: #ffc107; color: #856404; }
    .categoria-problematico { background-color: #dc3545; }

    .badge-compromiso {
        padding: 2px 4px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
    }

    .compromiso-alto { background-color: #28a745; color: white; }
    .compromiso-medio { background-color: #ffc107; color: #856404; }
    .compromiso-bajo { background-color: #dc3545; color: white; }

    .badge-participacion {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 6px;
        font-weight: bold;
    }

    .participacion-muy-activo { background-color: #d4edda; color: #155724; }
    .participacion-activo { background-color: #d1ecf1; color: #0c5460; }
    .participacion-poco-activo { background-color: #fff3cd; color: #856404; }
    .participacion-inactivo { background-color: #f8d7da; color: #721c24; }

    .puntuacion-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
        text-align: center;
    }

    .score-excelente { background-color: #28a745; color: white; }
    .score-bueno { background-color: #17a2b8; color: white; }
    .score-regular { background-color: #ffc107; color: #856404; }
    .score-bajo { background-color: #dc3545; color: white; }

    .metricas-texto {
        font-size: 6px;
        color: #495057;
    }

    .ultima-interaccion {
        font-size: 6px;
        padding: 2px 3px;
        border-radius: 3px;
        font-weight: bold;
    }

    .interaccion-reciente { background-color: #d4edda; color: #155724; }
    .interaccion-antigua { background-color: #fff3cd; color: #856404; }
    .interaccion-muy-antigua { background-color: #f8d7da; color: #721c24; }
    .sin-interaccion { background-color: #f8f9fa; color: #6c757d; }

    .col-id { width: 3%; }
    .col-apoderado { width: 14%; }
    .col-familia { width: 9%; }
    .col-categoria { width: 11%; }
    .col-compromiso { width: 8%; }
    .col-participacion { width: 9%; }
    .col-puntuacion { width: 7%; }
    .col-metricas { width: 12%; }
    .col-ultima { width: 9%; }
    .col-contacto { width: 8%; }

    .categorias-resumen {
        background-color: #f8f9fa;
        padding: 8px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        font-size: 8px;
        text-align: center;
    }
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
                <h4>CLASIFICACIÓN Y SEGMENTACIÓN</h4>
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
                <span class="stat-label">Total Apoderados</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $compromisos['Alto'] . '</span>
                <span class="stat-label">Alto Compromiso</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #ffc107;">' . $compromisos['Medio'] . '</span>
                <span class="stat-label">Medio Compromiso</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $compromisos['Bajo'] . '</span>
                <span class="stat-label">Bajo Compromiso</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . $participaciones['Muy activo'] . '</span>
                <span class="stat-label">Muy Activos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #6c757d;">' . $participaciones['Inactivo'] . '</span>
                <span class="stat-label">Inactivos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $puntuacion_promedio . '%</span>
                <span class="stat-label">Puntuación Promedio</span>
            </td>
        </tr>
    </table>
</div>';

// **Categorías más frecuentes**
$categorias_html = '<div class="categorias-resumen"><strong>Top Categorías:</strong> ';
foreach ($top_categorias as $cat => $cant) {
    $categorias_html .= htmlspecialchars($cat) . ' (' . $cant . ') | ';
}
$categorias_html = rtrim($categorias_html, ' | ');
$categorias_html .= ' | <strong>Sin Interacción:</strong> ' . $sin_interaccion . '</div>';
$html .= $categorias_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE CLASIFICACIÓN</div>';
$html .= '<table id="tabla-clasificacion">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-apoderado">Apoderado</th>
            <th class="col-familia">Familia</th>
            <th class="col-categoria">Categoría</th>
            <th class="col-compromiso">Compromiso</th>
            <th class="col-participacion">Participación</th>
            <th class="col-puntuacion">Punt.</th>
            <th class="col-metricas">Métricas</th>
            <th class="col-ultima">Últ. Interacción</th>
            <th class="col-contacto">Contacto</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosClasificacion as $row) {
    // Determinar clases
    $categoria = strtolower(str_replace(' ', '-', trim($row[3])));
    $categoriaClass = 'categoria-' . $categoria;
    
    $compromiso = strtolower(trim($row[4]));
    $compromisoClass = 'compromiso-' . $compromiso;
    
    $participacion = strtolower(str_replace(' ', '-', trim($row[5])));
    $participacionClass = 'participacion-' . $participacion;
    
    // Determinar clase de puntuación
    $puntuacion = floatval(str_replace('%', '', trim($row[6])));
    if ($puntuacion >= 80) $scoreClass = 'score-excelente';
    elseif ($puntuacion >= 60) $scoreClass = 'score-bueno';
    elseif ($puntuacion >= 40) $scoreClass = 'score-regular';
    else $scoreClass = 'score-bajo';
    
    // Determinar clase de última interacción
    $ultimaInteraccion = trim($row[8]);
    if (stripos($ultimaInteraccion, 'Sin interacciones') !== false) {
        $interaccionClass = 'sin-interaccion';
    } elseif (preg_match('/Hace (\d+) días/', $ultimaInteraccion, $matches)) {
        $dias = intval($matches[1]);
        if ($dias <= 7) $interaccionClass = 'interaccion-reciente';
        elseif ($dias <= 30) $interaccionClass = 'interaccion-antigua';
        else $interaccionClass = 'interaccion-muy-antigua';
    } else {
        $interaccionClass = 'sin-interaccion';
    }
    
    // Truncar textos largos
    $apoderado = $row[1];
    if (strlen($apoderado) > 80) {
        $apoderado = substr($apoderado, 0, 77) . '...';
    }
    
    $familia = $row[2];
    if (strlen($familia) > 50) {
        $familia = substr($familia, 0, 47) . '...';
    }
    
    $metricas = $row[7];
    if (strlen($metricas) > 60) {
        $metricas = substr($metricas, 0, 57) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-apoderado" style="font-size: 6px;">' . htmlspecialchars($apoderado) . '</td>
        <td class="col-familia" style="font-size: 6px;">' . htmlspecialchars($familia) . '</td>
        <td class="col-categoria" style="text-align: center;">
            <span class="badge-categoria ' . $categoriaClass . '">' . strtoupper(htmlspecialchars(substr($row[3], 0, 15))) . '</span>
        </td>
        <td class="col-compromiso" style="text-align: center;">
            <span class="badge-compromiso ' . $compromisoClass . '">' . strtoupper(htmlspecialchars($row[4])) . '</span>
        </td>
        <td class="col-participacion" style="text-align: center;">
            <span class="badge-participacion ' . $participacionClass . '">' . strtoupper(htmlspecialchars(substr($row[5], 0, 8))) . '</span>
        </td>
        <td class="col-puntuacion" style="text-align: center;">
            <span class="puntuacion-badge ' . $scoreClass . '">' . htmlspecialchars($row[6]) . '</span>
        </td>
        <td class="col-metricas">
            <div class="metricas-texto">' . htmlspecialchars($metricas) . '</div>
        </td>
        <td class="col-ultima" style="text-align: center;">
            <span class="ultima-interaccion ' . $interaccionClass . '">' . htmlspecialchars(substr($ultimaInteraccion, 0, 18)) . '</span>
        </td>
        <td class="col-contacto" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[9]) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Alto Compromiso:</strong> ' . $compromisos['Alto'] . ' | 
    <strong>Muy Activos:</strong> ' . $participaciones['Muy activo'] . ' | 
    <strong>Puntuación Promedio:</strong> ' . $puntuacion_promedio . '%
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
$filename = 'Reporte_Clasificacion_Apoderados_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>