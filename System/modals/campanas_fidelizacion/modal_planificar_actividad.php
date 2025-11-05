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

/* Estilos personalizados para el modal */
.actividad-info-card {
    background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.icon-feature {
    font-size: 2rem;
    color: #ffa726;
}

.feature-item {
    background-color: #fff8e1;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.fecha-preview {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 6px;
    padding: 12px;
    text-align: center;
}

.fecha-preview-dia {
    font-size: 2rem;
    font-weight: bold;
    color: #1976d2;
}

.fecha-preview-mes {
    font-size: 0.9rem;
    color: #424242;
}
</style>

<!-- Modal Planificar Actividad -->
<div class="modal fade" id="modalPlanificarActividad" tabindex="-1" aria-labelledby="modalPlanificarActividadLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #ffb74d 0%, #ffa726 100%); color: white;">
        <h5 class="modal-title" id="modalPlanificarActividadLabel">
          <i class="ti ti-users me-2"></i>
          Planificar Actividad Familia-Colegio
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formPlanificarActividad" method="POST" action="" novalidate>
        <input type="hidden" name="accion" value="planificar_actividad">
        
        <div class="modal-body">
          
          <!-- Información Introductoria -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Actividad Familia-Colegio:</strong> Planifique reuniones, talleres o actividades para fortalecer la relación entre las familias y el colegio. Todas las actividades creadas aquí son <strong>gratuitas</strong> y requieren <strong>confirmación</strong> de asistencia.
          </div>

          <div class="row">
            <!-- Columna Izquierda: Información Básica -->
            <div class="col-md-7">
              <div class="card">
                <div class="card-header" style="background-color: #fff8e1;">
                  <h6 class="mb-0">
                    <i class="ti ti-clipboard me-1"></i>
                    Información de la Actividad
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Título de la Actividad -->
                  <div class="mb-3">
                    <label for="titulo" class="form-label">
                      Título de la Actividad <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="titulo" name="titulo" 
                           required maxlength="200"
                           placeholder="Ej: Reunión de Padres I Trimestre, Taller de Crianza Positiva, etc.">
                    <div class="invalid-feedback">
                      Por favor ingrese el título de la actividad
                    </div>
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label for="descripcion" class="form-label">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="descripcion" name="descripcion" 
                              rows="4" required maxlength="500"
                              placeholder="Describa el objetivo y contenido de la actividad..."></textarea>
                    <div class="form-text">Máximo 500 caracteres</div>
                    <div class="invalid-feedback">
                      Por favor ingrese la descripción de la actividad
                    </div>
                  </div>

                  <!-- Fecha y Hora -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="fecha_inicio" class="form-label">
                          Fecha y Hora de Inicio <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                               required>
                        <div class="invalid-feedback">
                          Por favor seleccione la fecha y hora
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label">
                          <i class="ti ti-clock me-1"></i>
                          Vista Previa
                        </label>
                        <div class="fecha-preview" id="fecha-preview">
                          <div class="fecha-preview-dia">--</div>
                          <div class="fecha-preview-mes">Seleccione una fecha</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Ubicación -->
                  <div class="mb-3">
                    <label for="ubicacion" class="form-label">
                      Ubicación <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                           required maxlength="200"
                           placeholder="Ej: Auditorio Principal, Sala de Conferencias, Patio Central, etc.">
                    <div class="invalid-feedback">
                      Por favor ingrese la ubicación de la actividad
                    </div>
                  </div>

                  <!-- Observaciones -->
                  <div class="mb-3">
                    <label for="observaciones" class="form-label">
                      Observaciones y Notas Adicionales
                    </label>
                    <textarea class="form-control" id="observaciones" name="observaciones" 
                              rows="3" maxlength="500"
                              placeholder="Información adicional, requisitos, materiales necesarios, etc."></textarea>
                    <div class="form-text">Opcional - Máximo 500 caracteres</div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Características y Tips -->
            <div class="col-md-5">
              
              <!-- Características Automáticas -->
              <div class="card">
                <div class="card-header" style="background-color: #e8f5e9;">
                  <h6 class="mb-0">
                    <i class="ti ti-star me-1"></i>
                    Características de la Actividad
                  </h6>
                </div>
                <div class="card-body">
                  
                  <div class="feature-item">
                    <i class="ti ti-users icon-feature" style="color: #4caf50;"></i>
                    <div>
                      <strong>Dirigido a:</strong> Padres de Familia<br>
                      <small class="text-muted">Automático</small>
                    </div>
                  </div>

                  <div class="feature-item">
                    <i class="ti ti-calendar-check icon-feature" style="color: #2196f3;"></i>
                    <div>
                      <strong>Requiere Confirmación:</strong> Sí<br>
                      <small class="text-muted">Los padres deben confirmar asistencia</small>
                    </div>
                  </div>

                  <div class="feature-item">
                    <i class="ti ti-coin icon-feature" style="color: #ff9800;"></i>
                    <div>
                      <strong>Costo:</strong> Gratuito<br>
                      <small class="text-muted">Sin costo para los participantes</small>
                    </div>
                  </div>

                  <div class="feature-item">
                    <i class="ti ti-clock icon-feature" style="color: #9c27b0;"></i>
                    <div>
                      <strong>Estado Inicial:</strong> Programado<br>
                      <small class="text-muted">Se puede iniciar después</small>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Tips y Recomendaciones -->
              <div class="alert alert-warning mt-3" role="alert">
                <h6 class="alert-heading">
                  <i class="ti ti-bulb me-1"></i>
                  Tips para una Actividad Exitosa
                </h6>
                <ul class="mb-0 mt-2 small">
                  <li>Envíe las invitaciones con <strong>al menos 7 días</strong> de anticipación</li>
                  <li>Incluya la <strong>agenda</strong> y objetivos claros en la descripción</li>
                  <li>Confirme la <strong>disponibilidad</strong> de la ubicación antes</li>
                  <li>Prepare <strong>material de apoyo</strong> si es necesario</li>
                  <li>Planifique un <strong>horario conveniente</strong> para los padres</li>
                </ul>
              </div>

              <!-- Sugerencias de Títulos -->
              <div class="card mt-3">
                <div class="card-header" style="background-color: #f3e5f5;">
                  <h6 class="mb-0">
                    <i class="ti ti-lightbulb me-1"></i>
                    Ideas de Actividades
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm text-start btn-sugerencia" 
                            data-titulo="Reunión de Padres - I Trimestre"
                            data-descripcion="Presentación de resultados académicos y comportamiento del primer trimestre">
                      <i class="ti ti-clipboard me-1"></i>
                      Reunión Trimestral
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-start btn-sugerencia" 
                            data-titulo="Taller: Comunicación Efectiva con sus Hijos"
                            data-descripcion="Taller práctico sobre técnicas de comunicación asertiva y empática con niños y adolescentes">
                      <i class="ti ti-message-circle me-1"></i>
                      Taller de Comunicación
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-start btn-sugerencia" 
                            data-titulo="Charla: Prevención del Bullying Escolar"
                            data-descripcion="Información sobre identificación, prevención y manejo del acoso escolar">
                      <i class="ti ti-shield me-1"></i>
                      Charla Preventiva
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-start btn-sugerencia" 
                            data-titulo="Escuela para Padres: Manejo de Emociones"
                            data-descripcion="Sesión sobre inteligencia emocional y manejo de emociones en el entorno familiar">
                      <i class="ti ti-heart me-1"></i>
                      Escuela para Padres
                    </button>
                  </div>
                  <div class="form-text mt-2">
                    <i class="ti ti-info-circle me-1"></i>
                    Haga clic en una sugerencia para usarla como plantilla
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Resumen Previo -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="actividad-info-card" style="display: none;" id="resumen-actividad">
                <h6 class="mb-2">
                  <i class="ti ti-check-circle me-1"></i>
                  Resumen de la Actividad
                </h6>
                <div class="row g-2">
                  <div class="col-md-8">
                    <small class="text-muted">Título:</small>
                    <div class="fw-bold" id="resumen-titulo">-</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">Fecha:</small>
                    <div class="fw-bold" id="resumen-fecha">-</div>
                  </div>
                  <div class="col-md-8">
                    <small class="text-muted">Ubicación:</small>
                    <div class="fw-bold" id="resumen-ubicacion">-</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">Tipo:</small>
                    <div><span class="badge bg-primary">Reunión de Padres</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-outline-info" id="btn-previsualizar">
            <i class="ti ti-eye me-1"></i>
            Previsualizar
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="ti ti-calendar-plus me-1"></i>
            Planificar Actividad
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formPlanificarActividad = document.getElementById('formPlanificarActividad');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaPreview = document.getElementById('fecha-preview');
    const titulo = document.getElementById('titulo');
    const ubicacion = document.getElementById('ubicacion');
    const resumenActividad = document.getElementById('resumen-actividad');
    const btnPrevisualizar = document.getElementById('btn-previsualizar');
    
    // Configurar fecha mínima (hoy)
    const ahora = new Date();
    const year = ahora.getFullYear();
    const month = String(ahora.getMonth() + 1).padStart(2, '0');
    const day = String(ahora.getDate()).padStart(2, '0');
    const hour = String(ahora.getHours()).padStart(2, '0');
    const minute = String(ahora.getMinutes()).padStart(2, '0');
    const fechaMinima = `${year}-${month}-${day}T${hour}:${minute}`;
    fechaInicio.setAttribute('min', fechaMinima);

    // Actualizar preview de fecha
    fechaInicio.addEventListener('change', function() {
        if (this.value) {
            const fecha = new Date(this.value);
            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const fechaFormateada = fecha.toLocaleDateString('es-PE', opciones);
            const horaFormateada = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
            
            const dia = fecha.getDate();
            const mes = fecha.toLocaleDateString('es-PE', { month: 'short' }).toUpperCase();
            
            fechaPreview.innerHTML = `
                <div class="fecha-preview-dia">${dia}</div>
                <div class="fecha-preview-mes">${mes} ${fecha.getFullYear()}</div>
                <small class="text-muted">${horaFormateada}</small>
            `;
        } else {
            fechaPreview.innerHTML = `
                <div class="fecha-preview-dia">--</div>
                <div class="fecha-preview-mes">Seleccione una fecha</div>
            `;
        }
    });

    // Botones de sugerencia
    document.querySelectorAll('.btn-sugerencia').forEach(btn => {
        btn.addEventListener('click', function() {
            const tituloSugerido = this.getAttribute('data-titulo');
            const descripcionSugerida = this.getAttribute('data-descripcion');
            
            titulo.value = tituloSugerido;
            document.getElementById('descripcion').value = descripcionSugerida;
            
            // Efecto visual
            titulo.classList.add('border-success');
            setTimeout(() => {
                titulo.classList.remove('border-success');
            }, 1000);
            
            Swal.fire({
                icon: 'success',
                title: 'Plantilla aplicada',
                text: 'Puede modificar el contenido según sus necesidades',
                timer: 2000,
                showConfirmButton: false
            });
        });
    });

    // Previsualizar actividad
    btnPrevisualizar.addEventListener('click', function() {
        const tituloVal = titulo.value;
        const fechaVal = fechaInicio.value;
        const ubicacionVal = ubicacion.value;
        
        if (!tituloVal || !fechaVal || !ubicacionVal) {
            Swal.fire({
                icon: 'warning',
                title: 'Información incompleta',
                text: 'Complete al menos el título, fecha y ubicación para previsualizar'
            });
            return;
        }
        
        // Actualizar resumen
        document.getElementById('resumen-titulo').textContent = tituloVal;
        
        const fecha = new Date(fechaVal);
        const fechaFormateada = fecha.toLocaleDateString('es-PE', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById('resumen-fecha').textContent = fechaFormateada;
        document.getElementById('resumen-ubicacion').textContent = ubicacionVal;
        
        resumenActividad.style.display = 'block';
        
        // Scroll al resumen
        resumenActividad.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // Validación y envío del formulario
    formPlanificarActividad.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        // Validar que la fecha no sea en el pasado
        const fechaSeleccionada = new Date(fechaInicio.value);
        const fechaActual = new Date();
        
        if (fechaSeleccionada < fechaActual) {
            Swal.fire({
                icon: 'error',
                title: 'Fecha inválida',
                text: 'La fecha de la actividad no puede ser en el pasado'
            });
            return;
        }
        
        // Confirmación final
        const tituloVal = titulo.value;
        const fecha = new Date(fechaInicio.value);
        const fechaFormateada = fecha.toLocaleDateString('es-PE', { 
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        Swal.fire({
            title: '¿Planificar Actividad?',
            html: `
                <div class="text-start">
                    <p><strong>Título:</strong> ${tituloVal}</p>
                    <p><strong>Fecha:</strong> ${fechaFormateada}</p>
                    <p><strong>Ubicación:</strong> ${ubicacion.value}</p>
                    <p class="text-info mt-3">
                        <i class="ti ti-info-circle me-1"></i>
                        La actividad se creará en estado <strong>"Programado"</strong> y será <strong>gratuita</strong> con confirmación requerida.
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffa726',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, planificar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Contador de caracteres para textarea
    const descripcion = document.getElementById('descripcion');
    const observaciones = document.getElementById('observaciones');
    
    function actualizarContador(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const currentLength = textarea.value.length;
        const formText = textarea.nextElementSibling;
        if (formText && formText.classList.contains('form-text')) {
            formText.textContent = `${currentLength}/${maxLength} caracteres`;
            if (currentLength >= maxLength * 0.9) {
                formText.classList.add('text-warning');
            } else {
                formText.classList.remove('text-warning');
            }
        }
    }
    
    descripcion.addEventListener('input', function() {
        actualizarContador(this);
    });
    
    observaciones.addEventListener('input', function() {
        actualizarContador(this);
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalPlanificarActividad').addEventListener('hidden.bs.modal', function() {
        formPlanificarActividad.reset();
        formPlanificarActividad.classList.remove('was-validated');
        resumenActividad.style.display = 'none';
        fechaPreview.innerHTML = `
            <div class="fecha-preview-dia">--</div>
            <div class="fecha-preview-mes">Seleccione una fecha</div>
        `;
    });

    // Auto-guardar en localStorage (opcional)
    const autoGuardarCampos = ['titulo', 'descripcion', 'ubicacion', 'observaciones'];
    
    autoGuardarCampos.forEach(campo => {
        const elemento = document.getElementById(campo);
        
        // Restaurar valor guardado
        const valorGuardado = localStorage.getItem(`actividad_${campo}`);
        if (valorGuardado && !elemento.value) {
            elemento.value = valorGuardado;
        }
        
        // Guardar mientras escribe
        elemento.addEventListener('input', function() {
            localStorage.setItem(`actividad_${campo}`, this.value);
        });
    });
    
    // Limpiar localStorage al enviar
    formPlanificarActividad.addEventListener('submit', function() {
        autoGuardarCampos.forEach(campo => {
            localStorage.removeItem(`actividad_${campo}`);
        });
    });
});
</script>