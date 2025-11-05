<?php
// Obtener eventos programados para el selector
$sql_eventos = "SELECT id, titulo, tipo, fecha_inicio, ubicacion 
                FROM eventos 
                WHERE estado = 'programado' 
                ORDER BY fecha_inicio ASC";
$result_eventos = $conn->query($sql_eventos);

// Obtener apoderados activos
$sql_apoderados = "SELECT a.id, a.nombres, a.apellidos, a.tipo_apoderado, 
                          f.codigo_familia
                   FROM apoderados a
                   INNER JOIN familias f ON a.familia_id = f.id
                   WHERE a.activo = 1
                   ORDER BY a.apellidos, a.nombres";
$result_apoderados = $conn->query($sql_apoderados);

// Obtener familias activas
$sql_familias = "SELECT id, codigo_familia, direccion, distrito
                 FROM familias 
                 WHERE activo = 1
                 ORDER BY codigo_familia";
$result_familias = $conn->query($sql_familias);
?>

<style>
/* Estilos para asegurar que SweetAlert2 se muestre por encima de todo */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

/* Estilo para que el modal esté por debajo del SweetAlert */
.modal {
    z-index: 9999 !important;
}

/* Estilo para el backdrop del modal */
.modal-backdrop {
    z-index: 9998 !important;
}

/* Estilos personalizados para el modal */
.evento-info-card {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.participante-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.participante-item:hover {
    background-color: #e9ecef;
}

.btn-remove-participante {
    padding: 2px 8px;
    font-size: 0.75rem;
}

.lista-participantes-container {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 10px;
}

.badge-tipo-participante {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
}
</style>

<!-- Modal Organizar Evento -->
<div class="modal fade" id="modalOrganizarEvento" tabindex="-1" aria-labelledby="modalOrganizarEventoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%); color: white;">
        <h5 class="modal-title" id="modalOrganizarEventoLabel">
          <i class="ti ti-calendar-event me-2"></i>
          Organizar Evento Especial
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formOrganizarEvento" method="POST" action="" novalidate>
        <input type="hidden" name="accion" value="organizar_evento">
        <input type="hidden" name="participantes" id="participantes_json" value="[]">
        
        <div class="modal-body">
          
          <!-- Información Introductoria -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Organizar Evento:</strong> Seleccione el evento que desea iniciar e invite a participantes (apoderados o familias).
          </div>

          <div class="row">
            <!-- Columna Izquierda: Selección de Evento -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header" style="background-color: #e8f5e9;">
                  <h6 class="mb-0">
                    <i class="ti ti-calendar me-1"></i>
                    Seleccionar Evento
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Selector de Evento -->
                  <div class="mb-3">
                    <label for="evento_id" class="form-label">
                      Evento a Organizar <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="evento_id" name="evento_id" required>
                      <option value="">Seleccionar evento...</option>
                      <?php
                      if ($result_eventos->num_rows > 0) {
                          while($evento = $result_eventos->fetch_assoc()) {
                              $fecha_formato = date('d/m/Y H:i', strtotime($evento['fecha_inicio']));
                              $tipo_texto = ucfirst(str_replace('_', ' ', $evento['tipo']));
                              echo "<option value='" . $evento['id'] . "' 
                                    data-titulo='" . htmlspecialchars($evento['titulo']) . "'
                                    data-tipo='" . htmlspecialchars($tipo_texto) . "'
                                    data-fecha='" . $fecha_formato . "'
                                    data-ubicacion='" . htmlspecialchars($evento['ubicacion']) . "'>
                                    " . htmlspecialchars($evento['titulo']) . " - " . $fecha_formato . "
                                    </option>";
                          }
                      }
                      ?>
                    </select>
                    <div class="invalid-feedback">
                      Por favor seleccione un evento
                    </div>
                  </div>

                  <!-- Información del Evento Seleccionado -->
                  <div id="evento-info" style="display: none;">
                    <div class="evento-info-card">
                      <h6 class="mb-2">
                        <i class="ti ti-info-circle me-1"></i>
                        Detalles del Evento
                      </h6>
                      <div class="row g-2">
                        <div class="col-12">
                          <small class="text-muted">Título:</small>
                          <div class="fw-bold" id="info-titulo">-</div>
                        </div>
                        <div class="col-6">
                          <small class="text-muted">Tipo:</small>
                          <div class="fw-bold" id="info-tipo">-</div>
                        </div>
                        <div class="col-6">
                          <small class="text-muted">Fecha:</small>
                          <div class="fw-bold" id="info-fecha">-</div>
                        </div>
                        <div class="col-12">
                          <small class="text-muted">Ubicación:</small>
                          <div class="fw-bold" id="info-ubicacion">-</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Agregar Participantes -->
                  <div class="mb-3">
                    <label class="form-label">
                      <i class="ti ti-users me-1"></i>
                      Agregar Participantes
                    </label>
                    
                    <div class="btn-group w-100 mb-2" role="group">
                      <input type="radio" class="btn-check" name="tipo_participante" id="tipo_apoderado" value="apoderado" checked>
                      <label class="btn btn-outline-primary" for="tipo_apoderado">
                        <i class="ti ti-user me-1"></i>
                        Apoderado
                      </label>

                      <input type="radio" class="btn-check" name="tipo_participante" id="tipo_familia" value="familia">
                      <label class="btn btn-outline-primary" for="tipo_familia">
                        <i class="ti ti-home me-1"></i>
                        Familia
                      </label>
                    </div>

                    <!-- Selector de Apoderado -->
                    <div id="selector-apoderado" class="selector-participante">
                      <select class="form-select" id="apoderado_selector">
                        <option value="">Seleccionar apoderado...</option>
                        <?php
                        if ($result_apoderados->num_rows > 0) {
                            while($apoderado = $result_apoderados->fetch_assoc()) {
                                echo "<option value='" . $apoderado['id'] . "' 
                                      data-nombre='" . htmlspecialchars($apoderado['nombres'] . ' ' . $apoderado['apellidos']) . "'
                                      data-tipo='" . htmlspecialchars($apoderado['tipo_apoderado']) . "'
                                      data-familia='" . htmlspecialchars($apoderado['codigo_familia']) . "'>
                                      " . htmlspecialchars($apoderado['apellidos'] . ', ' . $apoderado['nombres']) . 
                                      " (" . ucfirst($apoderado['tipo_apoderado']) . " - Fam: " . $apoderado['codigo_familia'] . ")
                                      </option>";
                            }
                        }
                        ?>
                      </select>
                    </div>

                    <!-- Selector de Familia -->
                    <div id="selector-familia" class="selector-participante" style="display: none;">
                      <select class="form-select" id="familia_selector">
                        <option value="">Seleccionar familia...</option>
                        <?php
                        if ($result_familias->num_rows > 0) {
                            while($familia = $result_familias->fetch_assoc()) {
                                $direccion_corta = substr($familia['direccion'], 0, 30);
                                echo "<option value='" . $familia['id'] . "' 
                                      data-codigo='" . htmlspecialchars($familia['codigo_familia']) . "'
                                      data-direccion='" . htmlspecialchars($familia['direccion']) . "'
                                      data-distrito='" . htmlspecialchars($familia['distrito']) . "'>
                                      " . htmlspecialchars($familia['codigo_familia']) . 
                                      " - " . htmlspecialchars($direccion_corta) . "... (" . $familia['distrito'] . ")
                                      </option>";
                            }
                        }
                        ?>
                      </select>
                    </div>

                    <button type="button" class="btn btn-success btn-sm w-100 mt-2" id="btn-agregar-participante">
                      <i class="ti ti-plus me-1"></i>
                      Agregar Participante
                    </button>
                  </div>

                </div>
              </div>
            </div>

            <!-- Columna Derecha: Lista de Participantes -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header" style="background-color: #e3f2fd;">
                  <h6 class="mb-0">
                    <i class="ti ti-users me-1"></i>
                    Participantes Invitados
                    <span class="badge bg-primary float-end" id="contador-participantes">0</span>
                  </h6>
                </div>
                <div class="card-body">
                  
                  <div class="lista-participantes-container" id="lista-participantes">
                    <div class="text-center text-muted py-3">
                      <i class="ti ti-user-plus" style="font-size: 2rem;"></i>
                      <p class="mt-2">No hay participantes agregados</p>
                      <small>Seleccione apoderados o familias para invitar al evento</small>
                    </div>
                  </div>

                  <!-- Acciones rápidas -->
                  <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-limpiar-participantes">
                      <i class="ti ti-trash me-1"></i>
                      Limpiar Lista
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm float-end" id="btn-invitar-todos-padres">
                      <i class="ti ti-users me-1"></i>
                      Invitar Todos los Padres
                    </button>
                  </div>

                </div>
              </div>

              <!-- Instrucciones -->
              <div class="alert alert-warning mt-3" role="alert">
                <i class="ti ti-alert-triangle me-1"></i>
                <strong>Importante:</strong>
                <ul class="mb-0 mt-2">
                  <li>Al organizar el evento, su estado cambiará a <strong>"En Curso"</strong></li>
                  <li>Los participantes agregados recibirán el estado <strong>"Invitado"</strong></li>
                  <li>Puede agregar más participantes después de organizar el evento</li>
                </ul>
              </div>
            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i class="ti ti-calendar-check me-1"></i>
            Organizar e Iniciar Evento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formOrganizarEvento = document.getElementById('formOrganizarEvento');
    const eventoSelector = document.getElementById('evento_id');
    const eventoInfo = document.getElementById('evento-info');
    const tipoApoderado = document.getElementById('tipo_apoderado');
    const tipoFamilia = document.getElementById('tipo_familia');
    const selectorApoderado = document.getElementById('selector-apoderado');
    const selectorFamilia = document.getElementById('selector-familia');
    const apoderadoSelector = document.getElementById('apoderado_selector');
    const familiaSelector = document.getElementById('familia_selector');
    const btnAgregarParticipante = document.getElementById('btn-agregar-participante');
    const listaParticipantes = document.getElementById('lista-participantes');
    const contadorParticipantes = document.getElementById('contador-participantes');
    const btnLimpiarParticipantes = document.getElementById('btn-limpiar-participantes');
    const btnInvitarTodosPadres = document.getElementById('btn-invitar-todos-padres');
    const participantesJson = document.getElementById('participantes_json');
    
    let participantes = [];

    // Mostrar información del evento seleccionado
    eventoSelector.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const titulo = selectedOption.getAttribute('data-titulo');
            const tipo = selectedOption.getAttribute('data-tipo');
            const fecha = selectedOption.getAttribute('data-fecha');
            const ubicacion = selectedOption.getAttribute('data-ubicacion');
            
            document.getElementById('info-titulo').textContent = titulo;
            document.getElementById('info-tipo').textContent = tipo;
            document.getElementById('info-fecha').textContent = fecha;
            document.getElementById('info-ubicacion').textContent = ubicacion;
            
            eventoInfo.style.display = 'block';
        } else {
            eventoInfo.style.display = 'none';
        }
    });

    // Cambiar entre selector de apoderado y familia
    tipoApoderado.addEventListener('change', function() {
        if (this.checked) {
            selectorApoderado.style.display = 'block';
            selectorFamilia.style.display = 'none';
        }
    });

    tipoFamilia.addEventListener('change', function() {
        if (this.checked) {
            selectorApoderado.style.display = 'none';
            selectorFamilia.style.display = 'block';
        }
    });

    // Agregar participante
    btnAgregarParticipante.addEventListener('click', function() {
        const tipoSeleccionado = document.querySelector('input[name="tipo_participante"]:checked').value;
        
        if (tipoSeleccionado === 'apoderado') {
            const apoderadoId = apoderadoSelector.value;
            if (!apoderadoId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección requerida',
                    text: 'Por favor seleccione un apoderado'
                });
                return;
            }
            
            const option = apoderadoSelector.options[apoderadoSelector.selectedIndex];
            const nombre = option.getAttribute('data-nombre');
            const tipo = option.getAttribute('data-tipo');
            const familia = option.getAttribute('data-familia');
            
            // Verificar si ya está agregado
            if (participantes.find(p => p.apoderado_id === apoderadoId)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Participante duplicado',
                    text: 'Este apoderado ya está en la lista'
                });
                return;
            }
            
            participantes.push({
                tipo: 'apoderado',
                apoderado_id: apoderadoId,
                nombre: nombre,
                subtipo: tipo,
                familia: familia
            });
            
            apoderadoSelector.value = '';
        } else {
            const familiaId = familiaSelector.value;
            if (!familiaId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección requerida',
                    text: 'Por favor seleccione una familia'
                });
                return;
            }
            
            const option = familiaSelector.options[familiaSelector.selectedIndex];
            const codigo = option.getAttribute('data-codigo');
            const direccion = option.getAttribute('data-direccion');
            const distrito = option.getAttribute('data-distrito');
            
            // Verificar si ya está agregada
            if (participantes.find(p => p.familia_id === familiaId)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Participante duplicado',
                    text: 'Esta familia ya está en la lista'
                });
                return;
            }
            
            participantes.push({
                tipo: 'familia',
                familia_id: familiaId,
                codigo: codigo,
                direccion: direccion,
                distrito: distrito
            });
            
            familiaSelector.value = '';
        }
        
        actualizarListaParticipantes();
    });

    // Actualizar lista visual de participantes
    function actualizarListaParticipantes() {
        if (participantes.length === 0) {
            listaParticipantes.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="ti ti-user-plus" style="font-size: 2rem;"></i>
                    <p class="mt-2">No hay participantes agregados</p>
                    <small>Seleccione apoderados o familias para invitar al evento</small>
                </div>
            `;
            contadorParticipantes.textContent = '0';
            participantesJson.value = '[]';
            return;
        }
        
        let html = '';
        participantes.forEach((p, index) => {
            if (p.tipo === 'apoderado') {
                html += `
                    <div class="participante-item">
                        <div>
                            <span class="badge badge-tipo-participante bg-primary">Apoderado</span>
                            <strong class="ms-2">${p.nombre}</strong>
                            <br>
                            <small class="text-muted">Tipo: ${p.subtipo} | Familia: ${p.familia}</small>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-remove-participante" onclick="removerParticipante(${index})">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                `;
            } else {
                html += `
                    <div class="participante-item">
                        <div>
                            <span class="badge badge-tipo-participante bg-success">Familia</span>
                            <strong class="ms-2">${p.codigo}</strong>
                            <br>
                            <small class="text-muted">${p.direccion} - ${p.distrito}</small>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-remove-participante" onclick="removerParticipante(${index})">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                `;
            }
        });
        
        listaParticipantes.innerHTML = html;
        contadorParticipantes.textContent = participantes.length;
        participantesJson.value = JSON.stringify(participantes);
    }

    // Función global para remover participante
    window.removerParticipante = function(index) {
        participantes.splice(index, 1);
        actualizarListaParticipantes();
    };

    // Limpiar lista de participantes
    btnLimpiarParticipantes.addEventListener('click', function() {
        if (participantes.length === 0) return;
        
        Swal.fire({
            title: '¿Limpiar lista?',
            text: 'Se eliminarán todos los participantes agregados',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                participantes = [];
                actualizarListaParticipantes();
                Swal.fire('Limpiado', 'La lista ha sido limpiada', 'success');
            }
        });
    });

    // Invitar todos los padres (apoderados titulares)
    btnInvitarTodosPadres.addEventListener('click', function() {
        Swal.fire({
            title: 'Invitar todos los padres',
            text: '¿Desea agregar a todos los apoderados titulares activos?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, agregar todos',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Agregar todos los apoderados titulares que no estén ya en la lista
                let agregados = 0;
                for (let i = 0; i < apoderadoSelector.options.length; i++) {
                    const option = apoderadoSelector.options[i];
                    if (option.value) {
                        const apoderadoId = option.value;
                        const tipo = option.getAttribute('data-tipo');
                        
                        // Solo agregar titulares y que no estén duplicados
                        if (tipo === 'titular' && !participantes.find(p => p.apoderado_id === apoderadoId)) {
                            participantes.push({
                                tipo: 'apoderado',
                                apoderado_id: apoderadoId,
                                nombre: option.getAttribute('data-nombre'),
                                subtipo: tipo,
                                familia: option.getAttribute('data-familia')
                            });
                            agregados++;
                        }
                    }
                }
                
                actualizarListaParticipantes();
                Swal.fire('Agregados', `Se agregaron ${agregados} apoderados titulares`, 'success');
            }
        });
    });

    // Validación y envío del formulario
    formOrganizarEvento.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        if (!eventoSelector.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Evento requerido',
                text: 'Por favor seleccione un evento para organizar'
            });
            return;
        }
        
        // Confirmación final
        Swal.fire({
            title: '¿Organizar Evento?',
            html: `
                <p>Se organizará el evento: <strong>${eventoSelector.options[eventoSelector.selectedIndex].text}</strong></p>
                <p>Participantes invitados: <strong>${participantes.length}</strong></p>
                <p class="text-warning">El estado del evento cambiará a "En Curso"</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, organizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalOrganizarEvento').addEventListener('hidden.bs.modal', function() {
        formOrganizarEvento.reset();
        formOrganizarEvento.classList.remove('was-validated');
        participantes = [];
        actualizarListaParticipantes();
        eventoInfo.style.display = 'none';
        selectorApoderado.style.display = 'block';
        selectorFamilia.style.display = 'none';
        tipoApoderado.checked = true;
    });
});
</script>