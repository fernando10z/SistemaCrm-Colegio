<style>
    .swal2-container {
        z-index: 9999999 !important;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>

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
            <form id="formEditarLead" method="POST" novalidate>
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
                                    <input type="text" class="form-control" name="nombres_estudiante" 
                                           id="edit_nombres_estudiante" required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los nombres del estudiante</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_estudiante" 
                                           id="edit_apellidos_estudiante" required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los apellidos del estudiante</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento_estudiante" 
                                           id="edit_fecha_nacimiento_estudiante" max="<?php echo date('Y-m-d'); ?>">
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
                                        <?php 
                                        $grados_result->data_seek(0);
                                        while($grado = $grados_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $grado['id']; ?>">
                                                <?php echo $grado['nivel_nombre'] . ' - ' . $grado['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor seleccione un grado</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Colegio de Procedencia</label>
                                    <input type="text" class="form-control" name="colegio_procedencia" 
                                           id="edit_colegio_procedencia" maxlength="100">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Motivo del Cambio</label>
                                <textarea class="form-control" name="motivo_cambio" id="edit_motivo_cambio" 
                                          rows="2" maxlength="500"></textarea>
                            </div>
                        </div>

                        <!-- Pestaña Contacto -->
                        <div class="tab-pane fade" id="edit-contacto-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_contacto" 
                                           id="edit_nombres_contacto" required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los nombres del contacto</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_contacto" 
                                           id="edit_apellidos_contacto" required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los apellidos del contacto</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="telefono" id="edit_telefono"
                                           required minlength="9" maxlength="9" pattern="[0-9]{9}"
                                           placeholder="Ej: 987654321">
                                    <div class="invalid-feedback">Debe tener exactamente 9 dígitos</div>
                                    <small class="text-muted">Solo números, 9 dígitos exactos</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="text" class="form-control" name="whatsapp" id="edit_whatsapp"
                                           minlength="9" maxlength="9" pattern="[0-9]{9}"
                                           placeholder="Ej: 987654321">
                                    <div class="invalid-feedback">Debe tener exactamente 9 dígitos</div>
                                    <small class="text-muted">Solo números, 9 dígitos exactos</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Email (Opcional)</label>
                                <input type="email" class="form-control" name="email" id="edit_email" maxlength="100">
                                <small class="text-muted">El email es opcional</small>
                            </div>
                        </div>

                        <!-- Pestaña Adicional -->
                        <div class="tab-pane fade" id="edit-adicional-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Canal de Captación <span class="text-danger">*</span></label>
                                    <select class="form-select" name="canal_captacion_id" id="edit_canal_captacion_id" required>
                                        <option value="">Seleccionar canal</option>
                                        <?php 
                                        $canales_result->data_seek(0);
                                        while($canal = $canales_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $canal['id']; ?>">
                                                <?php echo $canal['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor seleccione un canal</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" name="estado_lead_id" id="edit_estado_lead_id" required>
                                        <?php 
                                        $estados_result->data_seek(0);
                                        while($estado = $estados_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $estado['id']; ?>">
                                                <?php echo $estado['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id" id="edit_responsable_id">
                                        <option value="">Sin asignar</option>
                                        <?php 
                                        $usuarios_result->data_seek(0);
                                        while($usuario = $usuarios_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $usuario['id']; ?>">
                                                <?php echo $usuario['nombre_completo']; ?>
                                            </option>
                                        <?php endwhile; ?>
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
                                    <input type="number" class="form-control" name="puntaje_interes" 
                                           id="edit_puntaje_interes"  min="0" max="100" maxlength="3" 
                                           oninput="if(this.value.length > 3) this.value = this.value.slice(0,3)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Próxima Acción (Fecha)</label>
                                    <input type="date" class="form-control" name="proxima_accion_fecha" 
                                           id="edit_proxima_accion_fecha" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Descripción Próxima Acción</label>
                                <input type="text" class="form-control" name="proxima_accion_descripcion" 
                                       id="edit_proxima_accion_descripcion" maxlength="200">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="edit_observaciones" 
                                          rows="2" maxlength="500"></textarea>
                            </div>
                            
                            <!-- Campos UTM -->
                            <div class="mt-3">
                                <h6 class="text-muted">Información de Tracking</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Source</label>
                                        <input type="text" class="form-control" name="utm_source" 
                                               id="edit_utm_source" maxlength="100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Medium</label>
                                        <input type="text" class="form-control" name="utm_medium" 
                                               id="edit_utm_medium" maxlength="100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Campaign</label>
                                        <input type="text" class="form-control" name="utm_campaign" 
                                               id="edit_utm_campaign" maxlength="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Conversión</label>
                                        <input type="date" class="form-control" name="fecha_conversion" 
                                               id="edit_fecha_conversion">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Validación en tiempo real - EDITAR
    $('#formEditarLead input[required], #formEditarLead select[required]').on('blur', function() {
        validateEditField($(this));
    });
    
    // Solo permitir números en teléfono y whatsapp - EDITAR
    $('#edit_telefono, #edit_whatsapp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 9) {
            this.value = this.value.slice(0, 9);
        }
    });
    
    function validateEditField($field) {
        if (!$field[0].checkValidity()) {
            $field.addClass('is-invalid');
            return false;
        } else {
            $field.removeClass('is-invalid');
            return true;
        }
    }
    
    $('#formEditarLead').on('submit', function(e) {
        e.preventDefault();
        
        // Validar todos los campos requeridos
        let isValid = true;
        $(this).find('input[required], select[required]').each(function() {
            if (!validateEditField($(this))) {
                isValid = false;
            }
        });
        
        // Validación específica de teléfono - 9 dígitos exactos
        const telefono = $('#edit_telefono').val().trim();
        if (telefono.length !== 9) {
            Swal.fire({
                icon: 'error',
                title: 'Teléfono inválido',
                text: 'El teléfono debe tener exactamente 9 dígitos',
                confirmButtonColor: '#FA896B'
            });
            $('#edit_telefono').addClass('is-invalid').focus();
            $('#edit-contacto-tab').tab('show');
            return false;
        }
        
        // Validación específica de WhatsApp si está lleno - 9 dígitos exactos
        const whatsapp = $('#edit_whatsapp').val().trim();
        if (whatsapp && whatsapp.length !== 9) {
            Swal.fire({
                icon: 'error',
                title: 'WhatsApp inválido',
                text: 'El WhatsApp debe tener exactamente 9 dígitos',
                confirmButtonColor: '#FA896B'
            });
            $('#edit_whatsapp').addClass('is-invalid').focus();
            $('#edit-contacto-tab').tab('show');
            return false;
        }
        
        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor complete todos los campos obligatorios marcados con *',
                confirmButtonColor: '#5D87FF'
            });
            
            // Ir a la primera pestaña con error
            let $firstInvalid = $(this).find('.is-invalid').first();
            if ($firstInvalid.length) {
                let $tab = $firstInvalid.closest('.tab-pane');
                let tabId = $tab.attr('id').replace('-pane', '');
                $(`#${tabId}`).tab('show');
                $firstInvalid.focus();
            }
            
            return false;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Actualizando lead...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        $.ajax({
            url: 'acciones/leads/editar_lead.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Lead actualizado exitosamente',
                        confirmButtonColor: '#5D87FF',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        $('#modalEditar').modal('hide');
                        $('#formEditarLead')[0].reset();
                        $('.is-invalid').removeClass('is-invalid');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: response.message,
                        confirmButtonColor: '#FA896B'
                    });
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    html: `<p>No se pudo completar la operación</p>`,
                    confirmButtonColor: '#FA896B'
                });
            }
        });
    });
    
    // Resetear al cerrar modal
    $('#modalEditar').on('hidden.bs.modal', function() {
        $('#formEditarLead')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('#edit-estudiante-tab').tab('show');
    });
});
</script>