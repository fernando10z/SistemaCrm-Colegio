<?php
  // Incluir conexión a la base de datos
  include 'bd/conexion.php';

  // Consulta para obtener los leads con información de tablas relacionadas
  $sql = "SELECT 
      l.id,
      l.codigo_lead,
      l.nombres_estudiante,
      l.apellidos_estudiante,
      l.fecha_nacimiento_estudiante,
      l.genero_estudiante,
      l.nombres_contacto,
      l.apellidos_contacto,
      l.telefono,
      l.whatsapp,
      l.email,
      l.colegio_procedencia,
      l.motivo_cambio,
      l.observaciones,
      l.canal_captacion_id,
      cc.nombre as canal_captacion,
      cc.tipo as canal_tipo,
      l.grado_interes_id,
      g.nombre as grado_nombre,
      ne.nombre as nivel_nombre,
      l.estado_lead_id,
      el.nombre as estado_lead,
      el.color as color_estado,
      el.descripcion as estado_descripcion,
      l.responsable_id,
      CONCAT(u.nombre, ' ', u.apellidos) as responsable_nombre,
      l.prioridad,
      l.puntaje_interes,
      l.fecha_conversion,
      l.fecha_ultima_interaccion,
      l.proxima_accion_fecha,
      l.proxima_accion_descripcion,
      l.utm_source,
      l.utm_medium,
      l.utm_campaign,
      l.ip_origen,
      l.activo,
      l.created_at,
      l.updated_at,
      CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante_completo,
      CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto_completo
  FROM leads l
  LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
  LEFT JOIN grados g ON l.grado_interes_id = g.id
  LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
  LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
  LEFT JOIN usuarios u ON l.responsable_id = u.id
  WHERE l.activo = 1
  ORDER BY l.created_at DESC";

  $result = $conn->query($sql);

  // Obtener estadísticas de leads para mostrar
  $stats_sql = "SELECT 
      COUNT(*) as total_leads,
      COUNT(CASE WHEN el.nombre = 'Nuevo' THEN 1 END) as leads_nuevos,
      COUNT(CASE WHEN el.nombre = 'Contactado' THEN 1 END) as leads_contactados,
      COUNT(CASE WHEN el.nombre = 'Interesado' THEN 1 END) as leads_interesados,
      COUNT(CASE WHEN el.nombre = 'Matriculado' THEN 1 END) as leads_matriculados,
      COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
      COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as acciones_hoy
  FROM leads l
  LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
  WHERE l.activo = 1";

  $stats_result = $conn->query($stats_sql);
  $stats = $stats_result->fetch_assoc();

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
    <title>Gestión de Leads - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Leads"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Leads, Postulantes, Captación"
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
    
    <!-- Custom styles for leads -->
    <style>
      .badge-canal {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .canal-web { background-color: #17a2b8; }
      .canal-redes_sociales { background-color: #e83e8c; }
      .canal-referido { background-color: #28a745; }
      .canal-publicidad { background-color: #fd7e14; }
      .canal-evento { background-color: #6f42c1; }
      .canal-llamada_directa { background-color: #20c997; }
      .canal-otro { background-color: #6c757d; }
      
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      
      .badge-grado {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
      }

      .badge-prioridad {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .prioridad-baja { background-color: #d4edda; color: #155724; }
      .prioridad-media { background-color: #fff3cd; color: #856404; }
      .prioridad-alta { background-color: #f8d7da; color: #721c24; }
      .prioridad-urgente { 
        background-color: #dc3545; 
        color: white; 
        animation: pulse 2s infinite;
      }
      
      @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
      }
      
      .lead-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .lead-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .lead-email {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .lead-codigo {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
        border: 1px solid #e9ecef;
        color: #495057;
      }
      
      .contacto-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .telefono-info {
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        color: #495057;
        font-weight: 500;
      }
      
      .whatsapp-info {
        font-size: 0.7rem;
        color: #25d366;
        font-weight: 500;
      }
      
      .fecha-contacto {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .puntaje-interes {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
      }
      
      .estrella-activa { color: #ffc107; }
      .estrella-inactiva { color: #e9ecef; }
      
      .interes-bajo { color: #dc3545; }
      .interes-medio { color: #fd7e14; }
      .interes-alto { color: #28a745; }
      
      .utm-info {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        border-radius: 10px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
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
        font-size: 1.5rem;
        font-weight: bold;
        display: block;
      }
      
      .stat-label {
        font-size: 0.8rem;
        opacity: 0.9;
      }
      
      .btn-grupo-lead {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-lead .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .responsable-info {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }

      .proxima-accion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
      }

      .proxima-accion.hoy {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
        font-weight: bold;
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
                    Leads
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
                    Gestión de Leads y Postulantes
                  </h3>
                  <small class="text-muted">
                    Administra los leads captados desde diferentes canales. 
                    Registra nuevos leads, edita información y realiza seguimiento del proceso de captación.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarLeadsPDF()">
                    <i class="fas fa-file-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoLead">
                    <i class="ti ti-user-plus me-1"></i>
                    Nuevo Lead
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de leads -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="leads-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="12%">Estudiante</th>
                        <th width="12%">Contacto</th>
                        <th width="8%">Teléfono/WA</th>
                        <th width="8%">Grado</th>
                        <th width="8%">Canal</th>
                        <th width="7%">Estado</th>
                        <th width="6%">Prioridad</th>
                        <th width="6%">Interés</th>
                        <th width="8%">Responsable</th>
                        <th width="8%">Próxima Acción</th>
                        <th width="7%">Registro</th>
                        <th width="16%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_registro = date('d/m/Y', strtotime($row['created_at']));
                              $proxima_accion = $row['proxima_accion_fecha'] ? 
                                date('d/m/Y', strtotime($row['proxima_accion_fecha'])) : '';
                              
                              // Determinar puntuación de interés (escala de 0-100)
                              $puntaje = (int)($row['puntaje_interes'] ?? 0);

                              // Normalizar de 0-100 a 0-5 estrellas
                              $estrellas_cantidad = ceil($puntaje / 20); // 0–20 = 1 ⭐, 21–40 = 2 ⭐, etc.

                              // Generar estrellas
                              $estrellas = '';
                              for($i = 1; $i <= 5; $i++) {
                                  $clase = $i <= $estrellas_cantidad ? 'estrella-activa' : 'estrella-inactiva';
                                  $estrellas .= "<i class='fas fa-star $clase'></i>";
                              }

                              // Clase de color según puntaje
                              $interes_class = '';
                              if($puntaje < 40) $interes_class = 'interes-bajo';       // rojo
                              elseif($puntaje < 70) $interes_class = 'interes-medio';  // naranja
                              else $interes_class = 'interes-alto';                    // verde

                              
                              $interes_class = '';
                              if($puntaje <= 2) $interes_class = 'interes-bajo';
                              elseif($puntaje <= 3) $interes_class = 'interes-medio';
                              else $interes_class = 'interes-alto';
                              
                              // Determinar clase CSS para el canal
                              $canal_tipo = $row['canal_tipo'] ?? 'otro';
                              $canal_class = 'canal-' . $canal_tipo;
                              
                              // Determinar clase de prioridad
                              $prioridad = $row['prioridad'] ?? 'media';
                              $prioridad_class = 'prioridad-' . $prioridad;
                              
                              // Verificar si la próxima acción es hoy
                              $es_hoy = $row['proxima_accion_fecha'] == date('Y-m-d') ? 'hoy' : '';
                              
                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='lead-codigo'>" . htmlspecialchars($row['codigo_lead'] ?? '') . "</span>
                                    </td>";
                              echo "<td>
                                      <div class='lead-info'>
                                        <span class='lead-nombre'>" . htmlspecialchars($row['nombre_estudiante_completo'] ?? '') . "</span>
                                        <span class='fecha-contacto'>Nació: " . ($row['fecha_nacimiento_estudiante'] ? date('d/m/Y', strtotime($row['fecha_nacimiento_estudiante'])) : 'N/A') . "</span>
                                        <span class='fecha-contacto'>Género: " . ($row['genero_estudiante'] == 'M' ? 'Masculino' : ($row['genero_estudiante'] == 'F' ? 'Femenino' : 'N/A')) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='lead-info'>
                                        <span class='lead-nombre'>" . htmlspecialchars($row['nombre_contacto_completo'] ?? '') . "</span>
                                        <span class='lead-email'>" . htmlspecialchars($row['email'] ?? 'Sin email') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='telefono-info'>" . htmlspecialchars($row['telefono'] ?? 'Sin teléfono') . "</span>
                                        " . ($row['whatsapp'] ? "<span class='whatsapp-info'>WA: " . htmlspecialchars($row['whatsapp']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-grado'>" . 
                                   htmlspecialchars(($row['nivel_nombre'] ?? '') . ' - ' . ($row['grado_nombre'] ?? 'Sin grado')) . "</span></td>";
                              echo "<td><span class='badge badge-canal $canal_class'>" . 
                                   htmlspecialchars($row['canal_captacion'] ?? 'Sin canal') . "</span></td>";
                              echo "<td><span class='badge badge-estado' style='background-color: " . 
                                   ($row['color_estado'] ?? '#6c757d') . ";'>" . 
                                   htmlspecialchars($row['estado_lead'] ?? 'Sin estado') . "</span></td>";
                              echo "<td><span class='badge badge-prioridad $prioridad_class'>" . 
                                   ucfirst($prioridad) . "</span></td>";
                              echo "<td>
                                      <div class='puntaje-interes $interes_class'>
                                        $estrellas
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='responsable-info'>
                                        " . htmlspecialchars($row['responsable_nombre'] ?? 'Sin asignar') . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      " . ($proxima_accion ? "<span class='proxima-accion $es_hoy'>" . $proxima_accion . "</span>" : "<span class='text-muted'>Sin programar</span>") . "
                                    </td>";
                              echo "<td><span class='fecha-contacto'>" . $fecha_registro . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-lead'>
                                        <button type='button' class='btn btn-outline-info btn-consultar' 
                                                data-id='" . $row['id'] . "'
                                                title='Ver Detalles'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-editar-lead' 
                                                data-id='" . $row['id'] . "'
                                                title='Editar Lead'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-contactar' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_contacto_completo'] ?? '') . "'
                                                data-telefono='" . htmlspecialchars($row['telefono'] ?? '') . "'
                                                data-email='" . htmlspecialchars($row['email'] ?? '') . "'
                                                title='Registrar Contacto'>
                                          <i class='ti ti-phone'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='13' class='text-center'>No hay leads registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Estudiante</th>
                        <th>Contacto</th>
                        <th>Teléfono/WA</th>
                        <th>Grado</th>
                        <th>Canal</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Interés</th>
                        <th>Responsable</th>
                        <th>Próxima Acción</th>
                        <th>Registro</th>
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
    <?php include 'modals/leads/modal_nuevo.php'; ?>
    <?php include 'modals/leads/modal_editar.php'; ?>
    <?php include 'modals/leads/modal_consultar.php'; ?>
    <?php include 'modals/leads/modal_contactar.php'; ?>
    <?php include 'modals/leads/modal_duplicados.php'; ?>

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
            var table = $("#leads-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay leads disponibles en la tabla",
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
              "pageLength": 5,
              "order": [[ 0, "desc" ]], // Ordenar por ID descendente (más recientes primero)
              "columnDefs": [
                { "orderable": false, "targets": 12 } // Deshabilitar ordenación en columna de acciones
              ],
              "initComplete": function () {
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;
                    
                  // Solo aplicar filtros a las primeras 12 columnas (sin acciones)
                  if (index < 12) {
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

            // Función para exportar leads a PDF con datos filtrados
            window.exportarLeadsPDF = function() {
              var tabla = $('#leads-table').DataTable();
              var datosVisibles = [];
              
              // Obtener solo las filas visibles/filtradas
              tabla.rows({ filter: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                var data = this.data();
                var row = [];
                
                // Extraer texto limpio de cada celda (sin HTML)
                for (var i = 0; i < data.length - 1; i++) { // -1 para excluir acciones
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
              form.action = 'reports/generar_pdf_leads.php';
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

            // Función para validar duplicados globalmente
            window.validarDuplicados = function() {
              $('#modalDuplicados').modal('show');
            };

            // Manejar click en botón consultar/ver detalles
            $(document).on('click', '.btn-consultar', function() {
                var id = $(this).data('id');
                // Cargar datos del lead en modal consultar
                cargarDatosLead(id, 'consultar');
            });

            // Manejar click en botón editar lead
            $(document).on('click', '.btn-editar-lead', function() {
                var id = $(this).data('id');
                // Cargar datos del lead en modal editar
                cargarDatosLead(id, 'editar');
            });

            // Manejar click en botón contactar
            $(document).on('click', '.btn-contactar', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var telefono = $(this).data('telefono');
                var email = $(this).data('email');
                
                $('#contacto_lead_id').val(id);
                $('#contacto_nombre_lead').text(nombre);
                $('#contacto_telefono_lead').text(telefono);
                $('#contacto_email_lead').text(email);
                
                $('#modalContactar').modal('show');
            });

            // Manejar click en botón verificar duplicados individual
            $(document).on('click', '.btn-duplicados', function() {
                var id = $(this).data('id');
                var email = $(this).data('email');
                var telefono = $(this).data('telefono');
                
                if (email === '' && telefono === '') {
                    alert('Este lead no tiene suficientes datos para verificar duplicados.');
                    return;
                }
                
                // Cargar verificación de duplicados para este lead específico
                verificarDuplicadosIndividual(id, email, telefono);
            });

            // Función para cargar datos del lead
            window.cargarDatosLead = function(id, accion) {
              $.ajax({
                  url: 'acciones/leads/obtener_lead.php',
                  method: 'POST',
                  data: { id: id, accion: accion },
                  dataType: 'json',
                  success: function(response) {
                      if (response.success) {
                          const lead = response.data;
                          
                          if (accion === 'consultar') {
                              cargarModalConsultar(lead);
                          } else if (accion === 'editar') {
                              cargarModalEditar(lead);
                          }
                      } else {
                          alert('Error al cargar los datos: ' + response.message);
                      }
                  },
                  error: function() {
                      alert('Error de conexión al obtener los datos del lead.');
                  }
              });
          };

            function cargarModalConsultar(lead) {
              // Información general
              $('#view_id').text(lead.id);
              $('#view_codigo_lead').text(lead.codigo_lead || '-');
              $('#view_fecha_registro').text(lead.fecha_registro_formateada || '-');
              $('#view_estado').html(`<span class="badge" style="background-color: ${lead.color_estado || '#6c757d'};">${lead.estado_lead || 'Sin estado'}</span>`);
              $('#view_responsable').text(lead.responsable_nombre || 'Sin asignar');
              $('#view_prioridad').html(`<span class="badge badge-prioridad prioridad-${lead.prioridad}">${lead.prioridad_formateada}</span>`);
              $('#view_puntaje_interes').html(lead.estrellas_interes + ` (${lead.puntaje_interes || 0})`);
              $('#view_canal').text(lead.canal_captacion || 'Sin canal');

              // Pestaña Estudiante
              $('#view_nombres_estudiante').text(lead.nombres_estudiante || '-');
              $('#view_apellidos_estudiante').text(lead.apellidos_estudiante || '-');
              $('#view_fecha_nacimiento').text(lead.fecha_nacimiento_formateada || '-');
              $('#view_genero').text(lead.genero_formateado);
              $('#view_grado_interes').text((lead.nivel_nombre || '') + (lead.grado_nombre ? ' - ' + lead.grado_nombre : ''));
              $('#view_colegio_procedencia').text(lead.colegio_procedencia || '-');
              $('#view_motivo_cambio').text(lead.motivo_cambio || '-');

              // Pestaña Contacto
              $('#view_nombres_contacto').text(lead.nombres_contacto || '-');
              $('#view_apellidos_contacto').text(lead.apellidos_contacto || '-');
              $('#view_telefono').text(lead.telefono || '-');
              $('#view_whatsapp').text(lead.whatsapp || '-');
              $('#view_email').text(lead.email || '-');

              // Configurar enlaces de contacto
              if (lead.telefono) {
                  $('#link_telefono').attr('href', `tel:${lead.telefono}`).show();
              } else {
                  $('#link_telefono').hide();
              }

              if (lead.whatsapp) {
                  $('#link_whatsapp').attr('href', `https://wa.me/${lead.whatsapp.replace(/[^0-9]/g, '')}`).show();
              } else {
                  $('#link_whatsapp').hide();
              }

              if (lead.email) {
                  $('#link_email').attr('href', `mailto:${lead.email}`).show();
              } else {
                  $('#link_email').hide();
              }

              // Pestaña Seguimiento
              $('#view_proxima_accion_fecha').text(lead.proxima_accion_formateada || '-');
              $('#view_proxima_accion_descripcion').text(lead.proxima_accion_descripcion || '-');
              $('#view_ultima_interaccion').text(lead.fecha_ultima_interaccion_formateada || '-');
              $('#view_fecha_conversion').text(lead.fecha_conversion_formateada || '-');
              $('#view_observaciones').text(lead.observaciones || '-');

              // Pestaña Adicional
              $('#view_utm_source').text(lead.utm_source || '-');
              $('#view_utm_medium').text(lead.utm_medium || '-');
              $('#view_utm_campaign').text(lead.utm_campaign || '-');
              $('#view_ip_origen').text(lead.ip_origen || '-');
              $('#view_created_at').text(lead.fecha_registro_formateada || '-');
              $('#view_updated_at').text(lead.fecha_actualizacion_formateada || '-');
              $('#view_activo').text(lead.activo == 1 ? 'Activo' : 'Inactivo');

              $('#modalConsultar').modal('show');
            }

            function cargarModalEditar(lead) {
              // Cargar datos básicos
              $('#edit_id').val(lead.id);
              $('#edit_nombres_estudiante').val(lead.nombres_estudiante);
              $('#edit_apellidos_estudiante').val(lead.apellidos_estudiante);
              $('#edit_fecha_nacimiento_estudiante').val(lead.fecha_nacimiento_estudiante);
              $('#edit_genero_estudiante').val(lead.genero_estudiante);
              $('#edit_nombres_contacto').val(lead.nombres_contacto);
              $('#edit_apellidos_contacto').val(lead.apellidos_contacto);
              $('#edit_telefono').val(lead.telefono);
              $('#edit_whatsapp').val(lead.whatsapp);
              $('#edit_email').val(lead.email);
              $('#edit_colegio_procedencia').val(lead.colegio_procedencia);
              $('#edit_motivo_cambio').val(lead.motivo_cambio);
              $('#edit_prioridad').val(lead.prioridad);
              $('#edit_puntaje_interes').val(lead.puntaje_interes);
              $('#edit_proxima_accion_fecha').val(lead.proxima_accion_fecha);
              $('#edit_proxima_accion_descripcion').val(lead.proxima_accion_descripcion);
              $('#edit_observaciones').val(lead.observaciones);
              $('#edit_utm_source').val(lead.utm_source);
              $('#edit_utm_medium').val(lead.utm_medium);
              $('#edit_utm_campaign').val(lead.utm_campaign);
              $('#edit_fecha_conversion').val(lead.fecha_conversion);
              $('#edit_codigo_lead').val(lead.codigo_lead);

              // Cargar opciones en los selects
              if (lead.opciones) {
                  // Canales
                  let canalSelect = $('#edit_canal_captacion_id');
                  canalSelect.empty().append('<option value="">Seleccionar canal</option>');
                  lead.opciones.canales.forEach(function(canal) {
                      canalSelect.append(`<option value="${canal.id}" ${canal.id == lead.canal_captacion_id ? 'selected' : ''}>${canal.nombre}</option>`);
                  });

                  // Estados
                  let estadoSelect = $('#edit_estado_lead_id');
                  estadoSelect.empty();
                  lead.opciones.estados.forEach(function(estado) {
                      estadoSelect.append(`<option value="${estado.id}" ${estado.id == lead.estado_lead_id ? 'selected' : ''}>${estado.nombre}</option>`);
                  });

                  // Grados
                  let gradoSelect = $('#edit_grado_interes_id');
                  gradoSelect.empty().append('<option value="">Seleccionar grado</option>');
                  lead.opciones.grados.forEach(function(grado) {
                      gradoSelect.append(`<option value="${grado.id}" ${grado.id == lead.grado_interes_id ? 'selected' : ''}>${grado.nivel_nombre} - ${grado.nombre}</option>`);
                  });

                  // Usuarios
                  let usuarioSelect = $('#edit_responsable_id');
                  usuarioSelect.empty().append('<option value="">Sin asignar</option>');
                  lead.opciones.usuarios.forEach(function(usuario) {
                      usuarioSelect.append(`<option value="${usuario.id}" ${usuario.id == lead.responsable_id ? 'selected' : ''}>${usuario.nombre_completo}</option>`);
                  });
              }

              $('#modalEditar').modal('show');
            }

            // Función para verificar duplicados individual
            function verificarDuplicadosIndividual(id, email, telefono) {
              $.ajax({
                url: 'acciones/leads/verificar_duplicados.php',
                method: 'POST',
                data: { 
                  lead_id: id, 
                  email: email, 
                  telefono: telefono 
                },
                dataType: 'json',
                success: function(response) {
                  if (response.duplicados && response.duplicados.length > 0) {
                    // Mostrar duplicados encontrados
                    var mensaje = 'Se encontraron ' + response.duplicados.length + ' posibles duplicados:\n\n';
                    response.duplicados.forEach(function(dup, index) {
                      mensaje += (index + 1) + '. ID: ' + dup.id + ' - ' + dup.nombre + ' (' + dup.motivo + ')\n';
                    });
                    alert(mensaje);
                  } else {
                    alert('No se encontraron duplicados para este lead.');
                  }
                },
                error: function() {
                  alert('Error al verificar duplicados.');
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