<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

$estado_id = $_POST['estado_id'] ?? null;

if (!$estado_id) {
    echo json_encode(['success' => false, 'message' => 'ID de estado no proporcionado']);
    exit;
}

try {
    // Obtener configuración guardada
    $sql_config = "SELECT configuracion_json FROM flujo_estados WHERE estado_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql_config);
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result_config = $stmt->get_result();

    $configuracion = null;
    if ($result_config->num_rows > 0) {
        $row = $result_config->fetch_assoc();
        $configuracion = json_decode($row['configuracion_json'], true);
    }

    // Obtener estados disponibles
    $sql_estados = "SELECT id, nombre, color, orden_display 
                    FROM estados_lead 
                    WHERE activo = 1 
                    ORDER BY orden_display ASC";
    $result_estados = $conn->query($sql_estados);
    $estados_disponibles = [];

    while($estado = $result_estados->fetch_assoc()) {
        $estados_disponibles[] = [
            'id' => (int)$estado['id'],
            'nombre' => $estado['nombre'],
            'color' => $estado['color'] ?? '#6c757d'
        ];
    }

    // Obtener métricas
    $sql_metricas = "SELECT 
        COUNT(*) as total_leads,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_activos,
        ROUND(AVG(TIMESTAMPDIFF(DAY, created_at, IFNULL(updated_at, NOW()))), 1) as tiempo_promedio
        FROM leads 
        WHERE estado_lead_id = ? AND activo = 1";
        
    $stmt_metricas = $conn->prepare($sql_metricas);
    $stmt_metricas->bind_param("i", $estado_id);
    $stmt_metricas->execute();
    $metricas = $stmt_metricas->get_result()->fetch_assoc();

    // Calcular tasa de conversión
    $sql_conversion = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN el.es_final = 1 THEN 1 END) as convertidos
        FROM leads l
        LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
        WHERE l.activo = 1";
    $result_conversion = $conn->query($sql_conversion);
    $conversion = $result_conversion->fetch_assoc();
    
    $tasa_conversion = $conversion['total'] > 0 
        ? round(($conversion['convertidos'] / $conversion['total']) * 100, 1) 
        : 0;

    $metricas['tasa_conversion'] = $tasa_conversion;

    echo json_encode([
        'success' => true,
        'configuracion' => $configuracion,
        'estados_disponibles' => $estados_disponibles,
        'metricas' => $metricas
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();