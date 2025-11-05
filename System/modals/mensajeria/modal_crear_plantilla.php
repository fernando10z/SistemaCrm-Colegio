<style>
    .swal2-container {
        z-index: 9999999 !important;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>
<!-- Modal Crear/Editar Plantilla -->
<div class="modal fade" id="modalCrearPlantilla" tabindex="-1" aria-labelledby="modalCrearPlantillaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: linear-gradient(135deg, #ffd1dc 0%, #fce4ec 100%); border-radius: 15px 15px 0 0; border-bottom: none;">
        <h5 class="modal-title" id="modalCrearPlantillaLabel" style="color: #6b4e71; font-weight: 600;">
          <i class="ti ti-template me-2"></i><span id="tituloModal">Crear Nueva Plantilla</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formPlantilla" method="POST" action="actions/procesar_plantilla.php">
        <input type="hidden" name="accion" id="accion" value="crear">
        <input type="hidden" name="plantilla_id" id="plantilla_id" value="">
        
        <div class="modal-body" style="background-color: #ffffff; padding: 30px;">
          
          <!-- Información General -->
          <div class="card mb-3" style="border: 1px solid #e8d7f1; border-radius: 10px; background-color: #fef9ff;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #6b4e71; font-weight: 600;">
                <i class="ti ti-info-circle me-1"></i>Información General
              </h6>
              
              <div class="row">
                <div class="col-md-8 mb-3">
                  <label for="nombre" class="form-label" style="color: #6b4e71; font-weight: 500;">
                    Nombre de la Plantilla <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control" id="nombre" name="nombre" required 
                         placeholder="Ej: Recordatorio de Cumpleaños"
                         style="border: 1px solid #d4c5e0; border-radius: 8px; padding: 10px;">
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="tipo" class="form-label" style="color: #6b4e71; font-weight: 500;">
                    Canal <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="tipo" name="tipo" required
                          style="border: 1px solid #d4c5e0; border-radius: 8px; padding: 10px;">
                    <option value="">Seleccionar...</option>
                    <option value="email">📧 Email</option>
                    <option value="whatsapp">💬 WhatsApp</option>
                    <option value="sms">📱 SMS</option>
                  </select>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-8 mb-3">
                  <label for="categoria" class="form-label" style="color: #6b4e71; font-weight: 500;">
                    Categoría
                  </label>
                  <select class="form-select" id="categoria" name="categoria"
                          style="border: 1px solid #d4c5e0; border-radius: 8px; padding: 10px;">
                    <option value="general">General</option>
                    <option value="cumpleanos">Cumpleaños</option>
                    <option value="evento">Evento</option>
                    <option value="recordatorio">Recordatorio</option>
                    <option value="bienvenida">Bienvenida</option>
                    <option value="leads">Leads</option>
                  </select>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="activo" class="form-label" style="color: #6b4e71; font-weight: 500;">
                    Estado
                  </label>
                  <select class="form-select" id="activo" name="activo"
                          style="border: 1px solid #d4c5e0; border-radius: 8px; padding: 10px;">
                    <option value="1">✅ Activa</option>
                    <option value="0">⏸️ Inactiva</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3" id="asuntoContainer">
                <label for="asunto" class="form-label" style="color: #6b4e71; font-weight: 500;">
                  Asunto (Solo para Email)
                </label>
                <input type="text" class="form-control" id="asunto" name="asunto" 
                       placeholder="Ej: ¡Feliz Cumpleaños {{nombre_estudiante}}!"
                       style="border: 1px solid #d4c5e0; border-radius: 8px; padding: 10px;">
              </div>
            </div>
          </div>
          
          <!-- Contenido del Mensaje -->
          <div class="card mb-3" style="border: 1px solid #d4e7f7; border-radius: 10px; background-color: #f7fcff;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #4a7ba7; font-weight: 600;">
                <i class="ti ti-message me-1"></i>Contenido del Mensaje
              </h6>
              
              <div class="mb-3">
                <label for="contenido" class="form-label" style="color: #4a7ba7; font-weight: 500;">
                  Texto del Mensaje <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="contenido" name="contenido" rows="6" required
                          placeholder="Escribe el contenido del mensaje. Usa variables como {{nombre_estudiante}}, {{nombre_apoderado}}, etc."
                          style="border: 1px solid #b8d9ed; border-radius: 8px; padding: 15px; font-family: 'Courier New', monospace;"></textarea>
                <small class="text-muted">💡 Puedes usar variables personalizadas entre dobles llaves {{ }}</small>
              </div>
              
              <div class="alert alert-info" style="background-color: #e3f2fd; border: 1px solid #90caf9; border-radius: 8px;">
                <strong>Variables disponibles comunes:</strong><br>
                <code>{{nombre_estudiante}}</code> 
                <code>{{nombre_apoderado}}</code> 
                <code>{{grado}}</code> 
                <code>{{fecha_evento}}</code>
                <code>{{nombre_institucion}}</code>
              </div>
            </div>
          </div>
          
          <!-- Variables Personalizadas -->
          <div class="card" style="border: 1px solid #d7e8d4; border-radius: 10px; background-color: #f9fff7;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #5a8f4f; font-weight: 600;">
                <i class="ti ti-variable me-1"></i>Variables Personalizadas (Opcional)
              </h6>
              
              <div id="variablesContainer">
                <div class="input-group mb-2 variable-input">
                  <span class="input-group-text" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #5a8f4f;">
                    <i class="ti ti-tag"></i>
                  </span>
                  <input type="text" class="form-control variable-item" name="variables[]" 
                         placeholder="nombre_variable (sin {{ }})"
                         style="border: 1px solid #c8e6c9; border-radius: 0 8px 8px 0;">
                </div>
              </div>
              
              <button type="button" class="btn btn-sm btn-outline-success" id="addVariable"
                      style="border-radius: 8px; border-color: #81c784;">
                <i class="ti ti-plus"></i> Agregar Variable
              </button>
            </div>
          </div>
          
        </div>
        
        <div class="modal-footer" style="background-color: #fafafa; border-radius: 0 0 15px 15px; border-top: 1px solid #efefef; padding: 20px;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                  style="border-radius: 8px; padding: 10px 20px;">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="btnGuardar"
                  style="border-radius: 8px; padding: 10px 25px; background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); border: none;">
            <i class="ti ti-device-floppy me-1"></i>Guardar Plantilla
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  
  // Mostrar/ocultar campo asunto según el tipo
  const tipoSelect = document.getElementById('tipo');
  const asuntoContainer = document.getElementById('asuntoContainer');
  
  tipoSelect.addEventListener('change', function() {
    if(this.value === 'email') {
      asuntoContainer.style.display = 'block';
      document.getElementById('asunto').required = true;
    } else {
      asuntoContainer.style.display = 'none';
      document.getElementById('asunto').required = false;
      document.getElementById('asunto').value = '';
    }
  });
  
  // Agregar más variables
  document.getElementById('addVariable').addEventListener('click', function() {
    const container = document.getElementById('variablesContainer');
    const newInput = `
      <div class="input-group mb-2 variable-input">
        <span class="input-group-text" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #5a8f4f;">
          <i class="ti ti-tag"></i>
        </span>
        <input type="text" class="form-control variable-item" name="variables[]" 
               placeholder="nombre_variable (sin {{ }})"
               style="border: 1px solid #c8e6c9;">
        <button type="button" class="btn btn-outline-danger btn-sm remove-variable"
                style="border-radius: 0 8px 8px 0;">
          <i class="ti ti-trash"></i>
        </button>
      </div>
    `;
    container.insertAdjacentHTML('beforeend', newInput);
  });
  
  // Eliminar variable
  document.addEventListener('click', function(e) {
    if(e.target.closest('.remove-variable')) {
      e.target.closest('.variable-input').remove();
    }
  });
  
  // Enviar formulario
  document.getElementById('formPlantilla').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Recolectar variables no vacías
    const variables = [];
    document.querySelectorAll('.variable-item').forEach(input => {
      if(input.value.trim() !== '') {
        variables.push(input.value.trim());
      }
    });
    
    // Eliminar inputs de variables y agregar como JSON
    formData.delete('variables[]');
    formData.append('variables_disponibles', JSON.stringify(variables));
    
    const btnGuardar = document.getElementById('btnGuardar');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="ti ti-loader spinning"></i> Guardando...';
    
    fetch('actions/procesar_plantilla.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Plantilla';
      
      if(data.success) {
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: data.message,
          confirmButtonText: 'Aceptar',
          confirmButtonColor: '#a29bfe',
          background: '#fef9ff',
          iconColor: '#81c784',
          customClass: {
            popup: 'swal-pastel',
            confirmButton: 'swal-btn-pastel'
          }
        }).then(() => {
          $('#modalCrearPlantilla').modal('hide');
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message,
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#f48fb1',
          background: '#fff5f8',
          iconColor: '#ef5350',
          customClass: {
            popup: 'swal-pastel',
            confirmButton: 'swal-btn-pastel'
          }
        });
      }
    })
    .catch(error => {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Plantilla';
      
      Swal.fire({
        icon: 'error',
        title: 'Error de Conexión',
        text: 'No se pudo conectar con el servidor. Por favor, intenta nuevamente.',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#f48fb1',
        background: '#fff5f8',
        iconColor: '#ef5350',
        customClass: {
          popup: 'swal-pastel',
          confirmButton: 'swal-btn-pastel'
        }
      });
    });
  });
  
  // Limpiar formulario al cerrar modal
  $('#modalCrearPlantilla').on('hidden.bs.modal', function() {
    document.getElementById('formPlantilla').reset();
    document.getElementById('accion').value = 'crear';
    document.getElementById('plantilla_id').value = '';
    document.getElementById('tituloModal').textContent = 'Crear Nueva Plantilla';
    
    // Mantener solo el primer input de variables
    const container = document.getElementById('variablesContainer');
    container.innerHTML = `
      <div class="input-group mb-2 variable-input">
        <span class="input-group-text" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #5a8f4f;">
          <i class="ti ti-tag"></i>
        </span>
        <input type="text" class="form-control variable-item" name="variables[]" 
               placeholder="nombre_variable (sin {{ }})"
               style="border: 1px solid #c8e6c9; border-radius: 0 8px 8px 0;">
      </div>
    `;
  });
  
});
</script>

<script>
// Función para cargar datos en el formulario de edición
function cargarModalEditarPlantilla(data) {
  // Cambiar título y acción
  $('#tituloModal').text('Editar Plantilla');
  $('#accion').val('editar');
  $('#plantilla_id').val(data.id);
  
  // Cargar datos básicos
  $('#nombre').val(data.nombre);
  $('#tipo').val(data.tipo);
  $('#categoria').val(data.categoria);
  $('#activo').val(data.activo);
  $('#contenido').val(data.contenido);
  
  // Mostrar/ocultar asunto según tipo
  if(data.tipo === 'email') {
    $('#asuntoContainer').show();
    $('#asunto').val(data.asunto || '');
    $('#asunto').prop('required', true);
  } else {
    $('#asuntoContainer').hide();
    $('#asunto').prop('required', false);
  }
  
  // Cargar variables
  const container = $('#variablesContainer');
  container.empty();
  
  if(data.variables_disponibles) {
    try {
      const variables = JSON.parse(data.variables_disponibles);
      if(variables && variables.length > 0) {
        variables.forEach((variable, index) => {
          const removeBtn = index > 0 ? `
            <button type="button" class="btn btn-outline-danger btn-sm remove-variable"
                    style="border-radius: 0 8px 8px 0;">
              <i class="ti ti-trash"></i>
            </button>
          ` : '';
          
          container.append(`
            <div class="input-group mb-2 variable-input">
              <span class="input-group-text" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #5a8f4f;">
                <i class="ti ti-tag"></i>
              </span>
              <input type="text" class="form-control variable-item" name="variables[]" 
                     value="${variable}"
                     placeholder="nombre_variable (sin {{ }})"
                     style="border: 1px solid #c8e6c9; ${index === 0 ? 'border-radius: 0 8px 8px 0;' : ''}">
              ${removeBtn}
            </div>
          `);
        });
      } else {
        agregarInputVariableVacio(container);
      }
    } catch(e) {
      agregarInputVariableVacio(container);
    }
  } else {
    agregarInputVariableVacio(container);
  }
  
  // Mostrar modal
  $('#modalCrearPlantilla').modal('show');
}

// Función auxiliar para agregar input vacío
function agregarInputVariableVacio(container) {
  container.append(`
    <div class="input-group mb-2 variable-input">
      <span class="input-group-text" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #5a8f4f;">
        <i class="ti ti-tag"></i>
      </span>
      <input type="text" class="form-control variable-item" name="variables[]" 
             placeholder="nombre_variable (sin {{ }})"
             style="border: 1px solid #c8e6c9; border-radius: 0 8px 8px 0;">
    </div>
  `);
}
</script>

<style>
.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>