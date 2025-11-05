<!-- INCLUIR EN HEAD -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Modal Análisis de Resultados -->
<div class="modal fade" id="modalAnalisisEncuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #E8EAF6 0%, #C5CAE9 100%); border-bottom: 2px solid #9FA8DA;">
                <div>
                    <h5 class="modal-title" style="color: #3F51B5; font-weight: 600;">
                        <i class="ti ti-chart-line"></i> Análisis de Resultados
                    </h5>
                    <small class="text-muted" id="subtituloAnalisis"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #FAFAFA;">
                
                <!-- Loading -->
                <div id="loadingAnalisis" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Procesando respuestas...</p>
                </div>

                <!-- Contenido Principal -->
                <div id="contenidoAnalisis" style="display: none;">
                    
                    <!-- Estadísticas Generales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card" style="border: 2px solid #C8E6C9; background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);">
                                <div class="card-body text-center">
                                    <i class="ti ti-users" style="font-size: 2.5rem; color: #4CAF50;"></i>
                                    <h2 class="mt-2 mb-0" id="totalRespuestas" style="color: #2E7D32;">0</h2>
                                    <p class="text-muted mb-0">Total Respuestas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="border: 2px solid #B39DDB; background: linear-gradient(135deg, #EDE7F6 0%, #D1C4E9 100%);">
                                <div class="card-body text-center">
                                    <i class="ti ti-calendar" style="font-size: 2.5rem; color: #673AB7;"></i>
                                    <h2 class="mt-2 mb-0" id="periodoEncuesta" style="color: #512DA8;">-</h2>
                                    <p class="text-muted mb-0">Periodo Activo</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="border: 2px solid #FFCCBC; background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);">
                                <div class="card-body text-center">
                                    <i class="ti ti-star" style="font-size: 2.5rem; color: #FF9800;"></i>
                                    <h2 class="mt-2 mb-0" id="promedioGeneral" style="color: #E65100;">0.0</h2>
                                    <p class="text-muted mb-0">Promedio General</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="border: 2px solid #B2DFDB; background: linear-gradient(135deg, #E0F2F1 0%, #B2DFDB 100%);">
                                <div class="card-body text-center">
                                    <i class="ti ti-list-check" style="font-size: 2.5rem; color: #009688;"></i>
                                    <h2 class="mt-2 mb-0" id="totalPreguntas" style="color: #00695C;">0</h2>
                                    <p class="text-muted mb-0">Preguntas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Distribución por Tipo de Usuario -->
                    <div class="card mb-4" style="border: 1px solid #E0E0E0; border-radius: 12px;">
                        <div class="card-header" style="background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%); border-bottom: 2px solid #CE93D8;">
                            <h6 class="mb-0" style="color: #7B1FA2;">
                                <i class="ti ti-chart-pie"></i> Distribución de Respuestas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="chartDistribucion" height="200"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th class="text-center">Cantidad</th>
                                                    <th class="text-center">%</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDistribucion"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Análisis por Pregunta -->
                    <div class="card" style="border: 1px solid #E0E0E0; border-radius: 12px;">
                        <div class="card-header" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); border-bottom: 2px solid #81C784;">
                            <h6 class="mb-0" style="color: #388E3C;">
                                <i class="ti ti-message-circle"></i> Análisis Detallado por Pregunta
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="contenedorPreguntas"></div>
                        </div>
                    </div>

                </div>

                <!-- Sin Respuestas -->
                <div id="sinRespuestas" style="display: none;" class="text-center py-5">
                    <i class="ti ti-inbox" style="font-size: 5rem; color: #BDBDBD;"></i>
                    <h4 class="mt-3 text-muted">Sin respuestas aún</h4>
                    <p class="text-muted">Esta encuesta aún no ha recibido respuestas</p>
                </div>

            </div>
            <div class="modal-footer" style="background-color: #F5F5F5; border-top: 2px solid #E0E0E0;">
                <button type="button" class="btn" onclick="exportarResultados()" 
                        style="background: linear-gradient(135deg, #90CAF9 0%, #64B5F6 100%); color: white; border: none; border-radius: 8px;">
                    <i class="ti ti-download"></i> Exportar Resultados
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.pregunta-analisis {
    border: 2px solid #E0E0E0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    background-color: white;
    transition: all 0.3s ease;
}

.pregunta-analisis:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #B39DDB;
}

.respuesta-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    background-color: #FAFAFA;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.respuesta-item:hover {
    background-color: #F0F0F0;
}

.barra-progreso {
    height: 25px;
    border-radius: 8px;
    overflow: hidden;
    background-color: #E0E0E0;
}

.barra-fill {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 500;
    font-size: 0.85rem;
    transition: width 0.6s ease;
}
</style>

<script>
let chartInstances = {};
let currentEncuestaId = null;

// Función principal para abrir análisis
function abrirAnalisisEncuesta(encuestaId) {
    currentEncuestaId = encuestaId;
    
    // Resetear modal
    document.getElementById('loadingAnalisis').style.display = 'block';
    document.getElementById('contenidoAnalisis').style.display = 'none';
    document.getElementById('sinRespuestas').style.display = 'none';
    
    // Destruir gráficos anteriores
    Object.values(chartInstances).forEach(chart => chart.destroy());
    chartInstances = {};
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalAnalisisEncuesta'));
    modal.show();
    
    // Cargar datos
    cargarAnalisis(encuestaId);
}

// Cargar datos del análisis
async function cargarAnalisis(encuestaId) {
    try {
        const response = await fetch(`acciones/encuestas/analizar_encuesta.php?id=${encuestaId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al cargar análisis');
        }
        
        if (data.data.total_respuestas === 0) {
            document.getElementById('loadingAnalisis').style.display = 'none';
            document.getElementById('sinRespuestas').style.display = 'block';
            return;
        }
        
        // Renderizar análisis
        renderizarAnalisis(data.data);
        
        document.getElementById('loadingAnalisis').style.display = 'none';
        document.getElementById('contenidoAnalisis').style.display = 'block';
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el análisis: ' + error.message,
            confirmButtonColor: '#EF5350'
        });
        bootstrap.Modal.getInstance(document.getElementById('modalAnalisisEncuesta')).hide();
    }
}

// Renderizar todo el análisis
function renderizarAnalisis(data) {
    // Título
    document.getElementById('subtituloAnalisis').textContent = data.encuesta.titulo;
    
    // Estadísticas generales
    document.getElementById('totalRespuestas').textContent = data.total_respuestas;
    document.getElementById('periodoEncuesta').textContent = 
        `${formatearFecha(data.encuesta.fecha_inicio)} - ${data.encuesta.fecha_fin ? formatearFecha(data.encuesta.fecha_fin) : 'Activa'}`;
    document.getElementById('promedioGeneral').textContent = data.promedio_general.toFixed(1);
    document.getElementById('totalPreguntas').textContent = data.preguntas_analisis.length;
    
    // Gráfico de distribución
    renderizarDistribucion(data.distribucion_usuarios);
    
    // Tabla de distribución
    renderizarTablaDistribucion(data.distribucion_usuarios, data.total_respuestas);
    
    // Análisis por pregunta
    renderizarPreguntas(data.preguntas_analisis);
}

// Gráfico de distribución
function renderizarDistribucion(distribucion) {
    const ctx = document.getElementById('chartDistribucion').getContext('2d');
    
    const colores = {
        'Padres': '#81C784',
        'Estudiantes': '#64B5F6',
        'Ex-alumnos': '#FFB74D',
        'Leads': '#E57373'
    };
    
    const labels = Object.keys(distribucion);
    const valores = Object.values(distribucion);
    const backgroundColors = labels.map(label => colores[label] || '#BDBDBD');
    
    chartInstances['distribucion'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: backgroundColors,
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${porcentaje}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Tabla de distribución
function renderizarTablaDistribucion(distribucion, total) {
    const tbody = document.getElementById('tablaDistribucion');
    tbody.innerHTML = '';
    
    for (const [tipo, cantidad] of Object.entries(distribucion)) {
        const porcentaje = ((cantidad / total) * 100).toFixed(1);
        tbody.innerHTML += `
            <tr>
                <td>${tipo}</td>
                <td class="text-center"><strong>${cantidad}</strong></td>
                <td class="text-center">${porcentaje}%</td>
            </tr>
        `;
    }
}

// Renderizar análisis de preguntas
function renderizarPreguntas(preguntas) {
    const contenedor = document.getElementById('contenedorPreguntas');
    contenedor.innerHTML = '';
    
    preguntas.forEach((pregunta, index) => {
        const html = crearHTMLPregunta(pregunta, index);
        contenedor.innerHTML += html;
    });
    
    // Crear gráficos
    setTimeout(() => {
        preguntas.forEach((pregunta, index) => {
            if (['select', 'radio', 'checkbox', 'escala', 'rating', 'si_no'].includes(pregunta.tipo)) {
                crearGraficoPregunta(pregunta, index);
            }
        });
    }, 100);
}

// HTML de cada pregunta
function crearHTMLPregunta(pregunta, index) {
    let contenido = '';
    
    if (pregunta.tipo === 'text') {
        // Texto libre - Mostrar respuestas
        contenido = `
            <div class="respuestas-texto" style="max-height: 300px; overflow-y: auto;">
                ${pregunta.respuestas.slice(0, 10).map(r => `
                    <div class="alert alert-light" style="border-left: 4px solid #9FA8DA; margin-bottom: 10px;">
                        ${r}
                    </div>
                `).join('')}
                ${pregunta.respuestas.length > 10 ? `
                    <p class="text-muted text-center">Y ${pregunta.respuestas.length - 10} respuestas más...</p>
                ` : ''}
            </div>
        `;
    } else {
        // Opciones múltiples - Gráfico y barras
        contenido = `
            <div class="row">
                <div class="col-md-6">
                    <canvas id="chartPregunta${index}" height="250"></canvas>
                </div>
                <div class="col-md-6">
                    ${Object.entries(pregunta.estadisticas).map(([opcion, datos]) => `
                        <div class="respuesta-item">
                            <div style="flex: 1;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="font-weight: 500; color: #424242;">${opcion}</span>
                                    <span style="color: #757575;">${datos.cantidad} (${datos.porcentaje.toFixed(1)}%)</span>
                                </div>
                                <div class="barra-progreso">
                                    <div class="barra-fill" style="width: ${datos.porcentaje}%; background: ${obtenerColorBarra(datos.porcentaje)};">
                                        ${datos.porcentaje >= 10 ? datos.porcentaje.toFixed(0) + '%' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    return `
        <div class="pregunta-analisis">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 style="color: #5C6BC0; flex: 1;">
                    <span class="badge" style="background-color: #E8EAF6; color: #3F51B5;">P${index + 1}</span>
                    ${pregunta.pregunta}
                </h6>
                <span class="badge" style="background: linear-gradient(135deg, #FFE082 0%, #FFD54F 100%); color: #F57F17;">
                    ${pregunta.total_respuestas} respuestas
                </span>
            </div>
            ${contenido}
        </div>
    `;
}

// Crear gráfico de pregunta
function crearGraficoPregunta(pregunta, index) {
    const ctx = document.getElementById(`chartPregunta${index}`);
    if (!ctx) return;
    
    const labels = Object.keys(pregunta.estadisticas);
    const valores = Object.values(pregunta.estadisticas).map(e => e.cantidad);
    const colores = generarColoresPastel(labels.length);
    
    chartInstances[`pregunta${index}`] = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Respuestas',
                data: valores,
                backgroundColor: colores,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((context.parsed.y / total) * 100).toFixed(1);
                            return `${context.parsed.y} respuestas (${porcentaje}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

// Funciones auxiliares
function obtenerColorBarra(porcentaje) {
    if (porcentaje >= 75) return 'linear-gradient(90deg, #66BB6A 0%, #81C784 100%)';
    if (porcentaje >= 50) return 'linear-gradient(90deg, #42A5F5 0%, #64B5F6 100%)';
    if (porcentaje >= 25) return 'linear-gradient(90deg, #FFA726 0%, #FFB74D 100%)';
    return 'linear-gradient(90deg, #EF5350 0%, #E57373 100%)';
}

function generarColoresPastel(cantidad) {
    const colores = [
        '#81C784', '#64B5F6', '#FFB74D', '#E57373', '#BA68C8',
        '#4DB6AC', '#FFD54F', '#FF8A65', '#9575CD', '#4DD0E1'
    ];
    return colores.slice(0, cantidad);
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}

function exportarResultados() {
    if (!currentEncuestaId) return;
    window.open(`acciones/encuestas/exportar_resultados.php?id=${currentEncuestaId}`, '_blank');
}

// Event listener para botones de análisis
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-analizar')) {
        const btn = e.target.closest('.btn-analizar');
        const encuestaId = btn.getAttribute('data-id');
        abrirAnalisisEncuesta(encuestaId);
    }
});
</script>