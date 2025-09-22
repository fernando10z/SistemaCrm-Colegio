<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    // Obtener leads sin asignar con información relevante
    $sql = "SELECT 
        l.id,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto,
        l.telefono,
        l.whatsapp,
        l.email,
        l.prioridad,
        l.puntaje_interes,
        l.created_at,
        l.fecha_ultima_interaccion,
        l.proxima_accion_fecha,
        l.proxima_accion_descripcion,
        
        -- Información del estado
        el.nombre as estado_nombre,
        el.color as estado_color,
        
        -- Información del grado
        g.nombre as grado_nombre,
        ne.nombre as nivel_nombre,
        
        -- Información del canal
        cc.nombre as canal_nombre,
        cc.tipo as canal_tipo,
        
        -- Días sin asignar
        DATEDIFF(NOW(), l.created_at) as dias_sin_asignar,
        
        -- Urgencia calculada
        CASE 
            WHEN l.prioridad = 'urgente' THEN 4
            WHEN l.prioridad = 'alta' THEN 3
            WHEN l.prioridad = 'media' THEN 2
            WHEN l.prioridad = 'baja' THEN 1
            ELSE 1
        END as nivel_urgencia
        
    FROM leads l
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    WHERE l.responsable_id IS NULL 
      AND l.activo = 1
    ORDER BY 
        nivel_urgencia DESC,
        CASE WHEN l.proxima_accion_fecha IS NOT NULL AND l.proxima_accion_fecha <= CURDATE() THEN 0 ELSE 1 END,
        l.created_at ASC";

    $result = $conn->query($sql);
    
    $leads = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Formatear información del lead
            $nombre_completo = htmlspecialchars($row['nombre_estudiante']);
            if ($row['nombre_contacto'] && $row['nombre_contacto'] !== $row['nombre_estudiante']) {
                $nombre_completo .= ' (Contacto: ' . htmlspecialchars($row['nombre_contacto']) . ')';
            }
            
            $estado_info = htmlspecialchars($row['estado_nombre']);
            $grado_info = '';
            if ($row['nivel_nombre'] && $row['grado_nombre']) {
                $grado_info = htmlspecialchars($row['nivel_nombre'] . ' - ' . $row['grado_nombre']);
            }
            
            $canal_info = htmlspecialchars($row['canal_nombre'] ?? 'Sin canal');
            
            // Determinar urgencia visual
            $urgencia_texto = '';
            $urgencia_clase = '';
            switch($row['prioridad']) {
                case 'urgente':
                    $urgencia_texto = 'URGENTE';
                    $urgencia_clase = 'text-danger fw-bold';
                    break;
                case 'alta':
                    $urgencia_texto = 'ALTA';
                    $urgencia_clase = 'text-warning fw-bold';
                    break;
                case 'media':
                    $urgencia_texto = 'Media';
                    $urgencia_clase = 'text-info';
                    break;
                default:
                    $urgencia_texto = 'Baja';
                    $urgencia_clase = 'text-muted';
            }
            
            // Calcular días sin asignar
            $dias_sin_asignar = (int)$row['dias_sin_asignar'];
            $tiempo_texto = '';
            if ($dias_sin_asignar == 0) {
                $tiempo_texto = 'Hoy';
            } elseif ($dias_sin_asignar == 1) {
                $tiempo_texto = 'Ayer';
            } else {
                $tiempo_texto = "Hace {$dias_sin_asignar} días";
            }
            
            // Verificar si tiene acción vencida
            $accion_vencida = false;
            if ($row['proxima_accion_fecha'] && $row['proxima_accion_fecha'] < date('Y-m-d')) {
                $accion_vencida = true;
            }
            
            $leads[] = [
                'id' => $row['id'],
                'codigo' => $row['codigo_lead'],
                'nombre' => $nombre_completo,
                'estado' => $estado_info,
                'grado' => $grado_info,
                'canal' => $canal_info,
                'prioridad' => $row['prioridad'],
                'prioridad_texto' => $urgencia_texto,
                'prioridad_clase' => $urgencia_clase,
                'puntaje_interes' => (int)($row['puntaje_interes'] ?? 0),
                'telefono' => $row['telefono'],
                'email' => $row['email'],
                'dias_sin_asignar' => $dias_sin_asignar,
                'tiempo_texto' => $tiempo_texto,
                'accion_vencida' => $accion_vencida,
                'fecha_creacion' => date('d/m/Y H:i', strtotime($row['created_at'])),
                'nivel_urgencia' => (int)$row['nivel_urgencia']
            ];
        }
    }
    
    // Estadísticas adicionales
    $stats_sql = "SELECT 
        COUNT(*) as total_sin_asignar,
        COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as urgentes,
        COUNT(CASE WHEN l.prioridad = 'alta' THEN 1 END) as altas,
        COUNT(CASE WHEN l.created_at >= CURDATE() THEN 1 END) as hoy,
        COUNT(CASE WHEN l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND l.created_at < CURDATE() THEN 1 END) as ayer,
        COUNT(CASE WHEN l.proxima_accion_fecha IS NOT NULL AND l.proxima_accion_fecha < CURDATE() THEN 1 END) as acciones_vencidas,
        AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
        MAX(DATEDIFF(NOW(), l.created_at)) as dias_mas_antiguo
    FROM leads l
    WHERE l.responsable_id IS NULL 
      AND l.activo = 1";
    
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $leads,
        'total' => count($leads),
        'estadisticas' => [
            'total_sin_asignar' => (int)$stats['total_sin_asignar'],
            'urgentes' => (int)$stats['urgentes'],
            'altas' => (int)$stats['altas'],
            'hoy' => (int)$stats['hoy'],
            'ayer' => (int)$stats['ayer'],
            'acciones_vencidas' => (int)$stats['acciones_vencidas'],
            'promedio_interes' => round($stats['promedio_interes'], 1),
            'dias_mas_antiguo' => (int)$stats['dias_mas_antiguo']
        ],
        'recomendacion' => generarRecomendacion($stats)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generarRecomendacion($stats) {
    $recomendaciones = [];
    
    if ($stats['urgentes'] > 0) {
        $recomendaciones[] = "¡Atención! Hay {$stats['urgentes']} lead(s) marcados como URGENTES que requieren asignación inmediata.";
    }
    
    if ($stats['acciones_vencidas'] > 0) {
        $recomendaciones[] = "Hay {$stats['acciones_vencidas']} lead(s) con acciones de seguimiento vencidas.";
    }
    
    if ($stats['dias_mas_antiguo'] > 7) {
        $recomendaciones[] = "El lead más antiguo tiene {$stats['dias_mas_antiguo']} días sin asignar. Se recomienda priorizar leads antiguos.";
    }
    
    if ($stats['total_sin_asignar'] > 20) {
        $recomendaciones[] = "Alto volumen de leads sin asignar ({$stats['total_sin_asignar']}). Considera usar distribución automática.";
    }
    
    if (empty($recomendaciones)) {
        return "Estado normal. Se puede proceder con la asignación manual o automática.";
    }
    
    return implode(" ", $recomendaciones);
}

$conn->close();
?>