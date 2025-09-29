<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/leads_referidos/gestionar_referido.php');
        exit();
    }

    // Consulta principal para obtener leads referidos con información relacionada
    $sql = "SELECT
        ur.id,
        ur.codigo_referido_id,
        ur.lead_id,
        ur.fecha_uso,
        ur.convertido,
        ur.fecha_conversion,
        ur.observaciones,
        -- Datos del código de referido
        cr.codigo as codigo_referido,
        cr.descripcion as descripcion_codigo,
        cr.beneficio_referido,
        cr.limite_usos,
        cr.usos_actuales,
        cr.fecha_inicio,
        cr.fecha_fin,
        cr.activo as codigo_activo,
        -- Datos del apoderado/familia referente
        CASE
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as referente_nombre,
        -- Datos del lead referido
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', IFNULL(l.apellidos_contacto, '')) as nombre_contacto,
        l.telefono,
        l.email,
        l.fecha_conversion as lead_fecha_conversion,
        -- Estado del lead
        el.nombre as estado_lead,
        el.color as estado_color,
        -- Canal de captación
        cc.nombre as canal_captacion,
        cc.tipo as canal_tipo,
        -- Información adicional
        g.nombre as grado_interes,
        -- Clasificación por antigüedad
        CASE
            WHEN ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Reciente'
            WHEN ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 'Este mes'
            WHEN ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 'Últimos 3 meses'
            ELSE 'Antiguo'
        END as antiguedad_uso,
        -- Días desde uso
        DATEDIFF(CURDATE(), ur.fecha_uso) as dias_desde_uso
    FROM usos_referido ur
    INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
    INNER JOIN leads l ON ur.lead_id = l.id
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    ORDER BY ur.fecha_uso DESC, ur.convertido ASC";

    $result = $conn->query($sql);

    // Obtener estadísticas generales
    $stats_sql = "SELECT
        COUNT(*) as total_usos,
        COUNT(CASE WHEN ur.convertido = 1 THEN 1 END) as convertidos,
        COUNT(CASE WHEN ur.convertido = 0 THEN 1 END) as pendientes,
        COUNT(DISTINCT ur.codigo_referido_id) as codigos_usados,
        COUNT(DISTINCT ur.lead_id) as leads_unicos,
        COUNT(CASE WHEN ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as usos_mes_actual,
        ROUND(AVG(CASE WHEN ur.convertido = 1 AND ur.fecha_conversion IS NOT NULL 
            THEN DATEDIFF(ur.fecha_conversion, ur.fecha_uso) END), 1) as dias_promedio_conversion
    FROM usos_referido ur";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener códigos más usados
    $codigos_top_sql = "SELECT
        cr.codigo,
        cr.descripcion,
        COUNT(ur.id) as total_usos,
        COUNT(CASE WHEN ur.convertido = 1 THEN 1 END) as conversiones,
        ROUND((COUNT(CASE WHEN ur.convertido = 1 THEN 1 END) * 100.0 / COUNT(ur.id)), 1) as tasa_conversion
    FROM codigos_referido cr
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE cr.activo = 1
    GROUP BY cr.id
    ORDER BY total_usos DESC
    LIMIT 5";

    $codigos_top_result = $conn->query($codigos_top_sql);
    $codigos_top = [];
    while($codigo = $codigos_top_result->fetch_assoc()) {
        $codigos_top[] = $codigo;
    }

    // Calcular tasa de conversión general
    $tasa_conversion = $stats['total_usos'] > 0 
        ? round(($stats['convertidos'] / $stats['total_usos']) * 100, 1) 
        : 0;

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
    <title>Leads Referidos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Leads Referidos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Leads, Referidos, Códigos de Referencia"
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

    <!-- Custom styles for leads referidos -->
    <style>
      .badge-convertido {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .convertido-si { background-color: #28a745; }
      .convertido-no { background-color: #ffc107; color: #856404; }

      .badge-antiguedad {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .antiguedad-reciente { background-color: #d4edda; color: #155724; }
      .antiguedad-este-mes { background-color: #d1ecf1; color: #0c5460; }
      .antiguedad-ultimos-3-meses { background-color: #fff3cd; color: #856404; }
      .antiguedad-antiguo { background-color: #f8d7da; color: #721c24; }

      .referido-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .referido-estudiante {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }

      .referido-codigo-lead {
        font-size: 0.75rem;
        color: #6c757d;
        font-family: 'Courier New', monospace;
      }

      .referido-contacto {
        font-size: 0.7rem;
        color: #495057;
      }

      .codigo-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .codigo-referido {
        font-weight: bold;
        color: #495057;
        font-family: 'Courier New', monospace;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
      }

      .codigo-descripcion {
        color: #6c757d;
        font-size: 0.7rem;
      }

      .referente-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .referente-nombre {
        font-weight: 500;
        color: #495057;
      }

      .referente-beneficio {
        color: #28a745;
        font-size: 0.7rem;
      }

      .fecha-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        font-size: 0.75rem;
      }

      .fecha-uso {
        font-weight: bold;
        color: #495057;
      }

      .dias-transcurridos {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e8f4fd;
        color: #0c5460;
      }

      .estado-lead-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
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

      .codigos-top-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .codigo-top-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        margin: 4px 0;
        border-radius: 6px;
        font-size: 0.8rem;
        background-color: white;
        border: 1px solid #dee2e6;
      }

      .btn-grupo-referido {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }

      .btn-grupo-referido .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .alert-mensaje {
        margin-bottom: 20px;
      }

      .conversion-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .conversion-si { background-color: #d4edda; color: #155724; }
      .conversion-no { background-color: #f8d7da; color: #721c24; }
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
                    <a href="javascript: void(0)">Marketing y Captación</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Leads Referidos
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

        <!-- [ Códigos Más Usados ] start -->
        <?php if(count($codigos_top) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="codigos-top-panel">
              <h6 class="mb-3">
                <i class="ti ti-trophy me-1"></i>
                Códigos Más Usados
              </h6>
              <?php foreach($codigos_top as $codigo): ?>
              <div class="codigo-top-item">
                <div>
                  <strong class="codigo-referido"><?php echo htmlspecialchars($codigo['codigo']); ?></strong>
                  <small class="ms-2 text-muted"><?php echo htmlspecialchars($codigo['descripcion']); ?></small>
                </div>
                <div class="text-end">
                  <span class="badge bg-primary"><?php echo $codigo['total_usos']; ?> usos</span>
                  <span class="badge bg-success"><?php echo $codigo['conversiones']; ?> conversiones</span>
                  <span class="badge bg-info"><?php echo $codigo['tasa_conversion']; ?>%</span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <!-- [ Códigos Más Usados ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Gestión de Leads Referidos
                  </h3>
                  <small class="text-muted">
                    Administra los leads captados mediante códigos de referencia. Registra nuevos usos,
                    actualiza el estado de conversión y consulta el historial de referidos.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsoReferido">
                    <i class="ti ti-link me-1"></i>
                    Registrar Uso de Código
                  </button>
                </div>
              </div>

              <div class="card-body">
                <!-- Tabla de leads referidos -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="referidos-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Lead Referido</th>
                        <th width="12%">Código Usado</th>
                        <th width="12%">Referente</th>
                        <th width="10%">Fecha Uso</th>
                        <th width="8%">Estado Lead</th>
                        <th width="8%">Convertido</th>
                        <th width="8%">Canal</th>
                        <th width="10%">Antigüedad</th>
                        <th width="12%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para convertido
                              $convertido_class = $row['convertido'] ? 'convertido-si' : 'convertido-no';
                              $convertido_text = $row['convertido'] ? 'Sí' : 'Pendiente';

                              // Determinar clase de antigüedad
                              $antiguedad_class = 'antiguedad-' . strtolower(str_replace(' ', '-', $row['antiguedad_uso']));

                              // Formatear fechas
                              $fecha_uso = date('d/m/Y', strtotime($row['fecha_uso']));
                              $fecha_conversion = $row['fecha_conversion'] ? date('d/m/Y', strtotime($row['fecha_conversion'])) : 'N/A';

                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='badge badge-antiguedad $antiguedad_class'>" . $row['antiguedad_uso'] . "</span>
                                    </td>";
                              echo "<td>
                                      <div class='referido-info'>
                                        <span class='referido-estudiante'>" . htmlspecialchars($row['nombre_estudiante']) . "</span>
                                        <span class='referido-codigo-lead'>" . htmlspecialchars($row['codigo_lead']) . "</span>
                                        <span class='referido-contacto'>" . htmlspecialchars($row['nombre_contacto']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='codigo-info'>
                                        <span class='codigo-referido'>" . htmlspecialchars($row['codigo_referido']) . "</span>
                                        <span class='codigo-descripcion'>" . htmlspecialchars($row['descripcion_codigo']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='referente-info'>
                                        <span class='referente-nombre'>" . htmlspecialchars($row['referente_nombre']) . "</span>
                                        " . ($row['beneficio_referido'] ? "<span class='referente-beneficio'>🎁 " . htmlspecialchars($row['beneficio_referido']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='fecha-info'>
                                        <span class='fecha-uso'>" . $fecha_uso . "</span>
                                        <span class='dias-transcurridos'>Hace " . $row['dias_desde_uso'] . " días</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <span class='badge estado-lead-badge' style='background-color: " . $row['estado_color'] . "'>" . 
                                      htmlspecialchars($row['estado_lead']) . "</span>
                                    </td>";
                              echo "<td>
                                      <span class='badge badge-convertido $convertido_class'>" . $convertido_text . "</span>
                                      " . ($row['fecha_conversion'] ? "<br><small class='text-muted'>" . $fecha_conversion . "</small>" : "") . "
                                    </td>";
                              echo "<td>" . htmlspecialchars($row['canal_captacion']) . "</td>";
                              echo "<td>
                                      <small class='text-muted'>Grado:</small>
                                      <br><strong>" . ($row['grado_interes'] ?? 'N/A') . "</strong>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-referido'>
                                        <button type='button' class='btn btn-outline-info btn-ver-referido'
                                                data-id='" . $row['id'] . "'
                                                data-lead='" . htmlspecialchars($row['nombre_estudiante']) . "'
                                                data-codigo='" . htmlspecialchars($row['codigo_referido']) . "'
                                                title='Ver Detalles'>
                                          <i class='ti ti-eye'></i>
                                        </button>

                                        " . (!$row['convertido'] ? "
                                        <button type='button' class='btn btn-outline-success btn-convertir-referido'
                                                data-id='" . $row['id'] . "'
                                                data-lead='" . htmlspecialchars($row['nombre_estudiante']) . "'
                                                title='Marcar como Convertido'>
                                          <i class='ti ti-check'></i>
                                        </button>
                                        " : "") . "

                                        <button type='button' class='btn btn-outline-warning btn-editar-referido'
                                                data-id='" . $row['id'] . "'
                                                data-lead='" . htmlspecialchars($row['nombre_estudiante']) . "'
                                                title='Editar Observaciones'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='10' class='text-center'>No hay leads referidos registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Lead Referido</th>
                        <th>Código Usado</th>
                        <th>Referente</th>
                        <th>Fecha Uso</th>
                        <th>Estado Lead</th>
                        <th>Convertido</th>
                        <th>Canal</th>
                        <th>Antigüedad</th>
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

    <!-- Incluir Modales (se crearán después) -->
    <?php include 'modals/leads_referidos/modal_registrar_uso_referido.php'; ?>
    <?php include 'modals/leads_referidos/modal_ver_referido.php'; ?>
    <?php include 'modals/leads_referidos/modal_convertir_referido.php'; ?>

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
            // Verificar si hay datos en la tabla
            var tieneFilas = $("#referidos-table tbody tr").length > 0 && 
                            !$("#referidos-table tbody tr td[colspan]").length;
            
            if (!tieneFilas) {
                // Si no hay datos, mostrar mensaje amigable sin inicializar DataTables
                $("#referidos-table tbody").html(`
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="ti ti-database-off" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                                <h5 class="text-muted">No hay leads referidos registrados</h5>
                                <p class="text-muted mb-3">Comienza registrando el uso de códigos de referencia</p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsoReferido">
                                    <i class="ti ti-link me-1"></i>
                                    Registrar Primer Uso
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
                
                // Ocultar controles de DataTables que no son necesarios
                $(".dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate").hide();
                
                console.log("Tabla vacía - DataTables no inicializado");
                
            } else {
                // Inicializar DataTable solo si hay datos
                var table = $("#referidos-table").DataTable({
                "language": {
                    "decimal": "",
                    "emptyTable": "No hay leads referidos disponibles",
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
                    { "orderable": false, "targets": 9 } // Deshabilitar ordenación en columna de acciones
                ],
                "initComplete": function () {
                    this.api().columns().every(function (index) {
                    var column = this;

                    if (index < 9) {
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
                        $(column.footer()).html('<strong>Acciones</strong>');
                    }
                    });
                }
                });
                
                console.log("DataTables inicializado correctamente con " + table.rows().count() + " filas");
            }

            // Manejar click en botón ver referido
            $(document).on('click', '.btn-ver-referido', function() {
                var id = $(this).data('id');
                var lead = $(this).data('lead');
                var codigo = $(this).data('codigo');

                Swal.fire({
                title: 'Ver Detalles del Referido',
                html: `
                    <div class="text-start">
                    <p><strong>Lead:</strong> ${lead}</p>
                    <p><strong>Código:</strong> ${codigo}</p>
                    <p class="text-muted"><small>ID del registro: ${id}</small></p>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ver Completo',
                cancelButtonText: 'Cerrar'
                }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal de ver referido (por implementar)
                    cargarDatosReferido(id);
                  $('#modalVerReferido').modal('show');
                }
                });
            });

            // Manejar click en botón convertir referido
            $(document).on('click', '.btn-convertir-referido', function() {
                var id = $(this).data('id');
                var lead = $(this).data('lead');

                Swal.fire({
                title: '¿Marcar como Convertido?',
                html: `
                    <div class="text-start">
                    <p>Se registrará que el lead <strong>${lead}</strong> se ha convertido en estudiante.</p>
                    <p class="text-muted"><small>Esta acción actualizará las estadísticas de conversión.</small></p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, marcar como convertido',
                cancelButtonText: 'Cancelar'
                }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal de conversión (por implementar)
                    // $('#modalConvertirReferido').modal('show');
                    Swal.fire({
                    title: 'Próximamente',
                    text: 'El modal de conversión está en desarrollo',
                    icon: 'info',
                    confirmButtonColor: '#28a745'
                    });
                }
                });
            });

            // Manejar click en botón editar referido
            $(document).on('click', '.btn-editar-referido', function() {
                var id = $(this).data('id');
                var lead = $(this).data('lead');

                Swal.fire({
                title: 'Editar Observaciones',
                html: `
                    <div class="text-start mb-3">
                    <p><strong>Lead:</strong> ${lead}</p>
                    <p class="text-muted"><small>Edite las observaciones adicionales del registro</small></p>
                    </div>
                    <textarea id="swal-observaciones" class="form-control" rows="4" placeholder="Ingrese observaciones..."></textarea>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return document.getElementById('swal-observaciones').value;
                }
                }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí iría la lógica para guardar observaciones
                    Swal.fire({
                    title: 'Próximamente',
                    text: 'La funcionalidad de edición está en desarrollo',
                    icon: 'info',
                    confirmButtonColor: '#ffc107'
                    });
                }
                });
            });

            // Tooltip para elementos
            $('[title]').tooltip();
            
            console.log("Eventos de referidos configurados correctamente");
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