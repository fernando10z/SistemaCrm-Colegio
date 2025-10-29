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
$whatsapp = null;
if (!empty($_POST['whatsapp'])) {
    $whatsapp = trim($_POST['whatsapp']);
    if (!preg_match('/^[0-9]{9}$/', $whatsapp)) {
        responder(false, 'El WhatsApp debe tener exactamente 9 dígitos');
    }
}

// Validar email solo si se proporciona
$email = null;
if (!empty($_POST['email'])) {
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        responder(false, 'El formato del email no es válido');
    }
    $email = trim($_POST['email']);
}

// Verificar duplicados
if ($email) {
    $check = $conn->prepare("SELECT codigo_lead FROM leads WHERE (email = ? OR telefono = ?) AND activo = 1");
    $check->bind_param("ss", $email, $telefono);
} else {
    $check = $conn->prepare("SELECT codigo_lead FROM leads WHERE telefono = ? AND activo = 1");
    $check->bind_param("s", $telefono);
}

$check->execute();
if ($check->get_result()->num_rows > 0) {
    responder(false, 'Ya existe un lead con este teléfono' . ($email ? ' o email' : ''));
}
$check->close();

// Generar código
$year = date('Y');
$result = $conn->query("SELECT COUNT(*) as total FROM leads WHERE YEAR(created_at) = '$year'");
$count = $result->fetch_assoc()['total'] + 1;
$codigo = 'LD' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);

// Construir SQL dinámicamente
$campos = [
    'codigo_lead', 'canal_captacion_id', 'estado_lead_id',
    'nombres_estudiante', 'apellidos_estudiante', 'grado_interes_id',
    'nombres_contacto', 'apellidos_contacto', 'telefono',
    'prioridad', 'puntaje_interes', 'ip_origen'
];

$valores_array = [
    $codigo,
    intval($_POST['canal_captacion_id']),
    intval($_POST['estado_lead_id']),
    trim($_POST['nombres_estudiante']),
    trim($_POST['apellidos_estudiante']),
    intval($_POST['grado_interes_id']),
    trim($_POST['nombres_contacto']),
    trim($_POST['apellidos_contacto']),
    $telefono,
    $_POST['prioridad'] ?? 'media',
    intval($_POST['puntaje_interes'] ?? 50),
    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

$tipos = 'siissssssssi';

// Agregar WhatsApp si existe
if ($whatsapp) {
    $campos[] = 'whatsapp';
    $valores_array[] = $whatsapp;
    $tipos .= 's';
}

// Agregar email si existe
if ($email) {
    $campos[] = 'email';
    $valores_array[] = $email;
    $tipos .= 's';
}

// Campos opcionales
$opcionales = [
    'responsable_id' => ['tipo' => 'i', 'cast' => 'intval'],
    'fecha_nacimiento_estudiante' => ['tipo' => 's', 'cast' => 'trim'],
    'genero_estudiante' => ['tipo' => 's', 'cast' => 'trim'],
    'colegio_procedencia' => ['tipo' => 's', 'cast' => 'trim'],
    'motivo_cambio' => ['tipo' => 's', 'cast' => 'trim'],
    'observaciones' => ['tipo' => 's', 'cast' => 'trim'],
    'proxima_accion_fecha' => ['tipo' => 's', 'cast' => 'trim'],
    'proxima_accion_descripcion' => ['tipo' => 's', 'cast' => 'trim'],
    'utm_source' => ['tipo' => 's', 'cast' => 'trim'],
    'utm_medium' => ['tipo' => 's', 'cast' => 'trim'],
    'utm_campaign' => ['tipo' => 's', 'cast' => 'trim']
];

foreach ($opcionales as $campo => $config) {
    if (!empty($_POST[$campo])) {
        $campos[] = $campo;
        $valor = $config['cast']($_POST[$campo]);
        $valores_array[] = $valor;
        $tipos .= $config['tipo'];
    }
}

// Construir SQL
$placeholders = implode(', ', array_fill(0, count($campos), '?'));
$sql = "INSERT INTO leads (" . implode(', ', $campos) . ") VALUES ($placeholders)";

// Preparar statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder(false, 'Error en la consulta: ' . $conn->error);
}

// Bind dinámico
$bind_params = [$tipos];
foreach ($valores_array as $key => $value) {
    $bind_params[] = &$valores_array[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bind_params);

// Ejecutar
if (!$stmt->execute()) {
    responder(false, 'Error al guardar: ' . $stmt->error);
}

// OBTENER ID ANTES DE CERRAR
$lead_id = $conn->insert_id;

// AHORA SÍ CERRAR
$stmt->close();
$conn->close();

// RESPONDER CON EL ID GUARDADO
responder(true, 'Lead registrado exitosamente', [
    'lead_id' => $lead_id,
    'codigo_lead' => $codigo
]);
?>