<?php
// acciones/incentivos_referidos/buscar_lead.php
session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

$termino = trim($_POST['termino'] ?? '');

if (strlen($termino) < 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Ingrese al menos 3 caracteres'
    ]);
    exit();
}

$search = "%{$termino}%";

$sql = "SELECT l.id, l.nombres_estudiante, l.apellidos_estudiante,
        l.nombres_contacto, l.apellidos_contacto, l.telefono, l.email,
        el.nombre as estado_nombre
        FROM leads l
        INNER JOIN estados_lead el ON l.estado_lead_id = el.id
        WHERE l.activo = 1 
        AND l.fecha_conversion IS NULL
        AND (l.nombres_estudiante LIKE ? 
             OR l.apellidos_estudiante LIKE ?
             OR l.nombres_contacto LIKE ?
             OR l.apellidos_contacto LIKE ?
             OR l.numero_documento LIKE ?
             OR l.email LIKE ?)
        ORDER BY l.created_at DESC
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $search, $search, $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$leads = [];
while ($row = $result->fetch_assoc()) {
    $leads[] = $row;
}

echo json_encode([
    'success' => count($leads) > 0,
    'leads' => $leads,
    'message' => count($leads) > 0 ? '' : 'No se encontraron leads'
]);
?>