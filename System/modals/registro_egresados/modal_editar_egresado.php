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
</style>

<!-- Modal Editar Egresado -->
<div class="modal fade" id="modalEditarEgresado" tabindex="-1" aria-labelledby="modalEditarEgresadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalEditarEgresadoLabel">
          <i class="ti ti-edit me-2"></i>
          Editar Información de Egresado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formEditarEgresado" method="POST" action="acciones/registro_egresados/registro_egresado.php" novalidate>
        <input type="hidden" name="accion" value="editar_egresado">
        <input type="hidden" name="egresado_id" id="edit_egresado_id">
        
        <div class="modal-body">
          
          <!-- Información del Egresado Seleccionado -->
          <div class="alert alert-info" role="alert" id="infoEgresadoEditar">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Editando:</strong> <span id="nombreEgresadoEditar">Seleccione un egresado</span>
          </div>

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
                  
                  <!-- Código de Egresado (Solo lectura) -->
                  <div class="mb-3">
                    <label for="edit_codigo_exalumno" class="form-label">
                      Código de Egresado
                    </label>
                    <input type="text" class="form-control" id="edit_codigo_exalumno" name="codigo_exalumno" 
                           readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">El código no se puede modificar</small>
                  </div>

                  <!-- Tipo de Documento -->
                  <div class="mb-3">
                    <label for="edit_tipo_documento" class="form-label">
                      Tipo de Documento <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="edit_tipo_documento" name="tipo_documento" required>
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
                    <label for="edit_numero_documento" class="form-label">
                      Número de Documento <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="edit_numero_documento" name="numero_documento" 
                           required maxlength="12"
                           title="Ingrese un número de documento válido">
                    <div class="invalid-feedback" id="edit-documento-feedback">
                      Ingrese un número de documento válido
                    </div>
                    <small class="form-text text-muted" id="edit-documento-help">
                      DNI: 8 dígitos | CE: 9 dígitos | Pasaporte: 6-12 caracteres
                    </small>
                  </div>

                  <!-- Nombres -->
                  <div class="mb-3">
                    <label for="edit_nombres" class="form-label">
                      Nombres <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="edit_nombres" name="nombres" 
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
                    <label for="edit_apellidos" class="form-label">
                      Apellidos <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="edit_apellidos" name="apellidos" 
                           pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{2,50}$"
                           minlength="2" maxlength="50" 
                           required
                           title="Solo letras y espacios, entre 2 y 50 caracteres">
                    <div class="invalid-feedback">
                      Los apellidos solo pueden contener letras y espacios (2-50 caracteres)
                    </div>
                  </div>

                  <!-- Estado de Contacto -->
                  <div class="mb-3">
                    <label for="edit_estado_contacto" class="form-label">
                      Estado de Contacto <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="edit_estado_contacto" name="estado_contacto" required>
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
                      <input class="form-check-input" type="checkbox" id="edit_acepta_comunicaciones" 
                             name="acepta_comunicaciones" value="1">
                      <label class="form-check-label" for="edit_acepta_comunicaciones">
                        Acepta recibir comunicaciones del colegio
                      </label>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Datos de Contacto -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-phone me-1"></i>
                    Información de Contacto
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Email -->
                  <div class="mb-3">
                    <label for="edit_email" class="form-label">
                      Email <i class="ti ti-mail text-primary"></i>
                    </label>
                    <input type="email" class="form-control" id="edit_email" name="email" 
                           maxlength="100"
                           pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                           title="Ingrese un email válido">
                    <div class="invalid-feedback">
                      Ingrese un email válido (ejemplo@dominio.com)
                    </div>
                    <small class="form-text text-success" id="emailStatus"></small>
                  </div>

                  <!-- Teléfono -->
                  <div class="mb-3">
                    <label for="edit_telefono" class="form-label">
                      Teléfono <i class="ti ti-phone text-success"></i>
                    </label>
                    <input type="tel" class="form-control" id="edit_telefono" name="telefono" 
                           pattern="^(\+51|51)?[0-9]{9}$"
                           maxlength="15"
                           placeholder="+51 987654321"
                           title="Formato peruano: +51 seguido de 9 dígitos">
                    <div class="invalid-feedback">
                      Formato válido: +51 seguido de 9 dígitos
                    </div>
                    <small class="form-text text-success" id="telefonoStatus"></small>
                  </div>

                  <!-- WhatsApp -->
                  <div class="mb-3">
                    <label for="edit_whatsapp" class="form-label">
                      WhatsApp <i class="ti ti-brand-whatsapp text-success"></i>
                    </label>
                    <input type="tel" class="form-control" id="edit_whatsapp" name="whatsapp" 
                           pattern="^(\+51|51)?[0-9]{9}$"
                           maxlength="15"
                           placeholder="+51 987654321"
                           title="Formato peruano: +51 seguido de 9 dígitos">
                    <div class="invalid-feedback">
                      Formato válido: +51 seguido de 9 dígitos
                    </div>
                  </div>

                  <!-- Dirección Actual -->
                  <div class="mb-3">
                    <label for="edit_direccion_actual" class="form-label">
                      Dirección Actual <i class="ti ti-map-pin text-warning"></i>
                    </label>
                    <textarea class="form-control" id="edit_direccion_actual" name="direccion_actual" 
                              rows="2" maxlength="200"
                              title="Dirección de residencia actual"></textarea>
                    <div class="form-text">Máximo 200 caracteres</div>
                  </div>

                  <!-- Distrito Actual -->
                  <div class="mb-3">
                    <label for="edit_distrito_actual" class="form-label">Distrito</label>
                    <input type="text" class="form-control" id="edit_distrito_actual" name="distrito_actual" 
                           maxlength="50"
                           pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-]{2,50}$"
                           title="Solo letras, espacios y guiones (2-50 caracteres)">
                    <div class="invalid-feedback">
                      Solo letras, espacios y guiones (2-50 caracteres)
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Fila Completa: Situación Laboral y Académica -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-briefcase me-1"></i>
                    Situación Actual (Laboral y Académica)
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    
                    <!-- Ocupación Actual -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="edit_ocupacion_actual" class="form-label">
                          Ocupación/Profesión Actual
                        </label>
                        <input type="text" class="form-control" id="edit_ocupacion_actual" name="ocupacion_actual" 
                               maxlength="100"
                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-\.]{2,100}$"
                               title="Solo letras, espacios, guiones y puntos (2-100 caracteres)">
                        <div class="invalid-feedback">
                          Solo letras, espacios, guiones y puntos (2-100 caracteres)
                        </div>
                      </div>
                    </div>

                    <!-- Empresa Actual -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="edit_empresa_actual" class="form-label">
                          Empresa/Institución donde labora
                        </label>
                        <input type="text" class="form-control" id="edit_empresa_actual" name="empresa_actual" 
                               maxlength="100"
                               title="Nombre de la empresa o institución donde labora">
                      </div>
                    </div>

                  </div>

                  <!-- Estudios Superiores -->
                  <div class="mb-3">
                    <label for="edit_estudios_superiores" class="form-label">
                      Estudios Superiores (Universidad/Instituto y Carrera)
                    </label>
                    <input type="text" class="form-control" id="edit_estudios_superiores" name="estudios_superiores" 
                           maxlength="150"
                           title="Universidad/Instituto y carrera estudiada">
                    <div class="form-text">Ejemplo: Universidad Nacional Mayor de San Marcos - Medicina</div>
                  </div>

                  <!-- Observaciones -->
                  <div class="mb-3">
                    <label for="edit_observaciones" class="form-label">
                      Observaciones y Notas Adicionales
                    </label>
                    <textarea class="form-control" id="edit_observaciones" name="observaciones" 
                              rows="3" maxlength="500"
                              title="Información adicional relevante"></textarea>
                    <div class="form-text">Máximo 500 caracteres</div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Información de Auditoría -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                  <h6 class="mb-0">
                    <i class="ti ti-clock me-1"></i>
                    Información de Registro
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3">
                      <small class="text-muted">Promoción:</small>
                      <div class="fw-bold" id="edit_promocion_info">-</div>
                    </div>
                    <div class="col-md-3">
                      <small class="text-muted">Último Grado:</small>
                      <div class="fw-bold" id="edit_grado_info">-</div>
                    </div>
                    <div class="col-md-3">
                      <small class="text-muted">Fecha Registro:</small>
                      <div class="fw-bold" id="edit_fecha_registro">-</div>
                    </div>
                    <div class="col-md-3">
                      <small class="text-muted">Última Actualización:</small>
                      <div class="fw-bold" id="edit_fecha_actualizacion">-</div>
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
          <button type="button" class="btn btn-info" onclick="validarContacto()">
            <i class="ti ti-phone-check me-1"></i>
            Validar Contacto
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-device-floppy me-1"></i>
            Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
                // Manejar click en botón editar egresado
            $(document).on('click', '.btn-editar-egresado', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                  title: 'Editar Egresado',
                  text: '¿Desea editar la información de ' + nombre + '?',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonColor: '#28a745',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, editar',
                  cancelButtonText: 'Cancelar'
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Cargar datos del egresado en el modal
                    cargarDatosEgresado(id);
                    $('#modalEditarEgresado').modal('show');
                  }
                });
            });


    const editForm = document.getElementById('formEditarEgresado');
    const editTipoDocumento = document.getElementById('edit_tipo_documento');
    const editNumeroDocumento = document.getElementById('edit_numero_documento');
    const editDocumentoFeedback = document.getElementById('edit-documento-feedback');

    // Validación dinámica del número de documento en edición
    editTipoDocumento.addEventListener('change', function() {
        const tipo = this.value;
        
        switch(tipo) {
            case 'DNI':
                editNumeroDocumento.pattern = '^[0-9]{8}$';
                editNumeroDocumento.maxLength = 8;
                editNumeroDocumento.placeholder = '12345678';
                editNumeroDocumento.title = 'DNI debe tener exactamente 8 dígitos';
                editDocumentoFeedback.textContent = 'DNI debe tener exactamente 8 dígitos';
                break;
            case 'CE':
                editNumeroDocumento.pattern = '^[0-9]{9}$';
                editNumeroDocumento.maxLength = 9;
                editNumeroDocumento.placeholder = '123456789';
                editNumeroDocumento.title = 'Carnet de Extranjería debe tener exactamente 9 dígitos';
                editDocumentoFeedback.textContent = 'Carnet de Extranjería debe tener exactamente 9 dígitos';
                break;
            case 'pasaporte':
                editNumeroDocumento.pattern = '^[A-Z0-9]{6,12}$';
                editNumeroDocumento.maxLength = 12;
                editNumeroDocumento.placeholder = 'ABC123456';
                editNumeroDocumento.title = 'Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos';
                editDocumentoFeedback.textContent = 'Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos';
                break;
            default:
                editNumeroDocumento.pattern = '';
                editNumeroDocumento.placeholder = '';
                editNumeroDocumento.title = '';
        }
        
        editNumeroDocumento.classList.remove('is-valid', 'is-invalid');
    });

    // Validación en tiempo real del número de documento
    editNumeroDocumento.addEventListener('input', function() {
        const tipo = editTipoDocumento.value;
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
                this.value = valor.toUpperCase();
                break;
        }
        
        if (valor && !esValido) {
            this.setCustomValidity('Formato de documento inválido');
        } else {
            this.setCustomValidity('');
        }
    });

    // Validación de email en tiempo real
    document.getElementById('edit_email').addEventListener('input', function() {
        const emailStatus = document.getElementById('emailStatus');
        const email = this.value;
        
        if (email && this.checkValidity()) {
            emailStatus.textContent = '✓ Email válido';
            emailStatus.className = 'form-text text-success';
        } else if (email) {
            emailStatus.textContent = '✗ Formato de email inválido';
            emailStatus.className = 'form-text text-danger';
        } else {
            emailStatus.textContent = '';
        }
    });

    // Validación de teléfono en tiempo real
    document.getElementById('edit_telefono').addEventListener('input', function() {
        const telefonoStatus = document.getElementById('telefonoStatus');
        const telefono = this.value;
        
        if (telefono && this.checkValidity()) {
            telefonoStatus.textContent = '✓ Teléfono válido';
            telefonoStatus.className = 'form-text text-success';
        } else if (telefono) {
            telefonoStatus.textContent = '✗ Formato de teléfono inválido';
            telefonoStatus.className = 'form-text text-danger';
        } else {
            telefonoStatus.textContent = '';
        }
    });

    // Formatear teléfonos automáticamente
    function formatearTelefonoEdit(input) {
        input.addEventListener('input', function() {
            let valor = this.value.replace(/\D/g, ''); // Solo números
            
            if (valor.startsWith('51') && valor.length === 11) {
                valor = '+' + valor;
            } else if (valor.length === 9 && !valor.startsWith('51')) {
                valor = '+51' + valor;
            }
            
            this.value = valor;
        });
    }
    
    formatearTelefonoEdit(document.getElementById('edit_telefono'));
    formatearTelefonoEdit(document.getElementById('edit_whatsapp'));

    // Validación del formulario de edición
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!editForm.checkValidity()) {
            e.stopPropagation();
            editForm.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor corrija los errores marcados antes de continuar',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }
        
        // Confirmación antes de guardar cambios
        Swal.fire({
            title: '¿Guardar cambios?',
            text: 'Se actualizará la información del egresado',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                editForm.submit();
            }
        });
    });

    // Actualizar el JavaScript en registro_egresados.php
// Reemplazar la función cargarDatosEgresado existente

// Función para cargar datos del egresado mediante AJAX
// Función para cargar datos del egresado mediante AJAX - VERSIÓN CORREGIDA
window.cargarDatosEgresado = function(egresadoId) {
    // Mostrar loading
    Swal.fire({
        title: 'Cargando datos...',
        html: 'Obteniendo información del egresado',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Realizar petición AJAX con manejo de errores mejorado
    fetch('acciones/registro_egresados/obtener_egresado.php?id=' + encodeURIComponent(egresadoId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Verificar si la respuesta es OK
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
        }
        
        // Verificar si el content-type es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Response is not JSON:', contentType);
            
            // Intentar obtener el texto para debugging
            return response.text().then(text => {
                console.error('Response text:', text);
                throw new Error('La respuesta del servidor no es JSON válido. Content-Type: ' + contentType);
            });
        }
        
        return response.json();
    })
    .then(data => {
        Swal.close();
        
        console.log('Data received:', data);
        
        // Verificar estructura de la respuesta
        if (typeof data !== 'object' || data === null) {
            throw new Error('Respuesta no es un objeto válido');
        }
        
        if (data.success) {
            // Verificar que existan los datos
            if (!data.data) {
                throw new Error('No se recibieron datos del egresado');
            }
            
            // Llenar el formulario con los datos obtenidos
            llenarFormularioEdicion(data.data);
            
            // Mostrar el modal
            $('#modalEditarEgresado').modal('show');
            
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudieron obtener los datos del egresado',
                icon: 'error',
                confirmButtonText: 'Entendido',
                footer: data.debug ? `<small>Debug: ${data.debug}</small>` : ''
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error completo:', error);
        
        let errorMessage = 'Error desconocido';
        let debugInfo = '';
        
        if (error.message) {
            errorMessage = error.message;
        }
        
        if (error.name === 'SyntaxError' && error.message.includes('JSON')) {
            errorMessage = 'El servidor devolvió una respuesta inválida (no JSON)';
            debugInfo = 'Posible error PHP o contenido HTML inesperado';
        } else if (error.message.includes('HTTP Error')) {
            errorMessage = 'Error del servidor: ' + error.message;
        } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
            errorMessage = 'Error de conexión de red';
            debugInfo = 'Verifique su conexión a internet';
        }
        
        Swal.fire({
            title: 'Error de Conexión',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Entendido',
            showCancelButton: true,
            cancelButtonText: 'Reintentar',
            footer: debugInfo ? `<small>${debugInfo}</small>` : '',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                // Reintentar la operación
                setTimeout(() => {
                    cargarDatosEgresado(egresadoId);
                }, 500);
            }
        });
    });
};

// Función auxiliar para verificar si una cadena es JSON válido
function esJSONValido(str) {
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
}

// Función de debugging para el desarrollo (solo en desarrollo)
function debugRespuesta(url) {
    console.log('Debugging URL:', url);
    
    fetch(url)
    .then(response => response.text())
    .then(text => {
        console.log('Raw response:', text);
        console.log('Is JSON?', esJSONValido(text));
        
        if (!esJSONValido(text)) {
            console.log('First 500 chars:', text.substring(0, 500));
            console.log('Last 500 chars:', text.substring(Math.max(0, text.length - 500)));
        }
    })
    .catch(error => {
        console.error('Debug error:', error);
    });
}

// Función para llenar el formulario de edición con los datos - MEJORADA
function llenarFormularioEdicion(datos) {
    try {
        console.log('Llenando formulario con datos:', datos);
        
        // Función auxiliar para establecer valor de forma segura
        function establecerValor(elementId, valor) {
            const elemento = document.getElementById(elementId);
            if (elemento) {
                elemento.value = valor || '';
            } else {
                console.warn(`Elemento no encontrado: ${elementId}`);
            }
        }
        
        // Función auxiliar para establecer texto de forma segura
        function establecerTexto(elementId, texto) {
            const elemento = document.getElementById(elementId);
            if (elemento) {
                elemento.textContent = texto || '';
            } else {
                console.warn(`Elemento no encontrado: ${elementId}`);
            }
        }
        
        // Información básica del egresado
        establecerValor('edit_egresado_id', datos.id);
        establecerValor('edit_codigo_exalumno', datos.codigo_exalumno);
        establecerTexto('nombreEgresadoEditar', datos.nombre_completo);
        
        // Datos personales
        establecerValor('edit_tipo_documento', datos.tipo_documento);
        establecerValor('edit_numero_documento', datos.numero_documento);
        establecerValor('edit_nombres', datos.nombres);
        establecerValor('edit_apellidos', datos.apellidos);
        establecerValor('edit_estado_contacto', datos.estado_contacto);
        
        // Checkbox para comunicaciones
        const checkboxComunicaciones = document.getElementById('edit_acepta_comunicaciones');
        if (checkboxComunicaciones) {
            checkboxComunicaciones.checked = datos.acepta_comunicaciones == 1;
        }
        
        // Datos de contacto
        establecerValor('edit_email', datos.email);
        establecerValor('edit_telefono', datos.telefono);
        establecerValor('edit_whatsapp', datos.whatsapp);
        establecerValor('edit_direccion_actual', datos.direccion_actual);
        establecerValor('edit_distrito_actual', datos.distrito_actual);
        
        // Situación actual
        establecerValor('edit_ocupacion_actual', datos.ocupacion_actual);
        establecerValor('edit_empresa_actual', datos.empresa_actual);
        establecerValor('edit_estudios_superiores', datos.estudios_superiores);
        establecerValor('edit_observaciones', datos.observaciones);
        
        // Información de auditoría
        establecerTexto('edit_promocion_info', datos.promocion_display);
        establecerTexto('edit_grado_info', datos.grado_display);
        establecerTexto('edit_fecha_registro', datos.fecha_registro_formateada);
        establecerTexto('edit_fecha_actualizacion', datos.fecha_actualizacion_formateada);
        
        // Disparar evento change en tipo de documento para validaciones
        const tipoDoc = document.getElementById('edit_tipo_documento');
        if (tipoDoc) {
            tipoDoc.dispatchEvent(new Event('change'));
        }
        
        // Validar contactos en tiempo real si la función existe
        if (typeof validarContactosEnTiempoReal === 'function') {
            validarContactosEnTiempoReal(datos);
        }
        
        // Mostrar alertas de validación si la función existe
        if (typeof mostrarAlertasValidacion === 'function') {
            mostrarAlertasValidacion(datos);
        }
        
        console.log('Datos del egresado cargados correctamente');
        
    } catch (error) {
        console.error('Error al llenar el formulario:', error);
        
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema al cargar los datos en el formulario',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    }
}
// Función para validar contactos en tiempo real
function validarContactosEnTiempoReal(datos) {
    const emailField = document.getElementById('edit_email');
    const telefonoField = document.getElementById('edit_telefono');
    const emailStatus = document.getElementById('emailStatus');
    const telefonoStatus = document.getElementById('telefonoStatus');
    
    // Validar email
    if (datos.email) {
        if (datos.contacto_valido.email) {
            emailStatus.textContent = '✓ Email válido';
            emailStatus.className = 'form-text text-success';
            emailField.classList.add('is-valid');
            emailField.classList.remove('is-invalid');
        } else {
            emailStatus.textContent = '⚠ Email con formato inválido';
            emailStatus.className = 'form-text text-warning';
            emailField.classList.add('is-invalid');
            emailField.classList.remove('is-valid');
        }
    } else {
        emailStatus.textContent = '';
        emailField.classList.remove('is-valid', 'is-invalid');
    }
    
    // Validar teléfono
    if (datos.telefono) {
        if (datos.contacto_valido.telefono) {
            telefonoStatus.textContent = '✓ Teléfono válido';
            telefonoStatus.className = 'form-text text-success';
            telefonoField.classList.add('is-valid');
            telefonoField.classList.remove('is-invalid');
        } else {
            telefonoStatus.textContent = '⚠ Teléfono con formato inválido';
            telefonoStatus.className = 'form-text text-warning';
            telefonoField.classList.add('is-invalid');
            telefonoField.classList.remove('is-valid');
        }
    } else {
        telefonoStatus.textContent = '';
        telefonoField.classList.remove('is-valid', 'is-invalid');
    }
}

// Función para mostrar alertas de validación
function mostrarAlertasValidacion(datos) {
    const alertas = [];
    
    // Verificar datos de contacto
    if (!datos.medios_contacto.email && !datos.medios_contacto.telefono && !datos.medios_contacto.whatsapp) {
        alertas.push('⚠️ Sin medios de contacto registrados');
    }
    
    // Verificar completitud del perfil
    if (!datos.estadisticas.perfil_completo) {
        const faltantes = [];
        if (!datos.email) faltantes.push('email');
        if (!datos.telefono) faltantes.push('teléfono');
        if (!datos.ocupacion_actual) faltantes.push('ocupación');
        if (!datos.direccion_actual) faltantes.push('dirección');
        
        if (faltantes.length > 0) {
            alertas.push(`ℹ️ Perfil incompleto: falta ${faltantes.join(', ')}`);
        }
    }
    
    // Verificar estado vs comunicaciones
    if (datos.estado_contacto !== 'activo' && datos.acepta_comunicaciones) {
        alertas.push('⚠️ Estado inactivo pero acepta comunicaciones');
    }
    
    // Mostrar alertas si las hay
    if (alertas.length > 0) {
        // Crear elemento de alerta en el modal
        const alertaExistente = document.getElementById('alertaValidacionEditar');
        if (alertaExistente) {
            alertaExistente.remove();
        }
        
        const alertaHTML = `
            <div id="alertaValidacionEditar" class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Advertencias:</strong><br>
                ${alertas.join('<br>')}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insertar después del elemento de información del egresado
        const infoEgresado = document.getElementById('infoEgresadoEditar');
        infoEgresado.insertAdjacentHTML('afterend', alertaHTML);
    }
}

// Función mejorada para validar contacto (actualizar la existente)
window.validarContacto = function() {
    const email = document.getElementById('edit_email').value;
    const telefono = document.getElementById('edit_telefono').value;
    const whatsapp = document.getElementById('edit_whatsapp').value;
    
    let mensajes = [];
    let tieneContactoValido = false;
    
    // Verificar si tiene al menos un medio de contacto
    if (!email && !telefono && !whatsapp) {
        mensajes.push('❌ No tiene ningún medio de contacto registrado');
    }
    
    // Validar email
    if (email) {
        const emailValido = document.getElementById('edit_email').checkValidity();
        if (emailValido) {
            mensajes.push('✅ Email válido: ' + email);
            tieneContactoValido = true;
        } else {
            mensajes.push('❌ Email inválido: ' + email);
        }
    }
    
    // Validar teléfono
    if (telefono) {
        const telefonoValido = document.getElementById('edit_telefono').checkValidity();
        if (telefonoValido) {
            mensajes.push('✅ Teléfono válido: ' + telefono);
            tieneContactoValido = true;
        } else {
            mensajes.push('❌ Teléfono inválido: ' + telefono);
        }
    }
    
    // Validar WhatsApp
    if (whatsapp) {
        const whatsappValido = document.getElementById('edit_whatsapp').checkValidity();
        if (whatsappValido) {
            mensajes.push('✅ WhatsApp válido: ' + whatsapp);
            tieneContactoValido = true;
        } else {
            mensajes.push('❌ WhatsApp inválido: ' + whatsapp);
        }
    }
    
    // Recomendaciones
    if (tieneContactoValido) {
        mensajes.push('');
        mensajes.push('📞 <strong>Recomendación:</strong> El egresado tiene medios de contacto válidos');
        
        if (document.getElementById('edit_estado_contacto').value === 'sin_contacto') {
            mensajes.push('💡 <strong>Sugerencia:</strong> Considere cambiar el estado a "Activo" si logra contactarlo');
        }
    } else {
        mensajes.push('');
        mensajes.push('⚠️ <strong>Atención:</strong> No hay medios de contacto válidos');
        mensajes.push('💡 <strong>Sugerencia:</strong> Investigue nuevos datos de contacto o marque como "Sin contacto"');
    }
    
    // Determinar ícono del resultado
    let icono = 'info';
    if (!tieneContactoValido) {
        icono = 'warning';
    } else if (mensajes.some(m => m.includes('❌'))) {
        icono = 'warning';
    } else {
        icono = 'success';
    }
    
    Swal.fire({
        title: 'Validación de Contacto',
        html: mensajes.join('<br>'),
        icon: icono,
        confirmButtonText: 'Entendido',
        width: '600px'
    });
};

// Función auxiliar para manejar errores de carga
function manejarErrorCarga(error, contexto = '') {
    console.error('Error en ' + contexto + ':', error);
    
    Swal.fire({
        title: 'Error de Carga',
        text: 'No se pudieron cargar los datos. Verifique su conexión e intente nuevamente.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Reintentar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar lógica de reintento si es necesario
            console.log('Reintentando carga...');
        }
    });
}

// Función para limpiar formulario cuando se cierra el modal (actualizar)
document.getElementById('modalEditarEgresado').addEventListener('hidden.bs.modal', function() {
    const editForm = document.getElementById('formEditarEgresado');
    editForm.reset();
    editForm.classList.remove('was-validated');
    
    // Limpiar estados de validación
    document.getElementById('emailStatus').textContent = '';
    document.getElementById('telefonoStatus').textContent = '';
    
    // Limpiar clases de validación
    const inputs = editForm.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.classList.remove('is-valid', 'is-invalid');
    });
    
    // Remover alertas de validación
    const alertaValidacion = document.getElementById('alertaValidacionEditar');
    if (alertaValidacion) {
        alertaValidacion.remove();
    }
    
    console.log('Formulario de edición limpiado');
});
    // Función para validar información de contacto
    window.validarContacto = function() {
        const email = document.getElementById('edit_email').value;
        const telefono = document.getElementById('edit_telefono').value;
        const whatsapp = document.getElementById('edit_whatsapp').value;
        
        let mensajes = [];
        
        if (!email && !telefono && !whatsapp) {
            mensajes.push('⚠️ No tiene ningún medio de contacto registrado');
        }
        
        if (email && !document.getElementById('edit_email').checkValidity()) {
            mensajes.push('❌ El email tiene formato inválido');
        } else if (email) {
            mensajes.push('✅ Email válido');
        }
        
        if (telefono && !document.getElementById('edit_telefono').checkValidity()) {
            mensajes.push('❌ El teléfono tiene formato inválido');
        } else if (telefono) {
            mensajes.push('✅ Teléfono válido');
        }
        
        if (whatsapp && !document.getElementById('edit_whatsapp').checkValidity()) {
            mensajes.push('❌ El WhatsApp tiene formato inválido');
        } else if (whatsapp) {
            mensajes.push('✅ WhatsApp válido');
        }
        
        Swal.fire({
            title: 'Validación de Contacto',
            html: mensajes.join('<br>'),
            icon: mensajes.some(m => m.includes('❌')) ? 'warning' : 'success',
            confirmButtonText: 'Entendido'
        });
    };

    // Función auxiliar para formatear fechas
    function formatearFecha(fechaISO) {
        if (!fechaISO) return 'No disponible';
        const fecha = new Date(fechaISO);
        return fecha.toLocaleDateString('es-PE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Limpiar formulario cuando se cierra el modal
    document.getElementById('modalEditarEgresado').addEventListener('hidden.bs.modal', function() {
        editForm.reset();
        editForm.classList.remove('was-validated');
        document.getElementById('emailStatus').textContent = '';
        document.getElementById('telefonoStatus').textContent = '';
    });
});
</script>