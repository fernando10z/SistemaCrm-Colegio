<?php
session_start();
require_once '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$codigo = trim($_POST['codigo'] ?? '');

// Validaciones
if (empty($email) || empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Email y código son requeridos']);
    exit;
}

if (!preg_match('/^\d{6}$/', $codigo)) {
    echo json_encode(['success' => false, 'message' => 'El código debe ser de 6 dígitos']);
    exit;
}

// Verificar código
$stmt = $conn->prepare("
    SELECT t.id, t.usuario_id, t.token, t.expira_en, t.usado, u.email 
    FROM tokens_recuperacion t
    INNER JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.email = ? AND t.token = ? AND t.usado = 0 AND t.expira_en > NOW()
    ORDER BY t.created_at DESC
    LIMIT 1
");
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Código inválido o expirado']);
    exit;
}

$token_data = $result->fetch_assoc();

echo json_encode([
    'success' => true, 
    'message' => 'Código verificado correctamente',
    'token_id' => $token_data['id'],
    'usuario_id' => $token_data['usuario_id']
]);

$stmt->close();
$conn->close();
?>