<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    // Obtener parámetros
    $periodo = $_POST['periodo'] ?? 'mes_actual';
    $categoria = $_POST['categoria'] ?? 'todas';
    $metrica = $_POST['metrica'] ?? 'conversiones';
    
    // Validar parámetros
    $periodos_validos = ['todo', 'mes_actual', 'mes_anterior', 'trimestre', 'semestre', 'año'];
    $categorias_validas = ['todas', 'Elite', 'Destacado', 'Activo', 'En Progreso', 'Nuevo'];
    $metricas_validas = ['conversiones', 'tasa_conversion', 'total_usos', 'codigos_activos'];
    
    if (!in_array($periodo, $periodos_validos)) {
        throw new Exception('Período no válido');
    }
    
    if (!in_array($categoria, $categorias_validas)) {
        throw new Exception('Categoría no válida');
    }
    
    if (!in_array($metrica, $metricas_validas)) {
        throw new Exception('Métrica no válida');
    }
    
    // Construir filtros
    $where_periodo = construirFiltrosPeriodo($periodo);
    
    // Construir ORDER BY según métrica - CORREGIDO
    $order_by = 'conversiones DESC, tasa_conversion DESC';
    switch($metrica) {
        case 'tasa_conversion':
            $order_by = 'tasa_conversion DESC, conversiones DESC';
            break;
        case 'total_usos':
            $order_by = 'total_usos DESC, conversiones DESC';
            break;
        case 'codigos_activos':
            $order_by = 'codigos_activos DESC, conversiones DESC';
            break;
    }
    
    // Query principal con subconsulta para manejar el filtro de categoría correctamente
    $sql = "SELECT * FROM (
        SELECT
            a.id as apoderado_id,
            CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
            f.apellido_principal as familia,
            COUNT(DISTINCT cr.id) as total_codigos,
            COALESCE(SUM(cr.usos_actuales), 0) as total_usos,
            COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones,
            CASE 
                WHEN SUM(cr.usos_actuales) > 0 
                THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
                ELSE 0 
            END as tasa_conversion,
            COUNT(DISTINCT CASE WHEN cr.activo = 1 THEN cr.id END) as codigos_activos,
            CASE
                WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 10 THEN 'Elite'
                WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 5 THEN 'Destacado'
                WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 1 THEN 'Activo'
                WHEN SUM(cr.usos_actuales) > 0 THEN 'En Progreso'
                ELSE 'Nuevo'
            END as categoria
        FROM apoderados a
        INNER JOIN familias f ON a.familia_id = f.id
        LEFT JOIN codigos_referido cr ON a.id = cr.apoderado_id
        LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
        WHERE a.activo = 1 $where_periodo
        GROUP BY a.id, a.nombres, a.apellidos, f.apellido_principal
        HAVING total_codigos > 0
    ) as ranking_temp";
    
    // Agregar filtro de categoría si no es 'todas'
    if ($categoria !== 'todas') {
        $sql .= " WHERE categoria = " . $conn->real_escape_string("'$categoria'");
    }
    
    $sql .= " ORDER BY $order_by LIMIT 100";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    $leaderboard = [];
    while($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    
    // Obtener estadísticas del período
    $stats_sql = "SELECT
        COUNT(DISTINCT a.id) as total_referentes,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as total_conversiones,
        CASE 
            WHEN SUM(cr.usos_actuales) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
            ELSE 0 
        END as tasa_promedio
    FROM apoderados a
    LEFT JOIN codigos_referido cr ON a.id = cr.apoderado_id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE a.activo = 1 $where_periodo";
    
    $stats_result = $conn->query($stats_sql);
    
    if (!$stats_result) {
        throw new Exception('Error en consulta de estadísticas: ' . $conn->error);
    }
    
    $estadisticas = $stats_result->fetch_assoc();
    
    // Asegurar valores por defecto
    if (!$estadisticas || $estadisticas['total_referentes'] == 0) {
        $estadisticas = [
            'total_referentes' => 0,
            'total_conversiones' => 0,
            'tasa_promedio' => 0,
            'mejor_mes' => 'Sin datos'
        ];
    } else {
        // Obtener mejor referente del mes
        if (count($leaderboard) > 0) {
            $estadisticas['mejor_mes'] = $leaderboard[0]['nombre_completo'];
        } else {
            $estadisticas['mejor_mes'] = 'Sin datos';
        }
    }
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard,
        'estadisticas' => $estadisticas,
        'total_registros' => count($leaderboard)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => isset($e) ? $e->getLine() : 'N/A'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function construirFiltrosPeriodo($periodo) {
    $where = '';
    
    switch($periodo) {
        case 'mes_actual':
            $where = "AND MONTH(ur.fecha_uso) = MONTH(CURDATE()) AND YEAR(ur.fecha_uso) = YEAR(CURDATE())";
            break;
        case 'mes_anterior':
            $where = "AND MONTH(ur.fecha_uso) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                      AND YEAR(ur.fecha_uso) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
        case 'trimestre':
            $where = "AND ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case 'semestre':
            $where = "AND ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            break;
        case 'año':
            $where = "AND YEAR(ur.fecha_uso) = YEAR(CURDATE())";
            break;
        case 'todo':
        default:
            $where = '';
            break;
    }
    
    return $where;
}

$conn->close();
?>