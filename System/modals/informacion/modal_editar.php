<!-- Modal para editar configuración -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Editar Configuración</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formEditar" method="POST" action="acciones/informacion/procesar_configuracion.php">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="accion" value="editar">
        <div class="modal-body">
            <div class="mb-3">
            <label class="form-label">Clave <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="clave" id="edit_clave" required>
            </div>
            <div class="mb-3">
            <label class="form-label">Valor <span class="text-danger">*</span></label>
            <textarea class="form-control" name="valor" id="edit_valor" rows="3" required></textarea>
            </div>
            <div class="mb-3">
            <label class="form-label">Tipo <span class="text-danger">*</span></label>
            <select class="form-select" name="tipo" id="edit_tipo" required>
                <option value="texto">Texto</option>
                <option value="numero">Número</option>
                <option value="booleano">Booleano</option>
                <option value="json">JSON</option>
            </select>
            </div>
            <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="2"></textarea>
            </div>
            <div class="mb-3">
            <label class="form-label">Categoría <span class="text-danger">*</span></label>
            <select class="form-select" name="categoria" id="edit_categoria" required>
                <option value="general">General</option>
                <option value="contacto">Contacto</option>
                <option value="sistema">Sistema</option>
                <option value="finanzas">Finanzas</option>
            </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
        </form>
    </div>
    </div>
</div>