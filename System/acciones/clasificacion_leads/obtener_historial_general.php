<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

try {
    // Obtener parámetros de filtros
    $fecha_desde = $_POST['fecha_desde'] ?? '';
    $fecha_hasta = $_POST['fecha_hasta'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? '';
    $estado_id = $_POST['estado_id'] ?? '';
    $pagina = (int)($_POST['pagina'] ?? 1);
    $por_pagina = (int)($_POST['por_pagina'] ?? 20);
    
    // Validar parámetros
    if ($pagina < 1) $pagina = 1;
    if ($por_pagina < 5 || $por_pagina > 100) $por_pagina = 20;
    
    // Construir condiciones WHERE
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Filtro por fecha
    if (!empty($fecha_desde)) {
        $where_conditions[] = "hel.created_at >= ?";
        $params[] = $fecha_desde . ' 00:00:00';
        $types .= 's';
    }
    
    if (!empty($fecha_hasta)) {
        $where_conditions[] = "hel.created_at <= ?";
        $params[] = $fecha_hasta . ' 23:59:59';
        $types .= 's';
    }
    
    // Filtro por usuario
    if (!empty($usuario_id) && is_numeric($usuario_id)) {
        $where_conditions[] = "hel.usuario_id = ?";
        $params[] = $usuario_id;
        $types .= 'i';
    }
    
    // Filtro por estado (puede ser estado anterior o nuevo)
    if (!empty($estado_id) && is_numeric($estado_id)) {
        $where_conditions[] = "(hel.estado_anterior_id = ? OR hel.estado_nuevo_id = ?)";
        $params[] = $estado_id;
        $params[] = $estado_id;
        $types .= 'ii';
    }
    
    // Si no hay filtros, aplicar filtro por defecto (último mes)
    if (empty($where_conditions)) {
        $where_conditions[] = "hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Consulta para contar registros totales
    $count_sql = "SELECT COUNT(*) as total
                  FROM historial_estados_lead hel
                  LEFT JOIN leads l ON hel.lead_id = l.id
                  LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
                  LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
                  LEFT JOIN usuarios u ON hel.usuario_id = u.id
                  WHERE $where_clause";
    
    // Preparar y ejecutar consulta de conteo
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_registros = $count_result->fetch_assoc()['total'];
    
    // Calcular paginación
    $total_paginas = ceil($total_registros / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    
    // Consulta principal con datos detallados
    $sql = "SELECT 
        hel.id,
        hel.lead_id,
        hel.observaciones,
        hel.created_at,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', COALESCE(l.apellidos_estudiante, '')) as lead_nombre,
        CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as contacto_nombre,
        ea.id as estado_anterior_id,
        ea.nombre as estado_anterior,
        ea.color as color_anterior,
        en.id as estado_nuevo_id,
        en.nombre as estado_nuevo,
        en.color as color_nuevo,
        en.es_final,
        u.id as usuario_id,
        CONCAT(u.nombre, ' ', u.apellidos) as usuario_nombre,
        u.usuario as usuario_login,
        r.nombre as usuario_rol,
        TIMESTAMPDIFF(HOUR, l.created_at, hel.created_at) as horas_desde_creacion,
        TIMESTAMPDIFF(DAY, l.created_at, hel.created_at) as dias_desde_creacion,
        -- Tiempo desde el cambio anterior para este lead
        (SELECT TIMESTAMPDIFF(HOUR, MAX(hel2.created_at), hel.created_at)
         FROM historial_estados_lead hel2 
         WHERE hel2.lead_id = hel.lead_id AND hel2.created_at < hel.created_at) as horas_desde_ultimo_cambio
    FROM historial_estados_lead hel
    LEFT JOIN leads l ON hel.lead_id = l.id
    LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    LEFT JOIN usuarios u ON hel.usuario_id = u.id
    LEFT JOIN roles r ON u.rol_id = r.id
    WHERE $where_clause
    ORDER BY hel.created_at DESC, hel.id DESC
    LIMIT $por_pagina OFFSET $offset";
    
    // Preparar y ejecutar consulta principal
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Error en la consulta principal: " . $conn->error);
    }
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear fecha
        $fecha = new DateTime($row['created_at']);
        $fecha_formateada = $fecha->format('d/m/Y H:i');
        
        // Calcular tiempo transcurrido desde la creación del lead
        $tiempo_transcurrido = '';
        if ($row['dias_desde_creacion'] !== null) {
            if ($row['dias_desde_creacion'] == 0) {
                $tiempo_transcurrido = $row['horas_desde_creacion'] . 'h';
            } else {
                $tiempo_transcurrido = $row['dias_desde_creacion'] . 'd';
            }
        }
        
        // Tiempo desde el último cambio
        $tiempo_ultimo_cambio = '';
        if ($row['horas_desde_ultimo_cambio'] !== null) {
            if ($row['horas_desde_ultimo_cambio'] < 24) {
                $tiempo_ultimo_cambio = $row['horas_desde_ultimo_cambio'] . 'h';
            } else {
                $dias = floor($row['horas_desde_ultimo_cambio'] / 24);
                $tiempo_ultimo_cambio = $dias . 'd';
            }
        }
        
        // Determinar tipo de cambio
        $tipo_cambio = '';
        if ($row['estado_anterior_id'] === null) {
            $tipo_cambio = 'Ingreso inicial';
        } elseif ($row['es_final'] == 1) {
            $tipo_cambio = 'Conversión final';
        } else {
            $tipo_cambio = 'Progreso';
        }
        
        $historial[] = [
            'id' => $row['id'],
            'lead_id' => $row['lead_id'],
            'lead_codigo' => $row['codigo_lead'] ?? 'Sin código',
            'lead_nombre' => $row['lead_nombre'] ?? 'Sin nombre',
            'contacto_nombre' => $row['contacto_nombre'] ?? 'Sin contacto',
            'estado_anterior_id' => $row['estado_anterior_id'],
            'estado_anterior' => $row['estado_anterior'],
            'color_anterior' => $row['color_anterior'] ?? '#6c757d',
            'estado_nuevo_id' => $row['estado_nuevo_id'],
            'estado_nuevo' => $row['estado_nuevo'] ?? 'Sin estado',
            'color_nuevo' => $row['color_nuevo'] ?? '#007bff',
            'es_final' => $row['es_final'],
            'usuario_id' => $row['usuario_id'],
            'usuario_nombre' => $row['usuario_nombre'] ?? 'Usuario desconocido',
            'usuario_login' => $row['usuario_login'] ?? 'Sin login',
            'usuario_rol' => $row['usuario_rol'] ?? 'Sin rol',
            'observaciones' => $row['observaciones'],
            'fecha_cambio' => $row['created_at'],
            'fecha_formateada' => $fecha_formateada,
            'tiempo_transcurrido' => $tiempo_transcurrido,
            'tiempo_ultimo_cambio' => $tiempo_ultimo_cambio,
            'tipo_cambio' => $tipo_cambio,
            'dias_desde_creacion' => $row['dias_desde_creacion'],
            'horas_desde_creacion' => $row['horas_desde_creacion'],
            'horas_desde_ultimo_cambio' => $row['horas_desde_ultimo_cambio']
        ];
    }
    
    // Información de paginación
    $pagination = [
        'pagina_actual' => $pagina,
        'total_paginas' => $total_paginas,
        'total_registros' => $total_registros,
        'por_pagina' => $por_pagina,
        'desde' => $offset + 1,
        'hasta' => min($offset + $por_pagina, $total_registros),
        'tiene_anterior' => $pagina > 1,
        'tiene_siguiente' => $pagina < $total_paginas
    ];
    
    // Estadísticas adicionales de la consulta actual
    $stats_consulta = [
        'registros_cargados' => count($historial),
        'filtros_aplicados' => !empty($fecha_desde) || !empty($fecha_hasta) || !empty($usuario_id) || !empty($estado_id),
        'periodo_consulta' => empty($fecha_desde) && empty($fecha_hasta) ? 'Últimos 30 días' : 
                             (!empty($fecha_desde) && !empty($fecha_hasta) ? "Desde $fecha_desde hasta $fecha_hasta" : 
                             (!empty($fecha_desde) ? "Desde $fecha_desde" : "Hasta $fecha_hasta"))
    ];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $historial,
        'pagination' => $pagination,
        'stats' => $stats_consulta,
        'filtros' => [
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'usuario_id' => $usuario_id,
            'estado_id' => $estado_id
        ],
        'message' => 'Historial cargado correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener historial: ' . $e->getMessage(),
        'data' => [],
        'pagination' => [
            'pagina_actual' => 1,
            'total_paginas' => 0,
            'total_registros' => 0,
            'por_pagina' => 20
        ]
    ]);
} finally {
    // Cerrar statements y conexión
    if (isset($count_stmt)) {
        $count_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>