<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

try {
    // Consulta para obtener usuarios que han realizado cambios de estado
    $sql = "SELECT DISTINCT
        u.id,
        CONCAT(u.nombre, ' ', u.apellidos) as nombre,
        u.usuario,
        r.nombre as rol,
        COUNT(hel.id) as total_cambios,
        MAX(hel.created_at) as ultimo_cambio
    FROM usuarios u
    INNER JOIN historial_estados_lead hel ON u.id = hel.usuario_id
    LEFT JOIN roles r ON u.rol_id = r.id
    WHERE u.activo = 1
    GROUP BY u.id, u.nombre, u.apellidos, u.usuario, r.nombre
    ORDER BY total_cambios DESC, u.nombre ASC";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear fecha del último cambio
        $ultimo_cambio = $row['ultimo_cambio'] ? 
            date('d/m/Y H:i', strtotime($row['ultimo_cambio'])) : 'Sin cambios';
            
        $usuarios[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'usuario' => $row['usuario'],
            'rol' => $row['rol'] ?? 'Sin rol',
            'total_cambios' => $row['total_cambios'],
            'ultimo_cambio' => $ultimo_cambio,
            'ultimo_cambio_raw' => $row['ultimo_cambio']
        ];
    }
    
    // Agregar estadísticas adicionales
    $stats_sql = "SELECT 
        COUNT(DISTINCT hel.usuario_id) as usuarios_activos_total,
        COUNT(DISTINCT CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN hel.usuario_id END) as usuarios_activos_semana,
        COUNT(DISTINCT CASE WHEN hel.created_at >= CURDATE() THEN hel.usuario_id END) as usuarios_activos_hoy
    FROM historial_estados_lead hel
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $usuarios,
        'stats' => $stats,
        'total' => count($usuarios),
        'message' => 'Usuarios cargados correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usuarios: ' . $e->getMessage(),
        'data' => []
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        $conn->close();
    }
}
?>