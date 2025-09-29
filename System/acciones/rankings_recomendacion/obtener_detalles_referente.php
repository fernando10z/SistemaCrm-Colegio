<?php
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    // Obtener ID del referente
    $referente_id = intval($_POST['referente_id'] ?? 0);
    
    if ($referente_id <= 0) {
        throw new Exception('ID de referente no válido');
    }
    
    // Obtener datos básicos del referente
    $sql_basicos = "SELECT
        a.id,
        a.nombres,
        a.apellidos,
        a.email,
        a.telefono_principal as telefono,
        a.whatsapp,
        f.apellido_principal as familia,
        f.codigo_familia
    FROM apoderados a
    INNER JOIN familias f ON a.familia_id = f.id
    WHERE a.id = ? AND a.activo = 1";
    
    $stmt = $conn->prepare($sql_basicos);
    $stmt->bind_param("i", $referente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Referente no encontrado');
    }
    
    $datos = $result->fetch_assoc();
    
    // Obtener estadísticas del referente
    $sql_stats = "SELECT
        COUNT(DISTINCT cr.id) as total_codigos,
        COALESCE(SUM(cr.usos_actuales), 0) as total_usos,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones_exitosas,
        CASE 
            WHEN SUM(cr.usos_actuales) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
            ELSE 0 
        END as tasa_conversion,
        COUNT(DISTINCT CASE WHEN cr.activo = 1 THEN cr.id END) as codigos_activos,
        COALESCE(SUM(CASE 
            WHEN cr.limite_usos IS NOT NULL 
            THEN cr.limite_usos - cr.usos_actuales 
            ELSE 0 
        END), 0) as usos_restantes,
        MAX(ur.fecha_uso) as ultima_conversion,
        CASE
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 10 THEN 'Elite'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 5 THEN 'Destacado'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 1 THEN 'Activo'
            WHEN SUM(cr.usos_actuales) > 0 THEN 'En Progreso'
            ELSE 'Nuevo'
        END as categoria
    FROM codigos_referido cr
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE cr.apoderado_id = ?";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("i", $referente_id);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result();
    $stats = $stats_result->fetch_assoc();
    
    // Asegurar valores por defecto si no hay datos
    if ($stats['total_codigos'] == 0) {
        $stats = [
            'total_codigos' => 0,
            'total_usos' => 0,
            'conversiones_exitosas' => 0,
            'tasa_conversion' => 0,
            'codigos_activos' => 0,
            'usos_restantes' => 0,
            'ultima_conversion' => null,
            'categoria' => 'Nuevo'
        ];
    }
    
    // Combinar datos básicos con estadísticas
    $datos = array_merge($datos, $stats);
    
    // Calcular posición en el ranking
    $sql_posicion = "SELECT 
        COUNT(*) + 1 as posicion
    FROM (
        SELECT
            a.id,
            COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones,
            CASE 
                WHEN SUM(cr.usos_actuales) > 0 
                THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
                ELSE 0 
            END as tasa
        FROM apoderados a
        LEFT JOIN codigos_referido cr ON a.id = cr.apoderado_id
        LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
        WHERE a.activo = 1 AND a.id != ?
        GROUP BY a.id
        HAVING conversiones > ? OR (conversiones = ? AND tasa > ?)
    ) as ranking";
    
    $stmt_pos = $conn->prepare($sql_posicion);
    $stmt_pos->bind_param("iiid", 
        $referente_id,
        $datos['conversiones_exitosas'], 
        $datos['conversiones_exitosas'], 
        $datos['tasa_conversion']
    );
    $stmt_pos->execute();
    $pos_result = $stmt_pos->get_result();
    $posicion_data = $pos_result->fetch_assoc();
    $datos['posicion_ranking'] = $posicion_data['posicion'];
    
    // Obtener códigos del referente
    $sql_codigos = "SELECT
        id,
        codigo,
        descripcion,
        usos_actuales,
        limite_usos,
        DATE_FORMAT(fecha_inicio, '%d/%m/%Y') as fecha_inicio,
        CASE 
            WHEN fecha_fin IS NOT NULL 
            THEN DATE_FORMAT(fecha_fin, '%d/%m/%Y')
            ELSE 'Sin límite'
        END as fecha_fin,
        activo
    FROM codigos_referido
    WHERE apoderado_id = ?
    ORDER BY activo DESC, fecha_inicio DESC";
    
    $stmt_codigos = $conn->prepare($sql_codigos);
    $stmt_codigos->bind_param("i", $referente_id);
    $stmt_codigos->execute();
    $codigos_result = $stmt_codigos->get_result();
    
    $codigos = [];
    while($codigo = $codigos_result->fetch_assoc()) {
        $codigos[] = $codigo;
    }
    
    $datos['codigos'] = $codigos;
    
    // Obtener historial de conversiones - CORREGIDO
    $sql_conversiones = "SELECT
        CONCAT(l.nombres_estudiante, ' ', COALESCE(l.apellidos_estudiante, '')) as lead_nombre,
        CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as contacto_nombre,
        cr.codigo,
        DATE_FORMAT(ur.fecha_uso, '%d/%m/%Y %H:%i') as fecha_uso,
        ur.convertido,
        CASE 
            WHEN ur.fecha_conversion IS NOT NULL 
            THEN DATE_FORMAT(ur.fecha_conversion, '%d/%m/%Y')
            ELSE NULL
        END as fecha_conversion
    FROM usos_referido ur
    INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
    INNER JOIN leads l ON ur.lead_id = l.id
    WHERE cr.apoderado_id = ?
    ORDER BY ur.fecha_uso DESC
    LIMIT 50";
    
    $stmt_conv = $conn->prepare($sql_conversiones);
    $stmt_conv->bind_param("i", $referente_id);
    $stmt_conv->execute();
    $conv_result = $stmt_conv->get_result();
    
    $conversiones = [];
    while($conv = $conv_result->fetch_assoc()) {
        $conversiones[] = $conv;
    }
    
    $datos['conversiones'] = $conversiones;
    
    // Formatear última conversión
    if ($datos['ultima_conversion']) {
        $datos['ultima_conversion'] = date('d/m/Y', strtotime($datos['ultima_conversion']));
    } else {
        $datos['ultima_conversion'] = 'Sin conversiones';
    }
    
    echo json_encode([
        'success' => true,
        'datos' => $datos
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>