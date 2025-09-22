<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los logs de acceso con información del usuario
$sql = "SELECT 
    l.id,
    l.usuario_id,
    COALESCE(CONCAT(u.nombre, ' ', u.apellidos), 'Sistema') as nombre_usuario,
    COALESCE(u.usuario, 'sistema') as username,
    l.ip_address,
    l.user_agent,
    l.accion,
    l.resultado,
    l.detalles,
    l.created_at
FROM logs_acceso l
LEFT JOIN usuarios u ON l.usuario_id = u.id
ORDER BY l.created_at DESC";

$result = $conn->query($sql);

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
    <title><?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Logs de Acceso"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Logs, Auditoría"
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
    
    <!-- Custom styles for logs -->
    <style>
      .badge-resultado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
      }
      .resultado-exitoso { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
      }
      .resultado-fallido { 
        background-color: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb;
      }
      
      .ip-address {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        border: 1px solid #e9ecef;
      }
      
      .user-agent-truncated {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: help;
        font-size: 0.8rem;
        color: #6c757d;
      }
      
      .accion-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: 500;
      }
      .accion-login { background-color: #e3f2fd; color: #0d47a1; }
      .accion-logout { background-color: #fce4ec; color: #880e4f; }
      .accion-editar { background-color: #fff3e0; color: #e65100; }
      .accion-crear { background-color: #e8f5e8; color: #1b5e20; }
      .accion-eliminar { background-color: #ffebee; color: #c62828; }
      .accion-otro { background-color: #f3e5f5; color: #7b1fa2; }
      
      .fecha-relativa {
        font-size: 0.85rem;
        color: #6c757d;
      }
      
      .detalles-json {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: help;
        background-color: #f8f9fa;
        padding: 2px 4px;
        border-radius: 3px;
      }
      
      .usuario-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .usuario-nombre {
        font-weight: 500;
        color: #495057;
      }
      
      .usuario-username {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
      }
      
      .stats-card .card-body {
        padding: 1rem;
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
                    <a href="javascript: void(0)">Configuración</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Logs de Acceso
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Logs de Acceso al Sistema
                  </h3>
                  <small class="text-muted">
                    Registro completo de todas las acciones realizadas en el sistema. 
                    Puedes filtrar por usuario, acción, resultado o fecha.
                  </small>
                </div>
                <div>
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarLogsPDF()">
                   <i class="fas fa-file-pdf me-1"></i>
                    Generar PDF
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de logs -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="logs-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Usuario</th>
                        <th width="10%">IP</th>
                        <th width="15%">Acción</th>
                        <th width="8%">Resultado</th>
                        <th width="25%">Detalles</th>
                        <th width="12%">User Agent</th>
                        <th width="15%">Fecha</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear la fecha
                              $fecha_formateada = date('d/m/Y H:i:s', strtotime($row['created_at']));
                              
                              // Determinar clase CSS para el resultado
                              $resultado_class = 'resultado-' . $row['resultado'];
                              
                              // Determinar clase CSS para la acción
                              $accion_lower = strtolower($row['accion']);
                              $accion_class = 'accion-otro'; // por defecto
                              if (strpos($accion_lower, 'login') !== false) $accion_class = 'accion-login';
                              elseif (strpos($accion_lower, 'logout') !== false) $accion_class = 'accion-logout';
                              elseif (strpos($accion_lower, 'editar') !== false) $accion_class = 'accion-editar';
                              elseif (strpos($accion_lower, 'crear') !== false) $accion_class = 'accion-crear';
                              elseif (strpos($accion_lower, 'eliminar') !== false) $accion_class = 'accion-eliminar';
                              
                              // Truncar User Agent
                              $user_agent_truncated = strlen($row['user_agent']) > 50 ? 
                                                    substr($row['user_agent'], 0, 50) . '...' : 
                                                    $row['user_agent'];
                              
                              // Procesar detalles JSON
                              $detalles_mostrar = $row['detalles'];
                              if (is_string($detalles_mostrar) && (strpos($detalles_mostrar, '{') === 0 || strpos($detalles_mostrar, '[') === 0)) {
                                  $detalles_json = json_decode($detalles_mostrar, true);
                                  if ($detalles_json && isset($detalles_json['detalle'])) {
                                      $detalles_mostrar = $detalles_json['detalle'];
                                  }
                              }
                              
                              // Truncar detalles si es muy largo
                              $detalles_truncados = strlen($detalles_mostrar) > 100 ? 
                                                   substr($detalles_mostrar, 0, 100) . '...' : 
                                                   $detalles_mostrar;
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='usuario-info'>
                                        <span class='usuario-nombre'>" . htmlspecialchars($row['nombre_usuario']) . "</span>
                                        <span class='usuario-username'>@" . htmlspecialchars($row['username']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='ip-address'>" . htmlspecialchars($row['ip_address']) . "</span></td>";
                              echo "<td><span class='badge accion-badge " . $accion_class . "'>" . 
                                   htmlspecialchars($row['accion']) . "</span></td>";
                              echo "<td><span class='badge badge-resultado " . $resultado_class . "'>" . 
                                   ucfirst($row['resultado']) . "</span></td>";
                              echo "<td><span class='detalles-json' title='" . htmlspecialchars($detalles_mostrar) . "'>" . 
                                   htmlspecialchars($detalles_truncados) . "</span></td>";
                              echo "<td><span class='user-agent-truncated' title='" . htmlspecialchars($row['user_agent']) . "'>" . 
                                   htmlspecialchars($user_agent_truncated) . "</span></td>";
                              echo "<td>
                                      <div class='fecha-relativa'>
                                        " . $fecha_formateada . "
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='8' class='text-center'>No hay logs registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>IP</th>
                        <th>Acción</th>
                        <th>Resultado</th>
                        <th>Detalles</th>
                        <th>User Agent</th>
                        <th>Fecha</th>
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
            var table = $("#logs-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay logs disponibles en la tabla",
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
              "pageLength": 50,
              "order": [[ 0, "desc" ]], // Ordenar por ID descendente (más recientes primero)
              "columnDefs": [
                { "orderable": true, "targets": "_all" } // Todas las columnas son ordenables
              ],
              "initComplete": function () {
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;
                  var title = $(column.header()).text();
                  
                  // Crear input de búsqueda para cada columna
                  var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
                    .appendTo($(column.footer()).empty())
                    .on('keyup change clear', function () {
                      if (column.search() !== this.value) {
                        column
                          .search(this.value)
                          .draw();
                      }
                    });
                });
              }
            });

            // Función para exportar logs a PDF con datos filtrados
            window.exportarLogsPDF = function() {
              var tabla = $('#logs-table').DataTable();
              var datosVisibles = [];
              
              // Obtener solo las filas visibles/filtradas
              tabla.rows({ filter: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                var data = this.data();
                var row = [];
                
                // Extraer texto limpio de cada celda (sin HTML)
                for (var i = 0; i < data.length; i++) {
                  var cellContent = $(data[i]).text() || data[i];
                  row.push(cellContent);
                }
                datosVisibles.push(row);
              });
              
              if (datosVisibles.length === 0) {
                alert('No hay registros visibles para generar el reporte PDF.');
                return;
              }
              
              // Crear formulario para enviar datos por POST
              var form = document.createElement('form');
              form.method = 'POST';
              form.action = 'reports/generar_pdf_logs.php';
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
            
            // Tooltip para elementos truncados
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