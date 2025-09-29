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

<!-- Modal Crear Campaña -->
<div class="modal fade" id="modalCrearCampana" tabindex="-1" aria-labelledby="modalCrearCampanaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearCampanaLabel">
          <i class="ti ti-plus me-2"></i>Crear Campaña para Exalumnos
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearCampana" method="POST" action="acciones/campanas_exalumnos/campanas_exalumnos.php">
        <div class="modal-body">
          <input type="hidden" name="accion" value="crear_campana">
          
          <!-- Información de la Campaña -->
          <div class="card mb-3">
            <div class="card-header">
              <h6 class="mb-0">Información de la Campaña</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="tipo_campana" class="form-label">
                    Tipo de Campaña <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="tipo_campana" name="tipo_campana" required>
                    <option value="">Seleccionar tipo</option>
                    <option value="networking">Networking</option>
                    <option value="evento">Evento</option>
                    <option value="agradecimiento">Agradecimiento</option>
                    <option value="boletin">Boletín Informativo</option>
                    <option value="reunion">Reunión de Promoción</option>
                    <option value="reconocimiento">Reconocimiento</option>
                    <option value="general">General</option>
                  </select>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="canal" class="form-label">
                    Canal de Comunicación <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="canal" name="canal" required>
                    <option value="">Seleccionar canal</option>
                    <option value="email">Email</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="sms">SMS</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="asunto_campana" class="form-label">
                  Asunto del Mensaje <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="asunto_campana" name="asunto" 
                       placeholder="Ej: Invitación Reunión de Promoción 2020" required maxlength="200">
              </div>
              
              <div class="mb-3">
                <label for="contenido_campana" class="form-label">
                  Contenido del Mensaje <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="contenido_campana" name="contenido" 
                          rows="5" placeholder="Escribe el contenido de tu mensaje aquí..." required></textarea>
                <small class="text-muted">Puedes usar {nombre}, {apellidos}, {promocion} como variables</small>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="plantilla_id" class="form-label">Plantilla (Opcional)</label>
                  <select class="form-select" id="plantilla_id" name="plantilla_id">
                    <option value="">Sin plantilla</option>
                    <?php
                    $plantillas_query = $conn->query("SELECT id, nombre, tipo FROM plantillas_mensajes WHERE activo = 1 ORDER BY nombre");
                    while($plantilla = $plantillas_query->fetch_assoc()) {
                        echo "<option value='" . $plantilla['id'] . "'>" . htmlspecialchars($plantilla['nombre']) . " (" . $plantilla['tipo'] . ")</option>";
                    }
                    ?>
                  </select>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="fecha_envio" class="form-label">
                    Fecha y Hora de Envío
                  </label>
                  <input type="datetime-local" class="form-control" id="fecha_envio" name="fecha_envio">
                  <small class="text-muted">Dejar vacío para envío inmediato</small>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Segmentación -->
          <div class="card mb-3">
            <div class="card-header">
              <h6 class="mb-0">Segmentación de Destinatarios</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Enviar a:</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="segmentacion" 
                         id="todos_exalumnos" value="todos" checked>
                  <label class="form-check-label" for="todos_exalumnos">
                    Todos los exalumnos con contacto activo
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="segmentacion" 
                         id="por_promocion" value="promocion">
                  <label class="form-check-label" for="por_promocion">
                    Filtrar por promoción
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="segmentacion" 
                         id="especificos" value="especificos">
                  <label class="form-check-label" for="especificos">
                    Seleccionar destinatarios específicos
                  </label>
                </div>
              </div>
              
              <div id="filtro_promocion" class="mb-3" style="display: none;">
                <label for="promociones_select" class="form-label">
                  Seleccionar Promociones
                </label>
                <select class="form-select" id="promociones_select" name="promociones[]" multiple size="5">
                  <?php
                  $promociones_query = $conn->query("SELECT DISTINCT promocion_egreso FROM exalumnos WHERE promocion_egreso IS NOT NULL ORDER BY promocion_egreso DESC");
                  while($promo = $promociones_query->fetch_assoc()) {
                      echo "<option value='" . htmlspecialchars($promo['promocion_egreso']) . "'>" . htmlspecialchars($promo['promocion_egreso']) . "</option>";
                  }
                  ?>
                </select>
                <small class="text-muted">Mantén presionado Ctrl para seleccionar múltiples</small>
              </div>
              
              <div id="filtro_especificos" class="mb-3" style="display: none;">
                <label for="exalumnos_select" class="form-label">
                  Seleccionar Exalumnos
                </label>
                <select class="form-select" id="exalumnos_select" name="exalumnos[]" multiple size="8">
                  <?php
                  $exalumnos_query = $conn->query("SELECT id, nombres, apellidos, codigo_exalumno, promocion_egreso, email FROM exalumnos WHERE estado_contacto = 'activo' ORDER BY apellidos, nombres");
                  while($ex = $exalumnos_query->fetch_assoc()) {
                      echo "<option value='" . $ex['id'] . "'>" . 
                           htmlspecialchars($ex['apellidos'] . ' ' . $ex['nombres']) . 
                           " - " . htmlspecialchars($ex['codigo_exalumno']) . 
                           " (" . htmlspecialchars($ex['promocion_egreso']) . ")" .
                           "</option>";
                  }
                  ?>
                </select>
                <small class="text-muted">Mantén presionado Ctrl para seleccionar múltiples</small>
              </div>
            </div>
          </div>
          
          <!-- Observaciones -->
          <div class="mb-3">
            <label for="observaciones_campana" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones_campana" name="observaciones" 
                      rows="2" placeholder="Notas adicionales sobre esta campaña..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-send me-1"></i>Crear y Enviar Campaña
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Manejar cambios en segmentación
    $('input[name="segmentacion"]').on('change', function() {
        $('#filtro_promocion').hide();
        $('#filtro_especificos').hide();
        
        if ($(this).val() === 'promocion') {
            $('#filtro_promocion').show();
        } else if ($(this).val() === 'especificos') {
            $('#filtro_especificos').show();
        }
    });
    
    // Cargar plantilla seleccionada
    $('#plantilla_id').on('change', function() {
        var plantillaId = $(this).val();
        if (plantillaId) {
            $.ajax({
                url: 'actions/obtener_plantilla.php',
                method: 'POST',
                data: { plantilla_id: plantillaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.data.asunto) {
                            $('#asunto_campana').val(response.data.asunto);
                        }
                        if (response.data.contenido) {
                            $('#contenido_campana').val(response.data.contenido);
                        }
                    }
                }
            });
        }
    });
    
    // Validación y confirmación con SweetAlert
    $('#formCrearCampana').on('submit', function(e) {
        e.preventDefault();
        
        var segmentacion = $('input[name="segmentacion"]:checked').val();
        var destinatarios = 0;
        
        if (segmentacion === 'promocion') {
            destinatarios = $('#promociones_select option:selected').length;
        } else if (segmentacion === 'especificos') {
            destinatarios = $('#exalumnos_select option:selected').length;
        }
        
        Swal.fire({
            title: '¿Confirmar envío de campaña?',
            text: segmentacion === 'todos' ? 'Se enviará a todos los exalumnos activos' : 
                  'Se enviará a ' + destinatarios + ' destinatarios seleccionados',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
});
</script>