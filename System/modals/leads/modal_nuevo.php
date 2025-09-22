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
            <form id="formNuevoLead" method="POST" action="acciones/leads/procesar_lead.php">
                <input type="hidden" name="accion" value="crear">
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
                                    <input type="text" class="form-control" name="nombres_estudiante" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Estudiante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_estudiante" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento_estudiante">
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
                                        <?php while($grado = $grados_result->fetch_assoc()): ?>
                                            <option value="<?php echo $grado['id']; ?>">
                                                <?php echo $grado['nivel_nombre'] . ' - ' . $grado['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Colegio de Procedencia</label>
                                    <input type="text" class="form-control" name="colegio_procedencia">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Motivo del Cambio</label>
                                <textarea class="form-control" name="motivo_cambio" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Pestaña Contacto -->
                        <div class="tab-pane fade" id="contacto-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombres del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombres_contacto" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellidos del Contacto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="apellidos_contacto" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="telefono" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="tel" class="form-control" name="whatsapp">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>

                        <!-- Pestaña Adicional -->
                        <div class="tab-pane fade" id="adicional-tab-pane" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Canal de Captación <span class="text-danger">*</span></label>
                                    <select class="form-select" name="canal_captacion_id" required>
                                        <option value="">Seleccionar canal</option>
                                        <?php while($canal = $canales_result->fetch_assoc()): ?>
                                            <option value="<?php echo $canal['id']; ?>">
                                                <?php echo $canal['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" name="estado_lead_id" required>
                                        <?php while($estado = $estados_result->fetch_assoc()): ?>
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
                                        <?php while($usuario = $usuarios_result->fetch_assoc()): ?>
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
                                    <input type="number" class="form-control" name="puntaje_interes" min="0" max="100" value="50">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Próxima Acción (Fecha)</label>
                                    <input type="date" class="form-control" name="proxima_accion_fecha">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Descripción Próxima Acción</label>
                                <input type="text" class="form-control" name="proxima_accion_descripcion" placeholder="Ej: Programar visita guiada">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="2"></textarea>
                            </div>
                            
                            <!-- Campos UTM (opcional) -->
                            <div class="mt-3">
                                <h6 class="text-muted">Información de Tracking (Opcional)</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Source</label>
                                        <input type="text" class="form-control" name="utm_source">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Medium</label>
                                        <input type="text" class="form-control" name="utm_medium">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">UTM Campaign</label>
                                        <input type="text" class="form-control" name="utm_campaign">
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

<script>
$(document).ready(function() {
    $('#formNuevoLead').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Lead registrado exitosamente');
                    $('#modalNuevoLead').modal('hide');
                    location.reload(); // Recargar la página para ver el nuevo lead
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al registrar el lead');
            }
        });
    });
});
</script>