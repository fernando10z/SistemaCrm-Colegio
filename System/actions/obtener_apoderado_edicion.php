<?php
header('Content-Type: application/json; charset=utf-8');
include '../bd/conexion.php';

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$apoderado_id = intval($_POST['id']);

try {
    $sql = "SELECT a.*, f.codigo_familia, f.apellido_principal as familia_apellido, f.nivel_socioeconomico
            FROM apoderados a
            LEFT JOIN familias f ON a.familia_id = f.id
            WHERE a.id = ? AND a.activo = 1
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Apoderado no encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>