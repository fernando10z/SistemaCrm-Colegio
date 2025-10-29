<!-- Modal para crear nuevo estado -->
<div class="modal fade" id="modalNuevoEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-plus me-2"></i>
                    Crear Nuevo Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoEstado" method="POST" action="acciones/clasificacion_leads/procesar_estado.php">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Estado <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required 
                               placeholder="Ej: Contactado, Interesado, Matriculado">
                        <small class="text-muted">Nombre descriptivo del estado en el pipeline</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2" 
                                  placeholder="Descripción del propósito y características de este estado"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Color del Estado <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color" 
                                           value="#007bff" required id="nuevo_color_picker">
                                    <input type="text" class="form-control" id="nuevo_color_hex" 
                                           value="#007bff" pattern="^#[0-9A-Fa-f]{6}$" readonly>
                                </div>
                                <small class="text-muted">Color que se usará para identificar este estado</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Orden de Visualización <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="orden_display" 
                                       min="1" max="100" value="1" required>
                                <small class="text-muted">Posición en el pipeline (1 = primero)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Vista previa del badge -->
                    <div class="mb-3">
                        <label class="form-label">Vista Previa</label>
                        <div class="p-3 bg-light rounded">
                            <span id="preview_badge" class="badge" style="background-color: #007bff; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600;">
                                Estado Ejemplo
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="es_final" id="nuevo_es_final" value="1">
                            <label class="form-check-label" for="nuevo_es_final">
                                <strong>Es un Estado Final</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            Los estados finales representan el fin del proceso (ej: Matriculado, No Interesado)
                        </small>
                    </div>

                    <div class="card bg-info bg-opacity-10 border-info">
                        <div class="card-body p-3">
                            <h6 class="card-title text-info mb-2">
                                <i class="ti ti-info-circle me-1"></i>
                                Configuración Recomendada
                            </h6>
                            <ul class="mb-0 small">
                                <li><strong>Estados Iniciales:</strong> Nuevo, Captado (colores azules)</li>
                                <li><strong>Estados de Proceso:</strong> Contactado, Interesado, Visita Programada (colores amarillos/naranjas)</li>
                                <li><strong>Estados Positivos:</strong> Matriculado, Convertido (colores verdes)</li>
                                <li><strong>Estados Negativos:</strong> No Interesado, Sin Respuesta (colores rojos/grises)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Crear Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function updatePreviewBadge() {
    var nombre = $('#modalNuevoEstado input[name="nombre"]').val() || 'Estado Ejemplo';
    var color = $('#modalNuevoEstado input[name="color"]').val();
    
    $('#preview_badge').text(nombre).css('background-color', color);
}

$(document).ready(function() {
    // Actualizar vista previa del badge en tiempo real
    $('#modalNuevoEstado input[name="nombre"], #modalNuevoEstado input[name="color"]').on('input', function() {
        updatePreviewBadge();
    });

    // Sincronizar color picker con input hex
    $('#nuevo_color_picker').on('input', function() {
        $('#nuevo_color_hex').val($(this).val());
        updatePreviewBadge();
    });

    // Envío del formulario con SweetAlert
    $('#formNuevoEstado').on('submit', function(e) {
        e.preventDefault();
        
        // Mostrar loading
        Swal.fire({
            title: 'Creando Estado...',
            html: 'Por favor espere',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Estado creado exitosamente',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'Entendido'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#modalNuevoEstado').modal('hide');
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo crear el estado',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Entendido'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    html: '<p>No se pudo comunicar con el servidor</p>' +
                          '<small class="text-muted">Código: ' + xhr.status + '</small>',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Entendido',
                    footer: '<small>Por favor, verifica tu conexión e intenta nuevamente</small>'
                });
            }
        });
    });

    // Limpiar formulario al cerrar modal
    $('#modalNuevoEstado').on('hidden.bs.modal', function() {
        $('#formNuevoEstado')[0].reset();
        $('#nuevo_color_picker').val('#007bff');
        $('#nuevo_color_hex').val('#007bff');
        updatePreviewBadge();
    });

    // Colores predefinidos sugeridos
    const coloresSugeridos = [
        { nombre: 'Azul', valor: '#007bff' },
        { nombre: 'Verde', valor: '#28a745' },
        { nombre: 'Amarillo', valor: '#ffc107' },
        { nombre: 'Naranja', valor: '#fd7e14' },
        { nombre: 'Rojo', valor: '#dc3545' },
        { nombre: 'Morado', valor: '#6f42c1' },
        { nombre: 'Celeste', valor: '#17a2b8' },
        { nombre: 'Gris', valor: '#6c757d' }
    ];

    // Agregar botones de colores sugeridos
    var coloresHtml = '<div class="mt-2"><small class="text-muted">Colores sugeridos:</small><br>';
    coloresSugeridos.forEach(function(color) {
        coloresHtml += `<button type="button" class="btn btn-sm me-1 mt-1" 
                               style="background-color: ${color.valor}; width: 25px; height: 25px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #ccc;"
                               onclick="$('#nuevo_color_picker').val('${color.valor}'); $('#nuevo_color_hex').val('${color.valor}'); updatePreviewBadge();"
                               title="${color.nombre}"></button>`;
    });
    coloresHtml += '</div>';
    
    $('#nuevo_color_picker').closest('.input-group').after(coloresHtml);
});

// ====================== FUNCIÓN PARA EDITAR ESTADO ======================
function editarEstado(id, nombre, descripcion, color, orden, esFinal) {
    // Cargar datos en el modal de edición (crear este modal si no existe)
    $('#modalEditarEstado input[name="id"]').val(id);
    $('#modalEditarEstado input[name="nombre"]').val(nombre);
    $('#modalEditarEstado textarea[name="descripcion"]').val(descripcion);
    $('#modalEditarEstado input[name="color"]').val(color);
    $('#modalEditarEstado input[name="orden_display"]').val(orden);
    
    if (esFinal == 1) {
        $('#modalEditarEstado input[name="es_final"]').prop('checked', true);
    } else {
        $('#modalEditarEstado input[name="es_final"]').prop('checked', false);
    }
    
    // Mostrar modal
    $('#modalEditarEstado').modal('show');
}

// ====================== FUNCIÓN PARA ELIMINAR ESTADO ======================
function eliminarEstado(id, nombre) {
    Swal.fire({
        title: '¿Eliminar Estado?',
        html: `<p>¿Estás seguro de que deseas eliminar el estado <strong>"${nombre}"</strong>?</p>` +
              '<p class="text-danger mb-0"><small>Esta acción no se puede deshacer</small></p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: 'Por favor espere',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'acciones/clasificacion_leads/procesar_estado.php',
                method: 'POST',
                data: {
                    accion: 'eliminar',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: response.message || 'Estado eliminado exitosamente',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo eliminar',
                            text: response.message,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Entendido'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo comunicar con el servidor',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Entendido'
                    });
                }
            });
        }
    });
}

// ====================== FUNCIÓN PARA CAMBIAR ESTADO ACTIVO ======================
function cambiarEstadoActivo(id, nombre, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const accionTexto = nuevoEstado == 1 ? 'activar' : 'desactivar';
    
    Swal.fire({
        title: `¿${accionTexto.charAt(0).toUpperCase() + accionTexto.slice(1)} Estado?`,
        html: `¿Deseas ${accionTexto} el estado <strong>"${nombre}"</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#28a745' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${accionTexto}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                html: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'acciones/clasificacion_leads/procesar_estado.php',
                method: 'POST',
                data: {
                    accion: 'cambiar_estado',
                    id: id,
                    activo: nuevoEstado
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: response.message,
                            confirmButtonColor: '#28a745',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo comunicar con el servidor',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    });
}
</script>