<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Iniciar sesión para obtener datos del usuario
session_start();

// Debug: Verificar estructura de base de datos
error_log("=== DEBUG CRM: Verificando estructura de logs_acceso ===");

// Verificar si se envió una acción
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'editar';
    
    switch($accion) {
        case 'editar':
        case '': // Si no se especifica acción en POST, es editar
            editarConfiguracion($conn);
            break;
            
        default:
            registrarLog($conn, 0, 'Editar Configuración', 'fallido', 'Acción inválida: ' . $accion);
            registrarLogIntegracion($conn, 'Procesar Acción', 'error', ['accion' => $accion], 
                                  ['error' => 'accion_invalida'], false, 'Acción no reconocida por el sistema');
            header("Location: ../../informacion.php?error=accion_invalida");
            exit();
    }
} else {
    header("Location: ../../informacion.php");
    exit();
}

/**
 * Editar configuración existente
 */
function editarConfiguracion($conn) {
    // Verificar sesión y obtener usuario_id
    $usuario_id = null;
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
        $usuario_id = $_SESSION['usuario_id'];
    } else {
        // Si no hay sesión, crear o usar usuario por defecto
        $usuario_id = obtenerUsuarioDefecto($conn);
    }
    
    try {
        // Validar datos requeridos
        if (empty($_POST['id']) || empty($_POST['clave']) || empty($_POST['valor']) || empty($_POST['tipo']) || empty($_POST['categoria'])) {
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', 'Campos requeridos vacíos');
            registrarLogIntegracion($conn, 'Validar Datos Configuración', 'error', $_POST, 
                                  ['error' => 'campos_requeridos'], false, 'Campos requeridos vacíos');
            header("Location: ../../informacion.php?error=campos_requeridos");
            exit();
        }
        
        $id = (int)$_POST['id'];
        $clave = trim($_POST['clave']);
        $valor = trim($_POST['valor']);
        $tipo = $_POST['tipo'];
        $descripcion = trim($_POST['descripcion']);
        $categoria = $_POST['categoria'];
        
        // Obtener datos anteriores para el log
        $sql_anterior = "SELECT clave, valor, tipo, descripcion, categoria FROM configuracion_sistema WHERE id = ?";
        $stmt_anterior = $conn->prepare($sql_anterior);
        $stmt_anterior->bind_param("i", $id);
        $stmt_anterior->execute();
        $result_anterior = $stmt_anterior->get_result();
        
        if ($result_anterior->num_rows == 0) {
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', 'Configuración no existe - ID: ' . $id);
            registrarLogIntegracion($conn, 'Buscar Configuración', 'error', ['id' => $id], 
                                  ['error' => 'no_existe'], false, 'Configuración no encontrada');
            header("Location: ../../informacion.php?error=no_existe");
            exit();
        }
        
        $datos_anteriores = $result_anterior->fetch_assoc();
        
        // Validar que la clave no exista en otro registro
        $sql_check_clave = "SELECT id FROM configuracion_sistema WHERE clave = ? AND id != ?";
        $stmt_check_clave = $conn->prepare($sql_check_clave);
        $stmt_check_clave->bind_param("si", $clave, $id);
        $stmt_check_clave->execute();
        $result_check_clave = $stmt_check_clave->get_result();
        
        if ($result_check_clave->num_rows > 0) {
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', 'Clave duplicada: ' . $clave);
            registrarLogIntegracion($conn, 'Validar Clave Única', 'error', ['clave' => $clave, 'id' => $id], 
                                  ['error' => 'clave_duplicada'], false, 'La clave ya existe en otro registro');
            header("Location: ../../informacion.php?error=clave_existe");
            exit();
        }
        
        // Validar el valor según el tipo
        if (!validarValorPorTipo($valor, $tipo)) {
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', 'Valor inválido para tipo ' . $tipo . ': ' . $valor);
            registrarLogIntegracion($conn, 'Validar Valor por Tipo', 'error', ['valor' => $valor, 'tipo' => $tipo], 
                                  ['error' => 'valor_invalido'], false, 'Valor no válido para el tipo especificado');
            header("Location: ../../informacion.php?error=valor_invalido&tipo=" . $tipo);
            exit();
        }
        
        // Actualizar configuración
        $sql = "UPDATE configuracion_sistema SET clave = ?, valor = ?, tipo = ?, descripcion = ?, categoria = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $clave, $valor, $tipo, $descripcion, $categoria, $id);
        
        if ($stmt->execute()) {
            // Preparar detalles del cambio para el log
            $cambios = [];
            if ($datos_anteriores['clave'] !== $clave) $cambios[] = "Clave: '{$datos_anteriores['clave']}' → '{$clave}'";
            if ($datos_anteriores['valor'] !== $valor) $cambios[] = "Valor: '{$datos_anteriores['valor']}' → '{$valor}'";
            if ($datos_anteriores['tipo'] !== $tipo) $cambios[] = "Tipo: '{$datos_anteriores['tipo']}' → '{$tipo}'";
            if ($datos_anteriores['descripcion'] !== $descripcion) $cambios[] = "Descripción: '{$datos_anteriores['descripcion']}' → '{$descripcion}'";
            if ($datos_anteriores['categoria'] !== $categoria) $cambios[] = "Categoría: '{$datos_anteriores['categoria']}' → '{$categoria}'";
            
            $detalle_cambios = "ID: {$id}. Cambios: " . implode(', ', $cambios);
            
            // Registrar en logs_acceso
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'exitoso', $detalle_cambios);
            
            // También registrar en logs_integracion
            registrarLogIntegracion($conn, 'Editar Configuración Sistema', 'sincronizacion', 
                                  ['datos_anteriores' => $datos_anteriores, 'datos_nuevos' => $_POST], 
                                  ['resultado' => 'actualizado', 'cambios' => $cambios], true);
            
            header("Location: ../../informacion.php?success=actualizado");
        } else {
            registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', 'Error SQL al actualizar ID: ' . $id);
            
            // También registrar error en logs_integracion
            registrarLogIntegracion($conn, 'Editar Configuración Sistema', 'error', 
                                  ['id' => $id, 'datos' => $_POST], 
                                  ['error' => 'Error SQL al ejecutar UPDATE'], false, 'Error en la base de datos');
            
            header("Location: ../../informacion.php?error=error_actualizar");
        }
        
        $stmt->close();
        $stmt_anterior->close();
        $stmt_check_clave->close();
        
    } catch (Exception $e) {
        $error_detalle = "Error al editar configuración ID: " . (isset($id) ? $id : 'N/A') . ". Error: " . $e->getMessage();
        error_log($error_detalle);
        registrarLog($conn, $usuario_id, 'Editar Configuración', 'fallido', $error_detalle);
        registrarLogIntegracion($conn, 'Editar Configuración Sistema', 'error', 
                              isset($_POST) ? $_POST : [], 
                              ['error' => $e->getMessage()], false, 'Excepción del sistema');
        header("Location: ../../informacion.php?error=error_sistema");
    }
}

/**
 * Validar valor según su tipo
 */
function validarValorPorTipo($valor, $tipo) {
    switch($tipo) {
        case 'texto':
            return true; // Cualquier texto es válido
            
        case 'numero':
            return is_numeric($valor);
            
        case 'booleano':
            $valor_lower = strtolower($valor);
            return in_array($valor_lower, ['true', 'false', '1', '0']);
            
        case 'json':
            json_decode($valor);
            return json_last_error() === JSON_ERROR_NONE;
            
        default:
            return false;
    }
}

/**
 * Obtener o crear usuario por defecto para logs
 */
function obtenerUsuarioDefecto($conn) {
    try {
        // Buscar usuario administrador por defecto
        $sql = "SELECT id FROM usuarios WHERE usuario = 'admin' OR rol_id = 1 LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // Si no existe ningún usuario, crear uno básico
        $sql_insert = "INSERT INTO usuarios (usuario, email, password_hash, nombre, apellidos, rol_id) 
                       VALUES ('sistema', 'sistema@crm.local', '', 'Sistema', 'CRM', 1)";
        if ($conn->query($sql_insert)) {
            return $conn->insert_id;
        }
        
        return 1; // Fallback
        
    } catch (Exception $e) {
        error_log("Error obteniendo usuario defecto: " . $e->getMessage());
        return 1;
    }
}

/**
 * Verificar estructura de tabla logs_acceso
 */
function verificarTablaLogsAcceso($conn) {
    try {
        $sql = "SHOW TABLES LIKE 'logs_acceso'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 0) {
            error_log("ADVERTENCIA: La tabla logs_acceso no existe");
            return false;
        }
        
        // Verificar columnas
        $sql_columns = "SHOW COLUMNS FROM logs_acceso";
        $result_columns = $conn->query($sql_columns);
        $columns = [];
        
        while ($row = $result_columns->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $required_columns = ['id', 'usuario_id', 'ip_address', 'user_agent', 'accion', 'resultado', 'detalles', 'created_at'];
        $missing_columns = array_diff($required_columns, $columns);
        
        if (!empty($missing_columns)) {
            error_log("ADVERTENCIA: Faltan columnas en logs_acceso: " . implode(', ', $missing_columns));
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error verificando tabla logs_acceso: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar log en la tabla logs_acceso
 */
function registrarLog($conn, $usuario_id, $accion, $resultado, $detalles) {
    try {
        // Verificar que la tabla existe
        if (!verificarTablaLogsAcceso($conn)) {
            error_log("No se puede registrar log: tabla logs_acceso no disponible");
            return false;
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        
        // Asegurar que usuario_id sea válido
        if ($usuario_id <= 0) {
            $usuario_id = obtenerUsuarioDefecto($conn);
        }
        
        // Truncar detalles si es muy largo
        if (strlen($detalles) > 500) {
            $detalles = substr($detalles, 0, 497) . '...';
        }
        
        // Crear JSON con información adicional (simplificado)
        $detalles_json = json_encode([
            'detalle' => $detalles,
            'timestamp' => date('Y-m-d H:i:s'),
            'modulo' => 'configuracion_sistema'
        ]);
        
        // INSERT simplificado sin created_at (se usa DEFAULT CURRENT_TIMESTAMP)
        $sql_log = "INSERT INTO logs_acceso (usuario_id, ip_address, user_agent, accion, resultado, detalles) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        
        if (!$stmt_log) {
            error_log("Error preparando statement para logs_acceso: " . $conn->error);
            return false;
        }
        
        $stmt_log->bind_param("isssss", $usuario_id, $ip_address, $user_agent, $accion, $resultado, $detalles_json);
        
        if (!$stmt_log->execute()) {
            error_log("Error ejecutando insert en logs_acceso: " . $stmt_log->error);
            error_log("SQL: $sql_log");
            error_log("Parámetros: usuario_id=$usuario_id, ip=$ip_address, accion=$accion, resultado=$resultado");
            return false;
        }
        
        $stmt_log->close();
        return true;
        
    } catch (Exception $e) {
        // Log detallado del error
        error_log("Excepción al registrar log en logs_acceso: " . $e->getMessage());
        error_log("Datos intentados: usuario_id=$usuario_id, accion=$accion, resultado=$resultado");
        return false;
    }
}

/**
 * Registrar log en la tabla logs_integracion
 */
function registrarLogIntegracion($conn, $accion, $tipo_operacion, $datos_enviados, $respuesta_recibida, $exitoso, $mensaje_error = null) {
    try {
        // Buscar o crear una configuración de integración para el sistema interno
        $sql_config = "SELECT id FROM integraciones_config WHERE nombre = 'Sistema Interno' AND tipo = 'api_externa'";
        $result_config = $conn->query($sql_config);
        
        if ($result_config->num_rows == 0) {
            // Crear configuración de integración para sistema interno
            $sql_insert_config = "INSERT INTO integraciones_config (nombre, tipo, configuracion, activo) 
                                  VALUES ('Sistema Interno', 'api_externa', JSON_OBJECT('descripcion', 'Logs del sistema CRM interno'), 1)";
            $conn->query($sql_insert_config);
            $integracion_id = $conn->insert_id;
        } else {
            $config = $result_config->fetch_assoc();
            $integracion_id = $config['id'];
        }
        
        // Convertir arrays a JSON
        $datos_enviados_json = is_array($datos_enviados) ? json_encode($datos_enviados) : $datos_enviados;
        $respuesta_recibida_json = is_array($respuesta_recibida) ? json_encode($respuesta_recibida) : $respuesta_recibida;
        
        // Calcular tiempo de respuesta simulado (en milisegundos)
        $tiempo_respuesta = rand(10, 100);
        
        $sql_log = "INSERT INTO logs_integracion (integracion_id, accion, tipo_operacion, datos_enviados, respuesta_recibida, exitoso, mensaje_error, tiempo_respuesta_ms, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("isssssii", $integracion_id, $accion, $tipo_operacion, $datos_enviados_json, $respuesta_recibida_json, $exitoso, $mensaje_error, $tiempo_respuesta);
        $stmt_log->execute();
        $stmt_log->close();
        
    } catch (Exception $e) {
        // Si falla el log, no interrumpir el proceso principal
        error_log("Error al registrar log de integración: " . $e->getMessage());
    }
}

// Cerrar conexión
$conn->close();
?>