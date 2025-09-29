<?php
    session_start();
    // Incluir conexión a la base de datos
    include 'bd/conexion.php';

    // Procesar acciones POST
    $mensaje_sistema = '';
    $tipo_mensaje = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        // Redirigir todas las acciones al archivo de procesamiento
        header('Location: acciones/incentivos_referidos/gestionar_referidos.php');
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
        cr.updated_at,
        -- Información del referente
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as nombre_referente,
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN a.email
            WHEN cr.familia_id IS NOT NULL THEN 
                (SELECT email FROM apoderados WHERE familia_id = cr.familia_id AND tipo_apoderado = 'titular' LIMIT 1)
            ELSE NULL
        END as email_referente,
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN a.telefono_principal
            WHEN cr.familia_id IS NOT NULL THEN 
                (SELECT telefono_principal FROM apoderados WHERE familia_id = cr.familia_id AND tipo_apoderado = 'titular' LIMIT 1)
            ELSE NULL
        END as telefono_referente,
        -- Estadísticas de uso
        COUNT(DISTINCT ur.id) as total_usos,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as usos_convertidos,
        -- Porcentaje de conversión
        CASE 
            WHEN COUNT(DISTINCT ur.id) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / COUNT(DISTINCT ur.id), 1)
            ELSE 0
        END as tasa_conversion,
        -- Usos disponibles
        CASE 
            WHEN cr.limite_usos IS NULL THEN 'Ilimitado'
            ELSE CAST((cr.limite_usos - cr.usos_actuales) AS CHAR)
        END as usos_disponibles,
        -- Estado del código
        CASE 
            WHEN cr.activo = 0 THEN 'Inactivo'
            WHEN cr.fecha_fin IS NOT NULL AND cr.fecha_fin < CURDATE() THEN 'Vencido'
            WHEN cr.limite_usos IS NOT NULL AND cr.usos_actuales >= cr.limite_usos THEN 'Agotado'
            ELSE 'Activo'
        END as estado_codigo,
        -- Días restantes
        CASE 
            WHEN cr.fecha_fin IS NULL THEN NULL
            ELSE DATEDIFF(cr.fecha_fin, CURDATE())
        END as dias_restantes
    FROM codigos_referido cr
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    GROUP BY cr.id
    ORDER BY 
        CASE 
            WHEN cr.activo = 0 THEN 3
            WHEN cr.fecha_fin IS NOT NULL AND cr.fecha_fin < CURDATE() THEN 2
            WHEN cr.limite_usos IS NOT NULL AND cr.usos_actuales >= cr.limite_usos THEN 2
            ELSE 1
        END,
        cr.created_at DESC";

    $result = $conn->query($sql);

    // Obtener estadísticas generales
    $stats_sql = "SELECT
        COUNT(*) as total_codigos,
        COUNT(CASE WHEN activo = 1 THEN 1 END) as codigos_activos,
        COUNT(CASE WHEN activo = 0 THEN 1 END) as codigos_inactivos,
        COUNT(CASE WHEN fecha_fin IS NOT NULL AND fecha_fin < CURDATE() THEN 1 END) as codigos_vencidos,
        COUNT(CASE WHEN limite_usos IS NOT NULL AND usos_actuales >= limite_usos THEN 1 END) as codigos_agotados,
        SUM(usos_actuales) as total_usos,
        COUNT(CASE WHEN apoderado_id IS NOT NULL THEN 1 END) as codigos_personalizados,
        COUNT(CASE WHEN apoderado_id IS NULL AND familia_id IS NULL THEN 1 END) as codigos_generales
    FROM codigos_referido";

    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Obtener estadísticas de conversión
    $conversion_sql = "SELECT
        COUNT(*) as total_usos_sistema,
        COUNT(CASE WHEN convertido = 1 THEN 1 END) as usos_convertidos,
        COUNT(CASE WHEN convertido = 0 THEN 1 END) as usos_pendientes,
        ROUND((COUNT(CASE WHEN convertido = 1 THEN 1 END) * 100.0) / NULLIF(COUNT(*), 0), 1) as tasa_conversion_global
    FROM usos_referido";

    $conversion_result = $conn->query($conversion_sql);
    $conversion_stats = $conversion_result->fetch_assoc();

    // Top 5 códigos más efectivos
    $top_codigos_sql = "SELECT
        cr.codigo,
        cr.descripcion,
        COUNT(DISTINCT ur.id) as total_usos,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones,
        CASE 
            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
            ELSE 'Código General'
        END as referente
    FROM codigos_referido cr
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    LEFT JOIN apoderados a ON cr.apoderado_id = a.id
    LEFT JOIN familias f ON cr.familia_id = f.id
    WHERE cr.activo = 1
    GROUP BY cr.id
    HAVING total_usos > 0
    ORDER BY conversiones DESC, total_usos DESC
    LIMIT 5";

    $top_codigos_result = $conn->query($top_codigos_sql);
    $top_codigos = [];
    while($codigo = $top_codigos_result->fetch_assoc()) {
        $top_codigos[] = $codigo;
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
    <title>Incentivos por Referidos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Referidos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Referidos, Incentivos, Marketing"
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

    <!-- Custom styles for incentivos referidos -->
    <style>
      /* Paleta de colores pastel */
      :root {
        --pastel-green: #B8E6B8;
        --pastel-blue: #B8D4E6;
        --pastel-yellow: #FFF4B8;
        --pastel-pink: #FFB8D4;
        --pastel-purple: #D4B8E6;
        --pastel-orange: #FFD4B8;
        --pastel-red: #FFB8B8;
      }

      body {
        background-color: #FFFFFF;
      }

      .badge-estado-codigo {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
      }
      .estado-activo { 
        background-color: var(--pastel-green); 
        color: #2d5016;
      }
      .estado-inactivo { 
        background-color: #E0E0E0; 
        color: #666666;
      }
      .estado-vencido { 
        background-color: var(--pastel-orange); 
        color: #8B4513;
      }
      .estado-agotado { 
        background-color: var(--pastel-red); 
        color: #8B0000;
      }

      .badge-tipo-referente {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .tipo-personalizado { 
        background-color: var(--pastel-purple); 
        color: #4B0082;
      }
      .tipo-general { 
        background-color: var(--pastel-blue); 
        color: #00008B;
      }

      .codigo-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .codigo-principal {
        font-weight: 700;
        color: #2c3e50;
        font-size: 1rem;
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
        font-weight: 600;
        color: #495057;
      }

      .referente-contacto {
        color: #6c757d;
        font-size: 0.7rem;
      }

      .beneficios-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
        font-size: 0.7rem;
      }

      .beneficio-item {
        padding: 2px 6px;
        border-radius: 4px;
        background-color: var(--pastel-yellow);
        color: #856404;
      }

      .beneficio-referente {
        background-color: var(--pastel-green);
        color: #2d5016;
      }

      .beneficio-referido {
        background-color: var(--pastel-pink);
        color: #8B0042;
      }

      .usos-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .usos-numero {
        font-size: 1.2rem;
        font-weight: bold;
        color: #495057;
      }

      .usos-limite {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .usos-barra-progreso {
        width: 100%;
        height: 6px;
        background-color: #E0E0E0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 3px;
      }

      .usos-progreso {
        height: 100%;
        background-color: var(--pastel-blue);
        transition: width 0.3s ease;
      }

      .conversion-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }

      .conversion-porcentaje {
        font-size: 1.1rem;
        font-weight: bold;
        color: #28a745;
      }

      .conversion-detalle {
        font-size: 0.7rem;
        color: #6c757d;
      }

      .vigencia-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        font-size: 0.75rem;
      }

      .vigencia-fecha {
        font-weight: 500;
        color: #495057;
      }

      .vigencia-dias {
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 500;
      }
      .dias-suficientes { 
        background-color: var(--pastel-green); 
        color: #2d5016;
      }
      .dias-advertencia { 
        background-color: var(--pastel-yellow); 
        color: #856404;
      }
      .dias-critico { 
        background-color: var(--pastel-red); 
        color: #8B0000;
      }

      .stats-card {
        background: linear-gradient(135deg, var(--pastel-purple) 0%, var(--pastel-pink) 100%);
        color: #4B0082;
        border: none;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }

      .stats-card .card-body {
        padding: 1.5rem;
      }

      .stat-item {
        text-align: center;
        padding: 10px;
      }

      .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        display: block;
      }

      .stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
        margin-top: 5px;
      }

      .top-codigos-panel {
        background: linear-gradient(135deg, var(--pastel-blue) 0%, var(--pastel-green) 100%);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .top-codigo-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        margin: 5px 0;
        border-radius: 8px;
        background-color: rgba(255, 255, 255, 0.8);
        font-size: 0.8rem;
      }

      .top-codigo-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-family: 'Courier New', monospace;
      }

      .top-codigo-stats {
        display: flex;
        gap: 10px;
        font-size: 0.75rem;
      }

      .top-codigo-badge {
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 500;
      }

      .badge-usos {
        background-color: var(--pastel-blue);
        color: #00008B;
      }

      .badge-conversiones {
        background-color: var(--pastel-green);
        color: #2d5016;
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

      .table-hover tbody tr:hover {
        background-color: #F5F5F5;
      }

      /* Estilos para las tarjetas de estadísticas adicionales */
      .conversion-card {
        background: linear-gradient(135deg, var(--pastel-green) 0%, var(--pastel-yellow) 100%);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }

      .conversion-card h6 {
        color: #2d5016;
        font-weight: 600;
        margin-bottom: 10px;
      }

      .conversion-stat {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 0.85rem;
      }

      .conversion-stat-label {
        color: #495057;
      }

      .conversion-stat-value {
        font-weight: bold;
        color: #2d5016;
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
                    <a href="javascript: void(0)">Marketing y Captación</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Incentivos por Referidos
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

        <!-- [ Top Códigos y Conversiones ] start -->
        <div class="row">
          <div class="col-md-6">
            <div class="top-codigos-panel">
              <h6 class="mb-3" style="color: #00008B; font-weight: 600;">
                <i class="ti ti-trophy me-1"></i>
                Top 5 Códigos Más Efectivos
              </h6>
              <?php if(count($top_codigos) > 0): ?>
                <?php foreach($top_codigos as $index => $codigo): ?>
                  <div class="top-codigo-item">
                    <div>
                      <span style="font-weight: bold; color: #2c3e50; margin-right: 5px;">#<?php echo $index + 1; ?></span>
                      <span class="top-codigo-nombre"><?php echo htmlspecialchars($codigo['codigo']); ?></span>
                      <br>
                      <small style="color: #6c757d;"><?php echo htmlspecialchars($codigo['referente']); ?></small>
                    </div>
                    <div class="top-codigo-stats">
                      <span class="top-codigo-badge badge-usos">
                        <i class="ti ti-users"></i> <?php echo $codigo['total_usos']; ?> usos
                      </span>
                      <span class="top-codigo-badge badge-conversiones">
                        <i class="ti ti-check"></i> <?php echo $codigo['conversiones']; ?> conversiones
                      </span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-info mb-0">No hay códigos con usos registrados</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-6">
            <div class="conversion-card">
              <h6>
                <i class="ti ti-chart-line me-1"></i>
                Estadísticas de Conversión
              </h6>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Total Usos en el Sistema:</span>
                <span class="conversion-stat-value"><?php echo $conversion_stats['total_usos_sistema']; ?></span>
              </div>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Usos Convertidos:</span>
                <span class="conversion-stat-value"><?php echo $conversion_stats['usos_convertidos']; ?></span>
              </div>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Usos Pendientes:</span>
                <span class="conversion-stat-value"><?php echo $conversion_stats['usos_pendientes']; ?></span>
              </div>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Tasa Global de Conversión:</span>
                <span class="conversion-stat-value" style="font-size: 1.2rem;"><?php echo $conversion_stats['tasa_conversion_global']; ?>%</span>
              </div>
              <hr>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Códigos Personalizados:</span>
                <span class="conversion-stat-value"><?php echo $stats['codigos_personalizados']; ?></span>
              </div>
              <div class="conversion-stat">
                <span class="conversion-stat-label">Códigos Generales:</span>
                <span class="conversion-stat-value"><?php echo $stats['codigos_generales']; ?></span>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Top Códigos y Conversiones ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    <i class="ti ti-gift me-2"></i>
                    Gestión de Códigos de Referido
                  </h3>
                  <small class="text-muted">
                    Administra los códigos de referido, registra usos y valida conversiones.
                    Incentiva a tus familias actuales y genera nuevos leads efectivos.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarUso">
                    <i class="ti ti-user-plus me-1"></i>
                    Registrar Uso de Código
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalValidarConversion">
                    <i class="fas fa-check-circle"></i>
                    Validar Conversión
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGestionarCodigo">
                    <i class="ti ti-code-plus me-1"></i>
                    Crear Código
                  </button>
                </div>
              </div>

              <div class="card-body">
                <!-- Tabla de códigos de referido -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="referidos-table"
                    class="table table-striped table-bordered table-hover nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Código</th>
                        <th width="15%">Referente</th>
                        <th width="15%">Beneficios</th>
                        <th width="8%">Estado</th>
                        <th width="10%">Usos</th>
                        <th width="8%">Conversión</th>
                        <th width="12%">Vigencia</th>
                        <th width="12%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado
                              $estado_class = 'estado-' . strtolower(str_replace(' ', '-', $row['estado_codigo']));
                              
                              // Determinar clase de tipo de referente
                              $tipo_class = ($row['apoderado_id'] || $row['familia_id']) ? 'tipo-personalizado' : 'tipo-general';
                              $tipo_label = ($row['apoderado_id'] || $row['familia_id']) ? 'Personalizado' : 'General';
                              
                              // Formatear fechas
                              $fecha_inicio = date('d/m/Y', strtotime($row['fecha_inicio']));
                              $fecha_fin = $row['fecha_fin'] ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Sin límite';
                              
                              // Calcular porcentaje de uso
                              $porcentaje_uso = 0;
                              if ($row['limite_usos']) {
                                  $porcentaje_uso = ($row['usos_actuales'] / $row['limite_usos']) * 100;
                              }
                              
                              // Determinar clase de días restantes
                              $dias_class = '';
                              if ($row['dias_restantes'] !== null) {
                                  if ($row['dias_restantes'] > 30) {
                                      $dias_class = 'dias-suficientes';
                                  } elseif ($row['dias_restantes'] > 7) {
                                      $dias_class = 'dias-advertencia';
                                  } else {
                                      $dias_class = 'dias-critico';
                                  }
                              }

                              echo "<tr>";
                              echo "<td>
                                      <strong>" . $row['id'] . "</strong>
                                      <br><span class='badge badge-tipo-referente $tipo_class'>$tipo_label</span>
                                    </td>";
                              echo "<td>
                                      <div class='codigo-info'>
                                        <span class='codigo-principal'>" . htmlspecialchars($row['codigo']) . "</span>
                                        <span class='codigo-descripcion'>" . 
                                        htmlspecialchars($row['descripcion'] ?? 'Sin descripción') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='referente-info'>
                                        <span class='referente-nombre'>" . htmlspecialchars($row['nombre_referente']) . "</span>
                                        " . ($row['email_referente'] ? 
                                        "<span class='referente-contacto'><i class='ti ti-mail'></i> " . htmlspecialchars($row['email_referente']) . "</span>" 
                                        : "") . "
                                        " . ($row['telefono_referente'] ? 
                                        "<span class='referente-contacto'><i class='ti ti-phone'></i> " . htmlspecialchars($row['telefono_referente']) . "</span>" 
                                        : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='beneficios-info'>
                                        <span class='beneficio-item beneficio-referente' title='Beneficio para el referente'>
                                          <i class='ti ti-gift'></i> " . htmlspecialchars($row['beneficio_referente'] ?? 'Sin beneficio') . "
                                        </span>
                                        <span class='beneficio-item beneficio-referido' title='Beneficio para el referido'>
                                          <i class='ti ti-star'></i> " . htmlspecialchars($row['beneficio_referido'] ?? 'Sin beneficio') . "
                                        </span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-estado-codigo $estado_class'>" . $row['estado_codigo'] . "</span></td>";
                              echo "<td>
                                      <div class='usos-info'>
                                        <span class='usos-numero'>" . $row['usos_actuales'] . "</span>
                                        <span class='usos-limite'>de " . ($row['limite_usos'] ?? '∞') . "</span>
                                        " . ($row['limite_usos'] ? 
                                        "<div class='usos-barra-progreso'>
                                          <div class='usos-progreso' style='width: {$porcentaje_uso}%;'></div>
                                        </div>" : "") . "
                                        <small style='color: #28a745; font-weight: 500;'>Disponibles: " . $row['usos_disponibles'] . "</small>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='conversion-info'>
                                        <span class='conversion-porcentaje'>" . $row['tasa_conversion'] . "%</span>
                                        <span class='conversion-detalle'>" . $row['usos_convertidos'] . " de " . $row['total_usos'] . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='vigencia-info'>
                                        <span class='vigencia-fecha'><i class='ti ti-calendar'></i> $fecha_inicio</span>
                                        <span class='vigencia-fecha'><i class='ti ti-calendar-event'></i> $fecha_fin</span>
                                        " . ($row['dias_restantes'] !== null ? 
                                        "<span class='vigencia-dias $dias_class'>" . $row['dias_restantes'] . " días restantes</span>" 
                                        : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='btn-grupo-referido'>
                                        <button type='button' class='btn btn-outline-primary btn-editar-codigo'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='Editar Código'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-ver-usos'
                                                data-id='" . $row['id'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='Ver Usos'>
                                          <i class='ti ti-list-details'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-" . 
                                        ($row['activo'] == 1 ? 'warning' : 'success') . " btn-toggle-estado'
                                                data-id='" . $row['id'] . "'
                                                data-estado='" . $row['activo'] . "'
                                                data-codigo='" . htmlspecialchars($row['codigo']) . "'
                                                title='" . ($row['activo'] == 1 ? 'Desactivar' : 'Activar') . "'>
                                          <i class='ti ti-" . ($row['activo'] == 1 ? 'ban' : 'check') . "'></i>
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
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Referente</th>
                        <th>Beneficios</th>
                        <th>Estado</th>
                        <th>Usos</th>
                        <th>Conversión</th>
                        <th>Vigencia</th>
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
    <?php include 'modals/incentivos_referidos/modal_gestionar_codigo.php'; ?>
    <?php include 'modals/incentivos_referidos/modal_registrar_uso.php'; ?>
    <?php include 'modals/incentivos_referidos/modal_validar_conversion.php'; ?>

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
            // Inicializar DataTable
            var table = $("#referidos-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay códigos disponibles en la tabla",
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
              "order": [[ 0, "desc" ]],
              "columnDefs": [
                { "orderable": false, "targets": 8 }
              ],
              "initComplete": function () {
                this.api().columns().every(function (index) {
                  var column = this;
                  if (index < 8) {
                    var title = $(column.header()).text();
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
                      .appendTo($(column.footer()).empty())
                      .on('keyup change clear', function () {
                        if (column.search() !== this.value) {
                          column.search(this.value).draw();
                        }
                      });
                  } else {
                    $(column.footer()).html('<strong>Acciones</strong>');
                  }
                });
              }
            });

            // Manejar click en botón editar código
            $(document).on('click', '.btn-editar-codigo', function() {
                var id = $(this).data('id');
                var codigo = $(this).data('codigo');
                
                Swal.fire({
                  title: '¿Editar Código?',
                  text: '¿Desea editar el código "' + codigo + '"?',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#6f42c1',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, editar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    cargarDatosCodigo(id);
                    $('#modalGestionarCodigo').modal('show');
                  }
                });
            });

            // Manejar click en botón ver usos
            $(document).on('click', '.btn-ver-usos', function() {
                var id = $(this).data('id');
                var codigo = $(this).data('codigo');
                
                Swal.fire({
                    title: 'Cargando usos del código',
                    text: 'Obteniendo información de "' + codigo + '"',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'acciones/incentivos_referidos/obtener_usos_codigo.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_codigo: id,
                        accion: 'obtener_usos'
                    },
                    success: function(data) {
                        if (data.success) {
                            let usosHtml = `
                                <div class="usos-container">
                                    <div class="usos-header mb-3">
                                        <h5>Historial de Usos del Código</h5>
                                        <span class="text-muted">Código: ${codigo}</span>
                                    </div>
                                    <div class="usos-content">
                            `;

                            if (data.usos && data.usos.length > 0) {
                                data.usos.forEach(uso => {
                                    let estadoBadge = uso.convertido == 1 ? 
                                        '<span class="badge" style="background-color: #B8E6B8; color: #2d5016;">✓ Convertido</span>' : 
                                        '<span class="badge" style="background-color: #FFF4B8; color: #856404;">⏳ Pendiente</span>';
                                    
                                    usosHtml += `
                                        <div class="uso-item mb-3 p-3 border-bottom" style="background-color: #F9F9F9; border-radius: 8px;">
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong style="color: #2c3e50;">${uso.lead_nombre}</strong>
                                                ${estadoBadge}
                                            </div>
                                            <div class="mb-1">
                                                <small class="text-muted">
                                                    <i class="ti ti-calendar"></i> Usado: ${uso.fecha_uso}
                                                </small>
                                            </div>
                                            ${uso.fecha_conversion ? `
                                                <div class="mb-1">
                                                    <small class="text-success">
                                                        <i class="ti ti-check-circle"></i> Convertido: ${uso.fecha_conversion}
                                                    </small>
                                                </div>
                                            ` : ''}
                                            ${uso.observaciones ? `
                                                <div class="mt-2">
                                                    <small style="color: #6c757d;">
                                                        <i class="ti ti-note"></i> ${uso.observaciones}
                                                    </small>
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                });
                            } else {
                                usosHtml += `
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle"></i>
                                        No hay registros de uso para este código.
                                    </div>
                                `;
                            }

                            usosHtml += `
                                    </div>
                                </div>
                            `;

                            Swal.fire({
                                title: 'Historial de Usos',
                                html: usosHtml,
                                width: '700px',
                                showCloseButton: true,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'usos-modal'
                                }
                            });

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo cargar el historial de usos',
                                confirmButtonColor: '#6f42c1'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo obtener el historial. Código: ' + xhr.status,
                            confirmButtonColor: '#6f42c1'
                        });
                    }
                });
            });

            // Manejar click en botón toggle estado
            $(document).on('click', '.btn-toggle-estado', function() {
                var id = $(this).data('id');
                var estado = $(this).data('estado');
                var codigo = $(this).data('codigo');
                var accion = estado == 1 ? 'desactivar' : 'activar';
                var textoAccion = estado == 1 ? 'Desactivar' : 'Activar';
                
                Swal.fire({
                  title: textoAccion + ' Código',
                  text: '¿Desea ' + accion + ' el código "' + codigo + '"?',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: estado == 1 ? '#ffc107' : '#28a745',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, ' + accion,
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Enviar solicitud AJAX para cambiar estado
                    $.ajax({
                        url: 'acciones/incentivos_referidos/gestionar_referidos.php',
                        type: 'POST',
                        data: {
                            accion: 'toggle_estado',
                            codigo_id: id,
                            nuevo_estado: estado == 1 ? 0 : 1
                        },
                        success: function(response) {
                            try {
                                var data = typeof response === 'string' ? JSON.parse(response) : response;
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Éxito!',
                                        text: data.message,
                                        confirmButtonColor: '#6f42c1'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message,
                                        confirmButtonColor: '#6f42c1'
                                    });
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error al procesar la respuesta del servidor',
                                    confirmButtonColor: '#6f42c1'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error AJAX:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo cambiar el estado del código',
                                confirmButtonColor: '#6f42c1'
                            });
                        }
                    });
                  }
                });
            });

            // Función para cargar datos del código
            function cargarDatosCodigo(codigoId) {
                // Esta función se implementará con AJAX
                console.log('Cargando datos del código:', codigoId);
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