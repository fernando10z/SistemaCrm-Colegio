<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
        throw new Exception('ID de usuario requerido');
    }

    $usuario_id = intval($_POST['usuario_id']);

    // Obtener métricas de rendimiento por mes (últimos 6 meses)
    $rendimiento_mensual_sql = "SELECT 
        DATE_FORMAT(l.created_at, '%Y-%m') as mes,
        DATE_FORMAT(l.created_at, '%M %Y') as mes_nombre,
        COUNT(l.id) as leads_asignados,
        COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) as leads_convertidos,
        ROUND(
            (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0) / 
            NULLIF(COUNT(l.id), 0), 
            2
        ) as tasa_conversion,
        AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
        COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes_manejados,
        
        -- Tiempo promedio desde asignación hasta conversión
        AVG(CASE 
            WHEN el.es_final = 1 AND el.nombre = 'Matriculado' AND l.fecha_conversion IS NOT NULL 
            THEN DATEDIFF(l.fecha_conversion, l.created_at) 
        END) as dias_promedio_conversion,
        
        -- Interacciones promedio por lead
        (SELECT AVG(interacciones_count.total) 
         FROM (
             SELECT COUNT(i.id) as total 
             FROM interacciones i 
             WHERE i.lead_id IN (
                 SELECT l2.id 
                 FROM leads l2 
                 WHERE l2.responsable_id = ? 
                   AND DATE_FORMAT(l2.created_at, '%Y-%m') = DATE_FORMAT(l.created_at, '%Y-%m')
             )
             GROUP BY i.lead_id
         ) as interacciones_count
        ) as interacciones_promedio_por_lead
        
    FROM leads l
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    WHERE l.responsable_id = ? 
      AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(l.created_at, '%Y-%m')
    ORDER BY mes DESC";

    $stmt = $conn->prepare($rendimiento_mensual_sql);
    $stmt->bind_param("ii", $usuario_id, $usuario_id);
    $stmt->execute();
    $rendimiento_result = $stmt->get_result();

    $rendimiento_mensual = [];
    while($row = $rendimiento_result->fetch_assoc()) {
        $rendimiento_mensual[] = $row;
    }

    // Obtener comparación con promedios del equipo
    $comparacion_equipo_sql = "SELECT 
        -- Métricas del usuario
        u_stats.tasa_conversion_usuario,
        u_stats.promedio_interes_usuario,
        u_stats.tiempo_conversion_usuario,
        u_stats.leads_totales_usuario,
        
        -- Promedios del equipo
        equipo_stats.tasa_conversion_equipo,
        equipo_stats.promedio_interes_equipo,
        equipo_stats.tiempo_conversion_equipo,
        
        -- Ranking
        u_ranking.ranking_conversion,
        u_ranking.total_usuarios
        
    FROM (
        -- Estadísticas del usuario específico
        SELECT 
            ROUND(
                (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0) / 
                NULLIF(COUNT(l.id), 0), 
                2
            ) as tasa_conversion_usuario,
            AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes_usuario,
            AVG(CASE 
                WHEN el.es_final = 1 AND el.nombre = 'Matriculado' AND l.fecha_conversion IS NOT NULL 
                THEN DATEDIFF(l.fecha_conversion, l.created_at) 
            END) as tiempo_conversion_usuario,
            COUNT(l.id) as leads_totales_usuario
        FROM leads l
        LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
        WHERE l.responsable_id = ?
          AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ) u_stats
    CROSS JOIN (
        -- Promedios del equipo
        SELECT 
            AVG(user_metrics.tasa_conversion) as tasa_conversion_equipo,
            AVG(user_metrics.promedio_interes) as promedio_interes_equipo,
            AVG(user_metrics.tiempo_conversion) as tiempo_conversion_equipo
        FROM (
            SELECT 
                l.responsable_id,
                ROUND(
                    (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(l.id), 0), 
                    2
                ) as tasa_conversion,
                AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
                AVG(CASE 
                    WHEN el.es_final = 1 AND el.nombre = 'Matriculado' AND l.fecha_conversion IS NOT NULL 
                    THEN DATEDIFF(l.fecha_conversion, l.created_at) 
                END) as tiempo_conversion
            FROM leads l
            LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
            WHERE l.responsable_id IS NOT NULL
              AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            GROUP BY l.responsable_id
            HAVING COUNT(l.id) >= 5
        ) user_metrics
    ) equipo_stats
    CROSS JOIN (
        -- Ranking del usuario
        SELECT 
            user_rank.ranking_conversion,
            user_rank.total_usuarios
        FROM (
            SELECT 
                responsable_id,
                ROUND(
                    (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(l.id), 0), 
                    2
                ) as tasa_conversion,
                ROW_NUMBER() OVER (ORDER BY 
                    ROUND(
                        (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0) / 
                        NULLIF(COUNT(l.id), 0), 
                        2
                    ) DESC
                ) as ranking_conversion,
                (SELECT COUNT(DISTINCT l2.responsable_id) 
                 FROM leads l2 
                 WHERE l2.responsable_id IS NOT NULL 
                   AND l2.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                ) as total_usuarios
            FROM leads l
            LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
            WHERE l.responsable_id IS NOT NULL
              AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            GROUP BY l.responsable_id
            HAVING COUNT(l.id) >= 5
        ) user_rank
        WHERE user_rank.responsable_id = ?
    ) u_ranking";

    $stmt2 = $conn->prepare($comparacion_equipo_sql);
    $stmt2->bind_param("ii", $usuario_id, $usuario_id);
    $stmt2->execute();
    $comparacion_result = $stmt2->get_result();
    $comparacion_equipo = $comparacion_result->fetch_assoc();

    // Obtener actividad por día de la semana
    $actividad_semanal_sql = "SELECT 
        DAYNAME(i.fecha_realizada) as dia_semana,
        DAYOFWEEK(i.fecha_realizada) as dia_numero,
        COUNT(i.id) as total_interacciones,
        COUNT(DISTINCT i.lead_id) as leads_contactados,
        AVG(i.duracion_minutos) as duracion_promedio
    FROM interacciones i
    WHERE i.usuario_id = ?
      AND i.fecha_realizada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
      AND i.estado = 'realizado'
    GROUP BY DAYOFWEEK(i.fecha_realizada), DAYNAME(i.fecha_realizada)
    ORDER BY dia_numero";

    $stmt3 = $conn->prepare($actividad_semanal_sql);
    $stmt3->bind_param("i", $usuario_id);
    $stmt3->execute();
    $actividad_result = $stmt3->get_result();

    $actividad_semanal = [];
    while($row = $actividad_result->fetch_assoc()) {
        $actividad_semanal[] = $row;
    }

    // Generar HTML de métricas
    $metricas_html = generarMetricasHTML($comparacion_equipo, $actividad_semanal);

    echo json_encode([
        'success' => true,
        'rendimiento_mensual' => $rendimiento_mensual,
        'comparacion_equipo' => $comparacion_equipo,
        'actividad_semanal' => $actividad_semanal,
        'metricas_html' => $metricas_html
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generarMetricasHTML($comparacion, $actividad) {
    if (!$comparacion) {
        return '<div class="alert alert-info">No hay suficientes datos para generar métricas de rendimiento.</div>';
    }

    $tasa_usuario = floatval($comparacion['tasa_conversion_usuario'] ?? 0);
    $tasa_equipo = floatval($comparacion['tasa_conversion_equipo'] ?? 0);
    $diferencia_conversion = $tasa_usuario - $tasa_equipo;
    
    $interes_usuario = floatval($comparacion['promedio_interes_usuario'] ?? 0);
    $interes_equipo = floatval($comparacion['promedio_interes_equipo'] ?? 0);
    $diferencia_interes = $interes_usuario - $interes_equipo;
    
    $ranking = intval($comparacion['ranking_conversion'] ?? 0);
    $total_usuarios = intval($comparacion['total_usuarios'] ?? 1);
    
    // Determinar colores y textos según rendimiento
    $conversion_class = $diferencia_conversion >= 0 ? 'text-success' : 'text-danger';
    $conversion_icon = $diferencia_conversion >= 0 ? 'ti-trending-up' : 'ti-trending-down';
    $conversion_texto = $diferencia_conversion >= 0 ? 'Superior' : 'Inferior';
    
    $interes_class = $diferencia_interes >= 0 ? 'text-success' : 'text-danger';
    $interes_icon = $diferencia_interes >= 0 ? 'ti-trending-up' : 'ti-trending-down';
    
    // Generar gráfico de actividad semanal
    $actividad_html = '';
    if (!empty($actividad)) {
        $max_interacciones = max(array_column($actividad, 'total_interacciones'));
        
        $actividad_html = '<div class="mt-3"><h6>Actividad por Día de la Semana</h6><div class="row">';
        foreach($actividad as $dia) {
            $porcentaje = $max_interacciones > 0 ? ($dia['total_interacciones'] / $max_interacciones) * 100 : 0;
            $actividad_html .= '
            <div class="col text-center">
                <div class="mb-1" style="height: 60px; display: flex; align-items: end; justify-content: center;">
                    <div style="height: ' . $porcentaje . '%; width: 20px; background-color: #007bff; border-radius: 2px;"></div>
                </div>
                <small class="text-muted">' . substr($dia['dia_semana'], 0, 3) . '</small>
                <div class="small fw-bold">' . $dia['total_interacciones'] . '</div>
            </div>';
        }
        $actividad_html .= '</div></div>';
    }

    return '
    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="ti ' . $conversion_icon . ' ' . $conversion_class . '" style="font-size: 2rem;"></i>
                    <h4 class="mt-2">' . number_format($tasa_usuario, 1) . '%</h4>
                    <p class="mb-1">Tasa de Conversión</p>
                    <small class="' . $conversion_class . '">
                        ' . $conversion_texto . ' al promedio (' . number_format($tasa_equipo, 1) . '%)
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="ti ti-trophy text-warning" style="font-size: 2rem;"></i>
                    <h4 class="mt-2">#' . $ranking . '</h4>
                    <p class="mb-1">Ranking Conversión</p>
                    <small class="text-muted">de ' . $total_usuarios . ' usuarios</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="ti ' . $interes_icon . ' ' . $interes_class . '" style="font-size: 2rem;"></i>
                    <h4 class="mt-2">' . number_format($interes_usuario, 1) . '/5</h4>
                    <p class="mb-1">Interés Promedio</p>
                    <small class="' . $interes_class . '">
                        Vs equipo: ' . number_format($interes_equipo, 1) . '/5
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Fortalezas Identificadas</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        ' . ($tasa_usuario > $tasa_equipo ? '<li class="text-success"><i class="ti ti-check me-1"></i>Conversión superior al promedio</li>' : '') . '
                        ' . ($interes_usuario > $interes_equipo ? '<li class="text-success"><i class="ti ti-check me-1"></i>Genera mayor interés en leads</li>' : '') . '
                        ' . ($ranking <= 3 ? '<li class="text-success"><i class="ti ti-check me-1"></i>Entre los top 3 performers</li>' : '') . '
                        ' . (empty($actividad) ? '' : '<li class="text-success"><i class="ti ti-check me-1"></i>Actividad consistente</li>') . '
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Áreas de Mejora</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        ' . ($tasa_usuario < $tasa_equipo ? '<li class="text-warning"><i class="ti ti-alert-triangle me-1"></i>Mejorar tasa de conversión</li>' : '') . '
                        ' . ($interes_usuario < $interes_equipo ? '<li class="text-warning"><i class="ti ti-alert-triangle me-1"></i>Trabajar en generación de interés</li>' : '') . '
                        ' . ($ranking > 5 ? '<li class="text-warning"><i class="ti ti-alert-triangle me-1"></i>Optimizar proceso de seguimiento</li>' : '') . '
                        <li class="text-info"><i class="ti ti-bulb me-1"></i>Capacitación en técnicas de cierre</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    ' . $actividad_html . '
    
    <div class="mt-3 text-center">
        <small class="text-muted">
            * Métricas basadas en los últimos 3 meses de actividad
        </small>
    </div>';
}

$conn->close();
?>