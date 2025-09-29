<!-- Modal Editar Código de Referido -->
<div class="modal fade" id="modalEditarCodigo" tabindex="-1" aria-labelledby="modalEditarCodigoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalEditarCodigoLabel">
          <i class="ti ti-edit me-2"></i>
          Editar Código de Recomendación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formEditarCodigo" method="POST" action="acciones/codigos_referido/gestionar_codigo.php" novalidate>
        <input type="hidden" name="accion" value="editar_codigo">
        <input type="hidden" name="codigo_id" id="edit_codigo_id">
        <input type="hidden" name="usos_actuales" id="edit_usos_actuales">
        
        <div class="modal-body">
          
          <!-- Alert de Información del Código -->
          <div class="alert alert-info" role="alert" id="infoCodigoEditar">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Editando:</strong> <span id="nombreCodigoEditar">-</span>
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
                  
                  <!-- Código (Solo lectura) -->
                  <div class="mb-3">
                    <label for="edit_codigo" class="form-label">
                      Código
                    </label>
                    <input type="text" class="form-control" id="edit_codigo" 
                           readonly style="background-color: #f8f9fa; font-weight: bold; font-size: 1.1rem;">
                    <small class="form-text text-muted">
                      El código no se puede modificar una vez creado
                    </small>
                  </div>

                  <!-- Tipo de Código (Solo lectura) -->
                  <div class="mb-3">
                    <label class="form-label">Tipo de Código</label>
                    <div class="form-control" style="background-color: #f8f9fa;" id="edit_tipo_display">
                      -
                    </div>
                  </div>

                  <!-- Propietario (Solo lectura si ya tiene) -->
                  <div class="mb-3" id="edit_div_propietario">
                    <label class="form-label">Propietario</label>
                    <div class="form-control" style="background-color: #f8f9fa;" id="edit_propietario_display">
                      -
                    </div>
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label for="edit_descripcion" class="form-label">
                      Descripción
                    </label>
                    <textarea class="form-control" id="edit_descripcion" name="descripcion" 
                              rows="2" maxlength="200"
                              minlength="10"></textarea>
                    <div class="invalid-feedback">
                      La descripción debe tener al menos 10 caracteres
                    </div>
                    <div class="form-text">
                      <span id="edit_descripcion_count">0</span>/200 caracteres
                    </div>
                  </div>

                  <!-- Estadísticas de Uso -->
                  <div class="mb-3">
                    <label class="form-label">Estadísticas de Uso</label>
                    <div class="card border-info">
                      <div class="card-body p-2">
                        <div class="row text-center">
                          <div class="col-6">
                            <small class="text-muted">Usos Actuales</small>
                            <div class="h4 mb-0" id="edit_usos_display">0</div>
                          </div>
                          <div class="col-6">
                            <small class="text-muted">Disponibles</small>
                            <div class="h4 mb-0" id="edit_disponibles_display">∞</div>
                          </div>
                        </div>
                      </div>
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
                    <label for="edit_beneficio_referente" class="form-label">
                      Beneficio para el Referente <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="edit_beneficio_referente" name="beneficio_referente" 
                              rows="2" maxlength="500"
                              minlength="10"
                              required></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referente (10-500 caracteres)
                    </div>
                    <div class="form-text">
                      <span id="edit_beneficio_referente_count">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Beneficio para el Referido -->
                  <div class="mb-3">
                    <label for="edit_beneficio_referido" class="form-label">
                      Beneficio para el Referido <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="edit_beneficio_referido" name="beneficio_referido" 
                              rows="2" maxlength="500"
                              minlength="10"
                              required></textarea>
                    <div class="invalid-feedback">
                      Debe especificar el beneficio para el referido (10-500 caracteres)
                    </div>
                    <div class="form-text">
                      <span id="edit_beneficio_referido_count">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Límite de Usos -->
                  <div class="mb-3">
                    <label for="edit_limite_usos" class="form-label">
                      Límite de Usos
                      <i class="ti ti-help-circle" data-bs-toggle="tooltip" 
                         title="El límite debe ser mayor o igual a los usos actuales"></i>
                    </label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="edit_limite_usos" name="limite_usos" 
                             min="1" max="1000" step="1">
                      <span class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" id="edit_usos_ilimitados">
                        <label class="ms-1" for="edit_usos_ilimitados">Ilimitado</label>
                      </span>
                    </div>
                    <div class="invalid-feedback" id="edit_limite_feedback">
                      El límite debe ser mayor o igual a los usos actuales
                    </div>
                    <small class="form-text text-warning" id="edit_limite_warning"></small>
                  </div>

                  <!-- Fecha de Inicio -->
                  <div class="mb-3">
                    <label for="edit_fecha_inicio" class="form-label">
                      Fecha de Inicio <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="edit_fecha_inicio" name="fecha_inicio" 
                           required>
                    <div class="invalid-feedback">
                      Fecha de inicio requerida
                    </div>
                  </div>

                  <!-- Fecha de Fin -->
                  <div class="mb-3">
                    <label for="edit_fecha_fin" class="form-label">
                      Fecha de Fin
                    </label>
                    <div class="input-group">
                      <input type="date" class="form-control" id="edit_fecha_fin" name="fecha_fin">
                      <span class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" id="edit_sin_limite">
                        <label class="ms-1" for="edit_sin_limite">Sin límite</label>
                      </span>
                    </div>
                    <div class="invalid-feedback">
                      La fecha de fin debe ser posterior a la fecha de inicio
                    </div>
                    <small class="form-text text-muted" id="edit_duracion_codigo"></small>
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
                    <div class="col-md-4">
                      <small class="text-muted">Fecha Creación:</small>
                      <div class="fw-bold" id="edit_fecha_creacion">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Última Actualización:</small>
                      <div class="fw-bold" id="edit_fecha_actualizacion">-</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted">Estado:</small>
                      <div class="fw-bold" id="edit_estado_codigo">-</div>
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
    const formEditar = document.getElementById('formEditarCodigo');
    const descripcionTextarea = document.getElementById('edit_descripcion');
    const beneficioReferenteTextarea = document.getElementById('edit_beneficio_referente');
    const beneficioReferidoTextarea = document.getElementById('edit_beneficio_referido');
    const limiteUsosInput = document.getElementById('edit_limite_usos');
    const usosIlimitadosCheck = document.getElementById('edit_usos_ilimitados');
    const fechaInicioInput = document.getElementById('edit_fecha_inicio');
    const fechaFinInput = document.getElementById('edit_fecha_fin');
    const sinLimiteCheck = document.getElementById('edit_sin_limite');

    // Función para cargar datos del código
    window.cargarDatosCodigo = function(codigoId) {
        Swal.fire({
            title: 'Cargando datos...',
            html: 'Obteniendo información del código',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('acciones/codigos_referido/obtener_codigo.php?id=' + encodeURIComponent(codigoId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            Swal.close();
            
            if (data.success) {
                llenarFormularioEdicion(data.data);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'No se pudieron obtener los datos del código',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error completo:', error);
            
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los datos del código',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
    };

    // Llenar formulario con datos
    function llenarFormularioEdicion(datos) {
        // ID y código
        document.getElementById('edit_codigo_id').value = datos.id;
        document.getElementById('edit_codigo').value = datos.codigo;
        document.getElementById('nombreCodigoEditar').textContent = datos.codigo;
        
        // Tipo y propietario
        const tipo = datos.apoderado_id ? 'Personal' : 'General';
        document.getElementById('edit_tipo_display').textContent = tipo;
        document.getElementById('edit_propietario_display').textContent = datos.propietario || 'Código General';
        
        // Descripción
        descripcionTextarea.value = datos.descripcion || '';
        document.getElementById('edit_descripcion_count').textContent = 
            (datos.descripcion || '').length;
        
        // Beneficios
        beneficioReferenteTextarea.value = datos.beneficio_referente || '';
        document.getElementById('edit_beneficio_referente_count').textContent = 
            (datos.beneficio_referente || '').length;
        
        beneficioReferidoTextarea.value = datos.beneficio_referido || '';
        document.getElementById('edit_beneficio_referido_count').textContent = 
            (datos.beneficio_referido || '').length;
        
        // Usos
        document.getElementById('edit_usos_actuales').value = datos.usos_actuales;
        document.getElementById('edit_usos_display').textContent = datos.usos_actuales;
        
        if (datos.limite_usos) {
            limiteUsosInput.value = datos.limite_usos;
            limiteUsosInput.min = datos.usos_actuales;
            usosIlimitadosCheck.checked = false;
            limiteUsosInput.disabled = false;
            document.getElementById('edit_disponibles_display').textContent = 
                datos.limite_usos - datos.usos_actuales;
        } else {
            limiteUsosInput.value = '';
            usosIlimitadosCheck.checked = true;
            limiteUsosInput.disabled = true;
            document.getElementById('edit_disponibles_display').textContent = '∞';
        }
        
        // Fechas
        fechaInicioInput.value = datos.fecha_inicio;
        
        if (datos.fecha_fin) {
            fechaFinInput.value = datos.fecha_fin;
            sinLimiteCheck.checked = false;
            fechaFinInput.disabled = false;
        } else {
            fechaFinInput.value = '';
            sinLimiteCheck.checked = true;
            fechaFinInput.disabled = true;
        }
        
        // Auditoría
        document.getElementById('edit_fecha_creacion').textContent = datos.created_at_formato;
        document.getElementById('edit_fecha_actualizacion').textContent = datos.updated_at_formato;
        document.getElementById('edit_estado_codigo').textContent = datos.estado_display;
        
        calcularDuracionEdit();
    }

    // Contador de caracteres
    descripcionTextarea.addEventListener('input', function() {
        document.getElementById('edit_descripcion_count').textContent = this.value.length;
    });

    beneficioReferenteTextarea.addEventListener('input', function() {
        document.getElementById('edit_beneficio_referente_count').textContent = this.value.length;
    });

    beneficioReferidoTextarea.addEventListener('input', function() {
        document.getElementById('edit_beneficio_referido_count').textContent = this.value.length;
    });

    // Manejar límite de usos
    limiteUsosInput.addEventListener('input', function() {
        const usosActuales = parseInt(document.getElementById('edit_usos_actuales').value);
        const limiteNuevo = parseInt(this.value);
        
        if (limiteNuevo < usosActuales) {
            this.setCustomValidity('El límite debe ser mayor o igual a los usos actuales');
            document.getElementById('edit_limite_feedback').textContent = 
                `El límite debe ser al menos ${usosActuales} (usos actuales)`;
        } else {
            this.setCustomValidity('');
            document.getElementById('edit_limite_feedback').textContent = 
                'El límite debe ser mayor o igual a los usos actuales';
        }
        
        if (limiteNuevo > usosActuales) {
            const disponibles = limiteNuevo - usosActuales;
            document.getElementById('edit_limite_warning').textContent = 
                `✓ Quedarán ${disponibles} usos disponibles`;
        } else {
            document.getElementById('edit_limite_warning').textContent = '';
        }
    });

    usosIlimitadosCheck.addEventListener('change', function() {
        if (this.checked) {
            limiteUsosInput.value = '';
            limiteUsosInput.disabled = true;
            limiteUsosInput.removeAttribute('required');
        } else {
            limiteUsosInput.disabled = false;
            const usosActuales = parseInt(document.getElementById('edit_usos_actuales').value);
            limiteUsosInput.min = usosActuales;
        }
    });

    // Validar fechas
    fechaInicioInput.addEventListener('change', function() {
        const fechaInicio = new Date(this.value);
        const fechaMinFin = new Date(fechaInicio);
        fechaMinFin.setDate(fechaMinFin.getDate() + 1);
        fechaFinInput.min = fechaMinFin.toISOString().split('T')[0];
        
        if (fechaFinInput.value && new Date(fechaFinInput.value) <= fechaInicio) {
            fechaFinInput.value = '';
        }
        
        calcularDuracionEdit();
    });

    fechaFinInput.addEventListener('change', calcularDuracionEdit);

    sinLimiteCheck.addEventListener('change', function() {
        if (this.checked) {
            fechaFinInput.value = '';
            fechaFinInput.disabled = true;
        } else {
            fechaFinInput.disabled = false;
        }
        calcularDuracionEdit();
    });

    function calcularDuracionEdit() {
        const inicio = fechaInicioInput.value;
        const fin = fechaFinInput.value;
        const duracionDiv = document.getElementById('edit_duracion_codigo');
        
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

    // Validación del formulario
    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formEditar.checkValidity()) {
            e.stopPropagation();
            formEditar.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor corrija los errores marcados antes de continuar',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }

        // Validaciones adicionales
        const usosActuales = parseInt(document.getElementById('edit_usos_actuales').value);
        const limiteNuevo = limiteUsosInput.value ? parseInt(limiteUsosInput.value) : null;
        
        if (limiteNuevo && limiteNuevo < usosActuales) {
            Swal.fire({
                title: 'Error de Validación',
                text: `El límite de usos (${limiteNuevo}) no puede ser menor a los usos actuales (${usosActuales})`,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

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

        // Confirmación
        Swal.fire({
            title: '¿Guardar cambios?',
            html: `Se actualizará el código <strong>${document.getElementById('edit_codigo').value}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                formEditar.submit();
            }
        });
    });

    // Limpiar al cerrar
    document.getElementById('modalEditarCodigo').addEventListener('hidden.bs.modal', function() {
        formEditar.reset();
        formEditar.classList.remove('was-validated');
    });
});
</script>