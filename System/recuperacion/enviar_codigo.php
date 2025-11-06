<?php
session_start();
require_once '../bd/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

// Validar email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Verificar si el email existe
$stmt = $conn->prepare("SELECT id, nombre, apellidos, activo FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No existe una cuenta con ese correo electrónico']);
    exit;
}

$usuario = $result->fetch_assoc();

if ($usuario['activo'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Esta cuenta está inactiva. Contacta al administrador']);
    exit;
}

// Generar código de 6 dígitos
$codigo = sprintf("%06d", mt_rand(0, 999999));

// Tiempo de expiración: 15 minutos
$expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));
$ip = $_SERVER['REMOTE_ADDR'];

// Invalidar tokens anteriores del mismo usuario
$stmt_invalidar = $conn->prepare("UPDATE tokens_recuperacion SET usado = 1 WHERE usuario_id = ? AND usado = 0");
$stmt_invalidar->bind_param("i", $usuario['id']);
$stmt_invalidar->execute();

// Insertar nuevo token
$stmt_insert = $conn->prepare("INSERT INTO tokens_recuperacion (usuario_id, email, token, expira_en, ip_solicitante) VALUES (?, ?, ?, ?, ?)");
$stmt_insert->bind_param("issss", $usuario['id'], $email, $codigo, $expira, $ip);

if (!$stmt_insert->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al generar código de recuperación']);
    exit;
}

// Obtener configuración SMTP desde la base de datos
$smtp_config = [];
$config_keys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'smtp_from_name', 'nombre_institucion'];

$placeholders = implode(',', array_fill(0, count($config_keys), '?'));
$stmt_config = $conn->prepare("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ($placeholders)");
$stmt_config->bind_param(str_repeat('s', count($config_keys)), ...$config_keys);
$stmt_config->execute();
$result_config = $stmt_config->get_result();

while ($row = $result_config->fetch_assoc()) {
    $smtp_config[$row['clave']] = $row['valor'];
}

// Valores de configuración
$smtp_host = $smtp_config['smtp_host'] ?? 'mail.spaciosolutions.com.pe';
$smtp_port = intval($smtp_config['smtp_port'] ?? 587);
$smtp_username = $smtp_config['smtp_username'] ?? 'sistemas@spaciosolutions.com.pe';
$smtp_password = $smtp_config['smtp_password'] ?? 'AbPOOtAi@(2@(P*X';
$smtp_secure = $smtp_config['smtp_secure'] ?? 'tls';
$smtp_from_name = $smtp_config['smtp_from_name'] ?? 'Sistema CRM Escolar';
$nombre_institucion = $smtp_config['nombre_institucion'] ?? 'CRM Escolar';

// Validar credenciales
if (empty($smtp_username) || empty($smtp_password)) {
    echo json_encode(['success' => false, 'message' => 'Configuración SMTP incompleta']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = $smtp_secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    
    // Configuración para debugging (quitar en producción)
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'error_log';
    
    // Configuración del correo
    $mail->setFrom($smtp_username, $smtp_from_name);
    $mail->addAddress($email, $usuario['nombre'] . ' ' . $usuario['apellidos']);
    $mail->addReplyTo($smtp_username, $smtp_from_name);
    
    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Código de recuperación de contraseña - ' . $nombre_institucion;
    
    $mail->Body = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
            .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: linear-gradient(135deg, #a8e6cf 0%, #dcedc1 100%); padding: 40px 30px; text-align: center; }
            .header h1 { color: #1f2937; margin: 0; font-size: 28px; font-weight: 700; }
            .header p { color: #4b5563; margin: 8px 0 0 0; font-size: 14px; }
            .content { padding: 40px 30px; }
            .greeting { font-size: 16px; margin-bottom: 20px; }
            .greeting strong { color: #1f2937; font-weight: 600; }
            .message { color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 25px; }
            .code-container { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 3px dashed #3b82f6; border-radius: 12px; padding: 30px; margin: 30px 0; text-align: center; }
            .code { font-size: 42px; font-weight: 700; color: #2563eb; letter-spacing: 12px; font-family: 'Courier New', monospace; }
            .warning-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 25px 0; border-radius: 6px; }
            .warning-box strong { color: #92400e; display: block; margin-bottom: 5px; }
            .security-box { background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; margin: 25px 0; border-radius: 6px; }
            .security-box strong { color: #991b1b; display: block; margin-bottom: 5px; }
            .info-block { background: #f9fafb; padding: 16px; border-radius: 8px; margin: 20px 0; font-size: 13px; color: #6b7280; }
            .footer { background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb; }
            .footer p { color: #6b7280; font-size: 13px; margin: 5px 0; }
            .footer strong { color: #374151; }
            @media only screen and (max-width: 600px) {
                .header { padding: 30px 20px; }
                .content { padding: 30px 20px; }
                .code { font-size: 36px; letter-spacing: 8px; }
            }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='header'>
                <h1>🔐 Recuperación de Contraseña</h1>
                <p>{$nombre_institucion}</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Hola <strong>{$usuario['nombre']} {$usuario['apellidos']}</strong>,
                </div>
                
                <div class='message'>
                    Recibimos una solicitud para restablecer la contraseña de tu cuenta en nuestro sistema.
                </div>
                
                <div class='message'>
                    Para continuar con el proceso de recuperación, ingresa el siguiente código de verificación:
                </div>
                
                <div class='code-container'>
                    <div class='code'>{$codigo}</div>
                </div>
                
                <div class='warning-box'>
                    <strong>⏱️ Tiempo de validez</strong>
                    Este código expira en <strong>15 minutos</strong> por razones de seguridad.
                </div>
                
                <div class='message'>
                    Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.
                </div>
                
                <div class='security-box'>
                    <strong>🛡️ Nota de Seguridad</strong>
                    Nunca compartas este código con nadie. Nuestro equipo de soporte NUNCA te pedirá este código.
                </div>
                
                <div class='info-block'>
                    <strong>📋 Información de la solicitud:</strong><br>
                    📍 Dirección IP: <code>{$ip}</code><br>
                    🕐 Fecha y hora: " . date('d/m/Y H:i:s') . " (hora del servidor)
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>{$nombre_institucion}</strong></p>
                <p>&copy; " . date('Y') . " Todos los derechos reservados.</p>
                <p style='margin-top: 15px; font-size: 12px;'>
                    Este es un correo automático. Por favor, no respondas a este mensaje.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Versión texto plano
    $mail->AltBody = "RECUPERACIÓN DE CONTRASEÑA - {$nombre_institucion}\n\n"
                    . "Hola {$usuario['nombre']} {$usuario['apellidos']},\n\n"
                    . "Tu código de recuperación es: {$codigo}\n\n"
                    . "IMPORTANTE: Este código expira en 15 minutos.\n\n"
                    . "Si no solicitaste este cambio, ignora este correo.\n\n"
                    . "Información de la solicitud:\n"
                    . "IP: {$ip}\n"
                    . "Fecha: " . date('d/m/Y H:i:s') . "\n\n"
                    . "---\n"
                    . "{$nombre_institucion}\n"
                    . "Este es un correo automático, no respondas a este mensaje.";
    
    // Enviar correo
    $mail->send();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Código enviado correctamente. Revisa tu bandeja de entrada.',
        'usuario_id' => $usuario['id']
    ]);
    
} catch (Exception $e) {
    error_log("Error al enviar email de recuperación: " . $mail->ErrorInfo);
    
    echo json_encode([
        'success' => false, 
        'message' => 'No se pudo enviar el correo. Por favor, verifica tu conexión o contacta al administrador.'
    ]);
}

$stmt->close();
$stmt_invalidar->close();
$stmt_insert->close();
$stmt_config->close();
$conn->close();
?>
