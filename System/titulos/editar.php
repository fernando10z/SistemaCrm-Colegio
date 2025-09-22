<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $subtitulo = $_POST['subtitulo'];
    $boton_texto = $_POST['boton_texto'] ?? '';
    $boton_url = $_POST['boton_url'] ?? '';
    $video_actual = $_POST['video_actual'];

    // Obtener video actual
    $sql_select = "SELECT video_url FROM Titulos WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $stmt_select->bind_result($video_url_actual);
    $stmt_select->fetch();
    $stmt_select->close();

    $video_url = $video_url_actual; // Por defecto mantener video actual

    // Si hay nuevo video, procesarlo
    if(isset($_FILES['video']) && $_FILES['video']['error'] == 0){
        $archivo = $_FILES['video'];
        $extensionesPermitidas = ['mp4','avi','mov','wmv'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if(!in_array($extension, $extensionesPermitidas)){
            echo json_encode(["success"=>false, "message"=>"Solo se permiten videos MP4, AVI, MOV o WMV."]);
            exit;
        }

        // Verificar tamaño del archivo (máximo 10MB)
        if ($archivo['size'] > 10485760) {
            echo json_encode(["success"=>false, "message"=>"El video no puede ser mayor a 10MB."]);
            exit;
        }

        $nombreArchivo = uniqid('titulo_') . '.' . $extension;
        $rutaDestino = '../../assets/videos/' . $nombreArchivo;

        // Crear directorio si no existe
        if (!file_exists('../../assets/videos/')) {
            mkdir('../../assets/videos/', 0777, true);
        }

        if(!move_uploaded_file($archivo['tmp_name'], $rutaDestino)){
            echo json_encode(["success"=>false, "message"=>"Error al subir el video."]);
            exit;
        }

        $video_url = 'assets/videos/' . $nombreArchivo;

        // Borrar video anterior si existe
        if(!empty($video_url_actual) && file_exists('../../'.$video_url_actual)){
            unlink('../../'.$video_url_actual);
        }
    }

    // Actualizar en la base de datos
    $sql = "UPDATE Titulos SET 
            video_url = ?, 
            titulo = ?, 
            subtitulo = ?, 
            boton_texto = ?, 
            boton_url = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $video_url, $titulo, $subtitulo, $boton_texto, $boton_url, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Título actualizado correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el título: ' . $conn->error
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