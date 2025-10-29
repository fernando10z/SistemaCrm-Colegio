<?php
require_once '../../config/conexion.php';
session_start();

header('Content-Type: application/json');


$usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;

try {
    $query = "SELECT 
                l.id,
                CONCAT(l.codigo_lead, ' - ', l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre
              FROM leads l
              WHERE l.responsable_id = 1 
              AND l.activo = 1
              ORDER BY l.created_at DESC
              LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'leads' => $leads
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>