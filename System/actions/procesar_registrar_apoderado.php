<?php
// Iniciar sesión y validar acceso
session_start();

// Validar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Incluir conexión a la base de datos
include '../bd/conexion.php';

// Función helper para sanitizar strings
function sanitizeString($value) {
    return trim(htmlspecialchars(strip_tags($value ?? ''), ENT_QUOTES, 'UTF-8'));
}

// Obtener y validar campos obligatorios
$familia_id = filter_input(INPUT_POST, 'familia_id', FILTER_VALIDATE_INT);
$tipo_apoderado = sanitizeString($_POST['tipo_apoderado'] ?? '');
$tipo_documento = sanitizeString($_POST['tipo_documento'] ?? '');
$numero_documento = sanitizeString($_POST['numero_documento'] ?? '');
$nombres = sanitizeString($_POST['nombres'] ?? '');
$apellidos = sanitizeString($_POST['apellidos'] ?? '');

// Validar que campos obligatorios no estén vacíos
if (!$familia_id || empty($tipo_apoderado) || empty($tipo_documento) || empty($numero_documento) || empty($nombres) || empty($apellidos)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
    exit;
}

// Validar que tipo_apoderado sea válido
$tipos_validos = ['titular', 'suplente', 'economico'];
if (!in_array($tipo_apoderado, $tipos_validos)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de apoderado no válido']);
    exit;
}

// Validar que tipo_documento sea válido
$tipos_documento_validos = ['DNI', 'CE', 'pasaporte'];
if (!in_array($tipo_documento, $tipos_documento_validos)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de documento no válido']);
    exit;
}

// Validar longitud de DNI
if ($tipo_documento === 'DNI' && strlen($numero_documento) !== 8) {
    echo json_encode(['success' => false, 'message' => 'El DNI debe tener exactamente 8 dígitos']);
    exit;
}

// Validar que solo contenga números el DNI
if ($tipo_documento === 'DNI' && !ctype_digit($numero_documento)) {
    echo json_encode(['success' => false, 'message' => 'El DNI solo debe contener números']);
    exit;
}

// Validar que la familia exista y esté activa
$stmt_familia = $conn->prepare("SELECT id FROM familias WHERE id = ? AND activo = 1");
$stmt_familia->bind_param("i", $familia_id);
$stmt_familia->execute();
$result_familia = $stmt_familia->get_result();

if ($result_familia->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'La familia seleccionada no existe o está inactiva']);
    $stmt_familia->close();
    exit;
}
$stmt_familia->close();

// Verificar si el documento ya existe
$stmt_check = $conn->prepare("SELECT id, nombres, apellidos FROM apoderados WHERE numero_documento = ? AND activo = 1");
$stmt_check->bind_param("s", $numero_documento);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $apoderado_existente = $result_check->fetch_assoc();
    echo json_encode([
        'success' => false, 
        'message' => 'Ya existe un apoderado activo con este documento: ' . $apoderado_existente['nombres'] . ' ' . $apoderado_existente['apellidos']
    ]);
    $stmt_check->close();
    exit;
}
$stmt_check->close();

// Obtener y validar campos opcionales
$fecha_nacimiento = sanitizeString($_POST['fecha_nacimiento'] ?? '');
if (!empty($fecha_nacimiento)) {
    // Validar formato de fecha
    $fecha_valida = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    if (!$fecha_valida || $fecha_valida->format('Y-m-d') !== $fecha_nacimiento) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha de nacimiento no válido']);
        exit;
    }
    // Validar que la fecha no sea futura
    if ($fecha_valida > new DateTime()) {
        echo json_encode(['success' => false, 'message' => 'La fecha de nacimiento no puede ser futura']);
        exit;
    }
} else {
    $fecha_nacimiento = null;
}

$genero = sanitizeString($_POST['genero'] ?? '');
if (!empty($genero)) {
    $generos_validos = ['M', 'F', 'otro'];
    if (!in_array($genero, $generos_validos)) {
        $genero = null;
    }
} else {
    $genero = null;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El formato del email no es válido']);
    exit;
}
$email = !empty($email) ? $email : null;

$telefono_principal = sanitizeString($_POST['telefono_principal'] ?? '');
$telefono_principal = !empty($telefono_principal) ? $telefono_principal : null;

$telefono_secundario = sanitizeString($_POST['telefono_secundario'] ?? '');
$telefono_secundario = !empty($telefono_secundario) ? $telefono_secundario : null;

$whatsapp = sanitizeString($_POST['whatsapp'] ?? '');
$whatsapp = !empty($whatsapp) ? $whatsapp : null;

$ocupacion = sanitizeString($_POST['ocupacion'] ?? '');
$ocupacion = !empty($ocupacion) ? $ocupacion : null;

$empresa = sanitizeString($_POST['empresa'] ?? '');
$empresa = !empty($empresa) ? $empresa : null;

$nivel_educativo = sanitizeString($_POST['nivel_educativo'] ?? '');
$nivel_educativo = !empty($nivel_educativo) ? $nivel_educativo : null;

$estado_civil = sanitizeString($_POST['estado_civil'] ?? '');
if (!empty($estado_civil)) {
    $estados_civiles_validos = ['soltero', 'casado', 'divorciado', 'viudo', 'conviviente'];
    if (!in_array($estado_civil, $estados_civiles_validos)) {
        $estado_civil = null;
    }
} else {
    $estado_civil = null;
}

$nivel_compromiso = sanitizeString($_POST['nivel_compromiso'] ?? '');
$niveles_compromiso_validos = ['alto', 'medio', 'bajo'];
if (empty($nivel_compromiso) || !in_array($nivel_compromiso, $niveles_compromiso_validos)) {
    $nivel_compromiso = 'medio';
}

$nivel_participacion = sanitizeString($_POST['nivel_participacion'] ?? '');
$niveles_participacion_validos = ['muy_activo', 'activo', 'poco_activo', 'inactivo'];
if (empty($nivel_participacion) || !in_array($nivel_participacion, $niveles_participacion_validos)) {
    $nivel_participacion = 'activo';
}

$preferencia_contacto = sanitizeString($_POST['preferencia_contacto'] ?? '');
$preferencias_validas = ['email', 'whatsapp', 'llamada', 'sms'];
if (empty($preferencia_contacto) || !in_array($preferencia_contacto, $preferencias_validas)) {
    $preferencia_contacto = 'whatsapp';
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Preparar consulta de inserción
    $sql = "INSERT INTO apoderados (
        familia_id, tipo_apoderado, tipo_documento, numero_documento, nombres, apellidos,
        fecha_nacimiento, genero, email, telefono_principal, telefono_secundario, whatsapp,
        ocupacion, empresa, nivel_educativo, estado_civil, nivel_compromiso, 
        nivel_participacion, preferencia_contacto, activo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param(
        "issssssssssssssssss",
        $familia_id, 
        $tipo_apoderado, 
        $tipo_documento, 
        $numero_documento, 
        $nombres, 
        $apellidos,
        $fecha_nacimiento, 
        $genero, 
        $email, 
        $telefono_principal, 
        $telefono_secundario, 
        $whatsapp,
        $ocupacion, 
        $empresa, 
        $nivel_educativo, 
        $estado_civil, 
        $nivel_compromiso,
        $nivel_participacion, 
        $preferencia_contacto
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $apoderado_id = $stmt->insert_id;
    $stmt->close();
    
    // Confirmar transacción
    $conn->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Apoderado registrado exitosamente',
        'id' => $apoderado_id,
        'nombre_completo' => $nombres . ' ' . $apellidos
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al registrar el apoderado: ' . $e->getMessage()
    ]);
}

// Cerrar conexión
$conn->close();
?>