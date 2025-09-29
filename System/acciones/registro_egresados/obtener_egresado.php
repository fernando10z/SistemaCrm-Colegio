<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Función para enviar respuesta JSON
function enviarRespuesta($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Verificar que sea una petición GET o POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarRespuesta(false, 'Método de petición no permitido');
}

// Obtener ID del egresado
$egresado_id = null;

if (isset($_GET['id'])) {
    $egresado_id = $_GET['id'];
} elseif (isset($_POST['id'])) {
    $egresado_id = $_POST['id'];
} elseif (isset($_GET['egresado_id'])) {
    $egresado_id = $_GET['egresado_id'];
} elseif (isset($_POST['egresado_id'])) {
    $egresado_id = $_POST['egresado_id'];
}

// Validar que se proporcionó un ID
if (empty($egresado_id)) {
    enviarRespuesta(false, 'ID de egresado no proporcionado');
}

// Validar que el ID sea numérico
if (!is_numeric($egresado_id)) {
    enviarRespuesta(false, 'ID de egresado no válido');
}

$egresado_id = intval($egresado_id);

// Validar que el ID sea positivo
if ($egresado_id <= 0) {
    enviarRespuesta(false, 'ID de egresado debe ser un número positivo');
}

try {
    // Consulta principal para obtener datos del egresado
    $sql = "SELECT 
                id,
                codigo_exalumno,
                tipo_documento,
                numero_documento,
                nombres,
                apellidos,
                email,
                telefono,
                whatsapp,
                promocion_egreso,
                fecha_egreso,
                ultimo_grado,
                ocupacion_actual,
                empresa_actual,
                estudios_superiores,
                direccion_actual,
                distrito_actual,
                estado_contacto,
                acepta_comunicaciones,
                observaciones,
                created_at,
                updated_at
            FROM exalumnos 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        enviarRespuesta(false, 'Error en la preparación de la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $egresado_id);
    
    if (!$stmt->execute()) {
        enviarRespuesta(false, 'Error al ejecutar la consulta: ' . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    
    // Verificar si se encontró el egresado
    if ($resultado->num_rows === 0) {
        $stmt->close();
        enviarRespuesta(false, 'Egresado no encontrado');
    }
    
    $egresado = $resultado->fetch_assoc();
    $stmt->close();
    
    // Formatear los datos para el frontend
    $datos_formateados = [
        'id' => $egresado['id'],
        'codigo_exalumno' => $egresado['codigo_exalumno'],
        'tipo_documento' => $egresado['tipo_documento'],
        'numero_documento' => $egresado['numero_documento'],
        'nombres' => $egresado['nombres'],
        'apellidos' => $egresado['apellidos'],
        'nombre_completo' => trim($egresado['nombres'] . ' ' . $egresado['apellidos']),
        'email' => $egresado['email'],
        'telefono' => $egresado['telefono'],
        'whatsapp' => $egresado['whatsapp'],
        'promocion_egreso' => $egresado['promocion_egreso'],
        'fecha_egreso' => $egresado['fecha_egreso'],
        'fecha_egreso_formateada' => $egresado['fecha_egreso'] ? date('d/m/Y', strtotime($egresado['fecha_egreso'])) : null,
        'ultimo_grado' => $egresado['ultimo_grado'],
        'ocupacion_actual' => $egresado['ocupacion_actual'],
        'empresa_actual' => $egresado['empresa_actual'],
        'estudios_superiores' => $egresado['estudios_superiores'],
        'direccion_actual' => $egresado['direccion_actual'],
        'distrito_actual' => $egresado['distrito_actual'],
        'estado_contacto' => $egresado['estado_contacto'],
        'estado_contacto_texto' => ucfirst(str_replace('_', ' ', $egresado['estado_contacto'])),
        'acepta_comunicaciones' => (int)$egresado['acepta_comunicaciones'],
        'acepta_comunicaciones_texto' => $egresado['acepta_comunicaciones'] ? 'Sí' : 'No',
        'observaciones' => $egresado['observaciones'],
        'created_at' => $egresado['created_at'],
        'updated_at' => $egresado['updated_at'],
        'fecha_registro_formateada' => date('d/m/Y H:i', strtotime($egresado['created_at'])),
        'fecha_actualizacion_formateada' => date('d/m/Y H:i', strtotime($egresado['updated_at']))
    ];
    
    // Agregar información adicional calculada
    $datos_formateados['anos_egreso'] = null;
    if ($egresado['fecha_egreso']) {
        $fecha_egreso = new DateTime($egresado['fecha_egreso']);
        $fecha_actual = new DateTime();
        $datos_formateados['anos_egreso'] = $fecha_actual->diff($fecha_egreso)->y;
    }
    
    // Categoría de egreso
    if ($egresado['fecha_egreso']) {
        $anos = $datos_formateados['anos_egreso'];
        if ($anos <= 2) {
            $datos_formateados['categoria_egreso'] = 'Reciente';
        } elseif ($anos <= 5) {
            $datos_formateados['categoria_egreso'] = 'Intermedio';
        } else {
            $datos_formateados['categoria_egreso'] = 'Antiguo';
        }
    } else {
        $datos_formateados['categoria_egreso'] = 'Sin fecha';
    }
    
    // Verificar si tiene datos de contacto completos
    $tiene_email = !empty($egresado['email']);
    $tiene_telefono = !empty($egresado['telefono']);
    $tiene_whatsapp = !empty($egresado['whatsapp']);
    
    $datos_formateados['contacto_completo'] = $tiene_email && ($tiene_telefono || $tiene_whatsapp);
    $datos_formateados['medios_contacto'] = [
        'email' => $tiene_email,
        'telefono' => $tiene_telefono,
        'whatsapp' => $tiene_whatsapp
    ];
    
    // Validar formato de datos de contacto
    $datos_formateados['contacto_valido'] = [
        'email' => $tiene_email ? filter_var($egresado['email'], FILTER_VALIDATE_EMAIL) !== false : null,
        'telefono' => $tiene_telefono ? preg_match('/^(\+51|51)?[0-9]{9}$/', $egresado['telefono']) === 1 : null,
        'whatsapp' => $tiene_whatsapp ? preg_match('/^(\+51|51)?[0-9]{9}$/', $egresado['whatsapp']) === 1 : null
    ];
    
    // Información adicional para el formulario
    $datos_formateados['form_data'] = [
        'documento_tipo_display' => $egresado['tipo_documento'] . ': ' . $egresado['numero_documento'],
        'promocion_display' => $egresado['promocion_egreso'] ?: 'No especificada',
        'grado_display' => $egresado['ultimo_grado'] ?: 'No especificado',
        'situacion_laboral' => $egresado['ocupacion_actual'] ? 
            ($egresado['empresa_actual'] ? 
                $egresado['ocupacion_actual'] . ' en ' . $egresado['empresa_actual'] : 
                $egresado['ocupacion_actual']) : 
            'No especificada',
        'direccion_completa' => $egresado['direccion_actual'] ? 
            ($egresado['distrito_actual'] ? 
                $egresado['direccion_actual'] . ', ' . $egresado['distrito_actual'] : 
                $egresado['direccion_actual']) : 
            'No especificada'
    ];
    
    // Información de estadísticas (opcional para mostrar en el modal)
    $datos_formateados['estadisticas'] = [
        'tiene_ocupacion' => !empty($egresado['ocupacion_actual']),
        'tiene_estudios_superiores' => !empty($egresado['estudios_superiores']),
        'tiene_direccion' => !empty($egresado['direccion_actual']),
        'perfil_completo' => !empty($egresado['email']) && 
                            !empty($egresado['telefono']) && 
                            !empty($egresado['ocupacion_actual']) && 
                            !empty($egresado['direccion_actual'])
    ];
    
    // Registrar consulta en log si existe la tabla
    try {
        $resultado_log = $conn->query("SHOW TABLES LIKE 'logs_acceso'");
        if ($resultado_log && $resultado_log->num_rows > 0) {
            $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Sistema CRM';
            $accion = 'Consulta Egresado';
            $detalles = "Datos obtenidos del egresado: {$egresado['nombres']} {$egresado['apellidos']} (ID: {$egresado['id']})";
            
            $stmt_log = $conn->prepare("INSERT INTO logs_acceso (usuario_id, ip_address, user_agent, accion, resultado, detalles) VALUES (?, ?, ?, ?, 'exitoso', ?)");
            $stmt_log->bind_param("issss", $usuario_id, $ip_address, $user_agent, $accion, $detalles);
            $stmt_log->execute();
            $stmt_log->close();
        }
    } catch (Exception $e) {
        // Error en log no debe interrumpir la operación principal
        error_log("Error al registrar log: " . $e->getMessage());
    }
    
    // Enviar respuesta exitosa
    enviarRespuesta(true, 'Datos del egresado obtenidos correctamente', $datos_formateados);
    
} catch (Exception $e) {
    // Registrar error en log
    error_log("Error al obtener egresado ID $egresado_id: " . $e->getMessage());
    
    enviarRespuesta(false, 'Error interno del servidor al obtener los datos del egresado');
}

// Cerrar conexión
$conn->close();
?>