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

    $sql = "SELECT id, nombres, apellidos, email, telefono_principal 
            FROM apoderados 
            WHERE (email IS NOT NULL OR telefono_principal IS NOT NULL)
            ORDER BY nombres, apellidos";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }

    $apoderados = [];
    while ($row = $result->fetch_assoc()) {
        $apoderados[] = [
            'id' => $row['id'],
            'nombres' => $row['nombres'] ?? '',
            'apellidos' => $row['apellidos'] ?? '',
            'email' => $row['email'] ?? '',
            'telefono_principal' => $row['telefono_principal'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true, 
        'apoderados' => $apoderados,
        'total' => count($apoderados)
    ], JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'apoderados' => []
    ], JSON_UNESCAPED_UNICODE);
}
exit;
?>