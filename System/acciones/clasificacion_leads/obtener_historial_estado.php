<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

// Verificar que se recibió el ID del estado
if (!isset($_POST['estado_id']) || empty($_POST['estado_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID del estado es requerido',
        'data' => []
    ]);
    exit;
}

try {
    $estado_id = (int)$_POST['estado_id'];
    $limite = (int)($_POST['limite'] ?? 50);
    $tipo_historial = $_POST['tipo'] ?? 'todos'; // 'hacia', 'desde', 'todos'
    
    // Validar límite
    if ($limite < 1 || $limite > 200) {
        $limite = 50;
    }
    
    // Primero obtener información del estado
    $estado_sql = "SELECT nombre, descripcion, color FROM estados_lead WHERE id = ?";
    $estado_stmt = $conn->prepare($estado_sql);
    $estado_stmt->bind_param("i", $estado_id);
    $estado_stmt->execute();
    $estado_result = $estado_stmt->get_result();
    
    if ($estado_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Estado no encontrado',
            'data' => []
        ]);
        exit;
    }
    
    $estado_info = $estado_result->fetch_assoc();
    
    // Construir consulta según el tipo de historial
    $where_condition = '';
    switch ($tipo_historial) {
        case 'hacia':
            $where_condition = 'hel.estado_nuevo_id = ?';
            break;
        case 'desde':
            $where_condition = 'hel.estado_anterior_id = ?';
            break;
        default:
            $where_condition = '(hel.estado_nuevo_id = ? OR hel.estado_anterior_id = ?)';
            break;
    }
    
    // Consulta principal para obtener el historial
    $sql = "SELECT 
        hel.id,
        hel.lead_id,
        hel.observaciones,
        hel.created_at,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', COALESCE(l.apellidos_estudiante, '')) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as nombre_contacto,
        l.telefono,
        l.email,
        l.prioridad,
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
        r.nombre as rol_usuario,
        -- Calcular tiempo transcurrido desde la creación del lead
        TIMESTAMPDIFF(HOUR, l.created_at, hel.created_at) as horas_desde_creacion,
        TIMESTAMPDIFF(DAY, l.created_at, hel.created_at) as dias_desde_creacion,
        -- Tiempo desde el cambio anterior del mismo lead
        (SELECT TIMESTAMPDIFF(HOUR, MAX(hel2.created_at), hel.created_at)
         FROM historial_estados_lead hel2 
         WHERE hel2.lead_id = hel.lead_id AND hel2.created_at < hel.created_at) as horas_desde_ultimo_cambio,
        -- Determinar si es el primer cambio del lead
        (SELECT COUNT(*) FROM historial_estados_lead hel3 
         WHERE hel3.lead_id = hel.lead_id AND hel3.created_at < hel.created_at) as cambios_anteriores
    FROM historial_estados_lead hel
    LEFT JOIN leads l ON hel.lead_id = l.id
    LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    LEFT JOIN usuarios u ON hel.usuario_id = u.id
    LEFT JOIN roles r ON u.rol_id = r.id
    WHERE $where_condition
    ORDER BY hel.created_at DESC, hel.id DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters según el tipo de historial
    if ($tipo_historial === 'todos') {
        $stmt->bind_param("iii", $estado_id, $estado_id, $limite);
    } else {
        $stmt->bind_param("ii", $estado_id, $limite);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear fecha y hora
        $fecha = new DateTime($row['created_at']);
        $fecha_formateada = $fecha->format('d/m/Y H:i:s');
        $fecha_relativa = calcularTiempoRelativo($row['created_at']);
        
        // Determinar tipo de cambio
        $tipo_cambio = '';
        $descripcion_cambio = '';
        
        if ($row['cambios_anteriores'] == 0) {
            $tipo_cambio = 'inicial';
            $descripcion_cambio = 'Ingreso inicial al sistema';
        } elseif ($row['es_final'] == 1) {
            $tipo_cambio = 'conversion';
            $descripcion_cambio = 'Conversión final';
        } else {
            $tipo_cambio = 'progreso';
            $descripcion_cambio = 'Progreso en el pipeline';
        }
        
        // Formatear tiempo transcurrido
        $tiempo_total = '';
        if ($row['dias_desde_creacion'] !== null) {
            if ($row['dias_desde_creacion'] == 0) {
                $tiempo_total = $row['horas_desde_creacion'] . 'h desde creación';
            } else {
                $tiempo_total = $row['dias_desde_creacion'] . 'd desde creación';
            }
        }
        
        $tiempo_ultimo = '';
        if ($row['horas_desde_ultimo_cambio'] !== null) {
            if ($row['horas_desde_ultimo_cambio'] < 24) {
                $tiempo_ultimo = $row['horas_desde_ultimo_cambio'] . 'h desde último cambio';
            } else {
                $dias = floor($row['horas_desde_ultimo_cambio'] / 24);
                $tiempo_ultimo = $dias . 'd desde último cambio';
            }
        }
        
        // Determinar dirección del cambio respecto al estado consultado
        $direccion = '';
        $es_relevante = false;
        
        if ($row['estado_nuevo_id'] == $estado_id) {
            $direccion = 'hacia';
            $es_relevante = true;
        }
        if ($row['estado_anterior_id'] == $estado_id) {
            $direccion = ($direccion === 'hacia') ? 'ambos' : 'desde';
            $es_relevante = true;
        }
        
        $historial[] = [
            'id' => $row['id'],
            'lead_id' => $row['lead_id'],
            'codigo_lead' => $row['codigo_lead'] ?? 'Sin código',
            'nombre_estudiante' => $row['nombre_estudiante'] ?? 'Sin nombre',
            'nombre_contacto' => $row['nombre_contacto'] ?? 'Sin contacto',
            'telefono' => $row['telefono'],
            'email' => $row['email'],
            'prioridad' => $row['prioridad'],
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
            'rol_usuario' => $row['rol_usuario'] ?? 'Sin rol',
            'observaciones' => $row['observaciones'],
            'created_at' => $row['created_at'],
            'fecha_formateada' => $fecha_formateada,
            'fecha_relativa' => $fecha_relativa,
            'tipo_cambio' => $tipo_cambio,
            'descripcion_cambio' => $descripcion_cambio,
            'tiempo_total' => $tiempo_total,
            'tiempo_ultimo' => $tiempo_ultimo,
            'direccion' => $direccion,
            'es_relevante' => $es_relevante,
            'cambios_anteriores' => $row['cambios_anteriores'],
            'dias_desde_creacion' => $row['dias_desde_creacion'],
            'horas_desde_creacion' => $row['horas_desde_creacion'],
            'horas_desde_ultimo_cambio' => $row['horas_desde_ultimo_cambio']
        ];
    }
    
    // Obtener estadísticas adicionales del historial
    $stats_sql = "SELECT 
        COUNT(*) as total_registros,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as registros_semana,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as registros_mes,
        COUNT(DISTINCT hel.lead_id) as leads_unicos,
        COUNT(DISTINCT hel.usuario_id) as usuarios_involucrados,
        MIN(hel.created_at) as primer_registro,
        MAX(hel.created_at) as ultimo_registro
    FROM historial_estados_lead hel
    WHERE $where_condition";
    
    $stats_stmt = $conn->prepare($stats_sql);
    
    if ($tipo_historial === 'todos') {
        $stats_stmt->bind_param("ii", $estado_id, $estado_id);
    } else {
        $stats_stmt->bind_param("i", $estado_id);
    }
    
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $estadisticas = $stats_result->fetch_assoc();
    
    // Formatear fechas de estadísticas
    $estadisticas['primer_registro_formatted'] = $estadisticas['primer_registro'] ? 
        date('d/m/Y H:i', strtotime($estadisticas['primer_registro'])) : 'N/A';
    $estadisticas['ultimo_registro_formatted'] = $estadisticas['ultimo_registro'] ? 
        date('d/m/Y H:i', strtotime($estadisticas['ultimo_registro'])) : 'N/A';
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $historial,
        'estado_info' => $estado_info,
        'estadisticas' => $estadisticas,
        'filtros' => [
            'estado_id' => $estado_id,
            'tipo_historial' => $tipo_historial,
            'limite' => $limite
        ],
        'total_cargados' => count($historial),
        'message' => 'Historial del estado cargado correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener historial del estado: ' . $e->getMessage(),
        'data' => []
    ]);
} finally {
    // Cerrar statements y conexión
    if (isset($estado_stmt)) {
        $estado_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($stats_stmt)) {
        $stats_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Función auxiliar para calcular tiempo relativo
function calcularTiempoRelativo($fecha) {
    $ahora = new DateTime();
    $fecha_dt = new DateTime($fecha);
    $diff = $ahora->diff($fecha_dt);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Hace menos de 1 minuto';
            } else {
                return 'Hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
            }
        } else {
            return 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        }
    } elseif ($diff->days == 1) {
        return 'Ayer';
    } elseif ($diff->days < 7) {
        return 'Hace ' . $diff->days . ' días';
    } elseif ($diff->days < 30) {
        $semanas = floor($diff->days / 7);
        return 'Hace ' . $semanas . ' semana' . ($semanas > 1 ? 's' : '');
    } elseif ($diff->days < 365) {
        $meses = floor($diff->days / 30);
        return 'Hace ' . $meses . ' mes' . ($meses > 1 ? 'es' : '');
    } else {
        $años = floor($diff->days / 365);
        return 'Hace ' . $años . ' año' . ($años > 1 ? 's' : '');
    }
}
?>