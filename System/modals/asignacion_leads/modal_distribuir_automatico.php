<!-- Modal para distribución automática de leads -->
<div class="modal fade" id="modalDistribuirAutomatico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-robot me-2"></i>
                    Distribución Automática Inteligente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="alert alert-primary">
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Sistema de Distribución Inteligente:</strong> Asigna leads automáticamente considerando carga de trabajo, especialización y rendimiento del equipo.
                </div>

                <form id="formDistribuirAutomatico" method="POST" action="acciones/asignacion_leads/procesar_distribucion_automatica.php">
                    <input type="hidden" name="accion" value="distribuir_automatico">

                    <!-- Pestañas de configuración -->
                    <ul class="nav nav-tabs mb-3" id="distribucionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="criterios-tab" data-bs-toggle="tab" data-bs-target="#criterios" type="button" role="tab">
                                <i class="ti ti-settings me-1"></i>
                                Criterios de Distribución
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="equipos-tab" data-bs-toggle="tab" data-bs-target="#equipos" type="button" role="tab">
                                <i class="ti ti-users me-1"></i>
                                Selección de Equipo
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reglas-tab" data-bs-toggle="tab" data-bs-target="#reglas" type="button" role="tab">
                                <i class="ti ti-rules me-1"></i>
                                Reglas Avanzadas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="simulacion-tab" data-bs-toggle="tab" data-bs-target="#simulacion" type="button" role="tab">
                                <i class="ti ti-eye me-1"></i>
                                Simulación
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="distribucionTabsContent">
                        <!-- Criterios de Distribución -->
                        <div class="tab-pane fade show active" id="criterios" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Filtros de Leads</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Estado de Leads</label>
                                                <select class="form-select" name="estados_incluir[]" id="estados_incluir" multiple>
                                                    <?php
                                                    $estados_query = "SELECT id, nombre FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
                                                    $estados_result = $conn->query($estados_query);
                                                    while($estado = $estados_result->fetch_assoc()) {
                                                        $selected = in_array($estado['nombre'], ['Nuevo', 'Contactado']) ? 'selected' : '';
                                                        echo "<option value='{$estado['id']}' $selected>" . htmlspecialchars($estado['nombre']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <small class="text-muted">Por defecto: Nuevo y Contactado</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Prioridad Mínima</label>
                                                <select class="form-select" name="prioridad_minima" id="prioridad_minima">
                                                    <option value="baja">Todas las prioridades</option>
                                                    <option value="media">Media y superior</option>
                                                    <option value="alta">Alta y urgente</option>
                                                    <option value="urgente">Solo urgentes</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Rango de Fechas</label>
                                                <select class="form-select" name="rango_fechas" id="rango_fechas">
                                                    <option value="sin_asignar">Solo sin asignar</option>
                                                    <option value="hoy">Creados hoy</option>
                                                    <option value="semana">Última semana</option>
                                                    <option value="mes">Último mes</option>
                                                    <option value="todos">Todos los activos</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Canales de Captación</label>
                                                <select class="form-select" name="canales_incluir[]" id="canales_incluir" multiple>
                                                    <?php
                                                    $canales_query = "SELECT id, nombre FROM canales_captacion WHERE activo = 1 ORDER BY nombre";
                                                    $canales_result = $conn->query($canales_query);
                                                    while($canal = $canales_result->fetch_assoc()) {
                                                        echo "<option value='{$canal['id']}' selected>" . htmlspecialchars($canal['nombre']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <small class="text-muted">Por defecto: Todos los canales</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Algoritmo de Distribución</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Método de Asignación</label>
                                                <select class="form-select" name="metodo_asignacion" id="metodo_asignacion" required>
                                                    <option value="">Seleccionar método</option>
                                                    <option value="round_robin">Round Robin (Rotativo)</option>
                                                    <option value="carga_balanceada">Carga Balanceada</option>
                                                    <option value="rendimiento">Por Rendimiento</option>
                                                    <option value="especializacion">Por Especialización</option>
                                                    <option value="hibrido">Híbrido (Recomendado)</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Cantidad Máxima por Usuario</label>
                                                <input type="number" class="form-control" name="max_por_usuario" id="max_por_usuario" min="1" max="50" value="10">
                                                <small class="text-muted">Límite de leads a asignar por usuario en esta sesión</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Porcentaje de Carga Objetivo</label>
                                                <div class="input-group">
                                                    <input type="range" class="form-range" name="carga_objetivo" id="carga_objetivo" min="50" max="100" value="75">
                                                    <span class="input-group-text" id="carga_objetivo_value">75%</span>
                                                </div>
                                                <small class="text-muted">Carga de trabajo objetivo para cada usuario</small>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="respetar_especializacion" id="respetar_especializacion" checked>
                                                <label class="form-check-label" for="respetar_especializacion">
                                                    Respetar especialización por nivel educativo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selección de Equipo -->
                        <div class="tab-pane fade" id="equipos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Seleccionar Usuarios del Equipo</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th width="10%">
                                                                <input type="checkbox" id="select_all_users" checked>
                                                            </th>
                                                            <th>Usuario</th>
                                                            <th>Rol</th>
                                                            <th>Carga Actual</th>
                                                            <th>Rendimiento</th>
                                                            <th>Disponibilidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="usuarios_disponibles">
                                                        <!-- Se carga dinámicamente -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Resumen del Equipo</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="resumen_equipo">
                                                <p class="text-muted">Selecciona usuarios para ver el resumen</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Configuración de Peso</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-2">
                                                <label class="form-label">Carga de Trabajo</label>
                                                <input type="range" class="form-range" name="peso_carga" id="peso_carga" min="0" max="100" value="40">
                                                <small class="text-muted">40%</small>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Rendimiento</label>
                                                <input type="range" class="form-range" name="peso_rendimiento" id="peso_rendimiento" min="0" max="100" value="35">
                                                <small class="text-muted">35%</small>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Especialización</label>
                                                <input type="range" class="form-range" name="peso_especializacion" id="peso_especializacion" min="0" max="100" value="25">
                                                <small class="text-muted">25%</small>
                                            </div>
                                            <small class="text-muted">Total: <span id="peso_total">100%</span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reglas Avanzadas -->
                        <div class="tab-pane fade" id="reglas" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Reglas de Negocio</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="evitar_sobrecarga" id="evitar_sobrecarga" checked>
                                                <label class="form-check-label" for="evitar_sobrecarga">
                                                    Evitar sobrecarga de usuarios
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="priorizar_urgentes" id="priorizar_urgentes" checked>
                                                <label class="form-check-label" for="priorizar_urgentes">
                                                    Priorizar leads urgentes a mejores performers
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="equilibrar_niveles" id="equilibrar_niveles">
                                                <label class="form-check-label" for="equilibrar_niveles">
                                                    Equilibrar leads entre niveles educativos
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="considerar_horarios" id="considerar_horarios">
                                                <label class="form-check-label" for="considerar_horarios">
                                                    Considerar horarios de trabajo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Notificaciones</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="notificar_asignaciones" id="notificar_asignaciones" checked>
                                                <label class="form-check-label" for="notificar_asignaciones">
                                                    Notificar nuevas asignaciones por email
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="notificar_whatsapp" id="notificar_whatsapp">
                                                <label class="form-check-label" for="notificar_whatsapp">
                                                    Notificar por WhatsApp (urgentes)
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="generar_reporte" id="generar_reporte" checked>
                                                <label class="form-check-label" for="generar_reporte">
                                                    Generar reporte de distribución
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="programar_seguimiento" id="programar_seguimiento" checked>
                                                <label class="form-check-label" for="programar_seguimiento">
                                                    Programar seguimiento automático
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Programación</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="ejecucion_inmediata" id="ejecucion_inmediata" checked>
                                                <label class="form-check-label" for="ejecucion_inmediata">
                                                    Ejecutar inmediatamente
                                                </label>
                                            </div>
                                            
                                            <div id="programacion_avanzada" style="display: none;">
                                                <div class="mb-2">
                                                    <label class="form-label">Fecha y hora de ejecución</label>
                                                    <input type="datetime-local" class="form-control" name="fecha_ejecucion" id="fecha_ejecucion">
                                                </div>
                                                
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="repetir_automatico" id="repetir_automatico">
                                                    <label class="form-check-label" for="repetir_automatico">
                                                        Repetir automáticamente
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Simulación -->
                        <div class="tab-pane fade" id="simulacion" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Simulación de Distribución</h6>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_ejecutar_simulacion">
                                                <i class="ti ti-play me-1"></i>
                                                Ejecutar Simulación
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div id="resultados_simulacion">
                                                <div class="text-center py-4">
                                                    <i class="ti ti-click" style="font-size: 48px; color: #6c757d;"></i>
                                                    <p class="text-muted mt-2">Haz clic en "Ejecutar Simulación" para ver cómo se distribuirían los leads</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-outline-info" onclick="guardarConfiguracion()">
                    <i class="ti ti-device-floppy me-1"></i>
                    Guardar Configuración
                </button>
                <button type="submit" form="formDistribuirAutomatico" class="btn btn-primary">
                    <i class="ti ti-robot me-1"></i>
                    Iniciar Distribución
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cargar usuarios disponibles al abrir el modal
    $('#modalDistribuirAutomatico').on('show.bs.modal', function() {
        cargarUsuariosDisponibles();
    });

    // Actualizar valor del slider de carga
    $('#carga_objetivo').on('input', function() {
        $('#carga_objetivo_value').text($(this).val() + '%');
    });

    // Actualizar pesos y validar que sumen 100%
    $('#peso_carga, #peso_rendimiento, #peso_especializacion').on('input', function() {
        var total = parseInt($('#peso_carga').val()) + parseInt($('#peso_rendimiento').val()) + parseInt($('#peso_especializacion').val());
        $('#peso_total').text(total + '%');
        
        if (total !== 100) {
            $('#peso_total').addClass('text-danger');
        } else {
            $('#peso_total').removeClass('text-danger').addClass('text-success');
        }
    });

    // Manejar selección de todos los usuarios
    $('#select_all_users').change(function() {
        $('input[name="usuarios_seleccionados[]"]').prop('checked', $(this).is(':checked'));
        actualizarResumenEquipo();
    });

    // Manejar ejecución inmediata
    $('#ejecucion_inmediata').change(function() {
        if ($(this).is(':checked')) {
            $('#programacion_avanzada').hide();
        } else {
            $('#programacion_avanzada').show();
        }
    });

    // Ejecutar simulación
    $('#btn_ejecutar_simulacion').click(function() {
        var formData = $('#formDistribuirAutomatico').serialize() + '&simular=true';
        
        $('#resultados_simulacion').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Ejecutando simulación...</p></div>');
        
        $.ajax({
            url: 'acciones/asignacion_leads/simular_distribucion.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#resultados_simulacion').html(response.html);
                } else {
                    $('#resultados_simulacion').html('<div class="alert alert-danger">Error en simulación: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#resultados_simulacion').html('<div class="alert alert-danger">Error de conexión en la simulación.</div>');
            }
        });
    });

    function cargarUsuariosDisponibles() {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_usuarios_para_distribucion.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#usuarios_disponibles').html(response.html);
                    actualizarResumenEquipo();
                    
                    // Agregar eventos a los checkboxes de usuarios
                    $('input[name="usuarios_seleccionados[]"]').change(function() {
                        actualizarResumenEquipo();
                    });
                }
            },
            error: function() {
                $('#usuarios_disponibles').html('<tr><td colspan="6" class="text-danger">Error al cargar usuarios</td></tr>');
            }
        });
    }

    function actualizarResumenEquipo() {
        var usuariosSeleccionados = $('input[name="usuarios_seleccionados[]"]:checked').length;
        var totalUsuarios = $('input[name="usuarios_seleccionados[]"]').length;
        
        var html = '<div class="row text-center">';
        html += '<div class="col-6"><h5>' + usuariosSeleccionados + '</h5><small class="text-muted">Seleccionados</small></div>';
        html += '<div class="col-6"><h5>' + totalUsuarios + '</h5><small class="text-muted">Disponibles</small></div>';
        html += '</div>';
        
        if (usuariosSeleccionados > 0) {
            html += '<hr><div class="mt-2">';
            html += '<p class="small text-success"><i class="ti ti-check"></i> Equipo listo para distribución</p>';
            html += '</div>';
        }
        
        $('#resumen_equipo').html(html);
    }

    // Función para guardar configuración
    window.guardarConfiguracion = function() {
        var configuracion = $('#formDistribuirAutomatico').serialize();
        
        $.ajax({
            url: 'acciones/asignacion_leads/guardar_configuracion_distribucion.php',
            method: 'POST',
            data: configuracion,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Configuración guardada exitosamente');
                } else {
                    alert('Error al guardar configuración: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al guardar configuración');
            }
        });
    };

    // Validación antes de enviar
    $('#formDistribuirAutomatico').submit(function(e) {
        var usuariosSeleccionados = $('input[name="usuarios_seleccionados[]"]:checked').length;
        
        if (usuariosSeleccionados === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un usuario para la distribución');
            return false;
        }
        
        var total = parseInt($('#peso_carga').val()) + parseInt($('#peso_rendimiento').val()) + parseInt($('#peso_especializacion').val());
        if (total !== 100) {
            e.preventDefault();
            alert('Los pesos de criterios deben sumar exactamente 100%');
            return false;
        }
        
        if (!confirm('¿Confirmas que deseas iniciar la distribución automática con la configuración actual?')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>