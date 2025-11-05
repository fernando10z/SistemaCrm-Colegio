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
$datosBoletines = isset($_POST['datosBoletines']) ? json_decode($_POST['datosBoletines'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosBoletines)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de boletines.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosBoletines);
$categorias = [];
$niveles = ['Inicial' => 0, 'Primaria' => 0, 'Secundaria' => 0, 'General' => 0];
$popularidad = ['Alta' => 0, 'Media' => 0, 'Baja' => 0, 'Sin uso' => 0];
$estados = ['Activo' => 0, 'Inactivo' => 0];
$total_envios = 0;
$total_aperturas = 0;
$boletines_con_envios = 0;

foreach ($datosBoletines as $row) {
    // Contar categorías (columna 2)
    $categoria = ucfirst(trim($row[2]));
    if (!isset($categorias[$categoria])) {
        $categorias[$categoria] = 0;
    }
    $categorias[$categoria]++;
    
    // Contar niveles (columna 3)
    $nivel = ucfirst(trim($row[3]));
    if (isset($niveles[$nivel])) {
        $niveles[$nivel]++;
    }
    
    // Contar popularidad (columna 6)
    $pop = ucfirst(trim(str_replace('_', ' ', $row[6])));
    if (isset($popularidad[$pop])) {
        $popularidad[$pop]++;
    }
    
    // Contar estados (columna 9)
    $estado = ucfirst(trim($row[9]));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Sumar envíos (columna 5 - formato: "XX envíos")
    $estadisticas = trim($row[5]);
    if (preg_match('/(\d+(?:,\d+)?)\s+envíos/', str_replace(['.', ' '], ['', ' '], $estadisticas), $matches)) {
        $envios = intval(str_replace(',', '', $matches[1]));
        $total_envios += $envios;
        if ($envios > 0) {
            $boletines_con_envios++;
        }
    }
    
    // Sumar aperturas (columna 5 - formato: "XX aperturas")
    if (preg_match('/(\d+(?:,\d+)?)\s+aperturas/', str_replace(['.', ' '], ['', ' '], $estadisticas), $matches)) {
        $aperturas = intval(str_replace(',', '', $matches[1]));
        $total_aperturas += $aperturas;
    }
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

    #tabla-cabecera, #tabla-boletines {
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

    #tabla-boletines td, #tabla-boletines th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-boletines th {
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

    .categoria-boletin { background-color: #007bff; }
    .categoria-newsletter { background-color: #28a745; }
    .categoria-informativo { background-color: #17a2b8; }
    .categoria-evento { background-color: #fd7e14; }

    .badge-nivel {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
    }

    .nivel-inicial { background-color: #fff3e0; color: #e65100; }
    .nivel-primaria { background-color: #e8f5e8; color: #2d5a2d; }
    .nivel-secundaria { background-color: #e3f2fd; color: #1565c0; }
    .nivel-general { background-color: #f8f9fa; color: #495057; }

    .badge-popularidad {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .popularidad-alta { background-color: #28a745; }
    .popularidad-media { background-color: #ffc107; color: #856404; }
    .popularidad-baja { background-color: #fd7e14; }
    .popularidad-sin-uso { background-color: #6c757d; }

    .estado-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 6px;
        font-weight: bold;
    }

    .estado-activo { background-color: #28a745; color: white; }
    .estado-inactivo { background-color: #dc3545; color: white; }

    .col-id { width: 3%; }
    .col-boletin { width: 18%; }
    .col-categoria { width: 9%; }
    .col-nivel { width: 8%; }
    .col-variables { width: 9%; }
    .col-estadisticas { width: 13%; }
    .col-popularidad { width: 9%; }
    .col-metricas { width: 11%; }
    .col-ultimo { width: 10%; }
    .col-estado { width: 10%; }

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
                <h4>BOLETINES INFORMATIVOS</h4>
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
                <span class="stat-label">Total Boletines</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $estados['Activo'] . '</span>
                <span class="stat-label">Activos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $estados['Inactivo'] . '</span>
                <span class="stat-label">Inactivos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #007bff;">' . number_format($total_envios) . '</span>
                <span class="stat-label">Total Envíos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . number_format($total_aperturas) . '</span>
                <span class="stat-label">Total Aperturas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $popularidad['Alta'] . '</span>
                <span class="stat-label">Alta Popularidad</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $boletines_con_envios . '</span>
                <span class="stat-label">Con Envíos</span>
            </td>
        </tr>
    </table>
</div>';

// **Distribución por categoría y nivel**
$categorias_html = '<div class="categorias-resumen"><strong>Categorías:</strong> ';
foreach ($categorias as $cat => $cant) {
    $categorias_html .= htmlspecialchars($cat) . ' (' . $cant . ') | ';
}
$categorias_html = rtrim($categorias_html, ' | ');
$categorias_html .= ' | <strong>Niveles:</strong> ';
foreach ($niveles as $niv => $cant) {
    if ($cant > 0) {
        $categorias_html .= htmlspecialchars($niv) . ' (' . $cant . ') | ';
    }
}
$categorias_html = rtrim($categorias_html, ' | ');
$categorias_html .= '</div>';
$html .= $categorias_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE BOLETINES INFORMATIVOS</div>';
$html .= '<table id="tabla-boletines">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-boletin">Boletín</th>
            <th class="col-categoria">Categoría</th>
            <th class="col-nivel">Nivel</th>
            <th class="col-variables">Variables</th>
            <th class="col-estadisticas">Estadísticas</th>
            <th class="col-popularidad">Popularidad</th>
            <th class="col-metricas">Métricas</th>
            <th class="col-ultimo">Último Envío</th>
            <th class="col-estado">Estado</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosBoletines as $row) {
    // Determinar clases
    $categoria = strtolower(trim($row[2]));
    $categoriaClass = 'categoria-' . $categoria;
    
    $nivel = strtolower(trim($row[3]));
    $nivelClass = 'nivel-' . $nivel;
    
    $pop = strtolower(str_replace(' ', '-', trim($row[6])));
    $popularidadClass = 'popularidad-' . $pop;
    
    $estado = strtolower(trim($row[9]));
    $estadoClass = 'estado-' . $estado;
    
    // Truncar textos largos
    $boletin = $row[1];
    if (strlen($boletin) > 120) {
        $boletin = substr($boletin, 0, 117) . '...';
    }
    
    $variables = $row[4];
    if (strlen($variables) > 40) {
        $variables = substr($variables, 0, 37) . '...';
    }
    
    $estadisticas = $row[5];
    if (strlen($estadisticas) > 80) {
        $estadisticas = substr($estadisticas, 0, 77) . '...';
    }
    
    $metricas = $row[7];
    if (strlen($metricas) > 60) {
        $metricas = substr($metricas, 0, 57) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-boletin" style="font-size: 6px;">' . htmlspecialchars($boletin) . '</td>
        <td class="col-categoria" style="text-align: center;">
            <span class="badge-categoria ' . $categoriaClass . '">' . strtoupper(htmlspecialchars($row[2])) . '</span>
        </td>
        <td class="col-nivel" style="text-align: center;">
            <span class="badge-nivel ' . $nivelClass . '">' . strtoupper(htmlspecialchars($row[3])) . '</span>
        </td>
        <td class="col-variables" style="font-size: 6px;">' . htmlspecialchars($variables) . '</td>
        <td class="col-estadisticas" style="font-size: 6px;">' . htmlspecialchars($estadisticas) . '</td>
        <td class="col-popularidad" style="text-align: center;">
            <span class="badge-popularidad ' . $popularidadClass . '">' . strtoupper(htmlspecialchars($row[6])) . '</span>
        </td>
        <td class="col-metricas" style="font-size: 6px;">' . htmlspecialchars($metricas) . '</td>
        <td class="col-ultimo" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[8]) . '</td>
        <td class="col-estado" style="text-align: center;">
            <span class="estado-badge ' . $estadoClass . '">' . strtoupper(htmlspecialchars($row[9])) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Activos:</strong> ' . $estados['Activo'] . ' | 
    <strong>Total Envíos:</strong> ' . number_format($total_envios) . ' | 
    <strong>Alta Popularidad:</strong> ' . $popularidad['Alta'] . '
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
$filename = 'Reporte_Boletines_Informativos_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>