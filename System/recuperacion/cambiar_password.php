<?php
session_start();
require_once '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$token_id = intval($_POST['token_id'] ?? 0);
$usuario_id = intval($_POST['usuario_id'] ?? 0);
$nueva_password = trim($_POST['nueva_password'] ?? '');
$confirmar_password = trim($_POST['confirmar_password'] ?? '');

// Validaciones
if ($token_id <= 0 || $usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos de sesión inválidos']);
    exit;
}

if (empty($nueva_password) || empty($confirmar_password)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

if (strlen($nueva_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

if ($nueva_password !== $confirmar_password) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
    exit;
}

// Verificar que el token sea válido y no usado
$stmt_check = $conn->prepare("SELECT id FROM tokens_recuperacion WHERE id = ? AND usuario_id = ? AND usado = 0 AND expira_en > NOW() LIMIT 1");
$stmt_check->bind_param("ii", $token_id, $usuario_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Solicita un nuevo código']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Hash de la nueva contraseña
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    // Actualizar contraseña del usuario
    $stmt_update = $conn->prepare("UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $stmt_update->bind_param("si", $password_hash, $usuario_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar contraseña");
    }
    
    // Marcar token como usado
    $stmt_token = $conn->prepare("UPDATE tokens_recuperacion SET usado = 1 WHERE id = ?");
    $stmt_token->bind_param("i", $token_id);
    
    if (!$stmt_token->execute()) {
        throw new Exception("Error al invalidar token");
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    
    $stmt_update->close();
    $stmt_token->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al cambiar contraseña: ' . $e->getMessage()]);
}

$stmt_check->close();
$conn->close();
?>