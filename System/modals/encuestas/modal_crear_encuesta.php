<!-- Modal para crear encuesta -->
<div class="modal fade" id="modalCrearEncuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nueva Encuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearEncuesta" method="POST" action="acciones/encuestas/procesar_encuestas.php">
                <input type="hidden" name="accion" value="crear_encuesta">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="titulo" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" required>
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
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Dirigido a <span class="text-danger">*</span></label>
                                <select class="form-select" name="dirigido_a" required>
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
                                <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha Fin (Opcional)</label>
                                <input type="date" class="form-control" name="fecha_fin">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Preguntas de la Encuesta</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarPregunta()">
                            <i class="ti ti-plus"></i> Agregar Pregunta
                        </button>
                    </div>
                    
                    <div id="contenedor-preguntas">
                        <!-- Las preguntas se agregarán dinámicamente aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Encuesta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let contadorPreguntas = 0;

function agregarPregunta() {
    contadorPreguntas++;
    const contenedor = document.getElementById('contenedor-preguntas');
    
    const preguntaHtml = `
        <div class="card mb-3 pregunta-card" data-pregunta="${contadorPreguntas}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Pregunta ${contadorPreguntas}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarPregunta(${contadorPreguntas})">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Texto de la pregunta</label>
                            <input type="text" class="form-control" name="preguntas[${contadorPreguntas-1}][pregunta]" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tipo de respuesta</label>
                            <select class="form-select" name="preguntas[${contadorPreguntas-1}][tipo]" onchange="mostrarOpciones(${contadorPreguntas}, this.value)" required>
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
                    <label class="form-label">Opciones de respuesta</label>
                    <div class="opciones-lista">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="preguntas[${contadorPreguntas-1}][opciones][]" placeholder="Opción 1">
                            <button type="button" class="btn btn-outline-secondary" onclick="agregarOpcion(${contadorPreguntas})">+</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="preguntas[${contadorPreguntas-1}][requerida]" value="1">
                    <label class="form-check-label">Pregunta obligatoria</label>
                </div>
            </div>
        </div>
    `;
    
    contenedor.insertAdjacentHTML('beforeend', preguntaHtml);
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
    const nuevaOpcion = document.createElement('div');
    nuevaOpcion.className = 'input-group mb-2';
    nuevaOpcion.innerHTML = `
        <input type="text" class="form-control" name="preguntas[${numeroPregunta-1}][opciones][]" placeholder="Nueva opción">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">-</button>
    `;
    contenedor.appendChild(nuevaOpcion);
}

// Agregar una pregunta por defecto al abrir el modal
document.getElementById('modalCrearEncuesta').addEventListener('shown.bs.modal', function () {
    if (contadorPreguntas === 0) {
        agregarPregunta();
    }
});
</script>