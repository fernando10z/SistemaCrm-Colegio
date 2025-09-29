<!-- Modal Crear Código de Referido -->
<div class="modal fade" id="modalCrearCodigo" tabindex="-1" aria-labelledby="modalCrearCodigoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCrearCodigoLabel">
          <i class="ti ti-plus me-2"></i>
          Crear Código de Recomendación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formCrearCodigo" method="POST" action="acciones/codigos_referido/gestionar_codigo.php" novalidate>
        <input type="hidden" name="accion" value="crear_codigo">
        
        <div class="modal-body">
          
          <!-- Alert de Información -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Importante:</strong> El código debe ser único y fácil de recordar. 
            Puede ser personal (asociado a un apoderado/familia) o general (para campañas masivas).
          </div>

          <div class="row">
            <!-- Columna Izquierda: Información del Código -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-tag me-1"></i>
                    Información del Código
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Código -->
                  <div class="mb-3">
                    <label for="crear_codigo" class="form-label">
                      Código <span class="text-danger">*</span>
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="Código único de referido. Solo letras, números y guiones. Máximo 20 caracteres."></i>
                    </label>
                    <div class="input-group">
                      <input type="text" class="form-control text-uppercase" id="crear_codigo" name="codigo" 
                             pattern="^[A-Z0-9\-]{3,20}$"
                             minlength="3" maxlength="20" 
                             required
                             placeholder="CODIGO2025"
                             title="Solo letras mayúsculas, números y guiones (3-20 caracteres)">
                      <button class="btn btn-outline-secondary" type="button" id="btnGenerarCodigo">
                        <i class="ti ti-refresh"></i>
                      </button>
                    </div>
                    <div class="invalid-feedback" id="crear-codigo-feedback">
                      El código debe contener solo letras, números y guiones (3-20 caracteres)
                    </div>
                    <div class="valid-feedback" id="crear-codigo-disponible">
                      ✓ Código disponible
                    </div>
                    <small class="form-text text-muted">
                      El código se convertirá automáticamente a mayúsculas
                    </small>
                  </div>

                  <!-- Tipo de Código -->
                  <div class="mb-3">
                    <label class="form-label">
                      Tipo de Código <span class="text-danger">*</span>
                    </label>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tipo_codigo" 
                             id="tipo_general" value="general" checked>
                      <label class="form-check-label" for="tipo_general">
                        <strong>General</strong> - Para campañas masivas o públicas
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tipo_codigo" 
                             id="tipo_personal" value="personal">
                      <label class="form-check-label" for="tipo_personal">
                        <strong>Personal</strong> - Asociado a un apoderado o familia específica
                      </label>
                    </div>
                  </div>

                  <!-- Selección de Apoderado (Solo si es personal) -->
                  <div class="mb-3 d-none" id="div_apoderado">
                    <label for="crear_apoderado_id" class="form-label">
                      Apoderado <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="crear_apoderado_id" name="apoderado_id">
                      <option value="">Seleccionar apoderado...</option>
                      <?php
                      $sql_apoderados = "SELECT a.id, a.nombres, a.apellidos, a.email, f.apellido_principal
                                        FROM apoderados a
                                        LEFT JOIN familias f ON a.familia_id = f.id
                                        WHERE a.activo = 1
                                        ORDER BY a.apellidos ASC";
                      $result_apoderados = $conn->query($sql_apoderados);
                      while($apoderado = $result_apoderados->fetch_assoc()) {
                          echo "<option value='" . $apoderado['id'] . "' 
                                        data-familia='" . ($apoderado['apellido_principal'] ?? '') . "'>" .
                               htmlspecialchars($apoderado['apellidos'] . ' ' . $apoderado['nombres']) .
                               ($apoderado['email'] ? ' (' . htmlspecialchars($apoderado['email']) . ')' : '') .
                               "</option>";
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted" id="familia_apoderado"></small>
                  </div>

                  <!-- Selección de Familia (Solo si es personal y no tiene apoderado) -->
                  <div class="mb-3 d-none" id="div_familia">
                    <label for="crear_familia_id" class="form-label">
                      Familia (Opcional)
                    </label>
                    <select class="form-select" id="crear_familia_id" name="familia_id">
                      <option value="">Seleccionar familia...</option>
                      <?php
                      $sql_familias = "SELECT id, codigo_familia, apellido_principal 
                                      FROM familias 
                                      WHERE activo = 1 
                                      ORDER BY apellido_principal ASC";
                      $result_familias = $conn->query($sql_familias);
                      while($familia = $result_familias->fetch_assoc()) {
                          echo "<option value='" . $familia['id'] . "'>" .
                               htmlspecialchars($familia['apellido_principal']) .
                               " (" . htmlspecialchars($familia['codigo_familia']) . ")" .
                               "</option>";
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted">
                      Solo si no seleccionó apoderado específico
                    </small>
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label for="crear_descripcion" class="form-label">
                      Descripción
                    </label>
                    <textarea class="form-control" id="crear_descripcion" name="descripcion" 
                              rows="2" maxlength="200"
                              minlength="10"
                              placeholder="Descripción breve del código (ej: Campaña navideña 2025)"></textarea>
                    <div class="invalid-feedback">
                      La descripción debe tener al menos 10 caracteres
                    </div>
                    <div class="form-text">
                      <span id="crear_descripcion_count">0</span>/200 caracteres
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Beneficios y Configuración -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-gift me-1"></i>
                    Beneficios y Configuración
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Beneficio para el Referente -->
                  <div class="mb-3">
                    <label for="crear_beneficio_referente" class="form-label">
                      Beneficio para el Referente <span class="text-danger">*</span>
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="Beneficio que recibe quien recomienda"></i>
                    </label>
                    <textarea class="form-control" id="crear_beneficio_referente" name="beneficio_referente" 
                              rows="2" maxlength="500"
                              minlength="10"
                              required
                              placeholder="Ej: 10% de descuento en la siguiente pensión"></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referente (10-500 caracteres)
                    </div>
                    <div class="form-text">
                      <span id="crear_beneficio_referente_count">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Beneficio para el Referido -->
                  <div class="mb-3">
                    <label for="crear_beneficio_referido" class="form-label">
                      Beneficio para el Referido <span class="text-danger">*</span>
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="Beneficio que recibe quien es recomendado"></i>
                    </label>
                    <textarea class="form-control" id="crear_beneficio_referido" name="beneficio_referido" 
                              rows="2" maxlength="500"
                              minlength="10"
                              required
                              placeholder="Ej: 15% de descuento en matrícula"></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referido (10-500 caracteres)
                    </div>
                    <div class="form-text">
                      <span id="crear_beneficio_referido_count">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Límite de Usos -->
                  <div class="mb-3">
                    <label for="crear_limite_usos" class="form-label">
                      Límite de Usos
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="Número máximo de veces que se puede usar el código. Dejar vacío para ilimitado"></i>
                    </label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="crear_limite_usos" name="limite_usos" 
                             min="1" max="1000" step="1"
                             placeholder="Dejar vacío para ilimitado">
                      <span class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" id="crear_usos_ilimitados">
                        <label class="ms-1" for="crear_usos_ilimitados">Ilimitado</label>
                      </span>
                    </div>
                    <div class="invalid-feedback">
                      El límite debe ser entre 1 y 1000 usos
                    </div>
                    <small class="form-text text-muted">
                      Límite de personas que pueden usar este código
                    </small>
                  </div>

                  <!-- Fecha de Inicio -->
                  <div class="mb-3">
                    <label for="crear_fecha_inicio" class="form-label">
                      Fecha de Inicio <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="crear_fecha_inicio" name="fecha_inicio" 
                           required>
                    <div class="invalid-feedback">
                      La fecha de inicio no puede ser anterior a hoy
                    </div>
                  </div>

                  <!-- Fecha de Fin -->
                  <div class="mb-3">
                    <label for="crear_fecha_fin" class="form-label">
                      Fecha de Fin
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="Fecha límite para usar el código. Dejar vacío para sin límite"></i>
                    </label>
                    <div class="input-group">
                      <input type="date" class="form-control" id="crear_fecha_fin" name="fecha_fin">
                      <span class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" id="crear_sin_limite">
                        <label class="ms-1" for="crear_sin_limite">Sin límite</label>
                      </span>
                    </div>
                    <div class="invalid-feedback">
                      La fecha de fin debe ser posterior a la fecha de inicio
                    </div>
                    <small class="form-text text-muted" id="duracion_codigo"></small>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Resumen del Código -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                  <h6 class="mb-0">
                    <i class="ti ti-eye me-1"></i>
                    Vista Previa del Código
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-4">
                      <small class="text-muted">Código:</small>
                      <div class="fw-bold" id="preview_codigo">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Tipo:</small>
                      <div class="fw-bold" id="preview_tipo">General</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Propietario:</small>
                      <div class="fw-bold" id="preview_propietario">Código General</div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <small class="text-muted">Beneficio Referente:</small>
                      <div id="preview_beneficio_referente" class="text-success">-</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted">Beneficio Referido:</small>
                      <div id="preview_beneficio_referido" class="text-info">-</div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-4">
                      <small class="text-muted">Usos Permitidos:</small>
                      <div class="fw-bold" id="preview_usos">Ilimitado</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Vigencia Desde:</small>
                      <div class="fw-bold" id="preview_inicio">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Vigencia Hasta:</small>
                      <div class="fw-bold" id="preview_fin">Sin límite</div>
                    </div>
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
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            Crear Código
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formCrear = document.getElementById('formCrearCodigo');
    const codigoInput = document.getElementById('crear_codigo');
    const tipoGeneral = document.getElementById('tipo_general');
    const tipoPersonal = document.getElementById('tipo_personal');
    const divApoderado = document.getElementById('div_apoderado');
    const divFamilia = document.getElementById('div_familia');
    const apoderadoSelect = document.getElementById('crear_apoderado_id');
    const familiaSelect = document.getElementById('crear_familia_id');
    const descripcionTextarea = document.getElementById('crear_descripcion');
    const beneficioReferenteTextarea = document.getElementById('crear_beneficio_referente');
    const beneficioReferidoTextarea = document.getElementById('crear_beneficio_referido');
    const limiteUsosInput = document.getElementById('crear_limite_usos');
    const usosIlimitadosCheck = document.getElementById('crear_usos_ilimitados');
    const fechaInicioInput = document.getElementById('crear_fecha_inicio');
    const fechaFinInput = document.getElementById('crear_fecha_fin');
    const sinLimiteCheck = document.getElementById('crear_sin_limite');

    // Establecer fecha mínima (hoy)
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicioInput.min = hoy;
    fechaInicioInput.value = hoy;

    // Convertir código a mayúsculas automáticamente
    codigoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
        validarCodigoDisponible();
        actualizarVistaPrevia();
    });

    // Validar disponibilidad del código con AJAX
    let timeoutValidacion;
    function validarCodigoDisponible() {
        clearTimeout(timeoutValidacion);
        const codigo = codigoInput.value.trim();
        
        if (codigo.length >= 3) {
            timeoutValidacion = setTimeout(() => {
                fetch('acciones/codigos_referido/validar_codigo.php?codigo=' + encodeURIComponent(codigo))
                    .then(response => response.json())
                    .then(data => {
                        if (data.disponible) {
                            codigoInput.classList.remove('is-invalid');
                            codigoInput.classList.add('is-valid');
                            document.getElementById('crear-codigo-disponible').style.display = 'block';
                        } else {
                            codigoInput.classList.remove('is-valid');
                            codigoInput.classList.add('is-invalid');
                            document.getElementById('crear-codigo-feedback').textContent = 
                                'Este código ya está en uso. Elija otro.';
                            document.getElementById('crear-codigo-disponible').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error validando código:', error);
                    });
            }, 500);
        }
    }

    // Generar código aleatorio
    document.getElementById('btnGenerarCodigo').addEventListener('click', function() {
        const prefijos = ['PROMO', 'AMIGO', 'REF', 'CODIGO', 'FAMILIA'];
        const prefijo = prefijos[Math.floor(Math.random() * prefijos.length)];
        const anio = new Date().getFullYear();
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        const codigoGenerado = `${prefijo}${anio}${random}`;
        
        codigoInput.value = codigoGenerado;
        validarCodigoDisponible();
        actualizarVistaPrevia();
        
        Swal.fire({
            icon: 'success',
            title: 'Código generado',
            text: codigoGenerado,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    });

    // Manejar cambio de tipo de código
    tipoGeneral.addEventListener('change', function() {
        if (this.checked) {
            divApoderado.classList.add('d-none');
            divFamilia.classList.add('d-none');
            apoderadoSelect.value = '';
            familiaSelect.value = '';
            apoderadoSelect.removeAttribute('required');
            actualizarVistaPrevia();
        }
    });

    tipoPersonal.addEventListener('change', function() {
        if (this.checked) {
            divApoderado.classList.remove('d-none');
            divFamilia.classList.remove('d-none');
            apoderadoSelect.setAttribute('required', 'required');
            actualizarVistaPrevia();
        }
    });

    // Mostrar familia al seleccionar apoderado
    apoderadoSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const familia = selectedOption.dataset.familia;
        
        if (familia) {
            document.getElementById('familia_apoderado').textContent = 
                '✓ Familia: ' + familia;
            familiaSelect.value = '';
            familiaSelect.disabled = true;
        } else {
            document.getElementById('familia_apoderado').textContent = '';
            familiaSelect.disabled = false;
        }
        actualizarVistaPrevia();
    });

    familiaSelect.addEventListener('change', actualizarVistaPrevia);

    // Contador de caracteres
    descripcionTextarea.addEventListener('input', function() {
        document.getElementById('crear_descripcion_count').textContent = this.value.length;
        actualizarVistaPrevia();
    });

    beneficioReferenteTextarea.addEventListener('input', function() {
        document.getElementById('crear_beneficio_referente_count').textContent = this.value.length;
        actualizarVistaPrevia();
    });

    beneficioReferidoTextarea.addEventListener('input', function() {
        document.getElementById('crear_beneficio_referido_count').textContent = this.value.length;
        actualizarVistaPrevia();
    });

    // Manejar checkbox de usos ilimitados
    usosIlimitadosCheck.addEventListener('change', function() {
        if (this.checked) {
            limiteUsosInput.value = '';
            limiteUsosInput.disabled = true;
            limiteUsosInput.removeAttribute('required');
        } else {
            limiteUsosInput.disabled = false;
        }
        actualizarVistaPrevia();
    });

    limiteUsosInput.addEventListener('input', actualizarVistaPrevia);

    // Validar fecha de fin
    fechaInicioInput.addEventListener('change', function() {
        const fechaInicio = new Date(this.value);
        const fechaMinFin = new Date(fechaInicio);
        fechaMinFin.setDate(fechaMinFin.getDate() + 1);
        fechaFinInput.min = fechaMinFin.toISOString().split('T')[0];
        
        // Validar si fecha fin es anterior
        if (fechaFinInput.value && new Date(fechaFinInput.value) <= fechaInicio) {
            fechaFinInput.value = '';
        }
        
        calcularDuracion();
        actualizarVistaPrevia();
    });

    fechaFinInput.addEventListener('change', function() {
        calcularDuracion();
        actualizarVistaPrevia();
    });

    // Manejar checkbox sin límite temporal
    sinLimiteCheck.addEventListener('change', function() {
        if (this.checked) {
            fechaFinInput.value = '';
            fechaFinInput.disabled = true;
        } else {
            fechaFinInput.disabled = false;
        }
        calcularDuracion();
        actualizarVistaPrevia();
    });

    // Calcular duración
    function calcularDuracion() {
        const inicio = fechaInicioInput.value;
        const fin = fechaFinInput.value;
        const duracionDiv = document.getElementById('duracion_codigo');
        
        if (inicio && fin) {
            const fechaInicio = new Date(inicio);
            const fechaFin = new Date(fin);
            const diferencia = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24));
            
            if (diferencia > 0) {
                duracionDiv.textContent = `Duración: ${diferencia} días`;
                duracionDiv.className = 'form-text text-success';
            } else {
                duracionDiv.textContent = 'La fecha de fin debe ser posterior a la de inicio';
                duracionDiv.className = 'form-text text-danger';
            }
        } else if (inicio && !fin) {
            duracionDiv.textContent = 'Sin límite de tiempo';
            duracionDiv.className = 'form-text text-info';
        } else {
            duracionDiv.textContent = '';
        }
    }

    // Actualizar vista previa
    function actualizarVistaPrevia() {
        // Código
        document.getElementById('preview_codigo').textContent = 
            codigoInput.value || '-';
        
        // Tipo
        const tipo = tipoPersonal.checked ? 'Personal' : 'General';
        document.getElementById('preview_tipo').textContent = tipo;
        
        // Propietario
        let propietario = 'Código General';
        if (tipoPersonal.checked) {
            if (apoderadoSelect.value) {
                const selectedOption = apoderadoSelect.options[apoderadoSelect.selectedIndex];
                propietario = selectedOption.text.split('(')[0].trim();
            } else if (familiaSelect.value) {
                const selectedOption = familiaSelect.options[familiaSelect.selectedIndex];
                propietario = 'Familia ' + selectedOption.text.split('(')[0].trim();
            }
        }
        document.getElementById('preview_propietario').textContent = propietario;
        
        // Beneficios
        document.getElementById('preview_beneficio_referente').textContent = 
            beneficioReferenteTextarea.value || '-';
        document.getElementById('preview_beneficio_referido').textContent = 
            beneficioReferidoTextarea.value || '-';
        
        // Usos
        const usos = limiteUsosInput.value || 'Ilimitado';
        document.getElementById('preview_usos').textContent = usos;
        
        // Fechas
        const inicio = fechaInicioInput.value ? 
            new Date(fechaInicioInput.value).toLocaleDateString('es-PE') : '-';
        const fin = fechaFinInput.value ? 
            new Date(fechaFinInput.value).toLocaleDateString('es-PE') : 'Sin límite';
        
        document.getElementById('preview_inicio').textContent = inicio;
        document.getElementById('preview_fin').textContent = fin;
    }

    // Validación del formulario
    formCrear.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formCrear.checkValidity()) {
            e.stopPropagation();
            formCrear.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor corrija los errores marcados antes de continuar',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }

        // Validación adicional: fecha fin debe ser posterior a fecha inicio
        if (fechaFinInput.value) {
            const inicio = new Date(fechaInicioInput.value);
            const fin = new Date(fechaFinInput.value);
            
            if (fin <= inicio) {
                Swal.fire({
                    title: 'Error de Validación',
                    text: 'La fecha de fin debe ser posterior a la fecha de inicio',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
        }

        // Confirmación antes de crear
        Swal.fire({
            title: '¿Crear código de referido?',
            html: `
                <div class="text-start">
                    <strong>Código:</strong> ${codigoInput.value}<br>
                    <strong>Tipo:</strong> ${tipoPersonal.checked ? 'Personal' : 'General'}<br>
                    <strong>Usos permitidos:</strong> ${limiteUsosInput.value || 'Ilimitado'}<br>
                    <strong>Vigencia:</strong> ${fechaInicioInput.value} al ${fechaFinInput.value || 'Sin límite'}
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4680ff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, crear',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                formCrear.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalCrearCodigo').addEventListener('hidden.bs.modal', function() {
        formCrear.reset();
        formCrear.classList.remove('was-validated');
        codigoInput.classList.remove('is-valid', 'is-invalid');
        divApoderado.classList.add('d-none');
        divFamilia.classList.add('d-none');
        familiaSelect.disabled = false;
        limiteUsosInput.disabled = false;
        fechaFinInput.disabled = false;
        document.getElementById('familia_apoderado').textContent = '';
        document.getElementById('duracion_codigo').textContent = '';
        document.getElementById('crear_descripcion_count').textContent = '0';
        document.getElementById('crear_beneficio_referente_count').textContent = '0';
        document.getElementById('crear_beneficio_referido_count').textContent = '0';
        actualizarVistaPrevia();
    });

    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>