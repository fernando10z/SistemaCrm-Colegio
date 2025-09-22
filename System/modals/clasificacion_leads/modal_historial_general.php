<!-- Modal para ver historial general de estados -->
<div class="modal fade" id="modalHistorialGeneral" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-history me-2"></i>
                    Historial General de Estados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Estadísticas Generales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1" id="stat-total-cambios">-</h4>
                                <small>Total Cambios</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1" id="stat-leads-cambios">-</h4>
                                <small>Leads con Cambios</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1" id="stat-cambios-semana">-</h4>
                                <small>Esta Semana</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1" id="stat-usuarios-activos">-</h4>
                                <small>Usuarios Activos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Filtros de Búsqueda</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Desde</label>
                                        <input type="date" class="form-control form-control-sm" id="filtro-fecha-desde">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="filtro-fecha-hasta">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Usuario</label>
                                        <select class="form-select form-select-sm" id="filtro-usuario">
                                            <option value="">Todos los usuarios</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select form-select-sm" id="filtro-estado">
                                            <option value="">Todos los estados</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosHistorial()">
                                            <i class="ti ti-refresh me-1"></i>
                                            Limpiar
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="aplicarFiltrosHistorial()">
                                            <i class="ti ti-search me-1"></i>
                                            Filtrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Historial -->
                <div class="table-responsive">
                    <table id="historial-general-table" class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th width="10%">Fecha</th>
                                <th width="15%">Lead</th>
                                <th width="12%">Estado Anterior</th>
                                <th width="12%">Estado Nuevo</th>
                                <th width="12%">Usuario</th>
                                <th width="25%">Observaciones</th>
                                <th width="8%">Tiempo</th>
                                <th width="6%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historial-general-tbody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="dataTables_info" id="historial-info">
                            Mostrando registros del historial
                        </div>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Paginación del historial">
                            <ul class="pagination pagination-sm justify-content-end mb-0" id="historial-pagination">
                                <!-- La paginación se generará dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarHistorialPDF()">
                    <i class="fas fa-file-pdf me-1"></i>
                    Exportar PDF
                </button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="exportarHistorialExcel()">
                    <i class="fas fa-file-excel me-1"></i>
                    Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para el historial general -->
<style>
.historial-cambio-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.historial-fecha {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
}

.historial-lead {
    font-weight: 600;
    color: #2c3e50;
}

.historial-estados {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 4px 0;
}

.historial-arrow {
    color: #6c757d;
    font-size: 1rem;
}

.historial-usuario {
    font-size: 0.8rem;
    color: #495057;
    font-style: italic;
}

.historial-observaciones {
    font-size: 0.85rem;
    color: #495057;
    line-height: 1.3;
}

.tiempo-transcurrido {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
}

.estado-badge-historial {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-weight: 500;
    color: white;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.historial-sin-datos {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

#historial-general-table {
    font-size: 0.85rem;
}

#historial-general-table th {
    background-color: #495057;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 8px;
}

#historial-general-table td {
    padding: 6px 8px;
    vertical-align: middle;
}

.pagination-sm .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.dataTables_info {
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 2;
}
</style>

<script>
// Funciones específicas para el historial general
window.cargarHistorialGeneral = function() {
    // Configurar fechas por defecto (último mes)
    const fechaHasta = new Date();
    const fechaDesde = new Date();
    fechaDesde.setMonth(fechaDesde.getMonth() - 1);
    
    document.getElementById('filtro-fecha-desde').value = fechaDesde.toISOString().split('T')[0];
    document.getElementById('filtro-fecha-hasta').value = fechaHasta.toISOString().split('T')[0];
    
    // Cargar datos iniciales
    cargarEstadisticasGenerales();
    cargarOpcionesFiltros();
    cargarDatosHistorial();
};

function cargarEstadisticasGenerales() {
    $.ajax({
        url: 'acciones/clasificacion_leads/obtener_estadisticas_historial.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#stat-total-cambios').text(response.data.total_cambios || 0);
                $('#stat-leads-cambios').text(response.data.leads_con_cambios || 0);
                $('#stat-cambios-semana').text(response.data.cambios_semana || 0);
                $('#stat-usuarios-activos').text(response.data.usuarios_activos || 0);
            }
        },
        error: function() {
            console.error('Error al cargar estadísticas generales');
        }
    });
}

function cargarOpcionesFiltros() {
    // Cargar usuarios
    $.ajax({
        url: 'acciones/clasificacion_leads/obtener_usuarios_historial.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let usuarioSelect = $('#filtro-usuario');
                usuarioSelect.html('<option value="">Todos los usuarios</option>');
                response.data.forEach(function(usuario) {
                    usuarioSelect.append(`<option value="${usuario.id}">${usuario.nombre}</option>`);
                });
            }
        }
    });
    
    // Cargar estados
    $.ajax({
        url: 'acciones/clasificacion_leads/obtener_estados_historial.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let estadoSelect = $('#filtro-estado');
                estadoSelect.html('<option value="">Todos los estados</option>');
                response.data.forEach(function(estado) {
                    estadoSelect.append(`<option value="${estado.id}">${estado.nombre}</option>`);
                });
            }
        }
    });
}

function cargarDatosHistorial(pagina = 1) {
    const filtros = {
        fecha_desde: $('#filtro-fecha-desde').val(),
        fecha_hasta: $('#filtro-fecha-hasta').val(),
        usuario_id: $('#filtro-usuario').val(),
        estado_id: $('#filtro-estado').val(),
        pagina: pagina,
        por_pagina: 20
    };
    
    $('#historial-general-tbody').html(`
        <tr>
            <td colspan="8" class="text-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Cargando historial...
            </td>
        </tr>
    `);
    
    $.ajax({
        url: 'acciones/clasificacion_leads/obtener_historial_general.php',
        method: 'POST',
        data: filtros,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarDatosHistorial(response.data);
                generarPaginacion(response.pagination);
                actualizarInfoPaginacion(response.pagination);
            } else {
                $('#historial-general-tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center text-danger">
                            Error al cargar el historial: ${response.message}
                        </td>
                    </tr>
                `);
            }
        },
        error: function() {
            $('#historial-general-tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        Error de conexión al cargar el historial
                    </td>
                </tr>
            `);
        }
    });
}

function mostrarDatosHistorial(datos) {
    let html = '';
    
    if (datos.length === 0) {
        html = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <div class="historial-sin-datos">
                        <i class="ti ti-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                        <p class="mt-2 mb-0">No se encontraron registros de historial con los filtros aplicados</p>
                    </div>
                </td>
            </tr>
        `;
    } else {
        datos.forEach(function(cambio) {
            const estadoAnterior = cambio.estado_anterior ? 
                `<span class="estado-badge-historial" style="background-color: ${cambio.color_anterior};">
                    ${cambio.estado_anterior}
                </span>` : 
                '<span class="text-muted">Nuevo</span>';
                
            const estadoNuevo = `<span class="estado-badge-historial" style="background-color: ${cambio.color_nuevo};">
                ${cambio.estado_nuevo}
            </span>`;
            
            const tiempoTranscurrido = cambio.tiempo_transcurrido ? 
                `<span class="tiempo-transcurrido">${cambio.tiempo_transcurrido}</span>` : 
                '<span class="text-muted">-</span>';
                
            html += `
                <tr>
                    <td class="historial-fecha">${cambio.fecha_formateada}</td>
                    <td class="historial-lead">
                        <a href="ver_lead.php?id=${cambio.lead_id}" target="_blank" class="text-decoration-none">
                            ${cambio.lead_codigo}<br>
                            <small class="text-muted">${cambio.lead_nombre}</small>
                        </a>
                    </td>
                    <td>${estadoAnterior}</td>
                    <td>${estadoNuevo}</td>
                    <td class="historial-usuario">${cambio.usuario_nombre}</td>
                    <td class="historial-observaciones">
                        ${cambio.observaciones ? cambio.observaciones : '<span class="text-muted">Sin observaciones</span>'}
                    </td>
                    <td>${tiempoTranscurrido}</td>
                    <td>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="verDetallesCambio(${cambio.id})" title="Ver detalles">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#historial-general-tbody').html(html);
}

function generarPaginacion(pagination) {
    let html = '';
    
    if (pagination.total_paginas > 1) {
        // Botón anterior
        if (pagination.pagina_actual > 1) {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="cargarDatosHistorial(${pagination.pagina_actual - 1})">Anterior</a>
            </li>`;
        }
        
        // Páginas
        for (let i = Math.max(1, pagination.pagina_actual - 2); 
             i <= Math.min(pagination.total_paginas, pagination.pagina_actual + 2); 
             i++) {
            const active = i === pagination.pagina_actual ? 'active' : '';
            html += `<li class="page-item ${active}">
                <a class="page-link" href="#" onclick="cargarDatosHistorial(${i})">${i}</a>
            </li>`;
        }
        
        // Botón siguiente
        if (pagination.pagina_actual < pagination.total_paginas) {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="cargarDatosHistorial(${pagination.pagina_actual + 1})">Siguiente</a>
            </li>`;
        }
    }
    
    $('#historial-pagination').html(html);
}

function actualizarInfoPaginacion(pagination) {
    const desde = ((pagination.pagina_actual - 1) * pagination.por_pagina) + 1;
    const hasta = Math.min(pagination.pagina_actual * pagination.por_pagina, pagination.total_registros);
    
    $('#historial-info').text(
        `Mostrando ${desde} a ${hasta} de ${pagination.total_registros} registros`
    );
}

window.aplicarFiltrosHistorial = function() {
    cargarDatosHistorial(1);
};

window.limpiarFiltrosHistorial = function() {
    $('#filtro-fecha-desde').val('');
    $('#filtro-fecha-hasta').val('');
    $('#filtro-usuario').val('');
    $('#filtro-estado').val('');
    cargarDatosHistorial(1);
};

window.exportarHistorialPDF = function() {
    const filtros = {
        fecha_desde: $('#filtro-fecha-desde').val(),
        fecha_hasta: $('#filtro-fecha-hasta').val(),
        usuario_id: $('#filtro-usuario').val(),
        estado_id: $('#filtro-estado').val(),
        formato: 'pdf'
    };
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reports/exportar_historial_general.php';
    form.target = '_blank';
    
    Object.keys(filtros).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = filtros[key];
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

window.exportarHistorialExcel = function() {
    const filtros = {
        fecha_desde: $('#filtro-fecha-desde').val(),
        fecha_hasta: $('#filtro-fecha-hasta').val(),
        usuario_id: $('#filtro-usuario').val(),
        estado_id: $('#filtro-estado').val(),
        formato: 'excel'
    };
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reports/exportar_historial_general.php';
    form.target = '_blank';
    
    Object.keys(filtros).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = filtros[key];
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

function verDetallesCambio(cambioId) {
    // Implementar modal para ver detalles específicos del cambio
    alert('Función para ver detalles del cambio ID: ' + cambioId);
}

// Event listener para cargar datos cuando se abre el modal
$('#modalHistorialGeneral').on('shown.bs.modal', function () {
    cargarHistorialGeneral();
});
</script>