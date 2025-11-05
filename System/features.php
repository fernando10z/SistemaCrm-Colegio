<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';
 $sql2 = "SELECT id, nombre, email, telefono, fecha_registro, estado 
        FROM registros 
        ORDER BY fecha_registro DESC 
        LIMIT 10";
$result1 = $conn->query($sql2);
// Consulta para obtener los features
$sql = "SELECT 
    id,
    titulo,
    icono,
    descripcion,
    descripcion_corta,
    enlace,
    texto_enlace,
    clase_extra,
    estado
FROM features
ORDER BY id DESC";

$result = $conn->query($sql);

// Obtener nombre del sistema para el título
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
  $nombre_sistema = "CRM Escolar";
}

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
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title>Gestión de Features - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Features"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Features, Administración"
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
    
    <!-- Custom styles for features -->
    <style>
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
      
      .feature-icon {
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        background-color: #f8f9fa;
      }
      
      .feature-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .feature-titulo {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .feature-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
      }
      
      .stats-card .card-body {
        padding: 1rem;
      }
      
      .link-info {
        font-size: 0.8rem;
        color: #495057;
        word-break: break-all;
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
                    Features
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
                    Gestión de Features
                  </h3>
                  <small class="text-muted">
                    Administra los features disponibles en el sistema. 
                    Puedes editar, activar/desactivar y gestionar features.
                  </small>
                </div>
                <div class="d-flex gap-2">
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de features -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="features-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="8%">Icono</th>
                        <th width="15%">Título</th>
                        <th width="20%">Descripción</th>
                        <th width="15%">Descripción Corta</th>
                        <th width="10%">Enlace</th>
                        <th width="10%">Texto Enlace</th>
                        <th width="8%">Clase Extra</th>
                        <th width="8%">Estado</th>
                        <th width="6%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado
                              $estado_class = ($row['estado'] == 'activo') ? 'estado-activo' : 'estado-inactivo';
                              $estado_texto = ucfirst($row['estado']);
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='feature-icon'>
                                        <i class='" . htmlspecialchars($row['icono']) . "'></i>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='feature-info'>
                                        <span class='feature-titulo'>" . htmlspecialchars($row['titulo']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='feature-descripcion'>" . htmlspecialchars($row['descripcion']) . "</div>
                                    </td>";
                              echo "<td>
                                      <div class='feature-descripcion'>" . htmlspecialchars($row['descripcion_corta'] ?? '') . "</div>
                                    </td>";
                              echo "<td>
                                      <a href='" . htmlspecialchars($row['enlace'] ?? '#') . "' target='_blank' class='link-info'>
                                        <i class='ti ti-external-link me-1'></i>Ver enlace
                                      </a>
                                    </td>";
                              echo "<td>" . htmlspecialchars($row['texto_enlace'] ?? '') . "</td>";
                              echo "<td>" . htmlspecialchars($row['clase_extra'] ?? '') . "</td>";
                              echo "<td><span class='badge badge-estado " . $estado_class . "'>" . $estado_texto . "</span></td>";
                             echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                      <button type='button' class='btn btn-outline-primary btn-editar' 
                                               data-id='" . $row['id'] . "'
                                              data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                            data-icono='" . htmlspecialchars($row['icono']) . "'
                                               data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                                data-descripcion_corta='" . htmlspecialchars($row['descripcion_corta'] ?? '') . "'
                                            data-texto_enlace='" . htmlspecialchars($row['texto_enlace'] ?? '') . "'
                                            data-clase_extra='" . htmlspecialchars($row['clase_extra'] ?? '') . "'
                                               data-estado='" . $row['estado'] . "'
                                               title='Editar'>
                                         <i class='ti ti-edit'></i>
                                       </button>
                                       </td>";
                                        echo "</tr>";
                              // echo "<td>
                                    //  <div class='btn-group btn-group-sm' role='group'>
                                    //    <button type='button' class='btn btn-outline-primary btn-editar' 
                                    //            data-id='" . $row['id'] . "'
                                    //           data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                    //            data-icono='" . htmlspecialchars($row['icono']) . "'
                                      //          data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                   //             data-descripcion_corta='" . htmlspecialchars($row['descripcion_corta'] ?? '') . "'
                                        //        data-enlace='" . htmlspecialchars($row['enlace'] ?? '') . "'
                                   //             data-texto_enlace='" . htmlspecialchars($row['texto_enlace'] ?? '') . "'
                                      //          data-clase_extra='" . htmlspecialchars($row['clase_extra'] ?? '') . "'
                                      //          data-estado='" . $row['estado'] . "'
                                         //       title='Editar'>
                                     //     <i class='ti ti-edit'></i>
                                       // </button>
                                      //  <button type='button' class='btn btn-outline-warning btn-cambiar-estado' 
                                     //           data-id='" . $row['id'] . "'
                                      //          data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                       //         data-estado='" . $row['estado'] . "'
                                      //          title='Cambiar Estado'>
                                       //   <i class='ti ti-refresh'></i>
                                      //  </button>
                                       // <button type='button' class='btn btn-outline-danger btn-eliminare' 
                                      //          data-id='" . $row['id'] . "'
                                        //        data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                      //         title='Eliminar'>
                                      //    <i class='ti ti-trash'></i>
                                     //   </button>
                                    //  </div>
                                   // </td>";
                             // echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='10' class='text-center'>No hay features registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Icono</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Descripción Corta</th>
                        <th>Enlace</th>
                        <th>Texto Enlace</th>
                        <th>Clase Extra</th>
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

<!-- Modal para editar feature -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Feature</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarFeature" action="features/editar.php" method="POST">
        <input type="hidden" id="edit_id" name="id">

        <div class="modal-body">
          <div class="row g-3">
            <!-- Título -->
            <div class="col-md-6">
              <label for="edit_titulo" class="form-label">Título *</label>
              <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
            </div>

            <!-- Icono -->
            <div class="col-md-6">
              <label for="edit_icono" class="form-label">Icono *</label>
              <input type="text" class="form-control" id="edit_icono" name="icono" required>
              <small class="text-muted">Ejemplo: "fa fa-pencil", "ti ti-book", "material-icons book"</small>
            </div>

            <!-- Descripción -->
            <div class="col-12">
              <label for="edit_descripcion" class="form-label">Descripción *</label>
              <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
            </div>

            <!-- Descripción Corta -->
            <div class="col-12">
              <label for="edit_descripcion_corta" class="form-label">Descripción Corta</label>
              <textarea class="form-control" id="edit_descripcion_corta" name="descripcion_corta" rows="2"></textarea>
            </div>

            <!-- Enlace -->
            <div class="col-md-6">
              <label for="edit_enlace" class="form-label">Enlace</label>
              <input type="text" class="form-control" id="edit_enlace" name="enlace">
            </div>

            <!-- Texto Enlace -->
            <div class="col-md-6">
              <label for="edit_texto_enlace" class="form-label">Texto Enlace</label>
              <input type="text" class="form-control" id="edit_texto_enlace" name="texto_enlace">
            </div>

            <!-- Clase Extra -->
            <div class="col-md-6">
              <label for="edit_clase_extra" class="form-label">Clase Extra</label>
              <input type="text" class="form-control" id="edit_clase_extra" name="clase_extra">
              <small class="text-muted">Ejemplo: "second-features", "third-features"</small>
            </div>

            <!-- Estado -->
            <div class="col-md-6">
              <label for="edit_estado" class="form-label">Estado *</label>
              <select class="form-select" id="edit_estado" name="estado" required>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Feature</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
      // Inicializar DataTable con filtros integrados
      var table = $("#features-table").DataTable({
        "language": {
          "decimal": "",
          "emptyTable": "No hay features disponibles en la tabla",
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

      // Función para exportar features a PDF con datos filtrados
      window.exportarFeaturesPDF = function() {
        var tabla = $('#features-table').DataTable();
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
        form.action = 'reports/generar_pdf_features.php';
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
        var titulo = $(this).data('titulo');
        var icono = $(this).data('icono');
        var descripcion = $(this).data('descripcion');
        var descripcion_corta = $(this).data('descripcion_corta');
        var enlace = $(this).data('enlace');
        var texto_enlace = $(this).data('texto_enlace');
        var clase_extra = $(this).data('clase_extra');
        var estado = $(this).data('estado');

        // Llenar los campos del modal
        $('#edit_id').val(id);
        $('#edit_titulo').val(titulo);
        $('#edit_icono').val(icono);
        $('#edit_descripcion').val(descripcion);
        $('#edit_descripcion_corta').val(descripcion_corta);
        $('#edit_enlace').val(enlace);
        $('#edit_texto_enlace').val(texto_enlace);
        $('#edit_clase_extra').val(clase_extra);
        $('#edit_estado').val(estado);

        // Mostrar modal
        $('#modalEditar').modal('show');
      });

      // Manejar eliminación de feature
      $(document).on('click', '.btn-eliminare', function() {
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar el feature: ' + titulo,
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
              url: 'features/eliminar.php?id=' + id,
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

      // Manejar cambio de estado
      $(document).on('click', '.btn-cambiar-estado', function() {
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        var estado_actual = $(this).data('estado');
        var nuevo_estado = (estado_actual == 'activo') ? 'inactivo' : 'activo';
        
        Swal.fire({
          title: '¿Cambiar estado?',
          text: 'El feature "' + titulo + '" cambiará de ' + estado_actual + ' a ' + nuevo_estado,
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
              url: 'features/cambiar_estado.php',
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

      // Envío de formulario de edición con AJAX
      $('#formEditarFeature').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
          type: 'POST',
          url: $(this).attr('action'),
          data: formData,
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
                $('#modalEditar').modal('hide');
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
      });

      // Tooltip para elementos truncados
      $('[title]').tooltip();
    });
    </script>

    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
</html>

<?php
// Cerrar conexión
$conn->close();
?>