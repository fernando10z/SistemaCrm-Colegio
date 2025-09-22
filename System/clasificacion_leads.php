<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los estados con estadísticas de leads
$sql = "SELECT 
    el.id,
    el.nombre,
    el.descripcion,
    el.color,
    el.orden_display,
    el.es_final,
    el.activo,
    el.created_at,
    COUNT(l.id) as total_leads_estado,
    COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_mes_actual,
    COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_semana_actual,
    AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
    COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
    COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as acciones_hoy,
    MAX(l.created_at) as ultimo_lead_fecha,
    COUNT(CASE WHEN l.responsable_id IS NOT NULL THEN 1 END) as leads_asignados
FROM estados_lead el
LEFT JOIN leads l ON el.id = l.estado_lead_id AND l.activo = 1
WHERE el.activo = 1
GROUP BY el.id, el.nombre, el.descripcion, el.color, el.orden_display, el.es_final, el.activo, el.created_at
ORDER BY el.orden_display ASC, el.id ASC";

$result = $conn->query($sql);

// Obtener estadísticas generales de cambios de estado
$stats_historial_sql = "SELECT 
    COUNT(*) as total_cambios,
    COUNT(DISTINCT hel.lead_id) as leads_con_cambios,
    COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as cambios_semana,
    COUNT(CASE WHEN hel.created_at >= CURDATE() THEN 1 END) as cambios_hoy,
    COUNT(DISTINCT hel.usuario_id) as usuarios_activos,
    AVG(TIMESTAMPDIFF(DAY, l.created_at, hel.created_at)) as dias_promedio_cambio
FROM historial_estados_lead hel
LEFT JOIN leads l ON hel.lead_id = l.id
WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$stats_historial_result = $conn->query($stats_historial_sql);
$stats_historial = $stats_historial_result->fetch_assoc();

// Obtener flujo de estados más común
$flujo_sql = "SELECT 
    ea.nombre as estado_anterior,
    en.nombre as estado_nuevo,
    COUNT(*) as total_cambios,
    ea.color as color_anterior,
    en.color as color_nuevo
FROM historial_estados_lead hel
LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY hel.estado_anterior_id, hel.estado_nuevo_id, ea.nombre, en.nombre, ea.color, en.color
ORDER BY total_cambios DESC
LIMIT 10";

$flujo_result = $conn->query($flujo_sql);

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
    <title>Clasificación y Estados - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Estados de Leads"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Estados, Clasificación, Workflow, Pipeline"
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
    
    <!-- Custom styles for estados -->
    <style>
      .badge-estado-config {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 5px;
      }
      
      .badge-final {
        position: relative;
      }
      
      .badge-final::after {
        content: "🏁";
        margin-left: 4px;
      }
      
      .estado-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
      }
      
      .estado-nombre {
        font-weight: 700;
        font-size: 1rem;
        color: #2c3e50;
      }
      
      .estado-descripcion {
        font-size: 0.8rem;
        color: #6c757d;
        font-style: italic;
        line-height: 1.3;
      }
      
      .orden-display {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
      }
      
      .estadisticas-estado {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .stat-item-small {
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
      }
      
      .stat-number-small {
        font-weight: bold;
        color: #2c3e50;
      }
      
      .stat-label-small {
        color: #6c757d;
      }
      
      .conversion-rate {
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-weight: bold;
      }
      
      .rate-high { background-color: #d4edda; color: #155724; }
      .rate-medium { background-color: #fff3cd; color: #856404; }
      .rate-low { background-color: #f8d7da; color: #721c24; }
      
      .interes-promedio {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.75rem;
      }
      
      .estrella-promedio { color: #ffc107; font-size: 0.7rem; }
      .estrella-vacia { color: #e9ecef; font-size: 0.7rem; }
      
      .urgente-indicator {
        background-color: #dc3545;
        color: white;
        padding: 0.15rem 0.4rem;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: bold;
      }
      
      .urgente-indicator.pulsing {
        animation: pulse 2s infinite;
      }
      
      @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
      }
      
      .acciones-hoy {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: bold;
        border: 1px solid #ffeaa7;
      }
      
      .ultimo-lead {
        font-size: 0.7rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .stats-card-historial {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .stats-card-historial .card-body {
        padding: 1.5rem;
      }
      
      .flujo-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        margin-bottom: 8px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border-left: 4px solid #007bff;
      }
      
      .flujo-estados {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .flujo-arrow {
        color: #6c757d;
        font-size: 1.2rem;
      }
      
      .flujo-count {
        background-color: #007bff;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: bold;
        margin-left: auto;
      }
      
      .btn-grupo-estados {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-estados .btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
      }
      
      .config-panel {
        background: #d6eaff; /* Azul pastel */
        color: #333; /* Texto oscuro para mejor contraste */
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 20px;
      }
      
      .workflow-visualization {
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                    Estados y Clasificación
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Config Panel ] start -->
        <div class="row mb-3">
          <div class="col-sm-12">
            <div class="config-panel">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h4 class="mb-2">Panel de Configuración de Estados</h4>
                  <p class="mb-0 opacity-75">
                    Gestiona el flujo de estados de tus leads. Configura etapas, define estados finales y establece el proceso de conversión óptimo para tu institución educativa.
                  </p>
                </div>
                <div class="col-md-4 text-end">
                  <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <button type="button" class="btn btn-light btn-sm" onclick="configurarFlujos()">
                      <i class="ti ti-git-branch me-1"></i>
                      Configurar Flujos
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="verHistorialGeneral()">
                      <i class="ti ti-history me-1"></i>
                      Historial Global
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Config Panel ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
          <!-- [ Estados Table ] start -->
          <div class="col-xl-8">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Estados del Pipeline de Leads
                  </h3>
                  <small class="text-muted">
                    Configura y gestiona los estados del proceso de captación. 
                    Define etapas, establece orden y configura estados finales.
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarEstadosPDF()">
                    <i class="fas fa-file-pdf me-1"></i>
                    Exportar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoEstado">
                    <i class="ti ti-plus me-1"></i>
                    Nuevo Estado
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de estados -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="estados-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">Orden</th>
                        <th width="20%">Estado</th>
                        <th width="12%">Badge</th>
                        <th width="15%">Estadísticas</th>
                        <th width="8%">Promedio Interés</th>
                        <th width="8%">Urgentes</th>
                        <th width="8%">Acciones Hoy</th>
                        <th width="10%">Último Lead</th>
                        <th width="14%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Calcular promedio de interés con estrellas
                              $promedio_interes = round($row['promedio_interes'] ?? 0, 1);
                              $estrellas_promedio = '';
                              for($i = 1; $i <= 5; $i++) {
                                  $clase = $i <= $promedio_interes ? 'estrella-promedio ti ti-star-filled' : 'estrella-vacia ti ti-star';
                                  $estrellas_promedio .= "<i class='$clase'></i>";
                              }
                              
                              // Formatear fecha del último lead
                              $ultimo_lead = $row['ultimo_lead_fecha'] ? 
                                'Hace ' . ceil((strtotime('now') - strtotime($row['ultimo_lead_fecha'])) / 86400) . ' días' : 'Sin leads';
                              
                              echo "<tr>";
                              echo "<td>
                                      <div class='interes-promedio'>" . $row['orden_display'] . "</div>
                                    </td>";
                              echo "<td>
                                      <div class='estado-info'>
                                        <span class='estado-nombre'>" . htmlspecialchars($row['nombre']) . "</span>
                                        <span class='estado-descripcion'>" . htmlspecialchars($row['descripcion'] ?? 'Sin descripción') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <span class='badge badge-estado-config" . ($row['es_final'] ? ' badge-final' : '') . "' 
                                            style='background-color: " . ($row['color'] ?? '#007bff') . ";'>
                                        " . htmlspecialchars($row['nombre']) . "
                                      </span>
                                    </td>";
                              echo "<td>
                                      <div class='estadisticas-estado'>
                                        <div class='stat-item-small'>
                                          <span class='stat-label-small'>Total:</span>
                                          <span class='stat-number-small'>" . $row['total_leads_estado'] . "</span>
                                        </div>
                                        <div class='stat-item-small'>
                                          <span class='stat-label-small'>Este mes:</span>
                                          <span class='stat-number-small'>" . $row['leads_mes_actual'] . "</span>
                                        </div>
                                        <div class='stat-item-small'>
                                          <span class='stat-label-small'>Asignados:</span>
                                          <span class='stat-number-small'>" . $row['leads_asignados'] . "</span>
                                        </div>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='interes-promedio'>
                                        $estrellas_promedio
                                        <span style='margin-left: 5px; font-weight: bold;'>$promedio_interes</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      " . ($row['leads_urgentes'] > 0 ? 
                                        "<span class='urgente-indicator pulsing'>" . $row['leads_urgentes'] . "</span>" : 
                                        "<span class='text-muted'>0</span>") . "
                                    </td>";
                              echo "<td>
                                      " . ($row['acciones_hoy'] > 0 ? 
                                        "<span class='acciones-hoy'>" . $row['acciones_hoy'] . "</span>" : 
                                        "<span class='text-muted'>0</span>") . "
                                    </td>";
                              echo "<td>
                                      <span class='ultimo-lead'>" . $ultimo_lead . "</span>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-estados'>
                                        <button type='button' class='btn btn-outline-info btn-historial' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Ver Historial'>
                                          <i class='ti ti-history'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-editar-estado' 
                                                data-id='" . $row['id'] . "'
                                                title='Editar Estado'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-configurar-flujo' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Configurar Flujo'>
                                          <i class='ti ti-git-branch'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-asignar-interes' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Asignar Nivel de Interés'>
                                          <i class='ti ti-star'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>No hay estados configurados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Badge</th>
                        <th>Estadísticas</th>
                        <th>Promedio Interés</th>
                        <th>Urgentes</th>
                        <th>Acciones Hoy</th>
                        <th>Último Lead</th>
                        <th>Acciones</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- [ Estados Table ] end -->
          
          <!-- [ Flujos Panel ] start -->
          <div class="col-xl-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Flujos de Estados Más Comunes</h5>
                <small class="text-muted">Últimos 90 días</small>
              </div>
              <div class="card-body">
                <div class="workflow-visualization">
                  <?php
                  if ($flujo_result->num_rows > 0) {
                      while($flujo = $flujo_result->fetch_assoc()) {
                          echo "<div class='flujo-item'>
                                  <div class='flujo-estados'>
                                    <span class='badge' style='background-color: " . ($flujo['color_anterior'] ?? '#6c757d') . "; color: white;'>" . 
                                    htmlspecialchars($flujo['estado_anterior'] ?? 'Nuevo') . "</span>
                                    <i class='ti ti-arrow-right flujo-arrow'></i>
                                    <span class='badge' style='background-color: " . ($flujo['color_nuevo'] ?? '#6c757d') . "; color: white;'>" . 
                                    htmlspecialchars($flujo['estado_nuevo']) . "</span>
                                  </div>
                                  <span class='flujo-count'>" . $flujo['total_cambios'] . "</span>
                                </div>";
                      }
                  } else {
                      echo "<p class='text-muted text-center'>No hay datos de flujo disponibles</p>";
                  }
                  ?>
                </div>
                <div class="mt-3 text-center">
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="analizarFlujos()">
                    <i class="ti ti-chart-line me-1"></i>
                    Análisis Completo de Flujos
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- [ Flujos Panel ] end -->
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </section>

    <!-- Incluir Modales -->
    <?php include 'modals/clasificacion_leads/modal_nuevo.php'; ?>
    <?php include 'modals/clasificacion_leads/modal_editar.php'; ?>
    <?php include 'modals/clasificacion_leads/modal_historial.php'; ?>
    <?php include 'modals/clasificacion_leads/modal_configurar_flujo.php'; ?>
    <?php include 'modals/clasificacion_leads/modal_asignar_interes.php'; ?>
    <?php include 'modals/clasificacion_leads/modal_historial_general.php'; ?>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Required Js -->
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/fonts/custom-font.js"></script>
    <script src="assets/js/pcoded.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            var table = $("#estados-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay estados disponibles en la tabla",
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
              "order": [[ 0, "asc" ]], // Ordenar por orden de display
              "columnDefs": [
                { "orderable": false, "targets": 8 } // Deshabilitar ordenación en columna de acciones
              ],
              "initComplete": function () {
                this.api().columns().every(function (index) {
                  var column = this;
                  
                  if (index < 8) { // Solo filtros para las primeras 8 columnas
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

            // Función para exportar estados a PDF
            window.exportarEstadosPDF = function() {
              var tabla = $('#estados-table').DataTable();
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
              form.action = 'reports/generar_pdf_estados.php';
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

            // Función para configurar flujos generales
            window.configurarFlujos = function() {
              $('#modalConfigurarFlujo').modal('show');
            };

            // Función para ver historial general
            window.verHistorialGeneral = function() {
              $('#modalHistorialGeneral').modal('show');
            };

            // Función para analizar flujos
            window.analizarFlujos = function() {
              window.location.href = 'analisis_flujos.php';
            };

            // Manejar click en botón ver historial
            $(document).on('click', '.btn-historial', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#historial_estado_id').val(id);
                $('#historial_estado_nombre').text(nombre);
                
                // Cargar historial específico del estado
                cargarHistorialEstado(id);
                
                $('#modalHistorial').modal('show');
            });

            // Manejar click en botón editar estado
            $(document).on('click', '.btn-editar-estado', function() {
                var id = $(this).data('id');
                cargarDatosEstado(id, 'editar');
            });

            // Manejar click en botón configurar flujo
            $(document).on('click', '.btn-configurar-flujo', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#flujo_estado_id').val(id);
                $('#flujo_estado_nombre').text(nombre);
                
                cargarConfiguracionFlujo(id);
                
                $('#modalConfigurarFlujo').modal('show');
            });

            // Manejar click en botón asignar nivel de interés
            $(document).on('click', '.btn-asignar-interes', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#interes_estado_id').val(id);
                $('#interes_estado_nombre').text(nombre);
                
                $('#modalAsignarInteres').modal('show');
            });

            // Función para cargar datos del estado
            function cargarDatosEstado(id, accion) {
              $.ajax({
                url: 'acciones/clasificacion_leads/obtener_estado.php',
                  method: 'POST',
                  data: { id: id, accion: accion },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    if (accion === 'editar') {
                      // Llenar modal de edición con los datos
                      $('#edit_estado_id').val(response.data.id);
                      $('#edit_nombre').val(response.data.nombre);
                      $('#edit_descripcion').val(response.data.descripcion);
                      $('#edit_color').val(response.data.color);
                      $('#edit_orden_display').val(response.data.orden_display);
                      $('#edit_es_final').prop('checked', response.data.es_final == 1);
                      
                      $('#modalEditar').modal('show');
                    }
                  } else {
                    alert('Error al cargar los datos: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener los datos del estado.');
                }
              });
            }

            // Función para cargar historial de un estado específico
            function cargarHistorialEstado(estadoId) {
              $.ajax({
                url: 'acciones/clasificacion_leads/obtener_historial_estado.php',
                method: 'POST',
                data: { estado_id: estadoId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    var historialHtml = '';
                    response.data.forEach(function(cambio) {
                      historialHtml += '<div class="historial-item">';
                      historialHtml += '<div class="cambio-fecha">' + cambio.fecha + '</div>';
                      historialHtml += '<div class="cambio-detalle">' + cambio.detalle + '</div>';
                      historialHtml += '<div class="cambio-usuario">Por: ' + cambio.usuario + '</div>';
                      historialHtml += '</div>';
                    });
                    $('#historial-contenido').html(historialHtml);
                  }
                },
                error: function() {
                  $('#historial-contenido').html('<p class="text-danger">Error al cargar el historial.</p>');
                }
              });
            }

            // Función para cargar configuración de flujo
            function cargarConfiguracionFlujo(estadoId) {
              $.ajax({
                url: 'acciones/clasificacion_leads/obtener_flujo_estado.php',
                method: 'POST',
                data: { estado_id: estadoId },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    // Cargar configuración de flujo específica
                    $('#flujo-configuracion').html(response.html);
                  }
                },
                error: function() {
                  $('#flujo-configuracion').html('<p class="text-danger">Error al cargar la configuración de flujo.</p>');
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