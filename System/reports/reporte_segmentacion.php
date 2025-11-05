<?php
require_once '../bd/conexion.php';
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

// Construir consulta según criterio
$titulo_reporte = '';
$datos_segmentacion = [];

if ($criterio === 'compromiso_participacion') {
    $titulo_reporte = 'Segmentación por Compromiso y Participación';
    
    $sql = "SELECT 
        CONCAT(nivel_compromiso, '_', nivel_participacion) as segmento,
        nivel_compromiso,
        nivel_participacion,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje,
        ROUND(AVG(YEAR(CURDATE()) - YEAR(fecha_nacimiento)), 1) as edad_promedio
        FROM apoderados 
        WHERE activo = 1 
        GROUP BY nivel_compromiso, nivel_participacion
        ORDER BY cantidad DESC";
        
} elseif ($criterio === 'nivel_socioeconomico') {
    $titulo_reporte = 'Segmentación por Nivel Socioeconómico';
    
    $sql = "SELECT 
        f.nivel_socioeconomico as segmento,
        COUNT(a.id) as cantidad,
        ROUND(COUNT(a.id) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje,
        a.nivel_compromiso,
        COUNT(CASE WHEN a.nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos,
        COUNT(CASE WHEN a.nivel_compromiso = 'alto' THEN 1 END) as alto_compromiso
        FROM apoderados a
        LEFT JOIN familias f ON a.familia_id = f.id
        WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
        GROUP BY f.nivel_socioeconomico, a.nivel_compromiso
        ORDER BY f.nivel_socioeconomico, cantidad DESC";
        
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
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje,
        AVG((SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1)) as promedio_interacciones
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
    COUNT(CASE WHEN nivel_compromiso = 'medio' THEN 1 END) as medio_compromiso,
    COUNT(CASE WHEN nivel_compromiso = 'bajo' THEN 1 END) as bajo_compromiso,
    COUNT(CASE WHEN nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos,
    COUNT(CASE WHEN nivel_participacion = 'activo' THEN 1 END) as activos,
    COUNT(CASE WHEN nivel_participacion = 'poco_activo' THEN 1 END) as poco_activos,
    COUNT(CASE WHEN nivel_participacion = 'inactivo' THEN 1 END) as inactivos
FROM apoderados 
WHERE activo = 1";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_reporte; ?> - <?php echo $config['nombre_institucion']; ?></title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
            background-color: #ffffff;
            color: #333;
            padding: 20px;
        }
        
        .reporte-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reporte-header {
            background: linear-gradient(135deg, #a8e6cf 0%, #dcedc8 100%);
            padding: 30px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 10px;
            border: 2px solid #fff;
        }
        
        .info-section {
            text-align: center;
            flex-grow: 1;
        }
        
        .info-section h2 {
            margin: 0;
            font-size: 24px;
            color: #2d3748;
        }
        
        .info-section p {
            margin: 5px 0;
            color: #4a5568;
        }
        
        .fecha-section {
            text-align: right;
            background: white;
            padding: 15px;
            border-radius: 8px;
            min-width: 200px;
        }
        
        .fecha-section strong {
            display: block;
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .fecha-section span {
            color: #718096;
            font-size: 14px;
        }
        
        .reporte-body {
            padding: 30px;
        }
        
        .seccion-titulo {
            background: linear-gradient(135deg, #fde2e4 0%, #fad0c4 100%);
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            margin: 25px 0 15px 0;
            text-align: center;
            color: #2d3748;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .stat-card .numero {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
            display: block;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }
        
        .tabla-segmentacion {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .tabla-segmentacion thead {
            background: linear-gradient(135deg, #b8e0d2 0%, #d6eadf 100%);
        }
        
        .tabla-segmentacion th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #a8e6cf;
        }
        
        .tabla-segmentacion td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        .tabla-segmentacion tbody tr:hover {
            background-color: #f7fafc;
        }
        
        .badge-segmento {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: white;
        }
        
        .segmento-colaborador { background: linear-gradient(45deg, #a8e6cf, #81c784); }
        .segmento-comprometido { background-color: #81d4fa; }
        .segmento-participativo { background-color: #ce93d8; }
        .segmento-regular { background-color: #b0bec5; }
        .segmento-problematico { background-color: #ef9a9a; }
        
        .barra-progreso {
            width: 100%;
            height: 25px;
            background-color: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .barra-progreso-fill {
            height: 100%;
            background: linear-gradient(90deg, #a8e6cf 0%, #81c784 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        
        .observaciones-box {
            background: #fffbea;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .pie-reporte {
            margin-top: 40px;
            padding: 20px;
            background: linear-gradient(135deg, #f0f4f8 0%, #e6eef5 100%);
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            color: #4a5568;
        }
        
        .btn-acciones {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #a8e6cf 0%, #81c784 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        @media print {
            .btn-acciones, .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="reporte-container">
        <!-- Header -->
        <div class="reporte-header">
            <div class="logo-section">
                <img src="../<?php echo $config['imagen']; ?>" alt="Logo" onerror="this.src='../assets/images/logo-placeholder.png'">
            </div>
            
            <div class="info-section">
                <h2><?php echo htmlspecialchars($config['nombre_institucion']); ?></h2>
                <p>Sistema de Gestión CRM Escolar</p>
                <p><?php echo htmlspecialchars($config['email_principal']); ?> | <?php echo htmlspecialchars($config['telefono_principal']); ?></p>
            </div>
            
            <div class="fecha-section">
                <strong><?php echo strtoupper($titulo_reporte); ?></strong>
                <span>Fecha: <?php echo date('d/m/Y'); ?></span><br>
                <span>Hora: <?php echo date('H:i:s'); ?></span>
            </div>
        </div>
        
        <!-- Body -->
        <div class="reporte-body">
            <!-- Botones de Acción -->
            <div class="btn-acciones no-print">
                <a href="javascript:window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> Imprimir
                </a>
                <a href="../actions/generar_pdf_segmentacion.php?criterio=<?php echo $criterio; ?>&graficos=<?php echo $incluir_graficos; ?>&metricas=<?php echo $incluir_metricas; ?>&obs=<?php echo urlencode($observaciones); ?>" target="_blank" class="btn btn-primary">
                    <i class="ti ti-file-type-pdf"></i> Descargar PDF
                </a>
                <a href="../clasi_seg.php" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> Volver
                </a>
            </div>
            
            <!-- Estadísticas Generales -->
            <?php if ($incluir_metricas === '1'): ?>
            <div class="seccion-titulo">ESTADÍSTICAS GENERALES</div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="numero"><?php echo $stats['total_apoderados']; ?></span>
                    <div class="label">Total Apoderados</div>
                </div>
                <div class="stat-card">
                    <span class="numero"><?php echo $stats['alto_compromiso']; ?></span>
                    <div class="label">Alto Compromiso</div>
                </div>
                <div class="stat-card">
                    <span class="numero"><?php echo $stats['muy_activos']; ?></span>
                    <div class="label">Muy Activos</div>
                </div>
                <div class="stat-card">
                    <span class="numero"><?php echo $stats['bajo_compromiso']; ?></span>
                    <div class="label">Bajo Compromiso</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabla de Segmentación -->
            <div class="seccion-titulo">RESULTADOS DE SEGMENTACIÓN</div>
            
            <table class="tabla-segmentacion">
                <thead>
                    <tr>
                        <th style="width: 40%">Segmento</th>
                        <th style="width: 15%">Cantidad</th>
                        <th style="width: 15%">Porcentaje</th>
                        <th style="width: 30%">Distribución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_segmentacion as $dato): 
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
                    ?>
                    <tr>
                        <td>
                            <span class="badge-segmento <?php echo $clase_badge; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $nombre_segmento)); ?>
                            </span>
                        </td>
                        <td><strong><?php echo $dato['cantidad']; ?></strong> apoderados</td>
                        <td><strong><?php echo number_format($dato['porcentaje'], 2); ?>%</strong></td>
                        <td>
                            <div class="barra-progreso">
                                <div class="barra-progreso-fill" style="width: <?php echo $dato['porcentaje']; ?>%">
                                    <?php echo number_format($dato['porcentaje'], 1); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Observaciones -->
            <?php if (!empty($observaciones)): ?>
            <div class="observaciones-box">
                <strong><i class="ti ti-notes"></i> Observaciones:</strong><br>
                <?php echo nl2br(htmlspecialchars($observaciones)); ?>
            </div>
            <?php endif; ?>
            
            <!-- Pie de Reporte -->
            <div class="pie-reporte">
                <strong>Reporte generado por:</strong> Sistema CRM Escolar<br>
                <strong>Fecha y hora de generación:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
                <strong>Criterio utilizado:</strong> <?php echo $titulo_reporte; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>