<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/reenganche_desertores/gestionar_desertor.php');
        exit();
    }

    // Consulta principal para obtener estudiantes retirados con información relacionada
    $sql = "SELECT
        e.id,
        e.codigo_estudiante,
        e.nombres,
        e.apellidos,
        e.fecha_retiro,
        e.motivo_retiro,
        e.estado_matricula,
        e.grado_id,
        e.seccion,
        -- Información de la familia
        f.id as familia_id,
        f.codigo_familia,
        f.apellido_principal,
        f.nivel_socioeconomico,
        f.distrito,
        -- Apoderado titular
        apt.id as apoderado_titular_id,
        apt.nombres as apoderado_nombres,
        apt.apellidos as apoderado_apellidos,
        apt.email as apoderado_email,
        apt.telefono_principal as apoderado_telefono,
        apt.whatsapp as apoderado_whatsapp,
        apt.preferencia_contacto,
        -- Grado que cursaba
        g.nombre as grado_nombre,
        -- Calcular tiempo desde retiro
        IFNULL(DATEDIFF(CURDATE(), e.fecha_retiro), 0) as dias_retirado,
        CASE
            WHEN e.fecha_retiro IS NULL THEN 'Sin Fecha'
            WHEN DATEDIFF(CURDATE(), e.fecha_retiro) <= 90 THEN 'Reciente'
            WHEN DATEDIFF(CURDATE(), e.fecha_retiro) <= 180 THEN 'Medio'
            ELSE 'Antiguo'
        END as categoria_retiro
    FROM estudiantes e
    LEFT JOIN familias f ON e.familia_id = f.id
    LEFT JOIN apoderados apt ON f.id = apt.familia_id AND apt.tipo_apoderado = 'titular'
    LEFT JOIN grados g ON e.grado_id = g.id
    WHERE e.estado_matricula = 'retirado'
    AND e.activo = 1
    ORDER BY e.fecha_retiro DESC, e.apellidos ASC";

    $result = $conn->query($sql);

    // Si no hay resultados, preparar mensaje
    $tiene_resultados = ($result && $result->num_rows > 0);

    // Obtener estadísticas generales
    $stats_sql = "SELECT
        COUNT(*) as total_desertores,
        COUNT(CASE WHEN IFNULL(DATEDIFF(CURDATE(), fecha_retiro), 0) <= 90 THEN 1 END) as recientes,
        COUNT(CASE WHEN IFNULL(DATEDIFF(CURDATE(), fecha_retiro), 0) > 90 AND IFNULL(DATEDIFF(CURDATE(), fecha_retiro), 0) <= 180 THEN 1 END) as medios,
        COUNT(CASE WHEN IFNULL(DATEDIFF(CURDATE(), fecha_retiro), 0) > 180 THEN 1 END) as antiguos
    FROM estudiantes
    WHERE estado_matricula = 'retirado' AND activo = 1";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener motivo más común
    $motivo_sql = "SELECT motivo_retiro, COUNT(*) as cantidad
                   FROM estudiantes 
                   WHERE estado_matricula = 'retirado' 
                   AND motivo_retiro IS NOT NULL 
                   AND motivo_retiro != ''
                   GROUP BY motivo_retiro 
                   ORDER BY cantidad DESC 
                   LIMIT 1";
    
    $motivo_result = $conn->query($motivo_sql);
    $motivo_principal = '';
    if ($motivo_result && $motivo_result->num_rows > 0) {
        $motivo_row = $motivo_result->fetch_assoc();
        $motivo_principal = $motivo_row['motivo_retiro'];
    }

    // Obtener estadísticas de intentos de reenganche
    $intentos_sql = "SELECT
        COUNT(DISTINCT i.familia_id) as familias_contactadas,
        COUNT(*) as total_intentos,
        COUNT(CASE WHEN i.resultado = 'exitoso' THEN 1 END) as contactos_exitosos,
        COUNT(CASE WHEN i.resultado = 'sin_respuesta' THEN 1 END) as sin_respuesta
    FROM interacciones i
    WHERE i.asunto LIKE '%reenganche%'
    AND i.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";

    $intentos_result = $conn->query($intentos_sql);
    $intentos_stats = $intentos_result->fetch_assoc();

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
    <title>Reenganche de Desertores - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Sistema CRM para instituciones educativas - Reenganche de Desertores" />
    <meta name="keywords" content="CRM, Educación, Desertores, Reenganche, Seguimiento" />
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
      .badge-categoria-retiro {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .categoria-reciente { background-color: #28a745; }
      .categoria-medio { background-color: #ffc107; color: #856404; }
      .categoria-antiguo { background-color: #dc3545; }
      .categoria-sin-fecha { background-color: #6c757d; }

      .desertor-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .desertor-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }

      .desertor-codigo {
        font-size: 0.75rem;
        color: #6c757d;
        font-family: 'Courier New', monospace;
      }

      .desertor-grado {
        font-size: 0.7rem;
        color: #495057;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
      }

      .familia-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .familia-apellido {
        font-weight: 500;
        color: #495057;
      }

      .familia-nivel {
        color: #6c757d;
        font-size: 0.7rem;
      }

      .contacto-apoderado {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }

      .contacto-nombre {
        font-weight: 500;
        color: #495057;
      }

      .contacto-datos {
        color: #6c757d;
      }

      .retiro-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .retiro-fecha {
        font-size: 0.8rem;
        font-weight: 500;
        color: #495057;
      }

      .retiro-dias {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e8f4fd;
        color: #0c5460;
      }

      .motivo-retiro {
        font-size: 0.75rem;
        color: #721c24;
        background-color: #f8d7da;
        padding: 0.3rem 0.5rem;
        border-radius: 6px;
        text-align: center;
      }

      .stats-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

      .btn-grupo-desertor {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }

      .btn-grupo-desertor .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .alert-mensaje {
        margin-bottom: 20px;
      }

      .motivo-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
      }

      .motivo-text {
        font-size: 0.85rem;
        color: #495057;
        font-style: italic;
      }

      .alert-info-custom {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
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
                  <li class="breadcrumb-item"><a href="javascript: void(0)">Gestión Académica</a></li>
                  <li class="breadcrumb-item" aria-current="page">Reenganche de Desertores</li>
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
                  <h3 class="mb-1">Reenganche de Desertores</h3>
                  <small class="text-muted">
                    Gestiona el seguimiento y reincorporación de estudiantes retirados. Identifica oportunidades de reenganche
                    y contacta familias para recuperar estudiantes.
                  </small>
                  <?php if(!empty($motivo_principal)): ?>
                  <div class="motivo-panel">
                    <small class="text-muted">Motivo de deserción más común:</small>
                    <div class="motivo-text"><?php echo htmlspecialchars($motivo_principal); ?></div>
                  </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card-body" style="padding-top: 0;">
                <?php if (!$tiene_resultados): ?>
                <div class="alert-info-custom">
                  <h5><i class="ti ti-info-circle me-2"></i>No hay estudiantes retirados registrados</h5>
                  <p class="mb-0">Actualmente no existen estudiantes con estado "retirado" en la base de datos. 
                  Este módulo se activará cuando haya estudiantes que hayan dejado la institución.</p>
                </div>
                <?php endif; ?>

                <!-- Tabla de desertores -->
                <div class="dt-responsive table-responsive">
                  <table id="desertores-table" class="table table-striped table-bordered nowrap">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Estudiante</th>
                        <th>Familia</th>
                        <th>Apoderado</th>
                        <th>Retiro</th>
                        <th>Categoría</th>
                        <th>Motivo</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($tiene_resultados) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para la categoría
                              $categoria_class = 'categoria-' . strtolower(str_replace(' ', '-', $row['categoria_retiro']));
                              
                              // Formatear fechas
                              $fecha_retiro = $row['fecha_retiro'] ? date('d/m/Y', strtotime($row['fecha_retiro'])) : 'No especificada';

                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              
                              echo "<td>
                                      <div class='desertor-info'>
                                        <span class='desertor-nombre'>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</span>
                                        <span class='desertor-codigo'>" . htmlspecialchars($row['codigo_estudiante']) . "</span>
                                        <span class='desertor-grado'>" . htmlspecialchars($row['grado_nombre'] ?? 'S/G') . " - Sección " . htmlspecialchars($row['seccion'] ?? 'S/S') . "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='familia-info'>
                                        <span class='familia-apellido'>" . htmlspecialchars($row['apellido_principal'] ?? 'Sin apellido') . "</span>
                                        <span class='familia-nivel'>Nivel: " . htmlspecialchars($row['nivel_socioeconomico'] ?? 'N/E') . "</span>
                                        <span class='familia-nivel'>" . htmlspecialchars($row['distrito'] ?? 'Sin distrito') . "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='contacto-apoderado'>
                                        <span class='contacto-nombre'>" . 
                                        ($row['apoderado_nombres'] ? htmlspecialchars($row['apoderado_nombres'] . ' ' . $row['apoderado_apellidos']) : 'Sin apoderado') . 
                                        "</span>
                                        <span class='contacto-datos'>" . 
                                        ($row['apoderado_telefono'] ? htmlspecialchars($row['apoderado_telefono']) : 'Sin teléfono') . 
                                        "</span>
                                      </div>
                                    </td>";
                              
                              echo "<td>
                                      <div class='retiro-info'>
                                        <span class='retiro-fecha'>" . $fecha_retiro . "</span>
                                        <span class='retiro-dias'>" . $row['dias_retirado'] . " días</span>
                                      </div>
                                    </td>";
                              
                              echo "<td><span class='badge badge-categoria-retiro $categoria_class'>" . $row['categoria_retiro'] . "</span></td>";
                              
                              echo "<td><div class='motivo-retiro'>" . 
                                   ($row['motivo_retiro'] ? htmlspecialchars(substr($row['motivo_retiro'], 0, 50)) . (strlen($row['motivo_retiro']) > 50 ? '...' : '') : 'No especificado') . 
                                   "</div></td>";
                              
                              echo "<td>
                                      <div class='btn-grupo-desertor'>
                                        <button type='button' class='btn btn-outline-warning btn-contactar-desertor'
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                data-familia-id='" . $row['familia_id'] . "'
                                                data-apoderado-id='" . ($row['apoderado_titular_id'] ?? 0) . "'
                                                title='Contactar para Reenganche'>
                                          <i class='ti ti-phone'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-info btn-registrar-intento'
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                data-familia-id='" . $row['familia_id'] . "'
                                                title='Registrar Intento de Reenganche'>
                                          <i class='ti ti-clipboard-check'></i>
                                        </button>

                                        <button type='button' class='btn btn-outline-success btn-convertir-lead'
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                title='Convertir a Lead'>
                                          <i class='ti ti-user-plus'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='8' class='text-center'>No hay estudiantes retirados registrados</td></tr>";
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
            var tablaConDatos = $("#desertores-table tbody tr").length > 0 && 
                                !$("#desertores-table tbody tr td[colspan]").length;
            
            if (tablaConDatos) {
                // Inicializar DataTable solo si hay datos
                var table = $("#desertores-table").DataTable({
                "language": {
                    "decimal": "",
                    "emptyTable": "No hay desertores disponibles en la tabla",
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
                "order": [[ 4, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": 7 }
                ],
                "autoWidth": false,
                "deferRender": true,
                "processing": true
                });
            } else {
                // Si no hay datos, aplicar estilos básicos sin DataTable
                $("#desertores-table").addClass('table-no-data');
                console.log('DataTable no inicializado: No hay datos disponibles');
            }

            // Tooltip para elementos
            $('[title]').tooltip();
            
            // Manejar clicks en botones (funcionan con o sin DataTable)
            $(document).on('click', '.btn-contactar-desertor', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: 'Contactar Desertor',
                    text: '¿Desea iniciar contacto con la familia de ' + nombre + '?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, contactar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí se abrirá el modal de contactar
                        $('#modalContactarDesertor').modal('show');
                    }
                });
            });

            $(document).on('click', '.btn-registrar-intento', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: 'Registrar Intento',
                    text: 'Registrar intento de reenganche para ' + nombre,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí se abrirá el modal de registrar intento
                        $('#modalRegistrarIntento').modal('show');
                    }
                });
            });

            $(document).on('click', '.btn-convertir-lead', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: 'Convertir a Lead',
                    text: '¿Desea convertir a ' + nombre + ' en un nuevo lead?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, convertir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí se abrirá el modal de convertir a lead
                        $('#modalConvertirLead').modal('show');
                    }
                });
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