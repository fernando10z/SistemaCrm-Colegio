<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    $sql = "SELECT id, nombre, tipo, categoria 
            FROM plantillas_mensajes 
            WHERE activo = 1 
            ORDER BY categoria ASC, nombre ASC";

    $result = $conn->query($sql);
    $plantillas = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plantillas[] = [
                'id' => (int)$row['id'],
                'nombre' => $row['nombre'],
                'tipo' => $row['tipo'],
                'categoria' => $row['categoria'] ?? 'general'
            ];
        }
    }

    echo json_encode($plantillas, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([]);
}

$conn->close();