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
                                            <div id="estados_destino"></div>
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
                                            <div id="estados_origen"></div>
                                            <button type="button" class="btn btn-outline-success btn-sm mt-2" id="btn_agregar_origen">
                                                <i class="ti ti-plus me-1"></i>Agregar Estado Origen
                                            </button>
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

                            <!-- Integraciones Externas (OPCIONALES - FUTURAS) -->
                            <div class="mt-3">
                                <h6>Integraciones Externas <span class="badge bg-secondary">Integraciones Futuras</span></h6>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="webhook_activo" disabled>
                                                    <label class="form-check-label text-muted" for="webhook_activo">
                                                        Enviar webhook (Próximamente)
                                                    </label>
                                                </div>
                                                <div class="mb-3" id="config_webhook" style="display: none;">
                                                    <label class="form-label text-muted">URL del webhook:</label>
                                                    <input type="url" class="form-control" id="webhook_url" placeholder="https://..." disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="crm_externo" disabled>
                                                    <label class="form-check-label text-muted" for="crm_externo">
                                                        Sincronizar con CRM externo (Próximamente)
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="analytics_tracking" disabled>
                                                    <label class="form-check-label text-muted" for="analytics_tracking">
                                                        Enviar evento a Google Analytics (Próximamente)
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
                            <!-- Métricas del estado -->
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
            <div class="modal-footer">
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

    .flujo-visual-container {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 20px;
        min-height: 150px;
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

    .flujo-arrow {
        font-size: 1.5rem;
        color: #28a745;
    }

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


<script>
$(document).ready(function() {
    let estadosDisponibles = [];
    let configuracionActual = {};

    // Cargar estados disponibles al abrir modal
    $('#modalConfigurarFlujo').on('show.bs.modal', function() {
        cargarEstadosDisponibles();
        cargarPlantillasMensajes();
    });

    function cargarEstadosDisponibles() {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_estados_disponibles.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (Array.isArray(data)) {
                    estadosDisponibles = data;
                    
                    // Llenar select de estado destino automático
                    let options = '<option value="">Seleccionar estado</option>';
                    data.forEach(function(estado) {
                        options += `<option value="${estado.id}">${estado.nombre}</option>`;
                    });
                    $('#estado_destino_automatico').html(options);
                } else {
                    estadosDisponibles = [];
                    console.error('Los datos recibidos no son un array');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los estados disponibles'
                });
                estadosDisponibles = [];
            }
        });
    }

    function cargarPlantillasMensajes() {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_plantillas_mensajes.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (Array.isArray(data)) {
                    let options = '<option value="">Seleccionar plantilla</option>';
                    data.forEach(function(plantilla) {
                        options += `<option value="${plantilla.id}">${plantilla.nombre} (${plantilla.tipo})</option>`;
                    });
                    $('#plantilla_notificacion').html(options);
                }
            },
            error: function() {
                $('#plantilla_notificacion').html('<option value="">No hay plantillas disponibles</option>');
            }
        });
    }

    // Manejar cambios en checkboxes
    $('#crear_tarea_seguimiento').on('change', function() {
        $('#config_tarea').toggle($(this).is(':checked'));
    });

    $('#actualizar_puntaje').on('change', function() {
        $('#config_puntaje').toggle($(this).is(':checked'));
    });

    // Botones agregar
    $('#btn_agregar_destino').on('click', function() {
        agregarEstadoDestino();
    });

    $('#btn_agregar_origen').on('click', function() {
        agregarEstadoOrigen();
    });

    $('#btn_guardar_flujo').on('click', function() {
        guardarConfiguracionFlujo();
    });

    $('#btn_resetear_flujo').on('click', function() {
        Swal.fire({
            title: '¿Resetear configuración?',
            text: "Se perderán todos los cambios no guardados",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, resetear',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                resetearConfiguracion();
            }
        });
    });

    function agregarEstadoDestino() {
        if (!Array.isArray(estadosDisponibles) || estadosDisponibles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'No hay estados disponibles. Intente cerrar y abrir el modal nuevamente.'
            });
            return;
        }

        let html = '<div class="estado-flujo-item">';
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
        if (!Array.isArray(estadosDisponibles) || estadosDisponibles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'No hay estados disponibles. Intente cerrar y abrir el modal nuevamente.'
            });
            return;
        }

        let html = '<div class="estado-flujo-item">';
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

    $(document).on('click', '.btn-eliminar-estado', function() {
        $(this).closest('.estado-flujo-item').remove();
        actualizarVisualizacionFlujo();
    });

    $(document).on('change', '.estado-destino-select, .estado-origen-select', function() {
        actualizarVisualizacionFlujo();
    });

    function actualizarVisualizacionFlujo() {
        let estadoActualId = $('#flujo_estado_id').val();
        
        if (!estadoActualId || !Array.isArray(estadosDisponibles)) {
            return;
        }

        let estadoActual = estadosDisponibles.find(e => e.id == estadoActualId);
        
        if (!estadoActual) return;

        let destinos = [];
        $('.estado-destino-select').each(function() {
            let id = $(this).val();
            if (id) {
                let estado = estadosDisponibles.find(e => e.id == id);
                if (estado) destinos.push(estado);
            }
        });

        let origenes = [];
        $('.estado-origen-select').each(function() {
            let id = $(this).val();
            if (id) {
                let estado = estadosDisponibles.find(e => e.id == id);
                if (estado) origenes.push(estado);
            }
        });

        let html = '<div class="flujo-visual-container">';
        
        if (origenes.length > 0) {
            html += '<div class="d-flex flex-column align-items-center">';
            origenes.forEach(function(estado) {
                html += `<div class="flujo-node mb-2" style="background-color: ${estado.color}">${estado.nombre}</div>`;
            });
            html += '</div>';
            html += '<div class="flujo-arrow"><i class="ti ti-arrow-right"></i></div>';
        }

        html += `<div class="flujo-node current" style="background-color: ${estadoActual.color}">${estadoActual.nombre}<br><small>(Estado Actual)</small></div>`;

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
        let estadoId = $('#flujo_estado_id').val();
        
        if (!estadoId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se ha seleccionado un estado'
            });
            return;
        }

        let configuracion = {
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
                nuevo_puntaje: $('#nuevo_puntaje').val()
            }
        };

        $('.estado-destino-select').each(function() {
            let id = $(this).val();
            if (id) configuracion.estados_destino.push(id);
        });

        $('.estado-origen-select').each(function() {
            let id = $(this).val();
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
            beforeSend: function() {
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Configuración guardada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#modalConfigurarFlujo').modal('hide');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo guardar la configuración'
                });
                console.error(xhr.responseText);
            }
        });
    }

    function resetearConfiguracion() {
        $('#estados_destino').empty();
        $('#estados_origen').empty();
        $('#formReglasEstado')[0].reset();
        $('input[type="checkbox"]').prop('checked', false);
        $('#config_tarea, #config_puntaje').hide();
        $('#flujo_visual').html('<div class="text-center text-muted"><p>Configure las transiciones para ver la visualización</p></div>');
        
        Swal.fire({
            icon: 'success',
            title: 'Configuración reseteada',
            timer: 1500,
            showConfirmButton: false
        });
    }

    function cargarConfiguracionFlujo(estadoId) {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_configuracion_flujo.php',
            method: 'POST',
            data: { estado_id: estadoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    estadosDisponibles = response.estados_disponibles || [];
                    configuracionActual = response.configuracion;
                    
                    cargarConfiguracionEnFormulario(response.configuracion);
                    cargarMetricas(response.metricas);
                    
                    // Llenar select de estado destino automático
                    let options = '<option value="">Seleccionar estado</option>';
                    estadosDisponibles.forEach(function(estado) {
                        options += `<option value="${estado.id}">${estado.nombre}</option>`;
                    });
                    $('#estado_destino_automatico').html(options);
                }
            },
            error: function() {
                console.error('Error al cargar la configuración del flujo');
            }
        });
    }

    function cargarConfiguracionEnFormulario(config) {
        if (!config) return;

        if (config.estados_destino) {
            config.estados_destino.forEach(function(estadoId) {
                agregarEstadoDestino();
                $('#estados_destino .estado-destino-select:last').val(estadoId);
            });
        }

        if (config.estados_origen) {
            config.estados_origen.forEach(function(estadoId) {
                agregarEstadoOrigen();
                $('#estados_origen .estado-origen-select:last').val(estadoId);
            });
        }

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

            $('#config_tarea').toggle(config.automatizacion.crear_tarea_seguimiento);
            $('#config_puntaje').toggle(config.automatizacion.actualizar_puntaje);
        }

        actualizarVisualizacionFlujo();
    }

    function cargarMetricas(metricas) {
        if (!metricas) return;

        $('#metric_total').text(metricas.total_leads || 0);
        $('#metric_conversion').text((metricas.tasa_conversion || 0) + '%');
        $('#metric_tiempo').text(Math.round(metricas.tiempo_promedio || 0) + 'd');
        $('#metric_activos').text(metricas.leads_activos || 0);
    }

    window.abrirModalFlujo = function(estadoId, estadoNombre) {
        $('#flujo_estado_id').val(estadoId);
        $('#flujo_estado_nombre').text(estadoNombre);
        cargarConfiguracionFlujo(estadoId);
        $('#modalConfigurarFlujo').modal('show');
    };
});
</script>