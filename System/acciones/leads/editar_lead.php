<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
include '../../bd/conexion.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

function responder($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido');
}

// Validar ID
if (empty($_POST['id'])) {
    responder(false, 'ID del lead es requerido');
}

$id = intval($_POST['id']);

// Verificar que existe
$check = $conn->prepare("SELECT id FROM leads WHERE id = ? AND activo = 1");
$check->bind_param("i", $id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    responder(false, 'Lead no encontrado');
}
$check->close();

// Validar campos requeridos
$requeridos = [
    'nombres_estudiante' => 'Nombres del Estudiante',
    'apellidos_estudiante' => 'Apellidos del Estudiante',
    'nombres_contacto' => 'Nombres del Contacto',
    'apellidos_contacto' => 'Apellidos del Contacto',
    'telefono' => 'Teléfono',
    'canal_captacion_id' => 'Canal de Captación',
    'estado_lead_id' => 'Estado',
    'grado_interes_id' => 'Grado de Interés'
];

foreach ($requeridos as $campo => $nombre) {
    if (empty(trim($_POST[$campo] ?? ''))) {
        responder(false, "El campo '$nombre' es obligatorio");
    }
}

// Validar teléfono - EXACTAMENTE 9 DÍGITOS
$telefono = trim($_POST['telefono']);
if (!preg_match('/^[0-9]{9}$/', $telefono)) {
    responder(false, 'El teléfono debe tener exactamente 9 dígitos');
}

// Validar WhatsApp si se proporciona - EXACTAMENTE 9 DÍGITOS
$whatsapp = !empty($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null;
if ($whatsapp && !preg_match('/^[0-9]{9}$/', $whatsapp)) {
    responder(false, 'El WhatsApp debe tener exactamente 9 dígitos');
}

// Validar email solo si se proporciona
$email = !empty($_POST['email']) ? trim($_POST['email']) : null;
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, 'El formato del email no es válido');
}

// Verificar duplicados (excluyendo el mismo registro)
if ($email) {
    $check_dup = $conn->prepare("SELECT id FROM leads WHERE (email = ? OR telefono = ?) AND activo = 1 AND id != ?");
    $check_dup->bind_param("ssi", $email, $telefono, $id);
} else {
    $check_dup = $conn->prepare("SELECT id FROM leads WHERE telefono = ? AND activo = 1 AND id != ?");
    $check_dup->bind_param("si", $telefono, $id);
}

$check_dup->execute();
if ($check_dup->get_result()->num_rows > 0) {
    responder(false, 'Ya existe otro lead con este teléfono' . ($email ? ' o email' : ''));
}
$check_dup->close();

// Preparar valores con manejo de NULL
$canal_captacion_id = intval($_POST['canal_captacion_id']);
$estado_lead_id = intval($_POST['estado_lead_id']);
$grado_interes_id = intval($_POST['grado_interes_id']);
$nombres_estudiante = trim($_POST['nombres_estudiante']);
$apellidos_estudiante = trim($_POST['apellidos_estudiante']);
$nombres_contacto = trim($_POST['nombres_contacto']);
$apellidos_contacto = trim($_POST['apellidos_contacto']);
$prioridad = $_POST['prioridad'] ?? 'media';
$puntaje_interes = intval($_POST['puntaje_interes'] ?? 50);

$responsable_id = !empty($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null;
$fecha_nacimiento_estudiante = !empty($_POST['fecha_nacimiento_estudiante']) ? trim($_POST['fecha_nacimiento_estudiante']) : null;
$genero_estudiante = !empty($_POST['genero_estudiante']) ? trim($_POST['genero_estudiante']) : null;
$colegio_procedencia = !empty($_POST['colegio_procedencia']) ? trim($_POST['colegio_procedencia']) : null;
$motivo_cambio = !empty($_POST['motivo_cambio']) ? trim($_POST['motivo_cambio']) : null;
$observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
$proxima_accion_fecha = !empty($_POST['proxima_accion_fecha']) ? trim($_POST['proxima_accion_fecha']) : null;
$proxima_accion_descripcion = !empty($_POST['proxima_accion_descripcion']) ? trim($_POST['proxima_accion_descripcion']) : null;
$utm_source = !empty($_POST['utm_source']) ? trim($_POST['utm_source']) : null;
$utm_medium = !empty($_POST['utm_medium']) ? trim($_POST['utm_medium']) : null;
$utm_campaign = !empty($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : null;
$fecha_conversion = !empty($_POST['fecha_conversion']) ? trim($_POST['fecha_conversion']) : null;

// SQL UPDATE - 25 parámetros
$sql = "UPDATE leads SET 
    canal_captacion_id = ?,
    estado_lead_id = ?,
    responsable_id = ?,
    nombres_estudiante = ?,
    apellidos_estudiante = ?,
    fecha_nacimiento_estudiante = ?,
    genero_estudiante = ?,
    grado_interes_id = ?,
    nombres_contacto = ?,
    apellidos_contacto = ?,
    telefono = ?,
    whatsapp = ?,
    email = ?,
    colegio_procedencia = ?,
    motivo_cambio = ?,
    observaciones = ?,
    prioridad = ?,
    puntaje_interes = ?,
    proxima_accion_fecha = ?,
    proxima_accion_descripcion = ?,
    utm_source = ?,
    utm_medium = ?,
    utm_campaign = ?,
    fecha_conversion = ?,
    updated_at = CURRENT_TIMESTAMP
    WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder(false, 'Error en la consulta: ' . $conn->error);
}

// CORREGIDO: 25 caracteres en el string de tipos
// i i i s s s s i s s s s s s s s s i s s s s s s i
$stmt->bind_param(
    "iiissssisssssssssissssssi", // 25 caracteres
    $canal_captacion_id,          // i - 1
    $estado_lead_id,              // i - 2
    $responsable_id,              // i - 3
    $nombres_estudiante,          // s - 4
    $apellidos_estudiante,        // s - 5
    $fecha_nacimiento_estudiante, // s - 6
    $genero_estudiante,           // s - 7
    $grado_interes_id,            // i - 8
    $nombres_contacto,            // s - 9
    $apellidos_contacto,          // s - 10
    $telefono,                    // s - 11
    $whatsapp,                    // s - 12
    $email,                       // s - 13
    $colegio_procedencia,         // s - 14
    $motivo_cambio,               // s - 15
    $observaciones,               // s - 16
    $prioridad,                   // s - 17
    $puntaje_interes,             // i - 18
    $proxima_accion_fecha,        // s - 19
    $proxima_accion_descripcion,  // s - 20
    $utm_source,                  // s - 21
    $utm_medium,                  // s - 22
    $utm_campaign,                // s - 23
    $fecha_conversion,            // s - 24
    $id                           // i - 25
);

if (!$stmt->execute()) {
    responder(false, 'Error al actualizar: ' . $stmt->error);
}

$stmt->close();
$conn->close();

responder(true, 'Lead actualizado exitosamente');
?>