<?php
    // Obtener datos para los select
    include 'bd/conexion.php';

    // Canales de captación
    $canales_sql = "SELECT id, nombre FROM canales_captacion WHERE activo = 1 ORDER BY nombre";
    $canales_result = $conn->query($canales_sql);

    // Estados de lead
    $estados_sql = "SELECT id, nombre FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
    $estados_result = $conn->query($estados_sql);

    // Grados con niveles
    $grados_sql = "SELECT g.id, g.nombre, ne.nombre as nivel_nombre 
                FROM grados g 
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id 
                WHERE g.activo = 1 
                ORDER BY ne.orden_display, g.orden_display";
    $grados_result = $conn->query($grados_sql);

    // Usuarios (responsables)
    $usuarios_sql = "SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo 
                    FROM usuarios 
                    WHERE activo = 1 AND rol_id IN (2, 3) 
                    ORDER BY nombre";
    $usuarios_result = $conn->query($usuarios_sql);
?>

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

<!-- Modal para crear nuevo lead -->
<div class="modal fade" id="modalNuevoLead" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user-plus me-2"></i>
                    Registrar Nuevo Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoLead" method="POST" novalidate>
                <div class="modal-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="leadTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="estudiante-tab" data-bs-toggle="tab" 
                                    data-bs-target="#estudiante-tab-pane" type="button" role="tab">
                                <i class="ti ti-school me-1"></i>Estudiante
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" 
                                    data-bs-target="#contacto-tab-pane" type="button" role="tab">
                                <i class="ti ti-phone me-1"></i>Contacto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="adicional-tab" data-bs-toggle="tab" 
                                    data-bs-target="#adicional-tab-pane" type="button" role="tab">
                                <i class="ti ti-settings me-1"></i>Adicional
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="leadTabContent">
                        <!-- Pestaña Estudiante -->
                        <div class="tab-pane fade show active" id="estudiante-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_estudiante" 
                                           required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los nombres del estudiante</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_estudiante" 
                                           required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los apellidos del estudiante</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento_estudiante" 
                                           max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Género</label>
                                    <select class="form-select" name="genero_estudiante">
                                        <option value="">Seleccionar</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Grado de Interés <span class="text-danger">*</span></label>
                                    <select class="form-select" name="grado_interes_id" required>
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
                                    <input type="text" class="form-control" name="colegio_procedencia" maxlength="100">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Motivo del Cambio</label>
                                <textarea class="form-control" name="motivo_cambio" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>

                        <!-- Pestaña Contacto -->
                        <div class="tab-pane fade" id="contacto-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_contacto" 
                                        required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los nombres del contacto</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_contacto" 
                                        required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Por favor ingrese los apellidos del contacto</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="telefono" 
                                        required minlength="9" maxlength="9" pattern="[0-9]{9}"
                                        placeholder="Ej: 987654321">
                                    <div class="invalid-feedback">Debe tener exactamente 9 dígitos</div>
                                    <small class="text-muted">Solo números, 9 dígitos exactos</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="text" class="form-control" name="whatsapp" 
                                        minlength="9" maxlength="9" pattern="[0-9]{9}"
                                        placeholder="Ej: 987654321">
                                    <div class="invalid-feedback">Debe tener exactamente 9 dígitos</div>
                                    <small class="text-muted">Solo números, 9 dígitos exactos</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Email (Opcional)</label>
                                <input type="email" class="form-control" name="email" maxlength="100">
                                <small class="text-muted">El email es opcional</small>
                            </div>
                        </div>

                        <!-- Pestaña Adicional -->
                        <div class="tab-pane fade" id="adicional-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Canal de Captación <span class="text-danger">*</span></label>
                                    <select class="form-select" name="canal_captacion_id" required>
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
                                    <select class="form-select" name="estado_lead_id" required>
                                        <?php 
                                        $estados_result->data_seek(0);
                                        while($estado = $estados_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $estado['id']; ?>" 
                                                    <?php echo $estado['id'] == 1 ? 'selected' : ''; ?>>
                                                <?php echo $estado['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id">
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
                                    <select class="form-select" name="prioridad">
                                        <option value="baja">Baja</option>
                                        <option value="media" selected>Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Puntaje de Interés (0-100)</label>
                                    <input type="number" class="form-control" name="puntaje_interes" 
                                           min="0" max="100" value="50" maxlength="3" 
                                           oninput="if(this.value.length > 3) this.value = this.value.slice(0,3)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Próxima Acción (Fecha)</label>
                                    <input type="date" class="form-control" name="proxima_accion_fecha" 
                                           min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Descripción Próxima Acción</label>
                                <input type="text" class="form-control" name="proxima_accion_descripcion" 
                                       placeholder="Ej: Programar visita guiada" maxlength="200">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="2" maxlength="500"></textarea>
                            </div>
                            
                            <!-- Campos UTM -->
                            <div class="mt-3">
                                <h6 class="text-muted">Información de Tracking (Opcional)</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Source</label>
                                        <input type="text" class="form-control" name="utm_source" maxlength="100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Medium</label>
                                        <input type="text" class="form-control" name="utm_medium" maxlength="100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Campaign</label>
                                        <input type="text" class="form-control" name="utm_campaign" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Registrar Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Validación en tiempo real
        $('#formNuevoLead input[required], #formNuevoLead select[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Solo permitir números en teléfono y whatsapp
        $('input[name="telefono"], input[name="whatsapp"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 9) {
                this.value = this.value.slice(0, 9);
            }
        });
        
        function validateField($field) {
            if (!$field[0].checkValidity()) {
                $field.addClass('is-invalid');
                return false;
            } else {
                $field.removeClass('is-invalid');
                return true;
            }
        }
        
        $('#formNuevoLead').on('submit', function(e) {
            e.preventDefault();
            
            // Validar todos los campos requeridos
            let isValid = true;
            $(this).find('input[required], select[required]').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            
            // Validación específica de teléfono - 9 dígitos exactos
            const telefono = $('input[name="telefono"]').val().trim();
            if (telefono.length !== 9) {
                Swal.fire({
                    icon: 'error',
                    title: 'Teléfono inválido',
                    text: 'El teléfono debe tener exactamente 9 dígitos',
                    confirmButtonColor: '#FA896B'
                });
                $('input[name="telefono"]').addClass('is-invalid').focus();
                $('#contacto-tab').tab('show');
                return false;
            }
            
            // Validación específica de WhatsApp si está lleno - 9 dígitos exactos
            const whatsapp = $('input[name="whatsapp"]').val().trim();
            if (whatsapp && whatsapp.length !== 9) {
                Swal.fire({
                    icon: 'error',
                    title: 'WhatsApp inválido',
                    text: 'El WhatsApp debe tener exactamente 9 dígitos',
                    confirmButtonColor: '#FA896B'
                });
                $('input[name="whatsapp"]').addClass('is-invalid').focus();
                $('#contacto-tab').tab('show');
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
                title: 'Registrando lead...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'acciones/leads/crear_lead.php',
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
                            html: `
                                <p><strong>Lead registrado exitosamente</strong></p>
                                <p>Código: <span class="badge bg-primary">${response.codigo_lead}</span></p>
                            `,
                            confirmButtonColor: '#5D87FF',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $('#modalNuevoLead').modal('hide');
                            $('#formNuevoLead')[0].reset();
                            $('.is-invalid').removeClass('is-invalid');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al registrar',
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
        $('#modalNuevoLead').on('hidden.bs.modal', function() {
            $('#formNuevoLead')[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('#estudiante-tab').tab('show');
        });
    });
</script>