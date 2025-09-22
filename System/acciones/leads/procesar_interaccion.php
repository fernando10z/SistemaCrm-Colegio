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
    $campos_requeridos = ['lead_id', 'tipo_interaccion_id', 'usuario_id', 'asunto', 'descripcion'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => 'El campo ' . $campo . ' es requerido']);
            return;
        }
    }

    $lead_id = $_POST['lead_id'];
    $tipo_interaccion_id = $_POST['tipo_interaccion_id'];
    $usuario_id = $_POST['usuario_id'];
    $asunto = trim($_POST['asunto']);
    $descripcion = trim($_POST['descripcion']);
    $ya_realizado = $_POST['ya_realizado'] ?? 0;
    $fecha_programada = !empty($_POST['fecha_programada']) ? $_POST['fecha_programada'] : null;
    $duracion_minutos = !empty($_POST['duracion_minutos']) ? $_POST['duracion_minutos'] : null;
    $resultado = !empty($_POST['resultado']) ? $_POST['resultado'] : null;
    $observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $requiere_seguimiento = isset($_POST['requiere_seguimiento']) ? 1 : 0;
    $fecha_proximo_seguimiento = !empty($_POST['fecha_proximo_seguimiento']) ? $_POST['fecha_proximo_seguimiento'] : null;

    // Verificar que el lead existe
    $check_lead_sql = "SELECT id FROM leads WHERE id = ? AND activo = 1";
    $check_lead_stmt = $conn->prepare($check_lead_sql);
    $check_lead_stmt->bind_param("i", $lead_id);
    $check_lead_stmt->execute();
    
    if ($check_lead_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead no encontrado']);
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

        // Insertar interacción
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

        if (!$stmt->execute()) {
            throw new Exception('Error al registrar la interacción: ' . $stmt->error);
        }

        $interaccion_id = $conn->insert_id;

        // Actualizar el lead con la fecha de última interacción
        $update_lead_sql = "UPDATE leads SET fecha_ultima_interaccion = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_lead_sql);
        $fecha_interaccion = $fecha_realizada ?: $fecha_prog;
        $update_stmt->bind_param("si", $fecha_interaccion, $lead_id);
        $update_stmt->execute();

        // Actualizar datos adicionales del lead si se proporcionaron
        $updates_lead = [];
        $params_lead = [];
        $types_lead = "";

        if (!empty($_POST['nuevo_estado_lead_id'])) {
            $updates_lead[] = "estado_lead_id = ?";
            $params_lead[] = $_POST['nuevo_estado_lead_id'];
            $types_lead .= "i";
        }

        if (!empty($_POST['nueva_prioridad'])) {
            $updates_lead[] = "prioridad = ?";
            $params_lead[] = $_POST['nueva_prioridad'];
            $types_lead .= "s";
        }

        if (!empty($_POST['nuevo_puntaje_interes'])) {
            $updates_lead[] = "puntaje_interes = ?";
            $params_lead[] = $_POST['nuevo_puntaje_interes'];
            $types_lead .= "i";
        }

        if (!empty($_POST['proxima_accion_fecha'])) {
            $updates_lead[] = "proxima_accion_fecha = ?";
            $params_lead[] = $_POST['proxima_accion_fecha'];
            $types_lead .= "s";
        }

        if (!empty($_POST['proxima_accion_descripcion'])) {
            $updates_lead[] = "proxima_accion_descripcion = ?";
            $params_lead[] = trim($_POST['proxima_accion_descripcion']);
            $types_lead .= "s";
        }

        // Si hay actualizaciones del lead, ejecutarlas
        if (!empty($updates_lead)) {
            $update_lead_extra_sql = "UPDATE leads SET " . implode(", ", $updates_lead) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $params_lead[] = $lead_id;
            $types_lead .= "i";

            $update_extra_stmt = $conn->prepare($update_lead_extra_sql);
            $update_extra_stmt->bind_param($types_lead, ...$params_lead);
            $update_extra_stmt->execute();
        }

        // Si el resultado es "convertido", marcar fecha de conversión
        if ($resultado === 'convertido') {
            $convert_sql = "UPDATE leads SET fecha_conversion = CURDATE(), estado_lead_id = 5 WHERE id = ?";
            $convert_stmt = $conn->prepare($convert_sql);
            $convert_stmt->bind_param("i", $lead_id);
            $convert_stmt->execute();
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