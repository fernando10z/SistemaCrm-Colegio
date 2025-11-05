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

<!-- Modal para crear encuesta -->
<div class="modal fade" id="modalCrearEncuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #FFE5E5 0%, #E8F5E9 100%); border-bottom: 2px solid #FFD1DC;">
                <h5 class="modal-title" style="color: #6B5B95; font-weight: 600;">
                    <i class="ti ti-clipboard-check"></i> Crear Nueva Encuesta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearEncuesta" method="POST" action="acciones/encuestas/procesar_encuestas.php">
                <input type="hidden" name="accion" value="crear_encuesta">
                <div class="modal-body" style="background-color: #FEFEFE;">
                    <!-- Información general -->
                    <div class="card mb-3" style="border: 1px solid #E8D5F2; background-color: #FFF9FC;">
                        <div class="card-body">
                            <h6 class="card-title" style="color: #6B5B95; border-bottom: 2px solid #E8D5F2; padding-bottom: 8px;">
                                <i class="ti ti-info-circle"></i> Información General
                            </h6>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #7B68A6;">Título <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="titulo" required 
                                               style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #7B68A6;">Tipo <span class="text-danger">*</span></label>
                                        <select class="form-select" name="tipo" required 
                                                style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                            <option value="">Seleccionar tipo</option>
                                            <option value="satisfaccion">Satisfacción</option>
                                            <option value="feedback">Feedback</option>
                                            <option value="evento">Evento</option>
                                            <option value="general">General</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" style="color: #7B68A6;">Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="3" 
                                          style="border: 1px solid #D5C4E8; border-radius: 8px;"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #7B68A6;">Dirigido a <span class="text-danger">*</span></label>
                                        <select class="form-select" name="dirigido_a" required 
                                                style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                            <option value="">Seleccionar destinatarios</option>
                                            <option value="padres">Padres de Familia</option>
                                            <option value="estudiantes">Estudiantes</option>
                                            <option value="exalumnos">Ex-alumnos</option>
                                            <option value="leads">Leads</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #7B68A6;">Fecha Inicio <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="fecha_inicio" required 
                                               style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #7B68A6;">Fecha Fin (Opcional)</label>
                                        <input type="date" class="form-control" name="fecha_fin" 
                                               style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preguntas -->
                    <div class="card" style="border: 1px solid #C8E6C9; background-color: #F9FFF9;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0" style="color: #4CAF50; border-bottom: 2px solid #C8E6C9; padding-bottom: 8px;">
                                    <i class="ti ti-list-check"></i> Preguntas de la Encuesta
                                </h6>
                                <button type="button" class="btn btn-sm" onclick="agregarPregunta()" 
                                        style="background: linear-gradient(135deg, #C8E6C9 0%, #A5D6A7 100%); 
                                               color: #2E7D32; border: none; border-radius: 8px; padding: 8px 16px;">
                                    <i class="ti ti-plus"></i> Agregar Pregunta
                                </button>
                            </div>
                            
                            <div id="contenedor-preguntas"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #FAFAFA; border-top: 2px solid #E8E8E8;">
                    <button type="button" class="btn" data-bs-dismiss="modal" 
                            style="background-color: #FFE5E5; color: #D32F2F; border: none; border-radius: 8px;">
                        <i class="ti ti-x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn" 
                            style="background: linear-gradient(135deg, #C8E6C9 0%, #81C784 100%); 
                                   color: white; border: none; border-radius: 8px;">
                        <i class="ti ti-check"></i> Crear Encuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pregunta-card {
    border: 2px solid #E0E0E0;
    border-radius: 12px;
    transition: all 0.3s ease;
    background-color: white;
}

.pregunta-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #B8B8D0;
}

.opciones-container {
    background-color: #F7F9FC;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #9FA8DA;
}

.form-control:focus, .form-select:focus {
    border-color: #B39DDB;
    box-shadow: 0 0 0 0.2rem rgba(179, 157, 219, 0.25);
}
</style>

<script>
let contadorPreguntas = 0;

function agregarPregunta() {
    contadorPreguntas++;
    const contenedor = document.getElementById('contenedor-preguntas');
    
    const preguntaHtml = `
        <div class="card mb-3 pregunta-card" data-pregunta="${contadorPreguntas}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="color: #6B5B95;">
                        <i class="ti ti-message-circle"></i> Pregunta ${contadorPreguntas}
                    </h6>
                    <button type="button" class="btn btn-sm" onclick="confirmarEliminarPregunta(${contadorPreguntas})" 
                            style="background-color: #FFCDD2; color: #D32F2F; border: none; border-radius: 8px;">
                        <i class="ti ti-trash"></i> Eliminar
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label" style="color: #7B68A6;">Texto de la pregunta</label>
                            <input type="text" class="form-control" name="preguntas[${contadorPreguntas-1}][pregunta]" required 
                                   style="border: 1px solid #D5C4E8; border-radius: 8px;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" style="color: #7B68A6;">Tipo de respuesta</label>
                            <select class="form-select" name="preguntas[${contadorPreguntas-1}][tipo]" 
                                    onchange="mostrarOpciones(${contadorPreguntas}, this.value)" required 
                                    style="border: 1px solid #D5C4E8; border-radius: 8px;">
                                <option value="">Seleccionar tipo</option>
                                <option value="text">Texto libre</option>
                                <option value="select">Selección única</option>
                                <option value="radio">Opción múltiple</option>
                                <option value="checkbox">Casillas múltiples</option>
                                <option value="rating">Calificación (1-5)</option>
                                <option value="si_no">Sí/No</option>
                                <option value="escala">Escala de satisfacción</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="opciones-container" id="opciones-${contadorPreguntas}" style="display: none;">
                    <label class="form-label" style="color: #5C6BC0;">Opciones de respuesta</label>
                    <div class="opciones-lista">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="preguntas[${contadorPreguntas-1}][opciones][]" 
                                   placeholder="Opción 1" style="border-radius: 8px 0 0 8px;">
                            <button type="button" class="btn" onclick="agregarOpcion(${contadorPreguntas})" 
                                    style="background-color: #C5CAE9; color: #3F51B5; border: none; border-radius: 0 8px 8px 0;">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="preguntas[${contadorPreguntas-1}][requerida]" value="1" 
                           style="border: 2px solid #B39DDB;">
                    <label class="form-check-label" style="color: #7B68A6;">
                        Pregunta obligatoria
                    </label>
                </div>
            </div>
        </div>
    `;
    
    contenedor.insertAdjacentHTML('beforeend', preguntaHtml);
    
    Swal.fire({
        icon: 'success',
        title: '¡Pregunta agregada!',
        text: `Pregunta #${contadorPreguntas} añadida correctamente`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        background: '#E8F5E9',
        iconColor: '#4CAF50'
    });
}

function confirmarEliminarPregunta(numero) {
    Swal.fire({
        title: '¿Eliminar pregunta?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF5350',
        cancelButtonColor: '#B0BEC5',
        confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
        cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
        background: '#FFFBFC'
    }).then((result) => {
        if (result.isConfirmed) {
            eliminarPregunta(numero);
            Swal.fire({
                icon: 'success',
                title: '¡Eliminada!',
                text: 'La pregunta ha sido eliminada',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                background: '#FFEBEE',
                iconColor: '#EF5350'
            });
        }
    });
}

function eliminarPregunta(numero) {
    const pregunta = document.querySelector(`[data-pregunta="${numero}"]`);
    pregunta.remove();
}

function mostrarOpciones(numero, tipo) {
    const contenedor = document.getElementById(`opciones-${numero}`);
    
    if (['select', 'radio', 'checkbox', 'escala'].includes(tipo)) {
        contenedor.style.display = 'block';
        
        if (tipo === 'escala') {
            const opcionesLista = contenedor.querySelector('.opciones-lista');
            opcionesLista.innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="preguntas[${numero-1}][opciones][]" value="Excelente" readonly>
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="preguntas[${numero-1}][opciones][]" value="Muy Bueno" readonly>
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="preguntas[${numero-1}][opciones][]" value="Bueno" readonly>
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="preguntas[${numero-1}][opciones][]" value="Regular" readonly>
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="preguntas[${numero-1}][opciones][]" value="Deficiente" readonly>
                </div>
            `;
        }
    } else {
        contenedor.style.display = 'none';
    }
}

function agregarOpcion(numeroPregunta) {
    const contenedor = document.querySelector(`#opciones-${numeroPregunta} .opciones-lista`);
    const cantidadOpciones = contenedor.querySelectorAll('.input-group').length + 1;
    const nuevaOpcion = document.createElement('div');
    nuevaOpcion.className = 'input-group mb-2';
    nuevaOpcion.innerHTML = `
        <input type="text" class="form-control" name="preguntas[${numeroPregunta-1}][opciones][]" 
               placeholder="Opción ${cantidadOpciones}" style="border-radius: 8px 0 0 8px;">
        <button type="button" class="btn" onclick="this.parentElement.remove()" 
                style="background-color: #FFCDD2; color: #D32F2F; border: none; border-radius: 0 8px 8px 0;">
            <i class="ti ti-minus"></i>
        </button>
    `;
    contenedor.appendChild(nuevaOpcion);
}

document.getElementById('formCrearEncuesta').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cantidadPreguntas = document.querySelectorAll('.pregunta-card').length;
    
    if (cantidadPreguntas === 0) {
        Swal.fire({
            icon: 'warning',
            title: '¡Atención!',
            text: 'Debes agregar al menos una pregunta',
            confirmButtonColor: '#FF9800',
            background: '#FFF9E6',
            iconColor: '#FF9800'
        });
        return;
    }
    
    Swal.fire({
        title: '¿Crear encuesta?',
        html: `<p>Estás a punto de crear una encuesta con <strong>${cantidadPreguntas}</strong> pregunta(s).</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#66BB6A',
        cancelButtonColor: '#B0BEC5',
        confirmButtonText: '<i class="ti ti-check"></i> Sí, crear',
        cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
        background: '#F1F8E9'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Creando encuesta...',
                html: 'Por favor espera un momento',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            this.submit();
        }
    });
});

document.getElementById('modalCrearEncuesta').addEventListener('shown.bs.modal', function () {
    if (contadorPreguntas === 0) {
        agregarPregunta();
    }
});

document.getElementById('modalCrearEncuesta').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formCrearEncuesta').reset();
    document.getElementById('contenedor-preguntas').innerHTML = '';
    contadorPreguntas = 0;
});
</script>

<!-- Mensajes de respuesta del servidor -->
<?php if (isset($_SESSION['mensaje_encuesta'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?php echo $_SESSION['tipo_mensaje_encuesta']; ?>',
        title: '<?php echo $_SESSION['tipo_mensaje_encuesta'] === 'success' ? '¡Éxito!' : 'Error'; ?>',
        text: '<?php echo $_SESSION['mensaje_encuesta']; ?>',
        confirmButtonColor: '#66BB6A',
        background: '<?php echo $_SESSION['tipo_mensaje_encuesta'] === 'success' ? '#E8F5E9' : '#FFEBEE'; ?>',
        iconColor: '<?php echo $_SESSION['tipo_mensaje_encuesta'] === 'success' ? '#4CAF50' : '#EF5350'; ?>'
    });
});
</script>
<?php 
    unset($_SESSION['mensaje_encuesta']);
    unset($_SESSION['tipo_mensaje_encuesta']);
endif; 
?>