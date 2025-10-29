<?php
session_start();
require_once '../../bd/conexion.php';

header('Content-Type: application/json');

$tipo = $_POST['tipo'] ?? '';
$leads_preview = [];

try {
    switch ($tipo) {
        case 'individual':
            $lead_id = filter_var($_POST['lead_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($lead_id) {
                $sql = "SELECT l.id, 
                        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre,
                        l.email, 
                        e.nombre as estado, 
                        l.prioridad, 
                        c.nombre as canal, 
                        DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i') as fecha_creacion
                        FROM leads l
                        LEFT JOIN estados_lead e ON l.estado_lead_id = e.id
                        LEFT JOIN canales_captacion c ON l.canal_captacion_id = c.id
                        WHERE l.id = ? AND l.responsable_id IS NULL AND l.activo = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $lead_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $leads_preview[] = $row;
                }
            }
            break;
            
        case 'multiple':
            if (isset($_POST['leads_ids']) && is_array($_POST['leads_ids'])) {
                $cantidad_maxima = filter_var($_POST['cantidad_maxima'] ?? 5, FILTER_VALIDATE_INT);
                $leads_ids = array_map('intval', $_POST['leads_ids']);
                $leads_ids = array_slice($leads_ids, 0, $cantidad_maxima);
                
                if (!empty($leads_ids)) {
                    $placeholders = str_repeat('?,', count($leads_ids) - 1) . '?';
                    $sql = "SELECT l.id, 
                            CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre,
                            l.email, 
                            e.nombre as estado, 
                            l.prioridad,
                            c.nombre as canal, 
                            DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i') as fecha_creacion
                            FROM leads l
                            LEFT JOIN estados_lead e ON l.estado_lead_id = e.id
                            LEFT JOIN canales_captacion c ON l.canal_captacion_id = c.id
                            WHERE l.id IN ($placeholders) AND l.responsable_id IS NULL AND l.activo = 1";
                    
                    $stmt = $conn->prepare($sql);
                    $types = str_repeat('i', count($leads_ids));
                    $stmt->bind_param($types, ...$leads_ids);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $leads_preview[] = $row;
                    }
                }
            }
            break;
            
        case 'por_criterio':
            $sql = "SELECT l.id, 
                    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre,
                    l.email, 
                    e.nombre as estado, 
                    l.prioridad,
                    c.nombre as canal, 
                    DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i') as fecha_creacion
                    FROM leads l
                    LEFT JOIN estados_lead e ON l.estado_lead_id = e.id
                    LEFT JOIN canales_captacion c ON l.canal_captacion_id = c.id
                    WHERE l.responsable_id IS NULL AND l.activo = 1";
            
            $params = [];
            $types = '';
            
            $prioridad_filtro = $_POST['prioridad'] ?? '';
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
                $sql .= " AND l.canal_captacion_id = ?";
                $params[] = intval($_POST['canal_id']);
                $types .= 'i';
            }
            
            if (!empty($_POST['estado_id'])) {
                $sql .= " AND l.estado_lead_id = ?";
                $params[] = intval($_POST['estado_id']);
                $types .= 'i';
            }
            
            if (!empty($_POST['grado_id'])) {
                $sql .= " AND l.grado_interes_id = ?";
                $params[] = intval($_POST['grado_id']);
                $types .= 'i';
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
            if ($types) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $leads_preview[] = $row;
            }
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $leads_preview,
        'total' => count($leads_preview)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>