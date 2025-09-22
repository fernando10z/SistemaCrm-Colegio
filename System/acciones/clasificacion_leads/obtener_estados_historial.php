<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

try {
    // Consulta para obtener estados que han sido utilizados en el historial
    $sql = "SELECT DISTINCT
        el.id,
        el.nombre,
        el.descripcion,
        el.color,
        el.orden_display,
        el.es_final,
        COUNT(hel_nuevo.id) as veces_estado_nuevo,
        COUNT(hel_anterior.id) as veces_estado_anterior,
        COUNT(hel_nuevo.id) + COUNT(hel_anterior.id) as total_menciones,
        MAX(GREATEST(
            COALESCE(hel_nuevo.created_at, '1970-01-01'),
            COALESCE(hel_anterior.created_at, '1970-01-01')
        )) as ultima_mencion
    FROM estados_lead el
    LEFT JOIN historial_estados_lead hel_nuevo ON el.id = hel_nuevo.estado_nuevo_id
    LEFT JOIN historial_estados_lead hel_anterior ON el.id = hel_anterior.estado_anterior_id
    WHERE el.activo = 1
    GROUP BY el.id, el.nombre, el.descripcion, el.color, el.orden_display, el.es_final
    HAVING total_menciones > 0
    ORDER BY total_menciones DESC, el.orden_display ASC";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $estados = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear última mención
        $ultima_mencion = ($row['ultima_mencion'] && $row['ultima_mencion'] != '1970-01-01 00:00:00') ? 
            date('d/m/Y H:i', strtotime($row['ultima_mencion'])) : 'Sin registros';
            
        // Determinar tipo de estado según su uso
        $tipo_estado = '';
        if ($row['es_final'] == 1) {
            $tipo_estado = 'Final';
        } else if ($row['veces_estado_anterior'] == 0) {
            $tipo_estado = 'Inicial';
        } else {
            $tipo_estado = 'Intermedio';
        }
        
        $estados[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'] ?? 'Sin descripción',
            'color' => $row['color'],
            'orden_display' => $row['orden_display'],
            'es_final' => $row['es_final'],
            'tipo_estado' => $tipo_estado,
            'veces_estado_nuevo' => $row['veces_estado_nuevo'],
            'veces_estado_anterior' => $row['veces_estado_anterior'],
            'total_menciones' => $row['total_menciones'],
            'ultima_mencion' => $ultima_mencion,
            'ultima_mencion_raw' => $row['ultima_mencion']
        ];
    }
    
    // Consulta adicional para obtener estadísticas de flujo de estados
    $flujo_sql = "SELECT 
        COUNT(DISTINCT hel.estado_anterior_id) as estados_origen_unicos,
        COUNT(DISTINCT hel.estado_nuevo_id) as estados_destino_unicos,
        COUNT(*) as total_transiciones,
        COUNT(CASE WHEN ea.es_final = 0 AND en.es_final = 1 THEN 1 END) as transiciones_finales
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $flujo_result = $conn->query($flujo_sql);
    $flujo_stats = $flujo_result->fetch_assoc();
    
    // Obtener el estado más frecuente como destino
    $destino_sql = "SELECT 
        el.nombre as estado_mas_frecuente,
        el.color,
        COUNT(*) as frecuencia
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead el ON hel.estado_nuevo_id = el.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY hel.estado_nuevo_id, el.nombre, el.color
    ORDER BY frecuencia DESC
    LIMIT 1";
    
    $destino_result = $conn->query($destino_sql);
    $destino_frecuente = $destino_result->fetch_assoc();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $estados,
        'flujo_stats' => $flujo_stats,
        'destino_frecuente' => $destino_frecuente,
        'total' => count($estados),
        'message' => 'Estados cargados correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estados: ' . $e->getMessage(),
        'data' => []
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        $conn->close();
    }
}
?>