<!-- Modal para ver historial de estado específico -->
<div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-history me-2"></i>
                    Historial del Estado: <span id="historial_estado_nombre" class="text-primary">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="historial_estado_id">
                
                <!-- Filtros de historial -->
                <div class="card bg-light mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3">
                            <i class="ti ti-filter me-1"></i>
                            Filtros de Búsqueda
                        </h6>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Desde</label>
                                <input type="date" class="form-control form-control-sm" id="historial_fecha_desde">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="historial_fecha_hasta">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Usuario</label>
                                <select class="form-select form-select-sm" id="historial_usuario">
                                    <option value="">Todos los usuarios</option>
                                    <!-- Se llenará dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Tipo de Cambio</label>
                                <select class="form-select form-select-sm" id="historial_tipo">
                                    <option value="">Todos los tipos</option>
                                    <option value="entrada">Entrada al estado</option>
                                    <option value="salida">Salida del estado</option>
                                    <option value="modificacion">Modificación del estado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_filtrar_historial">
                                    <i class="ti ti-search me-1"></i>Aplicar Filtros
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn_limpiar_filtros">
                                    <i class="ti ti-refresh me-1"></i>Limpiar
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm float-end" id="btn_exportar_historial">
                                    <i class="ti ti-download me-1"></i>Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas del historial -->
                <div class="row mb-3" id="estadisticas_historial">
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 text-center">
                            <div class="card-body p-2">
                                <h4 class="text-primary mb-1" id="stat_total_cambios">0</h4>
                                <small class="text-muted">Total Cambios</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success bg-opacity-10 text-center">
                            <div class="card-body p-2">
                                <h4 class="text-success mb-1" id="stat_leads_unicos">0</h4>
                                <small class="text-muted">Leads Únicos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info bg-opacity-10 text-center">
                            <div class="card-body p-2">
                                <h4 class="text-info mb-1" id="stat_tiempo_promedio">0</h4>
                                <small class="text-muted">Días Promedio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning bg-opacity-10 text-center">
                            <div class="card-body p-2">
                                <h4 class="text-warning mb-1" id="stat_cambios_mes">0</h4>
                                <small class="text-muted">Este Mes</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="historial_loading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando historial...</p>
                </div>

                <!-- Contenido del historial -->
                <div id="historial-contenido">
                    <div class="text-center text-muted">
                        <i class="ti ti-search" style="font-size: 3rem;"></i>
                        <p class="mt-2">Seleccione los filtros y presione "Aplicar Filtros" para ver el historial</p>
                    </div>
                </div>

                <!-- Timeline de cambios -->
                <div id="historial_timeline" style="display: none;">
                    <h6 class="mb-3">Timeline de Cambios</h6>
                    <div id="timeline_contenido">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btn_generar_reporte">
                    <i class="ti ti-file-text me-1"></i>Generar Reporte
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.historial-item {
    border-left: 4px solid #007bff;
    padding: 15px 20px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    position: relative;
}

.historial-item:before {
    content: '';
    position: absolute;
    left: -8px;
    top: 20px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
}

.historial-item.entrada {
    border-left-color: #28a745;
}

.historial-item.entrada:before {
    background-color: #28a745;
}

.historial-item.salida {
    border-left-color: #dc3545;
}

.historial-item.salida:before {
    background-color: #dc3545;
}

.historial-item.modificacion {
    border-left-color: #ffc107;
}

.historial-item.modificacion:before {
    background-color: #ffc107;
}

.cambio-fecha {
    font-weight: bold;
    color: #495057;
    font-size: 0.9rem;
}

.cambio-detalle {
    margin: 8px 0;
    font-size: 0.95rem;
    line-height: 1.4;
}

.cambio-usuario {
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

.cambio-lead {
    font-size: 0.8rem;
    color: #007bff;
    font-weight: 500;
}

.badge-cambio {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    position: relative;
}

.timeline-item:not(:last-child):after {
    content: '';
    position: absolute;
    left: 15px;
    top: 35px;
    width: 2px;
    height: calc(100% + 5px);
    background-color: #e9ecef;
}

.timeline-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
    font-size: 0.8rem;
    color: white;
    font-weight: bold;
}

.timeline-content {
    flex: 1;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 12px 15px;
    border-left: 3px solid #dee2e6;
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 5px;
}

.timeline-title {
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.timeline-date {
    font-size: 0.75rem;
    color: #6c757d;
}

.timeline-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.timeline-meta {
    font-size: 0.75rem;
    color: #6c757d;
    border-top: 1px solid #e9ecef;
    padding-top: 8px;
    margin-top: 8px;
}
</style>

<script>
$(document).ready(function() {
    // Variables globales
    let historialData = [];
    let filtrosAplicados = {};

    // Inicializar fechas por defecto (último mes)
    var hoy = new Date();
    var hace30Dias = new Date();
    hace30Dias.setDate(hoy.getDate() - 30);
    
    $('#historial_fecha_hasta').val(hoy.toISOString().split('T')[0]);
    $('#historial_fecha_desde').val(hace30Dias.toISOString().split('T')[0]);

    // Aplicar filtros
    $('#btn_filtrar_historial').on('click', function() {
        aplicarFiltros();
    });

    // Limpiar filtros
    $('#btn_limpiar_filtros').on('click', function() {
        $('#historial_fecha_desde').val('');
        $('#historial_fecha_hasta').val('');
        $('#historial_usuario').val('');
        $('#historial_tipo').val('');
        $('#historial-contenido').html('<div class="text-center text-muted"><p>Filtros limpiados. Aplique nuevos filtros para ver el historial.</p></div>');
        $('#historial_timeline').hide();
    });

    // Exportar historial
    $('#btn_exportar_historial').on('click', function() {
        exportarHistorial();
    });

    // Generar reporte
    $('#btn_generar_reporte').on('click', function() {
        generarReporteHistorial();
    });

    function aplicarFiltros() {
        var estadoId = $('#historial_estado_id').val();
        
        if (!estadoId) {
            alert('Error: No se ha seleccionado un estado');
            return;
        }

        var filtros = {
            estado_id: estadoId,
            fecha_desde: $('#historial_fecha_desde').val(),
            fecha_hasta: $('#historial_fecha_hasta').val(),
            usuario_id: $('#historial_usuario').val(),
            tipo_cambio: $('#historial_tipo').val()
        };

        filtrosAplicados = filtros;

        $('#historial_loading').show();
        $('#historial-contenido').hide();

        $.ajax({
            url: 'acciones/clasificacion_leads/obtener_historial_estado.php',
            method: 'POST',
            data: filtros,
            dataType: 'json',
            success: function(response) {
                $('#historial_loading').hide();
                $('#historial-contenido').show();
                
                if (response.success) {
                    historialData = response.data;
                    mostrarHistorial(response.data);
                    mostrarEstadisticas(response.estadisticas);
                    mostrarTimeline(response.timeline);
                } else {
                    $('#historial-contenido').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#historial_loading').hide();
                $('#historial-contenido').html('<div class="alert alert-danger">Error de conexión al cargar el historial.</div>').show();
            }
        });
    }

    function mostrarHistorial(data) {
        if (!data || data.length === 0) {
            $('#historial-contenido').html('<div class="text-center text-muted"><i class="ti ti-inbox" style="font-size: 3rem;"></i><p class="mt-2">No se encontraron cambios en el período seleccionado</p></div>');
            return;
        }

        var html = '<div class="historial-lista">';
        
        data.forEach(function(item) {
            var tipoClass = item.tipo_cambio || 'modificacion';
            var iconoTipo = '';
            var descripcionTipo = '';
            
            switch(tipoClass) {
                case 'entrada':
                    iconoTipo = 'ti ti-arrow-right';
                    descripcionTipo = 'Entrada al estado';
                    break;
                case 'salida':
                    iconoTipo = 'ti ti-arrow-left';
                    descripcionTipo = 'Salida del estado';
                    break;
                default:
                    iconoTipo = 'ti ti-edit';
                    descripcionTipo = 'Modificación';
                    break;
            }

            html += `
                <div class="historial-item ${tipoClass}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="cambio-fecha">
                                <i class="${iconoTipo} me-1"></i>
                                ${item.fecha_formateada} - ${descripcionTipo}
                                <span class="badge badge-cambio bg-secondary ms-2">${item.hora}</span>
                            </div>
                            <div class="cambio-detalle">
                                ${item.descripcion}
                            </div>
                            ${item.lead_codigo ? '<div class="cambio-lead">Lead: ' + item.lead_codigo + ' - ' + item.lead_nombre + '</div>' : ''}
                            <div class="cambio-usuario">
                                Por: ${item.usuario_nombre} ${item.observaciones ? '| Obs: ' + item.observaciones : ''}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            ${item.estado_anterior ? '<span class="badge me-1" style="background-color: ' + item.color_anterior + '">' + item.estado_anterior + '</span>' : ''}
                            ${item.estado_nuevo ? '<span class="badge" style="background-color: ' + item.color_nuevo + '">' + item.estado_nuevo + '</span>' : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#historial-contenido').html(html);
    }

    function mostrarEstadisticas(stats) {
        if (stats) {
            $('#stat_total_cambios').text(stats.total_cambios || 0);
            $('#stat_leads_unicos').text(stats.leads_unicos || 0);
            $('#stat_tiempo_promedio').text((stats.tiempo_promedio || 0) + 'd');
            $('#stat_cambios_mes').text(stats.cambios_mes || 0);
        }
    }

    function mostrarTimeline(timeline) {
        if (!timeline || timeline.length === 0) {
            $('#historial_timeline').hide();
            return;
        }

        var html = '';
        timeline.forEach(function(item, index) {
            var iconColor = item.tipo_cambio === 'entrada' ? '#28a745' : 
                           item.tipo_cambio === 'salida' ? '#dc3545' : '#ffc107';
            
            html += `
                <div class="timeline-item">
                    <div class="timeline-icon" style="background-color: ${iconColor}">
                        ${index + 1}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h6 class="timeline-title">${item.titulo}</h6>
                            <span class="timeline-date">${item.fecha}</span>
                        </div>
                        <div class="timeline-description">${item.descripcion}</div>
                        <div class="timeline-meta">
                            <strong>Usuario:</strong> ${item.usuario} | 
                            <strong>Lead:</strong> ${item.lead_info || 'N/A'}
                        </div>
                    </div>
                </div>
            `;
        });

        $('#timeline_contenido').html(html);
        $('#historial_timeline').show();
    }

    function exportarHistorial() {
        if (historialData.length === 0) {
            alert('No hay datos para exportar');
            return;
        }

        // Crear CSV
        var csv = 'Fecha,Hora,Tipo,Descripción,Usuario,Lead,Observaciones\n';
        historialData.forEach(function(item) {
            csv += `"${item.fecha_formateada}","${item.hora}","${item.tipo_cambio}","${item.descripcion}","${item.usuario_nombre}","${item.lead_codigo || ''}","${item.observaciones || ''}"\n`;
        });

        // Descargar archivo
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'historial_estado_' + $('#historial_estado_nombre').text() + '_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    function generarReporteHistorial() {
        var estadoId = $('#historial_estado_id').val();
        var estadoNombre = $('#historial_estado_nombre').text();
        
        if (!estadoId) {
            alert('Error: No se ha seleccionado un estado');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reports/reporte_historial_estado.php';
        form.target = '_blank';

        var inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'estado_id';
        inputId.value = estadoId;

        var inputNombre = document.createElement('input');
        inputNombre.type = 'hidden';
        inputNombre.name = 'estado_nombre';
        inputNombre.value = estadoNombre;

        var inputFiltros = document.createElement('input');
        inputFiltros.type = 'hidden';
        inputFiltros.name = 'filtros';
        inputFiltros.value = JSON.stringify(filtrosAplicados);

        form.appendChild(inputId);
        form.appendChild(inputNombre);
        form.appendChild(inputFiltros);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Limpiar modal al cerrar
    $('#modalHistorial').on('hidden.bs.modal', function() {
        $('#historial-contenido').html('<div class="text-center text-muted"><p>Seleccione los filtros y presione "Aplicar Filtros" para ver el historial</p></div>');
        $('#historial_timeline').hide();
        historialData = [];
        filtrosAplicados = {};
    });
});

// Función para cargar usuarios en el select
function cargarUsuariosHistorial() {
    $.ajax({
        url: 'acciones/clasificacion_leads/obtener_usuarios.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var select = $('#historial_usuario');
                select.empty().append('<option value="">Todos los usuarios</option>');
                response.data.forEach(function(usuario) {
                    select.append(`<option value="${usuario.id}">${usuario.nombre_completo}</option>`);
                });
            }
        }
    });
}

// Cargar usuarios al abrir el modal
$('#modalHistorial').on('shown.bs.modal', function() {
    cargarUsuariosHistorial();
});
</script>