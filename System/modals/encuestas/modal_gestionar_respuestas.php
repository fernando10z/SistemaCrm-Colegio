<!-- Modal para gestionar respuestas -->
<div class="modal fade" id="modalGestionarRespuestas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Respuestas de Encuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="gestionar_respuestas_encuesta_id">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Seleccionar Encuesta</label>
                        <select class="form-select" id="select-encuesta-respuestas" onchange="cargarRespuestasEncuesta()">
                            <option value="">Seleccionar encuesta</option>
                            <?php
                            // Obtener encuestas para gestionar respuestas
                            $query_encuestas_resp = "SELECT e.id, e.titulo, e.tipo, e.dirigido_a, COUNT(re.id) as total_respuestas
                                                   FROM encuestas e 
                                                   LEFT JOIN respuestas_encuesta re ON e.id = re.encuesta_id
                                                   GROUP BY e.id, e.titulo, e.tipo, e.dirigido_a
                                                   ORDER BY e.created_at DESC";
                            $result_encuestas_resp = $conn->query($query_encuestas_resp);
                            if ($result_encuestas_resp && $result_encuestas_resp->num_rows > 0) {
                                while($encuesta = $result_encuestas_resp->fetch_assoc()) {
                                    echo "<option value='" . $encuesta['id'] . "'>" . 
                                         htmlspecialchars($encuesta['titulo']) . " (" . $encuesta['total_respuestas'] . " respuestas)</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Filtros</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="filtro-estado-respuesta" onchange="filtrarRespuestas()">
                                <option value="">Todas las respuestas</option>
                                <option value="completa">Completas</option>
                                <option value="incompleta">Incompletas</option>
                            </select>
                            <input type="date" class="form-control" id="filtro-fecha-respuesta" onchange="filtrarRespuestas()" 
                                   placeholder="Fecha">
                        </div>
                    </div>
                </div>
                
                <div id="info-encuesta-seleccionada" style="display: none;">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-9">
                                <h6 class="mb-1" id="info-titulo-encuesta"></h6>
                                <p class="mb-0" id="info-descripcion-encuesta"></p>
                            </div>
                            <div class="col-md-3 text-end">
                                <span class="badge bg-primary" id="info-total-respuestas">0 respuestas</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs para diferentes vistas -->
                <ul class="nav nav-tabs" id="tabs-respuestas" role="tablist" style="display: none;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-lista-respuestas" data-bs-toggle="tab" 
                                data-bs-target="#panel-lista-respuestas" type="button">
                            Lista de Respuestas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-respuesta-detalle" data-bs-toggle="tab" 
                                data-bs-target="#panel-respuesta-detalle" type="button">
                            Detalle de Respuesta
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-exportar-datos" data-bs-toggle="tab" 
                                data-bs-target="#panel-exportar-datos" type="button">
                            Exportar Datos
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="contenido-tabs-respuestas" style="display: none;">
                    <!-- Panel Lista de Respuestas -->
                    <div class="tab-pane fade show active" id="panel-lista-respuestas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabla-respuestas">
                                <thead class="table-light">
                                    <tr>
                                        <th width="8%">ID</th>
                                        <th width="25%">Respondente</th>
                                        <th width="15%">Fecha</th>
                                        <th width="12%">Puntaje</th>
                                        <th width="15%">Estado</th>
                                        <th width="10%">IP</th>
                                        <th width="15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-respuestas">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="paginacion-respuestas" class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <select class="form-select form-select-sm" id="respuestas-por-pagina" onchange="cargarRespuestasEncuesta()" style="width: auto;">
                                    <option value="10">10 por página</option>
                                    <option value="25" selected>25 por página</option>
                                    <option value="50">50 por página</option>
                                    <option value="100">100 por página</option>
                                </select>
                            </div>
                            <div id="info-paginacion">
                                <span id="texto-paginacion">Mostrando 0 de 0 respuestas</span>
                            </div>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm" id="btn-pagina-anterior" onclick="cambiarPagina(-1)" disabled>
                                    <i class="ti ti-chevron-left"></i> Anterior
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" id="btn-pagina-siguiente" onclick="cambiarPagina(1)" disabled>
                                    Siguiente <i class="ti ti-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Detalle de Respuesta -->
                    <div class="tab-pane fade" id="panel-respuesta-detalle" role="tabpanel">
                        <div id="contenido-respuesta-detalle">
                            <div class="text-center py-5">
                                <i class="ti ti-file-search" style="font-size: 4rem; color: #ccc;"></i>
                                <h5 class="text-muted mt-3">Selecciona una respuesta para ver el detalle</h5>
                                <p class="text-muted">Haz clic en "Ver Detalle" en la lista de respuestas</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Exportar Datos -->
                    <div class="tab-pane fade" id="panel-exportar-datos" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Exportar Respuestas</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Formato de exportación</label>
                                            <select class="form-select" id="formato-exportacion">
                                                <option value="excel">Excel (.xlsx)</option>
                                                <option value="csv">CSV (.csv)</option>
                                                <option value="pdf">PDF (.pdf)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Incluir en la exportación</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="incluir-respuestas" checked>
                                                <label class="form-check-label">Respuestas individuales</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="incluir-estadisticas" checked>
                                                <label class="form-check-label">Estadísticas generales</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="incluir-graficos">
                                                <label class="form-check-label">Gráficos (solo PDF)</label>
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn btn-primary" onclick="exportarRespuestas()">
                                            <i class="ti ti-download"></i> Exportar Datos
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Acciones Masivas</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Seleccionar respuestas</label>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodasRespuestas()">
                                                    Seleccionar Todas
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deseleccionarTodasRespuestas()">
                                                    Deseleccionar Todas
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-warning" onclick="marcarComoIncompletas()">
                                                <i class="ti ti-exclamation-circle"></i> Marcar como Incompletas
                                            </button>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-danger" onclick="eliminarRespuestasSeleccionadas()">
                                                <i class="ti ti-trash"></i> Eliminar Seleccionadas
                                            </button>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <small>
                                                <i class="ti ti-alert-triangle me-1"></i>
                                                Las acciones masivas no se pueden deshacer. Use con precaución.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="sin-encuesta-seleccionada" class="text-center py-5">
                    <i class="ti ti-clipboard-list" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="text-muted mt-3">Selecciona una encuesta para gestionar sus respuestas</h5>
                    <p class="text-muted">Podrás ver, filtrar y exportar las respuestas recibidas</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-info" id="btn-actualizar-datos" onclick="cargarRespuestasEncuesta()" style="display: none;">
                    <i class="ti ti-refresh"></i> Actualizar Datos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let respuestasActuales = [];
let paginaActual = 1;
let totalPaginas = 1;
let encuestaSeleccionada = null;

function cargarRespuestasEncuesta() {
    const encuestaId = document.getElementById('select-encuesta-respuestas').value;
    
    if (!encuestaId) {
        ocultarContenidoRespuestas();
        return;
    }
    
    const respuestasPorPagina = document.getElementById('respuestas-por-pagina').value;
    
    const formData = new FormData();
    formData.append('accion', 'obtener_respuestas_encuesta');
    formData.append('encuesta_id', encuestaId);
    formData.append('pagina', paginaActual);
    formData.append('por_pagina', respuestasPorPagina);
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            encuestaSeleccionada = data.encuesta;
            respuestasActuales = data.respuestas;
            totalPaginas = data.total_paginas;
            
            mostrarInfoEncuesta(data.encuesta);
            mostrarRespuestas(data.respuestas);
            actualizarPaginacion(data.paginacion);
            mostrarContenidoRespuestas();
        } else {
            alert('Error al cargar las respuestas: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al cargar las respuestas');
    });
}

function mostrarInfoEncuesta(encuesta) {
    document.getElementById('info-titulo-encuesta').textContent = encuesta.titulo;
    document.getElementById('info-descripcion-encuesta').textContent = encuesta.descripcion || 'Sin descripción';
    document.getElementById('info-total-respuestas').textContent = encuesta.total_respuestas + ' respuestas';
    document.getElementById('info-encuesta-seleccionada').style.display = 'block';
}

function mostrarRespuestas(respuestas) {
    const tbody = document.getElementById('tbody-respuestas');
    let html = '';
    
    if (respuestas.length === 0) {
        html = '<tr><td colspan="7" class="text-center text-muted">No hay respuestas para mostrar</td></tr>';
    } else {
        respuestas.forEach(respuesta => {
            const estadoBadge = respuesta.estado === 'completa' ? 
                '<span class="badge bg-success">Completa</span>' : 
                '<span class="badge bg-warning">Incompleta</span>';
            
            const puntaje = respuesta.puntaje_calculado ? 
                `<span class="fw-bold">${respuesta.puntaje_calculado}/5</span>` : 
                '<span class="text-muted">N/A</span>';
            
            html += `
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input respuesta-checkbox" value="${respuesta.id}">
                        <strong>#${respuesta.id}</strong>
                    </td>
                    <td>
                        <div>
                            <strong>${respuesta.respondente_nombre || 'Anónimo'}</strong>
                            ${respuesta.respondente_email ? `<br><small class="text-muted">${respuesta.respondente_email}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        <small>${respuesta.fecha_respuesta}</small>
                    </td>
                    <td>${puntaje}</td>
                    <td>${estadoBadge}</td>
                    <td><small class="text-muted">${respuesta.ip_respuesta || 'N/A'}</small></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="verDetalleRespuesta(${respuesta.id})" title="Ver Detalle">
                                <i class="ti ti-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarRespuesta(${respuesta.id})" title="Eliminar">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    tbody.innerHTML = html;
}

function verDetalleRespuesta(respuestaId) {
    const formData = new FormData();
    formData.append('accion', 'obtener_detalle_respuesta');
    formData.append('respuesta_id', respuestaId);
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarDetalleRespuesta(data.respuesta);
            // Cambiar a la pestaña de detalle
            document.getElementById('tab-respuesta-detalle').click();
        } else {
            alert('Error al cargar el detalle: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al cargar el detalle');
    });
}

function mostrarDetalleRespuesta(respuesta) {
    let html = `
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Respuesta #${respuesta.id}</h6>
                    <div>
                        <span class="badge bg-primary">${respuesta.fecha_respuesta}</span>
                        ${respuesta.puntaje_calculado ? `<span class="badge bg-success">${respuesta.puntaje_calculado}/5</span>` : ''}
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Respondente:</strong> ${respuesta.respondente_nombre || 'Anónimo'}
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong> ${respuesta.respondente_email || 'No proporcionado'}
                    </div>
                </div>
                
                <hr>
                
                <h6 class="mb-3">Respuestas por Pregunta:</h6>
    `;
    
    if (respuesta.respuestas_detalle && respuesta.respuestas_detalle.length > 0) {
        respuesta.respuestas_detalle.forEach((pregunta, index) => {
            html += `
                <div class="mb-4">
                    <div class="fw-bold mb-2">Pregunta ${index + 1}: ${pregunta.pregunta}</div>
                    <div class="ps-3">
                        <div class="alert alert-light">
                            ${Array.isArray(pregunta.respuesta) ? pregunta.respuesta.join(', ') : pregunta.respuesta}
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += '<p class="text-muted">No hay respuestas detalladas disponibles.</p>';
    }
    
    html += `
            </div>
        </div>
    `;
    
    document.getElementById('contenido-respuesta-detalle').innerHTML = html;
}

function actualizarPaginacion(paginacion) {
    document.getElementById('texto-paginacion').textContent = 
        `Mostrando ${paginacion.desde} a ${paginacion.hasta} de ${paginacion.total} respuestas`;
    
    document.getElementById('btn-pagina-anterior').disabled = paginacion.pagina_actual <= 1;
    document.getElementById('btn-pagina-siguiente').disabled = paginacion.pagina_actual >= paginacion.total_paginas;
}

function cambiarPagina(direccion) {
    const nuevaPagina = paginaActual + direccion;
    if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas) {
        paginaActual = nuevaPagina;
        cargarRespuestasEncuesta();
    }
}

function filtrarRespuestas() {
    // Implementar filtrado por estado y fecha
    cargarRespuestasEncuesta();
}

function exportarRespuestas() {
    const formato = document.getElementById('formato-exportacion').value;
    const incluirRespuestas = document.getElementById('incluir-respuestas').checked;
    const incluirEstadisticas = document.getElementById('incluir-estadisticas').checked;
    const incluirGraficos = document.getElementById('incluir-graficos').checked;
    
    const formData = new FormData();
    formData.append('accion', 'exportar_respuestas');
    formData.append('encuesta_id', document.getElementById('select-encuesta-respuestas').value);
    formData.append('formato', formato);
    formData.append('incluir_respuestas', incluirRespuestas ? '1' : '0');
    formData.append('incluir_estadisticas', incluirEstadisticas ? '1' : '0');
    formData.append('incluir_graficos', incluirGraficos ? '1' : '0');
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `respuestas_encuesta_${new Date().getTime()}.${formato === 'excel' ? 'xlsx' : formato}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al exportar las respuestas');
    });
}

function eliminarRespuesta(respuestaId) {
    if (!confirm('¿Está seguro de que desea eliminar esta respuesta? Esta acción no se puede deshacer.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'eliminar_respuesta');
    formData.append('respuesta_id', respuestaId);
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarRespuestasEncuesta(); // Recargar la lista
        } else {
            alert('Error al eliminar la respuesta: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al eliminar la respuesta');
    });
}

function seleccionarTodasRespuestas() {
    document.querySelectorAll('.respuesta-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deseleccionarTodasRespuestas() {
    document.querySelectorAll('.respuesta-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function eliminarRespuestasSeleccionadas() {
    const seleccionadas = Array.from(document.querySelectorAll('.respuesta-checkbox:checked')).map(cb => cb.value);
    
    if (seleccionadas.length === 0) {
        alert('No hay respuestas seleccionadas');
        return;
    }
    
    if (!confirm(`¿Está seguro de que desea eliminar ${seleccionadas.length} respuestas? Esta acción no se puede deshacer.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'eliminar_respuestas_masivo');
    formData.append('respuestas_ids', JSON.stringify(seleccionadas));
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarRespuestasEncuesta(); // Recargar la lista
        } else {
            alert('Error al eliminar las respuestas: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al eliminar las respuestas');
    });
}

function mostrarContenidoRespuestas() {
    document.getElementById('tabs-respuestas').style.display = 'block';
    document.getElementById('contenido-tabs-respuestas').style.display = 'block';
    document.getElementById('sin-encuesta-seleccionada').style.display = 'none';
    document.getElementById('btn-actualizar-datos').style.display = 'inline-block';
}

function ocultarContenidoRespuestas() {
    document.getElementById('tabs-respuestas').style.display = 'none';
    document.getElementById('contenido-tabs-respuestas').style.display = 'none';
    document.getElementById('info-encuesta-seleccionada').style.display = 'none';
    document.getElementById('sin-encuesta-seleccionada').style.display = 'block';
    document.getElementById('btn-actualizar-datos').style.display = 'none';
}

// Resetear estado al abrir el modal
document.getElementById('modalGestionarRespuestas').addEventListener('shown.bs.modal', function () {
    paginaActual = 1;
    respuestasActuales = [];
    encuestaSeleccionada = null;
    
    // Limpiar selecciones
    document.getElementById('select-encuesta-respuestas').value = '';
    document.getElementById('filtro-estado-respuesta').value = '';
    document.getElementById('filtro-fecha-respuesta').value = '';
    
    ocultarContenidoRespuestas();
});
</script>