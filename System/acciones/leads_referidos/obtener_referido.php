<?php
session_start();
require_once '../../bd/conexion.php';

// Establecer header JSON
header('Content-Type: application/json; charset=utf-8');

// Función para enviar respuesta JSON
function enviarRespuesta($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Verificar método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    enviarRespuesta(false, 'Método no permitido. Use GET');
}

// Verificar parámetro ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    enviarRespuesta(false, 'ID de referido no especificado');
}

$referido_id = intval($_GET['id']);

// Validar ID
if ($referido_id <= 0) {
    enviarRespuesta(false, 'ID inválido');
}

try {
    // Consulta principal con toda la información necesaria
    $sql = "SELECT
        ur.id,
        ur.codigo_referido_id,
        ur.lead_id,
        ur.fecha_uso,
        ur.convertido,
        ur.fecha_conversion,
        ur.observaciones,
        -- Datos del código de referido
        cr.codigo as codigo_referido,
        cr.descripcion as descripcion_codigo,
        cr.beneficio_referido,
        cr.limite_usos,
        cr.usos_actuales,
        cr.fecha_inicio as codigo_fecha_inicio,
        cr.fecha_fin as codigo_fecha_fin,
        cr.activo as codigo_activo,
        -- Datos del referente
        CASE
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as referente_nombre,
        -- Datos del lead
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', IFNULL(l.apellidos_contacto, '')) as nombre_contacto,
        l.telefono,
        l.email,
        -- Estado del lead
        el.nombre as estado_lead,
        el.color as estado_color,
        -- Canal de captación
        cc.nombre as canal_captacion,
        -- Grado de interés
        g.nombre as grado_interes,
        -- Cálculos
        DATEDIFF(CURDATE(), ur.fecha_uso) as dias_desde_uso,
        CASE
            WHEN ur.convertido = 1 AND ur.fecha_conversion IS NOT NULL 
            THEN DATEDIFF(ur.fecha_conversion, ur.fecha_uso)
            ELSE NULL
        END as dias_conversion
    FROM usos_referido ur
    INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    INNER JOIN leads l ON ur.lead_id = l.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    WHERE ur.id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $referido_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        enviarRespuesta(false, 'Registro de referido no encontrado');
    }
    
    $datos = $result->fetch_assoc();
    $stmt->close();
    
    // Formatear fechas para mostrar
    $datos['fecha_uso_formateada'] = date('d/m/Y', strtotime($datos['fecha_uso']));
    $datos['fecha_conversion_formateada'] = $datos['fecha_conversion'] 
        ? date('d/m/Y', strtotime($datos['fecha_conversion'])) 
        : 'No convertido aún';
    
    $datos['codigo_fecha_inicio_formateada'] = date('d/m/Y', strtotime($datos['codigo_fecha_inicio']));
    $datos['codigo_fecha_fin_formateada'] = $datos['codigo_fecha_fin'] 
        ? date('d/m/Y', strtotime($datos['codigo_fecha_fin'])) 
        : 'Sin vencimiento';
    
    // Agregar timeline de actividad
    $timeline = [];
    
    // Evento: Registro de uso
    $timeline[] = [
        'tipo' => 'creacion',
        'icono' => 'link',
        'titulo' => 'Uso de Código Registrado',
        'descripcion' => 'Se vinculó el código "' . $datos['codigo_referido'] . '" con el lead',
        'fecha' => $datos['fecha_uso_formateada']
    ];
    
    // Evento: Conversión (si existe)
    if ($datos['convertido']) {
        $timeline[] = [
            'tipo' => 'conversion',
            'icono' => 'check-circle',
            'titulo' => 'Lead Convertido',
            'descripcion' => 'El lead completó el proceso de matrícula. Tiempo de conversión: ' . $datos['dias_conversion'] . ' días',
            'fecha' => $datos['fecha_conversion_formateada']
        ];
    }
    
    $datos['timeline'] = $timeline;
    
    // Agregar información adicional útil
    $datos['tiene_email'] = !empty($datos['email']);
    $datos['tiene_telefono'] = !empty($datos['telefono']);
    $datos['codigo_vigente'] = ($datos['codigo_fecha_fin'] === null || $datos['codigo_fecha_fin'] >= date('Y-m-d'));
    $datos['usos_disponibles'] = $datos['limite_usos'] 
        ? max(0, $datos['limite_usos'] - $datos['usos_actuales']) 
        : 'Ilimitado';
    
    // Cerrar conexión
    $conn->close();
    
    // Enviar respuesta exitosa
    enviarRespuesta(true, 'Datos obtenidos correctamente', $datos);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en obtener_referido.php: " . $e->getMessage());
    
    // Cerrar conexión si está abierta
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
    
    // Enviar respuesta de error
    enviarRespuesta(false, 'Error al obtener datos: ' . $e->getMessage());
}
?>