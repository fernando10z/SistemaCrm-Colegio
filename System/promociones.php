<?php
session_start();
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener la sección promo
$sql = "SELECT 
    id,
    subtitulo
FROM seccion_promo
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
    <title>Gestión de Sección Promo - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Sección Promo"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Promo, Administración"
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
    
    <!-- Custom styles for seccion_promo -->
    <style>
      .promo-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .promo-titulo {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .promo-subtitulo {
        font-size: 0.75rem;
        color: #6c757d;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;
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
                    Sección Promo
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
                    Gestión de Sección Promo
                  </h3>
                  <small class="text-muted">
                    Administra la sección promocional. 
                    Puedes editar y gestionar el contenido.
                  </small>
                </div>
                <div class="d-flex gap-2">
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de sección promo -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="promo-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="40%">Subtítulo</th>
                        <th width="15%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                             
                              echo "<td>
                                      <div class='promo-subtitulo'>" . htmlspecialchars($row['subtitulo']) . "</div>
                                    </td>";
                              echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id'] . "'
                                                data-subtitulo='" . htmlspecialchars($row['subtitulo']) . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-danger btn-eliminar' 
                                                data-id='" . $row['id'] . "'
                                                title='Eliminar'>
                                          <i class='ti ti-trash'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='4' class='text-center'>No hay secciones promo registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Subtítulo</th>
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

<!-- Modal para editar sección promo -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Sección Promo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarPromo" action="seccion_promo/editar.php" method="POST">
        <input type="hidden" id="edit_id" name="id">

        <div class="modal-body">
          <div class="row g-3">
      

            <!-- Subtítulo -->
            <div class="col-12">
              <label for="edit_subtitulo" class="form-label">Subtítulo *</label>
              <textarea class="form-control" id="edit_subtitulo" name="subtitulo" rows="3" required></textarea>
              <small class="text-muted">Puedes usar &lt;em&gt;texto&lt;/em&gt; para texto en énfasis</small>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Sección Promo</button>
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
      var table = $("#promo-table").DataTable({
        "language": {
          "decimal": "",
          "emptyTable": "No hay secciones promo disponibles en la tabla",
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
          { "orderable": false, "targets": 2 } // Deshabilitar ordenación en columna de acciones
        ],
        "initComplete": function () {
          // Configurar filtros después de que la tabla esté completamente inicializada
          this.api().columns().every(function (index) {
            var column = this;
            
            // Solo aplicar filtros a las primeras 3 columnas (sin acciones)
            if (index < 3) {
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

      // Función para exportar promo a PDF con datos filtrados
      window.exportarPromoPDF = function() {
        var tabla = $('#promo-table').DataTable();
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
        form.action = 'reports/generar_pdf_promo.php';
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
        var subtitulo = $(this).data('subtitulo');

        // Llenar los campos del modal
        $('#edit_id').val(id);
        $('#edit_subtitulo').val(subtitulo);

        // Mostrar modal
        $('#modalEditar').modal('show');
      });

      // Manejar eliminación de sección promo
      $(document).on('click', '.btn-eliminar', function() {
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar la sección promo: ' + titulo,
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
              url: 'seccion_promo/eliminar.php?id=' + id,
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

      // Envío de formulario de edición con AJAX
      $('#formEditarPromo').on('submit', function(e) {
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