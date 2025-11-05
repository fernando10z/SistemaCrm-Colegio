<?php
include '../bd/conexion.php';
header('Content-Type: application/json');

$criterio = $_POST['criterio'] ?? '';

$response = ['success' => false, 'data' => []];

if ($criterio === 'compromiso_participacion') {
    $sql = "SELECT 
        CONCAT(nivel_compromiso, '_', nivel_participacion) as segmento,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 1) as porcentaje
        FROM apoderados 
        WHERE activo = 1 
        GROUP BY nivel_compromiso, nivel_participacion
        ORDER BY cantidad DESC
        LIMIT 10";
    
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        $response['data'][] = [
            'nombre' => ucwords(str_replace('_', ' + ', $row['segmento'])),
            'cantidad' => $row['cantidad'],
            'porcentaje' => $row['porcentaje'],
            'clase' => 'colaborador'
        ];
    }
    
    $response['success'] = true;
    
} elseif ($criterio === 'nivel_socioeconomico') {
    $sql = "SELECT 
        f.nivel_socioeconomico as segmento,
        COUNT(a.id) as cantidad,
        ROUND(COUNT(a.id) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 1) as porcentaje
        FROM apoderados a
        LEFT JOIN familias f ON a.familia_id = f.id
        WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
        GROUP BY f.nivel_socioeconomico
        ORDER BY cantidad DESC";
    
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        $response['data'][] = [
            'nombre' => 'Nivel ' . strtoupper($row['segmento']),
            'cantidad' => $row['cantidad'],
            'porcentaje' => $row['porcentaje'],
            'clase' => 'regular'
        ];
    }
    
    $response['success'] = true;
    
} elseif ($criterio === 'problematicos_colaboradores') {
    $sql = "SELECT 
        CASE 
            WHEN nivel_compromiso = 'alto' AND nivel_participacion IN ('muy_activo', 'activo') THEN 'Colaborador Estrella'
            WHEN nivel_compromiso = 'bajo' AND nivel_participacion = 'inactivo' THEN 'Problemático'
            ELSE 'Regular'
        END as segmento,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 1) as porcentaje
        FROM apoderados
        WHERE activo = 1
        GROUP BY segmento
        ORDER BY cantidad DESC";
    
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        $clase = $row['segmento'] === 'Colaborador Estrella' ? 'colaborador' : 
                 ($row['segmento'] === 'Problemático' ? 'problematico' : 'regular');
        
        $response['data'][] = [
            'nombre' => $row['segmento'],
            'cantidad' => $row['cantidad'],
            'porcentaje' => $row['porcentaje'],
            'clase' => $clase
        ];
    }
    
    $response['success'] = true;
}

echo json_encode($response);
$conn->close();
?>