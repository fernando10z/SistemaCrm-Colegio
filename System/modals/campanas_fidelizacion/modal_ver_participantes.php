<style>
/* Estilos para asegurar que SweetAlert2 se muestre por encima de todo */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

/* Estilo para que el modal esté por debajo del SweetAlert */
.modal {
    z-index: 9999 !important;
}

/* Estilo para el backdrop del modal */
.modal-backdrop {
    z-index: 9998 !important;
}

/* Estilos personalizados para participantes */
.participante-card {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.participante-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.participante-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.participante-nombre {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.95rem;
}

.participante-tipo {
    font-size: 0.75rem;
    color: #7f8c8d;
}

.participante-contacto {
    font-size: 0.8rem;
    color: #34495e;
    margin-top: 4px;
}

.participante-info {
    font-size: 0.75rem;
    color: #95a5a6;
    margin-top: 2px;
}

.participante-fechas {
    display: flex;
    gap: 10px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.fecha-item {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.participante-acciones {
    display: flex;
    gap: 4px;
    margin-top: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.stat-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.stat-box.success {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.stat-box.warning {
    background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%);
}

.stat-box.danger {
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
}

.stat-box.info {
    background: linear-gradient(135deg, #e1f5fe 0%, #b3e5fc 100%);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    font-size: 0.75rem;
    color: #7f8c8d;
    text-transform: uppercase;
}

.filtro-estados {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.btn-filtro {
    font-size: 0.75rem;
    padding: 4px 10px;
}

.lista-participantes-scroll {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 5px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #95a5a6;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
}

.badge-estado-participante {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 12px;
}
</style>

<!-- Modal Ver Participantes -->
<div class="modal fade" id="modalVerParticipantes" tabindex="-1" aria-labelledby="modalVerParticipantesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #64b5f6 0%, #42a5f5 100%); color: white;">
        <h5 class="modal-title" id="modalVerParticipantesLabel">
          <i class="ti ti-users me-2"></i>
          Participantes del Evento
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        
        <!-- Información del Evento -->
        <div class="alert alert-info" role="alert" id="info-evento-participantes">
          <i class="ti ti-calendar me-1"></i>
          <strong id="evento-titulo-participantes">Cargando evento...</strong>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid" id="stats-participantes">
          <div class="stat-box">
            <div class="stat-number" id="stat-total">0</div>
            <div class="stat-label">Total</div>
          </div>
          <div class="stat-box warning">
            <div class="stat-number" id="stat-invitados">0</div>
            <div class="stat-label">Invitados</div>
          </div>
          <div class="stat-box success">
            <div class="stat-number" id="stat-confirmados">0</div>
            <div class="stat-label">Confirmados</div>
          </div>
          <div class="stat-box info">
            <div class="stat-number" id="stat-asistieron">0</div>
            <div class="stat-label">Asistieron</div>
          </div>
          <div class="stat-box danger">
            <div class="stat-number" id="stat-no-asistieron">0</div>
            <div class="stat-label">No Asistieron</div>
          </div>
          <div class="stat-box">
            <div class="stat-number" id="stat-cancelados">0</div>
            <div class="stat-label">Cancelados</div>
          </div>
        </div>

        <!-- Filtros por Estado -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="filtro-estados" id="filtro-estados">
            <button type="button" class="btn btn-outline-primary btn-filtro active" data-estado="todos">
              <i class="ti ti-list me-1"></i>
              Todos
            </button>
            <button type="button" class="btn btn-outline-warning btn-filtro" data-estado="invitado">
              <i class="ti ti-mail me-1"></i>
              Invitados
            </button>
            <button type="button" class="btn btn-outline-success btn-filtro" data-estado="confirmado">
              <i class="ti ti-check me-1"></i>
              Confirmados
            </button>
            <button type="button" class="btn btn-outline-info btn-filtro" data-estado="asistio">
              <i class="ti ti-user-check me-1"></i>
              Asistieron
            </button>
            <button type="button" class="btn btn-outline-secondary btn-filtro" data-estado="no_asistio">
              <i class="ti ti-user-x me-1"></i>
              No Asistieron
            </button>
            <button type="button" class="btn btn-outline-danger btn-filtro" data-estado="cancelado">
              <i class="ti ti-ban me-1"></i>
              Cancelados
            </button>
          </div>
          <div>
            <input type="text" class="form-control form-control-sm" id="buscar-participante" 
                   placeholder="Buscar participante...">
          </div>
        </div>

        <!-- Lista de Participantes -->
        <div class="lista-participantes-scroll" id="lista-participantes-evento">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando participantes...</p>
          </div>
        </div>

      </div>
      
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-outline-secondary" id="btn-exportar-participantes">
          <i class="ti ti-download me-1"></i>
          Exportar Lista
        </button> -->
        <button type="button" class="btn btn-outline-primary" id="btn-enviar-recordatorio">
          <i class="ti ti-mail me-1"></i>
          Enviar Recordatorio
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Variable global para almacenar participantes
let participantesData = [];
let participantesFiltrados = [];
let eventoIdActual = null;

// Función para cargar participantes
function cargarParticipantes(eventoId, eventoTitulo) {
    eventoIdActual = eventoId;
    
    // Actualizar título del evento
    document.getElementById('evento-titulo-participantes').textContent = eventoTitulo;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalVerParticipantes'));
    modal.show();
    
    // Hacer petición AJAX
    $.ajax({
        url: 'actions/obtener_participantes_evento.php',
        method: 'POST',
        data: { evento_id: eventoId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                participantesData = response.data;
                participantesFiltrados = response.data;
                
                // Actualizar estadísticas
                actualizarEstadisticas(response.stats);
                
                // Mostrar participantes
                mostrarParticipantes(participantesData);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo cargar la lista de participantes'
            });
        }
    });
}

// Función para actualizar estadísticas
function actualizarEstadisticas(stats) {
    document.getElementById('stat-total').textContent = stats.total || 0;
    document.getElementById('stat-invitados').textContent = stats.invitados || 0;
    document.getElementById('stat-confirmados').textContent = stats.confirmados || 0;
    document.getElementById('stat-asistieron').textContent = stats.asistieron || 0;
    document.getElementById('stat-no-asistieron').textContent = stats.no_asistieron || 0;
    document.getElementById('stat-cancelados').textContent = stats.cancelados || 0;
}

// Función para mostrar participantes
function mostrarParticipantes(participantes) {
    const container = document.getElementById('lista-participantes-evento');
    
    if (participantes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="ti ti-user-off"></i>
                <p>No hay participantes que cumplan con los criterios de búsqueda</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    participantes.forEach(p => {
        html += `
            <div class="participante-card" data-estado="${p.estado_raw}">
                <div class="participante-header">
                    <div>
                        <div class="participante-nombre">${p.nombre}</div>
                        <div class="participante-tipo">${p.tipo_participante}</div>
                    </div>
                    <span class="badge bg-${p.estado_class} badge-estado-participante">
                        ${p.estado}
                    </span>
                </div>
                <div class="participante-contacto">
                    <i class="ti ti-mail me-1"></i>
                    ${p.contacto}
                </div>
                ${p.info_adicional ? `<div class="participante-info">${p.info_adicional}</div>` : ''}
                <div class="participante-fechas">
                    <div class="fecha-item">
                        <i class="ti ti-calendar me-1"></i>
                        Invitado: ${p.fecha_invitacion}
                    </div>
                    ${p.fecha_confirmacion ? `
                        <div class="fecha-item">
                            <i class="ti ti-check me-1"></i>
                            Confirmado: ${p.fecha_confirmacion}
                        </div>
                    ` : ''}
                    ${p.fecha_asistencia ? `
                        <div class="fecha-item">
                            <i class="ti ti-user-check me-1"></i>
                            Asistió: ${p.fecha_asistencia}
                        </div>
                    ` : ''}
                </div>
                ${p.observaciones ? `
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="ti ti-note me-1"></i>
                            ${p.observaciones}
                        </small>
                    </div>
                ` : ''}
                <div class="participante-acciones">
                    ${p.estado_raw === 'invitado' ? `
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="cambiarEstadoParticipante(${p.id}, 'confirmado', '${p.nombre}')">
                            <i class="ti ti-check me-1"></i>
                            Confirmar
                        </button>
                    ` : ''}
                    ${p.estado_raw === 'confirmado' ? `
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="cambiarEstadoParticipante(${p.id}, 'asistio', '${p.nombre}')">
                            <i class="ti ti-user-check me-1"></i>
                            Marcar Asistencia
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="cambiarEstadoParticipante(${p.id}, 'no_asistio', '${p.nombre}')">
                            <i class="ti ti-user-x me-1"></i>
                            No Asistió
                        </button>
                    ` : ''}
                    ${p.estado_raw !== 'cancelado' ? `
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="cambiarEstadoParticipante(${p.id}, 'cancelado', '${p.nombre}')">
                            <i class="ti ti-ban me-1"></i>
                            Cancelar
                        </button>
                    ` : ''}
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="agregarObservacion(${p.id}, '${p.nombre}')">
                        <i class="ti ti-message me-1"></i>
                        Observación
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Función para cambiar estado de participante
function cambiarEstadoParticipante(participanteId, nuevoEstado, nombre) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: `Cambiar a: ${nuevoEstado.replace('_', ' ')} - ${nombre}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { 
                    accion: 'actualizar_participacion',
                    participante_id: participanteId,
                    nuevo_estado: nuevoEstado,
                    observaciones: 'Actualizado desde gestión de participantes'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        text: 'La participación ha sido actualizada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Recargar participantes
                        cargarParticipantes(eventoIdActual, document.getElementById('evento-titulo-participantes').textContent);
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo actualizar el estado'
                    });
                }
            });
        }
    });
}

// Función para agregar observación
function agregarObservacion(participanteId, nombre) {
    Swal.fire({
        title: `Observación - ${nombre}`,
        input: 'textarea',
        inputPlaceholder: 'Escriba la observación...',
        inputAttributes: {
            'aria-label': 'Observación',
            'maxlength': 500
        },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { 
                    accion: 'actualizar_participacion',
                    participante_id: participanteId,
                    nuevo_estado: 'mantener', // Mantener el estado actual
                    observaciones: result.value
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Observación guardada',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        cargarParticipantes(eventoIdActual, document.getElementById('evento-titulo-participantes').textContent);
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar la observación'
                    });
                }
            });
        }
    });
}

// Filtros por estado
document.addEventListener('DOMContentLoaded', function() {
    // Filtros de estado
    document.querySelectorAll('.btn-filtro').forEach(btn => {
        btn.addEventListener('click', function() {
            // Actualizar botones activos
            document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const estadoFiltro = this.getAttribute('data-estado');
            
            if (estadoFiltro === 'todos') {
                participantesFiltrados = participantesData;
            } else {
                participantesFiltrados = participantesData.filter(p => p.estado_raw === estadoFiltro);
            }
            
            mostrarParticipantes(participantesFiltrados);
        });
    });
    
    // Búsqueda
    document.getElementById('buscar-participante').addEventListener('input', function() {
        const busqueda = this.value.toLowerCase();
        
        const filtrados = participantesFiltrados.filter(p => 
            p.nombre.toLowerCase().includes(busqueda) ||
            p.contacto.toLowerCase().includes(busqueda) ||
            p.tipo_participante.toLowerCase().includes(busqueda)
        );
        
        mostrarParticipantes(filtrados);
    });
    
    // Exportar participantes
    document.getElementById('btn-exportar-participantes').addEventListener('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Exportar lista',
            text: 'Funcionalidad de exportación en desarrollo'
        });
    });
    
    // Enviar recordatorio
    document.getElementById('btn-enviar-recordatorio').addEventListener('click', function() {
        Swal.fire({
            title: '¿Enviar recordatorio?',
            text: 'Se enviará un recordatorio a todos los participantes confirmados',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Recordatorios enviados',
                    text: 'Los recordatorios se están enviando'
                });
            }
        });
    });
    
    // Limpiar al cerrar modal
    document.getElementById('modalVerParticipantes').addEventListener('hidden.bs.modal', function() {
        participantesData = [];
        participantesFiltrados = [];
        eventoIdActual = null;
        document.getElementById('buscar-participante').value = '';
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active'));
        document.querySelector('.btn-filtro[data-estado="todos"]').classList.add('active');
    });
});
</script>