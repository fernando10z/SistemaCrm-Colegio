<!-- Modal para crear boletín -->
<div class="modal fade" id="modalCrearBoletin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nuevo Boletín Informativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearBoletin" method="POST" action="acciones/boletines/procesar_acciones.php">
                <input type="hidden" name="accion" value="crear_boletin">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Boletín <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" required 
                                       placeholder="Ej: Newsletter Mensual Marzo 2025">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Asunto del Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="asunto" required 
                                       placeholder="Ej: Novedades y Eventos de Marzo">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Seleccionar categoría</option>
                                    <option value="boletin_informativo">Boletín Informativo</option>
                                    <option value="newsletter_mensual">Newsletter Mensual</option>
                                    <option value="comunicado_eventos">Comunicado de Eventos</option>
                                    <option value="boletin_academico">Boletín Académico</option>
                                    <option value="informativo_general">Informativo General</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nivel Educativo Objetivo</label>
                                <select class="form-select" name="nivel_educativo">
                                    <option value="general">General (Todos los niveles)</option>
                                    <option value="inicial">Inicial</option>
                                    <option value="primaria">Primaria</option>
                                    <option value="secundaria">Secundaria</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contenido del Boletín <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="contenido" rows="12" required 
                                  placeholder="Escriba aquí el contenido de su boletín informativo...

Ejemplo de estructura:
¡Hola queridas familias!

Nos complace compartir con ustedes las principales novedades de este mes:

📚 NOTICIAS ACADÉMICAS
- [Escriba aquí las novedades académicas]

🎉 EVENTOS DESTACADOS  
- [Escriba aquí los eventos importantes]

📢 COMUNICADOS IMPORTANTES
- [Escriba aquí comunicados relevantes]

¡Gracias por ser parte de nuestra comunidad educativa!

Atentamente,
Equipo Directivo"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="card border-light">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Opciones Adicionales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="incluir_eventos" id="incluir_eventos" value="1">
                                            <label class="form-check-label" for="incluir_eventos">
                                                Incluir eventos próximos automáticamente
                                            </label>
                                        </div>
                                        <small class="text-muted">Se agregarán automáticamente los próximos 5 eventos programados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Variables Disponibles</label>
                                <div class="card border-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input variable-check" type="checkbox" value="{{nombre_familia}}" id="var1">
                                                    <label class="form-check-label small" for="var1">{{nombre_familia}}</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input variable-check" type="checkbox" value="{{nombre_institucion}}" id="var2">
                                                    <label class="form-check-label small" for="var2">{{nombre_institucion}}</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input variable-check" type="checkbox" value="{{fecha_actual}}" id="var3">
                                                    <label class="form-check-label small" for="var3">{{fecha_actual}}</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input variable-check" type="checkbox" value="{{mes_actual}}" id="var4">
                                                    <label class="form-check-label small" for="var4">{{mes_actual}}</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input variable-check" type="checkbox" value="{{año_academico}}" id="var5">
                                                    <label class="form-check-label small" for="var5">{{año_academico}}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="variables_disponibles" id="variables_disponibles">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="ti ti-info-circle me-2"></i>Información sobre Variables</h6>
                        <p class="mb-0">Las variables seleccionadas se pueden usar en el contenido del boletín y serán reemplazadas automáticamente al enviarlo. Por ejemplo: "Estimada {{nombre_familia}}" se convertirá en "Estimada Familia García".</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-info" onclick="previsualizarBoletin()">
                        <i class="ti ti-eye me-1"></i>Vista Previa
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-file-plus me-1"></i>Crear Boletín
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar variables disponibles cuando se seleccionan checkboxes
    document.querySelectorAll('.variable-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            actualizarVariablesDisponibles();
        });
    });
    
    // Manejar envío del formulario
    document.getElementById('formCrearBoletin').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Mostrar loader
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';
        submitBtn.disabled = true;
        
        fetch('acciones/boletines/procesar_acciones.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.mensaje);
                location.reload();
            } else {
                alert('Error: ' + data.mensaje);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión. Inténtelo nuevamente.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

function actualizarVariablesDisponibles() {
    const checkboxes = document.querySelectorAll('.variable-check:checked');
    const variables = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('variables_disponibles').value = JSON.stringify(variables);
}

function previsualizarBoletin() {
    const contenido = document.querySelector('textarea[name="contenido"]').value;
    const asunto = document.querySelector('input[name="asunto"]').value;
    const nombre = document.querySelector('input[name="nombre"]').value;
    
    if (!contenido || !asunto || !nombre) {
        alert('Por favor complete al menos el nombre, asunto y contenido para ver la vista previa.');
        return;
    }
    
    // Crear modal de vista previa
    const modalPreview = document.createElement('div');
    modalPreview.innerHTML = `
        <div class="modal fade" id="modalVistaPrevia" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vista Previa - ${nombre}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="email-preview" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                            <div class="email-header" style="background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd;">
                                <h6 class="mb-1">Asunto: ${asunto}</h6>
                                <small class="text-muted">De: ${nombre}</small>
                            </div>
                            <div class="email-body" style="padding: 20px; background: white;">
                                ${contenido.replace(/\n/g, '<br>')}
                            </div>
                            <div class="email-footer" style="background: #f8f9fa; padding: 10px; border-top: 1px solid #ddd;">
                                <small class="text-muted">Este es un boletín informativo del sistema CRM</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar Vista Previa</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalPreview);
    
    const modal = new bootstrap.Modal(document.getElementById('modalVistaPrevia'));
    modal.show();
    
    // Limpiar modal cuando se cierre
    document.getElementById('modalVistaPrevia').addEventListener('hidden.bs.modal', function () {
        modalPreview.remove();
    });
}

// Limpiar formulario al cerrar modal
document.getElementById('modalCrearBoletin').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formCrearBoletin').reset();
    document.getElementById('variables_disponibles').value = '';
});
</script>