<?php
// Obtener lista de eventos para el selector
$eventos_sql = "SELECT id, titulo, fecha_inicio, tipo 
                FROM eventos 
                WHERE fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                ORDER BY fecha_inicio DESC";
$eventos_result = $conn->query($eventos_sql);
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">

<!-- Modal Medir Participación -->
<div class="modal fade" id="modalMedirParticipacion" tabindex="-1" aria-labelledby="modalMedirParticipacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="formMedirParticipacion">
        <input type="hidden" name="accion" value="medir_participacion">
        
        <div class="modal-header" style="background: linear-gradient(135deg, #a8c0ff 0%, #c4d5ff 100%); border: none;">
          <h5 class="modal-title" id="modalMedirParticipacionLabel" style="color: #2c3e50; font-weight: 600;">
            <i class="ti ti-chart-bar me-2"></i>
            Medir Participación en Evento
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body" style="background-color: #ffffff;">
          <div class="alert alert-info" style="background-color: #e8f4fd; border-color: #bee5eb; color: #0c5460;">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Información:</strong> Selecciona un evento y un rango de fechas para calcular las métricas de participación (total participantes, asistentes, tasa de asistencia).
          </div>

          <div class="row g-3">
            <!-- Selector de Evento -->
            <div class="col-md-12">
              <label for="evento_id" class="form-label" style="color: #495057; font-weight: 500;">
                Evento <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="evento_id" name="evento_id" required style="border-color: #a8c0ff;">
                <option value="">Selecciona un evento...</option>
                <?php
                if ($eventos_result && $eventos_result->num_rows > 0) {
                    while($evento = $eventos_result->fetch_assoc()) {
                        $fecha_formato = date('d/m/Y', strtotime($evento['fecha_inicio']));
                        echo '<option value="' . $evento['id'] . '">';
                        echo htmlspecialchars($evento['titulo']) . ' - ' . $fecha_formato;
                        echo ' (' . ucfirst(str_replace('_', ' ', $evento['tipo'])) . ')';
                        echo '</option>';
                    }
                } else {
                    echo '<option value="" disabled>No hay eventos disponibles</option>';
                }
                ?>
              </select>
              <small class="form-text text-muted">
                <i class="ti ti-calendar me-1"></i>
                Eventos de los últimos 12 meses
              </small>
            </div>

            <!-- Fecha de Inicio -->
            <div class="col-md-6">
              <label for="fecha_inicio" class="form-label" style="color: #495057; font-weight: 500;">
                Fecha de Inicio <span class="text-danger">*</span>
              </label>
              <input 
                type="date" 
                class="form-control" 
                id="fecha_inicio" 
                name="fecha_inicio" 
                required 
                style="border-color: #a8c0ff;"
                max="<?php echo date('Y-m-d'); ?>"
              >
              <small class="form-text text-muted">Inicio del período a analizar</small>
            </div>

            <!-- Fecha de Fin -->
            <div class="col-md-6">
              <label for="fecha_fin" class="form-label" style="color: #495057; font-weight: 500;">
                Fecha de Fin <span class="text-danger">*</span>
              </label>
              <input 
                type="date" 
                class="form-control" 
                id="fecha_fin" 
                name="fecha_fin" 
                required 
                style="border-color: #a8c0ff;"
                max="<?php echo date('Y-m-d'); ?>"
              >
              <small class="form-text text-muted">Fin del período a analizar</small>
            </div>

            <!-- Métricas a Calcular (informativo) -->
            <div class="col-md-12">
              <div class="card" style="background-color: #f8f9fa; border: 1px solid #e0e7ff; border-radius: 8px;">
                <div class="card-body p-3">
                  <h6 class="mb-2" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-clipboard-list me-2"></i>
                    Métricas que se calcularán:
                  </h6>
                  <ul class="mb-0" style="font-size: 0.9rem; color: #6c757d;">
                    <li>Total de participantes registrados</li>
                    <li>Número de asistentes confirmados</li>
                    <li>Número de no asistentes</li>
                    <li>Participantes confirmados pendientes</li>
                    <li>Porcentaje de asistencia</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Opciones Adicionales -->
            <div class="col-md-12">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="incluir_detalles" 
                  name="incluir_detalles"
                  style="cursor: pointer;"
                >
                <label class="form-check-label" for="incluir_detalles" style="color: #495057; cursor: pointer;">
                  Incluir detalles por familia y apoderado
                </label>
              </div>
              <small class="form-text text-muted ms-4">
                <i class="ti ti-info-circle me-1"></i>
                Genera un reporte detallado de participación por cada familia
              </small>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e0e7ff;">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="color: #6c757d;">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
            <i class="ti ti-calculator me-1"></i>
            Calcular Métricas
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('formMedirParticipacion');
  const fechaInicio = document.getElementById('fecha_inicio');
  const fechaFin = document.getElementById('fecha_fin');
  const eventoSelect = document.getElementById('evento_id');

  // Validación de fechas
  fechaInicio.addEventListener('change', function() {
    fechaFin.min = this.value;
    if (fechaFin.value && fechaFin.value < this.value) {
      fechaFin.value = this.value;
      Swal.fire({
        icon: 'info',
        title: 'Fecha Ajustada',
        text: 'La fecha de fin se ajustó automáticamente',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
    }
  });

  fechaFin.addEventListener('change', function() {
    if (fechaInicio.value && this.value < fechaInicio.value) {
      Swal.fire({
        icon: 'warning',
        title: 'Fecha Inválida',
        text: 'La fecha de fin no puede ser anterior a la fecha de inicio',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'Entendido'
      });
      this.value = fechaInicio.value;
    }
  });

  // Al seleccionar evento, autocompletar fechas
  eventoSelect.addEventListener('change', function() {
    if (this.value) {
      // Obtener fecha del evento del texto de la opción
      const selectedOption = this.options[this.selectedIndex].text;
      const fechaMatch = selectedOption.match(/(\d{2}\/\d{2}\/\d{4})/);
      
      if (fechaMatch) {
        const [dia, mes, anio] = fechaMatch[0].split('/');
        const fechaEvento = `${anio}-${mes}-${dia}`;
        
        // Establecer rango de 7 días antes y 7 días después del evento
        const fecha = new Date(fechaEvento);
        const fechaInicioProp = new Date(fecha);
        fechaInicioProp.setDate(fecha.getDate() - 7);
        const fechaFinProp = new Date(fecha);
        fechaFinProp.setDate(fecha.getDate() + 7);
        
        fechaInicio.value = fechaInicioProp.toISOString().split('T')[0];
        fechaFin.value = fechaFinProp.toISOString().split('T')[0];

        // Notificación de autocompletado
        Swal.fire({
          icon: 'success',
          title: 'Fechas Sugeridas',
          text: 'Se estableció un rango de ±7 días del evento',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        });
      }
    }
  });

  // Validación antes de enviar
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Validar evento
    if (!eventoSelect.value) {
      Swal.fire({
        icon: 'error',
        title: 'Campo Requerido',
        text: 'Por favor, selecciona un evento',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'Entendido'
      });
      eventoSelect.focus();
      return false;
    }

    // Validar fechas
    if (!fechaInicio.value || !fechaFin.value) {
      Swal.fire({
        icon: 'error',
        title: 'Campos Incompletos',
        text: 'Por favor, completa las fechas de inicio y fin',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'Entendido'
      });
      return false;
    }

    // Confirmar acción con SweetAlert
    Swal.fire({
      title: '¿Calcular Métricas?',
      html: `
        <div style="text-align: left; padding: 10px;">
          <p><strong>Evento:</strong> ${eventoSelect.options[eventoSelect.selectedIndex].text}</p>
          <p><strong>Período:</strong> ${fechaInicio.value} al ${fechaFin.value}</p>
          <p><strong>Detalles:</strong> ${document.getElementById('incluir_detalles').checked ? 'Sí' : 'No'}</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#667eea',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="ti ti-check me-1"></i> Sí, calcular',
      cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
      reverseButtons: true,
      showLoaderOnConfirm: true,
      preConfirm: () => {
        return new Promise((resolve) => {
          form.submit();
          resolve();
        });
      },
      allowOutsideClick: () => !Swal.isLoading()
    });
  });

  // Limpiar formulario al cerrar modal
  document.getElementById('modalMedirParticipacion').addEventListener('hidden.bs.modal', function() {
    form.reset();
  });

  // Mostrar mensaje de éxito/error si viene de POST
  <?php if(!empty($mensaje_sistema) && $_POST['accion'] === 'medir_participacion'): ?>
  const modalElement = document.getElementById('modalMedirParticipacion');
  const modal = bootstrap.Modal.getInstance(modalElement);
  if (modal) {
    modal.hide();
  }
  
  Swal.fire({
    icon: '<?php echo $tipo_mensaje === "error" ? "error" : "success"; ?>',
    title: '<?php echo $tipo_mensaje === "error" ? "Error" : "¡Éxito!"; ?>',
    text: '<?php echo addslashes($mensaje_sistema); ?>',
    confirmButtonColor: '#667eea',
    confirmButtonText: 'Aceptar',
    timer: <?php echo $tipo_mensaje === "error" ? "null" : "5000"; ?>,
    timerProgressBar: true
  });
  <?php endif; ?>
});
</script>

<style>
#modalMedirParticipacion .form-control:focus,
#modalMedirParticipacion .form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

#modalMedirParticipacion .modal-content {
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

#modalMedirParticipacion .btn-primary:hover {
  background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

#modalMedirParticipacion .form-check-input:checked {
  background-color: #667eea;
  border-color: #667eea;
}

/* SweetAlert2 Customization */
.swal2-popup {
  border-radius: 12px;
  font-family: 'Public Sans', sans-serif;
}

.swal2-title {
  color: #2c3e50;
  font-weight: 600;
}

.swal2-html-container {
  color: #495057;
}

.swal2-confirm {
  border-radius: 8px;
  font-weight: 500;
  padding: 10px 24px;
}

.swal2-cancel {
  border-radius: 8px;
  font-weight: 500;
  padding: 10px 24px;
}
</style>