<?php
require_once '../bd/conexion.php';
header('Content-Type: application/json');

$apoderado_id = $_POST['apoderado_id'] ?? 0;

$response = ['success' => false];

if ($apoderado_id > 0) {
    // Calcular nivel de compromiso automáticamente
    $sql_compromiso = "SELECT 
        COUNT(i.id) as total_interacciones,
        COUNT(CASE WHEN i.resultado = 'exitoso' THEN 1 END) as interacciones_exitosas,
        COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as interacciones_recientes,
        COUNT(CASE WHEN i.requiere_seguimiento = 1 AND i.fecha_proximo_seguimiento < CURDATE() THEN 1 END) as seguimientos_vencidos
        FROM interacciones i 
        WHERE i.apoderado_id = ? AND i.activo = 1";
    
    $stmt = $conn->prepare($sql_compromiso);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $result_compromiso = $stmt->get_result();
    $metricas_compromiso = $result_compromiso->fetch_assoc();
    
    // Calcular puntuación de compromiso
    $puntuacion_compromiso = 0;
    if ($metricas_compromiso['total_interacciones'] > 0) {
        $tasa_exito = ($metricas_compromiso['interacciones_exitosas'] / $metricas_compromiso['total_interacciones']) * 100;
        $puntuacion_compromiso += $tasa_exito * 0.4;
        $puntuacion_compromiso += min(($metricas_compromiso['interacciones_recientes'] / 5) * 30, 30);
        $puntuacion_compromiso -= ($metricas_compromiso['seguimientos_vencidos'] * 10);
    }
    $puntuacion_compromiso = max(0, min(100, $puntuacion_compromiso));
    $nivel_compromiso = $puntuacion_compromiso >= 70 ? 'alto' : ($puntuacion_compromiso >= 40 ? 'medio' : 'bajo');
    
    // Calcular nivel de participación automáticamente
    $sql_participacion = "SELECT 
        COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as interacciones_mes,
        COUNT(CASE WHEN i.tipo_interaccion_id = 4 THEN 1 END) as reuniones_presenciales,
        MAX(DATE(i.created_at)) as ultima_interaccion
        FROM interacciones i 
        WHERE i.apoderado_id = ? AND i.activo = 1";
    
    $stmt2 = $conn->prepare($sql_participacion);
    $stmt2->bind_param("i", $apoderado_id);
    $stmt2->execute();
    $result_participacion = $stmt2->get_result();
    $metricas_participacion = $result_participacion->fetch_assoc();
    
    // Calcular puntuación de participación
    $puntuacion_participacion = 0;
    $puntuacion_participacion += min($metricas_participacion['interacciones_mes'] * 15, 60);
    $puntuacion_participacion += min($metricas_participacion['reuniones_presenciales'] * 20, 40);
    
    if ($metricas_participacion['ultima_interaccion']) {
        $dias_ultima = (strtotime('now') - strtotime($metricas_participacion['ultima_interaccion'])) / (60*60*24);
        if ($dias_ultima <= 7) $puntuacion_participacion += 20;
        elseif ($dias_ultima <= 30) $puntuacion_participacion += 10;
    }
    
    $puntuacion_participacion = max(0, min(100, $puntuacion_participacion));
    $nivel_participacion = $puntuacion_participacion >= 80 ? 'muy_activo' : 
                          ($puntuacion_participacion >= 60 ? 'activo' : 
                          ($puntuacion_participacion >= 30 ? 'poco_activo' : 'inactivo'));
    
    $response = [
        'success' => true,
        'nivel_compromiso' => $nivel_compromiso,
        'nivel_participacion' => $nivel_participacion,
        'puntuacion_compromiso' => round($puntuacion_compromiso, 2),
        'puntuacion_participacion' => round($puntuacion_participacion, 2)
    ];
    
    $stmt->close();
    $stmt2->close();
} else {
    $response['message'] = 'ID de apoderado inválido';
}

echo json_encode($response);
$conn->close();
?>