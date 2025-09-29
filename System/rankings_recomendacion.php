<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/rankings_recomendacion/gestionar_rankings.php');
        exit();
    }

    // Consulta principal para obtener ranking de referentes
    $sql = "SELECT
        a.id as apoderado_id,
        a.nombres,
        a.apellidos,
        a.email,
        a.telefono_principal,
        a.whatsapp,
        f.apellido_principal as familia,
        f.codigo_familia,
        -- Códigos de referido activos
        COUNT(DISTINCT cr.id) as total_codigos,
        -- Total de usos de códigos
        SUM(cr.usos_actuales) as total_usos,
        -- Límite total de usos disponibles
        SUM(cr.limite_usos) as limite_total,
        -- Usos restantes
        SUM(CASE 
            WHEN cr.limite_usos IS NOT NULL 
            THEN cr.limite_usos - cr.usos_actuales 
            ELSE 0 
        END) as usos_restantes,
        -- Conversiones exitosas
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones_exitosas,
        -- Tasa de conversión
        CASE 
            WHEN SUM(cr.usos_actuales) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
            ELSE 0 
        END as tasa_conversion,
        -- Última fecha de uso
        MAX(ur.fecha_uso) as ultimo_uso,
        -- Códigos activos vs inactivos
        COUNT(DISTINCT CASE WHEN cr.activo = 1 THEN cr.id END) as codigos_activos,
        -- Clasificación de rendimiento
        CASE
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 10 THEN 'Elite'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 5 THEN 'Destacado'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 1 THEN 'Activo'
            WHEN SUM(cr.usos_actuales) > 0 THEN 'En Progreso'
            ELSE 'Nuevo'
        END as categoria_rendimiento
    FROM apoderados a
    INNER JOIN familias f ON a.familia_id = f.id
    LEFT JOIN codigos_referido cr ON a.id = cr.apoderado_id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE a.activo = 1
    GROUP BY a.id, a.nombres, a.apellidos, a.email, a.telefono_principal, 
             a.whatsapp, f.apellido_principal, f.codigo_familia
    HAVING total_codigos > 0
    ORDER BY conversiones_exitosas DESC, tasa_conversion DESC, total_usos DESC";

    $result = $conn->query($sql);

    // Obtener estadísticas generales del sistema de referidos
    $stats_sql = "SELECT
        -- Total de códigos de referido
        COUNT(DISTINCT cr.id) as total_codigos_sistema,
        COUNT(DISTINCT CASE WHEN cr.activo = 1 THEN cr.id END) as codigos_activos_sistema,
        -- Total de apoderados con códigos
        COUNT(DISTINCT cr.apoderado_id) as total_referentes,
        -- Total de usos
        SUM(cr.usos_actuales) as total_usos_sistema,
        -- Total de conversiones
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as total_conversiones_sistema,
        -- Tasa de conversión global
        CASE 
            WHEN SUM(cr.usos_actuales) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
            ELSE 0 
        END as tasa_conversion_global,
        -- Promedio de conversiones por referente
        ROUND(COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 1.0 / 
              COUNT(DISTINCT cr.apoderado_id), 2) as promedio_conversiones_referente,
        -- Código más usado
        (SELECT codigo FROM codigos_referido ORDER BY usos_actuales DESC LIMIT 1) as codigo_mas_usado,
        (SELECT MAX(usos_actuales) FROM codigos_referido) as usos_codigo_mas_usado
    FROM codigos_referido cr
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener Top 5 Referentes del Mes Actual
    $top_mes_sql = "SELECT
        a.id,
        CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
        f.apellido_principal as familia,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones_mes,
        SUM(cr.usos_actuales) as usos_mes
    FROM apoderados a
    INNER JOIN familias f ON a.familia_id = f.id
    INNER JOIN codigos_referido cr ON a.id = cr.apoderado_id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE MONTH(ur.fecha_uso) = MONTH(CURDATE()) 
        AND YEAR(ur.fecha_uso) = YEAR(CURDATE())
        AND a.activo = 1
    GROUP BY a.id, nombre_completo, f.apellido_principal
    ORDER BY conversiones_mes DESC, usos_mes DESC
    LIMIT 5";

    $top_mes_result = $conn->query($top_mes_sql);
    $top_mes = [];
    while($row = $top_mes_result->fetch_assoc()) {
        $top_mes[] = $row;
    }

    // Obtener categorías de rendimiento
$categorias_sql = "SELECT
    categoria,
    COUNT(DISTINCT apoderado_id) as cantidad
FROM (
    SELECT
        a.id as apoderado_id,
        CASE
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 10 THEN 'Elite'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 5 THEN 'Destacado'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 1 THEN 'Activo'
            WHEN SUM(cr.usos_actuales) > 0 THEN 'En Progreso'
            ELSE 'Nuevo'
        END as categoria
    FROM apoderados a
    INNER JOIN codigos_referido cr ON a.id = cr.apoderado_id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE a.activo = 1
    GROUP BY a.id
) AS categorias_temp
GROUP BY categoria
ORDER BY 
    CASE categoria
        WHEN 'Elite' THEN 1
        WHEN 'Destacado' THEN 2
        WHEN 'Activo' THEN 3
        WHEN 'En Progreso' THEN 4
        WHEN 'Nuevo' THEN 5
    END";

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
    <title>Rankings de Recomendación - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Rankings de Recomendación"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Referidos, Rankings, Conversiones"
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

    <!-- Custom styles for rankings -->
    <style>
      .badge-categoria {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      .categoria-elite { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.4);
      }
      .categoria-destacado { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(240, 147, 251, 0.4);
      }
      .categoria-activo { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(79, 172, 254, 0.4);
      }
      .categoria-en-progreso { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(67, 233, 123, 0.4);
      }
      .categoria-nuevo { 
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(250, 112, 154, 0.4);
      }

      .referente-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .referente-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
      }

      .referente-familia {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }

      .referente-codigo {
        font-size: 0.7rem;
        color: #495057;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        font-family: 'Courier New', monospace;
      }

      .stats-referido {
        display: flex;
        flex-direction: column;
        gap: 3px;
        text-align: center;
      }

      .stat-numero {
        font-size: 1.4rem;
        font-weight: bold;
        color: #495057;
      }

      .stat-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
      }

      .conversion-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
      }

      .conversion-rate {
        font-size: 1.5rem;
        font-weight: bold;
        color: #28a745;
      }

      .conversion-count {
        font-size: 0.75rem;
        color: #6c757d;
      }

      .progress-uso {
        width: 100%;
        height: 8px;
        background-color: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
      }

      .progress-uso-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
      }

      .uso-texto {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 3px;
      }

      .stats-card-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
      }

      .stats-card-gradient .card-body {
        padding: 1.5rem;
      }

      .stat-item-grande {
        text-align: center;
        padding: 15px;
      }

      .stat-numero-grande {
        font-size: 2rem;
        font-weight: bold;
        display: block;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }

      .stat-label-grande {
        font-size: 0.85rem;
        opacity: 0.95;
        text-transform: uppercase;
        letter-spacing: 1px;
      }

      .top-referentes-panel {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }

      .top-referente-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 15px;
        margin: 8px 0;
        background: white;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }

      .top-referente-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      }

      .top-posicion {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
        min-width: 40px;
      }

      .top-nombre {
        flex: 1;
        font-weight: 600;
        color: #2c3e50;
        padding: 0 15px;
      }

      .top-stats {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
      }

      .top-stat-item {
        text-align: center;
      }

      .top-stat-numero {
        font-weight: bold;
        color: #495057;
        display: block;
      }

      .top-stat-label {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .btn-grupo-ranking {
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
        justify-content: center;
      }

      .btn-grupo-ranking .btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
      }

      .alert-mensaje {
        margin-bottom: 20px;
      }

      .categorias-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .categoria-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        margin: 4px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }

      .categoria-badge {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 0.85rem;
      }

      .contacto-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 10px;
        margin: 2px;
      }

      .tiene-email {
        background-color: #d4edda;
        color: #155724;
      }

      .tiene-telefono {
        background-color: #d1ecf1;
        color: #0c5460;
      }

      .tiene-whatsapp {
        background-color: #d4edda;
        color: #155724;
      }

      .ultimo-uso-info {
        font-size: 0.7rem;
        color: #6c757d;
        text-align: center;
      }

      .ranking-position {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        font-weight: bold;
        font-size: 1rem;
        margin-right: 8px;
      }

      .posicion-1 {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: white;
        box-shadow: 0 3px 6px rgba(255, 215, 0, 0.4);
      }

      .posicion-2 {
        background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
        color: white;
        box-shadow: 0 3px 6px rgba(192, 192, 192, 0.4);
      }

      .posicion-3 {
        background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
        color: white;
        box-shadow: 0 3px 6px rgba(205, 127, 50, 0.4);
      }

      .posicion-otros {
        background-color: #e9ecef;
        color: #495057;
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
                    <a href="javascript: void(0)">Marketing y Referidos</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Rankings de Recomendación
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
                    <i class="ti ti-medal me-2"></i>
                    Ranking de Referentes
                  </h3>
                  <small class="text-muted">
                    Visualiza el desempeño de tus mejores embajadores. Identifica los referentes más efectivos
                    y analiza las métricas de conversión para optimizar tu programa de referidos.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="verLeaderboardCompleto()">
                    <i class="ti ti-trophy me-1"></i>
                    Ver Leaderboard
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" onclick="verDetallesReferente()">
                    <i class="ti ti-user-search me-1"></i>
                    Detalles Referente
                  </button>
                  <!-- <button type="button" class="btn btn-primary btn-sm" onclick="generarReporteEfectividad()">
                    <i class="ti ti-file-analytics me-1"></i>
                    Generar Reporte
                  </button> -->
                </div>
              </div>

              <div class="card-body">
                <!-- Tabla de rankings -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="rankings-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="8%">Ranking</th>
                        <th width="20%">Referente</th>
                        <th width="8%">Categoría</th>
                        <th width="10%">Códigos</th>
                        <th width="10%">Usos</th>
                        <th width="12%">Conversiones</th>
                        <th width="10%">Tasa Conv.</th>
                        <th width="12%">Contacto</th>
                        <th width="10%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          $ranking = 1;
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase de posición
                              $posicion_class = 'posicion-otros';
                              if ($ranking == 1) $posicion_class = 'posicion-1';
                              elseif ($ranking == 2) $posicion_class = 'posicion-2';
                              elseif ($ranking == 3) $posicion_class = 'posicion-3';

                              // Determinar clase de categoría
                              $categoria_class = 'categoria-' . strtolower(str_replace(' ', '-', $row['categoria_rendimiento']));

                              // Calcular porcentaje de uso
                              $porcentaje_uso = 0;
                              if ($row['limite_total'] > 0) {
                                  $porcentaje_uso = ($row['total_usos'] / $row['limite_total']) * 100;
                              }

                              // Formatear última fecha de uso
                              $ultimo_uso = $row['ultimo_uso'] ? date('d/m/Y', strtotime($row['ultimo_uso'])) : 'Nunca usado';

                              echo "<tr>";
                              echo "<td>
                                      <div class='d-flex align-items-center'>
                                        <span class='ranking-position $posicion_class'>#$ranking</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='referente-info'>
                                        <span class='referente-nombre'>" . 
                                        htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</span>
                                        <span class='referente-familia'>Familia: " . 
                                        htmlspecialchars($row['familia']) . "</span>
                                        <span class='referente-codigo'>Cód: " . 
                                        htmlspecialchars($row['codigo_familia']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-categoria $categoria_class'>" . 
                                   htmlspecialchars($row['categoria_rendimiento']) . "</span></td>";
                              echo "<td>
                                      <div class='stats-referido'>
                                        <span class='stat-numero'>" . $row['total_codigos'] . "</span>
                                        <span class='stat-label'>Códigos</span>
                                        <small class='text-success'>" . $row['codigos_activos'] . " activos</small>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div>
                                        <div class='progress-uso'>
                                          <div class='progress-uso-bar' style='width: " . min($porcentaje_uso, 100) . "%;'></div>
                                        </div>
                                        <div class='uso-texto'>" . 
                                        $row['total_usos'] . " / " . 
                                        ($row['limite_total'] ?: '∞') . " usos</div>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='conversion-info'>
                                        <span class='conversion-rate'>" . $row['conversiones_exitosas'] . "</span>
                                        <span class='conversion-count'>conversiones</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='conversion-info'>
                                        <span class='conversion-rate' style='font-size: 1.3rem;'>" . 
                                        $row['tasa_conversion'] . "%</span>
                                        <span class='conversion-count'>efectividad</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='d-flex flex-wrap gap-1 justify-content-center'>";
                              if ($row['email']) {
                                  echo "<span class='contacto-badge tiene-email' title='Email: " . 
                                       htmlspecialchars($row['email']) . "'><i class='ti ti-mail'></i></span>";
                              }
                              if ($row['telefono_principal']) {
                                  echo "<span class='contacto-badge tiene-telefono' title='Teléfono: " . 
                                       htmlspecialchars($row['telefono_principal']) . "'><i class='ti ti-phone'></i></span>";
                              }
                              if ($row['whatsapp']) {
                                  echo "<span class='contacto-badge tiene-whatsapp' title='WhatsApp: " . 
                                       htmlspecialchars($row['whatsapp']) . "'><i class='ti ti-brand-whatsapp'></i></span>";
                              }
                              echo "</div>
                                    <div class='ultimo-uso-info mt-1'>
                                      Último: $ultimo_uso
                                    </div>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-ranking'>
                                        <button type='button' class='btn btn-outline-info btn-ver-detalles'
                                                data-id='" . $row['apoderado_id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                title='Ver Detalles'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-ver-leaderboard'
                                                data-id='" . $row['apoderado_id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "'
                                                title='Ver en Leaderboard'>
                                          <i class='ti ti-trophy'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";

                              $ranking++;
                          }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>No hay referentes con códigos activos</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Ranking</th>
                        <th>Referente</th>
                        <th>Categoría</th>
                        <th>Códigos</th>
                        <th>Usos</th>
                        <th>Conversiones</th>
                        <th>Tasa Conv.</th>
                        <th>Contacto</th>
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
    <?php include 'modals/rankings_recomendacion/modal_leaderboard.php'; ?>
    <?php include 'modals/rankings_recomendacion/modal_detalles_referente.php'; ?>

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
            var table = $("#rankings-table").DataTable({
            "language": {
                "decimal": "",
                "emptyTable": "No hay referentes disponibles",
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
                }
            },
            "pageLength": 25,
            "order": [[ 0, "asc" ]], // Ordenar por ranking ascendente
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

            // Función para ver leaderboard completo
            window.verLeaderboardCompleto = function() {
            $('#modalLeaderboard').modal('show');
            }

            // Función para ver detalles de referente (sin seleccionar)
            window.verDetallesReferente = function() {
            Swal.fire({
                title: 'Seleccionar Referente',
                text: 'Por favor, seleccione un referente de la tabla para ver sus detalles',
                icon: 'info',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4c9aff'
            });
            }

            // Función para generar reporte de efectividad
            window.generarReporteEfectividad = function() {
            Swal.fire({
                title: 'Generar Reporte de Efectividad',
                html: `
                <div class="text-start">
                    <p class="mb-3">Seleccione el formato del reporte:</p>
                    <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" onclick="generarReporteExcel()">
                        <i class="ti ti-file-spreadsheet me-2"></i>Excel (.xlsx)
                    </button>
                    <button class="btn btn-outline-danger" onclick="generarReportePDF()">
                        <i class="ti ti-file-type-pdf me-2"></i>PDF
                    </button>
                    <button class="btn btn-outline-info" onclick="generarReporteCSV()">
                        <i class="ti ti-file-text me-2"></i>CSV
                    </button>
                    </div>
                </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                width: '400px'
            });
            }

            // Funciones de exportación
            window.generarReporteExcel = function() {
            Swal.fire({
                title: '¿Generar Reporte Excel?',
                text: 'Se descargará un archivo Excel con el análisis completo de efectividad',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="ti ti-download me-1"></i> Descargar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                // Redirigir a exportación
                window.location.href = 'acciones/rankings_recomendacion/exportar_reporte_efectividad.php?formato=excel';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Generando Reporte',
                    text: 'La descarga comenzará en breve',
                    timer: 2000,
                    showConfirmButton: false
                });
                }
            });
            }

            window.generarReportePDF = function() {
            Swal.fire({
                title: '¿Generar Reporte PDF?',
                text: 'Se descargará un archivo PDF con el análisis completo de efectividad',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="ti ti-download me-1"></i> Descargar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                // Redirigir a exportación
                window.location.href = 'acciones/rankings_recomendacion/exportar_reporte_efectividad.php?formato=pdf';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Generando Reporte',
                    text: 'La descarga comenzará en breve',
                    timer: 2000,
                    showConfirmButton: false
                });
                }
            });
            }

            window.generarReporteCSV = function() {
            Swal.fire({
                title: '¿Generar Reporte CSV?',
                text: 'Se descargará un archivo CSV con los datos de efectividad',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="ti ti-download me-1"></i> Descargar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                // Redirigir a exportación
                window.location.href = 'acciones/rankings_recomendacion/exportar_reporte_efectividad.php?formato=csv';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Generando Reporte',
                    text: 'La descarga comenzará en breve',
                    timer: 2000,
                    showConfirmButton: false
                });
                }
            });
            }

            // Manejar click en botón ver detalles
            $(document).on('click', '.btn-ver-detalles', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                // Cargar detalles del referente
                cargarDetallesReferente(id, nombre);
            });

            // Manejar click en botón ver en leaderboard
            $(document).on('click', '.btn-ver-leaderboard', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                title: 'Ver en Leaderboard',
                text: '¿Desea ver la posición de ' + nombre + ' en el leaderboard completo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, ver',
                cancelButtonText: 'Cancelar'
                }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal de leaderboard y resaltar al referente
                    $('#modalLeaderboard').modal('show');
                    setTimeout(function() {
                    resaltarReferenteEnLeaderboard(id);
                    }, 500);
                }
                });
            });

            // Función para cargar detalles del referente
            function cargarDetallesReferente(id, nombre) {
            Swal.fire({
                title: 'Cargando detalles...',
                text: 'Obteniendo información de ' + nombre,
                allowOutsideClick: false,
                didOpen: () => {
                Swal.showLoading();
                }
            });

            // Simular carga y luego abrir modal
            setTimeout(function() {
                Swal.close();
                
                // Cargar datos en el modal
                cargarDetallesReferenteCompleto(id, nombre);
                
                // Mostrar modal
                $('#modalDetallesReferente').modal('show');
            }, 800);
            }

            // Función para resaltar referente en leaderboard
            function resaltarReferenteEnLeaderboard(id) {
            // Esta función se implementa en el modal de leaderboard
            console.log('Resaltando referente:', id);
            
            // Buscar y resaltar el elemento en el leaderboard
            setTimeout(function() {
                var elemento = $('.leaderboard-item[data-id="' + id + '"]');
                if (elemento.length > 0) {
                elemento.css({
                    'background': 'linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%)',
                    'border': '2px solid #ffc107',
                    'transform': 'scale(1.02)'
                });
                
                // Scroll hacia el elemento
                elemento[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Remover resaltado después de 3 segundos
                setTimeout(function() {
                    elemento.css({
                    'background': 'white',
                    'border': 'none',
                    'transform': 'scale(1)'
                    });
                }, 3000);
                }
            }, 500);
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