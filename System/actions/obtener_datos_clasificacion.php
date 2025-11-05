<?php
require_once '../bd/conexion.php';
header('Content-Type: application/json');

$apoderado_id = $_POST['apoderado_id'] ?? 0;

$response = ['success' => false];

if ($apoderado_id > 0) {
    // Obtener datos actuales del apoderado
    $sql = "SELECT 
        a.tipo_apoderado,
        a.nivel_compromiso,
        a.nivel_participacion,
        a.preferencia_contacto,
        CASE 
            WHEN a.nivel_compromiso = 'alto' AND a.nivel_participacion IN ('muy_activo', 'activo') THEN 'colaborador_estrella'
            WHEN a.nivel_compromiso = 'alto' THEN 'comprometido'
            WHEN a.nivel_participacion = 'muy_activo' THEN 'muy_participativo'
            WHEN a.nivel_compromiso = 'bajo' AND a.nivel_participacion = 'inactivo' THEN 'problematico'
            WHEN a.nivel_compromiso = 'bajo' THEN 'bajo_compromiso'
            WHEN a.nivel_participacion = 'inactivo' THEN 'inactivo'
            ELSE 'regular'
        END as categoria_apoderado,
        (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) as total_interacciones,
        (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.resultado = 'exitoso' AND i.activo = 1) as interacciones_exitosas,
        (SELECT MAX(DATE(i.created_at)) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) as ultima_interaccion,
        CASE 
            WHEN (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) = 0 THEN 0
            ELSE ROUND(
                ((SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.resultado = 'exitoso' AND i.activo = 1) * 100.0 / 
                 (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1)) * 0.6 +
                LEAST((SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1 
                       AND DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)) * 8, 40)
            , 2)
        END as puntuacion_compromiso
        FROM apoderados a
        WHERE a.id = ? AND a.activo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Calcular tasa de éxito
        $tasa_exito = 0;
        if ($row['total_interacciones'] > 0) {
            $tasa_exito = round(($row['interacciones_exitosas'] / $row['total_interacciones']) * 100, 2);
        }
        
        // Formatear última interacción
        $ultima_interaccion_texto = 'Sin registro';
        if ($row['ultima_interaccion']) {
            $fecha = new DateTime($row['ultima_interaccion']);
            $ultima_interaccion_texto = $fecha->format('d/m/Y');
        }
        
        $response = [
            'success' => true,
            'datos' => [
                'tipo_apoderado' => $row['tipo_apoderado'] ?? 'padre',
                'nivel_compromiso' => $row['nivel_compromiso'] ?? 'medio',
                'nivel_participacion' => $row['nivel_participacion'] ?? 'activo',
                'preferencia_contacto' => $row['preferencia_contacto'] ?? 'email',
                'categoria_apoderado' => $row['categoria_apoderado']
            ],
            'metricas' => [
                'total_interacciones' => (int)$row['total_interacciones'],
                'interacciones_exitosas' => (int)$row['interacciones_exitosas'],
                'tasa_exito' => $tasa_exito,
                'puntuacion_compromiso' => round($row['puntuacion_compromiso'], 2),
                'ultima_interaccion' => $ultima_interaccion_texto
            ]
        ];
    } else {
        $response['message'] = 'Apoderado no encontrado';
    }
    
    $stmt->close();
} else {
    $response['message'] = 'ID de apoderado inválido';
}

echo json_encode($response);
$conn->close();
?>