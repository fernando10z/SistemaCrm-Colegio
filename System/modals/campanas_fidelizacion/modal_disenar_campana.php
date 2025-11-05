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
.campana-preview-card {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.tipo-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.tipo-option {
    position: relative;
}

.tipo-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.tipo-option label {
    display: block;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #fff;
}

.tipo-option input[type="radio"]:checked + label {
    border-color: #e91e63;
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
    font-weight: bold;
}

.tipo-option label:hover {
    border-color: #f48fb1;
    transform: translateY(-2px);
}

.tipo-option i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 5px;
    color: #e91e63;
}

.dirigido-selector {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.dirigido-option {
    flex: 1;
    min-width: 120px;
}

.dirigido-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.dirigido-option label {
    display: block;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #fff;
    font-size: 0.85rem;
}

.dirigido-option input[type="radio"]:checked + label {
    border-color: #9c27b0;
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    font-weight: bold;
}

.counter-display {
    font-size: 0.75rem;
    color: #757575;
    margin-top: 5px;
}

.caracteristica-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
}

.caracteristica-icon {
    font-size: 1.5rem;
    color: #e91e63;
}

.switch-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    background-color: #fff3e0;
    border-radius: 6px;
}

.form-switch .form-check-input:checked {
    background-color: #4caf50;
    border-color: #4caf50;
}
</style>

<!-- Modal Diseñar Campaña -->
<div class="modal fade" id="modalDisenarCampana" tabindex="-1" aria-labelledby="modalDisenarCampanaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #ec407a 0%, #e91e63 100%); color: white;">
        <h5 class="modal-title" id="modalDisenarCampanaLabel">
          <i class="ti ti-heart me-2"></i>
          Diseñar Campaña de Fidelización
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formDisenarCampana" method="POST" action="" novalidate>
        <input type="hidden" name="accion" value="disenar_campana">
        
        <div class="modal-body">
          
          <!-- Información Introductoria -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Campaña de Fidelización:</strong> Diseñe eventos y actividades especiales para fortalecer los vínculos con las familias. Puede configurar todos los detalles: tipo, costos, capacidad, confirmación y más.
          </div>

          <div class="row">
            <!-- Columna Izquierda: Información Principal -->
            <div class="col-md-7">
              
              <!-- Información Básica -->
              <div class="card mb-3">
                <div class="card-header" style="background-color: #fce4ec;">
                  <h6 class="mb-0">
                    <i class="ti ti-clipboard me-1"></i>
                    Información de la Campaña
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Título -->
                  <div class="mb-3">
                    <label for="titulo" class="form-label">
                      Título de la Campaña <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="titulo" name="titulo" 
                           required maxlength="200"
                           placeholder="Ej: Festival de Talentos 2025, Día de la Familia, etc.">
                    <div class="invalid-feedback">
                      Por favor ingrese el título de la campaña
                    </div>
                    <div class="counter-display">
                      <span id="titulo-counter">0</span>/200 caracteres
                    </div>
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label for="descripcion" class="form-label">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="descripcion" name="descripcion" 
                              rows="4" required maxlength="500"
                              placeholder="Describa el objetivo, actividades y beneficios de la campaña..."></textarea>
                    <div class="invalid-feedback">
                      Por favor ingrese la descripción
                    </div>
                    <div class="counter-display">
                      <span id="descripcion-counter">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Tipo de Campaña -->
                  <div class="mb-3">
                    <label class="form-label">
                      Tipo de Campaña <span class="text-danger">*</span>
                    </label>
                    <div class="tipo-selector">
                      <div class="tipo-option">
                        <input type="radio" id="tipo_evento_social" name="tipo" value="evento_social" required>
                        <label for="tipo_evento_social">
                          <i class="ti ti-confetti"></i>
                          <span>Evento Social</span>
                        </label>
                      </div>
                      <div class="tipo-option">
                        <input type="radio" id="tipo_reunion_padres" name="tipo" value="reunion_padres">
                        <label for="tipo_reunion_padres">
                          <i class="ti ti-users"></i>
                          <span>Reunión Padres</span>
                        </label>
                      </div>
                      <div class="tipo-option">
                        <input type="radio" id="tipo_charla" name="tipo" value="charla_informativa">
                        <label for="tipo_charla">
                          <i class="ti ti-presentation"></i>
                          <span>Charla</span>
                        </label>
                      </div>
                      <div class="tipo-option">
                        <input type="radio" id="tipo_academico" name="tipo" value="academico">
                        <label for="tipo_academico">
                          <i class="ti ti-book"></i>
                          <span>Académico</span>
                        </label>
                      </div>
                      <div class="tipo-option">
                        <input type="radio" id="tipo_deportivo" name="tipo" value="deportivo">
                        <label for="tipo_deportivo">
                          <i class="ti ti-ball-football"></i>
                          <span>Deportivo</span>
                        </label>
                      </div>
                      <div class="tipo-option">
                        <input type="radio" id="tipo_otro" name="tipo" value="otro">
                        <label for="tipo_otro">
                          <i class="ti ti-dots"></i>
                          <span>Otro</span>
                        </label>
                      </div>
                    </div>
                    <div class="invalid-feedback d-block" id="tipo-feedback" style="display: none;">
                      Por favor seleccione un tipo de campaña
                    </div>
                  </div>

                  <!-- Dirigido A -->
                  <div class="mb-3">
                    <label class="form-label">
                      Dirigido A <span class="text-danger">*</span>
                    </label>
                    <div class="dirigido-selector">
                      <div class="dirigido-option">
                        <input type="radio" id="dirigido_padres" name="dirigido_a" value="padres" required>
                        <label for="dirigido_padres">
                          <i class="ti ti-users me-1"></i>
                          Padres
                        </label>
                      </div>
                      <div class="dirigido-option">
                        <input type="radio" id="dirigido_estudiantes" name="dirigido_a" value="estudiantes">
                        <label for="dirigido_estudiantes">
                          <i class="ti ti-school me-1"></i>
                          Estudiantes
                        </label>
                      </div>
                      <div class="dirigido-option">
                        <input type="radio" id="dirigido_exalumnos" name="dirigido_a" value="exalumnos">
                        <label for="dirigido_exalumnos">
                          <i class="ti ti-certificate me-1"></i>
                          Exalumnos
                        </label>
                      </div>
                      <div class="dirigido-option">
                        <input type="radio" id="dirigido_general" name="dirigido_a" value="general">
                        <label for="dirigido_general">
                          <i class="ti ti-world me-1"></i>
                          General
                        </label>
                      </div>
                    </div>
                    <div class="invalid-feedback d-block" id="dirigido-feedback" style="display: none;">
                      Por favor seleccione el público objetivo
                    </div>
                  </div>

                </div>
              </div>

              <!-- Detalles del Evento -->
              <div class="card">
                <div class="card-header" style="background-color: #e1f5fe;">
                  <h6 class="mb-0">
                    <i class="ti ti-calendar me-1"></i>
                    Detalles del Evento
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Fechas -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="fecha_inicio" class="form-label">
                          Fecha y Hora de Inicio <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        <div class="invalid-feedback">
                          Por favor seleccione la fecha de inicio
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="fecha_fin" class="form-label">
                          Fecha y Hora de Fin
                          <small class="text-muted">(Opcional)</small>
                        </label>
                        <input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin">
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
                           placeholder="Ej: Auditorio Principal, Patio Central, etc.">
                    <div class="invalid-feedback">
                      Por favor ingrese la ubicación
                    </div>
                  </div>

                  <!-- Capacidad y Costo -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="capacidad_maxima" class="form-label">
                          Capacidad Máxima
                          <small class="text-muted">(Opcional)</small>
                        </label>
                        <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima" 
                               min="1" max="10000"
                               placeholder="Dejar vacío = ilimitado">
                        <small class="text-muted">Dejar vacío para capacidad ilimitada</small>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="costo" class="form-label">
                          Costo (S/)
                        </label>
                        <input type="number" class="form-control" id="costo" name="costo" 
                               min="0" step="0.01" value="0.00"
                               placeholder="0.00">
                        <small class="text-muted">0.00 = Gratuito</small>
                      </div>
                    </div>
                  </div>

                  <!-- Observaciones -->
                  <div class="mb-3">
                    <label for="observaciones" class="form-label">
                      Observaciones
                    </label>
                    <textarea class="form-control" id="observaciones" name="observaciones" 
                              rows="3" maxlength="500"
                              placeholder="Información adicional, requisitos especiales, etc."></textarea>
                    <div class="counter-display">
                      <span id="observaciones-counter">0</span>/500 caracteres
                    </div>
                  </div>

                </div>
              </div>

            </div>

            <!-- Columna Derecha: Configuración y Preview -->
            <div class="col-md-5">
              
              <!-- Configuración -->
              <div class="card mb-3">
                <div class="card-header" style="background-color: #fff9c4;">
                  <h6 class="mb-0">
                    <i class="ti ti-settings me-1"></i>
                    Configuración
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Requiere Confirmación -->
                  <div class="switch-container mb-3">
                    <div>
                      <strong>¿Requiere Confirmación?</strong>
                      <br>
                      <small class="text-muted">Los participantes deben confirmar su asistencia</small>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="requiere_confirmacion" name="requiere_confirmacion" checked>
                      <label class="form-check-label" for="requiere_confirmacion"></label>
                    </div>
                  </div>

                  <!-- Características Automáticas -->
                  <div class="caracteristica-item">
                    <i class="ti ti-calendar-check caracteristica-icon"></i>
                    <div>
                      <strong>Estado Inicial</strong>
                      <br>
                      <small class="text-muted">Programado</small>
                    </div>
                  </div>

                  <div class="caracteristica-item" id="invitaciones-automaticas" style="display: none;">
                    <i class="ti ti-mail caracteristica-icon"></i>
                    <div>
                      <strong>Invitaciones Automáticas</strong>
                      <br>
                      <small class="text-muted">Se enviarán al crear la campaña</small>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Tips -->
              <div class="alert alert-success" role="alert">
                <h6 class="alert-heading">
                  <i class="ti ti-bulb me-1"></i>
                  Tips para una Campaña Exitosa
                </h6>
                <ul class="mb-0 mt-2 small">
                  <li>Use títulos <strong>atractivos</strong> y descriptivos</li>
                  <li>Describa claramente los <strong>beneficios</strong></li>
                  <li>Planifique con <strong>anticipación</strong> (mínimo 2 semanas)</li>
                  <li>Considere la <strong>disponibilidad</strong> de las familias</li>
                  <li>Ofrezca <strong>incentivos</strong> para asistencia</li>
                </ul>
              </div>

              <!-- Preview -->
              <div class="campana-preview-card" id="campana-preview" style="display: none;">
                <h6 class="mb-2">
                  <i class="ti ti-eye me-1"></i>
                  Vista Previa
                </h6>
                <div id="preview-content">
                  <div><strong>Título:</strong> <span id="preview-titulo">-</span></div>
                  <div><strong>Tipo:</strong> <span id="preview-tipo">-</span></div>
                  <div><strong>Dirigido a:</strong> <span id="preview-dirigido">-</span></div>
                  <div><strong>Fecha:</strong> <span id="preview-fecha">-</span></div>
                  <div><strong>Ubicación:</strong> <span id="preview-ubicacion">-</span></div>
                  <div><strong>Costo:</strong> <span id="preview-costo">-</span></div>
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
          <!-- <button type="button" class="btn btn-outline-info" id="btn-preview-campana">
            <i class="ti ti-eye me-1"></i>
            Vista Previa
          </button> -->
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-heart me-1"></i>
            Diseñar Campaña
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formDisenarCampana = document.getElementById('formDisenarCampana');
    const titulo = document.getElementById('titulo');
    const descripcion = document.getElementById('descripcion');
    const observaciones = document.getElementById('observaciones');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const ubicacion = document.getElementById('ubicacion');
    const costo = document.getElementById('costo');
    const btnPreview = document.getElementById('btn-preview-campana');
    const campanaPreview = document.getElementById('campana-preview');
    const invitacionesAuto = document.getElementById('invitaciones-automaticas');
    
    // Configurar fecha mínima (hoy)
    const ahora = new Date();
    const year = ahora.getFullYear();
    const month = String(ahora.getMonth() + 1).padStart(2, '0');
    const day = String(ahora.getDate()).padStart(2, '0');
    const hour = String(ahora.getHours()).padStart(2, '0');
    const minute = String(ahora.getMinutes()).padStart(2, '0');
    const fechaMinima = `${year}-${month}-${day}T${hour}:${minute}`;
    fechaInicio.setAttribute('min', fechaMinima);

    // Contadores de caracteres
    function actualizarContador(input, counterId) {
        const counter = document.getElementById(counterId);
        counter.textContent = input.value.length;
        const maxLength = input.getAttribute('maxlength');
        if (input.value.length >= maxLength * 0.9) {
            counter.classList.add('text-warning');
        } else {
            counter.classList.remove('text-warning');
        }
    }

    titulo.addEventListener('input', function() {
        actualizarContador(this, 'titulo-counter');
    });

    descripcion.addEventListener('input', function() {
        actualizarContador(this, 'descripcion-counter');
    });

    observaciones.addEventListener('input', function() {
        actualizarContador(this, 'observaciones-counter');
    });

    // Validar fecha fin mayor que fecha inicio
    fechaInicio.addEventListener('change', function() {
        if (fechaFin.value && fechaFin.value < this.value) {
            fechaFin.value = '';
            Swal.fire({
                icon: 'warning',
                title: 'Fecha inválida',
                text: 'La fecha de fin debe ser posterior a la fecha de inicio'
            });
        }
        fechaFin.setAttribute('min', this.value);
    });

    // Mostrar invitaciones automáticas según tipo y dirigido_a
    function actualizarInvitacionesAuto() {
        const tipo = document.querySelector('input[name="tipo"]:checked');
        const dirigido = document.querySelector('input[name="dirigido_a"]:checked');
        
        if ((tipo && tipo.value === 'evento_social') || (dirigido && dirigido.value === 'padres')) {
            invitacionesAuto.style.display = 'flex';
        } else {
            invitacionesAuto.style.display = 'none';
        }
    }

    document.querySelectorAll('input[name="tipo"]').forEach(input => {
        input.addEventListener('change', actualizarInvitacionesAuto);
    });

    document.querySelectorAll('input[name="dirigido_a"]').forEach(input => {
        input.addEventListener('change', actualizarInvitacionesAuto);
    });

    // Vista previa
    btnPreview.addEventListener('click', function() {
        const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked');
        const dirigidoSeleccionado = document.querySelector('input[name="dirigido_a"]:checked');
        
        if (!titulo.value || !tipoSeleccionado || !dirigidoSeleccionado || !fechaInicio.value || !ubicacion.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Información incompleta',
                text: 'Complete al menos: título, tipo, dirigido a, fecha y ubicación'
            });
            return;
        }

        // Actualizar preview
        document.getElementById('preview-titulo').textContent = titulo.value;
        document.getElementById('preview-tipo').textContent = tipoSeleccionado.nextElementSibling.textContent.trim();
        document.getElementById('preview-dirigido').textContent = dirigidoSeleccionado.nextElementSibling.textContent.trim();
        
        const fecha = new Date(fechaInicio.value);
        const fechaFormateada = fecha.toLocaleDateString('es-PE', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById('preview-fecha').textContent = fechaFormateada;
        document.getElementById('preview-ubicacion').textContent = ubicacion.value;
        
        const costoVal = parseFloat(costo.value || 0);
        document.getElementById('preview-costo').textContent = costoVal > 0 ? `S/ ${costoVal.toFixed(2)}` : 'Gratuito';
        
        campanaPreview.style.display = 'block';
        campanaPreview.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // Validación y envío del formulario
    formDisenarCampana.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar campos requeridos
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            
            // Mostrar feedback para radios
            const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked');
            if (!tipoSeleccionado) {
                document.getElementById('tipo-feedback').style.display = 'block';
            }
            
            const dirigidoSeleccionado = document.querySelector('input[name="dirigido_a"]:checked');
            if (!dirigidoSeleccionado) {
                document.getElementById('dirigido-feedback').style.display = 'block';
            }
            
            return;
        }

        // Ocultar feedbacks
        document.getElementById('tipo-feedback').style.display = 'none';
        document.getElementById('dirigido-feedback').style.display = 'none';
        
        // Validar fechas
        const fechaInicioVal = new Date(fechaInicio.value);
        const fechaActual = new Date();
        
        if (fechaInicioVal < fechaActual) {
            Swal.fire({
                icon: 'error',
                title: 'Fecha inválida',
                text: 'La fecha de inicio no puede ser en el pasado'
            });
            return;
        }

        if (fechaFin.value) {
            const fechaFinVal = new Date(fechaFin.value);
            if (fechaFinVal < fechaInicioVal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha inválida',
                    text: 'La fecha de fin debe ser posterior a la fecha de inicio'
                });
                return;
            }
        }

        // Confirmación final
        const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked');
        const dirigidoSeleccionado = document.querySelector('input[name="dirigido_a"]:checked');
        
        Swal.fire({
            title: '¿Diseñar Campaña?',
            html: `
                <div class="text-start">
                    <p><strong>Título:</strong> ${titulo.value}</p>
                    <p><strong>Tipo:</strong> ${tipoSeleccionado.nextElementSibling.textContent.trim()}</p>
                    <p><strong>Dirigido a:</strong> ${dirigidoSeleccionado.nextElementSibling.textContent.trim()}</p>
                    <p><strong>Fecha:</strong> ${new Date(fechaInicio.value).toLocaleDateString('es-PE')}</p>
                    <p class="text-info mt-3">
                        <i class="ti ti-info-circle me-1"></i>
                        La campaña se creará en estado <strong>"Programado"</strong>
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#e91e63',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, diseñar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Limpiar al cerrar modal
    document.getElementById('modalDisenarCampana').addEventListener('hidden.bs.modal', function() {
        formDisenarCampana.reset();
        formDisenarCampana.classList.remove('was-validated');
        campanaPreview.style.display = 'none';
        invitacionesAuto.style.display = 'none';
        document.getElementById('tipo-feedback').style.display = 'none';
        document.getElementById('dirigido-feedback').style.display = 'none';
        document.getElementById('titulo-counter').textContent = '0';
        document.getElementById('descripcion-counter').textContent = '0';
        document.getElementById('observaciones-counter').textContent = '0';
    });
});
</script>