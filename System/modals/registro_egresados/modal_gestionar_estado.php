<!-- Modal Gestionar Estado -->
<div class="modal fade" id="modalGestionarEstado" tabindex="-1" aria-labelledby="modalGestionarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="modalGestionarEstadoLabel">
          <i class="ti ti-settings me-2"></i>
          Gestionar Estado de Contacto
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGestionarEstado" method="POST" action="acciones/registro_egresados/registro_egresado.php" novalidate>
        <input type="hidden" name="accion" value="gestionar_estado">
        <input type="hidden" name="egresado_id" id="estado_egresado_id">
        
        <div class="modal-body">
          
          <!-- Información del Egresado -->
          <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0">
                <i class="ti ti-user me-1"></i>
                Información del Egresado
              </h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <strong>Nombre:</strong> <span id="estado_nombre_egresado">-</span><br>
                  <strong>Código:</strong> <span id="estado_codigo_egresado">-</span><br>
                  <strong>Documento:</strong> <span id="estado_documento_egresado">-</span>
                </div>
                <div class="col-md-6">
                  <strong>Promoción:</strong> <span id="estado_promocion_egresado">-</span><br>
                  <strong>Email:</strong> <span id="estado_email_egresado">-</span><br>
                  <strong>Teléfono:</strong> <span id="estado_telefono_egresado">-</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Estado Actual vs Nuevo Estado -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                  <h6 class="mb-0">Estado Actual</h6>
                </div>
                <div class="card-body text-center">
                  <div class="badge-estado-contacto p-3 rounded" id="estado_actual_badge">
                    <i class="ti ti-user-circle" style="font-size: 2rem;"></i>
                    <div class="mt-2">
                      <strong id="estado_actual_texto">-</strong>
                    </div>
                    <small id="estado_actual_descripcion">-</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-success">
                <div class="card-header bg-success text-white">
                  <h6 class="mb-0">Nuevo Estado</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label for="nuevo_estado_contacto" class="form-label">
                      Cambiar a <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="nuevo_estado_contacto" name="nuevo_estado_contacto" required>
                      <option value="">Seleccionar nuevo estado</option>
                      <option value="activo">Activo - Mantiene contacto regular</option>
                      <option value="sin_contacto">Sin contacto - No es posible localizarlo</option>
                      <option value="no_contactar">No contactar - No desea recibir comunicaciones</option>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un nuevo estado de contacto
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Motivo y Detalles del Cambio -->
          <div class="card border-info mb-4">
            <div class="card-header bg-info text-white">
              <h6 class="mb-0">
                <i class="ti ti-note me-1"></i>
                Detalles del Cambio
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Motivo del Cambio -->
              <div class="mb-3">
                <label for="motivo_cambio" class="form-label">
                  Motivo del Cambio <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="motivo_cambio" name="motivo_cambio" required>
                  <option value="">Seleccionar motivo</option>
                  <option value="actualizacion_datos">Actualización de datos del egresado</option>
                  <option value="contacto_exitoso">Se logró establecer contacto exitoso</option>
                  <option value="telefono_desactualizado">Teléfono/email desactualizado o inválido</option>
                  <option value="cambio_residencia">Cambio de residencia sin datos actuales</option>
                  <option value="solicitud_no_contactar">Egresado solicitó no ser contactado</option>
                  <option value="fallecimiento">Fallecimiento del egresado</option>
                  <option value="datos_incorrectos">Datos de contacto incorrectos</option>
                  <option value="actualizacion_laboral">Actualización de situación laboral</option>
                  <option value="reactivacion_contacto">Reactivación de contacto</option>
                  <option value="otro">Otro motivo (especificar en observaciones)</option>
                </select>
                <div class="invalid-feedback">
                  Debe especificar el motivo del cambio de estado
                </div>
              </div>

              <!-- Fecha Efectiva del Cambio -->
              <div class="mb-3">
                <label for="fecha_cambio" class="form-label">
                  Fecha Efectiva del Cambio <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control" id="fecha_cambio" name="fecha_cambio" 
                       required 
                       max="<?php echo date('Y-m-d'); ?>"
                       min="2020-01-01"
                       value="<?php echo date('Y-m-d'); ?>">
                <div class="invalid-feedback">
                  Debe especificar una fecha válida (no puede ser futura)
                </div>
                <small class="form-text text-muted">
                  La fecha no puede ser posterior a hoy
                </small>
              </div>

              <!-- Medio de Verificación -->
              <div class="mb-3">
                <label for="medio_verificacion" class="form-label">
                  ¿Cómo se verificó este cambio?
                </label>
                <select class="form-select" id="medio_verificacion" name="medio_verificacion">
                  <option value="">No especificar</option>
                  <option value="llamada_telefonica">Llamada telefónica</option>
                  <option value="mensaje_whatsapp">Mensaje de WhatsApp</option>
                  <option value="correo_electronico">Correo electrónico</option>
                  <option value="redes_sociales">Redes sociales</option>
                  <option value="terceros">Información de terceros</option>
                  <option value="visita_presencial">Visita presencial</option>
                  <option value="evento_colegio">Evento del colegio</option>
                  <option value="actualizacion_voluntaria">Egresado actualizó voluntariamente</option>
                  <option value="investigacion_propia">Investigación propia</option>
                </select>
              </div>

              <!-- Observaciones Detalladas -->
              <div class="mb-3">
                <label for="observaciones_cambio" class="form-label">
                  Observaciones y Detalles <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="observaciones_cambio" name="observaciones_cambio" 
                          rows="4" 
                          maxlength="1000"
                          required
                          placeholder="Describa detalladamente las circunstancias del cambio de estado, información adicional relevante, acciones tomadas, etc."
                          title="Proporcione información detallada sobre el cambio"></textarea>
                <div class="invalid-feedback">
                  Debe proporcionar observaciones detalladas sobre el cambio
                </div>
                <div class="form-text">
                  <span id="contador_caracteres">0</span>/1000 caracteres | 
                  <strong>Requerido:</strong> Mínimo 20 caracteres
                </div>
              </div>

            </div>
          </div>

          <!-- Configuración de Comunicaciones -->
          <div class="card border-warning" id="config_comunicaciones_card" style="display: none;">
            <div class="card-header bg-warning text-dark">
              <h6 class="mb-0">
                <i class="ti ti-mail-check me-1"></i>
                Configuración de Comunicaciones
              </h6>
            </div>
            <div class="card-body">
              
              <div class="mb-3">
                <label class="form-label">¿El egresado acepta recibir comunicaciones?</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="acepta_comunicaciones_cambio" 
                         id="acepta_si" value="1">
                  <label class="form-check-label text-success" for="acepta_si">
                    <i class="ti ti-check me-1"></i>
                    Sí, acepta recibir comunicaciones del colegio
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="acepta_comunicaciones_cambio" 
                         id="acepta_no" value="0">
                  <label class="form-check-label text-danger" for="acepta_no">
                    <i class="ti ti-x me-1"></i>
                    No, no desea recibir comunicaciones
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="acepta_comunicaciones_cambio" 
                         id="acepta_mantener" value="mantener" checked>
                  <label class="form-check-label text-muted" for="acepta_mantener">
                    <i class="ti ti-minus me-1"></i>
                    Mantener configuración actual
                  </label>
                </div>
              </div>

              <div class="alert alert-info" role="alert">
                <i class="ti ti-info-circle me-1"></i>
                <strong>Nota:</strong> Esta configuración solo se aplica si el nuevo estado es "Activo". 
                Para otros estados, automáticamente se deshabilitan las comunicaciones.
              </div>

            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" onclick="previsualizarCambio()">
            <i class="ti ti-eye me-1"></i>
            Previsualizar
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="ti ti-device-floppy me-1"></i>
            Aplicar Cambio
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const estadoForm = document.getElementById('formGestionarEstado');
    const nuevoEstado = document.getElementById('nuevo_estado_contacto');
    const motivoCambio = document.getElementById('motivo_cambio');
    const observacionesCambio = document.getElementById('observaciones_cambio');
    const contadorCaracteres = document.getElementById('contador_caracteres');
    const configComunicacionesCard = document.getElementById('config_comunicaciones_card');

    // Contador de caracteres en tiempo real
    observacionesCambio.addEventListener('input', function() {
        const longitud = this.value.length;
        contadorCaracteres.textContent = longitud;
        
        // Cambiar color según la longitud
        if (longitud < 20) {
            contadorCaracteres.className = 'text-danger fw-bold';
            this.setCustomValidity('Mínimo 20 caracteres requeridos');
        } else if (longitud < 50) {
            contadorCaracteres.className = 'text-warning fw-bold';
            this.setCustomValidity('');
        } else {
            contadorCaracteres.className = 'text-success fw-bold';
            this.setCustomValidity('');
        }
        
        // Validar máximo
        if (longitud > 1000) {
            this.value = this.value.substring(0, 1000);
            contadorCaracteres.textContent = '1000';
        }
    });

    // Mostrar/ocultar configuración de comunicaciones según el estado
    nuevoEstado.addEventListener('change', function() {
        const valor = this.value;
        
        if (valor === 'activo') {
            configComunicacionesCard.style.display = 'block';
        } else {
            configComunicacionesCard.style.display = 'none';
            // Resetear selección
            document.getElementById('acepta_mantener').checked = true;
        }
        
        // Actualizar descripción del estado
        actualizarDescripcionEstado(valor);
    });

    // Validar que el nuevo estado sea diferente al actual
    nuevoEstado.addEventListener('change', function() {
        const estadoActual = document.getElementById('estado_actual_texto').textContent.toLowerCase();
        const nuevoEstadoValor = this.value;
        
        if (estadoActual === nuevoEstadoValor) {
            this.setCustomValidity('El nuevo estado debe ser diferente al estado actual');
            
            Swal.fire({
                title: 'Estado Duplicado',
                text: 'El estado seleccionado es el mismo que el estado actual. Seleccione un estado diferente.',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
        } else {
            this.setCustomValidity('');
        }
    });

    // Validación de fecha (no puede ser futura)
    document.getElementById('fecha_cambio').addEventListener('change', function() {
        const fechaSeleccionada = new Date(this.value);
        const hoy = new Date();
        hoy.setHours(23, 59, 59, 999); // Hasta el final del día
        
        if (fechaSeleccionada > hoy) {
            this.setCustomValidity('La fecha no puede ser posterior a hoy');
            
            Swal.fire({
                title: 'Fecha Inválida',
                text: 'La fecha efectiva del cambio no puede ser posterior a la fecha actual.',
                icon: 'error',
                confirmButtonText: 'Corregir'
            });
        } else {
            this.setCustomValidity('');
        }
    });

    // Validación del formulario
    estadoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validación personalizada de observaciones
        const observaciones = observacionesCambio.value.trim();
        if (observaciones.length < 20) {
            observacionesCambio.setCustomValidity('Mínimo 20 caracteres requeridos');
        }
        
        if (!estadoForm.checkValidity()) {
            e.stopPropagation();
            estadoForm.classList.add('was-validated');
            
            Swal.fire({
                title: 'Formulario Incompleto',
                text: 'Por favor complete todos los campos requeridos correctamente',
                icon: 'error',
                confirmButtonText: 'Revisar'
            });
            
            return false;
        }
        
        // Confirmación final
        const nuevoEstadoTexto = nuevoEstado.options[nuevoEstado.selectedIndex].text;
        const motivoTexto = motivoCambio.options[motivoCambio.selectedIndex].text;
        
        Swal.fire({
            title: '¿Confirmar Cambio de Estado?',
            html: `
                <strong>Nuevo Estado:</strong> ${nuevoEstadoTexto}<br>
                <strong>Motivo:</strong> ${motivoTexto}<br>
                <strong>Fecha:</strong> ${document.getElementById('fecha_cambio').value}<br><br>
                <em>Esta acción se registrará en el historial del egresado.</em>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, aplicar cambio',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                estadoForm.submit();
            }
        });
    });

    // Función para cargar datos del egresado en el modal
    window.cargarEstadoEgresado = function(egresadoId, nombre, estadoActual) {
        // Cargar información básica
        document.getElementById('estado_egresado_id').value = egresadoId;
        document.getElementById('estado_nombre_egresado').textContent = nombre;
        
        // Mostrar estado actual
        document.getElementById('estado_actual_texto').textContent = estadoActual.replace('_', ' ');
        
        // Configurar badge del estado actual
        const badgeActual = document.getElementById('estado_actual_badge');
        badgeActual.className = `badge-estado-contacto estado-${estadoActual} p-3 rounded text-center`;
        
        let descripcionActual = '';
        switch(estadoActual) {
            case 'activo':
                descripcionActual = 'Mantiene contacto regular';
                break;
            case 'sin_contacto':
                descripcionActual = 'No es posible contactarlo';
                break;
            case 'no_contactar':
                descripcionActual = 'No desea comunicaciones';
                break;
        }
        document.getElementById('estado_actual_descripcion').textContent = descripcionActual;
        
        // Aquí harías una llamada AJAX para obtener más detalles del egresado
        // Por ahora, datos de ejemplo:
        console.log('Cargando estado del egresado:', egresadoId, nombre, estadoActual);
    };

    // Función para previsualizar el cambio
    window.previsualizarCambio = function() {
        if (!estadoForm.checkValidity()) {
            Swal.fire({
                title: 'Formulario Incompleto',
                text: 'Complete todos los campos requeridos antes de previsualizar',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        const estadoActual = document.getElementById('estado_actual_texto').textContent;
        const nuevoEstadoTexto = nuevoEstado.options[nuevoEstado.selectedIndex].text;
        const motivoTexto = motivoCambio.options[motivoCambio.selectedIndex].text;
        const fecha = document.getElementById('fecha_cambio').value;
        const observaciones = observacionesCambio.value;
        
        Swal.fire({
            title: 'Previsualización del Cambio',
            html: `
                <div class="text-start">
                    <strong>📋 Resumen del Cambio:</strong><br><br>
                    <strong>Estado Actual:</strong> ${estadoActual}<br>
                    <strong>Nuevo Estado:</strong> ${nuevoEstadoTexto}<br>
                    <strong>Motivo:</strong> ${motivoTexto}<br>
                    <strong>Fecha Efectiva:</strong> ${fecha}<br><br>
                    <strong>Observaciones:</strong><br>
                    <em>${observaciones}</em>
                </div>
            `,
            icon: 'info',
            width: '600px',
            confirmButtonText: 'Entendido'
        });
    };

    // Función auxiliar para actualizar descripción del estado
    function actualizarDescripcionEstado(estado) {
        // Esta función podría agregar más detalles visuales del estado seleccionado
        console.log('Estado seleccionado:', estado);
    }

    // Limpiar formulario cuando se cierra el modal
    document.getElementById('modalGestionarEstado').addEventListener('hidden.bs.modal', function() {
        estadoForm.reset();
        estadoForm.classList.remove('was-validated');
        configComunicacionesCard.style.display = 'none';
        contadorCaracteres.textContent = '0';
        contadorCaracteres.className = 'text-muted';
        document.getElementById('acepta_mantener').checked = true;
    });

    // Autocompletar algunos campos según el motivo seleccionado
    motivoCambio.addEventListener('change', function() {
        const motivo = this.value;
        const observaciones = observacionesCambio;
        
        // Solo autocompletar si está vacío
        if (observaciones.value.trim() === '') {
            let textoBase = '';
            
            switch(motivo) {
                case 'contacto_exitoso':
                    textoBase = 'Se logró establecer contacto exitoso con el egresado. ';
                    break;
                case 'telefono_desactualizado':
                    textoBase = 'Los datos de contacto (teléfono/email) están desactualizados o son inválidos. ';
                    break;
                case 'solicitud_no_contactar':
                    textoBase = 'El egresado solicitó expresamente no ser contactado por el colegio. ';
                    break;
                case 'cambio_residencia':
                    textoBase = 'El egresado cambió de residencia y no se cuenta con datos actualizados. ';
                    break;
            }
            
            if (textoBase) {
                observaciones.value = textoBase;
                observaciones.dispatchEvent(new Event('input'));
            }
        }
    });
});
</script>