<?php
include '../bd/conexion.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $id_curso = $_POST['id_curso'] ?? '';
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $autor_nombre = $_POST['autor_nombre'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $link = $_POST['link'] ?? '';
    $estado = $_POST['estado'] ?? '';

    if(empty($id_curso) || empty($titulo) || empty($descripcion) || empty($tipo) || empty($link) || empty($estado)){
        echo json_encode(["success"=>false, "message"=>"Todos los campos obligatorios deben estar completos."]);
        exit;
    }

    // Obtener imagen actual
    $sql_select = "SELECT imagen_curso FROM cursos WHERE id_curso = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id_curso);
    $stmt_select->execute();
    $stmt_select->bind_result($imagen_curso_actual);
    $stmt_select->fetch();
    $stmt_select->close();

    $imagen_curso = $imagen_curso_actual; // Por defecto mantener imagen actual

    // Si hay nueva imagen, procesarla
    if(isset($_FILES['imagen_curso']) && $_FILES['imagen_curso']['error'] == 0){
        $archivo = $_FILES['imagen_curso'];
        $extensionesPermitidas = ['jpg','jpeg','png','gif'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if(!in_array($extension, $extensionesPermitidas)){
            echo json_encode(["success"=>false, "message"=>"Solo se permiten imágenes JPG, PNG o GIF."]);
            exit;
        }

        $nombreArchivo = uniqid('curso_') . '.' . $extension;
        $rutaDestino = '../../assets/images/' . $nombreArchivo;

        if(!move_uploaded_file($archivo['tmp_name'], $rutaDestino)){
            echo json_encode(["success"=>false, "message"=>"Error al subir la imagen."]);
            exit;
        }

        $imagen_curso = 'assets/images/' . $nombreArchivo;

        // Borrar imagen anterior si existe
        if(file_exists('../../'.$imagen_curso_actual)){
            unlink('../../'.$imagen_curso_actual);
        }
    }

    // Actualizar curso
    $sql_update = "UPDATE cursos SET titulo=?, descripcion=?, imagen_curso=?, autor_nombre=?, tipo=?, link=?, estado=? WHERE id_curso=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssssi", $titulo, $descripcion, $imagen_curso, $autor_nombre, $tipo, $link, $estado, $id_curso);

    if($stmt_update->execute()){
        echo json_encode(["success"=>true, "message"=>"Curso actualizado correctamente"]);
    } else {
        echo json_encode(["success"=>false, "message"=>"Error al actualizar curso: ".$stmt_update->error]);
    }

    $stmt_update->close();
    $conn->close();

} else {
    echo json_encode(["success"=>false, "message"=>"Acceso no permitido."]);
}
?>
