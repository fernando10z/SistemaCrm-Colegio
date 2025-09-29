<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Función para registrar en logs
function registrarLog($conn, $accion, $detalle, $resultado = 'exitoso') {
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $detalles_json = json_encode([
        'detalle' => $detalle,
        'timestamp' => date('Y-m-d H:i:s'),
        'modulo' => 'codigos_referido'
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, ip_address, user_agent, accion, resultado, detalles, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssss", $usuario_id, $ip, $user_agent, $accion, $resultado, $detalles_json);
    $stmt->execute();
    $stmt->close();
}

// Función de sanitización
function sanitizar($data) {
    if (is_array($data)) {
        return array_map('sanitizar', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Función para validar fecha
function validarFecha($fecha) {
    if (empty($fecha)) return true; // Fecha vacía es válida (opcional)
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

// Función para validar código único
function validarCodigoUnico($conn, $codigo, $codigo_id = null) {
    if ($codigo_id) {
        $stmt = $conn->prepare("SELECT id FROM codigos_referido WHERE codigo = ? AND id != ?");
        $stmt->bind_param("si", $codigo, $codigo_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM codigos_referido WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    
    return !$existe;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no válido'
    ]);
    exit();
}

// Verificar acción
if (!isset($_POST['accion'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acción no especificada'
    ]);
    exit();
}

$accion = sanitizar($_POST['accion']);
$conn->begin_transaction();

try {
    switch ($accion) {
        
        // ============================================
        // ACCIÓN: CREAR CÓDIGO
        // ============================================
        case 'crear_codigo':
            // Validar campos requeridos
            $campos_requeridos = ['codigo', 'beneficio_referente', 'beneficio_referido', 'fecha_inicio'];
            foreach ($campos_requeridos as $campo) {
                if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
                    throw new Exception("El campo '{$campo}' es requerido");
                }
            }
            
            // Sanitizar y validar datos
            $codigo = strtoupper(sanitizar($_POST['codigo']));
            $tipo_codigo = sanitizar($_POST['tipo_codigo'] ?? 'general');
            $apoderado_id = !empty($_POST['apoderado_id']) ? intval($_POST['apoderado_id']) : null;
            $familia_id = !empty($_POST['familia_id']) ? intval($_POST['familia_id']) : null;
            $descripcion = sanitizar($_POST['descripcion'] ?? '');
            $beneficio_referente = sanitizar($_POST['beneficio_referente']);
            $beneficio_referido = sanitizar($_POST['beneficio_referido']);
            $limite_usos = !empty($_POST['limite_usos']) ? intval($_POST['limite_usos']) : null;
            $fecha_inicio = sanitizar($_POST['fecha_inicio']);
            $fecha_fin = !empty($_POST['fecha_fin']) ? sanitizar($_POST['fecha_fin']) : null;
            
            // Validaciones de formato
            if (!preg_match('/^[A-Z0-9\-]{3,20}$/', $codigo)) {
                throw new Exception("El código debe contener solo letras mayúsculas, números y guiones (3-20 caracteres)");
            }
            
            if (!validarCodigoUnico($conn, $codigo)) {
                throw new Exception("El código '{$codigo}' ya existe. Por favor elija otro.");
            }
            
            if (strlen($descripcion) > 0 && strlen($descripcion) < 10) {
                throw new Exception("La descripción debe tener al menos 10 caracteres");
            }
            
            if (strlen($beneficio_referente) < 10 || strlen($beneficio_referente) > 500) {
                throw new Exception("El beneficio del referente debe tener entre 10 y 500 caracteres");
            }
            
            if (strlen($beneficio_referido) < 10 || strlen($beneficio_referido) > 500) {
                throw new Exception("El beneficio del referido debe tener entre 10 y 500 caracteres");
            }
            
            if (!validarFecha($fecha_inicio)) {
                throw new Exception("Fecha de inicio inválida");
            }
            
            if ($fecha_fin && !validarFecha($fecha_fin)) {
                throw new Exception("Fecha de fin inválida");
            }
            
            // Validar que fecha de inicio no sea anterior a hoy
            $hoy = new DateTime();
            $fecha_inicio_obj = new DateTime($fecha_inicio);
            if ($fecha_inicio_obj < $hoy->setTime(0, 0, 0)) {
                throw new Exception("La fecha de inicio no puede ser anterior a hoy");
            }
            
            // Validar que fecha fin sea posterior a fecha inicio
            if ($fecha_fin) {
                $fecha_fin_obj = new DateTime($fecha_fin);
                if ($fecha_fin_obj <= $fecha_inicio_obj) {
                    throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio");
                }
            }
            
            // Validar límite de usos
            if ($limite_usos !== null && ($limite_usos < 1 || $limite_usos > 1000)) {
                throw new Exception("El límite de usos debe estar entre 1 y 1000");
            }
            
            // Si es personal, validar que tenga al menos apoderado o familia
            if ($tipo_codigo === 'personal' && !$apoderado_id && !$familia_id) {
                throw new Exception("Para un código personal debe seleccionar un apoderado o familia");
            }
            
            // Si es general, limpiar apoderado y familia
            if ($tipo_codigo === 'general') {
                $apoderado_id = null;
                $familia_id = null;
            }
            
            // Validar que apoderado existe
            if ($apoderado_id) {
                $stmt = $conn->prepare("SELECT id, familia_id FROM apoderados WHERE id = ? AND activo = 1");
                $stmt->bind_param("i", $apoderado_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception("El apoderado seleccionado no existe o no está activo");
                }
                
                $apoderado_data = $result->fetch_assoc();
                // Si el apoderado tiene familia, usarla
                if ($apoderado_data['familia_id']) {
                    $familia_id = $apoderado_data['familia_id'];
                }
                $stmt->close();
            }
            
            // Validar que familia existe
            if ($familia_id && !$apoderado_id) {
                $stmt = $conn->prepare("SELECT id FROM familias WHERE id = ? AND activo = 1");
                $stmt->bind_param("i", $familia_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception("La familia seleccionada no existe o no está activa");
                }
                $stmt->close();
            }
            
            // Insertar código
            $stmt = $conn->prepare("INSERT INTO codigos_referido 
                                   (codigo, apoderado_id, familia_id, descripcion, beneficio_referente, 
                                    beneficio_referido, limite_usos, usos_actuales, fecha_inicio, fecha_fin, 
                                    activo, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1, NOW(), NOW())");
            
            $stmt->bind_param("siisssiss", 
                $codigo, $apoderado_id, $familia_id, $descripcion, 
                $beneficio_referente, $beneficio_referido, $limite_usos, 
                $fecha_inicio, $fecha_fin
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear el código: " . $stmt->error);
            }
            
            $codigo_id = $conn->insert_id();
            $stmt->close();
            
            // Registrar en logs
            registrarLog($conn, 'Crear Código Referido', "Código: {$codigo}, ID: {$codigo_id}", 'exitoso');
            
            $conn->commit();
            
            // Preparar mensaje de éxito con SweetAlert
            $_SESSION['mensaje_sistema'] = json_encode([
                'tipo' => 'success',
                'titulo' => '¡Código Creado!',
                'mensaje' => "El código <strong>{$codigo}</strong> ha sido creado exitosamente",
                'icono' => 'success'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Código creado exitosamente',
                'codigo_id' => $codigo_id,
                'codigo' => $codigo
            ]);
            
            // Redireccionar
            header("Location: ../../codigos_referido.php");
            exit();
            
            break;
        
        // ============================================
        // ACCIÓN: EDITAR CÓDIGO
        // ============================================
        case 'editar_codigo':
            // Validar campos requeridos
            if (!isset($_POST['codigo_id']) || empty($_POST['codigo_id'])) {
                throw new Exception("ID de código no especificado");
            }
            
            $codigo_id = intval($_POST['codigo_id']);
            $usos_actuales = intval($_POST['usos_actuales'] ?? 0);
            
            // Validar que el código existe
            $stmt = $conn->prepare("SELECT codigo, activo FROM codigos_referido WHERE id = ?");
            $stmt->bind_param("i", $codigo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("El código no existe");
            }
            
            $codigo_data = $result->fetch_assoc();
            $codigo = $codigo_data['codigo'];
            $stmt->close();
            
            // Sanitizar y validar datos
            $descripcion = sanitizar($_POST['descripcion'] ?? '');
            $beneficio_referente = sanitizar($_POST['beneficio_referente']);
            $beneficio_referido = sanitizar($_POST['beneficio_referido']);
            $limite_usos = !empty($_POST['limite_usos']) ? intval($_POST['limite_usos']) : null;
            $fecha_inicio = sanitizar($_POST['fecha_inicio']);
            $fecha_fin = !empty($_POST['fecha_fin']) ? sanitizar($_POST['fecha_fin']) : null;
            
            // Validaciones
            if (strlen($descripcion) > 0 && strlen($descripcion) < 10) {
                throw new Exception("La descripción debe tener al menos 10 caracteres");
            }
            
            if (strlen($beneficio_referente) < 10 || strlen($beneficio_referente) > 500) {
                throw new Exception("El beneficio del referente debe tener entre 10 y 500 caracteres");
            }
            
            if (strlen($beneficio_referido) < 10 || strlen($beneficio_referido) > 500) {
                throw new Exception("El beneficio del referido debe tener entre 10 y 500 caracteres");
            }
            
            if (!validarFecha($fecha_inicio)) {
                throw new Exception("Fecha de inicio inválida");
            }
            
            if ($fecha_fin && !validarFecha($fecha_fin)) {
                throw new Exception("Fecha de fin inválida");
            }
            
            // Validar que fecha fin sea posterior a fecha inicio
            if ($fecha_fin) {
                $fecha_inicio_obj = new DateTime($fecha_inicio);
                $fecha_fin_obj = new DateTime($fecha_fin);
                if ($fecha_fin_obj <= $fecha_inicio_obj) {
                    throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio");
                }
            }
            
            // Validar límite de usos (debe ser mayor o igual a usos actuales)
            if ($limite_usos !== null) {
                if ($limite_usos < 1 || $limite_usos > 1000) {
                    throw new Exception("El límite de usos debe estar entre 1 y 1000");
                }
                
                if ($limite_usos < $usos_actuales) {
                    throw new Exception("El límite de usos ({$limite_usos}) no puede ser menor a los usos actuales ({$usos_actuales})");
                }
            }
            
            // Actualizar código
            $stmt = $conn->prepare("UPDATE codigos_referido 
                                   SET descripcion = ?, beneficio_referente = ?, beneficio_referido = ?, 
                                       limite_usos = ?, fecha_inicio = ?, fecha_fin = ?, updated_at = NOW() 
                                   WHERE id = ?");
            
            $stmt->bind_param("ssssssi", 
                $descripcion, $beneficio_referente, $beneficio_referido, 
                $limite_usos, $fecha_inicio, $fecha_fin, $codigo_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el código: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Registrar en logs
            registrarLog($conn, 'Editar Código Referido', "Código: {$codigo}, ID: {$codigo_id}", 'exitoso');
            
            $conn->commit();
            
            // Preparar mensaje de éxito
            $_SESSION['mensaje_sistema'] = json_encode([
                'tipo' => 'success',
                'titulo' => '¡Código Actualizado!',
                'mensaje' => "El código <strong>{$codigo}</strong> ha sido actualizado exitosamente",
                'icono' => 'success'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Código actualizado exitosamente',
                'codigo_id' => $codigo_id,
                'codigo' => $codigo
            ]);
            
            // Redireccionar
            header("Location: ../../codigos_referido.php");
            exit();
            
            break;
        
        // ============================================
        // ACCIÓN: GESTIONAR ESTADO
        // ============================================
        case 'gestionar_estado':
            // Validar campos requeridos
            if (!isset($_POST['codigo_id']) || empty($_POST['codigo_id'])) {
                throw new Exception("ID de código no especificado");
            }
            
            if (!isset($_POST['estado'])) {
                throw new Exception("Estado no especificado");
            }
            
            if (!isset($_POST['motivo']) || empty(trim($_POST['motivo']))) {
                throw new Exception("Debe especificar un motivo para el cambio de estado");
            }
            
            $codigo_id = intval($_POST['codigo_id']);
            $nuevo_estado = intval($_POST['estado']);
            $motivo = sanitizar($_POST['motivo']);
            
            // Validar estado
            if ($nuevo_estado !== 0 && $nuevo_estado !== 1) {
                throw new Exception("Estado inválido");
            }
            
            // Validar motivo
            if (strlen($motivo) < 10 || strlen($motivo) > 500) {
                throw new Exception("El motivo debe tener entre 10 y 500 caracteres");
            }
            
            // Obtener estado actual
            $stmt = $conn->prepare("SELECT codigo, activo FROM codigos_referido WHERE id = ?");
            $stmt->bind_param("i", $codigo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("El código no existe");
            }
            
            $codigo_data = $result->fetch_assoc();
            $codigo = $codigo_data['codigo'];
            $estado_actual = $codigo_data['activo'];
            $stmt->close();
            
            // Verificar que el estado sea diferente
            if ($estado_actual == $nuevo_estado) {
                $estado_texto = $nuevo_estado ? 'activo' : 'inactivo';
                throw new Exception("El código ya se encuentra {$estado_texto}");
            }
            
            // Actualizar estado
            $stmt = $conn->prepare("UPDATE codigos_referido SET activo = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $nuevo_estado, $codigo_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el estado: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Registrar cambio en historial (tabla logs_acceso)
            $accion_texto = $nuevo_estado ? 'Activar' : 'Desactivar';
            registrarLog($conn, "{$accion_texto} Código Referido", 
                        "Código: {$codigo}, ID: {$codigo_id}, Motivo: {$motivo}", 'exitoso');
            
            // Si existe una tabla de historial específica, registrar ahí también
            // (Opcional - crear tabla historial_estados_codigo si se necesita más detalle)
            
            $conn->commit();
            
            // Preparar mensaje de éxito
            $estado_nuevo_texto = $nuevo_estado ? 'activado' : 'desactivado';
            $_SESSION['mensaje_sistema'] = json_encode([
                'tipo' => 'success',
                'titulo' => '¡Estado Actualizado!',
                'mensaje' => "El código <strong>{$codigo}</strong> ha sido {$estado_nuevo_texto} exitosamente",
                'icono' => 'success'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'codigo_id' => $codigo_id,
                'codigo' => $codigo,
                'nuevo_estado' => $nuevo_estado
            ]);
            
            // Redireccionar
            header("Location: ../../codigos_referido.php");
            exit();
            
            break;
        
        default:
            throw new Exception("Acción no válida: {$accion}");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Registrar error en logs
    registrarLog($conn, "Error en Gestión Código", $e->getMessage(), 'fallido');
    
    // Preparar mensaje de error
    $_SESSION['mensaje_sistema'] = json_encode([
        'tipo' => 'error',
        'titulo' => 'Error',
        'mensaje' => $e->getMessage(),
        'icono' => 'error'
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Redireccionar
    header("Location: ../../codigos_referido.php");
    exit();
}

$conn->close();
?>