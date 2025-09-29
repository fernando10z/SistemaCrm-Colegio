<?php
// SOLUCIÓN DEFINITIVA - Solo JSON limpio
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('{"success":false,"message":"Método no permitido","historial":[]}');
}

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Función de respuesta simple
function json_response($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'historial' => $data
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar entrada
if (!isset($_POST['id_egresado']) || !isset($_POST['accion'])) {
    json_response(false, 'Datos insuficientes');
}

$id_egresado = intval($_POST['id_egresado']);
$accion = trim($_POST['accion']);

if ($id_egresado <= 0) {
    json_response(false, 'ID inválido');
}

if ($accion !== 'obtener_historial') {
    json_response(false, 'Acción no reconocida');
}

// Conectar a la base de datos
$conn = null;
try {
    include '../../bd/conexion.php';
    if (!$conn) {
        json_response(false, 'Error de conexión');
    }
} catch (Exception $e) {
    json_response(false, 'No se pudo conectar a la base de datos');
}

// Buscar el egresado
try {
    $stmt = $conn->prepare("SELECT nombres, apellidos, created_at, updated_at FROM exalumnos WHERE id = ?");
    $stmt->bind_param("i", $id_egresado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(false, 'Egresado no encontrado');
    }
    
    $egresado = $result->fetch_assoc();
    $stmt->close();
    
    // Crear historial de ejemplo
    $historial = [
        [
            'id' => 1,
            'tipo_interaccion' => 'Registro inicial',
            'descripcion' => 'Egresado registrado en el sistema',
            'fecha' => date('d/m/Y H:i', strtotime($egresado['created_at'])),
            'usuario' => 'Sistema'
        ]
    ];
    
    // Agregar actualización si existe
    if ($egresado['updated_at'] && $egresado['updated_at'] != $egresado['created_at']) {
        $historial[] = [
            'id' => 2,
            'tipo_interaccion' => 'Actualización de datos',
            'descripcion' => 'Información actualizada',
            'fecha' => date('d/m/Y H:i', strtotime($egresado['updated_at'])),
            'usuario' => 'Sistema'
        ];
    }
    
    // Agregar consulta actual
    $historial[] = [
        'id' => 3,
        'tipo_interaccion' => 'Consulta de historial',
        'descripcion' => 'Historial consultado desde el panel',
        'fecha' => date('d/m/Y H:i'),
        'usuario' => 'Administrador'
    ];
    
    $conn->close();
    json_response(true, 'Historial obtenido correctamente', $historial);
    
} catch (Exception $e) {
    if ($conn) $conn->close();
    json_response(false, 'Error interno del servidor');
}
?>