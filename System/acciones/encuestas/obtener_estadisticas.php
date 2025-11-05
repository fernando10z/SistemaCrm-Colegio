<?php
require_once '../../bd/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$encuesta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($encuesta_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID no válido']);
    exit();
}

try {
    // Obtener datos de la encuesta
    $stmt = $conn->prepare("
        SELECT 
            e.tipo,
            e.dirigido_a,
            COUNT(r.id) as total_respuestas
        FROM encuestas e
        LEFT JOIN respuestas_encuesta r ON e.id = r.encuesta_id
        WHERE e.id = ?
        GROUP BY e.id, e.tipo, e.dirigido_a
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Encuesta no encontrada');
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
    
    // Formatear tipo y dirigido_a
    $tipos = [
        'satisfaccion' => 'Satisfacción',
        'feedback' => 'Feedback',
        'evento' => 'Evento',
        'general' => 'General'
    ];
    
    $dirigidos = [
        'padres' => 'Padres',
        'estudiantes' => 'Estudiantes',
        'exalumnos' => 'Ex-alumnos',
        'leads' => 'Leads'
    ];
    
    echo json_encode([
        'success' => true,
        'tipo' => $tipos[$data['tipo']] ?? ucfirst($data['tipo']),
        'dirigido_a' => $dirigidos[$data['dirigido_a']] ?? ucfirst($data['dirigido_a']),
        'total_respuestas' => $data['total_respuestas']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>