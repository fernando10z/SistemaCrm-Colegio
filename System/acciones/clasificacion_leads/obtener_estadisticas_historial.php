<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

try {
    // Consulta para obtener estadísticas generales del historial
    $sql = "SELECT 
        COUNT(*) as total_cambios,
        COUNT(DISTINCT hel.lead_id) as leads_con_cambios,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as cambios_semana,
        COUNT(CASE WHEN hel.created_at >= CURDATE() THEN 1 END) as cambios_hoy,
        COUNT(DISTINCT hel.usuario_id) as usuarios_activos,
        ROUND(AVG(
            CASE 
                WHEN hel.estado_anterior_id IS NOT NULL 
                THEN TIMESTAMPDIFF(DAY, l.created_at, hel.created_at)
                ELSE NULL 
            END
        ), 1) as dias_promedio_cambio,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as cambios_mes,
        COUNT(CASE WHEN hel.estado_anterior_id IS NULL THEN 1 END) as leads_nuevos_historial
    FROM historial_estados_lead hel
    LEFT JOIN leads l ON hel.lead_id = l.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $stats = $result->fetch_assoc();
    
    // Consulta adicional para obtener estadísticas de conversión
    $conversion_sql = "SELECT 
        COUNT(CASE WHEN el.es_final = 1 AND el.nombre IN ('Matriculado', 'Convertido') THEN 1 END) as conversiones_exitosas,
        COUNT(CASE WHEN el.es_final = 1 AND el.nombre NOT IN ('Matriculado', 'Convertido') THEN 1 END) as conversiones_fallidas
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead el ON hel.estado_nuevo_id = el.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND el.es_final = 1";
      
    $conversion_result = $conn->query($conversion_sql);
    $conversion_stats = $conversion_result->fetch_assoc();
    
    // Combinar estadísticas
    $estadisticas_completas = array_merge($stats, $conversion_stats);
    
    // Calcular tasa de conversión
    $total_conversiones = ($conversion_stats['conversiones_exitosas'] ?? 0) + ($conversion_stats['conversiones_fallidas'] ?? 0);
    $estadisticas_completas['tasa_conversion'] = $total_conversiones > 0 ? 
        round(($conversion_stats['conversiones_exitosas'] ?? 0) / $total_conversiones * 100, 1) : 0;
    
    // Consulta para obtener el estado más común como destino
    $estado_comun_sql = "SELECT 
        el.nombre as estado_mas_comun,
        COUNT(*) as frecuencia
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead el ON hel.estado_nuevo_id = el.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY hel.estado_nuevo_id, el.nombre
    ORDER BY frecuencia DESC
    LIMIT 1";
    
    $estado_result = $conn->query($estado_comun_sql);
    $estado_comun = $estado_result->fetch_assoc();
    
    if ($estado_comun) {
        $estadisticas_completas['estado_mas_comun'] = $estado_comun['estado_mas_comun'];
        $estadisticas_completas['frecuencia_estado_comun'] = $estado_comun['frecuencia'];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $estadisticas_completas,
        'message' => 'Estadísticas cargadas correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
        'data' => null
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        $conn->close();
    }
}
?>