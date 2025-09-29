<!-- Modal Ver Detalles del Referido -->
<div class="modal fade" id="modalVerReferido" tabindex="-1" aria-labelledby="modalVerReferidoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalVerReferidoLabel">
          <i class="ti ti-eye me-2"></i>
          Detalles del Lead Referido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        
        <!-- Información del Referido Seleccionado -->
        <div class="alert alert-info" role="alert" id="infoReferidoVer">
          <i class="ti ti-info-circle me-1"></i>
          <strong>Viendo:</strong> <span id="nombreReferidoVer">Cargando información...</span>
        </div>

        <input type="hidden" id="ver_referido_id">

        <div class="row">
          
          <!-- Columna Izquierda: Información del Código -->
          <div class="col-md-6">
            <div class="card mb-3">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="ti ti-ticket me-1"></i>
                  Información del Código
                </h6>
              </div>
              <div class="card-body">
                
                <div class="row">
                  <div class="col-6 mb-3">
                    <small class="text-muted">Código:</small>
                    <div class="fw-bold fs-5" id="ver_codigo">-</div>
                  </div>
                  <div class="col-6 mb-3">
                    <small class="text-muted">Estado:</small>
                    <div id="ver_codigo_estado">-</div>
                  </div>
                  <div class="col-12 mb-3">
                    <small class="text-muted">Descripción:</small>
                    <div id="ver_codigo_descripcion">-</div>
                  </div>
                  <div class="col-12 mb-3">
                    <small class="text-muted">Referente:</small>
                    <div class="fw-bold" id="ver_referente">-</div>
                  </div>
                  <div class="col-12 mb-3">
                    <small class="text-muted">Beneficio para el Referido:</small>
                    <div class="text-success fw-bold" id="ver_beneficio">-</div>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Usos Actuales:</small>
                    <div id="ver_usos">-</div>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Límite de Usos:</small>
                    <div id="ver_limite">-</div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Columna Derecha: Información del Lead -->
          <div class="col-md-6">
            <div class="card mb-3">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="ti ti-user-check me-1"></i>
                  Información del Lead
                </h6>
              </div>
              <div class="card-body">
                
                <div class="row">
                  <div class="col-6 mb-3">
                    <small class="text-muted">Código Lead:</small>
                    <div class="fw-bold" id="ver_codigo_lead">-</div>
                  </div>
                  <div class="col-6 mb-3">
                    <small class="text-muted">Estado Lead:</small>
                    <div id="ver_estado_lead">-</div>
                  </div>
                  <div class="col-12 mb-3">
                    <small class="text-muted">Nombre del Estudiante:</small>
                    <div class="fw-bold fs-6" id="ver_estudiante">-</div>
                  </div>
                  <div class="col-12 mb-3">
                    <small class="text-muted">Contacto:</small>
                    <div id="ver_contacto">-</div>
                  </div>
                  <div class="col-6 mb-3">
                    <small class="text-muted">Teléfono:</small>
                    <div id="ver_telefono">-</div>
                  </div>
                  <div class="col-6 mb-3">
                    <small class="text-muted">Email:</small>
                    <div id="ver_email">-</div>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Canal de Captación:</small>
                    <div id="ver_canal">-</div>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Grado de Interés:</small>
                    <div id="ver_grado">-</div>
                  </div>
                </div>

              </div>
            </div>
          </div>

        </div>

        <!-- Fila Completa: Información del Uso -->
        <div class="row">
          <div class="col-12">
            <div class="card mb-3">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="ti ti-calendar me-1"></i>
                  Información del Uso del Código
                </h6>
              </div>
              <div class="card-body">
                
                <div class="row">
                  <div class="col-md-3 mb-3">
                    <small class="text-muted">Fecha de Uso:</small>
                    <div class="fw-bold" id="ver_fecha_uso">-</div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <small class="text-muted">Días Transcurridos:</small>
                    <div id="ver_dias_transcurridos">-</div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <small class="text-muted">¿Convertido?:</small>
                    <div id="ver_convertido">-</div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <small class="text-muted">Fecha de Conversión:</small>
                    <div id="ver_fecha_conversion">-</div>
                  </div>
                  <div class="col-12">
                    <small class="text-muted">Observaciones:</small>
                    <div class="border rounded p-2 bg-light" id="ver_observaciones">-</div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Timeline de Actividad (si existe) -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <i class="ti ti-timeline me-1"></i>
                  Historial de Actividad
                </h6>
              </div>
              <div class="card-body">
                <div id="ver_timeline">
                  <p class="text-muted text-center">Cargando historial...</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
        <button type="button" class="btn btn-warning" onclick="editarObservacionesReferido()">
          <i class="ti ti-edit me-1"></i>
          Editar Observaciones
        </button>
        <button type="button" class="btn btn-success" id="ver_btn_convertir" onclick="abrirModalConvertir()" style="display: none;">
          <i class="ti ti-check me-1"></i>
          Marcar como Convertido
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Función para cargar datos del referido
window.cargarDatosReferido = function(referidoId) {
    // Mostrar loading
    Swal.fire({
        title: 'Cargando datos...',
        html: 'Obteniendo información del referido',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Realizar petición AJAX
    fetch('acciones/leads_referidos/obtener_referido.php?id=' + encodeURIComponent(referidoId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        cache: 'no-cache'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Response text:', text);
                throw new Error('La respuesta del servidor no es JSON válido');
            });
        }
        
        return response.json();
    })
    .then(data => {
        Swal.close();
        
        if (data.success) {
            if (!data.data) {
                throw new Error('No se recibieron datos del referido');
            }
            
            // Llenar el modal con los datos
            llenarModalVerReferido(data.data);
            
            // Mostrar el modal
            $('#modalVerReferido').modal('show');
            
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudieron obtener los datos del referido',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error completo:', error);
        
        Swal.fire({
            title: 'Error de Conexión',
            text: error.message || 'No se pudo cargar la información',
            icon: 'error',
            confirmButtonColor: '#dc3545',
            showCancelButton: true,
            cancelButtonText: 'Reintentar'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                setTimeout(() => {
                    cargarDatosReferido(referidoId);
                }, 500);
            }
        });
    });
};

// Función para llenar el modal con datos
function llenarModalVerReferido(datos) {
    try {
        // Función auxiliar
        function establecerTexto(elementId, texto) {
            const elemento = document.getElementById(elementId);
            if (elemento) {
                elemento.textContent = texto || '-';
            }
        }
        
        function establecerHTML(elementId, html) {
            const elemento = document.getElementById(elementId);
            if (elemento) {
                elemento.innerHTML = html || '-';
            }
        }
        
        // ID del referido
        document.getElementById('ver_referido_id').value = datos.id;
        
        // Título
        establecerTexto('nombreReferidoVer', datos.nombre_estudiante);
        
        // Información del código
        establecerTexto('ver_codigo', datos.codigo_referido);
        establecerHTML('ver_codigo_estado', 
            datos.codigo_activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'
        );
        establecerTexto('ver_codigo_descripcion', datos.descripcion_codigo);
        establecerTexto('ver_referente', datos.referente_nombre);
        establecerTexto('ver_beneficio', datos.beneficio_referido);
        establecerTexto('ver_usos', datos.usos_actuales);
        establecerTexto('ver_limite', datos.limite_usos || 'Ilimitado');
        
        // Información del lead
        establecerTexto('ver_codigo_lead', datos.codigo_lead);
        establecerHTML('ver_estado_lead', 
            `<span class="badge" style="background-color: ${datos.estado_color}">${datos.estado_lead}</span>`
        );
        establecerTexto('ver_estudiante', datos.nombre_estudiante);
        establecerTexto('ver_contacto', datos.nombre_contacto);
        establecerTexto('ver_telefono', datos.telefono);
        establecerTexto('ver_email', datos.email);
        establecerTexto('ver_canal', datos.canal_captacion);
        establecerTexto('ver_grado', datos.grado_interes);
        
        // Información del uso
        establecerTexto('ver_fecha_uso', datos.fecha_uso_formateada);
        establecerHTML('ver_dias_transcurridos', 
            `<span class="badge bg-info">Hace ${datos.dias_desde_uso} días</span>`
        );
        establecerHTML('ver_convertido', 
            datos.convertido ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-warning">Pendiente</span>'
        );
        establecerTexto('ver_fecha_conversion', datos.fecha_conversion_formateada);
        establecerTexto('ver_observaciones', datos.observaciones);
        
        // Mostrar botón de convertir si no está convertido
        const btnConvertir = document.getElementById('ver_btn_convertir');
        if (btnConvertir) {
            btnConvertir.style.display = datos.convertido ? 'none' : 'inline-block';
        }
        
        // Timeline de actividad
        if (datos.timeline && datos.timeline.length > 0) {
            let timelineHTML = '<ul class="timeline">';
            datos.timeline.forEach(item => {
                timelineHTML += `
                    <li class="timeline-item">
                        <span class="timeline-icon bg-${item.tipo === 'creacion' ? 'primary' : item.tipo === 'conversion' ? 'success' : 'info'}">
                            <i class="ti ti-${item.icono}"></i>
                        </span>
                        <div class="timeline-content">
                            <h6 class="mb-1">${item.titulo}</h6>
                            <p class="text-muted mb-1">${item.descripcion}</p>
                            <small class="text-muted">${item.fecha}</small>
                        </div>
                    </li>
                `;
            });
            timelineHTML += '</ul>';
            establecerHTML('ver_timeline', timelineHTML);
        } else {
            establecerHTML('ver_timeline', '<p class="text-muted text-center">Sin actividad registrada</p>');
        }
        
        console.log('Datos del referido cargados correctamente');
        
    } catch (error) {
        console.error('Error al llenar el modal:', error);
        
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema al mostrar los datos',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Función para editar observaciones
window.editarObservacionesReferido = function() {
    const referidoId = document.getElementById('ver_referido_id').value;
    const observacionesActuales = document.getElementById('ver_observaciones').textContent;
    
    Swal.fire({
        title: 'Editar Observaciones',
        html: `
            <textarea id="swal-edit-observaciones" class="form-control" rows="5" maxlength="500">${observacionesActuales === '-' ? '' : observacionesActuales}</textarea>
            <div class="form-text text-end mt-1">
                <span id="swal-contador">0</span>/500 caracteres
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const textarea = document.getElementById('swal-edit-observaciones');
            const contador = document.getElementById('swal-contador');
            contador.textContent = textarea.value.length;
            
            textarea.addEventListener('input', function() {
                contador.textContent = this.value.length;
            });
        },
        preConfirm: () => {
            return document.getElementById('swal-edit-observaciones').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario y enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'acciones/leads_referidos/gestionar_referido.php';
            
            const accionInput = document.createElement('input');
            accionInput.type = 'hidden';
            accionInput.name = 'accion';
            accionInput.value = 'editar_observaciones';
            form.appendChild(accionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'referido_id';
            idInput.value = referidoId;
            form.appendChild(idInput);
            
            const obsInput = document.createElement('input');
            obsInput.type = 'hidden';
            obsInput.name = 'observaciones';
            obsInput.value = result.value;
            form.appendChild(obsInput);
            
            document.body.appendChild(form);
            
            Swal.fire({
                title: 'Guardando...',
                html: 'Actualizando observaciones',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            form.submit();
        }
    });
};

// Función para abrir modal de convertir
window.abrirModalConvertir = function() {
    const referidoId = document.getElementById('ver_referido_id').value;
    $('#modalVerReferido').modal('hide');
    
    setTimeout(() => {
        // Cargar datos en modal de conversión
        document.getElementById('conv_referido_id').value = referidoId;
        $('#modalConvertirReferido').modal('show');
    }, 500);
};

// Actualizar listener de botón ver referido
$(document).on('click', '.btn-ver-referido', function() {
    const id = $(this).data('id');
    cargarDatosReferido(id);
});
</script>

<style>
/* Estilos para el timeline */
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 20px;
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
}
</style>