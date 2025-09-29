<?php
session_start();
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    // Redirigir todas las acciones al archivo de procesamiento
    header('Location: acciones/campanas_exalumnos.php');
    exit();
}

// Consulta principal para obtener campañas/mensajes con información relacionada
$sql = "SELECT 
    m.id,
    m.tipo,
    m.asunto,
    m.contenido,
    m.estado,
    m.fecha_envio,
    m.fecha_entrega,
    m.fecha_lectura,
    m.destinatario_email,
    m.destinatario_telefono,
    m.costo,
    m.created_at,
    -- Información de plantilla
    p.nombre as plantilla_nombre,
    p.categoria as plantilla_categoria,
    -- Información de exalumno (si coincide por email o teléfono)
    ex.id as exalumno_id,
    ex.nombres as exalumno_nombres,
    ex.apellidos as exalumno_apellidos,
    ex.codigo_exalumno,
    ex.promocion_egreso,
    ex.estado_contacto,
    -- Información de apoderado (si tiene)
    CONCAT(a.nombres, ' ', a.apellidos) as apoderado_nombre,
    -- Clasificar tipo de campaña por contenido y asunto
    CASE 
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%networking%' OR LOWER(m.contenido) LIKE '%networking%' THEN 'Networking'
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%evento%' OR LOWER(m.contenido) LIKE '%evento%' OR LOWER(IFNULL(m.asunto, '')) LIKE '%invit%' THEN 'Evento'
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%agradecimiento%' OR LOWER(m.contenido) LIKE '%gracias%' THEN 'Agradecimiento'
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%boletin%' OR LOWER(IFNULL(m.asunto, '')) LIKE '%newsletter%' THEN 'Boletín'
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%reunion%' OR LOWER(m.contenido) LIKE '%reunion%' THEN 'Reunión'
        WHEN LOWER(IFNULL(m.asunto, '')) LIKE '%reconocimiento%' OR LOWER(m.contenido) LIKE '%felicit%' THEN 'Reconocimiento'
        ELSE 'General'
    END as tipo_campana,
    -- Calcular días desde envío
    IFNULL(DATEDIFF(CURDATE(), DATE(m.fecha_envio)), 0) as dias_desde_envio,
    -- Determinar efectividad
    CASE 
        WHEN m.estado = 'leido' THEN 'Alta'
        WHEN m.estado = 'entregado' THEN 'Media'
        WHEN m.estado = 'enviado' THEN 'Baja'
        WHEN m.estado = 'fallido' THEN 'Fallida'
        ELSE 'Pendiente'
    END as efectividad
FROM mensajes_enviados m
LEFT JOIN plantillas_mensajes p ON m.plantilla_id = p.id
LEFT JOIN apoderados a ON m.apoderado_id = a.id
LEFT JOIN exalumnos ex ON (
    (m.destinatario_email IS NOT NULL AND m.destinatario_email = ex.email) OR
    (m.destinatario_telefono IS NOT NULL AND m.destinatario_telefono = ex.telefono)
)
ORDER BY 
    CASE m.estado 
        WHEN 'pendiente' THEN 1
        WHEN 'enviado' THEN 2
        WHEN 'entregado' THEN 3
        WHEN 'leido' THEN 4
        WHEN 'fallido' THEN 5
        ELSE 6
    END,
    m.created_at DESC
LIMIT 100";

$result = $conn->query($sql);

// Obtener estadísticas generales de campañas
$stats_sql = "SELECT 
    COUNT(*) as total_mensajes,
    COUNT(CASE WHEN estado = 'enviado' THEN 1 END) as enviados,
    COUNT(CASE WHEN estado = 'entregado' THEN 1 END) as entregados,
    COUNT(CASE WHEN estado = 'leido' THEN 1 END) as leidos,
    COUNT(CASE WHEN estado = 'fallido' THEN 1 END) as fallidos,
    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
    COUNT(CASE WHEN tipo = 'email' THEN 1 END) as emails,
    COUNT(CASE WHEN tipo = 'whatsapp' THEN 1 END) as whatsapp,
    COUNT(CASE WHEN tipo = 'sms' THEN 1 END) as sms,
    IFNULL(SUM(costo), 0) as costo_total,
    IFNULL(AVG(CASE WHEN estado = 'leido' THEN 1 ELSE 0 END) * 100, 0) as tasa_lectura
FROM mensajes_enviados";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas por tipo de campaña
$campanas_sql = "SELECT 
    CASE 
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%networking%' OR LOWER(contenido) LIKE '%networking%' THEN 'Networking'
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%evento%' OR LOWER(contenido) LIKE '%evento%' OR LOWER(IFNULL(asunto, '')) LIKE '%invit%' THEN 'Evento'
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%agradecimiento%' OR LOWER(contenido) LIKE '%gracias%' THEN 'Agradecimiento'
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%boletin%' OR LOWER(IFNULL(asunto, '')) LIKE '%newsletter%' THEN 'Boletín'
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%reunion%' OR LOWER(contenido) LIKE '%reunion%' THEN 'Reunión'
        WHEN LOWER(IFNULL(asunto, '')) LIKE '%reconocimiento%' OR LOWER(contenido) LIKE '%felicit%' THEN 'Reconocimiento'
        ELSE 'General'
    END as tipo_campana,
    COUNT(*) as cantidad,
    COUNT(CASE WHEN estado = 'leido' THEN 1 END) as exito
FROM mensajes_enviados 
GROUP BY tipo_campana
ORDER BY cantidad DESC
LIMIT 10";

$campanas_result = $conn->query($campanas_sql);
$campanas_stats = [];
while($campana = $campanas_result->fetch_assoc()) {
    $campanas_stats[] = $campana;
}

// Obtener estadísticas por promoción de exalumnos
$promociones_sql = "SELECT 
    ex.promocion_egreso,
    COUNT(m.id) as mensajes_enviados,
    COUNT(CASE WHEN m.estado = 'leido' THEN 1 END) as mensajes_leidos
FROM exalumnos ex
LEFT JOIN mensajes_enviados m ON (
    (m.destinatario_email = ex.email) OR (m.destinatario_telefono = ex.telefono)
)
WHERE ex.promocion_egreso IS NOT NULL 
GROUP BY ex.promocion_egreso
HAVING mensajes_enviados > 0
ORDER BY ex.promocion_egreso DESC
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
    <title>Campañas para Exalumnos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Campañas para Exalumnos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Exalumnos, Campañas, Marketing, Comunicación"
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
    
    <!-- Custom styles for campañas exalumnos -->
    <style>
      .badge-tipo-mensaje {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-email { background-color: #dc3545; }
      .tipo-whatsapp { background-color: #25d366; }
      .tipo-sms { background-color: #6f42c1; }
      
      .badge-estado-mensaje {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .estado-pendiente { background-color: #6c757d; }
      .estado-enviado { background-color: #007bff; }
      .estado-entregado { background-color: #ffc107; color: #856404; }
      .estado-leido { background-color: #28a745; }
      .estado-fallido { background-color: #dc3545; }
      
      .badge-tipo-campana {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: 500;
      }
      .campana-networking { background-color: #17a2b8; color: white; }
      .campana-evento { background-color: #e83e8c; color: white; }
      .campana-agradecimiento { background-color: #28a745; color: white; }
      .campana-boletin { background-color: #6f42c1; color: white; }
      .campana-reunion { background-color: #fd7e14; color: white; }
      .campana-reconocimiento { background-color: #ffc107; color: #856404; }
      .campana-general { background-color: #6c757d; color: white; }
      
      .badge-efectividad {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: bold;
      }
      .efectividad-alta { background-color: #d4edda; color: #155724; }
      .efectividad-media { background-color: #fff3cd; color: #856404; }
      .efectividad-baja { background-color: #d1ecf1; color: #0c5460; }
      .efectividad-fallida { background-color: #f8d7da; color: #721c24; }
      .efectividad-pendiente { background-color: #e2e3e5; color: #6c757d; }
      
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
      
      .mensaje-contenido {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .mensaje-plantilla {
        font-size: 0.7rem;
        color: #495057;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
      }
      
      .exalumno-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .exalumno-nombre {
        font-weight: 500;
        color: #495057;
      }
      
      .exalumno-codigo {
        color: #6c757d;
        font-family: 'Courier New', monospace;
      }
      
      .exalumno-promocion {
        color: #28a745;
        font-size: 0.7rem;
        font-weight: 500;
      }
      
      .destinatario-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .destinatario-email {
        color: #495057;
      }
      
      .destinatario-telefono {
        color: #6c757d;
      }
      
      .fechas-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .fecha-envio {
        font-weight: 500;
        color: #495057;
      }
      
      .fecha-entrega {
        color: #6c757d;
      }
      
      .fecha-lectura {
        color: #28a745;
        font-weight: 500;
      }
      
      .dias-info {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .dias-reciente { background-color: #d4edda; color: #155724; }
      .dias-medio { background-color: #fff3cd; color: #856404; }
      .dias-antiguo { background-color: #f8d7da; color: #721c24; }
      
      .costo-info {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: bold;
        color: #28a745;
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
      
      .campanas-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .campana-item {
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
      
      .btn-grupo-campana {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-campana .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
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
                    <a href="javascript: void(0)">Gestión de Exalumnos</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Campañas para Exalumnos
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
                    Campañas para Exalumnos
                  </h3>
                  <small class="text-muted">
                    Gestiona comunicaciones y campañas dirigidas a exalumnos. 
                    Crea campañas de networking, eventos, boletines y mantén contacto activo.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" onclick="medirResultados()">
                    <i class="ti ti-chart-bar me-1"></i>
                    Medir Resultados
                  </button> -->
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalEnviarComunicacion">
                    <i class="ti ti-send me-1"></i>
                    Enviar Comunicación
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearCampana">
                    <i class="ti ti-plus me-1"></i>
                    Crear Campaña
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de campañas -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="campanas-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="18%">Mensaje</th>
                        <th width="15%">Exalumno</th>
                        <th width="12%">Destinatario</th>
                        <th width="8%">Estado</th>
                        <th width="8%">Efectividad</th>
                        <th width="12%">Fechas</th>
                        <th width="6%">Costo</th>
                        <th width="8%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clases CSS
                              $tipo_class = 'tipo-' . $row['tipo'];
                              $estado_class = 'estado-' . $row['estado'];
                              $campana_class = 'campana-' . strtolower(str_replace(' ', '', $row['tipo_campana']));
                              $efectividad_class = 'efectividad-' . strtolower($row['efectividad']);
                              
                              // Formatear fechas
                              $fecha_envio = $row['fecha_envio'] ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '-';
                              $fecha_entrega = $row['fecha_entrega'] ? date('d/m/Y H:i', strtotime($row['fecha_entrega'])) : '-';
                              $fecha_lectura = $row['fecha_lectura'] ? date('d/m/Y H:i', strtotime($row['fecha_lectura'])) : '-';
                              
                              // Calcular días desde envío
                              $dias = (int)$row['dias_desde_envio'];
                              $dias_class = '';
                              if ($dias <= 1) $dias_class = 'dias-reciente';
                              elseif ($dias <= 7) $dias_class = 'dias-medio';
                              else $dias_class = 'dias-antiguo';
                              
                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='badge badge-tipo-campana $campana_class'>" . $row['tipo_campana'] . "</span>
                                    </td>";
                              echo "<td><span class='badge badge-tipo-mensaje $tipo_class'>" . 
                                   strtoupper($row['tipo']) . "</span></td>";
                              echo "<td>
                                      <div class='mensaje-info'>
                                        <span class='mensaje-asunto'>" . htmlspecialchars($row['asunto'] ?? 'Sin asunto') . "</span>
                                        <span class='mensaje-contenido' title='" . htmlspecialchars($row['contenido']) . "'>" . 
                                        htmlspecialchars(substr($row['contenido'], 0, 50)) . "...</span>
                                        " . ($row['plantilla_nombre'] ? "<span class='mensaje-plantilla'>" . htmlspecialchars($row['plantilla_nombre']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>";
                              if ($row['exalumno_id']) {
                                  echo "<div class='exalumno-info'>
                                          <span class='exalumno-nombre'>" . htmlspecialchars($row['exalumno_nombres'] . ' ' . $row['exalumno_apellidos']) . "</span>
                                          <span class='exalumno-codigo'>" . htmlspecialchars($row['codigo_exalumno']) . "</span>
                                          <span class='exalumno-promocion'>Promoción " . htmlspecialchars($row['promocion_egreso'] ?? 'S/D') . "</span>
                                        </div>";
                              } else {
                                  echo "<span class='text-muted'>No identificado</span>";
                              }
                              echo "</td>";
                              echo "<td>
                                      <div class='destinatario-info'>
                                        <span class='destinatario-email'>" . 
                                        ($row['destinatario_email'] ? htmlspecialchars($row['destinatario_email']) : 'Sin email') . "</span>
                                        <span class='destinatario-telefono'>" . 
                                        ($row['destinatario_telefono'] ? htmlspecialchars($row['destinatario_telefono']) : 'Sin teléfono') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-mensaje $estado_class'>" . 
                                   ucfirst($row['estado']) . "</span></td>";
                              echo "<td><span class='badge badge-efectividad $efectividad_class'>" . 
                                   $row['efectividad'] . "</span></td>";
                              echo "<td>
                                      <div class='fechas-info'>
                                        <span class='fecha-envio'>Envío: " . $fecha_envio . "</span>
                                        " . ($row['fecha_entrega'] ? "<span class='fecha-entrega'>Entrega: " . $fecha_entrega . "</span>" : "") . "
                                        " . ($row['fecha_lectura'] ? "<span class='fecha-lectura'>Lectura: " . $fecha_lectura . "</span>" : "") . "
                                        <span class='dias-info $dias_class'>Hace " . $dias . " días</span>
                                      </div>
                                    </td>";
                              echo "<td>" . 
                                   ($row['costo'] > 0 ? "<span class='costo-info'>S/ " . number_format((float)$row['costo'], 4) . "</span>" : 
                                   "<span class='text-success'>Gratuito</span>") . "</td>";
                              echo "<td>
                                      <div class='btn-grupo-campana'>
                                        <button type='button' class='btn btn-outline-success btn-reenviar' 
                                                data-id='" . $row['id'] . "'
                                                data-tipo='" . $row['tipo'] . "'
                                                title='Reenviar'>
                                          <i class='ti ti-refresh'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-info btn-ver-detalles' 
                                                data-id='" . $row['id'] . "'
                                                data-asunto='" . htmlspecialchars($row['asunto'] ?? '') . "'
                                                title='Ver Detalles'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-analizar' 
                                                data-id='" . $row['id'] . "'
                                                data-estado='" . $row['estado'] . "'
                                                title='Analizar Resultado'>
                                          <i class='ti ti-chart-line'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='10' class='text-center'>No hay campañas registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Mensaje</th>
                        <th>Exalumno</th>
                        <th>Destinatario</th>
                        <th>Estado</th>
                        <th>Efectividad</th>
                        <th>Fechas</th>
                        <th>Costo</th>
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
    <?php include 'modals/campanas_exalumnos/modal_crear_campana.php'; ?>
    <?php include 'modals/campanas_exalumnos/modal_enviar_comunicacion.php'; ?>


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
            var table = $("#campanas-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay campañas disponibles en la tabla",
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
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;
                  
                  // Solo aplicar filtros a las primeras 9 columnas (sin acciones)
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
                    // Agregar "ACCIONES" en negrita en la columna de acciones
                    $(column.footer()).html('<strong>Acciones</strong>');
                  }
                });
              }
            });

            // Función para medir resultados globales
            window.medirResultados = function() {
              Swal.fire({
                title: '¿Generar Reporte de Resultados?',
                text: 'Se analizarán todas las campañas y se generará un reporte detallado',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, generar',
                cancelButtonText: 'Cancelar'
              }).then((result) => {
                if (result.isConfirmed) {
                  $('#modalMedirResultados').modal('show');
                }
              });
            };

            // Manejar click en botón reenviar
            $(document).on('click', '.btn-reenviar', function() {
                var id = $(this).data('id');
                var tipo = $(this).data('tipo');
                
                Swal.fire({
                  title: '¿Reenviar Mensaje?',
                  text: 'Se reenviará el mensaje ' + tipo.toUpperCase() + ' al mismo destinatario',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#28a745',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, reenviar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Aquí iría la lógica para reenviar
                    Swal.fire(
                      'Reenviado',
                      'El mensaje ha sido reenviado exitosamente',
                      'success'
                    );
                  }
                });
            });

            // Manejar click en botón ver detalles
            $(document).on('click', '.btn-ver-detalles', function() {
                var id = $(this).data('id');
                var asunto = $(this).data('asunto');
                
                Swal.fire({
                  title: 'Detalles del Mensaje',
                  text: 'Asunto: ' + asunto,
                  icon: 'info',
                  confirmButtonText: 'Cerrar'
                });
            });

            // Manejar click en botón analizar
            $(document).on('click', '.btn-analizar', function() {
                var id = $(this).data('id');
                var estado = $(this).data('estado');
                
                Swal.fire({
                  title: 'Análisis de Resultado',
                  text: 'Estado actual: ' + estado.toUpperCase(),
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonColor: '#ffc107',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Ver Análisis Completo',
                  cancelButtonText: 'Cerrar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Aquí iría la lógica para mostrar análisis detallado
                    Swal.fire(
                      'Análisis Completo',
                      'Funcionalidad de análisis detallado será implementada próximamente',
                      'info'
                    );
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