<?php
session_start();
include 'bd/conexion.php';

// Obtener nombre del sistema
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
    $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
    $nombre_sistema = "CRM Escolar";
}

// ============================================
// CAPTURAR FILTROS
// ============================================
$fecha_inicio = isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fecha_fin = isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
$canal_id = isset($_GET['canal_id']) && !empty($_GET['canal_id']) ? intval($_GET['canal_id']) : null;
$nivel_id = isset($_GET['nivel_id']) && !empty($_GET['nivel_id']) ? intval($_GET['nivel_id']) : null;

// ============================================
// CONSTRUIR CONDICIONES WHERE
// ============================================
$where_conditions = [];
$where_sql = "";

if ($fecha_inicio) {
    $where_conditions[] = "l.created_at >= '" . $conn->real_escape_string($fecha_inicio) . "'";
}
if ($fecha_fin) {
    $where_conditions[] = "l.created_at <= '" . $conn->real_escape_string($fecha_fin) . " 23:59:59'";
}
if ($canal_id) {
    $where_conditions[] = "l.canal_captacion_id = " . $canal_id;
}
if ($nivel_id) {
    $where_conditions[] = "g.nivel_educativo_id = " . $nivel_id;
}

if (count($where_conditions) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_conditions);
}

// Para consultas sin JOIN a grados
$where_conditions_simple = [];
$where_sql_simple = "";

if ($fecha_inicio) {
    $where_conditions_simple[] = "created_at >= '" . $conn->real_escape_string($fecha_inicio) . "'";
}
if ($fecha_fin) {
    $where_conditions_simple[] = "created_at <= '" . $conn->real_escape_string($fecha_fin) . " 23:59:59'";
}
if ($canal_id) {
    $where_conditions_simple[] = "canal_captacion_id = " . $canal_id;
}

if (count($where_conditions_simple) > 0) {
    $where_sql_simple = " WHERE " . implode(" AND ", $where_conditions_simple);
}

// ============================================
// CONSULTAS CON FILTROS APLICADOS
// ============================================

// Consulta para datos generales de leads
$sql_general = "SELECT 
    COUNT(*) as total_leads,
    COUNT(CASE WHEN estado_lead_id = 5 THEN 1 END) as convertidos,
    COUNT(CASE WHEN estado_lead_id IN (6, 7) THEN 1 END) as perdidos,
    COUNT(CASE WHEN estado_lead_id NOT IN (5, 6, 7) THEN 1 END) as en_proceso,
    ROUND(AVG(puntaje_interes), 2) as puntaje_promedio,
    COUNT(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as leads_ano_actual,
    COUNT(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) - 1 THEN 1 END) as leads_ano_anterior
FROM leads" . $where_sql_simple;
$result_general = $conn->query($sql_general);
$datos_generales = $result_general->fetch_assoc();

// Calcular tasa de conversión
$tasa_conversion = $datos_generales['total_leads'] > 0 
    ? round(($datos_generales['convertidos'] / $datos_generales['total_leads']) * 100, 2) 
    : 0;

// Calcular variación año a año
$variacion_anual = $datos_generales['leads_ano_anterior'] > 0
    ? round((($datos_generales['leads_ano_actual'] - $datos_generales['leads_ano_anterior']) / $datos_generales['leads_ano_anterior']) * 100, 2)
    : 0;

// Consulta por canal de captación
$where_canal = $where_sql_simple;
if ($canal_id && !empty($where_canal)) {
    $where_canal = str_replace("canal_captacion_id = " . $canal_id, "cc.id = " . $canal_id, $where_canal);
} else if ($canal_id) {
    $where_canal = " WHERE cc.id = " . $canal_id;
}

// Agregar filtros de fecha para canales
$fecha_conditions_canal = [];
if ($fecha_inicio) {
    $fecha_conditions_canal[] = "l.created_at >= '" . $conn->real_escape_string($fecha_inicio) . "'";
}
if ($fecha_fin) {
    $fecha_conditions_canal[] = "l.created_at <= '" . $conn->real_escape_string($fecha_fin) . " 23:59:59'";
}

$having_canal = "";
if (count($fecha_conditions_canal) > 0) {
    $where_canal_leads = " AND " . implode(" AND ", $fecha_conditions_canal);
} else {
    $where_canal_leads = "";
}

$sql_canales = "SELECT 
    cc.id,
    cc.nombre,
    cc.tipo,
    COUNT(l.id) as total_leads,
    COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) as convertidos,
    ROUND(AVG(l.puntaje_interes), 2) as puntaje_promedio,
    ROUND((COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) * 100.0 / NULLIF(COUNT(l.id), 0)), 2) as tasa_conversion
FROM canales_captacion cc
LEFT JOIN leads l ON cc.id = l.canal_captacion_id" . $where_canal_leads . "
WHERE cc.activo = 1" . ($canal_id ? " AND cc.id = " . $canal_id : "") . "
GROUP BY cc.id, cc.nombre, cc.tipo
ORDER BY total_leads DESC";
$result_canales = $conn->query($sql_canales);

// Consulta por estado de lead
$sql_estados = "SELECT 
    el.id,
    el.nombre,
    el.color,
    el.es_final,
    COUNT(l.id) as cantidad,
    ROUND((COUNT(l.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM leads" . $where_sql_simple . "), 0)), 2) as porcentaje
FROM estados_lead el
LEFT JOIN leads l ON el.id = l.estado_lead_id" . $where_sql_simple . "
WHERE el.activo = 1
GROUP BY el.id, el.nombre, el.color, el.es_final
ORDER BY el.orden_display";
$result_estados = $conn->query($sql_estados);

// Consulta por nivel educativo
$where_nivel = "";
if ($nivel_id) {
    $where_nivel = " AND ne.id = " . $nivel_id;
}

$fecha_nivel_conditions = [];
if ($fecha_inicio) {
    $fecha_nivel_conditions[] = "l.created_at >= '" . $conn->real_escape_string($fecha_inicio) . "'";
}
if ($fecha_fin) {
    $fecha_nivel_conditions[] = "l.created_at <= '" . $conn->real_escape_string($fecha_fin) . " 23:59:59'";
}
if ($canal_id) {
    $fecha_nivel_conditions[] = "l.canal_captacion_id = " . $canal_id;
}

$where_nivel_leads = "";
if (count($fecha_nivel_conditions) > 0) {
    $where_nivel_leads = " AND " . implode(" AND ", $fecha_nivel_conditions);
}

$sql_niveles = "SELECT 
    ne.id,
    ne.nombre,
    COUNT(l.id) as total_leads,
    COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) as convertidos,
    ROUND((COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) * 100.0 / NULLIF(COUNT(l.id), 0)), 2) as tasa_conversion,
    ROUND(AVG(l.puntaje_interes), 2) as puntaje_promedio
FROM niveles_educativos ne
LEFT JOIN grados g ON ne.id = g.nivel_educativo_id
LEFT JOIN leads l ON g.id = l.grado_interes_id" . $where_nivel_leads . "
WHERE ne.activo = 1" . $where_nivel . "
GROUP BY ne.id, ne.nombre
ORDER BY ne.orden_display";
$result_niveles = $conn->query($sql_niveles);

// Consulta de tendencias mensuales (últimos 12 meses)
$sql_tendencias = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as mes,
    COUNT(*) as total_leads,
    COUNT(CASE WHEN estado_lead_id = 5 THEN 1 END) as convertidos,
    ROUND(AVG(puntaje_interes), 2) as puntaje_promedio
FROM leads
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)" . 
($where_sql_simple ? str_replace("WHERE", "AND", $where_sql_simple) : "") . "
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY mes";
$result_tendencias = $conn->query($sql_tendencias);

// Consulta de eficiencia por grado específico
$sql_grados = "SELECT 
    g.id,
    g.nombre,
    ne.nombre as nivel_educativo,
    COUNT(l.id) as total_leads,
    COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) as convertidos,
    ROUND((COUNT(CASE WHEN l.estado_lead_id = 5 THEN 1 END) * 100.0 / NULLIF(COUNT(l.id), 0)), 2) as tasa_conversion
FROM grados g
INNER JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
LEFT JOIN leads l ON g.id = l.grado_interes_id" . $where_nivel_leads . "
WHERE g.activo = 1" . ($nivel_id ? " AND ne.id = " . $nivel_id : "") . "
GROUP BY g.id, g.nombre, ne.nombre
HAVING total_leads > 0
ORDER BY ne.orden_display, g.orden_display";
$result_grados = $conn->query($sql_grados);

// Preparar datos para gráficos en JavaScript
$datos_canales_chart = [];
while($row = $result_canales->fetch_assoc()) {
    $datos_canales_chart[] = $row;
}
$result_canales->data_seek(0);

$datos_estados_chart = [];
while($row = $result_estados->fetch_assoc()) {
    $datos_estados_chart[] = $row;
}
$result_estados->data_seek(0);

$datos_tendencias_chart = [];
while($row = $result_tendencias->fetch_assoc()) {
    $datos_tendencias_chart[] = $row;
}
$result_tendencias->data_seek(0);

$datos_niveles_chart = [];
while($row = $result_niveles->fetch_assoc()) {
    $datos_niveles_chart[] = $row;
}
$result_niveles->data_seek(0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Análisis Comparativo - <?php echo $nombre_sistema; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />

    <style>
        .stats-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 20px;
        }
        .filter-card {
            color: black;
            margin-bottom: 20px;
        }
        .filter-card .form-label {
            color: black;
            font-weight: 500;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border: 1px solid rgba(0,0,0,0.2);
            background: white;
            color: black;
        }
        .filter-card .form-control::placeholder {
            color: rgba(0,0,0,0.5);
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            background: white;
            color: black;
            border-color: #667eea;
        }
        .stat-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 12px;
            font-weight: 500;
        }
        .comparison-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .filtro-activo {
            background-color: #d4edda !important;
            border-color: #28a745 !important;
        }
        .badge-filtro {
            display: inline-block;
            margin: 2px;
            padding: 4px 8px;
            background-color: #667eea;
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
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

    <section class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="javascript: void(0)">Leads & Prospección</a></li>
                                <li class="breadcrumb-item" aria-current="page">Análisis Comparativo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-1">
                                <i class="ti ti-chart-bar me-2"></i>
                                Análisis Comparativo de Leads
                            </h3>
                            <small class="text-muted">
                                Compare resultados por campaña, canales, niveles educativos y períodos temporales.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros Activos -->
            <?php if($fecha_inicio || $fecha_fin || $canal_id || $nivel_id): ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-info">
                        <strong><i class="ti ti-filter me-1"></i> Filtros Activos:</strong>
                        <?php if($fecha_inicio): ?>
                            <span class="badge-filtro">Desde: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></span>
                        <?php endif; ?>
                        <?php if($fecha_fin): ?>
                            <span class="badge-filtro">Hasta: <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></span>
                        <?php endif; ?>
                        <?php if($canal_id): 
                            $canal_nombre = $conn->query("SELECT nombre FROM canales_captacion WHERE id = $canal_id")->fetch_assoc()['nombre'];
                        ?>
                            <span class="badge-filtro">Canal: <?php echo $canal_nombre; ?></span>
                        <?php endif; ?>
                        <?php if($nivel_id): 
                            $nivel_nombre = $conn->query("SELECT nombre FROM niveles_educativos WHERE id = $nivel_id")->fetch_assoc()['nombre'];
                        ?>
                            <span class="badge-filtro">Nivel: <?php echo $nivel_nombre; ?></span>
                        <?php endif; ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-sm btn-outline-danger ms-2">
                            <i class="ti ti-x me-1"></i>Limpiar Filtros
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <form id="filtrosForm" method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-calendar me-1"></i>
                                                Fecha Inicio
                                            </label>
                                            <input type="date" class="form-control <?php echo $fecha_inicio ? 'filtro-activo' : ''; ?>" 
                                                   id="fecha_inicio" name="fecha_inicio" 
                                                   value="<?php echo $fecha_inicio ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-calendar me-1"></i>
                                                Fecha Fin
                                            </label>
                                            <input type="date" class="form-control <?php echo $fecha_fin ? 'filtro-activo' : ''; ?>" 
                                                   id="fecha_fin" name="fecha_fin"
                                                   value="<?php echo $fecha_fin ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-target me-1"></i>
                                                Canal de Captación
                                            </label>
                                            <select class="form-select <?php echo $canal_id ? 'filtro-activo' : ''; ?>" 
                                                    id="canal_id" name="canal_id">
                                                <option value="">Todos los canales</option>
                                                <?php
                                                $sql_canales_filter = "SELECT id, nombre FROM canales_captacion WHERE activo = 1 ORDER BY nombre";
                                                $result_filter = $conn->query($sql_canales_filter);
                                                while($canal = $result_filter->fetch_assoc()) {
                                                    $selected = ($canal_id == $canal['id']) ? 'selected' : '';
                                                    echo "<option value='{$canal['id']}' {$selected}>{$canal['nombre']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="ti ti-school me-1"></i>
                                                Nivel Educativo
                                            </label>
                                            <select class="form-select <?php echo $nivel_id ? 'filtro-activo' : ''; ?>" 
                                                    id="nivel_id" name="nivel_id">
                                                <option value="">Todos los niveles</option>
                                                <?php
                                                $sql_niveles_filter = "SELECT id, nombre FROM niveles_educativos WHERE activo = 1 ORDER BY orden_display";
                                                $result_filter = $conn->query($sql_niveles_filter);
                                                while($nivel = $result_filter->fetch_assoc()) {
                                                    $selected = ($nivel_id == $nivel['id']) ? 'selected' : '';
                                                    echo "<option value='{$nivel['id']}' {$selected}>{$nivel['nombre']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary me-2">
                                            <i class="ti ti-refresh me-1"></i>
                                            Limpiar Filtros
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter me-1"></i>
                                            Aplicar Filtros
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Principales -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Leads</p>
                                    <h3 class="mb-0"><?php echo number_format($datos_generales['total_leads']); ?></h3>
                                    <small class="text-muted">
                                        Año actual: <?php echo number_format($datos_generales['leads_ano_actual']); ?>
                                    </small>
                                </div>
                                <div class="avtar bg-light-primary">
                                    <i class="ti ti-users text-primary" style="font-size: 24px;"></i>
                                </div>
                            </div>
                            <?php if($variacion_anual != 0): ?>
                            <div class="mt-2">
                                <span class="stat-badge <?php echo $variacion_anual > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <i class="ti ti-trending-<?php echo $variacion_anual > 0 ? 'up' : 'down'; ?> me-1"></i>
                                    <?php echo abs($variacion_anual); ?>% vs año anterior
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Convertidos</p>
                                    <h3 class="mb-0"><?php echo number_format($datos_generales['convertidos']); ?></h3>
                                    <small class="text-success">Matriculados</small>
                                </div>
                                <div class="avtar bg-light-success">
                                    <i class="ti ti-check text-success" style="font-size: 24px;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="stat-badge bg-success">
                                    <?php echo $tasa_conversion; ?>% Tasa de conversión
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">En Proceso</p>
                                    <h3 class="mb-0"><?php echo number_format($datos_generales['en_proceso']); ?></h3>
                                    <small class="text-info">Oportunidades activas</small>
                                </div>
                                <div class="avtar bg-light-info">
                                    <i class="ti ti-clock text-info" style="font-size: 24px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Perdidos</p>
                                    <h3 class="mb-0"><?php echo number_format($datos_generales['perdidos']); ?></h3>
                                    <small class="text-danger">No interesados</small>
                                </div>
                                <div class="avtar bg-light-danger">
                                    <i class="ti ti-x text-danger" style="font-size: 24px;"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="stat-badge bg-light text-dark">
                                    Puntaje promedio: <?php echo $datos_generales['puntaje_promedio'] ?? 0; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-chart-pie me-2"></i>Distribución por Canal de Captación</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartCanales"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-chart-donut me-2"></i>Distribución por Estado</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartEstados"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resto del código de tablas igual que antes... -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-table me-2"></i>Análisis Detallado por Canal de Captación</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaCanales" class="table table-bordered comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Canal</th>
                                            <th>Tipo</th>
                                            <th>Total Leads</th>
                                            <th>Convertidos</th>
                                            <th>Tasa Conversión</th>
                                            <th>Puntaje Promedio</th>
                                            <th>Eficiencia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($canal = $result_canales->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($canal['nombre']); ?></strong></td>
                                            <td><span class="badge bg-light-secondary"><?php echo htmlspecialchars($canal['tipo']); ?></span></td>
                                            <td><?php echo number_format($canal['total_leads']); ?></td>
                                            <td><?php echo number_format($canal['convertidos']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $canal['tasa_conversion'] >= 20 ? 'success' : ($canal['tasa_conversion'] >= 10 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $canal['tasa_conversion'] ?? 0; ?>%
                                                </span>
                                            </td>
                                            <td><?php echo $canal['puntaje_promedio'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                $eficiencia = ($canal['tasa_conversion'] ?? 0) * ($canal['puntaje_promedio'] ?? 0) / 100;
                                                $eficiencia_label = $eficiencia >= 15 ? 'Alta' : ($eficiencia >= 8 ? 'Media' : 'Baja');
                                                $eficiencia_color = $eficiencia >= 15 ? 'success' : ($eficiencia >= 8 ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?php echo $eficiencia_color; ?>">
                                                    <?php echo $eficiencia_label; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-school me-2"></i>Análisis por Nivel Educativo</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaNiveles" class="table table-bordered comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Nivel Educativo</th>
                                            <th>Total Leads</th>
                                            <th>Convertidos</th>
                                            <th>Tasa Conversión</th>
                                            <th>Puntaje Promedio</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($nivel = $result_niveles->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($nivel['nombre']); ?></strong></td>
                                            <td><?php echo number_format($nivel['total_leads']); ?></td>
                                            <td><?php echo number_format($nivel['convertidos']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($nivel['tasa_conversion'] ?? 0) >= 20 ? 'success' : (($nivel['tasa_conversion'] ?? 0) >= 10 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $nivel['tasa_conversion'] ?? 0; ?>%
                                                </span>
                                            </td>
                                            <td><?php echo $nivel['puntaje_promedio'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                $performance = (($nivel['tasa_conversion'] ?? 0) + ($nivel['puntaje_promedio'] ?? 0)) / 2;
                                                $performance_label = $performance >= 40 ? 'Excelente' : ($performance >= 25 ? 'Bueno' : ($performance >= 15 ? 'Regular' : 'Bajo'));
                                                $performance_color = $performance >= 40 ? 'success' : ($performance >= 25 ? 'info' : ($performance >= 15 ? 'warning' : 'danger'));
                                                ?>
                                                <span class="badge bg-<?php echo $performance_color; ?>">
                                                    <?php echo $performance_label; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-list-details me-2"></i>Análisis Detallado por Grado</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaGrados" class="table table-bordered comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Nivel</th>
                                            <th>Grado</th>
                                            <th>Total Leads</th>
                                            <th>Convertidos</th>
                                            <th>Tasa Conversión</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($grado = $result_grados->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($grado['nivel_educativo']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($grado['nombre']); ?></strong></td>
                                            <td><?php echo number_format($grado['total_leads']); ?></td>
                                            <td><?php echo number_format($grado['convertidos']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                        <div class="progress-bar <?php echo ($grado['tasa_conversion'] ?? 0) >= 20 ? 'bg-success' : (($grado['tasa_conversion'] ?? 0) >= 10 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                             style="width: <?php echo min($grado['tasa_conversion'] ?? 0, 100); ?>%">
                                                            <?php echo $grado['tasa_conversion'] ?? 0; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($grado['tasa_conversion'] ?? 0) >= 20 ? 'success' : (($grado['tasa_conversion'] ?? 0) >= 10 ? 'info' : 'secondary'); ?>">
                                                    <?php 
                                                    if(($grado['tasa_conversion'] ?? 0) >= 20) {
                                                        echo 'Alta demanda';
                                                    } elseif(($grado['tasa_conversion'] ?? 0) >= 10) {
                                                        echo 'Demanda media';
                                                    } else {
                                                        echo 'Baja demanda';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="ti ti-chart-line me-2"></i>Tendencia de Leads y Conversiones (Últimos 12 Meses)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 400px;">
                                <canvas id="chartTendencias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    $(document).ready(function() {
        $('#tablaCanales, #tablaNiveles, #tablaGrados').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [[2, 'desc']]
        });

        const datosCanales = <?php echo json_encode($datos_canales_chart); ?>;
        const datosEstados = <?php echo json_encode($datos_estados_chart); ?>;
        const datosTendencias = <?php echo json_encode($datos_tendencias_chart); ?>;

        const ctxCanales = document.getElementById('chartCanales').getContext('2d');
        new Chart(ctxCanales, {
            type: 'pie',
            data: {
                labels: datosCanales.map(d => d.nombre),
                datasets: [{
                    data: datosCanales.map(d => d.total_leads),
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#30cfd0', '#a8edea']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        const ctxEstados = document.getElementById('chartEstados').getContext('2d');
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: datosEstados.map(d => d.nombre),
                datasets: [{
                    data: datosEstados.map(d => d.cantidad),
                    backgroundColor: datosEstados.map(d => d.color)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = datosEstados[context.dataIndex].porcentaje || 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        const ctxTendencias = document.getElementById('chartTendencias').getContext('2d');
        new Chart(ctxTendencias, {
            type: 'line',
            data: {
                labels: datosTendencias.map(d => {
                    const [year, month] = d.mes.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('es-PE', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Total Leads',
                        data: datosTendencias.map(d => d.total_leads),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Convertidos',
                        data: datosTendencias.map(d => d.convertidos),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    });
    </script>
</body>
</html>

<?php $conn->close(); ?>