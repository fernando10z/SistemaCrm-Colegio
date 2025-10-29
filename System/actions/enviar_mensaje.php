<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (ob_get_level()) ob_end_clean();
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

function responder($success, $message, $data = []) {
    ob_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(false, 'Sesión no válida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido');
}

$conexion_path = __DIR__ . '/../bd/conexion.php';
if (!file_exists($conexion_path)) {
    $conexion_path = dirname(__DIR__) . '/bd/conexion.php';
}

require_once $conexion_path;

if (!isset($conn) || $conn->connect_error) {
    responder(false, 'Error de conexión a la base de datos');
}

function limpiar_dato($dato) {
    if (is_null($dato)) return null;
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

function validar_email($email) {
    return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validar_telefono($telefono) {
    return !empty($telefono) && preg_match('/^\+?[0-9]{9,15}$/', str_replace([' ', '-', '(', ')'], '', $telefono));
}

try {
    $tipo = limpiar_dato($_POST['tipo'] ?? '');
    $plantilla_id = !empty($_POST['plantilla_id']) ? intval($_POST['plantilla_id']) : null;
    $asunto = limpiar_dato($_POST['asunto'] ?? '');
    $contenido = $_POST['contenido'] ?? '';
    $destinatarios_json = $_POST['destinatarios'] ?? '';
    
    // Obtener usuario_id de la sesión
    $usuario_id = intval($_SESSION['usuario_id']);

    if (!in_array($tipo, ['email', 'whatsapp', 'sms'])) {
        responder(false, 'Tipo de mensaje no válido');
    }

    if (empty($contenido)) {
        responder(false, 'El contenido es obligatorio');
    }

    if ($tipo === 'email' && empty($asunto)) {
        responder(false, 'El asunto es obligatorio para emails');
    }

    if ($tipo === 'sms' && mb_strlen($contenido) > 160) {
        responder(false, 'El SMS no puede exceder 160 caracteres');
    }

    if ($tipo === 'whatsapp' && mb_strlen($contenido) > 1600) {
        responder(false, 'El WhatsApp no puede exceder 1600 caracteres');
    }

    $destinatarios = json_decode($destinatarios_json, true);
    if (!is_array($destinatarios) || empty($destinatarios)) {
        responder(false, 'Debe seleccionar al menos un destinatario');
    }

    $conn->begin_transaction();

    $mensajes_creados = 0;
    $errores = [];

    // ESTRUCTURA REAL CON usuario_id (11 columnas en total)
    $stmt = $conn->prepare("
        INSERT INTO mensajes_enviados 
        (tipo, plantilla_id, lead_id, apoderado_id, usuario_id,
         destinatario_email, destinatario_telefono, asunto, contenido, 
         estado, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ");

    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conn->error);
    }

    foreach ($destinatarios as $index => $dest) {
        try {
            $lead_id = null;
            $apoderado_id = null;
            $email = null;
            $telefono = null;

            if (!isset($dest['tipo'], $dest['id'])) {
                $errores[] = "Destinatario #" . ($index + 1) . ": datos incompletos";
                continue;
            }

            $dest_id = intval($dest['id']);
            $dest_nombre = $dest['nombre'] ?? 'Desconocido';

            if ($dest['tipo'] === 'lead') {
                // COLUMNAS CORRECTAS: telefono, whatsapp, email (sin _contacto)
                $stmt_dest = $conn->prepare("
                    SELECT nombres_estudiante, apellidos_estudiante, 
                           email, telefono, whatsapp,
                           nombres_contacto, apellidos_contacto
                    FROM leads WHERE id = ?
                ");
                $stmt_dest->bind_param("i", $dest_id);
                $stmt_dest->execute();
                $result = $stmt_dest->get_result();
                $data = $result->fetch_assoc();
                $stmt_dest->close();

                if ($data) {
                    $lead_id = $dest_id;
                    $email = $data['email'];
                    
                    // Priorizar whatsapp si es mensaje de WhatsApp, sino usar telefono
                    if ($tipo === 'whatsapp' && !empty($data['whatsapp'])) {
                        $telefono = $data['whatsapp'];
                    } else {
                        $telefono = $data['telefono'];
                    }
                    
                    // Para personalización, usar nombres del contacto
                    $nombre = $data['nombres_contacto'] ?? $data['nombres_estudiante'];
                    $apellido = $data['apellidos_contacto'] ?? $data['apellidos_estudiante'];
                } else {
                    $errores[] = "Lead '$dest_nombre' no encontrado";
                    continue;
                }
            } 
            else if ($dest['tipo'] === 'apoderado') {
                $stmt_dest = $conn->prepare("
                    SELECT nombres, apellidos, email, 
                    COALESCE(whatsapp, telefono_principal, telefono_secundario) as telefono 
                    FROM apoderados WHERE id = ?
                ");
                $stmt_dest->bind_param("i", $dest_id);
                $stmt_dest->execute();
                $result = $stmt_dest->get_result();
                $data = $result->fetch_assoc();
                $stmt_dest->close();

                if ($data) {
                    $apoderado_id = $dest_id;
                    $email = $data['email'];
                    $telefono = $data['telefono'];
                    $nombre = $data['nombres'];
                    $apellido = $data['apellidos'];
                } else {
                    $errores[] = "Apoderado '$dest_nombre' no encontrado";
                    continue;
                }
            } else {
                $errores[] = "Tipo no válido: {$dest['tipo']}";
                continue;
            }

            // Validaciones según tipo de mensaje
            if ($tipo === 'email' && !validar_email($email)) {
                $errores[] = "Email no válido para '$dest_nombre'";
                continue;
            }
            
            if (($tipo === 'whatsapp' || $tipo === 'sms') && !validar_telefono($telefono)) {
                $errores[] = "Teléfono no válido para '$dest_nombre'";
                continue;
            }

            // Personalización del contenido
            $contenido_personalizado = str_replace(
                ['{nombre}', '{apellido}', '{email}', '{telefono}'],
                [$nombre ?? '', $apellido ?? '', $email ?? '', $telefono ?? ''],
                $contenido
            );

            $asunto_personalizado = str_replace(
                ['{nombre}', '{apellido}', '{email}', '{telefono}'],
                [$nombre ?? '', $apellido ?? '', $email ?? '', $telefono ?? ''],
                $asunto
            );

            // 9 PARÁMETROS: s i i i i s s s s
            $stmt->bind_param(
                "siiisssss",
                $tipo,
                $plantilla_id,
                $lead_id,
                $apoderado_id,
                $usuario_id,        // ¡IMPORTANTE! Incluir usuario_id
                $email,
                $telefono,
                $asunto_personalizado,
                $contenido_personalizado
            );

            if ($stmt->execute()) {
                $mensajes_creados++;
            } else {
                $errores[] = "Error al insertar '$dest_nombre': " . $stmt->error;
            }

        } catch (Exception $e) {
            $errores[] = "Error procesando '$dest_nombre': " . $e->getMessage();
        }
    }

    $stmt->close();

    if ($mensajes_creados > 0) {
        $conn->commit();
        responder(true, "$mensajes_creados mensaje(s) creado(s)", [
            'creados' => $mensajes_creados,
            'errores' => $errores
        ]);
    } else {
        $conn->rollback();
        responder(false, "No se pudo crear ningún mensaje. " . implode("; ", array_slice($errores, 0, 3)), [
            'errores' => $errores
        ]);
    }

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    responder(false, 'Error: ' . $e->getMessage());
} finally {
    if (isset($conn)) $conn->close();
}
?>