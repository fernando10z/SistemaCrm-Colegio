<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los apoderados con información de tablas relacionadas
$sql = "SELECT 
    a.id,
    a.familia_id,
    f.codigo_familia,
    f.apellido_principal as familia_apellido,
    f.direccion as familia_direccion,
    f.distrito,
    f.nivel_socioeconomico,
    a.tipo_apoderado,
    a.tipo_documento,
    a.numero_documento,
    a.nombres,
    a.apellidos,
    a.fecha_nacimiento,
    a.genero,
    a.email,
    a.telefono_principal,
    a.telefono_secundario,
    a.whatsapp,
    a.ocupacion,
    a.empresa,
    a.nivel_educativo,
    a.estado_civil,
    a.nivel_compromiso,
    a.nivel_participacion,
    a.preferencia_contacto,
    a.activo,
    a.created_at,
    a.updated_at,
    CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
    -- Contar estudiantes asociados
    COUNT(e.id) as total_estudiantes,
    GROUP_CONCAT(CONCAT(e.nombres, ' ', e.apellidos) SEPARATOR ', ') as estudiantes_nombres,
    -- Calcular edad
    YEAR(CURDATE()) - YEAR(a.fecha_nacimiento) - 
    (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(a.fecha_nacimiento, '%m%d')) as edad,
    -- Información adicional
    CASE 
        WHEN a.nivel_compromiso = 'alto' AND a.nivel_participacion IN ('muy_activo', 'activo') THEN 'excelente'
        WHEN a.nivel_compromiso = 'medio' AND a.nivel_participacion IN ('activo', 'poco_activo') THEN 'buena'
        ELSE 'regular'
    END as calificacion_participacion
FROM apoderados a
LEFT JOIN familias f ON a.familia_id = f.id
LEFT JOIN estudiantes e ON a.familia_id = e.familia_id
WHERE a.activo = 1
GROUP BY a.id, a.familia_id, f.codigo_familia, f.apellido_principal, f.direccion, f.distrito, f.nivel_socioeconomico,
         a.tipo_apoderado, a.tipo_documento, a.numero_documento, a.nombres, a.apellidos, a.fecha_nacimiento,
         a.genero, a.email, a.telefono_principal, a.telefono_secundario, a.whatsapp, a.ocupacion, a.empresa,
         a.nivel_educativo, a.estado_civil, a.nivel_compromiso, a.nivel_participacion, a.preferencia_contacto,
         a.activo, a.created_at, a.updated_at
ORDER BY a.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de apoderados para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_apoderados,
    COUNT(CASE WHEN tipo_apoderado = 'titular' THEN 1 END) as apoderados_titulares,
    COUNT(CASE WHEN tipo_apoderado = 'suplente' THEN 1 END) as apoderados_suplentes,
    COUNT(CASE WHEN tipo_apoderado = 'economico' THEN 1 END) as apoderados_economicos,
    COUNT(CASE WHEN nivel_compromiso = 'alto' THEN 1 END) as compromiso_alto,
    COUNT(CASE WHEN nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos,
    COUNT(CASE WHEN nivel_participacion = 'activo' THEN 1 END) as activos,
    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as nuevos_mes,
    COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as con_email,
    COUNT(CASE WHEN whatsapp IS NOT NULL AND whatsapp != '' THEN 1 END) as con_whatsapp
FROM apoderados
WHERE activo = 1";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener estadísticas por nivel socioeconómico
$socioeconomico_sql = "SELECT 
    f.nivel_socioeconomico,
    COUNT(DISTINCT a.id) as cantidad_apoderados
FROM apoderados a
LEFT JOIN familias f ON a.familia_id = f.id
WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
GROUP BY f.nivel_socioeconomico
ORDER BY f.nivel_socioeconomico";

$socioeconomico_result = $conn->query($socioeconomico_sql);
$socioeconomico_stats = [];
while($socio = $socioeconomico_result->fetch_assoc()) {
    $socioeconomico_stats[$socio['nivel_socioeconomico']] = $socio['cantidad_apoderados'];
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
    <title>Registro de Apoderados - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Registro de Apoderados"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Apoderados, Familias, Perfil, Gestión"
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
    
    <!-- Custom styles for apoderados -->
    <style>
      .badge-tipo-apoderado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-titular { background-color: #28a745; }
      .tipo-suplente { background-color: #fd7e14; }
      .tipo-economico { background-color: #6f42c1; }
      
      .badge-nivel-socio {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .nivel-A { background-color: #17a2b8; }
      .nivel-B { background-color: #28a745; }
      .nivel-C { background-color: #ffc107; color: #856404; }
      .nivel-D { background-color: #fd7e14; }
      .nivel-E { background-color: #dc3545; }
      
      .badge-compromiso {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .compromiso-alto { background-color: #28a745; color: white; }
      .compromiso-medio { background-color: #ffc107; color: #856404; }
      .compromiso-bajo { background-color: #dc3545; color: white; }
      
      .badge-participacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .participacion-muy_activo { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
      }
      .participacion-activo { 
        background-color: #d1ecf1; 
        color: #0c5460; 
        border: 1px solid #bee5eb;
      }
      .participacion-poco_activo { 
        background-color: #fff3cd; 
        color: #856404; 
        border: 1px solid #ffeaa7;
      }
      .participacion-inactivo { 
        background-color: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb;
      }
      
      .apoderado-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .apoderado-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .apoderado-documento {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
        border: 1px solid #e9ecef;
        color: #495057;
      }
      
      .apoderado-edad {
        font-size: 0.7rem;
        color: #6c757d;
      }
      
      .contacto-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .contacto-principal {
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        color: #495057;
        font-weight: 500;
      }
      
      .contacto-secundario {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .contacto-whatsapp {
        font-size: 0.7rem;
        color: #25d366;
        font-weight: 500;
      }
      
      .contacto-email {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .familia-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .familia-codigo {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        background-color: #e3f2fd;
        color: #1565c0;
        padding: 1px 4px;
        border-radius: 3px;
        border: 1px solid #bbdefb;
      }
      
      .familia-apellido {
        font-weight: 500;
        color: #495057;
        font-size: 0.8rem;
      }
      
      .familia-direccion {
        font-size: 0.7rem;
        color: #6c757d;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .estudiantes-info {
        font-size: 0.75rem;
        color: #495057;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .estudiantes-count {
        font-weight: bold;
        color: #28a745;
      }
      
      .profesional-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .profesional-ocupacion {
        font-weight: 500;
        color: #495057;
      }
      
      .profesional-empresa {
        color: #6c757d;
        font-style: italic;
      }
      
      .calificacion-participacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: 500;
      }
      .calificacion-excelente { background-color: #d4edda; color: #155724; }
      .calificacion-buena { background-color: #d1ecf1; color: #0c5460; }
      .calificacion-regular { background-color: #fff3cd; color: #856404; }
      
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
      
      .btn-grupo-apoderado {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-apoderado .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }

      .socioeconomico-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }

      .socio-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
      }

      .socio-item:last-child {
        border-bottom: none;
      }

      .preferencia-contacto {
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
        background-color: #e8f4fd;
        color: #0c5460;
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
                    <a href="javascript: void(0)">Gestión Familiar</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Apoderados
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
                    Registro y Perfil de Apoderados
                  </h3>
                  <small class="text-muted">
                    Gestiona la información completa de los apoderados, incluyendo datos personales, 
                    contacto, información familiar y vinculación con estudiantes.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <!-- <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalVincularEstudiantes">
                    <i class="ti ti-link me-1"></i>
                    Vincular Estudiantes
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalConsultarFicha">
                    <i class="ti ti-id-badge me-1"></i>
                    Consultar Ficha
                  </button> -->
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarApoderado">
                    <i class="ti ti-user-plus me-1"></i>
                    Registrar Apoderado
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de apoderados -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="apoderados-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="15%">Apoderado</th>
                        <th width="8%">Documento</th>
                        <th width="12%">Contacto</th>
                        <th width="10%">Familia</th>
                        <th width="8%">Tipo</th>
                        <th width="10%">Información Profesional</th>
                        <th width="8%">Participación</th>
                        <th width="10%">Estudiantes</th>
                        <th width="8%">Registro</th>
                        <th width="7%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_registro = date('d/m/Y', strtotime($row['created_at']));
                              
                              // Determinar clase CSS para el tipo de apoderado
                              $tipo_class = 'tipo-' . $row['tipo_apoderado'];
                              
                              // Determinar clase CSS para nivel socioeconómico
                              $nivel_socio = $row['nivel_socioeconomico'] ?? '';
                              $nivel_socio_class = $nivel_socio ? 'nivel-' . $nivel_socio : '';
                              
                              // Determinar clase de compromiso
                              $compromiso = $row['nivel_compromiso'] ?? 'medio';
                              $compromiso_class = 'compromiso-' . $compromiso;
                              
                              // Determinar clase de participación
                              $participacion = $row['nivel_participacion'] ?? 'activo';
                              $participacion_class = 'participacion-' . $participacion;
                              
                              // Determinar calificación de participación
                              $calificacion = $row['calificacion_participacion'] ?? 'regular';
                              $calificacion_class = 'calificacion-' . $calificacion;
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='apoderado-info'>
                                        <span class='apoderado-nombre'>" . htmlspecialchars($row['nombre_completo']) . "</span>
                                        <span class='apoderado-documento'>" . 
                                        htmlspecialchars($row['tipo_documento'] . ': ' . $row['numero_documento']) . "</span>
                                        <span class='apoderado-edad'>" . 
                                        ($row['edad'] ? $row['edad'] . ' años' : 'Edad no registrada') . " - " . 
                                        ($row['genero'] == 'M' ? 'Masculino' : ($row['genero'] == 'F' ? 'Femenino' : 'No especificado')) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='contacto-principal'>" . htmlspecialchars($row['telefono_principal'] ?? 'Sin teléfono') . "</span>
                                        " . ($row['telefono_secundario'] ? "<span class='contacto-secundario'>Alt: " . htmlspecialchars($row['telefono_secundario']) . "</span>" : "") . "
                                        " . ($row['whatsapp'] ? "<span class='contacto-whatsapp'>WA: " . htmlspecialchars($row['whatsapp']) . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='contacto-email' title='" . htmlspecialchars($row['email'] ?? '') . "'>" . 
                                        htmlspecialchars($row['email'] ?? 'Sin email') . "</span>
                                        <span class='preferencia-contacto'>Pref: " . ucfirst($row['preferencia_contacto'] ?? 'email') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='familia-info'>
                                        <span class='familia-codigo'>" . htmlspecialchars($row['codigo_familia'] ?? '') . "</span>
                                        <span class='familia-apellido'>Fam. " . htmlspecialchars($row['familia_apellido'] ?? '') . "</span>
                                        " . ($nivel_socio ? "<span class='badge badge-nivel-socio $nivel_socio_class'>" . $nivel_socio . "</span>" : "") . "
                                        <span class='familia-direccion' title='" . htmlspecialchars($row['familia_direccion'] ?? '') . "'>" . 
                                        htmlspecialchars($row['distrito'] ?? 'Sin ubicación') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-tipo-apoderado $tipo_class'>" . 
                                   ucfirst($row['tipo_apoderado']) . "</span></td>";
                              echo "<td>
                                      <div class='profesional-info'>
                                        <span class='profesional-ocupacion'>" . htmlspecialchars($row['ocupacion'] ?? 'No especificada') . "</span>
                                        " . ($row['empresa'] ? "<span class='profesional-empresa'>" . htmlspecialchars($row['empresa']) . "</span>" : "") . "
                                        " . ($row['nivel_educativo'] ? "<small class='text-muted'>" . htmlspecialchars($row['nivel_educativo']) . "</small>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='contacto-info'>
                                        <span class='badge badge-compromiso $compromiso_class'>" . ucfirst($compromiso) . "</span>
                                        <span class='badge badge-participacion $participacion_class'>" . 
                                        str_replace('_', ' ', ucfirst($participacion)) . "</span>
                                        <span class='calificacion-participacion $calificacion_class'>" . ucfirst($calificacion) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='estudiantes-info' title='" . htmlspecialchars($row['estudiantes_nombres'] ?? '') . "'>
                                        <span class='estudiantes-count'>" . $row['total_estudiantes'] . " estudiante(s)</span>
                                        <div style='font-size: 0.7rem; color: #6c757d;'>" . 
                                        htmlspecialchars($row['estudiantes_nombres'] ?? 'Sin estudiantes vinculados') . "</div>
                                      </div>
                                    </td>";
                              echo "<td><span class='fecha-contacto'>" . $fecha_registro . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-apoderado'>
                                        <button type='button' class='btn btn-outline-info btn-consultar-ficha' 
                                                data-id='" . $row['id'] . "'
                                                title='Ver Ficha Completa'>
                                          <i class='ti ti-eye'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-editar-apoderado' 
                                                data-id='" . $row['id'] . "'
                                                title='Editar Datos'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-vincular-estudiantes' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_completo']) . "'
                                                title='Vincular Estudiantes'>
                                          <i class='ti ti-link'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay apoderados registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Apoderado</th>
                        <th>Documento</th>
                        <th>Contacto</th>
                        <th>Familia</th>
                        <th>Tipo</th>
                        <th>Información Profesional</th>
                        <th>Participación</th>
                        <th>Estudiantes</th>
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
    <?php include 'modals/apoderados/modal_registrar_apoderado.php'; ?>
    <?php include 'modals/apoderados/modal_editar_datos.php'; ?>
    <?php include 'modals/apoderados/modal_consultar_ficha.php'; ?>
    <?php include 'modals/apoderados/modal_vincular_estudiantes.php'; ?>

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
            var table = $("#apoderados-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay apoderados disponibles en la tabla",
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

            // Función para exportar apoderados a Excel
            window.exportarApoderados = function() {
              window.open('exports/apoderados_excel.php', '_blank');
            };

            // Manejar click en botón consultar ficha
            $(document).on('click', '.btn-consultar-ficha', function() {
                var id = $(this).data('id');
                cargarFichaCompleta(id);
            });

            // Manejar click en botón editar apoderado
            $(document).on('click', '.btn-editar-apoderado', function() {
                var id = $(this).data('id');
                cargarDatosEdicion(id);
            });

            // Manejar click en botón vincular estudiantes
            $(document).on('click', '.btn-vincular-estudiantes', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#vincular_apoderado_id').val(id);
                $('#vincular_apoderado_nombre').text(nombre);
                cargarEstudiantesDisponibles(id);
                $('#modalVincularEstudiantes').modal('show');
            });

            // Función para cargar ficha completa
            function cargarFichaCompleta(id) {
              $.ajax({
                url: 'actions/obtener_ficha_apoderado.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarFichaCompleta(response.data);
                  } else {
                    alert('Error al cargar la ficha: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener la ficha del apoderado.');
                }
              });
            }

            // Función para cargar datos de edición
            function cargarDatosEdicion(id) {
              $.ajax({
                url: 'actions/obtener_apoderado_edicion.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    llenarFormularioEdicion(response.data);
                    $('#modalEditarApoderado').modal('show');
                  } else {
                    alert('Error al cargar los datos: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener los datos del apoderado.');
                }
              });
            }

            // Función para cargar estudiantes disponibles
            function cargarEstudiantesDisponibles(apoderado_id) {
              $.ajax({
                url: 'actions/obtener_estudiantes_disponibles.php',
                method: 'POST',
                data: { apoderado_id: apoderado_id },
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    mostrarEstudiantesDisponibles(response.data);
                  } else {
                    alert('Error al cargar estudiantes: ' + response.message);
                  }
                },
                error: function() {
                  alert('Error de conexión al obtener estudiantes disponibles.');
                }
              });
            }

            // Función para mostrar ficha completa
            function mostrarFichaCompleta(data) {
              // Cargar datos en modal de consulta de ficha
              $('#modalConsultarFicha').modal('show');
              // Implementar llenado de datos en el modal
              console.log('Ficha completa:', data);
            }

            // Función para llenar formulario de edición
            function llenarFormularioEdicion(data) {
              // Implementar llenado de formulario
              console.log('Datos para edición:', data);
            }

            // Función para mostrar estudiantes disponibles
            function mostrarEstudiantesDisponibles(data) {
              // Implementar lista de estudiantes
              console.log('Estudiantes disponibles:', data);
            }

            // Validación de documentos en tiempo real
            $(document).on('input', 'input[name="numero_documento"]', function() {
                var tipoDoc = $('select[name="tipo_documento"]').val();
                var numero = $(this).val();
                
                if (tipoDoc === 'DNI' && numero.length === 8) {
                    validarDocumento(tipoDoc, numero);
                } else if (tipoDoc === 'CE' && numero.length >= 9) {
                    validarDocumento(tipoDoc, numero);
                }
            });

            // Función para validar documento
            function validarDocumento(tipo, numero) {
              $.ajax({
                url: 'actions/validar_documento.php',
                method: 'POST',
                data: { tipo: tipo, numero: numero },
                dataType: 'json',
                success: function(response) {
                  if (response.existe) {
                    alert('¡Atención! Ya existe un apoderado registrado con este documento.');
                  }
                },
                error: function() {
                  console.log('Error al validar documento');
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