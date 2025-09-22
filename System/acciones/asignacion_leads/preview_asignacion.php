<?php
header('Content-Type: application/json');
include '../../bd/conexion.php'; // tu conexión

$response = ["success" => true, "data" => []];

// Aquí construyes la consulta dependiendo del tipo
$tipo = $_POST['tipo'] ?? '';

if ($tipo === 'individual') {
    $lead_id = intval($_POST['lead_id']);
    $sql = "SELECT id, nombre, estado, prioridad, canal FROM leads WHERE id = $lead_id";
} elseif ($tipo === 'multiple') {
    $ids = $_POST['leads_ids'] ?? [];
    $ids = array_map('intval', $ids);
    if (!empty($ids)) {
        $sql = "SELECT id, nombre, estado, prioridad, canal FROM leads WHERE id IN (" . implode(",", $ids) . ")";
    }
} elseif ($tipo === 'por_criterio') {
    // Filtras según canal, estado, grado, fechas, etc.
    $sql = "SELECT id, nombre, estado, prioridad, canal FROM leads WHERE asignado = 0";
    if (!empty($_POST['canal_id'])) $sql .= " AND canal_id = " . intval($_POST['canal_id']);
    if (!empty($_POST['estado_id'])) $sql .= " AND estado_id = " . intval($_POST['estado_id']);
    if (!empty($_POST['grado_id'])) $sql .= " AND grado_id = " . intval($_POST['grado_id']);
    // aquí metes más filtros de fechas
}

if (isset($sql)) {
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
}

echo json_encode($response);
