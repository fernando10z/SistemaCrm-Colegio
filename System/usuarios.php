<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los usuarios con información del rol
$sql = "SELECT 
    u.id,
    u.usuario,
    u.email,
    u.nombre,
    u.apellidos,
    u.telefono,
    u.rol_id,
    r.nombre as nombre_rol,
    u.ultimo_acceso,
    u.intentos_fallidos,
    u.bloqueado_hasta,
    u.activo,
    u.created_at,
    u.updated_at
FROM usuarios u
LEFT JOIN roles r ON u.rol_id = r.id
ORDER BY u.created_at DESC";

$result = $conn->query($sql);

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
    <title>Gestión de Usuarios - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Usuarios"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Usuarios, Administración"
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
    
    <!-- Custom styles for usuarios -->
    <style>
      .badge-rol {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
      }
      .rol-administrador { background-color: #dc3545; color: white; }
      .rol-coordinador { background-color: #fd7e14; color: white; }
      .rol-tutor { background-color: #20c997; color: white; }
      .rol-finanzas { background-color: #6f42c1; color: white; }
      .rol-otro { background-color: #6c757d; color: white; }
      
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 500;
      }
      .estado-activo { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
      }
      .estado-inactivo { 
        background-color: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb;
      }
      .estado-bloqueado { 
        background-color: #fff3cd; 
        color: #856404; 
        border: 1px solid #ffeaa7;
      }
      
      .usuario-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .usuario-nombre {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .usuario-email {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
      }
      
      .usuario-username {
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        background-color: #f8f9fa;
        padding: 1px 4px;
        border-radius: 3px;
        border: 1px solid #e9ecef;
      }
      
      .acceso-info {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .fecha-relativa {
        font-size: 0.85rem;
        color: #6c757d;
      }
      
      .intentos-fallidos {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .intentos-0 { background-color: #d4edda; color: #155724; }
      .intentos-1-2 { background-color: #fff3cd; color: #856404; }
      .intentos-3-plus { background-color: #f8d7da; color: #721c24; }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
      }
      
      .stats-card .card-body {
        padding: 1rem;
      }
      
      .telefono-info {
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
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
                    <a href="javascript: void(0)">Administración</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Usuarios
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
                    Gestión de Usuarios del Sistema
                  </h3>
                  <small class="text-muted">
                    Administra los usuarios que tienen acceso al sistema CRM. 
                    Puedes crear, editar, activar/desactivar y gestionar roles.
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarUsuariosPDF()">
                   <i class="fas fa-file-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                    <i class="ti ti-user-plus me-1"></i>
                    Nuevo Usuario
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de usuarios -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="usuarios-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="18%">Usuario</th>
                        <th width="12%">Username</th>
                        <th width="12%">Teléfono</th>
                        <th width="12%">Rol</th>
                        <th width="8%">Estado</th>
                        <th width="8%">Intentos</th>
                        <th width="12%">Último Acceso</th>
                        <th width="10%">Fecha Registro</th>
                        <th width="13%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_registro = date('d/m/Y', strtotime($row['created_at']));
                              $ultimo_acceso = $row['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca';
                              
                              // Determinar estado
                              $ahora = new DateTime();
                              $bloqueado_hasta = $row['bloqueado_hasta'] ? new DateTime($row['bloqueado_hasta']) : null;
                              $esta_bloqueado = $bloqueado_hasta && $bloqueado_hasta > $ahora;
                              
                              if (!$row['activo']) {
                                  $estado_class = 'estado-inactivo';
                                  $estado_texto = 'Inactivo';
                              } elseif ($esta_bloqueado) {
                                  $estado_class = 'estado-bloqueado';
                                  $estado_texto = 'Bloqueado';
                              } else {
                                  $estado_class = 'estado-activo';
                                  $estado_texto = 'Activo';
                              }
                              
                              // Determinar clase CSS para el rol
                              $rol_lower = strtolower($row['nombre_rol']);
                              $rol_class = 'rol-otro'; // por defecto
                              if (strpos($rol_lower, 'administrador') !== false) $rol_class = 'rol-administrador';
                              elseif (strpos($rol_lower, 'coordinador') !== false) $rol_class = 'rol-coordinador';
                              elseif (strpos($rol_lower, 'tutor') !== false) $rol_class = 'rol-tutor';
                              elseif (strpos($rol_lower, 'finanzas') !== false) $rol_class = 'rol-finanzas';
                              
                              // Clase para intentos fallidos
                              $intentos = (int)$row['intentos_fallidos'];
                              if ($intentos == 0) {
                                  $intentos_class = 'intentos-0';
                              } elseif ($intentos <= 2) {
                                  $intentos_class = 'intentos-1-2';
                              } else {
                                  $intentos_class = 'intentos-3-plus';
                              }
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='usuario-info'>
                                        <span class='usuario-nombre'>" . htmlspecialchars(($row['nombre'] ?? '') . ' ' . ($row['apellidos'] ?? '')) . "</span>
                                        <span class='usuario-email'>" . htmlspecialchars($row['email'] ?? '') . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='usuario-username'>" . htmlspecialchars($row['usuario'] ?? '') . "</span></td>";
                              echo "<td><span class='telefono-info'>" . (($row['telefono'] ?? '') ? htmlspecialchars($row['telefono'] ?? '') : 'No registrado') . "</span></td>";
                              echo "<td><span class='badge badge-rol " . $rol_class . "'>" . 
                                   htmlspecialchars($row['nombre_rol'] ?? 'Sin rol') . "</span></td>";
                              echo "<td><span class='badge badge-estado " . $estado_class . "'>" . $estado_texto . "</span></td>";
                              echo "<td><span class='badge intentos-fallidos " . $intentos_class . "'>" . $intentos . "</span></td>";
                              echo "<td>
                                      <div class='acceso-info'>
                                        " . $ultimo_acceso . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='fecha-relativa'>
                                        " . $fecha_registro . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id'] . "'
                                                data-usuario='" . htmlspecialchars($row['usuario'] ?? '') . "'
                                                data-email='" . htmlspecialchars($row['email'] ?? '') . "'
                                                data-nombre='" . htmlspecialchars($row['nombre'] ?? '') . "'
                                                data-apellidos='" . htmlspecialchars($row['apellidos'] ?? '') . "'
                                                data-telefono='" . htmlspecialchars($row['telefono'] ?? '') . "'
                                                data-rol_id='" . $row['rol_id'] . "'
                                                data-activo='" . $row['activo'] . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-resetear' 
                                                data-id='" . $row['id'] . "'
                                                data-usuario='" . htmlspecialchars($row['usuario']) . "'
                                                title='Resetear Password'>
                                          <i class='ti ti-key'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-danger btn-eliminar' 
                                                data-id='" . $row['id'] . "'
                                                data-usuario='" . htmlspecialchars($row['usuario']) . "'
                                                title='Eliminar'>
                                          <i class='ti ti-trash'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='10' class='text-center'>No hay usuarios registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Username</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Intentos</th>
                        <th>Último Acceso</th>
                        <th>Fecha Registro</th>
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
            var table = $("#usuarios-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay usuarios disponibles en la tabla",
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

            // Función para exportar usuarios a PDF con datos filtrados
            window.exportarUsuariosPDF = function() {
              var tabla = $('#usuarios-table').DataTable();
              var datosVisibles = [];
              
              // Obtener solo las filas visibles/filtradas
              tabla.rows({ filter: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                var data = this.data();
                var row = [];
                
                // Extraer texto limpio de cada celda (sin HTML)
                for (var i = 0; i < data.length - 1; i++) { // -1 para excluir acciones
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
              form.action = 'reports/generar_pdf_usuarios.php';
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

            // Manejar click en botón editar
            $(document).on('click', '.btn-editar', function() {
                var id = $(this).data('id');
                var usuario = $(this).data('usuario');
                var email = $(this).data('email');
                var nombre = $(this).data('nombre');
                var apellidos = $(this).data('apellidos');
                var telefono = $(this).data('telefono');
                var rol_id = $(this).data('rol_id');
                var activo = $(this).data('activo');
                
                $('#edit_id').val(id);
                $('#edit_usuario').val(usuario);
                $('#edit_email').val(email);
                $('#edit_nombre').val(nombre);
                $('#edit_apellidos').val(apellidos);
                $('#edit_telefono').val(telefono);
                $('#edit_rol_id').val(rol_id);
                $('#edit_activo').val(activo);
                
                $('#modalEditar').modal('show');
            });

            // Manejar click en botón resetear password
            $(document).on('click', '.btn-resetear', function() {
                var id = $(this).data('id');
                var usuario = $(this).data('usuario');
                
                if (confirm('¿Estás seguro de que deseas resetear la contraseña del usuario "' + usuario + '"? Se enviará una nueva contraseña temporal por email.')) {
                    window.location.href = 'actions/resetear_password.php?id=' + id;
                }
            });

            // Manejar click en botón eliminar
            $(document).on('click', '.btn-eliminar', function() {
                var id = $(this).data('id');
                var usuario = $(this).data('usuario');
                
                if (confirm('¿Estás seguro de que deseas eliminar el usuario "' + usuario + '"? Esta acción no se puede deshacer.')) {
                    window.location.href = 'actions/eliminar_usuario.php?id=' + id;
                }
            });
            
            // Tooltip para elementos truncados
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