<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar filtros si existen
$where_conditions = ["1=1"];
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$tipo_mensaje = $_GET['tipo_mensaje'] ?? '';
$estado_mensaje = $_GET['estado_mensaje'] ?? '';
$contacto_filtro = $_GET['contacto_filtro'] ?? '';

if (!empty($fecha_inicio)) {
    $where_conditions[] = "DATE(me.created_at) >= '" . $conn->real_escape_string($fecha_inicio) . "'";
}
if (!empty($fecha_fin)) {
    $where_conditions[] = "DATE(me.created_at) <= '" . $conn->real_escape_string($fecha_fin) . "'";
}
if (!empty($tipo_mensaje)) {
    $where_conditions[] = "me.tipo = '" . $conn->real_escape_string($tipo_mensaje) . "'";
}
if (!empty($estado_mensaje)) {
    $where_conditions[] = "me.estado = '" . $conn->real_escape_string($estado_mensaje) . "'";
}
if (!empty($contacto_filtro)) {
    $where_conditions[] = "(me.destinatario_email LIKE '%" . $conn->real_escape_string($contacto_filtro) . "%' 
                           OR me.destinatario_telefono LIKE '%" . $conn->real_escape_string($contacto_filtro) . "%'
                           OR CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) LIKE '%" . $conn->real_escape_string($contacto_filtro) . "%'
                           OR CONCAT(a.nombres, ' ', a.apellidos) LIKE '%" . $conn->real_escape_string($contacto_filtro) . "%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Consulta para obtener el historial de comunicaciones con información de tablas relacionadas
$sql = "SELECT 
    me.id,
    me.tipo,
    me.plantilla_id,
    pm.nombre as plantilla_nombre,
    pm.categoria as plantilla_categoria,
    me.lead_id,
    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as lead_nombre,
    CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as lead_contacto,
    me.apoderado_id,
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
    f.apellido_principal as familia_apellido,
    me.destinatario_email,
    me.destinatario_telefono,
    me.asunto,
    LEFT(me.contenido, 200) as contenido_preview,
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
    CASE 
        WHEN me.lead_id IS NOT NULL THEN CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante, ' (', l.nombres_contacto, ' ', l.apellidos_contacto, ')')
        WHEN me.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos, ' - Fam. ', f.apellido_principal)
        ELSE COALESCE(me.destinatario_email, me.destinatario_telefono)
    END as destinatario_completo,
    -- Calcular tiempo de entrega
    CASE 
        WHEN me.fecha_entrega IS NOT NULL AND me.fecha_envio IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, me.fecha_envio, me.fecha_entrega)
        ELSE NULL
    END as tiempo_entrega_minutos,
    -- Calcular tiempo hasta lectura
    CASE 
        WHEN me.fecha_lectura IS NOT NULL AND me.fecha_envio IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, me.fecha_envio, me.fecha_lectura)
        ELSE NULL
    END as tiempo_lectura_minutos
FROM mensajes_enviados me
LEFT JOIN plantillas_mensajes pm ON me.plantilla_id = pm.id
LEFT JOIN leads l ON me.lead_id = l.id
LEFT JOIN apoderados a ON me.apoderado_id = a.id
LEFT JOIN familias f ON a.familia_id = f.id
WHERE $where_clause
ORDER BY me.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas del historial para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_mensajes,
    COUNT(CASE WHEN estado = 'enviado' THEN 1 END) as mensajes_enviados,
    COUNT(CASE WHEN estado = 'entregado' THEN 1 END) as mensajes_entregados,
    COUNT(CASE WHEN estado = 'leido' THEN 1 END) as mensajes_leidos,
    COUNT(CASE WHEN estado = 'fallido' THEN 1 END) as mensajes_fallidos,
    COUNT(CASE WHEN tipo = 'email' THEN 1 END) as total_emails,
    COUNT(CASE WHEN tipo = 'whatsapp' THEN 1 END) as total_whatsapp,
    COUNT(CASE WHEN tipo = 'sms' THEN 1 END) as total_sms,
    SUM(costo) as costo_total,
    ROUND(AVG(CASE WHEN estado IN ('entregado', 'leido') THEN 1 ELSE 0 END) * 100, 2) as tasa_entrega,
    ROUND(AVG(CASE WHEN estado = 'leido' THEN 1 ELSE 0 END) * 100, 2) as tasa_lectura,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as mensajes_hoy,
    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as mensajes_semana
FROM mensajes_enviados me
WHERE $where_clause";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas de fallos por tipo
$fallos_sql = "SELECT 
    tipo,
    COUNT(*) as total_fallos,
    COUNT(CASE WHEN error_mensaje LIKE '%timeout%' OR error_mensaje LIKE '%conexion%' THEN 1 END) as fallos_conexion,
    COUNT(CASE WHEN error_mensaje LIKE '%invalid%' OR error_mensaje LIKE '%formato%' THEN 1 END) as fallos_formato,
    COUNT(CASE WHEN error_mensaje LIKE '%quota%' OR error_mensaje LIKE '%limit%' THEN 1 END) as fallos_limite
FROM mensajes_enviados me
WHERE estado = 'fallido' AND $where_clause
GROUP BY tipo";

$fallos_result = $conn->query($fallos_sql);
$fallos_stats = [];
while($fallo = $fallos_result->fetch_assoc()) {
    $fallos_stats[$fallo['tipo']] = $fallo;
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
    <title>Historial de Comunicaciones - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Historial de Comunicaciones"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Comunicaciones, Historial, Reportes, Análisis"
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
    
    <!-- Custom styles for historial comunicaciones -->
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
      .estado-leido { 
        background-color: #17a2b8; 
        position: relative;
      }
      .estado-leido::after {
        content: '👁';
        margin-left: 4px;
      }
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
        font-size: 0.85rem;
      }
      
      .mensaje-preview {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
        max-width: 250px;
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
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .destinatario-contacto {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .tiempo-entrega {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        border-radius: 8px;
        font-weight: 500;
      }
      
      .entrega-rapida {
        background-color: #d4edda;
        color: #155724;
      }
      
      .entrega-normal {
        background-color: #fff3cd;
        color: #856404;
      }
      
      .entrega-lenta {
        background-color: #f8d7da;
        color: #721c24;
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
      
      .error-detalle {
        font-size: 0.7rem;
        color: #dc3545;
        background-color: #f8d7da;
        padding: 2px 4px;
        border-radius: 3px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: pointer;
      }
      
      .plantilla-usado {
        font-size: 0.7rem;
        background-color: #e8f4fd;
        color: #0c5460;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #bee5eb;
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
      
      .filtros-panel {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
      }
      
      .btn-grupo-historial {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-historial .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .metricas-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .metrica-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
      }

      .metrica-item:last-child {
        border-bottom: none;
      }

      .metrica-valor {
        font-weight: bold;
        color: #495057;
      }

      .tasa-exitosa { color: #28a745; }
      .tasa-regular { color: #ffc107; }
      .tasa-baja { color: #dc3545; }
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
                    Historial de Comunicaciones
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
                    Historial de Comunicaciones
                  </h3>
                  <small class="text-muted">
                    Consulta el historial completo de mensajes enviados con métricas de entrega y análisis detallado.
                    <?php if($fecha_inicio || $fecha_fin || $tipo_mensaje || $estado_mensaje || $contacto_filtro): ?>
                      <strong>(Filtros aplicados)</strong>
                    <?php endif; ?>
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEstadoEntrega">
                    <i class="ti ti-truck me-1"></i>
                    Estado Entrega
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalConsultarContacto">
                    <i class="fas fa-user me-1"></i>
                    Consultar por Contacto
                  </button> -->
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarLogsPDF()">
                    <i class="fas fa-file-pdf me-1"></i>
                    Generar PDF
                  </button>
                </div>
              </div>
              
              <div class="card-body">

                <!-- Tabla de historial -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="historial-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="6%">Tipo</th>
                        <th width="16%">Mensaje</th>
                        <th width="16%">Destinatario</th>
                        <th width="8%">Plantilla</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Fecha Envío</th>
                        <th width="8%">Tiempo Entrega</th>
                        <th width="6%">Costo</th>
                        <th width="10%">Error/Observaciones</th>
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
                              $fecha_lectura = $row['fecha_lectura'] ? date('d/m/Y H:i', strtotime($row['fecha_lectura'])) : '';
                              
                              // Determinar clase CSS para el tipo
                              $tipo_class = 'tipo-' . $row['tipo'];
                              
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . $row['estado'];
                              
                              // Calcular tiempo de entrega
                              $tiempo_entrega = '';
                              if ($row['tiempo_entrega_minutos'] !== null) {
                                  $minutos = (int)$row['tiempo_entrega_minutos'];
                                  if ($minutos < 5) {
                                      $tiempo_class = 'entrega-rapida';
                                      $tiempo_entrega = $minutos . ' min';
                                  } elseif ($minutos < 30) {
                                      $tiempo_class = 'entrega-normal';
                                      $tiempo_entrega = $minutos . ' min';
                                  } else {
                                      $tiempo_class = 'entrega-lenta';
                                      $tiempo_entrega = $minutos > 60 ? round($minutos/60, 1) . ' hrs' : $minutos . ' min';
                                  }
                              } else {
                                  $tiempo_class = '';
                                  $tiempo_entrega = '-';
                              }
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td><span class='badge badge-tipo $tipo_class'>" . 
                                   strtoupper($row['tipo']) . "</span></td>";
                              echo "<td>
                                      <div class='mensaje-info'>
                                        <span class='mensaje-asunto'>" . htmlspecialchars($row['asunto'] ?? 'Sin asunto') . "</span>
                                        <span class='mensaje-preview'>" . htmlspecialchars($row['contenido_preview']) . "...</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='destinatario-info'>
                                        <span class='badge badge-destinatario'>" . $row['tipo_destinatario'] . "</span>
                                        <span class='destinatario-nombre' title='" . htmlspecialchars($row['destinatario_completo']) . "'>" . 
                                        htmlspecialchars($row['destinatario_completo']) . "</span>
                                        <span class='destinatario-contacto'>" . 
                                        htmlspecialchars($row['destinatario_email'] ?? $row['destinatario_telefono'] ?? '') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>" . 
                                   ($row['plantilla_nombre'] ? "<span class='plantilla-usado'>" . htmlspecialchars($row['plantilla_nombre']) . "</span>" : "<span class='text-muted'>Manual</span>") . 
                                   "</td>";
                              echo "<td><span class='badge badge-estado $estado_class'>" . 
                                   ucfirst($row['estado']) . "</span></td>";
                              echo "<td>
                                      <div class='fecha-envio'>
                                        <strong>" . $fecha_envio . "</strong>" .
                                        ($fecha_entrega ? "<br><small>Entregado: " . $fecha_entrega . "</small>" : "") .
                                        ($fecha_lectura ? "<br><small>Leído: " . $fecha_lectura . "</small>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>" . 
                                   ($tiempo_entrega != '-' ? "<span class='tiempo-entrega $tiempo_class'>" . $tiempo_entrega . "</span>" : 
                                   "<span class='text-muted'>-</span>") . "</td>";
                              echo "<td>" . 
                                   ($row['costo'] > 0 ? "<span class='costo-mensaje'>S/ " . number_format($row['costo'], 4) . "</span>" : 
                                   "<span class='text-muted'>Gratis</span>") . "</td>";
                              echo "<td>" . 
                                   ($row['error_mensaje'] ? "<span class='error-detalle' title='" . htmlspecialchars($row['error_mensaje']) . "'>Ver Error</span>" : 
                                   ($row['mensaje_id_externo'] ? "<span class='text-muted' title='ID: " . htmlspecialchars($row['mensaje_id_externo']) . "'>ID: " . substr($row['mensaje_id_externo'], 0, 8) . "...</span>" :
                                   "<span class='text-muted'>-</span>")) . "</td>";
                              echo "<td>
                                      <div class='btn-grupo-historial'>
                                        <button type='button' class='btn btn-outline-info btn-ver-completo' 
                                                data-id='" . $row['id'] . "'
                                                title='Ver Mensaje Completo'>
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
                          echo "<tr><td colspan='11' class='text-center'>No hay mensajes en el historial" . 
                               ($fecha_inicio || $fecha_fin || $tipo_mensaje || $estado_mensaje || $contacto_filtro ? " con los filtros aplicados" : "") . "</td></tr>";
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
                        <th>Tiempo Entrega</th>
                        <th>Costo</th>
                        <th>Error/Observaciones</th>
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
    <?php include 'modals/comunicaciones/modal_consultar_contacto.php'; ?>
    <?php include 'modals/comunicaciones/modal_filtrar_fechas.php'; ?>
    <?php include 'modals/comunicaciones/modal_estado_entrega.php'; ?>

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
            var table = $("#historial-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay mensajes disponibles en el historial",
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

            $(document).on('click', '.btn-ver-completo', function() {
              var mensajeId = $(this).data('id');
              cargarMensajeCompleto(mensajeId);
            });

            // Función para cargar mensaje completo
            function cargarMensajeCompleto(id) {
              $.ajax({
                url: 'actions/obtener_mensaje_completo.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarMensajeCompleto(response.data);
                  } else {
                    alert('Error al cargar el mensaje: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener el mensaje completo.');
                }
              });
            }

            // Función para reenviar mensaje
            function reenviarMensaje(id) {
              $.ajax({
                url: 'actions/reenviar_mensaje_historial.php',
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

            // Función para mostrar mensaje completo
            function mostrarMensajeCompleto(data) {
              var modalHTML = `
                <div class="modal fade" id="modalMensajeCompleto" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Mensaje Completo - ID: ${data.id}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-md-6">
                            <strong>Tipo:</strong> ${data.tipo.toUpperCase()}<br>
                            <strong>Estado:</strong> ${data.estado}<br>
                            <strong>Destinatario:</strong> ${data.destinatario}<br>
                            <strong>Fecha Envío:</strong> ${data.fecha_envio || 'No enviado'}
                          </div>
                          <div class="col-md-6">
                            <strong>Asunto:</strong> ${data.asunto || 'Sin asunto'}<br>
                            <strong>Costo:</strong> S/ ${data.costo || '0.00'}<br>
                            <strong>Plantilla:</strong> ${data.plantilla || 'Manual'}<br>
                            <strong>ID Externo:</strong> ${data.mensaje_id_externo || 'N/A'}
                          </div>
                        </div>
                        <hr>
                        <div>
                          <strong>Contenido:</strong>
                          <div class="border p-3 mt-2" style="max-height: 300px; overflow-y: auto;">
                            ${data.contenido}
                          </div>
                        </div>
                        ${data.error_mensaje ? `<div class="alert alert-danger mt-3"><strong>Error:</strong> ${data.error_mensaje}</div>` : ''}
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                      </div>
                    </div>
                  </div>
                </div>
              `;
              
              // Remover modal anterior si existe
              $('#modalMensajeCompleto').remove();
              // Agregar nuevo modal
              $('body').append(modalHTML);
              // Mostrar modal
              $('#modalMensajeCompleto').modal('show');
            }

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
              form.action = 'reports/generar_pdf_apoderados.php';
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


            // Tooltip para elementos con title
            $('[title]').tooltip();

            // Auto-actualización cada 5 minutos para mensajes en proceso
            setInterval(function() {
              var mensajesPendientes = $('.estado-pendiente, .estado-enviado').length;
              if (mensajesPendientes > 0) {
                // Solo recargar si hay mensajes en proceso y no han cambiado los filtros
                var urlActual = window.location.href;
                if (urlActual === window.location.href) {
                  location.reload();
                }
              }
            }, 300000); // 5 minutos
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