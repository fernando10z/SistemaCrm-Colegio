<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión
include '../bd/conexion.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            crearPlantilla($conn);
            break;
        case 'editar':
            editarPlantilla($conn);
            break;
        case 'obtener':
            obtenerPlantilla($conn);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// FUNCIÓN: Crear plantilla
function crearPlantilla($conn) {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $asunto = trim($_POST['asunto'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = $_POST['categoria'] ?? 'general';
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    $variables_json = $_POST['variables_disponibles'] ?? '[]';
    
    // Validaciones
    if (empty($nombre) || empty($tipo) || empty($contenido)) {
        throw new Exception('Nombre, tipo y contenido son obligatorios');
    }
    
    if (!in_array($tipo, ['email', 'whatsapp', 'sms'])) {
        throw new Exception('Tipo de mensaje no válido');
    }
    
    if ($tipo === 'email' && empty($asunto)) {
        throw new Exception('El asunto es obligatorio para emails');
    }
    
    // Preparar query
    $sql = "INSERT INTO plantillas_mensajes 
            (nombre, tipo, asunto, contenido, variables_disponibles, categoria, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $nombre, $tipo, $asunto, $contenido, $variables_json, $categoria, $activo);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Plantilla creada exitosamente',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Error al crear la plantilla: ' . $stmt->error);
    }
    
    $stmt->close();
}

// FUNCIÓN: Editar plantilla
function editarPlantilla($conn) {
    $id = (int)($_POST['plantilla_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $asunto = trim($_POST['asunto'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = $_POST['categoria'] ?? 'general';
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    $variables_json = $_POST['variables_disponibles'] ?? '[]';
    
    // Validaciones
    if ($id <= 0) {
        throw new Exception('ID de plantilla no válido');
    }
    
    if (empty($nombre) || empty($tipo) || empty($contenido)) {
        throw new Exception('Nombre, tipo y contenido son obligatorios');
    }
    
    if (!in_array($tipo, ['email', 'whatsapp', 'sms'])) {
        throw new Exception('Tipo de mensaje no válido');
    }
    
    if ($tipo === 'email' && empty($asunto)) {
        throw new Exception('El asunto es obligatorio para emails');
    }
    
    // Preparar query
    $sql = "UPDATE plantillas_mensajes 
            SET nombre = ?, tipo = ?, asunto = ?, contenido = ?, 
                variables_disponibles = ?, categoria = ?, activo = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $nombre, $tipo, $asunto, $contenido, $variables_json, $categoria, $activo, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Plantilla actualizada exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la plantilla: ' . $stmt->error);
    }
    
    $stmt->close();
}

// FUNCIÓN: Obtener plantilla
function obtenerPlantilla($conn) {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID de plantilla no válido');
    }
    
    $sql = "SELECT * FROM plantillas_mensajes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        throw new Exception('Plantilla no encontrada');
    }
    
    $stmt->close();
}
?>