<?php
include '../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $icono = $_POST['icono'];
    $descripcion = $_POST['descripcion'];
    $descripcion_corta = $_POST['descripcion_corta'] ?? '';
    $enlace = $_POST['enlace'] ?? '';
    $texto_enlace = $_POST['texto_enlace'] ?? '';
    $clase_extra = $_POST['clase_extra'] ?? '';
    $estado = $_POST['estado'];

    $sql = "UPDATE features SET 
            titulo = ?, 
            icono = ?, 
            descripcion = ?, 
            descripcion_corta = ?, 
            enlace = ?, 
            texto_enlace = ?, 
            clase_extra = ?, 
            estado = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $titulo, $icono, $descripcion, $descripcion_corta, $enlace, $texto_enlace, $clase_extra, $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feature actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el feature: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>