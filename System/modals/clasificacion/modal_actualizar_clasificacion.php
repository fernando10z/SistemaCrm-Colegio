<!-- Modal Actualizar Clasificación Completa -->
<div class="modal fade" id="modalActualizarClasificacion" tabindex="-1" aria-labelledby="modalActualizarClasificacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #e8d5f2 0%, #f3e5f5 100%);">
        <h5 class="modal-title" id="modalActualizarClasificacionLabel">
          <i class="ti ti-edit me-2"></i>Actualizar Clasificación Completa
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formActualizarClasificacion" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="accion" value="actualizar_clasificacion">
        <input type="hidden" id="clasificacion_apoderado_id" name="apoderado_id">
        
        <div class="modal-body">
          <!-- Información del Apoderado -->
          <div class="alert" style="background: linear-gradient(135deg, #f3e5f5 0%, #fce4ec 100%); border-left: 4px solid #9c27b0;">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h6 class="mb-2"><i class="ti ti-user me-1"></i>Apoderado Seleccionado:</h6>
                <div><strong id="clasificacion_apoderado_nombre" style="font-size: 1.1rem;"></strong></div>
                <div class="mt-2" id="clasificacion_metricas_actuales" style="font-size: 0.85rem; color: #666;"></div>
              </div>
              <div class="col-md-4 text-end">
                <div id="clasificacion_categoria_actual" style="padding: 10px; background: white; border-radius: 8px; text-align: center;">
                  <small class="text-muted d-block mb-1">Categoría Actual</small>
                  <span id="badge_categoria_actual" class="badge" style="font-size: 0.9rem;">Cargando...</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <!-- COLUMNA IZQUIERDA: Clasificaciones -->
            <div class="col-md-6">
              <div class="card mb-3" style="background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%); border: 1px solid #ffd54f;">
                <div class="card-body">
                  <h6 class="mb-3"><i class="ti ti-tags me-1"></i>Clasificaciones del Apoderado</h6>
                  
                  <!-- Tipo de Apoderado -->
                  <div class="mb-3">
                    <label for="tipo_apoderado" class="form-label">
                      <i class="ti ti-user-check me-1"></i>Tipo de Apoderado
                    </label>
                    <select class="form-select" id="tipo_apoderado" name="tipo_apoderado" required>
                      <option value="">Seleccione...</option>
                      <option value="padre">👨 Padre</option>
                      <option value="madre">👩 Madre</option>
                      <option value="tutor_legal">👤 Tutor Legal</option>
                      <option value="abuelo">👴 Abuelo/a</option>
                      <option value="tio">👥 Tío/a</option>
                      <option value="hermano">🧑 Hermano/a Mayor</option>
                      <option value="otro">➕ Otro</option>
                    </select>
                  </div>

                  <!-- Nivel de Compromiso -->
                  <div class="mb-3">
                    <label for="clasificacion_nivel_compromiso" class="form-label">
                      <i class="ti ti-heart me-1"></i>Nivel de Compromiso
                    </label>
                    <select class="form-select" id="clasificacion_nivel_compromiso" name="nivel_compromiso" required>
                      <option value="">Seleccione...</option>
                      <option value="alto" style="background-color: #d4edda;">🟢 Alto</option>
                      <option value="medio" style="background-color: #fff3cd;">🟡 Medio</option>
                      <option value="bajo" style="background-color: #f8d7da;">🔴 Bajo</option>
                    </select>
                  </div>

                  <!-- Nivel de Participación -->
                  <div class="mb-3">
                    <label for="clasificacion_nivel_participacion" class="form-label">
                      <i class="ti ti-activity me-1"></i>Nivel de Participación
                    </label>
                    <select class="form-select" id="clasificacion_nivel_participacion" name="nivel_participacion" required>
                      <option value="">Seleccione...</option>
                      <option value="muy_activo" style="background-color: #d4edda;">⭐ Muy Activo</option>
                      <option value="activo" style="background-color: #d1ecf1;">✓ Activo</option>
                      <option value="poco_activo" style="background-color: #fff3cd;">⚠ Poco Activo</option>
                      <option value="inactivo" style="background-color: #f8d7da;">✗ Inactivo</option>
                    </select>
                  </div>

                  <!-- Preferencia de Contacto -->
                  <div class="mb-3">
                    <label for="preferencia_contacto" class="form-label">
                      <i class="ti ti-message me-1"></i>Preferencia de Contacto
                    </label>
                    <select class="form-select" id="preferencia_contacto" name="preferencia_contacto" required>
                      <option value="">Seleccione...</option>
                      <option value="email">📧 Email</option>
                      <option value="telefono">📞 Teléfono</option>
                      <option value="whatsapp">💬 WhatsApp</option>
                      <option value="presencial">🏢 Presencial</option>
                      <option value="sms">📱 SMS</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- COLUMNA DERECHA: Preview y Métricas -->
            <div class="col-md-6">
              <!-- Preview de Categoría -->
              <div class="card mb-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 1px solid #9c27b0;">
                <div class="card-body">
                  <h6 class="mb-3"><i class="ti ti-eye me-1"></i>Vista Previa de Categoría</h6>
                  
                  <div id="preview_categoria_nueva" class="text-center p-4" style="background: white; border-radius: 12px; min-height: 120px;">
                    <div class="text-muted">
                      <i class="ti ti-info-circle" style="font-size: 2rem;"></i>
                      <p class="mt-2 mb-0">Seleccione los campos para ver la categoría resultante</p>
                    </div>
                  </div>

                  <div class="mt-3 p-3" style="background: #fff; border-radius: 8px; border-left: 4px solid #9c27b0;">
                    <h6 class="mb-2" style="font-size: 0.85rem;">Criterios de Categorización:</h6>
                    <ul style="font-size: 0.75rem; margin-bottom: 0; color: #666;">
                      <li><strong>Colaborador Estrella:</strong> Alto compromiso + (Muy Activo o Activo)</li>
                      <li><strong>Comprometido:</strong> Alto compromiso</li>
                      <li><strong>Muy Participativo:</strong> Muy Activo</li>
                      <li><strong>Problemático:</strong> Bajo compromiso + Inactivo</li>
                      <li><strong>Bajo Compromiso:</strong> Bajo compromiso</li>
                      <li><strong>Inactivo:</strong> Inactivo</li>
                      <li><strong>Regular:</strong> Otros casos</li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Métricas Actuales -->
              <div class="card" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border: 1px solid #ff9800;">
                <div class="card-body">
                  <h6 class="mb-3"><i class="ti ti-chart-line me-1"></i>Métricas Actuales</h6>
                  <div id="metricas_clasificacion_resumen">
                    <div class="text-center text-muted">
                      <div class="spinner-border spinner-border-sm" role="status"></div>
                      <p class="mt-2 mb-0" style="font-size: 0.85rem;">Cargando métricas...</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Observaciones (ancho completo) -->
            <div class="col-md-12 mt-3">
              <div class="card" style="background: #fafafa; border: 1px solid #e0e0e0;">
                <div class="card-body">
                  <label for="observaciones_clasificacion" class="form-label">
                    <i class="ti ti-notes me-1"></i>Observaciones y Justificación
                  </label>
                  <textarea class="form-control" id="observaciones_clasificacion" name="observaciones" rows="4" 
                            placeholder="Agregue notas, justificación de los cambios o comentarios relevantes sobre esta actualización..."></textarea>
                  <small class="text-muted">Estas observaciones se registrarán en el historial de interacciones</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background-color: #fafafa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="button" class="btn btn-warning" id="btnRecalcularAuto">
            <i class="ti ti-refresh me-1"></i>Recalcular Automático
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Guardar Clasificación
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .metrica-clasificacion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin: 5px 0;
    background: white;
    border-radius: 6px;
    border-left: 3px solid #9c27b0;
  }
  
  .metrica-clasificacion-label {
    font-size: 0.8rem;
    color: #666;
  }
  
  .metrica-clasificacion-valor {
    font-size: 1rem;
    font-weight: bold;
    color: #9c27b0;
  }
  
  .categoria-preview-badge {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 25px;
    font-size: 1.1rem;
    font-weight: 600;
    color: white;
    margin: 10px 0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  
  .preview-colaborador-estrella { 
    background: linear-gradient(45deg, #a8e6cf, #81c784);
    animation: pulse-star 2s infinite;
  }
  .preview-comprometido { background-color: #81d4fa; }
  .preview-muy-participativo { background-color: #ce93d8; }
  .preview-regular { background-color: #b0bec5; }
  .preview-bajo-compromiso { background-color: #ffb74d; }
  .preview-inactivo { background-color: #ffc107; color: #856404; }
  .preview-problematico { 
    background-color: #ef5350;
    animation: pulse-problem 2s infinite;
  }
  
  @keyframes pulse-star {
    0%, 100% { transform: scale(1); box-shadow: 0 4px 8px rgba(168, 230, 207, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 6px 12px rgba(168, 230, 207, 0.6); }
  }
  
  @keyframes pulse-problem {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
  }
  
  .cambio-indicator {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 10px;
    margin-left: 8px;
  }
  
  .cambio-mejora { background-color: #d4edda; color: #155724; }
  .cambio-empeora { background-color: #f8d7da; color: #721c24; }
  .cambio-neutro { background-color: #e2e3e5; color: #383d41; }
</style>

<script>
$(document).ready(function() {
  // Al hacer click en botón actualizar clasificación
  $(document).on('click', '.btn-actualizar-clasificacion', function() {
    var id = $(this).data('id');
    var nombre = $(this).data('nombre');
    
    $('#clasificacion_apoderado_id').val(id);
    $('#clasificacion_apoderado_nombre').text(nombre);
    
    // Limpiar formulario
    $('#observaciones_clasificacion').val('');
    $('#preview_categoria_nueva').html('<div class="text-muted"><i class="ti ti-info-circle" style="font-size: 2rem;"></i><p class="mt-2 mb-0">Seleccione los campos para ver la categoría resultante</p></div>');
    
    // Cargar datos actuales del apoderado
    cargarDatosClasificacion(id);
    
    $('#modalActualizarClasificacion').modal('show');
  });
  
  // Función para cargar datos actuales de clasificación
  function cargarDatosClasificacion(apoderado_id) {
    $('#metricas_clasificacion_resumen').html('<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mt-2 mb-0" style="font-size: 0.85rem;">Cargando datos...</p></div>');
    
    $.ajax({
      url: 'actions/obtener_datos_clasificacion.php',
      method: 'POST',
      data: { apoderado_id: apoderado_id },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Prellenar campos del formulario con datos actuales
          $('#tipo_apoderado').val(response.datos.tipo_apoderado);
          $('#clasificacion_nivel_compromiso').val(response.datos.nivel_compromiso);
          $('#clasificacion_nivel_participacion').val(response.datos.nivel_participacion);
          $('#preferencia_contacto').val(response.datos.preferencia_contacto);
          
          // Mostrar categoría actual
          var categoriaActual = response.datos.categoria_apoderado;
          var claseCategoriaActual = 'categoria-' + categoriaActual;
          $('#badge_categoria_actual').removeClass().addClass('badge badge-categoria ' + claseCategoriaActual).text(ucwords(categoriaActual.replace(/_/g, ' ')));
          
          // Mostrar métricas
          var metricasHtml = '';
          metricasHtml += '<div class="metrica-clasificacion-item">';
          metricasHtml += '<span class="metrica-clasificacion-label">Total Interacciones</span>';
          metricasHtml += '<span class="metrica-clasificacion-valor">' + response.metricas.total_interacciones + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-clasificacion-item">';
          metricasHtml += '<span class="metrica-clasificacion-label">Tasa de Éxito</span>';
          metricasHtml += '<span class="metrica-clasificacion-valor">' + response.metricas.tasa_exito + '%</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-clasificacion-item">';
          metricasHtml += '<span class="metrica-clasificacion-label">Puntuación Compromiso</span>';
          metricasHtml += '<span class="metrica-clasificacion-valor">' + response.metricas.puntuacion_compromiso + '%</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-clasificacion-item">';
          metricasHtml += '<span class="metrica-clasificacion-label">Última Interacción</span>';
          metricasHtml += '<span class="metrica-clasificacion-valor" style="font-size: 0.85rem;">' + response.metricas.ultima_interaccion + '</span>';
          metricasHtml += '</div>';
          
          $('#metricas_clasificacion_resumen').html(metricasHtml);
          
          // Mostrar métricas en el alert superior
          $('#clasificacion_metricas_actuales').html(
            '<i class="ti ti-chart-dots me-1"></i>' +
            '<strong>Tipo:</strong> ' + ucwords(response.datos.tipo_apoderado) + ' | ' +
            '<strong>Compromiso:</strong> ' + ucwords(response.datos.nivel_compromiso) + ' | ' +
            '<strong>Participación:</strong> ' + ucwords(response.datos.nivel_participacion.replace(/_/g, ' ')) + ' | ' +
            '<strong>Contacto:</strong> ' + ucfirst(response.datos.preferencia_contacto)
          );
          
          // Actualizar preview de categoría
          actualizarPreviewCategoria();
          
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los datos del apoderado',
            confirmButtonText: 'Entendido'
          });
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error de Conexión',
          text: 'No se pudo conectar con el servidor',
          confirmButtonText: 'Entendido'
        });
      }
    });
  }
  
  // Función para actualizar preview de categoría
  function actualizarPreviewCategoria() {
    var compromiso = $('#clasificacion_nivel_compromiso').val();
    var participacion = $('#clasificacion_nivel_participacion').val();
    
    if (compromiso && participacion) {
      var categoria = 'regular';
      var claseCategoria = 'preview-regular';
      
      if (compromiso === 'alto' && (participacion === 'muy_activo' || participacion === 'activo')) {
        categoria = 'colaborador_estrella';
        claseCategoria = 'preview-colaborador-estrella';
      } else if (compromiso === 'alto') {
        categoria = 'comprometido';
        claseCategoria = 'preview-comprometido';
      } else if (participacion === 'muy_activo') {
        categoria = 'muy_participativo';
        claseCategoria = 'preview-muy-participativo';
      } else if (compromiso === 'bajo' && participacion === 'inactivo') {
        categoria = 'problematico';
        claseCategoria = 'preview-problematico';
      } else if (compromiso === 'bajo') {
        categoria = 'bajo_compromiso';
        claseCategoria = 'preview-bajo-compromiso';
      } else if (participacion === 'inactivo') {
        categoria = 'inactivo';
        claseCategoria = 'preview-inactivo';
      }
      
      var emoji = '';
      if (categoria === 'colaborador_estrella') emoji = '⭐';
      else if (categoria === 'comprometido') emoji = '💚';
      else if (categoria === 'muy_participativo') emoji = '🎯';
      else if (categoria === 'problematico') emoji = '⚠️';
      else if (categoria === 'bajo_compromiso') emoji = '⬇️';
      else if (categoria === 'inactivo') emoji = '😴';
      else emoji = '➖';
      
      $('#preview_categoria_nueva').html(
        '<div>' +
        '<div style="font-size: 0.85rem; color: #666; margin-bottom: 8px;">Nueva Categoría:</div>' +
        '<span class="categoria-preview-badge ' + claseCategoria + '">' + 
        emoji + ' ' + ucwords(categoria.replace(/_/g, ' ')) +
        '</span>' +
        '</div>'
      );
    } else {
      $('#preview_categoria_nueva').html('<div class="text-muted"><i class="ti ti-info-circle" style="font-size: 2rem;"></i><p class="mt-2 mb-0">Seleccione compromiso y participación</p></div>');
    }
  }
  
  // Cambios en los selects actualizan el preview
  $('#clasificacion_nivel_compromiso, #clasificacion_nivel_participacion').on('change', function() {
    actualizarPreviewCategoria();
  });
  
  // Botón Recalcular Automático
  $('#btnRecalcularAuto').on('click', function() {
    var apoderado_id = $('#clasificacion_apoderado_id').val();
    
    Swal.fire({
      title: '¿Recalcular Automáticamente?',
      text: 'Se calcularán automáticamente los niveles de compromiso y participación basados en las métricas actuales.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-refresh me-1"></i> Sí, Recalcular',
      cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
      reverseButtons: true,
      customClass: {
        confirmButton: 'btn btn-warning me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Recalculando...',
          html: 'Por favor espere...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Llamar a recalcular
        $.ajax({
          url: 'actions/recalcular_clasificacion_auto.php',
          method: 'POST',
          data: { apoderado_id: apoderado_id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Actualizar campos con valores calculados
              $('#clasificacion_nivel_compromiso').val(response.nivel_compromiso);
              $('#clasificacion_nivel_participacion').val(response.nivel_participacion);
              
              // Actualizar preview
              actualizarPreviewCategoria();
              
              Swal.fire({
                icon: 'success',
                title: 'Recalculado',
                html: '<p>Compromiso: <strong>' + response.nivel_compromiso.toUpperCase() + '</strong> (' + response.puntuacion_compromiso + '%)</p>' +
                      '<p>Participación: <strong>' + response.nivel_participacion.toUpperCase().replace('_', ' ') + '</strong> (' + response.puntuacion_participacion + '%)</p>',
                confirmButtonText: 'Entendido'
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo recalcular automáticamente',
                confirmButtonText: 'Entendido'
              });
            }
          },
          error: function() {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error al recalcular',
              confirmButtonText: 'Entendido'
            });
          }
        });
      }
    });
  });
  
  // Validación y envío del formulario con SweetAlert2
  $('#formActualizarClasificacion').on('submit', function(e) {
    e.preventDefault();
    
    var apoderado_id = $('#clasificacion_apoderado_id').val();
    var apoderado_nombre = $('#clasificacion_apoderado_nombre').text();
    
    // Validar campos requeridos
    if (!$('#tipo_apoderado').val() || !$('#clasificacion_nivel_compromiso').val() || 
        !$('#clasificacion_nivel_participacion').val() || !$('#preferencia_contacto').val()) {
      Swal.fire({
        icon: 'warning',
        title: 'Campos Incompletos',
        text: 'Por favor complete todos los campos requeridos',
        confirmButtonText: 'Entendido'
      });
      return false;
    }
    
    // Obtener categoría resultante
    var compromiso = $('#clasificacion_nivel_compromiso').val();
    var participacion = $('#clasificacion_nivel_participacion').val();
    var categoria = determinarCategoria(compromiso, participacion);
    
    // Confirmación con SweetAlert2
    Swal.fire({
      title: '¿Confirmar Actualización?',
      html: '<p>Se actualizará la clasificación completa para:</p>' +
            '<p><strong>' + apoderado_nombre + '</strong></p>' +
            '<hr>' +
            '<p class="text-start mb-1"><strong>Compromiso:</strong> ' + compromiso.toUpperCase() + '</p>' +
            '<p class="text-start mb-1"><strong>Participación:</strong> ' + participacion.toUpperCase().replace('_', ' ') + '</p>' +
            '<p class="text-start mb-3"><strong>Categoría Resultante:</strong> <span class="badge bg-primary">' + ucwords(categoria.replace(/_/g, ' ')) + '</span></p>',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-check me-1"></i> Sí, Actualizar',
      cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
      reverseButtons: true,
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Guardando Clasificación',
          html: 'Por favor espere...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Enviar formulario por AJAX
        $.ajax({
          url: $('#formActualizarClasificacion').attr('action'),
          method: 'POST',
          data: $('#formActualizarClasificacion').serialize(),
          success: function(response) {
            // Cerrar modal
            $('#modalActualizarClasificacion').modal('hide');
            
            // Mostrar mensaje de éxito
            Swal.fire({
              icon: 'success',
              title: '¡Clasificación Actualizada!',
              html: '<p>La clasificación se ha actualizado correctamente.</p>' +
                    '<p class="text-muted mt-2">La página se recargará para mostrar los cambios.</p>',
              confirmButtonText: 'Entendido',
              timer: 3000,
              timerProgressBar: true
            }).then(() => {
              // Recargar página para ver cambios
              location.reload();
            });
          },
          error: function(xhr, status, error) {
            Swal.fire({
              icon: 'error',
              title: 'Error al Guardar',
              html: '<p>No se pudo guardar la clasificación.</p>' +
                    '<p class="text-muted">' + error + '</p>',
              confirmButtonText: 'Entendido'
            });
          }
        });
      }
    });
    
    return false;
  });
  
  // Función auxiliar para determinar categoría
  function determinarCategoria(compromiso, participacion) {
    if (compromiso === 'alto' && (participacion === 'muy_activo' || participacion === 'activo')) {
      return 'colaborador_estrella';
    } else if (compromiso === 'alto') {
      return 'comprometido';
    } else if (participacion === 'muy_activo') {
      return 'muy_participativo';
    } else if (compromiso === 'bajo' && participacion === 'inactivo') {
      return 'problematico';
    } else if (compromiso === 'bajo') {
      return 'bajo_compromiso';
    } else if (participacion === 'inactivo') {
      return 'inactivo';
    } else {
      return 'regular';
    }
  }
  
  // Funciones auxiliares de formato
  function ucwords(str) {
    return str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
      return letter.toUpperCase();
    });
  }
  
  function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  }
  
  // Limpiar modal al cerrar
  $('#modalActualizarClasificacion').on('hidden.bs.modal', function() {
    $('#formActualizarClasificacion')[0].reset();
    $('#clasificacion_apoderado_id').val('');
    $('#clasificacion_apoderado_nombre').text('');
    $('#clasificacion_metricas_actuales').html('');
  });
});
</script>