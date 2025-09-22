<?php
// actions/verificar_duplicados.php
include '../../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'buscar_duplicados':
            buscarDuplicados($conn);
            break;
        case 'verificar_individual':
            verificarIndividual($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function buscarDuplicados($conn) {
    $tipo = $_POST['tipo'] ?? 'manual';
    $duplicados = [];
    $estadisticas = [];

    if ($tipo === 'manual') {
        // Búsqueda manual con criterios específicos
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($email)) {
            $conditions[] = "l.email LIKE ?";
            $params[] = "%$email%";
            $types .= "s";
        }

        if (!empty($telefono)) {
            // Limpiar teléfono de caracteres especiales para comparación
            $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);
            $conditions[] = "(REPLACE(REPLACE(REPLACE(l.telefono, ' ', ''), '-', ''), '+', '') LIKE ? OR REPLACE(REPLACE(REPLACE(l.whatsapp, ' ', ''), '-', ''), '+', '') LIKE ?)";
            $params[] = "%$telefono_limpio%";
            $params[] = "%$telefono_limpio%";
            $types .= "ss";
        }

        if (!empty($nombre)) {
            $conditions[] = "(CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) LIKE ? OR CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) LIKE ?)";
            $params[] = "%$nombre%";
            $params[] = "%$nombre%";
            $types .= "ss";
        }

        if (empty($conditions)) {
            echo json_encode(['success' => false, 'message' => 'Debe proporcionar al menos un criterio de búsqueda']);
            return;
        }

        $where_clause = "(" . implode(" OR ", $conditions) . ")";
        
    } else {
        // Verificación completa de todos los leads
        $where_clause = "1=1";
        $params = [];
        $types = "";
    }

    // Consulta principal para encontrar duplicados
    $sql = "SELECT 
        l.id,
        l.codigo_lead,
        l.nombres_estudiante,
        l.apellidos_estudiante,
        l.fecha_nacimiento_estudiante,
        l.genero_estudiante,
        l.nombres_contacto,
        l.apellidos_contacto,
        l.telefono,
        l.whatsapp,
        l.email,
        l.colegio_procedencia,
        l.canal_captacion_id,
        cc.nombre as canal_captacion,
        l.estado_lead_id,
        el.nombre as estado_lead,
        el.color as color_estado,
        l.responsable_id,
        CONCAT(u.nombre, ' ', u.apellidos) as responsable_nombre,
        l.prioridad,
        l.puntaje_interes,
        l.created_at,
        DATE_FORMAT(l.created_at, '%d/%m/%Y') as fecha_registro,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante_completo,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto_completo
    FROM leads l
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN usuarios u ON l.responsable_id = u.id
    WHERE l.activo = 1 AND $where_clause
    ORDER BY l.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }

    if ($tipo === 'todos') {
        // Para verificación completa, buscar duplicados por algoritmos más sofisticados
        $duplicados = encontrarDuplicadosCompletos($leads);
    } else {
        // Para búsqueda manual, los resultados ya son los potenciales duplicados
        $duplicados = marcarMotivoDuplicado($leads, $_POST);
    }

    // Calcular estadísticas
    $estadisticas = calcularEstadisticas($conn, $duplicados);

    echo json_encode([
        'success' => true,
        'duplicados' => $duplicados,
        'estadisticas' => $estadisticas,
        'total_encontrados' => count($duplicados)
    ]);
}

function encontrarDuplicadosCompletos($leads) {
    $duplicados = [];
    $procesados = [];

    foreach ($leads as $i => $lead1) {
        if (in_array($lead1['id'], $procesados)) continue;

        $grupo_duplicados = [$lead1];
        $procesados[] = $lead1['id'];

        foreach ($leads as $j => $lead2) {
            if ($i >= $j || in_array($lead2['id'], $procesados)) continue;

            $es_duplicado = false;
            $motivos = [];

            // Verificar por email exacto
            if (!empty($lead1['email']) && !empty($lead2['email']) && 
                strtolower($lead1['email']) === strtolower($lead2['email'])) {
                $es_duplicado = true;
                $motivos[] = 'Email idéntico';
            }

            // Verificar por teléfono (limpiando formatos)
            $tel1 = preg_replace('/[^0-9]/', '', $lead1['telefono'] ?? '');
            $tel2 = preg_replace('/[^0-9]/', '', $lead2['telefono'] ?? '');
            if (!empty($tel1) && !empty($tel2) && $tel1 === $tel2) {
                $es_duplicado = true;
                $motivos[] = 'Teléfono idéntico';
            }

            // Verificar por WhatsApp
            $wa1 = preg_replace('/[^0-9]/', '', $lead1['whatsapp'] ?? '');
            $wa2 = preg_replace('/[^0-9]/', '', $lead2['whatsapp'] ?? '');
            if (!empty($wa1) && !empty($wa2) && $wa1 === $wa2) {
                $es_duplicado = true;
                $motivos[] = 'WhatsApp idéntico';
            }

            // Verificar nombres similares (Levenshtein distance)
            $nombre1 = strtolower($lead1['nombre_contacto_completo'] ?? '');
            $nombre2 = strtolower($lead2['nombre_contacto_completo'] ?? '');
            if (!empty($nombre1) && !empty($nombre2)) {
                $distancia = levenshtein($nombre1, $nombre2);
                $similaridad = 1 - ($distancia / max(strlen($nombre1), strlen($nombre2)));
                if ($similaridad > 0.85) { // 85% de similitud
                    $es_duplicado = true;
                    $motivos[] = 'Nombres muy similares';
                }
            }

            // Verificar estudiante con mismo nombre y fecha de nacimiento
            if (!empty($lead1['nombres_estudiante']) && !empty($lead2['nombres_estudiante']) &&
                !empty($lead1['fecha_nacimiento_estudiante']) && !empty($lead2['fecha_nacimiento_estudiante'])) {
                $estudiante1 = strtolower($lead1['nombre_estudiante_completo']);
                $estudiante2 = strtolower($lead2['nombre_estudiante_completo']);
                if ($estudiante1 === $estudiante2 && 
                    $lead1['fecha_nacimiento_estudiante'] === $lead2['fecha_nacimiento_estudiante']) {
                    $es_duplicado = true;
                    $motivos[] = 'Estudiante idéntico (nombre y fecha)';
                }
            }

            if ($es_duplicado) {
                $lead2['motivo_duplicado'] = implode(', ', $motivos);
                $grupo_duplicados[] = $lead2;
                $procesados[] = $lead2['id'];
            }
        }

        // Si encontramos duplicados en este grupo, agregarlos todos
        if (count($grupo_duplicados) > 1) {
            foreach ($grupo_duplicados as $dup) {
                if (!isset($dup['motivo_duplicado'])) {
                    $dup['motivo_duplicado'] = 'Lead principal del grupo';
                }
                $duplicados[] = $dup;
            }
        }
    }

    return $duplicados;
}

function marcarMotivoDuplicado($leads, $criterios) {
    foreach ($leads as &$lead) {
        $motivos = [];
        
        if (!empty($criterios['email']) && stripos($lead['email'], $criterios['email']) !== false) {
            $motivos[] = 'Email coincidente';
        }
        
        if (!empty($criterios['telefono'])) {
            $telefono_busqueda = preg_replace('/[^0-9]/', '', $criterios['telefono']);
            $telefono_lead = preg_replace('/[^0-9]/', '', $lead['telefono'] ?? '');
            $whatsapp_lead = preg_replace('/[^0-9]/', '', $lead['whatsapp'] ?? '');
            
            if (strpos($telefono_lead, $telefono_busqueda) !== false || 
                strpos($whatsapp_lead, $telefono_busqueda) !== false) {
                $motivos[] = 'Teléfono coincidente';
            }
        }
        
        if (!empty($criterios['nombre'])) {
            if (stripos($lead['nombre_contacto_completo'], $criterios['nombre']) !== false ||
                stripos($lead['nombre_estudiante_completo'], $criterios['nombre']) !== false) {
                $motivos[] = 'Nombre coincidente';
            }
        }
        
        $lead['motivo_duplicado'] = implode(', ', $motivos);
    }
    
    return $leads;
}

function verificarIndividual($conn) {
    $lead_id = $_POST['lead_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    if (empty($lead_id) || (empty($email) && empty($telefono))) {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes para verificar']);
        return;
    }

    $conditions = [];
    $params = [];
    $types = "";

    if (!empty($email)) {
        $conditions[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if (!empty($telefono)) {
        $conditions[] = "(telefono = ? OR whatsapp = ?)";
        $params[] = $telefono;
        $params[] = $telefono;
        $types .= "ss";
    }

    $where_clause = "(" . implode(" OR ", $conditions) . ") AND id != ? AND activo = 1";
    $params[] = $lead_id;
    $types .= "i";

    $sql = "SELECT 
        id, codigo_lead, 
        CONCAT(nombres_contacto, ' ', apellidos_contacto) as nombre_contacto,
        CONCAT(nombres_estudiante, ' ', apellidos_estudiante) as nombre_estudiante,
        email, telefono, whatsapp,
        'Email/Teléfono duplicado' as motivo
    FROM leads 
    WHERE $where_clause
    LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $duplicados = [];
    while ($row = $result->fetch_assoc()) {
        $duplicados[] = $row;
    }

    echo json_encode([
        'success' => true,
        'duplicados' => $duplicados
    ]);
}

function calcularEstadisticas($conn, $duplicados) {
    // Estadísticas básicas
    $total_leads_sql = "SELECT COUNT(*) as total FROM leads WHERE activo = 1";
    $total_result = $conn->query($total_leads_sql);
    $total_leads = $total_result->fetch_assoc()['total'];

    // Contar duplicados por tipo
    $email_duplicados = 0;
    $telefono_duplicados = 0;

    foreach ($duplicados as $dup) {
        if (stripos($dup['motivo_duplicado'] ?? '', 'email') !== false) {
            $email_duplicados++;
        }
        if (stripos($dup['motivo_duplicado'] ?? '', 'teléfono') !== false) {
            $telefono_duplicados++;
        }
    }

    return [
        'total_leads' => $total_leads,
        'duplicados_encontrados' => count($duplicados),
        'email_duplicados' => $email_duplicados,
        'telefono_duplicados' => $telefono_duplicados
    ];
}

$conn->close();
?>