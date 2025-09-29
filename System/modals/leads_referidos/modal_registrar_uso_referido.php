<style>
/* Estilos para asegurar que SweetAlert2 se muestre por encima de todo */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

.modal {
    z-index: 9999 !important;
}

.modal-backdrop {
    z-index: 9998 !important;
}

/* Estilos para indicadores de validación */
.validacion-ok {
    color: #28a745;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.validacion-error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.codigo-disponible {
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.codigo-no-disponible {
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.info-codigo-panel {
    background-color: #f8f9fa;
    border-left: 4px solid #17a2b8;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 4px;
}

.info-lead-panel {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 4px;
}
</style>

<!-- Modal Registrar Uso de Código de Referido -->
<div class="modal fade" id="modalRegistrarUsoReferido" tabindex="-1" aria-labelledby="modalRegistrarUsoReferidoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalRegistrarUsoReferidoLabel">
          <i class="ti ti-link me-2"></i>
          Registrar Uso de Código de Referido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formRegistrarUsoReferido" method="POST" action="acciones/leads_referidos/gestionar_referido.php" novalidate>
        <input type="hidden" name="accion" value="registrar_uso_referido">
        
        <div class="modal-body">
          
          <!-- Información Importante -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Importante:</strong> Vincule un lead con un código de referido para registrar que llegó por recomendación.
            Verifique que el código esté activo y tenga usos disponibles.
          </div>

          <div class="row">
            
            <!-- Sección: Código de Referido -->
            <div class="col-12">
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-ticket me-1"></i>
                    Código de Referido
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Código de Referido -->
                  <div class="mb-3">
                    <label for="reg_codigo_referido_id" class="form-label">
                      Código de Referido <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="reg_codigo_referido_id" name="codigo_referido_id" required>
                      <option value="">Seleccionar código...</option>
                      <?php
                      // Obtener códigos activos con información relevante
                      $sql_codigos = "SELECT 
                          cr.id,
                          cr.codigo,
                          cr.descripcion,
                          cr.beneficio_referido,
                          cr.limite_usos,
                          cr.usos_actuales,
                          cr.fecha_inicio,
                          cr.fecha_fin,
                          cr.activo,
                          CASE
                              WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
                              WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
                              ELSE 'General'
                          END as referente,
                          CASE
                              WHEN cr.limite_usos IS NULL THEN 'Ilimitado'
                              WHEN cr.limite_usos > cr.usos_actuales THEN CONCAT(cr.limite_usos - cr.usos_actuales, ' usos disponibles')
                              ELSE 'Sin usos disponibles'
                          END as disponibilidad
                      FROM codigos_referido cr
                      LEFT JOIN apoderados a ON cr.apoderado_id = a.id
                      LEFT JOIN familias f ON cr.familia_id = f.id
                      WHERE cr.activo = 1 
                      AND (cr.fecha_fin IS NULL OR cr.fecha_fin >= CURDATE())
                      AND (cr.limite_usos IS NULL OR cr.limite_usos > cr.usos_actuales)
                      ORDER BY cr.codigo ASC";
                      
                      $result_codigos = $conn->query($sql_codigos);
                      while($codigo = $result_codigos->fetch_assoc()) {
                          $disabled = '';
                          $info = " - " . $codigo['referente'] . " - " . $codigo['disponibilidad'];
                          
                          echo '<option value="' . $codigo['id'] . '" 
                                  data-codigo="' . htmlspecialchars($codigo['codigo']) . '"
                                  data-descripcion="' . htmlspecialchars($codigo['descripcion']) . '"
                                  data-beneficio="' . htmlspecialchars($codigo['beneficio_referido']) . '"
                                  data-referente="' . htmlspecialchars($codigo['referente']) . '"
                                  data-usos="' . $codigo['usos_actuales'] . '"
                                  data-limite="' . ($codigo['limite_usos'] ?? 'null') . '"
                                  data-fecha-inicio="' . $codigo['fecha_inicio'] . '"
                                  data-fecha-fin="' . ($codigo['fecha_fin'] ?? '') . '"
                                  ' . $disabled . '>' 
                                  . htmlspecialchars($codigo['codigo']) . $info . 
                                '</option>';
                      }
                      ?>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un código de referido válido
                    </div>
                    <div id="reg_codigo_status" class="form-text"></div>
                  </div>

                  <!-- Panel de Información del Código -->
                  <div id="reg_info_codigo_panel" class="info-codigo-panel" style="display: none;">
                    <h6 class="text-info mb-2">
                      <i class="ti ti-info-circle me-1"></i>
                      Información del Código
                    </h6>
                    <div class="row">
                      <div class="col-md-6">
                        <small class="text-muted">Código:</small>
                        <div class="fw-bold" id="reg_info_codigo">-</div>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted">Referente:</small>
                        <div class="fw-bold" id="reg_info_referente">-</div>
                      </div>
                      <div class="col-md-12 mt-2">
                        <small class="text-muted">Descripción:</small>
                        <div id="reg_info_descripcion">-</div>
                      </div>
                      <div class="col-md-12 mt-2">
                        <small class="text-muted">Beneficio para el Referido:</small>
                        <div class="text-success fw-bold" id="reg_info_beneficio">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Usos Actuales:</small>
                        <div id="reg_info_usos">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Vigencia:</small>
                        <div id="reg_info_vigencia">-</div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Sección: Lead a Vincular -->
            <div class="col-12">
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-user-check me-1"></i>
                    Lead a Vincular
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Lead -->
                  <div class="mb-3">
                    <label for="reg_lead_id" class="form-label">
                      Lead <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="reg_lead_id" name="lead_id" required>
                      <option value="">Seleccionar lead...</option>
                      <?php
                      // Obtener leads que NO estén ya vinculados a un código
                      $sql_leads = "SELECT 
                          l.id,
                          l.codigo_lead,
                          CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as nombre_estudiante,
                          CONCAT(l.nombres_contacto, ' ', IFNULL(l.apellidos_contacto, '')) as nombre_contacto,
                          l.telefono,
                          l.email,
                          el.nombre as estado_lead,
                          cc.nombre as canal_captacion,
                          l.created_at
                      FROM leads l
                      LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
                      LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
                      WHERE l.id NOT IN (SELECT lead_id FROM usos_referido)
                      AND l.activo = 1
                      ORDER BY l.created_at DESC
                      LIMIT 100";
                      
                      $result_leads = $conn->query($sql_leads);
                      while($lead = $result_leads->fetch_assoc()) {
                          $info = " - " . $lead['nombre_contacto'] . " - " . $lead['estado_lead'];
                          
                          echo '<option value="' . $lead['id'] . '" 
                                  data-codigo-lead="' . htmlspecialchars($lead['codigo_lead']) . '"
                                  data-estudiante="' . htmlspecialchars($lead['nombre_estudiante']) . '"
                                  data-contacto="' . htmlspecialchars($lead['nombre_contacto']) . '"
                                  data-telefono="' . htmlspecialchars($lead['telefono']) . '"
                                  data-email="' . htmlspecialchars($lead['email']) . '"
                                  data-estado="' . htmlspecialchars($lead['estado_lead']) . '"
                                  data-canal="' . htmlspecialchars($lead['canal_captacion']) . '">' 
                                  . htmlspecialchars($lead['codigo_lead'] . ' - ' . $lead['nombre_estudiante']) . $info . 
                                '</option>';
                      }
                      ?>
                    </select>
                    <div class="invalid-feedback">
                      Debe seleccionar un lead válido
                    </div>
                    <small class="form-text text-muted">
                      Solo se muestran leads que no han sido vinculados a ningún código
                    </small>
                    <div id="reg_lead_status" class="form-text"></div>
                  </div>

                  <!-- Panel de Información del Lead -->
                  <div id="reg_info_lead_panel" class="info-lead-panel" style="display: none;">
                    <h6 class="text-warning mb-2">
                      <i class="ti ti-user me-1"></i>
                      Información del Lead
                    </h6>
                    <div class="row">
                      <div class="col-md-6">
                        <small class="text-muted">Código Lead:</small>
                        <div class="fw-bold" id="reg_info_codigo_lead">-</div>
                      </div>
                      <div class="col-md-6">
                        <small class="text-muted">Estudiante:</small>
                        <div class="fw-bold" id="reg_info_estudiante">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Contacto:</small>
                        <div id="reg_info_contacto">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Estado:</small>
                        <div id="reg_info_estado">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Teléfono:</small>
                        <div id="reg_info_telefono">-</div>
                      </div>
                      <div class="col-md-6 mt-2">
                        <small class="text-muted">Email:</small>
                        <div id="reg_info_email">-</div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Sección: Información Adicional -->
            <div class="col-12">
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-file-description me-1"></i>
                    Información Adicional
                  </h6>
                </div>
                <div class="card-body">
                  
                  <!-- Fecha de Uso -->
                  <div class="mb-3">
                    <label for="reg_fecha_uso" class="form-label">
                      Fecha de Uso del Código <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="reg_fecha_uso" name="fecha_uso" 
                           required 
                           max="<?php echo date('Y-m-d'); ?>"
                           title="Fecha en que el lead utilizó el código">
                    <div class="invalid-feedback">
                      La fecha no puede ser futura
                    </div>
                    <small class="form-text text-muted">
                      Fecha en que el lead mencionó o utilizó el código de referido
                    </small>
                  </div>

                  <!-- Observaciones -->
                  <div class="mb-3">
                    <label for="reg_observaciones" class="form-label">
                      Observaciones
                    </label>
                    <textarea class="form-control" id="reg_observaciones" name="observaciones" 
                              rows="3" maxlength="500"
                              placeholder="Ej: El lead mencionó el código al momento de la primera llamada..."
                              title="Información adicional sobre el uso del código"></textarea>
                    <div class="form-text">
                      <span id="reg_observaciones_contador">0</span>/500 caracteres
                    </div>
                  </div>

                  <!-- Validación Previa -->
                  <div class="alert alert-warning" role="alert" id="reg_validacion_previa" style="display: none;">
                    <i class="ti ti-alert-triangle me-1"></i>
                    <strong>Validación:</strong>
                    <ul class="mb-0 mt-2" id="reg_validacion_lista"></ul>
                  </div>

                </div>
              </div>
            </div>

          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" onclick="validarVinculacion()">
            <i class="ti ti-check me-1"></i>
            Validar Vinculación
          </button>
          <button type="submit" class="btn btn-primary" id="reg_btn_guardar">
            <i class="ti ti-device-floppy me-1"></i>
            Registrar Uso
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const regForm = document.getElementById('formRegistrarUsoReferido');
    const regCodigoSelect = document.getElementById('reg_codigo_referido_id');
    const regLeadSelect = document.getElementById('reg_lead_id');
    const regFechaUso = document.getElementById('reg_fecha_uso');
    const regObservaciones = document.getElementById('reg_observaciones');
    
    // Establecer fecha actual por defecto
    regFechaUso.value = new Date().toISOString().split('T')[0];

    // Manejar cambio de código de referido
    regCodigoSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const infoCodigo = document.getElementById('reg_info_codigo_panel');
        const statusDiv = document.getElementById('reg_codigo_status');
        
        if (this.value) {
            // Mostrar información del código
            document.getElementById('reg_info_codigo').textContent = option.dataset.codigo;
            document.getElementById('reg_info_referente').textContent = option.dataset.referente;
            document.getElementById('reg_info_descripcion').textContent = option.dataset.descripcion || 'Sin descripción';
            document.getElementById('reg_info_beneficio').textContent = option.dataset.beneficio || 'Sin beneficio especificado';
            
            const limite = option.dataset.limite;
            const usos = option.dataset.usos;
            if (limite === 'null') {
                document.getElementById('reg_info_usos').innerHTML = `<span class="text-success">${usos} (Ilimitado)</span>`;
            } else {
                const disponibles = parseInt(limite) - parseInt(usos);
                const colorClass = disponibles > 5 ? 'text-success' : disponibles > 0 ? 'text-warning' : 'text-danger';
                document.getElementById('reg_info_usos').innerHTML = `<span class="${colorClass}">${usos} / ${limite} (${disponibles} disponibles)</span>`;
            }
            
            const fechaInicio = option.dataset.fechaInicio;
            const fechaFin = option.dataset.fechaFin;
            const vigenciaText = fechaFin ? `${fechaInicio} al ${fechaFin}` : `Desde ${fechaInicio} (Sin vencimiento)`;
            document.getElementById('reg_info_vigencia').textContent = vigenciaText;
            
            infoCodigo.style.display = 'block';
            statusDiv.innerHTML = '<span class="validacion-ok"><i class="ti ti-check"></i> Código válido y disponible</span>';
            this.classList.add('codigo-disponible');
            this.classList.remove('codigo-no-disponible');
        } else {
            infoCodigo.style.display = 'none';
            statusDiv.textContent = '';
            this.classList.remove('codigo-disponible', 'codigo-no-disponible');
        }
    });

    // Manejar cambio de lead
    regLeadSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const infoLead = document.getElementById('reg_info_lead_panel');
        const statusDiv = document.getElementById('reg_lead_status');
        
        if (this.value) {
            // Mostrar información del lead
            document.getElementById('reg_info_codigo_lead').textContent = option.dataset.codigoLead;
            document.getElementById('reg_info_estudiante').textContent = option.dataset.estudiante;
            document.getElementById('reg_info_contacto').textContent = option.dataset.contacto;
            document.getElementById('reg_info_estado').textContent = option.dataset.estado;
            document.getElementById('reg_info_telefono').textContent = option.dataset.telefono || 'No registrado';
            document.getElementById('reg_info_email').textContent = option.dataset.email || 'No registrado';
            
            infoLead.style.display = 'block';
            statusDiv.innerHTML = '<span class="validacion-ok"><i class="ti ti-check"></i> Lead seleccionado correctamente</span>';
        } else {
            infoLead.style.display = 'none';
            statusDiv.textContent = '';
        }
    });

    // Validar fecha de uso
    regFechaUso.addEventListener('change', function() {
        const fechaSeleccionada = new Date(this.value);
        const fechaActual = new Date();
        fechaActual.setHours(0, 0, 0, 0);
        
        if (fechaSeleccionada > fechaActual) {
            this.setCustomValidity('La fecha no puede ser futura');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });

    // Contador de caracteres para observaciones
    regObservaciones.addEventListener('input', function() {
        const contador = document.getElementById('reg_observaciones_contador');
        contador.textContent = this.value.length;
        
        if (this.value.length > 450) {
            contador.classList.add('text-warning');
        } else {
            contador.classList.remove('text-warning');
        }
    });

    // Función para validar vinculación
    window.validarVinculacion = function() {
        const validaciones = [];
        const codigoId = regCodigoSelect.value;
        const leadId = regLeadSelect.value;
        const fecha = regFechaUso.value;
        
        // Validar que se haya seleccionado código
        if (!codigoId) {
            validaciones.push('Debe seleccionar un código de referido');
        }
        
        // Validar que se haya seleccionado lead
        if (!leadId) {
            validaciones.push('Debe seleccionar un lead');
        }
        
        // Validar fecha
        if (!fecha) {
            validaciones.push('Debe ingresar la fecha de uso');
        } else {
            const fechaSeleccionada = new Date(fecha);
            const fechaActual = new Date();
            fechaActual.setHours(0, 0, 0, 0);
            
            if (fechaSeleccionada > fechaActual) {
                validaciones.push('La fecha de uso no puede ser futura');
            }
        }
        
        const alertaValidacion = document.getElementById('reg_validacion_previa');
        const listaValidacion = document.getElementById('reg_validacion_lista');
        
        if (validaciones.length > 0) {
            listaValidacion.innerHTML = validaciones.map(v => `<li>${v}</li>`).join('');
            alertaValidacion.style.display = 'block';
            
            Swal.fire({
                title: 'Validación Incompleta',
                html: '<ul class="text-start">' + validaciones.map(v => `<li>${v}</li>`).join('') + '</ul>',
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
        } else {
            alertaValidacion.style.display = 'none';
            
            const codigoOption = regCodigoSelect.options[regCodigoSelect.selectedIndex];
            const leadOption = regLeadSelect.options[regLeadSelect.selectedIndex];
            
            Swal.fire({
                title: 'Validación Exitosa',
                html: `
                    <div class="text-start">
                        <p><strong>Código:</strong> ${codigoOption.dataset.codigo}</p>
                        <p><strong>Lead:</strong> ${leadOption.dataset.estudiante}</p>
                        <p><strong>Contacto:</strong> ${leadOption.dataset.contacto}</p>
                        <p><strong>Fecha uso:</strong> ${fecha}</p>
                        <hr>
                        <p class="text-success"><i class="ti ti-check-circle"></i> Todos los datos son correctos</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
        }
    };

    // Validación del formulario al enviar
    regForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!regForm.checkValidity()) {
            e.stopPropagation();
            regForm.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor complete todos los campos requeridos correctamente',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            
            return false;
        }
        
        // Confirmar antes de registrar
        const codigoOption = regCodigoSelect.options[regCodigoSelect.selectedIndex];
        const leadOption = regLeadSelect.options[regLeadSelect.selectedIndex];
        
        Swal.fire({
            title: '¿Registrar Uso de Código?',
            html: `
                <div class="text-start">
                    <p>Se vinculará el lead con el código de referido:</p>
                    <hr>
                    <p><strong>Código:</strong> ${codigoOption.dataset.codigo}</p>
                    <p><strong>Lead:</strong> ${leadOption.dataset.estudiante}</p>
                    <p><strong>Contacto:</strong> ${leadOption.dataset.contacto}</p>
                    <p><strong>Beneficio:</strong> ${codigoOption.dataset.beneficio || 'Ninguno'}</p>
                    <hr>
                    <p class="text-muted"><small>Esta acción se puede revertir desde la gestión de referidos</small></p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Registrando...',
                    html: 'Guardando uso de código de referido',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                regForm.submit();
            }
        });
    });

    // Limpiar formulario al cerrar modal
    document.getElementById('modalRegistrarUsoReferido').addEventListener('hidden.bs.modal', function() {
        regForm.reset();
        regForm.classList.remove('was-validated');
        document.getElementById('reg_info_codigo_panel').style.display = 'none';
        document.getElementById('reg_info_lead_panel').style.display = 'none';
        document.getElementById('reg_validacion_previa').style.display = 'none';
        document.getElementById('reg_codigo_status').textContent = '';
        document.getElementById('reg_lead_status').textContent = '';
        document.getElementById('reg_observaciones_contador').textContent = '0';
        regCodigoSelect.classList.remove('codigo-disponible', 'codigo-no-disponible');
        regFechaUso.value = new Date().toISOString().split('T')[0];
    });
});
</script>