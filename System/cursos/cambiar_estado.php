<?php
header('Content-Type: application/json');

// Incluir conexión a la base de datos
include '../bd/conexion.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar los datos
$id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';

// Validar datos
if ($id_curso <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de curso inválido']);
    exit;
}

if (!in_array($nuevo_estado, ['Activo', 'Inactivo'])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

try {
    // Preparar la consulta para actualizar el estado
    $sql = "UPDATE cursos SET estado = ? WHERE id_curso = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $nuevo_estado, $id_curso);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Verificar si se actualizó algún registro
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Estado del curso actualizado exitosamente a: ' . $nuevo_estado
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el curso con ID: ' . $id_curso]);
        }
    } else {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Cerrar conexión
$conn->close();
?>