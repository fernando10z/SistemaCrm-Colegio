<?php
include '../bd/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $titulo_tab = $_POST['titulo_tab'];
    $titulo_h4 = $_POST['titulo_h4'];
    $descripcion = $_POST['descripcion'];
    $descripcion_extra = $_POST['descripcion_extra'] ?? '';
    $estado = $_POST['estado'];
    $imagen_actual = $_POST['imagen_actual'];
    
    // Procesar imagen si se subió una nueva
    $imagen = $imagen_actual;
    if (!empty($_FILES['imagen']['name'])) {
        $target_dir = "../../assets/images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Mantener la secuencia de nombres (choose-us-image-01, choose-us-image-02, etc.)
        $imageFileType = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'choose-us-image-' . sprintf('%02d', $id) . '.' . $imageFileType;
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
    $sql = "UPDATE tabs SET 
            titulo_tab = ?, 
            titulo_h4 = ?, 
            descripcion = ?, 
            descripcion_extra = ?, 
            imagen = ?, 
            estado = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $titulo_tab, $titulo_h4, $descripcion, $descripcion_extra, $imagen, $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Tab actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al actualizar el tab: " . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>