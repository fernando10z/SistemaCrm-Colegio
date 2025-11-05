<?php
require_once '../bd/conexion.php';
header('Content-Type: application/json');

$apoderado_id = $_POST['apoderado_id'] ?? 0;

$response = ['success' => false];

if ($apoderado_id > 0) {
    // Algoritmo de evaluación de compromiso basado en interacciones
    $sql_evaluacion = "SELECT 
        COUNT(i.id) as total_interacciones,
        COUNT(CASE WHEN i.resultado = 'exitoso' THEN 1 END) as interacciones_exitosas,
        COUNT(CASE WHEN i.resultado = 'sin_respuesta' THEN 1 END) as sin_respuesta,
        COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as interacciones_recientes,
        COUNT(CASE WHEN i.requiere_seguimiento = 1 AND i.fecha_proximo_seguimiento < CURDATE() THEN 1 END) as seguimientos_vencidos
        FROM interacciones i 
        WHERE i.apoderado_id = ? AND i.activo = 1";
    
    $stmt = $conn->prepare($sql_evaluacion);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $resultado_eval = $stmt->get_result();
    $metricas = $resultado_eval->fetch_assoc();
    
    // Calcular puntuación automática
    $puntuacion = 0;
    $tasa_exito = 0;
    
    if ($metricas['total_interacciones'] > 0) {
        $tasa_exito = ($metricas['interacciones_exitosas'] / $metricas['total_interacciones']) * 100;
        $puntuacion += $tasa_exito * 0.4; // 40% peso
        $puntuacion += min(($metricas['interacciones_recientes'] / 5) * 30, 30); // 30% peso, máx 5 interacciones
        $puntuacion -= ($metricas['seguimientos_vencidos'] * 10); // Penalización
    }
    
    // Asegurar que la puntuación esté entre 0 y 100
    $puntuacion = max(0, min(100, $puntuacion));
    
    // Determinar nivel
    $nivel_calculado = $puntuacion >= 70 ? 'alto' : ($puntuacion >= 40 ? 'medio' : 'bajo');
    
    // Generar recomendaciones
    $recomendaciones = [];
    
    if ($metricas['total_interacciones'] === 0) {
        $recomendaciones[] = "No hay interacciones registradas. Iniciar contacto con el apoderado.";
    } elseif ($metricas['total_interacciones'] < 5) {
        $recomendaciones[] = "Pocas interacciones registradas. Se recomienda incrementar la frecuencia de contacto.";
    }
    
    if ($tasa_exito < 50) {
        $recomendaciones[] = "Tasa de éxito baja. Revisar estrategia de comunicación.";
    }
    
    if ($metricas['sin_respuesta'] > 3) {
        $recomendaciones[] = "Alto número de interacciones sin respuesta. Considerar cambiar canal de comunicación.";
    }
    
    if ($metricas['interacciones_recientes'] === 0) {
        $recomendaciones[] = "Sin interacciones recientes (90 días). Programar contacto urgente.";
    }
    
    if ($metricas['seguimientos_vencidos'] > 0) {
        $recomendaciones[] = "Tiene " . $metricas['seguimientos_vencidos'] . " seguimiento(s) vencido(s). Realizar seguimiento inmediato.";
    }
    
    if ($nivel_calculado === 'alto' && $tasa_exito >= 80) {
        $recomendaciones[] = "Apoderado comprometido. Considerar para roles de liderazgo o colaboración.";
    }
    
    if ($nivel_calculado === 'bajo') {
        $recomendaciones[] = "Nivel bajo de compromiso. Evaluar causas y diseñar plan de recuperación.";
    }
    
    $response = [
        'success' => true,
        'puntuacion' => round($puntuacion, 2),
        'nivel' => $nivel_calculado,
        'metricas' => [
            'total_interacciones' => (int)$metricas['total_interacciones'],
            'interacciones_exitosas' => (int)$metricas['interacciones_exitosas'],
            'sin_respuesta' => (int)$metricas['sin_respuesta'],
            'interacciones_recientes' => (int)$metricas['interacciones_recientes'],
            'seguimientos_vencidos' => (int)$metricas['seguimientos_vencidos'],
            'tasa_exito' => round($tasa_exito, 2)
        ],
        'recomendaciones' => $recomendaciones
    ];
    
    $stmt->close();
} else {
    $response['message'] = 'ID de apoderado inválido';
}

echo json_encode($response);
$conn->close();
?>