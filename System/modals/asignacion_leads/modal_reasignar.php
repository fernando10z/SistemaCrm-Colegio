<!-- Modal para reasignar leads -->
<div class="modal fade" id="modalReasignar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="ti ti-refresh me-2"></i>
                    Reasignar Leads entre Responsables
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReasignar" method="POST">
                <input type="hidden" name="accion" value="reasignar_leads">
                <input type="hidden" name="usuario_origen_id" id="reasignar_usuario_origen_id">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Reasignando leads desde: <strong id="reasignar_usuario_origen_nombre"></strong>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Reasignación <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_reasignacion" id="tipo_reasignacion" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="leads_especificos">Leads Específicos</option>
                                <option value="por_criterio">Por Criterio</option>
                                <option value="transferir_todos">Transferir Todos</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuario Destino <span class="text-danger">*</span></label>
                            <select class="form-select" name="usuario_destino_id" id="usuario_destino" required>
                                <option value="">Seleccionar usuario destino...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Leads Específicos -->
                    <div id="reasignacion_especifica" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Leads a Reasignar <span class="text-danger">*</span></label>
                            <select class="form-select" name="leads_para_reasignar[]" id="leads_para_reasignar" multiple size="8">
                                <option value="">Cargando leads...</option>
                            </select>
                            <small class="text-muted">Mantén Ctrl/Cmd presionado para seleccionar múltiples leads</small>
                        </div>
                    </div>

                    <!-- Por Criterio -->
                    <div id="reasignacion_criterio" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado Actual</label>
                                <select class="form-select" name="criterio_estado" id="criterio_estado">
                                    <option value="">Todos los estados</option>
                                    <?php
                                    $estados_query = "SELECT id, nombre FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
                                    $estados_result = $conn->query($estados_query);
                                    while($estado = $estados_result->fetch_assoc()) {
                                        echo "<option value='{$estado['id']}'>" . htmlspecialchars($estado['nombre']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select" name="criterio_prioridad" id="criterio_prioridad">
                                    <option value="">Todas las prioridades</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Transferir Todos -->
                    <div id="reasignacion_total" style="display: none;">
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>¡Atención!</strong> Esta opción transferirá TODOS los leads activos del usuario origen.
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="confirmar_transferencia_total" id="confirmar_transferencia_total">
                                <label class="form-check-label" for="confirmar_transferencia_total">
                                    <strong>Confirmo que deseo transferir TODOS los leads</strong>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo_transferencia" id="motivo_transferencia">
                                <option value="">Seleccionar motivo...</option>
                                <option value="cambio_rol">Cambio de rol</option>
                                <option value="licencia">Licencia/Vacaciones</option>
                                <option value="renuncia">Renuncia</option>
                                <option value="reorganizacion">Reorganización</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones_reasignacion" id="observaciones_reasignacion" rows="3" placeholder="Motivo de la reasignación..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notificar_origen" id="notificar_origen" checked>
                                <label class="form-check-label" for="notificar_origen">
                                    Notificar al usuario origen
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notificar_destino" id="notificar_destino" checked>
                                <label class="form-check-label" for="notificar_destino">
                                    Notificar al usuario destino
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-info" id="btn_preview_reasignacion">
                        <i class="ti ti-eye me-1"></i>Vista Previa
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-refresh me-1"></i>Confirmar Reasignación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Función para abrir el modal
    window.abrirModalReasignar = function(usuarioId, usuarioNombre) {
        $('#reasignar_usuario_origen_id').val(usuarioId);
        $('#reasignar_usuario_origen_nombre').text(usuarioNombre);
        
        cargarUsuariosDisponibles(usuarioId);
        
        $('#formReasignar')[0].reset();
        $('#tipo_reasignacion').val('').trigger('change');
        
        $('#modalReasignar').modal('show');
    };

    // Cargar usuarios disponibles
    function cargarUsuariosDisponibles(usuarioOrigenId) {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_usuarios_disponibles.php',
            method: 'POST',
            data: { usuario_origen_id: usuarioOrigenId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var $select = $('#usuario_destino');
                    $select.empty().append('<option value="">Seleccionar usuario destino...</option>');
                    
                    $.each(response.usuarios, function(index, usuario) {
                        $select.append($('<option>', {
                            value: usuario.id,
                            text: usuario.nombre + ' (' + usuario.total_leads + ' leads)'
                        }));
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar usuarios disponibles'
                });
            }
        });
    }

    // Cargar leads del usuario
    function cargarLeadsUsuario(usuarioId) {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_leads_usuarios.php',
            method: 'POST',
            data: { usuario_id: usuarioId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var $select = $('#leads_para_reasignar');
                    $select.empty();
                    
                    if (response.leads.length === 0) {
                        $select.append('<option value="">No hay leads disponibles</option>');
                    } else {
                        $.each(response.leads, function(index, lead) {
                            $select.append($('<option>', {
                                value: lead.id,
                                text: lead.nombre
                            }));
                        });
                    }
                }
            }
        });
    }

    // Cambio de tipo de reasignación
    $('#tipo_reasignacion').change(function() {
        var tipo = $(this).val();
        
        $('#reasignacion_especifica, #reasignacion_criterio, #reasignacion_total').hide();
        $('#confirmar_transferencia_total, #motivo_transferencia').prop('required', false);
        
        if (tipo === 'leads_especificos') {
            $('#reasignacion_especifica').show();
            var usuarioId = $('#reasignar_usuario_origen_id').val();
            if (usuarioId) {
                cargarLeadsUsuario(usuarioId);
            }
        } else if (tipo === 'por_criterio') {
            $('#reasignacion_criterio').show();
        } else if (tipo === 'transferir_todos') {
            $('#reasignacion_total').show();
            $('#confirmar_transferencia_total, #motivo_transferencia').prop('required', true);
        }
    });

    // Vista previa
    $('#btn_preview_reasignacion').click(function() {
        var tipo = $('#tipo_reasignacion').val();
        var usuarioOrigen = $('#reasignar_usuario_origen_id').val();
        var usuarioDestino = $('#usuario_destino').val();
        
        if (!tipo || !usuarioDestino) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor completa todos los campos requeridos'
            });
            return;
        }
        
        var data = {
            tipo: tipo,
            usuario_origen: usuarioOrigen,
            usuario_destino: usuarioDestino
        };
        
        if (tipo === 'leads_especificos') {
            data.leads_ids = $('#leads_para_reasignar').val();
        } else if (tipo === 'por_criterio') {
            data.criterio_estado = $('#criterio_estado').val();
            data.criterio_prioridad = $('#criterio_prioridad').val();
        }
        
        Swal.fire({
            title: 'Generando vista previa...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'acciones/asignacion_leads/preview_reasignacion.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Vista Previa',
                        html: `
                            <div class="text-start">
                                <p><strong>Leads a transferir:</strong> ${response.cantidad}</p>
                                <p><strong>De:</strong> ${response.usuario_origen}</p>
                                <p><strong>A:</strong> ${response.usuario_destino}</p>
                            </div>
                        `,
                        width: '500px'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            }
        });
    });

    // Envío del formulario
    $('#formReasignar').submit(function(e) {
        e.preventDefault();
        
        var tipo = $('#tipo_reasignacion').val();
        
        if (tipo === 'transferir_todos') {
            if (!$('#confirmar_transferencia_total').is(':checked')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Confirmación requerida',
                    text: 'Debes confirmar la transferencia total'
                });
                return false;
            }
            
            Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: 'Se transferirán TODOS los leads activos',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, transferir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    procesarReasignacion();
                }
            });
            return false;
        }
        
        if (tipo === 'leads_especificos') {
            var leadsSeleccionados = $('#leads_para_reasignar').val();
            if (!leadsSeleccionados || leadsSeleccionados.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección requerida',
                    text: 'Debes seleccionar al menos un lead'
                });
                return false;
            }
        }
        
        Swal.fire({
            icon: 'question',
            title: '¿Confirmar reasignación?',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reasignar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarReasignacion();
            }
        });
        
        return false;
    });

    // Procesar reasignación
    function procesarReasignacion() {
        var formData = $('#formReasignar').serialize();
        
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'acciones/asignacion_leads/procesar_reasignacion.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reasignación exitosa!',
                        text: response.message + ' (' + response.cantidad_reasignados + ' leads)',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        $('#modalReasignar').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la reasignación'
                });
            }
        });
    }
});
</script>