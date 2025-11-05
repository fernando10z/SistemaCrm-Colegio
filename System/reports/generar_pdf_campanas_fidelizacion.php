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
$datosCampanas = isset($_POST['datosCampanas']) ? json_decode($_POST['datosCampanas'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosCampanas)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de campañas.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosCampanas);
$tipos = [];
$estados = ['Programado' => 0, 'En curso' => 0, 'Finalizado' => 0, 'Cancelado' => 0];
$dirigidos = ['Padres' => 0, 'Estudiantes' => 0, 'Exalumnos' => 0, 'General' => 0];
$prioridades = ['Urgente' => 0, 'Alta' => 0, 'Activo' => 0, 'Normal' => 0];
$total_invitados = 0;
$total_asistieron = 0;
$suma_tasas = 0;
$contador_tasas = 0;
$costo_total = 0;
$campanas_gratuitas = 0;

foreach ($datosCampanas as $row) {
    // Contar tipos (columna 1)
    $tipo = ucfirst(trim(str_replace('_', ' ', $row[1])));
    if (!isset($tipos[$tipo])) {
        $tipos[$tipo] = 0;
    }
    $tipos[$tipo]++;
    
    // Contar dirigidos (columna 3)
    $dirigido = ucfirst(trim($row[3]));
    if (isset($dirigidos[$dirigido])) {
        $dirigidos[$dirigido]++;
    }
    
    // Contar estados (columna 4)
    $estado = ucfirst(trim(str_replace('_', ' ', $row[4])));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Extraer prioridad del ID (columna 0 incluye prioridad)
    if (preg_match('/(URGENTE|ALTA|ACTIVO|NORMAL)/i', $row[0], $matches)) {
        $prioridad = ucfirst(strtolower($matches[1]));
        if (isset($prioridades[$prioridad])) {
            $prioridades[$prioridad]++;
        }
    }
    
    // Sumar participantes (columna 6)
    $participacion = $row[6];
    if (preg_match('/(\d+(?:,\d+)?)\s+invitados/', str_replace('.', '', $participacion), $matches)) {
        $invitados = intval(str_replace(',', '', $matches[1]));
        $total_invitados += $invitados;
    }
    if (preg_match('/(\d+(?:,\d+)?)\s+asistieron/', str_replace('.', '', $participacion), $matches)) {
        $asistieron = intval(str_replace(',', '', $matches[1]));
        $total_asistieron += $asistieron;
    }
    
    // Sumar tasas (columna 7)
    $tasa = floatval(str_replace('%', '', trim($row[7])));
    if ($tasa > 0) {
        $suma_tasas += $tasa;
        $contador_tasas++;
    }
    
    // Sumar costos (columna 8)
    if (stripos($row[8], 'Gratuito') === false) {
        if (preg_match('/S\/\s*([\d,]+\.?\d*)/', $row[8], $matches)) {
            $costo = floatval(str_replace(',', '', $matches[1]));
            $costo_total += $costo;
        }
    } else {
        $campanas_gratuitas++;
    }
}

$tasa_promedio = $contador_tasas > 0 ? number_format($suma_tasas / $contador_tasas, 1) : 0;
$tasa_asistencia_global = $total_invitados > 0 ? number_format(($total_asistieron / $total_invitados) * 100, 1) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-campanas {
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

    #tabla-campanas td, #tabla-campanas th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-campanas th {
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

    .badge-tipo {
        padding: 2px 5px;
        border-radius: 8px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .tipo-evento-social { background-color: #e83e8c; }
    .tipo-reunion-padres { background-color: #6f42c1; }
    .tipo-charla-informativa { background-color: #17a2b8; }
    .tipo-academico { background-color: #28a745; }
    .tipo-deportivo { background-color: #fd7e14; }
    .tipo-otro { background-color: #6c757d; }

    .badge-estado {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .estado-programado { background-color: #ffc107; color: #856404; }
    .estado-en-curso { background-color: #28a745; }
    .estado-finalizado { background-color: #17a2b8; }
    .estado-cancelado { background-color: #dc3545; }

    .badge-dirigido {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 6px;
        font-weight: bold;
    }

    .dirigido-padres { background-color: #d4edda; color: #155724; }
    .dirigido-estudiantes { background-color: #d1ecf1; color: #0c5460; }
    .dirigido-exalumnos { background-color: #fff3cd; color: #856404; }
    .dirigido-general { background-color: #f8d7da; color: #721c24; }

    .tasa-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .tasa-excelente { background-color: #d4edda; color: #155724; }
    .tasa-buena { background-color: #d1ecf1; color: #0c5460; }
    .tasa-regular { background-color: #fff3cd; color: #856404; }
    .tasa-baja { background-color: #f8d7da; color: #721c24; }

    .col-id { width: 5%; }
    .col-tipo { width: 9%; }
    .col-campana { width: 18%; }
    .col-dirigido { width: 8%; }
    .col-estado { width: 8%; }
    .col-fechas { width: 12%; }
    .col-participacion { width: 12%; }
    .col-tasa { width: 8%; }
    .col-costo { width: 10%; }
    .col-capacidad { width: 10%; }

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
                <h4>CAMPAÑAS DE FIDELIZACIÓN</h4>
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
                <span class="stat-label">Total Campañas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $estados['En curso'] . '</span>
                <span class="stat-label">En Curso</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #ffc107;">' . $estados['Programado'] . '</span>
                <span class="stat-label">Programadas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . number_format($total_invitados) . '</span>
                <span class="stat-label">Total Invitados</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #e83e8c;">' . number_format($total_asistieron) . '</span>
                <span class="stat-label">Total Asistencias</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $tasa_asistencia_global . '%</span>
                <span class="stat-label">Tasa Global</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">S/ ' . number_format($costo_total, 2) . '</span>
                <span class="stat-label">Inversión Total</span>
            </td>
        </tr>
    </table>
</div>';

// **Distribución por tipo y estado**
$distribucion_html = '<div class="distribucion-resumen"><strong>Tipos:</strong> ';
foreach ($tipos as $tipo => $cant) {
    $distribucion_html .= htmlspecialchars($tipo) . ' (' . $cant . ') | ';
}
$distribucion_html = rtrim($distribucion_html, ' | ');
$distribucion_html .= ' | <strong>Prioridades:</strong> Urgente (' . $prioridades['Urgente'] . ') Alta (' . $prioridades['Alta'] . ') Normal (' . $prioridades['Normal'] . ')';
$distribucion_html .= ' | <strong>Gratuitas:</strong> ' . $campanas_gratuitas . '</div>';
$html .= $distribucion_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE CAMPAÑAS DE FIDELIZACIÓN</div>';
$html .= '<table id="tabla-campanas">
    <thead>
        <tr>
            <th class="col-id">ID/Prior.</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-campana">Campaña</th>
            <th class="col-dirigido">Dirigido</th>
            <th class="col-estado">Estado</th>
            <th class="col-fechas">Fechas</th>
            <th class="col-participacion">Participación</th>
            <th class="col-tasa">Tasa</th>
            <th class="col-costo">Costo</th>
            <th class="col-capacidad">Capacidad</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosCampanas as $row) {
    // Determinar clases
    $tipo = strtolower(str_replace(' ', '-', trim($row[1])));
    $tipoClass = 'tipo-' . $tipo;
    
    $dirigido = strtolower(trim($row[3]));
    $dirigidoClass = 'dirigido-' . $dirigido;
    
    $estado = strtolower(str_replace(' ', '-', trim($row[4])));
    $estadoClass = 'estado-' . $estado;
    
    // Determinar clase de tasa
    $tasa = floatval(str_replace('%', '', trim($row[7])));
    if ($tasa >= 80) $tasaClass = 'tasa-excelente';
    elseif ($tasa >= 60) $tasaClass = 'tasa-buena';
    elseif ($tasa >= 40) $tasaClass = 'tasa-regular';
    else $tasaClass = 'tasa-baja';
    
    // Truncar textos largos
    $campana = $row[2];
    if (strlen($campana) > 130) {
        $campana = substr($campana, 0, 127) . '...';
    }
    
    $fechas = $row[5];
    if (strlen($fechas) > 80) {
        $fechas = substr($fechas, 0, 77) . '...';
    }
    
    $participacion = $row[6];
    if (strlen($participacion) > 80) {
        $participacion = substr($participacion, 0, 77) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="font-size: 6px;">' . htmlspecialchars($row[0]) . '</td>
        <td class="col-tipo" style="text-align: center;">
            <span class="badge-tipo ' . $tipoClass . '">' . strtoupper(htmlspecialchars(substr($row[1], 0, 10))) . '</span>
        </td>
        <td class="col-campana" style="font-size: 6px;">' . htmlspecialchars($campana) . '</td>
        <td class="col-dirigido" style="text-align: center;">
            <span class="badge-dirigido ' . $dirigidoClass . '">' . strtoupper(htmlspecialchars($row[3])) . '</span>
        </td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars(substr($row[4], 0, 8))) . '</span>
        </td>
        <td class="col-fechas" style="font-size: 6px;">' . htmlspecialchars($fechas) . '</td>
        <td class="col-participacion" style="font-size: 6px;">' . htmlspecialchars($participacion) . '</td>
        <td class="col-tasa" style="text-align: center;">
            <span class="tasa-badge ' . $tasaClass . '">' . htmlspecialchars($row[7]) . '</span>
        </td>
        <td class="col-costo" style="font-size: 7px; text-align: center;">' . htmlspecialchars($row[8]) . '</td>
        <td class="col-capacidad" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[9]) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>En Curso:</strong> ' . $estados['En curso'] . ' | 
    <strong>Total Invitados:</strong> ' . number_format($total_invitados) . ' | 
    <strong>Tasa Global:</strong> ' . $tasa_asistencia_global . '% | 
    <strong>Inversión:</strong> S/ ' . number_format($costo_total, 2) . '
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
$filename = 'Reporte_Campanas_Fidelizacion_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>