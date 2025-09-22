<!-- Modal para configurar flujo de estados -->
<div class="modal fade" id="modalConfigurarFlujo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-git-branch me-2"></i>
                    Configurar Flujo de Estados: <span id="flujo_estado_nombre" class="text-primary">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="flujo_estado_id">
                
                <!-- Pestañas de configuración -->
                <ul class="nav nav-tabs" id="flujoTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="transiciones-tab" data-bs-toggle="tab" 
                                data-bs-target="#transiciones-tab-pane" type="button" role="tab">
                            <i class="ti ti-arrow-right me-1"></i>Transiciones Permitidas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reglas-tab" data-bs-toggle="tab" 
                                data-bs-target="#reglas-tab-pane" type="button" role="tab">
                            <i class="ti ti-shield-check me-1"></i>Reglas y Validaciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="automatizacion-tab" data-bs-toggle="tab" 
                                data-bs-target="#automatizacion-tab-pane" type="button" role="tab">
                            <i class="ti ti-robot me-1"></i>Automatización
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="visualizacion-tab" data-bs-toggle="tab" 
                                data-bs-target="#visualizacion-tab-pane" type="button" role="tab">
                            <i class="ti ti-chart-line me-1"></i>Visualización
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="flujoTabContent">
                    <!-- Pestaña Transiciones -->
                    <div class="tab-pane fade show active" id="transiciones-tab-pane" role="tabpanel">
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Estados Permitidos DESDE este estado:</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="estados_destino">
                                                <!-- Se llenará dinámicamente -->
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn_agregar_destino">
                                                <i class="ti ti-plus me-1"></i>Agregar Estado Destino
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Estados Permitidos HACIA este estado:</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="estados_origen">
                                                <!-- Se llenará dinámicamente -->
                                            </div>
                                            <button type="button" class="btn btn-outline-success btn-sm mt-2" id="btn_agregar_origen">
                                                <i class="ti ti-plus me-1"></i>Agregar Estado Origen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mapa visual de flujo -->
                            <div class="mt-4">
                                <h6>Mapa Visual del Flujo:</h6>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div id="flujo_visual" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            <p class="mt-2">Generando visualización del flujo...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Reglas -->
                    <div class="tab-pane fade" id="reglas-tab-pane" role="tabpanel">
                        <div class="mt-3">
                            <form id="formReglasEstado">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Validaciones de Entrada:</h6>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="requerir_observaciones">
                                                <label class="form-check-label" for="requerir_observaciones">
                                                    Requerir observaciones al entrar a este estado
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="requerir_responsable">
                                                <label class="form-check-label" for="requerir_responsable">
                                                    Requerir responsable asignado
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="validar_datos_completos">
                                                <label class="form-check-label" for="validar_datos_completos">
                                                    Validar que el lead tenga datos completos
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Puntaje mínimo de interés requerido:</label>
                                            <input type="number" class="form-control" id="puntaje_minimo" min="0" max="100" placeholder="0-100">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Restricciones de Usuario:</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Roles que pueden mover leads a este estado:</label>
                                            <div id="roles_permitidos">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" name="roles[]" id="rol_admin">
                                                    <label class="form-check-label" for="rol_admin">Administrador</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="2" name="roles[]" id="rol_marketing">
                                                    <label class="form-check-label" for="rol_marketing">Coordinador Marketing</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="3" name="roles[]" id="rol_tutor">
                                                    <label class="form-check-label" for="rol_tutor">Tutor</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="solo_responsable_asignado">
                                                <label class="form-check-label" for="solo_responsable_asignado">
                                                    Solo el responsable asignado puede mover el lead
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuraciones avanzadas -->
                                <div class="mt-4">
                                    <h6>Configuraciones Avanzadas:</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Tiempo máximo en este estado (días):</label>
                                            <input type="number" class="form-control" id="tiempo_maximo" min="1" placeholder="Ej: 7">
                                            <small class="text-muted">Se generará alerta después de este tiempo</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Acción automática después del tiempo máximo:</label>
                                            <select class="form-select" id="accion_automatica">
                                                <option value="">Sin acción</option>
                                                <option value="alerta">Generar alerta</option>
                                                <option value="reasignar">Reasignar automáticamente</option>
                                                <option value="cambiar_estado">Cambiar a estado específico</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Estado destino (si aplica):</label>
                                            <select class="form-select" id="estado_destino_automatico">
                                                <option value="">Seleccionar estado</option>
                                                <!-- Se llenará dinámicamente -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Pestaña Automatización -->
                    <div class="tab-pane fade" id="automatizacion-tab-pane" role="tabpanel">
                        <div class="mt-3">
                            <h6>Acciones Automáticas al Entrar al Estado:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Notificaciones</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="notificar_responsable">
                                                <label class="form-check-label" for="notificar_responsable">
                                                    Notificar al responsable
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="notificar_equipo">
                                                <label class="form-check-label" for="notificar_equipo">
                                                    Notificar al equipo de marketing
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="notificar_director">
                                                <label class="form-check-label" for="notificar_director">
                                                    Notificar al director (solo estados críticos)
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Plantilla de mensaje:</label>
                                                <select class="form-select" id="plantilla_notificacion">
                                                    <option value="">Seleccionar plantilla</option>
                                                    <!-- Se llenará dinámicamente -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Tareas y Seguimiento</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="crear_tarea_seguimiento">
                                                <label class="form-check-label" for="crear_tarea_seguimiento">
                                                    Crear tarea de seguimiento automática
                                                </label>
                                            </div>
                                            <div class="mb-3" id="config_tarea" style="display: none;">
                                                <label class="form-label">Días para la tarea:</label>
                                                <input type="number" class="form-control" id="dias_tarea" min="1" value="3">
                                                <label class="form-label mt-2">Descripción de la tarea:</label>
                                                <textarea class="form-control" id="descripcion_tarea" rows="2" placeholder="Ej: Realizar seguimiento telefónico"></textarea>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="actualizar_puntaje">
                                                <label class="form-check-label" for="actualizar_puntaje">
                                                    Actualizar puntaje de interés automáticamente
                                                </label>
                                            </div>
                                            <div class="mb-3" id="config_puntaje" style="display: none;">
                                                <label class="form-label">Nuevo puntaje:</label>
                                                <input type="number" class="form-control" id="nuevo_puntaje" min="0" max="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Webhooks y integraciones -->
                            <div class="mt-3">
                                <h6>Integraciones Externas:</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="webhook_activo">
                                                    <label class="form-check-label" for="webhook_activo">
                                                        Enviar webhook
                                                    </label>
                                                </div>
                                                <div class="mb-3" id="config_webhook" style="display: none;">
                                                    <label class="form-label">URL del webhook:</label>
                                                    <input type="url" class="form-control" id="webhook_url" placeholder="https://...">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="crm_externo">
                                                    <label class="form-check-label" for="crm_externo">
                                                        Sincronizar con CRM externo
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="analytics_tracking">
                                                    <label class="form-check-label" for="analytics_tracking">
                                                        Enviar evento a Google Analytics
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Visualización -->
                    <div class="tab-pane fade" id="visualizacion-tab-pane" role="tabpanel">
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Análisis de Conversión:</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <canvas id="chart_conversion" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Tiempo Promedio en Estado:</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <canvas id="chart_tiempo" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Métricas del estado -->
                            <div class="mt-3">
                                <h6>Métricas del Estado:</h6>
                                <div class="row" id="metricas_estado">
                                    <div class="col-md-3">
                                        <div class="card bg-primary bg-opacity-10 text-center">
                                            <div class="card-body p-3">
                                                <h4 class="text-primary mb-1" id="metric_total">0</h4>
                                                <small class="text-muted">Total Leads</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success bg-opacity-10 text-center">
                                            <div class="card-body p-3">
                                                <h4 class="text-success mb-1" id="metric_conversion">0%</h4>
                                                <small class="text-muted">Tasa Conversión</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning bg-opacity-10 text-center">
                                            <div class="card-body p-3">
                                                <h4 class="text-warning mb-1" id="metric_tiempo">0d</h4>
                                                <small class="text-muted">Tiempo Promedio</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info bg-opacity-10 text-center">
                                            <div class="card-body p-3">
                                                <h4 class="text-info mb-1" id="metric_activos">0</h4>
                                                <small class="text-muted">Leads Activos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-info" id="btn_probar_flujo">
                    <i class="ti ti-test-pipe me-1"></i>Probar Configuración
                </button>
                <button type="button" class="btn btn-outline-warning" id="btn_resetear_flujo">
                    <i class="ti ti-refresh me-1"></i>Resetear
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn_guardar_flujo">
                    <i class="ti ti-check me-1"></i>Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.estado-flujo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

.estado-flujo-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.flujo-arrow {
    font-size: 1.2rem;
    color: #6c757d;
    margin: 0 10px;
}

.flujo-visual-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 20px;
    min-height: 200px;
}

.flujo-node {
    position: relative;
    padding: 10px 15px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    text-align: center;
    min-width: 120px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flujo-node.current {
    border: 3px solid #ffc107;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}

.flujo-connection {
    position: absolute;
    top: 50%;
    right: -15px;
    transform: translateY(-50%);
    font-size: 1.5rem;
    color: #28a745;
}
</style>

<script>
$(document).ready(function() {
    // Variables globales
    let estadosDisponibles = [];
    let configuracionActual = {};

    // Manejar cambios en checkboxes para mostrar/ocultar configuraciones
    $('#crear_tarea_seguimiento').on('change', function() {
        $('#config_tarea').toggle($(this).is(':checked'));
    });

    $('#actualizar_puntaje').on('change', function() {
        $('#config_puntaje').toggle($(this).is(':checked'));
    });

    $('#webhook_activo').on('change', function() {
        $('#config_webhook').toggle($(this).is(':checked'));
    });

    // Agregar estado destino
    $('#btn_agregar_destino').on('click', function() {
        agregarEstadoDestino();
    });

    // Agregar estado origen
    $('#btn_agregar_origen').on('click', function() {
        agregarEstadoOrigen();
    });

    // Guardar configuración
    $('#btn_guardar_flujo').on('click', function() {
        guardarConfiguracionFlujo();
    });

    // Probar configuración
    $('#btn_probar_flujo').on('click', function() {
        probarConfiguracionFlujo();
    });

    // Resetear configuración
    $('#btn_resetear_flujo').on('click', function() {
        if (confirm('¿Está seguro de que desea resetear toda la configuración? Se perderán todos los cambios no guardados.')) {
            resetearConfiguracion();
        }
    });

    function agregarEstadoDestino() {
        if (estadosDisponibles.length === 0) {
            alert('No hay estados disponibles');
            return;
        }

        var html = '<div class="estado-flujo-item">';
        html += '<select class="form-select form-select-sm estado-destino-select">';
        html += '<option value="">Seleccionar estado</option>';
        estadosDisponibles.forEach(function(estado) {
            html += `<option value="${estado.id}" data-color="${estado.color}">${estado.nombre}</option>`;
        });
        html += '</select>';
        html += '<button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-estado"><i class="ti ti-x"></i></button>';
        html += '</div>';

        $('#estados_destino').append(html);
    }

    function agregarEstadoOrigen() {
        if (estadosDisponibles.length === 0) {
            alert('No hay estados disponibles');
            return;
        }

        var html = '<div class="estado-flujo-item">';
        html += '<select class="form-select form-select-sm estado-origen-select">';
        html += '<option value="">Seleccionar estado</option>';
        estadosDisponibles.forEach(function(estado) {
            html += `<option value="${estado.id}" data-color="${estado.color}">${estado.nombre}</option>`;
        });
        html += '</select>';
        html += '<button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-estado"><i class="ti ti-x"></i></button>';
        html += '</div>';

        $('#estados_origen').append(html);
    }

    // Eliminar estado de la configuración
    $(document).on('click', '.btn-eliminar-estado', function() {
        $(this).closest('.estado-flujo-item').remove();
        actualizarVisualizacionFlujo();
    });

    // Actualizar visualización cuando cambian los selects
    $(document).on('change', '.estado-destino-select, .estado-origen-select', function() {
        actualizarVisualizacionFlujo();
    });

    function actualizarVisualizacionFlujo() {
        var estadoActualId = $('#flujo_estado_id').val();
        var estadoActual = estadosDisponibles.find(e => e.id == estadoActualId);
        
        if (!estadoActual) return;

        var destinos = [];
        $('.estado-destino-select').each(function() {
            var id = $(this).val();
            if (id) {
                var estado = estadosDisponibles.find(e => e.id == id);
                if (estado) destinos.push(estado);
            }
        });

        var origenes = [];
        $('.estado-origen-select').each(function() {
            var id = $(this).val();
            if (id) {
                var estado = estadosDisponibles.find(e => e.id == id);
                if (estado) origenes.push(estado);
            }
        });

        // Generar visualización
        var html = '<div class="flujo-visual-container">';
        
        // Estados origen
        if (origenes.length > 0) {
            html += '<div class="d-flex flex-column align-items-center">';
            origenes.forEach(function(estado) {
                html += `<div class="flujo-node mb-2" style="background-color: ${estado.color}">${estado.nombre}</div>`;
            });
            html += '</div>';
            html += '<div class="flujo-arrow"><i class="ti ti-arrow-right"></i></div>';
        }

        // Estado actual
        html += `<div class="flujo-node current" style="background-color: ${estadoActual.color}">${estadoActual.nombre}<br><small>(Estado Actual)</small></div>`;

        // Estados destino
        if (destinos.length > 0) {
            html += '<div class="flujo-arrow"><i class="ti ti-arrow-right"></i></div>';
            html += '<div class="d-flex flex-column align-items-center">';
            destinos.forEach(function(estado) {
                html += `<div class="flujo-node mb-2" style="background-color: ${estado.color}">${estado.nombre}</div>`;
            });
            html += '</div>';
        }

        html += '</div>';
        $('#flujo_visual').html(html);
    }

    function guardarConfiguracionFlujo() {
        var estadoId = $('#flujo_estado_id').val();
        
        if (!estadoId) {
            alert('Error: No se ha seleccionado un estado');
            return;
        }

        // Recopilar configuración
        var configuracion = {
            estado_id: estadoId,
            estados_destino: [],
            estados_origen: [],
            reglas: {
                requerir_observaciones: $('#requerir_observaciones').is(':checked'),
                requerir_responsable: $('#requerir_responsable').is(':checked'),
                validar_datos_completos: $('#validar_datos_completos').is(':checked'),
                puntaje_minimo: $('#puntaje_minimo').val(),
                roles_permitidos: $('input[name="roles[]"]:checked').map(function() {
                    return this.value;
                }).get(),
                solo_responsable_asignado: $('#solo_responsable_asignado').is(':checked'),
                tiempo_maximo: $('#tiempo_maximo').val(),
                accion_automatica: $('#accion_automatica').val(),
                estado_destino_automatico: $('#estado_destino_automatico').val()
            },
            automatizacion: {
                notificar_responsable: $('#notificar_responsable').is(':checked'),
                notificar_equipo: $('#notificar_equipo').is(':checked'),
                notificar_director: $('#notificar_director').is(':checked'),
                plantilla_notificacion: $('#plantilla_notificacion').val(),
                crear_tarea_seguimiento: $('#crear_tarea_seguimiento').is(':checked'),
                dias_tarea: $('#dias_tarea').val(),
                descripcion_tarea: $('#descripcion_tarea').val(),
                actualizar_puntaje: $('#actualizar_puntaje').is(':checked'),
                nuevo_puntaje: $('#nuevo_puntaje').val(),
                webhook_activo: $('#webhook_activo').is(':checked'),
                webhook_url: $('#webhook_url').val(),
                crm_externo: $('#crm_externo').is(':checked'),
                analytics_tracking: $('#analytics_tracking').is(':checked')
            }
        };

        // Obtener estados destino
        $('.estado-destino-select').each(function() {
            var id = $(this).val();
            if (id) configuracion.estados_destino.push(id);
        });

        // Obtener estados origen
        $('.estado-origen-select').each(function() {
            var id = $(this).val();
            if (id) configuracion.estados_origen.push(id);
        });

        $.ajax({
            url: 'acciones/clasificacion_leads/guardar_configuracion_flujo.php',
            method: 'POST',
            data: {
                accion: 'guardar',
                configuracion: JSON.stringify(configuracion)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Configuración guardada exitosamente');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al guardar la configuración');
            }
        });
    }

    function probarConfiguracionFlujo() {
        alert('Función de prueba en desarrollo. Esta característica permitirá simular el flujo con datos de prueba.');
    }

    function resetearConfiguracion() {
        // Limpiar todas las configuraciones
        $('#estados_destino').empty();
        $('#estados_origen').empty();
        $('#formReglasEstado')[0].reset();
        $('input[type="checkbox"]').prop('checked', false);
        $('#config_tarea, #config_puntaje, #config_webhook').hide();
        $('#flujo_visual').html('<div class="text-center text-muted"><p>Configure las transiciones para ver la visualización</p></div>');
    }

    function cargarConfiguracionFlujo(estadoId) {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_configuracion_flujo.php',
            method: 'POST',
            data: { estado_id: estadoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    estadosDisponibles = response.estados_disponibles;
                    configuracionActual = response.configuracion;
                    
                    // Cargar configuración en el formulario
                    cargarConfiguracionEnFormulario(response.configuracion);
                    cargarMetricas(response.metricas);
                }
            },
            error: function() {
                console.error('Error al cargar la configuración del flujo');
            }
        });
    }

    function cargarConfiguracionEnFormulario(config) {
        if (!config) return;

        // Cargar estados destino
        if (config.estados_destino) {
            config.estados_destino.forEach(function(estadoId) {
                agregarEstadoDestino();
                $('#estados_destino .estado-destino-select:last').val(estadoId);
            });
        }

        // Cargar estados origen
        if (config.estados_origen) {
            config.estados_origen.forEach(function(estadoId) {
                agregarEstadoOrigen();
                $('#estados_origen .estado-origen-select:last').val(estadoId);
            });
        }

        // Cargar reglas
        if (config.reglas) {
            $('#requerir_observaciones').prop('checked', config.reglas.requerir_observaciones);
            $('#requerir_responsable').prop('checked', config.reglas.requerir_responsable);
            $('#validar_datos_completos').prop('checked', config.reglas.validar_datos_completos);
            $('#puntaje_minimo').val(config.reglas.puntaje_minimo);
            $('#solo_responsable_asignado').prop('checked', config.reglas.solo_responsable_asignado);
            $('#tiempo_maximo').val(config.reglas.tiempo_maximo);
            $('#accion_automatica').val(config.reglas.accion_automatica);
            $('#estado_destino_automatico').val(config.reglas.estado_destino_automatico);
            
            if (config.reglas.roles_permitidos) {
                config.reglas.roles_permitidos.forEach(function(rolId) {
                    $(`input[name="roles[]"][value="${rolId}"]`).prop('checked', true);
                });
            }
        }

        // Cargar automatización
        if (config.automatizacion) {
            $('#notificar_responsable').prop('checked', config.automatizacion.notificar_responsable);
            $('#notificar_equipo').prop('checked', config.automatizacion.notificar_equipo);
            $('#notificar_director').prop('checked', config.automatizacion.notificar_director);
            $('#plantilla_notificacion').val(config.automatizacion.plantilla_notificacion);
            $('#crear_tarea_seguimiento').prop('checked', config.automatizacion.crear_tarea_seguimiento);
            $('#dias_tarea').val(config.automatizacion.dias_tarea);
            $('#descripcion_tarea').val(config.automatizacion.descripcion_tarea);
            $('#actualizar_puntaje').prop('checked', config.automatizacion.actualizar_puntaje);
            $('#nuevo_puntaje').val(config.automatizacion.nuevo_puntaje);
            $('#webhook_activo').prop('checked', config.automatizacion.webhook_activo);
            $('#webhook_url').val(config.automatizacion.webhook_url);
            $('#crm_externo').prop('checked', config.automatizacion.crm_externo);
            $('#analytics_tracking').prop('checked', config.automatizacion.analytics_tracking);

            // Mostrar/ocultar secciones según configuración
            $('#config_tarea').toggle(config.automatizacion.crear_tarea_seguimiento);
            $('#config_puntaje').toggle(config.automatizacion.actualizar_puntaje);
            $('#config_webhook').toggle(config.automatizacion.webhook_activo);
        }

        actualizarVisualizacionFlujo();
    }

    function cargarMetricas(metricas) {
        if (!metricas) return;

        $('#metric_total').text(metricas.total_leads || 0);
        $('#metric_conversion').text((metricas.tasa_conversion || 0) + '%');
        $('#metric_tiempo').text((metricas.tiempo_promedio || 0) + 'd');
        $('#metric_activos').text(metricas.leads_activos || 0);
    }

    // Evento cuando se abre el modal
    $('#modalConfigurarFlujo').on('show.bs.modal', function() {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_estado.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                estadosDisponibles = data; // aquí ya tendrás el array de estados
            },
            error: function() {
                alert('Error al cargar estados disponibles');
            }
        });
    });
});
</script>