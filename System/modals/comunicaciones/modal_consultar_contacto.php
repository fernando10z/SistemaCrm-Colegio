<!-- Modal para consultar contacto -->
<div class="modal fade" id="modalConsultarContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>
                    Consultar Historial por Contacto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConsultarContacto" method="GET" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Buscar por:</label>
                                <select class="form-select" name="tipo_busqueda" id="tipo_busqueda" onchange="toggleCamposBusqueda()">
                                    <option value="email">Email</option>
                                    <option value="telefono">Teléfono/WhatsApp</option>
                                    <option value="nombre">Nombre del Contacto</option>
                                    <option value="lead_id">ID Lead</option>
                                    <option value="apoderado_id">ID Apoderado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Valor a buscar <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contacto_filtro" id="contacto_filtro" 
                                       placeholder="Ingrese el valor a buscar" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de mensaje:</label>
                                <select class="form-select" name="tipo_mensaje">
                                    <option value="">Todos los tipos</option>
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado del mensaje:</label>
                                <select class="form-select" name="estado_mensaje">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="enviado">Enviado</option>
                                    <option value="entregado">Entregado</option>
                                    <option value="leido">Leído</option>
                                    <option value="fallido">Fallido</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha desde:</label>
                                <input type="date" class="form-control" name="fecha_inicio" 
                                       value="<?php echo date('Y-m-01'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta:</label>
                                <input type="date" class="form-control" name="fecha_fin" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Ayuda:</strong> 
                        <ul class="mb-0 mt-2">
                            <li><strong>Email:</strong> Busca en destinatario_email</li>
                            <li><strong>Teléfono:</strong> Busca en destinatario_telefono y whatsapp</li>
                            <li><strong>Nombre:</strong> Busca en nombres de leads y apoderados</li>
                            <li><strong>ID Lead/Apoderado:</strong> Busca por ID específico</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        Buscar Historial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCamposBusqueda() {
    const tipoBusqueda = document.getElementById('tipo_busqueda').value;
    const inputContacto = document.getElementById('contacto_filtro');
    
    switch(tipoBusqueda) {
        case 'email':
            inputContacto.placeholder = 'Ej: ana.castillo@email.com';
            inputContacto.type = 'email';
            break;
        case 'telefono':
            inputContacto.placeholder = 'Ej: +51 987654321 o 987654321';
            inputContacto.type = 'text';
            break;
        case 'nombre':
            inputContacto.placeholder = 'Ej: Ana María o García';
            inputContacto.type = 'text';
            break;
        case 'lead_id':
            inputContacto.placeholder = 'Ej: 1, 2, 3...';
            inputContacto.type = 'number';
            break;
        case 'apoderado_id':
            inputContacto.placeholder = 'Ej: 1, 2, 3...';
            inputContacto.type = 'number';
            break;
        default:
            inputContacto.placeholder = 'Ingrese el valor a buscar';
            inputContacto.type = 'text';
    }
}
</script>