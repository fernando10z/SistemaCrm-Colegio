<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    $sql = "SELECT id, nombre, descripcion, color, orden_display, es_final 
            FROM estados_lead 
            WHERE activo = 1 
            ORDER BY orden_display ASC, nombre ASC";

    $result = $conn->query($sql);
    $estados = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $estados[] = [
                'id' => (int)$row['id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'] ?? '',
                'color' => $row['color'] ?? '#6c757d',
                'orden_display' => (int)$row['orden_display'],
                'es_final' => (int)$row['es_final']
            ];
        }
    }

    echo json_encode($estados, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([]);
}

$conn->close();