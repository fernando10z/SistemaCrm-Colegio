<?php
session_start();
header('Content-Type: application/json');

require_once '../../bd/conexion.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['estado_id'])) {
    echo json_encode(['success' => false, 'message' => 'Estado no especificado']);
    exit;
}

$estado_id = intval($_POST['estado_id']);

try {
    // Obtener datos del estado
    $stmt = $conn->prepare("
        SELECT 
            el.id,
            el.nombre,
            el.color,
            COUNT(l.id) as total_leads,
            COALESCE(AVG(l.puntaje_interes), 0) as promedio_interes,
            COALESCE(MIN(l.puntaje_interes), 0) as minimo,
            COALESCE(MAX(l.puntaje_interes), 100) as maximo
        FROM estados_lead el
        LEFT JOIN leads l ON l.estado_lead_id = el.id AND l.activo = 1
        WHERE el.id = ?
        GROUP BY el.id
    ");
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $datos = $result->fetch_assoc();
    $stmt->close();

    // Obtener distribución de puntajes
    $stmt_dist = $conn->prepare("
        SELECT 
            CASE 
                WHEN puntaje_interes = 0 THEN '0'
                WHEN puntaje_interes BETWEEN 1 AND 20 THEN '1-20'
                WHEN puntaje_interes BETWEEN 21 AND 40 THEN '21-40'
                WHEN puntaje_interes BETWEEN 41 AND 60 THEN '41-60'
                WHEN puntaje_interes BETWEEN 61 AND 80 THEN '61-80'
                ELSE '81-100'
            END as rango,
            COUNT(*) as cantidad
        FROM leads
        WHERE estado_lead_id = ? AND activo = 1
        GROUP BY rango
        ORDER BY rango
    ");
    $stmt_dist->bind_param("i", $estado_id);
    $stmt_dist->execute();
    $result_dist = $stmt_dist->get_result();
    $distribucion = [];
    while ($row = $result_dist->fetch_assoc()) {
        $distribucion[] = $row;
    }
    $stmt_dist->close();

    // Generar recomendaciones inteligentes
    $recomendaciones = [];
    $promedio = floatval($datos['promedio_interes']);
    
    if ($promedio < 30) {
        $recomendaciones[] = [
            'prioridad' => 'baja',
            'icono' => 'alert-circle',
            'titulo' => 'Puntaje promedio bajo',
            'detalle' => 'Se recomienda incrementar el valor base para este estado'
        ];
    } elseif ($promedio > 80) {
        $recomendaciones[] = [
            'prioridad' => 'alta',
            'icono' => 'star',
            'titulo' => 'Excelente nivel de interés',
            'detalle' => 'Los leads en este estado muestran alto compromiso'
        ];
    } else {
        $recomendaciones[] = [
            'prioridad' => 'media',
            'icono' => 'chart-line',
            'titulo' => 'Nivel de interés moderado',
            'detalle' => 'Considera ajustar según el tipo de leads que llegan a este estado'
        ];
    }

    // Configuración actual (simulada - crear tabla si es necesario)
    $configuracion = [
        'asignacion_automatica' => false,
        'tipo_asignacion' => 'valor_fijo',
        'valor_fijo' => 50,
        'solo_primera_vez' => false,
        'respetar_manual' => true,
        'notificar_cambio' => false
    ];

    // Historial (simulado)
    $historial = [];

    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'distribucion' => $distribucion,
        'recomendaciones' => $recomendaciones,
        'configuracion' => $configuracion,
        'historial' => $historial
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>