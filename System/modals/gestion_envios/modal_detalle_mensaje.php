<!-- Modal Detalle de Mensaje -->
<div class="modal fade" id="modalDetalleMensaje" tabindex="-1" aria-labelledby="modalDetalleMensajeLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8e6cf 0%, #dcedc1 100%);">
        <h5 class="modal-title" id="modalDetalleMensajeLabel">
          <i class="ti ti-info-circle me-2"></i>Detalle del Mensaje
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="detalleMensajeContenido">
        <!-- Contenido se llenará dinámicamente -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
.detalle-seccion {
  background: #f8f9fa;
  border-left: 4px solid #a8e6cf;
  padding: 15px;
  margin-bottom: 15px;
  border-radius: 8px;
}

.detalle-titulo {
  font-weight: 600;
  color: #2c3e50;
  font-size: 1rem;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.detalle-campo {
  display: flex;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.detalle-label {
  font-weight: 600;
  color: #495057;
  min-width: 140px;
}

.detalle-valor {
  color: #6c757d;
  flex: 1;
}

.contenido-mensaje {
  background: #ffffff;
  border: 1px solid #dee2e6;
  padding: 15px;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-size: 0.85rem;
  max-height: 300px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.badge-detalle {
  font-size: 0.85rem;
  padding: 0.35rem 0.7rem;
  border-radius: 12px;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
  position: relative;
  padding-left: 30px;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: 9px;
  top: 24px;
  bottom: -12px;
  width: 2px;
  background: #dee2e6;
}

.timeline-item:last-child::before {
  display: none;
}

.timeline-icon {
  position: absolute;
  left: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: white;
}

.timeline-content {
  flex: 1;
}

.timeline-fecha {
  font-size: 0.75rem;
  color: #6c757d;
  display: block;
  margin-top: 2px;
}

.error-detalle-box {
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
  padding: 12px;
  border-radius: 6px;
  font-size: 0.85rem;
}

.costo-detalle-box {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  font-size: 1.1rem;
  font-weight: 600;
}
</style>