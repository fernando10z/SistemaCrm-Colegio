<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $subtitulo = $_POST['subtitulo'];

    // Validar campos obligatorios
    if (empty($subtitulo)) {
        echo json_encode(["success"=>false, "message"=>"El subtítulo es obligatorios."]);
        exit;
    }

    // Actualizar en la base de datos
    $sql = "UPDATE seccion_promo SET 
            subtitulo = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $subtitulo, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Sección promo actualizada correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la sección promo: ' . $conn->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}
?>