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
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de plantilla no válido']);
    exit;
}

if (!in_array($estado, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    $sql = "UPDATE plantillas_mensajes SET activo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el estado');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>