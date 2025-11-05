<?php
session_start();
require_once '../../bd/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_encuesta'] = 'Método no permitido';
    $_SESSION['tipo_mensaje_encuesta'] = 'error';
    header('Location: ../../encuestas.php');
    exit();
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear_encuesta':
            crearEncuesta($conn);
            break;
            
        case 'actualizar_encuesta':
            actualizarEncuesta($conn);
            break;
            
        case 'eliminar_encuesta':
            eliminarEncuesta($conn);
            break;
            
        case 'cambiar_estado':
            cambiarEstado($conn);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    $_SESSION['mensaje_encuesta'] = $e->getMessage();
    $_SESSION['tipo_mensaje_encuesta'] = 'error';
    header('Location: ../../encuestas.php');
    exit();
}

function crearEncuesta($conn) {
    // Validar campos requeridos
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $dirigido_a = trim($_POST['dirigido_a'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    
    if (empty($titulo) || empty($tipo) || empty($dirigido_a) || empty($fecha_inicio)) {
        throw new Exception('Por favor complete todos los campos obligatorios');
    }
    
    // Validar tipos según base de datos
    $tipos_validos = ['satisfaccion', 'feedback', 'evento', 'general'];
    $dirigido_validos = ['padres', 'estudiantes', 'exalumnos', 'leads'];
    
    if (!in_array($tipo, $tipos_validos)) {
        throw new Exception('Tipo de encuesta no válido');
    }
    
    if (!in_array($dirigido_a, $dirigido_validos)) {
        throw new Exception('Destinatario no válido');
    }
    
    // Obtener datos opcionales
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? null);
    
    // Validar fechas
    if (!empty($fecha_fin) && strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        throw new Exception('La fecha de fin no puede ser anterior a la fecha de inicio');
    }
    
    // Procesar preguntas
    $preguntas = $_POST['preguntas'] ?? [];
    
    if (empty($preguntas)) {
        throw new Exception('Debe agregar al menos una pregunta');
    }
    
    // Formatear preguntas para JSON
    $preguntas_json = [];
    $contador_pregunta = 1;
    
    foreach ($preguntas as $pregunta) {
        if (empty(trim($pregunta['pregunta'] ?? ''))) {
            continue;
        }
        
        $pregunta_data = [
            'id' => $contador_pregunta,
            'pregunta' => trim($pregunta['pregunta']),
            'tipo' => trim($pregunta['tipo'] ?? 'text'),
            'requerida' => isset($pregunta['requerida']) ? true : false
        ];
        
        // Agregar opciones si el tipo lo requiere
        if (in_array($pregunta_data['tipo'], ['select', 'radio', 'checkbox', 'escala'])) {
            $opciones = array_filter(
                array_map('trim', $pregunta['opciones'] ?? []),
                function($v) { return !empty($v); }
            );
            
            if (empty($opciones) && $pregunta_data['tipo'] !== 'escala') {
                throw new Exception("La pregunta '{$pregunta_data['pregunta']}' requiere opciones de respuesta");
            }
            
            $pregunta_data['opciones'] = array_values($opciones);
        }
        
        $preguntas_json[] = $pregunta_data;
        $contador_pregunta++;
    }
    
    if (empty($preguntas_json)) {
        throw new Exception('No se agregaron preguntas válidas');
    }
    
    // Convertir preguntas a JSON
    $preguntas_json_str = json_encode($preguntas_json, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al procesar las preguntas: ' . json_last_error_msg());
    }
    
    // Insertar en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO encuestas (
            titulo, 
            descripcion, 
            tipo, 
            dirigido_a, 
            preguntas, 
            fecha_inicio, 
            fecha_fin,
            activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->bind_param(
        "sssssss",
        $titulo,
        $descripcion,
        $tipo,
        $dirigido_a,
        $preguntas_json_str,
        $fecha_inicio,
        $fecha_fin
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al crear la encuesta: ' . $stmt->error);
    }
    
    $encuesta_id = $conn->insert_id;
    $stmt->close();
    
    $_SESSION['mensaje_encuesta'] = "Encuesta '{$titulo}' creada exitosamente con " . count($preguntas_json) . " pregunta(s)";
    $_SESSION['tipo_mensaje_encuesta'] = 'success';
    
    header('Location: ../../encuestas.php');
    exit();
}

function actualizarEncuesta($conn) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID de encuesta no válido');
    }
    
    // Verificar que la encuesta existe
    $check = $conn->prepare("SELECT id FROM encuestas WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('La encuesta no existe');
    }
    $check->close();
    
    // Validar campos requeridos
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $dirigido_a = trim($_POST['dirigido_a'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    
    if (empty($titulo) || empty($tipo) || empty($dirigido_a) || empty($fecha_inicio)) {
        throw new Exception('Por favor complete todos los campos obligatorios');
    }
    
    // Validar tipos
    $tipos_validos = ['satisfaccion', 'feedback', 'evento', 'general'];
    $dirigido_validos = ['padres', 'estudiantes', 'exalumnos', 'leads'];
    
    if (!in_array($tipo, $tipos_validos)) {
        throw new Exception('Tipo de encuesta no válido');
    }
    
    if (!in_array($dirigido_a, $dirigido_validos)) {
        throw new Exception('Destinatario no válido');
    }
    
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? null);
    
    // Procesar preguntas igual que en crear
    $preguntas = $_POST['preguntas'] ?? [];
    
    if (empty($preguntas)) {
        throw new Exception('Debe agregar al menos una pregunta');
    }
    
    $preguntas_json = [];
    $contador_pregunta = 1;
    
    foreach ($preguntas as $pregunta) {
        if (empty(trim($pregunta['pregunta'] ?? ''))) {
            continue;
        }
        
        $pregunta_data = [
            'id' => $contador_pregunta,
            'pregunta' => trim($pregunta['pregunta']),
            'tipo' => trim($pregunta['tipo'] ?? 'text'),
            'requerida' => isset($pregunta['requerida']) ? true : false
        ];
        
        if (in_array($pregunta_data['tipo'], ['select', 'radio', 'checkbox', 'escala'])) {
            $opciones = array_filter(
                array_map('trim', $pregunta['opciones'] ?? []),
                function($v) { return !empty($v); }
            );
            
            if (empty($opciones) && $pregunta_data['tipo'] !== 'escala') {
                throw new Exception("La pregunta '{$pregunta_data['pregunta']}' requiere opciones de respuesta");
            }
            
            $pregunta_data['opciones'] = array_values($opciones);
        }
        
        $preguntas_json[] = $pregunta_data;
        $contador_pregunta++;
    }
    
    $preguntas_json_str = json_encode($preguntas_json, JSON_UNESCAPED_UNICODE);
    
    // Actualizar
    $stmt = $conn->prepare("
        UPDATE encuestas SET
            titulo = ?,
            descripcion = ?,
            tipo = ?,
            dirigido_a = ?,
            preguntas = ?,
            fecha_inicio = ?,
            fecha_fin = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "sssssssi",
        $titulo,
        $descripcion,
        $tipo,
        $dirigido_a,
        $preguntas_json_str,
        $fecha_inicio,
        $fecha_fin,
        $id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar la encuesta: ' . $stmt->error);
    }
    
    $stmt->close();
    
    $_SESSION['mensaje_encuesta'] = "Encuesta actualizada exitosamente";
    $_SESSION['tipo_mensaje_encuesta'] = 'success';
    
    header('Location: ../../encuestas.php');
    exit();
}

function eliminarEncuesta($conn) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID de encuesta no válido');
    }
    
    // Verificar si tiene respuestas
    $check = $conn->prepare("SELECT COUNT(*) as total FROM respuestas_encuesta WHERE encuesta_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        throw new Exception("No se puede eliminar. La encuesta tiene {$result['total']} respuesta(s) asociada(s)");
    }
    $check->close();
    
    // Eliminar
    $stmt = $conn->prepare("DELETE FROM encuestas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar la encuesta: ' . $stmt->error);
    }
    
    $stmt->close();
    
    $_SESSION['mensaje_encuesta'] = 'Encuesta eliminada exitosamente';
    $_SESSION['tipo_mensaje_encuesta'] = 'success';
    
    header('Location: ../../encuestas.php');
    exit();
}

function cambiarEstado($conn) {
    $id = intval($_POST['id'] ?? 0);
    $estado = intval($_POST['estado'] ?? 1);
    
    if ($id <= 0) {
        throw new Exception('ID de encuesta no válido');
    }
    
    $stmt = $conn->prepare("UPDATE encuestas SET activo = ? WHERE id = ?");
    $stmt->bind_param("ii", $estado, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al cambiar el estado: ' . $stmt->error);
    }
    
    $stmt->close();
    
    $mensaje = $estado ? 'activada' : 'desactivada';
    $_SESSION['mensaje_encuesta'] = "Encuesta {$mensaje} exitosamente";
    $_SESSION['tipo_mensaje_encuesta'] = 'success';
    
    header('Location: ../../encuestas.php');
    exit();
}
?>