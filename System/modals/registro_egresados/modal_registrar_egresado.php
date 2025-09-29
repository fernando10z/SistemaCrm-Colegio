<!-- Modal Registrar Egresado -->
<div class="modal fade" id="modalRegistrarEgresado" tabindex="-1" aria-labelledby="modalRegistrarEgresadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarEgresadoLabel">
          <i class="ti ti-user-plus me-2"></i>
          Registrar Nuevo Egresado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formRegistrarEgresado" method="POST" action="acciones/registro_egresados/registro_egresado.php" novalidate>
        <input type="hidden" name="accion" value="registrar_egresado">
        
        <div class="modal-body">
          <div class="row">
            <!-- Columna Izquierda: Datos Personales -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-user me-1"></i>
                    Datos Personales
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Código de Egresado -->
                  <div class="mb-3">
                    <label for="codigo_exalumno" class="form-label">
                      Código de Egresado <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="codigo_exalumno" name="codigo_exalumno" 
                           pattern="^EX[0-9]{7}$" 
                           placeholder="EX2024001" 
                           maxlength="10" 
                           required
                           title="Formato: EX seguido de 7 dígitos (ej: EX2024001)">
                    <div class="invalid-feedback">
                      El código debe tener el formato EX seguido de 7 dígitos (ej: EX2024001)
                    </div>
                  </div>

                  <!-- Tipo de Documento -->
                  <div class="mb-3">
                    <label for="tipo_documento" class="form-label">
                      Tipo de Documento <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                      <option value="">Seleccionar tipo</option>
                      <option value="DNI">DNI - Documento Nacional de Identidad</option>
                      <option value="CE">CE - Carnet de Extranjería</option>
                      <option value="pasaporte">Pasaporte</option>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un tipo de documento
                    </div>
                  </div>

                  <!-- Número de Documento -->
                  <div class="mb-3">
                    <label for="numero_documento" class="form-label">
                      Número de Documento <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="numero_documento" name="numero_documento" 
                           required maxlength="12"
                           title="Ingrese un número de documento válido">
                    <div class="invalid-feedback" id="documento-feedback">
                      Ingrese un número de documento válido
                    </div>
                    <small class="form-text text-muted" id="documento-help">
                      DNI: 8 dígitos | CE: 9 dígitos | Pasaporte: 6-12 caracteres
                    </small>
                  </div>

                  <!-- Nombres -->
                  <div class="mb-3">
                    <label for="nombres" class="form-label">
                      Nombres <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="nombres" name="nombres" 
                           pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{2,50}$"
                           minlength="2" maxlength="50" 
                           required
                           title="Solo letras y espacios, entre 2 y 50 caracteres">
                    <div class="invalid-feedback">
                      Los nombres solo pueden contener letras y espacios (2-50 caracteres)
                    </div>
                  </div>

                  <!-- Apellidos -->
                  <div class="mb-3">
                    <label for="apellidos" class="form-label">
                      Apellidos <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="apellidos" name="apellidos" 
                           pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{2,50}$"
                           minlength="2" maxlength="50" 
                           required
                           title="Solo letras y espacios, entre 2 y 50 caracteres">
                    <div class="invalid-feedback">
                      Los apellidos solo pueden contener letras y espacios (2-50 caracteres)
                    </div>
                  </div>

                  <!-- Fecha de Nacimiento -->
                  <div class="mb-3">
                    <label for="fecha_nacimiento" class="form-label">
                      Fecha de Nacimiento
                    </label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                           min="1950-01-01" max="2010-12-31">
                    <div class="invalid-feedback">
                      Ingrese una fecha válida (entre 1950 y 2010)
                    </div>
                  </div>

                  <!-- Género -->
                  <div class="mb-3">
                    <label for="genero" class="form-label">Género</label>
                    <select class="form-select" id="genero" name="genero">
                      <option value="">No especificar</option>
                      <option value="M">Masculino</option>
                      <option value="F">Femenino</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Datos de Contacto y Académicos -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-phone me-1"></i>
                    Contacto y Datos Académicos
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Email -->
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           maxlength="100"
                           pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                           title="Ingrese un email válido">
                    <div class="invalid-feedback">
                      Ingrese un email válido (ejemplo@dominio.com)
                    </div>
                  </div>

                  <!-- Teléfono -->
                  <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                           pattern="^(\+51|51)?[0-9]{9}$"
                           maxlength="15"
                           placeholder="+51 987654321"
                           title="Formato peruano: +51 seguido de 9 dígitos">
                    <div class="invalid-feedback">
                      Formato válido: +51 seguido de 9 dígitos
                    </div>
                  </div>

                  <!-- WhatsApp -->
                  <div class="mb-3">
                    <label for="whatsapp" class="form-label">WhatsApp</label>
                    <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                           pattern="^(\+51|51)?[0-9]{9}$"
                           maxlength="15"
                           placeholder="+51 987654321"
                           title="Formato peruano: +51 seguido de 9 dígitos">
                    <div class="invalid-feedback">
                      Formato válido: +51 seguido de 9 dígitos
                    </div>
                  </div>

                  <!-- Promoción de Egreso -->
                  <div class="mb-3">
                    <label for="promocion_egreso" class="form-label">
                      Promoción de Egreso <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="promocion_egreso" name="promocion_egreso" required>
                      <option value="">Seleccionar año</option>
                      <?php for($year = date('Y'); $year >= 1980; $year--): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                      <?php endfor; ?>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar el año de egreso
                    </div>
                  </div>

                  <!-- Fecha de Egreso -->
                  <div class="mb-3">
                    <label for="fecha_egreso" class="form-label">Fecha de Egreso</label>
                    <input type="date" class="form-control" id="fecha_egreso" name="fecha_egreso" 
                           min="1980-01-01" max="<?php echo date('Y-m-d'); ?>">
                    <div class="invalid-feedback">
                      La fecha debe estar entre 1980 y hoy
                    </div>
                  </div>

                  <!-- Último Grado -->
                  <div class="mb-3">
                    <label for="ultimo_grado" class="form-label">Último Grado Cursado</label>
                    <select class="form-select" id="ultimo_grado" name="ultimo_grado">
                      <option value="">Seleccionar grado</option>
                      <option value="5° Secundaria">5° Secundaria</option>
                      <option value="4° Secundaria">4° Secundaria (retirado)</option>
                      <option value="3° Secundaria">3° Secundaria (retirado)</option>
                      <option value="6° Primaria">6° Primaria (retirado)</option>
                      <option value="Inicial">Inicial (retirado)</option>
                    </select>
                  </div>

                  <!-- Estado de Contacto -->
                  <div class="mb-3">
                    <label for="estado_contacto" class="form-label">
                      Estado de Contacto <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="estado_contacto" name="estado_contacto" required>
                      <option value="">Seleccionar estado</option>
                      <option value="activo">Activo - Mantiene contacto</option>
                      <option value="sin_contacto">Sin contacto - No localizable</option>
                      <option value="no_contactar">No contactar - No desea comunicación</option>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un estado de contacto
                    </div>
                  </div>

                  <!-- Acepta Comunicaciones -->
                  <div class="mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="acepta_comunicaciones" 
                             name="acepta_comunicaciones" value="1" checked>
                      <label class="form-check-label" for="acepta_comunicaciones">
                        Acepta recibir comunicaciones del colegio
                      </label>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Fila Completa: Situación Actual -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-briefcase me-1"></i>
                    Situación Actual (Opcional)
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    
                    <!-- Ocupación Actual -->
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="ocupacion_actual" class="form-label">Ocupación Actual</label>
                        <input type="text" class="form-control" id="ocupacion_actual" name="ocupacion_actual" 
                               maxlength="100"
                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-\.]{2,100}$"
                               title="Solo letras, espacios, guiones y puntos (2-100 caracteres)">
                        <div class="invalid-feedback">
                          Solo letras, espacios, guiones y puntos (2-100 caracteres)
                        </div>
                      </div>
                    </div>

                    <!-- Empresa Actual -->
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="empresa_actual" class="form-label">Empresa/Institución</label>
                        <input type="text" class="form-control" id="empresa_actual" name="empresa_actual" 
                               maxlength="100"
                               title="Nombre de la empresa o institución donde labora">
                      </div>
                    </div>

                    <!-- Estudios Superiores -->
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="estudios_superiores" class="form-label">Estudios Superiores</label>
                        <input type="text" class="form-control" id="estudios_superiores" name="estudios_superiores" 
                               maxlength="100"
                               title="Universidad/Instituto y carrera">
                      </div>
                    </div>

                  </div>

                  <div class="row">
                    
                    <!-- Dirección Actual -->
                    <div class="col-md-8">
                      <div class="mb-3">
                        <label for="direccion_actual" class="form-label">Dirección Actual</label>
                        <textarea class="form-control" id="direccion_actual" name="direccion_actual" 
                                  rows="2" maxlength="200"
                                  title="Dirección de residencia actual"></textarea>
                      </div>
                    </div>

                    <!-- Distrito Actual -->
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="distrito_actual" class="form-label">Distrito</label>
                        <input type="text" class="form-control" id="distrito_actual" name="distrito_actual" 
                               maxlength="50"
                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-]{2,50}$"
                               title="Solo letras, espacios y guiones (2-50 caracteres)">
                        <div class="invalid-feedback">
                          Solo letras, espacios y guiones (2-50 caracteres)
                        </div>
                      </div>
                    </div>

                  </div>

                  <!-- Observaciones -->
                  <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" 
                              rows="3" maxlength="500"
                              title="Información adicional relevante"></textarea>
                    <div class="form-text">Máximo 500 caracteres</div>
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
            Registrar Egresado
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRegistrarEgresado');
    const tipoDocumento = document.getElementById('tipo_documento');
    const numeroDocumento = document.getElementById('numero_documento');
    const documentoFeedback = document.getElementById('documento-feedback');
    const promocionEgreso = document.getElementById('promocion_egreso');
    const fechaEgreso = document.getElementById('fecha_egreso');

    // Validación dinámica del número de documento
    tipoDocumento.addEventListener('change', function() {
        const tipo = this.value;
        
        switch(tipo) {
            case 'DNI':
                numeroDocumento.pattern = '^[0-9]{8}$';
                numeroDocumento.maxLength = 8;
                numeroDocumento.placeholder = '12345678';
                numeroDocumento.title = 'DNI debe tener exactamente 8 dígitos';
                documentoFeedback.textContent = 'DNI debe tener exactamente 8 dígitos';
                break;
            case 'CE':
                numeroDocumento.pattern = '^[0-9]{9}$';
                numeroDocumento.maxLength = 9;
                numeroDocumento.placeholder = '123456789';
                numeroDocumento.title = 'Carnet de Extranjería debe tener exactamente 9 dígitos';
                documentoFeedback.textContent = 'Carnet de Extranjería debe tener exactamente 9 dígitos';
                break;
            case 'pasaporte':
                numeroDocumento.pattern = '^[A-Z0-9]{6,12}$';
                numeroDocumento.maxLength = 12;
                numeroDocumento.placeholder = 'ABC123456';
                numeroDocumento.title = 'Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos';
                documentoFeedback.textContent = 'Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos';
                break;
            default:
                numeroDocumento.pattern = '';
                numeroDocumento.placeholder = '';
                numeroDocumento.title = '';
                documentoFeedback.textContent = 'Seleccione primero el tipo de documento';
        }
        
        // Limpiar el campo cuando cambie el tipo
        numeroDocumento.value = '';
        numeroDocumento.classList.remove('is-valid', 'is-invalid');
    });

    // Validación en tiempo real del número de documento
    numeroDocumento.addEventListener('input', function() {
        const tipo = tipoDocumento.value;
        const valor = this.value;
        
        if (!tipo) {
            this.setCustomValidity('Seleccione primero el tipo de documento');
            return;
        }
        
        let esValido = false;
        
        switch(tipo) {
            case 'DNI':
                esValido = /^[0-9]{8}$/.test(valor);
                break;
            case 'CE':
                esValido = /^[0-9]{9}$/.test(valor);
                break;
            case 'pasaporte':
                esValido = /^[A-Z0-9]{6,12}$/.test(valor.toUpperCase());
                this.value = valor.toUpperCase(); // Convertir a mayúsculas
                break;
        }
        
        if (valor && !esValido) {
            this.setCustomValidity('Formato de documento inválido');
        } else {
            this.setCustomValidity('');
        }
    });

    // Sincronizar promoción con fecha de egreso
    promocionEgreso.addEventListener('change', function() {
        if (this.value) {
            const año = this.value;
            fechaEgreso.min = año + '-01-01';
            fechaEgreso.max = año + '-12-31';
            
            // Si ya hay una fecha que no corresponde al año, limpiarla
            if (fechaEgreso.value) {
                const fechaActual = new Date(fechaEgreso.value);
                if (fechaActual.getFullYear() !== parseInt(año)) {
                    fechaEgreso.value = '';
                }
            }
        }
    });

    // Validar que la fecha de egreso corresponda con la promoción
    fechaEgreso.addEventListener('change', function() {
        if (this.value && promocionEgreso.value) {
            const fechaSelected = new Date(this.value);
            const añoPromocion = parseInt(promocionEgreso.value);
            
            if (fechaSelected.getFullYear() !== añoPromocion) {
                this.setCustomValidity('La fecha debe corresponder al año de promoción seleccionado');
            } else {
                this.setCustomValidity('');
            }
        }
    });

    // Validación general del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            
            // Mostrar SweetAlert con errores
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor corrija los errores marcados en rojo antes de continuar',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }
        
        // Si todo está válido, mostrar confirmación
        Swal.fire({
            title: '¿Registrar Egresado?',
            text: 'Se creará un nuevo registro de egresado con la información proporcionada',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar formulario
                form.submit();
            }
        });
    });

    // Autocompletar WhatsApp con el teléfono
    document.getElementById('telefono').addEventListener('blur', function() {
        const whatsappField = document.getElementById('whatsapp');
        if (this.value && !whatsappField.value) {
            whatsappField.value = this.value;
        }
    });

    // Formatear teléfonos automáticamente
    function formatearTelefono(input) {
        input.addEventListener('input', function() {
            let valor = this.value.replace(/\D/g, ''); // Solo números
            
            if (valor.startsWith('51')) {
                valor = '+' + valor;
            } else if (valor.length === 9) {
                valor = '+51' + valor;
            }
            
            this.value = valor;
        });
    }
    
    formatearTelefono(document.getElementById('telefono'));
    formatearTelefono(document.getElementById('whatsapp'));

    // Generar código automático basado en año y siguiente número
    promocionEgreso.addEventListener('change', function() {
        if (this.value && !document.getElementById('codigo_exalumno').value) {
            const año = this.value;
            // Aquí podrías hacer una consulta AJAX para obtener el siguiente número
            // Por ahora, genero uno de ejemplo
            const siguienteNumero = String(Math.floor(Math.random() * 999) + 1).padStart(3, '0');
            document.getElementById('codigo_exalumno').value = 'EX' + año + siguienteNumero;
        }
    });
});
</script>