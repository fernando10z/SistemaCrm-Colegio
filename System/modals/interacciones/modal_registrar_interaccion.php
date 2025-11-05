<?php
// Obtener datos para los selects
include_once 'bd/conexion.php';

// Obtener tipos de interacción activos
$tipos_sql = "SELECT id, nombre, descripcion, icono, color FROM tipos_interaccion WHERE activo = 1 ORDER BY nombre";
$tipos_result = $conn->query($tipos_sql);

// Obtener apoderados activos con información de la familia
$apoderados_sql = "SELECT 
    a.id, 
    a.nombres, 
    a.apellidos, 
    a.tipo_apoderado,
    a.email,
    a.telefono_principal,
    f.codigo_familia,
    f.apellido_principal as familia_apellido
FROM apoderados a
INNER JOIN familias f ON a.familia_id = f.id
WHERE a.activo = 1 
ORDER BY a.apellidos, a.nombres";
$apoderados_result = $conn->query($apoderados_sql);

// Obtener familias activas
$familias_sql = "SELECT 
    id, 
    codigo_familia, 
    apellido_principal,
    direccion,
    distrito
FROM familias 
WHERE activo = 1 
ORDER BY apellido_principal";
$familias_result = $conn->query($familias_sql);
?>

<!-- Modal Registrar Interacción -->
<div class="modal fade" id="modalRegistrarInteraccion" tabindex="-1" aria-labelledby="modalRegistrarInteraccionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #b8cfe6 0%, #d4e4f5 100%); border-bottom: 2px solid #e8f2f9;">
        <h5 class="modal-title" id="modalRegistrarInteraccionLabel">
          <i class="ti ti-plus-circle me-2"></i>Registrar Nueva Interacción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      
      <form method="POST" action="" id="formRegistrarInteraccion" novalidate>
        <div class="modal-body">
          <input type="hidden" name="accion" value="registrar_interaccion">
          
          <!-- Alerta de validación -->
          <div class="alert alert-danger d-none" id="alertValidacion" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <span id="mensajeValidacion"></span>
          </div>
          
          <!-- Sección: Tipo de Interacción -->
          <div class="card mb-3" style="background-color: #fef7f0; border: 1px solid #f9e5d3;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #6b4f3a;">
                <i class="ti ti-category me-2"></i>Tipo de Interacción
              </h6>
              
              <div class="row">
                <div class="col-12">
                  <label for="tipo_interaccion_id" class="form-label">
                    Tipo de Interacción <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="tipo_interaccion_id" name="tipo_interaccion_id" required>
                    <option value="">Seleccionar tipo de interacción</option>
                    <?php
                    if ($tipos_result && $tipos_result->num_rows > 0) {
                        while($tipo = $tipos_result->fetch_assoc()) {
                            $color = htmlspecialchars($tipo['color'] ?? '#6c757d');
                            $nombre = htmlspecialchars($tipo['nombre']);
                            $descripcion = htmlspecialchars($tipo['descripcion'] ?? '');
                            echo "<option value='" . $tipo['id'] . "' data-color='$color' data-descripcion='$descripcion'>$nombre</option>";
                        }
                    }
                    ?>
                  </select>
                  <div class="invalid-feedback">Por favor, seleccione un tipo de interacción.</div>
                  <small class="form-text text-muted" id="descripcionTipo"></small>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección: Contacto -->
          <div class="card mb-3" style="background-color: #f0f7fe; border: 1px solid #d3e5f9;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #3a4f6b;">
                <i class="ti ti-users me-2"></i>Información del Contacto
              </h6>
              
              <div class="row">
                <div class="col-12 mb-3">
                  <label class="form-label">Tipo de Contacto <span class="text-danger">*</span></label>
                  <div class="btn-group w-100" role="group" aria-label="Tipo de contacto">
                    <input type="radio" class="btn-check" name="tipo_contacto" id="radio_apoderado" value="apoderado" autocomplete="off">
                    <label class="btn btn-outline-primary" for="radio_apoderado">
                      <i class="ti ti-user me-1"></i>Apoderado
                    </label>
                    
                    <input type="radio" class="btn-check" name="tipo_contacto" id="radio_familia" value="familia" autocomplete="off">
                    <label class="btn btn-outline-primary" for="radio_familia">
                      <i class="ti ti-users me-1"></i>Familia
                    </label>
                  </div>
                  <div class="invalid-feedback d-block" id="errorTipoContacto" style="display: none !important;">
                    Por favor, seleccione un tipo de contacto.
                  </div>
                </div>
              </div>

              <div class="row">
                <!-- Select Apoderado -->
                <div class="col-12 d-none" id="contenedor_apoderado">
                  <label for="apoderado_id" class="form-label">Seleccionar Apoderado</label>
                  <select class="form-select" id="apoderado_id" name="apoderado_id">
                    <option value="">Seleccionar apoderado</option>
                    <?php
                    if ($apoderados_result && $apoderados_result->num_rows > 0) {
                        while($apoderado = $apoderados_result->fetch_assoc()) {
                            $nombre_completo = htmlspecialchars($apoderado['nombres'] . ' ' . $apoderado['apellidos']);
                            $tipo = htmlspecialchars(ucfirst($apoderado['tipo_apoderado']));
                            $familia = htmlspecialchars($apoderado['familia_apellido']);
                            $codigo = htmlspecialchars($apoderado['codigo_familia']);
                            $email = htmlspecialchars($apoderado['email'] ?? '');
                            $telefono = htmlspecialchars($apoderado['telefono_principal'] ?? '');
                            
                            echo "<option value='" . $apoderado['id'] . "' ";
                            echo "data-email='$email' data-telefono='$telefono' data-familia='$familia' data-codigo='$codigo'>";
                            echo "$nombre_completo ($tipo) - Fam. $familia ($codigo)";
                            echo "</option>";
                        }
                    }
                    ?>
                  </select>
                  <small class="form-text text-muted" id="infoApoderado"></small>
                </div>

                <!-- Select Familia -->
                <div class="col-12 d-none" id="contenedor_familia">
                  <label for="familia_id" class="form-label">Seleccionar Familia</label>
                  <select class="form-select" id="familia_id" name="familia_id">
                    <option value="">Seleccionar familia</option>
                    <?php
                    if ($familias_result && $familias_result->num_rows > 0) {
                        while($familia = $familias_result->fetch_assoc()) {
                            $apellido = htmlspecialchars($familia['apellido_principal']);
                            $codigo = htmlspecialchars($familia['codigo_familia']);
                            $direccion = htmlspecialchars($familia['direccion'] ?? '');
                            $distrito = htmlspecialchars($familia['distrito'] ?? '');
                            
                            echo "<option value='" . $familia['id'] . "' ";
                            echo "data-direccion='$direccion' data-distrito='$distrito'>";
                            echo "Familia $apellido ($codigo)";
                            echo "</option>";
                        }
                    }
                    ?>
                  </select>
                  <small class="form-text text-muted" id="infoFamilia"></small>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección: Detalles de la Interacción -->
          <div class="card mb-3" style="background-color: #f7f0fe; border: 1px solid #e5d3f9;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #4f3a6b;">
                <i class="ti ti-file-description me-2"></i>Detalles de la Interacción
              </h6>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="asunto" class="form-label">
                    Asunto <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control" id="asunto" name="asunto" 
                         placeholder="Ej: Consulta sobre matrícula 2026" 
                         maxlength="200" required>
                  <div class="invalid-feedback">Por favor, ingrese el asunto (máx. 200 caracteres).</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="fecha_realizada" class="form-label">
                    Fecha y Hora de la Interacción <span class="text-danger">*</span>
                  </label>
                  <input type="datetime-local" class="form-control" id="fecha_realizada" name="fecha_realizada" 
                         max="<?php echo date('Y-m-d\TH:i'); ?>" required>
                  <div class="invalid-feedback">Por favor, ingrese la fecha y hora de la interacción.</div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 mb-3">
                  <label for="descripcion" class="form-label">
                    Descripción de la Interacción <span class="text-danger">*</span>
                  </label>
                  <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                            placeholder="Describa los detalles de la interacción..." 
                            maxlength="1000" required></textarea>
                  <div class="invalid-feedback">Por favor, ingrese la descripción (máx. 1000 caracteres).</div>
                  <small class="form-text text-muted">
                    <span id="contadorDescripcion">0</span>/1000 caracteres
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección: Resultado -->
          <div class="card mb-3" style="background-color: #f0fef5; border: 1px solid #d3f9e0;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #3a6b4f;">
                <i class="ti ti-check-circle me-2"></i>Resultado de la Interacción
              </h6>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="resultado" class="form-label">
                    Resultado <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="resultado" name="resultado" required>
                    <option value="">Seleccionar resultado</option>
                    <option value="exitoso">✓ Exitoso</option>
                    <option value="sin_respuesta">⏳ Sin respuesta</option>
                    <option value="reagendar">📅 Reagendar</option>
                    <option value="no_interesado">✗ No interesado</option>
                    <option value="convertido">★ Convertido</option>
                  </select>
                  <div class="invalid-feedback">Por favor, seleccione el resultado.</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="duracion_minutos" class="form-label">
                    Duración (minutos)
                  </label>
                  <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" 
                         placeholder="Ej: 30" min="1" max="600">
                  <small class="form-text text-muted">Opcional - Entre 1 y 600 minutos</small>
                </div>
              </div>

              <div class="row">
                <div class="col-12 mb-3">
                  <label for="observaciones" class="form-label">
                    Observaciones <span class="text-danger">*</span>
                  </label>
                  <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                            placeholder="Registre observaciones importantes de la interacción..." 
                            maxlength="1000" required></textarea>
                  <div class="invalid-feedback">Por favor, ingrese las observaciones (máx. 1000 caracteres).</div>
                  <small class="form-text text-muted">
                    <span id="contadorObservaciones">0</span>/1000 caracteres
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección: Seguimiento -->
          <div class="card mb-3" style="background-color: #fffbf0; border: 1px solid #f9f3d3;">
            <div class="card-body">
              <h6 class="card-title mb-3" style="color: #6b5f3a;">
                <i class="ti ti-calendar-event me-2"></i>Seguimiento
              </h6>
              
              <div class="row">
                <div class="col-12 mb-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="requiere_seguimiento" name="requiere_seguimiento">
                    <label class="form-check-label" for="requiere_seguimiento">
                      ¿Esta interacción requiere seguimiento?
                    </label>
                  </div>
                </div>
              </div>

              <div class="row d-none" id="contenedor_seguimiento">
                <div class="col-12">
                  <label for="fecha_proximo_seguimiento" class="form-label">
                    Fecha del Próximo Seguimiento <span class="text-danger">*</span>
                  </label>
                  <input type="date" class="form-control" id="fecha_proximo_seguimiento" 
                         name="fecha_proximo_seguimiento" 
                         min="<?php echo date('Y-m-d'); ?>">
                  <div class="invalid-feedback">Por favor, ingrese la fecha del próximo seguimiento.</div>
                  <small class="form-text text-muted">Debe ser una fecha futura</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Información de ayuda -->
          <div class="alert alert-info" role="alert" style="background-color: #e8f4fd; border-color: #b8dff5;">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Nota:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.
            Asegúrese de completar toda la información antes de guardar.
          </div>
        </div>

        <div class="modal-footer" style="background-color: #f8f9fa;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="btnGuardarInteraccion">
            <i class="ti ti-device-floppy me-1"></i>Guardar Interacción
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- JavaScript para validaciones y comportamiento del modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRegistrarInteraccion');
    const alertValidacion = document.getElementById('alertValidacion');
    const mensajeValidacion = document.getElementById('mensajeValidacion');
    
    // Establecer fecha actual como valor por defecto
    const fechaRealizada = document.getElementById('fecha_realizada');
    if (fechaRealizada) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        fechaRealizada.value = now.toISOString().slice(0, 16);
    }

    // Contadores de caracteres
    const descripcion = document.getElementById('descripcion');
    const contadorDescripcion = document.getElementById('contadorDescripcion');
    const observaciones = document.getElementById('observaciones');
    const contadorObservaciones = document.getElementById('contadorObservaciones');

    if (descripcion && contadorDescripcion) {
        descripcion.addEventListener('input', function() {
            contadorDescripcion.textContent = this.value.length;
        });
    }

    if (observaciones && contadorObservaciones) {
        observaciones.addEventListener('input', function() {
            contadorObservaciones.textContent = this.value.length;
        });
    }

    // Mostrar descripción del tipo de interacción
    const tipoInteraccion = document.getElementById('tipo_interaccion_id');
    const descripcionTipo = document.getElementById('descripcionTipo');
    
    if (tipoInteraccion && descripcionTipo) {
        tipoInteraccion.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const descripcion = selectedOption.getAttribute('data-descripcion');
            if (descripcion) {
                descripcionTipo.textContent = descripcion;
                descripcionTipo.style.color = selectedOption.getAttribute('data-color');
            } else {
                descripcionTipo.textContent = '';
            }
        });
    }

    // Manejo de tipo de contacto (Apoderado o Familia)
    const radioApoderado = document.getElementById('radio_apoderado');
    const radioFamilia = document.getElementById('radio_familia');
    const contenedorApoderado = document.getElementById('contenedor_apoderado');
    const contenedorFamilia = document.getElementById('contenedor_familia');
    const selectApoderado = document.getElementById('apoderado_id');
    const selectFamilia = document.getElementById('familia_id');
    const errorTipoContacto = document.getElementById('errorTipoContacto');

    function mostrarContenedorContacto(tipo) {
        if (tipo === 'apoderado') {
            contenedorApoderado.classList.remove('d-none');
            contenedorFamilia.classList.add('d-none');
            selectFamilia.value = '';
            selectFamilia.removeAttribute('required');
            errorTipoContacto.style.display = 'none';
        } else if (tipo === 'familia') {
            contenedorFamilia.classList.remove('d-none');
            contenedorApoderado.classList.add('d-none');
            selectApoderado.value = '';
            selectApoderado.removeAttribute('required');
            errorTipoContacto.style.display = 'none';
        }
    }

    if (radioApoderado) {
        radioApoderado.addEventListener('change', function() {
            if (this.checked) mostrarContenedorContacto('apoderado');
        });
    }

    if (radioFamilia) {
        radioFamilia.addEventListener('change', function() {
            if (this.checked) mostrarContenedorContacto('familia');
        });
    }

    // Mostrar información adicional del apoderado
    const infoApoderado = document.getElementById('infoApoderado');
    if (selectApoderado && infoApoderado) {
        selectApoderado.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                const email = selectedOption.getAttribute('data-email');
                const telefono = selectedOption.getAttribute('data-telefono');
                const familia = selectedOption.getAttribute('data-familia');
                let info = `Familia: ${familia}`;
                if (email) info += ` | Email: ${email}`;
                if (telefono) info += ` | Tel: ${telefono}`;
                infoApoderado.textContent = info;
            } else {
                infoApoderado.textContent = '';
            }
        });
    }

    // Mostrar información adicional de la familia
    const infoFamilia = document.getElementById('infoFamilia');
    if (selectFamilia && infoFamilia) {
        selectFamilia.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                const direccion = selectedOption.getAttribute('data-direccion');
                const distrito = selectedOption.getAttribute('data-distrito');
                let info = '';
                if (direccion) info += `Dirección: ${direccion}`;
                if (distrito) info += ` | Distrito: ${distrito}`;
                infoFamilia.textContent = info || 'Sin información adicional';
            } else {
                infoFamilia.textContent = '';
            }
        });
    }

    // Manejo del checkbox de seguimiento
    const requiereSeguimiento = document.getElementById('requiere_seguimiento');
    const contenedorSeguimiento = document.getElementById('contenedor_seguimiento');
    const fechaSeguimiento = document.getElementById('fecha_proximo_seguimiento');

    if (requiereSeguimiento && contenedorSeguimiento && fechaSeguimiento) {
        requiereSeguimiento.addEventListener('change', function() {
            if (this.checked) {
                contenedorSeguimiento.classList.remove('d-none');
                fechaSeguimiento.setAttribute('required', 'required');
            } else {
                contenedorSeguimiento.classList.add('d-none');
                fechaSeguimiento.removeAttribute('required');
                fechaSeguimiento.value = '';
            }
        });
    }

    // Validación del formulario
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();

            // Ocultar alerta previa
            alertValidacion.classList.add('d-none');

            // Validación personalizada
            let errores = [];

            // Validar tipo de interacción
            if (!tipoInteraccion.value) {
                errores.push('Debe seleccionar un tipo de interacción');
                tipoInteraccion.classList.add('is-invalid');
            } else {
                tipoInteraccion.classList.remove('is-invalid');
            }

            // Validar tipo de contacto
            if (!radioApoderado.checked && !radioFamilia.checked) {
                errores.push('Debe seleccionar un tipo de contacto (Apoderado o Familia)');
                errorTipoContacto.style.display = 'block';
            } else {
                errorTipoContacto.style.display = 'none';
                
                // Validar que se haya seleccionado apoderado o familia según el tipo
                if (radioApoderado.checked && !selectApoderado.value) {
                    errores.push('Debe seleccionar un apoderado');
                    selectApoderado.classList.add('is-invalid');
                } else {
                    selectApoderado.classList.remove('is-invalid');
                }

                if (radioFamilia.checked && !selectFamilia.value) {
                    errores.push('Debe seleccionar una familia');
                    selectFamilia.classList.add('is-invalid');
                } else {
                    selectFamilia.classList.remove('is-invalid');
                }
            }

            // Validar asunto
            const asunto = document.getElementById('asunto');
            if (!asunto.value.trim()) {
                errores.push('El asunto es obligatorio');
                asunto.classList.add('is-invalid');
            } else if (asunto.value.length > 200) {
                errores.push('El asunto no puede exceder 200 caracteres');
                asunto.classList.add('is-invalid');
            } else {
                asunto.classList.remove('is-invalid');
            }

            // Validar descripción
            if (!descripcion.value.trim()) {
                errores.push('La descripción es obligatoria');
                descripcion.classList.add('is-invalid');
            } else if (descripcion.value.length > 1000) {
                errores.push('La descripción no puede exceder 1000 caracteres');
                descripcion.classList.add('is-invalid');
            } else {
                descripcion.classList.remove('is-invalid');
            }

            // Validar fecha realizada
            if (!fechaRealizada.value) {
                errores.push('La fecha de la interacción es obligatoria');
                fechaRealizada.classList.add('is-invalid');
            } else {
                const fechaSeleccionada = new Date(fechaRealizada.value);
                const ahora = new Date();
                if (fechaSeleccionada > ahora) {
                    errores.push('La fecha de la interacción no puede ser futura');
                    fechaRealizada.classList.add('is-invalid');
                } else {
                    fechaRealizada.classList.remove('is-invalid');
                }
            }

            // Validar duración (si se proporciona)
            const duracion = document.getElementById('duracion_minutos');
            if (duracion.value && (duracion.value < 1 || duracion.value > 600)) {
                errores.push('La duración debe estar entre 1 y 600 minutos');
                duracion.classList.add('is-invalid');
            } else {
                duracion.classList.remove('is-invalid');
            }

            // Validar resultado
            const resultado = document.getElementById('resultado');
            if (!resultado.value) {
                errores.push('Debe seleccionar un resultado');
                resultado.classList.add('is-invalid');
            } else {
                resultado.classList.remove('is-invalid');
            }

            // Validar observaciones
            if (!observaciones.value.trim()) {
                errores.push('Las observaciones son obligatorias');
                observaciones.classList.add('is-invalid');
            } else if (observaciones.value.length > 1000) {
                errores.push('Las observaciones no pueden exceder 1000 caracteres');
                observaciones.classList.add('is-invalid');
            } else {
                observaciones.classList.remove('is-invalid');
            }

            // Validar seguimiento
            if (requiereSeguimiento.checked) {
                if (!fechaSeguimiento.value) {
                    errores.push('Si requiere seguimiento, debe indicar la fecha del próximo seguimiento');
                    fechaSeguimiento.classList.add('is-invalid');
                } else {
                    const fechaSeguimientoDate = new Date(fechaSeguimiento.value);
                    const hoy = new Date();
                    hoy.setHours(0, 0, 0, 0);
                    if (fechaSeguimientoDate < hoy) {
                        errores.push('La fecha del seguimiento debe ser futura');
                        fechaSeguimiento.classList.add('is-invalid');
                    } else {
                        fechaSeguimiento.classList.remove('is-invalid');
                    }
                }
            }

            // Mostrar errores o enviar formulario
            if (errores.length > 0) {
                mensajeValidacion.innerHTML = '<ul class="mb-0">' + 
                    errores.map(error => '<li>' + error + '</li>').join('') + 
                    '</ul>';
                alertValidacion.classList.remove('d-none');
                
                // Scroll al inicio del modal para ver los errores
                document.querySelector('#modalRegistrarInteraccion .modal-body').scrollTop = 0;
            } else {
                // Validación HTML5
                if (form.checkValidity() === false) {
                    form.classList.add('was-validated');
                    return;
                }

                // Deshabilitar botón de envío para evitar doble clic
                const btnGuardar = document.getElementById('btnGuardarInteraccion');
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

                // Enviar formulario
                form.submit();
            }
        });
    }

    // Limpiar formulario al cerrar modal
    const modalElement = document.getElementById('modalRegistrarInteraccion');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            form.classList.remove('was-validated');
            alertValidacion.classList.add('d-none');
            contenedorApoderado.classList.add('d-none');
            contenedorFamilia.classList.add('d-none');
            contenedorSeguimiento.classList.add('d-none');
            errorTipoContacto.style.display = 'none';
            descripcionTipo.textContent = '';
            infoApoderado.textContent = '';
            infoFamilia.textContent = '';
            contadorDescripcion.textContent = '0';
            contadorObservaciones.textContent = '0';
            
            // Remover todas las clases de validación
            const inputs = form.querySelectorAll('.is-invalid, .is-valid');
            inputs.forEach(input => {
                input.classList.remove('is-invalid', 'is-valid');
            });

            // Restaurar botón
            const btnGuardar = document.getElementById('btnGuardarInteraccion');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Interacción';
        });
    }
});
</script>

<style>
/* Estilos adicionales para el modal */
#modalRegistrarInteraccion .card {
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

#modalRegistrarInteraccion .card-title {
    font-weight: 600;
    font-size: 0.95rem;
}

#modalRegistrarInteraccion .form-label {
    font-weight: 500;
    font-size: 0.9rem;
    color: #495057;
}

#modalRegistrarInteraccion .btn-check:checked + .btn-outline-primary {
    background-color: #b8cfe6;
    border-color: #b8cfe6;
    color: #2c3e50;
}

#modalRegistrarInteraccion .form-select:focus,
#modalRegistrarInteraccion .form-control:focus {
    border-color: #b8cfe6;
    box-shadow: 0 0 0 0.2rem rgba(184, 207, 230, 0.25);
}

#modalRegistrarInteraccion .modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

/* Mejorar apariencia de los switches */
#modalRegistrarInteraccion .form-check-input:checked {
    background-color: #b8cfe6;
    border-color: #b8cfe6;
}

/* Estilo para el texto de ayuda */
#modalRegistrarInteraccion .form-text {
    font-size: 0.8rem;
}
</style>