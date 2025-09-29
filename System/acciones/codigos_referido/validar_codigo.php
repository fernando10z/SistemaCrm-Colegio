<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'disponible' => false,
        'message' => 'Método de solicitud no válido'
    ]);
    exit();
}

// Verificar que se proporcionó el código
if (!isset($_GET['codigo']) || empty(trim($_GET['codigo']))) {
    echo json_encode([
        'disponible' => false,
        'message' => 'Código no especificado'
    ]);
    exit();
}

$codigo = strtoupper(trim($_GET['codigo']));

// Validar formato
if (!preg_match('/^[A-Z0-9\-]{3,20}$/', $codigo)) {
    echo json_encode([
        'disponible' => false,
        'message' => 'Formato de código inválido'
    ]);
    exit();
}

try {
    // Verificar si el código ya existe
    $stmt = $conn->prepare("SELECT id, activo FROM codigos_referido WHERE codigo = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'disponible' => false,
            'message' => 'El código ya existe',
            'codigo_existente' => true,
            'activo' => $row['activo'] == 1
        ]);
    } else {
        echo json_encode([
            'disponible' => true,
            'message' => 'Código disponible'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'disponible' => false,
        'message' => 'Error al validar el código: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>