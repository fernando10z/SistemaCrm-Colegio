<?php
session_start();
require_once '../../bd/conexion.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../referidos_exalumnos.php');
    exit();
}

// Verificar que exista una acción
if (!isset($_POST['accion'])) {
    $_SESSION['mensaje_sistema'] = 'No se especificó ninguna acción';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: ../../referidos_exalumnos.php');
    exit();
}

$accion = $_POST['accion'];

// ==========================================
// ACCIÓN: GENERAR CÓDIGO DE REFERIDO
// ==========================================
if ($accion === 'generar_codigo') {
    
    // Validar campos obligatorios
    $errores = [];
    
    // Validar código
    if (empty($_POST['codigo'])) {
        $errores[] = 'El código de referido es obligatorio';
    } else {
        $codigo = strtoupper(trim($_POST['codigo']));
        
        // Validar formato: solo letras mayúsculas y números, 4-20 caracteres
        if (!preg_match('/^[A-Z0-9]{4,20}$/', $codigo)) {
            $errores[] = 'El código debe tener entre 4 y 20 caracteres (solo letras mayúsculas y números)';
        }
        
        // Verificar que el código no exista
        $check_codigo = $conn->prepare("SELECT id FROM codigos_referido WHERE codigo = ?");
        $check_codigo->bind_param("s", $codigo);
        $check_codigo->execute();
        $check_codigo->store_result();
        
        if ($check_codigo->num_rows > 0) {
            $errores[] = 'El código "' . $codigo . '" ya existe. Por favor, elija otro código';
        }
        $check_codigo->close();
    }
    
    // Validar tipo de referente y obtener IDs
    $tipo_referente = $_POST['tipo_referente'] ?? 'general';
    $apoderado_id = null;
    $familia_id = null;
    
    if ($tipo_referente === 'apoderado') {
        if (empty($_POST['apoderado_id'])) {
            $errores[] = 'Debe seleccionar un apoderado';
        } else {
            $apoderado_id = intval($_POST['apoderado_id']);
            
            // Verificar que el apoderado existe
            $check_apoderado = $conn->prepare("SELECT id FROM apoderados WHERE id = ? AND activo = 1");
            $check_apoderado->bind_param("i", $apoderado_id);
            $check_apoderado->execute();
            $check_apoderado->store_result();
            
            if ($check_apoderado->num_rows === 0) {
                $errores[] = 'El apoderado seleccionado no existe o está inactivo';
            }
            $check_apoderado->close();
        }
    } elseif ($tipo_referente === 'familia') {
        if (empty($_POST['familia_id'])) {
            $errores[] = 'Debe seleccionar una familia';
        } else {
            $familia_id = intval($_POST['familia_id']);
            
            // Verificar que la familia existe
            $check_familia = $conn->prepare("SELECT id FROM familias WHERE id = ? AND activo = 1");
            $check_familia->bind_param("i", $familia_id);
            $check_familia->execute();
            $check_familia->store_result();
            
            if ($check_familia->num_rows === 0) {
                $errores[] = 'La familia seleccionada no existe o está inactiva';
            }
            $check_familia->close();
        }
    }
    
    // Validar descripción (opcional pero con límite)
    $descripcion = null;
    if (!empty($_POST['descripcion'])) {
        $descripcion = trim($_POST['descripcion']);
        if (strlen($descripcion) > 200) {
            $errores[] = 'La descripción no puede exceder los 200 caracteres';
        }
        if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,:]{0,200}$/', $descripcion)) {
            $errores[] = 'La descripción contiene caracteres no permitidos';
        }
    }
    
    // Validar fecha de inicio
    if (empty($_POST['fecha_inicio'])) {
        $errores[] = 'La fecha de inicio es obligatoria';
    } else {
        $fecha_inicio = $_POST['fecha_inicio'];
        
        // Validar formato de fecha
        $fecha_inicio_obj = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
        if (!$fecha_inicio_obj) {
            $errores[] = 'Formato de fecha de inicio inválido';
        }
    }
    
    // Validar fecha de fin (opcional)
    $fecha_fin = null;
    if (!empty($_POST['fecha_fin'])) {
        $fecha_fin = $_POST['fecha_fin'];
        
        // Validar formato
        $fecha_fin_obj = DateTime::createFromFormat('Y-m-d', $fecha_fin);
        if (!$fecha_fin_obj) {
            $errores[] = 'Formato de fecha de fin inválido';
        }
        
        // Validar que fecha fin > fecha inicio
        if (isset($fecha_inicio_obj) && $fecha_fin_obj && $fecha_fin_obj <= $fecha_inicio_obj) {
            $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
        }
    }
    
    // Validar límite de usos (opcional)
    $limite_usos = null;
    if (!empty($_POST['limite_usos'])) {
        $limite_usos = intval($_POST['limite_usos']);
        
        if ($limite_usos < 1 || $limite_usos > 1000) {
            $errores[] = 'El límite de usos debe estar entre 1 y 1000';
        }
    }
    
    // Validar beneficios
    $beneficio_referente = null;
    if (!empty($_POST['beneficio_referente'])) {
        $beneficio_referente = trim($_POST['beneficio_referente']);
        if (strlen($beneficio_referente) > 500) {
            $errores[] = 'El beneficio del referente no puede exceder los 500 caracteres';
        }
    }
    
    $beneficio_referido = null;
    if (!empty($_POST['beneficio_referido'])) {
        $beneficio_referido = trim($_POST['beneficio_referido']);
        if (strlen($beneficio_referido) > 500) {
            $errores[] = 'El beneficio del referido no puede exceder los 500 caracteres';
        }
    }
    
    // Validar estado activo
    $activo = isset($_POST['activo']) && $_POST['activo'] == 1 ? 1 : 0;
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $_SESSION['mensaje_sistema'] = implode('<br>', $errores);
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    // Insertar el código de referido
    $sql = "INSERT INTO codigos_referido 
            (codigo, apoderado_id, familia_id, descripcion, beneficio_referente, beneficio_referido, 
             limite_usos, usos_actuales, fecha_inicio, fecha_fin, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['mensaje_sistema'] = 'Error al preparar la consulta: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    $stmt->bind_param(
        "siisssissi",
        $codigo,
        $apoderado_id,
        $familia_id,
        $descripcion,
        $beneficio_referente,
        $beneficio_referido,
        $limite_usos,
        $fecha_inicio,
        $fecha_fin,
        $activo
    );
    
    if ($stmt->execute()) {
        $codigo_id = $stmt->insert_id;
        
        // Preparar mensaje de éxito detallado
        $mensaje_exito = "Código de referido <strong style='font-family: Courier New; font-size: 1.1rem;'>{$codigo}</strong> generado exitosamente";
        
        if ($apoderado_id) {
            // Obtener nombre del apoderado
            $nombre_apoderado = $conn->query("SELECT CONCAT(nombres, ' ', apellidos) as nombre FROM apoderados WHERE id = {$apoderado_id}")->fetch_assoc()['nombre'];
            $mensaje_exito .= "<br><small>Asignado a: {$nombre_apoderado}</small>";
        } elseif ($familia_id) {
            // Obtener apellido de la familia
            $apellido_familia = $conn->query("SELECT apellido_principal FROM familias WHERE id = {$familia_id}")->fetch_assoc()['apellido_principal'];
            $mensaje_exito .= "<br><small>Asignado a: Familia {$apellido_familia}</small>";
        } else {
            $mensaje_exito .= "<br><small>Código general/campaña</small>";
        }
        
        if ($fecha_fin) {
            $fecha_fin_formateada = date('d/m/Y', strtotime($fecha_fin));
            $mensaje_exito .= "<br><small>Válido hasta: {$fecha_fin_formateada}</small>";
        } else {
            $mensaje_exito .= "<br><small>Sin fecha de vencimiento</small>";
        }
        
        $_SESSION['mensaje_sistema'] = $mensaje_exito;
        $_SESSION['tipo_mensaje'] = 'success';
        
        // Crear script SweetAlert personalizado
        $_SESSION['swal_script'] = "
            Swal.fire({
                icon: 'success',
                title: '¡Código Generado!',
                html: '{$mensaje_exito}',
                confirmButtonText: 'Aceptar',
                timer: 5000,
                timerProgressBar: true
            });
        ";
    } else {
        $_SESSION['mensaje_sistema'] = 'Error al generar el código: ' . $stmt->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }
    
    $stmt->close();
    header('Location: ../../referidos_exalumnos.php');
    exit();
}

// ==========================================
// ACCIÓN: GESTIONAR BENEFICIOS
// ==========================================
if ($accion === 'gestionar_beneficios') {
    
    // Validar campos obligatorios
    $errores = [];
    
    // Validar ID del código
    if (empty($_POST['codigo_referido_id'])) {
        $errores[] = 'ID del código de referido no especificado';
    } else {
        $codigo_referido_id = intval($_POST['codigo_referido_id']);
        
        // Verificar que el código existe
        $check_codigo = $conn->prepare("SELECT codigo FROM codigos_referido WHERE id = ?");
        $check_codigo->bind_param("i", $codigo_referido_id);
        $check_codigo->execute();
        $result_check = $check_codigo->get_result();
        
        if ($result_check->num_rows === 0) {
            $errores[] = 'El código de referido no existe';
        } else {
            $codigo_nombre = $result_check->fetch_assoc()['codigo'];
        }
        $check_codigo->close();
    }
    
    // Validar tipo de beneficio
    $tipo_beneficio = $_POST['tipo_beneficio'] ?? 'porcentaje';
    if (!in_array($tipo_beneficio, ['porcentaje', 'monto'])) {
        $errores[] = 'Tipo de beneficio inválido';
    }
    
    // Validar descripciones obligatorias
    if (empty($_POST['descripcion_beneficio_referente'])) {
        $errores[] = 'La descripción del beneficio para el referente es obligatoria';
    } else {
        $desc_referente = trim($_POST['descripcion_beneficio_referente']);
        if (strlen($desc_referente) > 500) {
            $errores[] = 'La descripción del beneficio referente no puede exceder 500 caracteres';
        }
    }
    
    if (empty($_POST['descripcion_beneficio_referido'])) {
        $errores[] = 'La descripción del beneficio para el referido es obligatoria';
    } else {
        $desc_referido = trim($_POST['descripcion_beneficio_referido']);
        if (strlen($desc_referido) > 500) {
            $errores[] = 'La descripción del beneficio referido no puede exceder 500 caracteres';
        }
    }
    
    // Validar valores según tipo de beneficio
    if ($tipo_beneficio === 'porcentaje') {
        // Validar porcentajes
        $porcentaje_referente = null;
        $porcentaje_referido = null;
        
        if (!empty($_POST['porcentaje_referente'])) {
            $porcentaje_referente = intval($_POST['porcentaje_referente']);
            if ($porcentaje_referente < 0 || $porcentaje_referente > 100) {
                $errores[] = 'El porcentaje del referente debe estar entre 0 y 100';
            }
        }
        
        if (!empty($_POST['porcentaje_referido'])) {
            $porcentaje_referido = intval($_POST['porcentaje_referido']);
            if ($porcentaje_referido < 0 || $porcentaje_referido > 100) {
                $errores[] = 'El porcentaje del referido debe estar entre 0 y 100';
            }
        }
        
        // Construir descripciones con porcentajes
        if ($porcentaje_referente) {
            $aplicable_referente = $_POST['aplicable_en_referente'] ?? '';
            $desc_referente = $porcentaje_referente . '% de descuento' . 
                            ($aplicable_referente ? ' en ' . $aplicable_referente : '') . 
                            '. ' . $desc_referente;
        }
        
        if ($porcentaje_referido) {
            $aplicable_referido = $_POST['aplicable_en_referido'] ?? '';
            $desc_referido = $porcentaje_referido . '% de descuento' . 
                           ($aplicable_referido ? ' en ' . $aplicable_referido : '') . 
                           '. ' . $desc_referido;
        }
        
    } else {
        // Validar montos
        $monto_referente = null;
        $monto_referido = null;
        
        if (!empty($_POST['monto_referente'])) {
            $monto_referente = floatval($_POST['monto_referente']);
            if ($monto_referente < 0 || $monto_referente > 10000) {
                $errores[] = 'El monto del referente debe estar entre 0 y 10000';
            }
        }
        
        if (!empty($_POST['monto_referido'])) {
            $monto_referido = floatval($_POST['monto_referido']);
            if ($monto_referido < 0 || $monto_referido > 10000) {
                $errores[] = 'El monto del referido debe estar entre 0 y 10000';
            }
        }
        
        // Construir descripciones con montos
        if ($monto_referente) {
            $aplicable_referente = $_POST['aplicable_en_referente'] ?? '';
            $desc_referente = 'S/ ' . number_format($monto_referente, 2) . ' de descuento' . 
                            ($aplicable_referente ? ' en ' . $aplicable_referente : '') . 
                            '. ' . $desc_referente;
        }
        
        if ($monto_referido) {
            $aplicable_referido = $_POST['aplicable_en_referido'] ?? '';
            $desc_referido = 'S/ ' . number_format($monto_referido, 2) . ' de descuento' . 
                           ($aplicable_referido ? ' en ' . $aplicable_referido : '') . 
                           '. ' . $desc_referido;
        }
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $_SESSION['mensaje_sistema'] = implode('<br>', $errores);
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    // Actualizar beneficios en la base de datos
    $sql = "UPDATE codigos_referido 
            SET beneficio_referente = ?, beneficio_referido = ?, updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['mensaje_sistema'] = 'Error al preparar la consulta: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    $stmt->bind_param("ssi", $desc_referente, $desc_referido, $codigo_referido_id);
    
    if ($stmt->execute()) {
        $mensaje_exito = "Beneficios del código <strong style='font-family: Courier New;'>{$codigo_nombre}</strong> actualizados exitosamente";
        
        $_SESSION['mensaje_sistema'] = $mensaje_exito;
        $_SESSION['tipo_mensaje'] = 'success';
        
        // Crear script SweetAlert personalizado
        $_SESSION['swal_script'] = "
            Swal.fire({
                icon: 'success',
                title: '¡Beneficios Actualizados!',
                html: '{$mensaje_exito}<br><br>" .
                "<div style=\"text-align: left; font-size: 0.9rem;\">" .
                "<strong>Referente:</strong> {$desc_referente}<br><br>" .
                "<strong>Referido:</strong> {$desc_referido}" .
                "</div>',
                confirmButtonText: 'Aceptar',
                width: '600px',
                timer: 6000,
                timerProgressBar: true
            });
        ";
    } else {
        $_SESSION['mensaje_sistema'] = 'Error al actualizar beneficios: ' . $stmt->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }
    
    $stmt->close();
    header('Location: ../../referidos_exalumnos.php');
    exit();
}

// ==========================================
// ACCIÓN: OBTENER USOS (AJAX - No redirecciona)
// ==========================================
if ($accion === 'obtener_usos') {
    header('Content-Type: application/json');
    
    if (empty($_POST['codigo_referido_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID del código de referido no especificado'
        ]);
        exit();
    }
    
    $codigo_referido_id = intval($_POST['codigo_referido_id']);
    
    // Obtener información del código
    $sql_codigo = "SELECT codigo, limite_usos, usos_actuales FROM codigos_referido WHERE id = ?";
    $stmt_codigo = $conn->prepare($sql_codigo);
    $stmt_codigo->bind_param("i", $codigo_referido_id);
    $stmt_codigo->execute();
    $result_codigo = $stmt_codigo->get_result();
    
    if ($result_codigo->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Código de referido no encontrado'
        ]);
        exit();
    }
    
    $codigo_info = $result_codigo->fetch_assoc();
    $stmt_codigo->close();
    
    // Obtener usos del código
    $sql_usos = "SELECT 
                    ur.id,
                    ur.fecha_uso,
                    ur.convertido,
                    ur.fecha_conversion,
                    ur.observaciones,
                    l.codigo as lead_codigo,
                    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as estudiante_nombre
                 FROM usos_referido ur
                 LEFT JOIN leads l ON ur.lead_id = l.id
                 WHERE ur.codigo_referido_id = ?
                 ORDER BY ur.fecha_uso DESC";
    
    $stmt_usos = $conn->prepare($sql_usos);
    $stmt_usos->bind_param("i", $codigo_referido_id);
    $stmt_usos->execute();
    $result_usos = $stmt_usos->get_result();
    
    $usos = [];
    while ($row = $result_usos->fetch_assoc()) {
        $usos[] = $row;
    }
    $stmt_usos->close();
    
    // Calcular estadísticas
    $total_usos = count($usos);
    $conversiones = 0;
    
    foreach ($usos as $uso) {
        if ($uso['convertido'] == 1) {
            $conversiones++;
        }
    }
    
    $tasa_conversion = $total_usos > 0 ? round(($conversiones / $total_usos) * 100, 1) : 0;
    $usos_disponibles = $codigo_info['limite_usos'] ? ($codigo_info['limite_usos'] - $codigo_info['usos_actuales']) : '∞';
    
    echo json_encode([
        'success' => true,
        'usos' => $usos,
        'estadisticas' => [
            'total_usos' => $total_usos,
            'conversiones' => $conversiones,
            'tasa_conversion' => $tasa_conversion,
            'usos_disponibles' => $usos_disponibles
        ]
    ]);
    exit();
}

// ==========================================
// ACCIÓN: DESACTIVAR/ACTIVAR CÓDIGO
// ==========================================
if ($accion === 'toggle_estado') {
    
    if (empty($_POST['codigo_referido_id'])) {
        $_SESSION['mensaje_sistema'] = 'ID del código no especificado';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    $codigo_referido_id = intval($_POST['codigo_referido_id']);
    
    // Obtener estado actual
    $check = $conn->prepare("SELECT codigo, activo FROM codigos_referido WHERE id = ?");
    $check->bind_param("i", $codigo_referido_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['mensaje_sistema'] = 'Código de referido no encontrado';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../../referidos_exalumnos.php');
        exit();
    }
    
    $codigo_data = $result->fetch_assoc();
    $nuevo_estado = $codigo_data['activo'] == 1 ? 0 : 1;
    $check->close();
    
    // Actualizar estado
    $update = $conn->prepare("UPDATE codigos_referido SET activo = ? WHERE id = ?");
    $update->bind_param("ii", $nuevo_estado, $codigo_referido_id);
    
    if ($update->execute()) {
        $estado_texto = $nuevo_estado == 1 ? 'activado' : 'desactivado';
        
        $_SESSION['mensaje_sistema'] = "Código <strong>{$codigo_data['codigo']}</strong> {$estado_texto} exitosamente";
        $_SESSION['tipo_mensaje'] = 'success';
        
        $_SESSION['swal_script'] = "
            Swal.fire({
                icon: 'success',
                title: '¡Estado Actualizado!',
                text: 'El código ha sido {$estado_texto}',
                timer: 3000,
                showConfirmButton: false
            });
        ";
    } else {
        $_SESSION['mensaje_sistema'] = 'Error al cambiar el estado del código';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    
    $update->close();
    header('Location: ../../referidos_exalumnos.php');
    exit();
}

// ==========================================
// ACCIÓN NO RECONOCIDA
// ==========================================
$_SESSION['mensaje_sistema'] = 'Acción no reconocida: ' . htmlspecialchars($accion);
$_SESSION['tipo_mensaje'] = 'error';
header('Location: ../../referidos_exalumnos.php');
exit();

$conn->close();
?>