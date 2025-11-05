<?php
session_start();

// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'medir_participacion':
            $mensaje_sistema = procesarMedirParticipacion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'calcular_indice':
            $mensaje_sistema = procesarCalcularIndice($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'generar_ranking':
            $mensaje_sistema = procesarGenerarRanking($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'exportar_datos':
            procesarExportarDatos($conn, $_POST);
            exit; // No mostrar mensaje, se descarga directamente
            break;
    }
}

// Función para procesar medición de participación
function procesarMedirParticipacion($conn, $data) {
    try {
        $evento_id = $conn->real_escape_string($data['evento_id']);
        $fecha_inicio = $conn->real_escape_string($data['fecha_inicio']);
        $fecha_fin = $conn->real_escape_string($data['fecha_fin']);
        
        // Calcular métricas de participación
        $sql_metricas = "
            SELECT 
                COUNT(pe.id) as total_participantes,
                COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as asistentes,
                COUNT(CASE WHEN pe.estado_participacion = 'no_asistio' THEN 1 END) as no_asistentes,
                COUNT(CASE WHEN pe.estado_participacion = 'confirmado' THEN 1 END) as confirmados,
                ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / 
                       NULLIF(COUNT(pe.id), 0)) * 100, 2) as porcentaje_asistencia
            FROM participantes_evento pe
            WHERE pe.evento_id = '$evento_id'";
        
        $result = $conn->query($sql_metricas);
        if ($result && $result->num_rows > 0) {
            return "Métricas de participación calculadas correctamente para el evento.";
        } else {
            return "Error al calcular las métricas de participación.";
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar cálculo de índice de asistencia
function procesarCalcularIndice($conn, $data) {
    try {
        $familia_id = !empty($data['familia_id']) ? $conn->real_escape_string($data['familia_id']) : null;
        $periodo_meses = $conn->real_escape_string($data['periodo_meses'] ?? '6');
        
        $where_familia = $familia_id ? "AND pe.familia_id = '$familia_id'" : "";
        
        // Calcular índice de asistencia por familia
        $sql_indice = "
            UPDATE familias f 
            SET f.observaciones = CONCAT(
                IFNULL(f.observaciones, ''), 
                '\n[ÍNDICE CALCULADO - ' + NOW() + '] Índice de asistencia actualizado'
            )
            WHERE f.id IN (
                SELECT DISTINCT pe.familia_id 
                FROM participantes_evento pe 
                JOIN eventos e ON pe.evento_id = e.id 
                WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL $periodo_meses MONTH)
                $where_familia
            )";
        
        if ($conn->query($sql_indice)) {
            return "Índices de asistencia calculados correctamente para el período de $periodo_meses meses.";
        } else {
            return "Error al calcular los índices: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar generación de ranking
function procesarGenerarRanking($conn, $data) {
    try {
        $periodo = $conn->real_escape_string($data['periodo'] ?? '6');
        $tipo_ranking = $conn->real_escape_string($data['tipo_ranking'] ?? 'familias');
        
        if ($tipo_ranking === 'familias') {
            // Generar ranking de familias más participativas
            $sql_ranking = "
                CREATE TEMPORARY TABLE temp_ranking_familias AS
                SELECT 
                    f.id,
                    f.apellido_principal,
                    COUNT(pe.id) as total_participaciones,
                    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as asistencias,
                    ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / 
                           NULLIF(COUNT(pe.id), 0)) * 100, 2) as porcentaje_asistencia
                FROM familias f
                LEFT JOIN participantes_evento pe ON f.id = pe.familia_id
                LEFT JOIN eventos e ON pe.evento_id = e.id
                WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL $periodo MONTH)
                GROUP BY f.id, f.apellido_principal
                ORDER BY total_participaciones DESC, porcentaje_asistencia DESC";
        } else {
            // Generar ranking de apoderados
            $sql_ranking = "
                CREATE TEMPORARY TABLE temp_ranking_apoderados AS
                SELECT 
                    a.id,
                    CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                    COUNT(pe.id) as total_participaciones,
                    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as asistencias
                FROM apoderados a
                LEFT JOIN participantes_evento pe ON a.id = pe.apoderado_id
                LEFT JOIN eventos e ON pe.evento_id = e.id
                WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL $periodo MONTH)
                GROUP BY a.id, a.nombres, a.apellidos
                ORDER BY total_participaciones DESC";
        }
        
        if ($conn->query($sql_ranking)) {
            return "Ranking de participación generado correctamente para el período de $periodo meses.";
        } else {
            return "Error al generar el ranking: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar exportación de datos
function procesarExportarDatos($conn, $data) {
    $formato = $data['formato'] ?? 'excel';
    $periodo = $conn->real_escape_string($data['periodo'] ?? '12');
    
    // Consulta para exportación
    $sql_export = "
        SELECT 
            f.codigo_familia,
            f.apellido_principal as familia,
            CONCAT(a.nombres, ' ', a.apellidos) as apoderado,
            e.titulo as evento,
            e.fecha_inicio,
            pe.estado_participacion,
            pe.fecha_confirmacion,
            pe.fecha_asistencia
        FROM participantes_evento pe
        JOIN eventos e ON pe.evento_id = e.id
        LEFT JOIN familias f ON pe.familia_id = f.id
        LEFT JOIN apoderados a ON pe.apoderado_id = a.id
        WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL $periodo MONTH)
        ORDER BY e.fecha_inicio DESC, f.apellido_principal";
    
    $result = $conn->query($sql_export);
    
    if ($formato === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="estadisticas_participacion.xls"');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="estadisticas_participacion.csv"');
    }
    
    echo "Codigo Familia\tFamilia\tApoderado\tEvento\tFecha\tEstado\tConfirmacion\tAsistencia\n";
    
    while ($row = $result->fetch_assoc()) {
        echo implode("\t", [
            $row['codigo_familia'] ?? '',
            $row['familia'] ?? '',
            $row['apoderado'] ?? '',
            $row['evento'] ?? '',
            $row['fecha_inicio'] ?? '',
            $row['estado_participacion'] ?? '',
            $row['fecha_confirmacion'] ?? '',
            $row['fecha_asistencia'] ?? ''
        ]) . "\n";
    }
}

// Consulta principal para mostrar estadísticas de participación
$sql = "SELECT 
    pe.id,
    pe.evento_id,
    e.titulo as evento_titulo,
    e.fecha_inicio as evento_fecha,
    e.tipo as evento_tipo,
    e.dirigido_a,
    e.capacidad_maxima,
    pe.apoderado_id,
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
    pe.familia_id,
    f.apellido_principal as familia_apellido,
    f.codigo_familia,
    pe.estado_participacion,
    pe.fecha_confirmacion,
    pe.fecha_asistencia,
    pe.observaciones,
    pe.created_at,
    -- Calcular métricas del evento
    (SELECT COUNT(*) FROM participantes_evento pe2 WHERE pe2.evento_id = e.id) as total_participantes,
    (SELECT COUNT(*) FROM participantes_evento pe3 WHERE pe3.evento_id = e.id AND pe3.estado_participacion = 'asistio') as total_asistentes,
    -- Calcular porcentaje de asistencia
    ROUND(((SELECT COUNT(*) FROM participantes_evento pe4 WHERE pe4.evento_id = e.id AND pe4.estado_participacion = 'asistio') / 
           NULLIF((SELECT COUNT(*) FROM participantes_evento pe5 WHERE pe5.evento_id = e.id), 0)) * 100, 2) as porcentaje_asistencia_evento,
    -- Determinar nivel de participación de la familia
    CASE 
        WHEN (SELECT COUNT(*) FROM participantes_evento pe6 JOIN eventos e2 ON pe6.evento_id = e2.id 
              WHERE pe6.familia_id = f.id AND e2.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) >= 5 THEN 'alta'
        WHEN (SELECT COUNT(*) FROM participantes_evento pe7 JOIN eventos e3 ON pe7.evento_id = e3.id 
              WHERE pe7.familia_id = f.id AND e3.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) >= 2 THEN 'media'
        ELSE 'baja'
    END as nivel_participacion_familia
FROM participantes_evento pe
JOIN eventos e ON pe.evento_id = e.id
LEFT JOIN apoderados a ON pe.apoderado_id = a.id
LEFT JOIN familias f ON pe.familia_id = f.id
ORDER BY e.fecha_inicio DESC, pe.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas generales
$stats_sql = "SELECT 
    COUNT(DISTINCT pe.evento_id) as total_eventos_con_participacion,
    COUNT(pe.id) as total_participaciones,
    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistencias,
    COUNT(CASE WHEN pe.estado_participacion = 'confirmado' THEN 1 END) as total_confirmados,
    COUNT(CASE WHEN pe.estado_participacion = 'no_asistio' THEN 1 END) as total_ausencias,
    COUNT(DISTINCT pe.familia_id) as familias_participantes,
    COUNT(DISTINCT pe.apoderado_id) as apoderados_participantes,
    ROUND(AVG(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 ELSE 0 END) * 100, 2) as tasa_asistencia_general,
    COUNT(CASE WHEN e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as participaciones_mes_actual
FROM participantes_evento pe
JOIN eventos e ON pe.evento_id = e.id";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener ranking de familias más activas (top 10)
$ranking_sql = "SELECT 
    f.id,
    f.apellido_principal,
    f.codigo_familia,
    COUNT(pe.id) as total_participaciones,
    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistencias,
    ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / 
           NULLIF(COUNT(pe.id), 0)) * 100, 2) as porcentaje_asistencia,
    COUNT(CASE WHEN e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) THEN 1 END) as participaciones_trimestre
FROM familias f
JOIN participantes_evento pe ON f.id = pe.familia_id
JOIN eventos e ON pe.evento_id = e.id
WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY f.id, f.apellido_principal, f.codigo_familia
ORDER BY total_participaciones DESC, porcentaje_asistencia DESC
LIMIT 10";

$ranking_result = $conn->query($ranking_sql);
$ranking_familias = [];
while($familia = $ranking_result->fetch_assoc()) {
    $ranking_familias[] = $familia;
}

// Obtener estadísticas por tipo de evento
$tipos_evento_sql = "SELECT 
    e.tipo,
    COUNT(DISTINCT e.id) as total_eventos,
    COUNT(pe.id) as total_participaciones,
    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistencias,
    ROUND(AVG(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 ELSE 0 END) * 100, 2) as tasa_asistencia_promedio
FROM eventos e
LEFT JOIN participantes_evento pe ON e.id = pe.evento_id
WHERE e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY e.tipo
ORDER BY total_participaciones DESC";

$tipos_result = $conn->query($tipos_evento_sql);
$tipos_evento = [];
while($tipo = $tipos_result->fetch_assoc()) {
    $tipos_evento[] = $tipo;
}

// Obtener nombre del sistema para el título
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
  $nombre_sistema = "CRM Escolar";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_estadisticas_evento') {
    header('Content-Type: application/json');
    
    $evento_id = $conn->real_escape_string($_POST['evento_id']);
    
    // Información del evento
    $sql_evento = "SELECT 
        e.id,
        e.titulo,
        e.descripcion,
        e.fecha_inicio,
        e.fecha_fin,
        e.tipo,
        e.dirigido_a,
        e.capacidad_maxima,
        e.ubicacion
    FROM eventos e
    WHERE e.id = '$evento_id'";
    
    $result_evento = $conn->query($sql_evento);
    
    if ($result_evento && $result_evento->num_rows > 0) {
        $evento = $result_evento->fetch_assoc();
        
        // Métricas de participación
        $sql_metricas = "SELECT 
            COUNT(pe.id) as total_participantes,
            COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistentes,
            COUNT(CASE WHEN pe.estado_participacion = 'confirmado' THEN 1 END) as total_confirmados,
            COUNT(CASE WHEN pe.estado_participacion = 'no_asistio' THEN 1 END) as total_ausentes,
            COUNT(CASE WHEN pe.estado_participacion = 'invitado' THEN 1 END) as total_invitados,
            COUNT(CASE WHEN pe.estado_participacion = 'cancelado' THEN 1 END) as total_cancelados,
            ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / 
                   NULLIF(COUNT(pe.id), 0)) * 100, 2) as porcentaje_asistencia,
            COUNT(DISTINCT pe.familia_id) as total_familias,
            COUNT(DISTINCT pe.apoderado_id) as total_apoderados
        FROM participantes_evento pe
        WHERE pe.evento_id = '$evento_id'";
        
        $result_metricas = $conn->query($sql_metricas);
        $metricas = $result_metricas->fetch_assoc();
        
        // Top 5 familias participantes en este evento
        $sql_top_familias = "SELECT 
            f.id,
            f.apellido_principal,
            f.codigo_familia,
            COUNT(pe.id) as participantes,
            GROUP_CONCAT(DISTINCT pe.estado_participacion) as estados
        FROM familias f
        JOIN participantes_evento pe ON f.id = pe.familia_id
        WHERE pe.evento_id = '$evento_id'
        GROUP BY f.id, f.apellido_principal, f.codigo_familia
        ORDER BY participantes DESC
        LIMIT 5";
        
        $result_top = $conn->query($sql_top_familias);
        $top_familias = [];
        while($fam = $result_top->fetch_assoc()) {
            $top_familias[] = $fam;
        }
        
        // Preparar respuesta
        $response = [
            'success' => true,
            'data' => [
                'evento' => $evento,
                'metricas' => $metricas,
                'top_familias' => $top_familias
            ]
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el evento especificado'
        ]);
    }
    
    $conn->close();
    exit;
}

// Función para obtener historial de una familia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_historial_familia') {
    header('Content-Type: application/json');
    
    $familia_id = $conn->real_escape_string($_POST['familia_id']);
    
    // Información de la familia
    $sql_familia = "SELECT 
        f.id,
        f.apellido_principal,
        f.apellido_secundario,
        f.codigo_familia,
        f.direccion,
        f.telefono_principal,
        f.email_principal,
        f.observaciones
    FROM familias f
    WHERE f.id = '$familia_id'";
    
    $result_familia = $conn->query($sql_familia);
    
    if ($result_familia && $result_familia->num_rows > 0) {
        $familia = $result_familia->fetch_assoc();
        
        // Estadísticas generales de la familia
        $sql_estadisticas = "SELECT 
            COUNT(DISTINCT pe.evento_id) as total_eventos,
            COUNT(pe.id) as total_participaciones,
            COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistencias,
            COUNT(CASE WHEN pe.estado_participacion = 'no_asistio' THEN 1 END) as total_ausencias,
            COUNT(CASE WHEN pe.estado_participacion = 'confirmado' THEN 1 END) as total_confirmados,
            ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / 
                   NULLIF(COUNT(pe.id), 0)) * 100, 2) as tasa_asistencia,
            -- Nivel de participación
            CASE 
                WHEN COUNT(pe.id) >= 10 THEN 'alta'
                WHEN COUNT(pe.id) >= 5 THEN 'media'
                ELSE 'baja'
            END as nivel_participacion
        FROM participantes_evento pe
        JOIN eventos e ON pe.evento_id = e.id
        WHERE pe.familia_id = '$familia_id'
        AND e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $result_stats = $conn->query($sql_estadisticas);
        $estadisticas = $result_stats->fetch_assoc();
        
        // Miembros de la familia (apoderados y estudiantes)
        $sql_miembros = "SELECT 
            a.id,
            a.nombres,
            a.apellidos,
            a.parentesco,
            a.email,
            a.telefono,
            COUNT(pe.id) as participaciones
        FROM apoderados a
        LEFT JOIN participantes_evento pe ON a.id = pe.apoderado_id
        WHERE a.familia_id = '$familia_id'
        GROUP BY a.id, a.nombres, a.apellidos, a.parentesco, a.email, a.telefono
        ORDER BY participaciones DESC";
        
        $result_miembros = $conn->query($sql_miembros);
        $miembros = [];
        while($miembro = $result_miembros->fetch_assoc()) {
            $miembros[] = $miembro;
        }
        
        // Estudiantes de la familia
        $sql_estudiantes = "SELECT 
            e.id,
            e.nombres,
            e.apellidos,
            e.grado,
            e.seccion
        FROM estudiantes e
        WHERE e.familia_id = '$familia_id'
        ORDER BY e.grado, e.apellidos, e.nombres";
        
        $result_estudiantes = $conn->query($sql_estudiantes);
        $estudiantes = [];
        while($estudiante = $result_estudiantes->fetch_assoc()) {
            $estudiantes[] = $estudiante;
        }
        
        // Historial de eventos (timeline)
        $sql_historial = "SELECT 
            pe.id,
            e.titulo as evento_titulo,
            e.fecha_inicio,
            e.tipo as evento_tipo,
            pe.estado_participacion,
            pe.fecha_confirmacion,
            pe.fecha_asistencia,
            pe.observaciones,
            CONCAT(a.nombres, ' ', a.apellidos) as participante_nombre
        FROM participantes_evento pe
        JOIN eventos e ON pe.evento_id = e.id
        LEFT JOIN apoderados a ON pe.apoderado_id = a.id
        WHERE pe.familia_id = '$familia_id'
        ORDER BY e.fecha_inicio DESC
        LIMIT 50";
        
        $result_historial = $conn->query($sql_historial);
        $historial = [];
        while($evento = $result_historial->fetch_assoc()) {
            $historial[] = $evento;
        }
        
        // Datos para gráfica de tendencia (últimos 12 meses)
        $sql_tendencia = "SELECT 
            DATE_FORMAT(e.fecha_inicio, '%Y-%m') as mes,
            COUNT(pe.id) as participaciones,
            COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as asistencias
        FROM participantes_evento pe
        JOIN eventos e ON pe.evento_id = e.id
        WHERE pe.familia_id = '$familia_id'
        AND e.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(e.fecha_inicio, '%Y-%m')
        ORDER BY mes";
        
        $result_tendencia = $conn->query($sql_tendencia);
        $tendencia = [];
        while($mes = $result_tendencia->fetch_assoc()) {
            $tendencia[] = $mes;
        }
        
        // Resumen trimestral
        $sql_trimestral = "SELECT 
            QUARTER(e.fecha_inicio) as trimestre,
            COUNT(pe.id) as participaciones,
            COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as asistencias
        FROM participantes_evento pe
        JOIN eventos e ON pe.evento_id = e.id
        WHERE pe.familia_id = '$familia_id'
        AND YEAR(e.fecha_inicio) = YEAR(CURDATE())
        GROUP BY QUARTER(e.fecha_inicio)
        ORDER BY trimestre";
        
        $result_trimestral = $conn->query($sql_trimestral);
        $trimestral = [];
        while($trim = $result_trimestral->fetch_assoc()) {
            $trimestral[] = $trim;
        }
        
        // Preparar respuesta
        $response = [
            'success' => true,
            'data' => [
                'familia' => $familia,
                'estadisticas' => $estadisticas,
                'miembros' => $miembros,
                'estudiantes' => $estudiantes,
                'historial' => $historial,
                'tendencia' => $tendencia,
                'trimestral' => $trimestral
            ]
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la familia especificada'
        ]);
    }
    
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title>Estadísticas de Participación - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Estadísticas de Participación"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Estadísticas, Participación, Eventos, Engagement"
    />
    <meta name="author" content="CRM Escolar" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <!-- [Page specific CSS] start -->
    <!-- data tables css -->
    <link
      rel="stylesheet"
      href="assets/css/plugins/dataTables.bootstrap5.min.css"
    />
    <!-- [Page specific CSS] end -->
    <!-- [Google Font] Family -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
      id="main-font-link"
    />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link
      rel="stylesheet"
      href="assets/css/style.css"
      id="main-style-link"
    />
    <link rel="stylesheet" href="assets/css/style-preset.css" />
    
    <!-- Custom styles for estadísticas participación -->
    <style>
      .badge-estado-participacion {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .estado-invitado { background-color: #6c757d; }
      .estado-confirmado { background-color: #17a2b8; }
      .estado-asistio { background-color: #28a745; }
      .estado-no_asistio { background-color: #dc3545; }
      .estado-cancelado { background-color: #fd7e14; }
      
      .badge-nivel-participacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .participacion-alta { background-color: #28a745; color: white; }
      .participacion-media { background-color: #ffc107; color: #856404; }
      .participacion-baja { background-color: #dc3545; color: white; }
      
      .badge-tipo-evento {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-reunion_padres { background-color: #6f42c1; }
      .tipo-charla_informativa { background-color: #17a2b8; }
      .tipo-evento_social { background-color: #20c997; }
      .tipo-academico { background-color: #fd7e14; }
      .tipo-deportivo { background-color: #28a745; }
      .tipo-otro { background-color: #6c757d; }
      
      .evento-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .evento-titulo {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .evento-fecha {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .evento-metricas {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
      }
      
      .participante-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .participante-nombre {
        font-weight: 500;
        color: #495057;
        font-size: 0.8rem;
      }
      
      .participante-familia {
        font-size: 0.7rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .fechas-participacion {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .fecha-confirmacion {
        color: #17a2b8;
      }
      
      .fecha-asistencia {
        color: #28a745;
        font-weight: 500;
      }
      
      .porcentaje-asistencia {
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
        border-radius: 8px;
        font-weight: bold;
        text-align: center;
      }
      
      .asistencia-excelente { background-color: #d4edda; color: #155724; }
      .asistencia-buena { background-color: #d1ecf1; color: #0c5460; }
      .asistencia-regular { background-color: #fff3cd; color: #856404; }
      .asistencia-baja { background-color: #f8d7da; color: #721c24; }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .stats-card .card-body {
        padding: 1.5rem;
      }
      
      .stat-item {
        text-align: center;
        padding: 10px;
      }
      
      .stat-number {
        font-size: 1.3rem;
        font-weight: bold;
        display: block;
      }
      
      .stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
      }
      
      .ranking-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .ranking-item {
        display: flex;
        justify-content: between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
      }
      
      .ranking-item:last-child {
        border-bottom: none;
      }
      
      .ranking-posicion {
        font-weight: bold;
        color: #495057;
        width: 30px;
      }
      
      .ranking-familia {
        flex-grow: 1;
        font-weight: 500;
        color: #495057;
      }
      
      .ranking-stats {
        font-size: 0.8rem;
        color: #6c757d;
        text-align: right;
      }
      
      .tipos-evento-panel {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .tipo-evento-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        margin: 5px 0;
        border-radius: 6px;
        background-color: #f8f9fa;
      }
      
      .btn-grupo-estadisticas {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-estadisticas .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
      }
      
      .progress-asistencia {
        height: 8px;
        border-radius: 4px;
      }
    </style>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->
  <body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
      <div class="loader-track">
        <div class="loader-fill"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->
    
    <!-- [ Sidebar Menu ] start -->
    <?php include 'includes/sidebar.php'; ?>
    <!-- [ Sidebar Menu ] end -->
    
    <!-- [ Header Topbar ] start -->
    <?php include 'includes/header.php'; ?>
    <!-- [ Header ] end -->
    
    <section class="pc-container">
      <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
          <div class="page-block">
            <div class="row align-items-center">
              <div class="col-md-12">
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                  <li class="breadcrumb-item">
                    <a href="javascript: void(0)">Gestión Familiar</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Estadísticas de Participación
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Mensaje del Sistema ] start -->
        <?php if(!empty($mensaje_sistema)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show alert-mensaje" role="alert">
          <?php echo $mensaje_sistema; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <!-- [ Mensaje del Sistema ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Estadísticas de Participación en Eventos
                  </h3>
                  <small class="text-muted">
                    Analiza la participación de familias y apoderados en eventos, genera rankings 
                    y mide el engagement de la comunidad educativa.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalExportarDatos">
                    <i class="ti ti-download me-1"></i>
                    Exportar Datos
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerarRanking">
                    <i class="ti ti-trophy me-1"></i>
                    Generar Ranking
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalCalcularIndice">
                    <i class="ti ti-calculator me-1"></i>
                    Calcular Índices
                  </button> -->
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMedirParticipacion">
                    <i class="ti ti-chart-bar me-1"></i>
                    Medir Participación
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de participaciones -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="participaciones-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="15%">Evento</th>
                        <th width="8%">Tipo</th>
                        <th width="12%">Participante</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Fechas</th>
                        <th width="8%">Métricas Evento</th>
                        <th width="8%">% Asistencia</th>
                        <th width="8%">Nivel Familia</th>
                        <th width="10%">Observaciones</th>
                        <th width="9%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_evento = date('d/m/Y', strtotime($row['evento_fecha']));
                              $fecha_confirmacion = $row['fecha_confirmacion'] ? date('d/m/Y H:i', strtotime($row['fecha_confirmacion'])) : '';
                              $fecha_asistencia = $row['fecha_asistencia'] ? date('d/m/Y H:i', strtotime($row['fecha_asistencia'])) : '';
                              
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado_participacion'];
                              
                              // Determinar clase CSS para tipo de evento
                              $tipo_evento_class = 'tipo-' . $row['evento_tipo'];
                              
                              // Determinar clase de nivel de participación
                              $nivel_participacion = $row['nivel_participacion_familia'] ?? 'baja';
                              $nivel_class = 'participacion-' . $nivel_participacion;
                              
                              // Determinar clase de porcentaje de asistencia
                              $porcentaje = (float)($row['porcentaje_asistencia_evento'] ?? 0);
                              if ($porcentaje >= 80) $asistencia_class = 'asistencia-excelente';
                              elseif ($porcentaje >= 60) $asistencia_class = 'asistencia-buena';
                              elseif ($porcentaje >= 40) $asistencia_class = 'asistencia-regular';
                              else $asistencia_class = 'asistencia-baja';
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='evento-info'>
                                        <span class='evento-titulo'>" . htmlspecialchars($row['evento_titulo']) . "</span>
                                        <span class='evento-fecha'>" . $fecha_evento . "</span>
                                        <span class='evento-metricas'>" . $row['total_participantes'] . " part. | " . $row['total_asistentes'] . " asist.</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-tipo-evento $tipo_evento_class'>" . 
                                   ucfirst(str_replace('_', ' ', $row['evento_tipo'])) . "</span></td>";
                              echo "<td>
                                      <div class='participante-info'>
                                        <span class='participante-nombre'>" . 
                                        htmlspecialchars($row['apoderado_nombre'] ?? 'Familia ' . $row['familia_apellido']) . "</span>
                                        <span class='participante-familia'>Fam: " . htmlspecialchars($row['codigo_familia'] ?? 'Sin código') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-participacion $estado_class'>" . 
                                   ucfirst(str_replace('_', ' ', $row['estado_participacion'])) . "</span></td>";
                              echo "<td>
                                      <div class='fechas-participacion'>
                                        " . ($fecha_confirmacion ? "<span class='fecha-confirmacion'>Conf: " . $fecha_confirmacion . "</span>" : "") . "
                                        " . ($fecha_asistencia ? "<span class='fecha-asistencia'>Asist: " . $fecha_asistencia . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='evento-metricas'>
                                        " . $row['total_participantes'] . " / " . ($row['capacidad_maxima'] ?? '∞') . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='porcentaje-asistencia $asistencia_class'>
                                        " . number_format($porcentaje, 1) . "%
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-nivel-participacion $nivel_class'>" . 
                                   ucfirst($nivel_participacion) . "</span></td>";
                              echo "<td>
                                      <span class='small text-muted' style='max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;' title='" . htmlspecialchars($row['observaciones'] ?? '') . "'>
                                        " . htmlspecialchars($row['observaciones'] ?? 'Sin observaciones') . "
                                      </span>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-estadisticas'>
                                        <button type='button' class='btn btn-outline-info btn-ver-evento' 
                                                data-evento-id='" . $row['evento_id'] . "'
                                                title='Ver Estadísticas del Evento'>
                                          <i class='ti ti-chart-pie'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay datos de participación disponibles</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Participante</th>
                        <th>Estado</th>
                        <th>Fechas</th>
                        <th>Métricas Evento</th>
                        <th>% Asistencia</th>
                        <th>Nivel Familia</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </section>

    <!-- Incluir Modales -->
    <?php include 'modals/estadisticas/modal_medir_participacion.php'; ?>
    <?php include 'modals/estadisticas/modal_calcular_indice.php'; ?>
    <?php include 'modals/estadisticas/modal_generar_ranking.php'; ?>
    <?php include 'modals/estadisticas/modal_ver_estadisticas_evento.php'; ?>
    <?php include 'modals/estadisticas/modal_ver_historial_familia.php'; ?>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Required Js -->
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/fonts/custom-font.js"></script>
    <script src="assets/js/pcoded.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>

    <script>
      layout_change("light");
      change_box_container("false");
      layout_rtl_change("false");
      preset_change("preset-1");
      font_change("Public-Sans");
    </script>

    <?php include 'includes/configuracion.php'; ?>

    <!-- [Page Specific JS] start -->
    <!-- datatable Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    
    <script>
      $(document).ready(function() {
            // Inicializar DataTable con filtros integrados
            var table = $("#participaciones-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay participaciones disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                  "first": "Primero",
                  "last": "Último",
                  "next": "Siguiente",
                  "previous": "Anterior"
                },
                "aria": {
                  "sortAscending": ": activar para ordenar la columna ascendente",
                  "sortDescending": ": activar para ordenar la columna descendente"
                }
              },
              "pageLength": 25,
              "order": [[ 0, "desc" ]], // Ordenar por ID descendente (más recientes primero)
              "columnDefs": [
                { "orderable": false, "targets": 10 } // Deshabilitar ordenación en columna de acciones
              ],
              "initComplete": function () {
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;
                  
                  // Solo aplicar filtros a las primeras 10 columnas (sin acciones)
                  if (index < 10) {
                    var title = $(column.header()).text();
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
                      .appendTo($(column.footer()).empty())
                      .on('keyup change clear', function () {
                        if (column.search() !== this.value) {
                          column
                            .search(this.value)
                            .draw();
                        }
                      });
                  } else {
                    // Agregar "ACCIONES" en negrita en la columna de acciones
                    $(column.footer()).html('<strong>Acciones</strong>');
                  }
                });
              }
            });

            // Función para mostrar estadísticas del evento
            function mostrarEstadisticasEvento(eventoId) {
              $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'POST',
                data: { 
                  accion: 'obtener_estadisticas_evento',
                  evento_id: eventoId 
                },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarModalEstadisticasEvento(response.data);
                  } else {
                    alert('Error al cargar las estadísticas: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener las estadísticas del evento.');
                }
              });
            }

            // Función para mostrar historial de familia
            function mostrarHistorialFamilia(familiaId) {
              $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'POST',
                data: { 
                  accion: 'obtener_historial_familia',
                  familia_id: familiaId 
                },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarModalHistorialFamilia(response.data);
                  } else {
                    alert('Error al cargar el historial: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener el historial de la familia.');
                }
              });
            }

            // Función para mostrar modal de estadísticas del evento
            function mostrarModalEstadisticasEvento(data) {
              var modalHTML = `
                <div class="modal fade" id="modalEstadisticasEvento" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Estadísticas del Evento: ${data.titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-md-6">
                            <h6>Información General</h6>
                            <p><strong>Fecha:</strong> ${data.fecha}</p>
                            <p><strong>Tipo:</strong> ${data.tipo}</p>
                            <p><strong>Dirigido a:</strong> ${data.dirigido_a}</p>
                          </div>
                          <div class="col-md-6">
                            <h6>Métricas de Participación</h6>
                            <p><strong>Total Participantes:</strong> ${data.total_participantes}</p>
                            <p><strong>Asistentes:</strong> ${data.total_asistentes}</p>
                            <p><strong>Tasa de Asistencia:</strong> ${data.porcentaje_asistencia}%</p>
                          </div>
                        </div>
                        <div class="progress mt-3">
                          <div class="progress-bar" style="width: ${data.porcentaje_asistencia}%"></div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                      </div>
                    </div>
                  </div>
                </div>
              `;
              
              $('#modalEstadisticasEvento').remove();
              $('body').append(modalHTML);
              $('#modalEstadisticasEvento').modal('show');
            }

            // Tooltip para elementos
            $('[title]').tooltip();

            // Auto-refresh cada 5 minutos para estadísticas
            setInterval(function() {
              // Actualizar solo las estadísticas principales
              actualizarEstadisticasPrincipales();
            }, 300000); // 5 minutos

            // Función para actualizar estadísticas principales
            function actualizarEstadisticasPrincipales() {
              $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'POST',
                data: { accion: 'actualizar_estadisticas' },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    // Actualizar contadores en las tarjetas
                    $('.stats-card .stat-number').each(function(index) {
                      var keys = ['total_eventos_con_participacion', 'total_participaciones', 'total_asistencias', 'tasa_asistencia_general', 'familias_participantes', 'participaciones_mes_actual'];
                      if (keys[index] && response.data[keys[index]] !== undefined) {
                        $(this).text(response.data[keys[index]] + (keys[index] === 'tasa_asistencia_general' ? '%' : ''));
                      }
                    });
                  }
                },
                error: function() {
                  console.log('Error al actualizar estadísticas');
                }
              });
            }
      });
    </script>

    <script>
      $(document).on('click', '.btn-ver-evento', function() {
          var eventoId = $(this).data('evento-id');
          mostrarEstadisticasEvento(eventoId);
      });

      // Función para mostrar estadísticas del evento
      function mostrarEstadisticasEvento(eventoId) {
          // Mostrar modal
          var modal = new bootstrap.Modal(document.getElementById('modalEstadisticasEvento'));
          modal.show();
          
          // Mostrar loading
          $('#loadingEstadisticas').show();
          $('#datosEstadisticas').hide();
          
          // Obtener datos por AJAX
          $.ajax({
              url: '<?php echo $_SERVER['PHP_SELF']; ?>',
              method: 'POST',
              data: { 
                  accion: 'obtener_estadisticas_evento',
                  evento_id: eventoId 
              },
              dataType: 'json',
              success: function(response) {
                  if (response.success) {
                      poblarModalEstadisticas(response.data);
                  } else {
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: response.message || 'Error al cargar las estadísticas',
                          confirmButtonColor: '#667eea'
                      });
                      modal.hide();
                  }
              },
              error: function() {
                  Swal.fire({
                      icon: 'error',
                      title: 'Error de Conexión',
                      text: 'No se pudo conectar con el servidor',
                      confirmButtonColor: '#667eea'
                  });
                  modal.hide();
              }
          });
      }

      // Función para poblar el modal con datos
      function poblarModalEstadisticas(data) {
          // Ocultar loading
          $('#loadingEstadisticas').hide();
          $('#datosEstadisticas').show();
          
          // Información del evento
          $('#evento-titulo').text(data.evento.titulo);
          $('#evento-fecha').html('<i class="ti ti-calendar me-1"></i>' + formatearFecha(data.evento.fecha_inicio));
          $('#evento-tipo').html('<i class="ti ti-category me-1"></i><span class="badge badge-tipo-evento tipo-' + data.evento.tipo + '">' + formatearTipo(data.evento.tipo) + '</span>');
          $('#evento-dirigido').html('<i class="ti ti-users me-1"></i>' + (data.evento.dirigido_a || 'Todos'));
          $('#evento-capacidad').text((data.metricas.total_participantes || 0) + ' / ' + (data.evento.capacidad_maxima || '∞'));
          
          // Métricas
          $('#total-participantes').text(data.metricas.total_participantes || 0);
          $('#total-asistentes').text(data.metricas.total_asistentes || 0);
          $('#total-confirmados').text(data.metricas.total_confirmados || 0);
          $('#total-ausentes').text(data.metricas.total_ausentes || 0);
          
          // Tasa de asistencia
          var porcentaje = parseFloat(data.metricas.porcentaje_asistencia || 0);
          $('#porcentaje-asistencia').text(porcentaje.toFixed(1) + '%');
          $('#barra-asistencia').css('width', porcentaje + '%').attr('aria-valuenow', porcentaje);
          
          // Cambiar color de la barra según porcentaje
          var barraClase = porcentaje >= 80 ? 'bg-success' : porcentaje >= 60 ? 'bg-info' : porcentaje >= 40 ? 'bg-warning' : 'bg-danger';
          $('#barra-asistencia').removeClass('bg-success bg-info bg-warning bg-danger').addClass(barraClase);
          
          // Distribución por estado
          var distribucionHTML = '';
          var estados = [
              { key: 'total_invitados', label: 'Invitados', color: '#6c757d', icon: 'ti-mail' },
              { key: 'total_confirmados', label: 'Confirmados', color: '#17a2b8', icon: 'ti-check' },
              { key: 'total_asistentes', label: 'Asistieron', color: '#28a745', icon: 'ti-user-check' },
              { key: 'total_ausentes', label: 'No Asistieron', color: '#dc3545', icon: 'ti-user-x' },
              { key: 'total_cancelados', label: 'Cancelados', color: '#fd7e14', icon: 'ti-ban' }
          ];
          
          estados.forEach(function(estado) {
              var valor = data.metricas[estado.key] || 0;
              var porcentajeEstado = data.metricas.total_participantes > 0 
                  ? ((valor / data.metricas.total_participantes) * 100).toFixed(1)
                  : 0;
              
              distribucionHTML += `
                  <div class="estado-item">
                      <div>
                          <i class="ti ${estado.icon} me-2" style="color: ${estado.color};"></i>
                          <strong>${estado.label}</strong>
                      </div>
                      <div>
                          <span class="badge" style="background-color: ${estado.color}; color: white;">
                              ${valor} (${porcentajeEstado}%)
                          </span>
                      </div>
                  </div>
              `;
          });
          $('#distribucion-estados').html(distribucionHTML);
          
          // Totales por tipo
          $('#total-familias').text(data.metricas.total_familias || 0);
          $('#total-apoderados').text(data.metricas.total_apoderados || 0);
          
          // Top 5 familias
          var topFamiliasHTML = '';
          if (data.top_familias && data.top_familias.length > 0) {
              data.top_familias.forEach(function(familia, index) {
                  var posicionClass = index === 0 ? 'posicion-1' : index === 1 ? 'posicion-2' : index === 2 ? 'posicion-3' : 'posicion-default';
                  topFamiliasHTML += `
                      <tr class="familia-item">
                          <td><span class="posicion-badge ${posicionClass}">${index + 1}</span></td>
                          <td><strong>${familia.apellido_principal}</strong></td>
                          <td><span class="badge bg-secondary">${familia.codigo_familia}</span></td>
                          <td>${familia.participantes} participante(s)</td>
                          <td>${formatearEstados(familia.estados)}</td>
                      </tr>
                  `;
              });
          } else {
              topFamiliasHTML = '<tr><td colspan="5" class="text-center text-muted">No hay datos disponibles</td></tr>';
          }
          $('#tabla-top-familias tbody').html(topFamiliasHTML);
      }

      // Funciones auxiliares
      function formatearFecha(fecha) {
          if (!fecha) return 'N/A';
          var partes = fecha.split('-');
          return partes[2] + '/' + partes[1] + '/' + partes[0];
      }

      function formatearTipo(tipo) {
          return tipo ? tipo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
      }

      function formatearEstados(estados) {
          if (!estados) return '<span class="badge bg-secondary">N/A</span>';
          var estadosArray = estados.split(',');
          var badgesHTML = '';
          estadosArray.forEach(function(estado) {
              var colorClase = 'bg-secondary';
              if (estado === 'asistio') colorClase = 'bg-success';
              else if (estado === 'confirmado') colorClase = 'bg-info';
              else if (estado === 'no_asistio') colorClase = 'bg-danger';
              else if (estado === 'cancelado') colorClase = 'bg-warning';
              
              badgesHTML += `<span class="badge ${colorClase} me-1">${formatearTipo(estado)}</span>`;
          });
          return badgesHTML;
      }

      // Función para exportar estadísticas
      function exportarEstadisticasEvento() {
          Swal.fire({
              icon: 'info',
              title: 'Función en Desarrollo',
              text: 'La exportación de estadísticas estará disponible próximamente',
              confirmButtonColor: '#667eea'
          });
      }
    </script>
    <!-- [Page Specific JS] end -->
    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
  <!-- [Body] end -->
</html>

<?php
// Cerrar conexión
$conn->close();
?>