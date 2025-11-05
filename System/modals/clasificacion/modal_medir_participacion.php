<!-- Modal Medir Participación -->
<div class="modal fade" id="modalMedirParticipacion" tabindex="-1" aria-labelledby="modalMedirParticipacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #d3f3ff 0%, #e6f7ff 100%);">
        <h5 class="modal-title" id="modalMedirParticipacionLabel">
          <i class="ti ti-activity me-2"></i>Medir Nivel de Participación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formMedirParticipacion" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="accion" value="medir_participacion">
        <input type="hidden" id="medir_apoderado_id" name="apoderado_id">
        
        <div class="modal-body">
          <!-- Información del Apoderado -->
          <div class="alert" style="background-color: #e6f7ff; border-left: 4px solid #40a9ff;">
            <h6 class="mb-2"><i class="ti ti-user me-1"></i>Apoderado Seleccionado:</h6>
            <div><strong id="medir_apoderado_nombre"></strong></div>
            <div class="mt-2" id="medir_metricas_actuales" style="font-size: 0.9rem; color: #666;"></div>
          </div>

          <div class="row">
            <!-- Nivel de Participación Manual -->
            <div class="col-md-6 mb-3">
              <label for="nivel_participacion_manual" class="form-label">
                <i class="ti ti-activity me-1"></i>Nivel de Participación
              </label>
              <select class="form-select" id="nivel_participacion_manual" name="nivel_participacion">
                <option value="">Calcular Automáticamente</option>
                <option value="muy_activo" style="background-color: #d4edda;">⭐ Muy Activo</option>
                <option value="activo" style="background-color: #d1ecf1;">✓ Activo</option>
                <option value="poco_activo" style="background-color: #fff3cd;">⚠ Poco Activo</option>
                <option value="inactivo" style="background-color: #f8d7da;">✗ Inactivo</option>
              </select>
              <small class="text-muted">Deje vacío para que el sistema calcule automáticamente</small>
            </div>

            <!-- Puntuación Calculada (solo visualización) -->
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <i class="ti ti-chart-bar me-1"></i>Puntuación de Actividad
              </label>
              <div id="puntuacion_participacion_preview" style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <div class="spinner-border spinner-border-sm text-info" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-muted">Calculando...</div>
              </div>
            </div>

            <!-- Algoritmo de Medición (Explicación) -->
            <div class="col-md-12 mb-3">
              <div class="card" style="background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%); border: none;">
                <div class="card-body p-3">
                  <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>Criterios de Medición Automática</h6>
                  <ul style="font-size: 0.85rem; margin-bottom: 0;">
                    <li><strong>Interacciones Mensuales (60%):</strong> Hasta 60 puntos (15 pts c/u, máx 4)</li>
                    <li><strong>Reuniones Presenciales (40%):</strong> Hasta 40 puntos (20 pts c/u, máx 2)</li>
                    <li><strong>Actividad Reciente (20%):</strong> Bonus por interacción ≤7 días</li>
                    <li><strong>Clasificación:</strong> ≥80 = Muy Activo | ≥60 = Activo | ≥30 = Poco Activo | <30 = Inactivo</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Métricas Detalladas (solo visualización) -->
            <div class="col-md-12 mb-3">
              <label class="form-label">
                <i class="ti ti-list-details me-1"></i>Métricas de Actividad
              </label>
              <div id="metricas_participacion_detalladas" style="background: #ffffff; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px;">
                <div class="text-center text-muted">
                  <div class="spinner-border spinner-border-sm" role="status"></div>
                  <p class="mt-2 mb-0">Cargando métricas...</p>
                </div>
              </div>
            </div>

            <!-- Timeline de Actividad -->
            <div class="col-md-12 mb-3">
              <label class="form-label">
                <i class="ti ti-timeline me-1"></i>Timeline de Actividad
              </label>
              <div id="timeline_actividad" style="background: #ffffff; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px;">
                <div class="text-center text-muted">
                  <div class="spinner-border spinner-border-sm" role="status"></div>
                  <p class="mt-2 mb-0">Cargando timeline...</p>
                </div>
              </div>
            </div>

            <!-- Observaciones -->
            <div class="col-md-12 mb-3">
              <label for="observaciones_participacion" class="form-label">
                <i class="ti ti-notes me-1"></i>Observaciones
              </label>
              <textarea class="form-control" id="observaciones_participacion" name="observaciones" rows="4" 
                        placeholder="Agregue notas sobre la participación del apoderado..."></textarea>
              <small class="text-muted">Estas observaciones se registrarán en el historial de interacciones</small>
            </div>

            <!-- Recomendaciones Automáticas -->
            <div class="col-md-12 mb-3">
              <div id="recomendaciones_participacion" style="display: none;">
                <div class="alert alert-info mb-0">
                  <h6 class="mb-2"><i class="ti ti-bulb me-1"></i>Recomendaciones del Sistema</h6>
                  <div id="texto_recomendaciones_participacion"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background-color: #fafafa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-info">
            <i class="ti ti-check me-1"></i>Guardar Medición
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .metrica-participacion-box {
    background: white;
    border-left: 4px solid #40a9ff;
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .metrica-participacion-label {
    font-size: 0.85rem;
    color: #666;
  }
  
  .metrica-participacion-valor {
    font-size: 1.1rem;
    font-weight: bold;
    color: #0050b3;
  }
  
  .puntuacion-participacion-display {
    font-size: 3rem;
    font-weight: bold;
    line-height: 1;
  }
  
  .participacion-muy-activo { color: #52c41a; }
  .participacion-activo { color: #1890ff; }
  .participacion-poco-activo { color: #faad14; }
  .participacion-inactivo { color: #f5222d; }
  
  .nivel-participacion-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 5px;
  }
  
  .badge-muy-activo { background-color: #d4edda; color: #155724; }
  .badge-activo { background-color: #d1ecf1; color: #0c5460; }
  .badge-poco-activo { background-color: #fff3cd; color: #856404; }
  .badge-inactivo { background-color: #f8d7da; color: #721c24; }
  
  .timeline-item {
    padding: 8px 12px;
    margin: 5px 0;
    background: #f8f9fa;
    border-left: 3px solid #40a9ff;
    border-radius: 4px;
    font-size: 0.85rem;
  }
  
  .timeline-fecha {
    font-weight: bold;
    color: #0050b3;
    margin-right: 10px;
  }
  
  .timeline-tipo {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    background-color: #e6f7ff;
    color: #0050b3;
    margin-right: 8px;
  }
</style>

<script>
$(document).ready(function() {
  // Al hacer click en botón medir participación
  $(document).on('click', '.btn-medir-participacion', function() {
    var id = $(this).data('id');
    var nombre = $(this).data('nombre');
    
    $('#medir_apoderado_id').val(id);
    $('#medir_apoderado_nombre').text(nombre);
    
    // Limpiar formulario
    $('#nivel_participacion_manual').val('');
    $('#observaciones_participacion').val('');
    $('#recomendaciones_participacion').hide();
    
    // Cargar métricas y cálculos
    cargarMetricasParticipacion(id);
    
    $('#modalMedirParticipacion').modal('show');
  });
  
  // Función para cargar métricas de participación
  function cargarMetricasParticipacion(apoderado_id) {
    $('#puntuacion_participacion_preview').html('<div class="spinner-border spinner-border-sm text-info" role="status"></div><div class="mt-2 text-muted">Calculando...</div>');
    $('#metricas_participacion_detalladas').html('<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mt-2 mb-0">Cargando métricas...</p></div>');
    $('#timeline_actividad').html('<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mt-2 mb-0">Cargando timeline...</p></div>');
    
    $.ajax({
      url: 'actions/calcular_metricas_participacion.php',
      method: 'POST',
      data: { apoderado_id: apoderado_id },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Mostrar puntuación calculada
          var puntuacion = parseFloat(response.puntuacion);
          var nivel = response.nivel;
          var clase_puntuacion = 'participacion-poco-activo';
          var clase_badge = 'badge-poco-activo';
          var texto_nivel = 'POCO ACTIVO';
          var emoji_nivel = '⚠';
          
          if (nivel === 'muy_activo') {
            clase_puntuacion = 'participacion-muy-activo';
            clase_badge = 'badge-muy-activo';
            texto_nivel = 'MUY ACTIVO';
            emoji_nivel = '⭐';
          } else if (nivel === 'activo') {
            clase_puntuacion = 'participacion-activo';
            clase_badge = 'badge-activo';
            texto_nivel = 'ACTIVO';
            emoji_nivel = '✓';
          } else if (nivel === 'inactivo') {
            clase_puntuacion = 'participacion-inactivo';
            clase_badge = 'badge-inactivo';
            texto_nivel = 'INACTIVO';
            emoji_nivel = '✗';
          }
          
          $('#puntuacion_participacion_preview').html(
            '<div class="puntuacion-participacion-display ' + clase_puntuacion + '">' + 
            puntuacion.toFixed(1) + '%</div>' +
            '<div class="nivel-participacion-badge ' + clase_badge + '">' + emoji_nivel + ' Nivel: ' + texto_nivel + '</div>'
          );
          
          // Mostrar métricas detalladas
          var metricasHtml = '';
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Interacciones del Mes</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.interacciones_mes + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Interacciones de la Semana</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.interacciones_semana + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Reuniones Presenciales</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.reuniones_presenciales + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Duración Promedio</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.duracion_promedio + ' min</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Última Interacción</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.ultima_interaccion + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-participacion-box">';
          metricasHtml += '<span class="metrica-participacion-label">Días Desde Última Actividad</span>';
          metricasHtml += '<span class="metrica-participacion-valor">' + response.metricas.dias_ultima_interaccion + '</span>';
          metricasHtml += '</div>';
          
          $('#metricas_participacion_detalladas').html(metricasHtml);
          
          // Mostrar métricas actuales en el alert
          $('#medir_metricas_actuales').html(
            '<i class="ti ti-chart-line me-1"></i>' +
            response.metricas.interacciones_mes + ' interacciones este mes | ' +
            response.metricas.reuniones_presenciales + ' reuniones | ' +
            'Nivel actual: <strong>' + texto_nivel + '</strong>'
          );
          
          // Mostrar timeline de actividad
          if (response.timeline && response.timeline.length > 0) {
            var timelineHtml = '';
            response.timeline.forEach(function(item) {
              timelineHtml += '<div class="timeline-item">';
              timelineHtml += '<span class="timeline-fecha">' + item.fecha + '</span>';
              timelineHtml += '<span class="timeline-tipo">' + item.tipo + '</span>';
              timelineHtml += '<span>' + item.descripcion + '</span>';
              timelineHtml += '</div>';
            });
            $('#timeline_actividad').html(timelineHtml);
          } else {
            $('#timeline_actividad').html('<div class="alert alert-warning mb-0">No hay actividad reciente registrada</div>');
          }
          
          // Mostrar recomendaciones
          if (response.recomendaciones && response.recomendaciones.length > 0) {
            var recomHtml = '<ul class="mb-0">';
            response.recomendaciones.forEach(function(rec) {
              recomHtml += '<li>' + rec + '</li>';
            });
            recomHtml += '</ul>';
            
            $('#texto_recomendaciones_participacion').html(recomHtml);
            $('#recomendaciones_participacion').slideDown();
          }
          
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo calcular la puntuación de participación',
            confirmButtonText: 'Entendido'
          });
          
          $('#puntuacion_participacion_preview').html('<div class="text-danger">Error al calcular puntuación</div>');
          $('#metricas_participacion_detalladas').html('<div class="alert alert-danger mb-0">Error al cargar métricas</div>');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error de Conexión',
          text: 'No se pudo conectar con el servidor',
          confirmButtonText: 'Entendido'
        });
        
        $('#puntuacion_participacion_preview').html('<div class="text-danger">Error de conexión</div>');
        $('#metricas_participacion_detalladas').html('<div class="alert alert-danger mb-0">Error al cargar métricas</div>');
      }
    });
  }
  
  // Cambio en nivel manual - actualizar UI
  $('#nivel_participacion_manual').on('change', function() {
    var nivelManual = $(this).val();
    
    if (nivelManual) {
      var emoji = '⚠';
      if (nivelManual === 'muy_activo') emoji = '⭐';
      else if (nivelManual === 'activo') emoji = '✓';
      else if (nivelManual === 'inactivo') emoji = '✗';
      
      $('#puntuacion_participacion_preview').find('.nivel-participacion-badge').html(emoji + ' Nivel Manual: <strong>' + nivelManual.toUpperCase().replace('_', ' ') + '</strong>');
    }
  });
  
  // Validación y envío del formulario con SweetAlert2
  $('#formMedirParticipacion').on('submit', function(e) {
    e.preventDefault();
    
    var apoderado_id = $('#medir_apoderado_id').val();
    var apoderado_nombre = $('#medir_apoderado_nombre').text();
    var nivel = $('#nivel_participacion_manual').val();
    var observaciones = $('#observaciones_participacion').val();
    
    if (!apoderado_id) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se ha seleccionado un apoderado',
        confirmButtonText: 'Entendido'
      });
      return false;
    }
    
    // Confirmación con SweetAlert2
    Swal.fire({
      title: '¿Confirmar Medición?',
      html: '<p>Se guardará la medición de participación para:</p>' +
            '<p><strong>' + apoderado_nombre + '</strong></p>' +
            '<p class="text-muted">Nivel: ' + (nivel ? nivel.toUpperCase().replace('_', ' ') : 'AUTOMÁTICO') + '</p>',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-check me-1"></i> Sí, Guardar',
      cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
      reverseButtons: true,
      customClass: {
        confirmButton: 'btn btn-info me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Guardando Medición',
          html: 'Por favor espere...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Enviar formulario por AJAX
        $.ajax({
          url: $('#formMedirParticipacion').attr('action'),
          method: 'POST',
          data: $('#formMedirParticipacion').serialize(),
          success: function(response) {
            // Cerrar modal
            $('#modalMedirParticipacion').modal('hide');
            
            // Mostrar mensaje de éxito
            Swal.fire({
              icon: 'success',
              title: '¡Medición Guardada!',
              html: '<p>La medición de participación se ha registrado correctamente.</p>' +
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
              html: '<p>No se pudo guardar la medición.</p>' +
                    '<p class="text-muted">' + error + '</p>',
              confirmButtonText: 'Entendido'
            });
          }
        });
      }
    });
    
    return false;
  });
  
  // Limpiar modal al cerrar
  $('#modalMedirParticipacion').on('hidden.bs.modal', function() {
    $('#formMedirParticipacion')[0].reset();
    $('#medir_apoderado_id').val('');
    $('#medir_apoderado_nombre').text('');
    $('#medir_metricas_actuales').html('');
    $('#recomendaciones_participacion').hide();
  });
});
</script>