<?php
include '../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $sql = "UPDATE features SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>