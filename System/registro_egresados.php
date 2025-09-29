<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/registro_egresados/registro_egresado.php');
        exit();
    }

    // Consulta principal para obtener egresados con información relacionada
    $sql = "SELECT
        ex.id,
        ex.codigo_exalumno,
        ex.tipo_documento,
        ex.numero_documento,
        ex.nombres,
        ex.apellidos,
        ex.email,
        ex.telefono,
        ex.whatsapp,
        ex.promocion_egreso,
        ex.fecha_egreso,
        ex.ultimo_grado,
        ex.ocupacion_actual,
        ex.empresa_actual,
        ex.estudios_superiores,
        ex.direccion_actual,
        ex.distrito_actual,
        ex.estado_contacto,
        ex.acepta_comunicaciones,
        ex.observaciones,
        ex.created_at,
        ex.updated_at,
        -- Clasificar por años desde egreso
        CASE
            WHEN ex.fecha_egreso IS NULL THEN 'Sin fecha'
            WHEN YEAR(CURDATE()) - YEAR(ex.fecha_egreso) <= 2 THEN 'Reciente'
            WHEN YEAR(CURDATE()) - YEAR(ex.fecha_egreso) <= 5 THEN 'Intermedio'
            ELSE 'Antiguo'
        END as categoria_egreso,
        -- Calcular años desde egreso
        CASE
            WHEN ex.fecha_egreso IS NULL THEN NULL
            ELSE YEAR(CURDATE()) - YEAR(ex.fecha_egreso)
        END as anos_egreso
    FROM exalumnos ex
    ORDER BY
        CASE ex.estado_contacto
            WHEN 'activo' THEN 1
            WHEN 'sin_contacto' THEN 2
            WHEN 'no_contactar' THEN 3
            ELSE 4
        END,
        ex.fecha_egreso DESC, ex.apellidos ASC";

    $result = $conn->query($sql);

    // Obtener estadísticas generales
    $stats_sql = "SELECT
        COUNT(*) as total_egresados,
        COUNT(CASE WHEN estado_contacto = 'activo' THEN 1 END) as activos,
        COUNT(CASE WHEN estado_contacto = 'sin_contacto' THEN 1 END) as sin_contacto,
        COUNT(CASE WHEN estado_contacto = 'no_contactar' THEN 1 END) as no_contactar,
        COUNT(CASE WHEN acepta_comunicaciones = 1 THEN 1 END) as acepta_comunicaciones,
        COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as con_email,
        COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as con_telefono,
        COUNT(CASE WHEN ocupacion_actual IS NOT NULL AND ocupacion_actual != '' THEN 1 END) as con_ocupacion
    FROM exalumnos";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener estadísticas por promoción
    $promociones_sql = "SELECT
        promocion_egreso,
        COUNT(*) as cantidad,
        COUNT(CASE WHEN estado_contacto = 'activo' THEN 1 END) as activos_promocion
    FROM exalumnos
    WHERE promocion_egreso IS NOT NULL
    GROUP BY promocion_egreso
    ORDER BY promocion_egreso DESC
    LIMIT 10";

    $promociones_result = $conn->query($promociones_sql);
    $promociones_stats = [];
    while($promocion = $promociones_result->fetch_assoc()) {
        $promociones_stats[] = $promocion;
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
    <title>Registro de Egresados - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Registro de Egresados"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Egresados, Exalumnos, Seguimiento"
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

    <!-- Custom styles for registro egresados -->
    <style>
      .badge-estado-contacto {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .estado-activo { background-color: #28a745; }
      .estado-sin_contacto { background-color: #ffc107; color: #856404; }
      .estado-no_contactar { background-color: #dc3545; }

      .badge-categoria {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .categoria-reciente { background-color: #d4edda; color: #155724; }
      .categoria-intermedio { background-color: #d1ecf1; color: #0c5460; }
      .categoria-antiguo { background-color: #fff3cd; color: #856404; }
      .categoria-sin-fecha { background-color: #f8d7da; color: #721c24; }

      .egresado-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .egresado-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }

      .egresado-codigo {
        font-size: 0.75rem;
        color: #6c757d;
        font-family: 'Courier New', monospace;
      }

      .egresado-documento {
        font-size: 0.7rem;
        color: #495057;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
      }

      .contacto-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .contacto-email {
        color: #495057;
        font-weight: 500;
      }

      .contacto-telefono {
        color: #6c757d;
      }

      .situacion-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .situacion-ocupacion {
        font-weight: 500;
        color: #495057;
      }

      .situacion-empresa {
        color: #6c757d;
      }

      .situacion-estudios {
        color: #28a745;
        font-size: 0.7rem;
      }

      .promocion-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .promocion-ano {
        font-size: 1rem;
        font-weight: bold;
        color: #495057;
      }

      .promocion-grado {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .años-egreso {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e8f4fd;
        color: #0c5460;
      }

      .interacciones-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        font-size: 0.75rem;
      }

      .total-interacciones {
        font-weight: bold;
        color: #495057;
      }

      .ultima-interaccion {
        color: #6c757d;
        font-size: 0.7rem;
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

      .promociones-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .promocion-item {
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

      .btn-grupo-egresado {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }

      .btn-grupo-egresado .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .alert-mensaje {
        margin-bottom: 20px;
      }

      .comunicaciones-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .acepta-si { background-color: #d4edda; color: #155724; }
      .acepta-no { background-color: #f8d7da; color: #721c24; }
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
                    <a href="javascript: void(0)">Gestión Académica</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Registro de Egresados
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
                    Registro de Egresados
                  </h3>
                  <small class="text-muted">
                    Gestiona el registro y seguimiento de exalumnos. Mantén contacto con egresados
                    y actualiza su información personal, académica y laboral.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" onclick="generarReporteEgresados()">
                    <i class="ti ti-chart-bar me-1"></i>
                    Generar Reporte
                  </button> -->
                  <!-- <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarEgresado">
                    <i class="ti ti-edit me-1"></i>
                    Editar Información
                  </button> -->
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarEgresado">
                    <i class="ti ti-user-plus me-1"></i>
                    Registrar Egresado
                  </button>
                </div>
              </div>

              <div class="card-body">
                <!-- Tabla de egresados -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="egresados-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="18%">Egresado</th>
                        <th width="15%">Contacto</th>
                        <th width="12%">Promoción</th>
                        <th width="8%">Estado</th>
                        <th width="18%">Situación Actual</th>
                        <th width="8%">Comunicaciones</th>
                        <th width="8%">Ubicación</th>
                        <th width="8%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado_contacto'];

                              // Determinar clase de categoría de egreso
                              $categoria_class = 'categoria-' . strtolower(str_replace(' ', '-', $row['categoria_egreso']));

                              // Formatear fechas
                              $fecha_egreso = $row['fecha_egreso'] ? date('d/m/Y', strtotime($row['fecha_egreso'])) : 'No especificada';

                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='badge badge-categoria $categoria_class'>" . $row['categoria_egreso'] . "</span>
                                    </td>";
                              echo "<td>
                                      <div class='egresado-info'>
                                        <span class='egresado-nombre'>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</span>
                                        <span class='egresado-codigo'>" . htmlspecialchars($row['codigo_exalumno']) . "</span>
                                        <span class='egresado-documento'>" .
                                        htmlspecialchars($row['tipo_documento'] . ': ' . $row['numero_documento']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='contacto-email'>" .
                                        ($row['email'] ? htmlspecialchars($row['email']) : 'Sin email') . "</span>
                                        <span class='contacto-telefono'>" .
                                        ($row['telefono'] ? htmlspecialchars($row['telefono']) : 'Sin teléfono') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='promocion-info'>
                                        <span class='promocion-ano'>" .
                                        ($row['promocion_egreso'] ? htmlspecialchars($row['promocion_egreso']) : 'S/D') . "</span>
                                        <span class='promocion-grado'>" . htmlspecialchars($row['ultimo_grado'] ?? 'No especificado') . "</span>
                                        " . ($row['anos_egreso'] ? "<span class='años-egreso'>Hace " . $row['anos_egreso'] . " años</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-contacto $estado_class'>" .
                                   ucfirst(str_replace('_', ' ', $row['estado_contacto'])) . "</span></td>";
                              echo "<td>
                                      <div class='situacion-info'>
                                        <span class='situacion-ocupacion'>" .
                                        ($row['ocupacion_actual'] ? htmlspecialchars($row['ocupacion_actual']) : 'No especificada') . "</span>
                                        <span class='situacion-empresa'>" .
                                        ($row['empresa_actual'] ? htmlspecialchars($row['empresa_actual']) : 'Sin empresa') . "</span>
                                        <span class='situacion-estudios'>" .
                                        ($row['estudios_superiores'] ? htmlspecialchars($row['estudios_superiores']) : 'Sin estudios superiores') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='comunicaciones-badge " .
                                   ($row['acepta_comunicaciones'] ? 'acepta-si' : 'acepta-no') . "'>" .
                                   ($row['acepta_comunicaciones'] ? 'Acepta' : 'No acepta') . "</span></td>";
                              echo "<td>" .
                                   ($row['direccion_actual'] ? htmlspecialchars($row['distrito_actual'] ?? 'No especificado') : 'Sin dirección') . "</td>";
                              echo "<td>
                                      <div class='btn-grupo-egresado'>
                                        <button type='button' class='btn btn-outline-success btn-editar-egresado'
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                title='Editar Información'>
                                          <i class='ti ti-edit'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-info btn-ver-historial'
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                title='Ver Historial'>
                                          <i class='ti ti-history'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>No hay egresados registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Egresado</th>
                        <th>Contacto</th>
                        <th>Promoción</th>
                        <th>Estado</th>
                        <th>Situación Actual</th>
                        <th>Comunicaciones</th>
                        <th>Ubicación</th>
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
    <?php include 'modals/registro_egresados/modal_registrar_egresado.php'; ?>
    <?php include 'modals/registro_egresados/modal_editar_egresado.php'; ?>
    <?php include 'modals/registro_egresados/modal_gestionar_estado.php'; ?>

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
            var table = $("#egresados-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay egresados disponibles en la tabla",
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
              "order": [[ 0, "desc" ]], // Ordenar por ID descendente
              "columnDefs": [
                { "orderable": false, "targets": 8 } // Deshabilitar ordenación en columna de acciones
              ],
              "initComplete": function () {
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;

                  // Solo aplicar filtros a las primeras 8 columnas (sin acciones)
                  if (index < 8) {
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

            // Función para generar reporte de egresados
            window.generarReporteEgresados = function() {
              Swal.fire({
                title: '¿Generar Reporte de Egresados?',
                text: 'Se generará un reporte completo con la información actual',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, generar',
                cancelButtonText: 'Cancelar'
              }).then((result) => {
                if (result.isConfirmed) {
                  // Aquí iría la lógica para generar el reporte
                  Swal.fire(
                    'Reporte Generado',
                    'El reporte de egresados ha sido generado exitosamente',
                    'success'
                  );
                }
              });
            }

            // Manejar click en botón gestionar estado
            $(document).on('click', '.btn-gestionar-estado', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var estadoActual = $(this).data('estado');

                Swal.fire({
                  title: 'Gestionar Estado de Contacto',
                  text: 'Estado actual de ' + nombre + ': ' + estadoActual.replace('_', ' '),
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonColor: '#ffc107',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Gestionar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Cargar datos en el modal de gestión de estado
                    cargarEstadoEgresado(id, nombre, estadoActual);
                    $('#modalGestionarEstado').modal('show');
                  }
                });
            });

           // Manejar click en botón ver historial
            $(document).on('click', '.btn-ver-historial', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');

                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Cargando historial',
                    text: 'Obteniendo datos de ' + nombre,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Realizar petición AJAX para obtener el historial
                $.ajax({
                    url: 'acciones/registro_egresados/obtener_historial_egresado.php',
                    type: 'POST',
                    dataType: 'json', // Especificar que esperamos JSON
                    data: {
                        id_egresado: id,
                        accion: 'obtener_historial'
                    },
                    success: function(data) {
                        // Ya no necesitamos JSON.parse porque dataType: 'json' lo hace automáticamente
                        if (data.success) {
                            // Construir HTML para el historial
                            let historialHtml = `
                                <div class="historial-container">
                                    <div class="historial-header mb-3">
                                        <h5>Historial de Interacciones</h5>
                                        <span class="text-muted">Egresado: ${nombre}</span>
                                    </div>
                                    <div class="historial-content">
                            `;

                            if (data.historial && data.historial.length > 0) {
                                data.historial.forEach(item => {
                                    historialHtml += `
                                        <div class="historial-item mb-3 p-2 border-bottom">
                                            <div class="d-flex justify-content-between">
                                                <strong>${item.tipo_interaccion}</strong>
                                                <small class="text-muted">${item.fecha}</small>
                                            </div>
                                            <p class="mb-1">${item.descripcion}</p>
                                            <small class="text-info">Por: ${item.usuario}</small>
                                        </div>
                                    `;
                                });
                            } else {
                                historialHtml += `
                                    <div class="alert alert-info">
                                        No hay registros de interacciones para este egresado.
                                    </div>
                                `;
                            }

                            historialHtml += `
                                    </div>
                                </div>
                            `;

                            // Mostrar modal con el historial
                            Swal.fire({
                                title: 'Historial de Interacciones',
                                html: historialHtml,
                                width: '600px',
                                showCloseButton: true,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'historial-modal'
                                }
                            });

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo cargar el historial'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo obtener el historial. Código: ' + xhr.status
                        });
                    }
                });
            });

            // Función para cargar estado del egresado
            function cargarEstadoEgresado(id, nombre, estado) {
              // Aquí iría la lógica para el modal de gestión de estado
              console.log('Gestionando estado del egresado:', id, nombre, estado);
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