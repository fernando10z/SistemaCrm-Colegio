<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

$accion = $_POST['accion'] ?? null;
$configuracion_json = $_POST['configuracion'] ?? null;

if (!$configuracion_json) {
    echo json_encode(['success' => false, 'message' => 'Datos no proporcionados'], JSON_UNESCAPED_UNICODE);
    exit;
}

$configuracion = json_decode($configuracion_json, true);
$estado_id = $configuracion['estado_id'] ?? null;

if (!$estado_id) {
    echo json_encode(['success' => false, 'message' => 'ID de estado no proporcionado'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar si ya existe configuración
    $sql_check = "SELECT id FROM flujo_estados WHERE estado_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $estado_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Actualizar
        $sql = "UPDATE flujo_estados SET configuracion_json = ?, updated_at = NOW() WHERE estado_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $configuracion_json, $estado_id);
    } else {
        // Insertar
        $sql = "INSERT INTO flujo_estados (estado_id, configuracion_json, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $estado_id, $configuracion_json);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Configuración guardada correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al guardar: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();