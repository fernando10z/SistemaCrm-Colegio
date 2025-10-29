<?php
// actions/procesar_interaccion.php
include '../../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear_interaccion':
            crearInteraccion($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function crearInteraccion($conn) {
    // Validaciones básicas
    $campos_requeridos = ['lead_id', 'tipo_interaccion_id', 'usuario_id', 'asunto', 'descripcion', 'ya_realizado'];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
            echo json_encode(['success' => false, 'message' => 'El campo ' . $campo . ' es requerido']);
            return;
        }
    }

    // Sanitizar y validar datos
    $lead_id = filter_var($_POST['lead_id'], FILTER_VALIDATE_INT);
    $tipo_interaccion_id = filter_var($_POST['tipo_interaccion_id'], FILTER_VALIDATE_INT);
    $usuario_id = filter_var($_POST['usuario_id'], FILTER_VALIDATE_INT);
    $asunto = trim($_POST['asunto']);
    $descripcion = trim($_POST['descripcion']);
    $ya_realizado = filter_var($_POST['ya_realizado'], FILTER_VALIDATE_INT);
    
    // Validar IDs
    if (!$lead_id || !$tipo_interaccion_id || !$usuario_id) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos en IDs']);
        return;
    }
    
    // Validar longitudes
    if (strlen($asunto) > 200) {
        echo json_encode(['success' => false, 'message' => 'El asunto no puede exceder 200 caracteres']);
        return;
    }
    
    if (strlen($descripcion) > 5000) {
        echo json_encode(['success' => false, 'message' => 'La descripción no puede exceder 5000 caracteres']);
        return;
    }
    
    // Validar ya_realizado
    if ($ya_realizado !== 0 && $ya_realizado !== 1) {
        echo json_encode(['success' => false, 'message' => 'Valor inválido para ya_realizado']);
        return;
    }
    
    // Procesar campos opcionales
    $fecha_programada = !empty($_POST['fecha_programada']) ? trim($_POST['fecha_programada']) : null;
    $duracion_minutos = !empty($_POST['duracion_minutos']) ? filter_var($_POST['duracion_minutos'], FILTER_VALIDATE_INT) : null;
    $resultado = !empty($_POST['resultado']) ? trim($_POST['resultado']) : null;
    $observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $requiere_seguimiento = isset($_POST['requiere_seguimiento']) ? 1 : 0;
    $fecha_proximo_seguimiento = !empty($_POST['fecha_proximo_seguimiento']) ? trim($_POST['fecha_proximo_seguimiento']) : null;

    // VALIDACIÓN CRÍTICA: Si ya se realizó, resultado es OBLIGATORIO
    if ($ya_realizado == 1) {
        if (empty($resultado)) {
            echo json_encode(['success' => false, 'message' => 'El resultado es obligatorio cuando el contacto ya se realizó']);
            return;
        }
        
        // Validar que el resultado sea uno de los valores válidos del ENUM
        $resultados_validos = ['exitoso', 'sin_respuesta', 'reagendar', 'no_interesado', 'convertido'];
        if (!in_array($resultado, $resultados_validos)) {
            echo json_encode(['success' => false, 'message' => 'Resultado inválido']);
            return;
        }
        
        // Si ya se realizó, debe tener fecha
        if (empty($fecha_programada)) {
            echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria cuando el contacto ya se realizó']);
            return;
        }
    } else {
        // Si no se realizó, el resultado DEBE ser NULL (no cadena vacía)
        $resultado = null;
    }
    
    // Validar duración de minutos
    if ($duracion_minutos !== null && ($duracion_minutos < 1 || $duracion_minutos > 300)) {
        echo json_encode(['success' => false, 'message' => 'La duración debe estar entre 1 y 300 minutos']);
        return;
    }
    
    // Validar que si requiere seguimiento, tenga fecha
    if ($requiere_seguimiento == 1 && empty($fecha_proximo_seguimiento)) {
        echo json_encode(['success' => false, 'message' => 'La fecha de seguimiento es obligatoria']);
        return;
    }
    
    // Validar observaciones
    if ($observaciones && strlen($observaciones) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Las observaciones no pueden exceder 2000 caracteres']);
        return;
    }

    // Verificar que el lead existe
    $check_lead_sql = "SELECT id FROM leads WHERE id = ? AND activo = 1";
    $check_lead_stmt = $conn->prepare($check_lead_sql);
    $check_lead_stmt->bind_param("i", $lead_id);
    $check_lead_stmt->execute();
    
    if ($check_lead_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead no encontrado o inactivo']);
        return;
    }
    
    // Verificar que el usuario existe
    $check_user_sql = "SELECT id FROM usuarios WHERE id = ? AND activo = 1";
    $check_user_stmt = $conn->prepare($check_user_sql);
    $check_user_stmt->bind_param("i", $usuario_id);
    $check_user_stmt->execute();
    
    if ($check_user_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o inactivo']);
        return;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Determinar estado y fechas según si ya se realizó o no
        if ($ya_realizado == 1) {
            $estado = 'realizado';
            $fecha_realizada = $fecha_programada ?: date('Y-m-d H:i:s');
            $fecha_prog = $fecha_programada ?: date('Y-m-d H:i:s');
        } else {
            $estado = 'programado';
            $fecha_realizada = null;
            $fecha_prog = $fecha_programada ?: date('Y-m-d H:i:s', strtotime('+1 day'));
        }

        // Construir la consulta SQL dinámicamente para manejar NULL correctamente
        if ($resultado === null) {
            // Si resultado es NULL, usar NULL en SQL
            $sql = "INSERT INTO interacciones (
                tipo_interaccion_id, usuario_id, lead_id, asunto, descripcion,
                fecha_programada, fecha_realizada, duracion_minutos, resultado,
                observaciones, requiere_seguimiento, fecha_proximo_seguimiento, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissssisss", 
                $tipo_interaccion_id, $usuario_id, $lead_id, $asunto, $descripcion,
                $fecha_prog, $fecha_realizada, $duracion_minutos,
                $observaciones, $requiere_seguimiento, $fecha_proximo_seguimiento, $estado
            );
        } else {
            // Si resultado tiene valor, incluirlo normalmente
            $sql = "INSERT INTO interacciones (
                tipo_interaccion_id, usuario_id, lead_id, asunto, descripcion,
                fecha_programada, fecha_realizada, duracion_minutos, resultado,
                observaciones, requiere_seguimiento, fecha_proximo_seguimiento, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiisssssissss", 
                $tipo_interaccion_id, $usuario_id, $lead_id, $asunto, $descripcion,
                $fecha_prog, $fecha_realizada, $duracion_minutos, $resultado,
                $observaciones, $requiere_seguimiento, $fecha_proximo_seguimiento, $estado
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Error al registrar la interacción: ' . $stmt->error);
        }

        $interaccion_id = $conn->insert_id;

        // Actualizar el lead con la fecha de última interacción
        $update_lead_sql = "UPDATE leads SET fecha_ultima_interaccion = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_lead_sql);
        $fecha_interaccion = $fecha_realizada ?: $fecha_prog;
        $update_stmt->bind_param("si", $fecha_interaccion, $lead_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Error al actualizar fecha de interacción del lead');
        }

        // Actualizar datos adicionales del lead si se proporcionaron
        $updates_lead = [];
        $params_lead = [];
        $types_lead = "";

        if (!empty($_POST['nuevo_estado_lead_id'])) {
            $nuevo_estado = filter_var($_POST['nuevo_estado_lead_id'], FILTER_VALIDATE_INT);
            if ($nuevo_estado) {
                $updates_lead[] = "estado_lead_id = ?";
                $params_lead[] = $nuevo_estado;
                $types_lead .= "i";
            }
        }

        if (!empty($_POST['nueva_prioridad'])) {
            $prioridades_validas = ['baja', 'media', 'alta', 'urgente'];
            $nueva_prioridad = trim($_POST['nueva_prioridad']);
            if (in_array($nueva_prioridad, $prioridades_validas)) {
                $updates_lead[] = "prioridad = ?";
                $params_lead[] = $nueva_prioridad;
                $types_lead .= "s";
            }
        }

        if (!empty($_POST['nuevo_puntaje_interes'])) {
            $puntaje = filter_var($_POST['nuevo_puntaje_interes'], FILTER_VALIDATE_INT);
            if ($puntaje !== false && $puntaje >= 0 && $puntaje <= 100) {
                $updates_lead[] = "puntaje_interes = ?";
                $params_lead[] = $puntaje;
                $types_lead .= "i";
            }
        }

        if (!empty($_POST['proxima_accion_fecha'])) {
            $updates_lead[] = "proxima_accion_fecha = ?";
            $params_lead[] = trim($_POST['proxima_accion_fecha']);
            $types_lead .= "s";
        }

        if (!empty($_POST['proxima_accion_descripcion'])) {
            $proxima_accion = trim($_POST['proxima_accion_descripcion']);
            if (strlen($proxima_accion) <= 200) {
                $updates_lead[] = "proxima_accion_descripcion = ?";
                $params_lead[] = $proxima_accion;
                $types_lead .= "s";
            }
        }

        // Si hay actualizaciones del lead, ejecutarlas
        if (!empty($updates_lead)) {
            $update_lead_extra_sql = "UPDATE leads SET " . implode(", ", $updates_lead) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $params_lead[] = $lead_id;
            $types_lead .= "i";

            $update_extra_stmt = $conn->prepare($update_lead_extra_sql);
            $update_extra_stmt->bind_param($types_lead, ...$params_lead);
            
            if (!$update_extra_stmt->execute()) {
                throw new Exception('Error al actualizar datos adicionales del lead');
            }
        }

        // Si el resultado es "convertido", marcar fecha de conversión
        if ($resultado === 'convertido') {
            $convert_sql = "UPDATE leads SET fecha_conversion = CURDATE(), estado_lead_id = 5 WHERE id = ?";
            $convert_stmt = $conn->prepare($convert_sql);
            $convert_stmt->bind_param("i", $lead_id);
            
            if (!$convert_stmt->execute()) {
                throw new Exception('Error al marcar lead como convertido');
            }
        }

        // Confirmar transacción
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Interacción registrada exitosamente',
            'interaccion_id' => $interaccion_id
        ]);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error en la transacción: ' . $e->getMessage()]);
    }
}

$conn->close();
?>