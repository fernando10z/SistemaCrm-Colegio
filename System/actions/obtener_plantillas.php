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

    $tipo = $_GET['tipo'] ?? '';

    if (empty($tipo)) {
        throw new Exception('Tipo no especificado');
    }

    $stmt = $conn->prepare("SELECT id, nombre, tipo, categoria, asunto, contenido FROM plantillas_mensajes WHERE tipo = ? AND activo = 1 ORDER BY nombre");
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conn->error);
    }

    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $result = $stmt->get_result();

    $plantillas = [];
    while ($row = $result->fetch_assoc()) {
        $plantillas[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'] ?? '',
            'tipo' => $row['tipo'] ?? '',
            'categoria' => $row['categoria'] ?? '',
            'asunto' => $row['asunto'] ?? '',
            'contenido' => $row['contenido'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true, 
        'plantillas' => $plantillas,
        'total' => count($plantillas)
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'plantillas' => []
    ], JSON_UNESCAPED_UNICODE);
}
exit;
?>