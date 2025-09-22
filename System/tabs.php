<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los tabs
$sql = "SELECT 
    id,
    titulo_tab,
    titulo_h4,
    descripcion,
    descripcion_extra,
    imagen,
    estado
FROM tabs
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
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title>Gestión de Tabs - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Tabs"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Tabs, Administración"
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
    
    <!-- Custom styles for tabs -->
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
      
      .tab-image {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
      }
      
      .tab-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .tab-titulo {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .tab-descripcion {
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
                    Tabs
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
                    Gestión de Tabs
                  </h3>
                  <small class="text-muted">
                    Administra los tabs disponibles en el sistema. 
                    Puedes editar, activar/desactivar y gestionar tabs.
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarTabsPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de tabs -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="tabs-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="10%">Imagen</th>
                        <th width="15%">Título Tab</th>
                        <th width="15%">Título H4</th>
                        <th width="20%">Descripción</th>
                        <th width="20%">Descripción Extra</th>
                        <th width="8%">Estado</th>
                        <th width="7%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el estado
                              $estado_class = ($row['estado'] == 'Activo') ? 'estado-activo' : 'estado-inactivo';
                              $estado_texto = $row['estado'];
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              
     echo "<td>
    <img src='../" . htmlspecialchars($row['imagen']) . "' 
         class='curso-imagen' 
         alt='Imagen del tab'
         width='80'>
</td>";


                              echo "<td>
                                      <div class='tab-info'>
                                        <span class='tab-titulo'>" . htmlspecialchars($row['titulo_tab']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='tab-info'>
                                        <span class='tab-titulo'>" . htmlspecialchars($row['titulo_h4']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='tab-descripcion'>" . htmlspecialchars($row['descripcion']) . "</div>
                                    </td>";
                              echo "<td>
                                      <div class='tab-descripcion'>" . htmlspecialchars($row['descripcion_extra'] ?? '') . "</div>
                                    </td>";
                              echo "<td><span class='badge badge-estado " . $estado_class . "'>" . $estado_texto . "</span></td>";
                               echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id'] . "'
                                                data-titulo_tab='" . htmlspecialchars($row['titulo_tab']) . "'
                                                data-titulo_h4='" . htmlspecialchars($row['titulo_h4']) . "'
                                                data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                                data-descripcion_extra='" . htmlspecialchars($row['descripcion_extra'] ?? '') . "'
                                                data-imagen='" . htmlspecialchars($row['imagen']) . "'
                                                data-estado='" . $row['estado'] . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                          </div>
                                           </td>";
                              echo "</tr>";
                              //echo "<td>
                                      //<div class='btn-group btn-group-sm' role='group'>
                                    //    <button type='button' class='btn btn-outline-primary btn-editar' 
                                        //        data-id='" . $row['id'] . "'
                                          //      data-titulo_tab='" . htmlspecialchars($row['titulo_tab']) . "'
                                            //    data-titulo_h4='" . htmlspecialchars($row['titulo_h4']) . "'
                                              //  data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                               // data-descripcion_extra='" . htmlspecialchars($row['descripcion_extra'] ?? '') . "'
                                               // data-imagen='" . htmlspecialchars($row['imagen']) . "'
                                                //data-estado='" . $row['estado'] . "'
                                                //title='Editar'>
                                          //<i class='ti ti-edit'></i>
                                         //</button>
                                         //<button type='button' class='btn btn-outline-warning btn-cambiar-estado' 
                                        //         data-id='" . $row['id'] . "'
                                          //       data-titulo_tab='" . htmlspecialchars($row['titulo_tab']) . "'
                                          //      data-estado='" . $row['estado'] . "'
                                            //      title='Cambiar Estado'>
                                          // <i class='ti ti-refresh'></i>
                                         //</button>
                                         //<button type='button' class='btn btn-outline-danger btn-eliminare' 
                                         //        data-id='" . $row['id'] . "'
                                           //      data-titulo_tab='" . htmlspecialchars($row['titulo_tab']) . "'
                                         //        title='Eliminar'>
                                           //<i class='ti ti-trash'></i>
                                         //</button>
                                      // </div>
                                   //  </td>";
                               //echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='8' class='text-center'>No hay tabs registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Título Tab</th>
                        <th>Título H4</th>
                        <th>Descripción</th>
                        <th>Descripción Extra</th>
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

<!-- Modal para editar tab -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Tab</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarTab" action="tabs/editar.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="edit_id" name="id">
        <input type="hidden" id="edit_imagen_actual" name="imagen_actual">

        <div class="modal-body">
          <div class="row g-3">
            <!-- Título Tab -->
            <div class="col-md-6">
              <label for="edit_titulo_tab" class="form-label">Título Tab *</label>
              <input type="text" class="form-control" id="edit_titulo_tab" name="titulo_tab" required>
            </div>

            <!-- Título H4 -->
            <div class="col-md-6">
              <label for="edit_titulo_h4" class="form-label">Título H4 *</label>
              <input type="text" class="form-control" id="edit_titulo_h4" name="titulo_h4" required>
            </div>

            <!-- Descripción -->
            <div class="col-12">
              <label for="edit_descripcion" class="form-label">Descripción *</label>
              <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
            </div>

            <!-- Descripción Extra -->
            <div class="col-12">
              <label for="edit_descripcion_extra" class="form-label">Descripción Extra</label>
              <textarea class="form-control" id="edit_descripcion_extra" name="descripcion_extra" rows="2"></textarea>
            </div>

            <!-- Imagen -->
            <div class="col-md-6">
              <label for="edit_imagen" class="form-label">Imagen</label>
              <input type="file" class="form-control" id="edit_imagen" name="imagen" accept="image/*">
              <small class="text-muted">Dejar vacío para mantener la imagen actual</small>
            </div>


            <!-- Estado -->
            <div class="col-md-6">
              <label for="edit_estado" class="form-label">Estado *</label>
              <select class="form-select" id="edit_estado" name="estado" required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Tab</button>
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
      var table = $("#tabs-table").DataTable({
        "language": {
          "decimal": "",
          "emptyTable": "No hay tabs disponibles en la tabla",
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
          { "orderable": false, "targets": 7 } // Deshabilitar ordenación en columna de acciones
        ],
        "initComplete": function () {
          // Configurar filtros después de que la tabla esté completamente inicializada
          this.api().columns().every(function (index) {
            var column = this;
            
            // Solo aplicar filtros a las primeras 7 columnas (sin acciones)
            if (index < 7) {
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

      // Función para exportar tabs a PDF con datos filtrados
      window.exportarTabsPDF = function() {
        var tabla = $('#tabs-table').DataTable();
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
        form.action = 'reports/generar_pdf_tabs.php';
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
        var titulo_tab = $(this).data('titulo_tab');
        var titulo_h4 = $(this).data('titulo_h4');
        var descripcion = $(this).data('descripcion');
        var descripcion_extra = $(this).data('descripcion_extra');
        var imagen = $(this).data('imagen');
        var estado = $(this).data('estado');

        // Llenar los campos del modal
        $('#edit_id').val(id);
        $('#edit_titulo_tab').val(titulo_tab);
        $('#edit_titulo_h4').val(titulo_h4);
        $('#edit_descripcion').val(descripcion);
        $('#edit_descripcion_extra').val(descripcion_extra);
        $('#edit_imagen_actual').val(imagen);
        $('#edit_estado').val(estado);
        
        // Mostrar vista previa de la imagen
        if (imagen) {
          $('#preview_imagen').attr('src', imagen).show();
        }

        // Mostrar modal
        $('#modalEditar').modal('show');
      });

      // Vista previa de imagen al seleccionar archivo
      $('#edit_imagen').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('#preview_imagen').attr('src', e.target.result).show();
          }
          reader.readAsDataURL(file);
        }
      });

      // Manejar eliminación de tab
      $(document).on('click', '.btn-eliminare', function() {
        var id = $(this).data('id');
        var titulo_tab = $(this).data('titulo_tab');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar el tab: ' + titulo_tab,
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
              url: 'tabs/eliminar.php?id=' + id,
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
        var titulo_tab = $(this).data('titulo_tab');
        var estado_actual = $(this).data('estado');
        var nuevo_estado = (estado_actual == 'Activo') ? 'Inactivo' : 'Activo';
        
        Swal.fire({
          title: '¿Cambiar estado?',
          text: 'El tab "' + titulo_tab + '" cambiará de ' + estado_actual + ' a ' + nuevo_estado,
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
              url: 'tabs/cambiar_estado.php',
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
      $('#formEditarTab').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
          type: 'POST',
          url: $(this).attr('action'),
          data: formData,
          dataType: 'json',
          processData: false,
          contentType: false,
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