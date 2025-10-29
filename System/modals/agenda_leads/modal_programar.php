<?php
// Obtener tipos de interacción
$sql_tipos = "SELECT id, nombre, icono, color FROM tipos_interaccion WHERE activo = 1 ORDER BY nombre ASC";
$result_tipos = $conn->query($sql_tipos);

// Obtener usuarios activos
$sql_usuarios = "SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre ASC";
$result_usuarios = $conn->query($sql_usuarios);

// Obtener leads activos
$sql_leads = "SELECT id, CONCAT(nombres_estudiante, ' ', apellidos_estudiante) as nombre_completo, 
              CONCAT(nombres_contacto, ' ', apellidos_contacto) as contacto,
              telefono, email 
              FROM leads WHERE activo = 1 ORDER BY nombres_estudiante ASC";
$result_leads = $conn->query($sql_leads);
?>

<!-- Modal Programar Interacción -->
<div class="modal fade" id="modalProgramarInteraccion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8e6cf 0%, #dcedc1 100%);">
        <h5 class="modal-title">
          <i class="ti ti-calendar-plus"></i> Programar Nueva Interacción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <form id="formProgramarInteraccion">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo de Interacción <span class="text-danger">*</span></label>
              <select class="form-select" name="tipo_interaccion_id" required>
                <option value="">Seleccionar...</option>
                <?php while($tipo = $result_tipos->fetch_assoc()): ?>
                <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nombre']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Lead/Estudiante <span class="text-danger">*</span></label>
              <select class="form-select" name="lead_id" id="lead_id" required>
                <option value="">Seleccionar...</option>
                <?php while($lead = $result_leads->fetch_assoc()): ?>
                <option value="<?= $lead['id'] ?>" 
                        data-telefono="<?= htmlspecialchars($lead['telefono'] ?? '') ?>" 
                        data-email="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                  <?= htmlspecialchars($lead['nombre_completo']) ?>
                </option>
                <?php endwhile; ?>
              </select>
              <small class="text-muted" id="info_lead"></small>
            </div>

            <div class="col-md-12 mb-3">
              <label class="form-label">Asunto <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="asunto" required maxlength="255">
            </div>

            <div class="col-md-12 mb-3">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="3"></textarea>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Fecha <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="fecha_programada" id="fecha_programada" required>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Hora <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="hora_programada" required>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Duración (min)</label>
              <input type="number" class="form-control" name="duracion_minutos" value="30" min="5" max="999" maxlength="3">
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Responsable <span class="text-danger">*</span></label>
              <select class="form-select" name="usuario_id" required>
                <option value="">Seleccionar...</option>
                <?php while($usuario = $result_usuarios->fetch_assoc()): ?>
                <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombre_completo']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Estado</label>
              <select class="form-select" name="estado">
                <option value="programado" selected>Programado</option>
                <option value="reagendado">Reagendado</option>
              </select>
            </div>

            <div class="col-md-12 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="requiere_seguimiento" name="requiere_seguimiento" value="1">
                <label class="form-check-label">Requiere seguimiento posterior</label>
              </div>
            </div>

            <div class="col-md-6 mb-3" id="div_fecha_seguimiento" style="display:none;">
              <label class="form-label">Fecha Próximo Seguimiento</label>
              <input type="date" class="form-control" name="fecha_proximo_seguimiento" id="fecha_proximo_seguimiento">
            </div>

            <div class="col-md-12 mb-3">
              <label class="form-label">Observaciones</label>
              <textarea class="form-control" name="observaciones" rows="2"></textarea>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Programar Interacción</button>
        </div>
      </form>
    </div>
  </div>
</div>