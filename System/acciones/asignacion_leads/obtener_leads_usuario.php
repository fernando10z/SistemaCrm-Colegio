<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
        throw new Exception('ID de usuario requerido');
    }

    $usuario_id = intval($_POST['usuario_id']);

    // Obtener leads del usuario para reasignación
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
        l.observaciones,
        
        -- Información del estado
        el.nombre as estado_nombre,
        el.color as estado_color,
        el.descripcion as estado_descripcion,
        el.es_final,
        
        -- Información del grado
        g.nombre as grado_nombre,
        ne.nombre as nivel_nombre,
        
        -- Información del canal
        cc.nombre as canal_nombre,
        cc.tipo as canal_tipo,
        
        -- Días asignado al usuario actual
        DATEDIFF(NOW(), l.created_at) as dias_asignado,
        
        -- Días desde última interacción
        CASE 
            WHEN l.fecha_ultima_interaccion IS NULL THEN DATEDIFF(NOW(), l.created_at)
            ELSE DATEDIFF(NOW(), l.fecha_ultima_interaccion)
        END as dias_sin_interaccion,
        
        -- Contar interacciones del usuario con este lead
        (SELECT COUNT(*) 
         FROM interacciones i 
         WHERE i.lead_id = l.id AND i.usuario_id = l.responsable_id
        ) as total_interacciones,
        
        -- Última interacción realizada
        (SELECT i.fecha_realizada 
         FROM interacciones i 
         WHERE i.lead_id = l.id AND i.usuario_id = l.responsable_id AND i.estado = 'realizado'
         ORDER BY i.fecha_realizada DESC 
         LIMIT 1
        ) as ultima_interaccion_fecha,
        
        -- Nivel de avance (basado en interacciones y estado)
        CASE 
            WHEN el.es_final = 1 THEN 'completado'
            WHEN (SELECT COUNT(*) FROM interacciones i WHERE i.lead_id = l.id AND i.usuario_id = l.responsable_id AND i.estado = 'realizado') >= 3 THEN 'avanzado'
            WHEN (SELECT COUNT(*) FROM interacciones i WHERE i.lead_id = l.id AND i.usuario_id = l.responsable_id AND i.estado = 'realizado') >= 1 THEN 'iniciado'
            ELSE 'sin_contacto'
        END as nivel_avance
        
    FROM leads l
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    WHERE l.responsable_id = ? 
      AND l.activo = 1
    ORDER BY 
        -- Priorizar por nivel de avance (menos avanzados primero)
        CASE nivel_avance 
            WHEN 'sin_contacto' THEN 1 
            WHEN 'iniciado' THEN 2 
            WHEN 'avanzado' THEN 3 
            WHEN 'completado' THEN 4 
        END,
        -- Luego por prioridad
        CASE l.prioridad 
            WHEN 'urgente' THEN 1 
            WHEN 'alta' THEN 2 
            WHEN 'media' THEN 3 
            WHEN 'baja' THEN 4 
        END,
        -- Finalmente por antigüedad
        l.created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leads = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Formatear información del lead
            $nombre_completo = htmlspecialchars($row['nombre_estudiante']);
            
            $estado_info = htmlspecialchars($row['estado_nombre']);
            $grado_info = '';
            if ($row['nivel_nombre'] && $row['grado_nombre']) {
                $grado_info = htmlspecialchars($row['nivel_nombre'] . ' - ' . $row['grado_nombre']);
            }
            
            // Determinar urgencia y recomendación para reasignación
            $recomendacion_reasignacion = '';
            $puede_reasignar = true;
            $motivo_bloqueo = '';
            
            // Evaluar si es recomendable reasignar
            if ($row['es_final'] == 1) {
                $puede_reasignar = false;
                $motivo_bloqueo = 'Lead en estado final';
            } elseif ($row['nivel_avance'] === 'avanzado' && $row['dias_sin_interaccion'] <= 3) {
                $recomendacion_reasignacion = 'No recomendado - Lead con progreso activo';
            } elseif ($row['nivel_avance'] === 'sin_contacto' && $row['dias_asignado'] > 7) {
                $recomendacion_reasignacion = 'Altamente recomendado - Sin contacto por ' . $row['dias_asignado'] . ' días';
            } elseif ($row['dias_sin_interaccion'] > 15) {
                $recomendacion_reasignacion = 'Recomendado - Sin actividad por ' . $row['dias_sin_interaccion'] . ' días';
            } elseif ($row['prioridad'] === 'urgente' && $row['dias_sin_interaccion'] > 2) {
                $recomendacion_reasignacion = 'Urgente - Lead prioritario sin atención';
            } else {
                $recomendacion_reasignacion = 'Evaluar - En proceso normal';
            }
            
            // Determinar valor del lead para priorizar reasignación
            $valor_lead = 0;
            $valor_lead += ($row['puntaje_interes'] ?? 0) * 2; // Interés vale doble
            $valor_lead += $row['prioridad'] === 'urgente' ? 20 : 0;
            $valor_lead += $row['prioridad'] === 'alta' ? 15 : 0;
            $valor_lead += $row['prioridad'] === 'media' ? 10 : 0;
            $valor_lead += $row['total_interacciones'] * 5; // Interacciones previas suman valor
            
            // Penalizar leads sin actividad reciente
            if ($row['dias_sin_interaccion'] > 7) {
                $valor_lead -= $row['dias_sin_interaccion'];
            }
            
            $leads[] = [
                'id' => $row['id'],
                'codigo' => $row['codigo_lead'],
                'nombre' => $nombre_completo,
                'estado' => $estado_info,
                'estado_color' => $row['estado_color'],
                'grado' => $grado_info,
                'canal' => htmlspecialchars($row['canal_nombre'] ?? 'Sin canal'),
                'prioridad' => $row['prioridad'],
                'puntaje_interes' => (int)($row['puntaje_interes'] ?? 0),
                'telefono' => $row['telefono'],
                'email' => $row['email'],
                'dias_asignado' => (int)$row['dias_asignado'],
                'dias_sin_interaccion' => (int)$row['dias_sin_interaccion'],
                'total_interacciones' => (int)$row['total_interacciones'],
                'nivel_avance' => $row['nivel_avance'],
                'puede_reasignar' => $puede_reasignar,
                'motivo_bloqueo' => $motivo_bloqueo,
                'recomendacion_reasignacion' => $recomendacion_reasignacion,
                'valor_lead' => $valor_lead,
                'proxima_accion_fecha' => $row['proxima_accion_fecha'],
                'proxima_accion_descripcion' => $row['proxima_accion_descripcion'],
                'ultima_interaccion' => $row['ultima_interaccion_fecha'] ? 
                    date('d/m/Y', strtotime($row['ultima_interaccion_fecha'])) : 'Nunca',
                'fecha_asignacion' => date('d/m/Y', strtotime($row['created_at']))
            ];
        }
    }
    
    // Estadísticas para la reasignación
    $stats_reasignacion = [
        'total_leads' => count($leads),
        'pueden_reasignar' => count(array_filter($leads, function($lead) { return $lead['puede_reasignar']; })),
        'recomendados_reasignar' => count(array_filter($leads, function($lead) { 
            return strpos($lead['recomendacion_reasignacion'], 'recomendado') !== false; 
        })),
        'sin_contacto' => count(array_filter($leads, function($lead) { return $lead['nivel_avance'] === 'sin_contacto'; })),
        'urgentes_desatendidos' => count(array_filter($leads, function($lead) { 
            return $lead['prioridad'] === 'urgente' && $lead['dias_sin_interaccion'] > 2; 
        })),
        'inactivos_15_dias' => count(array_filter($leads, function($lead) { 
            return $lead['dias_sin_interaccion'] > 15; 
        }))
    ];
    
    // Generar recomendaciones automáticas
    $recomendaciones = [];
    
    if ($stats_reasignacion['sin_contacto'] > 5) {
        $recomendaciones[] = "Hay {$stats_reasignacion['sin_contacto']} leads sin contacto inicial. Considera reasignar los más antiguos.";
    }
    
    if ($stats_reasignacion['urgentes_desatendidos'] > 0) {
        $recomendaciones[] = "¡ATENCIÓN! {$stats_reasignacion['urgentes_desatendidos']} leads urgentes sin atención reciente.";
    }
    
    if ($stats_reasignacion['inactivos_15_dias'] > 3) {
        $recomendaciones[] = "Hay {$stats_reasignacion['inactivos_15_dias']} leads inactivos por más de 15 días.";
    }
    
    if ($stats_reasignacion['total_leads'] > 30) {
        $recomendaciones[] = "Carga alta detectada ({$stats_reasignacion['total_leads']} leads). Considera redistribuir algunos leads.";
    }

    echo json_encode([
        'success' => true,
        'data' => $leads,
        'total' => count($leads),
        'estadisticas' => $stats_reasignacion,
        'recomendaciones' => $recomendaciones
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>