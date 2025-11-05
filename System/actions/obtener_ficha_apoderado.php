<?php
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
include '../bd/conexion.php';

// Validar que se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de apoderado no proporcionado'
    ]);
    exit;
}

$apoderado_id = intval($_POST['id']);

try {
    // Consulta principal para obtener toda la información del apoderado
    // SOLO usando columnas que EXISTEN en la base de datos
    $sql = "SELECT 
        a.id,
        a.familia_id,
        a.tipo_apoderado,
        a.tipo_documento,
        a.numero_documento,
        a.nombres,
        a.apellidos,
        a.fecha_nacimiento,
        a.genero,
        a.email,
        a.telefono_principal,
        a.telefono_secundario,
        a.whatsapp,
        a.ocupacion,
        a.empresa,
        a.nivel_educativo,
        a.estado_civil,
        a.nivel_compromiso,
        a.nivel_participacion,
        a.preferencia_contacto,
        a.activo,
        a.created_at,
        a.updated_at,
        CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
        -- Información de la familia (SOLO columnas que existen)
        f.codigo_familia,
        f.apellido_principal as familia_apellido,
        f.direccion as familia_direccion,
        f.distrito,
        f.provincia,
        f.departamento,
        f.nivel_socioeconomico,
        f.observaciones as familia_observaciones,
        -- Calcular edad
        YEAR(CURDATE()) - YEAR(a.fecha_nacimiento) - 
        (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(a.fecha_nacimiento, '%m%d')) as edad,
        -- Contar estudiantes
        COUNT(DISTINCT e.id) as total_estudiantes
    FROM apoderados a
    LEFT JOIN familias f ON a.familia_id = f.id
    LEFT JOIN estudiantes e ON a.familia_id = e.familia_id
    WHERE a.id = ? AND a.activo = 1
    GROUP BY a.id, a.familia_id, a.tipo_apoderado, a.tipo_documento, a.numero_documento,
             a.nombres, a.apellidos, a.fecha_nacimiento, a.genero, a.email,
             a.telefono_principal, a.telefono_secundario, a.whatsapp, a.ocupacion,
             a.empresa, a.nivel_educativo, a.estado_civil, a.nivel_compromiso,
             a.nivel_participacion, a.preferencia_contacto, a.activo, a.created_at, a.updated_at,
             f.codigo_familia, f.apellido_principal, f.direccion, f.distrito, f.provincia,
             f.departamento, f.nivel_socioeconomico, f.observaciones
    LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $apoderado_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Apoderado no encontrado'
        ]);
        exit;
    }

    $apoderado = $result->fetch_assoc();

    // Obtener estudiantes vinculados con detalles
    $sql_estudiantes = "SELECT 
        e.id,
        e.codigo_estudiante,
        e.nombres,
        e.apellidos,
        CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
        e.fecha_nacimiento,
        e.seccion,
        e.estado_matricula,
        YEAR(CURDATE()) - YEAR(e.fecha_nacimiento) - 
        (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(e.fecha_nacimiento, '%m%d')) as edad
    FROM estudiantes e
    WHERE e.familia_id = ? AND e.activo = 1
    ORDER BY e.apellidos, e.nombres";

    $stmt_estudiantes = $conn->prepare($sql_estudiantes);
    $stmt_estudiantes->bind_param("i", $apoderado['familia_id']);
    $stmt_estudiantes->execute();
    $result_estudiantes = $stmt_estudiantes->get_result();

    $estudiantes = [];
    while ($estudiante = $result_estudiantes->fetch_assoc()) {
        $estudiantes[] = $estudiante;
    }

    // Obtener otros apoderados de la misma familia
    $sql_otros_apoderados = "SELECT 
        a.id,
        CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
        a.tipo_apoderado,
        a.telefono_principal,
        a.email,
        a.tipo_documento,
        a.numero_documento
    FROM apoderados a
    WHERE a.familia_id = ? AND a.id != ? AND a.activo = 1
    ORDER BY 
        CASE a.tipo_apoderado
            WHEN 'titular' THEN 1
            WHEN 'economico' THEN 2
            WHEN 'suplente' THEN 3
        END,
        a.nombres";

    $stmt_otros = $conn->prepare($sql_otros_apoderados);
    $stmt_otros->bind_param("ii", $apoderado['familia_id'], $apoderado_id);
    $stmt_otros->execute();
    $result_otros = $stmt_otros->get_result();

    $otros_apoderados = [];
    while ($otro = $result_otros->fetch_assoc()) {
        $otros_apoderados[] = $otro;
    }

    // Formatear fechas
    $apoderado['fecha_nacimiento_formato'] = $apoderado['fecha_nacimiento'] 
        ? date('d/m/Y', strtotime($apoderado['fecha_nacimiento'])) 
        : null;
    $apoderado['fecha_registro_formato'] = date('d/m/Y H:i', strtotime($apoderado['created_at']));
    $apoderado['fecha_actualizacion_formato'] = date('d/m/Y H:i', strtotime($apoderado['updated_at']));

    // Formatear género
    $apoderado['genero_texto'] = '';
    if ($apoderado['genero'] == 'M') {
        $apoderado['genero_texto'] = 'Masculino';
    } elseif ($apoderado['genero'] == 'F') {
        $apoderado['genero_texto'] = 'Femenino';
    } elseif ($apoderado['genero'] == 'otro') {
        $apoderado['genero_texto'] = 'Otro';
    }

    // Formatear estado civil
    $estados_civiles = [
        'soltero' => 'Soltero(a)',
        'casado' => 'Casado(a)',
        'divorciado' => 'Divorciado(a)',
        'viudo' => 'Viudo(a)',
        'conviviente' => 'Conviviente'
    ];
    $apoderado['estado_civil_texto'] = $estados_civiles[$apoderado['estado_civil']] ?? 'No especificado';

    // Formatear tipo de apoderado
    $tipos_apoderado = [
        'titular' => 'Titular',
        'suplente' => 'Suplente',
        'economico' => 'Económico'
    ];
    $apoderado['tipo_apoderado_texto'] = $tipos_apoderado[$apoderado['tipo_apoderado']] ?? 'No especificado';

    // Formatear nivel de compromiso
    $niveles_compromiso = [
        'alto' => 'Alto',
        'medio' => 'Medio',
        'bajo' => 'Bajo'
    ];
    $apoderado['nivel_compromiso_texto'] = $niveles_compromiso[$apoderado['nivel_compromiso']] ?? 'Medio';

    // Formatear nivel de participación
    $niveles_participacion = [
        'muy_activo' => 'Muy Activo',
        'activo' => 'Activo',
        'poco_activo' => 'Poco Activo',
        'inactivo' => 'Inactivo'
    ];
    $apoderado['nivel_participacion_texto'] = $niveles_participacion[$apoderado['nivel_participacion']] ?? 'Activo';

    // Formatear preferencia de contacto
    $preferencias = [
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'llamada' => 'Llamada telefónica',
        'sms' => 'SMS'
    ];
    $apoderado['preferencia_contacto_texto'] = $preferencias[$apoderado['preferencia_contacto']] ?? 'Email';

    // Calcular calificación de participación
    $calificacion = 'regular';
    if ($apoderado['nivel_compromiso'] == 'alto' && in_array($apoderado['nivel_participacion'], ['muy_activo', 'activo'])) {
        $calificacion = 'excelente';
    } elseif ($apoderado['nivel_compromiso'] == 'medio' && in_array($apoderado['nivel_participacion'], ['activo', 'poco_activo'])) {
        $calificacion = 'buena';
    }
    $apoderado['calificacion_participacion'] = $calificacion;

    // Construir dirección completa de la familia
    $direccion_completa = [];
    if (!empty($apoderado['familia_direccion'])) {
        $direccion_completa[] = $apoderado['familia_direccion'];
    }
    if (!empty($apoderado['distrito'])) {
        $direccion_completa[] = $apoderado['distrito'];
    }
    if (!empty($apoderado['provincia'])) {
        $direccion_completa[] = $apoderado['provincia'];
    }
    if (!empty($apoderado['departamento'])) {
        $direccion_completa[] = $apoderado['departamento'];
    }
    $apoderado['direccion_completa'] = implode(', ', $direccion_completa);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'apoderado' => $apoderado,
            'estudiantes' => $estudiantes,
            'otros_apoderados' => $otros_apoderados
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener la ficha del apoderado: ' . $e->getMessage()
    ]);
}

$conn->close();
?>