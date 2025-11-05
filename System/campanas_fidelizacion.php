<?php

session_start();
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'disenar_campana':
            $mensaje_sistema = procesarDisenarCampana($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'planificar_actividad':
            $mensaje_sistema = procesarPlanificarActividad($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'organizar_evento':
            $mensaje_sistema = procesarOrganizarEvento($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'medir_impacto':
            $mensaje_sistema = procesarMedirImpacto($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'actualizar_participacion':
            $mensaje_sistema = procesarActualizarParticipacion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
    }
}

// Función para procesar diseño de campaña de fidelización
function procesarDisenarCampana($conn, $data) {
    try {
        $titulo = $conn->real_escape_string($data['titulo']);
        $descripcion = $conn->real_escape_string($data['descripcion']);
        $tipo = $conn->real_escape_string($data['tipo']);
        $dirigido_a = $conn->real_escape_string($data['dirigido_a']);
        $fecha_inicio = $conn->real_escape_string($data['fecha_inicio']);
        $fecha_fin = !empty($data['fecha_fin']) ? "'" . $conn->real_escape_string($data['fecha_fin']) . "'" : 'NULL';
        $ubicacion = $conn->real_escape_string($data['ubicacion']);
        $capacidad_maxima = !empty($data['capacidad_maxima']) ? $conn->real_escape_string($data['capacidad_maxima']) : 'NULL';
        $requiere_confirmacion = isset($data['requiere_confirmacion']) ? 1 : 0;
        $costo = !empty($data['costo']) ? $conn->real_escape_string($data['costo']) : '0.00';
        $observaciones = $conn->real_escape_string($data['observaciones']);
        
        $sql = "INSERT INTO eventos (
                    titulo, descripcion, tipo, dirigido_a, fecha_inicio, fecha_fin,
                    ubicacion, capacidad_maxima, requiere_confirmacion, costo, 
                    estado, observaciones
                ) VALUES (
                    '$titulo', '$descripcion', '$tipo', '$dirigido_a', '$fecha_inicio', $fecha_fin,
                    '$ubicacion', $capacidad_maxima, $requiere_confirmacion, $costo,
                    'programado', '$observaciones'
                )";
        
        if ($conn->query($sql)) {
            $evento_id = $conn->insert_id;
            
            // Si es una campaña de fidelización, crear invitaciones automáticas
            if ($tipo == 'evento_social' || $dirigido_a == 'padres') {
                crearInvitacionesAutomaticas($conn, $evento_id, $dirigido_a);
            }
            
            return "Campaña de fidelización diseñada y programada correctamente.";
        } else {
            return "Error al diseñar la campaña: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar planificación de actividad familia-colegio
function procesarPlanificarActividad($conn, $data) {
    try {
        $titulo = $conn->real_escape_string($data['titulo']);
        $descripcion = $conn->real_escape_string($data['descripcion']);
        $fecha_inicio = $conn->real_escape_string($data['fecha_inicio']);
        $ubicacion = $conn->real_escape_string($data['ubicacion']);
        $observaciones = $conn->real_escape_string($data['observaciones']);
        
        $sql = "INSERT INTO eventos (
                    titulo, descripcion, tipo, dirigido_a, fecha_inicio,
                    ubicacion, requiere_confirmacion, costo, estado, observaciones
                ) VALUES (
                    '$titulo', '$descripcion', 'reunion_padres', 'padres', '$fecha_inicio',
                    '$ubicacion', 1, 0.00, 'programado', '$observaciones'
                )";
        
        if ($conn->query($sql)) {
            return "Actividad familia-colegio planificada correctamente.";
        } else {
            return "Error al planificar la actividad: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar organización de evento especial
function procesarOrganizarEvento($conn, $data) {
    try {
        $evento_id = $conn->real_escape_string($data['evento_id']);
        $nuevos_participantes = json_decode($data['participantes'], true);
        
        // Actualizar estado del evento
        $sql_evento = "UPDATE eventos SET estado = 'en_curso' WHERE id = $evento_id";
        $conn->query($sql_evento);
        
        // Insertar participantes si se proporcionaron
        if (!empty($nuevos_participantes)) {
            foreach ($nuevos_participantes as $participante) {
                $apoderado_id = isset($participante['apoderado_id']) ? $participante['apoderado_id'] : 'NULL';
                $familia_id = isset($participante['familia_id']) ? $participante['familia_id'] : 'NULL';
                
                $sql_participante = "INSERT INTO participantes_evento 
                                   (evento_id, apoderado_id, familia_id, estado_participacion) 
                                   VALUES ($evento_id, $apoderado_id, $familia_id, 'invitado')";
                $conn->query($sql_participante);
            }
        }
        
        return "Evento especial organizado y participantes invitados correctamente.";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar medición de impacto
function procesarMedirImpacto($conn, $data) {
    try {
        $evento_id = $conn->real_escape_string($data['evento_id']);
        $calificacion = $conn->real_escape_string($data['calificacion']);
        $observaciones_impacto = $conn->real_escape_string($data['observaciones_impacto']);
        
        // Actualizar evento con medición de impacto
        $sql = "UPDATE eventos SET 
                estado = 'finalizado',
                observaciones = CONCAT(IFNULL(observaciones, ''), '\n[IMPACTO - " . date('Y-m-d H:i:s') . "] Calificación: $calificacion/5. $observaciones_impacto')
                WHERE id = $evento_id";
        
        if ($conn->query($sql)) {
            // Marcar participantes como asistidos si corresponde
            $sql_participantes = "UPDATE participantes_evento SET 
                                fecha_asistencia = CURRENT_TIMESTAMP,
                                estado_participacion = 'asistio'
                                WHERE evento_id = $evento_id AND estado_participacion = 'confirmado'";
            $conn->query($sql_participantes);
            
            return "Impacto de campaña medido y registrado correctamente.";
        } else {
            return "Error al medir el impacto: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar actualización de participación
function procesarActualizarParticipacion($conn, $data) {
    try {
        $participante_id = $conn->real_escape_string($data['participante_id']);
        $nuevo_estado = $conn->real_escape_string($data['nuevo_estado']);
        $observaciones = $conn->real_escape_string($data['observaciones']);
        
        // Si el estado es "mantener", solo actualizar observaciones
        if ($nuevo_estado === 'mantener') {
            $sql = "UPDATE participantes_evento SET 
                    observaciones = CONCAT(IFNULL(observaciones, ''), '\n[" . date('Y-m-d H:i:s') . "] ', '$observaciones')
                    WHERE id = $participante_id";
        } else {
            // Actualizar estado y agregar timestamp correspondiente
            $fecha_campo = '';
            switch ($nuevo_estado) {
                case 'confirmado':
                    $fecha_campo = 'fecha_confirmacion = CURRENT_TIMESTAMP,';
                    break;
                case 'asistio':
                    $fecha_campo = 'fecha_asistencia = CURRENT_TIMESTAMP,';
                    break;
            }
            
            $sql = "UPDATE participantes_evento SET 
                    estado_participacion = '$nuevo_estado',
                    $fecha_campo
                    observaciones = CONCAT(IFNULL(observaciones, ''), '\n[" . date('Y-m-d H:i:s') . "] Estado: $nuevo_estado. ', '$observaciones')
                    WHERE id = $participante_id";
        }
        
        if ($conn->query($sql)) {
            return "Participación actualizada correctamente.";
        } else {
            return "Error al actualizar participación: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función auxiliar para crear invitaciones automáticas
function crearInvitacionesAutomaticas($conn, $evento_id, $dirigido_a) {
    if ($dirigido_a == 'padres') {
        $sql = "INSERT INTO participantes_evento (evento_id, apoderado_id, estado_participacion)
                SELECT $evento_id, a.id, 'invitado'
                FROM apoderados a 
                WHERE a.activo = 1 AND a.tipo_apoderado = 'titular'";
    } else {
        $sql = "INSERT INTO participantes_evento (evento_id, familia_id, estado_participacion)
                SELECT $evento_id, f.id, 'invitado'
                FROM familias f 
                WHERE f.activo = 1";
    }
    $conn->query($sql);
}

// Consulta para obtener las campañas de fidelización con información de tablas relacionadas
$sql = "SELECT 
    e.id,
    e.titulo,
    e.descripcion,
    e.tipo,
    e.dirigido_a,
    e.fecha_inicio,
    e.fecha_fin,
    e.ubicacion,
    e.capacidad_maxima,
    e.requiere_confirmacion,
    e.costo,
    e.estado,
    e.observaciones,
    e.created_at,
    e.updated_at,
    -- Estadísticas de participación
    COUNT(pe.id) as total_invitados,
    COUNT(CASE WHEN pe.estado_participacion = 'confirmado' THEN 1 END) as total_confirmados,
    COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) as total_asistieron,
    COUNT(CASE WHEN pe.estado_participacion = 'no_asistio' THEN 1 END) as total_no_asistieron,
    COUNT(CASE WHEN pe.estado_participacion = 'cancelado' THEN 1 END) as total_cancelados,
    -- Calcular tasa de participación
    CASE 
        WHEN COUNT(pe.id) > 0 THEN ROUND((COUNT(CASE WHEN pe.estado_participacion = 'asistio' THEN 1 END) / COUNT(pe.id)) * 100, 2)
        ELSE 0
    END as tasa_participacion,
    -- Calcular días restantes o días transcurridos
    CASE 
        WHEN e.fecha_inicio > CURDATE() THEN DATEDIFF(e.fecha_inicio, CURDATE())
        WHEN e.fecha_inicio = CURDATE() THEN 0
        ELSE -DATEDIFF(CURDATE(), e.fecha_inicio)
    END as dias_diferencia,
    -- Determinar prioridad del evento
    CASE 
        WHEN e.estado = 'programado' AND e.fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'urgente'
        WHEN e.estado = 'programado' AND e.fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'alta'
        WHEN e.estado = 'en_curso' THEN 'activo'
        ELSE 'normal'
    END as prioridad_evento
FROM eventos e
LEFT JOIN participantes_evento pe ON e.id = pe.evento_id
WHERE e.tipo IN ('evento_social', 'reunion_padres', 'charla_informativa', 'academico', 'deportivo', 'otro')
GROUP BY e.id, e.titulo, e.descripcion, e.tipo, e.dirigido_a, e.fecha_inicio, e.fecha_fin, 
         e.ubicacion, e.capacidad_maxima, e.requiere_confirmacion, e.costo, e.estado, 
         e.observaciones, e.created_at, e.updated_at
ORDER BY 
    CASE e.estado 
        WHEN 'programado' THEN 1
        WHEN 'en_curso' THEN 2
        WHEN 'finalizado' THEN 3
        WHEN 'cancelado' THEN 4
        ELSE 5
    END,
    e.fecha_inicio ASC";

$result = $conn->query($sql);

// Obtener estadísticas generales de campañas
$stats_sql = "SELECT 
    COUNT(*) as total_campanas,
    COUNT(CASE WHEN estado = 'programado' THEN 1 END) as campanas_programadas,
    COUNT(CASE WHEN estado = 'en_curso' THEN 1 END) as campanas_activas,
    COUNT(CASE WHEN estado = 'finalizado' THEN 1 END) as campanas_finalizadas,
    COUNT(CASE WHEN fecha_inicio = CURDATE() THEN 1 END) as campanas_hoy,
    COUNT(CASE WHEN fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as campanas_semana,
    AVG(costo) as costo_promedio,
    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as campanas_mes
FROM eventos 
WHERE tipo IN ('evento_social', 'reunion_padres', 'charla_informativa', 'academico', 'deportivo', 'otro')";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas de participación global
$participacion_sql = "SELECT 
    COUNT(*) as total_invitaciones,
    COUNT(CASE WHEN estado_participacion = 'confirmado' THEN 1 END) as total_confirmaciones,
    COUNT(CASE WHEN estado_participacion = 'asistio' THEN 1 END) as total_asistencias,
    ROUND(AVG(CASE WHEN estado_participacion = 'asistio' THEN 1 ELSE 0 END) * 100, 2) as tasa_asistencia_global
FROM participantes_evento pe
INNER JOIN eventos e ON pe.evento_id = e.id
WHERE e.tipo IN ('evento_social', 'reunion_padres', 'charla_informativa', 'academico', 'deportivo', 'otro')";

$participacion_result = $conn->query($participacion_sql);
$participacion_stats = $participacion_result->fetch_assoc();

// Obtener estadísticas por tipo de campaña
$tipos_sql = "SELECT 
    tipo,
    COUNT(*) as cantidad,
    AVG(CASE 
        WHEN (SELECT COUNT(*) FROM participantes_evento pe WHERE pe.evento_id = e.id) > 0 
        THEN (SELECT COUNT(*) FROM participantes_evento pe WHERE pe.evento_id = e.id AND pe.estado_participacion = 'asistio') / 
             (SELECT COUNT(*) FROM participantes_evento pe WHERE pe.evento_id = e.id) * 100
        ELSE 0
    END) as tasa_exito
FROM eventos e
WHERE tipo IN ('evento_social', 'reunion_padres', 'charla_informativa', 'academico', 'deportivo', 'otro')
GROUP BY tipo
ORDER BY cantidad DESC";

$tipos_result = $conn->query($tipos_sql);
$tipos_stats = [];
while($tipo = $tipos_result->fetch_assoc()) {
    $tipos_stats[] = $tipo;
}

// Obtener nombre del sistema para el título
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
  $nombre_sistema = "CRM Escolar";
}
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title>Campañas de Fidelización - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Campañas de Fidelización"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Fidelización, Eventos, Familias, Participación"
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
    
    <!-- Custom styles for campañas fidelización -->
    <style>
      .badge-tipo-campana {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-evento_social { background-color: #e83e8c; }
      .tipo-reunion_padres { background-color: #6f42c1; }
      .tipo-charla_informativa { background-color: #17a2b8; }
      .tipo-academico { background-color: #28a745; }
      .tipo-deportivo { background-color: #fd7e14; }
      .tipo-otro { background-color: #6c757d; }
      
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .estado-programado { background-color: #ffc107; color: #856404; }
      .estado-en_curso { 
        background-color: #28a745; 
        animation: pulse-active 2s infinite;
      }
      .estado-finalizado { background-color: #17a2b8; }
      .estado-cancelado { background-color: #dc3545; }
      
      .badge-prioridad {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .prioridad-urgente { 
        background-color: #dc3545; 
        color: white;
        animation: pulse-urgent 2s infinite;
      }
      .prioridad-alta { background-color: #fd7e14; color: white; }
      .prioridad-activo { 
        background-color: #28a745; 
        color: white;
        animation: pulse-active 2s infinite;
      }
      .prioridad-normal { background-color: #6c757d; color: white; }
      
      .badge-dirigido {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .dirigido-padres { background-color: #d4edda; color: #155724; }
      .dirigido-estudiantes { background-color: #d1ecf1; color: #0c5460; }
      .dirigido-exalumnos { background-color: #fff3cd; color: #856404; }
      .dirigido-general { background-color: #f8d7da; color: #721c24; }
      
      @keyframes pulse-urgent {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
      }
      
      @keyframes pulse-active {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
      }
      
      .campana-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .campana-titulo {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .campana-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .campana-ubicacion {
        font-size: 0.7rem;
        color: #495057;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
      }
      
      .fecha-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .fecha-inicio {
        color: #495057;
        font-weight: 500;
      }
      
      .fecha-fin {
        color: #6c757d;
      }
      
      .dias-info {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      
      .dias-hoy { background-color: #dc3545; color: white; }
      .dias-proximo { background-color: #fd7e14; color: white; }
      .dias-futuro { background-color: #28a745; color: white; }
      .dias-pasado { background-color: #6c757d; color: white; }
      
      .participacion-stats {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .stat-principal {
        font-weight: bold;
        color: #495057;
      }
      
      .stat-secundario {
        color: #6c757d;
      }
      
      .tasa-participacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: bold;
      }
      .tasa-excelente { background-color: #d4edda; color: #155724; }
      .tasa-buena { background-color: #d1ecf1; color: #0c5460; }
      .tasa-regular { background-color: #fff3cd; color: #856404; }
      .tasa-baja { background-color: #f8d7da; color: #721c24; }
      
      .costo-info {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: bold;
        color: #28a745;
      }
      
      .capacidad-info {
        font-size: 0.7rem;
        color: #6c757d;
        padding: 0.2rem 0.4rem;
        background-color: #e8f4fd;
        border-radius: 4px;
      }
      
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
      
      .tipos-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .tipo-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 500;
        background-color: #6c757d;
        color: white;
      }
      
      .btn-grupo-campana {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-campana .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
      }
      
      .impacto-panel {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .impacto-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f1f1f1;
      }
      
      .impacto-item:last-child {
        border-bottom: none;
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
                    Campañas de Fidelización
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
                    Campañas de Fidelización
                  </h3>
                  <small class="text-muted">
                    Diseña, planifica y gestiona campañas para fortalecer vínculos con familias. 
                    Organiza eventos, actividades y mide el impacto en la satisfacción familiar.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" onclick="medirImpactoGlobal()">
                    <i class="ti ti-chart-bar me-1"></i>
                    Medir Impacto
                  </button> -->
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalOrganizarEvento">
                    <i class="ti ti-calendar-event me-1"></i>
                    Organizar Evento
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlanificarActividad">
                    <i class="ti ti-users me-1"></i>
                    Planificar Actividad
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisenarCampana">
                    <i class="ti ti-heart me-1"></i>
                    Diseñar Campaña
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de campañas -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="campanas-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="16%">Campaña</th>
                        <th width="8%">Dirigido A</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Fechas</th>
                        <th width="10%">Participación</th>
                        <th width="8%">Tasa Éxito</th>
                        <th width="8%">Costo</th>
                        <th width="8%">Capacidad</th>
                        <th width="12%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_inicio = date('d/m/Y H:i', strtotime($row['fecha_inicio']));
                              $fecha_fin = $row['fecha_fin'] ? date('d/m/Y H:i', strtotime($row['fecha_fin'])) : '';
                              
                              // Determinar clase CSS para el tipo
                              $tipo_class = 'tipo-' . $row['tipo'];
                              
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado'];
                              
                              // Determinar clase de dirigido a
                              $dirigido_class = 'dirigido-' . $row['dirigido_a'];
                              
                              // Determinar clase de días
                              $dias_diferencia = (int)$row['dias_diferencia'];
                              $dias_text = '';
                              $dias_class = '';
                              
                              if ($dias_diferencia == 0) {
                                  $dias_text = 'Hoy';
                                  $dias_class = 'dias-hoy';
                              } elseif ($dias_diferencia > 0) {
                                  $dias_text = 'En ' . $dias_diferencia . 'd';
                                  $dias_class = $dias_diferencia <= 7 ? 'dias-proximo' : 'dias-futuro';
                              } else {
                                  $dias_text = 'Hace ' . abs($dias_diferencia) . 'd';
                                  $dias_class = 'dias-pasado';
                              }
                              
                              // Determinar prioridad
                              $prioridad = $row['prioridad_evento'] ?? 'normal';
                              $prioridad_class = 'prioridad-' . $prioridad;
                              
                              // Calcular tasa de participación
                              $tasa = (float)$row['tasa_participacion'];
                              $tasa_class = '';
                              if ($tasa >= 80) $tasa_class = 'tasa-excelente';
                              elseif ($tasa >= 60) $tasa_class = 'tasa-buena';
                              elseif ($tasa >= 40) $tasa_class = 'tasa-regular';
                              else $tasa_class = 'tasa-baja';
                              
                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='badge badge-prioridad $prioridad_class'>" . strtoupper($prioridad) . "</span>
                                    </td>";
                              echo "<td><span class='badge badge-tipo-campana $tipo_class'>" . 
                                   ucfirst(str_replace('_', ' ', $row['tipo'])) . "</span></td>";
                              echo "<td>
                                      <div class='campana-info'>
                                        <span class='campana-titulo'>" . htmlspecialchars($row['titulo']) . "</span>
                                        <span class='campana-descripcion' title='" . htmlspecialchars($row['descripcion']) . "'>" . 
                                        htmlspecialchars($row['descripcion']) . "</span>
                                        <span class='campana-ubicacion'>" . htmlspecialchars($row['ubicacion'] ?? 'Sin ubicación') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-dirigido $dirigido_class'>" . 
                                   ucfirst(str_replace('_', ' ', $row['dirigido_a'])) . "</span></td>";
                              echo "<td><span class='badge badge-estado $estado_class'>" . 
                                   ucfirst(str_replace('_', ' ', $row['estado'])) . "</span></td>";
                              echo "<td>
                                      <div class='fecha-info'>
                                        <span class='fecha-inicio'>" . $fecha_inicio . "</span>
                                        " . ($fecha_fin ? "<span class='fecha-fin'>Hasta: " . $fecha_fin . "</span>" : "") . "
                                        <span class='dias-info $dias_class'>" . $dias_text . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='participacion-stats'>
                                        <span class='stat-principal'>" . number_format($row['total_invitados']) . " invitados</span>
                                        <span class='stat-secundario'>" . number_format($row['total_confirmados']) . " confirmados</span>
                                        <span class='stat-secundario'>" . number_format($row['total_asistieron']) . " asistieron</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='tasa-participacion $tasa_class'>" . $tasa . "%</span></td>";
                              echo "<td>" . 
                                   ($row['costo'] > 0 ? "<span class='costo-info'>S/ " . number_format($row['costo'], 2) . "</span>" : 
                                   "<span class='text-success'>Gratuito</span>") . "</td>";
                              echo "<td>" . 
                                   ($row['capacidad_maxima'] ? "<span class='capacidad-info'>Máx: " . number_format($row['capacidad_maxima']) . "</span>" : 
                                   "<span class='text-muted'>Ilimitado</span>") . "</td>";
                              echo "<td>
                                      <div class='btn-grupo-campana'>
                                        <button type='button' class='btn btn-outline-info btn-ver-participantes' 
                                                data-id='" . $row['id'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                title='Ver Participantes'>
                                          <i class='ti ti-users'></i>
                                        </button>
                                        " . ($row['estado'] == 'programado' ? 
                                        "<button type='button' class='btn btn-outline-success btn-iniciar-evento' 
                                                data-id='" . $row['id'] . "'
                                                title='Iniciar Evento'>
                                            <i class='ti ti-player-play'></i>

                                        </button>" : "") . "
                                        " . ($row['estado'] == 'en_curso' || $row['estado'] == 'finalizado' ? 
                                        "<button type='button' class='btn btn-outline-warning btn-medir-impacto' 
                                                data-id='" . $row['id'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                title='Medir Impacto'>
                                          <i class='ti ti-chart-bar'></i>
                                        </button>" : "") . "
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay campañas registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Campaña</th>
                        <th>Dirigido A</th>
                        <th>Estado</th>
                        <th>Fechas</th>
                        <th>Participación</th>
                        <th>Tasa Éxito</th>
                        <th>Costo</th>
                        <th>Capacidad</th>
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
    <?php include 'modals/campanas_fidelizacion/modal_disenar_campana.php'; ?>
    <?php include 'modals/campanas_fidelizacion/modal_planificar_actividad.php'; ?>
    <?php include 'modals/campanas_fidelizacion/modal_organizar_evento.php'; ?>
    <?php include 'modals/campanas_fidelizacion/modal_medir_impacto.php'; ?>
    <?php include 'modals/campanas_fidelizacion/modal_ver_participantes.php'; ?>



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
            var table = $("#campanas-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay campañas disponibles en la tabla",
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

            // Función para medir impacto global
            window.medirImpactoGlobal = function() {
              $('#modalMedirImpacto').modal('show');
            };

            // Manejar click en botón ver participantes
            $(document).on('click', '.btn-ver-participantes', function() {
                var id = $(this).data('id');
                var titulo = $(this).data('titulo');
                cargarParticipantes(id, titulo);
            });

            // Manejar click en botón iniciar evento
            $(document).on('click', '.btn-iniciar-evento', function() {
              var id = $(this).data('id');
              Swal.fire({
                title: '¿Iniciar evento?',
                text: "¿Está seguro de que desea iniciar este evento?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6', 
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, iniciar',
                cancelButtonText: 'Cancelar'
              }).then((result) => {
                if (result.isConfirmed) {
                  iniciarEvento(id);
                }
              });
            });

            // Manejar click en botón medir impacto
            $(document).on('click', '.btn-medir-impacto', function() {
                var id = $(this).data('id');
                var titulo = $(this).data('titulo');
                medirImpactoEvento(id, titulo);
            });

            // Manejar click en botón gestionar participación
            $(document).on('click', '.btn-gestionar-participacion', function() {
                var id = $(this).data('id');
                var titulo = $(this).data('titulo');
                gestionarParticipacion(id, titulo);
            });

            // Función para iniciar evento
            function iniciarEvento(eventoId) {
              $.ajax({
              url: '<?php echo $_SERVER['PHP_SELF']; ?>',
              method: 'POST',
              data: { 
                accion: 'organizar_evento',
                evento_id: eventoId,
                participantes: '[]'
              },
              success: function(response) {
                Swal.fire({
                icon: 'success',
                title: '¡Éxito!', 
                text: 'Evento iniciado correctamente.',
                showConfirmButton: true,
                timer: 1500
                }).then(() => {
                location.reload();
                });
              },
              error: function() {
                Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al iniciar el evento.',
                showConfirmButton: true
                });
              }
              });
            }

            // Función para medir impacto de evento específico
            function medirImpactoEvento(eventoId, titulo) {
              $('#modalMedirImpactoEvento').remove();
              
              var modalHTML = `
                <div class="modal fade" id="modalMedirImpactoEvento" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Medir Impacto: ${titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="">
                        <div class="modal-body">
                          <input type="hidden" name="accion" value="medir_impacto">
                          <input type="hidden" name="evento_id" value="${eventoId}">
                          <div class="mb-3">
                            <label class="form-label">Calificación del evento (1-5)</label>
                            <select name="calificacion" class="form-control" required>
                              <option value="">Seleccionar calificación</option>
                              <option value="5">5 - Excelente</option>
                              <option value="4">4 - Muy bueno</option>
                              <option value="3">3 - Bueno</option>
                              <option value="2">2 - Regular</option>
                              <option value="1">1 - Deficiente</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Observaciones del impacto</label>
                            <textarea name="observaciones_impacto" class="form-control" rows="4" required placeholder="Describe el impacto observado, nivel de satisfacción, fortalecimiento de vínculos, etc."></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                          <button type="submit" class="btn btn-warning">Registrar Impacto</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              `;
              
              $('body').append(modalHTML);
              $('#modalMedirImpactoEvento').modal('show');
            }

            // Función para gestionar participación
            function gestionarParticipacion(eventoId, titulo) {
              // Esta función ahora usa el modal completo de ver participantes
              cargarParticipantes(eventoId, titulo);
            }

            // Función para exportar campañas de fidelización a PDF
            window.exportarInteraccionesPDF = function() {
                var tabla = $('#campanas-table').DataTable();
                var datosVisibles = [];
                
                // Obtener solo las filas visibles/filtradas
                tabla.rows({ filter: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                    var data = this.data();
                    var row = [];
                    
                    // Extraer texto limpio de cada celda (sin HTML, excluyendo la última columna de acciones)
                    for (var i = 0; i < data.length - 1; i++) { // -1 para excluir columna de acciones
                        var cellContent = $(data[i]).text().trim() || data[i];
                        row.push(cellContent);
                    }
                    datosVisibles.push(row);
                });
                
                if (datosVisibles.length === 0) {
                    alert('No hay registros visibles para generar el reporte PDF.');
                    return;
                }
                
                // Crear formulario para enviar datos por POST
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'reports/generar_pdf_campanas_fidelizacion.php';
                form.target = '_blank';
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'datosCampanas';
                input.value = JSON.stringify(datosVisibles);
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            };

            // Auto-refresh cada 3 minutos para eventos activos
            setInterval(function() {
              var eventosActivos = $('.estado-en_curso').length;
              if (eventosActivos > 0) {
                location.reload();
              }
            }, 180000); // 3 minutos

            // Tooltip para elementos
            $('[title]').tooltip();
      });
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