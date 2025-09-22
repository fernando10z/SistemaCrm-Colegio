<?php
session_start();  

// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener las consultas
$sql = "SELECT 
    id,
    nombre,
    email,
    mensaje,
    fecha,
    estado
FROM consultas
ORDER BY fecha DESC";

$result = $conn->query($sql);
 $sql2 = "SELECT id, nombre, email, telefono, fecha_registro, estado 
        FROM registros 
        ORDER BY fecha_registro DESC 
        LIMIT 10";
$result1 = $conn->query($sql2);


// Contar mensajes pendientes
$sql_pendientes = "SELECT COUNT(*) as total FROM registros WHERE estado = 'Pendiente'";
$result_pendientes = $conn->query($sql_pendientes);
$total_pendientes = 0;

if ($result_pendientes->num_rows > 0) {
    $row = $result_pendientes->fetch_assoc();
    $total_pendientes = $row['total'];
}

// Contar consultas pendientes
$sql_consultas_pendientes = "SELECT COUNT(*) as total FROM consultas WHERE estado = 'Pendiente'";
$result_consultas_pendientes = $conn->query($sql_consultas_pendientes);
$total_consultas_pendientes = 0;

if ($result_consultas_pendientes->num_rows > 0) {
    $row = $result_consultas_pendientes->fetch_assoc();
    $total_consultas_pendientes = $row['total'];
}

// Total de notificaciones
$total_notificaciones = $total_pendientes + $total_consultas_pendientes;
// Consulta para obtener los cursos
// Obtener nombre del sistema para el título
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
  $nombre_sistema = "CRM Escolar";
}

// Contar consultas por estado
$sql_contar = "SELECT estado, COUNT(*) as total FROM consultas GROUP BY estado";
$result_contar = $conn->query($sql_contar);
$contadores = ['Pendiente' => 0, 'Atendido' => 0];

if ($result_contar->num_rows > 0) {
    while($row = $result_contar->fetch_assoc()) {
        $contadores[$row['estado']] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title>Bandeja de Consultas - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Bandeja de Consultas"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Consultas, Mensajes, Administración"
    />
    <meta name="author" content="CRM Escolar" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <!-- En el head, después de los otros estilos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    
    <!-- Custom styles for consultas -->
    <style>
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: 20px;
        font-weight: 500;
      }
      .estado-pendiente { 
        background-color: #fff3cd; 
        color: #856404; 
        border: 1px solid #ffeaa7;
      }
      .estado-atendido { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
      }
      
      .consulta-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      }
      
      .consulta-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
      }
      
      .consulta-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      .consulta-body {
        padding: 1.5rem;
      }
      
      .consulta-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 0 0 12px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: #6c63ff;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 20px;
        flex-shrink: 0;
        flex-grow: 0;
      }
      
      .consulta-info {
        flex: 1;
        margin-left: 1rem;
      }
      
      .consulta-nombre {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
      }
      
      .consulta-email {
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
      }
      
      .consulta-mensaje {
        color: #495057;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-top: 1rem;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #6c63ff;
      }
      
      .consulta-fecha {
        color: #6c757d;
        font-size: 0.8rem;
      }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
      }
      
      .stats-card .card-body {
        padding: 1.5rem;
      }
      
      .btn-action {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
      }
      
      .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
      }
      
      .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #dee2e6;
      }
      
      /* Solución definitiva para SweetAlert2 sobre modales */
      .swal2-container {
        z-index: 20000 !important;
      }

      .swal2-popup {
        z-index: 20001 !important;
      }

      .swal2-backdrop {
        z-index: 19999 !important;
      }
      
      .filter-buttons {
        margin-bottom: 1.5rem;
      }
      
      .filter-btn {
        border-radius: 20px;
        padding: 0.5rem 1.5rem;
        margin-right: 0.5rem;
        font-weight: 500;
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
                    Bandeja de Consultas
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
                    Bandeja de Consultas
                  </h3>
                  <small class="text-muted">
                    Gestiona las consultas y mensajes recibidos.
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarConsultasPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Filtros -->
                <div class="filter-buttons">
                  <button type="button" class="btn btn-outline-primary filter-btn active" data-filter="todos">
                    <i class="ti ti-mail me-1"></i>Todos (<?php echo $contadores['Pendiente'] + $contadores['Atendido']; ?>)
                  </button>
                  <button type="button" class="btn btn-outline-warning filter-btn" data-filter="pendiente">
                    <i class="ti ti-clock me-1"></i>Pendientes (<?php echo $contadores['Pendiente']; ?>)
                  </button>
                  <button type="button" class="btn btn-outline-success filter-btn" data-filter="atendido">
                    <i class="ti ti-check me-1"></i>Atendidas (<?php echo $contadores['Atendido']; ?>)
                  </button>
                </div>
                
                <!-- Lista de consultas en cards -->
                <div class="row" id="consultas-container">
                  <?php
                  // Reiniciar el resultado para mostrarlo en las cards
                  $result = $conn->query($sql);
                  
                  if ($result->num_rows > 0) {
                      while($row = $result->fetch_assoc()) {
                          // Determinar clase CSS para el estado
                          $estado_class = ($row['estado'] == 'Pendiente') ? 'estado-pendiente' : 'estado-atendido';
                          $estado_texto = ucfirst($row['estado']);
                          
                          // Obtener iniciales para el avatar
                          $iniciales = strtoupper(substr($row['nombre'], 0, 1));
                          
                          // Formatear fecha
                          $fecha = date('d/m/Y H:i', strtotime($row['fecha']));
                          
                          echo '<div class="col-md-6 col-lg-4 consulta-item" data-estado="' . strtolower($row['estado']) . '">';
                          echo '  <div class="consulta-card">';
                          echo '    <div class="consulta-header">';
                          echo '      <span class="badge badge-estado ' . $estado_class . '">' . $estado_texto . '</span>';
                          echo '      <small class="consulta-fecha">' . $fecha . '</small>';
                          echo '    </div>';
                          echo '    <div class="consulta-body">';
                          echo '      <div class="d-flex align-items-start">';
                          echo '        <div class="user-avatar">' . $iniciales . '</div>';
                          echo '        <div class="consulta-info">';
                          echo '          <div class="consulta-nombre">' . htmlspecialchars($row['nombre']) . '</div>';
                          echo '          <div class="consulta-email">';
                          echo '            <i class="ti ti-mail me-1"></i>' . htmlspecialchars($row['email']);
                          echo '          </div>';
                          echo '        </div>';
                          echo '      </div>';
                          echo '      <div class="consulta-mensaje">';
                          echo '        ' . nl2br(htmlspecialchars($row['mensaje']));
                          echo '      </div>';
                          echo '    </div>';
                          echo '    <div class="consulta-footer">';
                          echo '      <div>';
                          if ($row['estado'] == 'Pendiente') {
                          echo '        <button type="button" class="btn btn-success btn-sm btn-action btn-cambiar-estado" ';
                          echo '                data-id="' . $row['id'] . '" data-estado="Atendido" title="Marcar como atendida">';
                          echo '          <i class="ti ti-check me-1"></i>Atender';
                          echo '        </button>';
                          } else {
                          echo '        <button type="button" class="btn btn-secondary btn-sm" disabled>';
                          echo '          <i class="ti ti-check-double me-1"></i>Atendida';
                          echo '        </button>';
                          }
                          echo '      </div>';
                          echo '      <div>';
                          echo '        <button type="button" class="btn btn-danger btn-sm btn-action btn-eliminare" ';
                          echo '                data-id="' . $row['id'] . '" data-nombre="' . htmlspecialchars($row['nombre']) . '" title="Eliminar consulta">';
                          echo '          <i class="ti ti-trash"></i>';
                          echo '        </button>';
                          echo '      </div>';
                          echo '    </div>';
                          echo '  </div>';
                          echo '</div>';
                      }
                  } else {
                      echo '<div class="col-12">';
                      echo '  <div class="empty-state">';
                      echo '    <i class="ti ti-inbox"></i>';
                      echo '    <h4>No hay consultas</h4>';
                      echo '    <p>No se han recibido consultas todavía.</p>';
                      echo '  </div>';
                      echo '</div>';
                  }
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>  
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
    
    <script>
    $(document).ready(function() {
      // Función para exportar consultas a PDF
      window.exportarConsultasPDF = function() {
        // Crear formulario para enviar datos por POST
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reports/generar_pdf_consultas.php';
        form.target = '_blank';
        
        // Obtener filtro actual
        var filtro = $('.filter-btn.active').data('filter');
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'filtro';
        input.value = filtro;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      };

      // Filtrado de consultas
      $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filtro = $(this).data('filter');
        
        if (filtro === 'todos') {
          $('.consulta-item').show();
        } else {
          $('.consulta-item').hide();
          $('.consulta-item[data-estado="' + filtro + '"]').show();
        }
      });

      // Manejar cambio de estado
      $(document).on('click', '.btn-cambiar-estado', function() {
        var id = $(this).data('id');
        var nuevo_estado = $(this).data('estado');
        var nombre = $(this).closest('.consulta-card').find('.consulta-nombre').text();
        
        Swal.fire({
          title: '¿Cambiar estado?',
          text: 'La consulta de ' + nombre + ' cambiará a ' + nuevo_estado,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sí, cambiar',
          cancelButtonText: 'Cancelar',
          customClass: {
            popup: 'sweet-alert-on-top'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: 'consultas/cambiar_estado.php',
              type: 'POST',
              dataType: 'json',
              data: { 
                id: id,
                nuevo_estado: nuevo_estado
              },
              success: function(response) {
                if (response.success) {
                  Swal.fire({
                    icon: 'success',
                    title: response.message,
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: {
                      popup: 'sweet-alert-on-top'
                    }
                  }).then(function() {
                    window.location.reload();
                  });
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    customClass: {
                      popup: 'sweet-alert-on-top'
                    }
                  });
                }
              },
              error: function(xhr, status, error) {
                console.error("Error en la solicitud:", status, error);
                Swal.fire({
                  icon: 'error',
                  title: 'Error de conexión',
                  text: 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.',
                  customClass: {
                    popup: 'sweet-alert-on-top'
                  }
                });
              }
            });
          }
        });
      });

      // Manejar eliminación de consulta
      $(document).on('click', '.btn-eliminare', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar la consulta de: ' + nombre,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          customClass: {
            popup: 'sweet-alert-on-top'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: 'consultas/eliminar.php?id=' + id,
              type: 'GET',
              dataType: 'json',
              success: function(response) {
                if (response.success) {
                  Swal.fire({
                    icon: 'success',
                    title: response.message,
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: {
                      popup: 'sweet-alert-on-top'
                    }
                  }).then(function() {
                    window.location.reload();
                  });
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    customClass: {
                      popup: 'sweet-alert-on-top'
                    }
                  });
                }
              },
              error: function(xhr, status, error) {
                console.error("Error en la solicitud:", status, error);
                Swal.fire({
                  icon: 'error',
                  title: 'Error de conexión',
                  text: 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.',
                  customClass: {
                    popup: 'sweet-alert-on-top'
                  }
                });
              }
            });
          }
        });
      });
    });
    </script>

    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
</html>

<?php
// Cerrar conexión
$conn->close();
?>