<?php
require_once '../../config/conexion.php';
session_start();

header('Content-Type: application/json');


$tipo = $_POST['tipo_reasignacion'] ?? '';
$usuario_origen_id = intval($_POST['usuario_origen_id'] ?? 0);
$usuario_destino_id = intval($_POST['usuario_destino_id'] ?? 0);
$observaciones = $_POST['observaciones_reasignacion'] ?? '';

try {
    $conn->begin_transaction();
    
    $leads_ids = [];
    
    switch ($tipo) {
        case 'leads_especificos':
            $leads_ids = $_POST['leads_para_reasignar'] ?? [];
            break;
            
        case 'por_criterio':
            $where = ["responsable_id = ?", "activo = 1"];
            $params = [$usuario_origen_id];
            $types = 'i';
            
            if (!empty($_POST['criterio_estado'])) {
                $where[] = "estado_lead_id = ?";
                $params[] = intval($_POST['criterio_estado']);
                $types .= 'i';
            }
            
            if (!empty($_POST['criterio_prioridad'])) {
                $where[] = "prioridad = ?";
                $params[] = $_POST['criterio_prioridad'];
                $types .= 's';
            }
            
            $query = "SELECT id FROM leads WHERE " . implode(' AND ', $where);
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $leads_ids[] = $row['id'];
            }
            break;
            
        case 'transferir_todos':
            $query = "SELECT id FROM leads WHERE responsable_id = ? AND activo = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_origen_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $leads_ids[] = $row['id'];
            }
            break;
    }
    
    $leads_reasignados = 0;
    
    if (!empty($leads_ids)) {
        $placeholders = implode(',', array_fill(0, count($leads_ids), '?'));
        $query = "UPDATE leads SET responsable_id = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        
        $stmt = $conn->prepare($query);
        $types = 'i' . str_repeat('i', count($leads_ids));
        $params = array_merge([$usuario_destino_id], $leads_ids);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $leads_reasignados = $stmt->affected_rows;
        
        // Registrar en historial
        foreach ($leads_ids as $lead_id) {
            $query_hist = "INSERT INTO historial_estados_lead 
                          (lead_id, estado_anterior_id, estado_nuevo_id, usuario_id, observaciones, fecha_cambio) 
                          SELECT ?, estado_lead_id, estado_lead_id, ?, ?, NOW()
                          FROM leads WHERE id = ?";
            $stmt_hist = $conn->prepare($query_hist);
            $obs = "Reasignado de usuario $usuario_origen_id a $usuario_destino_id. " . $observaciones;
            $stmt_hist->bind_param("iisi", $lead_id, $_SESSION['usuario_id'], $obs, $lead_id);
            $stmt_hist->execute();
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reasignación completada',
        'cantidad_reasignados' => $leads_reasignados
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>