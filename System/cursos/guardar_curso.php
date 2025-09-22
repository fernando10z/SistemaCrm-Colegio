<?php
// Incluir la conexión a la base de datos
include '../bd/conexion.php';

// Siempre devolver JSON
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $autor_nombre = $_POST['autor_nombre'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $link = $_POST['link'] ?? '';
    $estado = $_POST['estado'] ?? '';

    // Validaciones básicas
    if(empty($titulo) || empty($descripcion) || empty($tipo) || empty($link) || empty($estado)){
        echo json_encode(["success"=>false, "message"=>"Todos los campos obligatorios deben estar completos."]);
        exit;
    }

    // Manejar imagen
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

        $imagen_curso = 'assets/images/' . $nombreArchivo; // ruta relativa
    } else {
        echo json_encode(["success"=>false, "message"=>"Debes seleccionar una imagen."]);
        exit;
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO cursos (titulo, descripcion, imagen_curso, autor_nombre, imagen_autor, tipo, link, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if(!$stmt){
        echo json_encode(["success"=>false, "message"=>"Error en la preparación de la consulta: ".$conn->error]);
        exit;
    }

    $imagen_autor = 'assets/images/author-01.png'; // imagen de autor por defecto
    $stmt->bind_param("ssssssss", $titulo, $descripcion, $imagen_curso, $autor_nombre, $imagen_autor, $tipo, $link, $estado);

    if($stmt->execute()){
        echo json_encode(["success"=>true, "message"=>"Curso guardado correctamente"]);
    } else {
        echo json_encode(["success"=>false, "message"=>"Error al guardar curso: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success"=>false, "message"=>"Acceso no permitido."]);
}
?>
