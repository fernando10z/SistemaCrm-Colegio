<?php
require_once 'bd/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'recuperacion/vendor/autoload.php';

try {
    $mail = new PHPMailer(true);
    
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = 'mail.spaciosolutions.com.pe';
    $mail->SMTPAuth = true;
    $mail->Username = 'sistemas@spaciosolutions.com.pe';
    $mail->Password = 'AbPOOtAi@(2@(P*X';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Debug (ver qué pasa)
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    
    // Configuración del correo
    $mail->setFrom('administracion@zeit.spaciosolutions.com.pe', 'Sistema CRM Test');
    $mail->addAddress('TU_EMAIL_PERSONAL@gmail.com', 'Tu Nombre'); // CAMBIA ESTO
    
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de envío SMTP';
    $mail->Body = '<h1>¡Funciona!</h1><p>El servidor SMTP está configurado correctamente.</p>';
    $mail->AltBody = 'Funciona! El servidor SMTP está configurado correctamente.';
    
    $mail->send();
    echo '<div style="padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">
            ✓ Correo enviado exitosamente!
          </div>';
    
} catch (Exception $e) {
    echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">
            ✗ Error: ' . $mail->ErrorInfo . '
          </div>';
}
?>