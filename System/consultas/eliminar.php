<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Preparar la consulta de eliminación
    $sql = "DELETE FROM consultas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Consulta eliminada correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar la consulta: ' . $conn->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID no proporcionado.'
    ]);
}

$conn->close();
?>