<?php
require_once '../bd/conexion.php';
header('Content-Type: application/json');

$apoderado_id = $_POST['apoderado_id'] ?? 0;

$response = ['success' => false];

if ($apoderado_id > 0) {
    // Algoritmo de medición de participación
    $sql_medicion = "SELECT 
        COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as interacciones_mes,
        COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as interacciones_semana,
        COALESCE(AVG(i.duracion_minutos), 0) as duracion_promedio,
        COUNT(CASE WHEN i.tipo_interaccion_id = 4 THEN 1 END) as reuniones_presenciales,
        MAX(DATE(i.created_at)) as ultima_interaccion,
        DATEDIFF(CURDATE(), MAX(DATE(i.created_at))) as dias_ultima_interaccion
        FROM interacciones i 
        WHERE i.apoderado_id = ? AND i.activo = 1";
    
    $stmt = $conn->prepare($sql_medicion);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $resultado_med = $stmt->get_result();
    $metricas = $resultado_med->fetch_assoc();
    
    // Calcular puntuación de participación
    $puntuacion = 0;
    $puntuacion += min($metricas['interacciones_mes'] * 15, 60); // Máx 4 interacciones/mes
    $puntuacion += min($metricas['reuniones_presenciales'] * 20, 40); // Máx 2 reuniones
    
    if ($metricas['ultima_interaccion']) {
        $dias_ultima = $metricas['dias_ultima_interaccion'];
        if ($dias_ultima <= 7) $puntuacion += 20;
        elseif ($dias_ultima <= 30) $puntuacion += 10;
    }
    
    // Asegurar que la puntuación esté entre 0 y 100
    $puntuacion = max(0, min(100, $puntuacion));
    
    // Determinar nivel
    $nivel_calculado = $puntuacion >= 80 ? 'muy_activo' : 
                      ($puntuacion >= 60 ? 'activo' : 
                      ($puntuacion >= 30 ? 'poco_activo' : 'inactivo'));
    
    // Obtener timeline de actividad (últimas 5 interacciones)
    $sql_timeline = "SELECT 
        DATE_FORMAT(i.fecha_realizada, '%d/%m/%Y') as fecha,
        ti.nombre as tipo,
        i.asunto as descripcion
        FROM interacciones i
        LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
        WHERE i.apoderado_id = ? AND i.activo = 1
        ORDER BY i.fecha_realizada DESC
        LIMIT 5";
    
    $stmt_timeline = $conn->prepare($sql_timeline);
    $stmt_timeline->bind_param("i", $apoderado_id);
    $stmt_timeline->execute();
    $resultado_timeline = $stmt_timeline->get_result();
    
    $timeline = [];
    while ($row = $resultado_timeline->fetch_assoc()) {
        $timeline[] = [
            'fecha' => $row['fecha'],
            'tipo' => $row['tipo'] ?? 'General',
            'descripcion' => substr($row['descripcion'], 0, 50) . (strlen($row['descripcion']) > 50 ? '...' : '')
        ];
    }
    
    // Generar recomendaciones
    $recomendaciones = [];
    
    if ($metricas['interacciones_mes'] === 0) {
        $recomendaciones[] = "Sin interacciones este mes. Contactar urgentemente al apoderado.";
    } elseif ($metricas['interacciones_mes'] === 1) {
        $recomendaciones[] = "Solo 1 interacción este mes. Incrementar frecuencia de contacto.";
    }
    
    if ($metricas['interacciones_semana'] === 0 && $metricas['dias_ultima_interaccion'] > 7) {
        $recomendaciones[] = "Sin actividad esta semana. Programar contacto inmediato.";
    }
    
    if ($metricas['reuniones_presenciales'] === 0) {
        $recomendaciones[] = "Sin reuniones presenciales. Considerar invitar a evento o reunión.";
    } elseif ($metricas['reuniones_presenciales'] >= 2) {
        $recomendaciones[] = "Alta participación presencial. Apoderado comprometido con asistencia física.";
    }
    
    if ($metricas['duracion_promedio'] > 0 && $metricas['duracion_promedio'] < 5) {
        $recomendaciones[] = "Duración promedio baja (" . round($metricas['duracion_promedio'], 1) . " min). Interacciones muy breves.";
    } elseif ($metricas['duracion_promedio'] >= 30) {
        $recomendaciones[] = "Duración promedio alta (" . round($metricas['duracion_promedio'], 1) . " min). Apoderado dedicado en conversaciones.";
    }
    
    if ($metricas['dias_ultima_interaccion'] > 30) {
        $recomendaciones[] = "Última interacción hace " . $metricas['dias_ultima_interaccion'] . " días. URGENTE: Recuperar contacto.";
    } elseif ($metricas['dias_ultima_interaccion'] <= 7) {
        $recomendaciones[] = "Actividad muy reciente. Mantener ritmo de contacto actual.";
    }
    
    if ($nivel_calculado === 'muy_activo') {
        $recomendaciones[] = "Apoderado muy activo. Considerar para roles de liderazgo en comunidad escolar.";
    } elseif ($nivel_calculado === 'inactivo') {
        $recomendaciones[] = "Nivel inactivo crítico. Evaluar causas y diseñar estrategia de recuperación.";
    }
    
    // Formatear última interacción
    $ultima_interaccion_texto = 'Sin registro';
    if ($metricas['ultima_interaccion']) {
        $fecha = new DateTime($metricas['ultima_interaccion']);
        $ultima_interaccion_texto = $fecha->format('d/m/Y');
    }
    
    $response = [
        'success' => true,
        'puntuacion' => round($puntuacion, 2),
        'nivel' => $nivel_calculado,
        'metricas' => [
            'interacciones_mes' => (int)$metricas['interacciones_mes'],
            'interacciones_semana' => (int)$metricas['interacciones_semana'],
            'duracion_promedio' => round($metricas['duracion_promedio'], 1),
            'reuniones_presenciales' => (int)$metricas['reuniones_presenciales'],
            'ultima_interaccion' => $ultima_interaccion_texto,
            'dias_ultima_interaccion' => $metricas['dias_ultima_interaccion'] ?? 'N/A'
        ],
        'timeline' => $timeline,
        'recomendaciones' => $recomendaciones
    ];
    
    $stmt->close();
    $stmt_timeline->close();
} else {
    $response['message'] = 'ID de apoderado inválido';
}

echo json_encode($response);
$conn->close();
?>