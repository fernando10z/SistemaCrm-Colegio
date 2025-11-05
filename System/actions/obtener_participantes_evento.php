<?php
/**
 * Archivo: actions/obtener_participantes_evento.php
 * Obtiene la lista de participantes de un evento específico
 */

session_start();
header('Content-Type: application/json');

// Incluir conexión a la base de datos
include '../bd/conexion.php';

// Verificar que se recibió el evento_id
if (!isset($_POST['evento_id']) || empty($_POST['evento_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de evento no proporcionado'
    ]);
    exit;
}

$evento_id = $conn->real_escape_string($_POST['evento_id']);

try {
    // Consulta para obtener participantes del evento
    $sql = "SELECT 
                pe.id,
                pe.evento_id,
                pe.apoderado_id,
                pe.familia_id,
                pe.lead_id,
                pe.exalumno_id,
                pe.estado_participacion,
                pe.fecha_confirmacion,
                pe.fecha_asistencia,
                pe.observaciones,
                pe.created_at,
                -- Datos del apoderado
                CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
                a.tipo_apoderado,
                a.email as apoderado_email,
                a.telefono_principal as apoderado_telefono,
                a.whatsapp as apoderado_whatsapp,
                -- Datos de la familia
                f.codigo_familia,
                f.direccion as familia_direccion,
                f.distrito as familia_distrito,
                -- Datos del lead
                CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as lead_nombre,
                l.email as lead_email,
                l.telefono as lead_telefono,
                -- Datos del exalumno
                CONCAT(ex.nombres, ' ', ex.apellidos) as exalumno_nombre,
                ex.email as exalumno_email,
                ex.telefono as exalumno_telefono
            FROM participantes_evento pe
            LEFT JOIN apoderados a ON pe.apoderado_id = a.id
            LEFT JOIN familias f ON pe.familia_id = f.id
            LEFT JOIN leads l ON pe.lead_id = l.id
            LEFT JOIN exalumnos ex ON pe.exalumno_id = ex.id
            WHERE pe.evento_id = $evento_id
            ORDER BY 
                CASE pe.estado_participacion 
                    WHEN 'confirmado' THEN 1
                    WHEN 'invitado' THEN 2
                    WHEN 'asistio' THEN 3
                    WHEN 'no_asistio' THEN 4
                    WHEN 'cancelado' THEN 5
                    ELSE 6
                END,
                pe.created_at DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $participantes = [];
    
    while ($row = $result->fetch_assoc()) {
        // Determinar el nombre del participante según el tipo
        $nombre = '';
        $tipo_participante = '';
        $contacto = '';
        $info_adicional = '';
        
        if ($row['apoderado_id']) {
            $nombre = $row['apoderado_nombre'];
            $tipo_participante = 'Apoderado ' . ucfirst($row['tipo_apoderado']);
            $contacto = $row['apoderado_email'] ?: $row['apoderado_telefono'] ?: $row['apoderado_whatsapp'] ?: 'Sin contacto';
            $info_adicional = $row['codigo_familia'] ? 'Familia: ' . $row['codigo_familia'] : '';
        } elseif ($row['familia_id']) {
            $nombre = 'Familia ' . $row['codigo_familia'];
            $tipo_participante = 'Familia completa';
            $contacto = $row['familia_distrito'] ?: 'Sin ubicación';
            $info_adicional = substr($row['familia_direccion'], 0, 50) . '...';
        } elseif ($row['lead_id']) {
            $nombre = $row['lead_nombre'];
            $tipo_participante = 'Lead (Prospecto)';
            $contacto = $row['lead_email'] ?: $row['lead_telefono'] ?: 'Sin contacto';
            $info_adicional = 'Prospecto en proceso';
        } elseif ($row['exalumno_id']) {
            $nombre = $row['exalumno_nombre'];
            $tipo_participante = 'Exalumno';
            $contacto = $row['exalumno_email'] ?: $row['exalumno_telefono'] ?: 'Sin contacto';
            $info_adicional = 'Egresado de la institución';
        }
        
        // Determinar clase CSS según el estado
        $estado_class = '';
        switch ($row['estado_participacion']) {
            case 'confirmado':
                $estado_class = 'success';
                break;
            case 'invitado':
                $estado_class = 'warning';
                break;
            case 'asistio':
                $estado_class = 'info';
                break;
            case 'no_asistio':
                $estado_class = 'secondary';
                break;
            case 'cancelado':
                $estado_class = 'danger';
                break;
            default:
                $estado_class = 'secondary';
        }
        
        // Formatear fechas
        $fecha_confirmacion = $row['fecha_confirmacion'] 
            ? date('d/m/Y H:i', strtotime($row['fecha_confirmacion'])) 
            : null;
        $fecha_asistencia = $row['fecha_asistencia'] 
            ? date('d/m/Y H:i', strtotime($row['fecha_asistencia'])) 
            : null;
        $fecha_invitacion = date('d/m/Y H:i', strtotime($row['created_at']));
        
        $participantes[] = [
            'id' => $row['id'],
            'nombre' => $nombre ?: 'Participante sin nombre',
            'tipo_participante' => $tipo_participante,
            'contacto' => $contacto,
            'info_adicional' => $info_adicional,
            'estado' => ucfirst(str_replace('_', ' ', $row['estado_participacion'])),
            'estado_raw' => $row['estado_participacion'],
            'estado_class' => $estado_class,
            'fecha_confirmacion' => $fecha_confirmacion,
            'fecha_asistencia' => $fecha_asistencia,
            'fecha_invitacion' => $fecha_invitacion,
            'observaciones' => $row['observaciones'] ?: '',
            'apoderado_id' => $row['apoderado_id'],
            'familia_id' => $row['familia_id'],
            'lead_id' => $row['lead_id'],
            'exalumno_id' => $row['exalumno_id']
        ];
    }
    
    // Obtener estadísticas del evento
    $sql_stats = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN estado_participacion = 'invitado' THEN 1 END) as invitados,
                    COUNT(CASE WHEN estado_participacion = 'confirmado' THEN 1 END) as confirmados,
                    COUNT(CASE WHEN estado_participacion = 'asistio' THEN 1 END) as asistieron,
                    COUNT(CASE WHEN estado_participacion = 'no_asistio' THEN 1 END) as no_asistieron,
                    COUNT(CASE WHEN estado_participacion = 'cancelado' THEN 1 END) as cancelados,
                    ROUND(AVG(CASE WHEN estado_participacion = 'asistio' THEN 1 ELSE 0 END) * 100, 2) as tasa_asistencia
                  FROM participantes_evento 
                  WHERE evento_id = $evento_id";
    
    $result_stats = $conn->query($sql_stats);
    $stats = $result_stats->fetch_assoc();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Participantes obtenidos correctamente',
        'data' => $participantes,
        'stats' => $stats,
        'total_participantes' => count($participantes)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener participantes: ' . $e->getMessage()
    ]);
}

$conn->close();
?>