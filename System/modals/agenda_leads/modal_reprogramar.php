<!-- Modal Reprogramar Interacción -->
<div class="modal fade" id="modalReprogramar" tabindex="-1" aria-labelledby="modalReprogramarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd6585 100%);">
        <h5 class="modal-title" id="modalReprogramarLabel">
          <i class="ti ti-calendar-time"></i> Reprogramar Interacción
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formReprogramar" method="POST" action="actions/reprogramar_interaccion.php">
        <input type="hidden" id="reprogramar_interaccion_id" name="interaccion_id">
        
        <div class="modal-body">
          <div class="alert alert-info">
            <strong>Interacción:</strong> <span id="reprogramar_asunto_actual"></span><br>
            <strong>Fecha actual:</strong> <span id="reprogramar_fecha_actual"></span>
          </div>

          <div class="mb-3">
            <label for="reprogramar_nueva_fecha" class="form-label">
              <i class="ti ti-calendar"></i> Nueva Fecha <span class="text-danger">*</span>
            </label>
            <input type="date" class="form-control" id="reprogramar_nueva_fecha" name="nueva_fecha" required>
          </div>

          <div class="mb-3">
            <label for="reprogramar_nueva_hora" class="form-label">
              <i class="ti ti-clock"></i> Nueva Hora <span class="text-danger">*</span>
            </label>
            <input type="time" class="form-control" id="reprogramar_nueva_hora" name="nueva_hora" required>
          </div>

          <div class="mb-3">
            <label for="reprogramar_motivo" class="form-label">
              <i class="ti ti-message"></i> Motivo del Cambio <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="reprogramar_motivo" name="motivo" rows="3" required 
                      placeholder="Explique el motivo de la reprogramación..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="ti ti-check"></i> Reprogramar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$('#formReprogramar').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Reprogramado!',
                    text: 'La interacción ha sido reprogramada exitosamente',
                    confirmButtonColor: '#ffc107',
                    timer: 2000
                }).then(() => {
                    $('#modalReprogramar').modal('hide');
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo completar la operación',
                confirmButtonColor: '#dc3545'
            });
        }
    });
});
</script>