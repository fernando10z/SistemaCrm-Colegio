<!-- Modal para gestionar respuestas -->
<div class="modal fade" id="modalGestionarRespuestas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); border-bottom: 2px solid #FFCC80;">
                <div>
                    <h5 class="modal-title" style="color: #E65100; font-weight: 600;">
                        <i class="ti ti-messages"></i> Gestionar Respuestas de Encuesta
                    </h5>
                    <small class="text-muted">Visualiza, filtra y exporta las respuestas recibidas</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #FAFAFA;">
                <input type="hidden" id="gestionar_respuestas_encuesta_id">
                
                <!-- Filtros superiores -->
                <div class="card mb-3" style="border: 1px solid #FFE0B2; background-color: #FFF9F0;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label" style="color: #E65100; font-weight: 500;">Encuesta</label>
                                <select class="form-select" id="select-encuesta-respuestas" onchange="cargarRespuestasEncuesta()" 
                                        style="border: 1px solid #FFCC80; border-radius: 8px;">
                                    <option value="">Seleccionar encuesta</option>
                                    <?php
                                    $query_encuestas_resp = "SELECT e.id, e.titulo, e.tipo, e.dirigido_a, 
                                                                   COUNT(re.id) as total_respuestas
                                                           FROM encuestas e 
                                                           LEFT JOIN respuestas_encuesta re ON e.id = re.encuesta_id
                                                           GROUP BY e.id, e.titulo, e.tipo, e.dirigido_a
                                                           ORDER BY e.created_at DESC";
                                    $result_encuestas_resp = $conn->query($query_encuestas_resp);
                                    if ($result_encuestas_resp && $result_encuestas_resp->num_rows > 0) {
                                        while($encuesta = $result_encuestas_resp->fetch_assoc()) {
                                            echo "<option value='" . $encuesta['id'] . "' data-titulo='" . htmlspecialchars($encuesta['titulo']) . "'>" . 
                                                 htmlspecialchars($encuesta['titulo']) . " (" . $encuesta['total_respuestas'] . " respuestas)</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="color: #E65100; font-weight: 500;">Tipo de Usuario</label>
                                <select class="form-select" id="filtro-tipo-usuario" onchange="cargarRespuestasEncuesta()" 
                                        style="border: 1px solid #FFCC80; border-radius: 8px;">
                                    <option value="">Todos</option>
                                    <option value="padres">Padres</option>
                                    <option value="estudiantes">Estudiantes</option>
                                    <option value="exalumnos">Ex-alumnos</option>
                                    <option value="leads">Leads</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="color: #E65100; font-weight: 500;">Fecha</label>
                                <input type="date" class="form-control" id="filtro-fecha-respuesta" onchange="cargarRespuestasEncuesta()" 
                                       style="border: 1px solid #FFCC80; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info de encuesta seleccionada -->
                <div id="info-encuesta-seleccionada" style="display: none;">
                    <div class="card mb-3" style="border: 1px solid #B39DDB; background: linear-gradient(135deg, #EDE7F6 0%, #E1BEE7 100%);">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <h6 class="mb-1" style="color: #6A1B9A;" id="info-titulo-encuesta"></h6>
                                    <p class="mb-0 text-muted" id="info-descripcion-encuesta"></p>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge" style="background: linear-gradient(135deg, #CE93D8 0%, #BA68C8 100%); font-size: 1rem; padding: 8px 16px;" 
                                          id="info-total-respuestas">0 respuestas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="tabs-respuestas" role="tablist" style="display: none;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-lista-respuestas" data-bs-toggle="tab" 
                                data-bs-target="#panel-lista-respuestas" type="button" style="border-radius: 8px 8px 0 0;">
                            <i class="ti ti-list"></i> Lista de Respuestas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-respuesta-detalle" data-bs-toggle="tab" 
                                data-bs-target="#panel-respuesta-detalle" type="button" style="border-radius: 8px 8px 0 0;">
                            <i class="ti ti-file-text"></i> Detalle de Respuesta
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-exportar-datos" data-bs-toggle="tab" 
                                data-bs-target="#panel-exportar-datos" type="button" style="border-radius: 8px 8px 0 0;">
                            <i class="ti ti-download"></i> Exportar
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="contenido-tabs-respuestas" style="display: none;">
                    <!-- Panel Lista de Respuestas -->
                    <div class="tab-pane fade show active" id="panel-lista-respuestas" role="tabpanel">
                        <div class="card" style="border: 1px solid #E0E0E0; border-radius: 12px;">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tabla-respuestas">
                                        <thead style="background-color: #F5F5F5;">
                                            <tr>
                                                <th width="8%">
                                                    <input type="checkbox" class="form-check-input" id="check-todos" onchange="toggleTodosCheckboxes()">
                                                    ID
                                                </th>
                                                <th width="25%">Respondente</th>
                                                <th width="15%">Fecha</th>
                                                <th width="12%">Puntaje</th>
                                                <th width="12%">Tipo</th>
                                                <th width="13%">IP</th>
                                                <th width="15%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-respuestas"></tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginación -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <select class="form-select form-select-sm" id="respuestas-por-pagina" onchange="cambiarPorPagina()" 
                                                style="width: auto; border-radius: 8px;">
                                            <option value="10">10 por página</option>
                                            <option value="25" selected>25 por página</option>
                                            <option value="50">50 por página</option>
                                            <option value="100">100 por página</option>
                                        </select>
                                    </div>
                                    <div>
                                        <span id="texto-paginacion" class="text-muted">Mostrando 0 de 0</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline-secondary btn-sm" id="btn-pagina-anterior" onclick="cambiarPagina(-1)" 
                                                disabled style="border-radius: 8px;">
                                            <i class="ti ti-chevron-left"></i> Anterior
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" id="btn-pagina-siguiente" onclick="cambiarPagina(1)" 
                                                disabled style="border-radius: 8px;">
                                            Siguiente <i class="ti ti-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Detalle de Respuesta -->
                    <div class="tab-pane fade" id="panel-respuesta-detalle" role="tabpanel">
                        <div id="contenido-respuesta-detalle">
                            <div class="text-center py-5">
                                <i class="ti ti-file-search" style="font-size: 4rem; color: #BDBDBD;"></i>
                                <h5 class="text-muted mt-3">Selecciona una respuesta para ver el detalle</h5>
                                <p class="text-muted">Haz clic en "Ver Detalle" en la lista de respuestas</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Exportar Datos -->
                    <div class="tab-pane fade" id="panel-exportar-datos" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card" style="border: 1px solid #C8E6C9; border-radius: 12px;">
                                    <div class="card-header" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);">
                                        <h6 class="mb-0" style="color: #2E7D32;">
                                            <i class="ti ti-download"></i> Exportar Respuestas
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Formato de exportación</label>
                                            <select class="form-select" id="formato-exportacion" style="border-radius: 8px;">
                                                <option value="csv">CSV (.csv)</option>
                                                <option value="excel">Excel (.xlsx)</option>
                                            </select>
                                        </div>
                                        
                                        <button type="button" class="btn w-100" onclick="exportarRespuestas()" 
                                                style="background: linear-gradient(135deg, #81C784 0%, #66BB6A 100%); color: white; border: none; border-radius: 8px;">
                                            <i class="ti ti-download"></i> Exportar Datos
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card" style="border: 1px solid #FFCDD2; border-radius: 12px;">
                                    <div class="card-header" style="background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);">
                                        <h6 class="mb-0" style="color: #C62828;">
                                            <i class="ti ti-tool"></i> Acciones Masivas
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <button type="button" class="btn btn-outline-danger w-100" onclick="eliminarRespuestasSeleccionadas()" 
                                                style="border-radius: 8px;">
                                            <i class="ti ti-trash"></i> Eliminar Seleccionadas
                                        </button>
                                        
                                        <div class="alert alert-warning mt-3" style="border-radius: 8px;">
                                            <small>
                                                <i class="ti ti-alert-triangle me-1"></i>
                                                Las acciones no se pueden deshacer. Use con precaución.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sin encuesta seleccionada -->
                <div id="sin-encuesta-seleccionada" class="text-center py-5">
                    <i class="ti ti-clipboard-list" style="font-size: 4rem; color: #BDBDBD;"></i>
                    <h5 class="text-muted mt-3">Selecciona una encuesta para gestionar sus respuestas</h5>
                    <p class="text-muted">Podrás ver, filtrar y exportar las respuestas recibidas</p>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #F5F5F5;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                    Cerrar
                </button>
                <button type="button" class="btn btn-outline-info" id="btn-actualizar-datos" onclick="cargarRespuestasEncuesta()" 
                        style="display: none; border-radius: 8px;">
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

// Event listener para botones de gestionar respuestas
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-gestionar-resp')) {
        const btn = e.target.closest('.btn-gestionar-resp');
        const encuestaId = btn.getAttribute('data-id');
        abrirModalGestionRespuestas(encuestaId);
    }
});

function abrirModalGestionRespuestas(encuestaId) {
    // Preseleccionar la encuesta
    document.getElementById('select-encuesta-respuestas').value = encuestaId;
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalGestionarRespuestas'));
    modal.show();
    
    // Cargar respuestas
    setTimeout(() => {
        cargarRespuestasEncuesta();
    }, 300);
}

function cargarRespuestasEncuesta() {
    const encuestaId = document.getElementById('select-encuesta-respuestas').value;
    
    if (!encuestaId) {
        ocultarContenidoRespuestas();
        return;
    }
    
    const respuestasPorPagina = document.getElementById('respuestas-por-pagina').value;
    const filtroTipo = document.getElementById('filtro-tipo-usuario').value;
    const filtroFecha = document.getElementById('filtro-fecha-respuesta').value;
    
    const formData = new FormData();
    formData.append('accion', 'obtener_respuestas_encuesta');
    formData.append('encuesta_id', encuestaId);
    formData.append('pagina', paginaActual);
    formData.append('por_pagina', respuestasPorPagina);
    formData.append('filtro_tipo', filtroTipo);
    formData.append('filtro_fecha', filtroFecha);
    
    fetch('acciones/encuestas/gestionar_respuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            encuestaSeleccionada = data.encuesta;
            respuestasActuales = data.respuestas;
            totalPaginas = data.paginacion.total_paginas;
            
            mostrarInfoEncuesta(data.encuesta);
            mostrarRespuestas(data.respuestas);
            actualizarPaginacion(data.paginacion);
            mostrarContenidoRespuestas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje || 'Error al cargar las respuestas',
                confirmButtonColor: '#EF5350'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonColor: '#EF5350'
        });
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
        html = '<tr><td colspan="7" class="text-center py-5"><i class="ti ti-inbox" style="font-size: 3rem; color: #BDBDBD;"></i><br><span class="text-muted">No hay respuestas para mostrar</span></td></tr>';
    } else {
        respuestas.forEach(respuesta => {
            const puntaje = respuesta.puntaje_calculado ? 
                `<span class="badge" style="background: linear-gradient(135deg, #FFD54F 0%, #FFC107 100%); color: #F57F17;">${respuesta.puntaje_calculado}/5</span>` : 
                '<span class="text-muted">N/A</span>';
            
            const tipoBadge = getTipoBadge(respuesta.tipo_usuario);
            
            html += `
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input respuesta-checkbox me-2" value="${respuesta.id}">
                        <strong>#${respuesta.id}</strong>
                    </td>
                    <td>
                        <div>
                            <strong>${respuesta.nombre || 'Anónimo'}</strong>
                            ${respuesta.email ? `<br><small class="text-muted">${respuesta.email}</small>` : ''}
                        </div>
                    </td>
                    <td><small>${formatearFechaHora(respuesta.fecha_respuesta)}</small></td>
                    <td>${puntaje}</td>
                    <td>${tipoBadge}</td>
                    <td><small class="text-muted">${respuesta.ip_respuesta || 'N/A'}</small></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="verDetalleRespuesta(${respuesta.id})" 
                                    title="Ver Detalle" style="border-radius: 8px 0 0 8px;">
                                <i class="ti ti-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmarEliminarRespuesta(${respuesta.id})" 
                                    title="Eliminar" style="border-radius: 0 8px 8px 0;">
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

function getTipoBadge(tipo) {
    const badges = {
        'Padres': '<span class="badge" style="background-color: #81C784; color: white;">Padres</span>',
        'Estudiantes': '<span class="badge" style="background-color: #64B5F6; color: white;">Estudiantes</span>',
        'Ex-alumnos': '<span class="badge" style="background-color: #FFB74D; color: white;">Ex-alumnos</span>',
        'Leads': '<span class="badge" style="background-color: #E57373; color: white;">Leads</span>'
    };
    return badges[tipo] || '<span class="badge bg-secondary">Otro</span>';
}

function verDetalleRespuesta(respuestaId) {
    const formData = new FormData();
    formData.append('accion', 'obtener_detalle_respuesta');
    formData.append('respuesta_id', respuestaId);
    
    fetch('acciones/encuestas/gestionar_respuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarDetalleRespuesta(data.respuesta);
            document.getElementById('tab-respuesta-detalle').click();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje || 'Error al cargar el detalle',
                confirmButtonColor: '#EF5350'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function mostrarDetalleRespuesta(respuesta) {
    let html = `
        <div class="card" style="border: 1px solid #E0E0E0; border-radius: 12px;">
            <div class="card-header" style="background: linear-gradient(135deg, #E8EAF6 0%, #C5CAE9 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0" style="color: #3F51B5;">Respuesta #${respuesta.id}</h6>
                    <div>
                        <span class="badge bg-primary">${formatearFechaHora(respuesta.fecha_respuesta)}</span>
                        ${respuesta.puntaje_calculado ? `<span class="badge" style="background-color: #FFC107; color: #F57F17;">${respuesta.puntaje_calculado}/5</span>` : ''}
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>Respondente:</strong><br>
                        <span class="text-muted">${respuesta.nombre || 'Anónimo'}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Email:</strong><br>
                        <span class="text-muted">${respuesta.email || 'No proporcionado'}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Tipo:</strong><br>
                        ${getTipoBadge(respuesta.tipo_usuario)}
                    </div>
                </div>
                
                <hr>
                
                <h6 class="mb-3" style="color: #5C6BC0;">Respuestas Detalladas:</h6>
    `;
    
    if (respuesta.respuestas_detalle && respuesta.respuestas_detalle.length > 0) {
        respuesta.respuestas_detalle.forEach((item, index) => {
            const respuestaTexto = Array.isArray(item.respuesta) ? item.respuesta.join(', ') : item.respuesta;
            html += `
                <div class="mb-4" style="padding-left: 15px; border-left: 3px solid #B39DDB;">
                    <div class="fw-bold mb-2" style="color: #6A1B9A;">Pregunta ${index + 1}</div>
                    <div class="text-muted mb-1">${item.pregunta}</div>
                    <div class="alert" style="background-color: #F3E5F5; border: 1px solid #E1BEE7; color: #6A1B9A;">
                        ${respuestaTexto || '<em>Sin respuesta</em>'}
                    </div>
                </div>
            `;
        });
    } else {
        html += '<p class="text-muted text-center py-4">No hay respuestas detalladas disponibles.</p>';
    }
    
    html += '</div></div>';
    
    document.getElementById('contenido-respuesta-detalle').innerHTML = html;
}

function confirmarEliminarRespuesta(respuestaId) {
    Swal.fire({
        title: '¿Eliminar respuesta?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF5350',
        cancelButtonColor: '#B0BEC5',
        confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            eliminarRespuesta(respuestaId);
        }
    });
}

function eliminarRespuesta(respuestaId) {
    const formData = new FormData();
    formData.append('accion', 'eliminar_respuesta');
    formData.append('respuesta_id', respuestaId);
    
    fetch('acciones/encuestas/gestionar_respuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Eliminada!',
                text: 'La respuesta ha sido eliminada',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
            cargarRespuestasEncuesta();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje || 'Error al eliminar'
            });
        }
    });
}

function eliminarRespuestasSeleccionadas() {
    const seleccionadas = Array.from(document.querySelectorAll('.respuesta-checkbox:checked')).map(cb => cb.value);
    
    if (seleccionadas.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'No hay respuestas seleccionadas'
        });
        return;
    }
    
    Swal.fire({
        title: `¿Eliminar ${seleccionadas.length} respuestas?`,
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF5350',
        cancelButtonColor: '#B0BEC5',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('accion', 'eliminar_respuestas_masivo');
            formData.append('respuestas_ids', JSON.stringify(seleccionadas));
            
            fetch('acciones/encuestas/gestionar_respuestas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminadas!',
                        text: `${seleccionadas.length} respuestas eliminadas`,
                        timer: 2000
                    });
                    cargarRespuestasEncuesta();
                }
            });
        }
    });
}

function exportarRespuestas() {
    const encuestaId = document.getElementById('select-encuesta-respuestas').value;
    const formato = document.getElementById('formato-exportacion').value;
    
    if (!encuestaId) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Selecciona una encuesta primero'
        });
        return;
    }
    
    Swal.fire({
        title: 'Exportando...',
        html: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Usar el mismo exportador que ya creamos
    window.open(`acciones/encuestas/exportar_resultados.php?id=${encuestaId}`, '_blank');
    
    setTimeout(() => {
        Swal.close();
    }, 1000);
}

function actualizarPaginacion(paginacion) {
    document.getElementById('texto-paginacion').textContent = 
        `Mostrando ${paginacion.desde} a ${paginacion.hasta} de ${paginacion.total}`;
    
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

function cambiarPorPagina() {
    paginaActual = 1;
    cargarRespuestasEncuesta();
}

function toggleTodosCheckboxes() {
    const checkTodos = document.getElementById('check-todos');
    document.querySelectorAll('.respuesta-checkbox').forEach(cb => {
        cb.checked = checkTodos.checked;
    });
}

function formatearFechaHora(fechaHora) {
    if (!fechaHora) return '-';
    const fecha = new Date(fechaHora);
    return fecha.toLocaleString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function mostrarContenidoRespuestas() {
    document.getElementById('tabs-respuestas').style.display = 'flex';
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

// Resetear al abrir modal
document.getElementById('modalGestionarRespuestas').addEventListener('shown.bs.modal', function () {
    if (!document.getElementById('select-encuesta-respuestas').value) {
        paginaActual = 1;
        respuestasActuales = [];
        encuestaSeleccionada = null;
        ocultarContenidoRespuestas();
    }
});

// Resetear al cerrar modal
document.getElementById('modalGestionarRespuestas').addEventListener('hidden.bs.modal', function () {
    paginaActual = 1;
    respuestasActuales = [];
    encuestaSeleccionada = null;
    document.getElementById('select-encuesta-respuestas').value = '';
    document.getElementById('filtro-tipo-usuario').value = '';
    document.getElementById('filtro-fecha-respuesta').value = '';
    document.getElementById('check-todos').checked = false;
    ocultarContenidoRespuestas();
});
</script>