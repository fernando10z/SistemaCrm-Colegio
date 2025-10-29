<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión
include '../bd/conexion.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de plantilla no válido']);
    exit;
}

try {
    // Consultar plantilla
    $sql = "SELECT 
                id, 
                nombre, 
                tipo, 
                asunto, 
                contenido, 
                variables_disponibles, 
                categoria, 
                activo, 
                created_at, 
                updated_at 
            FROM plantillas_mensajes 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row,
            'accion' => $accion
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Plantilla no encontrada'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>