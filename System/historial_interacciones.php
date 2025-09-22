<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'registrar_interaccion':
            $mensaje_sistema = procesarRegistroInteraccion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'programar_interaccion':
            $mensaje_sistema = procesarProgramarInteraccion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'actualizar_seguimiento':
            $mensaje_sistema = procesarActualizarSeguimiento($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'completar_interaccion':
            $mensaje_sistema = procesarCompletarInteraccion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
    }
}

// Función para procesar registro de interacción
function procesarRegistroInteraccion($conn, $data) {
    try {
        $tipo_interaccion_id = $conn->real_escape_string($data['tipo_interaccion_id']);
        $usuario_id = 1; // Obtener del session
        $apoderado_id = !empty($data['apoderado_id']) ? $conn->real_escape_string($data['apoderado_id']) : 'NULL';
        $familia_id = !empty($data['familia_id']) ? $conn->real_escape_string($data['familia_id']) : 'NULL';
        $asunto = $conn->real_escape_string($data['asunto']);
        $descripcion = $conn->real_escape_string($data['descripcion']);
        $fecha_realizada = $conn->real_escape_string($data['fecha_realizada']);
        $duracion_minutos = !empty($data['duracion_minutos']) ? $conn->real_escape_string($data['duracion_minutos']) : 'NULL';
        $resultado = $conn->real_escape_string($data['resultado']);
        $observaciones = $conn->real_escape_string($data['observaciones']);
        $requiere_seguimiento = isset($data['requiere_seguimiento']) ? 1 : 0;
        $fecha_proximo_seguimiento = !empty($data['fecha_proximo_seguimiento']) ? "'" . $conn->real_escape_string($data['fecha_proximo_seguimiento']) . "'" : 'NULL';
        
        $sql = "INSERT INTO interacciones (
                    tipo_interaccion_id, usuario_id, apoderado_id, familia_id, asunto, 
                    descripcion, fecha_realizada, duracion_minutos, resultado, 
                    observaciones, requiere_seguimiento, fecha_proximo_seguimiento, estado
                ) VALUES (
                    $tipo_interaccion_id, $usuario_id, $apoderado_id, $familia_id, '$asunto', 
                    '$descripcion', '$fecha_realizada', $duracion_minutos, '$resultado', 
                    '$observaciones', $requiere_seguimiento, $fecha_proximo_seguimiento, 'realizado'
                )";
        
        if ($conn->query($sql)) {
            return "Interacción registrada correctamente.";
        } else {
            return "Error al registrar la interacción: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar programación de interacción
function procesarProgramarInteraccion($conn, $data) {
    try {
        $tipo_interaccion_id = $conn->real_escape_string($data['tipo_interaccion_id']);
        $usuario_id = 1; // Obtener del session
        $apoderado_id = !empty($data['apoderado_id']) ? $conn->real_escape_string($data['apoderado_id']) : 'NULL';
        $familia_id = !empty($data['familia_id']) ? $conn->real_escape_string($data['familia_id']) : 'NULL';
        $asunto = $conn->real_escape_string($data['asunto']);
        $descripcion = $conn->real_escape_string($data['descripcion']);
        $fecha_programada = $conn->real_escape_string($data['fecha_programada']);
        
        $sql = "INSERT INTO interacciones (
                    tipo_interaccion_id, usuario_id, apoderado_id, familia_id, asunto, 
                    descripcion, fecha_programada, estado
                ) VALUES (
                    $tipo_interaccion_id, $usuario_id, $apoderado_id, $familia_id, '$asunto', 
                    '$descripcion', '$fecha_programada', 'programado'
                )";
        
        if ($conn->query($sql)) {
            return "Interacción programada correctamente.";
        } else {
            return "Error al programar la interacción: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar actualización de seguimiento
function procesarActualizarSeguimiento($conn, $data) {
    try {
        $id = $conn->real_escape_string($data['interaccion_id']);
        $observaciones = $conn->real_escape_string($data['observaciones']);
        $fecha_proximo_seguimiento = !empty($data['fecha_proximo_seguimiento']) ? "'" . $conn->real_escape_string($data['fecha_proximo_seguimiento']) . "'" : 'NULL';
        $requiere_seguimiento = isset($data['requiere_seguimiento']) ? 1 : 0;
        
        $sql = "UPDATE interacciones SET 
                observaciones = CONCAT(IFNULL(observaciones, ''), '\n[" . date('Y-m-d H:i:s') . "] $observaciones'),
                fecha_proximo_seguimiento = $fecha_proximo_seguimiento,
                requiere_seguimiento = $requiere_seguimiento,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            return "Seguimiento actualizado correctamente.";
        } else {
            return "Error al actualizar el seguimiento: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar completar interacción
function procesarCompletarInteraccion($conn, $data) {
    try {
        $id = $conn->real_escape_string($data['interaccion_id']);
        $resultado = $conn->real_escape_string($data['resultado']);
        $duracion_minutos = !empty($data['duracion_minutos']) ? $conn->real_escape_string($data['duracion_minutos']) : 'NULL';
        $observaciones_finales = $conn->real_escape_string($data['observaciones_finales']);
        
        $sql = "UPDATE interacciones SET 
                fecha_realizada = CURRENT_TIMESTAMP,
                duracion_minutos = $duracion_minutos,
                resultado = '$resultado',
                observaciones = CONCAT(IFNULL(observaciones, ''), '\n[FINALIZADA - " . date('Y-m-d H:i:s') . "] $observaciones_finales'),
                estado = 'realizado',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            return "Interacción completada correctamente.";
        } else {
            return "Error al completar la interacción: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Consulta para obtener el historial de interacciones con información de tablas relacionadas
$sql = "SELECT 
    i.id,
    i.tipo_interaccion_id,
    ti.nombre as tipo_interaccion,
    ti.icono as tipo_icono,
    ti.color as tipo_color,
    i.usuario_id,
    CONCAT(u.nombre, ' ', u.apellidos) as usuario_nombre,
    i.apoderado_id,
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
    i.familia_id,
    f.apellido_principal as familia_apellido,
    f.codigo_familia,
    i.asunto,
    i.descripcion,
    i.fecha_programada,
    i.fecha_realizada,
    i.duracion_minutos,
    i.resultado,
    i.observaciones,
    i.requiere_seguimiento,
    i.fecha_proximo_seguimiento,
    i.estado,
    i.created_at,
    i.updated_at,
    -- Determinar prioridad basada en fecha próxima
    CASE 
        WHEN i.fecha_proximo_seguimiento IS NOT NULL AND i.fecha_proximo_seguimiento <= CURDATE() THEN 'urgente'
        WHEN i.fecha_proximo_seguimiento IS NOT NULL AND i.fecha_proximo_seguimiento <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'alta'
        WHEN i.requiere_seguimiento = 1 THEN 'media'
        ELSE 'baja'
    END as prioridad_seguimiento,
    -- Calcular días para seguimiento
    CASE 
        WHEN i.fecha_proximo_seguimiento IS NOT NULL THEN DATEDIFF(i.fecha_proximo_seguimiento, CURDATE())
        ELSE NULL
    END as dias_para_seguimiento,
    -- Información del contacto principal
    CASE 
        WHEN i.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos, ' (Apoderado)')
        WHEN i.familia_id IS NOT NULL THEN CONCAT('Familia ', f.apellido_principal)
        ELSE 'Sin contacto específico'
    END as contacto_principal
FROM interacciones i
LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
LEFT JOIN apoderados a ON i.apoderado_id = a.id
LEFT JOIN familias f ON i.familia_id = f.id
WHERE i.activo = 1
ORDER BY 
    CASE i.estado 
        WHEN 'programado' THEN 1
        WHEN 'realizado' THEN 2
        WHEN 'cancelado' THEN 3
        ELSE 4
    END,
    i.fecha_proximo_seguimiento ASC,
    i.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de interacciones para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_interacciones,
    COUNT(CASE WHEN estado = 'programado' THEN 1 END) as programadas,
    COUNT(CASE WHEN estado = 'realizado' THEN 1 END) as realizadas,
    COUNT(CASE WHEN estado = 'cancelado' THEN 1 END) as canceladas,
    COUNT(CASE WHEN requiere_seguimiento = 1 THEN 1 END) as requieren_seguimiento,
    COUNT(CASE WHEN fecha_proximo_seguimiento <= CURDATE() AND requiere_seguimiento = 1 THEN 1 END) as seguimientos_vencidos,
    COUNT(CASE WHEN fecha_programada = CURDATE() THEN 1 END) as programadas_hoy,
    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as interacciones_semana,
    ROUND(AVG(duracion_minutos), 0) as duracion_promedio
FROM interacciones 
WHERE activo = 1";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas por tipo de interacción
$tipos_sql = "SELECT 
    ti.nombre,
    ti.color,
    COUNT(i.id) as cantidad
FROM tipos_interaccion ti
LEFT JOIN interacciones i ON ti.id = i.tipo_interaccion_id AND i.activo = 1
WHERE ti.activo = 1
GROUP BY ti.id, ti.nombre, ti.color
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
    <title>Historial de Interacciones - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Historial de Interacciones"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Interacciones, Historial, Seguimiento, Apoderados"
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
    
    <!-- Custom styles for interacciones -->
    <style>
      .badge-tipo-interaccion {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .estado-programado { background-color: #ffc107; color: #856404; }
      .estado-realizado { background-color: #28a745; }
      .estado-cancelado { background-color: #dc3545; }
      .estado-reagendado { background-color: #fd7e14; }
      
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
      .prioridad-media { background-color: #ffc107; color: #856404; }
      .prioridad-baja { background-color: #6c757d; color: white; }
      
      @keyframes pulse-urgent {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
      }
      
      .interaccion-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .interaccion-asunto {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .interaccion-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .contacto-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .contacto-nombre {
        font-weight: 500;
        color: #495057;
        font-size: 0.8rem;
      }
      
      .contacto-tipo {
        font-size: 0.7rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .usuario-info {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 500;
      }
      
      .fecha-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .fecha-programada {
        color: #495057;
        font-weight: 500;
      }
      
      .fecha-realizada {
        color: #28a745;
        font-weight: 500;
      }
      
      .fecha-vencida {
        color: #dc3545;
        font-weight: bold;
      }
      
      .duracion-info {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
      }
      
      .resultado-info {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-weight: 500;
      }
      .resultado-exitoso { background-color: #d4edda; color: #155724; }
      .resultado-sin_respuesta { background-color: #fff3cd; color: #856404; }
      .resultado-reagendar { background-color: #f8d7da; color: #721c24; }
      .resultado-no_interesado { background-color: #d1ecf1; color: #0c5460; }
      .resultado-convertido { background-color: #d4edda; color: #155724; }
      
      .seguimiento-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .requiere-seguimiento {
        color: #dc3545;
        font-weight: bold;
      }
      
      .no-requiere-seguimiento {
        color: #6c757d;
      }
      
      .dias-seguimiento {
        font-weight: bold;
      }
      
      .dias-vencido { color: #dc3545; }
      .dias-proximo { color: #fd7e14; }
      .dias-futuro { color: #28a745; }
      
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
        color: white;
      }
      
      .btn-grupo-interaccion {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-interaccion .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
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
                    Historial de Interacciones
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

        <!-- [ Tipos de Interacción Panel ] start -->
        <?php if(!empty($tipos_stats)): ?>
        <div class="row mb-3">
          <div class="col-sm-12">
            <div class="tipos-panel">
              <h6 class="mb-3"><i class="ti ti-category me-2"></i>Tipos de Interacciones</h6>
              <?php foreach($tipos_stats as $tipo): ?>
              <span class="tipo-item" style="background-color: <?php echo $tipo['color'] ?? '#6c757d'; ?>;">
                <?php echo htmlspecialchars($tipo['nombre']); ?>: <?php echo $tipo['cantidad']; ?>
              </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <!-- [ Tipos de Interacción Panel ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Historial de Interacciones
                  </h3>
                  <small class="text-muted">
                    Registra y gestiona todas las interacciones con apoderados y familias. 
                    Incluye reuniones, llamadas, reclamos, solicitudes y seguimientos programados.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-info btn-sm" onclick="consultarHistorial()">
                    <i class="ti ti-history me-1"></i>
                    Consultar Historial
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalGestionarSeguimientos">
                    <i class="ti ti-calendar-check me-1"></i>
                    Gestionar Seguimientos
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramarInteraccion">
                    <i class="ti ti-calendar-plus me-1"></i>
                    Programar Interacción
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarInteraccion">
                    <i class="ti ti-plus me-1"></i>
                    Registrar Interacción
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de interacciones -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="interacciones-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="15%">Asunto y Descripción</th>
                        <th width="12%">Contacto</th>
                        <th width="10%">Usuario</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Fechas</th>
                        <th width="8%">Duración</th>
                        <th width="8%">Resultado</th>
                        <th width="10%">Seguimiento</th>
                        <th width="7%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_creacion = date('d/m/Y', strtotime($row['created_at']));
                              $fecha_programada = $row['fecha_programada'] ? date('d/m/Y H:i', strtotime($row['fecha_programada'])) : '';
                              $fecha_realizada = $row['fecha_realizada'] ? date('d/m/Y H:i', strtotime($row['fecha_realizada'])) : '';
                              $fecha_seguimiento = $row['fecha_proximo_seguimiento'] ? date('d/m/Y', strtotime($row['fecha_proximo_seguimiento'])) : '';
                              
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado'];
                              
                              // Determinar clase de prioridad
                              $prioridad = $row['prioridad_seguimiento'] ?? 'baja';
                              $prioridad_class = 'prioridad-' . $prioridad;
                              
                              // Determinar clase de resultado
                              $resultado = $row['resultado'] ?? '';
                              $resultado_class = $resultado ? 'resultado-' . $resultado : '';
                              
                              // Días para seguimiento
                              $dias_seguimiento = $row['dias_para_seguimiento'];
                              $dias_class = '';
                              if ($dias_seguimiento !== null) {
                                  if ($dias_seguimiento < 0) $dias_class = 'dias-vencido';
                                  elseif ($dias_seguimiento <= 3) $dias_class = 'dias-proximo';
                                  else $dias_class = 'dias-futuro';
                              }
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td><span class='badge badge-tipo-interaccion' style='background-color: " . 
                                   ($row['tipo_color'] ?? '#6c757d') . ";'>" . 
                                   htmlspecialchars($row['tipo_interaccion'] ?? 'Sin tipo') . "</span></td>";
                              echo "<td>
                                      <div class='interaccion-info'>
                                        <span class='interaccion-asunto'>" . htmlspecialchars($row['asunto']) . "</span>
                                        <span class='interaccion-descripcion' title='" . htmlspecialchars($row['descripcion']) . "'>" . 
                                        htmlspecialchars($row['descripcion']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='contacto-nombre'>" . htmlspecialchars($row['contacto_principal']) . "</span>
                                        " . ($row['codigo_familia'] ? "<span class='contacto-tipo'>Fam: " . htmlspecialchars($row['codigo_familia']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='usuario-info'>" . htmlspecialchars($row['usuario_nombre'] ?? 'Sin asignar') . "</span></td>";
                              echo "<td><span class='badge badge-estado $estado_class'>" . 
                                   ucfirst($row['estado']) . "</span></td>";
                              echo "<td>
                                      <div class='fecha-info'>
                                        " . ($fecha_programada ? "<span class='fecha-programada'>Prog: " . $fecha_programada . "</span>" : "") . "
                                        " . ($fecha_realizada ? "<span class='fecha-realizada'>Real: " . $fecha_realizada . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>" . 
                                   ($row['duracion_minutos'] ? "<span class='duracion-info'>" . $row['duracion_minutos'] . " min</span>" : 
                                   "<span class='text-muted'>-</span>") . "</td>";
                              echo "<td>" . 
                                   ($resultado ? "<span class='resultado-info $resultado_class'>" . ucfirst(str_replace('_', ' ', $resultado)) . "</span>" : 
                                   "<span class='text-muted'>-</span>") . "</td>";
                              echo "<td>
                                      <div class='seguimiento-info'>
                                        <span class='" . ($row['requiere_seguimiento'] ? 'requiere-seguimiento' : 'no-requiere-seguimiento') . "'>
                                          " . ($row['requiere_seguimiento'] ? 'Sí requiere' : 'No requiere') . "
                                        </span>
                                        " . ($fecha_seguimiento ? 
                                        "<span class='dias-seguimiento $dias_class'>" . 
                                        ($dias_seguimiento < 0 ? "Vencido (" . abs($dias_seguimiento) . "d)" : 
                                         ($dias_seguimiento == 0 ? "Hoy" : "En " . $dias_seguimiento . "d")) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-interaccion'>
                                        <button type='button' class='btn btn-outline-info btn-ver-historial' 
                                                data-apoderado-id='" . ($row['apoderado_id'] ?? '') . "'
                                                data-familia-id='" . ($row['familia_id'] ?? '') . "'
                                                title='Ver Historial Completo'>
                                          <i class='ti ti-history'></i>
                                        </button>
                                        " . ($row['estado'] == 'programado' ? 
                                        "<button type='button' class='btn btn-outline-success btn-completar' 
                                                data-id='" . $row['id'] . "'
                                                title='Completar Interacción'>
                                          <i class='ti ti-check'></i>
                                        </button>" : "") . "
                                        " . ($row['requiere_seguimiento'] ? 
                                        "<button type='button' class='btn btn-outline-warning btn-actualizar-seguimiento' 
                                                data-id='" . $row['id'] . "'
                                                data-prioridad='" . $prioridad . "'
                                                title='Actualizar Seguimiento'>
                                          <i class='ti ti-calendar-event'></i>
                                        </button>" : "") . "
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay interacciones registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Asunto y Descripción</th>
                        <th>Contacto</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Fechas</th>
                        <th>Duración</th>
                        <th>Resultado</th>
                        <th>Seguimiento</th>
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
    <?php include 'modals/interacciones/modal_registrar_interaccion.php'; ?>
    <?php include 'modals/interacciones/modal_programar_interaccion.php'; ?>
    <?php include 'modals/interacciones/modal_consultar_historial.php'; ?>
    <?php include 'modals/interacciones/modal_gestionar_seguimientos.php'; ?>

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
            var table = $("#interacciones-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay interacciones disponibles en la tabla",
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

            // Función para consultar historial
            window.consultarHistorial = function() {
              $('#modalConsultarHistorial').modal('show');
            };

            // Manejar click en botón ver historial
            $(document).on('click', '.btn-ver-historial', function() {
                var apoderadoId = $(this).data('apoderado-id');
                var familiaId = $(this).data('familia-id');
                
                if (apoderadoId) {
                    cargarHistorialApoderado(apoderadoId);
                } else if (familiaId) {
                    cargarHistorialFamilia(familiaId);
                }
            });

            // Manejar click en botón completar
            $(document).on('click', '.btn-completar', function() {
                var id = $(this).data('id');
                completarInteraccion(id);
            });

            // Manejar click en botón actualizar seguimiento
            $(document).on('click', '.btn-actualizar-seguimiento', function() {
                var id = $(this).data('id');
                var prioridad = $(this).data('prioridad');
                actualizarSeguimiento(id, prioridad);
            });

            // Función para cargar historial de apoderado
            function cargarHistorialApoderado(apoderadoId) {
              $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'POST',
                data: { 
                  accion: 'consultar_historial',
                  apoderado_id: apoderadoId 
                },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarHistorialCompleto(response.data, 'Apoderado');
                  } else {
                    alert('Error al cargar el historial: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener el historial.');
                }
              });
            }

            // Función para completar interacción
            function completarInteraccion(id) {
              // Mostrar modal de completar con formulario
              $('#modalCompletarInteraccion').remove();
              
              var modalHTML = `
                <div class="modal fade" id="modalCompletarInteraccion" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Completar Interacción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="">
                        <div class="modal-body">
                          <input type="hidden" name="accion" value="completar_interaccion">
                          <input type="hidden" name="interaccion_id" value="${id}">
                          <div class="mb-3">
                            <label class="form-label">Resultado</label>
                            <select name="resultado" class="form-control" required>
                              <option value="">Seleccionar resultado</option>
                              <option value="exitoso">Exitoso</option>
                              <option value="sin_respuesta">Sin respuesta</option>
                              <option value="reagendar">Reagendar</option>
                              <option value="no_interesado">No interesado</option>
                              <option value="convertido">Convertido</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Duración (minutos)</label>
                            <input type="number" name="duracion_minutos" class="form-control" min="1">
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Observaciones finales</label>
                            <textarea name="observaciones_finales" class="form-control" rows="3" required></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                          <button type="submit" class="btn btn-success">Completar Interacción</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              `;
              
              $('body').append(modalHTML);
              $('#modalCompletarInteraccion').modal('show');
            }

            // Función para actualizar seguimiento
            function actualizarSeguimiento(id, prioridad) {
              // Mostrar modal de actualizar seguimiento
              $('#modalActualizarSeguimiento').remove();
              
              var modalHTML = `
                <div class="modal fade" id="modalActualizarSeguimiento" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Actualizar Seguimiento <span class="badge prioridad-${prioridad}">${prioridad.toUpperCase()}</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="">
                        <div class="modal-body">
                          <input type="hidden" name="accion" value="actualizar_seguimiento">
                          <input type="hidden" name="interaccion_id" value="${id}">
                          <div class="mb-3">
                            <label class="form-label">Observaciones del seguimiento</label>
                            <textarea name="observaciones" class="form-control" rows="3" required></textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Nueva fecha de seguimiento</label>
                            <input type="date" name="fecha_proximo_seguimiento" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                          </div>
                          <div class="mb-3">
                            <div class="form-check">
                              <input type="checkbox" name="requiere_seguimiento" id="requiere_seguimiento" class="form-check-input" checked>
                              <label for="requiere_seguimiento" class="form-check-label">Requiere seguimiento adicional</label>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                          <button type="submit" class="btn btn-warning">Actualizar Seguimiento</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              `;
              
              $('body').append(modalHTML);
              $('#modalActualizarSeguimiento').modal('show');
            }

            // Auto-refresh cada 2 minutos para interacciones programadas
            setInterval(function() {
              var interaccionesProgramadas = $('.estado-programado').length;
              if (interaccionesProgramadas > 0) {
                location.reload();
              }
            }, 120000); // 2 minutos

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