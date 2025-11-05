<?php
/**
 * Archivo: consultar_historial_interacciones.php
 * Descripción: Consulta el historial completo de interacciones
 * Retorna: JSON puro sin errores
 */

// CRÍTICO: Evitar cualquier output antes del JSON
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar buffer
if (ob_get_level()) ob_end_clean();
ob_start();

// Incluir conexión (ajusta la ruta según tu estructura)
require_once __DIR__ . '/../../bd/conexion.php';

// Limpiar y configurar headers
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ], JSON_UNESCAPED_UNICODE));
}

// Validar parámetros
if (!isset($_POST['tipo']) || !isset($_POST['id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Parámetros incompletos'
    ], JSON_UNESCAPED_UNICODE));
}

$tipo = trim($_POST['tipo']);
$id = intval($_POST['id']);

// Validar tipo
if (!in_array($tipo, ['apoderado', 'familia'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Tipo inválido'
    ], JSON_UNESCAPED_UNICODE));
}

// Validar ID
if ($id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID inválido'
    ], JSON_UNESCAPED_UNICODE));
}

try {
    if ($tipo === 'apoderado') {
        $response = consultarHistorialApoderado($conn, $id);
    } else {
        $response = consultarHistorialFamilia($conn, $id);
    }
    
    $conn->close();
    die(json_encode($response, JSON_UNESCAPED_UNICODE));
    
} catch (Exception $e) {
    if (isset($conn)) $conn->close();
    die(json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}

/**
 * Consultar historial de apoderado
 */
function consultarHistorialApoderado($conn, $apoderado_id) {
    // Obtener info del apoderado
    $sql = "SELECT 
        a.id,
        a.nombres,
        a.apellidos,
        a.tipo_apoderado,
        COALESCE(a.email, '') as email,
        COALESCE(a.telefono_principal, '') as telefono_principal,
        COALESCE(a.whatsapp, '') as whatsapp,
        COALESCE(a.nivel_compromiso, '') as nivel_compromiso,
        COALESCE(a.nivel_participacion, '') as nivel_participacion,
        f.id as familia_id,
        f.apellido_principal as familia_apellido,
        f.codigo_familia
    FROM apoderados a
    INNER JOIN familias f ON a.familia_id = f.id
    WHERE a.id = ? AND a.activo = 1
    LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Error en consulta'];
    }
    
    $stmt->bind_param("i", $apoderado_id);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Error al ejecutar'];
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Apoderado no encontrado'];
    }
    
    $apoderado = $result->fetch_assoc();
    $stmt->close();
    
    // Info del contacto
    $contacto = [
        'tipo' => 'apoderado',
        'id' => (int)$apoderado['id'],
        'nombre' => $apoderado['nombres'] . ' ' . $apoderado['apellidos'],
        'detalles' => construirDetallesApoderado($apoderado)
    ];
    
    // Obtener interacciones
    $sql2 = "SELECT 
        i.id,
        i.tipo_interaccion_id,
        COALESCE(ti.nombre, 'Sin tipo') as tipo_interaccion,
        COALESCE(ti.color, '#6c757d') as tipo_color,
        i.usuario_id,
        CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellidos, '')) as usuario_nombre,
        i.asunto,
        COALESCE(i.descripcion, '') as descripcion,
        i.fecha_programada,
        i.fecha_realizada,
        i.duracion_minutos,
        COALESCE(i.resultado, '') as resultado,
        COALESCE(i.observaciones, '') as observaciones,
        i.requiere_seguimiento,
        i.fecha_proximo_seguimiento,
        i.estado,
        i.created_at,
        i.updated_at
    FROM interacciones i
    LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    WHERE i.apoderado_id = ? AND i.activo = 1
    ORDER BY COALESCE(i.fecha_realizada, i.fecha_programada, i.created_at) DESC";
    
    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) {
        return ['success' => false, 'message' => 'Error en consulta interacciones'];
    }
    
    $stmt2->bind_param("i", $apoderado_id);
    if (!$stmt2->execute()) {
        $stmt2->close();
        return ['success' => false, 'message' => 'Error al ejecutar interacciones'];
    }
    
    $result2 = $stmt2->get_result();
    $interacciones = [];
    while ($row = $result2->fetch_assoc()) {
        $interacciones[] = $row;
    }
    $stmt2->close();
    
    return [
        'success' => true,
        'contacto' => $contacto,
        'interacciones' => $interacciones
    ];
}

/**
 * Consultar historial de familia
 */
function consultarHistorialFamilia($conn, $familia_id) {
    // Obtener info de la familia
    $sql = "SELECT 
        f.id,
        f.codigo_familia,
        f.apellido_principal,
        COALESCE(f.direccion, '') as direccion,
        COALESCE(f.distrito, '') as distrito,
        COALESCE(f.provincia, '') as provincia,
        COALESCE(f.departamento, '') as departamento,
        COALESCE(f.nivel_socioeconomico, '') as nivel_socioeconomico,
        COALESCE(f.observaciones, '') as observaciones,
        COUNT(DISTINCT a.id) as total_apoderados,
        COUNT(DISTINCT e.id) as total_estudiantes
    FROM familias f
    LEFT JOIN apoderados a ON f.id = a.familia_id AND a.activo = 1
    LEFT JOIN estudiantes e ON f.id = e.familia_id AND e.activo = 1
    WHERE f.id = ? AND f.activo = 1
    GROUP BY f.id
    LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Error en consulta'];
    }
    
    $stmt->bind_param("i", $familia_id);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Error al ejecutar'];
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Familia no encontrada'];
    }
    
    $familia = $result->fetch_assoc();
    $stmt->close();
    
    // Info del contacto
    $contacto = [
        'tipo' => 'familia',
        'id' => (int)$familia['id'],
        'nombre' => 'Familia ' . $familia['apellido_principal'] . ' (' . $familia['codigo_familia'] . ')',
        'detalles' => construirDetallesFamilia($familia)
    ];
    
    // Obtener interacciones
    $sql2 = "SELECT 
        i.id,
        i.tipo_interaccion_id,
        COALESCE(ti.nombre, 'Sin tipo') as tipo_interaccion,
        COALESCE(ti.color, '#6c757d') as tipo_color,
        i.usuario_id,
        CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellidos, '')) as usuario_nombre,
        i.apoderado_id,
        CONCAT(COALESCE(a.nombres, ''), ' ', COALESCE(a.apellidos, '')) as apoderado_nombre,
        i.asunto,
        COALESCE(i.descripcion, '') as descripcion,
        i.fecha_programada,
        i.fecha_realizada,
        i.duracion_minutos,
        COALESCE(i.resultado, '') as resultado,
        COALESCE(i.observaciones, '') as observaciones,
        i.requiere_seguimiento,
        i.fecha_proximo_seguimiento,
        i.estado,
        i.created_at,
        i.updated_at
    FROM interacciones i
    LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN apoderados a ON i.apoderado_id = a.id
    WHERE i.familia_id = ? AND i.activo = 1
    ORDER BY COALESCE(i.fecha_realizada, i.fecha_programada, i.created_at) DESC";
    
    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) {
        return ['success' => false, 'message' => 'Error en consulta interacciones'];
    }
    
    $stmt2->bind_param("i", $familia_id);
    if (!$stmt2->execute()) {
        $stmt2->close();
        return ['success' => false, 'message' => 'Error al ejecutar interacciones'];
    }
    
    $result2 = $stmt2->get_result();
    $interacciones = [];
    while ($row = $result2->fetch_assoc()) {
        if ($row['apoderado_nombre'] && trim($row['apoderado_nombre']) !== '') {
            $row['descripcion'] .= "\n\n[Contacto: " . $row['apoderado_nombre'] . "]";
        }
        $interacciones[] = $row;
    }
    $stmt2->close();
    
    return [
        'success' => true,
        'contacto' => $contacto,
        'interacciones' => $interacciones
    ];
}

/**
 * Construir detalles del apoderado
 */
function construirDetallesApoderado($apoderado) {
    $detalles = [];
    
    // Tipo
    $tipos = ['titular' => 'Titular', 'suplente' => 'Suplente', 'economico' => 'Económico'];
    $tipo = $tipos[$apoderado['tipo_apoderado']] ?? $apoderado['tipo_apoderado'];
    $detalles[] = '<span class="badge bg-primary me-2">' . $tipo . '</span>';
    
    // Familia
    $detalles[] = '<strong>Familia:</strong> ' . htmlspecialchars($apoderado['familia_apellido']) . 
                  ' (' . htmlspecialchars($apoderado['codigo_familia']) . ')';
    
    // Contacto
    if ($apoderado['email']) {
        $detalles[] = '<i class="ti ti-mail"></i> ' . htmlspecialchars($apoderado['email']);
    }
    if ($apoderado['telefono_principal']) {
        $detalles[] = '<i class="ti ti-phone"></i> ' . htmlspecialchars($apoderado['telefono_principal']);
    }
    if ($apoderado['whatsapp']) {
        $detalles[] = '<i class="ti ti-brand-whatsapp"></i> ' . htmlspecialchars($apoderado['whatsapp']);
    }
    
    // Compromiso
    if ($apoderado['nivel_compromiso']) {
        $colores = ['alto' => 'success', 'medio' => 'warning', 'bajo' => 'danger'];
        $color = $colores[$apoderado['nivel_compromiso']] ?? 'secondary';
        $detalles[] = '<span class="badge bg-' . $color . '">Compromiso: ' . 
                      ucfirst($apoderado['nivel_compromiso']) . '</span>';
    }
    
    // Participación
    if ($apoderado['nivel_participacion']) {
        $nivel = str_replace('_', ' ', ucfirst($apoderado['nivel_participacion']));
        $detalles[] = '<span class="badge bg-info">Participación: ' . $nivel . '</span>';
    }
    
    return implode(' | ', $detalles);
}

/**
 * Construir detalles de la familia
 */
function construirDetallesFamilia($familia) {
    $detalles = [];
    
    // Código
    $detalles[] = '<strong>Código:</strong> ' . htmlspecialchars($familia['codigo_familia']);
    
    // Ubicación
    if ($familia['direccion']) {
        $ubicacion = htmlspecialchars($familia['direccion']);
        if ($familia['distrito']) {
            $ubicacion .= ', ' . htmlspecialchars($familia['distrito']);
        }
        $detalles[] = '<i class="ti ti-map-pin"></i> ' . $ubicacion;
    }
    
    // Miembros
    $detalles[] = '<span class="badge bg-primary">' . $familia['total_apoderados'] . ' Apoderado(s)</span>';
    $detalles[] = '<span class="badge bg-success">' . $familia['total_estudiantes'] . ' Estudiante(s)</span>';
    
    // NSE
    if ($familia['nivel_socioeconomico']) {
        $detalles[] = '<span class="badge bg-info">NSE: ' . 
                      htmlspecialchars($familia['nivel_socioeconomico']) . '</span>';
    }
    
    return implode(' | ', $detalles);
}
?>