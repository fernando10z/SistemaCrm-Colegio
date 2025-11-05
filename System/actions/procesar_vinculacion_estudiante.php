<?php
header('Content-Type: application/json; charset=utf-8');
include '../bd/conexion.php';

if (!isset($_POST['apoderado_id']) || !isset($_POST['estudiante_id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$apoderado_id = intval($_POST['apoderado_id']);
$estudiante_id = intval($_POST['estudiante_id']);
$accion = trim($_POST['accion']);

try {
    // Verificar que el apoderado y el estudiante existen y están activos
    $check_sql = "SELECT 
        a.id as apoderado_existe,
        e.id as estudiante_existe,
        a.familia_id as apoderado_familia,
        e.familia_id as estudiante_familia
    FROM apoderados a
    CROSS JOIN estudiantes e
    WHERE a.id = ? AND e.id = ? AND a.activo = 1 AND e.activo = 1";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $apoderado_id, $estudiante_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Apoderado o estudiante no encontrado']);
        exit;
    }
    
    $check_data = $check_result->fetch_assoc();
    
    // Verificar que pertenecen a la misma familia
    if ($check_data['apoderado_familia'] !== $check_data['estudiante_familia']) {
        echo json_encode(['success' => false, 'message' => 'El estudiante y el apoderado no pertenecen a la misma familia']);
        exit;
    }
    
    if ($accion === 'vincular') {
        // Insertar vinculación en la tabla intermedia
        $vincular_sql = "INSERT INTO apoderado_estudiante (apoderado_id, estudiante_id) 
                         VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
        
        $vincular_stmt = $conn->prepare($vincular_sql);
        $vincular_stmt->bind_param("ii", $apoderado_id, $estudiante_id);
        
        if ($vincular_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Estudiante vinculado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al vincular: ' . $vincular_stmt->error
            ]);
        }
        
    } elseif ($accion === 'desvincular') {
        // Eliminar vinculación de la tabla intermedia
        $desvincular_sql = "DELETE FROM apoderado_estudiante 
                            WHERE apoderado_id = ? AND estudiante_id = ?";
        
        $desvincular_stmt = $conn->prepare($desvincular_sql);
        $desvincular_stmt->bind_param("ii", $apoderado_id, $estudiante_id);
        
        if ($desvincular_stmt->execute()) {
            if ($desvincular_stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estudiante desvinculado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se encontró la vinculación para eliminar'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al desvincular: ' . $desvincular_stmt->error
            ]);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>