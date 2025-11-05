<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los títulos
$sql = "SELECT 
    id,
    video_url,
    titulo,
    subtitulo,
    boton_texto,
    boton_url
FROM Titulos
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
    <title>Gestión de Títulos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Títulos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Títulos, Video, Administración"
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
    
    <!-- Custom styles for titulos -->
    <style>
      .badge-estado {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 500;
      }
      
      .video-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .video-titulo {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .video-descripcion {
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
      
      .preview-video {
        max-width: 100%;
        height: auto;
        max-height: 200px;
        border-radius: 5px;
        margin-top: 10px;
        display: none;
      }
      
      .video-thumbnail {
        width: 100px;
        height: 70px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #dee2e6;
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
                    Títulos
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
                    Gestión de Títulos
                  </h3>
                  <small class="text-muted">
                    Administra la sección de títulos con video. 
                    Puedes editar y gestionar el contenido.
                  </small>
                </div>
                <div class="d-flex gap-2">
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de títulos -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="titulos-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="10%">Video</th>
                        <th width="15%">Título</th>
                        <th width="15%">Subtítulo</th>
                        <th width="15%">Texto Botón</th>
                        <th width="15%">URL Botón</th>
                        <th width="10%">Acciones</th>
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
                                      <video class='video-thumbnail' controls>
                                        <source src='../" . htmlspecialchars($row['video_url']) . "' type='video/mp4'>
                                        Tu navegador no soporta el elemento de video.
                                      </video>
                                    </td>";
                              echo "<td>
                                      <div class='video-info'>
                                        <span class='video-titulo'>" . htmlspecialchars($row['titulo']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>" . htmlspecialchars($row['subtitulo']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['boton_texto'] ?? '') . "</td>";
                              echo "<td>
                                      <a href='" . htmlspecialchars($row['boton_url'] ?? '#') . "' target='_blank' class='link-info'>
                                        <i class='ti ti-external-link me-1'></i>Ver enlace
                                      </a>
                                    </td>";
                              echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id'] . "'
                                                data-video_url='" . htmlspecialchars($row['video_url']) . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                data-subtitulo='" . htmlspecialchars($row['subtitulo']) . "'
                                                data-boton_texto='" . htmlspecialchars($row['boton_texto'] ?? '') . "'
                                                data-boton_url='" . htmlspecialchars($row['boton_url'] ?? '') . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                           </div>
                                    </td>";
                              echo "</tr>";
                                     //   <button type='button' class='btn btn-outline-danger btn-eliminar' 
                                      //          data-id='" . $row['id'] . "'
                                      //          data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                     //           title='Eliminar'>
                                    //      <i class='ti ti-trash'></i>
                                   //     </button>
                                   
                          }
                      } else {
                          echo "<tr><td colspan='7' class='text-center'>No hay títulos registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Video</th>
                        <th>Título</th>
                        <th>Subtítulo</th>
                        <th>Texto Botón</th>
                        <th>URL Botón</th>
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
<!-- Modal para editar título -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Título</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarTitulo" action="titulos/editar.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="edit_id" name="id">
        <input type="hidden" id="edit_video_actual" name="video_actual">

        <div class="modal-body">
          <div class="row g-3">
            <!-- Título -->
            <div class="col-md-6">
              <label for="edit_titulo" class="form-label">Título *</label>
              <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
            </div>

            <!-- Subtítulo -->
            <div class="col-md-6">
              <label for="edit_subtitulo" class="form-label">Subtítulo *</label>
              <input type="text" class="form-control" id="edit_subtitulo" name="subtitulo" required>
            </div>

            <!-- Texto Botón -->
            <div class="col-md-6">
              <label for="edit_boton_texto" class="form-label">Texto del Botón</label>
              <input type="text" class="form-control" id="edit_boton_texto" name="boton_texto">
            </div>

            <!-- URL Botón -->
            <div class="col-md-6">
              <label for="edit_boton_url" class="form-label">URL del Botón</label>
              <!-- 🔹 Cambio: antes era type="url", ahora es text -->
              <input type="text" class="form-control" id="edit_boton_url" name="boton_url">
            </div>

            <!-- Video -->
            <div class="col-12">
              <label for="edit_video" class="form-label">Video</label>
              <input type="file" class="form-control" id="edit_video" name="video" accept="video/*">
              <small class="text-muted">Formatos permitidos: MP4, AVI, MOV. Tamaño máximo: 10MB</small>
              
              <!-- Vista previa del video -->
              <div class="mt-2">
                <video id="preview_video" class="preview-video" controls></video>
                <div id="current_video" class="mt-2"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Título</button>
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
      var table = $("#titulos-table").DataTable({
        "language": {
          "decimal": "",
          "emptyTable": "No hay títulos disponibles en la tabla",
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
          { "orderable": false, "targets": 6 } // Deshabilitar ordenación en columna de acciones
        ],
        "initComplete": function () {
          // Configurar filtros después de que la tabla esté completamente inicializada
          this.api().columns().every(function (index) {
            var column = this;
            
            // Solo aplicar filtros a las primeras 6 columnas (sin acciones)
            if (index < 6) {
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

      // Función para exportar títulos a PDF con datos filtrados
      window.exportarTitulosPDF = function() {
        var tabla = $('#titulos-table').DataTable();
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
        form.action = 'reports/generar_pdf_titulos.php';
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
        var video_url = $(this).data('video_url');
        var titulo = $(this).data('titulo');
        var subtitulo = $(this).data('subtitulo');
        var boton_texto = $(this).data('boton_texto');
        var boton_url = $(this).data('boton_url');

        // Llenar los campos del modal
        $('#edit_id').val(id);
        $('#edit_video_actual').val(video_url);
        $('#edit_titulo').val(titulo);
        $('#edit_subtitulo').val(subtitulo);
        $('#edit_boton_texto').val(boton_texto);
        $('#edit_boton_url').val(boton_url);
        
        // Mostrar video actual
        if (video_url) {
          $('#current_video').html('<strong>Video actual:</strong><br>' +
            '<video class="video-thumbnail mt-2" controls>' +
            '<source src="../' + video_url + '" type="video/mp4">' +
            'Tu navegador no soporta el elemento de video.' +
            '</video>');
        } else {
          $('#current_video').html('');
        }

        // Mostrar modal
        $('#modalEditar').modal('show');
      });

      // Vista previa del video seleccionado
      $('#edit_video').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('#preview_video').attr('src', e.target.result).show();
          }
          reader.readAsDataURL(file);
        }
      });

      // Manejar eliminación de título
      $(document).on('click', '.btn-eliminar', function() {
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar el título: ' + titulo,
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
              url: 'titulos/eliminar.php?id=' + id,
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
      $('#formEditarTitulo').on('submit', function(e) {
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