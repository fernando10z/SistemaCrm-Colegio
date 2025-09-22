<?php

session_start();  
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Consulta para obtener los datos de configuración
$sql = "SELECT 
    id,
    clave,
    valor,
    tipo,
    descripcion,
    categoria,
    updated_at
FROM configuracion_sistema 
ORDER BY categoria, clave";

$result = $conn->query($sql);

$query_nombre = "SELECT valor FROM configuracion_sistema WHERE id = 1 LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
}
    
?>

<!DOCTYPE html>
<html lang="es">
  <!-- [Head] start -->
  <head>
    <title><?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Gestión Escolar, Configuración Sistema"
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
    
    <!-- Custom styles for badges -->
    <style>
      .badge-tipo {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
      }
      .tipo-texto { background-color: #e3f2fd; color: #1565c0; }
      .tipo-numero { background-color: #f3e5f5; color: #7b1fa2; }
      .tipo-booleano { background-color: #e8f5e8; color: #2e7d32; }
      .tipo-json { background-color: #fff3e0; color: #ef6c00; }
      
      .categoria-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 12px;
      }
      .cat-general { background-color: #e3f2fd; color: #0d47a1; }
      .cat-contacto { background-color: #e8f5e8; color: #1b5e20; }
      .cat-sistema { background-color: #fff3e0; color: #e65100; }
      .cat-finanzas { background-color: #fce4ec; color: #880e4f; }
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
                    <a href="javascript: void(0)">Configuración</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Sistema
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
                    Configuración del Sistema
                  </h3>
                  <small class="text-muted">
                    Gestiona la configuración general del sistema CRM escolar. 
                    Puedes buscar por categoría, clave o tipo de dato.
                  </small>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de configuraciones -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="configuracion-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Clave</th>
                        <th width="25%">Valor</th>
                        <th width="10%">Tipo</th>
                        <th width="25%">Descripción</th>
                        <th width="10%">Categoría</th>
                        <th width="15%">Actualizado</th>
                        <th width="10%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Reiniciar el resultado para mostrarlo en la tabla
                      $result = $conn->query($sql);
                      
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear la fecha
                              $fecha_formateada = date('d/m/Y H:i', strtotime($row['updated_at']));
                              
                              // Determinar clase CSS para el tipo
                              $tipo_class = 'tipo-' . $row['tipo'];
                              
                              // Determinar clase CSS para la categoría
                              $categoria_class = 'cat-' . $row['categoria'];
                              
                              // Truncar valor si es muy largo
                              $valor_mostrar = strlen($row['valor']) > 50 ? 
                                             substr($row['valor'], 0, 50) . '...' : 
                                             $row['valor'];
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td><code>" . htmlspecialchars($row['clave']) . "</code></td>";
                              echo "<td title='" . htmlspecialchars($row['valor']) . "'>" . 
                                   htmlspecialchars($valor_mostrar) . "</td>";
                              echo "<td><span class='badge badge-tipo " . $tipo_class . "'>" . 
                                   ucfirst($row['tipo']) . "</span></td>";
                              echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                              echo "<td><span class='badge categoria-badge " . $categoria_class . "'>" . 
                                   ucfirst($row['categoria']) . "</span></td>";
                              echo "<td>" . $fecha_formateada . "</td>";
                              echo "<td>
                                      <div class='btn-group btn-group-sm' role='group'>
                                        <button type='button' class='btn btn-outline-primary btn-editar' 
                                                data-id='" . $row['id'] . "'
                                                data-clave='" . htmlspecialchars($row['clave']) . "'
                                                data-valor='" . htmlspecialchars($row['valor']) . "'
                                                data-tipo='" . $row['tipo'] . "'
                                                data-descripcion='" . htmlspecialchars($row['descripcion']) . "'
                                                data-categoria='" . $row['categoria'] . "'
                                                title='Editar'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='8' class='text-center'>No hay configuraciones registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Clave</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Actualizado</th>
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

    <?php include 'modals/informacion/modal_editar.php'; ?>

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
            var table = $("#configuracion-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
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
              "order": [[ 0, "asc" ]], // Ordenar por ID ascendente (columna 0)
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

          // Manejar click en botón editar
          $(document).on('click', '.btn-editar', function() {
              var id = $(this).data('id');
              var clave = $(this).data('clave');
              var valor = $(this).data('valor');
              var tipo = $(this).data('tipo');
              var descripcion = $(this).data('descripcion');
              var categoria = $(this).data('categoria');
              
              $('#edit_id').val(id);
              $('#edit_clave').val(clave);
              $('#edit_valor').val(valor);
              $('#edit_tipo').val(tipo);
              $('#edit_descripcion').val(descripcion);
              $('#edit_categoria').val(categoria);
              
              $('#modalEditar').modal('show');
          });
      });
    </script>
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