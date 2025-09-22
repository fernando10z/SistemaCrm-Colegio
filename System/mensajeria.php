<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener las plantillas de mensajes con estadísticas de uso
$sql = "SELECT 
    pm.id,
    pm.nombre,
    pm.tipo,
    pm.asunto,
    LEFT(pm.contenido, 150) as contenido_preview,
    pm.variables_disponibles,
    pm.categoria,
    pm.activo,
    pm.created_at,
    pm.updated_at,
    COUNT(me.id) as total_usos,
    COUNT(CASE WHEN me.estado = 'enviado' OR me.estado = 'entregado' THEN 1 END) as usos_exitosos,
    MAX(me.fecha_envio) as ultimo_uso,
    COUNT(CASE WHEN DATE(me.created_at) >= CURDATE() - INTERVAL 30 DAY THEN 1 END) as usos_ultimo_mes,
    -- Simulación de programaciones (basado en uso regular)
    CASE 
        WHEN COUNT(me.id) > 10 AND pm.categoria IN ('recordatorio', 'evento', 'cumpleanos') THEN 'activa'
        WHEN pm.categoria IN ('recordatorio', 'evento', 'cumpleanos') THEN 'programada'
        ELSE 'manual'
    END as tipo_programacion,
    CASE 
        WHEN pm.categoria = 'cumpleanos' THEN 'Cumpleaños (Diario)'
        WHEN pm.categoria = 'recordatorio' THEN 'Recordatorios (Semanal)'
        WHEN pm.categoria = 'evento' THEN 'Eventos (Según programación)'
        WHEN COUNT(me.id) > 5 THEN 'Mensual'
        ELSE 'Manual'
    END as frecuencia_estimada
FROM plantillas_mensajes pm
LEFT JOIN mensajes_enviados me ON pm.id = me.plantilla_id
GROUP BY pm.id, pm.nombre, pm.tipo, pm.asunto, pm.contenido, pm.variables_disponibles, pm.categoria, pm.activo, pm.created_at, pm.updated_at
ORDER BY pm.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de plantillas para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_plantillas,
    COUNT(CASE WHEN activo = 1 THEN 1 END) as plantillas_activas,
    COUNT(CASE WHEN categoria = 'cumpleanos' THEN 1 END) as plantillas_cumpleanos,
    COUNT(CASE WHEN categoria = 'evento' THEN 1 END) as plantillas_eventos,
    COUNT(CASE WHEN categoria = 'recordatorio' THEN 1 END) as plantillas_recordatorios,
    COUNT(CASE WHEN tipo = 'email' THEN 1 END) as plantillas_email,
    COUNT(CASE WHEN tipo = 'whatsapp' THEN 1 END) as plantillas_whatsapp,
    COUNT(CASE WHEN tipo = 'sms' THEN 1 END) as plantillas_sms
FROM plantillas_mensajes";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener próximos eventos para programaciones automáticas
$eventos_sql = "SELECT 
    id, 
    titulo, 
    fecha_inicio,
    tipo,
    dirigido_a
FROM eventos 
WHERE fecha_inicio >= CURDATE() 
AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
AND estado = 'programado'
ORDER BY fecha_inicio ASC
LIMIT 5";

$eventos_result = $conn->query($eventos_sql);

// Obtener cumpleaños próximos para recordatorios
$cumpleanos_sql = "SELECT 
    COUNT(*) as cumpleanos_mes_actual,
    COUNT(CASE WHEN DAY(fecha_nacimiento) = DAY(CURDATE()) THEN 1 END) as cumpleanos_hoy
FROM (
    SELECT fecha_nacimiento FROM estudiantes WHERE fecha_nacimiento IS NOT NULL
    UNION ALL
    SELECT fecha_nacimiento FROM apoderados WHERE fecha_nacimiento IS NOT NULL
) as todas_fechas
WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE())";

$cumpleanos_result = $conn->query($cumpleanos_sql);
$cumpleanos_stats = $cumpleanos_result->fetch_assoc();

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
    <title>Mensajes Recurrentes - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Mensajes Recurrentes"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Mensajes, Plantillas, Automatización, Recurrentes"
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
    
    <!-- Custom styles for mensajes recurrentes -->
    <style>
      .badge-tipo {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-email { background-color: #dc3545; }
      .tipo-whatsapp { background-color: #25d366; }
      .tipo-sms { background-color: #007bff; }
      
      .badge-categoria {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .categoria-cumpleanos { background-color: #e83e8c; }
      .categoria-evento { background-color: #6f42c1; }
      .categoria-recordatorio { background-color: #fd7e14; }
      .categoria-general { background-color: #6c757d; }
      .categoria-bienvenida { background-color: #20c997; }
      
      .badge-programacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .programacion-activa { 
        background-color: #28a745; 
        color: white;
        animation: pulse-active 2s infinite;
      }
      .programacion-programada { 
        background-color: #ffc107; 
        color: #856404;
      }
      .programacion-manual { 
        background-color: #6c757d; 
        color: white;
      }
      
      @keyframes pulse-active {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
      }
      
      .plantilla-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .plantilla-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .plantilla-asunto {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .plantilla-preview {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        background-color: #f8f9fa;
        padding: 2px 4px;
        border-radius: 3px;
      }
      
      .variables-info {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        border-radius: 8px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .uso-stats {
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
      
      .frecuencia-info {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        background-color: #e3f2fd;
        color: #1565c0;
        font-weight: 500;
        border: 1px solid #bbdefb;
      }
      
      .estado-activo {
        color: #28a745;
        font-weight: bold;
      }
      
      .estado-inactivo {
        color: #dc3545;
        font-weight: bold;
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
        font-size: 1.4rem;
        font-weight: bold;
        display: block;
      }
      
      .stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
      }
      
      .evento-proximo {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 8px;
        margin-bottom: 5px;
        font-size: 0.8rem;
      }
      
      .evento-titulo {
        font-weight: bold;
        color: #856404;
      }
      
      .evento-fecha {
        color: #6c757d;
      }
      
      .btn-grupo-plantilla {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-plantilla .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .alertas-panel {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
      }

      .alerta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f1f1;
      }

      .alerta-item:last-child {
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
                    <a href="javascript: void(0)">Comunicación</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Mensajes Recurrentes
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Alertas y Próximos Eventos ] start -->
        <div class="row mb-3">
          <div class="col-md-8">
            <div class="alertas-panel">
              <h6 class="mb-3"><i class="ti ti-calendar-event me-2"></i>Próximos Eventos Programados</h6>
              <?php 
              if ($eventos_result->num_rows > 0) {
                while($evento = $eventos_result->fetch_assoc()) {
                  echo "<div class='evento-proximo'>
                          <div class='evento-titulo'>" . htmlspecialchars($evento['titulo']) . "</div>
                          <div class='evento-fecha'>" . date('d/m/Y H:i', strtotime($evento['fecha_inicio'])) . " - " . ucfirst($evento['tipo']) . " (" . ucfirst($evento['dirigido_a']) . ")</div>
                        </div>";
                }
              } else {
                echo "<div class='text-muted text-center'>No hay eventos próximos programados</div>";
              }
              ?>
            </div>
          </div>
          <div class="col-md-4">
            <div class="alertas-panel">
              <h6 class="mb-3"><i class="ti ti-gift me-2"></i>Cumpleaños del Mes</h6>
              <div class="alerta-item">
                <i class="ti ti-calendar text-primary"></i>
                <div>
                  <div class="stat-principal"><?php echo $cumpleanos_stats['cumpleanos_mes_actual'] ?? 0; ?> cumpleaños</div>
                  <div class="stat-secundario">Este mes</div>
                </div>
              </div>
              <div class="alerta-item">
                <i class="ti ti-cake text-warning"></i>
                <div>
                  <div class="stat-principal"><?php echo $cumpleanos_stats['cumpleanos_hoy'] ?? 0; ?> cumpleaños</div>
                  <div class="stat-secundario">Hoy</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Alertas y Próximos Eventos ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Gestión de Mensajes Recurrentes
                  </h3>
                  <small class="text-muted">
                    Administra plantillas de mensajes y programa envíos automáticos. 
                    Configura recordatorios, notificaciones de eventos y disparadores automáticos.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisparadores">
                    <i class="ti ti-bolt me-1"></i>
                    Disparadores
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramar">
                    <i class="ti ti-clock me-1"></i>
                    Programar Recurrente
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" onclick="gestionarProgramaciones()">
                    <i class="ti ti-settings me-1"></i>
                    Gestionar Programaciones
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearPlantilla">
                    <i class="ti ti-template me-1"></i>
                    Crear Plantilla
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de plantillas -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="plantillas-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="18%">Plantilla</th>
                        <th width="10%">Categoría</th>
                        <th width="8%">Variables</th>
                        <th width="10%">Estadísticas Uso</th>
                        <th width="8%">Programación</th>
                        <th width="8%">Frecuencia</th>
                        <th width="6%">Estado</th>
                        <th width="8%">Último Uso</th>
                        <th width="12%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_creacion = date('d/m/Y', strtotime($row['created_at']));
                              $ultimo_uso = $row['ultimo_uso'] ? date('d/m/Y', strtotime($row['ultimo_uso'])) : 'Nunca';
                              
                              // Determinar clase CSS para el tipo
                              $tipo_class = 'tipo-' . $row['tipo'];
                              
                              // Determinar clase CSS para la categoría
                              $categoria = $row['categoria'] ?? 'general';
                              $categoria_class = 'categoria-' . $categoria;
                              
                              // Determinar clase de programación
                              $programacion = $row['tipo_programacion'] ?? 'manual';
                              $programacion_class = 'programacion-' . $programacion;
                              
                              // Procesar variables disponibles
                              $variables = json_decode($row['variables_disponibles'] ?? '[]', true);
                              $variables_text = is_array($variables) && !empty($variables) ? 
                                implode(', ', array_slice($variables, 0, 3)) . (count($variables) > 3 ? '...' : '') : 
                                'Ninguna';
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td><span class='badge badge-tipo $tipo_class'>" . 
                                   strtoupper($row['tipo']) . "</span></td>";
                              echo "<td>
                                      <div class='plantilla-info'>
                                        <span class='plantilla-nombre'>" . htmlspecialchars($row['nombre']) . "</span>
                                        <span class='plantilla-asunto'>" . htmlspecialchars($row['asunto'] ?? 'Sin asunto') . "</span>
                                        <span class='plantilla-preview'>" . htmlspecialchars($row['contenido_preview']) . "...</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-categoria $categoria_class'>" . 
                                   ucfirst($categoria) . "</span></td>";
                              echo "<td><span class='variables-info' title='" . htmlspecialchars($variables_text) . "'>" . 
                                   htmlspecialchars($variables_text) . "</span></td>";
                              echo "<td>
                                      <div class='uso-stats'>
                                        <span class='stat-principal'>" . number_format($row['total_usos']) . " total</span>
                                        <span class='stat-secundario'>" . number_format($row['usos_exitosos']) . " exitosos</span>
                                        <span class='stat-secundario'>" . number_format($row['usos_ultimo_mes']) . " este mes</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-programacion $programacion_class'>" . 
                                   ucfirst($programacion) . "</span></td>";
                              echo "<td><span class='frecuencia-info'>" . 
                                   htmlspecialchars($row['frecuencia_estimada']) . "</span></td>";
                              echo "<td><span class='" . ($row['activo'] ? 'estado-activo' : 'estado-inactivo') . "'>" . 
                                   ($row['activo'] ? 'Activa' : 'Inactiva') . "</span></td>";
                              echo "<td><span class='fecha-contacto'>" . $ultimo_uso . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-plantilla'>
                                        <button type='button' class='btn btn-outline-info btn-ver-plantilla' 
                                                data-id='" . $row['id'] . "'
                                                title='Ver Plantilla'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-editar-plantilla' 
                                                data-id='" . $row['id'] . "'
                                                title='Editar Plantilla'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-programar-plantilla' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Programar Recurrente'>
                                          <i class='ti ti-calendar-plus'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-" . ($row['activo'] ? 'warning' : 'success') . " btn-toggle-estado' 
                                                data-id='" . $row['id'] . "'
                                                data-estado='" . $row['activo'] . "'
                                                title='" . ($row['activo'] ? 'Desactivar' : 'Activar') . "'>
                                          <i class='fas fa-" . ($row['activo'] ? 'pause' : 'play') . "'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay plantillas registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Plantilla</th>
                        <th>Categoría</th>
                        <th>Variables</th>
                        <th>Estadísticas Uso</th>
                        <th>Programación</th>
                        <th>Frecuencia</th>
                        <th>Estado</th>
                        <th>Último Uso</th>
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
    <?php include 'modals/mensajeria/modal_crear_plantilla.php'; ?>
    <?php include 'modals/mensajeria/modal_programar_recurrente.php'; ?>
    <?php include 'modals/mensajeria/modal_configurar_disparadores.php'; ?>
    <?php include 'modals/mensajeria/modal_gestionar_programaciones.php'; ?>

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
            var table = $("#plantillas-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay plantillas disponibles en la tabla",
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

            // Función para gestionar programaciones
            window.gestionarProgramaciones = function() {
              $('#modalGestionarProgramaciones').modal('show');
            };

            // Manejar click en botón ver plantilla
            $(document).on('click', '.btn-ver-plantilla', function() {
                var id = $(this).data('id');
                cargarDetallesPlantilla(id, 'ver');
            });

            // Manejar click en botón editar plantilla
            $(document).on('click', '.btn-editar-plantilla', function() {
                var id = $(this).data('id');
                cargarDetallesPlantilla(id, 'editar');
            });

            // Manejar click en botón programar plantilla
            $(document).on('click', '.btn-programar-plantilla', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#programar_plantilla_id').val(id);
                $('#programar_plantilla_nombre').text(nombre);
                $('#modalProgramar').modal('show');
            });

            // Manejar click en botón toggle estado
            $(document).on('click', '.btn-toggle-estado', function() {
                var id = $(this).data('id');
                var estadoActual = $(this).data('estado');
                var nuevoEstado = estadoActual == 1 ? 0 : 1;
                var accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
                
                if (confirm('¿Está seguro de que desea ' + accion + ' esta plantilla?')) {
                    toggleEstadoPlantilla(id, nuevoEstado);
                }
            });

            // Función para cargar detalles de plantilla
            function cargarDetallesPlantilla(id, accion) {
              $.ajax({
                url: 'actions/obtener_plantilla_detalle.php',
                method: 'POST',
                data: { id: id, accion: accion },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    if (accion === 'ver') {
                      mostrarDetallesPlantilla(response.data);
                    } else if (accion === 'editar') {
                      cargarFormularioEdicion(response.data);
                    }
                  } else {
                    alert('Error al cargar los detalles: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener los detalles de la plantilla.');
                }
              });
            }

            // Función para toggle estado de plantilla
            function toggleEstadoPlantilla(id, nuevoEstado) {
              $.ajax({
                url: 'actions/toggle_estado_plantilla.php',
                method: 'POST',
                data: { id: id, estado: nuevoEstado },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    alert('Estado de la plantilla actualizado correctamente.');
                    location.reload();
                  } else {
                    alert('Error al actualizar el estado: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al actualizar el estado.');
                }
              });
            }

            // Función para mostrar detalles de plantilla
            function mostrarDetallesPlantilla(data) {
              // Implementar modal de vista de plantilla
              console.log('Detalles de plantilla:', data);
            }

            // Función para cargar formulario de edición
            function cargarFormularioEdicion(data) {
              // Implementar carga de datos en formulario de edición
              console.log('Editar plantilla:', data);
            }

            // Auto-refresh cada 60 segundos para estadísticas
            setInterval(function() {
              // Actualizar solo las estadísticas sin recargar la tabla completa
              actualizarEstadisticas();
            }, 60000);

            // Función para actualizar estadísticas
            function actualizarEstadisticas() {
              $.ajax({
                url: 'actions/obtener_estadisticas_plantillas.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    // Actualizar los números en las tarjetas de estadísticas
                    $('.stats-card .stat-number').each(function(index) {
                      var keys = ['total_plantillas', 'plantillas_activas', 'plantillas_cumpleanos', 'plantillas_eventos', 'cumpleanos_hoy', 'eventos_proximos'];
                      if (keys[index] && response.data[keys[index]] !== undefined) {
                        $(this).text(response.data[keys[index]]);
                      }
                    });
                  }
                },
                error: function() {
                  console.log('Error al actualizar estadísticas');
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