<?php
require_once '../../config/conexion.php';
session_start();

header('Content-Type: application/json');


$tipo = $_POST['tipo'] ?? '';
$usuario_origen = intval($_POST['usuario_origen'] ?? 0);
$usuario_destino = intval($_POST['usuario_destino'] ?? 0);

try {
    // Obtener nombres
    $query_usuarios = "SELECT id, nombre FROM usuarios WHERE id IN (?, ?)";
    $stmt = $conn->prepare($query_usuarios);
    $stmt->bind_param("ii", $usuario_origen, $usuario_destino);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[$row['id']] = $row['nombre'];
    }
    
    $cantidad = 0;
    
    switch ($tipo) {
        case 'leads_especificos':
            $leads_ids = $_POST['leads_ids'] ?? [];
            $cantidad = is_array($leads_ids) ? count($leads_ids) : 0;
            break;
            
        case 'por_criterio':
            $where = ["l.responsable_id = ?", "l.activo = 1"];
            $params = [$usuario_origen];
            $types = 'i';
            
            if (!empty($_POST['criterio_estado'])) {
                $where[] = "l.estado_lead_id = ?";
                $params[] = intval($_POST['criterio_estado']);
                $types .= 'i';
            }
            
            if (!empty($_POST['criterio_prioridad'])) {
                $where[] = "l.prioridad = ?";
                $params[] = $_POST['criterio_prioridad'];
                $types .= 's';
            }
            
            $query = "SELECT COUNT(*) as total FROM leads l WHERE " . implode(' AND ', $where);
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $cantidad = $row['total'];
            break;
            
        case 'transferir_todos':
            $query = "SELECT COUNT(*) as total FROM leads WHERE responsable_id = ? AND activo = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_origen);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $cantidad = $row['total'];
            break;
    }
    
    echo json_encode([
        'success' => true,
        'cantidad' => $cantidad,
        'usuario_origen' => $usuarios[$usuario_origen] ?? 'Desconocido',
        'usuario_destino' => $usuarios[$usuario_destino] ?? 'Desconocido'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>