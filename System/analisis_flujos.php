<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para análisis de flujos y conversiones
$sql_flujos = "SELECT 
    ea.id as estado_anterior_id,
    ea.nombre as estado_anterior,
    ea.color as color_anterior,
    en.id as estado_nuevo_id,
    en.nombre as estado_nuevo,
    en.color as color_nuevo,
    COUNT(*) as total_cambios,
    AVG(TIMESTAMPDIFF(HOUR, hel.created_at, NOW())) as tiempo_promedio_horas,
    COUNT(DISTINCT hel.lead_id) as leads_unicos,
    COUNT(DISTINCT hel.usuario_id) as usuarios_involucrados,
    MIN(hel.created_at) as primer_cambio,
    MAX(hel.created_at) as ultimo_cambio
FROM historial_estados_lead hel
LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY hel.estado_anterior_id, hel.estado_nuevo_id, ea.nombre, en.nombre, ea.color, en.color
ORDER BY total_cambios DESC";

$result_flujos = $conn->query($sql_flujos);

// Análisis de conversión por estado
$sql_conversion = "SELECT 
    el.id,
    el.nombre,
    el.color,
    el.es_final,
    COUNT(l.id) as total_leads,
    COUNT(CASE WHEN l.estado_lead_id IN (SELECT id FROM estados_lead WHERE es_final = 1) THEN 1 END) as leads_convertidos,
    ROUND((COUNT(CASE WHEN l.estado_lead_id IN (SELECT id FROM estados_lead WHERE es_final = 1) THEN 1 END) / COUNT(l.id)) * 100, 2) as tasa_conversion,
    AVG(DATEDIFF(NOW(), l.created_at)) as dias_promedio_estado,
    COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes_estado
FROM estados_lead el
LEFT JOIN leads l ON el.id = l.estado_lead_id AND l.activo = 1
WHERE el.activo = 1
GROUP BY el.id, el.nombre, el.color, el.es_final
ORDER BY el.orden_display ASC";

$result_conversion = $conn->query($sql_conversion);

// Estadísticas generales del pipeline
$sql_stats = "SELECT 
    COUNT(DISTINCT l.id) as total_leads_activos,
    COUNT(DISTINCT CASE WHEN l.estado_lead_id IN (SELECT id FROM estados_lead WHERE es_final = 1) THEN l.id END) as total_convertidos,
    AVG(DATEDIFF(NOW(), l.created_at)) as dias_promedio_pipeline,
    COUNT(DISTINCT l.responsable_id) as total_responsables,
    COUNT(DISTINCT CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN l.id END) as leads_mes_actual,
    COUNT(DISTINCT CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN l.id END) as leads_semana_actual
FROM leads l
WHERE l.activo = 1";

$result_stats = $conn->query($sql_stats);
$stats_generales = $result_stats->fetch_assoc();

// Análisis temporal de flujos
$sql_temporal = "SELECT 
    DATE_FORMAT(hel.created_at, '%Y-%m') as mes,
    COUNT(*) as total_cambios,
    COUNT(DISTINCT hel.lead_id) as leads_movidos,
    COUNT(DISTINCT hel.usuario_id) as usuarios_activos
FROM historial_estados_lead hel
WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(hel.created_at, '%Y-%m')
ORDER BY mes ASC";

$result_temporal = $conn->query($sql_temporal);

// Velocidad de conversión por estado
$sql_velocidad = "SELECT 
    el.nombre,
    el.color,
    AVG(TIMESTAMPDIFF(DAY, l.created_at, 
        (SELECT MIN(created_at) 
         FROM historial_estados_lead 
         WHERE lead_id = l.id 
         AND estado_nuevo_id != l.estado_lead_id
         LIMIT 1)
    )) as dias_promedio_cambio,
    COUNT(l.id) as total_leads_estado
FROM estados_lead el
LEFT JOIN leads l ON el.id = l.estado_lead_id AND l.activo = 1
WHERE el.activo = 1
GROUP BY el.id, el.nombre, el.color
HAVING total_leads_estado > 0
ORDER BY dias_promedio_cambio ASC";

$result_velocidad = $conn->query($sql_velocidad);

// Obtener nombre del sistema
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
    $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
    $nombre_sistema = "CRM Escolar";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Análisis de Flujos - <?php echo $nombre_sistema; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, #d6eaff 0%, #c9e4ff 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 20px;
            border: none;
        }
        
        .stats-card h3 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-card p {
            color: #5a6c7d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .flujo-sankey {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .conversion-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-left: 4px solid #007bff;
        }
        
        .conversion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .estado-badge-analysis {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .tasa-badge {
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .tasa-excelente { background: #d4edda; color: #155724; }
        .tasa-buena { background: #d1ecf1; color: #0c5460; }
        .tasa-regular { background: #fff3cd; color: #856404; }
        .tasa-baja { background: #f8d7da; color: #721c24; }
        
        .progress-custom {
            height: 25px;
            border-radius: 12px;
            background: #e9ecef;
        }
        
        .progress-bar-custom {
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .metric-box {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .header-analysis {
            background: linear-gradient(135deg, #ffd6f0 0%, #ffc9ea 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: #333;
        }
        
        .velocidad-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.6rem;
        }
        
        .velocidad-estado {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .velocidad-tiempo {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .timeline-flow {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-flow::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-dot {
            position: absolute;
            left: -26px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #007bff;
        }
    </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    
    <section class="pc-container">
        <div class="pc-content">
            <!-- Breadcrumb -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="clasificacion_leads.php">Clasificación</a></li>
                                <li class="breadcrumb-item" aria-current="page">Análisis de Flujos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="header-analysis">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="ti ti-chart-arrows me-2"></i>
                                    Análisis Completo de Flujos de Estados
                                </h2>
                                <p class="mb-0 opacity-75">
                                    Visualización detallada del comportamiento y conversión de leads a través del pipeline de ventas.
                                    Identifica cuellos de botella y optimiza tu proceso de captación.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="clasificacion_leads.php" class="btn btn-light">
                                    <i class="ti ti-arrow-left me-1"></i>
                                    Volver a Estados
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value"><?php echo number_format($stats_generales['total_leads_activos']); ?></div>
                        <div class="metric-label">Leads Activos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value"><?php echo number_format($stats_generales['total_convertidos']); ?></div>
                        <div class="metric-label">Convertidos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value">
                            <?php 
                            $tasa_global = $stats_generales['total_leads_activos'] > 0 
                                ? round(($stats_generales['total_convertidos'] / $stats_generales['total_leads_activos']) * 100, 1) 
                                : 0;
                            echo $tasa_global . '%';
                            ?>
                        </div>
                        <div class="metric-label">Tasa de Conversión</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="metric-value"><?php echo round($stats_generales['dias_promedio_pipeline']); ?></div>
                        <div class="metric-label">Días Promedio Pipeline</div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Principales -->
            <div class="row mb-4">
                <!-- Gráfico de Flujos -->
                <div class="col-lg-8">
                    <div class="chart-container">
                        <h5 class="mb-3">Flujos Entre Estados (Últimos 90 días)</h5>
                        <canvas id="flujosChart" height="80"></canvas>
                    </div>
                </div>
                
                <!-- Gráfico Temporal -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <h5 class="mb-3">Actividad Mensual</h5>
                        <canvas id="temporalChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Análisis de Conversión -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Tasas de Conversión por Estado</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($result_conversion->num_rows > 0) {
                                while($conv = $result_conversion->fetch_assoc()) {
                                    $tasa = floatval($conv['tasa_conversion']);
                                    $clase_tasa = $tasa >= 75 ? 'tasa-excelente' : 
                                                 ($tasa >= 50 ? 'tasa-buena' : 
                                                 ($tasa >= 25 ? 'tasa-regular' : 'tasa-baja'));
                                    
                                    echo '<div class="conversion-item">';
                                    echo '<div class="conversion-header">';
                                    echo '<span class="estado-badge-analysis" style="background-color: ' . $conv['color'] . ';">' . 
                                         htmlspecialchars($conv['nombre']) . '</span>';
                                    echo '<span class="tasa-badge ' . $clase_tasa . '">' . number_format($tasa, 1) . '%</span>';
                                    echo '</div>';
                                    echo '<div class="progress progress-custom">';
                                    echo '<div class="progress-bar progress-bar-custom" style="width: ' . $tasa . '%; background-color: ' . $conv['color'] . ';">';
                                    echo number_format($conv['total_leads']) . ' leads';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<small class="text-muted mt-1 d-block">';
                                    echo 'Promedio ' . round($conv['dias_promedio_estado']) . ' días en este estado';
                                    if ($conv['leads_urgentes_estado'] > 0) {
                                        echo ' | <span class="text-danger">' . $conv['leads_urgentes_estado'] . ' urgentes</span>';
                                    }
                                    echo '</small>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted">No hay datos de conversión disponibles.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Velocidad de Cambio -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Velocidad de Cambio de Estado</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($result_velocidad->num_rows > 0) {
                                while($vel = $result_velocidad->fetch_assoc()) {
                                    $dias = is_null($vel['dias_promedio_cambio']) ? 0 : round($vel['dias_promedio_cambio']);
                                    echo '<div class="velocidad-item">';
                                    echo '<div class="velocidad-estado">';
                                    echo '<span class="estado-badge-analysis" style="background-color: ' . $vel['color'] . ';">' . 
                                         htmlspecialchars($vel['nombre']) . '</span>';
                                    echo '<small class="text-muted">(' . $vel['total_leads_estado'] . ' leads)</small>';
                                    echo '</div>';
                                    echo '<div class="velocidad-tiempo">';
                                    echo $dias > 0 ? $dias . ' días' : 'N/A';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted">No hay datos de velocidad disponibles.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flujos Detallados -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Transiciones de Estados Más Frecuentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-flow">
                                <?php
                                if ($result_flujos->num_rows > 0) {
                                    $contador = 0;
                                    while($flujo = $result_flujos->fetch_assoc()) {
                                        if ($contador >= 15) break; // Limitar a top 15
                                        
                                        echo '<div class="timeline-item">';
                                        echo '<div class="timeline-dot"></div>';
                                        echo '<div class="card mb-2">';
                                        echo '<div class="card-body">';
                                        echo '<div class="d-flex justify-content-between align-items-center">';
                                        echo '<div class="d-flex align-items-center gap-3">';
                                        echo '<span class="estado-badge-analysis" style="background-color: ' . ($flujo['color_anterior'] ?? '#6c757d') . ';">' . 
                                             htmlspecialchars($flujo['estado_anterior'] ?? 'Nuevo') . '</span>';
                                        echo '<i class="ti ti-arrow-right" style="font-size: 1.5rem; color: #6c757d;"></i>';
                                        echo '<span class="estado-badge-analysis" style="background-color: ' . $flujo['color_nuevo'] . ';">' . 
                                             htmlspecialchars($flujo['estado_nuevo']) . '</span>';
                                        echo '</div>';
                                        echo '<div class="text-end">';
                                        echo '<h4 class="mb-0">' . number_format($flujo['total_cambios']) . '</h4>';
                                        echo '<small class="text-muted">cambios</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="mt-2 pt-2 border-top">';
                                        echo '<div class="row text-center">';
                                        echo '<div class="col-4">';
                                        echo '<small class="text-muted d-block">Leads Únicos</small>';
                                        echo '<strong>' . $flujo['leads_unicos'] . '</strong>';
                                        echo '</div>';
                                        echo '<div class="col-4">';
                                        echo '<small class="text-muted d-block">Usuarios</small>';
                                        echo '<strong>' . $flujo['usuarios_involucrados'] . '</strong>';
                                        echo '</div>';
                                        echo '<div class="col-4">';
                                        echo '<small class="text-muted d-block">Último cambio</small>';
                                        echo '<strong>' . ceil((strtotime('now') - strtotime($flujo['ultimo_cambio'])) / 86400) . 'd</strong>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        $contador++;
                                    }
                                } else {
                                    echo '<p class="text-muted">No hay datos de flujos disponibles.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/fonts/custom-font.js"></script>
    <script src="assets/js/pcoded.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        layout_change("light");
        change_box_container("false");
        layout_rtl_change("false");
        preset_change("preset-1");
        font_change("Public-Sans");
    </script>

    <?php include 'includes/configuracion.php'; ?>

    <script>
        // Gráfico de Flujos
        <?php
        $result_flujos->data_seek(0);
        $labels_flujos = [];
        $data_flujos = [];
        $colors_flujos = [];
        $limite_grafico = 10;
        $count = 0;
        
        while($flujo = $result_flujos->fetch_assoc()) {
            if ($count >= $limite_grafico) break;
            $labels_flujos[] = ($flujo['estado_anterior'] ?? 'Nuevo') . ' → ' . $flujo['estado_nuevo'];
            $data_flujos[] = $flujo['total_cambios'];
            $colors_flujos[] = $flujo['color_nuevo'];
            $count++;
        }
        ?>
        
        const ctxFlujos = document.getElementById('flujosChart').getContext('2d');
        new Chart(ctxFlujos, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_flujos); ?>,
                datasets: [{
                    label: 'Cantidad de Cambios',
                    data: <?php echo json_encode($data_flujos); ?>,
                    backgroundColor: <?php echo json_encode($colors_flujos); ?>,
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Gráfico Temporal
        <?php
        $result_temporal->data_seek(0);
        $labels_temporal = [];
        $data_cambios = [];
        $data_leads = [];
        
        while($temp = $result_temporal->fetch_assoc()) {
            $labels_temporal[] = date('M Y', strtotime($temp['mes'] . '-01'));
            $data_cambios[] = $temp['total_cambios'];
            $data_leads[] = $temp['leads_movidos'];
        }
        ?>
        
        const ctxTemporal = document.getElementById('temporalChart').getContext('2d');
        new Chart(ctxTemporal, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_temporal); ?>,
                datasets: [{
                    label: 'Cambios de Estado',
                    data: <?php echo json_encode($data_cambios); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true, position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0' }
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>