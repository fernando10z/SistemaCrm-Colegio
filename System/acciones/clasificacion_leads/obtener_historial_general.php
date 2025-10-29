<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    $fecha_desde = $_POST['fecha_desde'] ?? null;
    $fecha_hasta = $_POST['fecha_hasta'] ?? null;
    $usuario_id = $_POST['usuario_id'] ?? null;
    $estado_id = $_POST['estado_id'] ?? null;
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = isset($_POST['por_pagina']) ? (int)$_POST['por_pagina'] : 20;
    
    $offset = ($pagina - 1) * $por_pagina;

    // Construir query con filtros
    $where_clauses = [];
    $params = [];
    $types = '';

    if ($fecha_desde) {
        $where_clauses[] = "DATE(hel.created_at) >= ?";
        $params[] = $fecha_desde;
        $types .= 's';
    }

    if ($fecha_hasta) {
        $where_clauses[] = "DATE(hel.created_at) <= ?";
        $params[] = $fecha_hasta;
        $types .= 's';
    }

    if ($usuario_id) {
        $where_clauses[] = "hel.usuario_id = ?";
        $params[] = $usuario_id;
        $types .= 'i';
    }

    if ($estado_id) {
        $where_clauses[] = "(hel.estado_anterior_id = ? OR hel.estado_nuevo_id = ?)";
        $params[] = $estado_id;
        $params[] = $estado_id;
        $types .= 'ii';
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total
                  FROM historial_estados_lead hel
                  $where_sql";

    if (!empty($params)) {
        $stmt_count = $conn->prepare($count_sql);
        $stmt_count->bind_param($types, ...$params);
        $stmt_count->execute();
        $total_result = $stmt_count->get_result();
    } else {
        $total_result = $conn->query($count_sql);
    }

    $total_registros = $total_result->fetch_assoc()['total'];
    $total_paginas = ceil($total_registros / $por_pagina);

    // Obtener registros
    $sql = "SELECT 
        hel.id,
        hel.lead_id,
        hel.created_at,
        hel.observaciones,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as lead_nombre,
        ea.nombre as estado_anterior,
        ea.color as color_anterior,
        en.nombre as estado_nuevo,
        en.color as color_nuevo,
        CONCAT(u.nombre, ' ', u.apellidos) as usuario_nombre,
        TIMESTAMPDIFF(DAY, 
            (SELECT MAX(h2.created_at) 
             FROM historial_estados_lead h2 
             WHERE h2.lead_id = hel.lead_id 
             AND h2.created_at < hel.created_at),
            hel.created_at
        ) as dias_en_estado_anterior
        FROM historial_estados_lead hel
        LEFT JOIN leads l ON hel.lead_id = l.id
        LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
        LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
        LEFT JOIN usuarios u ON hel.usuario_id = u.id
        $where_sql
        ORDER BY hel.created_at DESC
        LIMIT ? OFFSET ?";

    // Agregar límite y offset a los parámetros
    $params[] = $por_pagina;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $datos = [];
    while($row = $result->fetch_assoc()) {
        // Formatear fecha
        $fecha = new DateTime($row['created_at']);
        $fecha_formateada = $fecha->format('d/m/Y H:i');

        // Calcular tiempo transcurrido
        $tiempo_transcurrido = '';
        if ($row['dias_en_estado_anterior'] !== null) {
            $dias = (int)$row['dias_en_estado_anterior'];
            if ($dias === 0) {
                $tiempo_transcurrido = 'Mismo día';
            } elseif ($dias === 1) {
                $tiempo_transcurrido = '1 día';
            } else {
                $tiempo_transcurrido = $dias . ' días';
            }
        }

        $datos[] = [
            'id' => (int)$row['id'],
            'lead_id' => (int)$row['lead_id'],
            'lead_codigo' => $row['codigo_lead'],
            'lead_nombre' => $row['lead_nombre'],
            'fecha_formateada' => $fecha_formateada,
            'estado_anterior' => $row['estado_anterior'],
            'color_anterior' => $row['color_anterior'] ?? '#6c757d',
            'estado_nuevo' => $row['estado_nuevo'],
            'color_nuevo' => $row['color_nuevo'] ?? '#6c757d',
            'usuario_nombre' => $row['usuario_nombre'],
            'observaciones' => $row['observaciones'],
            'tiempo_transcurrido' => $tiempo_transcurrido
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $datos,
        'pagination' => [
            'total_registros' => (int)$total_registros,
            'total_paginas' => (int)$total_paginas,
            'pagina_actual' => (int)$pagina,
            'por_pagina' => (int)$por_pagina
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();