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
$datosEgresados = isset($_POST['datosEgresados']) ? json_decode($_POST['datosEgresados'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosEgresados)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de egresados.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosEgresados);
$categorias = ['Reciente' => 0, 'Intermedio' => 0, 'Antiguo' => 0, 'Sin fecha' => 0];
$estados = ['Activo' => 0, 'Sin contacto' => 0, 'No contactar' => 0];
$comunicaciones = ['Acepta' => 0, 'No acepta' => 0];
$con_email = 0;
$con_telefono = 0;
$con_ocupacion = 0;
$con_estudios_superiores = 0;
$promociones = [];

foreach ($datosEgresados as $row) {
    // Contar categorías (columna 0 incluye categoría como badge)
    if (stripos($row[0], 'Reciente') !== false) $categorias['Reciente']++;
    elseif (stripos($row[0], 'Intermedio') !== false) $categorias['Intermedio']++;
    elseif (stripos($row[0], 'Antiguo') !== false) $categorias['Antiguo']++;
    else $categorias['Sin fecha']++;
    
    // Contar estados (columna 4)
    $estado = ucfirst(trim(str_replace('_', ' ', $row[4])));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Contar comunicaciones (columna 6)
    $comunicacion = trim($row[6]);
    if (stripos($comunicacion, 'Acepta') !== false) {
        $comunicaciones['Acepta']++;
    } else {
        $comunicaciones['No acepta']++;
    }
    
    // Contar contactos (columna 2)
    $contacto = $row[2];
    if (stripos($contacto, 'Sin email') === false && stripos($contacto, '@') !== false) {
        $con_email++;
    }
    if (stripos($contacto, 'Sin teléfono') === false && preg_match('/\d{6,}/', $contacto)) {
        $con_telefono++;
    }
    
    // Contar ocupación y estudios (columna 5)
    $situacion = $row[5];
    if (stripos($situacion, 'No especificada') === false && stripos($situacion, 'Sin empresa') === false) {
        $con_ocupacion++;
    }
    if (stripos($situacion, 'Sin estudios superiores') === false) {
        $con_estudios_superiores++;
    }
    
    // Contar promociones (columna 3)
    if (preg_match('/\d{4}/', $row[3], $matches)) {
        $promocion = $matches[0];
        if (!isset($promociones[$promocion])) {
            $promociones[$promocion] = 0;
        }
        $promociones[$promocion]++;
    }
}

arsort($promociones); // Ordenar promociones de mayor a menor
$top_promociones = array_slice($promociones, 0, 5, true); // Top 5 promociones

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-egresados {
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

    #tabla-egresados td, #tabla-egresados th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-egresados th {
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
        padding: 2px 4px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
    }

    .categoria-reciente { background-color: #d4edda; color: #155724; }
    .categoria-intermedio { background-color: #d1ecf1; color: #0c5460; }
    .categoria-antiguo { background-color: #fff3cd; color: #856404; }
    .categoria-sin-fecha { background-color: #f8d7da; color: #721c24; }

    .badge-estado {
        padding: 2px 5px;
        border-radius: 8px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .estado-activo { background-color: #28a745; }
    .estado-sin-contacto { background-color: #ffc107; color: #856404; }
    .estado-no-contactar { background-color: #dc3545; }

    .badge-comunicacion {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 6px;
        font-weight: bold;
    }

    .acepta-si { background-color: #d4edda; color: #155724; }
    .acepta-no { background-color: #f8d7da; color: #721c24; }

    .col-id { width: 5%; }
    .col-egresado { width: 20%; }
    .col-contacto { width: 16%; }
    .col-promocion { width: 13%; }
    .col-estado { width: 9%; }
    .col-situacion { width: 20%; }
    .col-comunicaciones { width: 8%; }
    .col-ubicacion { width: 9%; }

    .distribucion-resumen {
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
                <h4>REGISTRO DE EGRESADOS</h4>
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
                <span class="stat-label">Total Egresados</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $estados['Activo'] . '</span>
                <span class="stat-label">Activos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #ffc107;">' . $estados['Sin contacto'] . '</span>
                <span class="stat-label">Sin Contacto</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . $con_email . '</span>
                <span class="stat-label">Con Email</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #e83e8c;">' . $con_telefono . '</span>
                <span class="stat-label">Con Teléfono</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $comunicaciones['Acepta'] . '</span>
                <span class="stat-label">Aceptan Comunicación</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $con_ocupacion . '</span>
                <span class="stat-label">Con Ocupación</span>
            </td>
        </tr>
    </table>
</div>';

// **Distribución por categoría y top promociones**
$distribucion_html = '<div class="distribucion-resumen"><strong>Categorías:</strong> ';
foreach ($categorias as $cat => $cant) {
    $distribucion_html .= htmlspecialchars($cat) . ' (' . $cant . ') | ';
}
$distribucion_html = rtrim($distribucion_html, ' | ');
$distribucion_html .= ' | <strong>Top 5 Promociones:</strong> ';
foreach ($top_promociones as $promo => $cant) {
    $distribucion_html .= htmlspecialchars($promo) . ' (' . $cant . ') | ';
}
$distribucion_html = rtrim($distribucion_html, ' | ');
$distribucion_html .= ' | <strong>Con Estudios Sup.:</strong> ' . $con_estudios_superiores . '</div>';
$html .= $distribucion_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE EGRESADOS</div>';
$html .= '<table id="tabla-egresados">
    <thead>
        <tr>
            <th class="col-id">ID/Cat.</th>
            <th class="col-egresado">Egresado</th>
            <th class="col-contacto">Contacto</th>
            <th class="col-promocion">Promoción</th>
            <th class="col-estado">Estado</th>
            <th class="col-situacion">Situación Actual</th>
            <th class="col-comunicaciones">Comunic.</th>
            <th class="col-ubicacion">Ubicación</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosEgresados as $row) {
    // Determinar clases
    $categoria = '';
    if (stripos($row[0], 'Reciente') !== false) $categoria = 'reciente';
    elseif (stripos($row[0], 'Intermedio') !== false) $categoria = 'intermedio';
    elseif (stripos($row[0], 'Antiguo') !== false) $categoria = 'antiguo';
    else $categoria = 'sin-fecha';
    
    $estado = strtolower(str_replace(' ', '-', trim($row[4])));
    $estadoClass = 'estado-' . $estado;
    
    $comunicacion = stripos($row[6], 'Acepta') !== false ? 'si' : 'no';
    $comunicacionClass = 'acepta-' . $comunicacion;
    
    // Truncar textos largos
    $egresado = $row[1];
    if (strlen($egresado) > 120) {
        $egresado = substr($egresado, 0, 117) . '...';
    }
    
    $contacto = $row[2];
    if (strlen($contacto) > 100) {
        $contacto = substr($contacto, 0, 97) . '...';
    }
    
    $situacion = $row[5];
    if (strlen($situacion) > 120) {
        $situacion = substr($situacion, 0, 117) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="font-size: 6px;">' . htmlspecialchars($row[0]) . '</td>
        <td class="col-egresado" style="font-size: 6px;">' . htmlspecialchars($egresado) . '</td>
        <td class="col-contacto" style="font-size: 6px;">' . htmlspecialchars($contacto) . '</td>
        <td class="col-promocion" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[3]) . '</td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars(substr($row[4], 0, 10))) . '</span>
        </td>
        <td class="col-situacion" style="font-size: 6px;">' . htmlspecialchars($situacion) . '</td>
        <td class="col-comunicaciones" style="text-align: center;">
            <span class="badge-comunicacion ' . $comunicacionClass . '">' . strtoupper(htmlspecialchars($row[6])) . '</span>
        </td>
        <td class="col-ubicacion" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[7]) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Activos:</strong> ' . $estados['Activo'] . ' | 
    <strong>Con Email:</strong> ' . $con_email . ' | 
    <strong>Aceptan Comunicaciones:</strong> ' . $comunicaciones['Acepta'] . '
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
$filename = 'Reporte_Registro_Egresados_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>