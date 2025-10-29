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
    $valor_nuevo = 50;

    // Calcular valor según tipo
    if ($tipo_asignacion === 'valor_fijo') {
        $valor_nuevo = intval($_POST['valor_fijo'] ?? 50);
    }

    // Actualizar leads en este estado
    $stmt = $conn->prepare("
        UPDATE leads 
        SET puntaje_interes = ? 
        WHERE estado_lead_id = ? AND activo = 1
    ");
    $stmt->bind_param("ii", $valor_nuevo, $estado_id);
    $stmt->execute();
    $leads_actualizados = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Configuración aplicada exitosamente',
        'leads_actualizados' => $leads_actualizados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>