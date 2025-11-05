<?php
session_start();
require_once '../../bd/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit();
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'obtener_respuestas_encuesta':
            obtenerRespuestasEncuesta($conn);
            break;
            
        case 'obtener_detalle_respuesta':
            obtenerDetalleRespuesta($conn);
            break;
            
        case 'eliminar_respuesta':
            eliminarRespuesta($conn);
            break;
            
        case 'eliminar_respuestas_masivo':
            eliminarRespuestasMasivo($conn);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
    exit();
}

function obtenerRespuestasEncuesta($conn) {
    $encuesta_id = intval($_POST['encuesta_id'] ?? 0);
    $pagina = intval($_POST['pagina'] ?? 1);
    $por_pagina = intval($_POST['por_pagina'] ?? 25);
    $filtro_tipo = $_POST['filtro_tipo'] ?? '';
    $filtro_fecha = $_POST['filtro_fecha'] ?? '';
    
    if ($encuesta_id <= 0) {
        throw new Exception('ID de encuesta no válido');
    }
    
    // Obtener información de la encuesta
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, tipo, dirigido_a, preguntas
        FROM encuestas 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result_encuesta = $stmt->get_result();
    
    if ($result_encuesta->num_rows === 0) {
        throw new Exception('Encuesta no encontrada');
    }
    
    $encuesta = $result_encuesta->fetch_assoc();
    $stmt->close();
    
    // Construir query de respuestas con filtros
    $where_conditions = ["r.encuesta_id = ?"];
    $params = [$encuesta_id];
    $types = "i";
    
    // Filtro por tipo de usuario
    if (!empty($filtro_tipo)) {
        switch ($filtro_tipo) {
            case 'padres':
                $where_conditions[] = "r.apoderado_id IS NOT NULL";
                break;
            case 'estudiantes':
                $where_conditions[] = "r.apoderado_id IS NULL AND r.lead_id IS NULL AND r.exalumno_id IS NULL";
                break;
            case 'exalumnos':
                $where_conditions[] = "r.exalumno_id IS NOT NULL";
                break;
            case 'leads':
                $where_conditions[] = "r.lead_id IS NOT NULL";
                break;
        }
    }
    
    // Filtro por fecha
    if (!empty($filtro_fecha)) {
        $where_conditions[] = "DATE(r.fecha_respuesta) = ?";
        $params[] = $filtro_fecha;
        $types .= "s";
    }
    
    $where_sql = implode(" AND ", $where_conditions);
    
    // Contar total de respuestas
    $count_query = "
        SELECT COUNT(*) as total
        FROM respuestas_encuesta r
        WHERE $where_sql
    ";
    
    $stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_respuestas = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Calcular paginación
    $offset = ($pagina - 1) * $por_pagina;
    $total_paginas = ceil($total_respuestas / $por_pagina);
    
    // Obtener respuestas paginadas
    $query = "
        SELECT 
            r.id,
            r.encuesta_id,
            r.respuestas,
            r.puntaje_calculado,
            r.fecha_respuesta,
            r.ip_respuesta,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN 'Padres'
                WHEN r.lead_id IS NOT NULL THEN 'Leads'
                WHEN r.exalumno_id IS NOT NULL THEN 'Ex-alumnos'
                ELSE 'Estudiantes'
            END as tipo_usuario,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
                WHEN r.lead_id IS NOT NULL THEN CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto)
                WHEN r.exalumno_id IS NOT NULL THEN CONCAT(e.nombres, ' ', e.apellidos)
                ELSE 'Anónimo'
            END as nombre,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN a.email
                WHEN r.lead_id IS NOT NULL THEN l.email
                WHEN r.exalumno_id IS NOT NULL THEN e.email
                ELSE NULL
            END as email
        FROM respuestas_encuesta r
        LEFT JOIN apoderados a ON r.apoderado_id = a.id
        LEFT JOIN leads l ON r.lead_id = l.id
        LEFT JOIN exalumnos e ON r.exalumno_id = e.id
        WHERE $where_sql
        ORDER BY r.fecha_respuesta DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    $params[] = $por_pagina;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $respuestas = [];
    while ($row = $result->fetch_assoc()) {
        $respuestas[] = [
            'id' => $row['id'],
            'tipo_usuario' => $row['tipo_usuario'],
            'nombre' => $row['nombre'],
            'email' => $row['email'],
            'fecha_respuesta' => $row['fecha_respuesta'],
            'puntaje_calculado' => $row['puntaje_calculado'],
            'ip_respuesta' => $row['ip_respuesta']
        ];
    }
    $stmt->close();
    
    // Preparar respuesta
    $encuesta['total_respuestas'] = $total_respuestas;
    
    echo json_encode([
        'success' => true,
        'encuesta' => $encuesta,
        'respuestas' => $respuestas,
        'paginacion' => [
            'pagina_actual' => $pagina,
            'total_paginas' => $total_paginas,
            'por_pagina' => $por_pagina,
            'total' => $total_respuestas,
            'desde' => $offset + 1,
            'hasta' => min($offset + $por_pagina, $total_respuestas)
        ]
    ]);
}

function obtenerDetalleRespuesta($conn) {
    $respuesta_id = intval($_POST['respuesta_id'] ?? 0);
    
    if ($respuesta_id <= 0) {
        throw new Exception('ID de respuesta no válido');
    }
    
    // Obtener respuesta con información del usuario
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.encuesta_id,
            r.respuestas,
            r.puntaje_calculado,
            r.fecha_respuesta,
            r.ip_respuesta,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN 'Padres'
                WHEN r.lead_id IS NOT NULL THEN 'Leads'
                WHEN r.exalumno_id IS NOT NULL THEN 'Ex-alumnos'
                ELSE 'Estudiantes'
            END as tipo_usuario,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
                WHEN r.lead_id IS NOT NULL THEN CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto)
                WHEN r.exalumno_id IS NOT NULL THEN CONCAT(e.nombres, ' ', e.apellidos)
                ELSE 'Anónimo'
            END as nombre,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN a.email
                WHEN r.lead_id IS NOT NULL THEN l.email
                WHEN r.exalumno_id IS NOT NULL THEN e.email
                ELSE NULL
            END as email,
            e2.preguntas
        FROM respuestas_encuesta r
        LEFT JOIN apoderados a ON r.apoderado_id = a.id
        LEFT JOIN leads l ON r.lead_id = l.id
        LEFT JOIN exalumnos e ON r.exalumno_id = e.id
        LEFT JOIN encuestas e2 ON r.encuesta_id = e2.id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $respuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Respuesta no encontrada');
    }
    
    $respuesta = $result->fetch_assoc();
    $stmt->close();
    
    // Decodificar respuestas y preguntas
    $respuestas_data = json_decode($respuesta['respuestas'], true);
    $preguntas_data = json_decode($respuesta['preguntas'], true);
    
    // Combinar preguntas con respuestas
    $respuestas_detalle = [];
    foreach ($preguntas_data as $pregunta) {
        $pregunta_id = $pregunta['id'];
        $respuestas_detalle[] = [
            'pregunta' => $pregunta['pregunta'],
            'tipo' => $pregunta['tipo'],
            'respuesta' => $respuestas_data[$pregunta_id] ?? 'Sin respuesta'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'respuesta' => [
            'id' => $respuesta['id'],
            'tipo_usuario' => $respuesta['tipo_usuario'],
            'nombre' => $respuesta['nombre'],
            'email' => $respuesta['email'],
            'fecha_respuesta' => $respuesta['fecha_respuesta'],
            'puntaje_calculado' => $respuesta['puntaje_calculado'],
            'ip_respuesta' => $respuesta['ip_respuesta'],
            'respuestas_detalle' => $respuestas_detalle
        ]
    ]);
}

function eliminarRespuesta($conn) {
    $respuesta_id = intval($_POST['respuesta_id'] ?? 0);
    
    if ($respuesta_id <= 0) {
        throw new Exception('ID de respuesta no válido');
    }
    
    // Verificar que la respuesta existe
    $check = $conn->prepare("SELECT id FROM respuestas_encuesta WHERE id = ?");
    $check->bind_param("i", $respuesta_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Respuesta no encontrada');
    }
    $check->close();
    
    // Eliminar
    $stmt = $conn->prepare("DELETE FROM respuestas_encuesta WHERE id = ?");
    $stmt->bind_param("i", $respuesta_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar la respuesta: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Respuesta eliminada exitosamente'
    ]);
}

function eliminarRespuestasMasivo($conn) {
    $respuestas_ids_json = $_POST['respuestas_ids'] ?? '';
    $respuestas_ids = json_decode($respuestas_ids_json, true);
    
    if (!is_array($respuestas_ids) || empty($respuestas_ids)) {
        throw new Exception('No se proporcionaron IDs válidos');
    }
    
    // Validar que todos sean números
    $respuestas_ids = array_filter($respuestas_ids, function($id) {
        return is_numeric($id) && intval($id) > 0;
    });
    
    if (empty($respuestas_ids)) {
        throw new Exception('No hay IDs válidos para eliminar');
    }
    
    // Crear placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($respuestas_ids), '?'));
    $types = str_repeat('i', count($respuestas_ids));
    
    // Eliminar respuestas
    $stmt = $conn->prepare("DELETE FROM respuestas_encuesta WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$respuestas_ids);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar las respuestas: ' . $stmt->error);
    }
    
    $cantidad_eliminada = $stmt->affected_rows;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => "$cantidad_eliminada respuestas eliminadas exitosamente",
        'cantidad' => $cantidad_eliminada
    ]);
}
?>