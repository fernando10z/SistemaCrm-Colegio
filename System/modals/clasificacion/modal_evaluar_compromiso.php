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


<!-- Modal Evaluar Compromiso -->
<div class="modal fade" id="modalEvaluarCompromiso" tabindex="-1" aria-labelledby="modalEvaluarCompromisoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #ffd3d3 0%, #ffe6e6 100%);">
        <h5 class="modal-title" id="modalEvaluarCompromisoLabel">
          <i class="ti ti-heart me-2"></i>Evaluar Nivel de Compromiso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formEvaluarCompromiso" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="accion" value="evaluar_compromiso">
        <input type="hidden" id="evaluar_apoderado_id" name="apoderado_id">
        
        <div class="modal-body">
          <!-- Información del Apoderado -->
          <div class="alert" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
            <h6 class="mb-2"><i class="ti ti-user me-1"></i>Apoderado Seleccionado:</h6>
            <div><strong id="evaluar_apoderado_nombre"></strong></div>
            <div class="mt-2" id="evaluar_metricas_actuales" style="font-size: 0.9rem; color: #666;"></div>
          </div>

          <div class="row">
            <!-- Nivel de Compromiso Manual -->
            <div class="col-md-6 mb-3">
              <label for="nivel_compromiso_manual" class="form-label">
                <i class="ti ti-heart me-1"></i>Nivel de Compromiso
              </label>
              <select class="form-select" id="nivel_compromiso_manual" name="nivel_compromiso">
                <option value="">Calcular Automáticamente</option>
                <option value="alto" style="background-color: #d4edda;">🟢 Alto</option>
                <option value="medio" style="background-color: #fff3cd;">🟡 Medio</option>
                <option value="bajo" style="background-color: #f8d7da;">🔴 Bajo</option>
              </select>
              <small class="text-muted">Deje vacío para que el sistema calcule automáticamente</small>
            </div>

            <!-- Puntuación Calculada (solo visualización) -->
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <i class="ti ti-chart-bar me-1"></i>Puntuación Calculada
              </label>
              <div id="puntuacion_preview" style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-muted">Calculando...</div>
              </div>
            </div>

            <!-- Algoritmo de Evaluación (Explicación) -->
            <div class="col-md-12 mb-3">
              <div class="card" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: none;">
                <div class="card-body p-3">
                  <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>Criterios de Evaluación Automática</h6>
                  <ul style="font-size: 0.85rem; margin-bottom: 0;">
                    <li><strong>Tasa de Éxito (40%):</strong> Porcentaje de interacciones exitosas</li>
                    <li><strong>Actividad Reciente (30%):</strong> Interacciones en los últimos 90 días</li>
                    <li><strong>Penalizaciones:</strong> -10 puntos por cada seguimiento vencido</li>
                    <li><strong>Clasificación:</strong> ≥70 = Alto | ≥40 = Medio | <40 = Bajo</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Métricas Detalladas (solo visualización) -->
            <div class="col-md-12 mb-3">
              <label class="form-label">
                <i class="ti ti-list-details me-1"></i>Métricas Detalladas
              </label>
              <div id="metricas_detalladas" style="background: #ffffff; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px;">
                <div class="text-center text-muted">
                  <div class="spinner-border spinner-border-sm" role="status"></div>
                  <p class="mt-2 mb-0">Cargando métricas...</p>
                </div>
              </div>
            </div>

            <!-- Observaciones -->
            <div class="col-md-12 mb-3">
              <label for="observaciones_compromiso" class="form-label">
                <i class="ti ti-notes me-1"></i>Observaciones
              </label>
              <textarea class="form-control" id="observaciones_compromiso" name="observaciones" rows="4" 
                        placeholder="Agregue notas, comentarios o justificación de la evaluación..."></textarea>
              <small class="text-muted">Estas observaciones se registrarán en el historial de interacciones</small>
            </div>

            <!-- Recomendaciones Automáticas -->
            <div class="col-md-12 mb-3">
              <div id="recomendaciones_sistema" style="display: none;">
                <div class="alert alert-info mb-0">
                  <h6 class="mb-2"><i class="ti ti-bulb me-1"></i>Recomendaciones del Sistema</h6>
                  <div id="texto_recomendaciones"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background-color: #fafafa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-check me-1"></i>Guardar Evaluación
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .metrica-box {
    background: white;
    border-left: 4px solid #a8e6cf;
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .metrica-label {
    font-size: 0.85rem;
    color: #666;
  }
  
  .metrica-valor {
    font-size: 1.1rem;
    font-weight: bold;
    color: #2d3748;
  }
  
  .puntuacion-display {
    font-size: 3rem;
    font-weight: bold;
    line-height: 1;
  }
  
  .puntuacion-alto { color: #28a745; }
  .puntuacion-medio { color: #ffc107; }
  .puntuacion-bajo { color: #dc3545; }
  
  .nivel-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 5px;
  }
  
  .badge-alto { background-color: #d4edda; color: #155724; }
  .badge-medio { background-color: #fff3cd; color: #856404; }
  .badge-bajo { background-color: #f8d7da; color: #721c24; }
  
  /* Estilos personalizados para SweetAlert2 */
  .swal2-popup {
    font-family: 'Public Sans', sans-serif;
    border-radius: 15px;
  }
  
  .swal2-icon.swal2-success {
    border-color: #a8e6cf;
    color: #a8e6cf;
  }
  
  .swal2-icon.swal2-success [class^='swal2-success-line'] {
    background-color: #a8e6cf;
  }
  
  .swal2-icon.swal2-error {
    border-color: #ef9a9a;
    color: #ef9a9a;
  }
  
  .swal2-confirm {
    background: linear-gradient(135deg, #a8e6cf 0%, #81c784 100%) !important;
    border: none !important;
    border-radius: 8px !important;
  }
  
  .swal2-cancel {
    background-color: #e2e8f0 !important;
    color: #2d3748 !important;
    border: none !important;
    border-radius: 8px !important;
  }
</style>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Al hacer click en botón evaluar compromiso
  $(document).on('click', '.btn-evaluar-compromiso', function() {
    var id = $(this).data('id');
    var nombre = $(this).data('nombre');
    
    $('#evaluar_apoderado_id').val(id);
    $('#evaluar_apoderado_nombre').text(nombre);
    
    // Limpiar formulario
    $('#nivel_compromiso_manual').val('');
    $('#observaciones_compromiso').val('');
    $('#recomendaciones_sistema').hide();
    
    // Cargar métricas y cálculos
    cargarMetricasCompromiso(id);
    
    $('#modalEvaluarCompromiso').modal('show');
  });
  
  // Función para cargar métricas de compromiso
  function cargarMetricasCompromiso(apoderado_id) {
    $('#puntuacion_preview').html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div><div class="mt-2 text-muted">Calculando...</div>');
    $('#metricas_detalladas').html('<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mt-2 mb-0">Cargando métricas...</p></div>');
    
    $.ajax({
      url: 'actions/calcular_metricas_compromiso.php',
      method: 'POST',
      data: { apoderado_id: apoderado_id },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Mostrar puntuación calculada
          var puntuacion = parseFloat(response.puntuacion);
          var nivel = response.nivel;
          var clase_puntuacion = 'puntuacion-medio';
          var clase_badge = 'badge-medio';
          var texto_nivel = 'MEDIO';
          var emoji_nivel = '🟡';
          
          if (nivel === 'alto') {
            clase_puntuacion = 'puntuacion-alto';
            clase_badge = 'badge-alto';
            texto_nivel = 'ALTO';
            emoji_nivel = '🟢';
          } else if (nivel === 'bajo') {
            clase_puntuacion = 'puntuacion-bajo';
            clase_badge = 'badge-bajo';
            texto_nivel = 'BAJO';
            emoji_nivel = '🔴';
          }
          
          $('#puntuacion_preview').html(
            '<div class="puntuacion-display ' + clase_puntuacion + '">' + 
            puntuacion.toFixed(1) + '%</div>' +
            '<div class="nivel-badge ' + clase_badge + '">' + emoji_nivel + ' Nivel: ' + texto_nivel + '</div>'
          );
          
          // Mostrar métricas detalladas
          var metricasHtml = '';
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Total de Interacciones</span>';
          metricasHtml += '<span class="metrica-valor">' + response.metricas.total_interacciones + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Interacciones Exitosas</span>';
          metricasHtml += '<span class="metrica-valor">' + response.metricas.interacciones_exitosas + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Tasa de Éxito</span>';
          metricasHtml += '<span class="metrica-valor">' + response.metricas.tasa_exito + '%</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Interacciones Recientes (90 días)</span>';
          metricasHtml += '<span class="metrica-valor">' + response.metricas.interacciones_recientes + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Sin Respuesta</span>';
          metricasHtml += '<span class="metrica-valor">' + response.metricas.sin_respuesta + '</span>';
          metricasHtml += '</div>';
          
          metricasHtml += '<div class="metrica-box">';
          metricasHtml += '<span class="metrica-label">Seguimientos Vencidos</span>';
          metricasHtml += '<span class="metrica-valor" style="color: #dc3545;">' + response.metricas.seguimientos_vencidos + '</span>';
          metricasHtml += '</div>';
          
          $('#metricas_detalladas').html(metricasHtml);
          
          // Mostrar métricas actuales en el alert
          $('#evaluar_metricas_actuales').html(
            '<i class="ti ti-chart-line me-1"></i>' +
            response.metricas.total_interacciones + ' interacciones totales | ' +
            response.metricas.interacciones_exitosas + ' exitosas (' + response.metricas.tasa_exito + '%) | ' +
            'Nivel actual: <strong>' + texto_nivel + '</strong>'
          );
          
          // Mostrar recomendaciones
          if (response.recomendaciones && response.recomendaciones.length > 0) {
            var recomHtml = '<ul class="mb-0">';
            response.recomendaciones.forEach(function(rec) {
              recomHtml += '<li>' + rec + '</li>';
            });
            recomHtml += '</ul>';
            
            $('#texto_recomendaciones').html(recomHtml);
            $('#recomendaciones_sistema').slideDown();
          }
          
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo calcular la puntuación',
            confirmButtonText: 'Entendido'
          });
          
          $('#puntuacion_preview').html('<div class="text-danger">Error al calcular puntuación</div>');
          $('#metricas_detalladas').html('<div class="alert alert-danger mb-0">Error al cargar métricas</div>');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error de Conexión',
          text: 'No se pudo conectar con el servidor',
          confirmButtonText: 'Entendido'
        });
        
        $('#puntuacion_preview').html('<div class="text-danger">Error de conexión</div>');
        $('#metricas_detalladas').html('<div class="alert alert-danger mb-0">Error al cargar métricas</div>');
      }
    });
  }
  
  // Cambio en nivel manual - actualizar UI
  $('#nivel_compromiso_manual').on('change', function() {
    var nivelManual = $(this).val();
    
    if (nivelManual) {
      var emoji = nivelManual === 'alto' ? '🟢' : (nivelManual === 'medio' ? '🟡' : '🔴');
      $('#puntuacion_preview').find('.nivel-badge').html(emoji + ' Nivel Manual: <strong>' + nivelManual.toUpperCase() + '</strong>');
    }
  });
  
  // Validación y envío del formulario con SweetAlert2
  $('#formEvaluarCompromiso').on('submit', function(e) {
    e.preventDefault();
    
    var apoderado_id = $('#evaluar_apoderado_id').val();
    var apoderado_nombre = $('#evaluar_apoderado_nombre').text();
    var nivel = $('#nivel_compromiso_manual').val();
    var observaciones = $('#observaciones_compromiso').val();
    
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
      title: '¿Confirmar Evaluación?',
      html: '<p>Se guardará la evaluación de compromiso para:</p>' +
            '<p><strong>' + apoderado_nombre + '</strong></p>' +
            '<p class="text-muted">Nivel: ' + (nivel ? nivel.toUpperCase() : 'AUTOMÁTICO') + '</p>',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-check me-1"></i> Sí, Guardar',
      cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
      reverseButtons: true,
      customClass: {
        confirmButton: 'btn btn-success me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Guardando Evaluación',
          html: 'Por favor espere...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Enviar formulario por AJAX
        $.ajax({
          url: $('#formEvaluarCompromiso').attr('action'),
          method: 'POST',
          data: $('#formEvaluarCompromiso').serialize(),
          success: function(response) {
            // Cerrar modal
            $('#modalEvaluarCompromiso').modal('hide');
            
            // Mostrar mensaje de éxito
            Swal.fire({
              icon: 'success',
              title: '¡Evaluación Guardada!',
              html: '<p>La evaluación de compromiso se ha registrado correctamente.</p>' +
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
              html: '<p>No se pudo guardar la evaluación.</p>' +
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
  $('#modalEvaluarCompromiso').on('hidden.bs.modal', function() {
    $('#formEvaluarCompromiso')[0].reset();
    $('#evaluar_apoderado_id').val('');
    $('#evaluar_apoderado_nombre').text('');
    $('#evaluar_metricas_actuales').html('');
    $('#recomendaciones_sistema').hide();
  });
});
</script>