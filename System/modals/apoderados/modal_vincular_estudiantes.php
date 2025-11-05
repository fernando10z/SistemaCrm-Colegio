<style>
    .estudiante-card-vincular {
        background: #ffffff;
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
    }
    
    .estudiante-card-vincular:hover {
        border-color: #81c784;
        box-shadow: 0 4px 12px rgba(129, 199, 132, 0.15);
    }
    
    .estudiante-card-vincular.vinculado {
        background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
        border-color: #81c784;
    }
    
    .estudiante-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    
    .estudiante-nombre-vincular {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    .estudiante-codigo-badge {
        background: #e3f2fd;
        color: #1565c0;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }
    
    .estudiante-detalles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e9ecef;
    }
    
    .estudiante-detalle-item {
        display: flex;
        flex-direction: column;
    }
    
    .estudiante-detalle-label {
        font-size: 0.7rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.15rem;
    }
    
    .estudiante-detalle-value {
        font-size: 0.85rem;
        color: #2c3e50;
        font-weight: 500;
    }
    
    .btn-vincular {
        background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn-vincular:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(129, 199, 132, 0.4);
    }
    
    .btn-desvincular {
        background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn-desvincular:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 83, 80, 0.4);
    }
    
    .badge-vinculado {
        background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%);
        color: white;
        padding: 0.35rem 0.85rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .empty-estudiantes {
        text-align: center;
        padding: 3rem 2rem;
        color: #6c757d;
    }
    
    .empty-estudiantes i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    
    .empty-estudiantes p {
        font-size: 1rem;
        margin: 0;
        font-style: italic;
    }
    
    .filtro-container {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .badge-estado-matricula {
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .estado-activo { background: #d4edda; color: #155724; }
    .estado-inactivo { background: #f8d7da; color: #721c24; }
    .estado-retirado { background: #fff3cd; color: #856404; }
</style>

<!-- Modal Vincular Estudiantes -->
<div class="modal fade" id="modalVincularEstudiantes" tabindex="-1" aria-labelledby="modalVincularEstudiantesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="background: #fafafa; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
      <div class="modal-header" style="background: linear-gradient(135deg, #81c784 0%, #4db6ac 100%); border-bottom: none; border-radius: 12px 12px 0 0; padding: 1.5rem;">
        <div>
          <h5 class="modal-title" id="modalVincularEstudiantesLabel" style="color: #2c3e50; font-weight: 600; margin: 0;">
            <i class="ti ti-link me-2"></i>Vincular Estudiantes al Apoderado
          </h5>
          <small style="color: #546e7a; display: block; margin-top: 0.5rem; font-weight: 500;">
            Apoderado: <span id="vincular_apoderado_nombre" style="color: #2c3e50; font-weight: 600;"></span>
          </small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="padding: 1.5rem; max-height: 65vh; overflow-y: auto;">
        
        <input type="hidden" id="vincular_apoderado_id">
        
        <!-- Filtros -->
        <div class="filtro-container">
          <div class="row g-2 align-items-center">
            <div class="col-md-6">
              <div class="input-group">
                <span class="input-group-text" style="background: white; border: 1.5px solid #e0e0e0;">
                  <i class="ti ti-search"></i>
                </span>
                <input type="text" id="filtro_buscar_estudiante" class="form-control" 
                       placeholder="Buscar por nombre, apellido o código..."
                       style="border: 1.5px solid #e0e0e0;">
              </div>
            </div>
            <div class="col-md-3">
              <select id="filtro_estado" class="form-select" style="border: 1.5px solid #e0e0e0;">
                <option value="">Todos los estados</option>
                <option value="activo">Solo Activos</option>
                <option value="inactivo">Solo Inactivos</option>
                <option value="retirado">Solo Retirados</option>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filtro_vinculacion" class="form-select" style="border: 1.5px solid #e0e0e0;">
                <option value="">Todos</option>
                <option value="vinculados">Solo Vinculados</option>
                <option value="no_vinculados">No Vinculados</option>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Información de la Familia -->
        <div class="alert" style="background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%); border: 1px solid #90caf9; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem;">
          <div class="d-flex align-items-center">
            <i class="ti ti-info-circle" style="font-size: 1.5rem; color: #1976d2; margin-right: 0.75rem;"></i>
            <div>
              <strong style="color: #1565c0;">Familia: </strong>
              <span id="info_familia_codigo" style="color: #2c3e50; font-weight: 600;"></span>
              <span style="color: #6c757d; margin: 0 0.5rem;">|</span>
              <strong style="color: #1565c0;">Total de estudiantes: </strong>
              <span id="info_total_estudiantes" style="color: #2c3e50; font-weight: 600;">0</span>
            </div>
          </div>
        </div>
        
        <!-- Lista de Estudiantes -->
        <div id="lista_estudiantes_vincular">
          <div class="empty-estudiantes">
            <i class="ti ti-users"></i>
            <p>No hay estudiantes disponibles para vincular</p>
          </div>
        </div>
        
      </div>
      
      <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 1rem 1.5rem; border-radius: 0 0 12px 12px;">
        <div class="d-flex justify-content-between w-100 align-items-center">
          <div>
            <span style="color: #6c757d; font-size: 0.85rem;">
              <strong id="contador_vinculados">0</strong> estudiante(s) vinculado(s)
            </span>
          </div>
          <div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
              <i class="ti ti-x me-1"></i>Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Variable global para almacenar datos de estudiantes
let estudiantesData = [];

// Función para cargar estudiantes disponibles
function cargarEstudiantesDisponibles(apoderado_id) {
    $.ajax({
        url: 'actions/obtener_estudiantes_vinculacion.php',
        method: 'POST',
        data: { apoderado_id: apoderado_id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                estudiantesData = response.data.estudiantes;
                const familiaInfo = response.data.familia_info;
                
                // Mostrar información de la familia
                $('#info_familia_codigo').text(familiaInfo.codigo_familia + ' - ' + familiaInfo.apellido_principal);
                $('#info_total_estudiantes').text(estudiantesData.length);
                
                // Renderizar estudiantes
                renderizarEstudiantes();
                actualizarContador();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#ef9a9a'
                });
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los estudiantes',
                confirmButtonColor: '#ef9a9a'
            });
        }
    });
}

// Función para renderizar estudiantes
function renderizarEstudiantes() {
    const filtroTexto = $('#filtro_buscar_estudiante').val().toLowerCase();
    const filtroEstado = $('#filtro_estado').val();
    const filtroVinculacion = $('#filtro_vinculacion').val();
    
    let estudiantesFiltrados = estudiantesData.filter(function(estudiante) {
        // Filtro de texto
        const textoCompleto = (estudiante.nombres + ' ' + estudiante.apellidos + ' ' + estudiante.codigo_estudiante).toLowerCase();
        if (filtroTexto && !textoCompleto.includes(filtroTexto)) {
            return false;
        }
        
        // Filtro de estado
        if (filtroEstado && estudiante.estado_matricula !== filtroEstado) {
            return false;
        }
        
        // Filtro de vinculación
        if (filtroVinculacion === 'vinculados' && !estudiante.vinculado) {
            return false;
        }
        if (filtroVinculacion === 'no_vinculados' && estudiante.vinculado) {
            return false;
        }
        
        return true;
    });
    
    if (estudiantesFiltrados.length === 0) {
        $('#lista_estudiantes_vincular').html(`
            <div class="empty-estudiantes">
                <i class="ti ti-users"></i>
                <p>No se encontraron estudiantes con los filtros aplicados</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    estudiantesFiltrados.forEach(function(estudiante) {
        const vinculado = estudiante.vinculado ? 'vinculado' : '';
        const estadoClass = 'estado-' + (estudiante.estado_matricula || 'activo');
        const estadoTexto = estudiante.estado_matricula ? estudiante.estado_matricula.charAt(0).toUpperCase() + estudiante.estado_matricula.slice(1) : 'Activo';
        
        html += `
            <div class="estudiante-card-vincular ${vinculado}" data-estudiante-id="${estudiante.id}">
                <div class="estudiante-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="estudiante-codigo-badge">${estudiante.codigo_estudiante}</span>
                        <h6 class="estudiante-nombre-vincular">${estudiante.nombres} ${estudiante.apellidos}</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        ${estudiante.vinculado ? 
                            '<span class="badge-vinculado"><i class="ti ti-check"></i>Vinculado</span>' : 
                            '<button class="btn btn-vincular btn-toggle-vinculacion" data-estudiante-id="' + estudiante.id + '" data-accion="vincular"><i class="ti ti-link me-1"></i>Vincular</button>'
                        }
                        ${estudiante.vinculado ? 
                            '<button class="btn btn-desvincular btn-toggle-vinculacion" data-estudiante-id="' + estudiante.id + '" data-accion="desvincular"><i class="ti ti-unlink me-1"></i>Desvincular</button>' : 
                            ''
                        }
                    </div>
                </div>
                
                <div class="estudiante-detalles">
                    <div class="estudiante-detalle-item">
                        <span class="estudiante-detalle-label">Fecha Nacimiento</span>
                        <span class="estudiante-detalle-value">${estudiante.fecha_nacimiento_formato || 'No registrada'}</span>
                    </div>
                    <div class="estudiante-detalle-item">
                        <span class="estudiante-detalle-label">Edad</span>
                        <span class="estudiante-detalle-value">${estudiante.edad ? estudiante.edad + ' años' : 'N/A'}</span>
                    </div>
                    <div class="estudiante-detalle-item">
                        <span class="estudiante-detalle-label">Sección</span>
                        <span class="estudiante-detalle-value">${estudiante.seccion || 'No asignada'}</span>
                    </div>
                    <div class="estudiante-detalle-item">
                        <span class="estudiante-detalle-label">Estado</span>
                        <span class="badge-estado-matricula ${estadoClass}">${estadoTexto}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#lista_estudiantes_vincular').html(html);
}

// Actualizar contador de vinculados
function actualizarContador() {
    const vinculados = estudiantesData.filter(e => e.vinculado).length;
    $('#contador_vinculados').text(vinculados);
}

// Evento de vincular/desvincular
$(document).on('click', '.btn-toggle-vinculacion', function() {
    const estudianteId = $(this).data('estudiante-id');
    const accion = $(this).data('accion');
    const apoderadoId = $('#vincular_apoderado_id').val();
    
    Swal.fire({
        title: accion === 'vincular' ? '¿Vincular Estudiante?' : '¿Desvincular Estudiante?',
        text: accion === 'vincular' ? 
            'El estudiante será vinculado a este apoderado' : 
            'El estudiante dejará de estar vinculado a este apoderado',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: accion === 'vincular' ? '#81c784' : '#ef5350',
        cancelButtonColor: '#6c757d',
        confirmButtonText: accion === 'vincular' ? 'Sí, vincular' : 'Sí, desvincular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            procesarVinculacion(apoderadoId, estudianteId, accion);
        }
    });
});

// Procesar vinculación/desvinculación
function procesarVinculacion(apoderadoId, estudianteId, accion) {
    $.ajax({
        url: 'actions/procesar_vinculacion_estudiante.php',
        method: 'POST',
        data: {
            apoderado_id: apoderadoId,
            estudiante_id: estudianteId,
            accion: accion
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Actualizar el estado local
                const estudiante = estudiantesData.find(e => e.id == estudianteId);
                if (estudiante) {
                    estudiante.vinculado = (accion === 'vincular');
                }
                
                // Re-renderizar
                renderizarEstudiantes();
                actualizarContador();
                
                // Notificación
                Swal.fire({
                    icon: 'success',
                    title: accion === 'vincular' ? '¡Vinculado!' : '¡Desvinculado!',
                    text: response.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#ef9a9a'
                });
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo procesar la vinculación',
                confirmButtonColor: '#ef9a9a'
            });
        }
    });
}

// Eventos de filtros
$('#filtro_buscar_estudiante, #filtro_estado, #filtro_vinculacion').on('change keyup', function() {
    renderizarEstudiantes();
});

// Limpiar al cerrar modal
$('#modalVincularEstudiantes').on('hidden.bs.modal', function() {
    estudiantesData = [];
    $('#filtro_buscar_estudiante').val('');
    $('#filtro_estado').val('');
    $('#filtro_vinculacion').val('');
    $('#lista_estudiantes_vincular').html(`
        <div class="empty-estudiantes">
            <i class="ti ti-users"></i>
            <p>No hay estudiantes disponibles para vincular</p>
        </div>
    `);
});
</script>