<!-- Modal Consultar Historial de Interacciones -->
<div class="modal fade" id="modalConsultarHistorial" tabindex="-1" aria-labelledby="modalConsultarHistorialLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #b8cfe6 0%, #d4e4f5 100%); border-bottom: 2px solid #e8f2f9;">
        <h5 class="modal-title" id="modalConsultarHistorialLabel">
          <i class="ti ti-history me-2"></i>Historial Completo de Interacciones
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      
      <div class="modal-body" style="min-height: 400px;">
        <!-- Loading spinner -->
        <div id="historialLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-3 text-muted">Cargando historial de interacciones...</p>
        </div>

        <!-- Error message -->
        <div id="historialError" class="alert alert-danger d-none" role="alert">
          <i class="ti ti-alert-circle me-2"></i>
          <span id="mensajeError"></span>
        </div>

        <!-- Información del contacto -->
        <div id="infoContacto" class="d-none">
          <div class="card mb-3" style="background-color: #f0f7fe; border: 1px solid #d3e5f9;">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h6 class="mb-2" style="color: #2c3e50;">
                    <i class="ti ti-user-circle me-2"></i>
                    <span id="nombreContacto" style="font-weight: 600;"></span>
                  </h6>
                  <div id="detallesContacto" style="font-size: 0.85rem; color: #6c757d;"></div>
                </div>
                <div class="col-md-4 text-end">
                  <div class="estadisticas-contacto">
                    <div class="stat-badge" style="background-color: #e8f4fd; padding: 0.5rem 1rem; border-radius: 8px; display: inline-block;">
                      <span style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;" id="totalInteracciones">0</span>
                      <br>
                      <small style="color: #6c757d;">Interacciones</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Filtros rápidos -->
          <div class="card mb-3" style="background-color: #fef7f0; border: 1px solid #f9e5d3;">
            <div class="card-body">
              <h6 class="mb-3" style="color: #6b4f3a;">
                <i class="ti ti-filter me-2"></i>Filtros Rápidos
              </h6>
              <div class="row g-2">
                <div class="col-md-3">
                  <select class="form-select form-select-sm" id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="programado">Programado</option>
                    <option value="realizado">Realizado</option>
                    <option value="cancelado">Cancelado</option>
                    <option value="reagendado">Reagendado</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select class="form-select form-select-sm" id="filtroTipo">
                    <option value="">Todos los tipos</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select class="form-select form-select-sm" id="filtroResultado">
                    <option value="">Todos los resultados</option>
                    <option value="exitoso">Exitoso</option>
                    <option value="sin_respuesta">Sin respuesta</option>
                    <option value="reagendar">Reagendar</option>
                    <option value="no_interesado">No interesado</option>
                    <option value="convertido">Convertido</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="limpiarFiltrosHistorial()">
                    <i class="ti ti-x me-1"></i>Limpiar Filtros
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Timeline de interacciones -->
          <div id="timelineInteracciones" class="timeline-container">
            <!-- Se llenará dinámicamente con JavaScript -->
          </div>
        </div>
      </div>

      <div class="modal-footer" style="background-color: #f8f9fa;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="exportarHistorialPDF()">
          <i class="ti ti-file-type-pdf me-1"></i>Exportar PDF
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Estilos para el timeline -->
<style>
.timeline-container {
  position: relative;
  padding-left: 30px;
}

.timeline-container::before {
  content: '';
  position: absolute;
  left: 10px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #b8cfe6, #e8f2f9);
}

.timeline-item {
  position: relative;
  margin-bottom: 25px;
  padding-left: 30px;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -24px;
  top: 8px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: white;
  border: 3px solid #b8cfe6;
  z-index: 1;
}

.timeline-item.estado-realizado::before {
  border-color: #28a745;
  background-color: #28a745;
}

.timeline-item.estado-programado::before {
  border-color: #ffc107;
  background-color: #ffc107;
}

.timeline-item.estado-cancelado::before {
  border-color: #dc3545;
  background-color: #dc3545;
}

.timeline-card {
  background: white;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  padding: 15px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.timeline-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f0f0;
}

.timeline-fecha {
  font-size: 0.75rem;
  color: #6c757d;
  font-weight: 500;
}

.timeline-asunto {
  font-weight: 600;
  color: #2c3e50;
  font-size: 1rem;
  margin-bottom: 8px;
}

.timeline-descripcion {
  color: #6c757d;
  font-size: 0.85rem;
  margin-bottom: 10px;
  line-height: 1.5;
}

.timeline-detalles {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.detalle-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detalle-label {
  font-size: 0.7rem;
  color: #6c757d;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.detalle-valor {
  font-size: 0.85rem;
  color: #2c3e50;
  font-weight: 500;
}

.timeline-observaciones {
  margin-top: 12px;
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #b8cfe6;
}

.timeline-observaciones-titulo {
  font-size: 0.75rem;
  color: #6c757d;
  font-weight: 600;
  margin-bottom: 5px;
}

.timeline-observaciones-texto {
  font-size: 0.8rem;
  color: #495057;
  white-space: pre-wrap;
  line-height: 1.4;
}

.no-interacciones {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
}

.no-interacciones i {
  font-size: 4rem;
  color: #d0d0d0;
  margin-bottom: 20px;
}

.badge-seguimiento-si {
  background-color: #fff3cd;
  color: #856404;
  font-size: 0.7rem;
  padding: 3px 8px;
  border-radius: 10px;
  font-weight: 600;
}

.badge-seguimiento-no {
  background-color: #e0e0e0;
  color: #6c757d;
  font-size: 0.7rem;
  padding: 3px 8px;
  border-radius: 10px;
  font-weight: 600;
}
</style>

<!-- JavaScript para manejar el historial -->
<script>
let historialCompleto = [];
let historialFiltrado = [];
let contactoActual = null;

// Función para abrir el modal y cargar historial
function cargarHistorialApoderado(apoderadoId) {
    $('#modalConsultarHistorial').modal('show');
    mostrarLoading();
    
    $.ajax({
        url: 'acciones/interacciones/consultar_historial_interacciones.php',
        method: 'POST',
        data: { 
            tipo: 'apoderado',
            id: apoderadoId 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                historialCompleto = response.interacciones;
                contactoActual = response.contacto;
                mostrarHistorial();
            } else {
                mostrarError(response.message || 'Error al cargar el historial');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            mostrarError('Error de conexión al obtener el historial. Por favor, intente nuevamente.');
        }
    });
}

// Función para cargar historial de familia
function cargarHistorialFamilia(familiaId) {
    $('#modalConsultarHistorial').modal('show');
    mostrarLoading();
    
    $.ajax({
        url: 'ajax/consultar_historial_interacciones.php',
        method: 'POST',
        data: { 
            tipo: 'familia',
            id: familiaId 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                historialCompleto = response.interacciones;
                contactoActual = response.contacto;
                mostrarHistorial();
            } else {
                mostrarError(response.message || 'Error al cargar el historial');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            mostrarError('Error de conexión al obtener el historial. Por favor, intente nuevamente.');
        }
    });
}

// Función para mostrar el loading
function mostrarLoading() {
    $('#historialLoading').removeClass('d-none');
    $('#historialError').addClass('d-none');
    $('#infoContacto').addClass('d-none');
}

// Función para mostrar error
function mostrarError(mensaje) {
    $('#historialLoading').addClass('d-none');
    $('#historialError').removeClass('d-none');
    $('#mensajeError').text(mensaje);
    $('#infoContacto').addClass('d-none');
}

// Función para mostrar el historial
function mostrarHistorial() {
    $('#historialLoading').addClass('d-none');
    $('#historialError').addClass('d-none');
    $('#infoContacto').removeClass('d-none');
    
    // Mostrar información del contacto
    $('#nombreContacto').text(contactoActual.nombre);
    $('#detallesContacto').html(contactoActual.detalles);
    $('#totalInteracciones').text(historialCompleto.length);
    
    // Llenar el filtro de tipos
    const tipos = [...new Set(historialCompleto.map(i => i.tipo_interaccion))];
    let optionsTipos = '<option value="">Todos los tipos</option>';
    tipos.forEach(tipo => {
        optionsTipos += `<option value="${tipo}">${tipo}</option>`;
    });
    $('#filtroTipo').html(optionsTipos);
    
    // Aplicar filtros y mostrar
    historialFiltrado = historialCompleto;
    renderizarTimeline();
}

// Función para renderizar el timeline
function renderizarTimeline() {
    const container = $('#timelineInteracciones');
    container.empty();
    
    if (historialFiltrado.length === 0) {
        container.html(`
            <div class="no-interacciones">
                <i class="ti ti-database-off"></i>
                <h5>No hay interacciones registradas</h5>
                <p>No se encontraron interacciones que coincidan con los filtros seleccionados.</p>
            </div>
        `);
        return;
    }
    
    historialFiltrado.forEach((interaccion, index) => {
        const itemHTML = crearItemTimeline(interaccion, index);
        container.append(itemHTML);
    });
}

// Función para crear un item del timeline
function crearItemTimeline(interaccion, index) {
    const fechaDisplay = interaccion.fecha_realizada || interaccion.fecha_programada || interaccion.created_at;
    const fechaFormateada = formatearFecha(fechaDisplay);
    const estadoClass = `estado-${interaccion.estado}`;
    
    let resultadoHTML = '';
    if (interaccion.resultado) {
        const resultadoClass = `resultado-${interaccion.resultado}`;
        const resultadoTexto = interaccion.resultado.replace(/_/g, ' ');
        resultadoHTML = `<span class="resultado-info ${resultadoClass}">${resultadoTexto}</span>`;
    }
    
    let seguimientoHTML = '';
    if (interaccion.requiere_seguimiento == 1) {
        seguimientoHTML = `<span class="badge-seguimiento-si">Requiere seguimiento</span>`;
        if (interaccion.fecha_proximo_seguimiento) {
            seguimientoHTML += `<br><small class="text-muted">Próximo: ${formatearFecha(interaccion.fecha_proximo_seguimiento)}</small>`;
        }
    } else {
        seguimientoHTML = `<span class="badge-seguimiento-no">Sin seguimiento</span>`;
    }
    
    const observacionesHTML = interaccion.observaciones ? `
        <div class="timeline-observaciones">
            <div class="timeline-observaciones-titulo">
                <i class="ti ti-note me-1"></i>Observaciones:
            </div>
            <div class="timeline-observaciones-texto">${escapeHtml(interaccion.observaciones)}</div>
        </div>
    ` : '';
    
    return `
        <div class="timeline-item ${estadoClass}" data-index="${index}">
            <div class="timeline-card">
                <div class="timeline-header">
                    <div>
                        <span class="badge badge-tipo-interaccion" style="background-color: ${interaccion.tipo_color};">
                            ${escapeHtml(interaccion.tipo_interaccion)}
                        </span>
                        <span class="badge badge-estado ${estadoClass}">
                            ${interaccion.estado}
                        </span>
                    </div>
                    <div class="timeline-fecha">
                        <i class="ti ti-calendar me-1"></i>${fechaFormateada}
                    </div>
                </div>
                
                <div class="timeline-asunto">${escapeHtml(interaccion.asunto)}</div>
                <div class="timeline-descripcion">${escapeHtml(interaccion.descripcion)}</div>
                
                <div class="timeline-detalles">
                    <div class="detalle-item">
                        <span class="detalle-label">Usuario</span>
                        <span class="detalle-valor">${escapeHtml(interaccion.usuario_nombre)}</span>
                    </div>
                    ${interaccion.duracion_minutos ? `
                    <div class="detalle-item">
                        <span class="detalle-label">Duración</span>
                        <span class="detalle-valor">${interaccion.duracion_minutos} min</span>
                    </div>
                    ` : ''}
                    ${resultadoHTML ? `
                    <div class="detalle-item">
                        <span class="detalle-label">Resultado</span>
                        <span class="detalle-valor">${resultadoHTML}</span>
                    </div>
                    ` : ''}
                    <div class="detalle-item">
                        <span class="detalle-label">Seguimiento</span>
                        <span class="detalle-valor">${seguimientoHTML}</span>
                    </div>
                </div>
                
                ${observacionesHTML}
            </div>
        </div>
    `;
}

// Función para aplicar filtros
function aplicarFiltrosHistorial() {
    const filtroEstado = $('#filtroEstado').val();
    const filtroTipo = $('#filtroTipo').val();
    const filtroResultado = $('#filtroResultado').val();
    
    historialFiltrado = historialCompleto.filter(interaccion => {
        if (filtroEstado && interaccion.estado !== filtroEstado) return false;
        if (filtroTipo && interaccion.tipo_interaccion !== filtroTipo) return false;
        if (filtroResultado && interaccion.resultado !== filtroResultado) return false;
        return true;
    });
    
    renderizarTimeline();
}

// Event listeners para filtros
$('#filtroEstado, #filtroTipo, #filtroResultado').on('change', aplicarFiltrosHistorial);

// Función para limpiar filtros
function limpiarFiltrosHistorial() {
    $('#filtroEstado').val('');
    $('#filtroTipo').val('');
    $('#filtroResultado').val('');
    historialFiltrado = historialCompleto;
    renderizarTimeline();
}

// Función para formatear fechas
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';
    const date = new Date(fecha);
    const opciones = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('es-ES', opciones);
}

// Función para escapar HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Función para exportar a PDF (placeholder)
function exportarHistorialPDF() {
    if (!contactoActual || historialFiltrado.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Aquí iría la lógica para generar el PDF
    alert('Funcionalidad de exportación a PDF en desarrollo');
}

// Limpiar al cerrar el modal
$('#modalConsultarHistorial').on('hidden.bs.modal', function () {
    historialCompleto = [];
    historialFiltrado = [];
    contactoActual = null;
    $('#timelineInteracciones').empty();
    mostrarLoading();
});
</script>