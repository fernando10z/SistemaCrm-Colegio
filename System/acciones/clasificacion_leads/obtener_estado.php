<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

// Verificar que se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID del estado es requerido',
        'data' => null
    ]);
    exit;
}

try {
    $estado_id = (int)$_POST['id'];
    $accion = $_POST['accion'] ?? 'consultar';
    
    // Consulta principal para obtener datos del estado
    $sql = "SELECT 
        el.id,
        el.nombre,
        el.descripcion,
        el.color,
        el.orden_display,
        el.es_final,
        el.activo,
        el.created_at,
        COUNT(l.id) as total_leads,
        COUNT(CASE WHEN l.activo = 1 THEN 1 END) as leads_activos,
        COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_mes_actual,
        COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_semana_actual,
        COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
        COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as acciones_hoy,
        AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
        MAX(l.created_at) as ultimo_lead_fecha,
        COUNT(CASE WHEN l.responsable_id IS NOT NULL THEN 1 END) as leads_asignados,
        COUNT(CASE WHEN l.responsable_id IS NULL THEN 1 END) as leads_sin_asignar
    FROM estados_lead el
    LEFT JOIN leads l ON el.id = l.estado_lead_id AND l.activo = 1
    WHERE el.id = ?
    GROUP BY el.id, el.nombre, el.descripcion, el.color, el.orden_display, el.es_final, el.activo, el.created_at";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Estado no encontrado',
            'data' => null
        ]);
        exit;
    }
    
    $estado = $result->fetch_assoc();
    
    // Consultas adicionales según la acción
    if ($accion === 'consultar' || $accion === 'editar') {
        
        // Obtener historial de cambios hacia este estado
        $historial_sql = "SELECT 
            COUNT(*) as total_cambios_hacia,
            COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as cambios_mes,
            COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as cambios_semana,
            COUNT(DISTINCT hel.lead_id) as leads_unicos_cambiados,
            AVG(TIMESTAMPDIFF(DAY, l.created_at, hel.created_at)) as dias_promedio_llegada
        FROM historial_estados_lead hel
        LEFT JOIN leads l ON hel.lead_id = l.id
        WHERE hel.estado_nuevo_id = ?";
        
        $historial_stmt = $conn->prepare($historial_sql);
        $historial_stmt->bind_param("i", $estado_id);
        $historial_stmt->execute();
        $historial_result = $historial_stmt->get_result();
        $historial_stats = $historial_result->fetch_assoc();
        
        // Obtener historial de cambios desde este estado
        $desde_sql = "SELECT 
            COUNT(*) as total_cambios_desde,
            COUNT(DISTINCT hel.estado_nuevo_id) as estados_destino_unicos,
            COUNT(CASE WHEN en.es_final = 1 THEN 1 END) as conversiones_finales
        FROM historial_estados_lead hel
        LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
        WHERE hel.estado_anterior_id = ?";
        
        $desde_stmt = $conn->prepare($desde_sql);
        $desde_stmt->bind_param("i", $estado_id);
        $desde_stmt->execute();
        $desde_result = $desde_stmt->get_result();
        $desde_stats = $desde_result->fetch_assoc();
        
        // Obtener estados más comunes como origen para este estado
        $origen_sql = "SELECT 
            ea.id,
            ea.nombre as estado_origen,
            ea.color,
            COUNT(*) as frecuencia
        FROM historial_estados_lead hel
        LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
        WHERE hel.estado_nuevo_id = ? AND hel.estado_anterior_id IS NOT NULL
        GROUP BY ea.id, ea.nombre, ea.color
        ORDER BY frecuencia DESC
        LIMIT 5";
        
        $origen_stmt = $conn->prepare($origen_sql);
        $origen_stmt->bind_param("i", $estado_id);
        $origen_stmt->execute();
        $origen_result = $origen_stmt->get_result();
        
        $estados_origen = [];
        while ($row = $origen_result->fetch_assoc()) {
            $estados_origen[] = $row;
        }
        
        // Obtener estados más comunes como destino desde este estado
        $destino_sql = "SELECT 
            en.id,
            en.nombre as estado_destino,
            en.color,
            en.es_final,
            COUNT(*) as frecuencia
        FROM historial_estados_lead hel
        LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
        WHERE hel.estado_anterior_id = ?
        GROUP BY en.id, en.nombre, en.color, en.es_final
        ORDER BY frecuencia DESC
        LIMIT 5";
        
        $destino_stmt = $conn->prepare($destino_sql);
        $destino_stmt->bind_param("i", $estado_id);
        $destino_stmt->execute();
        $destino_result = $destino_stmt->get_result();
        
        $estados_destino = [];
        while ($row = $destino_result->fetch_assoc()) {
            $estados_destino[] = $row;
        }
        
        // Agregar datos adicionales al estado
        $estado['historial_stats'] = $historial_stats;
        $estado['desde_stats'] = $desde_stats;
        $estado['estados_origen'] = $estados_origen;
        $estado['estados_destino'] = $estados_destino;
    }
    
    // Formatear datos para la respuesta
    $estado['ultimo_lead_fecha_formatted'] = $estado['ultimo_lead_fecha'] ? 
        date('d/m/Y H:i', strtotime($estado['ultimo_lead_fecha'])) : 'Sin leads';
        
    $estado['created_at_formatted'] = date('d/m/Y H:i', strtotime($estado['created_at']));
    
    $estado['promedio_interes_formatted'] = $estado['promedio_interes'] ? 
        round($estado['promedio_interes'], 1) : 0;
    
    // Calcular tasa de conversión si es aplicable
    if (isset($estado['desde_stats'])) {
        $total_salidas = $estado['desde_stats']['total_cambios_desde'];
        $conversiones = $estado['desde_stats']['conversiones_finales'];
        $estado['tasa_conversion'] = $total_salidas > 0 ? 
            round(($conversiones / $total_salidas) * 100, 1) : 0;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $estado,
        'accion' => $accion,
        'message' => 'Estado cargado correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el estado: ' . $e->getMessage(),
        'data' => null
    ]);
} finally {
    // Cerrar statements y conexión
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($historial_stmt)) {
        $historial_stmt->close();
    }
    if (isset($desde_stmt)) {
        $desde_stmt->close();
    }
    if (isset($origen_stmt)) {
        $origen_stmt->close();
    }
    if (isset($destino_stmt)) {
        $destino_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>