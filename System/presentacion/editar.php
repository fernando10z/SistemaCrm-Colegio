<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $subtitulo = $_POST['subtitulo'];
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $url_video = $_POST['url_video'];
    $estado = $_POST['estado'];
    $imagen_actual = $_POST['imagen_actual'];

    // Procesar imagen si se subió una nueva
    $imagen = $imagen_actual;
    if (!empty($_FILES['imagen']['name'])) {
        $target_dir = "../../assets/images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Mantener la secuencia de nombres (main-thumb-01, main-thumb-02, etc.)
        $imageFileType = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'main-thumb' . sprintf('%02d', $id) . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        // Verificar si es una imagen real
        $check = getimagesize($_FILES["imagen"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
                $imagen = "assets/images/" . $new_filename;
                
                // Eliminar imagen anterior si existe
                if (!empty($imagen_actual) && file_exists("../../" . $imagen_actual)) {
                    unlink("../../" . $imagen_actual);
                }
            }
        }
    }

    // Actualizar en la base de datos
    $sql = "UPDATE presentacion SET 
            subtitulo = ?, 
            titulo = ?, 
            descripcion = ?, 
            url_video = ?, 
            imagen = ?, 
            estado = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $subtitulo, $titulo, $descripcion, $url_video, $imagen, $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Presentación actualizada correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la presentación: ' . $conn->error
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