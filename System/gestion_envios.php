<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los mensajes enviados con información de tablas relacionadas
$sql = "SELECT 
    me.id,
    me.tipo,
    me.plantilla_id,
    pm.nombre as plantilla_nombre,
    pm.categoria as plantilla_categoria,
    me.lead_id,
    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as lead_nombre,
    me.apoderado_id,
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
    me.destinatario_email,
    me.destinatario_telefono,
    me.asunto,
    LEFT(me.contenido, 100) as contenido_preview,
    me.estado,
    me.fecha_envio,
    me.fecha_entrega,
    me.fecha_lectura,
    me.proveedor_id,
    me.mensaje_id_externo,
    me.costo,
    me.error_mensaje,
    me.created_at,
    CASE 
        WHEN me.lead_id IS NOT NULL THEN 'Lead'
        WHEN me.apoderado_id IS NOT NULL THEN 'Apoderado'
        ELSE 'Directo'
    END as tipo_destinatario,
    ic.nombre as canal_nombre,
    ic.activo as canal_activo
FROM mensajes_enviados me
LEFT JOIN plantillas_mensajes pm ON me.plantilla_id = pm.id
LEFT JOIN leads l ON me.lead_id = l.id
LEFT JOIN apoderados a ON me.apoderado_id = a.id
LEFT JOIN integraciones_config ic ON ic.tipo = me.tipo AND ic.activo = 1
ORDER BY me.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de mensajes para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_mensajes,
    COUNT(CASE WHEN estado = 'enviado' THEN 1 END) as mensajes_enviados,
    COUNT(CASE WHEN estado = 'entregado' THEN 1 END) as mensajes_entregados,
    COUNT(CASE WHEN estado = 'leido' THEN 1 END) as mensajes_leidos,
    COUNT(CASE WHEN estado = 'fallido' THEN 1 END) as mensajes_fallidos,
    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as mensajes_pendientes,
    COUNT(CASE WHEN tipo = 'email' THEN 1 END) as total_emails,
    COUNT(CASE WHEN tipo = 'whatsapp' THEN 1 END) as total_whatsapp,
    COUNT(CASE WHEN tipo = 'sms' THEN 1 END) as total_sms,
    SUM(costo) as costo_total,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as mensajes_hoy
FROM mensajes_enviados";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas de canales activos
$canales_sql = "SELECT 
    tipo,
    nombre,
    activo,
    ultima_sincronizacion
FROM integraciones_config 
WHERE tipo IN ('email', 'whatsapp', 'sms')
ORDER BY tipo";

$canales_result = $conn->query($canales_sql);
$canales = [];
while($canal = $canales_result->fetch_assoc()) {
    $canales[$canal['tipo']] = $canal;
}

// Obtener plantillas activas por tipo
$plantillas_sql = "SELECT id, nombre, tipo, categoria FROM plantillas_mensajes WHERE activo = 1 ORDER BY tipo, nombre";
$plantillas_result = $conn->query($plantillas_sql);

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
    <title>Comunicados Automáticos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Comunicados Automáticos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Comunicados, Email, WhatsApp, SMS, Mensajes"
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
    
    <!-- Custom styles for comunicados -->
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
      
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .estado-pendiente { background-color: #6c757d; }
      .estado-enviado { background-color: #007bff; }
      .estado-entregado { background-color: #28a745; }
      .estado-leido { background-color: #17a2b8; }
      .estado-fallido { background-color: #dc3545; }
      
      .badge-destinatario {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
      }
      
      .mensaje-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .mensaje-asunto {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .mensaje-preview {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .destinatario-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .destinatario-nombre {
        font-weight: 500;
        color: #495057;
        font-size: 0.8rem;
      }
      
      .destinatario-contacto {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .plantilla-info {
        font-size: 0.75rem;
        color: #6c757d;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
      }
      
      .fecha-envio {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .costo-mensaje {
        font-family: 'Courier New', monospace;
        font-size: 0.7rem;
        font-weight: bold;
        color: #28a745;
      }
      
      .error-mensaje {
        font-size: 0.7rem;
        color: #dc3545;
        background-color: #f8d7da;
        padding: 2px 4px;
        border-radius: 3px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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
      
      .canal-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: 500;
      }
      
      .canal-activo {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }
      
      .canal-inactivo {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }
      
      .btn-grupo-mensaje {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-mensaje .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .canales-status {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
      }

      .progress-delivery {
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
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
                    Comunicados Automáticos
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
                    Gestión de Comunicados Automáticos
                  </h3>
                  <small class="text-muted">
                    Administra el envío masivo de mensajes por email, WhatsApp y SMS. 
                    Utiliza plantillas, selecciona destinatarios y monitorea el estado de entrega.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-success btn-sm" onclick="procesarCola()">
                    <i class="ti ti-play me-1"></i>
                    Procesar Cola
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlantillas">
                    <i class="ti ti-template me-1"></i>
                    Plantillas
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalDestinatarios">
                    <i class="ti ti-users me-1"></i>
                    Seleccionar Destinatarios
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEnviarMensaje">
                    <i class="ti ti-send me-1"></i>
                    Enviar Mensaje
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de mensajes -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="mensajes-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="16%">Mensaje</th>
                        <th width="14%">Destinatario</th>
                        <th width="8%">Plantilla</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Fecha Envío</th>
                        <th width="8%">Entrega</th>
                        <th width="6%">Costo</th>
                        <th width="10%">Error</th>
                        <th width="8%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_creacion = date('d/m/Y H:i', strtotime($row['created_at']));
                              $fecha_envio = $row['fecha_envio'] ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : 'No enviado';
                              $fecha_entrega = $row['fecha_entrega'] ? date('d/m/Y H:i', strtotime($row['fecha_entrega'])) : '';
                              
                              // Determinar clase CSS para el tipo
                              $tipo_class = 'tipo-' . $row['tipo'];
                              
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado'];
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td><span class='badge badge-tipo $tipo_class'>" . 
                                   strtoupper($row['tipo']) . "</span></td>";
                              echo "<td>
                                      <div class='mensaje-info'>
                                        <span class='mensaje-asunto'>" . htmlspecialchars($row['asunto'] ?? 'Sin asunto') . "</span>
                                        <span class='mensaje-preview'>" . htmlspecialchars($row['contenido_preview'] ?? '') . "...</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='destinatario-info'>
                                        <span class='badge badge-destinatario'>" . $row['tipo_destinatario'] . "</span>
                                        <span class='destinatario-nombre'>" . 
                                        htmlspecialchars($row['lead_nombre'] ?? $row['apoderado_nombre'] ?? 'Destinatario directo') . "</span>
                                        <span class='destinatario-contacto'>" . 
                                        htmlspecialchars($row['destinatario_email'] ?? $row['destinatario_telefono'] ?? '') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>" . 
                                   ($row['plantilla_nombre'] ? "<span class='plantilla-info'>" . htmlspecialchars($row['plantilla_nombre']) . "</span>" : "<span class='text-muted'>Manual</span>") . 
                                   "</td>";
                              echo "<td><span class='badge badge-estado $estado_class'>" . 
                                   ucfirst($row['estado']) . "</span></td>";
                              echo "<td><span class='fecha-envio'>" . $fecha_envio . "</span></td>";
                              echo "<td>
                                      " . ($fecha_entrega ? "<span class='fecha-envio'>" . $fecha_entrega . "</span>" : 
                                      ($row['estado'] == 'enviado' ? "<span class='text-warning'>Pendiente</span>" : 
                                      "<span class='text-muted'>-</span>")) . "
                                    </td>";
                              echo "<td>" . 
                                   ($row['costo'] > 0 ? "<span class='costo-mensaje'>S/ " . number_format($row['costo'], 4) . "</span>" : 
                                   "<span class='text-muted'>Gratis</span>") . "</td>";
                              echo "<td>" . 
                                   ($row['error_mensaje'] ? "<span class='error-mensaje' title='" . htmlspecialchars($row['error_mensaje']) . "'>Error</span>" : 
                                   "<span class='text-muted'>-</span>") . "</td>";
                              echo "<td>
                                      <div class='btn-grupo-mensaje'>
                                        <button type='button' class='btn btn-outline-info btn-ver-detalle' 
                                                data-id='" . $row['id'] . "'
                                                title='Ver Detalles'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        " . ($row['estado'] == 'fallido' ? 
                                        "<button type='button' class='btn btn-outline-warning btn-reenviar' 
                                                data-id='" . $row['id'] . "'
                                                title='Reenviar'>
                                          <i class='ti ti-refresh'></i>
                                        </button>" : "") . "
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay mensajes registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Mensaje</th>
                        <th>Destinatario</th>
                        <th>Plantilla</th>
                        <th>Estado</th>
                        <th>Fecha Envío</th>
                        <th>Entrega</th>
                        <th>Costo</th>
                        <th>Error</th>
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
    <?php include 'modals/gestion_envios/modal_enviar_mensaje.php'; ?>
    <?php include 'modals/gestion_envios/modal_seleccionar_destinatarios.php'; ?>
    <?php include 'modals/gestion_envios/modal_plantillas.php'; ?>
    <?php include 'modals/gestion_envios/modal_procesar_cola.php'; ?>

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
            var table = $("#mensajes-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay mensajes disponibles en la tabla",
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

            // Función para procesar cola de mensajes
            window.procesarCola = function() {
              $('#modalProcesarCola').modal('show');
            };

            // Manejar click en botón ver detalle
            $(document).on('click', '.btn-ver-detalle', function() {
                var id = $(this).data('id');
                cargarDetalleMensaje(id);
            });

            // Manejar click en botón reenviar
            $(document).on('click', '.btn-reenviar', function() {
                var id = $(this).data('id');
                if (confirm('¿Está seguro de que desea reenviar este mensaje?')) {
                    reenviarMensaje(id);
                }
            });

            // Función para cargar detalle del mensaje
            function cargarDetalleMensaje(id) {
              $.ajax({
                url: 'actions/obtener_mensaje_detalle.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    // Mostrar modal con detalles
                    mostrarModalDetalle(response.data);
                  } else {
                    alert('Error al cargar los detalles: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener los detalles del mensaje.');
                }
              });
            }

            // Función para reenviar mensaje
            function reenviarMensaje(id) {
              $.ajax({
                url: 'actions/reenviar_mensaje.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    alert('Mensaje programado para reenvío.');
                    location.reload();
                  } else {
                    alert('Error al programar reenvío: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al reenviar el mensaje.');
                }
              });
            }

            // Función para mostrar modal de detalle
            function mostrarModalDetalle(data) {
              // Implementar modal de detalle aquí
              console.log('Detalle del mensaje:', data);
            }

            // Auto-refresh cada 30 segundos para mensajes en proceso
            setInterval(function() {
              var mensajesPendientes = $('.estado-pendiente, .estado-enviado').length;
              if (mensajesPendientes > 0) {
                // Solo recargar si hay mensajes en proceso
                table.ajax.reload(null, false);
              }
            }, 30000);

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