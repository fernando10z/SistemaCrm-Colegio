<!-- Modal Ver Plantilla (Solo Lectura) -->
<div class="modal fade" id="modalVerPlantilla" tabindex="-1" aria-labelledby="modalVerPlantillaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: linear-gradient(135deg, #d4e7f7 0%, #e3f2fd 100%); border-radius: 15px 15px 0 0; border-bottom: none;">
        <h5 class="modal-title" id="modalVerPlantillaLabel" style="color: #4a7ba7; font-weight: 600;">
          <i class="ti ti-eye me-2"></i>Detalles de la Plantilla
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="background-color: #ffffff; padding: 30px;">
        
        <!-- Información General -->
        <div class="card mb-3" style="border: 1px solid #e8d7f1; border-radius: 10px; background-color: #fef9ff;">
          <div class="card-body">
            <h6 class="card-title mb-3" style="color: #6b4e71; font-weight: 600;">
              <i class="ti ti-info-circle me-1"></i>Información General
            </h6>
            
            <div class="row mb-3">
              <div class="col-md-2">
                <label class="form-label fw-bold" style="color: #6b4e71;">ID:</label>
                <p id="ver_id" class="mb-0"></p>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold" style="color: #6b4e71;">Nombre:</label>
                <p id="ver_nombre" class="mb-0"></p>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold" style="color: #6b4e71;">Canal:</label>
                <p id="ver_tipo" class="mb-0"></p>
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold" style="color: #6b4e71;">Categoría:</label>
                <p id="ver_categoria" class="mb-0"></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold" style="color: #6b4e71;">Estado:</label>
                <p id="ver_activo" class="mb-0"></p>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold" style="color: #6b4e71;">Creada:</label>
                <p id="ver_created_at" class="mb-0"></p>
              </div>
            </div>
            
            <div class="row" id="ver_asunto_container" style="display: none;">
              <div class="col-md-12">
                <label class="form-label fw-bold" style="color: #6b4e71;">Asunto (Email):</label>
                <p id="ver_asunto" class="mb-0 p-2" style="background-color: #f0f4ff; border-radius: 6px;"></p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Contenido del Mensaje -->
        <div class="card mb-3" style="border: 1px solid #d4e7f7; border-radius: 10px; background-color: #f7fcff;">
          <div class="card-body">
            <h6 class="card-title mb-3" style="color: #4a7ba7; font-weight: 600;">
              <i class="ti ti-message me-1"></i>Contenido del Mensaje
            </h6>
            
            <div class="p-3" style="background-color: #ffffff; border: 1px solid #b8d9ed; border-radius: 8px; font-family: 'Courier New', monospace; white-space: pre-wrap; max-height: 300px; overflow-y: auto;">
              <div id="ver_contenido"></div>
            </div>
          </div>
        </div>
        
        <!-- Variables Disponibles -->
        <div class="card" style="border: 1px solid #d7e8d4; border-radius: 10px; background-color: #f9fff7;">
          <div class="card-body">
            <h6 class="card-title mb-3" style="color: #5a8f4f; font-weight: 600;">
              <i class="ti ti-variable me-1"></i>Variables Disponibles
            </h6>
            
            <div id="ver_variables_container" class="d-flex flex-wrap gap-2">
              <!-- Variables se cargarán aquí dinámicamente -->
            </div>
          </div>
        </div>
        
      </div>
      
      <div class="modal-footer" style="background-color: #fafafa; border-radius: 0 0 15px 15px; border-top: 1px solid #efefef; padding: 20px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                style="border-radius: 8px; padding: 10px 20px;">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
        <button type="button" class="btn btn-primary" id="btnEditarDesdeVer"
                style="border-radius: 8px; padding: 10px 25px; background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); border: none;">
          <i class="ti ti-edit me-1"></i>Editar Plantilla
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Cargar datos al modal Ver Plantilla
function cargarModalVerPlantilla(data) {
  // Información general
  $('#ver_id').text(data.id);
  $('#ver_nombre').text(data.nombre);
  
  // Tipo con badge
  let tipoBadge = '';
  switch(data.tipo) {
    case 'email':
      tipoBadge = '<span class="badge" style="background-color: #dc3545; color: white;">📧 EMAIL</span>';
      break;
    case 'whatsapp':
      tipoBadge = '<span class="badge" style="background-color: #25d366; color: white;">💬 WHATSAPP</span>';
      break;
    case 'sms':
      tipoBadge = '<span class="badge" style="background-color: #007bff; color: white;">📱 SMS</span>';
      break;
  }
  $('#ver_tipo').html(tipoBadge);
  
  // Categoría con badge
  const categorias = {
    'cumpleanos': { color: '#e83e8c', texto: 'Cumpleaños' },
    'evento': { color: '#6f42c1', texto: 'Evento' },
    'recordatorio': { color: '#fd7e14', texto: 'Recordatorio' },
    'general': { color: '#6c757d', texto: 'General' },
    'bienvenida': { color: '#20c997', texto: 'Bienvenida' },
    'leads': { color: '#17a2b8', texto: 'Leads' }
  };
  const catInfo = categorias[data.categoria] || categorias['general'];
  $('#ver_categoria').html(`<span class="badge" style="background-color: ${catInfo.color}; color: white;">${catInfo.texto}</span>`);
  
  // Estado
  $('#ver_activo').html(data.activo == 1 
    ? '<span class="badge bg-success">✅ Activa</span>' 
    : '<span class="badge bg-danger">⏸️ Inactiva</span>');
  
  // Fecha de creación
  $('#ver_created_at').text(formatearFecha(data.created_at));
  
  // Asunto (solo si es email)
  if(data.tipo === 'email' && data.asunto) {
    $('#ver_asunto_container').show();
    $('#ver_asunto').text(data.asunto);
  } else {
    $('#ver_asunto_container').hide();
  }
  
  // Contenido
  $('#ver_contenido').text(data.contenido);
  
  // Variables disponibles
  let variablesHtml = '';
  if(data.variables_disponibles) {
    try {
      const variables = JSON.parse(data.variables_disponibles);
      if(variables && variables.length > 0) {
        variables.forEach(variable => {
          variablesHtml += `<span class="badge" style="background-color: #e8f5e9; color: #5a8f4f; padding: 8px 12px; border: 1px solid #c8e6c9; border-radius: 8px;">{{${variable}}}</span>`;
        });
      } else {
        variablesHtml = '<span class="text-muted">No hay variables definidas</span>';
      }
    } catch(e) {
      variablesHtml = '<span class="text-muted">No hay variables definidas</span>';
    }
  } else {
    variablesHtml = '<span class="text-muted">No hay variables definidas</span>';
  }
  $('#ver_variables_container').html(variablesHtml);
  
  // Guardar ID para botón editar
  $('#btnEditarDesdeVer').data('id', data.id);
}

// Formatear fecha
function formatearFecha(fecha) {
  const date = new Date(fecha);
  const dia = String(date.getDate()).padStart(2, '0');
  const mes = String(date.getMonth() + 1).padStart(2, '0');
  const anio = date.getFullYear();
  return `${dia}/${mes}/${anio}`;
}

// Botón editar desde modal ver
$(document).on('click', '#btnEditarDesdeVer', function() {
  const id = $(this).data('id');
  $('#modalVerPlantilla').modal('hide');
  
  // Pequeño delay para que cierre el modal anterior
  setTimeout(function() {
    $('.btn-editar-plantilla[data-id="' + id + '"]').trigger('click');
  }, 300);
});
</script>