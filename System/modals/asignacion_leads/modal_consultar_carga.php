<!-- Modal para consultar carga de trabajo -->
<div class="modal fade" id="modalConsultarCarga" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-chart-bar me-2"></i>
                    Análisis de Carga de Trabajo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="alert alert-primary">
                    <i class="ti ti-user me-2"></i>
                    Analizando carga de trabajo de: <strong id="carga_usuario_nombre"></strong>
                </div>

                <input type="hidden" id="carga_usuario_id">

                <!-- Pestañas de navegación -->
                <ul class="nav nav-tabs mb-3" id="cargaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab">
                            <i class="ti ti-dashboard me-1"></i>
                            Resumen General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="leads-tab" data-bs-toggle="tab" data-bs-target="#leads" type="button" role="tab">
                            <i class="ti ti-users me-1"></i>
                            Leads Asignados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tareas-tab" data-bs-toggle="tab" data-bs-target="#tareas" type="button" role="tab">
                            <i class="ti ti-calendar me-1"></i>
                            Tareas y Seguimiento
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rendimiento-tab" data-bs-toggle="tab" data-bs-target="#rendimiento" type="button" role="tab">
                            <i class="ti ti-trending-up me-1"></i>
                            Rendimiento
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="cargaTabsContent">
                    <!-- Resumen General -->
                    <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                        <div id="carga-detalle-contenido">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando información de carga de trabajo...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Leads Asignados -->
                    <div class="tab-pane fade" id="leads" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Filtrar por Estado</label>
                                <select class="form-select" id="filtro_estado_leads">
                                    <option value="">Todos los estados</option>
                                    <?php
                                    $estados_query = "SELECT id, nombre, color FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
                                    $estados_result = $conn->query($estados_query);
                                    while($estado = $estados_result->fetch_assoc()) {
                                        echo "<option value='{$estado['id']}'>" . htmlspecialchars($estado['nombre']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Filtrar por Prioridad</label>
                                <select class="form-select" id="filtro_prioridad_leads">
                                    <option value="">Todas las prioridades</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="tabla-leads-usuario">
                            <!-- Aquí se cargará la tabla de leads del usuario -->
                        </div>
                    </div>

                    <!-- Tareas y Seguimiento -->
                    <div class="tab-pane fade" id="tareas" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-center bg-warning text-white">
                                    <div class="card-body">
                                        <h4 id="tareas_hoy_count">-</h4>
                                        <p class="mb-0">Tareas Hoy</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center bg-danger text-white">
                                    <div class="card-body">
                                        <h4 id="tareas_vencidas_count">-</h4>
                                        <p class="mb-0">Tareas Vencidas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center bg-info text-white">
                                    <div class="card-body">
                                        <h4 id="tareas_proximas_count">-</h4>
                                        <p class="mb-0">Próximas (7 días)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="calendario-tareas">
                            <!-- Aquí se cargará el calendario de tareas -->
                        </div>
                    </div>

                    <!-- Rendimiento -->
                    <div class="tab-pane fade" id="rendimiento" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="chart-conversion" width="400" height="300"></canvas>
                                <h6 class="text-center mt-2">Tasa de Conversión (Últimos 6 meses)</h6>
                            </div>
                            <div class="col-md-6">
                                <canvas id="chart-actividad" width="400" height="300"></canvas>
                                <h6 class="text-center mt-2">Actividad Mensual</h6>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Métricas de Rendimiento</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="metricas-rendimiento">
                                            <!-- Se cargará via AJAX -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="exportarCargaPDF()">
                    <i class="ti ti-file-pdf me-1"></i>
                    Exportar PDF
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="sugerirOptimizacion()">
                    <i class="ti ti-bulb me-1"></i>
                    Sugerir Optimización
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar cambio de pestañas
    $('#cargaTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).data('bs-target');
        var usuarioId = $('#carga_usuario_id').val();
        
        if (target === '#leads' && usuarioId) {
            cargarLeadsUsuarioDetalle(usuarioId);
        } else if (target === '#tareas' && usuarioId) {
            cargarTareasUsuario(usuarioId);
        } else if (target === '#rendimiento' && usuarioId) {
            cargarRendimientoUsuario(usuarioId);
        }
    });

    // Filtros para leads
    $('#filtro_estado_leads, #filtro_prioridad_leads').change(function() {
        var usuarioId = $('#carga_usuario_id').val();
        if (usuarioId) {
            cargarLeadsUsuarioDetalle(usuarioId);
        }
    });

    function cargarLeadsUsuarioDetalle(usuarioId) {
        var estadoFiltro = $('#filtro_estado_leads').val();
        var prioridadFiltro = $('#filtro_prioridad_leads').val();
        
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_leads_usuario_detalle.php',
            method: 'POST',
            data: { 
                usuario_id: usuarioId,
                estado_filtro: estadoFiltro,
                prioridad_filtro: prioridadFiltro
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#tabla-leads-usuario').html(response.html);
                } else {
                    $('#tabla-leads-usuario').html('<p class="text-danger">Error al cargar leads del usuario.</p>');
                }
            },
            error: function() {
                $('#tabla-leads-usuario').html('<p class="text-danger">Error de conexión.</p>');
            }
        });
    }

    function cargarTareasUsuario(usuarioId) {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_tareas_usuario.php',
            method: 'POST',
            data: { usuario_id: usuarioId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#tareas_hoy_count').text(response.data.tareas_hoy);
                    $('#tareas_vencidas_count').text(response.data.tareas_vencidas);
                    $('#tareas_proximas_count').text(response.data.tareas_proximas);
                    $('#calendario-tareas').html(response.calendario_html);
                }
            },
            error: function() {
                $('#calendario-tareas').html('<p class="text-danger">Error al cargar tareas.</p>');
            }
        });
    }

    function cargarRendimientoUsuario(usuarioId) {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_rendimiento_usuario.php',
            method: 'POST',
            data: { usuario_id: usuarioId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Aquí podrías usar Chart.js para mostrar gráficos
                    $('#metricas-rendimiento').html(response.metricas_html);
                    
                    // Ejemplo de implementación con Chart.js (si está disponible)
                    // createConversionChart(response.conversion_data);
                    // createActivityChart(response.activity_data);
                }
            },
            error: function() {
                $('#metricas-rendimiento').html('<p class="text-danger">Error al cargar métricas.</p>');
            }
        });
    }

    // Función para exportar PDF
    window.exportarCargaPDF = function() {
        var usuarioId = $('#carga_usuario_id').val();
        if (usuarioId) {
            window.open('reports/generar_pdf_carga_usuario.php?usuario_id=' + usuarioId, '_blank');
        }
    };

    // Función para sugerir optimización
    window.sugerirOptimizacion = function() {
        var usuarioId = $('#carga_usuario_id').val();
        if (usuarioId) {
            $.ajax({
                url: 'acciones/asignacion_leads/sugerir_optimizacion.php',
                method: 'POST',
                data: { usuario_id: usuarioId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Sugerencias de optimización:\n\n' + response.sugerencias);
                    }
                },
                error: function() {
                    alert('Error al generar sugerencias de optimización.');
                }
            });
        }
    };
});
</script>