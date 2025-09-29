<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/codigos_referido/gestionar_codigo.php');
        exit();
    }

    // Consulta principal para obtener códigos de referido con información relacionada
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
            ELSE 'Código General'
        END as nombre_propietario,
        -- Información de la familia si existe
        CASE 
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE NULL
        END as apellido_familia,
        -- Email del apoderado
        a.email as email_propietario,
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
        END as estado_validez
    FROM codigos_referido cr
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    ORDER BY 
        CASE 
            WHEN cr.activo = 1 AND (cr.fecha_fin IS NULL OR cr.fecha_fin >= CURDATE()) THEN 1
            WHEN cr.activo = 0 THEN 2
            ELSE 3
        END,
        cr.created_at DESC";

    $result = $conn->query($sql);

    // Obtener estadísticas generales
    $stats_sql = "SELECT
        COUNT(*) as total_codigos,
        COUNT(CASE WHEN activo = 1 AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()) THEN 1 END) as codigos_activos,
        COUNT(CASE WHEN activo = 0 THEN 1 END) as codigos_inactivos,
        COUNT(CASE WHEN fecha_fin IS NOT NULL AND fecha_fin < CURDATE() THEN 1 END) as codigos_expirados,
        COUNT(CASE WHEN apoderado_id IS NOT NULL THEN 1 END) as codigos_personales,
        COUNT(CASE WHEN apoderado_id IS NULL THEN 1 END) as codigos_generales,
        SUM(usos_actuales) as total_usos,
        SUM(CASE WHEN limite_usos IS NOT NULL THEN limite_usos - usos_actuales ELSE 0 END) as usos_disponibles_total
    FROM codigos_referido";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener códigos más utilizados
    $top_codigos_sql = "SELECT
        codigo,
        usos_actuales,
        descripcion
    FROM codigos_referido
    WHERE activo = 1
    ORDER BY usos_actuales DESC
    LIMIT 5";

    $top_codigos_result = $conn->query($top_codigos_sql);
    $top_codigos = [];
    while($codigo = $top_codigos_result->fetch_assoc()) {
        $top_codigos[] = $codigo;
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
    <title>Códigos de Recomendación - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Sistema CRM para instituciones educativas - Códigos de Recomendación" />
    <meta name="keywords" content="CRM, Educación, Códigos, Referidos, Recomendaciones" />
    <meta name="author" content="CRM Escolar" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <!-- [Page specific CSS] start -->
    <!-- data tables css -->
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap5.min.css" />
    <!-- [Page specific CSS] end -->
    <!-- [Google Font] Family -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link" />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />

    <!-- Custom styles para códigos de referido -->
    <style>
      .badge-estado-validez {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .estado-activo { background-color: #28a745; }
      .estado-inactivo { background-color: #6c757d; }
      .estado-expirado { background-color: #dc3545; }
      .estado-agotado { background-color: #ffc107; color: #856404; }

      .codigo-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .codigo-text {
        font-weight: 700;
        color: #2c3e50;
        font-size: 1rem;
        font-family: 'Courier New', monospace;
        letter-spacing: 1px;
      }

      .codigo-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
      }

      .codigo-tipo {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 6px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
      }

      .propietario-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .propietario-nombre {
        font-weight: 500;
        color: #495057;
      }

      .propietario-email {
        color: #6c757d;
        font-size: 0.7rem;
      }

      .beneficio-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.7rem;
      }

      .beneficio-referente {
        color: #28a745;
        font-weight: 500;
      }

      .beneficio-referido {
        color: #17a2b8;
      }

      .uso-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .uso-numero {
        font-size: 1.2rem;
        font-weight: bold;
        color: #495057;
      }

      .uso-limite {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .uso-disponible {
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 500;
        background-color: #d4edda;
        color: #155724;
      }

      .fecha-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.7rem;
      }

      .fecha-inicio {
        color: #495057;
      }

      .fecha-fin {
        color: #dc3545;
        font-weight: 500;
      }

      .dias-restantes {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 500;
        background-color: #fff3cd;
        color: #856404;
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

      .top-codigos-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .codigo-item {
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

      .btn-grupo-codigo {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }

      .btn-grupo-codigo .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .alert-mensaje {
        margin-bottom: 20px;
      }

      .codigo-enlace {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 6px;
        background-color: #f8f9fa;
        color: #495057;
        font-family: 'Courier New', monospace;
        cursor: pointer;
      }

      .codigo-enlace:hover {
        background-color: #e8f4fd;
        color: #0c5460;
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
                  <li class="breadcrumb-item"><a href="javascript: void(0)">Marketing</a></li>
                  <li class="breadcrumb-item" aria-current="page">Códigos de Recomendación</li>
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
                  <h3 class="mb-1">Gestión de Códigos de Recomendación</h3>
                  <small class="text-muted">
                    Administra los códigos de referido para familias y campañas promocionales.
                    Incentiva las recomendaciones y genera nuevos contactos.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearCodigo">
                    <i class="ti ti-plus me-1"></i>
                    Crear Código
                  </button>
                </div>
              </div>

              <div class="card-body">
                <!-- Tabla de códigos de referido -->
                <div class="dt-responsive table-responsive">
                  <table id="codigos-table" class="table table-striped table-bordered nowrap">
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Código</th>
                        <th width="15%">Propietario</th>
                        <th width="20%">Beneficios</th>
                        <th width="10%">Usos</th>
                        <th width="12%">Vigencia</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado_validez'];

                              // Formatear fechas
                              $fecha_inicio = $row['fecha_inicio'] ? date('d/m/Y', strtotime($row['fecha_inicio'])) : 'No especificada';
                              $fecha_fin = $row['fecha_fin'] ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Sin límite';

                              // Generar enlace de referido
                              $enlace_referido = "https://colegio.edu.pe/registro?ref=" . urlencode($row['codigo']);

                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='codigo-info'>
                                        <span class='codigo-text'>" . htmlspecialchars($row['codigo']) . "</span>
                                        <span class='codigo-descripcion'>" . htmlspecialchars($row['descripcion'] ?? 'Sin descripción') . "</span>
                                        <span class='codigo-tipo'>" . ($row['apoderado_id'] ? 'Personal' : 'General') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='propietario-info'>
                                        <span class='propietario-nombre'>" . htmlspecialchars($row['nombre_propietario']) . "</span>
                                        " . ($row['email_propietario'] ? "<span class='propietario-email'>" . htmlspecialchars($row['email_propietario']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='beneficio-info'>
                                        <span class='beneficio-referente'>👤 " . htmlspecialchars($row['beneficio_referente'] ?? 'No especificado') . "</span>
                                        <span class='beneficio-referido'>🎁 " . htmlspecialchars($row['beneficio_referido'] ?? 'No especificado') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='uso-info'>
                                        <span class='uso-numero'>" . $row['usos_actuales'] . " / " . ($row['limite_usos'] ?? '∞') . "</span>
                                        <span class='uso-disponible'>" . 
                                        ($row['limite_usos'] ? ($row['limite_usos'] - $row['usos_actuales']) . ' disponibles' : 'Ilimitado') . 
                                        "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='fecha-info'>
                                        <span class='fecha-inicio'>📅 Inicio: " . $fecha_inicio . "</span>
                                        <span class='fecha-fin'>⏰ Fin: " . $fecha_fin . "</span>
                                        " . ($row['dias_restantes'] !== null ? 
                                        "<span class='dias-restantes'>" . $row['dias_restantes'] . " días restantes</span>" : 
                                        "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-validez $estado_class'>" . 
                                   ucfirst($row['estado_validez']) . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-codigo'>
                                        <button type='button' class='btn btn-outline-success btn-editar-codigo'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='Editar Código'>
                                          <i class='ti ti-edit'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-info btn-gestionar-estado'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                data-estado='" . $row['estado_validez'] . "'
                                                title='Gestionar Estado'>
                                          <i class='ti ti-settings'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>No hay códigos de referido registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Propietario</th>
                        <th>Beneficios</th>
                        <th>Usos</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
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
    <?php include 'modals/codigos_referido/modal_crear_codigo.php'; ?>
    <?php include 'modals/codigos_referido/modal_editar_codigo.php'; ?>
    <?php include 'modals/codigos_referido/modal_gestionar_estado.php'; ?>

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
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      $(document).ready(function() {
            // Inicializar DataTable con filtros integrados
            var table = $("#codigos-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay códigos disponibles en la tabla",
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
              "order": [[ 0, "desc" ]],
              "columnDefs": [
                { "orderable": false, "targets": [] }
              ],
              "initComplete": function () {
                this.api().columns().every(function (index) {
                  var column = this;

                  if (index < 6) {
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

            // Función para copiar enlace al portapapeles
            window.copiarEnlace = function(enlace) {
              navigator.clipboard.writeText(enlace).then(function() {
                Swal.fire({
                  icon: 'success',
                  title: '¡Enlace copiado!',
                  text: 'El enlace de referido ha sido copiado al portapapeles',
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 3000,
                  timerProgressBar: true
                });
              }).catch(function(err) {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'No se pudo copiar el enlace',
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 3000
                });
              });
            };

            // Manejar click en botón editar código
            $(document).on('click', '.btn-editar-codigo', function() {
                var id = $(this).data('id');
                var codigo = $(this).data('codigo');
                
                Swal.fire({
                  title: 'Editar Código',
                  text: '¿Desea editar el código ' + codigo + '?',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#28a745',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, editar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    cargarDatosCodigo(id);
                    $('#modalEditarCodigo').modal('show');
                  }
                });
            });

            // Manejar click en botón gestionar estado
            $(document).on('click', '.btn-gestionar-estado', function() {
                var id = $(this).data('id');
                var codigo = $(this).data('codigo');
                var estadoActual = $(this).data('estado');

                Swal.fire({
                  title: 'Gestionar Estado',
                  text: 'Código: ' + codigo + ' - Estado: ' + estadoActual,
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonColor: '#17a2b8',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Gestionar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    cargarEstadoCodigo(id, codigo, estadoActual);
                    $('#modalGestionarEstado').modal('show');
                  }
                });
            });

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