<?php
SESSION_start();
// Incluir conexión a la base de datos
include 'bd/conexion.php';

$usuario_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener el nombre del usuario
$usuario_id_logueado = $_SESSION['usuario_id'];
$usuario_nombre_logueado = $_SESSION['user_name'];
// Consulta para obtener usuarios con estadísticas de leads asignados
$sql = "SELECT 
    u.id,
    u.usuario,
    u.email,
    u.nombre,
    u.apellidos,
    u.telefono,
    u.rol_id,
    r.nombre as nombre_rol,
    u.ultimo_acceso,
    u.activo,
    u.created_at,
    CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
    
    -- Estadísticas de leads asignados
    COUNT(l.id) as total_leads_asignados,
    COUNT(CASE WHEN l.activo = 1 THEN 1 END) as leads_activos,
    COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_mes_actual,
    COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_semana_actual,
    COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as acciones_hoy,
    COUNT(CASE WHEN l.proxima_accion_fecha < CURDATE() AND l.proxima_accion_fecha IS NOT NULL THEN 1 END) as acciones_vencidas,
    COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
    COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) as leads_convertidos,
    
    -- Estadísticas por estado
    COUNT(CASE WHEN el.nombre = 'Nuevo' THEN 1 END) as leads_nuevos,
    COUNT(CASE WHEN el.nombre = 'Contactado' THEN 1 END) as leads_contactados,
    COUNT(CASE WHEN el.nombre = 'Interesado' THEN 1 END) as leads_interesados,
    
    -- Promedio de interés
    AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
    
    -- Fecha de última interacción
    MAX(l.fecha_ultima_interaccion) as ultima_interaccion,
    MAX(l.updated_at) as ultima_actividad,
    
    -- Carga de trabajo (leads activos / total posible)
    CASE 
        WHEN r.nombre = 'Coordinador Marketing' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 50.0 * 100
        WHEN r.nombre = 'Tutor' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 30.0 * 100
        ELSE COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 25.0 * 100
    END as porcentaje_carga
    
FROM usuarios u
LEFT JOIN roles r ON u.rol_id = r.id
LEFT JOIN leads l ON u.id = l.responsable_id
LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
WHERE u.activo = 1 
  AND r.nombre IN ('Administrador', 'Coordinador Marketing', 'Tutor', 'Finanzas')
GROUP BY u.id, u.usuario, u.email, u.nombre, u.apellidos, u.telefono, u.rol_id, r.nombre, u.ultimo_acceso, u.activo, u.created_at
ORDER BY total_leads_asignados DESC";

$result = $conn->query($sql);

// Obtener estadísticas generales del sistema
$stats_sistema_sql = "SELECT 
    COUNT(DISTINCT l.id) as total_leads_sistema,
    COUNT(DISTINCT CASE WHEN l.responsable_id IS NOT NULL THEN l.id END) as leads_asignados,
    COUNT(DISTINCT CASE WHEN l.responsable_id IS NULL THEN l.id END) as leads_sin_asignar,
    COUNT(DISTINCT u.id) as usuarios_activos,
    COUNT(DISTINCT CASE WHEN l.proxima_accion_fecha = CURDATE() THEN l.responsable_id END) as usuarios_con_tareas_hoy,
    COUNT(DISTINCT CASE WHEN l.prioridad = 'urgente' THEN l.responsable_id END) as usuarios_con_urgentes,
    AVG(subconsulta.leads_por_usuario) as promedio_leads_por_usuario
FROM leads l
LEFT JOIN usuarios u ON l.responsable_id = u.id
LEFT JOIN (
    SELECT responsable_id, COUNT(*) as leads_por_usuario
    FROM leads 
    WHERE responsable_id IS NOT NULL AND activo = 1
    GROUP BY responsable_id
) subconsulta ON u.id = subconsulta.responsable_id
WHERE l.activo = 1";

$stats_sistema_result = $conn->query($stats_sistema_sql);
$stats_sistema = $stats_sistema_result->fetch_assoc();

// Obtener leads sin asignar para distribución
$leads_sin_asignar_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as urgentes,
    COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as nuevos_hoy,
    COUNT(CASE WHEN el.nombre = 'Nuevo' THEN 1 END) as estado_nuevo
FROM leads l
LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
WHERE l.responsable_id IS NULL AND l.activo = 1";

$leads_sin_asignar_result = $conn->query($leads_sin_asignar_sql);
$leads_sin_asignar = $leads_sin_asignar_result->fetch_assoc();

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
    <title>Asignación de Responsables - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Responsables"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Responsables, Asignación, Carga Trabajo, Distribución"
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
    
    <!-- Custom styles for responsables -->
    <style>
      .badge-rol-responsable {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        border-radius: 15px;
        font-weight: 600;
        color: white;
      }
      .rol-administrador { background-color: #dc3545; }
      .rol-coordinador { background-color: #fd7e14; }
      .rol-tutor { background-color: #20c997; }
      .rol-finanzas { background-color: #6f42c1; }
      
      .usuario-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .usuario-nombre {
        font-weight: 700;
        color: #2c3e50;
        font-size: 0.95rem;
      }
      
      .usuario-email {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .usuario-username {
        font-family: 'Courier New', monospace;
        font-size: 0.7rem;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
        color: #495057;
      }
      
      .carga-trabajo {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
      }
      
      .carga-porcentaje {
        font-weight: bold;
        font-size: 1.1rem;
      }
      
      .carga-barra {
        width: 100%;
        height: 8px;
        background-color: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
      }
      
      .carga-progreso {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 10px;
      }
      
      .carga-baja { color: #28a745; }
      .carga-baja .carga-progreso { background-color: #28a745; }
      
      .carga-media { color: #ffc107; }
      .carga-media .carga-progreso { background-color: #ffc107; }
      
      .carga-alta { color: #fd7e14; }
      .carga-alta .carga-progreso { background-color: #fd7e14; }
      
      .carga-critica { color: #dc3545; }
      .carga-critica .carga-progreso { 
        background-color: #dc3545;
        animation: parpadeo 2s infinite;
      }
      
      @keyframes parpadeo {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
      }
      
      .estadisticas-usuario {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
        font-size: 0.75rem;
      }
      
      .stat-item-usuario {
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
      }
      
      .stat-number-usuario {
        font-weight: bold;
        color: #2c3e50;
      }
      
      .stat-label-usuario {
        color: #6c757d;
      }
      
      .conversion-rate-usuario {
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-weight: bold;
        text-align: center;
      }
      
      .promedio-interes-usuario {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.75rem;
      }
      
      .estrella-activa-usuario { color: #ffc107; font-size: 0.7rem; }
      .estrella-inactiva-usuario { color: #e9ecef; font-size: 0.7rem; }
      
      .actividad-info {
        font-size: 0.7rem;
        color: #6c757d;
      }
      
      .urgente-count {
        background-color: #dc3545;
        color: white;
        padding: 0.15rem 0.4rem;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: bold;
        animation: pulse 2s infinite;
      }
      
      .tareas-hoy {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: bold;
        border: 1px solid #ffeaa7;
      }
      
      .tareas-vencidas {
        background-color: #f8d7da;
        color: #721c24;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: bold;
        border: 1px solid #f5c6cb;
      }
      
      .stats-card-asignacion {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .stats-card-sin-asignar {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .btn-grupo-responsables {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-responsables .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .distribucion-panel {
        background: #d6eaff; /* Azul pastel */
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 20px;
      }
    </style>
    <style>
        .swal2-container {
            z-index: 9999999 !important;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
                    Asignación de Responsables
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Distribution Panel ] start -->
        <div class="row mb-3">
          <div class="col-sm-12">
            <div class="distribucion-panel">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h4 class="mb-2">Centro de Distribución de Leads</h4>
                  <p class="mb-0 text-muted">
                    Gestiona la asignación inteligente de leads entre tu equipo. 
                    Distribuye automáticamente, reasigna cargas de trabajo y optimiza la conversión.
                  </p>
                </div>
                <div class="col-md-4 text-end">
                  <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <!-- <button type="button" class="btn btn-light btn-sm" onclick="distribuirAutomaticamente()">
                      <i class="ti ti-robot me-1"></i>
                      Distribución Automática
                    </button> -->
                    <!-- <button type="button" class="btn btn-warning btn-sm" onclick="abrirModalReasignar()">
                      <i class="ti ti-refresh me-1"></i>
                      Reasignar Masivo
                    </button> -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Distribution Panel ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Gestión de Responsables y Carga de Trabajo
                  </h3>
                  <small class="text-muted">
                    Administra la asignación de leads entre tu equipo. 
                    Consulta cargas de trabajo, reasigna responsabilidades y optimiza la distribución.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarResponsablesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                <button type="button" 
                        class="btn btn-primary btn-sm" 
                        onclick="abrirModalAsignar(<?php echo $usuario_id_logueado; ?>, '<?php echo htmlspecialchars($usuario_nombre_logueado); ?>')">
                    <i class="ti ti-user-plus me-1"></i>
                    Asignar Leads a Mi Cartera
                </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de responsables -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="responsables-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="3%">ID</th>
                        <th width="15%">Usuario</th>
                        <th width="10%">Rol</th>
                        <th width="8%">Carga Trabajo</th>
                        <th width="12%">Estadísticas</th>
                        <th width="8%">Conversión</th>
                        <th width="8%">Interés Promedio</th>
                        <th width="8%">Urgentes</th>
                        <th width="8%">Tareas</th>
                        <th width="8%">Última Actividad</th>
                        <th width="12%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Calcular carga de trabajo
                              $porcentaje_carga = round($row['porcentaje_carga'] ?? 0, 1);
                              $carga_class = '';
                              if ($porcentaje_carga <= 50) $carga_class = 'carga-baja';
                              elseif ($porcentaje_carga <= 75) $carga_class = 'carga-media';
                              elseif ($porcentaje_carga <= 90) $carga_class = 'carga-alta';
                              else $carga_class = 'carga-critica';
                              
                              // Calcular tasa de conversión
                              $tasa_conversion = $row['total_leads_asignados'] > 0 ? 
                                round(($row['leads_convertidos'] / $row['total_leads_asignados']) * 100, 1) : 0;
                              
                              $conversion_class = '';
                              if ($tasa_conversion >= 20) $conversion_class = 'rate-high';
                              elseif ($tasa_conversion >= 10) $conversion_class = 'rate-medium';
                              else $conversion_class = 'rate-low';
                              
                              // Promedio de interés con estrellas
                              $promedio_interes = round($row['promedio_interes'] ?? 0, 1);
                              $estrellas = '';
                              for($i = 1; $i <= 5; $i++) {
                                  $clase = $i <= $promedio_interes ? 'estrella-activa-usuario ti ti-star-filled' : 'estrella-inactiva-usuario ti ti-star';
                                  $estrellas .= "<i class='$clase'></i>";
                              }
                              
                              // Determinar clase CSS para el rol
                              $rol_lower = strtolower($row['nombre_rol'] ?? '');
                              $rol_class = 'rol-finanzas'; // por defecto
                              if (strpos($rol_lower, 'administrador') !== false) $rol_class = 'rol-administrador';
                              elseif (strpos($rol_lower, 'coordinador') !== false) $rol_class = 'rol-coordinador';
                              elseif (strpos($rol_lower, 'tutor') !== false) $rol_class = 'rol-tutor';
                              
                              // Formatear última actividad
                              $ultima_actividad = $row['ultima_actividad'] ? 
                                'Hace ' . ceil((strtotime('now') - strtotime($row['ultima_actividad'])) / 86400) . ' días' : 'Sin actividad';
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='usuario-info'>
                                        <span class='usuario-nombre'>" . htmlspecialchars($row['nombre_completo']) . "</span>
                                        <span class='usuario-email'>" . htmlspecialchars($row['email']) . "</span>
                                        <span class='usuario-username'>@" . htmlspecialchars($row['usuario']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-rol-responsable $rol_class'>" . 
                                   htmlspecialchars($row['nombre_rol']) . "</span></td>";
                              echo "<td>
                                      <div class='carga-trabajo $carga_class'>
                                        <span class='carga-porcentaje'>" . $porcentaje_carga . "%</span>
                                        <div class='carga-barra'>
                                          <div class='carga-progreso' style='width: " . min($porcentaje_carga, 100) . "%;'></div>
                                        </div>
                                        <small>" . $row['leads_activos'] . " leads activos</small>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='estadisticas-usuario'>
                                        <div class='stat-item-usuario'>
                                          <span class='stat-label-usuario'>Total:</span>
                                          <span class='stat-number-usuario'>" . $row['total_leads_asignados'] . "</span>
                                        </div>
                                        <div class='stat-item-usuario'>
                                          <span class='stat-label-usuario'>Este mes:</span>
                                          <span class='stat-number-usuario'>" . $row['leads_mes_actual'] . "</span>
                                        </div>
                                        <div class='stat-item-usuario'>
                                          <span class='stat-label-usuario'>Nuevos:</span>
                                          <span class='stat-number-usuario'>" . $row['leads_nuevos'] . "</span>
                                        </div>
                                        <div class='stat-item-usuario'>
                                          <span class='stat-label-usuario'>Contactados:</span>
                                          <span class='stat-number-usuario'>" . $row['leads_contactados'] . "</span>
                                        </div>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='conversion-rate-usuario $conversion_class'>
                                        " . $tasa_conversion . "%
                                      </div>
                                      <small class='text-muted'>" . $row['leads_convertidos'] . " convertidos</small>
                                    </td>";
                              echo "<td>
                                      <div class='promedio-interes-usuario'>
                                        $estrellas
                                      </div>
                                      <small class='text-muted'>" . $promedio_interes . "/5</small>
                                    </td>";
                              echo "<td>
                                      " . ($row['leads_urgentes'] > 0 ? 
                                        "<span class='urgente-count'>" . $row['leads_urgentes'] . "</span>" : 
                                        "<span class='text-muted'>0</span>") . "
                                    </td>";
                              echo "<td>
                                      <div style='display: flex; flex-direction: column; gap: 2px;'>
                                        " . ($row['acciones_hoy'] > 0 ? 
                                          "<span class='tareas-hoy'>Hoy: " . $row['acciones_hoy'] . "</span>" : "") . "
                                        " . ($row['acciones_vencidas'] > 0 ? 
                                          "<span class='tareas-vencidas'>Vencidas: " . $row['acciones_vencidas'] . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <span class='actividad-info'>" . $ultima_actividad . "</span>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-responsables'>
                                        <button type='button' class='btn btn-outline-info btn-consultar-carga' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_completo']) . "'
                                                title='Consultar Carga'>
                                          <i class='ti ti-chart-bar'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay usuarios responsables registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Carga Trabajo</th>
                        <th>Estadísticas</th>
                        <th>Conversión</th>
                        <th>Interés Promedio</th>
                        <th>Urgentes</th>
                        <th>Tareas</th>
                        <th>Última Actividad</th>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Incluir Modales -->
    <?php include 'modals/asignacion_leads/modal_asignar_lead.php'; ?>
    <?php include 'modals/asignacion_leads/modal_consultar_carga.php'; ?>
    <?php include 'modals/asignacion_leads/modal_distribuir_automatico.php'; ?>
    <?php include 'modals/asignacion_leads/modal_reasignar.php'; ?>


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
            var table = $("#responsables-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay responsables disponibles en la tabla",
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
              "pageLength": 15,
              "order": [[ 3, "desc" ]], // Ordenar por carga de trabajo descendente
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

            // Función para exportar responsables a PDF
            window.exportarResponsablesPDF = function() {
              var tabla = $('#responsables-table').DataTable();
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
              form.action = 'reports/generar_pdf_responsables.php';
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

            // Función para distribución automática
            window.distribuirAutomaticamente = function() {
              $('#modalDistribuirAutomatico').modal('show');
            };

            // Función para reasignación masiva
            window.reasignarMasivo = function() {
              $('#modalReasignar').modal('show');
            };

            // Manejar click en botón consultar carga
            $(document).on('click', '.btn-consultar-carga', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#carga_usuario_id').val(id);
                $('#carga_usuario_nombre').text(nombre);
                
                cargarCargaTrabajo(id);
                
                $('#modalConsultarCarga').modal('show');
            });

            // Manejar click en botón asignar lead
            $(document).on('click', '.btn-asignar-lead', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#asignar_usuario_id').val(id);
                $('#asignar_usuario_nombre').text(nombre);
                
                cargarLeadsSinAsignar();
                
                $('#modalAsignarLead').modal('show');
            });

            // Manejar click en botón reasignar
            $(document).on('click', '.btn-reasignar', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#reasignar_usuario_origen_id').val(id);
                $('#reasignar_usuario_origen_nombre').text(nombre);
                
                cargarLeadsUsuario(id);
                cargarUsuariosDestino(id);
                
                $('#modalReasignar').modal('show');
            });

            // Función para cargar carga de trabajo detallada
            function cargarCargaTrabajo(usuarioId) {
              $.ajax({
                url: 'acciones/asignacion_leads/obtener_carga_trabajo.php',
                method: 'POST',
                data: { usuario_id: usuarioId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    $('#carga-detalle-contenido').html(response.html);
                  } else {
                    $('#carga-detalle-contenido').html('<p class="text-danger">Error al cargar la carga de trabajo.</p>');
                  }
                },
                error: function() {
                  $('#carga-detalle-contenido').html('<p class="text-danger">Error de conexión.</p>');
                }
              });
            }

            // Función para cargar leads sin asignar
            function cargarLeadsSinAsignar() {
              $.ajax({
                url: 'acciones/asignacion_leads/obtener_leads_sin_asignar.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    var select = $('#leads_para_asignar');
                    select.empty();
                    response.data.forEach(function(lead) {
                      select.append('<option value="' + lead.id + '">' + lead.nombre + ' - ' + lead.estado + '</option>');
                    });
                  }
                },
                error: function() {
                  alert('Error al cargar leads sin asignar.');
                }
              });
            }

            // Función para cargar leads de un usuario
            function cargarLeadsUsuario(usuarioId) {
              $.ajax({
                url: 'acciones/asignacion_leads/obtener_leads_usuario.php',
                method: 'POST',
                data: { usuario_id: usuarioId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    var select = $('#leads_para_reasignar');
                    select.empty();
                    response.data.forEach(function(lead) {
                      select.append('<option value="' + lead.id + '">' + lead.nombre + ' - ' + lead.estado + '</option>');
                    });
                  }
                },
                error: function() {
                  alert('Error al cargar leads del usuario.');
                }
              });
            }

            // Función para cargar usuarios destino
            function cargarUsuariosDestino(usuarioOrigenId) {
              $.ajax({
                url: 'acciones/asignacion_leads/obtener_usuarios_destino.php',
                method: 'POST',
                data: { usuario_origen_id: usuarioOrigenId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    var select = $('#usuario_destino');
                    select.empty();
                    response.data.forEach(function(usuario) {
                      select.append('<option value="' + usuario.id + '">' + usuario.nombre + ' (' + usuario.carga + '%)</option>');
                    });
                  }
                },
                error: function() {
                  alert('Error al cargar usuarios destino.');
                }
              });
            }
            
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