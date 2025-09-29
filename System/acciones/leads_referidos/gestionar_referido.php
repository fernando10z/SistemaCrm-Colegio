<?php
session_start();
require_once '../../bd/conexion.php';

// Función para registrar en logs (si existe la tabla)
function registrarLog($conn, $accion, $detalle, $exitoso = true) {
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1;
    
    $sql_log = "INSERT INTO logs_acceso (usuario_id, accion, resultado, detalles, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_log);
    if ($stmt) {
        $resultado = $exitoso ? 'exitoso' : 'fallido';
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $detalles_json = json_encode($detalle, JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param("isssss", $usuario_id, $accion, $resultado, $detalles_json, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

// Función para sanitizar entrada
function sanitizarEntrada($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Función para validar fecha
function validarFecha($fecha) {
    if (empty($fecha)) return false;
    
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        return false;
    }
    
    // No puede ser fecha futura
    $hoy = new DateTime();
    $fechaIngresada = new DateTime($fecha);
    
    return $fechaIngresada <= $hoy;
}

// Función para validar que el lead no esté ya vinculado
function leadYaVinculado($conn, $lead_id, $excluir_id = null) {
    $sql = "SELECT id FROM usos_referido WHERE lead_id = ?";
    
    if ($excluir_id) {
        $sql .= " AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $lead_id, $excluir_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $lead_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    
    return $existe;
}

// Función para validar código de referido disponible
function codigoDisponible($conn, $codigo_id) {
    $sql = "SELECT 
                cr.activo,
                cr.limite_usos,
                cr.usos_actuales,
                cr.fecha_inicio,
                cr.fecha_fin
            FROM codigos_referido cr
            WHERE cr.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $codigo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['valido' => false, 'mensaje' => 'Código no encontrado'];
    }
    
    $codigo = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar si está activo
    if (!$codigo['activo']) {
        return ['valido' => false, 'mensaje' => 'El código está inactivo'];
    }
    
    // Verificar vigencia
    $hoy = date('Y-m-d');
    if ($codigo['fecha_fin'] && $codigo['fecha_fin'] < $hoy) {
        return ['valido' => false, 'mensaje' => 'El código ha vencido'];
    }
    
    if ($codigo['fecha_inicio'] > $hoy) {
        return ['valido' => false, 'mensaje' => 'El código aún no está vigente'];
    }
    
    // Verificar límite de usos
    if ($codigo['limite_usos'] !== null && $codigo['usos_actuales'] >= $codigo['limite_usos']) {
        return ['valido' => false, 'mensaje' => 'El código ha alcanzado su límite de usos'];
    }
    
    return ['valido' => true, 'mensaje' => 'Código válido'];
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../leads_referidos.php');
    exit();
}

// Verificar acción
if (!isset($_POST['accion'])) {
    $_SESSION['mensaje'] = 'Acción no especificada';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: ../../leads_referidos.php');
    exit();
}

$accion = sanitizarEntrada($_POST['accion']);

// ============================================================================
// ACCIÓN 1: REGISTRAR USO DE CÓDIGO DE REFERIDO
// ============================================================================
if ($accion === 'registrar_uso_referido') {
    
    // Validar campos requeridos
    if (empty($_POST['codigo_referido_id']) || empty($_POST['lead_id']) || empty($_POST['fecha_uso'])) {
        $_SESSION['mensaje'] = 'Todos los campos obligatorios son requeridos';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => 'Campos faltantes'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    $codigo_referido_id = intval($_POST['codigo_referido_id']);
    $lead_id = intval($_POST['lead_id']);
    $fecha_uso = sanitizarEntrada($_POST['fecha_uso']);
    $observaciones = isset($_POST['observaciones']) ? sanitizarEntrada($_POST['observaciones']) : null;
    
    // Validar que sean IDs válidos
    if ($codigo_referido_id <= 0 || $lead_id <= 0) {
        $_SESSION['mensaje'] = 'IDs inválidos';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => 'IDs inválidos'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar fecha
    if (!validarFecha($fecha_uso)) {
        $_SESSION['mensaje'] = 'Fecha de uso inválida o futura';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => 'Fecha inválida'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar que el lead no esté ya vinculado
    if (leadYaVinculado($conn, $lead_id)) {
        $_SESSION['mensaje'] = 'El lead seleccionado ya está vinculado a un código de referido';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => 'Lead ya vinculado', 'lead_id' => $lead_id], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar que el código esté disponible
    $validacionCodigo = codigoDisponible($conn, $codigo_referido_id);
    if (!$validacionCodigo['valido']) {
        $_SESSION['mensaje'] = 'Código no disponible: ' . $validacionCodigo['mensaje'];
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => $validacionCodigo['mensaje'], 'codigo_id' => $codigo_referido_id], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar longitud de observaciones
    if ($observaciones && strlen($observaciones) > 500) {
        $observaciones = substr($observaciones, 0, 500);
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Insertar uso de referido
        $sql_insert = "INSERT INTO usos_referido (codigo_referido_id, lead_id, fecha_uso, convertido, observaciones) 
                       VALUES (?, ?, ?, 0, ?)";
        
        $stmt = $conn->prepare($sql_insert);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }
        
        $stmt->bind_param("iiss", $codigo_referido_id, $lead_id, $fecha_uso, $observaciones);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar inserción: " . $stmt->error);
        }
        
        $uso_id = $conn->insert_id;
        $stmt->close();
        
        // Actualizar contador de usos del código
        $sql_update = "UPDATE codigos_referido 
                       SET usos_actuales = usos_actuales + 1 
                       WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        if (!$stmt_update) {
            throw new Exception("Error al preparar actualización: " . $conn->error);
        }
        
        $stmt_update->bind_param("i", $codigo_referido_id);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar contador: " . $stmt_update->error);
        }
        
        $stmt_update->close();
        
        // Actualizar el canal de captación del lead a "referido" si existe
        $sql_canal = "SELECT id FROM canales_captacion WHERE tipo = 'referido' LIMIT 1";
        $result_canal = $conn->query($sql_canal);
        
        if ($result_canal && $result_canal->num_rows > 0) {
            $canal = $result_canal->fetch_assoc();
            $canal_id = $canal['id'];
            
            $sql_update_lead = "UPDATE leads SET canal_captacion_id = ? WHERE id = ?";
            $stmt_lead = $conn->prepare($sql_update_lead);
            $stmt_lead->bind_param("ii", $canal_id, $lead_id);
            $stmt_lead->execute();
            $stmt_lead->close();
        }
        
        // Commit de la transacción
        $conn->commit();
        
        // Obtener información para el log
        $sql_info = "SELECT 
                        l.codigo_lead,
                        CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as nombre_lead,
                        cr.codigo
                     FROM usos_referido ur
                     INNER JOIN leads l ON ur.lead_id = l.id
                     INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
                     WHERE ur.id = ?";
        
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $uso_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info = $result_info->fetch_assoc();
        $stmt_info->close();
        
        registrarLog($conn, 'Registrar Uso Referido', [
            'uso_id' => $uso_id,
            'codigo' => $info['codigo'],
            'lead' => $info['nombre_lead'],
            'fecha_uso' => $fecha_uso
        ], true);
        
        $_SESSION['mensaje'] = 'Uso de código de referido registrado exitosamente para el lead ' . $info['nombre_lead'];
        $_SESSION['tipo_mensaje'] = 'success';
        
        // JavaScript para mostrar SweetAlert
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = '¡Registro Exitoso!';
        $_SESSION['alerta_texto'] = 'El uso del código "' . $info['codigo'] . '" ha sido vinculado al lead ' . $info['nombre_lead'];
        $_SESSION['alerta_icono'] = 'success';
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $conn->rollback();
        
        $_SESSION['mensaje'] = 'Error al registrar uso de código: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Registrar Uso Referido', ['error' => $e->getMessage()], false);
        
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = 'Error';
        $_SESSION['alerta_texto'] = 'No se pudo registrar el uso del código. Por favor intente nuevamente.';
        $_SESSION['alerta_icono'] = 'error';
    }
    
    header('Location: ../../leads_referidos.php');
    exit();
}

// ============================================================================
// ACCIÓN 2: EDITAR OBSERVACIONES
// ============================================================================
else if ($accion === 'editar_observaciones') {
    
    // Validar campos requeridos
    if (empty($_POST['referido_id'])) {
        $_SESSION['mensaje'] = 'ID de referido no especificado';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Editar Observaciones Referido', ['error' => 'ID faltante'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    $referido_id = intval($_POST['referido_id']);
    $observaciones = isset($_POST['observaciones']) ? sanitizarEntrada($_POST['observaciones']) : '';
    
    // Validar que sea un ID válido
    if ($referido_id <= 0) {
        $_SESSION['mensaje'] = 'ID inválido';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Editar Observaciones Referido', ['error' => 'ID inválido'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar longitud de observaciones
    if (strlen($observaciones) > 500) {
        $observaciones = substr($observaciones, 0, 500);
    }
    
    // Verificar que el registro existe
    $sql_check = "SELECT id FROM usos_referido WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $referido_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        $_SESSION['mensaje'] = 'Registro de referido no encontrado';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Editar Observaciones Referido', ['error' => 'Registro no existe', 'id' => $referido_id], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    $stmt_check->close();
    
    try {
        // Actualizar observaciones
        $sql_update = "UPDATE usos_referido SET observaciones = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql_update);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }
        
        $stmt->bind_param("si", $observaciones, $referido_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Obtener información para el log
        $sql_info = "SELECT 
                        CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as nombre_lead
                     FROM usos_referido ur
                     INNER JOIN leads l ON ur.lead_id = l.id
                     WHERE ur.id = ?";
        
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $referido_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info = $result_info->fetch_assoc();
        $stmt_info->close();
        
        registrarLog($conn, 'Editar Observaciones Referido', [
            'referido_id' => $referido_id,
            'lead' => $info['nombre_lead']
        ], true);
        
        $_SESSION['mensaje'] = 'Observaciones actualizadas exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
        
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = '¡Actualizado!';
        $_SESSION['alerta_texto'] = 'Las observaciones del lead ' . $info['nombre_lead'] . ' han sido actualizadas';
        $_SESSION['alerta_icono'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error al actualizar observaciones: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Editar Observaciones Referido', ['error' => $e->getMessage()], false);
        
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = 'Error';
        $_SESSION['alerta_texto'] = 'No se pudieron actualizar las observaciones';
        $_SESSION['alerta_icono'] = 'error';
    }
    
    header('Location: ../../leads_referidos.php');
    exit();
}

// ============================================================================
// ACCIÓN 3: CONVERTIR REFERIDO (MARCAR COMO MATRICULADO)
// ============================================================================
else if ($accion === 'convertir_referido') {
    
    // Validar campos requeridos
    if (empty($_POST['referido_id']) || empty($_POST['fecha_conversion'])) {
        $_SESSION['mensaje'] = 'Campos obligatorios faltantes';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'Campos faltantes'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    $referido_id = intval($_POST['referido_id']);
    $fecha_conversion = sanitizarEntrada($_POST['fecha_conversion']);
    $observaciones_conversion = isset($_POST['observaciones_conversion']) ? sanitizarEntrada($_POST['observaciones_conversion']) : null;
    
    // Validar ID
    if ($referido_id <= 0) {
        $_SESSION['mensaje'] = 'ID inválido';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'ID inválido'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar fecha de conversión
    if (!validarFecha($fecha_conversion)) {
        $_SESSION['mensaje'] = 'Fecha de conversión inválida o futura';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'Fecha inválida'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar longitud de observaciones
    if ($observaciones_conversion && strlen($observaciones_conversion) > 500) {
        $observaciones_conversion = substr($observaciones_conversion, 0, 500);
    }
    
    // Verificar que el registro existe y no está ya convertido
    $sql_check = "SELECT 
                      ur.convertido,
                      ur.fecha_uso,
                      ur.observaciones as obs_anteriores
                  FROM usos_referido ur
                  WHERE ur.id = ?";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $referido_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        $_SESSION['mensaje'] = 'Registro de referido no encontrado';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'Registro no existe', 'id' => $referido_id], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    $registro = $result_check->fetch_assoc();
    $stmt_check->close();
    
    // Verificar si ya está convertido
    if ($registro['convertido']) {
        $_SESSION['mensaje'] = 'Este lead ya está marcado como convertido';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'Ya convertido', 'id' => $referido_id], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Validar coherencia de fechas
    $fecha_uso = new DateTime($registro['fecha_uso']);
    $fecha_conv = new DateTime($fecha_conversion);
    
    if ($fecha_conv < $fecha_uso) {
        $_SESSION['mensaje'] = 'La fecha de conversión no puede ser anterior a la fecha de uso del código';
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => 'Fecha conversión anterior a uso'], false);
        header('Location: ../../leads_referidos.php');
        exit();
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Combinar observaciones si hay anteriores
        $observaciones_finales = $registro['obs_anteriores'];
        if ($observaciones_conversion) {
            $separador = $observaciones_finales ? "\n---\n[CONVERSIÓN] " : "[CONVERSIÓN] ";
            $observaciones_finales .= $separador . $observaciones_conversion;
        }
        
        // Limitar a 500 caracteres totales
        if (strlen($observaciones_finales) > 500) {
            $observaciones_finales = substr($observaciones_finales, -500);
        }
        
        // Actualizar registro de uso
        $sql_update = "UPDATE usos_referido 
                       SET convertido = 1, 
                           fecha_conversion = ?, 
                           observaciones = ?
                       WHERE id = ?";
        
        $stmt = $conn->prepare($sql_update);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ssi", $fecha_conversion, $observaciones_finales, $referido_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar conversión: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Actualizar el estado del lead a "Matriculado" si existe ese estado
        $sql_estado = "SELECT id FROM estados_lead WHERE nombre = 'Matriculado' LIMIT 1";
        $result_estado = $conn->query($sql_estado);
        
        if ($result_estado && $result_estado->num_rows > 0) {
            $estado = $result_estado->fetch_assoc();
            $estado_id = $estado['id'];
            
            // Obtener el lead_id
            $sql_lead_id = "SELECT lead_id FROM usos_referido WHERE id = ?";
            $stmt_lead_id = $conn->prepare($sql_lead_id);
            $stmt_lead_id->bind_param("i", $referido_id);
            $stmt_lead_id->execute();
            $result_lead_id = $stmt_lead_id->get_result();
            $lead_data = $result_lead_id->fetch_assoc();
            $lead_id = $lead_data['lead_id'];
            $stmt_lead_id->close();
            
            // Actualizar estado del lead
            $sql_update_lead = "UPDATE leads 
                               SET estado_lead_id = ?, 
                                   fecha_conversion = ? 
                               WHERE id = ?";
            
            $stmt_lead = $conn->prepare($sql_update_lead);
            $stmt_lead->bind_param("isi", $estado_id, $fecha_conversion, $lead_id);
            $stmt_lead->execute();
            $stmt_lead->close();
        }
        
        // Commit de la transacción
        $conn->commit();
        
        // Obtener información para el log y mensaje
        $sql_info = "SELECT 
                        l.codigo_lead,
                        CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as nombre_lead,
                        cr.codigo,
                        DATEDIFF(?, ur.fecha_uso) as dias_conversion
                     FROM usos_referido ur
                     INNER JOIN leads l ON ur.lead_id = l.id
                     INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
                     WHERE ur.id = ?";
        
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("si", $fecha_conversion, $referido_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info = $result_info->fetch_assoc();
        $stmt_info->close();
        
        registrarLog($conn, 'Convertir Referido', [
            'referido_id' => $referido_id,
            'lead' => $info['nombre_lead'],
            'codigo' => $info['codigo'],
            'fecha_conversion' => $fecha_conversion,
            'dias_conversion' => $info['dias_conversion']
        ], true);
        
        $_SESSION['mensaje'] = 'Lead convertido exitosamente: ' . $info['nombre_lead'];
        $_SESSION['tipo_mensaje'] = 'success';
        
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = '¡Conversión Exitosa!';
        $_SESSION['alerta_texto'] = 'El lead ' . $info['nombre_lead'] . ' ha sido marcado como convertido. Tiempo de conversión: ' . $info['dias_conversion'] . ' días';
        $_SESSION['alerta_icono'] = 'success';
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $conn->rollback();
        
        $_SESSION['mensaje'] = 'Error al convertir referido: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
        registrarLog($conn, 'Convertir Referido', ['error' => $e->getMessage()], false);
        
        $_SESSION['mostrar_alerta'] = true;
        $_SESSION['alerta_titulo'] = 'Error';
        $_SESSION['alerta_texto'] = 'No se pudo completar la conversión. Por favor intente nuevamente.';
        $_SESSION['alerta_icono'] = 'error';
    }
    
    header('Location: ../../leads_referidos.php');
    exit();
}

// ============================================================================
// ACCIÓN NO VÁLIDA
// ============================================================================
else {
    $_SESSION['mensaje'] = 'Acción no válida';
    $_SESSION['tipo_mensaje'] = 'error';
    registrarLog($conn, 'Acción Inválida', ['accion' => $accion], false);
    header('Location: ../../leads_referidos.php');
    exit();
}

$conn->close();
?>