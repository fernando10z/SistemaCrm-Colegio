<?php
session_start();
require_once '../../bd/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

if (!isset($_POST['accion']) || $_POST['accion'] !== 'asignar_lead') {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

if (empty($_POST['usuario_id']) || empty($_POST['tipo_asignacion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$usuario_id = filter_var($_POST['usuario_id'], FILTER_VALIDATE_INT);
$tipo_asignacion = trim($_POST['tipo_asignacion']);
$prioridad_filtro = $_POST['prioridad_filtro'] ?? '';
$observaciones = trim($_POST['observaciones'] ?? '');
$notificar = isset($_POST['notificar_responsable']);
$usuario_asigna = $_SESSION['usuario_id'];

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuario ID inválido']);
    exit;
}

$leads_a_asignar = [];

try {
    switch ($tipo_asignacion) {
        case 'individual':
            if (empty($_POST['lead_id'])) {
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar un lead']);
                exit;
            }
            $lead_id = filter_var($_POST['lead_id'], FILTER_VALIDATE_INT);
            if ($lead_id) {
                $leads_a_asignar[] = $lead_id;
            }
            break;
            
        case 'multiple':
            if (empty($_POST['leads_ids']) || !is_array($_POST['leads_ids'])) {
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos un lead']);
                exit;
            }
            $cantidad_maxima = filter_var($_POST['cantidad_maxima'] ?? 5, FILTER_VALIDATE_INT);
            $leads_seleccionados = array_map('intval', $_POST['leads_ids']);
            $leads_seleccionados = array_filter($leads_seleccionados);
            $leads_a_asignar = array_slice($leads_seleccionados, 0, $cantidad_maxima);
            break;
            
        case 'por_criterio':
            $sql = "SELECT l.id FROM leads l WHERE l.responsable_id IS NULL AND l.activo = 1";
            $params = [];
            $types = '';
            
            if ($prioridad_filtro) {
                switch ($prioridad_filtro) {
                    case 'urgente':
                        $sql .= " AND l.prioridad = ?";
                        $params[] = 'urgente';
                        $types .= 's';
                        break;
                    case 'alta':
                        $sql .= " AND l.prioridad IN ('urgente', 'alta')";
                        break;
                    case 'media':
                        $sql .= " AND l.prioridad IN ('urgente', 'alta', 'media')";
                        break;
                }
            }
            
            if (!empty($_POST['canal_id'])) {
                $canal_id = filter_var($_POST['canal_id'], FILTER_VALIDATE_INT);
                if ($canal_id) {
                    $sql .= " AND l.canal_captacion_id = ?";
                    $params[] = $canal_id;
                    $types .= 'i';
                }
            }
            
            if (!empty($_POST['estado_id'])) {
                $estado_id = filter_var($_POST['estado_id'], FILTER_VALIDATE_INT);
                if ($estado_id) {
                    $sql .= " AND l.estado_lead_id = ?";
                    $params[] = $estado_id;
                    $types .= 'i';
                }
            }
            
            if (!empty($_POST['grado_id'])) {
                $grado_id = filter_var($_POST['grado_id'], FILTER_VALIDATE_INT);
                if ($grado_id) {
                    $sql .= " AND l.grado_interes_id = ?";
                    $params[] = $grado_id;
                    $types .= 'i';
                }
            }
            
            $rango_fecha = $_POST['rango_fecha'] ?? '';
            if ($rango_fecha) {
                switch ($rango_fecha) {
                    case 'hoy':
                        $sql .= " AND DATE(l.created_at) = CURDATE()";
                        break;
                    case 'semana':
                        $sql .= " AND l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'mes':
                        $sql .= " AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                    case 'personalizado':
                        if (!empty($_POST['fecha_desde'])) {
                            $sql .= " AND DATE(l.created_at) >= ?";
                            $params[] = $_POST['fecha_desde'];
                            $types .= 's';
                        }
                        if (!empty($_POST['fecha_hasta'])) {
                            $sql .= " AND DATE(l.created_at) <= ?";
                            $params[] = $_POST['fecha_hasta'];
                            $types .= 's';
                        }
                        break;
                }
            }
            
            $sql .= " ORDER BY l.created_at DESC LIMIT 50";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparando consulta: ' . $conn->error);
            }
            
            if ($types) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Error ejecutando consulta: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $leads_a_asignar[] = $row['id'];
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de asignación no válido']);
            exit;
    }
    
    if (empty($leads_a_asignar)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron leads para asignar']);
        exit;
    }
    
    $conn->begin_transaction();
    
    $contador_asignados = 0;
    $fecha_actual = date('Y-m-d H:i:s');
    
    // Actualizar leads
    $stmt_update = $conn->prepare("
        UPDATE leads 
        SET responsable_id = ?,
            fecha_ultima_interaccion = ?
        WHERE id = ? AND responsable_id IS NULL AND activo = 1
    ");
    
    if (!$stmt_update) {
        throw new Exception('Error preparando actualización: ' . $conn->error);
    }
    
    // Historial con las columnas CORRECTAS de TU base de datos
    $stmt_historial = $conn->prepare("
        INSERT INTO historial_estados_lead 
        (lead_id, estado_anterior_id, estado_nuevo_id, usuario_id, created_at)
        SELECT ?, estado_lead_id, estado_lead_id, ?, NOW()
        FROM leads WHERE id = ?
    ");
    
    if (!$stmt_historial) {
        throw new Exception('Error preparando historial: ' . $conn->error);
    }
    
    foreach ($leads_a_asignar as $lead_id) {
        $stmt_update->bind_param('isi', $usuario_id, $fecha_actual, $lead_id);
        
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            $stmt_historial->bind_param('iii', $lead_id, $usuario_asigna, $lead_id);
            $stmt_historial->execute();
            
            $contador_asignados++;
        }
    }
    
    $stmt_update->close();
    $stmt_historial->close();
    
    if ($contador_asignados === 0) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo asignar ningún lead. Pueden estar ya asignados o inactivos.'
        ]);
        exit;
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Leads asignados correctamente',
        'cantidad' => $contador_asignados
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>