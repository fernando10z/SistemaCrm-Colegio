<?php
// acciones/incentivos_referidos/validar_codigo.php
session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$codigo_id = intval($_POST['codigo_id'] ?? 0);

if (empty($codigo)) {
    echo json_encode(['disponible' => false]);
    exit();
}

$sql = "SELECT id FROM codigos_referido WHERE codigo = ? AND id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $codigo, $codigo_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode([
    'disponible' => $result->num_rows === 0
]);
?>