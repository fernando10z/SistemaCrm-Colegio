<?php
session_start();
include '../../bd/conexion.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../campanas_exalumnos.php');
    exit();
}

$accion = $_POST['accion'] ?? '';
$mensaje = '';
$tipo = 'success';

try {
    switch ($accion) {
        case 'crear_campana':
            $resultado = procesarCrearCampana($conn, $_POST);
            $mensaje = $resultado['mensaje'];
            $tipo = $resultado['tipo'];
            break;
            
        case 'enviar_comunicacion':
            $resultado = procesarEnviarComunicacion($conn, $_POST);
            $mensaje = $resultado['mensaje'];
            $tipo = $resultado['tipo'];
            break;
            
        default:
            $mensaje = 'Acción no válida';
            $tipo = 'error';
            break;
    }
} catch (Exception $e) {
    $mensaje = 'Error: ' . $e->getMessage();
    $tipo = 'error';
}

// Guardar mensaje en sesión y redirigir
$_SESSION['mensaje_sistema'] = $mensaje;
$_SESSION['tipo_mensaje'] = $tipo;
header('Location: ../../campanas_exalumnos.php');
exit();

/**
 * Función para procesar creación de campaña
 */
function procesarCrearCampana($conn, $data) {
    try {
        // Validar datos requeridos
        if (empty($data['tipo_campana']) || empty($data['canal']) || 
            empty($data['asunto']) || empty($data['contenido'])) {
            return ['mensaje' => 'Faltan datos requeridos', 'tipo' => 'error'];
        }
        
        $tipo_campana = $conn->real_escape_string($data['tipo_campana']);
        $canal = $conn->real_escape_string($data['canal']);
        $asunto = $conn->real_escape_string($data['asunto']);
        $contenido = $conn->real_escape_string($data['contenido']);
        $plantilla_id = !empty($data['plantilla_id']) ? (int)$data['plantilla_id'] : NULL;
        $segmentacion = $conn->real_escape_string($data['segmentacion']);
        $observaciones = $conn->real_escape_string($data['observaciones'] ?? '');
        
        // Determinar fecha de envío
        $fecha_envio = !empty($data['fecha_envio']) ? 
                       "'" . $conn->real_escape_string($data['fecha_envio']) . "'" : 
                       "NOW()";
        
        // Obtener destinatarios según segmentación
        $destinatarios = [];
        
        if ($segmentacion === 'todos') {
            // Todos los exalumnos activos
            $query = "SELECT id, nombres, apellidos, email, telefono, whatsapp, promocion_egreso 
                      FROM exalumnos 
                      WHERE estado_contacto = 'activo' AND acepta_comunicaciones = 1";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
            
        } elseif ($segmentacion === 'promocion' && !empty($data['promociones'])) {
            // Por promociones seleccionadas
            $promociones = array_map(function($p) use ($conn) {
                return "'" . $conn->real_escape_string($p) . "'";
            }, $data['promociones']);
            
            $query = "SELECT id, nombres, apellidos, email, telefono, whatsapp, promocion_egreso 
                      FROM exalumnos 
                      WHERE estado_contacto = 'activo' 
                      AND acepta_comunicaciones = 1 
                      AND promocion_egreso IN (" . implode(',', $promociones) . ")";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
            
        } elseif ($segmentacion === 'especificos' && !empty($data['exalumnos'])) {
            // Exalumnos específicos
            $exalumnos = array_map('intval', $data['exalumnos']);
            
            $query = "SELECT id, nombres, apellidos, email, telefono, whatsapp, promocion_egreso 
                      FROM exalumnos 
                      WHERE id IN (" . implode(',', $exalumnos) . ")";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
        }
        
        if (empty($destinatarios)) {
            return ['mensaje' => 'No se encontraron destinatarios válidos', 'tipo' => 'error'];
        }
        
        // Insertar mensajes para cada destinatario
        $mensajes_enviados = 0;
        $mensajes_fallidos = 0;
        
        foreach ($destinatarios as $destinatario) {
            // Personalizar contenido
            $contenido_personalizado = str_replace(
                ['{nombre}', '{apellidos}', '{promocion}'],
                [
                    $destinatario['nombres'], 
                    $destinatario['apellidos'], 
                    $destinatario['promocion_egreso'] ?? ''
                ],
                $contenido
            );
            
            // Determinar destinatario según canal
            $dest_email = NULL;
            $dest_telefono = NULL;
            
            if ($canal === 'email') {
                if (!empty($destinatario['email'])) {
                    $dest_email = "'" . $conn->real_escape_string($destinatario['email']) . "'";
                } else {
                    $mensajes_fallidos++;
                    continue;
                }
            } elseif ($canal === 'whatsapp') {
                if (!empty($destinatario['whatsapp'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['whatsapp']) . "'";
                } elseif (!empty($destinatario['telefono'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['telefono']) . "'";
                } else {
                    $mensajes_fallidos++;
                    continue;
                }
            } elseif ($canal === 'sms') {
                if (!empty($destinatario['telefono'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['telefono']) . "'";
                } else {
                    $mensajes_fallidos++;
                    continue;
                }
            }
            
            // Insertar mensaje
            $sql_insert = "INSERT INTO mensajes_enviados (
                plantilla_id, tipo, asunto, contenido,
                destinatario_email, destinatario_telefono,
                estado, fecha_envio, created_at
            ) VALUES (
                " . ($plantilla_id ? $plantilla_id : "NULL") . ",
                '$canal',
                '$asunto',
                '" . $conn->real_escape_string($contenido_personalizado) . "',
                " . ($dest_email ?? "NULL") . ",
                " . ($dest_telefono ?? "NULL") . ",
                'pendiente',
                $fecha_envio,
                NOW()
            )";
            
            if ($conn->query($sql_insert)) {
                $mensajes_enviados++;
                
                // Actualizar estado a 'enviado' si es envío inmediato
                if ($fecha_envio === "NOW()") {
                    $mensaje_id = $conn->insert_id;
                    $conn->query("UPDATE mensajes_enviados SET estado = 'enviado' WHERE id = $mensaje_id");
                }
            } else {
                $mensajes_fallidos++;
            }
        }
        
        $mensaje_resultado = "Campaña creada exitosamente. " .
                           "Mensajes programados: $mensajes_enviados";
        
        if ($mensajes_fallidos > 0) {
            $mensaje_resultado .= ". Fallidos: $mensajes_fallidos (sin contacto válido)";
        }
        
        return [
            'mensaje' => $mensaje_resultado,
            'tipo' => 'success'
        ];
        
    } catch (Exception $e) {
        return [
            'mensaje' => 'Error al crear campaña: ' . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}

/**
 * Función para procesar envío de comunicación rápida
 */
function procesarEnviarComunicacion($conn, $data) {
    try {
        // Validar datos requeridos
        if (empty($data['tipo_mensaje']) || empty($data['canal']) || 
            empty($data['asunto']) || empty($data['mensaje'])) {
            return ['mensaje' => 'Faltan datos requeridos', 'tipo' => 'error'];
        }
        
        $tipo_mensaje = $conn->real_escape_string($data['tipo_mensaje']);
        $canal = $conn->real_escape_string($data['canal']);
        $asunto = $conn->real_escape_string($data['asunto']);
        $mensaje = $conn->real_escape_string($data['mensaje']);
        $tipo_destinatario = $conn->real_escape_string($data['tipo_destinatario']);
        $envio_inmediato = isset($data['envio_inmediato']) && $data['envio_inmediato'] == '1';
        
        // Obtener destinatarios
        $destinatarios = [];
        
        if ($tipo_destinatario === 'promocion' && !empty($data['promocion'])) {
            $promocion = $conn->real_escape_string($data['promocion']);
            
            $query = "SELECT id, nombres, apellidos, email, telefono, whatsapp, promocion_egreso 
                      FROM exalumnos 
                      WHERE estado_contacto = 'activo' 
                      AND promocion_egreso = '$promocion'";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
            
        } elseif ($tipo_destinatario === 'individual' && !empty($data['exalumno_id'])) {
            $exalumno_id = (int)$data['exalumno_id'];
            
            $query = "SELECT id, nombres, apellidos, email, telefono, whatsapp, promocion_egreso 
                      FROM exalumnos 
                      WHERE id = $exalumno_id";
            $result = $conn->query($query);
            if ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
        }
        
        if (empty($destinatarios)) {
            return ['mensaje' => 'No se encontraron destinatarios válidos', 'tipo' => 'error'];
        }
        
        // Enviar mensaje a cada destinatario
        $enviados = 0;
        $fallidos = 0;
        
        foreach ($destinatarios as $destinatario) {
            // Personalizar mensaje
            $mensaje_personalizado = str_replace(
                ['{nombre}', '{apellidos}', '{promocion}'],
                [
                    $destinatario['nombres'], 
                    $destinatario['apellidos'], 
                    $destinatario['promocion_egreso'] ?? ''
                ],
                $mensaje
            );
            
            // Determinar destinatario según canal
            $dest_email = NULL;
            $dest_telefono = NULL;
            
            if ($canal === 'email') {
                if (!empty($destinatario['email'])) {
                    $dest_email = "'" . $conn->real_escape_string($destinatario['email']) . "'";
                } else {
                    $fallidos++;
                    continue;
                }
            } elseif ($canal === 'whatsapp') {
                if (!empty($destinatario['whatsapp'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['whatsapp']) . "'";
                } elseif (!empty($destinatario['telefono'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['telefono']) . "'";
                } else {
                    $fallidos++;
                    continue;
                }
            } elseif ($canal === 'sms') {
                if (!empty($destinatario['telefono'])) {
                    $dest_telefono = "'" . $conn->real_escape_string($destinatario['telefono']) . "'";
                } else {
                    $fallidos++;
                    continue;
                }
            }
            
            // Estado según envío
            $estado = $envio_inmediato ? 'enviado' : 'pendiente';
            $fecha_envio = $envio_inmediato ? 'NOW()' : 'NOW()';
            
            // Insertar mensaje
            $sql = "INSERT INTO mensajes_enviados (
                tipo, asunto, contenido,
                destinatario_email, destinatario_telefono,
                estado, fecha_envio, created_at
            ) VALUES (
                '$canal',
                '$asunto',
                '" . $conn->real_escape_string($mensaje_personalizado) . "',
                " . ($dest_email ?? "NULL") . ",
                " . ($dest_telefono ?? "NULL") . ",
                '$estado',
                $fecha_envio,
                NOW()
            )";
            
            if ($conn->query($sql)) {
                $enviados++;
            } else {
                $fallidos++;
            }
        }
        
        $mensaje_resultado = "Comunicación enviada exitosamente a $enviados destinatarios";
        
        if ($fallidos > 0) {
            $mensaje_resultado .= ". Fallidos: $fallidos";
        }
        
        return [
            'mensaje' => $mensaje_resultado,
            'tipo' => 'success'
        ];
        
    } catch (Exception $e) {
        return [
            'mensaje' => 'Error al enviar comunicación: ' . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}
?>