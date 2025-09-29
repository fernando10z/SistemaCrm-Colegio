// Variables globales
let chartsInstances = {};

// Inicializar al cargar la página
$(document).ready(function() {
    // Configurar evento de cambio de rango de fechas
    $('#rangoFechas').on('change', function() {
        if ($(this).val() === 'personalizado') {
            $('#fechaInicioContainer, #fechaFinContainer').show();
        } else {
            $('#fechaInicioContainer, #fechaFinContainer').hide();
        }
    });
    
    // Cargar datos iniciales
    cargarDashboard();
});

// Función principal para cargar el dashboard
function cargarDashboard() {
    mostrarLoading(true);
    
    const params = obtenerParametrosFiltro();
    
    $.ajax({
        url: 'acciones/dashboard_area/cargar_datos_dashboard.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                renderizarDashboard(data);
            } else {
                mostrarError('Error al cargar datos: ' + (data.message || 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            mostrarError('Error de conexión al cargar el dashboard');
        },
        complete: function() {
            mostrarLoading(false);
        }
    });
}

// Obtener parámetros de filtro
function obtenerParametrosFiltro() {
    const params = {
        area: $('#areaFiltro').val(),
        rango: $('#rangoFechas').val()
    };
    
    if (params.rango === 'personalizado') {
        params.fecha_inicio = $('#fechaInicio').val();
        params.fecha_fin = $('#fechaFin').val();
        
        if (!params.fecha_inicio || !params.fecha_fin) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas Requeridas',
                text: 'Por favor seleccione las fechas de inicio y fin'
            });
            return null;
        }
    }
    
    return params;
}

// Renderizar todo el dashboard
function renderizarDashboard(data) {
    // Limpiar gráficos anteriores
    destruirGraficos();
    
    // Renderizar según el área seleccionada
    const area = data.filtros.area;
    
    if (area === 'todas' || area === 'captacion') {
        renderizarCaptacion(data.captacion);
    }
    
    if (area === 'todas' || area === 'familias') {
        renderizarFamilias(data.familias);
    }
    
    if (area === 'todas' || area === 'finanzas') {
        renderizarFinanzas(data.finanzas);
    }
    
    if (area === 'todas' || area === 'seguimiento') {
        renderizarSeguimiento(data.seguimiento);
    }
}

// ==========================================
// RENDERIZAR CAPTACIÓN
// ==========================================
function renderizarCaptacion(datos) {
    let html = `
        <div class="col-12">
            <h5 class="section-title">
                <i class="ti ti-users"></i>
                Métricas de Captación
            </h5>
        </div>
        
        <!-- Tarjetas de métricas -->
        <div class="col-md-3">
            <div class="metric-card blue">
                <div>
                    <i class="ti ti-user-plus metric-icon"></i>
                    <div class="metric-label">Total de Leads</div>
                </div>
                <div class="metric-value">${datos.total_leads || 0}</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card green">
                <div>
                    <i class="ti ti-user-check metric-icon"></i>
                    <div class="metric-label">Convertidos</div>
                </div>
                <div class="metric-value">${datos.convertidos || 0}</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card purple">
                <div>
                    <i class="ti ti-percentage metric-icon"></i>
                    <div class="metric-label">Tasa de Conversión</div>
                </div>
                <div class="metric-value">${datos.tasa_conversion || 0}%</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card yellow">
                <div>
                    <i class="ti ti-chart-line metric-icon"></i>
                    <div class="metric-label">Por Estado</div>
                </div>
                <div class="metric-value">${(datos.leads_por_estado || []).length || 0}</div>
            </div>
        </div>
    `;
    
    // Gráficos
    html += `
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Leads por Estado</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartLeadsEstado"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Leads por Canal de Captación</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartLeadsCanal"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#metricsContainer').html(html);
    
    // Crear gráficos
    crearGraficoLeadsEstado(datos.leads_por_estado || []);
    crearGraficoLeadsCanal(datos.leads_por_canal || []);
}

// ==========================================
// RENDERIZAR FAMILIAS
// ==========================================
function renderizarFamilias(datos) {
    let html = `
        <div class="col-12">
            <h5 class="section-title">
                <i class="ti ti-home"></i>
                Métricas de Familias y Estudiantes
            </h5>
        </div>
        
        <div class="col-md-4">
            <div class="metric-card teal">
                <div>
                    <i class="ti ti-home-2 metric-icon"></i>
                    <div class="metric-label">Familias Activas</div>
                </div>
                <div class="metric-value">${datos.total_familias || 0}</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="metric-card blue">
                <div>
                    <i class="ti ti-school metric-icon"></i>
                    <div class="metric-label">Estudiantes Matriculados</div>
                </div>
                <div class="metric-value">${datos.total_estudiantes || 0}</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="metric-card pink">
                <div>
                    <i class="ti ti-users metric-icon"></i>
                    <div class="metric-label">Apoderados Activos</div>
                </div>
                <div class="metric-value">${datos.total_apoderados || 0}</div>
            </div>
        </div>
    `;
    
    html += `
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Estudiantes por Grado</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartEstudiantesGrado"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Compromiso de Apoderados</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartCompromisoApoderados"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#metricsContainer').append(html);
    
    crearGraficoEstudiantesGrado(datos.estudiantes_por_grado || []);
    crearGraficoCompromisoApoderados(datos.compromiso_apoderados || []);
}

// ==========================================
// RENDERIZAR FINANZAS
// ==========================================
function renderizarFinanzas(datos) {
    let html = `
        <div class="col-12">
            <h5 class="section-title">
                <i class="ti ti-cash"></i>
                Métricas Financieras
            </h5>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card green">
                <div>
                    <i class="ti ti-currency-dollar metric-icon"></i>
                    <div class="metric-label">Total Facturado</div>
                </div>
                <div class="metric-value">S/ ${formatearNumero(datos.total_facturado || 0)}</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card blue">
                <div>
                    <i class="ti ti-checkupn-list metric-icon"></i>
                    <div class="metric-label">Total Pagado</div>
                </div>
                <div class="metric-value">S/ ${formatearNumero(datos.total_pagado || 0)}</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card red">
                <div>
                    <i class="ti ti-clock-hour-4 metric-icon"></i>
                    <div class="metric-label">Total Pendiente</div>
                </div>
                <div class="metric-value">S/ ${formatearNumero(datos.total_pendiente || 0)}</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card purple">
                <div>
                    <i class="ti ti-percentage metric-icon"></i>
                    <div class="metric-label">Tasa de Cobranza</div>
                </div>
                <div class="metric-value">${datos.tasa_cobranza || 0}%</div>
            </div>
        </div>
    `;
    
    html += `
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Cuentas por Estado</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartCuentasEstado"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Pagos por Método</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartPagosMetodo"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#metricsContainer').append(html);
    
    crearGraficoCuentasEstado(datos.cuentas_por_estado || []);
    crearGraficoPagosMetodo(datos.pagos_por_metodo || []);
}

// ==========================================
// RENDERIZAR SEGUIMIENTO
// ==========================================
function renderizarSeguimiento(datos) {
    let html = `
        <div class="col-12">
            <h5 class="section-title">
                <i class="ti ti-chart-dots"></i>
                Métricas de Seguimiento
            </h5>
        </div>
        
        <div class="col-md-12">
            <div class="metric-card orange">
                <div>
                    <i class="ti ti-messages metric-icon"></i>
                    <div class="metric-label">Total de Interacciones</div>
                </div>
                <div class="metric-value">${datos.total_interacciones || 0}</div>
            </div>
        </div>
    `;
    
    html += `
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Interacciones por Tipo</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartInteraccionesTipo"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h6 class="mb-0">Interacciones por Estado</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartInteraccionesEstado"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#metricsContainer').append(html);
    
    crearGraficoInteraccionesTipo(datos.interacciones_por_tipo || []);
    crearGraficoInteraccionesEstado(datos.interacciones_por_estado || []);
}

// ==========================================
// FUNCIONES DE GRÁFICOS
// ==========================================
function crearGraficoLeadsEstado(datos) {
    const ctx = document.getElementById('chartLeadsEstado');
    if (!ctx) return;
    
    const labels = datos.map(d => d.estado);
    const values = datos.map(d => d.cantidad);
    const colors = datos.map(d => d.color || '#A8DADC');
    
    chartsInstances['leadsEstado'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function crearGraficoLeadsCanal(datos) {
    const ctx = document.getElementById('chartLeadsCanal');
    if (!ctx) return;
    
    chartsInstances['leadsCanal'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.canal),
            datasets: [{
                label: 'Cantidad de Leads',
                data: datos.map(d => d.cantidad),
                backgroundColor: '#B8E6B8',
                borderColor: '#90C990',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function crearGraficoEstudiantesGrado(datos) {
    const ctx = document.getElementById('chartEstudiantesGrado');
    if (!ctx) return;
    
    chartsInstances['estudiantesGrado'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.grado),
            datasets: [{
                label: 'Estudiantes',
                data: datos.map(d => d.cantidad),
                backgroundColor: '#A8DADC',
                borderColor: '#85B4B6',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y'
        }
    });
}

function crearGraficoCompromisoApoderados(datos) {
    const ctx = document.getElementById('chartCompromisoApoderados');
    if (!ctx) return;
    
    chartsInstances['compromisoApoderados'] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: datos.map(d => d.nivel_compromiso),
            datasets: [{
                data: datos.map(d => d.cantidad),
                backgroundColor: ['#B8E6B8', '#F9E4A3', '#FFD4D4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function crearGraficoCuentasEstado(datos) {
    const ctx = document.getElementById('chartCuentasEstado');
    if (!ctx) return;
    
    chartsInstances['cuentasEstado'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.estado),
            datasets: [{
                label: 'Monto (S/)',
                data: datos.map(d => d.monto),
                backgroundColor: ['#F9E4A3', '#FFB8B8', '#B8E6B8', '#D4C5E3']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function crearGraficoPagosMetodo(datos) {
    const ctx = document.getElementById('chartPagosMetodo');
    if (!ctx) return;
    
    chartsInstances['pagosMetodo'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: datos.map(d => d.metodo),
            datasets: [{
                data: datos.map(d => d.total),
                backgroundColor: ['#A8DADC', '#B8E6B8', '#FFD8B8', '#D4C5E3', '#FFD4D4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function crearGraficoInteraccionesTipo(datos) {
    const ctx = document.getElementById('chartInteraccionesTipo');
    if (!ctx) return;
    
    chartsInstances['interaccionesTipo'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.tipo),
            datasets: [{
                label: 'Cantidad',
                data: datos.map(d => d.cantidad),
                backgroundColor: '#FFD8B8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function crearGraficoInteraccionesEstado(datos) {
    const ctx = document.getElementById('chartInteraccionesEstado');
    if (!ctx) return;
    
    chartsInstances['interaccionesEstado'] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: datos.map(d => d.estado),
            datasets: [{
                data: datos.map(d => d.cantidad),
                backgroundColor: ['#F9E4A3', '#B8E6B8', '#FFD4D4', '#D4C5E3']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================
function destruirGraficos() {
    for (let key in chartsInstances) {
        if (chartsInstances[key]) {
            chartsInstances[key].destroy();
        }
    }
    chartsInstances = {};
}

function formatearNumero(numero) {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numero);
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('#loadingOverlay').fadeIn();
    } else {
        $('#loadingOverlay').fadeOut();
    }
}

function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje
    });
}

function aplicarFiltros() {
    cargarDashboard();
}

function actualizarDashboard() {
    Swal.fire({
        title: 'Actualizando...',
        text: 'Obteniendo datos actualizados',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    cargarDashboard();
    
    setTimeout(() => {
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Actualizado',
            text: 'Dashboard actualizado correctamente',
            timer: 1500,
            showConfirmButton: false
        });
    }, 1000);
}

function exportarDashboard() {
    Swal.fire({
        icon: 'info',
        title: 'Exportar Dashboard',
        text: 'Funcionalidad de exportación en desarrollo'
    });
}