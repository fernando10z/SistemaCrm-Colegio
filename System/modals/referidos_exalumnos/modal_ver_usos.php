<!-- Modal Generar Código de Referido -->
<div class="modal fade" id="modalGenerarCodigo" tabindex="-1" aria-labelledby="modalGenerarCodigoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalGenerarCodigoLabel">
          <i class="ti ti-ticket me-2"></i>
          Generar Código de Referido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGenerarCodigo" method="POST" action="acciones/referidos_exalumnos/gestionar_referido.php" novalidate>
        <input type="hidden" name="accion" value="generar_codigo">
        
        <div class="modal-body">
          
          <!-- Información del Código -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-info-circle me-1"></i>
                Información del Código
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Código -->
              <div class="mb-3">
                <label for="codigo" class="form-label">
                  Código de Referido <span class="text-danger">*</span>
                  <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                     title="Código único alfanumérico en mayúsculas (4-20 caracteres)"></i>
                </label>
                <div class="input-group">
                  <input type="text" class="form-control text-uppercase" id="codigo" name="codigo" 
                         pattern="^[A-Z0-9]{4,20}$"
                         minlength="4" maxlength="20" 
                         required
                         style="font-family: 'Courier New', monospace; font-size: 1.1rem; letter-spacing: 1px;"
                         title="Solo letras mayúsculas y números, sin espacios (4-20 caracteres)"
                         placeholder="AMIGOS2025">
                  <button class="btn btn-outline-secondary" type="button" id="btnGenerarAleatorio" 
                          data-bs-toggle="tooltip" title="Generar código aleatorio">
                    <i class="ti ti-refresh"></i>
                  </button>
                </div>
                <div class="invalid-feedback" id="codigo-feedback">
                  El código debe tener entre 4 y 20 caracteres (solo letras mayúsculas y números)
                </div>
                <small class="form-text text-success" id="codigoStatus"></small>
              </div>

              <!-- Descripción -->
              <div class="mb-3">
                <label for="descripcion" class="form-label">
                  Descripción del Código
                </label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                       maxlength="200"
                       pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,:]{0,200}$"
                       title="Letras, números y signos básicos de puntuación"
                       placeholder="Ej: Código para campaña de verano 2025">
                <div class="form-text">
                  <span id="descripcionCount">0</span>/200 caracteres
                </div>
              </div>

              <!-- Tipo de Referente -->
              <div class="mb-3">
                <label class="form-label">
                  Asignar Código a: <span class="text-danger">*</span>
                </label>
                <div class="btn-group w-100" role="group" id="tipoReferenteGroup">
                  <input type="radio" class="btn-check" name="tipo_referente" id="tipo_apoderado" value="apoderado" checked>
                  <label class="btn btn-outline-primary" for="tipo_apoderado">
                    <i class="ti ti-user me-1"></i>
                    Apoderado/Exalumno
                  </label>

                  <input type="radio" class="btn-check" name="tipo_referente" id="tipo_familia" value="familia">
                  <label class="btn btn-outline-primary" for="tipo_familia">
                    <i class="ti ti-users me-1"></i>
                    Familia
                  </label>

                  <input type="radio" class="btn-check" name="tipo_referente" id="tipo_general" value="general">
                  <label class="btn btn-outline-primary" for="tipo_general">
                    <i class="ti ti-world me-1"></i>
                    General/Campaña
                  </label>
                </div>
              </div>

              <!-- Select Apoderado (visible cuando tipo_apoderado) -->
              <div class="mb-3" id="selectApoderadoContainer">
                <label for="apoderado_id" class="form-label">
                  Seleccionar Apoderado/Exalumno
                </label>
                <select class="form-select" id="apoderado_id" name="apoderado_id">
                  <option value="">-- Seleccionar --</option>
                  <?php
                  // Obtener apoderados activos
                  $apoderados_sql = "SELECT a.id, a.nombres, a.apellidos, a.email, a.tipo_apoderado 
                                     FROM apoderados a 
                                     WHERE a.activo = 1 
                                     ORDER BY a.apellidos, a.nombres";
                  $apoderados_result = $conn->query($apoderados_sql);
                  if ($apoderados_result && $apoderados_result->num_rows > 0) {
                      while($apoderado = $apoderados_result->fetch_assoc()) {
                          echo '<option value="' . $apoderado['id'] . '">';
                          echo htmlspecialchars($apoderado['apellidos'] . ', ' . $apoderado['nombres']);
                          echo ' (' . ucfirst($apoderado['tipo_apoderado']) . ')';
                          echo '</option>';
                      }
                  }
                  ?>
                </select>
                <small class="form-text text-muted">Apoderado que recibirá el código de referido</small>
              </div>

              <!-- Select Familia (visible cuando tipo_familia) -->
              <div class="mb-3 d-none" id="selectFamiliaContainer">
                <label for="familia_id" class="form-label">
                  Seleccionar Familia
                </label>
                <select class="form-select" id="familia_id" name="familia_id">
                  <option value="">-- Seleccionar --</option>
                  <?php
                  // Obtener familias activas
                  $familias_sql = "SELECT f.id, f.codigo_familia, f.apellido_principal, f.distrito 
                                   FROM familias f 
                                   WHERE f.activo = 1 
                                   ORDER BY f.apellido_principal";
                  $familias_result = $conn->query($familias_sql);
                  if ($familias_result && $familias_result->num_rows > 0) {
                      while($familia = $familias_result->fetch_assoc()) {
                          echo '<option value="' . $familia['id'] . '">';
                          echo 'Familia ' . htmlspecialchars($familia['apellido_principal']);
                          echo ' - ' . htmlspecialchars($familia['codigo_familia']);
                          echo '</option>';
                      }
                  }
                  ?>
                </select>
                <small class="form-text text-muted">Familia que recibirá el código de referido</small>
              </div>

            </div>
          </div>

          <!-- Configuración de Uso -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-settings me-1"></i>
                Configuración de Uso
              </h6>
            </div>
            <div class="card-body">
              
              <div class="row">
                <!-- Fecha de Inicio -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="fecha_inicio" class="form-label">
                      Fecha de Inicio <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           required
                           min="<?php echo date('Y-m-d'); ?>">
                    <div class="invalid-feedback">
                      Debe seleccionar una fecha de inicio
                    </div>
                  </div>
                </div>

                <!-- Fecha de Fin -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="fecha_fin" class="form-label">
                      Fecha de Fin
                      <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                         title="Dejar vacío para código sin vencimiento"></i>
                    </label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    <div class="invalid-feedback">
                      La fecha de fin debe ser posterior a la fecha de inicio
                    </div>
                    <small class="form-text text-muted">Opcional - Sin fecha = código permanente</small>
                  </div>
                </div>
              </div>

              <!-- Límite de Usos -->
              <div class="mb-3">
                <label for="limite_usos" class="form-label">
                  Límite de Usos
                  <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                     title="Número máximo de veces que puede usarse el código"></i>
                </label>
                <div class="input-group">
                  <input type="number" class="form-control" id="limite_usos" name="limite_usos" 
                         min="1" max="1000" step="1"
                         pattern="^[1-9][0-9]{0,3}$"
                         title="Número entre 1 y 1000"
                         placeholder="Ej: 10">
                  <span class="input-group-text">usos</span>
                </div>
                <div class="invalid-feedback">
                  Debe ser un número entre 1 y 1000
                </div>
                <small class="form-text text-muted">Opcional - Dejar vacío para usos ilimitados</small>
              </div>

              <!-- Estado Activo -->
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                  <label class="form-check-label" for="activo">
                    <strong>Código Activo</strong>
                    <small class="d-block text-muted">Código disponible para uso inmediato</small>
                  </label>
                </div>
              </div>

            </div>
          </div>

          <!-- Beneficios -->
          <div class="card">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-gift me-1"></i>
                Beneficios y Descuentos
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Beneficio para Referente -->
              <div class="mb-3">
                <label for="beneficio_referente" class="form-label">
                  Beneficio para el Referente (quien comparte el código)
                  <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                     title="Qué beneficio recibe quien comparte el código"></i>
                </label>
                <textarea class="form-control" id="beneficio_referente" name="beneficio_referente" 
                          rows="2" maxlength="500"
                          pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,%$]{0,500}$"
                          title="Letras, números y signos de puntuación básicos"
                          placeholder="Ej: 10% de descuento en la siguiente pensión"></textarea>
                <div class="form-text">
                  <span id="beneficioReferenteCount">0</span>/500 caracteres
                </div>
              </div>

              <!-- Beneficio para Referido -->
              <div class="mb-3">
                <label for="beneficio_referido" class="form-label">
                  Beneficio para el Referido (quien usa el código)
                  <i class="ti ti-help-circle text-muted" data-bs-toggle="tooltip" 
                     title="Qué beneficio recibe quien usa el código"></i>
                </label>
                <textarea class="form-control" id="beneficio_referido" name="beneficio_referido" 
                          rows="2" maxlength="500"
                          pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.,%$]{0,500}$"
                          title="Letras, números y signos de puntuación básicos"
                          placeholder="Ej: 15% de descuento en matrícula 2026"></textarea>
                <div class="form-text">
                  <span id="beneficioReferidoCount">0</span>/500 caracteres
                </div>
              </div>

              <!-- Alerta informativa -->
              <div class="alert alert-info mb-0" role="alert">
                <i class="ti ti-info-circle me-1"></i>
                <small>
                  <strong>Nota:</strong> Los beneficios son informativos. Debe aplicar manualmente los descuentos 
                  correspondientes en el sistema de pagos cuando se use el código.
                </small>
              </div>

            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" id="btnPrevisualizar">
            <i class="ti ti-eye me-1"></i>
            Previsualizar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            Generar Código
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formGenerarCodigo = document.getElementById('formGenerarCodigo');
    const codigoInput = document.getElementById('codigo');
    const codigoStatus = document.getElementById('codigoStatus');
    const btnGenerarAleatorio = document.getElementById('btnGenerarAleatorio');
    const descripcionInput = document.getElementById('descripcion');
    const descripcionCount = document.getElementById('descripcionCount');
    const tipoApoderado = document.getElementById('tipo_apoderado');
    const tipoFamilia = document.getElementById('tipo_familia');
    const tipoGeneral = document.getElementById('tipo_general');
    const selectApoderadoContainer = document.getElementById('selectApoderadoContainer');
    const selectFamiliaContainer = document.getElementById('selectFamiliaContainer');
    const apoderadoSelect = document.getElementById('apoderado_id');
    const familiaSelect = document.getElementById('familia_id');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const limiteUsos = document.getElementById('limite_usos');
    const beneficioReferente = document.getElementById('beneficio_referente');
    const beneficioReferido = document.getElementById('beneficio_referido');
    const beneficioReferenteCount = document.getElementById('beneficioReferenteCount');
    const beneficioReferidoCount = document.getElementById('beneficioReferidoCount');

    // Inicializar fecha de inicio con fecha actual
    fechaInicio.value = new Date().toISOString().split('T')[0];

    // Forzar mayúsculas y validar código en tiempo real
    codigoInput.addEventListener('input', function() {
        // Convertir a mayúsculas y eliminar caracteres no permitidos
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Validar longitud y formato
        if (this.value.length >= 4 && this.value.length <= 20) {
            if (/^[A-Z0-9]{4,20}$/.test(this.value)) {
                codigoStatus.textContent = '✓ Código válido';
                codigoStatus.className = 'form-text text-success';
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                codigoStatus.textContent = '✗ Solo letras mayúsculas y números';
                codigoStatus.className = 'form-text text-danger';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        } else if (this.value.length > 0) {
            codigoStatus.textContent = '⚠ Mínimo 4 caracteres, máximo 20';
            codigoStatus.className = 'form-text text-warning';
            this.classList.remove('is-valid', 'is-invalid');
        } else {
            codigoStatus.textContent = '';
            this.classList.remove('is-valid', 'is-invalid');
        }
    });

    // Generar código aleatorio
    btnGenerarAleatorio.addEventListener('click', function() {
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let codigoAleatorio = '';
        const longitud = Math.floor(Math.random() * 5) + 8; // Entre 8 y 12 caracteres
        
        for (let i = 0; i < longitud; i++) {
            codigoAleatorio += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
        }
        
        codigoInput.value = codigoAleatorio;
        codigoInput.dispatchEvent(new Event('input'));
        
        Swal.fire({
            icon: 'success',
            title: 'Código Generado',
            text: 'Código aleatorio: ' + codigoAleatorio,
            timer: 2000,
            showConfirmButton: false
        });
    });

    // Contador de caracteres para descripción
    descripcionInput.addEventListener('input', function() {
        descripcionCount.textContent = this.value.length;
        
        if (this.value.length > 180) {
            descripcionCount.classList.add('text-warning');
        } else {
            descripcionCount.classList.remove('text-warning');
        }
    });

    // Contador de caracteres para beneficios
    beneficioReferente.addEventListener('input', function() {
        beneficioReferenteCount.textContent = this.value.length;
        if (this.value.length > 450) {
            beneficioReferenteCount.classList.add('text-warning');
        } else {
            beneficioReferenteCount.classList.remove('text-warning');
        }
    });

    beneficioReferido.addEventListener('input', function() {
        beneficioReferidoCount.textContent = this.value.length;
        if (this.value.length > 450) {
            beneficioReferidoCount.classList.add('text-warning');
        } else {
            beneficioReferidoCount.classList.remove('text-warning');
        }
    });

    // Manejo de tipo de referente
    function actualizarSelectsReferente() {
        if (tipoApoderado.checked) {
            selectApoderadoContainer.classList.remove('d-none');
            selectFamiliaContainer.classList.add('d-none');
            apoderadoSelect.required = true;
            familiaSelect.required = false;
            familiaSelect.value = '';
        } else if (tipoFamilia.checked) {
            selectApoderadoContainer.classList.add('d-none');
            selectFamiliaContainer.classList.remove('d-none');
            apoderadoSelect.required = false;
            familiaSelect.required = true;
            apoderadoSelect.value = '';
        } else { // General
            selectApoderadoContainer.classList.add('d-none');
            selectFamiliaContainer.classList.add('d-none');
            apoderadoSelect.required = false;
            familiaSelect.required = false;
            apoderadoSelect.value = '';
            familiaSelect.value = '';
        }
    }

    tipoApoderado.addEventListener('change', actualizarSelectsReferente);
    tipoFamilia.addEventListener('change', actualizarSelectsReferente);
    tipoGeneral.addEventListener('change', actualizarSelectsReferente);

    // Validación de fechas
    fechaInicio.addEventListener('change', function() {
        // Actualizar min de fecha fin
        if (this.value) {
            const fechaInicioObj = new Date(this.value);
            fechaInicioObj.setDate(fechaInicioObj.getDate() + 1);
            fechaFin.min = fechaInicioObj.toISOString().split('T')[0];
            
            // Validar si fecha fin ya está establecida
            if (fechaFin.value && fechaFin.value <= this.value) {
                fechaFin.setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio');
                fechaFin.classList.add('is-invalid');
            } else {
                fechaFin.setCustomValidity('');
                fechaFin.classList.remove('is-invalid');
            }
        }
    });

    fechaFin.addEventListener('change', function() {
        if (this.value && fechaInicio.value) {
            if (this.value <= fechaInicio.value) {
                this.setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                
                // Calcular días de vigencia
                const inicio = new Date(fechaInicio.value);
                const fin = new Date(this.value);
                const dias = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24));
                
                Swal.fire({
                    icon: 'info',
                    title: 'Vigencia del Código',
                    text: `El código estará vigente por ${dias} días`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });

    // Validación de límite de usos
    limiteUsos.addEventListener('input', function() {
        // Solo permitir números
        this.value = this.value.replace(/[^0-9]/g, '');
        
        if (this.value) {
            const valor = parseInt(this.value);
            if (valor < 1) {
                this.value = 1;
            } else if (valor > 1000) {
                this.value = 1000;
            }
        }
    });

    // Previsualizar código
    document.getElementById('btnPrevisualizar').addEventListener('click', function() {
        if (!formGenerarCodigo.checkValidity()) {
            formGenerarCodigo.classList.add('was-validated');
            Swal.fire({
                icon: 'warning',
                title: 'Formulario Incompleto',
                text: 'Por favor complete todos los campos obligatorios antes de previsualizar',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        let referente = 'General/Campaña';
        if (tipoApoderado.checked && apoderadoSelect.value) {
            referente = apoderadoSelect.options[apoderadoSelect.selectedIndex].text;
        } else if (tipoFamilia.checked && familiaSelect.value) {
            referente = familiaSelect.options[familiaSelect.selectedIndex].text;
        }

        const vigencia = fechaFin.value ? 
            `${fechaInicio.value} al ${fechaFin.value}` : 
            `Desde ${fechaInicio.value} (sin vencimiento)`;

        const usos = limiteUsos.value ? 
            `Máximo ${limiteUsos.value} usos` : 
            'Usos ilimitados';

        let beneficiosHtml = '<div style="text-align: left; margin-top: 10px;">';
        if (beneficioReferente.value) {
            beneficiosHtml += `<p><strong>Para el Referente:</strong><br>${beneficioReferente.value}</p>`;
        }
        if (beneficioReferido.value) {
            beneficiosHtml += `<p><strong>Para el Referido:</strong><br>${beneficioReferido.value}</p>`;
        }
        if (!beneficioReferente.value && !beneficioReferido.value) {
            beneficiosHtml += '<p class="text-muted">Sin beneficios configurados</p>';
        }
        beneficiosHtml += '</div>';

        Swal.fire({
            title: 'Previsualización del Código',
            html: `
                <div style="font-family: 'Courier New', monospace; font-size: 1.5rem; letter-spacing: 2px; 
                            background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <strong>${codigoInput.value}</strong>
                </div>
                <div style="text-align: left;">
                    <p><strong>Descripción:</strong> ${descripcionInput.value || 'Sin descripción'}</p>
                    <p><strong>Asignado a:</strong> ${referente}</p>
                    <p><strong>Vigencia:</strong> ${vigencia}</p>
                    <p><strong>Límite de uso:</strong> ${usos}</p>
                    <p><strong>Estado:</strong> ${document.getElementById('activo').checked ? 'Activo' : 'Inactivo'}</p>
                </div>
                ${beneficiosHtml}
            `,
            icon: 'info',
            confirmButtonText: 'Aceptar',
            width: '600px'
        });
    });

    // Validación del formulario completo
    formGenerarCodigo.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formGenerarCodigo.checkValidity()) {
            e.stopPropagation();
            formGenerarCodigo.classList.add('was-validated');
            
            Swal.fire({
                icon: 'error',
                title: 'Errores en el Formulario',
                text: 'Por favor corrija los errores marcados antes de continuar',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }

        // Validación adicional: tipo de referente
        if (tipoApoderado.checked && !apoderadoSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un Apoderado',
                text: 'Debe seleccionar un apoderado para asignar el código',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        if (tipoFamilia.checked && !familiaSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione una Familia',
                text: 'Debe seleccionar una familia para asignar el código',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Confirmación final
        Swal.fire({
            title: '¿Generar código de referido?',
            html: `
                Se creará el código <strong style="font-family: 'Courier New'; font-size: 1.2rem;">${codigoInput.value}</strong>
                <br><br>
                <small>Este código estará disponible para ser usado según la configuración establecida.</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                formGenerarCodigo.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalGenerarCodigo').addEventListener('hidden.bs.modal', function() {
        formGenerarCodigo.reset();
        formGenerarCodigo.classList.remove('was-validated');
        codigoStatus.textContent = '';
        codigoInput.classList.remove('is-valid', 'is-invalid');
        fechaFin.classList.remove('is-invalid');
        descripcionCount.textContent = '0';
        beneficioReferenteCount.textContent = '0';
        beneficioReferidoCount.textContent = '0';
        fechaInicio.value = new Date().toISOString().split('T')[0];
        actualizarSelectsReferente();
    });

    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>