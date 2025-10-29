<?php
require_once '../../bd/conexion.php';
session_start();

header('Content-Type: application/json');


$usuario_origen_id = isset($_POST['usuario_origen_id']) ? intval($_POST['usuario_origen_id']) : 0;

if ($usuario_origen_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario origen inválido']);
    exit;
}

try {
    $query = "SELECT 
                u.id,
                u.nombre,
                u.apellidos,
                CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
                COUNT(l.id) as total_leads
              FROM usuarios u
              LEFT JOIN leads l ON u.id = l.responsable_id AND l.activo = 1
              WHERE u.id != 1
              AND u.activo = 1
              GROUP BY u.id, u.nombre, u.apellidos
              ORDER BY u.nombre ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre_completo'],
            'total_leads' => $row['total_leads']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>