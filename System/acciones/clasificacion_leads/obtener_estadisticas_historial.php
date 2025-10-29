<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    $sql = "SELECT 
        COUNT(*) as total_cambios,
        COUNT(DISTINCT hel.lead_id) as leads_con_cambios,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as cambios_semana,
        COUNT(CASE WHEN hel.created_at >= CURDATE() THEN 1 END) as cambios_hoy,
        COUNT(DISTINCT hel.usuario_id) as usuarios_activos
        FROM historial_estados_lead hel
        WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

    $result = $conn->query($sql);
    $estadisticas = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_cambios' => (int)$estadisticas['total_cambios'],
            'leads_con_cambios' => (int)$estadisticas['leads_con_cambios'],
            'cambios_semana' => (int)$estadisticas['cambios_semana'],
            'cambios_hoy' => (int)$estadisticas['cambios_hoy'],
            'usuarios_activos' => (int)$estadisticas['usuarios_activos']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();