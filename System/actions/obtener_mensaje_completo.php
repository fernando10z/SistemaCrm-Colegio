<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mensaje no proporcionado']);
    exit;
}

$mensaje_id = $conn->real_escape_string($_POST['id']);

$sql = "SELECT 
    me.id,
    me.tipo,
    me.asunto,
    me.contenido,
    me.estado,
    me.destinatario_email,
    me.destinatario_telefono,
    me.fecha_envio,
    me.fecha_entrega,
    me.fecha_lectura,
    me.mensaje_id_externo,
    me.costo,
    me.error_mensaje,
    me.created_at,
    pm.nombre as plantilla_nombre,
    CASE 
        WHEN me.lead_id IS NOT NULL THEN CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante, ' (', l.nombres_contacto, ' ', l.apellidos_contacto, ')')
        WHEN me.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos, ' - Fam. ', f.apellido_principal)
        ELSE COALESCE(me.destinatario_email, me.destinatario_telefono)
    END as destinatario_completo
FROM mensajes_enviados me
LEFT JOIN plantillas_mensajes pm ON me.plantilla_id = pm.id
LEFT JOIN leads l ON me.lead_id = l.id
LEFT JOIN apoderados a ON me.apoderado_id = a.id
LEFT JOIN familias f ON a.familia_id = f.id
WHERE me.id = '$mensaje_id'
LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $mensaje = $result->fetch_assoc();
    
    // Formatear fechas
    $mensaje['fecha_envio'] = $mensaje['fecha_envio'] ? date('d/m/Y H:i:s', strtotime($mensaje['fecha_envio'])) : null;
    $mensaje['fecha_entrega'] = $mensaje['fecha_entrega'] ? date('d/m/Y H:i:s', strtotime($mensaje['fecha_entrega'])) : null;
    $mensaje['fecha_lectura'] = $mensaje['fecha_lectura'] ? date('d/m/Y H:i:s', strtotime($mensaje['fecha_lectura'])) : null;
    $mensaje['created_at'] = date('d/m/Y H:i:s', strtotime($mensaje['created_at']));
    
    // Formatear costo
    $mensaje['costo'] = number_format($mensaje['costo'], 4);
    
    // Formatear destinatario
    $mensaje['destinatario'] = $mensaje['destinatario_completo'];
    
    // Formatear plantilla
    $mensaje['plantilla'] = $mensaje['plantilla_nombre'] ?? 'Manual';
    
    echo json_encode(['success' => true, 'data' => $mensaje]);
} else {
    echo json_encode(['success' => false, 'message' => 'Mensaje no encontrado']);
}

$conn->close();
?>