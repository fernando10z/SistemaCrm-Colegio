<?php
session_start();
include 'bd/conexion.php';

// Obtener nombre del sistema - VERSIÓN CORREGIDA
$nombre_sistema = "CRM Escolar"; // Valor por defecto
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);

if ($result_nombre && $result_nombre->num_rows > 0) {
    $row_nombre = $result_nombre->fetch_assoc();
    if (isset($row_nombre['valor']) && !empty($row_nombre['valor'])) {
        $nombre_sistema = htmlspecialchars($row_nombre['valor'], ENT_QUOTES, 'UTF-8');
    }
}

// Obtener información del usuario logueado
$usuario_id = $_SESSION['usuario_id'] ?? 1;
$query_usuario = "SELECT u.*, r.nombre as rol_nombre FROM usuarios u 
                  LEFT JOIN roles r ON u.rol_id = r.id 
                  WHERE u.id = ?";
$stmt = $conn->prepare($query_usuario);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_usuario = $stmt->get_result();

// Manejar resultado del usuario
if ($result_usuario && $result_usuario->num_rows > 0) {
    $usuario = $result_usuario->fetch_assoc();
} else {
    // Usuario por defecto si no se encuentra
    $usuario = [
        'id' => 1,
        'nombre' => 'Usuario',
        'apellidos' => 'Sistema',
        'rol_nombre' => 'Administrador'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard por Área - <?php echo $nombre_sistema; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="assets/fonts/material.css" />
    
    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />
    
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" />
    
    <style>
        :root {
            --pastel-blue: #A8DADC;
            --pastel-green: #B8E6B8;
            --pastel-yellow: #F9E4A3;
            --pastel-pink: #FFD4D4;
            --pastel-purple: #D4C5E3;
            --pastel-orange: #FFD8B8;
            --pastel-teal: #B8E6D4;
            --pastel-red: #FFB8B8;
        }
        
        body {
            background-color: #FFFFFF;
        }
        
        .dashboard-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .metric-card {
            border-radius: 12px;
            padding: 20px;
            color: #2C3E50;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .metric-card.blue { background: linear-gradient(135deg, var(--pastel-blue) 0%, #E8F4F5 100%); }
        .metric-card.green { background: linear-gradient(135deg, var(--pastel-green) 0%, #E8F5E8 100%); }
        .metric-card.yellow { background: linear-gradient(135deg, var(--pastel-yellow) 0%, #FFF9E6 100%); }
        .metric-card.pink { background: linear-gradient(135deg, var(--pastel-pink) 0%, #FFE8E8 100%); }
        .metric-card.purple { background: linear-gradient(135deg, var(--pastel-purple) 0%, #F0EBF7 100%); }
        .metric-card.orange { background: linear-gradient(135deg, var(--pastel-orange) 0%, #FFF0E6 100%); }
        .metric-card.teal { background: linear-gradient(135deg, var(--pastel-teal) 0%, #E8F5F0 100%); }
        .metric-card.red { background: linear-gradient(135deg, var(--pastel-red) 0%, #FFE8E8 100%); }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.85;
            font-weight: 500;
        }
        
        .metric-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 15px;
        }
        
        .filter-section {
            background: #F8F9FA;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #2C3E50;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--pastel-blue);
        }
        
        .trend-indicator {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .trend-up {
            background: var(--pastel-green);
            color: #2E7D32;
        }
        
        .trend-down {
            background: var(--pastel-red);
            color: #C62828;
        }
        
        .trend-neutral {
            background: var(--pastel-yellow);
            color: #F57C00;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--pastel-blue);
            color: #2C3E50;
            font-weight: 600;
            border: none;
        }
        
        .table td {
            border-color: #F0F0F0;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--pastel-blue);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Pre-loader -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <section class="pc-container">
        <div class="pc-content">
            
            <!-- Breadcrumb -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                                <li class="breadcrumb-item">Reportes y Analítica</li>
                                <li class="breadcrumb-item" aria-current="page">Dashboard por Área</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <h3 class="mb-2">Dashboard por Área</h3>
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-user me-1"></i>
                                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?> 
                                        <span class="badge bg-light-primary text-primary ms-2">
                                            <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="actualizarDashboard()">
                                        <i class="ti ti-refresh me-1"></i>
                                        Actualizar
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarDashboard()">
                                        <i class="ti ti-download me-1"></i>
                                        Exportar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="row">
                <div class="col-12">
                    <div class="filter-section">
                        <h5 class="section-title">
                            <i class="ti ti-filter"></i>
                            Filtros de Datos
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Rango de Fechas</label>
                                <select class="form-select" id="rangoFechas">
                                    <option value="hoy">Hoy</option>
                                    <option value="semana">Esta Semana</option>
                                    <option value="mes">Este Mes</option>
                                    <option value="trimestre">Este Trimestre</option>
                                    <option value="anio" selected>Este Año</option>
                                    <option value="personalizado">Personalizado</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="fechaInicioContainer" style="display:none;">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fechaInicio">
                            </div>
                            <div class="col-md-3" id="fechaFinContainer" style="display:none;">
                                <label class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fechaFin">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Área</label>
                                <select class="form-select" id="areaFiltro">
                                    <option value="todas">Todas las Áreas</option>
                                    <option value="captacion">Captación</option>
                                    <option value="familias">Familias</option>
                                    <option value="finanzas">Finanzas</option>
                                    <option value="seguimiento">Seguimiento</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary w-100" onclick="aplicarFiltros()">
                                    <i class="ti ti-search me-1"></i>
                                    Aplicar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Métricas Generales -->
            <div class="row" id="metricsContainer">
                <!-- Se llenarán dinámicamente -->
            </div>
            
            <!-- Gráficos -->
            <div class="row" id="chartsContainer">
                <!-- Se llenarán dinámicamente -->
            </div>
            
            <!-- Tablas de Detalle -->
            <div class="row" id="tablesContainer">
                <!-- Se llenarán dinámicamente -->
            </div>
            
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Required JS -->
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/fonts/custom-font.js"></script>
    <script src="assets/js/pcoded.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
 <?php include 'includes/configuracion.php'; ?>
    
    <script>
        // Configuración de tema - Con validación de existencia
        $(document).ready(function() {
            try {
                if (typeof layout_change !== 'undefined') layout_change("light");
                if (typeof change_box_container !== 'undefined') change_box_container("false");
                if (typeof layout_rtl_change !== 'undefined') layout_rtl_change("false");
                if (typeof preset_change !== 'undefined') preset_change("preset-1");
                if (typeof font_change !== 'undefined') font_change("Public-Sans");
            } catch(e) {
                console.warn('Error al aplicar configuración de tema:', e);
            }
        });
    </script>
    
    <script src="assets/js/dashboard_area.js"></script>
 
    
</body>
</html>

<?php $conn->close(); ?>