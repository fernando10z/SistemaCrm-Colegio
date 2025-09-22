<?php
include '../bd/conexion.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM features WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feature eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el feature: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
}
?>