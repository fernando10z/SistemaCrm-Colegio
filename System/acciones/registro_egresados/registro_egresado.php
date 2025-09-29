<?php
session_start();
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../registro_egresados.php');
    exit();
}

// Verificar que exista la acción
if (!isset($_POST['accion'])) {
    $_SESSION['mensaje_sistema'] = 'Error: No se especificó la acción a realizar';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: ../../registro_egresados.php');
    exit();
}

$accion = $_POST['accion'];
$mensaje_sistema = '';
$tipo_mensaje = '';

try {
    switch ($accion) {
        case 'registrar_egresado':
            $resultado = procesarRegistrarEgresado($conn, $_POST);
            break;
            
        case 'editar_egresado':
            $resultado = procesarEditarEgresado($conn, $_POST);
            break;
            
        case 'gestionar_estado':
            $resultado = procesarGestionarEstado($conn, $_POST);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    $mensaje_sistema = $resultado['mensaje'];
    $tipo_mensaje = $resultado['tipo'];
    
} catch (Exception $e) {
    $mensaje_sistema = 'Error del sistema: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}

// Establecer mensaje en sesión y redireccionar
$_SESSION['mensaje_sistema'] = $mensaje_sistema;
$_SESSION['tipo_mensaje'] = $tipo_mensaje;
header('Location: ../../registro_egresados.php');
exit();

// ==========================================
// FUNCIÓN: REGISTRAR NUEVO EGRESADO
// ==========================================
function procesarRegistrarEgresado($conn, $data) {
    try {
        // Validaciones del servidor
        $errores = validarDatosEgresado($data, 'registrar');
        
        if (!empty($errores)) {
            throw new Exception('Errores de validación: ' . implode(', ', $errores));
        }
        
        // Verificar que no exista el código de egresado
        $codigo_exalumno = trim($data['codigo_exalumno']);
        $stmt_codigo = $conn->prepare("SELECT id FROM exalumnos WHERE codigo_exalumno = ?");
        $stmt_codigo->bind_param("s", $codigo_exalumno);
        $stmt_codigo->execute();
        if ($stmt_codigo->get_result()->num_rows > 0) {
            throw new Exception('El código de egresado ya existe');
        }
        $stmt_codigo->close();
        
        // Verificar que no exista el número de documento
        $numero_documento = trim($data['numero_documento']);
        $tipo_documento = trim($data['tipo_documento']);
        $stmt_doc = $conn->prepare("SELECT id FROM exalumnos WHERE numero_documento = ? AND tipo_documento = ?");
        $stmt_doc->bind_param("ss", $numero_documento, $tipo_documento);
        $stmt_doc->execute();
        if ($stmt_doc->get_result()->num_rows > 0) {
            throw new Exception('Ya existe un egresado con este número de documento');
        }
        $stmt_doc->close();
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Preparar datos para inserción
        $nombres = trim($data['nombres']);
        $apellidos = trim($data['apellidos']);
        $email = !empty($data['email']) ? trim($data['email']) : null;
        $telefono = !empty($data['telefono']) ? trim($data['telefono']) : null;
        $whatsapp = !empty($data['whatsapp']) ? trim($data['whatsapp']) : null;
        $promocion_egreso = !empty($data['promocion_egreso']) ? trim($data['promocion_egreso']) : null;
        $fecha_egreso = !empty($data['fecha_egreso']) ? trim($data['fecha_egreso']) : null;
        $ultimo_grado = !empty($data['ultimo_grado']) ? trim($data['ultimo_grado']) : null;
        $ocupacion_actual = !empty($data['ocupacion_actual']) ? trim($data['ocupacion_actual']) : null;
        $empresa_actual = !empty($data['empresa_actual']) ? trim($data['empresa_actual']) : null;
        $estudios_superiores = !empty($data['estudios_superiores']) ? trim($data['estudios_superiores']) : null;
        $direccion_actual = !empty($data['direccion_actual']) ? trim($data['direccion_actual']) : null;
        $distrito_actual = !empty($data['distrito_actual']) ? trim($data['distrito_actual']) : null;
        $estado_contacto = trim($data['estado_contacto']);
        $acepta_comunicaciones = isset($data['acepta_comunicaciones']) ? 1 : 0;
        $observaciones = !empty($data['observaciones']) ? trim($data['observaciones']) : null;
        
        // SQL de inserción
        $sql = "INSERT INTO exalumnos (
                    codigo_exalumno, tipo_documento, numero_documento, nombres, apellidos,
                    email, telefono, whatsapp, promocion_egreso, fecha_egreso, ultimo_grado,
                    ocupacion_actual, empresa_actual, estudios_superiores, direccion_actual,
                    distrito_actual, estado_contacto, acepta_comunicaciones, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssssssss", 
            $codigo_exalumno, $tipo_documento, $numero_documento, $nombres, $apellidos,
            $email, $telefono, $whatsapp, $promocion_egreso, $fecha_egreso, $ultimo_grado,
            $ocupacion_actual, $empresa_actual, $estudios_superiores, $direccion_actual,
            $distrito_actual, $estado_contacto, $acepta_comunicaciones, $observaciones
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al insertar el egresado: ' . $stmt->error);
        }
        
        $egresado_id = $conn->insert_id;
        $stmt->close();
        
        // Registrar en log de sistema (si existe la tabla)
        registrarLogSistema($conn, 'Registro de Egresado', "Nuevo egresado registrado: $nombres $apellidos (ID: $egresado_id)");
        
        // Confirmar transacción
        $conn->commit();
        
        return [
            'mensaje' => "Egresado $nombres $apellidos registrado exitosamente con código $codigo_exalumno",
            'tipo' => 'success'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'mensaje' => 'Error al registrar egresado: ' . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}

// ==========================================
// FUNCIÓN: EDITAR EGRESADO EXISTENTE
// ==========================================
function procesarEditarEgresado($conn, $data) {
    try {
        // Validar que existe el ID del egresado
        if (!isset($data['egresado_id']) || empty($data['egresado_id'])) {
            throw new Exception('ID de egresado no especificado');
        }
        
        $egresado_id = intval($data['egresado_id']);
        
        // Verificar que el egresado existe
        $stmt_existe = $conn->prepare("SELECT codigo_exalumno, nombres, apellidos FROM exalumnos WHERE id = ?");
        $stmt_existe->bind_param("i", $egresado_id);
        $stmt_existe->execute();
        $resultado_existe = $stmt_existe->get_result();
        
        if ($resultado_existe->num_rows === 0) {
            throw new Exception('El egresado especificado no existe');
        }
        
        $egresado_actual = $resultado_existe->fetch_assoc();
        $stmt_existe->close();
        
        // Validaciones del servidor
        $errores = validarDatosEgresado($data, 'editar');
        
        if (!empty($errores)) {
            throw new Exception('Errores de validación: ' . implode(', ', $errores));
        }
        
        // Verificar que no exista otro egresado con el mismo documento
        $numero_documento = trim($data['numero_documento']);
        $tipo_documento = trim($data['tipo_documento']);
        $stmt_doc = $conn->prepare("SELECT id FROM exalumnos WHERE numero_documento = ? AND tipo_documento = ? AND id != ?");
        $stmt_doc->bind_param("ssi", $numero_documento, $tipo_documento, $egresado_id);
        $stmt_doc->execute();
        if ($stmt_doc->get_result()->num_rows > 0) {
            throw new Exception('Ya existe otro egresado con este número de documento');
        }
        $stmt_doc->close();
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Preparar datos para actualización
        $nombres = trim($data['nombres']);
        $apellidos = trim($data['apellidos']);
        $email = !empty($data['email']) ? trim($data['email']) : null;
        $telefono = !empty($data['telefono']) ? trim($data['telefono']) : null;
        $whatsapp = !empty($data['whatsapp']) ? trim($data['whatsapp']) : null;
        $ocupacion_actual = !empty($data['ocupacion_actual']) ? trim($data['ocupacion_actual']) : null;
        $empresa_actual = !empty($data['empresa_actual']) ? trim($data['empresa_actual']) : null;
        $estudios_superiores = !empty($data['estudios_superiores']) ? trim($data['estudios_superiores']) : null;
        $direccion_actual = !empty($data['direccion_actual']) ? trim($data['direccion_actual']) : null;
        $distrito_actual = !empty($data['distrito_actual']) ? trim($data['distrito_actual']) : null;
        $estado_contacto = trim($data['estado_contacto']);
        $acepta_comunicaciones = isset($data['acepta_comunicaciones']) ? 1 : 0;
        $observaciones = !empty($data['observaciones']) ? trim($data['observaciones']) : null;
        
        // SQL de actualización
        $sql = "UPDATE exalumnos SET 
                    tipo_documento = ?, numero_documento = ?, nombres = ?, apellidos = ?,
                    email = ?, telefono = ?, whatsapp = ?, ocupacion_actual = ?, empresa_actual = ?,
                    estudios_superiores = ?, direccion_actual = ?, distrito_actual = ?,
                    estado_contacto = ?, acepta_comunicaciones = ?, observaciones = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssssi", 
            $tipo_documento, $numero_documento, $nombres, $apellidos,
            $email, $telefono, $whatsapp, $ocupacion_actual, $empresa_actual,
            $estudios_superiores, $direccion_actual, $distrito_actual,
            $estado_contacto, $acepta_comunicaciones, $observaciones, $egresado_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar el egresado: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Registrar en log de sistema
        registrarLogSistema($conn, 'Edición de Egresado', "Datos actualizados del egresado: $nombres $apellidos (ID: $egresado_id)");
        
        // Confirmar transacción
        $conn->commit();
        
        return [
            'mensaje' => "Información del egresado $nombres $apellidos actualizada exitosamente",
            'tipo' => 'success'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'mensaje' => 'Error al editar egresado: ' . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}

// ==========================================
// FUNCIÓN: GESTIONAR ESTADO DE CONTACTO
// ==========================================
function procesarGestionarEstado($conn, $data) {
    try {
        // Validar que exists el ID del egresado
        if (!isset($data['egresado_id']) || empty($data['egresado_id'])) {
            throw new Exception('ID de egresado no especificado');
        }
        
        $egresado_id = intval($data['egresado_id']);
        
        // Verificar que el egresado existe y obtener datos actuales
        $stmt_existe = $conn->prepare("SELECT codigo_exalumno, nombres, apellidos, estado_contacto, acepta_comunicaciones FROM exalumnos WHERE id = ?");
        $stmt_existe->bind_param("i", $egresado_id);
        $stmt_existe->execute();
        $resultado_existe = $stmt_existe->get_result();
        
        if ($resultado_existe->num_rows === 0) {
            throw new Exception('El egresado especificado no existe');
        }
        
        $egresado_actual = $resultado_existe->fetch_assoc();
        $stmt_existe->close();
        
        // Validaciones específicas para gestión de estado
        $errores = [];
        
        if (empty($data['nuevo_estado_contacto'])) {
            $errores[] = 'Debe seleccionar un nuevo estado de contacto';
        }
        
        if (empty($data['motivo_cambio'])) {
            $errores[] = 'Debe especificar el motivo del cambio';
        }
        
        if (empty($data['fecha_cambio'])) {
            $errores[] = 'Debe especificar la fecha del cambio';
        }
        
        if (empty($data['observaciones_cambio']) || strlen(trim($data['observaciones_cambio'])) < 20) {
            $errores[] = 'Las observaciones deben tener al menos 20 caracteres';
        }
        
        if (!empty($errores)) {
            throw new Exception('Errores de validación: ' . implode(', ', $errores));
        }
        
        // Validar que el nuevo estado sea diferente
        $nuevo_estado = trim($data['nuevo_estado_contacto']);
        if ($nuevo_estado === $egresado_actual['estado_contacto']) {
            throw new Exception('El nuevo estado debe ser diferente al estado actual');
        }
        
        // Validar fecha (no puede ser futura)
        $fecha_cambio = trim($data['fecha_cambio']);
        if (strtotime($fecha_cambio) > time()) {
            throw new Exception('La fecha del cambio no puede ser posterior a hoy');
        }
        
        // Validar estados permitidos
        $estados_permitidos = ['activo', 'sin_contacto', 'no_contactar'];
        if (!in_array($nuevo_estado, $estados_permitidos)) {
            throw new Exception('Estado de contacto no válido');
        }
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Preparar datos
        $motivo_cambio = trim($data['motivo_cambio']);
        $observaciones_cambio = trim($data['observaciones_cambio']);
        $medio_verificacion = !empty($data['medio_verificacion']) ? trim($data['medio_verificacion']) : null;
        
        // Configurar comunicaciones según el nuevo estado y la selección del usuario
        $acepta_comunicaciones_nuevo = $egresado_actual['acepta_comunicaciones']; // Mantener por defecto
        
        if (isset($data['acepta_comunicaciones_cambio'])) {
            $acepta_config = $data['acepta_comunicaciones_cambio'];
            if ($acepta_config === '1') {
                $acepta_comunicaciones_nuevo = 1;
            } elseif ($acepta_config === '0') {
                $acepta_comunicaciones_nuevo = 0;
            }
            // Si es 'mantener', no cambiar
        }
        
        // Si el estado no es activo, automáticamente no acepta comunicaciones
        if ($nuevo_estado !== 'activo') {
            $acepta_comunicaciones_nuevo = 0;
        }
        
        // Construir observaciones completas
        $observaciones_completas = "[CAMBIO DE ESTADO - " . date('Y-m-d H:i:s') . "]\n";
        $observaciones_completas .= "Estado anterior: " . $egresado_actual['estado_contacto'] . "\n";
        $observaciones_completas .= "Nuevo estado: $nuevo_estado\n";
        $observaciones_completas .= "Motivo: $motivo_cambio\n";
        $observaciones_completas .= "Fecha efectiva: $fecha_cambio\n";
        if ($medio_verificacion) {
            $observaciones_completas .= "Medio de verificación: $medio_verificacion\n";
        }
        $observaciones_completas .= "Observaciones: $observaciones_cambio\n";
        $observaciones_completas .= "---\n";
        
        // Concatenar con observaciones existentes
        $observaciones_finales = $observaciones_completas;
        if (!empty($egresado_actual['observaciones'])) {
            $observaciones_finales .= $egresado_actual['observaciones'];
        }
        
        // SQL de actualización
        $sql = "UPDATE exalumnos SET 
                    estado_contacto = ?, 
                    acepta_comunicaciones = ?,
                    observaciones = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $nuevo_estado, $acepta_comunicaciones_nuevo, $observaciones_finales, $egresado_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar el estado: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Registrar en log de sistema
        $log_mensaje = "Estado de contacto cambiado para {$egresado_actual['nombres']} {$egresado_actual['apellidos']} (ID: $egresado_id): {$egresado_actual['estado_contacto']} → $nuevo_estado. Motivo: $motivo_cambio";
        registrarLogSistema($conn, 'Gestión de Estado', $log_mensaje);
        
        // Confirmar transacción
        $conn->commit();
        
        $estado_texto = ucfirst(str_replace('_', ' ', $nuevo_estado));
        return [
            'mensaje' => "Estado de contacto de {$egresado_actual['nombres']} {$egresado_actual['apellidos']} cambiado a: $estado_texto",
            'tipo' => 'success'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'mensaje' => 'Error al gestionar estado: ' . $e->getMessage(),
            'tipo' => 'error'
        ];
    }
}

// ==========================================
// FUNCIÓN: VALIDAR DATOS DEL EGRESADO
// ==========================================
function validarDatosEgresado($data, $tipo = 'registrar') {
    $errores = [];
    
    // Validar código de egresado (solo en registro)
    if ($tipo === 'registrar') {
        if (empty($data['codigo_exalumno'])) {
            $errores[] = 'El código de egresado es obligatorio';
        } elseif (!preg_match('/^EX[0-9]{7}$/', trim($data['codigo_exalumno']))) {
            $errores[] = 'El código debe tener el formato EX seguido de 7 dígitos';
        }
    }
    
    // Validar tipo de documento
    if (empty($data['tipo_documento'])) {
        $errores[] = 'El tipo de documento es obligatorio';
    } elseif (!in_array($data['tipo_documento'], ['DNI', 'CE', 'pasaporte'])) {
        $errores[] = 'Tipo de documento no válido';
    }
    
    // Validar número de documento según el tipo
    if (empty($data['numero_documento'])) {
        $errores[] = 'El número de documento es obligatorio';
    } else {
        $numero_doc = trim($data['numero_documento']);
        $tipo_doc = $data['tipo_documento'];
        
        switch ($tipo_doc) {
            case 'DNI':
                if (!preg_match('/^[0-9]{8}$/', $numero_doc)) {
                    $errores[] = 'DNI debe tener exactamente 8 dígitos';
                }
                break;
            case 'CE':
                if (!preg_match('/^[0-9]{9}$/', $numero_doc)) {
                    $errores[] = 'Carnet de Extranjería debe tener exactamente 9 dígitos';
                }
                break;
            case 'pasaporte':
                if (!preg_match('/^[A-Z0-9]{6,12}$/', $numero_doc)) {
                    $errores[] = 'Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos';
                }
                break;
        }
    }
    
    // Validar nombres
    if (empty($data['nombres'])) {
        $errores[] = 'Los nombres son obligatorios';
    } elseif (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{2,50}$/', trim($data['nombres']))) {
        $errores[] = 'Los nombres solo pueden contener letras y espacios (2-50 caracteres)';
    }
    
    // Validar apellidos
    if (empty($data['apellidos'])) {
        $errores[] = 'Los apellidos son obligatorios';
    } elseif (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{2,50}$/', trim($data['apellidos']))) {
        $errores[] = 'Los apellidos solo pueden contener letras y espacios (2-50 caracteres)';
    }
    
    // Validar email (opcional pero debe ser válido si se proporciona)
    if (!empty($data['email']) && !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del email no es válido';
    }
    
    // Validar teléfono (opcional pero debe ser válido si se proporciona)
    if (!empty($data['telefono']) && !preg_match('/^(\+51|51)?[0-9]{9}$/', trim($data['telefono']))) {
        $errores[] = 'El formato del teléfono no es válido (formato peruano)';
    }
    
    // Validar WhatsApp (opcional pero debe ser válido si se proporciona)
    if (!empty($data['whatsapp']) && !preg_match('/^(\+51|51)?[0-9]{9}$/', trim($data['whatsapp']))) {
        $errores[] = 'El formato del WhatsApp no es válido (formato peruano)';
    }
    
    // Validar estado de contacto
    if (empty($data['estado_contacto'])) {
        $errores[] = 'El estado de contacto es obligatorio';
    } elseif (!in_array($data['estado_contacto'], ['activo', 'sin_contacto', 'no_contactar'])) {
        $errores[] = 'Estado de contacto no válido';
    }
    
    // Validar promoción de egreso (solo en registro)
    if ($tipo === 'registrar' && !empty($data['promocion_egreso'])) {
        $promocion = intval($data['promocion_egreso']);
        if ($promocion < 1980 || $promocion > date('Y')) {
            $errores[] = 'El año de promoción debe estar entre 1980 y ' . date('Y');
        }
    }
    
    // Validar fecha de egreso (si se proporciona)
    if (!empty($data['fecha_egreso'])) {
        $fecha_egreso = strtotime($data['fecha_egreso']);
        if ($fecha_egreso === false || $fecha_egreso > time()) {
            $errores[] = 'La fecha de egreso no es válida o es posterior a hoy';
        }
    }
    
    // Validar longitud de campos de texto
    $campos_texto = [
        'ocupacion_actual' => 100,
        'empresa_actual' => 100,
        'estudios_superiores' => 150,
        'direccion_actual' => 200,
        'distrito_actual' => 50,
        'observaciones' => 500
    ];
    
    foreach ($campos_texto as $campo => $max_length) {
        if (!empty($data[$campo]) && strlen(trim($data[$campo])) > $max_length) {
            $errores[] = ucfirst(str_replace('_', ' ', $campo)) . " no puede exceder $max_length caracteres";
        }
    }
    
    return $errores;
}

// ==========================================
// FUNCIÓN: REGISTRAR LOG DEL SISTEMA
// ==========================================
function registrarLogSistema($conn, $accion, $descripcion) {
    try {
        // Verificar si existe la tabla de logs
        $resultado = $conn->query("SHOW TABLES LIKE 'logs_acceso'");
        if ($resultado && $resultado->num_rows > 0) {
            $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1; // Usuario sistema por defecto
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Sistema CRM';
            
            $stmt_log = $conn->prepare("INSERT INTO logs_acceso (usuario_id, ip_address, user_agent, accion, resultado, detalles) VALUES (?, ?, ?, ?, 'exitoso', ?)");
            $stmt_log->bind_param("issss", $usuario_id, $ip_address, $user_agent, $accion, $descripcion);
            $stmt_log->execute();
            $stmt_log->close();
        }
    } catch (Exception $e) {
        // Si hay error en el log, no interrumpir el proceso principal
        error_log("Error al registrar log: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIÓN: LIMPIAR Y SANITIZAR DATOS
// ==========================================
function limpiarDatos($data) {
    $datos_limpios = [];
    
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            // Limpiar espacios y caracteres especiales
            $datos_limpios[$key] = trim($value);
            
            // Convertir a mayúsculas campos específicos
            if (in_array($key, ['tipo_documento', 'numero_documento']) && $key === 'numero_documento' && isset($data['tipo_documento']) && $data['tipo_documento'] === 'pasaporte') {
                $datos_limpios[$key] = strtoupper($datos_limpios[$key]);
            }
            
            // Formatear teléfonos
            if (in_array($key, ['telefono', 'whatsapp']) && !empty($datos_limpios[$key])) {
                $datos_limpios[$key] = formatearTelefono($datos_limpios[$key]);
            }
            
        } else {
            $datos_limpios[$key] = $value;
        }
    }
    
    return $datos_limpios;
}

// ==========================================
// FUNCIÓN: FORMATEAR TELÉFONO
// ==========================================
function formatearTelefono($telefono) {
    // Limpiar el teléfono de caracteres no numéricos excepto +
    $telefono_limpio = preg_replace('/[^0-9+]/', '', $telefono);
    
    // Si empieza con 51 y tiene 11 dígitos, agregar +
    if (preg_match('/^51[0-9]{9}$/', $telefono_limpio)) {
        return '+' . $telefono_limpio;
    }
    
    // Si tiene 9 dígitos, agregar +51
    if (preg_match('/^[0-9]{9}$/', $telefono_limpio)) {
        return '+51' . $telefono_limpio;
    }
    
    // Si ya tiene +51, mantenerlo
    if (preg_match('/^\+51[0-9]{9}$/', $telefono_limpio)) {
        return $telefono_limpio;
    }
    
    // Si no cumple ningún formato, devolver el original
    return $telefono;
}

// ==========================================
// FUNCIÓN: OBTENER EGRESADO POR ID (para AJAX)
// ==========================================
function obtenerEgresadoPorId($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM exalumnos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            return null;
        }
        
        $egresado = $resultado->fetch_assoc();
        $stmt->close();
        
        return $egresado;
    } catch (Exception $e) {
        return null;
    }
}

// Cerrar conexión
$conn->close();
?>