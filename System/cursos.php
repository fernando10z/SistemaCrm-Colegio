<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';
  $sql2 = "SELECT id, nombre, email, telefono, fecha_registro, estado 
        FROM registros 
        ORDER BY fecha_registro DESC 
        LIMIT 10";
$result1 = $conn->query($sql2);
// Consulta para obtener los cursos

$sql = "SELECT 
    id_curso,
    titulo,
    descripcion,
    imagen_curso,
    autor_nombre,
    imagen_autor,
    tipo,
    link,
    estado
FROM cursos
ORDER BY id_curso DESC";

$result = $conn->query($sql);


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
    <title>Gestión de Cursos - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Gestión de Cursos"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Cursos, Administración"
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
    
    <!-- Custom styles for cursos -->
    <style>
      .badge-tipo {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
      }
      .tipo-free { background-color: #20c997; color: white; }
      .tipo-pay { background-color: #6f42c1; color: white; }
      
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
      
      .curso-imagen {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
      }
      
      .curso-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .curso-titulo {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
      }
      
      .curso-descripcion {
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
                    Cursos
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
                    Gestión de Cursos
                  </h3>
                  <small class="text-muted">
                    Administra los cursos disponibles en el sistema. 
                    Puedes crear, editar, activar/desactivar y gestionar cursos.
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                    <i class="ti ti-plus me-1"></i>
                    Nuevo Curso
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de cursos -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="cursos-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Imagen</th>
                        <th width="20%">Título</th>
                        <th width="20%">Descripción</th>
                        <th width="8%">Tipo</th>
                        <th width="8%">Estado</th>
                        <th width="8%">Enlace</th>
                        <th width="6%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Determinar clase CSS para el tipo
                              $tipo_class = ($row['tipo'] == 'Free') ? 'tipo-free' : 'tipo-pay';
                              
                              // Determinar clase CSS para el estado
                              $estado_class = ($row['estado'] == 'Activo') ? 'estado-activo' : 'estado-inactivo';
                              $estado_texto = $row['estado'];
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id_curso'] . "</strong></td>";
       echo "<td>
    <img src='../" . htmlspecialchars($row['imagen_curso']) . "' 
         class='curso-imagen' 
         alt='Imagen del curso'>
</td>";

                              echo "<td>
                                      <div class='curso-info'>
                                        <span class='curso-titulo'>" . htmlspecialchars($row['titulo']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='curso-descripcion'>" . htmlspecialchars($row['descripcion']) . "</div>
                                    </td>";
                              echo "<td><span class='badge badge-tipo " . $tipo_class . "'>" . 
                                   htmlspecialchars($row['tipo']) . "</span></td>";
                              echo "<td><span class='badge badge-estado " . $estado_class . "'>" . $estado_texto . "</span></td>";
                              echo "<td>
                                      <a href='" . htmlspecialchars($row['link']) . "' target='_blank' class='link-info'>
                                        <i class='ti ti-external-link me-1'></i>Ver enlace
                                      </a>
                                    </td>";
                              echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id_curso'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                                data-imagen_curso='" . htmlspecialchars($row['imagen_curso']) . "'
                                                data-autor_nombre='" . htmlspecialchars($row['autor_nombre'] ?? '') . "'
                                                data-tipo='" . $row['tipo'] . "'
                                                data-link='" . htmlspecialchars($row['link']) . "'
                                                data-estado='" . $row['estado'] . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-cambiar-estado' 
                                                data-id='" . $row['id_curso'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                data-estado='" . $row['estado'] . "'
                                                title='Cambiar Estado'>
                                          <i class='ti ti-refresh'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-danger btn-eliminare' 
                                                data-id='" . $row['id_curso'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                title='Eliminar'>
                                          <i class='ti ti-trash'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='9' class='text-center'>No hay cursos registrados</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Enlace</th>
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
<!-- Modal para nuevo curso -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-labelledby="modalNuevoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevoLabel">Crear Nuevo Curso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNuevoCurso" action="cursos/guardar_curso.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nuevo_titulo" class="form-label">Título *</label>
              <input type="text" class="form-control" id="nuevo_titulo" name="titulo" required>
            </div>
            <div class="col-12">
              <label for="nuevo_descripcion" class="form-label">Descripción *</label>
              <textarea class="form-control" id="nuevo_descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            <div class="col-md-6">
              <label for="nuevo_imagen_curso" class="form-label">Imagen Curso *</label>
              <input type="file" class="form-control" id="nuevo_imagen_curso" name="imagen_curso" accept="image/*" required>
              <img id="preview_nuevo" src="#" alt="Previsualización" class="img-fluid mt-2" style="display:none; max-height:150px;">
            </div>
            <div class="col-md-6">
              <label for="nuevo_link" class="form-label">Enlace *</label>
              <input type="text" class="form-control" id="nuevo_link" name="link" required>
            </div>
            <div class="col-md-6">
              <label for="nuevo_tipo" class="form-label">Tipo *</label>
              <select class="form-select" id="nuevo_tipo" name="tipo" required>
                <option value="">Seleccionar tipo</option>
                <option value="Free">Free</option>
                <option value="Pay">Pay</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="nuevo_estado" class="form-label">Estado *</label>
              <select class="form-select" id="nuevo_estado" name="estado" required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarNuevo">Guardar Curso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para editar curso -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Curso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarCurso" action="cursos/editar_curso.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="edit_id_curso" name="id_curso">

        <div class="modal-body">
          <div class="row g-3">
            <!-- Título -->
            <div class="col-md-6">
              <label for="edit_titulo" class="form-label">Título *</label>
              <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
            </div>

          
            <!-- Descripción -->
            <div class="col-12">
              <label for="edit_descripcion" class="form-label">Descripción *</label>
              <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
            </div>

            <!-- Imagen curso -->
            <div class="col-md-6">
              <label for="edit_imagen_curso" class="form-label">Imagen Curso</label>
              <input type="file" class="form-control" id="edit_imagen_curso" name="imagen_curso" accept="image/*">
              <img id="preview_edit" src="#" alt="Previsualización" class="img-fluid mt-2" style="max-height:150px;">
            </div>

            <!-- Enlace -->
            <div class="col-md-6">
              <label for="edit_link" class="form-label">Enlace *</label>
              <input type="text" class="form-control" id="edit_link" name="link" required>
            </div>

            <!-- Tipo -->
            <div class="col-md-6">
              <label for="edit_tipo" class="form-label">Tipo *</label>
              <select class="form-select" id="edit_tipo" name="tipo" required>
                <option value="Free">Free</option>
                <option value="Pay">Pay</option>
              </select>
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
          <button type="submit" class="btn btn-primary">Actualizar Curso</button>
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
      var table = $("#cursos-table").DataTable({
        "language": {
          "decimal": "",
          "emptyTable": "No hay cursos disponibles en la tabla",
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
            
            // Solo aplicar filtros a las primeras 8 columnas (sin acciones)
            if (index < 8) {
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

      // Función para exportar cursos a PDF con datos filtrados
      window.exportarCursosPDF = function() {
        var tabla = $('#cursos-table').DataTable();
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
        form.action = 'reports/generar_pdf_cursos.php';
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
    var id_curso = $(this).data('id');
    var titulo = $(this).data('titulo');
    var descripcion = $(this).data('descripcion');
    var imagen_curso = $(this).data('imagen_curso'); // URL de la imagen actual
    var autor_nombre = $(this).data('autor_nombre');
    var tipo = $(this).data('tipo');
    var link = $(this).data('link');
    var estado = $(this).data('estado');

    // Llenar los campos del modal
    $('#edit_id_curso').val(id_curso);
    $('#edit_titulo').val(titulo);
    $('#edit_descripcion').val(descripcion);
    $('#edit_autor_nombre').val(autor_nombre);
    $('#edit_tipo').val(tipo);
    $('#edit_link').val(link);
    $('#edit_estado').val(estado);

    // Mostrar la imagen actual
    $('#preview_edit').attr('src', imagen_curso).show();

    // Limpiar el input file
    $('#edit_imagen_curso').val('');

    // Mostrar modal
    $('#modalEditar').modal('show');
});

// Previsualizar nueva imagen seleccionada
$('#edit_imagen_curso').on('change', function(event) {
    var file = event.target.files[0];
    if(file){
        $('#preview_edit').attr('src', URL.createObjectURL(file));
    } else {
        // Si no hay archivo, mantener la imagen actual
        var currentSrc = $('#preview_edit').data('current') || '';
$('#preview_edit').attr('src', imagen_curso).data('current', imagen_curso).show();
    }
});


      // Manejar eliminación de curso
      $(document).on('click', '.btn-eliminare', function() {
        var id_curso = $(this).data('id');
        var titulo = $(this).data('titulo');
        
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Vas a eliminar el curso: ' + titulo,
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
              url: 'cursos/eliminar_curso.php?id=' + id_curso,
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
        var id_curso = $(this).data('id');
        var titulo = $(this).data('titulo');
        var estado_actual = $(this).data('estado');
        var nuevo_estado = (estado_actual == 'Activo') ? 'Inactivo' : 'Activo';
        
        Swal.fire({
          title: '¿Cambiar estado?',
          text: 'El curso "' + titulo + '" cambiará de ' + estado_actual + ' a ' + nuevo_estado,
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
              url: 'cursos/cambiar_estado.php',
              type: 'POST',
              dataType: 'json',
              data: { 
                id_curso: id_curso,
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

      // Envío de formularios con AJAX
$('#formNuevoCurso').on('submit', function(e) {
    e.preventDefault();
    enviarFormulario($(this));
});

$('#formEditarCurso').on('submit', function(e) {
    e.preventDefault();
    enviarFormulario($(this));
});

// Función para enviar formularios vía AJAX con FormData (para archivos)
function enviarFormulario(form) {
    var formData = new FormData(form[0]); // Captura todos los campos incluyendo archivos
    var url = form.attr('action');

    $.ajax({
        type: 'POST',
        url: url,
        data: formData,
        contentType: false, // importante para FormData
        processData: false, // importante para FormData
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
                    window.location.reload(); // recarga para actualizar lista de cursos
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

      // Tooltip para elementos truncados
      $('[title]').tooltip();
    });
    </script>
 <!-- Script para previsualización -->
<script>
  document.getElementById('nuevo_imagen_curso').addEventListener('change', function(event){
    const preview = document.getElementById('preview_nuevo');
    const file = event.target.files[0];
    if(file){
      preview.src = URL.createObjectURL(file);
      preview.style.display = 'block';
    } else {
      preview.src = '#';
      preview.style.display = 'none';
    }
  });
</script>
    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
</html>

<?php
// Cerrar conexión
$conn->close();
?>