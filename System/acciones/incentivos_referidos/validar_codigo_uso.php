<?php
// acciones/incentivos_referidos/validar_codigo_uso.php
session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));

if (empty($codigo)) {
    echo json_encode([
        'valido' => false,
        'message' => 'Código no especificado'
    ]);
    exit();
}

$sql = "SELECT cr.id, cr.codigo, cr.descripcion, cr.limite_usos, cr.usos_actuales,
        cr.fecha_inicio, cr.fecha_fin, cr.activo, cr.beneficio_referente, cr.beneficio_referido,
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as referente
        FROM codigos_referido cr
        LEFT JOIN apoderados a ON cr.apoderado_id = a.id
        LEFT JOIN familias f ON cr.familia_id = f.id
        WHERE cr.codigo = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'valido' => false,
        'message' => 'El código no existe'
    ]);
    exit();
}

$codigo_data = $result->fetch_assoc();

// Validaciones
$errores = [];

if ($codigo_data['activo'] != 1) {
    $errores[] = 'El código está inactivo';
}

$hoy = new DateTime();
$fecha_inicio = new DateTime($codigo_data['fecha_inicio']);
if ($hoy < $fecha_inicio) {
    $errores[] = 'El código aún no está vigente';
}

if ($codigo_data['fecha_fin']) {
    $fecha_fin = new DateTime($codigo_data['fecha_fin']);
    if ($hoy > $fecha_fin) {
        $errores[] = 'El código ha vencido';
    }
}

if ($codigo_data['limite_usos'] && $codigo_data['usos_actuales'] >= $codigo_data['limite_usos']) {
    $errores[] = 'El código ha alcanzado su límite de usos';
}

if (count($errores) > 0) {
    echo json_encode([
        'valido' => false,
        'message' => implode('. ', $errores)
    ]);
    exit();
}

// Formatear fecha de fin
$codigo_data['fecha_fin'] = $codigo_data['fecha_fin'] ? 
    date('d/m/Y', strtotime($codigo_data['fecha_fin'])) : null;

echo json_encode([
    'valido' => true,
    'codigo_data' => $codigo_data
]);
?>