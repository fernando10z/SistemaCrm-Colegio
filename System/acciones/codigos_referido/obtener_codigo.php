<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no válido'
    ]);
    exit();
}

// Verificar que se proporcionó el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de código no especificado'
    ]);
    exit();
}

$codigo_id = intval($_GET['id']);

try {
    // Consulta principal para obtener el código con toda su información
    $sql = "SELECT
        cr.id,
        cr.codigo,
        cr.apoderado_id,
        cr.familia_id,
        cr.descripcion,
        cr.beneficio_referente,
        cr.beneficio_referido,
        cr.limite_usos,
        cr.usos_actuales,
        cr.fecha_inicio,
        cr.fecha_fin,
        cr.activo,
        cr.created_at,
        cr.updated_at,
        -- Información del apoderado si existe
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            ELSE NULL
        END as nombre_apoderado,
        a.email as email_apoderado,
        -- Información de la familia
        CASE 
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE NULL
        END as apellido_familia,
        f.codigo_familia,
        -- Calcular propietario display
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN CONCAT('Familia ', f.apellido_principal)
            ELSE 'Código General'
        END as propietario,
        -- Calcular días restantes
        CASE
            WHEN cr.fecha_fin IS NULL THEN NULL
            WHEN DATEDIFF(cr.fecha_fin, CURDATE()) < 0 THEN 0
            ELSE DATEDIFF(cr.fecha_fin, CURDATE())
        END as dias_restantes,
        -- Calcular usos disponibles
        CASE
            WHEN cr.limite_usos IS NULL THEN 'Ilimitado'
            ELSE cr.limite_usos - cr.usos_actuales
        END as usos_disponibles,
        -- Estado de validez
        CASE
            WHEN cr.activo = 0 THEN 'inactivo'
            WHEN cr.fecha_fin IS NOT NULL AND cr.fecha_fin < CURDATE() THEN 'expirado'
            WHEN cr.limite_usos IS NOT NULL AND cr.usos_actuales >= cr.limite_usos THEN 'agotado'
            ELSE 'activo'
        END as estado_validez,
        -- Porcentaje de uso
        CASE
            WHEN cr.limite_usos IS NOT NULL THEN ROUND((cr.usos_actuales / cr.limite_usos) * 100, 2)
            ELSE 0
        END as porcentaje_uso
    FROM codigos_referido cr
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    WHERE cr.id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $codigo_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Código no encontrado'
        ]);
        exit();
    }
    
    $codigo_data = $result->fetch_assoc();
    $stmt->close();
    
    // Formatear fechas
    $fecha_inicio_obj = new DateTime($codigo_data['fecha_inicio']);
    $codigo_data['fecha_inicio_formato'] = $fecha_inicio_obj->format('d/m/Y');
    
    if ($codigo_data['fecha_fin']) {
        $fecha_fin_obj = new DateTime($codigo_data['fecha_fin']);
        $codigo_data['fecha_fin_formato'] = $fecha_fin_obj->format('d/m/Y');
    } else {
        $codigo_data['fecha_fin_formato'] = 'Sin límite';
    }
    
    // Formatear fechas de auditoría
    $created_obj = new DateTime($codigo_data['created_at']);
    $codigo_data['created_at_formato'] = $created_obj->format('d/m/Y H:i');
    
    $updated_obj = new DateTime($codigo_data['updated_at']);
    $codigo_data['updated_at_formato'] = $updated_obj->format('d/m/Y H:i');
    
    // Display del estado
    switch ($codigo_data['estado_validez']) {
        case 'activo':
            $codigo_data['estado_display'] = '<span class="badge bg-success">Activo</span>';
            break;
        case 'inactivo':
            $codigo_data['estado_display'] = '<span class="badge bg-secondary">Inactivo</span>';
            break;
        case 'expirado':
            $codigo_data['estado_display'] = '<span class="badge bg-danger">Expirado</span>';
            break;
        case 'agotado':
            $codigo_data['estado_display'] = '<span class="badge bg-warning">Agotado</span>';
            break;
        default:
            $codigo_data['estado_display'] = '<span class="badge bg-secondary">-</span>';
    }
    
    // Información adicional de validación
    $codigo_data['validaciones'] = [
        'codigo_valido' => preg_match('/^[A-Z0-9\-]{3,20}$/', $codigo_data['codigo']),
        'tiene_beneficios' => !empty($codigo_data['beneficio_referente']) && !empty($codigo_data['beneficio_referido']),
        'fechas_validas' => true,
        'limite_consistente' => true
    ];
    
    // Validar fechas
    if ($codigo_data['fecha_fin']) {
        $hoy = new DateTime();
        $fecha_fin = new DateTime($codigo_data['fecha_fin']);
        $codigo_data['validaciones']['fechas_validas'] = $fecha_fin > $hoy;
    }
    
    // Validar límite consistente
    if ($codigo_data['limite_usos'] && $codigo_data['usos_actuales'] > $codigo_data['limite_usos']) {
        $codigo_data['validaciones']['limite_consistente'] = false;
    }
    
    // Información para alertas
    $codigo_data['alertas'] = [];
    
    // Alerta de uso
    if ($codigo_data['limite_usos']) {
        $porcentaje = $codigo_data['porcentaje_uso'];
        if ($porcentaje >= 90) {
            $codigo_data['alertas'][] = [
                'tipo' => 'danger',
                'mensaje' => 'Código cerca de agotarse'
            ];
        } elseif ($porcentaje >= 70) {
            $codigo_data['alertas'][] = [
                'tipo' => 'warning',
                'mensaje' => 'Alto uso del código'
            ];
        }
    }
    
    // Alerta de fecha
    if ($codigo_data['dias_restantes'] !== null) {
        if ($codigo_data['dias_restantes'] == 0) {
            $codigo_data['alertas'][] = [
                'tipo' => 'danger',
                'mensaje' => 'Código expirado'
            ];
        } elseif ($codigo_data['dias_restantes'] <= 7) {
            $codigo_data['alertas'][] = [
                'tipo' => 'warning',
                'mensaje' => 'Código próximo a expirar'
            ];
        }
    }
    
    // Estadísticas adicionales
    $codigo_data['estadisticas'] = [
        'perfil_completo' => !empty($codigo_data['descripcion']) && 
                            !empty($codigo_data['beneficio_referente']) && 
                            !empty($codigo_data['beneficio_referido']),
        'es_personal' => $codigo_data['apoderado_id'] !== null || $codigo_data['familia_id'] !== null,
        'tiene_limite_temporal' => $codigo_data['fecha_fin'] !== null,
        'tiene_limite_usos' => $codigo_data['limite_usos'] !== null,
        'porcentaje_uso' => $codigo_data['porcentaje_uso']
    ];
    
    // Obtener historial de usos (últimos 5)
    $stmt_usos = $conn->prepare("SELECT 
                                    ur.id,
                                    ur.fecha_uso,
                                    ur.convertido,
                                    ur.fecha_conversion,
                                    l.nombres_estudiante,
                                    l.apellidos_estudiante,
                                    CONCAT(l.nombres_contacto, ' ', COALESCE(l.apellidos_contacto, '')) as contacto
                                FROM usos_referido ur
                                LEFT JOIN leads l ON ur.lead_id = l.id
                                WHERE ur.codigo_referido_id = ?
                                ORDER BY ur.fecha_uso DESC
                                LIMIT 5");
    
    if ($stmt_usos) {
        $stmt_usos->bind_param("i", $codigo_id);
        $stmt_usos->execute();
        $result_usos = $stmt_usos->get_result();
        
        $codigo_data['ultimos_usos'] = [];
        while ($uso = $result_usos->fetch_assoc()) {
            $fecha_uso_obj = new DateTime($uso['fecha_uso']);
            $uso['fecha_uso_formato'] = $fecha_uso_obj->format('d/m/Y H:i');
            
            if ($uso['convertido'] && $uso['fecha_conversion']) {
                $fecha_conv_obj = new DateTime($uso['fecha_conversion']);
                $uso['fecha_conversion_formato'] = $fecha_conv_obj->format('d/m/Y');
            }
            
            $codigo_data['ultimos_usos'][] = $uso;
        }
        
        $stmt_usos->close();
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $codigo_data,
        'message' => 'Código obtenido exitosamente'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el código: ' . $e->getMessage(),
        'debug' => $e->getTrace()
    ]);
} finally {
    $conn->close();
}
?>