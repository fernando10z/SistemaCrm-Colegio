<?php
/**
 * Obtener Historial de Usos de un Código de Referido
 * Retorna todos los usos (convertidos y pendientes) de un código específico
 */

session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

try {
    // Validar que se recibió el ID del código
    $codigo_id = intval($_POST['id_codigo'] ?? 0);
    
    if ($codigo_id === 0) {
        throw new Exception('ID de código no válido');
    }

    // Verificar que el código existe
    $check_sql = "SELECT id, codigo FROM codigos_referido WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $codigo_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('El código especificado no existe');
    }
    
    $codigo_info = $check_result->fetch_assoc();

    // Obtener todos los usos del código
    $sql = "SELECT 
            ur.id,
            ur.fecha_uso,
            ur.convertido,
            ur.fecha_conversion,
            ur.observaciones,
            -- Información del Lead
            CONCAT(l.nombres_estudiante, ' ', COALESCE(l.apellidos_estudiante, '')) as lead_nombre,
            l.email as lead_email,
            l.telefono as lead_telefono,
            CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as contacto_nombre,
            -- Estado del Lead
            el.nombre as lead_estado,
            el.color as estado_color,
            -- Grado de interés
            g.nombre as grado_interes,
            -- Canal de captación
            cc.nombre as canal_captacion,
            -- Días transcurridos desde el uso
            DATEDIFF(CURDATE(), DATE(ur.fecha_uso)) as dias_desde_uso,
            -- Días transcurridos desde la conversión (si aplica)
            CASE 
                WHEN ur.fecha_conversion IS NOT NULL 
                THEN DATEDIFF(CURDATE(), ur.fecha_conversion)
                ELSE NULL
            END as dias_desde_conversion
        FROM usos_referido ur
        INNER JOIN leads l ON ur.lead_id = l.id
        LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
        LEFT JOIN grados g ON l.grado_interes_id = g.id
        LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
        WHERE ur.codigo_referido_id = ?
        ORDER BY ur.fecha_uso DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $codigo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $usos = [];
    $estadisticas = [
        'total_usos' => 0,
        'convertidos' => 0,
        'pendientes' => 0,
        'tasa_conversion' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        // Formatear fechas
        $row['fecha_uso_formateada'] = date('d/m/Y H:i', strtotime($row['fecha_uso']));
        $row['fecha_uso_relativa'] = obtenerFechaRelativa($row['fecha_uso']);
        
        if ($row['fecha_conversion']) {
            $row['fecha_conversion_formateada'] = date('d/m/Y', strtotime($row['fecha_conversion']));
            $row['fecha_conversion_relativa'] = obtenerFechaRelativa($row['fecha_conversion']);
        } else {
            $row['fecha_conversion_formateada'] = null;
            $row['fecha_conversion_relativa'] = null;
        }

        // Determinar estado del uso
        if ($row['convertido'] == 1) {
            $row['estado_uso'] = 'convertido';
            $row['estado_badge'] = 'success';
            $estadisticas['convertidos']++;
        } else {
            // Clasificar pendientes por tiempo transcurrido
            if ($row['dias_desde_uso'] > 30) {
                $row['estado_uso'] = 'pendiente_urgente';
                $row['estado_badge'] = 'danger';
            } elseif ($row['dias_desde_uso'] > 14) {
                $row['estado_uso'] = 'pendiente_seguimiento';
                $row['estado_badge'] = 'warning';
            } else {
                $row['estado_uso'] = 'pendiente_reciente';
                $row['estado_badge'] = 'info';
            }
            $estadisticas['pendientes']++;
        }

        // Tiempo de conversión (si aplica)
        if ($row['convertido'] == 1 && $row['fecha_conversion']) {
            $fecha_uso_obj = new DateTime($row['fecha_uso']);
            $fecha_conv_obj = new DateTime($row['fecha_conversion']);
            $intervalo = $fecha_uso_obj->diff($fecha_conv_obj);
            $row['tiempo_conversion_dias'] = $intervalo->days;
        } else {
            $row['tiempo_conversion_dias'] = null;
        }

        $estadisticas['total_usos']++;
        $usos[] = $row;
    }

    // Calcular tasa de conversión
    if ($estadisticas['total_usos'] > 0) {
        $estadisticas['tasa_conversion'] = round(
            ($estadisticas['convertidos'] / $estadisticas['total_usos']) * 100, 
            1
        );
    }

    // Calcular tiempo promedio de conversión
    $tiempo_promedio_sql = "SELECT 
                            AVG(DATEDIFF(fecha_conversion, fecha_uso)) as promedio_dias
                            FROM usos_referido
                            WHERE codigo_referido_id = ?
                            AND convertido = 1
                            AND fecha_conversion IS NOT NULL";
    $tiempo_stmt = $conn->prepare($tiempo_promedio_sql);
    $tiempo_stmt->bind_param("i", $codigo_id);
    $tiempo_stmt->execute();
    $tiempo_result = $tiempo_stmt->get_result();
    $tiempo_data = $tiempo_result->fetch_assoc();
    
    $estadisticas['tiempo_promedio_conversion'] = $tiempo_data['promedio_dias'] ? 
        round($tiempo_data['promedio_dias'], 1) : null;

    echo json_encode([
        'success' => true,
        'codigo' => $codigo_info['codigo'],
        'usos' => $usos,
        'estadisticas' => $estadisticas,
        'message' => count($usos) > 0 ? '' : 'No hay registros de uso para este código'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Función auxiliar para obtener fecha relativa
 */
function obtenerFechaRelativa($fecha) {
    $fecha_obj = new DateTime($fecha);
    $ahora = new DateTime();
    $diferencia = $ahora->diff($fecha_obj);
    
    if ($diferencia->y > 0) {
        return $diferencia->y . ' año' . ($diferencia->y > 1 ? 's' : '');
    } elseif ($diferencia->m > 0) {
        return $diferencia->m . ' mes' . ($diferencia->m > 1 ? 'es' : '');
    } elseif ($diferencia->d > 0) {
        return $diferencia->d . ' día' . ($diferencia->d > 1 ? 's' : '');
    } elseif ($diferencia->h > 0) {
        return $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
    } elseif ($diferencia->i > 0) {
        return $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
    } else {
        return 'Hace un momento';
    }
}

$conn->close();
?>