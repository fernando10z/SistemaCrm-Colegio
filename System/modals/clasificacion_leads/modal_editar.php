<!-- Modal para editar estado -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>
                    Editar Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarEstado" method="POST" action="acciones/clasificacion_leads/procesar_estado.php">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_estado_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Estado <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                        <small class="text-muted">Nombre descriptivo del estado en el pipeline</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Color del Estado <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color" 
                                           id="edit_color_picker" required>
                                    <input type="text" class="form-control" id="edit_color_hex" 
                                           pattern="^#[0-9A-Fa-f]{6}$" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Orden de Visualización <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="orden_display" 
                                       id="edit_orden_display" min="1" max="100" required>
                            </div>
                        </div>
                    </div>

                    <!-- Vista previa del badge -->
                    <div class="mb-3">
                        <label class="form-label">Vista Previa</label>
                        <div class="p-3 bg-light rounded">
                            <span id="edit_preview_badge" class="badge" style="padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600;">
                                Estado Ejemplo
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="es_final" id="edit_es_final" value="1">
                            <label class="form-check-label" for="edit_es_final">
                                <strong>Es un Estado Final</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            Los estados finales representan el fin del proceso
                        </small>
                    </div>

                    <!-- Información de uso del estado -->
                    <div class="card bg-warning bg-opacity-10 border-warning">
                        <div class="card-body p-3">
                            <h6 class="card-title text-warning mb-2">
                                <i class="ti ti-alert-triangle me-1"></i>
                                Información del Estado
                            </h6>
                            <div class="row text-sm">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Leads actuales:</strong> <span id="edit_total_leads">0</span></p>
                                    <p class="mb-1"><strong>Creado:</strong> <span id="edit_fecha_creacion">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Último uso:</strong> <span id="edit_ultimo_uso">-</span></p>
                                    <p class="mb-1"><strong>Estado:</strong> <span id="edit_estado_activo">Activo</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones avanzadas -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Opciones Avanzadas</h6>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" 
                                    data-bs-target="#opcionesAvanzadas" aria-expanded="false">
                                <i class="ti ti-settings"></i>
                            </button>
                        </div>
                        
                        <div class="collapse mt-2" id="opcionesAvanzadas">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_notificar_cambios">
                                            <label class="form-check-label" for="edit_notificar_cambios">
                                                Notificar cambios a responsables
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit_requerir_observaciones">
                                            <label class="form-check-label" for="edit_requerir_observaciones">
                                                Requerir observaciones al cambiar a este estado
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small">Tiempo promedio en estado (días)</label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="edit_tiempo_promedio" min="0" step="0.1">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Puntaje de interés sugerido</label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="edit_puntaje_sugerido" min="0" max="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btn_desactivar_estado">
                        <i class="ti ti-trash me-1"></i>Desactivar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Actualizar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateEditPreviewBadge() {
        var nombre = $('#edit_nombre').val() || 'Estado Ejemplo';
        var color = $('#edit_color_picker').val();
        var esFinal = $('#edit_es_final').is(':checked');
        
        var badge = $('#edit_preview_badge');
        badge.text(nombre).css('background-color', color);
        
        if (esFinal) {
            badge.addClass('badge-final');
        } else {
            badge.removeClass('badge-final');
        }
    }


$(document).ready(function() {
    // Actualizar vista previa del badge en tiempo real
    $('#modalEditar input[name="nombre"], #modalEditar input[name="color"]').on('input', function() {
        updateEditPreviewBadge();
    });

    // Sincronizar color picker con input hex
    $('#edit_color_picker').on('input', function() {
        $('#edit_color_hex').val($(this).val());
        updateEditPreviewBadge();
    });

    // Actualizar preview cuando cambia el checkbox de estado final
    $('#edit_es_final').on('change', function() {
        updateEditPreviewBadge();
    });

    // Envío del formulario
    $('#formEditarEstado').on('submit', function(e) {
        e.preventDefault();
        
        // Validar que el orden no esté duplicado
        var orden = $('#edit_orden_display').val();
        var estadoId = $('#edit_estado_id').val();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Estado actualizado exitosamente');
                    $('#modalEditar').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al actualizar el estado');
            }
        });
    });

    // Manejar desactivación del estado
    $('#btn_desactivar_estado').on('click', function() {
        var estadoId = $('#edit_estado_id').val();
        var totalLeads = parseInt($('#edit_total_leads').text()) || 0;
        
        if (totalLeads > 0) {
            alert('No se puede desactivar este estado porque tiene ' + totalLeads + ' leads asociados. Mueva los leads a otro estado primero.');
            return;
        }
        
        if (confirm('¿Está seguro de que desea desactivar este estado? Esta acción no se puede deshacer.')) {
            $.ajax({
                url: 'acciones/clasificacion_leads/procesar_estado.php',
                method: 'POST',
                data: {
                    accion: 'desactivar',
                    id: estadoId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Estado desactivado exitosamente');
                        $('#modalEditar').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al desactivar el estado');
                }
            });
        }
    });

    // Colores predefinidos sugeridos para edición
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
                               onclick="$('#edit_color_picker').val('${color.valor}'); $('#edit_color_hex').val('${color.valor}'); updateEditPreviewBadge();"
                               title="${color.nombre}"></button>`;
    });
    coloresHtml += '</div>';
    
    $('#edit_color_picker').closest('.input-group').after(coloresHtml);
});

// Función para cargar datos en el modal de edición
function cargarDatosEstadoEdicion(data) {
    $('#edit_estado_id').val(data.id);
    $('#edit_nombre').val(data.nombre);
    $('#edit_descripcion').val(data.descripcion);
    $('#edit_color_picker').val(data.color);
    $('#edit_color_hex').val(data.color);
    $('#edit_orden_display').val(data.orden_display);
    $('#edit_es_final').prop('checked', data.es_final == 1);
    
    // Cargar información adicional
    $('#edit_total_leads').text(data.total_leads || 0);
    $('#edit_fecha_creacion').text(data.fecha_creacion_formateada || '-');
    $('#edit_ultimo_uso').text(data.ultimo_uso_formateado || '-');
    $('#edit_estado_activo').text(data.activo == 1 ? 'Activo' : 'Inactivo');
    
    // Cargar configuraciones avanzadas si existen
    if (data.configuraciones) {
        $('#edit_notificar_cambios').prop('checked', data.configuraciones.notificar_cambios || false);
        $('#edit_requerir_observaciones').prop('checked', data.configuraciones.requerir_observaciones || false);
        $('#edit_tiempo_promedio').val(data.configuraciones.tiempo_promedio || '');
        $('#edit_puntaje_sugerido').val(data.configuraciones.puntaje_sugerido || '');
    }
    
    updateEditPreviewBadge();
}
</script>