<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    $sql = "SELECT DISTINCT u.id, CONCAT(u.nombre, ' ', u.apellido) as nombre
            FROM usuarios u
            INNER JOIN historial_estados_lead hel ON u.id = hel.usuario_id
            WHERE u.activo = 1
            ORDER BY u.nombre ASC";

    $result = $conn->query($sql);
    $usuarios = [];

    while($row = $result->fetch_assoc()) {
        $usuarios[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $usuarios
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();