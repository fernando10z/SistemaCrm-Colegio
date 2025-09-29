<style>
/* Estilos para asegurar que SweetAlert2 se muestre por encima de todo */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

/* Estilo para que el modal esté por debajo del SweetAlert */
.modal {
    z-index: 9999 !important;
}

/* Estilo para el backdrop del modal */
.modal-backdrop {
    z-index: 9998 !important;
}
</style>

<!-- Modal Ver Usos del Código -->
<div class="modal fade" id="modalVerUsos" tabindex="-1" aria-labelledby="modalVerUsosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalVerUsosLabel">
          <i class="ti ti-history me-2"></i>
          Historial de Usos del Código
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        
        <!-- Información del Código -->
        <div class="card mb-3">
          <div class="card-header bg-light">
            <div class="row align-items-center">
              <div class="col-md-6">
                <h6 class="mb-0">
                  <i class="ti ti-ticket me-1"></i>
                  Información del Código
                </h6>
              </div>
              <div class="col-md-6 text-end">
                <span class="badge bg-primary" style="font-size: 1rem; padding: 0.5rem 1rem; letter-spacing: 1px;" 
                      id="codigoDisplayUsos">
                  CÓDIGO
                </span>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <small class="text-muted">Total de Usos:</small>
                <div class="fw-bold fs-4" id="totalUsosDisplay">0</div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Conversiones:</small>
                <div class="fw-bold fs-4 text-success" id="conversionesDisplay">0</div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Tasa de Conversión:</small>
                <div class="fw-bold fs-4 text-info" id="tasaConversionDisplay">0%</div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Usos Disponibles:</small>
                <div class="fw-bold fs-4 text-warning" id="usosDisponiblesDisplay">∞</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-3">
          <div class="card-body">
            <div class="row align-items-end">
              <div class="col-md-4">
                <label for="filtroFechaDesde" class="form-label">Desde:</label>
                <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
              </div>
              <div class="col-md-4">
                <label for="filtroFechaHasta" class="form-label">Hasta:</label>
                <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
              </div>
              <div class="col-md-4">
                <label for="filtroEstado" class="form-label">Estado:</label>
                <select class="form-select form-select-sm" id="filtroEstado">
                  <option value="">Todos</option>
                  <option value="1">Convertidos</option>
                  <option value="0">No Convertidos</option>
                </select>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-md-12 text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiarFiltros">
                  <i class="ti ti-filter-off me-1"></i>
                  Limpiar Filtros
                </button>
                <button type="button" class="btn btn-sm btn-info" id="btnAplicarFiltros">
                  <i class="ti ti-filter me-1"></i>
                  Aplicar Filtros
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de Usos -->
        <div class="dt-responsive table-responsive">
          <table id="usos-table" class="table table-striped table-bordered nowrap">
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha de Uso</th>
                <th>Lead Generado</th>
                <th>Estudiante</th>
                <th>Estado</th>
                <th>Fecha Conversión</th>
                <th>Observaciones</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="usosTableBody">
              <!-- Datos cargados dinámicamente -->
            </tbody>
          </table>
        </div>

        <!-- Mensaje si no hay usos -->
        <div id="noUsosMessage" class="alert alert-info text-center d-none">
          <i class="ti ti-info-circle me-2"></i>
          Este código aún no ha sido utilizado. Los usos se registrarán aquí automáticamente.
        </div>

      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-info" id="btnExportarUsos">
          <i class="ti ti-download me-1"></i>
          Exportar a Excel
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let codigoReferidoIdActual = null;
    let usosDataTable = null;

    // Cargar usos cuando se abre el modal
    $(document).on('click', '.btn-ver-usos', function() {
        codigoReferidoIdActual = $(this).data('id');
        const codigo = $(this).data('codigo');
        
        // Actualizar header del modal
        $('#codigoDisplayUsos').text(codigo);
        
        // Cargar datos de usos
        cargarUsosDelCodigo(codigoReferidoIdActual);
    });

    // Función para cargar usos del código
    function cargarUsosDelCodigo(codigoId) {
        // Mostrar loading
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo historial de usos',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Petición AJAX
        $.ajax({
            url: 'acciones/referidos_exalumnos/obtener_usos_codigo.php',
            type: 'POST',
            dataType: 'json',
            data: {
                codigo_referido_id: codigoId,
                accion: 'obtener_usos'
            },
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    // Actualizar estadísticas
                    $('#totalUsosDisplay').text(response.estadisticas.total_usos);
                    $('#conversionesDisplay').text(response.estadisticas.conversiones);
                    $('#tasaConversionDisplay').text(response.estadisticas.tasa_conversion + '%');
                    $('#usosDisponiblesDisplay').text(response.estadisticas.usos_disponibles);

                    // Cargar tabla de usos
                    cargarTablaUsos(response.usos);
                    
                    // Mostrar/ocultar mensaje de sin usos
                    if (response.usos.length === 0) {
                        $('#noUsosMessage').removeClass('d-none');
                        $('.dt-responsive').addClass('d-none');
                    } else {
                        $('#noUsosMessage').addClass('d-none');
                        $('.dt-responsive').removeClass('d-none');
                    }
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudieron cargar los usos del código'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            }
        });
    }

    // Función para cargar tabla de usos
    function cargarTablaUsos(usos) {
        const tbody = $('#usosTableBody');
        tbody.empty();

        if (usos.length === 0) {
            return;
        }

        usos.forEach(function(uso) {
            const estadoBadge = uso.convertido == 1 ? 
                '<span class="badge bg-success">Convertido</span>' : 
                '<span class="badge bg-warning">Pendiente</span>';

            const fechaConversion = uso.fecha_conversion ? 
                new Date(uso.fecha_conversion).toLocaleDateString('es-PE') : 
                '<span class="text-muted">-</span>';

            const estudianteNombre = uso.estudiante_nombre || 
                '<span class="text-muted">Sin asignar</span>';

            const observaciones = uso.observaciones || 
                '<span class="text-muted">-</span>';

            const row = `
                <tr>
                    <td>${uso.id}</td>
                    <td>${new Date(uso.fecha_uso).toLocaleDateString('es-PE')}</td>
                    <td>${uso.lead_codigo || '<span class="text-muted">-</span>'}</td>
                    <td>${estudianteNombre}</td>
                    <td>${estadoBadge}</td>
                    <td>${fechaConversion}</td>
                    <td style="max-width: 200px;">
                        <small>${observaciones}</small>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-info btn-ver-detalle-uso"
                                data-uso-id="${uso.id}">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Inicializar o actualizar DataTable
        if (usosDataTable) {
            usosDataTable.destroy();
        }

        usosDataTable = $('#usos-table').DataTable({
            "language": {
                "decimal": "",
                "emptyTable": "No hay usos registrados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "pageLength": 10,
            "order": [[ 1, "desc" ]],
            "columnDefs": [
                { "orderable": false, "targets": 7 }
            ]
        });
    }

    // Aplicar filtros
    $('#btnAplicarFiltros').on('click', function() {
        const fechaDesde = $('#filtroFechaDesde').val();
        const fechaHasta = $('#filtroFechaHasta').val();
        const estado = $('#filtroEstado').val();

        if (!fechaDesde && !fechaHasta && !estado) {
            Swal.fire({
                icon: 'info',
                title: 'Sin Filtros',
                text: 'Debe seleccionar al menos un filtro',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        // Aplicar filtros al DataTable
        if (usosDataTable) {
            // Filtro de fecha
            if (fechaDesde || fechaHasta) {
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const fechaUso = new Date(data[1].split('/').reverse().join('-'));
                    const desde = fechaDesde ? new Date(fechaDesde) : null;
                    const hasta = fechaHasta ? new Date(fechaHasta) : null;

                    if (desde && fechaUso < desde) return false;
                    if (hasta && fechaUso > hasta) return false;
                    return true;
                });
            }

            // Filtro de estado
            if (estado) {
                usosDataTable.column(4).search(
                    estado == '1' ? 'Convertido' : 'Pendiente'
                ).draw();
            }

            usosDataTable.draw();
        }
    });

    // Limpiar filtros
    $('#btnLimpiarFiltros').on('click', function() {
        $('#filtroFechaDesde').val('');
        $('#filtroFechaHasta').val('');
        $('#filtroEstado').val('');
        
        // Limpiar filtros personalizados
        $.fn.dataTable.ext.search.pop();
        
        if (usosDataTable) {
            usosDataTable.search('').columns().search('').draw();
        }

        Swal.fire({
            icon: 'success',
            title: 'Filtros Limpiados',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Ver detalle de un uso específico
    $(document).on('click', '.btn-ver-detalle-uso', function() {
        const usoId = $(this).data('uso-id');
        
        Swal.fire({
            title: 'Detalle del Uso',
            text: 'Cargando información...',
            icon: 'info',
            confirmButtonText: 'Aceptar'
        });
    });

    // Exportar a Excel
    $('#btnExportarUsos').on('click', function() {
        if (!usosDataTable || usosDataTable.rows().count() === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin Datos',
                text: 'No hay datos para exportar',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Exportando...',
            text: 'Se descargará el archivo Excel',
            timer: 2000,
            showConfirmButton: false
        });

        // Aquí implementar lógica de exportación
        // Por ahora solo muestra mensaje
    });

    // Limpiar al cerrar modal
    $('#modalVerUsos').on('hidden.bs.modal', function() {
        if (usosDataTable) {
            usosDataTable.destroy();
            usosDataTable = null;
        }
        $('#usosTableBody').empty();
        $('#filtroFechaDesde').val('');
        $('#filtroFechaHasta').val('');
        $('#filtroEstado').val('');
        $.fn.dataTable.ext.search.pop();
    });
});
</script>