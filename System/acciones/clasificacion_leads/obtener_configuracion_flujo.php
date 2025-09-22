<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

try {
    // Obtener parámetros opcionales
    $incluir_estadisticas = $_POST['incluir_estadisticas'] ?? true;
    $periodo_dias = (int)($_POST['periodo_dias'] ?? 90);
    
    // Validar parámetros
    if ($periodo_dias < 7 || $periodo_dias > 365) {
        $periodo_dias = 90;
    }
    
    // Obtener todos los estados activos ordenados
    $estados_sql = "SELECT 
        id, nombre, descripcion, color, orden_display, es_final, activo, created_at
    FROM estados_lead 
    WHERE activo = 1
    ORDER BY orden_display ASC, nombre ASC";
    
    $estados_result = $conn->query($estados_sql);
    
    if (!$estados_result) {
        throw new Exception("Error al obtener estados: " . $conn->error);
    }
    
    $estados = [];
    while ($row = $estados_result->fetch_assoc()) {
        $estados[] = $row;
    }
    
    // Obtener matriz de transiciones entre estados
    $transiciones_sql = "SELECT 
        hel.estado_anterior_id,
        hel.estado_nuevo_id,
        ea.nombre as estado_anterior_nombre,
        ea.color as estado_anterior_color,
        en.nombre as estado_nuevo_nombre,
        en.color as estado_nuevo_color,
        en.es_final,
        COUNT(*) as total_transiciones,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transiciones_mes,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as transiciones_semana,
        COUNT(DISTINCT hel.lead_id) as leads_unicos,
        AVG(TIMESTAMPDIFF(HOUR, l.created_at, hel.created_at)) as horas_promedio_desde_creacion,
        MIN(hel.created_at) as primera_transicion,
        MAX(hel.created_at) as ultima_transicion,
        -- Calcular tiempo promedio de permanencia en el estado anterior
        AVG(
            CASE 
                WHEN hel.estado_anterior_id IS NOT NULL THEN
                    (SELECT TIMESTAMPDIFF(HOUR, MAX(hel2.created_at), hel.created_at)
                     FROM historial_estados_lead hel2 
                     WHERE hel2.lead_id = hel.lead_id 
                       AND hel2.estado_nuevo_id = hel.estado_anterior_id 
                       AND hel2.created_at < hel.created_at)
                ELSE NULL
            END
        ) as horas_promedio_permanencia
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    LEFT JOIN leads l ON hel.lead_id = l.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
      AND en.activo = 1
    GROUP BY hel.estado_anterior_id, hel.estado_nuevo_id, 
             ea.nombre, ea.color, en.nombre, en.color, en.es_final
    ORDER BY total_transiciones DESC";
    
    $transiciones_stmt = $conn->prepare($transiciones_sql);
    $transiciones_stmt->bind_param("i", $periodo_dias);
    $transiciones_stmt->execute();
    $transiciones_result = $transiciones_stmt->get_result();
    
    $transiciones = [];
    $matriz_flujo = [];
    
    while ($row = $transiciones_result->fetch_assoc()) {
        // Formatear datos de transición
        $transicion = [
            'estado_anterior_id' => $row['estado_anterior_id'],
            'estado_nuevo_id' => $row['estado_nuevo_id'],
            'estado_anterior_nombre' => $row['estado_anterior_nombre'] ?? 'Nuevo Lead',
            'estado_anterior_color' => $row['estado_anterior_color'] ?? '#6c757d',
            'estado_nuevo_nombre' => $row['estado_nuevo_nombre'],
            'estado_nuevo_color' => $row['estado_nuevo_color'],
            'es_final' => $row['es_final'],
            'total_transiciones' => $row['total_transiciones'],
            'transiciones_mes' => $row['transiciones_mes'],
            'transiciones_semana' => $row['transiciones_semana'],
            'leads_unicos' => $row['leads_unicos'],
            'horas_promedio_desde_creacion' => round($row['horas_promedio_desde_creacion'] ?? 0, 1),
            'horas_promedio_permanencia' => round($row['horas_promedio_permanencia'] ?? 0, 1),
            'primera_transicion' => $row['primera_transicion'],
            'ultima_transicion' => $row['ultima_transicion'],
            'primera_transicion_formatted' => $row['primera_transicion'] ? 
                date('d/m/Y H:i', strtotime($row['primera_transicion'])) : 'N/A',
            'ultima_transicion_formatted' => $row['ultima_transicion'] ? 
                date('d/m/Y H:i', strtotime($row['ultima_transicion'])) : 'N/A'
        ];
        
        $transiciones[] = $transicion;
        
        // Construir matriz de flujo para visualización
        $origen = $row['estado_anterior_id'] ?? 'nuevo';
        $destino = $row['estado_nuevo_id'];
        
        if (!isset($matriz_flujo[$origen])) {
            $matriz_flujo[$origen] = [];
        }
        $matriz_flujo[$origen][$destino] = $transicion;
    }
    
    // Obtener configuraciones específicas del sistema
    $configuraciones_sql = "SELECT 
        clave, valor, tipo, descripcion, categoria
    FROM configuracion_sistema 
    WHERE categoria IN ('flujo', 'estados', 'pipeline') OR clave LIKE '%flujo%' OR clave LIKE '%estado%'
    ORDER BY categoria, clave";
    
    $config_result = $conn->query($configuraciones_sql);
    $configuraciones_sistema = [];
    
    if ($config_result) {
        while ($row = $config_result->fetch_assoc()) {
            $configuraciones_sistema[] = $row;
        }
    }
    
    // Calcular estadísticas generales del flujo si se solicita
    $estadisticas_generales = [];
    
    if ($incluir_estadisticas) {
        $stats_sql = "SELECT 
            COUNT(DISTINCT hel.lead_id) as total_leads_con_movimiento,
            COUNT(*) as total_transiciones,
            COUNT(DISTINCT hel.estado_anterior_id) as estados_origen_activos,
            COUNT(DISTINCT hel.estado_nuevo_id) as estados_destino_activos,
            COUNT(CASE WHEN en.es_final = 1 THEN 1 END) as transiciones_finales,
            COUNT(CASE WHEN hel.estado_anterior_id IS NULL THEN 1 END) as ingresos_iniciales,
            AVG(TIMESTAMPDIFF(HOUR, l.created_at, hel.created_at)) as horas_promedio_conversion,
            COUNT(DISTINCT hel.usuario_id) as usuarios_activos_flujo,
            -- Calcular leads que se han movido más de una vez
            COUNT(CASE WHEN (
                SELECT COUNT(*) FROM historial_estados_lead hel2 
                WHERE hel2.lead_id = hel.lead_id
            ) > 1 THEN 1 END) as leads_multiples_movimientos
        FROM historial_estados_lead hel
        LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
        LEFT JOIN leads l ON hel.lead_id = l.id
        WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bind_param("i", $periodo_dias);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $estadisticas_generales = $stats_result->fetch_assoc();
        
        // Calcular métricas adicionales
        $total_transiciones = $estadisticas_generales['total_transiciones'];
        $transiciones_finales = $estadisticas_generales['transiciones_finales'];
        
        $estadisticas_generales['tasa_conversion_general'] = $total_transiciones > 0 ? 
            round(($transiciones_finales / $total_transiciones) * 100, 2) : 0;
            
        $estadisticas_generales['promedio_transiciones_por_lead'] = $estadisticas_generales['total_leads_con_movimiento'] > 0 ? 
            round($total_transiciones / $estadisticas_generales['total_leads_con_movimiento'], 2) : 0;
            
        $estadisticas_generales['eficiencia_flujo'] = $estadisticas_generales['ingresos_iniciales'] > 0 ? 
            round(($transiciones_finales / $estadisticas_generales['ingresos_iniciales']) * 100, 2) : 0;
    }
    
    // Identificar cuellos de botella en el flujo
    $cuellos_botella = [];
    foreach ($estados as $estado) {
        $entradas = 0;
        $salidas = 0;
        
        // Contar entradas a este estado
        foreach ($transiciones as $transicion) {
            if ($transicion['estado_nuevo_id'] == $estado['id']) {
                $entradas += $transicion['total_transiciones'];
            }
            if ($transicion['estado_anterior_id'] == $estado['id']) {
                $salidas += $transicion['total_transiciones'];
            }
        }
        
        // Si hay muchas más entradas que salidas, es un cuello de botella
        if ($entradas > 0 && $salidas > 0) {
            $ratio_retencion = ($entradas - $salidas) / $entradas;
            if ($ratio_retencion > 0.3) { // Más del 30% se queda en este estado
                $cuellos_botella[] = [
                    'estado_id' => $estado['id'],
                    'estado_nombre' => $estado['nombre'],
                    'estado_color' => $estado['color'],
                    'entradas' => $entradas,
                    'salidas' => $salidas,
                    'retenidos' => $entradas - $salidas,
                    'ratio_retencion' => round($ratio_retencion * 100, 1),
                    'severidad' => $ratio_retencion > 0.6 ? 'alta' : ($ratio_retencion > 0.4 ? 'media' : 'baja')
                ];
            }
        }
    }
    
    // Ordenar cuellos de botella por severidad
    usort($cuellos_botella, function($a, $b) {
        return $b['ratio_retencion'] <=> $a['ratio_retencion'];
    });
    
    // Generar recomendaciones de optimización
    $recomendaciones = generarRecomendacionesFlujo($estados, $transiciones, $cuellos_botella, $estadisticas_generales);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'estados' => $estados,
            'transiciones' => $transiciones,
            'matriz_flujo' => $matriz_flujo,
            'configuraciones_sistema' => $configuraciones_sistema,
            'estadisticas_generales' => $estadisticas_generales,
            'cuellos_botella' => $cuellos_botella,
            'recomendaciones' => $recomendaciones,
            'periodo_analizado' => $periodo_dias,
            'fecha_consulta' => date('Y-m-d H:i:s')
        ],
        'message' => 'Configuración de flujo cargada correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener configuración de flujo: ' . $e->getMessage(),
        'data' => null
    ]);
} finally {
    // Cerrar statements y conexión
    if (isset($transiciones_stmt)) {
        $transiciones_stmt->close();
    }
    if (isset($stats_stmt)) {
        $stats_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Función auxiliar para generar recomendaciones de optimización
function generarRecomendacionesFlujo($estados, $transiciones, $cuellos_botella, $estadisticas) {
    $recomendaciones = [];
    
    // Recomendación por cuellos de botella
    if (!empty($cuellos_botella)) {
        foreach ($cuellos_botella as $cuello) {
            if ($cuello['severidad'] === 'alta') {
                $recomendaciones[] = [
                    'tipo' => 'cuello_botella',
                    'prioridad' => 'alta',
                    'titulo' => 'Cuello de botella detectado',
                    'descripcion' => "El estado '{$cuello['estado_nombre']}' retiene {$cuello['ratio_retencion']}% de los leads. Considere revisar los criterios de avance.",
                    'accion_sugerida' => 'Revisar requisitos de transición y capacitar al equipo',
                    'estado_afectado' => $cuello['estado_id']
                ];
            }
        }
    }
    
    // Recomendación por tasa de conversión baja
    if (isset($estadisticas['tasa_conversion_general']) && $estadisticas['tasa_conversion_general'] < 20) {
        $recomendaciones[] = [
            'tipo' => 'conversion_baja',
            'prioridad' => 'media',
            'titulo' => 'Tasa de conversión mejorable',
            'descripcion' => "La tasa de conversión general es de {$estadisticas['tasa_conversion_general']}%. El objetivo recomendado es >25%.",
            'accion_sugerida' => 'Analizar motivos de pérdida de leads y optimizar proceso',
            'estado_afectado' => null
        ];
    }
    
    // Recomendación por estados sin actividad
    $estados_sin_movimiento = [];
    $estados_con_transiciones = [];
    
    foreach ($transiciones as $transicion) {
        $estados_con_transiciones[] = $transicion['estado_anterior_id'];
        $estados_con_transiciones[] = $transicion['estado_nuevo_id'];
    }
    
    foreach ($estados as $estado) {
        if (!in_array($estado['id'], $estados_con_transiciones)) {
            $estados_sin_movimiento[] = $estado['nombre'];
        }
    }
    
    if (!empty($estados_sin_movimiento)) {
        $recomendaciones[] = [
            'tipo' => 'estados_inactivos',
            'prioridad' => 'baja',
            'titulo' => 'Estados sin actividad',
            'descripcion' => 'Algunos estados no han registrado movimientos: ' . implode(', ', $estados_sin_movimiento),
            'accion_sugerida' => 'Evaluar si estos estados son necesarios o requieren activación',
            'estado_afectado' => null
        ];
    }
    
    // Recomendación por tiempo de conversión largo
    if (isset($estadisticas['horas_promedio_conversion']) && $estadisticas['horas_promedio_conversion'] > 168) { // más de 7 días
        $dias = round($estadisticas['horas_promedio_conversion'] / 24, 1);
        $recomendaciones[] = [
            'tipo' => 'tiempo_conversion',
            'prioridad' => 'media',
            'titulo' => 'Tiempo de conversión elevado',
            'descripcion' => "El tiempo promedio de conversión es de {$dias} días. Considere agilizar el proceso.",
            'accion_sugerida' => 'Implementar seguimientos más frecuentes y automatizaciones',
            'estado_afectado' => null
        ];
    }
    
    return $recomendaciones;
}
?>