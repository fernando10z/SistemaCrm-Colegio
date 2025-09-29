<!-- Modal Enviar Comunicación -->
<div class="modal fade" id="modalEnviarComunicacion" tabindex="-1" aria-labelledby="modalEnviarComunicacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEnviarComunicacionLabel">
          <i class="ti ti-send me-2"></i>Enviar Comunicación Rápida
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEnviarComunicacion" method="POST" action="acciones/campanas_exalumnos/campanas_exalumnos.php">
        <div class="modal-body">
          <input type="hidden" name="accion" value="enviar_comunicacion">
          
          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Comunicación Rápida:</strong> Envía mensajes inmediatos sin crear una campaña completa.
          </div>
          
          <!-- Tipo y Canal -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="tipo_mensaje" class="form-label">
                Tipo de Mensaje <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="tipo_mensaje" name="tipo_mensaje" required>
                <option value="">Seleccionar tipo</option>
                <option value="invitacion_evento">Invitación a Evento</option>
                <option value="agradecimiento">Mensaje de Agradecimiento</option>
                <option value="felicitacion">Felicitación</option>
                <option value="informativo">Informativo</option>
                <option value="recordatorio">Recordatorio</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="canal_comunicacion" class="form-label">
                Canal <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="canal_comunicacion" name="canal" required>
                <option value="">Seleccionar canal</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="sms">SMS</option>
              </select>
            </div>
          </div>
          
          <!-- Destinatarios -->
          <div class="card mb-3">
            <div class="card-header">
              <h6 class="mb-0">Destinatarios</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Seleccionar destinatarios:</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="tipo_destinatario" 
                         id="dest_promocion" value="promocion" checked>
                  <label class="form-check-label" for="dest_promocion">
                    Por promoción
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="tipo_destinatario" 
                         id="dest_individual" value="individual">
                  <label class="form-check-label" for="dest_individual">
                    Individual
                  </label>
                </div>
              </div>
              
              <div id="select_promocion_comm" class="mb-3">
                <label for="promocion_comunicacion" class="form-label">
                  Promoción <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="promocion_comunicacion" name="promocion">
                  <option value="">Seleccionar promoción</option>
                  <?php
                  $promo_query = $conn->query("SELECT DISTINCT promocion_egreso, COUNT(*) as total FROM exalumnos WHERE promocion_egreso IS NOT NULL AND estado_contacto = 'activo' GROUP BY promocion_egreso ORDER BY promocion_egreso DESC");
                  while($p = $promo_query->fetch_assoc()) {
                      echo "<option value='" . htmlspecialchars($p['promocion_egreso']) . "'>" . 
                           htmlspecialchars($p['promocion_egreso']) . " (" . $p['total'] . " exalumnos)</option>";
                  }
                  ?>
                </select>
              </div>
              
              <div id="select_individual_comm" class="mb-3" style="display: none;">
                <label for="exalumno_individual" class="form-label">
                  Exalumno <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="exalumno_individual" name="exalumno_id">
                  <option value="">Seleccionar exalumno</option>
                  <?php
                  $ex_query = $conn->query("SELECT id, nombres, apellidos, codigo_exalumno, promocion_egreso, email, telefono FROM exalumnos WHERE estado_contacto = 'activo' ORDER BY apellidos, nombres");
                  while($ex = $ex_query->fetch_assoc()) {
                      $contacto = $ex['email'] ? $ex['email'] : ($ex['telefono'] ? $ex['telefono'] : 'Sin contacto');
                      echo "<option value='" . $ex['id'] . "'>" . 
                           htmlspecialchars($ex['apellidos'] . ' ' . $ex['nombres']) . 
                           " - " . htmlspecialchars($ex['codigo_exalumno']) . 
                           " (" . htmlspecialchars($ex['promocion_egreso']) . ") - " .
                           htmlspecialchars($contacto) .
                           "</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Mensaje -->
          <div class="mb-3">
            <label for="asunto_comunicacion" class="form-label">
              Asunto <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="asunto_comunicacion" name="asunto" 
                   placeholder="Asunto del mensaje" required maxlength="200">
          </div>
          
          <div class="mb-3">
            <label for="mensaje_comunicacion" class="form-label">
              Mensaje <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="mensaje_comunicacion" name="mensaje" 
                      rows="6" placeholder="Escribe tu mensaje aquí..." required></textarea>
            <small class="text-muted">Variables disponibles: {nombre}, {apellidos}, {promocion}</small>
          </div>
          
          <!-- Plantillas rápidas -->
          <div class="mb-3">
            <label for="plantilla_rapida" class="form-label">Plantilla Rápida</label>
            <select class="form-select" id="plantilla_rapida" name="plantilla_rapida">
              <option value="">Seleccionar plantilla...</option>
              <?php
              $plantillas = $conn->query("SELECT id, nombre, asunto, contenido FROM plantillas_mensajes WHERE activo = 1 ORDER BY nombre");
              while($pl = $plantillas->fetch_assoc()) {
                  echo "<option value='" . $pl['id'] . "' data-asunto='" . htmlspecialchars($pl['asunto']) . "' data-contenido='" . htmlspecialchars($pl['contenido']) . "'>" . 
                       htmlspecialchars($pl['nombre']) . "</option>";
              }
              ?>
            </select>
          </div>
          
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="envio_inmediato" name="envio_inmediato" value="1" checked>
            <label class="form-check-label" for="envio_inmediato">
              Enviar inmediatamente
            </label>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-send me-1"></i>Enviar Comunicación
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Cambiar entre promoción e individual
    $('input[name="tipo_destinatario"]').on('change', function() {
        if ($(this).val() === 'promocion') {
            $('#select_promocion_comm').show();
            $('#select_individual_comm').hide();
            $('#promocion_comunicacion').prop('required', true);
            $('#exalumno_individual').prop('required', false);
        } else {
            $('#select_promocion_comm').hide();
            $('#select_individual_comm').show();
            $('#promocion_comunicacion').prop('required', false);
            $('#exalumno_individual').prop('required', true);
        }
    });
    
    // Cargar plantilla rápida
    $('#plantilla_rapida').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var asunto = selectedOption.data('asunto');
        var contenido = selectedOption.data('contenido');
        
        if (asunto) {
            $('#asunto_comunicacion').val(asunto);
        }
        if (contenido) {
            $('#mensaje_comunicacion').val(contenido);
        }
    });
    
    // Validación y confirmación con SweetAlert
    $('#formEnviarComunicacion').on('submit', function(e) {
        e.preventDefault();
        
        var tipoDestinatario = $('input[name="tipo_destinatario"]:checked').val();
        var destinatario = '';
        
        if (tipoDestinatario === 'promocion') {
            destinatario = $('#promocion_comunicacion option:selected').text();
        } else {
            destinatario = $('#exalumno_individual option:selected').text();
        }
        
        Swal.fire({
            title: '¿Enviar comunicación?',
            html: '<strong>Destinatario:</strong> ' + destinatario + '<br><strong>Canal:</strong> ' + $('#canal_comunicacion option:selected').text(),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
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