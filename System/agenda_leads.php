<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener interacciones con información relacionada
$sql = "SELECT 
    i.id,
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
    i.activo,
    i.created_at,
    i.updated_at,
    
    -- Información del tipo de interacción
    ti.nombre as tipo_interaccion,
    ti.descripcion as tipo_descripcion,
    ti.icono as tipo_icono,
    ti.color as tipo_color,
    
    -- Información del usuario responsable
    u.nombre as usuario_nombre,
    u.apellidos as usuario_apellidos,
    CONCAT(u.nombre, ' ', u.apellidos) as usuario_completo,
    
    -- Información del lead
    l.nombres_estudiante,
    l.apellidos_estudiante,
    l.nombres_contacto,
    l.apellidos_contacto,
    l.telefono as lead_telefono,
    l.email as lead_email,
    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as estudiante_completo,
    CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as contacto_completo,
    
    -- Estado del lead
    el.nombre as estado_lead,
    el.color as estado_color,
    
    -- Información de apoderados y familias si aplica
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_completo,
    f.apellido_principal as familia_apellido
    
FROM interacciones i
LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
LEFT JOIN leads l ON i.lead_id = l.id
LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
LEFT JOIN apoderados a ON i.apoderado_id = a.id
LEFT JOIN familias f ON i.familia_id = f.id
WHERE i.activo = 1
ORDER BY 
  CASE 
    WHEN i.estado = 'programado' AND i.fecha_programada <= NOW() THEN 1
    WHEN i.estado = 'programado' AND i.fecha_programada > NOW() THEN 2
    WHEN i.estado = 'reagendado' THEN 3
    ELSE 4
  END,
  i.fecha_programada ASC";

$result = $conn->query($sql);

// Obtener estadísticas de interacciones
$stats_sql = "SELECT 
    COUNT(*) as total_interacciones,
    COUNT(CASE WHEN i.estado = 'programado' THEN 1 END) as programadas,
    COUNT(CASE WHEN i.estado = 'realizado' THEN 1 END) as realizadas,
    COUNT(CASE WHEN i.estado = 'reagendado' THEN 1 END) as reagendadas,
    COUNT(CASE WHEN i.estado = 'cancelado' THEN 1 END) as canceladas,
    COUNT(CASE WHEN DATE(i.fecha_programada) = CURDATE() AND i.estado = 'programado' THEN 1 END) as hoy,
    COUNT(CASE WHEN DATE(i.fecha_programada) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND i.estado = 'programado' THEN 1 END) as mañana,
    COUNT(CASE WHEN i.fecha_programada < NOW() AND i.estado = 'programado' THEN 1 END) as vencidas,
    COUNT(CASE WHEN i.requiere_seguimiento = 1 AND i.fecha_proximo_seguimiento <= CURDATE() THEN 1 END) as seguimientos_pendientes,
    COUNT(DISTINCT i.usuario_id) as usuarios_con_interacciones,
    COUNT(DISTINCT i.lead_id) as leads_con_interacciones,
    AVG(CASE WHEN i.duracion_minutos IS NOT NULL AND i.duracion_minutos > 0 THEN i.duracion_minutos END) as duracion_promedio
FROM interacciones i
WHERE i.activo = 1 AND i.fecha_programada >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas por tipo de interacción
$tipos_stats_sql = "SELECT 
    ti.nombre as tipo_nombre,
    ti.color as tipo_color,
    ti.icono as tipo_icono,
    COUNT(i.id) as total,
    COUNT(CASE WHEN i.estado = 'realizado' THEN 1 END) as completadas,
    COUNT(CASE WHEN DATE(i.fecha_programada) = CURDATE() AND i.estado = 'programado' THEN 1 END) as hoy,
    AVG(CASE WHEN i.duracion_minutos IS NOT NULL AND i.duracion_minutos > 0 THEN i.duracion_minutos END) as duracion_promedio
FROM tipos_interaccion ti
LEFT JOIN interacciones i ON ti.id = i.tipo_interaccion_id AND i.activo = 1 
WHERE ti.activo = 1
GROUP BY ti.id, ti.nombre, ti.color, ti.icono
ORDER BY total DESC";

$tipos_stats_result = $conn->query($tipos_stats_sql);

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
    <title>Programación de Interacciones - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Programación de Interacciones"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Interacciones, Citas, Llamadas, Seguimiento, Agenda"
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
        padding: 0.3rem 0.7rem;
        border-radius: 15px;
        font-weight: 600;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 5px;
      }
      
      .badge-estado-interaccion {
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-weight: 600;
      }
      .estado-programado { background-color: #17a2b8; color: white; }
      .estado-realizado { background-color: #28a745; color: white; }
      .estado-reagendado { background-color: #ffc107; color: #000; }
      .estado-cancelado { background-color: #dc3545; color: white; }
      
      .interaccion-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .interaccion-asunto {
        font-weight: 700;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .interaccion-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .contacto-interaccion {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .contacto-estudiante {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.85rem;
      }
      
      .contacto-apoderado {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .contacto-info {
        font-size: 0.7rem;
        color: #6c757d;
        font-family: 'Courier New', monospace;
      }
      
      .fecha-interaccion {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }
      
      .fecha-dia {
        font-weight: bold;
        font-size: 0.85rem;
        color: #2c3e50;
      }
      
      .fecha-hora {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .fecha-vencida {
        color: #dc3545 !important;
        font-weight: bold;
        animation: parpadeo 2s infinite;
      }
      
      .fecha-hoy {
        color: #fd7e14 !important;
        font-weight: bold;
      }
      
      .fecha-mañana {
        color: #17a2b8 !important;
        font-weight: bold;
      }
      
      @keyframes parpadeo {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
      }
      
      .resultado-interaccion {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .resultado-exitoso { background-color: #d4edda; color: #155724; }
      .resultado-sin_respuesta { background-color: #fff3cd; color: #856404; }
      .resultado-reagendar { background-color: #d1ecf1; color: #0c5460; }
      .resultado-no_interesado { background-color: #f8d7da; color: #721c24; }
      .resultado-convertido { background-color: #d4edda; color: #155724; font-weight: bold; }
      
      .duracion-info {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: center;
      }
      
      .seguimiento-indicator {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: bold;
        border: 1px solid #ffeaa7;
      }
      
      .seguimiento-vencido {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
        animation: parpadeo 2s infinite;
      }
      
      .stats-card-interacciones {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .stats-card-tipos {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .agenda-panel {
        background: #d6eaff; /* Azul pastel */
        color: #2c3e50;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 20px;
      }
      
      .tipo-stat-item {
        text-align: center;
        padding: 10px;
        margin: 5px;
        border-radius: 8px;
        background-color: rgba(255,255,255,0.1);
      }
      
      .btn-grupo-interacciones {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-interacciones .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .usuario-responsable {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
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
                    <a href="javascript: void(0)">Captación</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Programación de Interacciones
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Agenda Panel ] start -->
        <div class="row mb-3">
          <div class="col-sm-12">
            <div class="agenda-panel">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h4 class="mb-2">Centro de Gestión de Interacciones</h4>
                  <p class="mb-0">
                    Programa, reprograma y gestiona todas las interacciones con tus leads y familias. 
                    Mantén un seguimiento efectivo de citas, llamadas y visitas programadas.
                  </p>
                </div>
                <div class="col-md-4 text-end">
                  <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm" onclick="consultarAgenda()">
                      <i class="ti ti-calendar me-1"></i>
                      Consultar Agenda
                    </button>
                      <!-- <button type="button" class="btn btn-warning btn-sm" onclick="configurarRecordatorios()">
                        <i class="ti ti-bell me-1"></i>
                        Recordatorios
                      </button> -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Agenda Panel ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Gestión de Interacciones y Seguimientos
                  </h3>
                  <small class="text-muted">
                    Programa, reprograma y registra resultados de todas tus interacciones con leads y familias. 
                    Mantén un control completo del pipeline de comunicación.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramarInteraccion">
                    <i class="ti ti-calendar-plus me-1"></i>
                    Programar Interacción
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
                        <th width="15%">Interacción</th>
                        <th width="8%">Tipo</th>
                        <th width="13%">Contacto</th>
                        <th width="10%">Fecha/Hora</th>
                        <th width="8%">Estado</th>
                        <th width="8%">Resultado</th>
                        <th width="7%">Duración</th>
                        <th width="8%">Seguimiento</th>
                        <th width="8%">Responsable</th>
                        <th width="11%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_programada = $row['fecha_programada'] ? new DateTime($row['fecha_programada']) : null;
                              $fecha_actual = new DateTime();
                              
                              $fecha_class = '';
                              $fecha_dia = '';
                              $fecha_hora = '';
                              
                              if ($fecha_programada) {
                                  $fecha_dia = $fecha_programada->format('d/m/Y');
                                  $fecha_hora = $fecha_programada->format('H:i');
                                  
                                  if ($fecha_programada->format('Y-m-d') == $fecha_actual->format('Y-m-d')) {
                                      $fecha_class = 'fecha-hoy';
                                      $fecha_dia = 'HOY';
                                  } elseif ($fecha_programada->format('Y-m-d') == $fecha_actual->modify('+1 day')->format('Y-m-d')) {
                                      $fecha_class = 'fecha-mañana';
                                      $fecha_dia = 'MAÑANA';
                                  } elseif ($fecha_programada < $fecha_actual && $row['estado'] == 'programado') {
                                      $fecha_class = 'fecha-vencida';
                                  }
                              }
                              
                              // Determinar contacto principal
                              $contacto_principal = '';
                              $contacto_secundario = '';
                              $contacto_info = '';
                              
                              if ($row['estudiante_completo']) {
                                  $contacto_principal = $row['estudiante_completo'];
                                  $contacto_secundario = $row['contacto_completo'] ?? '';
                                  $contacto_info = $row['lead_telefono'] ?? $row['lead_email'] ?? '';
                              } elseif ($row['apoderado_completo']) {
                                  $contacto_principal = $row['apoderado_completo'];
                                  $contacto_secundario = 'Familia ' . ($row['familia_apellido'] ?? '');
                              }
                              
                              // Determinar seguimiento
                              $seguimiento_texto = '';
                              $seguimiento_class = '';
                              if ($row['requiere_seguimiento']) {
                                  if ($row['fecha_proximo_seguimiento']) {
                                      $fecha_seguimiento = new DateTime($row['fecha_proximo_seguimiento']);
                                      if ($fecha_seguimiento <= $fecha_actual) {
                                          $seguimiento_texto = 'Pendiente';
                                          $seguimiento_class = 'seguimiento-vencido';
                                      } else {
                                          $seguimiento_texto = $fecha_seguimiento->format('d/m/Y');
                                          $seguimiento_class = 'seguimiento-indicator';
                                      }
                                  } else {
                                      $seguimiento_texto = 'Por definir';
                                      $seguimiento_class = 'seguimiento-indicator';
                                  }
                              }
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='interaccion-info'>
                                        <span class='interaccion-asunto'>" . htmlspecialchars($row['asunto']) . "</span>
                                        <span class='interaccion-descripcion' title='" . htmlspecialchars($row['descripcion'] ?? '') . "'>" . 
                                             htmlspecialchars($row['descripcion'] ?? 'Sin descripción') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <span class='badge badge-tipo-interaccion' style='background-color: " . 
                                      ($row['tipo_color'] ?? '#007bff') . ";'>
                                        <i class='ti ti-" . htmlspecialchars($row['tipo_icono'] ?? 'circle') . "'></i>
                                        " . htmlspecialchars($row['tipo_interaccion'] ?? 'Sin tipo') . "
                                      </span>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-interaccion'>
                                        <span class='contacto-estudiante'>" . htmlspecialchars($contacto_principal) . "</span>
                                        " . ($contacto_secundario ? "<span class='contacto-apoderado'>" . htmlspecialchars($contacto_secundario) . "</span>" : "") . "
                                        " . ($contacto_info ? "<span class='contacto-info'>" . htmlspecialchars($contacto_info) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='fecha-interaccion'>
                                        <span class='fecha-dia $fecha_class'>" . $fecha_dia . "</span>
                                        <span class='fecha-hora $fecha_class'>" . $fecha_hora . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-interaccion estado-" . $row['estado'] . "'>" . 
                                   ucfirst($row['estado']) . "</span></td>";
                              echo "<td>
                                      " . ($row['resultado'] ? 
                                        "<span class='resultado-interaccion resultado-" . $row['resultado'] . "'>" . 
                                        ucfirst(str_replace('_', ' ', $row['resultado'])) . "</span>" : 
                                        "<span class='text-muted'>Pendiente</span>") . "
                                    </td>";
                              echo "<td>
                                      <div class='duracion-info'>
                                        " . ($row['duracion_minutos'] ? $row['duracion_minutos'] . " min" : "N/A") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      " . ($seguimiento_texto ? 
                                        "<span class='$seguimiento_class'>$seguimiento_texto</span>" : 
                                        "<span class='text-muted'>No requerido</span>") . "
                                    </td>";
                              echo "<td>
                                      <span class='usuario-responsable'>" . htmlspecialchars($row['usuario_completo'] ?? 'Sin asignar') . "</span>
                                    </td>";
                                  echo "<td>
                                      <div class='btn-grupo-interacciones'>";
                                          
                                          if ($row['resultado'] != 'exitoso') { 
                                  echo "      <button type='button' class='btn btn-outline-warning btn-reprogramar' 
                                                  data-id='" . $row['id'] . "' title='Reprogramar'>
                                                  <i class='ti ti-calendar-time'></i>
                                              </button>";
                                    }
                                          // --- AQUÍ LA CONDICIÓN ---
                                          // Solo muestra el botón si el resultado NO es 'exitoso'
                                          if ($row['resultado'] != 'exitoso') { 
                                  echo "          <button type='button' class='btn btn-outline-success btn-registrar-resultado' 
                                                      data-id='" . $row['id'] . "'
                                                      data-asunto='" . htmlspecialchars($row['asunto']) . "'
                                                      title='Registrar Resultado'>
                                                      <i class='ti ti-check'></i>
                                                  </button>";
                                          }
                                          // --- FIN DE LA CONDICIÓN ---

                                          // Botón Ver Detalle (se muestra siempre)
                                  echo "      <button type='button' class='btn btn-outline-info btn-ver-detalle' 
                                                  data-id='" . $row['id'] . "' title='Ver Detalle'>
                                                  <i class='ti ti-eye'></i>
                                              </button>";

                                  echo "  </div>
                                      </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay interacciones programadas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Interacción</th>
                        <th>Tipo</th>
                        <th>Contacto</th>
                        <th>Fecha/Hora</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Duración</th>
                        <th>Seguimiento</th>
                        <th>Responsable</th>
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
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- Incluir Modales -->
    <?php include 'modals/agenda_leads/modal_programar.php'; ?>
    <?php include 'modals/agenda_leads/modal_reprogramar.php'; ?>
    <?php include 'modals/agenda_leads/modal_registrar_resultado.php'; ?>
    <?php include 'modals/agenda_leads/modal_consultar_agenda.php'; ?>
    <?php include 'modals/agenda_leads/modal_detalle_interaccion.php'; ?>

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
        // Inicializar DataTable
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
            }
          },
          "pageLength": 10,
          "order": [[ 0, "desc" ]], // Ordenar por ID ascendente
          "columnDefs": [
            { "orderable": false, "targets": 10 } // Deshabilitar ordenación en columna de acciones
          ],
          "initComplete": function () {
            this.api().columns().every(function (index) {
          var column = this;
          
          if (index < 10) { // Solo filtros para las primeras 10 columnas
            var title = $(column.header()).text();
            var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
              .appendTo($(column.footer()).empty())
              .on('keyup change clear', function () {
            if (column.search() !== this.value) {
              column.search(this.value).draw();
            }
              });
          } else {
            $(column.footer()).html('<strong>Acciones</strong>');
          }
            });
          }
        });

            // Función para exportar interacciones a PDF
            window.exportarInteraccionesPDF = function() {
              var tabla = $('#interacciones-table').DataTable();
              var datosVisibles = [];
              
              tabla.rows({ filter: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                var data = this.data();
                var row = [];
                
                for (var i = 0; i < data.length - 1; i++) {
                  var cellContent = $(data[i]).text() || data[i];
                  row.push(cellContent);
                }
                datosVisibles.push(row);
              });
              
              if (datosVisibles.length === 0) {
                alert('No hay registros visibles para generar el reporte PDF.');
                return;
              }
              
              var form = document.createElement('form');
              form.method = 'POST';
              form.action = 'reports/generar_pdf_interacciones.php';
              form.target = '_blank';
              
              var input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'filteredData';
              input.value = JSON.stringify(datosVisibles);
              
              form.appendChild(input);
              document.body.appendChild(form);
              form.submit();
              document.body.removeChild(form);
            };

            // Función para consultar agenda
            window.consultarAgenda = function() {
              $('#modalConsultarAgenda').modal('show');
              cargarAgendaSemanal();
            };

            // Función para configurar recordatorios
            window.configurarRecordatorios = function() {
              // Redirigir a página de configuración de recordatorios
              window.location.href = 'configurar_recordatorios.php';
            };

            // Manejar click en botón reprogramar
            $(document).on('click', '.btn-reprogramar', function() {
                var id = $(this).data('id');
                
                cargarDatosInteraccion(id, 'reprogramar');
                $('#modalReprogramar').modal('show');
            });

            // Manejar click en botón registrar resultado
            $(document).on('click', '.btn-registrar-resultado', function() {
                var id = $(this).data('id');
                var asunto = $(this).data('asunto');
                
                $('#resultado_interaccion_id').val(id);
                $('#resultado_asunto').text(asunto);
                
                $('#modalRegistrarResultado').modal('show');
            });

            // Manejar click en botón ver detalle
            $(document).on('click', '.btn-ver-detalle', function() {
                var id = $(this).data('id');
                
                cargarDatosInteraccion(id, 'detalle');
                $('#modalDetalleInteraccion').modal('show');
            });

            // Función para cargar datos de interacción
            function cargarDatosInteraccion(id, accion) {
              $.ajax({
                url: 'actions/obtener_interaccion.php',
                method: 'POST',
                data: { id: id, accion: accion },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    if (accion === 'reprogramar') {
                      // Llenar modal de reprogramación
                      $('#reprogramar_interaccion_id').val(response.data.id);
                      $('#reprogramar_asunto_actual').text(response.data.asunto);
                      $('#reprogramar_fecha_actual').text(response.data.fecha_programada);
                      $('#reprogramar_nueva_fecha').val('');
                      $('#reprogramar_nueva_hora').val('');
                      $('#reprogramar_motivo').val('');
                    } else if (accion === 'detalle') {
                      // Llenar modal de detalle
                      $('#detalle-interaccion-contenido').html(response.html);
                    }
                  } else {
                    alert('Error al cargar los datos: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener los datos de la interacción.');
                }
              });
            }

            // Función para cargar agenda semanal
            function cargarAgendaSemanal() {
              $.ajax({
                url: 'actions/obtener_agenda_semanal.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    $('#agenda-semanal-contenido').html(response.html);
                  } else {
                    $('#agenda-semanal-contenido').html('<p class="text-danger">Error al cargar la agenda.</p>');
                  }
                },
                error: function() {
                  $('#agenda-semanal-contenido').html('<p class="text-danger">Error de conexión.</p>');
                }
              });
            }

            // Actualizar la página cada 5 minutos para mostrar interacciones actualizadas
            setInterval(function() {
              table.ajax.reload(null, false);
            }, 300000); // 5 minutos
            
            // Tooltip para elementos
            $('[title]').tooltip();
      });
    </script>

        <script>
            $(document).ready(function() {
          // Mostrar/ocultar campo de fecha de seguimiento
          $('#requiere_seguimiento').change(function() {
              if ($(this).is(':checked')) {
                  $('#div_fecha_seguimiento').slideDown();
                  $('#fecha_proximo_seguimiento').prop('required', true);
              } else {
                  $('#div_fecha_seguimiento').slideUp();
                  $('#fecha_proximo_seguimiento').prop('required', false);
                  $('#fecha_proximo_seguimiento').val('');
              }
          });

          // Mostrar info del lead seleccionado
          $('#lead_id').change(function() {
              var selected = $(this).find(':selected');
              var telefono = selected.data('telefono');
              var email = selected.data('email');
              var contacto = selected.data('contacto');
              
              var info = '';
              if (contacto) info += 'Contacto: ' + contacto;
              if (telefono) info += (info ? ' | ' : '') + 'Tel: ' + telefono;
              if (email) info += (info ? ' | ' : '') + 'Email: ' + email;
              
              $('#info_lead').text(info);
          });

          // Limitar duración a 3 dígitos
          $('#duracion_minutos').on('input', function() {
              if (this.value.length > 3) {
                  this.value = this.value.slice(0, 3);
              }
              if (this.value > 999) {
                  this.value = 999;
              }
          });

          // Establecer fecha mínima (hoy)
          var today = new Date().toISOString().split('T')[0];
          $('#fecha_programada').attr('min', today);
          $('#fecha_proximo_seguimiento').attr('min', today);

          // Submit del formulario PROGRAMAR
          $('#formProgramarInteraccion').submit(function(e) {
              e.preventDefault();
              
              // Validación de duración
              var duracion = $('#duracion_minutos').val();
              if (duracion && (duracion < 1 || duracion > 999)) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'Duración Inválida',
                      text: 'La duración debe estar entre 1 y 999 minutos',
                      confirmButtonColor: '#ffc107'
                  });
                  return false;
              }
              
              var formData = $(this).serialize();
              
              // Mostrar loading
              Swal.fire({
                  title: 'Programando...',
                  text: 'Por favor espere',
                  allowOutsideClick: false,
                  didOpen: () => {
                      Swal.showLoading();
                  }
              });
              
              $.ajax({
                  url: 'actions/programar_interaccion.php',
                  method: 'POST',
                  data: formData,
                  dataType: 'json',
                  cache: false,
                  success: function(response) {
                      Swal.close();
                      
                      if (response.success) {
                          Swal.fire({
                              icon: 'success',
                              title: '¡Éxito!',
                              text: response.message,
                              confirmButtonColor: '#28a745',
                              timer: 2000,
                              timerProgressBar: true,
                              showConfirmButton: false
                          }).then(() => {
                              $('#modalProgramarInteraccion').modal('hide');
                              $('#formProgramarInteraccion')[0].reset();
                              $('#info_lead').text('');
                              location.reload();
                          });
                      } else {
                          Swal.fire({
                              icon: 'error',
                              title: 'Error',
                              text: response.message,
                              confirmButtonColor: '#dc3545'
                          });
                      }
                  },
                  error: function(xhr, status, error) {
                      Swal.close();
                      
                      console.error('Error AJAX:', xhr);
                      
                      var mensaje = 'Error de conexión con el servidor';
                      
                      if (xhr.responseJSON && xhr.responseJSON.message) {
                          mensaje = xhr.responseJSON.message;
                      } else if (xhr.responseText) {
                          console.log('Respuesta:', xhr.responseText);
                          mensaje = 'Error del servidor';
                      }
                      
                      Swal.fire({
                          icon: 'error',
                          title: 'Error de Conexión',
                          text: mensaje,
                          confirmButtonColor: '#dc3545'
                      });
                  }
              });
          });
      });
    </script>

  </body>
  <!-- [Body] end -->
</html>

<?php
// Cerrar conexión
$conn->close();
?>