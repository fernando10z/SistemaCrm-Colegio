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

<div class="modal fade" id="modalRegistrarResultado" tabindex="-1" aria-labelledby="modalRegistrarResultadoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #a8e6cf 0%, #dcedc1 100%);"> 
        <h5 class="modal-title" id="modalRegistrarResultadoLabel">
          <i class="ti ti-check"></i> Registrar Resultado
        </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
      </div>
      
      <form id="formRegistrarResultado" method="POST" action="actions/registrar_resultado.php">
        <input type="hidden" id="resultado_interaccion_id" name="resultado_interaccion_id"> 
        
        <div class="modal-body">
                    <div class="alert alert-info"> 
            <strong>Interacción:</strong> <span id="resultado_asunto"></span>
          </div>

                    <div class="mb-3">
            <label for="fecha_realizada" class="form-label">
              <i class="ti ti-calendar-check"></i> Fecha y Hora de Realización <span class="text-danger">*</span>
            </label>
            <input type="datetime-local" class="form-control" id="fecha_realizada" name="fecha_realizada" required>
          </div>

          <div class="mb-3">
            <label for="resultado" class="form-label">
              <i class="ti ti-flag"></i> Resultado <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="resultado" name="resultado" required>
              <option value="">Seleccionar resultado...</option>
              <option value="exitoso">Exitoso</option>
              <option value="sin_respuesta">Sin Respuesta</option>
              <option value="reagendar">Reagendar (requiere seguimiento)</option>
              <option value="no_interesado">No Interesado</option>
              <option value="convertido">Convertido</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="duracion_real" class="form-label">
              <i class="ti ti-hourglass"></i> Duración Real (min)
            </label>
            <input type="number" class="form-control" id="duracion_real" name="duracion_minutos" 
                   min="1" max="999" maxlength="3" oninput="this.value = this.value.slice(0, 3)">
          </div>

          <div class="mb-3">
            <label for="observaciones_resultado" class="form-label">
              <i class="ti ti-notes"></i> Observaciones <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="observaciones_resultado" name="resultado_observaciones" 
                      rows="4" required placeholder="Detalle el resultado de la interacción..."></textarea>
          </div>

          <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="resultado_requiere_seguimiento" name="resultado_requiere_seguimiento" value="1">
              <label class="form-check-label" for="resultado_requiere_seguimiento">¿Requiere seguimiento?</label>
          </div>
                    <div class="mb-3 hidden-section" id="div_fecha_seguimiento">
              <label for="resultado_fecha_proximo_seguimiento" class="form-label">
                  <i class="ti ti-calendar-event"></i> Fecha Próximo Seguimiento <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" id="resultado_fecha_proximo_seguimiento" name="resultado_fecha_proximo_seguimiento">
          </div>

        </div>
        
            <div class="modal-footer"> 
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-check"></i> Guardar Resultado
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Lógica para mostrar/ocultar fecha de seguimiento
    $('#resultado_requiere_seguimiento').change(function() {
        if ($(this).is(':checked')) {
            $('#div_fecha_seguimiento').slideDown();
            $('#resultado_fecha_proximo_seguimiento').prop('required', true);
        } else {
            $('#div_fecha_seguimiento').slideUp();
            $('#resultado_fecha_proximo_seguimiento').prop('required', false).val('');
        }
    });

    // Poner fecha mínima (hoy) para el seguimiento
    var today = new Date().toISOString().split('T')[0];
    $('#resultado_fecha_proximo_seguimiento').attr('min', today);

    // Llenar fecha_realizada con la fecha y hora actual por defecto
    function setDefaultDateTime() {
      const now = new Date();
      // Ajuste para zona horaria de Perú (UTC-5)
      // IMPORTANTE: Este ajuste SOLO afecta la hora que SE MUESTRA por defecto.
      // El valor enviado al servidor será la hora local del navegador.
      // El PHP ya tiene date_default_timezone_set('America/Lima'); para manejarlo correctamente.
      const localOffset = now.getTimezoneOffset() * 60000; // Offset local en milisegundos
      const peruOffset = -5 * 60 * 60000; // Offset Perú UTC-5 en milisegundos
      const peruTime = new Date(now.getTime() + localOffset + peruOffset);

      const year = peruTime.getFullYear();
      const month = (peruTime.getMonth() + 1).toString().padStart(2, '0');
      const day = peruTime.getDate().toString().padStart(2, '0');
      const hours = peruTime.getHours().toString().padStart(2, '0');
      const minutes = peruTime.getMinutes().toString().padStart(2, '0');
      const defaultDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
      $('#fecha_realizada').val(defaultDateTime);
    }

    // Llama a la función cuando se abre el modal
    $('#modalRegistrarResultado').on('show.bs.modal', function () {
        setDefaultDateTime();
        $('#resultado_requiere_seguimiento').prop('checked', false); 
        // Forzamos que el div se oculte al abrir
        $('#div_fecha_seguimiento').hide(); 
        $('#resultado_fecha_proximo_seguimiento').prop('required', false).val('');
        // Reseteamos validaciones si usas alguna librería extra
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').remove();
    });


    // Lógica del envío del formulario (AJAX)
    $('#formRegistrarResultado').submit(function(e) {
        e.preventDefault();
        
        // Simple validación visual (opcional pero recomendada)
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val() && $(this).is(':visible')) { // Solo valida campos visibles
                $(this).addClass('is-invalid');
                 // Evita añadir mensajes duplicados
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Este campo es obligatorio.</div>');
                }
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        if (!isValid) {
             Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor, rellena todos los campos marcados con *' });
             return; // Detiene el envío si no es válido
        }

        var formData = $(this).serialize();
        console.log("Datos enviados:", formData); // Para depurar

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            },
            success: function(response) {
                Swal.close(); 
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: response.message, 
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        $('#modalRegistrarResultado').modal('hide');
                        $('#formRegistrarResultado')[0].reset(); 
                        location.reload(); 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Guardar',
                        text: response.message, 
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close(); 
                console.error("Error AJAX:", xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo guardar el resultado. Verifica tu conexión o contacta al administrador.',
                    footer: 'Detalle: ' + xhr.status + ' ' + error,
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });
});
</script>