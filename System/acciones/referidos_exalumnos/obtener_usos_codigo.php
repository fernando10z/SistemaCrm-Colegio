<?php
session_start();
require_once '../../bd/conexion.php';

// Configurar header para JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no permitido'
    ]);
    exit();
}

// Verificar que exista el ID del código
if (empty($_POST['codigo_referido_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID del código de referido no especificado'
    ]);
    exit();
}

$codigo_referido_id = intval($_POST['codigo_referido_id']);

try {
    // Obtener información del código
    $sql_codigo = "SELECT codigo, limite_usos, usos_actuales FROM codigos_referido WHERE id = ?";
    $stmt_codigo = $conn->prepare($sql_codigo);
    
    if (!$stmt_codigo) {
        throw new Exception('Error al preparar consulta del código');
    }
    
    $stmt_codigo->bind_param("i", $codigo_referido_id);
    $stmt_codigo->execute();
    $result_codigo = $stmt_codigo->get_result();
    
    if ($result_codigo->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Código de referido no encontrado'
        ]);
        $stmt_codigo->close();
        exit();
    }
    
    $codigo_info = $result_codigo->fetch_assoc();
    $stmt_codigo->close();
    
    // Obtener usos del código con información detallada
    $sql_usos = "SELECT 
                    ur.id,
                    ur.fecha_uso,
                    ur.convertido,
                    ur.fecha_conversion,
                    ur.observaciones,
                    l.codigo_lead,
                    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as estudiante_nombre,
                    l.email as lead_email,
                    l.telefono as lead_telefono
                 FROM usos_referido ur
                 LEFT JOIN leads l ON ur.lead_id = l.id
                 WHERE ur.codigo_referido_id = ?
                 ORDER BY ur.fecha_uso DESC";
    
    $stmt_usos = $conn->prepare($sql_usos);
    
    if (!$stmt_usos) {
        throw new Exception('Error al preparar consulta de usos');
    }
    
    $stmt_usos->bind_param("i", $codigo_referido_id);
    $stmt_usos->execute();
    $result_usos = $stmt_usos->get_result();
    
    $usos = [];
    while ($row = $result_usos->fetch_assoc()) {
        $usos[] = [
            'id' => $row['id'],
            'fecha_uso' => $row['fecha_uso'],
            'convertido' => $row['convertido'],
            'fecha_conversion' => $row['fecha_conversion'],
            'observaciones' => $row['observaciones'],
            'lead_codigo' => $row['codigo_lead'],
            'estudiante_nombre' => $row['estudiante_nombre'],
            'lead_email' => $row['lead_email'],
            'lead_telefono' => $row['lead_telefono']
        ];
    }
    $stmt_usos->close();
    
    // Calcular estadísticas
    $total_usos = count($usos);
    $conversiones = 0;
    
    foreach ($usos as $uso) {
        if ($uso['convertido'] == 1) {
            $conversiones++;
        }
    }
    
    $tasa_conversion = $total_usos > 0 ? round(($conversiones / $total_usos) * 100, 1) : 0;
    
    // Calcular usos disponibles
    if ($codigo_info['limite_usos'] === null || $codigo_info['limite_usos'] === 0) {
        $usos_disponibles = '∞';
    } else {
        $usos_disponibles = max(0, $codigo_info['limite_usos'] - $codigo_info['usos_actuales']);
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'usos' => $usos,
        'estadisticas' => [
            'total_usos' => $total_usos,
            'conversiones' => $conversiones,
            'tasa_conversion' => $tasa_conversion,
            'usos_disponibles' => $usos_disponibles,
            'codigo' => $codigo_info['codigo'],
            'limite_usos' => $codigo_info['limite_usos'],
            'usos_actuales' => $codigo_info['usos_actuales']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usos: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>