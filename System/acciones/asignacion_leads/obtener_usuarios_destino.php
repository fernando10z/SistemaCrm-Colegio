<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_origen_id']) || empty($_POST['usuario_origen_id'])) {
        throw new Exception('ID de usuario origen requerido');
    }

    $usuario_origen_id = intval($_POST['usuario_origen_id']);

    // Obtener usuarios destino disponibles (excluyendo el usuario origen)
    $sql = "SELECT 
        u.id,
        u.usuario,
        u.email,
        u.nombre,
        u.apellidos,
        u.telefono,
        u.rol_id,
        r.nombre as nombre_rol,
        u.ultimo_acceso,
        u.activo,
        CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
        
        -- Estadísticas de carga actual
        COUNT(l.id) as total_leads_asignados,
        COUNT(CASE WHEN l.activo = 1 THEN 1 END) as leads_activos,
        COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
        COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as tareas_hoy,
        COUNT(CASE WHEN l.proxima_accion_fecha < CURDATE() AND l.proxima_accion_fecha IS NOT NULL THEN 1 END) as tareas_vencidas,
        
        -- Estadísticas de rendimiento
        COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) as leads_convertidos,
        AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
        
        -- Carga de trabajo calculada
        CASE 
            WHEN r.nombre = 'Coordinador Marketing' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 50.0 * 100
            WHEN r.nombre = 'Tutor' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 30.0 * 100
            ELSE COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 25.0 * 100
        END as porcentaje_carga,
        
        -- Capacidad disponible
        CASE 
            WHEN r.nombre = 'Coordinador Marketing' THEN 50 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)
            WHEN r.nombre = 'Tutor' THEN 30 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)
            ELSE 25 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)
        END as capacidad_disponible,
        
        -- Última actividad
        MAX(l.fecha_ultima_interaccion) as ultima_interaccion,
        MAX(l.updated_at) as ultima_actividad,
        
        -- Especialización (basada en grados más manejados)
        (SELECT GROUP_CONCAT(DISTINCT CONCAT(ne.nombre, '-', g.nombre) SEPARATOR ', ')
         FROM leads l2 
         LEFT JOIN grados g ON l2.grado_interes_id = g.id
         LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
         WHERE l2.responsable_id = u.id 
           AND l2.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
         LIMIT 3
        ) as especializacion_grados,
        
        -- Puntuación de idoneidad (para ordenar recomendaciones)
        (
            -- Base: capacidad disponible (más disponibilidad = mejor)
            CASE 
                WHEN r.nombre = 'Coordinador Marketing' THEN (50 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)) * 2
                WHEN r.nombre = 'Tutor' THEN (30 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)) * 2
                ELSE (25 - COUNT(CASE WHEN l.activo = 1 THEN 1 END)) * 2
            END +
            -- Rendimiento: tasa de conversión
            (COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) * 100.0 / NULLIF(COUNT(l.id), 0)) +
            -- Actividad reciente
            CASE 
                WHEN MAX(l.updated_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 20
                WHEN MAX(l.updated_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 10
                ELSE 0
            END +
            -- Penalizar sobrecarga
            CASE 
                WHEN (CASE 
                    WHEN r.nombre = 'Coordinador Marketing' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 50.0 * 100
                    WHEN r.nombre = 'Tutor' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 30.0 * 100
                    ELSE COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 25.0 * 100
                END) > 80 THEN -50
                WHEN (CASE 
                    WHEN r.nombre = 'Coordinador Marketing' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 50.0 * 100
                    WHEN r.nombre = 'Tutor' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 30.0 * 100
                    ELSE COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 25.0 * 100
                END) > 60 THEN -20
                ELSE 0
            END
        ) as puntuacion_idoneidad
        
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN leads l ON u.id = l.responsable_id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    WHERE u.activo = 1 
      AND u.id != ?  -- Excluir usuario origen
      AND r.nombre IN ('Coordinador Marketing', 'Tutor', 'Administrador')  -- Solo roles que pueden manejar leads
    GROUP BY u.id, u.usuario, u.email, u.nombre, u.apellidos, u.telefono, u.rol_id, r.nombre, u.ultimo_acceso, u.activo
    ORDER BY puntuacion_idoneidad DESC, porcentaje_carga ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_origen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios_destino = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Calcular tasa de conversión
            $tasa_conversion = $row['total_leads_asignados'] > 0 ? 
                round(($row['leads_convertidos'] / $row['total_leads_asignados']) * 100, 1) : 0;
            
            // Determinar nivel de carga
            $porcentaje_carga = round($row['porcentaje_carga'], 1);
            $nivel_carga = '';
            $color_carga = '';
            $disponibilidad = '';
            
            if ($porcentaje_carga <= 30) {
                $nivel_carga = 'Baja';
                $color_carga = 'success';
                $disponibilidad = 'Muy disponible';
            } elseif ($porcentaje_carga <= 60) {
                $nivel_carga = 'Media';
                $color_carga = 'info';
                $disponibilidad = 'Disponible';
            } elseif ($porcentaje_carga <= 80) {
                $nivel_carga = 'Alta';
                $color_carga = 'warning';
                $disponibilidad = 'Ocupado';
            } else {
                $nivel_carga = 'Crítica';
                $color_carga = 'danger';
                $disponibilidad = 'Sobrecargado';
            }
            
            // Determinar recomendación
            $recomendacion = '';
            $puntuacion_idoneidad = floatval($row['puntuacion_idoneidad']);
            
            if ($porcentaje_carga > 90) {
                $recomendacion = 'No recomendado - Sobrecargado';
            } elseif ($porcentaje_carga > 80) {
                $recomendacion = 'Con precaución - Carga alta';
            } elseif ($tasa_conversion >= 15 && $porcentaje_carga <= 60) {
                $recomendacion = 'Altamente recomendado - Buen rendimiento';
            } elseif ($porcentaje_carga <= 30) {
                $recomendacion = 'Recomendado - Muy disponible';
            } elseif ($tasa_conversion >= 10) {
                $recomendacion = 'Recomendado - Rendimiento aceptable';
            } else {
                $recomendacion = 'Evaluar - Rendimiento bajo';
            }
            
            // Formatear especialización
            $especializacion = $row['especializacion_grados'] ? 
                htmlspecialchars($row['especializacion_grados']) : 'Sin especialización definida';
            
            // Formatear última actividad
            $ultima_actividad = $row['ultima_actividad'] ? 
                'Hace ' . ceil((strtotime('now') - strtotime($row['ultima_actividad'])) / 86400) . ' días' : 'Sin actividad reciente';
            
            $usuarios_destino[] = [
                'id' => $row['id'],
                'usuario' => $row['usuario'],
                'nombre_completo' => htmlspecialchars($row['nombre_completo']),
                'email' => htmlspecialchars($row['email']),
                'telefono' => $row['telefono'],
                'rol_nombre' => htmlspecialchars($row['nombre_rol']),
                'leads_activos' => (int)$row['leads_activos'],
                'leads_urgentes' => (int)$row['leads_urgentes'],
                'tareas_hoy' => (int)$row['tareas_hoy'],
                'tareas_vencidas' => (int)$row['tareas_vencidas'],
                'porcentaje_carga' => $porcentaje_carga,
                'nivel_carga' => $nivel_carga,
                'color_carga' => $color_carga,
                'disponibilidad' => $disponibilidad,
                'capacidad_disponible' => max(0, (int)$row['capacidad_disponible']),
                'tasa_conversion' => $tasa_conversion,
                'promedio_interes' => round($row['promedio_interes'], 1),
                'especializacion' => $especializacion,
                'ultima_actividad' => $ultima_actividad,
                'recomendacion' => $recomendacion,
                'puntuacion_idoneidad' => $puntuacion_idoneidad,
                'ultimo_acceso' => $row['ultimo_acceso'] ? 
                    date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca'
            ];
        }
    }
    
    // Estadísticas del equipo para contexto
    $stats_equipo = [
        'total_usuarios_disponibles' => count($usuarios_destino),
        'usuarios_disponibles' => count(array_filter($usuarios_destino, function($u) { return $u['porcentaje_carga'] <= 60; })),
        'usuarios_sobrecargados' => count(array_filter($usuarios_destino, function($u) { return $u['porcentaje_carga'] > 80; })),
        'promedio_carga_equipo' => count($usuarios_destino) > 0 ? 
            round(array_sum(array_column($usuarios_destino, 'porcentaje_carga')) / count($usuarios_destino), 1) : 0,
        'mejor_performer' => !empty($usuarios_destino) ? 
            max(array_column($usuarios_destino, 'tasa_conversion')) : 0
    ];
    
    // Generar recomendaciones automáticas
    $recomendaciones_sistema = [];
    
    if ($stats_equipo['usuarios_sobrecargados'] > 0) {
        $recomendaciones_sistema[] = "⚠️ {$stats_equipo['usuarios_sobrecargados']} usuario(s) con sobrecarga. Evitar asignar más leads.";
    }
    
    if ($stats_equipo['usuarios_disponibles'] == 0) {
        $recomendaciones_sistema[] = "🚨 No hay usuarios con carga baja disponibles. Considerar redistribución general.";
    }
    
    if ($stats_equipo['promedio_carga_equipo'] > 75) {
        $recomendaciones_sistema[] = "📊 Carga promedio del equipo alta ({$stats_equipo['promedio_carga_equipo']}%). Revisar distribución global.";
    }
    
    if (count($usuarios_destino) <= 2) {
        $recomendaciones_sistema[] = "👥 Pocos usuarios disponibles para reasignación. Considerar ampliar el equipo.";
    }

    echo json_encode([
        'success' => true,
        'data' => $usuarios_destino,
        'total' => count($usuarios_destino),
        'estadisticas_equipo' => $stats_equipo,
        'recomendaciones_sistema' => $recomendaciones_sistema
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>