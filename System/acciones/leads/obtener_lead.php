<?php
// actions/obtener_lead.php
include '../../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = $_POST['id'] ?? '';
$accion = $_POST['accion'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID del lead es requerido']);
    exit;
}

try {
    // Consulta principal para obtener todos los datos del lead
    $sql = "SELECT 
        l.id,
        l.codigo_lead,
        l.nombres_estudiante,
        l.apellidos_estudiante,
        l.fecha_nacimiento_estudiante,
        l.genero_estudiante,
        l.nombres_contacto,
        l.apellidos_contacto,
        l.telefono,
        l.whatsapp,
        l.email,
        l.colegio_procedencia,
        l.motivo_cambio,
        l.observaciones,
        l.canal_captacion_id,
        cc.nombre as canal_captacion,
        cc.tipo as canal_tipo,
        l.grado_interes_id,
        g.nombre as grado_nombre,
        ne.nombre as nivel_nombre,
        l.estado_lead_id,
        el.nombre as estado_lead,
        el.color as color_estado,
        el.descripcion as estado_descripcion,
        l.responsable_id,
        CONCAT(u.nombre, ' ', u.apellidos) as responsable_nombre,
        l.prioridad,
        l.puntaje_interes,
        l.fecha_conversion,
        l.fecha_ultima_interaccion,
        l.proxima_accion_fecha,
        l.proxima_accion_descripcion,
        l.utm_source,
        l.utm_medium,
        l.utm_campaign,
        l.ip_origen,
        l.activo,
        l.created_at,
        l.updated_at,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante_completo,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto_completo
    FROM leads l
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN usuarios u ON l.responsable_id = u.id
    WHERE l.id = ? AND l.activo = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead no encontrado']);
        exit;
    }

    $lead = $result->fetch_assoc();

    // Formatear fechas para mejor presentación
    if ($lead['fecha_nacimiento_estudiante']) {
        $lead['fecha_nacimiento_formateada'] = date('d/m/Y', strtotime($lead['fecha_nacimiento_estudiante']));
    }
    
    if ($lead['proxima_accion_fecha']) {
        $lead['proxima_accion_formateada'] = date('d/m/Y', strtotime($lead['proxima_accion_fecha']));
    }
    
    if ($lead['fecha_conversion']) {
        $lead['fecha_conversion_formateada'] = date('d/m/Y', strtotime($lead['fecha_conversion']));
    }
    
    if ($lead['fecha_ultima_interaccion']) {
        $lead['fecha_ultima_interaccion_formateada'] = date('d/m/Y H:i', strtotime($lead['fecha_ultima_interaccion']));
    }
    
    $lead['fecha_registro_formateada'] = date('d/m/Y H:i', strtotime($lead['created_at']));
    $lead['fecha_actualizacion_formateada'] = date('d/m/Y H:i', strtotime($lead['updated_at']));

    // Formatear género
    if ($lead['genero_estudiante']) {
        $lead['genero_formateado'] = $lead['genero_estudiante'] == 'M' ? 'Masculino' : 'Femenino';
    } else {
        $lead['genero_formateado'] = 'No especificado';
    }

    // Formatear prioridad
    $lead['prioridad_formateada'] = ucfirst($lead['prioridad']);

    // Generar estrellas para puntaje de interés
    $puntaje = (int)($lead['puntaje_interes'] ?? 0);
    $estrellas_cantidad = ceil($puntaje / 20); // 0–20 = 1 ⭐, 21–40 = 2 ⭐, etc.
    $estrellas = '';
    for($i = 1; $i <= 5; $i++) {
        $clase = $i <= $estrellas_cantidad ? 'fas fa-star text-warning' : 'far fa-star text-muted';
        $estrellas .= "<i class='$clase'></i> ";
    }
    $lead['estrellas_interes'] = $estrellas;

    // Si es para el modal de edición, también obtener las opciones para los selects
    if ($accion === 'editar') {
        // Canales de captación
        $canales_sql = "SELECT id, nombre FROM canales_captacion WHERE activo = 1 ORDER BY nombre";
        $canales_result = $conn->query($canales_sql);
        $canales = [];
        while($canal = $canales_result->fetch_assoc()) {
            $canales[] = $canal;
        }

        // Estados de lead
        $estados_sql = "SELECT id, nombre FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
        $estados_result = $conn->query($estados_sql);
        $estados = [];
        while($estado = $estados_result->fetch_assoc()) {
            $estados[] = $estado;
        }

        // Grados con niveles
        $grados_sql = "SELECT g.id, g.nombre, ne.nombre as nivel_nombre 
                       FROM grados g 
                       LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id 
                       WHERE g.activo = 1 
                       ORDER BY ne.orden_display, g.orden_display";
        $grados_result = $conn->query($grados_sql);
        $grados = [];
        while($grado = $grados_result->fetch_assoc()) {
            $grados[] = $grado;
        }

        // Usuarios (responsables)
        $usuarios_sql = "SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo 
                        FROM usuarios 
                        WHERE activo = 1 AND rol_id IN (2, 3) 
                        ORDER BY nombre";
        $usuarios_result = $conn->query($usuarios_sql);
        $usuarios = [];
        while($usuario = $usuarios_result->fetch_assoc()) {
            $usuarios[] = $usuario;
        }

        $lead['opciones'] = [
            'canales' => $canales,
            'estados' => $estados,
            'grados' => $grados,
            'usuarios' => $usuarios
        ];
    }

    // Obtener historial de interacciones recientes (últimas 5)
    $interacciones_sql = "SELECT 
        i.id,
        i.asunto,
        i.descripcion,
        i.fecha_programada,
        i.fecha_realizada,
        i.resultado,
        i.estado,
        ti.nombre as tipo_interaccion,
        ti.icono,
        ti.color,
        CONCAT(u.nombre, ' ', u.apellidos) as usuario_nombre
    FROM interacciones i
    LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    WHERE i.lead_id = ? AND i.activo = 1
    ORDER BY i.created_at DESC
    LIMIT 5";

    $int_stmt = $conn->prepare($interacciones_sql);
    $int_stmt->bind_param("i", $id);
    $int_stmt->execute();
    $int_result = $int_stmt->get_result();
    
    $interacciones = [];
    while($interaccion = $int_result->fetch_assoc()) {
        // Formatear fechas de interacciones
        if ($interaccion['fecha_programada']) {
            $interaccion['fecha_programada_formateada'] = date('d/m/Y H:i', strtotime($interaccion['fecha_programada']));
        }
        if ($interaccion['fecha_realizada']) {
            $interaccion['fecha_realizada_formateada'] = date('d/m/Y H:i', strtotime($interaccion['fecha_realizada']));
        }
        $interacciones[] = $interaccion;
    }
    
    $lead['interacciones'] = $interacciones;

    echo json_encode([
        'success' => true, 
        'data' => $lead,
        'accion' => $accion
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
