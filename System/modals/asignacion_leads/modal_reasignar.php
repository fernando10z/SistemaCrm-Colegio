<!-- Modal para reasignar leads -->
<div class="modal fade" id="modalReasignar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-refresh me-2"></i>
                    Reasignar Leads entre Responsables
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReasignar" method="POST" action="acciones/asignacion_leads/procesar_reasignacion.php">
                <input type="hidden" name="accion" value="reasignar_leads">
                <input type="hidden" name="usuario_origen_id" id="reasignar_usuario_origen_id">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Reasignando leads desde: <strong id="reasignar_usuario_origen_nombre"></strong>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Reasignación <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo_reasignacion" id="tipo_reasignacion" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="leads_especificos">Leads Específicos</option>
                                    <option value="por_criterio">Por Criterio</option>
                                    <option value="balancear_carga">Balancear Carga</option>
                                    <option value="transferir_todos">Transferir Todos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usuario Destino <span class="text-danger">*</span></label>
                                <select class="form-select" name="usuario_destino_id" id="usuario_destino" required>
                                    <option value="">Seleccionar usuario destino...</option>
                                </select>
                            </div>
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluir_historial">
                                <label class="form-check-label" for="incluir_historial">
                                    Transferir historial completo de interacciones
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Por Criterio -->
                    <div id="reasignacion_criterio" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
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
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select" name="criterio_prioridad" id="criterio_prioridad">
                                        <option value="">Todas las prioridades</option>
                                        <option value="urgente">Solo urgentes</option>
                                        <option value="alta">Alta</option>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Creación</label>
                                    <select class="form-select" name="criterio_fecha" id="criterio_fecha">
                                        <option value="">Todas las fechas</option>
                                        <option value="ultima_semana">Última semana</option>
                                        <option value="ultimo_mes">Último mes</option>
                                        <option value="mas_30_dias">Más de 30 días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sin Actividad por</label>
                                    <select class="form-select" name="criterio_inactividad" id="criterio_inactividad">
                                        <option value="">Cualquier actividad</option>
                                        <option value="7">Más de 7 días</option>
                                        <option value="15">Más de 15 días</option>
                                        <option value="30">Más de 30 días</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balancear Carga -->
                    <div id="reasignacion_balanceo" style="display: none;">
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong>Balanceo Automático:</strong> Se transferirán leads para equilibrar la carga de trabajo entre usuarios.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Porcentaje de Carga Objetivo</label>
                                    <input type="number" class="form-control" name="carga_objetivo" id="carga_objetivo" min="20" max="80" value="70">
                                    <small class="text-muted">Carga máxima recomendada por usuario</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cantidad Máxima a Transferir</label>
                                    <input type="number" class="form-control" name="max_transferir" id="max_transferir" min="1" max="50" value="10">
                                </div>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="priorizar_antiguos" id="priorizar_antiguos" checked>
                            <label class="form-check-label" for="priorizar_antiguos">
                                Priorizar transferencia de leads más antiguos
                            </label>
                        </div>
                    </div>

                    <!-- Transferir Todos -->
                    <div id="reasignacion_total" style="display: none;">
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>¡Atención!</strong> Esta opción transferirá TODOS los leads activos del usuario origen al usuario destino.
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="confirmar_transferencia_total" id="confirmar_transferencia_total" required>
                                <label class="form-check-label" for="confirmar_transferencia_total">
                                    <strong>Confirmo que deseo transferir TODOS los leads</strong>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo de la transferencia total <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo_transferencia" id="motivo_transferencia">
                                <option value="">Seleccionar motivo...</option>
                                <option value="cambio_rol">Cambio de rol del empleado</option>
                                <option value="licencia">Licencia médica/vacaciones</option>
                                <option value="renuncia">Renuncia del empleado</option>
                                <option value="reorganizacion">Reorganización del equipo</option>
                                <option value="sobrecarga">Sobrecarga de trabajo</option>
                                <option value="otro">Otro motivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones de la Reasignación</label>
                        <textarea class="form-control" name="observaciones_reasignacion" id="observaciones_reasignacion" rows="3" placeholder="Explica el motivo de la reasignación, instrucciones especiales, etc."></textarea>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btn_preview_reasignacion">
                        <i class="ti ti-eye me-1"></i>
                        Vista Previa
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-refresh me-1"></i>
                        Confirmar Reasignación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar cambio de tipo de reasignación
    $('#tipo_reasignacion').change(function() {
        var tipo = $(this).val();
        $('#reasignacion_especifica, #reasignacion_criterio, #reasignacion_balanceo, #reasignacion_total').hide();
        
        // Resetear campos requeridos
        $('#confirmar_transferencia_total, #motivo_transferencia').prop('required', false);
        
        if (tipo === 'leads_especificos') {
            $('#reasignacion_especifica').show();
        } else if (tipo === 'por_criterio') {
            $('#reasignacion_criterio').show();
        } else if (tipo === 'balancear_carga') {
            $('#reasignacion_balanceo').show();
        } else if (tipo === 'transferir_todos') {
            $('#reasignacion_total').show();
            $('#confirmar_transferencia_total, #motivo_transferencia').prop('required', true);
        }
    });

    // Vista previa de reasignación
    $('#btn_preview_reasignacion').click(function() {
        var tipo = $('#tipo_reasignacion').val();
        var usuarioOrigen = $('#reasignar_usuario_origen_id').val();
        var usuarioDestino = $('#usuario_destino').val();
        
        if (!tipo || !usuarioDestino) {
            alert('Por favor completa todos los campos requeridos');
            return;
        }
        
        var data = {
            tipo: tipo,
            usuario_origen: usuarioOrigen,
            usuario_destino: usuarioDestino
        };
        
        // Agregar datos específicos según el tipo
        if (tipo === 'leads_especificos') {
            data.leads_ids = $('#leads_para_reasignar').val();
        } else if (tipo === 'por_criterio') {
            data.criterio_estado = $('#criterio_estado').val();
            data.criterio_prioridad = $('#criterio_prioridad').val();
            data.criterio_fecha = $('#criterio_fecha').val();
            data.criterio_inactividad = $('#criterio_inactividad').val();
        } else if (tipo === 'balancear_carga') {
            data.carga_objetivo = $('#carga_objetivo').val();
            data.max_transferir = $('#max_transferir').val();
        }
        
        $.ajax({
            url: 'acciones/asignacion_leads/preview_reasignacion.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var mensaje = 'Vista Previa de Reasignación:\n\n';
                    mensaje += 'Leads a transferir: ' + response.cantidad + '\n';
                    mensaje += 'Usuario origen: ' + response.usuario_origen + '\n';
                    mensaje += 'Usuario destino: ' + response.usuario_destino + '\n\n';
                    mensaje += 'Detalles:\n' + response.detalles;
                    alert(mensaje);
                } else {
                    alert('Error en vista previa: ' + response.message);
                }
            },
            error: function() {
                alert('Error al generar vista previa de reasignación.');
            }
        });
    });

    // Validación antes de enviar
    $('#formReasignar').submit(function(e) {
        var tipo = $('#tipo_reasignacion').val();
        
        if (tipo === 'transferir_todos') {
            if (!$('#confirmar_transferencia_total').is(':checked')) {
                e.preventDefault();
                alert('Debes confirmar la transferencia total de leads.');
                return false;
            }
            
            if (!confirm('¿Estás completamente seguro de que deseas transferir TODOS los leads? Esta acción afectará significativamente la carga de trabajo de ambos usuarios.')) {
                e.preventDefault();
                return false;
            }
        }
        
        if (tipo === 'leads_especificos') {
            var leadsSeleccionados = $('#leads_para_reasignar').val();
            if (!leadsSeleccionados || leadsSeleccionados.length === 0) {
                e.preventDefault();
                alert('Debes seleccionar al menos un lead para reasignar.');
                return false;
            }
        }
        
        return true;
    });
});
</script>