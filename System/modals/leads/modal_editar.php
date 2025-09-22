<!-- Modal para editar lead -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>
                    Editar Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarLead" method="POST" action="acciones/leads/procesar_lead.php">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="editLeadTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="edit-estudiante-tab" data-bs-toggle="tab" 
                                    data-bs-target="#edit-estudiante-tab-pane" type="button" role="tab">
                                <i class="ti ti-school me-1"></i>Estudiante
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-contacto-tab" data-bs-toggle="tab" 
                                    data-bs-target="#edit-contacto-tab-pane" type="button" role="tab">
                                <i class="ti ti-phone me-1"></i>Contacto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-adicional-tab" data-bs-toggle="tab" 
                                    data-bs-target="#edit-adicional-tab-pane" type="button" role="tab">
                                <i class="ti ti-settings me-1"></i>Adicional
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="editLeadTabContent">
                        <!-- Pestaña Estudiante -->
                        <div class="tab-pane fade show active" id="edit-estudiante-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_estudiante" id="edit_nombres_estudiante" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_estudiante" id="edit_apellidos_estudiante" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento_estudiante" id="edit_fecha_nacimiento_estudiante">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Género</label>
                                    <select class="form-select" name="genero_estudiante" id="edit_genero_estudiante">
                                        <option value="">Seleccionar</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Grado de Interés <span class="text-danger">*</span></label>
                                    <select class="form-select" name="grado_interes_id" id="edit_grado_interes_id" required>
                                        <option value="">Seleccionar grado</option>
                                        <!-- Opciones se cargarán dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Colegio de Procedencia</label>
                                    <input type="text" class="form-control" name="colegio_procedencia" id="edit_colegio_procedencia">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Motivo del Cambio</label>
                                <textarea class="form-control" name="motivo_cambio" id="edit_motivo_cambio" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Pestaña Contacto -->
                        <div class="tab-pane fade" id="edit-contacto-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_contacto" id="edit_nombres_contacto" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_contacto" id="edit_apellidos_contacto" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="telefono" id="edit_telefono" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="tel" class="form-control" name="whatsapp" id="edit_whatsapp">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                        </div>

                        <!-- Pestaña Adicional -->
                        <div class="tab-pane fade" id="edit-adicional-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Canal de Captación <span class="text-danger">*</span></label>
                                    <select class="form-select" name="canal_captacion_id" id="edit_canal_captacion_id" required>
                                        <option value="">Seleccionar canal</option>
                                        <!-- Opciones se cargarán dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" name="estado_lead_id" id="edit_estado_lead_id" required>
                                        <!-- Opciones se cargarán dinámicamente -->
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id" id="edit_responsable_id">
                                        <option value="">Sin asignar</option>
                                        <!-- Opciones se cargarán dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select" name="prioridad" id="edit_prioridad">
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Puntaje de Interés (0-100)</label>
                                    <input type="number" class="form-control" name="puntaje_interes" id="edit_puntaje_interes" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Próxima Acción (Fecha)</label>
                                    <input type="date" class="form-control" name="proxima_accion_fecha" id="edit_proxima_accion_fecha">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Descripción Próxima Acción</label>
                                <input type="text" class="form-control" name="proxima_accion_descripcion" id="edit_proxima_accion_descripcion">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="edit_observaciones" rows="2"></textarea>
                            </div>
                            
                            <!-- Campos UTM -->
                            <div class="mt-3">
                                <h6 class="text-muted">Información de Tracking</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Source</label>
                                        <input type="text" class="form-control" name="utm_source" id="edit_utm_source">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Medium</label>
                                        <input type="text" class="form-control" name="utm_medium" id="edit_utm_medium">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Campaign</label>
                                        <input type="text" class="form-control" name="utm_campaign" id="edit_utm_campaign">
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Conversión</label>
                                        <input type="date" class="form-control" name="fecha_conversion" id="edit_fecha_conversion">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Código de Lead</label>
                                        <input type="text" class="form-control" id="edit_codigo_lead" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Actualizar Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#formEditarLead').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Lead actualizado exitosamente');
                    $('#modalEditar').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al actualizar el lead');
            }
        });
    });
});
</script>