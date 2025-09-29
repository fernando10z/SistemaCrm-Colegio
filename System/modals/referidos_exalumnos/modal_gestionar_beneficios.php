<!-- Modal Gestionar Beneficios -->
<div class="modal fade" id="modalGestionarBeneficios" tabindex="-1" aria-labelledby="modalGestionarBeneficiosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalGestionarBeneficiosLabel">
          <i class="ti ti-gift me-2"></i>
          Gestionar Beneficios del Código
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGestionarBeneficios" method="POST" action="acciones/referidos_exalumnos/gestionar_referido.php" novalidate>
        <input type="hidden" name="accion" value="gestionar_beneficios">
        <input type="hidden" name="codigo_referido_id" id="codigo_referido_id_beneficios">
        
        <div class="modal-body">
          
          <!-- Información del Código -->
          <div class="alert alert-info" role="alert">
            <div class="row align-items-center">
              <div class="col-md-8">
                <i class="ti ti-info-circle me-1"></i>
                <strong>Código:</strong> 
                <span id="codigoDisplayBeneficios" style="font-family: 'Courier New'; font-size: 1.1rem; letter-spacing: 1px;">
                  CÓDIGO
                </span>
              </div>
              <div class="col-md-4 text-end">
                <span class="badge bg-primary" id="usosActualesBeneficios">0 usos</span>
              </div>
            </div>
          </div>

          <!-- Tipo de Beneficio -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-settings me-1"></i>
                Configuración de Beneficios
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Tipo de Beneficio -->
              <div class="mb-3">
                <label class="form-label">
                  Tipo de Beneficio <span class="text-danger">*</span>
                </label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tipo_beneficio" 
                             id="tipo_porcentaje" value="porcentaje" checked>
                      <label class="form-check-label" for="tipo_porcentaje">
                        <i class="ti ti-percentage me-1"></i>
                        Porcentaje de Descuento
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tipo_beneficio" 
                             id="tipo_monto" value="monto">
                      <label class="form-check-label" for="tipo_monto">
                        <i class="ti ti-currency-dollar me-1"></i>
                        Monto Fijo
                      </label>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Beneficio para Referente -->
          <div class="card mb-3">
            <div class="card-header" style="background-color: #e3f2fd;">
              <h6 class="mb-0 text-primary">
                <i class="ti ti-user-star me-1"></i>
                Beneficio para el Referente
                <small class="text-muted">(quien comparte el código)</small>
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Porcentaje/Monto para Referente -->
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3" id="porcentajeReferenteContainer">
                    <label for="porcentaje_referente" class="form-label">
                      Porcentaje de Descuento
                      <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                         title="Porcentaje de descuento para el referente"></i>
                    </label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="porcentaje_referente" 
                             name="porcentaje_referente" 
                             min="0" max="100" step="1"
                             pattern="^[0-9]{1,3}$"
                             placeholder="10">
                      <span class="input-group-text">%</span>
                    </div>
                    <div class="invalid-feedback">
                      Debe ser un número entre 0 y 100
                    </div>
                  </div>
                  
                  <div class="mb-3 d-none" id="montoReferenteContainer">
                    <label for="monto_referente" class="form-label">
                      Monto de Descuento
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">S/</span>
                      <input type="number" class="form-control" id="monto_referente" 
                             name="monto_referente" 
                             min="0" max="10000" step="0.01"
                             pattern="^[0-9]+(\.[0-9]{1,2})?$"
                             placeholder="50.00">
                    </div>
                    <div class="invalid-feedback">
                      Debe ser un monto válido
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="aplicable_en_referente" class="form-label">
                      Aplicable en:
                    </label>
                    <select class="form-select" id="aplicable_en_referente" name="aplicable_en_referente">
                      <option value="">Seleccionar</option>
                      <option value="matricula">Matrícula</option>
                      <option value="pension">Pensión mensual</option>
                      <option value="cuota_ingreso">Cuota de ingreso</option>
                      <option value="material">Material educativo</option>
                      <option value="uniforme">Uniforme</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Descripción del Beneficio Referente -->
              <div class="mb-3">
                <label for="descripcion_beneficio_referente" class="form-label">
                  Descripción del Beneficio <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="descripcion_beneficio_referente" 
                          name="descripcion_beneficio_referente" 
                          rows="2" maxlength="500" required
                          pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,%$]{1,500}$"
                          placeholder="Ej: 10% de descuento en la pensión del siguiente mes"></textarea>
                <div class="form-text">
                  <span id="descripcionReferenteCount">0</span>/500 caracteres
                </div>
                <div class="invalid-feedback">
                  La descripción es obligatoria
                </div>
              </div>

              <!-- Checkbox Activo Referente -->
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="activo_beneficio_referente" 
                       name="activo_beneficio_referente" value="1" checked>
                <label class="form-check-label" for="activo_beneficio_referente">
                  Beneficio activo para el referente
                </label>
              </div>

            </div>
          </div>

          <!-- Beneficio para Referido -->
          <div class="card">
            <div class="card-header" style="background-color: #e8f5e9;">
              <h6 class="mb-0 text-success">
                <i class="ti ti-user-plus me-1"></i>
                Beneficio para el Referido
                <small class="text-muted">(quien usa el código)</small>
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Porcentaje/Monto para Referido -->
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3" id="porcentajeReferidoContainer">
                    <label for="porcentaje_referido" class="form-label">
                      Porcentaje de Descuento
                      <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                         title="Porcentaje de descuento para el referido"></i>
                    </label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="porcentaje_referido" 
                             name="porcentaje_referido" 
                             min="0" max="100" step="1"
                             pattern="^[0-9]{1,3}$"
                             placeholder="15">
                      <span class="input-group-text">%</span>
                    </div>
                    <div class="invalid-feedback">
                      Debe ser un número entre 0 y 100
                    </div>
                  </div>
                  
                  <div class="mb-3 d-none" id="montoReferidoContainer">
                    <label for="monto_referido" class="form-label">
                      Monto de Descuento
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">S/</span>
                      <input type="number" class="form-control" id="monto_referido" 
                             name="monto_referido" 
                             min="0" max="10000" step="0.01"
                             pattern="^[0-9]+(\.[0-9]{1,2})?$"
                             placeholder="100.00">
                    </div>
                    <div class="invalid-feedback">
                      Debe ser un monto válido
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="aplicable_en_referido" class="form-label">
                      Aplicable en:
                    </label>
                    <select class="form-select" id="aplicable_en_referido" name="aplicable_en_referido">
                      <option value="">Seleccionar</option>
                      <option value="matricula">Matrícula</option>
                      <option value="pension">Pensión mensual</option>
                      <option value="cuota_ingreso">Cuota de ingreso</option>
                      <option value="material">Material educativo</option>
                      <option value="uniforme">Uniforme</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Descripción del Beneficio Referido -->
              <div class="mb-3">
                <label for="descripcion_beneficio_referido" class="form-label">
                  Descripción del Beneficio <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="descripcion_beneficio_referido" 
                          name="descripcion_beneficio_referido" 
                          rows="2" maxlength="500" required
                          pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,%$]{1,500}$"
                          placeholder="Ej: 15% de descuento en la matrícula 2026"></textarea>
                <div class="form-text">
                  <span id="descripcionReferidoCount">0</span>/500 caracteres
                </div>
                <div class="invalid-feedback">
                  La descripción es obligatoria
                </div>
              </div>

              <!-- Checkbox Activo Referido -->
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="activo_beneficio_referido" 
                       name="activo_beneficio_referido" value="1" checked>
                <label class="form-check-label" for="activo_beneficio_referido">
                  Beneficio activo para el referido
                </label>
              </div>

            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" id="btnCalcularBeneficio">
            <i class="ti ti-calculator me-1"></i>
            Calcular Ejemplo
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-device-floppy me-1"></i>
            Guardar Beneficios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formGestionarBeneficios = document.getElementById('formGestionarBeneficios');
    const tipoPorcentaje = document.getElementById('tipo_porcentaje');
    const tipoMonto = document.getElementById('tipo_monto');
    const descripcionReferenteTextarea = document.getElementById('descripcion_beneficio_referente');
    const descripcionReferidoTextarea = document.getElementById('descripcion_beneficio_referido');
    const descripcionReferenteCount = document.getElementById('descripcionReferenteCount');
    const descripcionReferidoCount = document.getElementById('descripcionReferidoCount');

    // Cargar datos cuando se abre el modal
    $(document).on('click', '.btn-gestionar-beneficios', function() {
        const id = $(this).data('id');
        const codigo = $(this).data('codigo');
        
        $('#codigo_referido_id_beneficios').val(id);
        $('#codigoDisplayBeneficios').text(codigo);
        
        // Cargar datos del código
        cargarDatosBeneficios(id);
    });

    // Función para cargar datos de beneficios
    function cargarDatosBeneficios(codigoId) {
        // Aquí cargarías los datos existentes vía AJAX
        // Por ahora solo simula
        $('#usosActualesBeneficios').text('0 usos');
    }

    // Alternar entre porcentaje y monto
    function alternarTipoBeneficio() {
        if (tipoPorcentaje.checked) {
            // Mostrar campos de porcentaje
            $('#porcentajeReferenteContainer').removeClass('d-none');
            $('#montoReferenteContainer').addClass('d-none');
            $('#porcentajeReferidoContainer').removeClass('d-none');
            $('#montoReferidoContainer').addClass('d-none');
            
            // Limpiar campos de monto
            $('#monto_referente').val('');
            $('#monto_referido').val('');
        } else {
            // Mostrar campos de monto
            $('#porcentajeReferenteContainer').addClass('d-none');
            $('#montoReferenteContainer').removeClass('d-none');
            $('#porcentajeReferidoContainer').addClass('d-none');
            $('#montoReferidoContainer').removeClass('d-none');
            
            // Limpiar campos de porcentaje
            $('#porcentaje_referente').val('');
            $('#porcentaje_referido').val('');
        }
    }

    tipoPorcentaje.addEventListener('change', alternarTipoBeneficio);
    tipoMonto.addEventListener('change', alternarTipoBeneficio);

    // Validar porcentajes en tiempo real
    $('#porcentaje_referente, #porcentaje_referido').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        
        if (this.value) {
            const valor = parseInt(this.value);
            if (valor < 0) {
                this.value = 0;
            } else if (valor > 100) {
                this.value = 100;
            }
        }
    });

    // Validar montos en tiempo real
    $('#monto_referente, #monto_referido').on('input', function() {
        // Permitir solo números y punto decimal
        this.value = this.value.replace(/[^0-9.]/g, '');
        
        // Limitar a dos decimales
        const partes = this.value.split('.');
        if (partes.length > 2) {
            this.value = partes[0] + '.' + partes[1];
        }
        if (partes[1] && partes[1].length > 2) {
            this.value = partes[0] + '.' + partes[1].substring(0, 2);
        }
        
        // Validar límites
        if (this.value) {
            const valor = parseFloat(this.value);
            if (valor < 0) {
                this.value = '0';
            } else if (valor > 10000) {
                this.value = '10000';
            }
        }
    });

    // Contador de caracteres para descripciones
    descripcionReferenteTextarea.addEventListener('input', function() {
        descripcionReferenteCount.textContent = this.value.length;
        if (this.value.length > 450) {
            descripcionReferenteCount.classList.add('text-warning');
        } else {
            descripcionReferenteCount.classList.remove('text-warning');
        }
    });

    descripcionReferidoTextarea.addEventListener('input', function() {
        descripcionReferidoCount.textContent = this.value.length;
        if (this.value.length > 450) {
            descripcionReferidoCount.classList.add('text-warning');
        } else {
            descripcionReferidoCount.classList.remove('text-warning');
        }
    });

    // Calcular ejemplo de beneficio
    $('#btnCalcularBeneficio').on('click', function() {
        const montoPension = 350; // Monto ejemplo
        const montoMatricula = 500; // Monto ejemplo

        let ejemploHtml = '<div style="text-align: left;">';
        
        if (tipoPorcentaje.checked) {
            const porcReferente = $('#porcentaje_referente').val() || 0;
            const porcReferido = $('#porcentaje_referido').val() || 0;
            const aplicaReferente = $('#aplicable_en_referente option:selected').text() || 'Pensión';
            const aplicaReferido = $('#aplicable_en_referido option:selected').text() || 'Matrícula';
            
            const montoBase = aplicaReferente.includes('Matrícula') ? montoMatricula : montoPension;
            const descuentoReferente = (montoBase * porcReferente / 100).toFixed(2);
            const totalReferente = (montoBase - descuentoReferente).toFixed(2);
            
            const montoBaseRef = aplicaReferido.includes('Matrícula') ? montoMatricula : montoPension;
            const descuentoReferido = (montoBaseRef * porcReferido / 100).toFixed(2);
            const totalReferido = (montoBaseRef - descuentoReferido).toFixed(2);
            
            ejemploHtml += `
                <h6 class="text-primary">Para el Referente:</h6>
                <p class="mb-2">
                    ${aplicaReferente}: S/ ${montoBase.toFixed(2)}<br>
                    Descuento (${porcReferente}%): <strong class="text-success">- S/ ${descuentoReferente}</strong><br>
                    Total a pagar: <strong>S/ ${totalReferente}</strong>
                </p>
                
                <h6 class="text-success">Para el Referido:</h6>
                <p class="mb-0">
                    ${aplicaReferido}: S/ ${montoBaseRef.toFixed(2)}<br>
                    Descuento (${porcReferido}%): <strong class="text-success">- S/ ${descuentoReferido}</strong><br>
                    Total a pagar: <strong>S/ ${totalReferido}</strong>
                </p>
            `;
        } else {
            const montoReferen = $('#monto_referente').val() || 0;
            const montoReferid = $('#monto_referido').val() || 0;
            
            ejemploHtml += `
                <h6 class="text-primary">Para el Referente:</h6>
                <p class="mb-2">Descuento fijo: <strong class="text-success">S/ ${parseFloat(montoReferen).toFixed(2)}</strong></p>
                
                <h6 class="text-success">Para el Referido:</h6>
                <p class="mb-0">Descuento fijo: <strong class="text-success">S/ ${parseFloat(montoReferid).toFixed(2)}</strong></p>
            `;
        }
        
        ejemploHtml += '</div>';

        Swal.fire({
            title: 'Ejemplo de Beneficios',
            html: ejemploHtml,
            icon: 'info',
            confirmButtonText: 'Aceptar',
            width: '500px'
        });
    });

    // Validación del formulario
    formGestionarBeneficios.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formGestionarBeneficios.checkValidity()) {
            e.stopPropagation();
            formGestionarBeneficios.classList.add('was-validated');
            
            Swal.fire({
                icon: 'error',
                title: 'Errores en el Formulario',
                text: 'Por favor complete todos los campos obligatorios',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }

        // Validar que al menos un beneficio esté configurado
        const tieneReferente = descripcionReferenteTextarea.value.trim().length > 0;
        const tieneReferido = descripcionReferidoTextarea.value.trim().length > 0;

        if (!tieneReferente && !tieneReferido) {
            Swal.fire({
                icon: 'warning',
                title: 'Beneficios Incompletos',
                text: 'Debe configurar al menos un beneficio (referente o referido)',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Confirmación final
        Swal.fire({
            title: '¿Guardar beneficios?',
            text: 'Se actualizarán los beneficios del código de referido',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                formGestionarBeneficios.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    $('#modalGestionarBeneficios').on('hidden.bs.modal', function() {
        formGestionarBeneficios.reset();
        formGestionarBeneficios.classList.remove('was-validated');
        descripcionReferenteCount.textContent = '0';
        descripcionReferidoCount.textContent = '0';
        alternarTipoBeneficio();
    });

    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>