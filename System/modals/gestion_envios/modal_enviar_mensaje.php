<!-- Modal Enviar Mensaje -->
<div class="modal fade" id="modalEnviarMensaje" tabindex="-1" aria-labelledby="modalEnviarMensajeLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8d8ea 0%, #d4a5e3 100%); color: white;">
        <h5 class="modal-title" id="modalEnviarMensajeLabel">
          <i class="ti ti-send me-2"></i>Enviar Nuevo Mensaje
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formEnviarMensaje" method="POST" novalidate>
        <div class="modal-body">
          <!-- Alertas -->
          <div id="alertaMensaje" class="alert alert-dismissible fade" role="alert" style="display: none;">
            <span id="alertaMensajeTexto"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>

          <div class="row">
            <!-- Columna Izquierda: Configuración -->
            <div class="col-md-6">
              <!-- Tipo de Mensaje -->
              <div class="mb-3">
                <label for="tipoMensaje" class="form-label">
                  <i class="ti ti-message-circle"></i> Tipo de Mensaje <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="tipoMensaje" name="tipo" required>
                  <option value="">Seleccione un tipo</option>
                  <option value="email">📧 Email</option>
                  <option value="whatsapp">💬 WhatsApp</option>
                  <option value="sms">📱 SMS</option>
                </select>
                <div class="invalid-feedback">Por favor seleccione un tipo de mensaje.</div>
              </div>

              <!-- Plantilla -->
              <div class="mb-3">
                <label for="plantillaMensaje" class="form-label">
                  <i class="ti ti-template"></i> Plantilla (Opcional)
                </label>
                <select class="form-select" id="plantillaMensaje" name="plantilla_id">
                  <option value="">Sin plantilla (mensaje manual)</option>
                </select>
                <small class="text-muted">Seleccione una plantilla para autocompletar el mensaje</small>
              </div>

              <!-- Destinatarios -->
              <div class="mb-3">
                <label class="form-label">
                  <i class="ti ti-users"></i> Destinatarios <span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-2 mb-2">
                  <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="btnSeleccionarLeads">
                    <i class="ti ti-user-plus"></i> Seleccionar Leads
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-success flex-fill" id="btnSeleccionarApoderados">
                    <i class="ti ti-users-group"></i> Seleccionar Apoderados
                  </button>
                </div>
                <div id="destinatariosSeleccionados" class="border rounded p-2" style="min-height: 100px; max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                  <small class="text-muted">No hay destinatarios seleccionados</small>
                </div>
                <input type="hidden" id="destinatariosData" name="destinatarios" required>
                <div class="invalid-feedback">Debe seleccionar al menos un destinatario.</div>
              </div>

              <!-- Programar Envío -->
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="programarEnvio" name="programar">
                  <label class="form-check-label" for="programarEnvio">
                    <i class="ti ti-clock"></i> Programar envío
                  </label>
                </div>
                <input type="datetime-local" class="form-control mt-2" id="fechaProgramada" name="fecha_programada" style="display: none;">
              </div>
            </div>

            <!-- Columna Derecha: Contenido del Mensaje -->
            <div class="col-md-6">
              <!-- Asunto (solo para email) -->
              <div class="mb-3" id="campoAsunto" style="display: none;">
                <label for="asuntoMensaje" class="form-label">
                  <i class="ti ti-mail"></i> Asunto <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="asuntoMensaje" name="asunto" maxlength="200" placeholder="Ingrese el asunto del email">
                <div class="invalid-feedback">El asunto es obligatorio para emails (máximo 200 caracteres).</div>
              </div>

              <!-- Contenido -->
              <div class="mb-3">
                <label for="contenidoMensaje" class="form-label">
                  <i class="ti ti-file-text"></i> Contenido del Mensaje <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="contenidoMensaje" name="contenido" rows="8" required placeholder="Escriba su mensaje aquí..."></textarea>
                <div class="d-flex justify-content-between mt-1">
                  <small class="text-muted">
                    <strong>Variables disponibles:</strong> {nombre}, {apellido}, {email}, {telefono}
                  </small>
                  <small id="contadorCaracteres" class="text-muted">0 caracteres</small>
                </div>
                <div class="invalid-feedback">El contenido del mensaje es obligatorio.</div>
              </div>

              <!-- Vista Previa -->
              <div class="mb-3">
                <label class="form-label">
                  <i class="ti ti-eye"></i> Vista Previa
                </label>
                <div id="vistaPreviaMensaje" class="border rounded p-3" style="background-color: #fff; min-height: 100px;">
                  <small class="text-muted">La vista previa aparecerá aquí...</small>
                </div>
              </div>

              <!-- Prioridad -->
              <div class="mb-3">
                <label for="prioridadMensaje" class="form-label">
                  <i class="ti ti-flag"></i> Prioridad
                </label>
                <select class="form-select form-select-sm" id="prioridadMensaje" name="prioridad">
                  <option value="normal" selected>Normal</option>
                  <option value="alta">Alta</option>
                  <option value="baja">Baja</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer" style="background-color: #f8f9fa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="btnEnviarMensaje">
            <i class="ti ti-send"></i> Enviar Mensaje
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Seleccionar Leads -->
<div class="modal fade" id="modalSeleccionarLeads" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #b3d9ff;">
        <h5 class="modal-title">Seleccionar Leads</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" class="form-control" id="buscarLeads" placeholder="Buscar leads...">
        </div>
        <div id="listaLeads" style="max-height: 400px; overflow-y: auto;">
          <!-- Se carga dinámicamente -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnAplicarLeads">Aplicar Selección</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Seleccionar Apoderados -->
<div class="modal fade" id="modalSeleccionarApoderados" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #c7e6bd;">
        <h5 class="modal-title">Seleccionar Apoderados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" class="form-control" id="buscarApoderados" placeholder="Buscar apoderados...">
        </div>
        <div id="listaApoderados" style="max-height: 400px; overflow-y: auto;">
          <!-- Se carga dinámicamente -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="btnAplicarApoderados">Aplicar Selección</button>
      </div>
    </div>
  </div>
</div>

<style>
  .destinatario-item {
    background-color: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 20px;
    padding: 5px 12px;
    margin: 3px;
    display: inline-flex;
    align-items: center;
    font-size: 0.85rem;
  }
  
  .destinatario-item .btn-remove {
    background: none;
    border: none;
    color: #d32f2f;
    margin-left: 8px;
    padding: 0;
    font-size: 1rem;
    cursor: pointer;
  }
  
  .destinatario-checkbox {
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
    transition: background-color 0.2s;
  }
  
  .destinatario-checkbox:hover {
    background-color: #f5f5f5;
  }
  
  .destinatario-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 10px;
  }
</style>

<script>
(function() {
  'use strict';

  let destinatariosSeleccionados = [];
  let plantillasCache = {};
  let leadsCache = [];
  let apoderadosCache = [];

  // Inicializar cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', function() {
    inicializarModal();
  });

  function inicializarModal() {
    const form = document.getElementById('formEnviarMensaje');
    const tipoMensaje = document.getElementById('tipoMensaje');
    const plantillaMensaje = document.getElementById('plantillaMensaje');
    const contenidoMensaje = document.getElementById('contenidoMensaje');
    const programarEnvio = document.getElementById('programarEnvio');
    const fechaProgramada = document.getElementById('fechaProgramada');

    // Cambio de tipo de mensaje
    tipoMensaje.addEventListener('change', function() {
      const tipo = this.value;
      actualizarCamposPorTipo(tipo);
      cargarPlantillasPorTipo(tipo);
      validarCampo(this);
    });

    // Cambio de plantilla
    plantillaMensaje.addEventListener('change', function() {
      const plantillaId = this.value;
      if (plantillaId && plantillasCache[plantillaId]) {
        aplicarPlantilla(plantillasCache[plantillaId]);
      }
    });

    // Contador de caracteres
    contenidoMensaje.addEventListener('input', function() {
      actualizarContadorCaracteres();
      actualizarVistaPrevia();
      validarCampo(this);
    });

    // Programar envío
    programarEnvio.addEventListener('change', function() {
      if (this.checked) {
        fechaProgramada.style.display = 'block';
        fechaProgramada.required = true;
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() + 30);
        fechaProgramada.min = ahora.toISOString().slice(0, 16);
      } else {
        fechaProgramada.style.display = 'none';
        fechaProgramada.required = false;
        fechaProgramada.value = '';
      }
    });

    // Validación en tiempo real
    document.getElementById('asuntoMensaje').addEventListener('input', function() {
      validarCampo(this);
      actualizarVistaPrevia();
    });

    // Botones de selección de destinatarios
    document.getElementById('btnSeleccionarLeads').addEventListener('click', abrirSeleccionLeads);
    document.getElementById('btnSeleccionarApoderados').addEventListener('click', abrirSeleccionApoderados);
    document.getElementById('btnAplicarLeads').addEventListener('click', aplicarSeleccionLeads);
    document.getElementById('btnAplicarApoderados').addEventListener('click', aplicarSeleccionApoderados);

    // Búsqueda en modales
    document.getElementById('buscarLeads').addEventListener('input', filtrarLeads);
    document.getElementById('buscarApoderados').addEventListener('input', filtrarApoderados);

    // Submit del formulario
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      e.stopPropagation();

      if (validarFormulario()) {
        enviarMensaje();
      }
    });

    // Reset al cerrar modal
    $('#modalEnviarMensaje').on('hidden.bs.modal', function() {
      resetearFormulario();
    });
  }

  function actualizarCamposPorTipo(tipo) {
    const campoAsunto = document.getElementById('campoAsunto');
    const asuntoInput = document.getElementById('asuntoMensaje');
    const contenidoMensaje = document.getElementById('contenidoMensaje');

    if (tipo === 'email') {
      campoAsunto.style.display = 'block';
      asuntoInput.required = true;
      contenidoMensaje.placeholder = 'Escriba el contenido del email. Puede usar HTML para formato.';
      contenidoMensaje.removeAttribute('maxlength');
    } else {
      campoAsunto.style.display = 'none';
      asuntoInput.required = false;
      asuntoInput.value = '';
      if (tipo === 'whatsapp') {
        contenidoMensaje.placeholder = 'Escriba el mensaje de WhatsApp (máx. 1600 caracteres)';
        contenidoMensaje.maxLength = 1600;
      } else if (tipo === 'sms') {
        contenidoMensaje.placeholder = 'Escriba el mensaje SMS (máx. 160 caracteres)';
        contenidoMensaje.maxLength = 160;
      }
    }
    actualizarContadorCaracteres();
  }

  function cargarPlantillasPorTipo(tipo) {
    if (!tipo) {
      document.getElementById('plantillaMensaje').innerHTML = '<option value="">Seleccione primero un tipo de mensaje</option>';
      return;
    }

    console.log('Cargando plantillas para tipo:', tipo);

    fetch('actions/obtener_plantillas.php?tipo=' + tipo)
      .then(response => {
        console.log('Response status plantillas:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Plantillas recibidas:', data);
        if (data.success) {
          let options = '<option value="">Sin plantilla (mensaje manual)</option>';
          if (data.plantillas && data.plantillas.length > 0) {
            data.plantillas.forEach(plantilla => {
              plantillasCache[plantilla.id] = plantilla;
              options += `<option value="${plantilla.id}">${plantilla.nombre} - ${plantilla.categoria}</option>`;
            });
          }
          document.getElementById('plantillaMensaje').innerHTML = options;
        } else {
          console.error('Error en respuesta plantillas:', data.message);
          document.getElementById('plantillaMensaje').innerHTML = '<option value="">Sin plantillas disponibles</option>';
        }
      })
      .catch(error => {
        console.error('Error al cargar plantillas:', error);
        document.getElementById('plantillaMensaje').innerHTML = '<option value="">Error al cargar plantillas</option>';
      });
  }

  function aplicarPlantilla(plantilla) {
    document.getElementById('asuntoMensaje').value = plantilla.asunto || '';
    document.getElementById('contenidoMensaje').value = plantilla.contenido || '';
    actualizarContadorCaracteres();
    actualizarVistaPrevia();
  }

  function abrirSeleccionLeads() {
    console.log('Abriendo modal de leads...');
    
    const listaContainer = document.getElementById('listaLeads');
    listaContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    
    fetch('actions/obtener_leads.php')
      .then(response => {
        console.log('Response status leads:', response.status);
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        console.log('Leads recibidos:', data);
        if (data.success && data.leads) {
          leadsCache = data.leads;
          mostrarListaLeads(data.leads);
          $('#modalSeleccionarLeads').modal('show');
        } else {
          listaContainer.innerHTML = '<div class="alert alert-warning">No se encontraron leads. ' + (data.message || '') + '</div>';
        }
      })
      .catch(error => {
        console.error('Error al cargar leads:', error);
        listaContainer.innerHTML = '<div class="alert alert-danger">Error al cargar leads: ' + error.message + '</div>';
      });
  }

  function abrirSeleccionApoderados() {
    console.log('Abriendo modal de apoderados...');
    
    const listaContainer = document.getElementById('listaApoderados');
    listaContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    
    fetch('actions/obtener_apoderados.php')
      .then(response => {
        console.log('Response status apoderados:', response.status);
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        console.log('Apoderados recibidos:', data);
        if (data.success && data.apoderados) {
          apoderadosCache = data.apoderados;
          mostrarListaApoderados(data.apoderados);
          $('#modalSeleccionarApoderados').modal('show');
        } else {
          listaContainer.innerHTML = '<div class="alert alert-warning">No se encontraron apoderados. ' + (data.message || '') + '</div>';
        }
      })
      .catch(error => {
        console.error('Error al cargar apoderados:', error);
        listaContainer.innerHTML = '<div class="alert alert-danger">Error al cargar apoderados: ' + error.message + '</div>';
      });
  }

  function mostrarListaLeads(leads) {
    const container = document.getElementById('listaLeads');
    
    if (!leads || leads.length === 0) {
      container.innerHTML = '<div class="alert alert-info text-center">No hay leads disponibles con información de contacto.</div>';
      return;
    }
    
    console.log('Mostrando ' + leads.length + ' leads');
    
    let html = '';
    leads.forEach(lead => {
      const checked = destinatariosSeleccionados.some(d => d.tipo === 'lead' && d.id == lead.id);
      const nombre = `${lead.nombres_estudiante || ''} ${lead.apellidos_estudiante || ''}`.trim();
      const email = lead.email_contacto || '';
      const telefono = lead.telefono_contacto || '';
      
      html += `
        <div class="destinatario-checkbox">
          <label class="d-flex align-items-center w-100" style="cursor: pointer; margin: 0;">
            <input type="checkbox" value="${lead.id}" data-tipo="lead" 
                   data-nombre="${nombre}"
                   data-email="${email}"
                   data-telefono="${telefono}"
                   ${checked ? 'checked' : ''}>
            <div class="ms-2 flex-grow-1">
              <strong>${nombre}</strong><br>
              <small class="text-muted">
                ${email ? '📧 ' + email : ''} 
                ${telefono ? ' 📱 ' + telefono : ''}
                ${!email && !telefono ? 'Sin contacto' : ''}
              </small>
            </div>
          </label>
        </div>
      `;
    });
    
    container.innerHTML = html;
  }

  function mostrarListaApoderados(apoderados) {
    const container = document.getElementById('listaApoderados');
    
    if (!apoderados || apoderados.length === 0) {
      container.innerHTML = '<div class="alert alert-info text-center">No hay apoderados disponibles con información de contacto.</div>';
      return;
    }
    
    console.log('Mostrando ' + apoderados.length + ' apoderados');
    
    let html = '';
    apoderados.forEach(apoderado => {
      const checked = destinatariosSeleccionados.some(d => d.tipo === 'apoderado' && d.id == apoderado.id);
      const nombre = `${apoderado.nombres || ''} ${apoderado.apellidos || ''}`.trim();
      const email = apoderado.email || '';
      const telefono = apoderado.celular || '';
      
      html += `
        <div class="destinatario-checkbox">
          <label class="d-flex align-items-center w-100" style="cursor: pointer; margin: 0;">
            <input type="checkbox" value="${apoderado.id}" data-tipo="apoderado"
                   data-nombre="${nombre}"
                   data-email="${email}"
                   data-telefono="${telefono}"
                   ${checked ? 'checked' : ''}>
            <div class="ms-2 flex-grow-1">
              <strong>${nombre}</strong><br>
              <small class="text-muted">
                ${email ? '📧 ' + email : ''} 
                ${telefono ? ' 📱 ' + telefono : ''}
                ${!email && !telefono ? 'Sin contacto' : ''}
              </small>
            </div>
          </label>
        </div>
      `;
    });
    
    container.innerHTML = html;
  }

  function aplicarSeleccionLeads() {
    const checkboxes = document.querySelectorAll('#listaLeads input[type="checkbox"]:checked');
    
    console.log('Aplicando selección de ' + checkboxes.length + ' leads');
    
    checkboxes.forEach(checkbox => {
      const existe = destinatariosSeleccionados.some(d => 
        d.tipo === 'lead' && d.id == checkbox.value
      );
      
      if (!existe) {
        destinatariosSeleccionados.push({
          tipo: 'lead',
          id: checkbox.value,
          nombre: checkbox.dataset.nombre,
          email: checkbox.dataset.email,
          telefono: checkbox.dataset.telefono
        });
      }
    });
    
    actualizarDestinatariosUI();
    $('#modalSeleccionarLeads').modal('hide');
  }

  function aplicarSeleccionApoderados() {
    const checkboxes = document.querySelectorAll('#listaApoderados input[type="checkbox"]:checked');
    
    console.log('Aplicando selección de ' + checkboxes.length + ' apoderados');
    
    checkboxes.forEach(checkbox => {
      const existe = destinatariosSeleccionados.some(d => 
        d.tipo === 'apoderado' && d.id == checkbox.value
      );
      
      if (!existe) {
        destinatariosSeleccionados.push({
          tipo: 'apoderado',
          id: checkbox.value,
          nombre: checkbox.dataset.nombre,
          email: checkbox.dataset.email,
          telefono: checkbox.dataset.telefono
        });
      }
    });
    
    actualizarDestinatariosUI();
    $('#modalSeleccionarApoderados').modal('hide');
  }

  function actualizarDestinatariosUI() {
    const container = document.getElementById('destinatariosSeleccionados');
    const dataInput = document.getElementById('destinatariosData');
    
    console.log('Destinatarios seleccionados:', destinatariosSeleccionados.length);
    
    if (destinatariosSeleccionados.length === 0) {
      container.innerHTML = '<small class="text-muted">No hay destinatarios seleccionados</small>';
      dataInput.value = '';
      dataInput.classList.remove('is-valid');
      return;
    }
    
    let html = '';
    destinatariosSeleccionados.forEach((dest, index) => {
      html += `
        <span class="destinatario-item">
          ${dest.tipo === 'lead' ? '👤' : '👥'} ${dest.nombre}
          <button type="button" class="btn-remove" onclick="window.eliminarDestinatario(${index})" title="Eliminar">
            ×
          </button>
        </span>
      `;
    });
    
    container.innerHTML = html;
    dataInput.value = JSON.stringify(destinatariosSeleccionados);
    dataInput.classList.add('is-valid');
  }

  window.eliminarDestinatario = function(index) {
    destinatariosSeleccionados.splice(index, 1);
    actualizarDestinatariosUI();
  };

  function filtrarLeads(e) {
    const busqueda = e.target.value.toLowerCase();
    const items = document.querySelectorAll('#listaLeads .destinatario-checkbox');
    
    items.forEach(item => {
      const texto = item.textContent.toLowerCase();
      item.style.display = texto.includes(busqueda) ? 'block' : 'none';
    });
  }

  function filtrarApoderados(e) {
    const busqueda = e.target.value.toLowerCase();
    const items = document.querySelectorAll('#listaApoderados .destinatario-checkbox');
    
    items.forEach(item => {
      const texto = item.textContent.toLowerCase();
      item.style.display = texto.includes(busqueda) ? 'block' : 'none';
    });
  }

  function actualizarContadorCaracteres() {
    const contenido = document.getElementById('contenidoMensaje').value;
    const contador = document.getElementById('contadorCaracteres');
    const tipo = document.getElementById('tipoMensaje').value;
    
    let limite = '';
    if (tipo === 'sms') limite = ' / 160';
    else if (tipo === 'whatsapp') limite = ' / 1600';
    
    contador.textContent = contenido.length + limite + ' caracteres';
    
    if (tipo === 'sms' && contenido.length > 160) {
      contador.classList.add('text-danger');
    } else if (tipo === 'whatsapp' && contenido.length > 1600) {
      contador.classList.add('text-danger');
    } else {
      contador.classList.remove('text-danger');
    }
  }

  function actualizarVistaPrevia() {
    const tipo = document.getElementById('tipoMensaje').value;
    const asunto = document.getElementById('asuntoMensaje').value;
    const contenido = document.getElementById('contenidoMensaje').value;
    const preview = document.getElementById('vistaPreviaMensaje');
    
    if (!contenido) {
      preview.innerHTML = '<small class="text-muted">La vista previa aparecerá aquí...</small>';
      return;
    }
    
    let html = '';
    if (tipo === 'email' && asunto) {
      html += `<div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 8px;">
                 <strong>Asunto:</strong> ${escapeHtml(asunto)}
               </div>`;
    }
    
    html += `<div style="white-space: pre-wrap;">${escapeHtml(contenido)}</div>`;
    preview.innerHTML = html;
  }

  function validarCampo(campo) {
    if (!campo) return false;
    
    if (campo.checkValidity()) {
      campo.classList.remove('is-invalid');
      campo.classList.add('is-valid');
      return true;
    } else {
      campo.classList.remove('is-valid');
      campo.classList.add('is-invalid');
      return false;
    }
  }

  function validarFormulario() {
    const form = document.getElementById('formEnviarMensaje');
    let valido = true;

    // Validar tipo
    const tipo = document.getElementById('tipoMensaje');
    if (!validarCampo(tipo)) valido = false;

    // Validar asunto si es email
    if (tipo.value === 'email') {
      const asunto = document.getElementById('asuntoMensaje');
      if (!validarCampo(asunto)) valido = false;
    }

    // Validar contenido
    const contenido = document.getElementById('contenidoMensaje');
    if (!validarCampo(contenido)) valido = false;

    // Validar límites de caracteres
    if (tipo.value === 'sms' && contenido.value.length > 160) {
      mostrarAlerta('El mensaje SMS no puede exceder 160 caracteres', 'danger');
      valido = false;
    }
    if (tipo.value === 'whatsapp' && contenido.value.length > 1600) {
      mostrarAlerta('El mensaje WhatsApp no puede exceder 1600 caracteres', 'danger');
      valido = false;
    }

    // Validar destinatarios
    if (destinatariosSeleccionados.length === 0) {
      mostrarAlerta('Debe seleccionar al menos un destinatario', 'danger');
      valido = false;
    }

    // Validar fecha programada
    if (document.getElementById('programarEnvio').checked) {
      const fechaProgramada = document.getElementById('fechaProgramada');
      if (!validarCampo(fechaProgramada)) valido = false;
      
      const fecha = new Date(fechaProgramada.value);
      const ahora = new Date();
      if (fecha <= ahora) {
        mostrarAlerta('La fecha programada debe ser futura', 'danger');
        valido = false;
      }
    }

    if (!valido) {
      mostrarAlerta('Por favor complete todos los campos requeridos correctamente', 'danger');
    }

    form.classList.add('was-validated');
    return valido;
  }

  function enviarMensaje() {
    const btn = document.getElementById('btnEnviarMensaje');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    const formData = new FormData(document.getElementById('formEnviarMensaje'));

    fetch('actions/enviar_mensaje.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      console.log('Respuesta envío:', data);
      if (data.success) {
        mostrarAlerta(data.message, 'success');
        setTimeout(() => {
          $('#modalEnviarMensaje').modal('hide');
          location.reload();
        }, 2000);
      } else {
        mostrarAlerta(data.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send"></i> Enviar Mensaje';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarAlerta('Error de conexión. Por favor intente nuevamente.', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti ti-send"></i> Enviar Mensaje';
    });
  }

  function mostrarAlerta(mensaje, tipo) {
    const alerta = document.getElementById('alertaMensaje');
    const texto = document.getElementById('alertaMensajeTexto');
    
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    texto.textContent = mensaje;
    alerta.style.display = 'block';
    
    setTimeout(() => {
      alerta.classList.remove('show');
      setTimeout(() => {
        alerta.style.display = 'none';
      }, 150);
    }, 5000);
  }

  function resetearFormulario() {
    const form = document.getElementById('formEnviarMensaje');
    form.reset();
    form.classList.remove('was-validated');
    
    destinatariosSeleccionados = [];
    actualizarDestinatariosUI();
    
    document.getElementById('campoAsunto').style.display = 'none';
    document.getElementById('fechaProgramada').style.display = 'none';
    document.getElementById('contadorCaracteres').textContent = '0 caracteres';
    document.getElementById('vistaPreviaMensaje').innerHTML = '<small class="text-muted">La vista previa aparecerá aquí...</small>';
    
    const alerta = document.getElementById('alertaMensaje');
    alerta.style.display = 'none';
    
    document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
      el.classList.remove('is-valid', 'is-invalid');
    });
  }

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }
})();
</script>