<?php
header('Content-Type: application/json');

try {
    // Verificar que el archivo conexion existe
    $conexion_path = '../bd/conexion.php';
    
    if (!file_exists($conexion_path)) {
        throw new Exception('Archivo conexion.php NO existe en: ' . realpath(dirname(__FILE__) . '/../bd/'));
    }
    
    include $conexion_path;
    
    if (!isset($conn)) {
        throw new Exception('Variable $conn no está definida después de incluir conexion.php');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión MySQL: ' . $conn->connect_error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'database' => 'crm_escolar',
        'ruta_archivo' => __FILE__,
        'ruta_conexion' => realpath($conexion_path)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'ruta_actual' => __FILE__,
        'directorio' => __DIR__
    ]);
}
?>