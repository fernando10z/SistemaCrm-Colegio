<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/referidos_exalumnos/gestionar_referido.php');
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
        -- Información del apoderado si existe
        a.nombres as apoderado_nombres,
        a.apellidos as apoderado_apellidos,
        a.email as apoderado_email,
        a.telefono_principal as apoderado_telefono,
        -- Información de la familia si existe
        f.codigo_familia,
        f.apellido_principal,
        -- Calcular días restantes de vigencia
        CASE
            WHEN cr.fecha_fin IS NULL THEN NULL
            WHEN DATEDIFF(cr.fecha_fin, CURDATE()) < 0 THEN 0
            ELSE DATEDIFF(cr.fecha_fin, CURDATE())
        END as dias_vigencia,
        -- Estado de vigencia
        CASE
            WHEN cr.fecha_fin IS NULL THEN 'Sin vencimiento'
            WHEN DATEDIFF(cr.fecha_fin, CURDATE()) < 0 THEN 'Vencido'
            WHEN DATEDIFF(cr.fecha_fin, CURDATE()) <= 30 THEN 'Por vencer'
            ELSE 'Vigente'
        END as estado_vigencia,
        -- Calcular usos restantes
        CASE
            WHEN cr.limite_usos IS NULL THEN 'Ilimitado'
            ELSE CAST((cr.limite_usos - cr.usos_actuales) as CHAR)
        END as usos_restantes,
        -- Porcentaje de uso
        CASE
            WHEN cr.limite_usos IS NULL OR cr.limite_usos = 0 THEN 0
            ELSE ROUND((cr.usos_actuales / cr.limite_usos) * 100, 0)
        END as porcentaje_uso
    FROM codigos_referido cr
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    ORDER BY 
        cr.activo DESC,
        CASE
            WHEN cr.fecha_fin IS NULL THEN 1
            WHEN DATEDIFF(cr.fecha_fin, CURDATE()) < 0 THEN 3
            ELSE 2
        END,
        cr.fecha_fin ASC,
        cr.codigo ASC";

    $result = $conn->query($sql);

    // Verificar si hay resultados
    $tiene_resultados = ($result && $result->num_rows > 0);

    // Obtener estadísticas generales de códigos de referido
    $stats_sql = "SELECT
        COUNT(*) as total_codigos,
        COUNT(CASE WHEN activo = 1 THEN 1 END) as codigos_activos,
        COUNT(CASE WHEN activo = 0 THEN 1 END) as codigos_inactivos,
        SUM(usos_actuales) as total_usos,
        COUNT(CASE WHEN fecha_fin IS NOT NULL AND fecha_fin < CURDATE() THEN 1 END) as codigos_vencidos
    FROM codigos_referido";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener estadísticas de conversiones
    $conversiones_sql = "SELECT
        COUNT(*) as total_usos_registrados,
        COUNT(CASE WHEN convertido = 1 THEN 1 END) as conversiones_exitosas,
        CASE 
            WHEN COUNT(*) > 0 THEN ROUND((COUNT(CASE WHEN convertido = 1 THEN 1 END) / COUNT(*)) * 100, 1)
            ELSE 0
        END as tasa_conversion
    FROM usos_referido";

    $conversiones_result = $conn->query($conversiones_sql);
    $conversiones_stats = $conversiones_result->fetch_assoc();

    // Obtener código más usado
    $codigo_top_sql = "SELECT cr.codigo, cr.usos_actuales
                       FROM codigos_referido cr
                       WHERE cr.usos_actuales > 0
                       ORDER BY cr.usos_actuales DESC
                       LIMIT 1";
    
    $codigo_top_result = $conn->query($codigo_top_sql);
    $codigo_top = '';
    $usos_top = 0;
    if ($codigo_top_result && $codigo_top_result->num_rows > 0) {
        $codigo_top_row = $codigo_top_result->fetch_assoc();
        $codigo_top = $codigo_top_row['codigo'];
        $usos_top = $codigo_top_row['usos_actuales'];
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
    <title>Referidos de Exalumnos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Sistema CRM para instituciones educativas - Referidos de Exalumnos" />
    <meta name="keywords" content="CRM, Educación, Referidos, Exalumnos, Incentivos" />
    <meta name="author" content="CRM Escolar" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <!-- [Page specific CSS] start -->
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap5.min.css" />
    <!-- [Page specific CSS] end -->
    <!-- [Google Font] Family -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link" />
    <!-- [Tabler Icons] -->
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] -->
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <!-- [Material Icons] -->
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />

    <!-- Custom styles -->
    <style>
      .badge-estado-vigencia {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .vigente { background-color: #28a745; }
      .por-vencer { background-color: #ffc107; color: #856404; }
      .vencido { background-color: #dc3545; }
      .sin-vencimiento { background-color: #17a2b8; }

      .codigo-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .codigo-principal {
        font-weight: 700;
        color: #2c3e50;
        font-size: 1.1rem;
        font-family: 'Courier New', monospace;
        letter-spacing: 1px;
      }

      .codigo-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
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

      .referente-contacto {
        color: #6c757d;
        font-size: 0.7rem;
      }

      .vigencia-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .vigencia-fechas {
        font-size: 0.75rem;
        color: #495057;
        text-align: center;
      }

      .vigencia-dias {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e8f4fd;
        color: #0c5460;
      }

      .uso-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
      }

      .uso-numeros {
        font-size: 1rem;
        font-weight: bold;
        color: #495057;
      }

      .uso-barra {
        width: 100%;
        max-width: 100px;
      }

      .uso-porcentaje {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .beneficio-item {
        font-size: 0.75rem;
        color: #495057;
        background-color: #e7f3ff;
        padding: 0.3rem 0.5rem;
        border-radius: 6px;
        margin-bottom: 3px;
        border-left: 3px solid #007bff;
      }

      .beneficio-label {
        font-weight: 600;
        color: #007bff;
        display: block;
        margin-bottom: 2px;
        font-size: 0.7rem;
      }

      .badge-activo {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .estado-activo { background-color: #d4edda; color: #155724; }
      .estado-inactivo { background-color: #f8d7da; color: #721c24; }

      .conversiones-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .conversiones-numero {
        font-size: 1.2rem;
        font-weight: bold;
        color: #28a745;
      }

      .conversiones-texto {
        font-size: 0.7rem;
        color: #6c757d;
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

      .alert-info-custom {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
      }

      .codigo-top-panel {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin-top: 10px;
        border-radius: 4px;
      }

      .codigo-top-text {
        font-size: 0.85rem;
        color: #856404;
        margin: 0;
      }

      .table-no-data {
        opacity: 0.8;
      }

      .table-no-data tbody td {
        text-align: center;
        padding: 2rem;
        font-size: 1rem;
        color: #6c757d;
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
                  <li class="breadcrumb-item"><a href="javascript: void(0)">Marketing y Captación</a></li>
                  <li class="breadcrumb-item" aria-current="page">Referidos de Exalumnos</li>
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
                  <h3 class="mb-1">Programa de Referidos de Exalumnos</h3>
                  <small class="text-muted">
                    Gestiona códigos de referido para exalumnos y familias actuales. Configura beneficios,
                    rastrea conversiones y genera reportes del programa de incentivos.
                  </small>
                  <?php if(!empty($codigo_top)): ?>
                  <div class="codigo-top-panel">
                    <p class="codigo-top-text mb-0">
                      <i class="ti ti-trophy me-1"></i>
                      <strong>Código más usado:</strong> <?php echo htmlspecialchars($codigo_top); ?> 
                      (<?php echo $usos_top; ?> uso<?php echo $usos_top != 1 ? 's' : ''; ?>)
                    </p>
                  </div>
                  <?php endif; ?>
                </div>
                <div>
                  <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalGenerarCodigo()">
                    <i class="ti ti-plus me-1"></i>
                    Generar Código
                  </button>
                </div>
              </div>

              <div class="card-body">
                <?php if (!$tiene_resultados): ?>
                <div class="alert-info-custom">
                  <h5><i class="ti ti-info-circle me-2"></i>No hay códigos de referido registrados</h5>
                  <p class="mb-0">Actualmente no existen códigos de referido en el sistema. 
                  Puede crear códigos para exalumnos o familias actuales haciendo clic en "Generar Código".</p>
                  <hr>
                  <small class="text-muted">
                    <strong>Beneficios del Programa:</strong> Los códigos de referido permiten incentivar 
                    recomendaciones y rastrear nuevos estudiantes provenientes de referencias.
                  </small>
                </div>
                <?php endif; ?>

                <!-- Tabla de códigos de referido -->
                <div class="dt-responsive table-responsive">
                  <table id="referidos-table" class="table table-striped table-bordered nowrap">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Referente</th>
                        <th>Vigencia</th>
                        <th>Uso</th>
                        <th>Beneficios</th>
                        <th>Estado</th>
                        <th>Conversiones</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($tiene_resultados) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado de vigencia
                              $vigencia_class = strtolower(str_replace(' ', '-', $row['estado_vigencia']));
                              
                              // Determinar clase de estado activo
                              $activo_class = $row['activo'] == 1 ? 'estado-activo' : 'estado-inactivo';
                              $activo_text = $row['activo'] == 1 ? 'Activo' : 'Inactivo';
                              
                              // Formatear fechas
                              $fecha_inicio = $row['fecha_inicio'] ? date('d/m/Y', strtotime($row['fecha_inicio'])) : 'No especificada';
                              $fecha_fin = $row['fecha_fin'] ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Sin vencimiento';
                              
                              // Información del referente
                              $referente_nombre = 'Sin asignar';
                              $referente_contacto = '';
                              if ($row['apoderado_nombres']) {
                                  $referente_nombre = htmlspecialchars($row['apoderado_nombres'] . ' ' . $row['apoderado_apellidos']);
                                  $referente_contacto = htmlspecialchars($row['apoderado_telefono'] ?? 'Sin teléfono');
                              } elseif ($row['apellido_principal']) {
                                  $referente_nombre = 'Familia ' . htmlspecialchars($row['apellido_principal']);
                                  $referente_contacto = 'Código: ' . htmlspecialchars($row['codigo_familia']);
                              }

                              // Calcular conversiones desde usos_referido
                              $conversiones_query = "SELECT COUNT(*) as total FROM usos_referido WHERE codigo_referido_id = " . $row['id'] . " AND convertido = 1";
                              $conv_result = $conn->query($conversiones_query);
                              $conversiones = 0;
                              if ($conv_result && $conv_row = $conv_result->fetch_assoc()) {
                                  $conversiones = $conv_row['total'];
                              }

                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              
                              echo "<td>
                                      <div class='codigo-info'>
                                        <span class='codigo-principal'>" . htmlspecialchars($row['codigo']) . "</span>
                                        <span class='codigo-descripcion'>" . 
                                        ($row['descripcion'] ? htmlspecialchars($row['descripcion']) : 'Sin descripción') . 
                                        "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='referente-info'>
                                        <span class='referente-nombre'>" . $referente_nombre . "</span>
                                        <span class='referente-contacto'>" . $referente_contacto . "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='vigencia-info'>
                                        <span class='vigencia-fechas'>" . $fecha_inicio . "<br>al<br>" . $fecha_fin . "</span>";
                              
                              if ($row['dias_vigencia'] !== null && $row['dias_vigencia'] > 0) {
                                  echo "<span class='vigencia-dias'>" . $row['dias_vigencia'] . " días restantes</span>";
                              }
                              
                              echo "    </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='uso-info'>
                                        <span class='uso-numeros'>" . $row['usos_actuales'] . " / " . 
                                        ($row['limite_usos'] ?? '∞') . "</span>
                                        <div class='progress uso-barra' style='height: 8px;'>
                                          <div class='progress-bar bg-primary' role='progressbar' 
                                               style='width: " . $row['porcentaje_uso'] . "%' 
                                               aria-valuenow='" . $row['porcentaje_uso'] . "' 
                                               aria-valuemin='0' aria-valuemax='100'></div>
                                        </div>
                                        <span class='uso-porcentaje'>" . $row['porcentaje_uso'] . "% usado</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div style='max-width: 200px;'>";
                              
                              if ($row['beneficio_referente']) {
                                  echo "<div class='beneficio-item'>
                                          <span class='beneficio-label'>Referente:</span>
                                          " . htmlspecialchars(substr($row['beneficio_referente'], 0, 40)) . 
                                          (strlen($row['beneficio_referente']) > 40 ? '...' : '') . "
                                        </div>";
                              }
                              
                              if ($row['beneficio_referido']) {
                                  echo "<div class='beneficio-item'>
                                          <span class='beneficio-label'>Referido:</span>
                                          " . htmlspecialchars(substr($row['beneficio_referido'], 0, 40)) . 
                                          (strlen($row['beneficio_referido']) > 40 ? '...' : '') . "
                                        </div>";
                              }
                              
                              if (!$row['beneficio_referente'] && !$row['beneficio_referido']) {
                                  echo "<span class='text-muted'>Sin beneficios</span>";
                              }
                              
                              echo "    </div>
                                    </td>";
                              
                              echo "<td>
                                      <span class='badge badge-estado-vigencia $vigencia_class'>" . $row['estado_vigencia'] . "</span>
                                      <br>
                                      <span class='badge badge-activo $activo_class mt-1'>" . $activo_text . "</span>
                                    </td>";
                              
                              echo "<td>
                                      <div class='conversiones-info'>
                                        <span class='conversiones-numero'>" . $conversiones . "</span>
                                        <span class='conversiones-texto'>conversión" . ($conversiones != 1 ? 'es' : '') . "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='btn-grupo-referido'>
                                        <button type='button' class='btn btn-outline-info btn-ver-usos'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                data-usos='" . $row['usos_actuales'] . "'
                                                title='Ver Historial de Usos'>
                                          <i class='ti ti-history'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-success btn-gestionar-beneficios'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='Gestionar Beneficios'>
                                          <i class='ti ti-gift'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-warning btn-editar-codigo'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='Editar Código'>
                                          <i class='ti ti-edit'></i>
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
    <?php include 'modals/referidos_exalumnos/modal_generar_codigo.php'; ?>
    <?php include 'modals/referidos_exalumnos/modal_ver_usos.php'; ?>
    <?php include 'modals/referidos_exalumnos/modal_gestionar_beneficios.php'; ?>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      $(document).ready(function() {
            // Verificar si hay datos en la tabla antes de inicializar DataTable
            var tablaConDatos = $("#referidos-table tbody tr").length > 0 && 
                                !$("#referidos-table tbody tr td[colspan]").length;
            
            if (tablaConDatos) {
                // Inicializar DataTable solo si hay datos
                var table = $("#referidos-table").DataTable({
                  "language": {
                    "decimal": "",
                    "emptyTable": "No hay códigos de referido disponibles",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
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
                  "pageLength": 25,
                  "order": [[ 6, "desc" ], [ 1, "asc" ]], // Ordenar por estado activo y código
                  "columnDefs": [
                    { "orderable": false, "targets": 8 }
                  ],
                  "autoWidth": false,
                  "deferRender": true,
                  "processing": true
                });
            } else {
                // Si no hay datos, aplicar estilos básicos sin DataTable
                $("#referidos-table").addClass('table-no-data');
                console.log('DataTable no inicializado: No hay datos disponibles');
            }

            // Tooltip para elementos
            $('[title]').tooltip();
      });

      // Función para abrir modal de generar código
      function abrirModalGenerarCodigo() {
          Swal.fire({
              title: 'Generar Código de Referido',
              text: '¿Desea crear un nuevo código de referido?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#007bff',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Sí, generar',
              cancelButtonText: 'Cancelar'
          }).then((result) => {
              if (result.isConfirmed) {
                  $('#modalGenerarCodigo').modal('show');
              }
          });
      }

      // Manejar click en botón ver usos
      $(document).on('click', '.btn-ver-usos', function() {
          var id = $(this).data('id');
          var codigo = $(this).data('codigo');
          var usos = $(this).data('usos');
          
          Swal.fire({
              title: 'Historial de Usos',
              text: 'Ver historial del código: ' + codigo + ' (' + usos + ' usos)',
              icon: 'info',
              showCancelButton: true,
              confirmButtonColor: '#17a2b8',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Ver Historial',
              cancelButtonText: 'Cancelar'
          }).then((result) => {
              if (result.isConfirmed) {
                  // Aquí se abrirá el modal de ver usos
                  $('#modalVerUsos').modal('show');
              }
          });
      });

      // Manejar click en botón gestionar beneficios
      $(document).on('click', '.btn-gestionar-beneficios', function() {
          var id = $(this).data('id');
          var codigo = $(this).data('codigo');
          
          Swal.fire({
              title: 'Gestionar Beneficios',
              text: 'Configurar beneficios para el código: ' + codigo,
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#28a745',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Gestionar',
              cancelButtonText: 'Cancelar'
          }).then((result) => {
              if (result.isConfirmed) {
                  // Aquí se abrirá el modal de gestionar beneficios
                  $('#modalGestionarBeneficios').modal('show');
              }
          });
      });

      // Manejar click en botón editar código
      $(document).on('click', '.btn-editar-codigo', function() {
          var id = $(this).data('id');
          var codigo = $(this).data('codigo');
          
          Swal.fire({
              title: 'Editar Código',
              text: 'Editar configuración del código: ' + codigo,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#ffc107',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Editar',
              cancelButtonText: 'Cancelar'
          }).then((result) => {
              if (result.isConfirmed) {
                  // Aquí se abrirá el modal de generar código con datos precargados
                  $('#modalGenerarCodigo').modal('show');
              }
          });
      });
    </script>
    <!-- [Page Specific JS] end -->
    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
  <!-- [Body] end -->
</html>

<?php
$conn->close();
?>