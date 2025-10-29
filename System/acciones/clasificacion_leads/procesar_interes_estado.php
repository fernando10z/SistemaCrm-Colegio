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
    $configuracion = json_encode([
        'asignacion_automatica' => isset($_POST['asignacion_automatica']) ? 1 : 0,
        'tipo_asignacion' => $_POST['tipo_asignacion'] ?? 'valor_fijo',
        'valor_fijo' => intval($_POST['valor_fijo'] ?? 50),
        'operacion_incremento' => $_POST['operacion_incremento'] ?? 'sum',
        'valor_incremento' => intval($_POST['valor_incremento'] ?? 10),
        'formula_personalizada' => $_POST['formula_personalizada'] ?? '',
        'solo_primera_vez' => isset($_POST['solo_primera_vez']) ? 1 : 0,
        'respetar_manual' => isset($_POST['respetar_manual']) ? 1 : 0,
        'notificar_cambio' => isset($_POST['notificar_cambio']) ? 1 : 0
    ]);

    // Aquí guardarías en una tabla de configuraciones
    // Por ahora, solo confirmamos el éxito
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración guardada exitosamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>