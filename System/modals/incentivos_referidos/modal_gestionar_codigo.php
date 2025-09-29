<!-- modals/incentivos_referidos/modal_gestionar_codigo.php -->
<style>
/* Estilos para SweetAlert2 */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

.modal {
    z-index: 9999 !important;
}

.modal-backdrop {
    z-index: 9998 !important;
}

/* Estilos personalizados para el modal */
.form-label-required::after {
    content: " *";
    color: #dc3545;
}

.input-group-pastel {
    border: 2px solid #D4B8E6;
    border-radius: 8px;
}

.input-group-pastel .form-control,
.input-group-pastel .form-select {
    border: none;
}

.input-group-pastel .input-group-text {
    background-color: #D4B8E6;
    color: #4B0082;
    border: none;
}

.codigo-preview {
    background: linear-gradient(135deg, #D4B8E6 0%, #FFB8D4 100%);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    letter-spacing: 3px;
    color: #4B0082;
    margin: 10px 0;
}

.beneficio-preview {
    background-color: #FFF4B8;
    padding: 10px;
    border-radius: 8px;
    border-left: 4px solid #FFD700;
    margin: 5px 0;
}

.character-counter {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
}

.character-counter.warning {
    color: #ffc107;
}

.character-counter.danger {
    color: #dc3545;
}
</style>

<!-- Modal Gestionar Código -->
<div class="modal fade" id="modalGestionarCodigo" tabindex="-1" aria-labelledby="modalGestionarCodigoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #D4B8E6 0%, #FFB8D4 100%);">
        <h5 class="modal-title" id="modalGestionarCodigoLabel" style="color: #4B0082;">
          <i class="ti ti-code-plus me-2"></i>
          <span id="tituloModalCodigo">Crear Código de Referido</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGestionarCodigo" method="POST" action="acciones/incentivos_referidos/gestionar_referidos.php" novalidate>
        <input type="hidden" name="accion" id="accion_codigo" value="crear_codigo">
        <input type="hidden" name="codigo_id" id="codigo_id">
        
        <div class="modal-body">
          
          <!-- Alerta Informativa -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Información:</strong> Cree códigos personalizados para familias específicas o códigos generales para campañas masivas.
          </div>

          <div class="row">
            <!-- Columna Izquierda: Configuración del Código -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header" style="background-color: #F5F5F5;">
                  <h6 class="mb-0">
                    <i class="ti ti-settings me-1"></i>
                    Configuración del Código
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Tipo de Código -->
                  <div class="mb-3">
                    <label for="tipo_codigo" class="form-label form-label-required">
                      Tipo de Código
                    </label>
                    <select class="form-select" id="tipo_codigo" name="tipo_codigo" required>
                      <option value="">Seleccionar tipo</option>
                      <option value="personalizado">Personalizado - Para apoderado/familia específica</option>
                      <option value="general">General - Para campañas masivas</option>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un tipo de código
                    </div>
                  </div>

                  <!-- Selección de Apoderado (solo si es personalizado) -->
                  <div class="mb-3" id="campo_apoderado" style="display: none;">
                    <label for="apoderado_id" class="form-label">
                      Seleccionar Apoderado
                    </label>
                    <select class="form-select" id="apoderado_id" name="apoderado_id">
                      <option value="">Seleccionar apoderado</option>
                      <?php
                      $apoderados_sql = "SELECT a.id, CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo, 
                                         a.email, f.codigo_familia
                                         FROM apoderados a
                                         INNER JOIN familias f ON a.familia_id = f.id
                                         WHERE a.activo = 1
                                         ORDER BY a.apellidos, a.nombres";
                      $apoderados_result = $conn->query($apoderados_sql);
                      while($apoderado = $apoderados_result->fetch_assoc()) {
                          echo "<option value='{$apoderado['id']}' data-email='{$apoderado['email']}'>
                                {$apoderado['nombre_completo']} - {$apoderado['codigo_familia']}
                                </option>";
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted">
                      <i class="ti ti-info-circle"></i> El código se asociará automáticamente a su familia
                    </small>
                  </div>

                  <!-- Selección de Familia (solo si es personalizado) -->
                  <div class="mb-3" id="campo_familia" style="display: none;">
                    <label for="familia_id" class="form-label">
                      O Seleccionar Familia Directamente
                    </label>
                    <select class="form-select" id="familia_id" name="familia_id">
                      <option value="">Seleccionar familia</option>
                      <?php
                      $familias_sql = "SELECT id, codigo_familia, apellido_principal 
                                       FROM familias 
                                       WHERE activo = 1 
                                       ORDER BY apellido_principal";
                      $familias_result = $conn->query($familias_sql);
                      while($familia = $familias_result->fetch_assoc()) {
                          echo "<option value='{$familia['id']}'>
                                {$familia['codigo_familia']} - {$familia['apellido_principal']}
                                </option>";
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted">
                      <i class="ti ti-alert-circle"></i> Solo si no seleccionó un apoderado específico
                    </small>
                  </div>

                  <!-- Código de Referido -->
                  <div class="mb-3">
                    <label for="codigo" class="form-label form-label-required">
                      Código de Referido
                    </label>
                    <div class="input-group input-group-pastel">
                      <span class="input-group-text">
                        <i class="ti ti-ticket"></i>
                      </span>
                      <input type="text" class="form-control text-uppercase" id="codigo" name="codigo" 
                             required
                             pattern="^[A-Z0-9]{4,20}$"
                             minlength="4" maxlength="20"
                             style="font-family: 'Courier New', monospace; font-weight: bold;"
                             title="Solo letras mayúsculas y números, entre 4 y 20 caracteres">
                      <button class="btn btn-outline-secondary" type="button" id="btnGenerarCodigo" 
                              style="background-color: #D4B8E6; color: #4B0082; border: none;">
                        <i class="ti ti-refresh"></i> Generar
                      </button>
                    </div>
                    <div class="invalid-feedback">
                      El código debe tener entre 4 y 20 caracteres (solo letras mayúsculas y números)
                    </div>
                    <div class="valid-feedback" id="codigo-disponible-msg">
                      ✓ Código disponible
                    </div>
                    <small class="form-text text-muted">
                      El código se convertirá automáticamente a mayúsculas
                    </small>
                  </div>

                  <!-- Vista Previa del Código -->
                  <div class="codigo-preview" id="codigoPreview">
                    <i class="ti ti-ticket"></i> CÓDIGO-AQUÍ
                  </div>

                  <!-- Descripción del Código -->
                  <div class="mb-3">
                    <label for="descripcion" class="form-label">
                      Descripción del Código
                    </label>
                    <textarea class="form-control" id="descripcion" name="descripcion" 
                              rows="2" maxlength="200"
                              title="Descripción breve del propósito del código"></textarea>
                    <div class="character-counter">
                      <span id="descripcion-counter">0</span> / 200 caracteres
                    </div>
                    <small class="form-text text-muted">
                      Ejemplo: "Código familia García - Campaña Verano 2025"
                    </small>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Beneficios y Límites -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header" style="background-color: #F5F5F5;">
                  <h6 class="mb-0">
                    <i class="ti ti-gift me-1"></i>
                    Beneficios e Incentivos
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Beneficio para el Referente -->
                  <div class="mb-3">
                    <label for="beneficio_referente" class="form-label form-label-required">
                      <i class="ti ti-user-check text-success"></i>
                      Beneficio para el Referente
                    </label>
                    <textarea class="form-control" id="beneficio_referente" name="beneficio_referente" 
                              rows="2" maxlength="200" required
                              placeholder="Ej: 10% descuento en pensión de octubre"
                              title="Describe el beneficio que recibe quien refiere"></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referente
                    </div>
                    <div class="character-counter">
                      <span id="beneficio-referente-counter">0</span> / 200 caracteres
                    </div>
                  </div>

                  <!-- Preview Beneficio Referente -->
                  <div class="beneficio-preview" id="beneficioReferentePreview">
                    <small><strong>Vista Previa:</strong></small><br>
                    <span id="beneficioReferenteText">Ingrese el beneficio para ver la vista previa</span>
                  </div>

                  <!-- Beneficio para el Referido -->
                  <div class="mb-3">
                    <label for="beneficio_referido" class="form-label form-label-required">
                      <i class="ti ti-user-plus text-info"></i>
                      Beneficio para el Nuevo Referido
                    </label>
                    <textarea class="form-control" id="beneficio_referido" name="beneficio_referido" 
                              rows="2" maxlength="200" required
                              placeholder="Ej: 15% descuento en matrícula 2026"
                              title="Describe el beneficio que recibe el nuevo lead"></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referido
                    </div>
                    <div class="character-counter">
                      <span id="beneficio-referido-counter">0</span> / 200 caracteres
                    </div>
                  </div>

                  <!-- Preview Beneficio Referido -->
                  <div class="beneficio-preview" id="beneficioReferidoPreview" 
                       style="background-color: #D4EDDA; border-left-color: #28a745;">
                    <small><strong>Vista Previa:</strong></small><br>
                    <span id="beneficioReferidoText">Ingrese el beneficio para ver la vista previa</span>
                  </div>

                  <!-- Límite de Usos -->
                  <div class="mb-3">
                    <label for="limite_usos" class="form-label">
                      <i class="ti ti-hash"></i>
                      Límite de Usos
                    </label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="limite_usos" name="limite_usos" 
                             min="1" max="999"
                             placeholder="Dejar vacío para ilimitado"
                             title="Número máximo de veces que se puede usar el código">
                      <span class="input-group-text">
                        <i class="ti ti-infinity"></i> Ilimitado
                      </span>
                    </div>
                    <small class="form-text text-muted">
                      Si no especifica, el código podrá usarse infinitas veces
                    </small>
                  </div>

                  <!-- Fecha de Inicio -->
                  <div class="mb-3">
                    <label for="fecha_inicio" class="form-label form-label-required">
                      <i class="ti ti-calendar-event"></i>
                      Fecha de Inicio
                    </label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           required
                           title="Fecha desde la cual el código estará activo">
                    <div class="invalid-feedback">
                      Debe especificar la fecha de inicio
                    </div>
                  </div>

                  <!-- Fecha de Fin -->
                  <div class="mb-3">
                    <label for="fecha_fin" class="form-label">
                      <i class="ti ti-calendar-x"></i>
                      Fecha de Fin (Opcional)
                    </label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                           title="Fecha hasta la cual el código estará activo">
                    <small class="form-text text-muted">
                      Dejar vacío si no tiene fecha de vencimiento
                    </small>
                  </div>

                  <!-- Activar Código -->
                  <div class="mb-3">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="activo" 
                             name="activo" value="1" checked>
                      <label class="form-check-label" for="activo">
                        <strong>Código Activo</strong>
                        <br><small class="text-muted">El código estará disponible inmediatamente</small>
                      </label>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Resumen del Código -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-info">
                <div class="card-header" style="background-color: #D1ECF1;">
                  <h6 class="mb-0" style="color: #0c5460;">
                    <i class="ti ti-clipboard-check me-1"></i>
                    Resumen de Configuración
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-4">
                      <small class="text-muted">Tipo:</small>
                      <div class="fw-bold" id="resumen_tipo">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Referente:</small>
                      <div class="fw-bold" id="resumen_referente">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Límite:</small>
                      <div class="fw-bold" id="resumen_limite">-</div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <small class="text-muted">Vigencia:</small>
                      <div class="fw-bold" id="resumen_vigencia">-</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted">Estado:</small>
                      <div class="fw-bold" id="resumen_estado">-</div>
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
          <button type="button" class="btn btn-info" id="btnValidarCodigo">
            <i class="ti ti-check me-1"></i>
            Validar Configuración
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            Guardar Código
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formCodigo = document.getElementById('formGestionarCodigo');
    const tipoCodigo = document.getElementById('tipo_codigo');
    const campoApoderado = document.getElementById('campo_apoderado');
    const campoFamilia = document.getElementById('campo_familia');
    const codigoInput = document.getElementById('codigo');
    const codigoPreview = document.getElementById('codigoPreview');
    const descripcionInput = document.getElementById('descripcion');
    const beneficioReferenteInput = document.getElementById('beneficio_referente');
    const beneficioReferidoInput = document.getElementById('beneficio_referido');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const limiteUsos = document.getElementById('limite_usos');

    // Establecer fecha mínima como hoy
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.min = hoy;
    fechaInicio.value = hoy;

    // Mostrar/ocultar campos según tipo de código
    tipoCodigo.addEventListener('change', function() {
        if (this.value === 'personalizado') {
            campoApoderado.style.display = 'block';
            campoFamilia.style.display = 'block';
        } else {
            campoApoderado.style.display = 'none';
            campoFamilia.style.display = 'none';
            document.getElementById('apoderado_id').value = '';
            document.getElementById('familia_id').value = '';
        }
        actualizarResumen();
    });

    // Convertir código a mayúsculas automáticamente
    codigoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        codigoPreview.innerHTML = '<i class="ti ti-ticket"></i> ' + (this.value || 'CÓDIGO-AQUÍ');
        
        // Validar disponibilidad del código
        if (this.value.length >= 4) {
            validarDisponibilidadCodigo(this.value);
        }
        
        actualizarResumen();
    });

    // Generar código aleatorio
    document.getElementById('btnGenerarCodigo').addEventListener('click', function() {
        const tipoSeleccionado = tipoCodigo.value;
        let prefijo = '';
        
        if (tipoSeleccionado === 'personalizado') {
            const apoderadoSelect = document.getElementById('apoderado_id');
            const familiaSelect = document.getElementById('familia_id');
            
            if (apoderadoSelect.value) {
                const nombreApoderado = apoderadoSelect.options[apoderadoSelect.selectedIndex].text;
                const apellido = nombreApoderado.split(' ')[nombreApoderado.split(' ').length - 1];
                prefijo = apellido.substring(0, 4).toUpperCase();
            } else if (familiaSelect.value) {
                const nombreFamilia = familiaSelect.options[familiaSelect.selectedIndex].text;
                prefijo = nombreFamilia.split('-')[1].trim().substring(0, 4).toUpperCase();
            }
        }
        
        const year = new Date().getFullYear();
        const random = Math.floor(Math.random() * 9999).toString().padStart(4, '0');
        const codigoGenerado = (prefijo || 'REF') + year + random;
        
        codigoInput.value = codigoGenerado;
        codigoPreview.innerHTML = '<i class="ti ti-ticket"></i> ' + codigoGenerado;
        
        Swal.fire({
            icon: 'success',
            title: 'Código Generado',
            text: 'Se ha generado el código: ' + codigoGenerado,
            timer: 2000,
            showConfirmButton: false
        });
    });

    // Validar disponibilidad del código mediante AJAX
    let timeoutCodigo;
    function validarDisponibilidadCodigo(codigo) {
        clearTimeout(timeoutCodigo);
        timeoutCodigo = setTimeout(() => {
            $.ajax({
                url: 'acciones/incentivos_referidos/validar_codigo.php',
                type: 'POST',
                data: { 
                    codigo: codigo,
                    codigo_id: document.getElementById('codigo_id').value 
                },
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.disponible) {
                        codigoInput.classList.add('is-valid');
                        codigoInput.classList.remove('is-invalid');
                        document.getElementById('codigo-disponible-msg').style.display = 'block';
                    } else {
                        codigoInput.classList.add('is-invalid');
                        codigoInput.classList.remove('is-valid');
                        codigoInput.setCustomValidity('Este código ya está en uso');
                    }
                },
                error: function() {
                    codigoInput.classList.remove('is-valid', 'is-invalid');
                }
            });
        }, 500);
    }

    // Contadores de caracteres
    function setupCharacterCounter(input, counter, max) {
        input.addEventListener('input', function() {
            const length = this.value.length;
            const counterElement = document.getElementById(counter);
            counterElement.textContent = length;
            
            const counterParent = counterElement.parentElement;
            counterParent.classList.remove('warning', 'danger');
            
            if (length > max * 0.9) {
                counterParent.classList.add('danger');
            } else if (length > max * 0.7) {
                counterParent.classList.add('warning');
            }
        });
    }

    setupCharacterCounter(descripcionInput, 'descripcion-counter', 200);
    setupCharacterCounter(beneficioReferenteInput, 'beneficio-referente-counter', 200);
    setupCharacterCounter(beneficioReferidoInput, 'beneficio-referido-counter', 200);

    // Vista previa de beneficios
    beneficioReferenteInput.addEventListener('input', function() {
        const preview = document.getElementById('beneficioReferenteText');
        preview.textContent = this.value || 'Ingrese el beneficio para ver la vista previa';
    });

    beneficioReferidoInput.addEventListener('input', function() {
        const preview = document.getElementById('beneficioReferidoText');
        preview.textContent = this.value || 'Ingrese el beneficio para ver la vista previa';
    });

    // Actualizar fecha mínima de fin cuando cambia fecha de inicio
    fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
        if (fechaFin.value && fechaFin.value < this.value) {
            fechaFin.value = '';
        }
        actualizarResumen();
    });

    // Validar que fecha fin sea posterior a fecha inicio
    fechaFin.addEventListener('change', function() {
        if (this.value && fechaInicio.value) {
            const inicio = new Date(fechaInicio.value);
            const fin = new Date(this.value);
            
            if (fin <= inicio) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha Inválida',
                    text: 'La fecha de fin debe ser posterior a la fecha de inicio',
                    confirmButtonColor: '#6f42c1'
                });
                this.value = '';
            }
        }
        actualizarResumen();
    });

    // Validar límite de usos
    limiteUsos.addEventListener('input', function() {
        if (this.value) {
            const valor = parseInt(this.value);
            if (valor < 1) {
                this.value = 1;
            } else if (valor > 999) {
                this.value = 999;
            }
        }
        actualizarResumen();
    });

    // Actualizar resumen en tiempo real
    function actualizarResumen() {
        // Tipo
        const tipo = tipoCodigo.value ? tipoCodigo.options[tipoCodigo.selectedIndex].text : '-';
        document.getElementById('resumen_tipo').textContent = tipo;

        // Referente
        let referente = 'Código General';
        const apoderadoSelect = document.getElementById('apoderado_id');
        const familiaSelect = document.getElementById('familia_id');
        
        if (apoderadoSelect.value) {
            referente = apoderadoSelect.options[apoderadoSelect.selectedIndex].text;
        } else if (familiaSelect.value) {
            referente = familiaSelect.options[familiaSelect.selectedIndex].text;
        }
        document.getElementById('resumen_referente').textContent = referente;

        // Límite
        const limite = limiteUsos.value ? limiteUsos.value + ' usos' : 'Ilimitado';
        document.getElementById('resumen_limite').textContent = limite;

        // Vigencia
        let vigencia = '-';
        if (fechaInicio.value) {
            const inicio = new Date(fechaInicio.value).toLocaleDateString('es-PE');
            const fin = fechaFin.value ? new Date(fechaFin.value).toLocaleDateString('es-PE') : 'Sin límite';
            vigencia = `${inicio} - ${fin}`;
        }
        document.getElementById('resumen_vigencia').textContent = vigencia;

        // Estado
        const activo = document.getElementById('activo').checked;
        document.getElementById('resumen_estado').innerHTML = activo ? 
            '<span class="badge" style="background-color: #B8E6B8; color: #2d5016;">Activo</span>' : 
            '<span class="badge" style="background-color: #E0E0E0; color: #666;">Inactivo</span>';
    }

    // Botón validar configuración
    document.getElementById('btnValidarCodigo').addEventListener('click', function() {
        if (!formCodigo.checkValidity()) {
            formCodigo.classList.add('was-validated');
            
            Swal.fire({
                icon: 'error',
                title: 'Formulario Incompleto',
                text: 'Por favor complete todos los campos requeridos',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        // Validaciones adicionales
        const errores = [];

        // Validar que el código tenga longitud adecuada
        if (codigoInput.value.length < 4) {
            errores.push('El código debe tener al menos 4 caracteres');
        }

        // Validar fechas
        if (fechaFin.value && fechaInicio.value) {
            const inicio = new Date(fechaInicio.value);
            const fin = new Date(fechaFin.value);
            if (fin <= inicio) {
                errores.push('La fecha de fin debe ser posterior a la fecha de inicio');
            }
        }

        // Validar que si es personalizado, tenga apoderado o familia
        if (tipoCodigo.value === 'personalizado') {
            if (!document.getElementById('apoderado_id').value && !document.getElementById('familia_id').value) {
                errores.push('Para código personalizado debe seleccionar un apoderado o familia');
            }
        }

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Errores de Validación',
                html: errores.map(e => `• ${e}`).join('<br>'),
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        // Todo válido
        Swal.fire({
            icon: 'success',
            title: 'Configuración Válida',
            html: `
                <div class="text-start">
                    <p><strong>El código está correctamente configurado:</strong></p>
                    <ul>
                        <li>✓ Código: <strong>${codigoInput.value}</strong></li>
                        <li>✓ Tipo: ${tipoCodigo.options[tipoCodigo.selectedIndex].text}</li>
                        <li>✓ Beneficios definidos</li>
                        <li>✓ Fechas válidas</li>
                    </ul>
                </div>
            `,
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#6f42c1'
        });
    });

    // Validación del formulario al enviar
    formCodigo.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            
            Swal.fire({
                icon: 'error',
                title: 'Formulario Incompleto',
                text: 'Por favor corrija los errores antes de continuar',
                confirmButtonColor: '#6f42c1'
            });
            return false;
        }

        // Validaciones finales
        const codigo = codigoInput.value;
        const beneficioRef = beneficioReferenteInput.value;
        const beneficioNew = beneficioReferidoInput.value;

        Swal.fire({
            title: '¿Guardar Código?',
            html: `
                <div class="text-start">
                    <p><strong>Código:</strong> ${codigo}</p>
                    <p><strong>Beneficio Referente:</strong> ${beneficioRef}</p>
                    <p><strong>Beneficio Referido:</strong> ${beneficioNew}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6f42c1',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalGestionarCodigo').addEventListener('hidden.bs.modal', function() {
        formCodigo.reset();
        formCodigo.classList.remove('was-validated');
        campoApoderado.style.display = 'none';
        campoFamilia.style.display = 'none';
        codigoPreview.innerHTML = '<i class="ti ti-ticket"></i> CÓDIGO-AQUÍ';
        codigoInput.classList.remove('is-valid', 'is-invalid');
        actualizarResumen();
    });

    // Listeners para actualizar resumen
    document.getElementById('apoderado_id').addEventListener('change', actualizarResumen);
    document.getElementById('familia_id').addEventListener('change', actualizarResumen);
    document.getElementById('activo').addEventListener('change', actualizarResumen);
});
</script>