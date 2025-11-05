<!-- Modal Generar Segmentación -->
<div class="modal fade" id="modalGenerarSegmentacion" tabindex="-1" aria-labelledby="modalGenerarSegmentacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8e6cf 0%, #dcedc8 100%);">
        <h5 class="modal-title" id="modalGenerarSegmentacionLabel">
          <i class="ti ti-chart-pie me-2"></i>Generar Segmentación de Apoderados
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGenerarSegmentacion" method="POST">
        <input type="hidden" name="accion" value="generar_segmentacion">
        
        <div class="modal-body">
          <div class="row">
            <!-- Criterio de Segmentación -->
            <div class="col-md-12 mb-3">
              <label for="criterio_segmentacion" class="form-label">
                <i class="ti ti-filter me-1"></i>Criterio de Segmentación
              </label>
              <select class="form-select" id="criterio_segmentacion" name="criterio_segmentacion" required>
                <option value="">Seleccione un criterio...</option>
                <option value="compromiso_participacion">Por Compromiso y Participación</option>
                <option value="nivel_socioeconomico">Por Nivel Socioeconómico</option>
                <option value="problematicos_colaboradores">Problemáticos vs Colaboradores</option>
              </select>
              <small class="text-muted">Seleccione cómo desea segmentar a los apoderados</small>
            </div>

            <!-- Preview de Segmentación -->
            <div class="col-md-12 mb-3">
              <div id="preview_segmentacion" class="p-3" style="background-color: #f8f9fa; border-radius: 8px; min-height: 100px; display: none;">
                <h6 class="mb-2">Vista Previa de Segmentación:</h6>
                <div id="contenido_preview"></div>
              </div>
            </div>

            <!-- Opciones de Formato -->
            <div class="col-md-6 mb-3">
              <label for="formato_reporte" class="form-label">
                <i class="ti ti-file-type-pdf me-1"></i>Formato de Salida
              </label>
              <select class="form-select" id="formato_reporte" name="formato">
                <option value="pantalla">Ver en Pantalla</option>
                <option value="pdf">Descargar PDF</option>
              </select>
            </div>

            <!-- Incluir Gráficos -->
            <div class="col-md-6 mb-3">
              <label class="form-label">
                <i class="ti ti-chart-bar me-1"></i>Opciones Adicionales
              </label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="incluir_graficos" name="incluir_graficos" checked>
                <label class="form-check-label" for="incluir_graficos">
                  Incluir gráficos estadísticos
                </label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="incluir_metricas" name="incluir_metricas" checked>
                <label class="form-check-label" for="incluir_metricas">
                  Incluir métricas detalladas
                </label>
              </div>
            </div>

            <!-- Observaciones -->
            <div class="col-md-12 mb-3">
              <label for="observaciones_segmentacion" class="form-label">
                <i class="ti ti-notes me-1"></i>Observaciones (opcional)
              </label>
              <textarea class="form-control" id="observaciones_segmentacion" name="observaciones" rows="3" 
                        placeholder="Agregue notas o comentarios sobre esta segmentación..."></textarea>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background-color: #fafafa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-chart-pie me-1"></i>Generar Segmentación
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
  // Cambio en criterio de segmentación - mostrar preview
  $('#criterio_segmentacion').on('change', function() {
    var criterio = $(this).val();
    
    if (criterio) {
      $('#preview_segmentacion').slideDown();
      cargarPreviewSegmentacion(criterio);
    } else {
      $('#preview_segmentacion').slideUp();
    }
  });
  
  // Función para cargar preview de segmentación
  function cargarPreviewSegmentacion(criterio) {
    $('#contenido_preview').html('<div class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Cargando preview...</div>');
    
    $.ajax({
      url: 'actions/preview_segmentacion.php',
      method: 'POST',
      data: { criterio: criterio },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          var html = '<div class="row">';
          
          response.data.forEach(function(segmento) {
            var clase = 'segmento-' + segmento.clase;
            html += '<div class="col-md-6 mb-2">';
            html += '<div class="metrica-item">';
            html += '<span class="segmento-preview ' + clase + '">' + segmento.nombre + '</span>';
            html += '<div class="mt-2"><strong>' + segmento.cantidad + '</strong> apoderados (' + segmento.porcentaje + '%)</div>';
            html += '</div></div>';
          });
          
          html += '</div>';
          $('#contenido_preview').html(html);
        } else {
          $('#contenido_preview').html('<div class="alert alert-warning">No se pudo cargar el preview</div>');
        }
      },
      error: function() {
        $('#contenido_preview').html('<div class="alert alert-danger">Error al cargar preview</div>');
      }
    });
  }
  
  // Validar y enviar formulario
  $('#formGenerarSegmentacion').on('submit', function(e) {
    e.preventDefault();
    
    var formato = $('#formato_reporte').val();
    var criterio = $('#criterio_segmentacion').val();
    var incluir_graficos = $('#incluir_graficos').is(':checked') ? '1' : '0';
    var incluir_metricas = $('#incluir_metricas').is(':checked') ? '1' : '0';
    var observaciones = $('#observaciones_segmentacion').val();
    
    if (formato === 'pantalla') {
      // Redirigir a reporte en pantalla
      window.location.href = 'reports/reporte_segmentacion.php?criterio=' + criterio + 
                             '&graficos=' + incluir_graficos + 
                             '&metricas=' + incluir_metricas + 
                             '&obs=' + encodeURIComponent(observaciones);
    } else if (formato === 'pdf') {
      // Abrir PDF en nueva ventana
      window.open('actions/generar_pdf_segmentacion.php?criterio=' + criterio + 
                  '&graficos=' + incluir_graficos + 
                  '&metricas=' + incluir_metricas + 
                  '&obs=' + encodeURIComponent(observaciones), '_blank');
    }
    
    $('#modalGenerarSegmentacion').modal('hide');
    return false;
  });
  
  // Reset al cerrar modal
  $('#modalGenerarSegmentacion').on('hidden.bs.modal', function() {
    $('#formGenerarSegmentacion')[0].reset();
    $('#preview_segmentacion').hide();
  });
});
</script>

<style>
  .segmento-preview {
    display: inline-block;
    padding: 8px 15px;
    margin: 5px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: white;
  }
  
  .segmento-colaborador { background: linear-gradient(45deg, #a8e6cf, #81c784); }
  .segmento-comprometido { background-color: #81d4fa; }
  .segmento-participativo { background-color: #ce93d8; }
  .segmento-regular { background-color: #b0bec5; }
  .segmento-problematico { background-color: #ef9a9a; }
  
  .metrica-item {
    padding: 10px;
    margin: 5px 0;
    border-left: 4px solid #a8e6cf;
    background-color: white;
    border-radius: 4px;
  }
</style>