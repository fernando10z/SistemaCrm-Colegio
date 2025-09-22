<?php
// actions/procesar_lead.php
include '../../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            crearLead($conn);
            break;
        case 'editar':
            editarLead($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function crearLead($conn) {
    // Validaciones básicas
    $campos_requeridos = ['nombres_estudiante', 'apellidos_estudiante', 'nombres_contacto', 'apellidos_contacto', 'telefono', 'email', 'canal_captacion_id', 'estado_lead_id', 'grado_interes_id'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => 'El campo ' . $campo . ' es requerido']);
            return;
        }
    }

    // Verificar duplicados por email y teléfono
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    
    $check_sql = "SELECT id, codigo_lead FROM leads WHERE (email = ? OR telefono = ?) AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $telefono);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe un lead con el mismo email o teléfono (ID: ' . $existing['id'] . ', Código: ' . $existing['codigo_lead'] . ')'
        ]);
        return;
    }

    // Generar código único de lead
    $year = date('Y');
    $count_sql = "SELECT COUNT(*) as total FROM leads WHERE YEAR(created_at) = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $year);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count = $count_result->fetch_assoc()['total'] + 1;
    $codigo_lead = 'LD' . $year . str_pad($count, 3, '0', STR_PAD_LEFT);

    // Preparar datos
    $nombres_estudiante = trim($_POST['nombres_estudiante']);
    $apellidos_estudiante = trim($_POST['apellidos_estudiante']);
    $fecha_nacimiento_estudiante = !empty($_POST['fecha_nacimiento_estudiante']) ? $_POST['fecha_nacimiento_estudiante'] : null;
    $genero_estudiante = !empty($_POST['genero_estudiante']) ? $_POST['genero_estudiante'] : null;
    $grado_interes_id = $_POST['grado_interes_id'];
    $nombres_contacto = trim($_POST['nombres_contacto']);
    $apellidos_contacto = trim($_POST['apellidos_contacto']);
    $whatsapp = !empty($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null;
    $colegio_procedencia = !empty($_POST['colegio_procedencia']) ? trim($_POST['colegio_procedencia']) : null;
    $motivo_cambio = !empty($_POST['motivo_cambio']) ? trim($_POST['motivo_cambio']) : null;
    $canal_captacion_id = $_POST['canal_captacion_id'];
    $estado_lead_id = $_POST['estado_lead_id'];
    $responsable_id = !empty($_POST['responsable_id']) ? $_POST['responsable_id'] : null;
    $prioridad = $_POST['prioridad'] ?? 'media';
    $puntaje_interes = !empty($_POST['puntaje_interes']) ? $_POST['puntaje_interes'] : 50;
    $proxima_accion_fecha = !empty($_POST['proxima_accion_fecha']) ? $_POST['proxima_accion_fecha'] : null;
    $proxima_accion_descripcion = !empty($_POST['proxima_accion_descripcion']) ? trim($_POST['proxima_accion_descripcion']) : null;
    $observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $utm_source = !empty($_POST['utm_source']) ? trim($_POST['utm_source']) : null;
    $utm_medium = !empty($_POST['utm_medium']) ? trim($_POST['utm_medium']) : null;
    $utm_campaign = !empty($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : null;
    $ip_origen = $_SERVER['REMOTE_ADDR'] ?? null;

    // Insertar lead
    $sql = "INSERT INTO leads (
        codigo_lead, canal_captacion_id, estado_lead_id, responsable_id,
        nombres_estudiante, apellidos_estudiante, fecha_nacimiento_estudiante, genero_estudiante,
        grado_interes_id, nombres_contacto, apellidos_contacto, telefono, whatsapp, email,
        colegio_procedencia, motivo_cambio, observaciones, prioridad, puntaje_interes,
        proxima_accion_fecha, proxima_accion_descripcion, utm_source, utm_medium, utm_campaign, ip_origen
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiissssissssssssiissss", 
        $codigo_lead, $canal_captacion_id, $estado_lead_id, $responsable_id,
        $nombres_estudiante, $apellidos_estudiante, $fecha_nacimiento_estudiante, $genero_estudiante,
        $grado_interes_id, $nombres_contacto, $apellidos_contacto, $telefono, $whatsapp, $email,
        $colegio_procedencia, $motivo_cambio, $observaciones, $prioridad, $puntaje_interes,
        $proxima_accion_fecha, $proxima_accion_descripcion, $utm_source, $utm_medium, $utm_campaign, $ip_origen
    );

    if ($stmt->execute()) {
        $lead_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Lead registrado exitosamente',
            'lead_id' => $lead_id,
            'codigo_lead' => $codigo_lead
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar el lead: ' . $stmt->error]);
    }
}

function editarLead($conn) {
    if (empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID del lead es requerido']);
        return;
    }

    $id = $_POST['id'];

    // Verificar que el lead existe
    $check_sql = "SELECT id FROM leads WHERE id = ? AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead no encontrado']);
        return;
    }

    // Verificar duplicados (excluyendo el lead actual)
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    
    $check_dup_sql = "SELECT id, codigo_lead FROM leads WHERE (email = ? OR telefono = ?) AND activo = 1 AND id != ?";
    $check_dup_stmt = $conn->prepare($check_dup_sql);
    $check_dup_stmt->bind_param("ssi", $email, $telefono, $id);
    $check_dup_stmt->execute();
    $check_dup_result = $check_dup_stmt->get_result();
    
    if ($check_dup_result->num_rows > 0) {
        $existing = $check_dup_result->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe otro lead con el mismo email o teléfono (ID: ' . $existing['id'] . ', Código: ' . $existing['codigo_lead'] . ')'
        ]);
        return;
    }

    // Preparar datos para actualización
    $nombres_estudiante = trim($_POST['nombres_estudiante']);
    $apellidos_estudiante = trim($_POST['apellidos_estudiante']);
    $fecha_nacimiento_estudiante = !empty($_POST['fecha_nacimiento_estudiante']) ? $_POST['fecha_nacimiento_estudiante'] : null;
    $genero_estudiante = !empty($_POST['genero_estudiante']) ? $_POST['genero_estudiante'] : null;
    $grado_interes_id = $_POST['grado_interes_id'];
    $nombres_contacto = trim($_POST['nombres_contacto']);
    $apellidos_contacto = trim($_POST['apellidos_contacto']);
    $whatsapp = !empty($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null;
    $colegio_procedencia = !empty($_POST['colegio_procedencia']) ? trim($_POST['colegio_procedencia']) : null;
    $motivo_cambio = !empty($_POST['motivo_cambio']) ? trim($_POST['motivo_cambio']) : null;
    $canal_captacion_id = $_POST['canal_captacion_id'];
    $estado_lead_id = $_POST['estado_lead_id'];
    $responsable_id = !empty($_POST['responsable_id']) ? $_POST['responsable_id'] : null;
    $prioridad = $_POST['prioridad'] ?? 'media';
    $puntaje_interes = !empty($_POST['puntaje_interes']) ? $_POST['puntaje_interes'] : 50;
    $proxima_accion_fecha = !empty($_POST['proxima_accion_fecha']) ? $_POST['proxima_accion_fecha'] : null;
    $proxima_accion_descripcion = !empty($_POST['proxima_accion_descripcion']) ? trim($_POST['proxima_accion_descripcion']) : null;
    $observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $utm_source = !empty($_POST['utm_source']) ? trim($_POST['utm_source']) : null;
    $utm_medium = !empty($_POST['utm_medium']) ? trim($_POST['utm_medium']) : null;
    $utm_campaign = !empty($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : null;
    $fecha_conversion = !empty($_POST['fecha_conversion']) ? $_POST['fecha_conversion'] : null;

    // Actualizar lead
    $sql = "UPDATE leads SET 
        canal_captacion_id = ?, estado_lead_id = ?, responsable_id = ?,
        nombres_estudiante = ?, apellidos_estudiante = ?, fecha_nacimiento_estudiante = ?, genero_estudiante = ?,
        grado_interes_id = ?, nombres_contacto = ?, apellidos_contacto = ?, telefono = ?, whatsapp = ?, email = ?,
        colegio_procedencia = ?, motivo_cambio = ?, observaciones = ?, prioridad = ?, puntaje_interes = ?,
        proxima_accion_fecha = ?, proxima_accion_descripcion = ?, utm_source = ?, utm_medium = ?, utm_campaign = ?,
        fecha_conversion = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissssissssssssisssssi", 
        $canal_captacion_id, $estado_lead_id, $responsable_id,
        $nombres_estudiante, $apellidos_estudiante, $fecha_nacimiento_estudiante, $genero_estudiante,
        $grado_interes_id, $nombres_contacto, $apellidos_contacto, $telefono, $whatsapp, $email,
        $colegio_procedencia, $motivo_cambio, $observaciones, $prioridad, $puntaje_interes,
        $proxima_accion_fecha, $proxima_accion_descripcion, $utm_source, $utm_medium, $utm_campaign,
        $fecha_conversion, $id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Lead actualizado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el lead: ' . $stmt->error]);
    }
}

$conn->close();
?>