<?php
// Deshabilitar errores visibles
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Limpiar cualquier salida previa
if (ob_get_level()) ob_clean();

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('No autorizado');
    }

    // Ajustar ruta según estructura del proyecto
    $conexion_path = __DIR__ . '/../bd/conexion.php';
    if (!file_exists($conexion_path)) {
        $conexion_path = dirname(__DIR__) . '/bd/conexion.php';
    }
    
    if (!file_exists($conexion_path)) {
        throw new Exception('Archivo de conexión no encontrado');
    }

    require_once $conexion_path;

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = "SELECT id, nombres_estudiante, apellidos_estudiante, telefono 
            FROM leads 
            WHERE activo = 1
            AND (telefono IS NOT NULL)
            ORDER BY nombres_estudiante, apellidos_estudiante";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }

    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = [
            'id' => $row['id'],
            'nombres_estudiante' => $row['nombres_estudiante'] ?? '',
            'apellidos_estudiante' => $row['apellidos_estudiante'] ?? '',
            'telefono' => $row['telefono'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true, 
        'leads' => $leads,
        'total' => count($leads)
    ], JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'leads' => []
    ], JSON_UNESCAPED_UNICODE);
}
exit;
?>