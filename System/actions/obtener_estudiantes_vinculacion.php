<?php
header('Content-Type: application/json; charset=utf-8');
include '../bd/conexion.php';

if (!isset($_POST['apoderado_id']) || empty($_POST['apoderado_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de apoderado no proporcionado']);
    exit;
}

$apoderado_id = intval($_POST['apoderado_id']);

try {
    // Obtener información del apoderado y su familia
    $apoderado_sql = "SELECT a.familia_id, f.codigo_familia, f.apellido_principal
                      FROM apoderados a
                      LEFT JOIN familias f ON a.familia_id = f.id
                      WHERE a.id = ? AND a.activo = 1
                      LIMIT 1";
    
    $stmt_apoderado = $conn->prepare($apoderado_sql);
    $stmt_apoderado->bind_param("i", $apoderado_id);
    $stmt_apoderado->execute();
    $result_apoderado = $stmt_apoderado->get_result();
    
    if ($result_apoderado->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Apoderado no encontrado']);
        exit;
    }
    
    $apoderado_data = $result_apoderado->fetch_assoc();
    $familia_id = $apoderado_data['familia_id'];
    
    // Verificar si la tabla apoderado_estudiante existe
    $check_table = $conn->query("SHOW TABLES LIKE 'apoderado_estudiante'");
    $tabla_existe = ($check_table->num_rows > 0);
    
    if ($tabla_existe) {
        // Si la tabla existe, usar LEFT JOIN para verificar vinculación
        $estudiantes_sql = "SELECT 
            e.id,
            e.codigo_estudiante,
            e.nombres,
            e.apellidos,
            e.fecha_nacimiento,
            e.seccion,
            e.estado_matricula,
            YEAR(CURDATE()) - YEAR(e.fecha_nacimiento) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(e.fecha_nacimiento, '%m%d')) as edad,
            IF(ae.id IS NOT NULL, 1, 0) as vinculado
        FROM estudiantes e
        LEFT JOIN apoderado_estudiante ae ON e.id = ae.estudiante_id AND ae.apoderado_id = ?
        WHERE e.familia_id = ? AND e.activo = 1
        ORDER BY e.apellidos, e.nombres";
        
        $stmt_estudiantes = $conn->prepare($estudiantes_sql);
        $stmt_estudiantes->bind_param("ii", $apoderado_id, $familia_id);
    } else {
        // Si la tabla NO existe, mostrar todos como no vinculados
        $estudiantes_sql = "SELECT 
            e.id,
            e.codigo_estudiante,
            e.nombres,
            e.apellidos,
            e.fecha_nacimiento,
            e.seccion,
            e.estado_matricula,
            YEAR(CURDATE()) - YEAR(e.fecha_nacimiento) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(e.fecha_nacimiento, '%m%d')) as edad,
            0 as vinculado
        FROM estudiantes e
        WHERE e.familia_id = ? AND e.activo = 1
        ORDER BY e.apellidos, e.nombres";
        
        $stmt_estudiantes = $conn->prepare($estudiantes_sql);
        $stmt_estudiantes->bind_param("i", $familia_id);
    }
    
    $stmt_estudiantes->execute();
    $result_estudiantes = $stmt_estudiantes->get_result();
    
    $estudiantes = [];
    while ($estudiante = $result_estudiantes->fetch_assoc()) {
        // Formatear fecha
        $estudiante['fecha_nacimiento_formato'] = $estudiante['fecha_nacimiento'] 
            ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) 
            : null;
        
        // Convertir vinculado a booleano
        $estudiante['vinculado'] = (bool)$estudiante['vinculado'];
        
        $estudiantes[] = $estudiante;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'familia_info' => [
                'familia_id' => $familia_id,
                'codigo_familia' => $apoderado_data['codigo_familia'],
                'apellido_principal' => $apoderado_data['apellido_principal']
            ],
            'estudiantes' => $estudiantes,
            'tabla_vinculacion_existe' => $tabla_existe,
            'debug' => [
                'apoderado_id' => $apoderado_id,
                'familia_id' => $familia_id,
                'total_estudiantes' => count($estudiantes)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

$conn->close();
?>