<!-- Modal Convertir Referido -->
<div class="modal fade" id="modalConvertirReferido" tabindex="-1" aria-labelledby="modalConvertirReferidoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalConvertirReferidoLabel">
          <i class="ti ti-check-circle me-2"></i>
          Marcar Lead como Convertido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formConvertirReferido" method="POST" action="acciones/leads_referidos/gestionar_referido.php" novalidate>
        <input type="hidden" name="accion" value="convertir_referido">
        <input type="hidden" name="referido_id" id="conv_referido_id">
        
        <div class="modal-body">
          
          <!-- Información del Referido -->
          <div class="alert alert-success" role="alert">
            <i class="ti ti-check me-1"></i>
            <strong>Conversión de Lead Referido</strong>
            <p class="mb-0 mt-2">
              Se registrará que el lead ha completado el proceso de matrícula y se ha convertido 
              en estudiante. Esta información actualizará las estadísticas de conversión del código de referido.
            </p>
          </div>

          <div class="row">
            
            <!-- Información del Lead -->
            <div class="col-12">
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-user-check me-1"></i>
                    Información del Lead
                  </h6>
                </div>
                <div class="card-body">
                  <div id="conv_info_lead">
                    <p class="text-muted text-center">Cargando información del lead...</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fecha de Conversión -->
            <div class="col-12">
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-calendar-check me-1"></i>
                    Datos de Conversión
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Fecha de Conversión -->
                  <div class="mb-3">
                    <label for="conv_fecha_conversion" class="form-label">
                      Fecha de Conversión (Matrícula) <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="conv_fecha_conversion" name="fecha_conversion" 
                           required 
                           max="<?php echo date('Y-m-d'); ?>"
                           title="Fecha en que el lead se matriculó">
                    <div class="invalid-feedback">
                      La fecha de conversión es obligatoria y no puede ser futura
                    </div>
                    <small class="form-text text-muted">
                      Fecha en que el lead completó el proceso de matrícula
                    </small>
                    <div id="conv_fecha_validacion" class="form-text"></div>
                  </div>

                  <!-- Validación de fechas -->
                  <div class="alert alert-warning" role="alert" id="conv_alerta_fechas" style="display: none;">
                    <i class="ti ti-alert-triangle me-1"></i>
                    <strong>Advertencia:</strong>
                    <ul class="mb-0 mt-2" id="conv_fechas_validacion_lista"></ul>
                  </div>

                  <!-- Observaciones de conversión -->
                  <div class="mb-3">
                    <label for="conv_observaciones_conversion" class="form-label">
                      Observaciones de la Conversión
                    </label>
                    <textarea class="form-control" id="conv_observaciones_conversion" name="observaciones_conversion" 
                              rows="3" maxlength="500"
                              placeholder="Ej: Completó matrícula satisfactoriamente. Aplicó beneficio del código de referido..."
                              title="Información adicional sobre el proceso de conversión"></textarea>
                    <div class="form-text">
                      <span id="conv_obs_contador">0</span>/500 caracteres
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Confirmación de Datos -->
            <div class="col-12">
              <div class="card border-success">
                <div class="card-header bg-success text-white">
                  <h6 class="mb-0">
                    <i class="ti ti-checklist me-1"></i>
                    Confirmación de Conversión
                  </h6>
                </div>
                <div class="card-body">
                  
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="conv_confirmar_datos" required>
                    <label class="form-check-label" for="conv_confirmar_datos">
                      <strong>Confirmo que los datos son correctos</strong>
                      <br>
                      <small class="text-muted">
                        He verificado que el lead completó el proceso de matrícula y la fecha es correcta
                      </small>
                    </label>
                    <div class="invalid-feedback">
                      Debe confirmar que los datos son correctos
                    </div>
                  </div>

                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="conv_confirmar_beneficio" required>
                    <label class="form-check-label" for="conv_confirmar_beneficio">
                      <strong>Confirmo aplicación de beneficio</strong>
                      <br>
                      <small class="text-muted">
                        Se aplicó el beneficio correspondiente al código de referido (si aplica)
                      </small>
                    </label>
                    <div class="invalid-feedback">
                      Debe confirmar la aplicación del beneficio
                    </div>
                  </div>

                  <div class="alert alert-info mb-0" role="alert">
                    <i class="ti ti-info-circle me-1"></i>
                    <strong>Importante:</strong> Esta acción:
                    <ul class="mb-0 mt-2">
                      <li>Marcará el lead como convertido definitivamente</li>
                      <li>Actualizará las estadísticas del código de referido</li>
                      <li>Calculará la tasa de conversión automáticamente</li>
                      <li>Puede requerir notificar al referente del beneficio</li>
                    </ul>
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
          <button type="button" class="btn btn-info" onclick="validarConversion()">
            <i class="ti ti-check me-1"></i>
            Validar Datos
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-device-floppy me-1"></i>
            Confirmar Conversión
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const convForm = document.getElementById('formConvertirReferido');
    const convFechaConversion = document.getElementById('conv_fecha_conversion');
    const convObservaciones = document.getElementById('conv_observaciones_conversion');
    const convConfirmarDatos = document.getElementById('conv_confirmar_datos');
    const convConfirmarBeneficio = document.getElementById('conv_confirmar_beneficio');
    
    // Establecer fecha actual por defecto
    convFechaConversion.value = new Date().toISOString().split('T')[0];

    // Validar fecha de conversión
    convFechaConversion.addEventListener('change', function() {
        const fechaConversion = new Date(this.value);
        const fechaActual = new Date();
        fechaActual.setHours(0, 0, 0, 0);
        
        const validacionDiv = document.getElementById('conv_fecha_validacion');
        const alertaDiv = document.getElementById('conv_alerta_fechas');
        const listaValidacion = document.getElementById('conv_fechas_validacion_lista');
        
        const advertencias = [];
        
        // Validar que no sea futura
        if (fechaConversion > fechaActual) {
            this.setCustomValidity('La fecha de conversión no puede ser futura');
            this.classList.add('is-invalid');
            validacionDiv.innerHTML = '<span class="validacion-error"><i class="ti ti-x"></i> La fecha no puede ser futura</span>';
            return;
        }
        
        // Validar que no sea muy antigua (más de 1 año)
        const unAñoAtras = new Date();
        unAñoAtras.setFullYear(unAñoAtras.getFullYear() - 1);
        
        if (fechaConversion < unAñoAtras) {
            advertencias.push('La fecha de conversión es de hace más de 1 año');
        }
        
        // Validar con respecto a la fecha de uso (si está disponible)
        const fechaUsoStr = document.getElementById('conv_info_fecha_uso')?.textContent;
        if (fechaUsoStr && fechaUsoStr !== '-') {
            const fechaUso = new Date(fechaUsoStr);
            
            if (fechaConversion < fechaUso) {
                advertencias.push('La fecha de conversión es anterior a la fecha de uso del código');
            }
            
            const diasDiferencia = Math.floor((fechaConversion - fechaUso) / (1000 * 60 * 60 * 24));
            
            if (diasDiferencia === 0) {
                advertencias.push('La conversión fue el mismo día del uso del código (verificar si es correcto)');
            } else if (diasDiferencia > 180) {
                advertencias.push(`Pasaron ${diasDiferencia} días entre el uso del código y la conversión`);
            }
        }
        
        // Mostrar advertencias
        if (advertencias.length > 0) {
            listaValidacion.innerHTML = advertencias.map(a => `<li>${a}</li>`).join('');
            alertaDiv.style.display = 'block';
        } else {
            alertaDiv.style.display = 'none';
        }
        
        // Validación exitosa
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        validacionDiv.innerHTML = '<span class="validacion-ok"><i class="ti ti-check"></i> Fecha válida</span>';
    });

    // Contador de caracteres para observaciones
    convObservaciones.addEventListener('input', function() {
        const contador = document.getElementById('conv_obs_contador');
        contador.textContent = this.value.length;
        
        if (this.value.length > 450) {
            contador.classList.add('text-warning');
        } else {
            contador.classList.remove('text-warning');
        }
    });

    // Función para validar conversión
    window.validarConversion = function() {
        const validaciones = [];
        const fecha = convFechaConversion.value;
        const confirmarDatos = convConfirmarDatos.checked;
        const confirmarBeneficio = convConfirmarBeneficio.checked;
        
        // Validar fecha
        if (!fecha) {
            validaciones.push('Debe ingresar la fecha de conversión');
        } else {
            const fechaConversion = new Date(fecha);
            const fechaActual = new Date();
            fechaActual.setHours(0, 0, 0, 0);
            
            if (fechaConversion > fechaActual) {
                validaciones.push('La fecha de conversión no puede ser futura');
            }
        }
        
        // Validar confirmaciones
        if (!confirmarDatos) {
            validaciones.push('Debe confirmar que los datos son correctos');
        }
        
        if (!confirmarBeneficio) {
            validaciones.push('Debe confirmar la aplicación del beneficio');
        }
        
        if (validaciones.length > 0) {
            Swal.fire({
                title: 'Validación Incompleta',
                html: '<ul class="text-start">' + validaciones.map(v => `<li>${v}</li>`).join('') + '</ul>',
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
        } else {
            Swal.fire({
                title: 'Validación Exitosa',
                html: `
                    <div class="text-start">
                        <p><strong>Fecha de conversión:</strong> ${fecha}</p>
                        <p><strong>Observaciones:</strong> ${convObservaciones.value || 'Ninguna'}</p>
                        <hr>
                        <p class="text-success"><i class="ti ti-check-circle"></i> Todos los datos son correctos</p>
                        <p class="text-muted"><small>Proceda a confirmar la conversión</small></p>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
        }
    };

    // Validación del formulario al enviar
    convForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!convForm.checkValidity()) {
            e.stopPropagation();
            convForm.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor complete todos los campos requeridos y confirmaciones',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            
            return false;
        }
        
        // Confirmar antes de convertir
        const leadNombre = document.getElementById('conv_info_lead_nombre')?.textContent || 'el lead';
        const fecha = convFechaConversion.value;
        
        Swal.fire({
            title: '¿Confirmar Conversión?',
            html: `
                <div class="text-start">
                    <p>Se marcará como convertido:</p>
                    <hr>
                    <p><strong>Lead:</strong> ${leadNombre}</p>
                    <p><strong>Fecha de conversión:</strong> ${fecha}</p>
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <i class="ti ti-alert-triangle me-1"></i>
                        <strong>Importante:</strong> Esta acción actualizará permanentemente el estado del lead 
                        y las estadísticas del código de referido.
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, confirmar conversión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando conversión...',
                    html: 'Actualizando datos del lead referido',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                convForm.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalConvertirReferido').addEventListener('hidden.bs.modal', function() {
        convForm.reset();
        convForm.classList.remove('was-validated');
        document.getElementById('conv_alerta_fechas').style.display = 'none';
        document.getElementById('conv_fecha_validacion').innerHTML = '';
        document.getElementById('conv_obs_contador').textContent = '0';
        document.getElementById('conv_info_lead').innerHTML = '<p class="text-muted text-center">Cargando información del lead...</p>';
        convFechaConversion.value = new Date().toISOString().split('T')[0];
        convFechaConversion.classList.remove('is-valid', 'is-invalid');
    });

    // Cargar información del lead cuando se abre el modal
    document.getElementById('modalConvertirReferido').addEventListener('show.bs.modal', function() {
        const referidoId = document.getElementById('conv_referido_id').value;
        
        if (referidoId) {
            // Aquí se cargaría la información del lead mediante AJAX
            // Por ahora, simulamos con un placeholder
            document.getElementById('conv_info_lead').innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Lead:</small>
                        <div class="fw-bold" id="conv_info_lead_nombre">Cargando...</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Código:</small>
                        <div id="conv_info_codigo_ref">Cargando...</div>
                    </div>
                    <div class="col-6 mt-2">
                        <small class="text-muted">Fecha uso código:</small>
                        <div id="conv_info_fecha_uso">-</div>
                    </div>
                    <div class="col-6 mt-2">
                        <small class="text-muted">Beneficio:</small>
                        <div class="text-success" id="conv_info_beneficio">-</div>
                    </div>
                </div>
            `;
        }
    });
});

// Actualizar listener de botón convertir
$(document).on('click', '.btn-convertir-referido', function() {
    const id = $(this).data('id');
    const lead = $(this).data('lead');
    
    document.getElementById('conv_referido_id').value = id;
    
    // Simular carga de datos
    document.getElementById('conv_info_lead').innerHTML = `
        <div class="row">
            <div class="col-6">
                <small class="text-muted">Lead:</small>
                <div class="fw-bold" id="conv_info_lead_nombre">${lead}</div>
            </div>
            <div class="col-6">
                <small class="text-muted">ID Registro:</small>
                <div>${id}</div>
            </div>
        </div>
    `;
    
    $('#modalConvertirReferido').modal('show');
});
</script>

