<?php
// acciones/incentivos_referidos/obtener_pendientes.php
session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

$filtro_codigo = trim($_POST['filtro_codigo'] ?? '');
$filtro_fecha = trim($_POST['filtro_fecha'] ?? '');

$sql = "SELECT ur.id, ur.fecha_uso, ur.observaciones,
        cr.codigo, cr.beneficio_referente, cr.beneficio_referido,
        l.nombres_estudiante as lead_estudiante_nombre,
        l.email as lead_email,
        CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as lead_contacto_nombre,
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as referente_nombre
        FROM usos_referido ur
        INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
        INNER JOIN leads l ON ur.lead_id = l.id
        LEFT JOIN apoderados a ON cr.apoderado_id = a.id
        LEFT JOIN familias f ON cr.familia_id = f.id
        WHERE ur.convertido = 0";

// Aplicar filtros
if (!empty($filtro_codigo)) {
    $sql .= " AND cr.codigo = ?";
}

if (!empty($filtro_fecha)) {
    switch ($filtro_fecha) {
        case 'hoy':
            $sql .= " AND DATE(ur.fecha_uso) = CURDATE()";
            break;
        case 'semana':
            $sql .= " AND YEARWEEK(ur.fecha_uso, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'mes':
            $sql .= " AND YEAR(ur.fecha_uso) = YEAR(CURDATE()) AND MONTH(ur.fecha_uso) = MONTH(CURDATE())";
            break;
        case 'anterior':
            $sql .= " AND YEAR(ur.fecha_uso) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                     AND MONTH(ur.fecha_uso) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
    }
}

$sql .= " ORDER BY ur.fecha_uso DESC LIMIT 50";

$stmt = $conn->prepare($sql);

if (!empty($filtro_codigo)) {
    $stmt->bind_param("s", $filtro_codigo);
}

$stmt->execute();
$result = $stmt->get_result();

$pendientes = [];
while ($row = $result->fetch_assoc()) {
    $pendientes[] = $row;
}

echo json_encode([
    'success' => count($pendientes) > 0,
    'pendientes' => $pendientes,
    'message' => count($pendientes) > 0 ? '' : 'No hay usos pendientes con los filtros aplicados'
]);
?>