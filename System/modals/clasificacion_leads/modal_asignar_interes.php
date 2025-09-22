<!-- Modal para asignar nivel de interés automático por estado -->
<div class="modal fade" id="modalAsignarInteres" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-star me-2"></i>
                    Configurar Nivel de Interés: <span id="interes_estado_nombre" class="text-primary">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAsignarInteres" method="POST" action="acciones/clasificacion_leads/procesar_interes_estado.php">
                <input type="hidden" name="accion" value="configurar">
                <input type="hidden" name="estado_id" id="interes_estado_id">
                <div class="modal-body">
                    
                    <!-- Información del estado -->
                    <div class="card bg-light mb-3">
                        <div class="card-body p-3">
                            <h6 class="card-title text-primary mb-2">
                                <i class="ti ti-info-circle me-1"></i>
                                Información del Estado
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Leads actuales:</strong> <span id="interes_total_leads">0</span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Promedio actual:</strong> <span id="interes_promedio_actual">0</span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Rango:</strong> <span id="interes_rango_actual">0 - 0</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de interés automático -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Configuración Automática:</h6>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="asignacion_automatica" id="asignacion_automatica" value="1">
                                    <label class="form-check-label" for="asignacion_automatica">
                                        <strong>Asignar interés automáticamente</strong>
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Cuando un lead llegue a este estado, se asignará automáticamente el nivel de interés configurado
                                </small>
                            </div>

                            <div id="config_automatica" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de asignación:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_asignacion" id="tipo_valor_fijo" value="valor_fijo" checked>
                                        <label class="form-check-label" for="tipo_valor_fijo">
                                            Valor fijo
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_asignacion" id="tipo_incremento" value="incremento">
                                        <label class="form-check-label" for="tipo_incremento">
                                            Incremento/Decremento
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_asignacion" id="tipo_formula" value="formula">
                                        <label class="form-check-label" for="tipo_formula">
                                            Fórmula personalizada
                                        </label>
                                    </div>
                                </div>

                                <!-- Configuración valor fijo -->
                                <div id="config_valor_fijo" class="mb-3">
                                    <label class="form-label">Valor de interés (0-100):</label>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="range" class="form-range" name="valor_fijo" id="valor_fijo_range" 
                                                   min="0" max="100" value="50" oninput="$('#valor_fijo_number').val(this.value)">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" class="form-control" id="valor_fijo_number" 
                                                   min="0" max="100" value="50" oninput="$('#valor_fijo_range').val(this.value)">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span id="valor_fijo_preview" class="interes-preview">
                                            <!-- Se llenará con estrellas -->
                                        </span>
                                    </div>
                                </div>

                                <!-- Configuración incremento -->
                                <div id="config_incremento" class="mb-3" style="display: none;">
                                    <label class="form-label">Modificación al valor actual:</label>
                                    <div class="input-group">
                                        <select class="form-select" name="operacion_incremento" style="max-width: 100px;">
                                            <option value="sum">+</option>
                                            <option value="subtract">-</option>
                                        </select>
                                        <input type="number" class="form-control" name="valor_incremento" 
                                               min="1" max="50" value="10" placeholder="Puntos">
                                        <span class="input-group-text">puntos</span>
                                    </div>
                                    <small class="text-muted">
                                        Se sumará o restará este valor al puntaje actual del lead
                                    </small>
                                </div>

                                <!-- Configuración fórmula -->
                                <div id="config_formula" class="mb-3" style="display: none;">
                                    <label class="form-label">Fórmula personalizada:</label>
                                    <textarea class="form-control" name="formula_personalizada" rows="3" 
                                              placeholder="Ej: if (dias_en_pipeline > 7) return min(valor_actual + 20, 100); else return valor_actual + 10;"></textarea>
                                    <small class="text-muted">
                                        Variables disponibles: valor_actual, dias_en_pipeline, interacciones_realizadas, canal_origen
                                    </small>
                                </div>
                            </div>

                            <!-- Condiciones adicionales -->
                            <div class="mt-3">
                                <h6>Condiciones Adicionales:</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="solo_primera_vez" id="solo_primera_vez" value="1">
                                    <label class="form-check-label" for="solo_primera_vez">
                                        Solo aplicar la primera vez que entra al estado
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="respetar_manual" id="respetar_manual" value="1" checked>
                                    <label class="form-check-label" for="respetar_manual">
                                        Respetar asignaciones manuales previas
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="notificar_cambio" id="notificar_cambio" value="1">
                                    <label class="form-check-label" for="notificar_cambio">
                                        Notificar al responsable sobre el cambio
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>Análisis y Recomendaciones:</h6>
                            
                            <!-- Distribución actual -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Distribución Actual de Interés</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart_distribucion_interes" width="300" height="200"></canvas>
                                </div>
                            </div>

                            <!-- Recomendaciones basadas en datos -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Recomendaciones Inteligentes</h6>
                                </div>
                                <div class="card-body">
                                    <div id="recomendaciones_interes">
                                        <div class="text-center text-muted">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Analizando...</span>
                                            </div>
                                            <p class="mt-2 small">Analizando patrones...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Simulación de resultados -->
                    <div class="mt-4">
                        <h6>Simulación de Resultados:</h6>
                        <div class="card bg-warning bg-opacity-10">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-warning mb-1" id="sim_leads_afectados">0</h4>
                                            <small class="text-muted">Leads Afectados</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-info mb-1" id="sim_promedio_nuevo">0</h4>
                                            <small class="text-muted">Nuevo Promedio</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-success mb-1" id="sim_incremento">+0</h4>
                                            <small class="text-muted">Incremento</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-primary mb-1" id="sim_conversion_esperada">0%</h4>
                                            <small class="text-muted">Conversión Esperada</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm" id="btn_simular">
                                        <i class="ti ti-calculator me-1"></i>Recalcular Simulación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de configuraciones -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Historial de Configuraciones</h6>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" 
                                    data-bs-target="#historialConfiguraciones" aria-expanded="false">
                                <i class="ti ti-history"></i>
                            </button>
                        </div>
                        
                        <div class="collapse mt-2" id="historialConfiguraciones">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div id="historial_configuraciones_lista">
                                        <!-- Se llenará dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-info" id="btn_aplicar_masivo">
                        <i class="ti ti-wand me-1"></i>Aplicar a Leads Existentes
                    </button>
                    <button type="button" class="btn btn-outline-warning" id="btn_vista_previa">
                        <i class="ti ti-eye me-1"></i>Vista Previa
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.interes-preview {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 1.2rem;
}

.interes-preview .star-active {
    color: #ffc107;
}

.interes-preview .star-inactive {
    color: #e9ecef;
}

.config-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 6px;
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.config-item .config-fecha {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
}

.config-item .config-descripcion {
    margin: 4px 0;
    font-size: 0.9rem;
}

.config-item .config-usuario {
    font-size: 0.75rem;
    color: #6c757d;
    font-style: italic;
}

.recomendacion-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.recomendacion-item:last-child {
    border-bottom: none;
}

.recomendacion-icono {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.recomendacion-icono.alta {
    background-color: #28a745;
    color: white;
}

.recomendacion-icono.media {
    background-color: #ffc107;
    color: white;
}

.recomendacion-icono.baja {
    background-color: #dc3545;
    color: white;
}

.recomendacion-contenido {
    flex: 1;
}

.recomendacion-titulo {
    font-weight: 600;
    font-size: 0.85rem;
    color: #495057;
}

.recomendacion-detalle {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 2px;
}
</style>

<script>
$(document).ready(function() {
    // Variables globales
    let datosEstado = {};
    let configuracionActual = {};

    // Manejar cambios en tipo de asignación
    $('input[name="tipo_asignacion"]').on('change', function() {
        var tipo = $(this).val();
        
        $('#config_valor_fijo, #config_incremento, #config_formula').hide();
        $('#config_' + tipo).show();
        
        actualizarSimulacion();
    });

    // Manejar cambios en asignación automática
    $('#asignacion_automatica').on('change', function() {
        $('#config_automatica').toggle($(this).is(':checked'));
        if ($(this).is(':checked')) {
            actualizarSimulacion();
        }
    });

    // Actualizar vista previa de estrellas cuando cambia el valor
    $('#valor_fijo_range, #valor_fijo_number').on('input', function() {
        var valor = $(this).val();
        actualizarVistaPrevia(valor);
        actualizarSimulacion();
    });

    // Simular resultados
    $('#btn_simular').on('click', function() {
        actualizarSimulacion();
    });

    // Aplicar a leads existentes
    $('#btn_aplicar_masivo').on('click', function() {
        aplicarALeadsExistentes();
    });

    // Vista previa
    $('#btn_vista_previa').on('click', function() {
        mostrarVistaPrevia();
    });

    // Envío del formulario
    $('#formAsignarInteres').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Configuración de interés guardada exitosamente');
                    $('#modalAsignarInteres').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al guardar la configuración');
            }
        });
    });

    function actualizarVistaPrevia(valor) {
        var estrellas = '';
        var valorNum = parseInt(valor);
        var estrellasCompletas = Math.floor(valorNum / 20);
        
        for (var i = 1; i <= 5; i++) {
            if (i <= estrellasCompletas) {
                estrellas += '<i class="ti ti-star-filled star-active"></i>';
            } else {
                estrellas += '<i class="ti ti-star star-inactive"></i>';
            }
        }
        
        estrellas += ` <span class="ms-2 fw-bold">${valorNum}/100</span>`;
        $('#valor_fijo_preview').html(estrellas);
    }

    function actualizarSimulacion() {
        if (!$('#asignacion_automatica').is(':checked')) {
            $('#sim_leads_afectados, #sim_promedio_nuevo, #sim_incremento, #sim_conversion_esperada').text('0');
            return;
        }

        var estadoId = $('#interes_estado_id').val();
        var tipoAsignacion = $('input[name="tipo_asignacion"]:checked').val();
        var configuracion = {};

        // Recopilar configuración según tipo
        switch(tipoAsignacion) {
            case 'valor_fijo':
                configuracion.valor = $('#valor_fijo_number').val();
                break;
            case 'incremento':
                configuracion.operacion = $('select[name="operacion_incremento"]').val();
                configuracion.valor = $('input[name="valor_incremento"]').val();
                break;
            case 'formula':
                configuracion.formula = $('textarea[name="formula_personalizada"]').val();
                break;
        }

        $.ajax({
            url: 'acciones/clasificacion_leads/simular_interes_estado.php',
            method: 'POST',
            data: {
                estado_id: estadoId,
                tipo_asignacion: tipoAsignacion,
                configuracion: JSON.stringify(configuracion),
                solo_primera_vez: $('#solo_primera_vez').is(':checked'),
                respetar_manual: $('#respetar_manual').is(':checked')
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#sim_leads_afectados').text(response.simulacion.leads_afectados);
                    $('#sim_promedio_nuevo').text(response.simulacion.promedio_nuevo);
                    $('#sim_incremento').text(response.simulacion.incremento >= 0 ? '+' + response.simulacion.incremento : response.simulacion.incremento);
                    $('#sim_conversion_esperada').text(response.simulacion.conversion_esperada + '%');
                }
            },
            error: function() {
                console.error('Error al simular resultados');
            }
        });
    }

    function aplicarALeadsExistentes() {
        if (!$('#asignacion_automatica').is(':checked')) {
            alert('Debe activar la asignación automática primero');
            return;
        }

        var leadsAfectados = $('#sim_leads_afectados').text();
        
        if (confirm(`¿Está seguro de que desea aplicar esta configuración a ${leadsAfectados} leads existentes? Esta acción no se puede deshacer.`)) {
            var estadoId = $('#interes_estado_id').val();
            
            $.ajax({
                url: 'acciones/clasificacion_leads/aplicar_interes_masivo.php',
                method: 'POST',
                data: $('#formAsignarInteres').serialize() + '&aplicar_existentes=1',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(`Configuración aplicada exitosamente a ${response.leads_actualizados} leads`);
                        cargarDatosEstado(estadoId);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al aplicar la configuración');
                }
            });
        }
    }

    function mostrarVistaPrevia() {
        var configuracion = recopilarConfiguracion();
        
        var html = '<div class="modal fade" id="modalVistaPrevia" tabindex="-1">';
        html += '<div class="modal-dialog">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header">';
        html += '<h5 class="modal-title">Vista Previa de Configuración</h5>';
        html += '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
        html += '</div>';
        html += '<div class="modal-body">';
        html += '<pre>' + JSON.stringify(configuracion, null, 2) + '</pre>';
        html += '</div>';
        html += '<div class="modal-footer">';
        html += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        $('#modalVistaPrevia').modal('show');
        
        $('#modalVistaPrevia').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    function recopilarConfiguracion() {
        return {
            asignacion_automatica: $('#asignacion_automatica').is(':checked'),
            tipo_asignacion: $('input[name="tipo_asignacion"]:checked').val(),
            valor_fijo: $('#valor_fijo_number').val(),
            operacion_incremento: $('select[name="operacion_incremento"]').val(),
            valor_incremento: $('input[name="valor_incremento"]').val(),
            formula_personalizada: $('textarea[name="formula_personalizada"]').val(),
            solo_primera_vez: $('#solo_primera_vez').is(':checked'),
            respetar_manual: $('#respetar_manual').is(':checked'),
            notificar_cambio: $('#notificar_cambio').is(':checked')
        };
    }

    function cargarDatosEstado(estadoId) {
        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_datos_interes_estado.php',
            method: 'POST',
            data: { estado_id: estadoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    datosEstado = response.datos;
                    configuracionActual = response.configuracion;
                    
                    // Actualizar información del estado
                    $('#interes_total_leads').text(datosEstado.total_leads || 0);
                    $('#interes_promedio_actual').text(datosEstado.promedio_interes || 0);
                    $('#interes_rango_actual').text(`${datosEstado.minimo || 0} - ${datosEstado.maximo || 0}`);
                    
                    // Cargar configuración existente
                    if (configuracionActual) {
                        cargarConfiguracion(configuracionActual);
                    }
                    
                    // Cargar datos en gráficos
                    cargarGraficoDistribucion(response.distribucion);
                    cargarRecomendaciones(response.recomendaciones);
                    cargarHistorialConfiguraciones(response.historial);
                }
            },
            error: function() {
                console.error('Error al cargar datos del estado');
            }
        });
    }

    function cargarConfiguracion(config) {
        $('#asignacion_automatica').prop('checked', config.asignacion_automatica || false);
        $('#config_automatica').toggle(config.asignacion_automatica || false);
        
        if (config.tipo_asignacion) {
            $(`input[name="tipo_asignacion"][value="${config.tipo_asignacion}"]`).prop('checked', true);
            $('#config_valor_fijo, #config_incremento, #config_formula').hide();
            $('#config_' + config.tipo_asignacion).show();
        }
        
        if (config.valor_fijo) {
            $('#valor_fijo_range, #valor_fijo_number').val(config.valor_fijo);
            actualizarVistaPrevia(config.valor_fijo);
        }
        
        if (config.operacion_incremento) {
            $('select[name="operacion_incremento"]').val(config.operacion_incremento);
        }
        
        if (config.valor_incremento) {
            $('input[name="valor_incremento"]').val(config.valor_incremento);
        }
        
        if (config.formula_personalizada) {
            $('textarea[name="formula_personalizada"]').val(config.formula_personalizada);
        }
        
        $('#solo_primera_vez').prop('checked', config.solo_primera_vez || false);
        $('#respetar_manual').prop('checked', config.respetar_manual !== false); // por defecto true
        $('#notificar_cambio').prop('checked', config.notificar_cambio || false);
        
        actualizarSimulacion();
    }

    function cargarGraficoDistribucion(distribucion) {
        // Implementar gráfico con Chart.js u otra librería
        console.log('Cargar gráfico de distribución:', distribucion);
    }

    function cargarRecomendaciones(recomendaciones) {
        if (!recomendaciones || recomendaciones.length === 0) {
            $('#recomendaciones_interes').html('<p class="text-muted small">No hay recomendaciones disponibles</p>');
            return;
        }

        var html = '';
        recomendaciones.forEach(function(rec) {
            html += `
                <div class="recomendacion-item">
                    <div class="recomendacion-icono ${rec.prioridad}">
                        <i class="ti ti-${rec.icono || 'lightbulb'}"></i>
                    </div>
                    <div class="recomendacion-contenido">
                        <div class="recomendacion-titulo">${rec.titulo}</div>
                        <div class="recomendacion-detalle">${rec.detalle}</div>
                    </div>
                </div>
            `;
        });

        $('#recomendaciones_interes').html(html);
    }

    function cargarHistorialConfiguraciones(historial) {
        if (!historial || historial.length === 0) {
            $('#historial_configuraciones_lista').html('<p class="text-muted small">No hay configuraciones anteriores</p>');
            return;
        }

        var html = '';
        historial.forEach(function(config) {
            html += `
                <div class="config-item">
                    <div class="config-fecha">${config.fecha}</div>
                    <div class="config-descripcion">${config.descripcion}</div>
                    <div class="config-usuario">Por: ${config.usuario}</div>
                </div>
            `;
        });

        $('#historial_configuraciones_lista').html(html);
    }

    // Inicializar vista previa con valor por defecto
    actualizarVistaPrevia(50);

    // Evento cuando se abre el modal
    $('#modalAsignarInteres').on('shown.bs.modal', function() {
        var estadoId = $('#interes_estado_id').val();
        if (estadoId) {
            cargarDatosEstado(estadoId);
        }
    });

    // Limpiar modal al cerrar
    $('#modalAsignarInteres').on('hidden.bs.modal', function() {
        $('#formAsignarInteres')[0].reset();
        $('#config_automatica').hide();
        $('#config_valor_fijo').show();
        $('#config_incremento, #config_formula').hide();
        $('input[name="tipo_asignacion"][value="valor_fijo"]').prop('checked', true);
        $('#valor_fijo_range, #valor_fijo_number').val(50);
        actualizarVistaPrevia(50);
    });
});
</script>