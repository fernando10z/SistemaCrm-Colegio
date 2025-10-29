<?php
// actions/obtener_mensaje_detalle.php
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
require_once '../bd/conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Debe iniciar sesión.'
    ]);
    exit;
}

// Verificar que se recibió el ID del mensaje
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó el ID del mensaje'
    ]);
    exit;
}

$mensaje_id = intval($_POST['id']);

try {
    // Consulta SQL basada EXACTAMENTE en la estructura real de la base de datos
    $sql = "SELECT 
        me.id,
        me.tipo,
        me.plantilla_id,
        pm.nombre as plantilla_nombre,
        pm.categoria as plantilla_categoria,
        pm.asunto as plantilla_asunto,
        me.lead_id,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as lead_nombre,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as contacto_lead,
        l.telefono as lead_telefono,
        l.email as lead_email,
        el.nombre as estado_lead,
        me.apoderado_id,
        CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
        a.email as apoderado_email,
        a.telefono_principal as apoderado_telefono,
        f.apellido_principal as familia_apellido,
        me.destinatario_email,
        me.destinatario_telefono,
        me.asunto,
        me.contenido,
        me.estado,
        me.fecha_envio,
        me.fecha_entrega,
        me.fecha_lectura,
        me.proveedor_id,
        me.mensaje_id_externo,
        me.costo,
        me.error_mensaje,
        me.created_at,
        CASE 
            WHEN me.lead_id IS NOT NULL THEN 'Lead'
            WHEN me.apoderado_id IS NOT NULL THEN 'Apoderado'
            ELSE 'Directo'
        END as tipo_destinatario
        
    FROM mensajes_enviados me
    LEFT JOIN plantillas_mensajes pm ON me.plantilla_id = pm.id
    LEFT JOIN leads l ON me.lead_id = l.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN apoderados a ON me.apoderado_id = a.id
    LEFT JOIN familias f ON a.familia_id = f.id
    WHERE me.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mensaje_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el mensaje con ID ' . $mensaje_id
        ]);
        exit;
    }
    
    $mensaje = $result->fetch_assoc();
    
    // Formatear fechas
    $mensaje['created_at_formateada'] = date('d/m/Y H:i:s', strtotime($mensaje['created_at']));
    
    if ($mensaje['fecha_envio']) {
        $mensaje['fecha_envio_formateada'] = date('d/m/Y H:i:s', strtotime($mensaje['fecha_envio']));
    }
    
    if ($mensaje['fecha_entrega']) {
        $mensaje['fecha_entrega_formateada'] = date('d/m/Y H:i:s', strtotime($mensaje['fecha_entrega']));
    }
    
    if ($mensaje['fecha_lectura']) {
        $mensaje['fecha_lectura_formateada'] = date('d/m/Y H:i:s', strtotime($mensaje['fecha_lectura']));
    }
    
    // Determinar el destinatario principal según el tipo
    if ($mensaje['lead_id']) {
        $mensaje['destinatario_principal'] = $mensaje['lead_nombre'];
        $mensaje['contacto_principal'] = $mensaje['contacto_lead'];
        $mensaje['telefono_principal'] = $mensaje['lead_telefono'];
        $mensaje['email_principal'] = $mensaje['lead_email'];
    } elseif ($mensaje['apoderado_id']) {
        $mensaje['destinatario_principal'] = $mensaje['apoderado_nombre'];
        $mensaje['contacto_principal'] = 'Familia ' . ($mensaje['familia_apellido'] ?? '');
        $mensaje['telefono_principal'] = $mensaje['apoderado_telefono'];
        $mensaje['email_principal'] = $mensaje['apoderado_email'];
    } else {
        $mensaje['destinatario_principal'] = 'Destinatario directo';
        $mensaje['contacto_principal'] = $mensaje['destinatario_email'] ?? $mensaje['destinatario_telefono'];
        $mensaje['telefono_principal'] = $mensaje['destinatario_telefono'];
        $mensaje['email_principal'] = $mensaje['destinatario_email'];
    }
    
    // Iconos según tipo
    $iconos = [
        'email' => 'ti-mail',
        'whatsapp' => 'ti-brand-whatsapp',
        'sms' => 'ti-message'
    ];
    $mensaje['icono_tipo'] = $iconos[$mensaje['tipo']] ?? 'ti-mail';
    
    // Colores según estado
    $colores = [
        'pendiente' => 'secondary',
        'enviado' => 'primary',
        'entregado' => 'success',
        'leido' => 'info',
        'fallido' => 'danger'
    ];
    $mensaje['color_estado'] = $colores[$mensaje['estado']] ?? 'secondary';
    
    // Calcular tiempo de entrega si existe
    if ($mensaje['fecha_envio'] && $mensaje['fecha_entrega']) {
        $envio = strtotime($mensaje['fecha_envio']);
        $entrega = strtotime($mensaje['fecha_entrega']);
        $diferencia = $entrega - $envio;
        $mensaje['tiempo_entrega_segundos'] = $diferencia;
        
        if ($diferencia < 60) {
            $mensaje['tiempo_entrega_texto'] = $diferencia . ' segundos';
        } elseif ($diferencia < 3600) {
            $mensaje['tiempo_entrega_texto'] = round($diferencia / 60) . ' minutos';
        } else {
            $mensaje['tiempo_entrega_texto'] = round($diferencia / 3600, 1) . ' horas';
        }
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $mensaje,
        'message' => 'Detalle del mensaje obtenido correctamente'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el detalle del mensaje: ' . $e->getMessage()
    ]);
}
?>