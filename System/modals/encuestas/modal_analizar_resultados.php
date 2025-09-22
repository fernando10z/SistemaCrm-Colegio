<!-- Modal para analizar resultados -->
<div class="modal fade" id="modalAnalizarResultados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Análisis de Resultados de Encuestas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Seleccionar Encuesta</label>
                        <select class="form-select" id="select-encuesta-analisis" onchange="cargarAnalisisEncuesta()">
                            <option value="">Seleccionar encuesta para analizar</option>
                            <?php
                            // Obtener encuestas con respuestas
                            $query_encuestas = "SELECT e.id, e.titulo, e.tipo, COUNT(re.id) as total_respuestas
                                              FROM encuestas e 
                                              LEFT JOIN respuestas_encuesta re ON e.id = re.encuesta_id
                                              GROUP BY e.id, e.titulo, e.tipo
                                              HAVING total_respuestas > 0
                                              ORDER BY e.created_at DESC";
                            $result_encuestas = $conn->query($query_encuestas);
                            if ($result_encuestas && $result_encuestas->num_rows > 0) {
                                while($encuesta = $result_encuestas->fetch_assoc()) {
                                    echo "<option value='" . $encuesta['id'] . "'>" . 
                                         htmlspecialchars($encuesta['titulo']) . " (" . $encuesta['total_respuestas'] . " respuestas)</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Filtrar por Fecha</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="date" class="form-control" id="fecha-desde" onchange="cargarAnalisisEncuesta()">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" id="fecha-hasta" onchange="cargarAnalisisEncuesta()">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="contenido-analisis" style="display: none;">
                    <!-- Estadísticas Generales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary mb-1" id="stat-total-respuestas">0</h3>
                                    <p class="text-muted mb-0">Total Respuestas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success mb-1" id="stat-promedio-puntaje">0</h3>
                                    <p class="text-muted mb-0">Puntaje Promedio</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-info mb-1" id="stat-tasa-respuesta">0%</h3>
                                    <p class="text-muted mb-0">Tasa de Respuesta</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-warning mb-1" id="stat-satisfaccion">0%</h3>
                                    <p class="text-muted mb-0">Satisfacción General</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs para diferentes análisis -->
                    <ul class="nav nav-tabs" id="tabs-analisis" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-resumen" data-bs-toggle="tab" data-bs-target="#panel-resumen" type="button">
                                Resumen Ejecutivo
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-preguntas" data-bs-toggle="tab" data-bs-target="#panel-preguntas" type="button">
                                Análisis por Pregunta
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-tendencias" data-bs-toggle="tab" data-bs-target="#panel-tendencias" type="button">
                                Tendencias
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-comentarios" data-bs-toggle="tab" data-bs-target="#panel-comentarios" type="button">
                                Comentarios
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="contenido-tabs-analisis">
                        <!-- Panel Resumen Ejecutivo -->
                        <div class="tab-pane fade show active" id="panel-resumen" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Distribución de Respuestas</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="grafico-distribucion" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Indicadores Clave</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="indicadores-clave">
                                                <!-- Se llenará dinámicamente -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Panel Análisis por Pregunta -->
                        <div class="tab-pane fade" id="panel-preguntas" role="tabpanel">
                            <div id="analisis-preguntas">
                                <!-- Se llenará dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Panel Tendencias -->
                        <div class="tab-pane fade" id="panel-tendencias" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Evolución Temporal de Respuestas</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="grafico-tendencias" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Panel Comentarios -->
                        <div class="tab-pane fade" id="panel-comentarios" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Comentarios y Respuestas Abiertas</h6>
                                        <select class="form-select form-select-sm" style="width: auto;" id="filtro-sentimiento">
                                            <option value="">Todos los comentarios</option>
                                            <option value="positivo">Comentarios Positivos</option>
                                            <option value="neutral">Comentarios Neutrales</option>
                                            <option value="negativo">Comentarios Negativos</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="lista-comentarios" style="max-height: 400px; overflow-y: auto;">
                                        <!-- Se llenará dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="sin-seleccion" class="text-center py-5">
                    <i class="ti ti-chart-line" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="text-muted mt-3">Selecciona una encuesta para ver el análisis</h5>
                    <p class="text-muted">Los resultados y estadísticas aparecerán aquí</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-success" onclick="exportarAnalisis()" id="btn-exportar" style="display: none;">
                    <i class="ti ti-download"></i> Exportar Reporte
                </button>
                <button type="button" class="btn btn-primary" onclick="imprimirAnalisis()" id="btn-imprimir" style="display: none;">
                    <i class="ti ti-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let datosAnalisisActual = null;

function cargarAnalisisEncuesta() {
    const encuestaId = document.getElementById('select-encuesta-analisis').value;
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;
    
    if (!encuestaId) {
        document.getElementById('contenido-analisis').style.display = 'none';
        document.getElementById('sin-seleccion').style.display = 'block';
        document.getElementById('btn-exportar').style.display = 'none';
        document.getElementById('btn-imprimir').style.display = 'none';
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'obtener_analisis_encuesta');
    formData.append('encuesta_id', encuestaId);
    if (fechaDesde) formData.append('fecha_desde', fechaDesde);
    if (fechaHasta) formData.append('fecha_hasta', fechaHasta);
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            datosAnalisisActual = data.datos;
            mostrarAnalisis(data.datos);
            document.getElementById('contenido-analisis').style.display = 'block';
            document.getElementById('sin-seleccion').style.display = 'none';
            document.getElementById('btn-exportar').style.display = 'inline-block';
            document.getElementById('btn-imprimir').style.display = 'inline-block';
        } else {
            alert('Error al cargar el análisis: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al cargar el análisis');
    });
}

function mostrarAnalisis(datos) {
    // Actualizar estadísticas generales
    document.getElementById('stat-total-respuestas').textContent = datos.estadisticas.total_respuestas || 0;
    document.getElementById('stat-promedio-puntaje').textContent = (datos.estadisticas.promedio_puntaje || 0).toFixed(1);
    document.getElementById('stat-tasa-respuesta').textContent = (datos.estadisticas.tasa_respuesta || 0) + '%';
    document.getElementById('stat-satisfaccion').textContent = (datos.estadisticas.satisfaccion_general || 0) + '%';
    
    // Crear indicadores clave
    const indicadores = document.getElementById('indicadores-clave');
    indicadores.innerHTML = `
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <span>Respuestas Completas:</span>
                <strong>${datos.estadisticas.respuestas_completas || 0}</strong>
            </div>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <span>Tiempo Promedio:</span>
                <strong>${datos.estadisticas.tiempo_promedio || 'N/A'}</strong>
            </div>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <span>Última Respuesta:</span>
                <strong>${datos.estadisticas.ultima_respuesta || 'N/A'}</strong>
            </div>
        </div>
    `;
    
    // Mostrar análisis por preguntas
    mostrarAnalisisPreguntas(datos.preguntas || []);
    
    // Mostrar comentarios
    mostrarComentarios(datos.comentarios || []);
}

function mostrarAnalisisPreguntas(preguntas) {
    const contenedor = document.getElementById('analisis-preguntas');
    let html = '';
    
    preguntas.forEach((pregunta, index) => {
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Pregunta ${index + 1}: ${pregunta.texto}</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="grafico-pregunta-${index}" width="400" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="estadisticas-pregunta">
                                <p><strong>Respuestas:</strong> ${pregunta.total_respuestas}</p>
                                <p><strong>Tipo:</strong> ${pregunta.tipo}</p>
                                ${pregunta.promedio ? `<p><strong>Promedio:</strong> ${pregunta.promedio}</p>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    contenedor.innerHTML = html;
}

function mostrarComentarios(comentarios) {
    const contenedor = document.getElementById('lista-comentarios');
    let html = '';
    
    if (comentarios.length === 0) {
        html = '<p class="text-muted text-center">No hay comentarios para mostrar</p>';
    } else {
        comentarios.forEach(comentario => {
            const sentimientoClass = comentario.sentimiento === 'positivo' ? 'text-success' : 
                                   comentario.sentimiento === 'negativo' ? 'text-danger' : 'text-muted';
            
            html += `
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="mb-1">"${comentario.texto}"</p>
                            <small class="text-muted">
                                ${comentario.fecha} - ${comentario.respondente || 'Anónimo'}
                            </small>
                        </div>
                        <span class="badge ${sentimientoClass === 'text-success' ? 'bg-success' : 
                                            sentimientoClass === 'text-danger' ? 'bg-danger' : 'bg-secondary'}">
                            ${comentario.sentimiento || 'Neutral'}
                        </span>
                    </div>
                </div>
            `;
        });
    }
    
    contenedor.innerHTML = html;
}

function exportarAnalisis() {
    if (!datosAnalisisActual) {
        alert('No hay datos para exportar');
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'exportar_analisis');
    formData.append('datos', JSON.stringify(datosAnalisisActual));
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `analisis_encuesta_${new Date().getTime()}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al exportar el análisis');
    });
}

function imprimirAnalisis() {
    window.print();
}

// Filtro de comentarios por sentimiento
document.getElementById('filtro-sentimiento').addEventListener('change', function() {
    if (!datosAnalisisActual) return;
    
    const filtro = this.value;
    const comentariosFiltrados = datosAnalisisActual.comentarios.filter(comentario => {
        return !filtro || comentario.sentimiento === filtro;
    });
    
    mostrarComentarios(comentariosFiltrados);
});
</script>