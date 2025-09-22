<?php
header('Content-Type: application/json');

// Incluir conexión a la base de datos
include '../bd/conexion.php';

// Verificar si se proporcionó el ID del curso
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de curso no proporcionado']);
    exit;
}

$id_curso = intval($_GET['id']);

try {
    // 1. Obtener la ruta de la imagen del curso
    $sqlSelect = "SELECT imagen_curso FROM cursos WHERE id_curso = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    if ($stmtSelect === false) {
        throw new Exception('Error al preparar la consulta SELECT: ' . $conn->error);
    }
    $stmtSelect->bind_param("i", $id_curso);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No se encontró el curso con ID: ' . $id_curso]);
        exit;
    }

    $curso = $result->fetch_assoc();
    $imagen_curso = $curso['imagen_curso'];
    $stmtSelect->close();

    // 2. Eliminar el registro del curso
    $sqlDelete = "DELETE FROM cursos WHERE id_curso = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    if ($stmtDelete === false) {
        throw new Exception('Error al preparar la consulta DELETE: ' . $conn->error);
    }
    $stmtDelete->bind_param("i", $id_curso);

    if ($stmtDelete->execute()) {
        if ($stmtDelete->affected_rows > 0) {
            // 3. Eliminar la imagen física si existe
            $rutaImagen = '../../' . $imagen_curso; // porque guardaste como "assets/images/..."
            if (file_exists($rutaImagen) && is_file($rutaImagen)) {
                unlink($rutaImagen);
            }

            echo json_encode(['success' => true, 'message' => 'Curso e imagen eliminados exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el curso con ID: ' . $id_curso]);
        }
    } else {
        throw new Exception('Error al ejecutar la consulta DELETE: ' . $stmtDelete->error);
    }

    $stmtDelete->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Cerrar conexión
$conn->close();
