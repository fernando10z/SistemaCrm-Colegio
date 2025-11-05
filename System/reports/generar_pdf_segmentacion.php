<?php
require_once '../vendor/autoload.php';
require_once '../bd/conexion.php';
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Lima');

// Obtener parámetros
$criterio = $_GET['criterio'] ?? 'compromiso_participacion';
$incluir_graficos = $_GET['graficos'] ?? '1';
$incluir_metricas = $_GET['metricas'] ?? '1';
$observaciones = $_GET['obs'] ?? '';

// Obtener configuración del sistema
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

// Convertir imagen a base64
$rutaImagen = '../' . $config['imagen'];
$imagenBase64 = '';
if (file_exists($rutaImagen)) {
    $imageData = file_get_contents($rutaImagen);
    $imagenBase64 = 'data:image/' . pathinfo($rutaImagen, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
} else {
    $imagenBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#a8e6cf"/><text x="50%" y="50%" fill="white" text-anchor="middle" dy=".3em" font-size="20">LOGO</text></svg>');
}

// Construir consulta según criterio
$titulo_reporte = '';
$datos_segmentacion = [];

if ($criterio === 'compromiso_participacion') {
    $titulo_reporte = 'Segmentación por Compromiso y Participación';
    
    $sql = "SELECT 
        CONCAT(nivel_compromiso, '_', nivel_participacion) as segmento,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje
        FROM apoderados 
        WHERE activo = 1 
        GROUP BY nivel_compromiso, nivel_participacion
        ORDER BY cantidad DESC";
        
} elseif ($criterio === 'nivel_socioeconomico') {
    $titulo_reporte = 'Segmentación por Nivel Socioeconómico';
    
    $sql = "SELECT 
        f.nivel_socioeconomico as segmento,
        COUNT(a.id) as cantidad,
        ROUND(COUNT(a.id) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje
        FROM apoderados a
        LEFT JOIN familias f ON a.familia_id = f.id
        WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
        GROUP BY f.nivel_socioeconomico
        ORDER BY f.nivel_socioeconomico";
        
} elseif ($criterio === 'problematicos_colaboradores') {
    $titulo_reporte = 'Segmentación: Problemáticos vs Colaboradores';
    
    $sql = "SELECT 
        CASE 
            WHEN a.nivel_compromiso = 'alto' AND a.nivel_participacion IN ('muy_activo', 'activo') THEN 'Colaborador Estrella'
            WHEN a.nivel_compromiso = 'alto' THEN 'Comprometido'
            WHEN a.nivel_participacion = 'muy_activo' THEN 'Muy Participativo'
            WHEN a.nivel_compromiso = 'bajo' AND a.nivel_participacion = 'inactivo' THEN 'Problemático'
            WHEN a.nivel_compromiso = 'bajo' THEN 'Bajo Compromiso'
            WHEN a.nivel_participacion = 'inactivo' THEN 'Inactivo'
            ELSE 'Regular'
        END as segmento,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje
        FROM apoderados a
        WHERE a.activo = 1
        GROUP BY segmento
        ORDER BY cantidad DESC";
}

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $datos_segmentacion[] = $row;
}

// Estadísticas generales
$stats_sql = "SELECT 
    COUNT(*) as total_apoderados,
    COUNT(CASE WHEN nivel_compromiso = 'alto' THEN 1 END) as alto_compromiso,
    COUNT(CASE WHEN nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos,
    COUNT(CASE WHEN nivel_compromiso = 'bajo' THEN 1 END) as bajo_compromiso
FROM apoderados 
WHERE activo = 1";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// **HTML del PDF**
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        text-align: center;
    }

    #tabla-cabecera h3 {
        font-size: 18px;
        margin-bottom: 2px;
        color: #444;
    }

    .logo-empresa {
        border: 1px solid #a8e6cf;
        border-radius: 15px;
        text-align: center;
        padding: 10px;
    }

    .reporte-titulo {
        border: 1px solid #a8e6cf;
        border-radius: 15px;
        text-align: center;
        padding: 10px;
        background-color: #f0f9ff;
    }

    .stats-grid {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    .stat-cell {
        background-color: #e3f2fd;
        border: 1px solid #a8e6cf;
        padding: 12px;
        text-align: center;
        border-radius: 8px;
    }

    .stat-numero {
        font-size: 24px;
        font-weight: bold;
        color: #2d3748;
        display: block;
    }

    .stat-label {
        font-size: 10px;
        color: #718096;
    }

    #tabla-segmentacion {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    #tabla-segmentacion th {
        background-color: #b8e0d2;
        padding: 10px;
        border: 1px solid #333;
        font-weight: bold;
        text-align: center;
    }

    #tabla-segmentacion td {
        padding: 8px;
        border: 1px solid #333;
        text-align: left;
    }

    .badge-segmento {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        color: white;
    }

    .segmento-colaborador { background-color: #a8e6cf; }
    .segmento-comprometido { background-color: #81d4fa; }
    .segmento-participativo { background-color: #ce93d8; }
    .segmento-regular { background-color: #b0bec5; }
    .segmento-problematico { background-color: #ef9a9a; }

    .barra-progreso {
        width: 100%;
        height: 18px;
        background-color: #e2e8f0;
        border-radius: 9px;
        position: relative;
        overflow: hidden;
    }

    .barra-progreso-fill {
        height: 100%;
        background-color: #a8e6cf;
        text-align: center;
        line-height: 18px;
        font-size: 9px;
        font-weight: bold;
        color: #2d3748;
    }

    .seccion-titulo {
        background-color: #fde2e4;
        padding: 12px;
        margin: 20px 0 10px 0;
        font-weight: bold;
        border-radius: 5px;
        text-align: center;
        font-size: 14px;
    }

    .observaciones-box {
        background-color: #fffbea;
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin: 15px 0;
        font-size: 11px;
    }

    .pie-pagina {
        margin-top: 20px;
        padding: 12px;
        font-size: 11px;
        border: 1px solid #333;
        border-radius: 10px;
        text-align: center;
        background-color: #f0f4f8;
    }
</style>';

// **Cabecera**
$html .= '<table id="tabla-cabecera">
    <tr>
        <td class="logo-empresa" style="width: 25%;">
            <img src="' . $imagenBase64 . '" alt="Logo" style="max-width: 80px; max-height: 80px;">
        </td>
        <td style="width: 45%;">
            <h3>' . htmlspecialchars($config['nombre_institucion']) . '</h3>
            <div>Sistema de Gestión CRM Escolar</div>
            <div style="font-size: 11px;">' . htmlspecialchars($config['email_principal']) . '</div>
            <div style="font-size: 11px;">Telf. ' . htmlspecialchars($config['telefono_principal']) . '</div>
        </td>
        <td style="width: 30%;">
            <div class="reporte-titulo">
                <h4>' . strtoupper($titulo_reporte) . '</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Generales**
if ($incluir_metricas === '1') {
    $html .= '<div class="seccion-titulo">ESTADÍSTICAS GENERALES</div>';
    $html .= '<table class="stats-grid">
        <tr>
            <td class="stat-cell">
                <span class="stat-numero">' . $stats['total_apoderados'] . '</span>
                <div class="stat-label">Total Apoderados</div>
            </td>
            <td class="stat-cell">
                <span class="stat-numero">' . $stats['alto_compromiso'] . '</span>
                <div class="stat-label">Alto Compromiso</div>
            </td>
            <td class="stat-cell">
                <span class="stat-numero">' . $stats['muy_activos'] . '</span>
                <div class="stat-label">Muy Activos</div>
            </td>
            <td class="stat-cell">
                <span class="stat-numero">' . $stats['bajo_compromiso'] . '</span>
                <div class="stat-label">Bajo Compromiso</div>
            </td>
        </tr>
    </table>';
}

// **Tabla de Segmentación**
$html .= '<div class="seccion-titulo">RESULTADOS DE SEGMENTACIÓN</div>';
$html .= '<table id="tabla-segmentacion">
    <thead>
        <tr>
            <th style="width: 40%">Segmento</th>
            <th style="width: 20%">Cantidad</th>
            <th style="width: 15%">Porcentaje</th>
            <th style="width: 25%">Distribución</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datos_segmentacion as $dato) {
    $clase_badge = 'segmento-regular';
    $nombre_segmento = $dato['segmento'];
    
    if (strpos(strtolower($nombre_segmento), 'colaborador') !== false) {
        $clase_badge = 'segmento-colaborador';
    } elseif (strpos(strtolower($nombre_segmento), 'problematico') !== false || strpos(strtolower($nombre_segmento), 'problemático') !== false) {
        $clase_badge = 'segmento-problematico';
    } elseif (strpos(strtolower($nombre_segmento), 'comprometido') !== false) {
        $clase_badge = 'segmento-comprometido';
    } elseif (strpos(strtolower($nombre_segmento), 'participativo') !== false) {
        $clase_badge = 'segmento-participativo';
    }
    
    $html .= '<tr>
        <td>
            <span class="badge-segmento ' . $clase_badge . '">' . 
                ucwords(str_replace('_', ' ', $nombre_segmento)) . 
            '</span>
        </td>
        <td style="text-align: center;"><strong>' . $dato['cantidad'] . '</strong></td>
        <td style="text-align: center;"><strong>' . number_format($dato['porcentaje'], 2) . '%</strong></td>
        <td>
            <div class="barra-progreso">
                <div class="barra-progreso-fill" style="width: ' . $dato['porcentaje'] . '%">' . 
                    number_format($dato['porcentaje'], 1) . '%' .
                '</div>
            </div>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Observaciones**
if (!empty($observaciones)) {
    $html .= '<div class="observaciones-box">
        <strong>Observaciones:</strong><br>' . 
        nl2br(htmlspecialchars($observaciones)) . 
    '</div>';
}

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> Sistema CRM Escolar<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Criterio utilizado:</strong> ' . $titulo_reporte . '
</div>';

// **Generar PDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Segmentacion_Apoderados_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>