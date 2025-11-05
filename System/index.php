<?php
session_start();
include 'bd/conexion.php';

// Obtener nombre del sistema
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE id = 1 LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
    $nombre_sistema = htmlspecialchars($row_nombre['valor']);
}

// Obtener filtros de fecha
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$anio_filtro = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$tipo_filtro = isset($_GET['tipo_filtro']) ? $_GET['tipo_filtro'] : 'anio'; // dia, mes, anio

$where_fecha = "";
$where_fecha_leads = "";
$where_fecha_interacciones = "";

switch($tipo_filtro) {
    case 'dia':
        $where_fecha = "DATE(created_at) = '$fecha_filtro'";
        $where_fecha_leads = "DATE(l.created_at) = '$fecha_filtro'";
        $where_fecha_interacciones = "DATE(i.created_at) = '$fecha_filtro'";
        break;
    case 'mes':
        $where_fecha = "DATE_FORMAT(created_at, '%Y-%m') = '$mes_filtro'";
        $where_fecha_leads = "DATE_FORMAT(l.created_at, '%Y-%m') = '$mes_filtro'";
        $where_fecha_interacciones = "DATE_FORMAT(i.created_at, '%Y-%m') = '$mes_filtro'";
        break;
    case 'anio':
        $where_fecha = "YEAR(created_at) = '$anio_filtro'";
        $where_fecha_leads = "YEAR(l.created_at) = '$anio_filtro'";
        $where_fecha_interacciones = "YEAR(i.created_at) = '$anio_filtro'";
        break;
}

// 1. Total de Leads Activos (sin cambios)
$query_leads = "SELECT COUNT(*) as total FROM leads WHERE activo = 1 AND $where_fecha";
$result_leads = $conn->query($query_leads);
$total_leads = $result_leads->fetch_assoc()['total'];

$fecha_anterior = date('Y-m-d', strtotime($fecha_filtro . ' -1 day'));
$query_leads_anterior = "SELECT COUNT(*) as total FROM leads WHERE activo = 1 AND DATE(created_at) = '$fecha_anterior'";
$result_leads_anterior = $conn->query($query_leads_anterior);
$leads_anterior = $result_leads_anterior->fetch_assoc()['total'];
$porcentaje_leads = $leads_anterior > 0 ? (($total_leads - $leads_anterior) / $leads_anterior) * 100 : 0;

// 2. Total de Estudiantes Activos (sin cambios)
$query_estudiantes = "SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1 AND estado_matricula = 'matriculado' AND $where_fecha";
$result_estudiantes = $conn->query($query_estudiantes);
$total_estudiantes = $result_estudiantes->fetch_assoc()['total'];

$query_estudiantes_anterior = "SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1 AND estado_matricula = 'matriculado' AND DATE(created_at) = '$fecha_anterior'";
$result_estudiantes_anterior = $conn->query($query_estudiantes_anterior);
$estudiantes_anterior = $result_estudiantes_anterior->fetch_assoc()['total'];
$porcentaje_estudiantes = $estudiantes_anterior > 0 ? (($total_estudiantes - $estudiantes_anterior) / $estudiantes_anterior) * 100 : 0;

// 3. Total de Familias Activas (sin cambios)
$query_familias = "SELECT COUNT(*) as total FROM familias WHERE activo = 1 AND $where_fecha";
$result_familias = $conn->query($query_familias);
$total_familias = $result_familias->fetch_assoc()['total'];

$query_familias_anterior = "SELECT COUNT(*) as total FROM familias WHERE activo = 1 AND DATE(created_at) = '$fecha_anterior'";
$result_familias_anterior = $conn->query($query_familias_anterior);
$familias_anterior = $result_familias_anterior->fetch_assoc()['total'];
$porcentaje_familias = $familias_anterior > 0 ? (($total_familias - $familias_anterior) / $familias_anterior) * 100 : 0;

// 4. Total de Interacciones (sin cambios)
$query_interacciones = "SELECT COUNT(*) as total FROM interacciones WHERE $where_fecha";
$result_interacciones = $conn->query($query_interacciones);
$total_interacciones = $result_interacciones->fetch_assoc()['total'];

$query_interacciones_anterior = "SELECT COUNT(*) as total FROM interacciones WHERE DATE(created_at) = '$fecha_anterior'";
$result_interacciones_anterior = $conn->query($query_interacciones_anterior);
$interacciones_anterior = $result_interacciones_anterior->fetch_assoc()['total'];
$porcentaje_interacciones = $interacciones_anterior > 0 ? (($total_interacciones - $interacciones_anterior) / $interacciones_anterior) * 100 : 0;

// 5. Leads por Estado (CORREGIDO - línea 81)
$query_leads_estado = "SELECT e.nombre, COUNT(l.id) as total 
                       FROM estados_lead e 
                       LEFT JOIN leads l ON e.id = l.estado_lead_id AND l.activo = 1 AND $where_fecha_leads
                       WHERE e.activo = 1 
                       GROUP BY e.id, e.nombre 
                       ORDER BY e.orden_display";
$result_leads_estado = $conn->query($query_leads_estado);
$leads_por_estado = [];
while($row = $result_leads_estado->fetch_assoc()) {
    $leads_por_estado[] = $row;
}

// 6. Próximos Eventos (sin cambios)
$query_eventos = "SELECT titulo, fecha_inicio, ubicacion, estado 
                  FROM eventos 
                  WHERE fecha_inicio >= NOW() 
                  ORDER BY fecha_inicio ASC 
                  LIMIT 5";
$result_eventos = $conn->query($query_eventos);

// 7. Estudiantes por Grado (sin cambios)
$query_estudiantes_grado = "SELECT g.nombre as grado, COUNT(e.id) as total 
                            FROM grados g 
                            LEFT JOIN estudiantes e ON g.id = e.grado_id AND e.activo = 1 AND e.estado_matricula = 'matriculado'
                            WHERE g.activo = 1 
                            GROUP BY g.id, g.nombre 
                            ORDER BY g.orden_display";
$result_estudiantes_grado = $conn->query($query_estudiantes_grado);
$estudiantes_por_grado = [];
while($row = $result_estudiantes_grado->fetch_assoc()) {
    $estudiantes_por_grado[] = $row;
}

// 8. Últimas Interacciones (CORREGIDO - línea 116)
$query_ultimas_interacciones = "SELECT i.asunto, i.descripcion, t.nombre as tipo, 
                                 i.fecha_realizada, i.estado, i.resultado
                                 FROM interacciones i
                                 JOIN tipos_interaccion t ON i.tipo_interaccion_id = t.id
                                 WHERE $where_fecha_interacciones
                                 ORDER BY i.created_at DESC 
                                 LIMIT 10";
$result_ultimas_interacciones = $conn->query($query_ultimas_interacciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <title><?php echo $nombre_sistema; ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css">
  <link rel="stylesheet" href="assets/fonts/feather.css">
  <link rel="stylesheet" href="assets/fonts/fontawesome.css">
  <link rel="stylesheet" href="assets/fonts/material.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/style-preset.css">
  <style>
    .filter-container {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
  </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
  <div class="loader-bg">
    <div class="loader-track">
      <div class="loader-fill"></div>
    </div>
  </div>

  <?php include 'includes/sidebar.php'; ?>
  <?php include 'includes/header.php'; ?>

  <div class="pc-container">
    <div class="pc-content">
      <div class="page-header">
        <div class="page-block">
          <div class="row align-items-center">
            <div class="col-md-12">
              <div class="page-header-title">
                <h5 class="m-b-10">Dashboard CRM</h5>
              </div>
              <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Dashboard</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="filter-container">
        <form method="GET" action="" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Tipo de Filtro</label>
            <select name="tipo_filtro" class="form-select" onchange="toggleFilters(this.value)">
              <option value="dia" <?php echo $tipo_filtro == 'dia' ? 'selected' : ''; ?>>Por Día</option>
              <option value="mes" <?php echo $tipo_filtro == 'mes' ? 'selected' : ''; ?>>Por Mes</option>
              <option value="anio" <?php echo $tipo_filtro == 'anio' ? 'selected' : ''; ?>>Por Año</option>
            </select>
          </div>
          
          <div class="col-md-3" id="filtro_dia" style="display: <?php echo $tipo_filtro == 'dia' ? 'block' : 'none'; ?>;">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="<?php echo $fecha_filtro; ?>">
          </div>
          
          <div class="col-md-3" id="filtro_mes" style="display: <?php echo $tipo_filtro == 'mes' ? 'block' : 'none'; ?>;">
            <label class="form-label">Mes</label>
            <input type="month" name="mes" class="form-control" value="<?php echo $mes_filtro; ?>">
          </div>
          
          <div class="col-md-3" id="filtro_anio" style="display: <?php echo $tipo_filtro == 'anio' ? 'block' : 'none'; ?>;">
            <label class="form-label">Año</label>
            <input type="number" name="anio" class="form-control" value="<?php echo $anio_filtro; ?>" min="2020" max="2099">
          </div>
          
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
          </div>
        </form>
      </div>

      <!-- Métricas -->
      <div class="row">
        <div class="col-md-6 col-xl-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-2 f-w-400 text-muted">Total Leads</h6>
              <h4 class="mb-3"><?php echo number_format($total_leads); ?> 
                <span class="badge bg-light-<?php echo $porcentaje_leads >= 0 ? 'primary' : 'danger'; ?> border border-<?php echo $porcentaje_leads >= 0 ? 'primary' : 'danger'; ?>">
                  <i class="ti ti-trending-<?php echo $porcentaje_leads >= 0 ? 'up' : 'down'; ?>"></i> 
                  <?php echo abs(round($porcentaje_leads, 1)); ?>%
                </span>
              </h4>
              <p class="mb-0 text-muted text-sm">Leads en el período seleccionado</p>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-2 f-w-400 text-muted">Estudiantes Activos</h6>
              <h4 class="mb-3"><?php echo number_format($total_estudiantes); ?> 
                <span class="badge bg-light-<?php echo $porcentaje_estudiantes >= 0 ? 'success' : 'danger'; ?> border border-<?php echo $porcentaje_estudiantes >= 0 ? 'success' : 'danger'; ?>">
                  <i class="ti ti-trending-<?php echo $porcentaje_estudiantes >= 0 ? 'up' : 'down'; ?>"></i> 
                  <?php echo abs(round($porcentaje_estudiantes, 1)); ?>%
                </span>
              </h4>
              <p class="mb-0 text-muted text-sm">Estudiantes matriculados</p>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-2 f-w-400 text-muted">Familias Activas</h6>
              <h4 class="mb-3"><?php echo number_format($total_familias); ?> 
                <span class="badge bg-light-<?php echo $porcentaje_familias >= 0 ? 'warning' : 'danger'; ?> border border-<?php echo $porcentaje_familias >= 0 ? 'warning' : 'danger'; ?>">
                  <i class="ti ti-trending-<?php echo $porcentaje_familias >= 0 ? 'up' : 'down'; ?>"></i> 
                  <?php echo abs(round($porcentaje_familias, 1)); ?>%
                </span>
              </h4>
              <p class="mb-0 text-muted text-sm">Familias registradas</p>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-2 f-w-400 text-muted">Interacciones</h6>
              <h4 class="mb-3"><?php echo number_format($total_interacciones); ?> 
                <span class="badge bg-light-info border border-info">
                  <i class="ti ti-trending-<?php echo $porcentaje_interacciones >= 0 ? 'up' : 'down'; ?>"></i> 
                  <?php echo abs(round($porcentaje_interacciones, 1)); ?>%
                </span>
              </h4>
              <p class="mb-0 text-muted text-sm">Contactos realizados</p>
            </div>
          </div>
        </div>

        <!-- Gráfico Leads por Estado -->
        <div class="col-md-12 col-xl-8">
          <h5 class="mb-3">Distribución de Leads por Estado</h5>
          <div class="card">
            <div class="card-body">
              <canvas id="leadsChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Estudiantes por Grado -->
        <div class="col-md-12 col-xl-4">
          <h5 class="mb-3">Estudiantes por Grado</h5>
          <div class="card">
            <div class="card-body">
              <canvas id="estudiantesChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Últimas Interacciones -->
        <div class="col-md-12 col-xl-8">
          <h5 class="mb-3">Últimas Interacciones</h5>
          <div class="card tbl-card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                  <thead>
                    <tr>
                      <th>ASUNTO</th>
                      <th>TIPO</th>
                      <th>FECHA</th>
                      <th>ESTADO</th>
                      <th>RESULTADO</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($interaccion = $result_ultimas_interacciones->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($interaccion['asunto']); ?></td>
                      <td><?php echo htmlspecialchars($interaccion['tipo']); ?></td>
                      <td><?php echo $interaccion['fecha_realizada'] ? date('d/m/Y H:i', strtotime($interaccion['fecha_realizada'])) : 'Pendiente'; ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo $interaccion['estado'] == 'realizado' ? 'success' : 
                               ($interaccion['estado'] == 'programado' ? 'warning' : 'secondary'); 
                        ?>">
                          <?php echo ucfirst($interaccion['estado']); ?>
                        </span>
                      </td>
                      <td>
                        <?php if($interaccion['resultado']): ?>
                          <span class="badge bg-<?php 
                            echo $interaccion['resultado'] == 'exitoso' ? 'success' : 
                                 ($interaccion['resultado'] == 'convertido' ? 'primary' : 'danger'); 
                          ?>">
                            <?php echo ucfirst($interaccion['resultado']); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Próximos Eventos -->
        <div class="col-md-12 col-xl-4">
          <h5 class="mb-3">Próximos Eventos</h5>
          <div class="card">
            <div class="list-group list-group-flush">
              <?php while($evento = $result_eventos->fetch_assoc()): ?>
                <a href="#" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                    <small><?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?></small>
                  </div>
                  <p class="mb-1 text-muted small"><?php echo htmlspecialchars($evento['ubicacion']); ?></p>
                  <span class="badge bg-<?php 
                    echo $evento['estado'] == 'programado' ? 'primary' : 
                         ($evento['estado'] == 'en_curso' ? 'success' : 'secondary'); 
                  ?>"><?php echo ucfirst($evento['estado']); ?></span>
                </a>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script src="assets/js/plugins/popper.min.js"></script>
  <script src="assets/js/plugins/simplebar.min.js"></script>
  <script src="assets/js/plugins/bootstrap.min.js"></script>
  <script src="assets/js/fonts/custom-font.js"></script>
  <script src="assets/js/pcoded.js"></script>
  <script src="assets/js/plugins/feather.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
    function toggleFilters(tipo) {
      document.getElementById('filtro_dia').style.display = tipo === 'dia' ? 'block' : 'none';
      document.getElementById('filtro_mes').style.display = tipo === 'mes' ? 'block' : 'none';
      document.getElementById('filtro_anio').style.display = tipo === 'anio' ? 'block' : 'none';
    }

    // Gráfico Leads por Estado
    const ctxLeads = document.getElementById('leadsChart').getContext('2d');
    new Chart(ctxLeads, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($leads_por_estado, 'nombre')); ?>,
        datasets: [{
          label: 'Leads',
          data: <?php echo json_encode(array_column($leads_por_estado, 'total')); ?>,
          backgroundColor: ['#e3f2fd', '#bbdefb', '#90caf9', '#64b5f6', '#42a5f5', '#2196f3', '#1e88e5']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        }
      }
    });

    // Gráfico Estudiantes por Grado
    const ctxEstudiantes = document.getElementById('estudiantesChart').getContext('2d');
    new Chart(ctxEstudiantes, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode(array_column($estudiantes_por_grado, 'grado')); ?>,
        datasets: [{
          data: <?php echo json_encode(array_column($estudiantes_por_grado, 'total')); ?>,
          backgroundColor: ['#ffebee', '#fce4ec', '#f3e5f5', '#ede7f6', '#e8eaf6', '#e3f2fd', '#e1f5fe', '#e0f7fa', '#e0f2f1']
        }]
      },
      options: {
        responsive: true
      }
    });
  </script>
</body>
</html>