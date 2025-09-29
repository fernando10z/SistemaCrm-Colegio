<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no válido'
    ]);
    exit();
}

// Verificar que se proporcionó el ID
if (!isset($_GET['codigo_id']) || empty($_GET['codigo_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de código no especificado'
    ]);
    exit();
}

$codigo_id = intval($_GET['codigo_id']);

try {
    // Obtener el código primero
    $stmt_codigo = $conn->prepare("SELECT codigo FROM codigos_referido WHERE id = ?");
    $stmt_codigo->bind_param("i", $codigo_id);
    $stmt_codigo->execute();
    $result_codigo = $stmt_codigo->get_result();
    
    if ($result_codigo->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Código no encontrado'
        ]);
        exit();
    }
    
    $codigo_data = $result_codigo->fetch_assoc();
    $stmt_codigo->close();
    
    // Obtener historial de cambios desde logs_acceso
    $stmt = $conn->prepare("SELECT 
                                la.id,
                                la.accion,
                                la.resultado,
                                la.detalles,
                                la.created_at,
                                u.nombre as usuario_nombre,
                                u.apellidos as usuario_apellidos
                            FROM logs_acceso la
                            LEFT JOIN usuarios u ON la.usuario_id = u.id
                            WHERE la.accion IN ('Activar Código Referido', 'Desactivar Código Referido')
                            AND JSON_EXTRACT(la.detalles, '$.detalle') LIKE ?
                            ORDER BY la.created_at DESC
                            LIMIT 10");
    
    $search_pattern = "%ID: {$codigo_id}%";
    $stmt->bind_param("s", $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        $detalles = json_decode($row['detalles'], true);
        
        // Determinar el estado
        $estado_nuevo = strpos($row['accion'], 'Activar') !== false ? 1 : 0;
        
        // Formatear fecha
        $fecha_obj = new DateTime($row['created_at']);
        $fecha_formato = $fecha_obj->format('d/m/Y H:i');
        
        // Extraer motivo si existe
        $motivo = '';
        if (isset($detalles['detalle']) && preg_match('/Motivo: (.+)/', $detalles['detalle'], $matches)) {
            $motivo = $matches[1];
        }
        
        $historial[] = [
            'id' => $row['id'],
            'accion' => $row['accion'],
            'accion_display' => $estado_nuevo ? 'Código Activado' : 'Código Desactivado',
            'estado_nuevo' => $estado_nuevo,
            'motivo' => $motivo,
            'usuario_nombre' => ($row['usuario_nombre'] ?? 'Sistema') . ' ' . ($row['usuario_apellidos'] ?? ''),
            'fecha' => $row['created_at'],
            'fecha_formato' => $fecha_formato,
            'resultado' => $row['resultado']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'historial' => $historial,
        'codigo' => $codigo_data['codigo'],
        'total_cambios' => count($historial)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el historial: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>