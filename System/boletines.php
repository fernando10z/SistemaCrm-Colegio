<?php
session_start();

// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los boletines con información de tablas relacionadas
$sql = "SELECT 
    pm.id,
    pm.nombre,
    pm.tipo,
    pm.asunto,
    LEFT(pm.contenido, 200) as contenido_preview,
    pm.variables_disponibles,
    pm.categoria,
    pm.activo,
    pm.created_at,
    pm.updated_at,
    -- Estadísticas de uso
    COUNT(me.id) as total_envios,
    COUNT(CASE WHEN me.estado = 'enviado' OR me.estado = 'entregado' THEN 1 END) as envios_exitosos,
    COUNT(CASE WHEN me.estado = 'leido' THEN 1 END) as aperturas,
    COUNT(CASE WHEN me.estado = 'fallido' THEN 1 END) as fallos,
    MAX(me.fecha_envio) as ultimo_envio,
    COUNT(CASE WHEN DATE(me.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as envios_ultimo_mes,
    -- Calcular métricas
    CASE 
        WHEN COUNT(me.id) > 0 THEN ROUND((COUNT(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 END) / COUNT(me.id)) * 100, 2)
        ELSE 0
    END as tasa_entrega,
    CASE 
        WHEN COUNT(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 END) > 0 
        THEN ROUND((COUNT(CASE WHEN me.estado = 'leido' THEN 1 END) / COUNT(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 END)) * 100, 2)
        ELSE 0
    END as tasa_apertura,
    -- Clasificar popularidad
    CASE 
        WHEN COUNT(me.id) >= 100 THEN 'alta'
        WHEN COUNT(me.id) >= 50 THEN 'media'
        WHEN COUNT(me.id) > 0 THEN 'baja'
        ELSE 'sin_uso'
    END as popularidad,
    -- Determinar nivel educativo del contenido
    CASE 
        WHEN pm.categoria LIKE '%inicial%' THEN 'Inicial'
        WHEN pm.categoria LIKE '%primaria%' THEN 'Primaria'
        WHEN pm.categoria LIKE '%secundaria%' THEN 'Secundaria'
        ELSE 'General'
    END as nivel_objetivo
FROM plantillas_mensajes pm
LEFT JOIN mensajes_enviados me ON pm.id = me.plantilla_id
WHERE pm.tipo = 'email' AND (pm.categoria LIKE '%boletin%' OR pm.categoria LIKE '%newsletter%' OR pm.categoria LIKE '%informativo%' OR pm.nombre LIKE '%Boletín%' OR pm.nombre LIKE '%Newsletter%')
GROUP BY pm.id, pm.nombre, pm.tipo, pm.asunto, pm.contenido, pm.variables_disponibles, pm.categoria, pm.activo, pm.created_at, pm.updated_at
ORDER BY pm.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de boletines para mostrar
$stats_sql = "SELECT 
    COUNT(DISTINCT pm.id) as total_boletines,
    COUNT(DISTINCT CASE WHEN pm.activo = 1 THEN pm.id END) as boletines_activos,
    COUNT(me.id) as total_envios_boletines,
    COUNT(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 END) as envios_exitosos,
    COUNT(CASE WHEN me.estado = 'leido' THEN 1 END) as total_aperturas,
    COUNT(CASE WHEN DATE(me.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as envios_semana,
    COUNT(CASE WHEN DATE(pm.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as boletines_nuevos_mes,
    ROUND(AVG(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 ELSE 0 END) * 100, 2) as tasa_entrega_promedio,
    ROUND(AVG(CASE WHEN me.estado = 'leido' AND me.estado IN ('entregado', 'leido') THEN 1 ELSE 0 END) * 100, 2) as tasa_apertura_promedio
FROM plantillas_mensajes pm
LEFT JOIN mensajes_enviados me ON pm.id = me.plantilla_id
WHERE pm.tipo = 'email' AND (pm.categoria LIKE '%boletin%' OR pm.categoria LIKE '%newsletter%' OR pm.categoria LIKE '%informativo%' OR pm.nombre LIKE '%Boletín%' OR pm.nombre LIKE '%Newsletter%')";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener próximos eventos para incluir en boletines
$eventos_proximos_sql = "SELECT 
    COUNT(*) as eventos_proximos,
    COUNT(CASE WHEN DATE(fecha_inicio) = CURDATE() THEN 1 END) as eventos_hoy,
    COUNT(CASE WHEN DATE(fecha_inicio) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as eventos_semana
FROM eventos 
WHERE fecha_inicio >= CURDATE() AND estado = 'programado'";

$eventos_stats_result = $conn->query($eventos_proximos_sql);
$eventos_stats = $eventos_stats_result->fetch_assoc();

// Obtener estadísticas por nivel educativo
$niveles_sql = "SELECT 
    CASE 
        WHEN pm.categoria LIKE '%inicial%' THEN 'Inicial'
        WHEN pm.categoria LIKE '%primaria%' THEN 'Primaria'
        WHEN pm.categoria LIKE '%secundaria%' THEN 'Secundaria'
        ELSE 'General'
    END as nivel,
    COUNT(DISTINCT pm.id) as cantidad_boletines,
    COUNT(me.id) as total_envios
FROM plantillas_mensajes pm
LEFT JOIN mensajes_enviados me ON pm.id = me.plantilla_id
WHERE pm.tipo = 'email' AND (pm.categoria LIKE '%boletin%' OR pm.categoria LIKE '%newsletter%' OR pm.categoria LIKE '%informativo%' OR pm.nombre LIKE '%Boletín%' OR pm.nombre LIKE '%Newsletter%')
GROUP BY nivel
ORDER BY cantidad_boletines DESC";

$niveles_result = $conn->query($niveles_sql);
$niveles_stats = [];
while($nivel = $niveles_result->fetch_assoc()) {
    $niveles_stats[] = $nivel;
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
    <title>Boletines Informativos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Boletines Informativos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Boletines, Newsletter, Comunicación, Envío Masivo"
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
    
    <!-- Custom styles for boletines -->
    <style>
      .badge-categoria {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .categoria-boletin { background-color: #007bff; }
      .categoria-newsletter { background-color: #28a745; }
      .categoria-informativo { background-color: #17a2b8; }
      .categoria-evento { background-color: #fd7e14; }
      
      .badge-popularidad {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .popularidad-alta { background-color: #28a745; }
      .popularidad-media { background-color: #ffc107; color: #856404; }
      .popularidad-baja { background-color: #fd7e14; }
      .popularidad-sin_uso { background-color: #6c757d; }
      
      .badge-nivel {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
      }
      .nivel-inicial { background-color: #fff3e0; color: #e65100; border-color: #ffcc02; }
      .nivel-primaria { background-color: #e8f5e8; color: #2d5a2d; border-color: #4caf50; }
      .nivel-secundaria { background-color: #e3f2fd; color: #1565c0; border-color: #2196f3; }
      
      .boletin-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .boletin-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .boletin-asunto {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .boletin-preview {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        background-color: #f8f9fa;
        padding: 2px 4px;
        border-radius: 3px;
        border: 1px solid #e9ecef;
      }
      
      .metricas-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .metrica-principal {
        font-weight: bold;
        color: #495057;
      }
      
      .metrica-secundaria {
        color: #6c757d;
      }
      
      .tasa-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .tasa-excelente { color: #28a745; font-weight: bold; }
      .tasa-buena { color: #ffc107; font-weight: bold; }
      .tasa-regular { color: #fd7e14; font-weight: bold; }
      .tasa-baja { color: #dc3545; font-weight: bold; }
      
      .variables-info {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        border-radius: 8px;
        background-color: #e8f4fd;
        color: #0c5460;
        font-weight: 500;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .ultimo-envio {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .estado-activo {
        color: #28a745;
        font-weight: bold;
      }
      
      .estado-inactivo {
        color: #dc3545;
        font-weight: bold;
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
      
      .niveles-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .nivel-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
      }
      
      .nivel-item:last-child {
        border-bottom: none;
      }
      
      .eventos-panel {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .evento-stat {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-right: 15px;
        font-size: 0.8rem;
        color: #856404;
      }
      
      .btn-grupo-boletin {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-boletin .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
      }
      
      .progress-metricas {
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 2px;
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
                    Boletines Informativos
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
                    Gestión de Boletines Informativos
                  </h3>
                  <small class="text-muted">
                    Crea, diseña y programa boletines informativos institucionales. 
                    Incluye eventos, personaliza por nivel educativo y analiza métricas de apertura.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" onclick="analizarMetricas()">
                    <i class="ti ti-chart-line me-1"></i>
                    Analizar Métricas
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPersonalizarContenido">
                    <i class="ti ti-palette me-1"></i>
                    Personalizar Contenido
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalProgramarEnvio">
                    <i class="ti ti-send me-1"></i>
                    Programar Envío
                  </button> -->
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearBoletin">
                    <i class="ti ti-file-plus me-1"></i>
                    Crear Boletín
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de boletines -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="boletines-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="16%">Boletín</th>
                        <th width="10%">Categoría</th>
                        <th width="8%">Nivel</th>
                        <th width="8%">Variables</th>
                        <th width="12%">Estadísticas</th>
                        <th width="8%">Popularidad</th>
                        <th width="10%">Métricas</th>
                        <th width="8%">Último Envío</th>
                        <th width="6%">Estado</th>
                        <th width="10%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_creacion = date('d/m/Y', strtotime($row['created_at']));
                              $ultimo_envio = $row['ultimo_envio'] ? date('d/m/Y H:i', strtotime($row['ultimo_envio'])) : 'Nunca';
                              
                              // Determinar clase CSS para la categoría
                              $categoria_base = explode('_', $row['categoria'])[0];
                              $categoria_class = 'categoria-' . $categoria_base;
                              
                              // Determinar clase de popularidad
                              $popularidad = $row['popularidad'] ?? 'sin_uso';
                              $popularidad_class = 'popularidad-' . $popularidad;
                              
                              // Determinar clase de nivel
                              $nivel = strtolower($row['nivel_objetivo'] ?? 'general');
                              $nivel_class = 'nivel-' . $nivel;
                              
                              // Procesar variables disponibles
                              $variables = json_decode($row['variables_disponibles'] ?? '[]', true);
                              $variables_text = is_array($variables) && !empty($variables) ? 
                                implode(', ', array_slice($variables, 0, 2)) . (count($variables) > 2 ? '...' : '') : 
                                'Ninguna';
                              
                              // Determinar clase de tasa
                              $tasa_entrega = (float)($row['tasa_entrega'] ?? 0);
                              $tasa_apertura = (float)($row['tasa_apertura'] ?? 0);
                              
                              $entrega_class = '';
                              if ($tasa_entrega >= 90) $entrega_class = 'tasa-excelente';
                              elseif ($tasa_entrega >= 75) $entrega_class = 'tasa-buena';
                              elseif ($tasa_entrega >= 50) $entrega_class = 'tasa-regular';
                              else $entrega_class = 'tasa-baja';
                              
                              $apertura_class = '';
                              if ($tasa_apertura >= 25) $apertura_class = 'tasa-excelente';
                              elseif ($tasa_apertura >= 15) $apertura_class = 'tasa-buena';
                              elseif ($tasa_apertura >= 5) $apertura_class = 'tasa-regular';
                              else $apertura_class = 'tasa-baja';
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='boletin-info'>
                                        <span class='boletin-nombre'>" . htmlspecialchars($row['nombre']) . "</span>
                                        <span class='boletin-asunto'>" . htmlspecialchars($row['asunto'] ?? 'Sin asunto') . "</span>
                                        <span class='boletin-preview' title='" . htmlspecialchars($row['contenido_preview']) . "'>" . htmlspecialchars($row['contenido_preview']) . "...</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-categoria $categoria_class'>" . 
                                   ucfirst(str_replace('_', ' ', $categoria_base)) . "</span></td>";
                              echo "<td><span class='badge badge-nivel $nivel_class'>" . 
                                   htmlspecialchars($row['nivel_objetivo']) . "</span></td>";
                              echo "<td><span class='variables-info' title='" . htmlspecialchars($variables_text) . "'>" . 
                                   htmlspecialchars($variables_text) . "</span></td>";
                              echo "<td>
                                      <div class='metricas-info'>
                                        <span class='metrica-principal'>" . number_format($row['total_envios']) . " envíos</span>
                                        <span class='metrica-secundaria'>" . number_format($row['envios_exitosos']) . " exitosos</span>
                                        <span class='metrica-secundaria'>" . number_format($row['aperturas']) . " aperturas</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-popularidad $popularidad_class'>" . 
                                   ucfirst(str_replace('_', ' ', $popularidad)) . "</span></td>";
                              echo "<td>
                                      <div class='tasa-info'>
                                        <span class='$entrega_class'>Entrega: " . $tasa_entrega . "%</span>
                                        <span class='$apertura_class'>Apertura: " . $tasa_apertura . "%</span>
                                        <div class='progress progress-metricas'>
                                          <div class='progress-bar bg-success' style='width: " . $tasa_entrega . "%'></div>
                                        </div>
                                      </div>
                                    </td>";
                              echo "<td><span class='ultimo-envio'>" . $ultimo_envio . "</span></td>";
                              echo "<td><span class='" . ($row['activo'] ? 'estado-activo' : 'estado-inactivo') . "'>" . 
                                   ($row['activo'] ? 'Activo' : 'Inactivo') . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-boletin'>
                                        <button type='button' class='btn btn-outline-info btn-ver-metricas' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Ver Métricas Detalladas'>
                                          <i class='ti ti-chart-bar'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-editar-boletin' 
                                                data-id='" . $row['id'] . "'
                                                title='Editar Boletín'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-programar-envio' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Programar Envío Masivo'>
                                          <i class='ti ti-send'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-personalizar' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre']) . "'
                                                title='Personalizar Contenido'>
                                          <i class='ti ti-palette'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay boletines informativos registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Boletín</th>
                        <th>Categoría</th>
                        <th>Nivel</th>
                        <th>Variables</th>
                        <th>Estadísticas</th>
                        <th>Popularidad</th>
                        <th>Métricas</th>
                        <th>Último Envío</th>
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
    <?php include 'modals/boletines/modal_crear_boletin.php'; ?>
    <?php include 'modals/boletines/modal_programar_envio_masivo.php'; ?>
    <?php include 'modals/boletines/modal_personalizar_contenido.php'; ?>
    <?php include 'modals/boletines/modal_analizar_metricas.php'; ?>

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
        var table = $("#boletines-table").DataTable({
          "language": {
            "decimal": "",
            "emptyTable": "No hay boletines disponibles en la tabla",
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

        // Función para analizar métricas
        window.analizarMetricas = function() {
          $('#modalAnalizarMetricas').modal('show');
        };

        // Manejar click en botón ver métricas
        $(document).on('click', '.btn-ver-metricas', function() {
            var id = $(this).data('id');
            var nombre = $(this).data('nombre');
            mostrarMetricasDetalladas(id, nombre);
        });

        // Manejar click en botón editar boletín
        $(document).on('click', '.btn-editar-boletin', function() {
            var id = $(this).data('id');
            cargarDatosEdicionBoletin(id);
        });

        // Manejar click en botón programar envío
        $(document).on('click', '.btn-programar-envio', function() {
            var id = $(this).data('id');
            var nombre = $(this).data('nombre');
            
            $('#envio_plantilla_id').val(id);
            $('#envio_boletin_nombre').text(nombre);
            $('#modalProgramarEnvio').modal('show');
        });

        // Manejar click en botón personalizar
        $(document).on('click', '.btn-personalizar', function() {
            var id = $(this).data('id');
            var nombre = $(this).data('nombre');
            
            $('#personalizar_plantilla_id').val(id);
            $('#personalizar_boletin_nombre').text(nombre);
            $('#modalPersonalizarContenido').modal('show');
        });

        // Función para mostrar métricas detalladas
        function mostrarMetricasDetalladas(id, nombre) {
          $('#modalMetricasDetalladas').remove();
          
          var modalHTML = `
            <div class="modal fade" id="modalMetricasDetalladas" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Métricas Detalladas - ${nombre}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div id="metricas-content">
                      <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Analizando métricas...</p>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="exportarMetricas(${id})">Exportar Reporte</button>
                  </div>
                </div>
              </div>
            </div>
          `;
          
          $('body').append(modalHTML);
          $('#modalMetricasDetalladas').modal('show');
          
          // CAMBIAR ESTA RUTA: Cargar métricas via AJAX
          setTimeout(function() {
            $.ajax({
              url: 'acciones/boletines/procesar_acciones.php', // <-- CAMBIO AQUÍ
              method: 'POST',
              data: { 
                accion: 'obtener_metricas_detalladas',
                plantilla_id: id,
                fecha_inicio: '<?php echo date('Y-m-d', strtotime('-30 days')); ?>',
                fecha_fin: '<?php echo date('Y-m-d'); ?>'
              },
              dataType: 'json',
              success: function(response) {
                if (response.success) {
                  var metricas = response.metricas;
                  $('#metricas-content').html(`
                    <div class="row">
                      <div class="col-md-3">
                        <div class="card text-center">
                          <div class="card-body">
                            <h4 class="text-primary">${metricas.total_enviados}</h4>
                            <p class="mb-0">Total Enviados</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card text-center">
                          <div class="card-body">
                            <h4 class="text-success">${metricas.entregados}</h4>
                            <p class="mb-0">Entregados</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card text-center">
                          <div class="card-body">
                            <h4 class="text-info">${metricas.abiertos}</h4>
                            <p class="mb-0">Abiertos</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card text-center">
                          <div class="card-body">
                            <h4 class="text-warning">${metricas.tasa_apertura}%</h4>
                            <p class="mb-0">Tasa Apertura</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row mt-3">
                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h6>Tasa de Entrega</h6>
                            <div class="progress">
                              <div class="progress-bar bg-success" style="width: ${metricas.tasa_entrega}%"></div>
                            </div>
                            <small class="text-muted">${metricas.tasa_entrega}% de entrega exitosa</small>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h6>Tasa de Apertura</h6>
                            <div class="progress">
                              <div class="progress-bar bg-info" style="width: ${metricas.tasa_apertura}%"></div>
                            </div>
                            <small class="text-muted">${metricas.tasa_apertura}% de apertura</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  `);
                } else {
                  $('#metricas-content').html(`
                    <div class="alert alert-danger">
                      <h6>Error</h6>
                      <p>${response.mensaje}</p>
                    </div>
                  `);
                }
              },
              error: function() {
                $('#metricas-content').html(`
                  <div class="alert alert-danger">
                    <h6>Error</h6>
                    <p>No se pudieron cargar las métricas. Inténtelo nuevamente.</p>
                  </div>
                `);
              }
            });
          }, 1000);
        }

        // Función para exportar métricas
        window.exportarMetricas = function(id) {
          window.open('exports/metricas_boletin.php?id=' + id, '_blank');
        };

        // Función para cargar datos de edición
        function cargarDatosEdicionBoletin(id) {
          // CAMBIAR ESTA RUTA: Cargar datos para edición
          $.ajax({
            url: 'acciones/boletines/procesar_acciones.php', // <-- CAMBIO AQUÍ
            method: 'POST',
            data: { 
              accion: 'obtener_boletin_edicion',
              boletin_id: id
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                // Llenar modal de edición con los datos
                $('#modalEditarBoletin').remove();
                
                var modalEditarHTML = `
                  <div class="modal fade" id="modalEditarBoletin" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Editar Boletín</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formEditarBoletin">
                          <input type="hidden" name="accion" value="actualizar_boletin">
                          <input type="hidden" name="boletin_id" value="${response.boletin.id}">
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Nombre del Boletín</label>
                              <input type="text" class="form-control" name="nombre" value="${response.boletin.nombre}" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Asunto</label>
                              <input type="text" class="form-control" name="asunto" value="${response.boletin.asunto}" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Contenido</label>
                              <textarea class="form-control" name="contenido" rows="8" required>${response.boletin.contenido}</textarea>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="mb-3">
                                  <label class="form-label">Categoría</label>
                                  <select class="form-select" name="categoria" required>
                                    <option value="boletin_informativo" ${response.boletin.categoria === 'boletin_informativo' ? 'selected' : ''}>Boletín Informativo</option>
                                    <option value="newsletter_mensual" ${response.boletin.categoria === 'newsletter_mensual' ? 'selected' : ''}>Newsletter Mensual</option>
                                    <option value="comunicado_eventos" ${response.boletin.categoria === 'comunicado_eventos' ? 'selected' : ''}>Comunicado de Eventos</option>
                                    <option value="boletin_academico" ${response.boletin.categoria === 'boletin_academico' ? 'selected' : ''}>Boletín Académico</option>
                                  </select>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-3">
                                  <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activo" ${response.boletin.activo ? 'checked' : ''}>
                                    <label class="form-check-label">Boletín Activo</label>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Actualizar Boletín</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                `;
                
                $('body').append(modalEditarHTML);
                $('#modalEditarBoletin').modal('show');
                
                // Manejar envío del formulario de edición
                $('#formEditarBoletin').on('submit', function(e) {
                  e.preventDefault();
                  
                  $.ajax({
                    url: 'acciones/boletines/procesar_acciones.php', // <-- CAMBIO AQUÍ
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                      if (response.success) {
                        alert(response.mensaje);
                        location.reload();
                      } else {
                        alert('Error: ' + response.mensaje);
                      }
                    },
                    error: function() {
                      alert('Error de conexión');
                    }
                  });
                });
                
              } else {
                alert('Error: ' + response.mensaje);
              }
            },
            error: function() {
              alert('Error de conexión al cargar datos de edición');
            }
          });
        }

        // Auto-refresh cada 5 minutos para estadísticas
        setInterval(function() {
          // Actualizar solo las estadísticas principales
          actualizarEstadisticasBoletines();
        }, 300000); // 5 minutos

        // Función para actualizar estadísticas
        function actualizarEstadisticasBoletines() {
          $.ajax({
            url: 'acciones/boletines/procesar_acciones.php', // <-- CAMBIO AQUÍ
            method: 'POST',
            data: { accion: 'obtener_estadisticas_boletines' },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                // Actualizar los números en las tarjetas de estadísticas
                $('.stats-card .stat-number').each(function(index) {
                  var keys = ['total_boletines', 'boletines_activos', 'total_envios_boletines', 'tasa_entrega_promedio', 'tasa_apertura_promedio', 'envios_semana'];
                  if (keys[index] && response.data[keys[index]] !== undefined) {
                    var valor = response.data[keys[index]];
                    if (keys[index].includes('tasa_')) {
                      valor += '%';
                    } else {
                      valor = parseInt(valor).toLocaleString();
                    }
                    $(this).text(valor);
                  }
                });
              }
            },
            error: function() {
              console.log('Error al actualizar estadísticas de boletines');
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