<?php
session_start();
header('Content-Type: application/json');

require_once '../../bd/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $estado_id = intval($_POST['estado_id']);
    $tipo_asignacion = $_POST['tipo_asignacion'] ?? 'valor_fijo';
    $configuracion = json_decode($_POST['configuracion'] ?? '{}', true);
    $solo_primera_vez = isset($_POST['solo_primera_vez']);
    $respetar_manual = isset($_POST['respetar_manual']);

    // Obtener leads actuales en este estado
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            AVG(puntaje_interes) as promedio_actual
        FROM leads
        WHERE estado_lead_id = ? AND activo = 1
    ");
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $datos = $result->fetch_assoc();
    $stmt->close();

    $leads_afectados = intval($datos['total']);
    $promedio_actual = floatval($datos['promedio_actual']);

    // Calcular nuevo promedio según tipo de asignación
    $promedio_nuevo = $promedio_actual;
    
    if ($tipo_asignacion === 'valor_fijo') {
        $promedio_nuevo = floatval($configuracion['valor'] ?? 50);
    } elseif ($tipo_asignacion === 'incremento') {
        $valor = floatval($configuracion['valor'] ?? 10);
        if ($configuracion['operacion'] === 'sum') {
            $promedio_nuevo = min($promedio_actual + $valor, 100);
        } else {
            $promedio_nuevo = max($promedio_actual - $valor, 0);
        }
    }

    $incremento = round($promedio_nuevo - $promedio_actual, 1);
    $conversion_esperada = round(($promedio_nuevo / 100) * 35, 1); // Simulación simple

    echo json_encode([
        'success' => true,
        'simulacion' => [
            'leads_afectados' => $leads_afectados,
            'promedio_nuevo' => round($promedio_nuevo, 1),
            'incremento' => $incremento,
            'conversion_esperada' => $conversion_esperada
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>