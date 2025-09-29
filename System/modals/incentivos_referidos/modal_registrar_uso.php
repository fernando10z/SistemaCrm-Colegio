<!-- modals/incentivos_referidos/modal_registrar_uso.php -->
<style>
.lead-search-result {
    padding: 10px;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    margin: 5px 0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.lead-search-result:hover {
    background-color: #F5F5F5;
    border-color: #D4B8E6;
}

.lead-search-result.selected {
    background-color: #D4B8E6;
    border-color: #6f42c1;
    color: #4B0082;
}

.codigo-valido-badge {
    background-color: #B8E6B8;
    color: #2d5016;
    padding: 5px 10px;
    border-radius: 8px;
    font-weight: 600;
}

.codigo-invalido-badge {
    background-color: #FFB8B8;
    color: #8B0000;
    padding: 5px 10px;
    border-radius: 8px;
    font-weight: 600;
}

.beneficio-aplicable {
    background-color: #FFF4B8;
    border-left: 4px solid #FFD700;
    padding: 10px;
    border-radius: 8px;
    margin: 10px 0;
}
</style>

<!-- Modal Registrar Uso -->
<div class="modal fade" id="modalRegistrarUso" tabindex="-1" aria-labelledby="modalRegistrarUsoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #B8E6B8 0%, #B8D4E6 100%);">
        <h5 class="modal-title" id="modalRegistrarUsoLabel" style="color: #2d5016;">
          <i class="ti ti-user-plus me-2"></i>
          Registrar Uso de Código de Referido
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formRegistrarUso" method="POST" action="acciones/incentivos_referidos/gestionar_referidos.php" novalidate>
        <input type="hidden" name="accion" value="registrar_uso">
        <input type="hidden" name="lead_id_selected" id="lead_id_selected">
        
        <div class="modal-body">
          
          <!-- Alerta Informativa -->
          <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Registro de Uso:</strong> Indique qué código de referido utilizó un lead. 
            El sistema validará automáticamente la disponibilidad y aplicará los beneficios correspondientes.
          </div>

          <!-- Paso 1: Búsqueda de Lead -->
          <div class="card mb-3">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-search me-1"></i>
                Paso 1: Buscar Lead que Usará el Código
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Búsqueda de Lead -->
              <div class="mb-3">
                <label for="buscar_lead" class="form-label form-label-required">
                  Buscar Lead por Nombre, Documento o Email
                </label>
                <div class="input-group">
                  <span class="input-group-text" style="background-color: #B8E6B8;">
                    <i class="ti ti-search"></i>
                  </span>
                  <input type="text" class="form-control" id="buscar_lead" 
                         placeholder="Escriba nombre, DNI o email del lead"
                         minlength="3"
                         title="Escriba al menos 3 caracteres para buscar">
                  <button class="btn" type="button" id="btnBuscarLead" 
                          style="background-color: #B8E6B8; color: #2d5016;">
                    <i class="ti ti-search"></i> Buscar
                  </button>
                </div>
                <small class="form-text text-muted">
                  <i class="ti ti-alert-circle"></i> Solo se mostrarán leads activos sin conversión previa
                </small>
              </div>

              <!-- Resultados de Búsqueda -->
              <div id="resultadosBusquedaLead" style="display: none;">
                <label class="form-label">Seleccione un Lead:</label>
                <div id="listaLeads" class="mb-2"></div>
              </div>

              <!-- Lead Seleccionado -->
              <div id="leadSeleccionado" style="display: none;">
                <div class="alert alert-success">
                  <strong><i class="ti ti-check-circle"></i> Lead Seleccionado:</strong>
                  <div id="infoLeadSeleccionado" class="mt-2"></div>
                </div>
              </div>

            </div>
          </div>

          <!-- Paso 2: Código de Referido -->
          <div class="card mb-3">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-ticket me-1"></i>
                Paso 2: Ingresar Código de Referido Utilizado
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Código de Referido -->
              <div class="mb-3">
                <label for="codigo_referido" class="form-label form-label-required">
                  Código de Referido
                </label>
                <div class="input-group">
                  <span class="input-group-text" style="background-color: #D4B8E6;">
                    <i class="ti ti-ticket"></i>
                  </span>
                  <input type="text" class="form-control text-uppercase" id="codigo_referido" 
                         name="codigo_referido" required
                         pattern="^[A-Z0-9]{4,20}$"
                         style="font-family: 'Courier New', monospace; font-weight: bold;"
                         placeholder="Ingrese el código"
                         title="Código de referido que utilizó el lead">
                  <button class="btn" type="button" id="btnValidarCodigo" 
                          style="background-color: #D4B8E6; color: #4B0082;">
                    <i class="ti ti-check"></i> Validar
                  </button>
                </div>
                <div class="invalid-feedback">
                  Ingrese un código válido (4-20 caracteres alfanuméricos)
                </div>
              </div>

              <!-- Listado de Códigos Disponibles -->
              <div class="mb-3">
                <button type="button" class="btn btn-outline-info btn-sm" 
                        data-bs-toggle="collapse" data-bs-target="#codigosDisponibles">
                  <i class="ti ti-list"></i> Ver códigos disponibles
                </button>
                <div class="collapse" id="codigosDisponibles">
                  <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                    <?php
                    $codigos_activos_sql = "SELECT 
                        cr.codigo, 
                        cr.descripcion,
                        CASE 
                            WHEN cr.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
                            WHEN cr.familia_id IS NOT NULL THEN f.apellido_principal
                            ELSE 'Código General'
                        END as referente
                        FROM codigos_referido cr
                        LEFT JOIN apoderados a ON cr.apoderado_id = a.id
                        LEFT JOIN familias f ON cr.familia_id = f.id
                        WHERE cr.activo = 1
                        AND (cr.fecha_fin IS NULL OR cr.fecha_fin >= CURDATE())
                        AND (cr.limite_usos IS NULL OR cr.usos_actuales < cr.limite_usos)
                        ORDER BY cr.codigo";
                    
                    $codigos_activos = $conn->query($codigos_activos_sql);
                    if ($codigos_activos->num_rows > 0) {
                        echo '<table class="table table-sm table-hover">';
                        echo '<thead><tr><th>Código</th><th>Referente</th><th>Descripción</th></tr></thead>';
                        echo '<tbody>';
                        while($cod = $codigos_activos->fetch_assoc()) {
                            echo "<tr onclick=\"seleccionarCodigoLista('{$cod['codigo']}')\" style='cursor: pointer;'>";
                            echo "<td><strong>{$cod['codigo']}</strong></td>";
                            echo "<td>{$cod['referente']}</td>";
                            echo "<td><small>{$cod['descripcion']}</small></td>";
                            echo "</tr>";
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<div class="alert alert-warning">No hay códigos activos disponibles</div>';
                    }
                    ?>
                  </div>
                </div>
              </div>

              <!-- Estado del Código -->
              <div id="estadoCodigo" style="display: none;"></div>

            </div>
          </div>

          <!-- Paso 3: Confirmación y Observaciones -->
          <div class="card mb-3">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-notes me-1"></i>
                Paso 3: Observaciones y Confirmación
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Observaciones -->
              <div class="mb-3">
                <label for="observaciones_uso" class="form-label">
                  Observaciones (Opcional)
                </label>
                <textarea class="form-control" id="observaciones_uso" name="observaciones_uso" 
                          rows="3" maxlength="500"
                          placeholder="Información adicional sobre el uso del código"></textarea>
                <div class="character-counter">
                  <span id="observaciones-counter">0</span> / 500 caracteres
                </div>
              </div>

              <!-- Resumen del Registro -->
              <div id="resumenRegistro" class="alert alert-light" style="display: none;">
                <h6 class="alert-heading">
                  <i class="ti ti-clipboard-check"></i> Resumen del Registro
                </h6>
                <hr>
                <div id="contenidoResumen"></div>
              </div>

            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" id="btnValidarRegistro">
            <i class="ti ti-check me-1"></i>
            Validar Registro
          </button>
          <button type="submit" class="btn btn-success" id="btnGuardarUso" disabled>
            <i class="ti ti-device-floppy me-1"></i>
            Registrar Uso
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formUso = document.getElementById('formRegistrarUso');
    const buscarLeadInput = document.getElementById('buscar_lead');
    const codigoReferidoInput = document.getElementById('codigo_referido');
    const btnGuardarUso = document.getElementById('btnGuardarUso');
    const observacionesInput = document.getElementById('observaciones_uso');
    
    let leadSeleccionadoData = null;
    let codigoValidadoData = null;

    // Búsqueda de Lead
    document.getElementById('btnBuscarLead').addEventListener('click', buscarLead);
    
    buscarLeadInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarLead();
        }
    });

    function buscarLead() {
        const termino = buscarLeadInput.value.trim();
        
        if (termino.length < 3) {
            Swal.fire({
                icon: 'warning',
                title: 'Búsqueda Inválida',
                text: 'Ingrese al menos 3 caracteres para buscar',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        Swal.fire({
            title: 'Buscando...',
            text: 'Consultando leads en el sistema',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'acciones/incentivos_referidos/buscar_lead.php',
            type: 'POST',
            dataType: 'json',
            data: {
                termino: termino,
                accion: 'buscar_lead'
            },
            success: function(data) {
                Swal.close();
                
                if (data.success && data.leads.length > 0) {
                    mostrarResultadosLead(data.leads);
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin Resultados',
                        text: data.message || 'No se encontraron leads que coincidan con la búsqueda',
                        confirmButtonColor: '#6f42c1'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Búsqueda',
                    text: 'No se pudo realizar la búsqueda',
                    confirmButtonColor: '#6f42c1'
                });
            }
        });
    }

    function mostrarResultadosLead(leads) {
        const listaLeads = document.getElementById('listaLeads');
        listaLeads.innerHTML = '';
        
        leads.forEach(lead => {
            const leadDiv = document.createElement('div');
            leadDiv.className = 'lead-search-result';
            leadDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${lead.nombres_estudiante} ${lead.apellidos_estudiante || ''}</strong>
                        <br>
                        <small class="text-muted">
                            <i class="ti ti-user"></i> Contacto: ${lead.nombres_contacto} ${lead.apellidos_contacto || ''}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="ti ti-phone"></i> ${lead.telefono || 'Sin teléfono'}
                            ${lead.email ? `| <i class="ti ti-mail"></i> ${lead.email}` : ''}
                        </small>
                    </div>
                    <div>
                        <span class="badge" style="background-color: #B8D4E6; color: #00008B;">
                            ${lead.estado_nombre}
                        </span>
                    </div>
                </div>
            `;
            
            leadDiv.addEventListener('click', function() {
                seleccionarLead(lead, this);
            });
            
            listaLeads.appendChild(leadDiv);
        });
        
        document.getElementById('resultadosBusquedaLead').style.display = 'block';
    }

    function seleccionarLead(lead, elemento) {
        // Remover selección previa
        document.querySelectorAll('.lead-search-result').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Marcar como seleccionado
        elemento.classList.add('selected');
        
        // Guardar datos del lead
        leadSeleccionadoData = lead;
        document.getElementById('lead_id_selected').value = lead.id;
        
        // Mostrar información del lead seleccionado
        document.getElementById('infoLeadSeleccionado').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Estudiante:</strong> ${lead.nombres_estudiante} ${lead.apellidos_estudiante || ''}<br>
                    <strong>Contacto:</strong> ${lead.nombres_contacto} ${lead.apellidos_contacto || ''}
                </div>
                <div class="col-md-6">
                    <strong>Teléfono:</strong> ${lead.telefono || 'No disponible'}<br>
                    <strong>Email:</strong> ${lead.email || 'No disponible'}
                </div>
            </div>
        `;
        document.getElementById('leadSeleccionado').style.display = 'block';
        
        validarFormulario();
    }

    // Convertir código a mayúsculas
    codigoReferidoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Validar código
    document.getElementById('btnValidarCodigo').addEventListener('click', validarCodigo);
    
    codigoReferidoInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            validarCodigo();
        }
    });

    function validarCodigo() {
        const codigo = codigoReferidoInput.value.trim();
        
        if (codigo.length < 4) {
            Swal.fire({
                icon: 'warning',
                title: 'Código Inválido',
                text: 'El código debe tener al menos 4 caracteres',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        Swal.fire({
            title: 'Validando código...',
            text: 'Verificando disponibilidad',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'acciones/incentivos_referidos/validar_codigo_uso.php',
            type: 'POST',
            dataType: 'json',
            data: {
                codigo: codigo,
                accion: 'validar_codigo_uso'
            },
            success: function(data) {
                Swal.close();
                
                if (data.valido) {
                    mostrarCodigoValido(data.codigo_data);
                    codigoValidadoData = data.codigo_data;
                    codigoReferidoInput.classList.add('is-valid');
                    codigoReferidoInput.classList.remove('is-invalid');
                } else {
                    mostrarCodigoInvalido(data.message);
                    codigoValidadoData = null;
                    codigoReferidoInput.classList.add('is-invalid');
                    codigoReferidoInput.classList.remove('is-valid');
                }
                
                validarFormulario();
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    text: 'No se pudo validar el código',
                    confirmButtonColor: '#6f42c1'
                });
            }
        });
    }

    function mostrarCodigoValido(codigoData) {
        const estadoCodigo = document.getElementById('estadoCodigo');
        estadoCodigo.innerHTML = `
            <div class="alert alert-success">
                <div class="d-flex align-items-center mb-2">
                    <span class="codigo-valido-badge">
                        <i class="ti ti-check-circle"></i> Código Válido
                    </span>
                    <strong class="ms-2" style="font-size: 1.2rem;">${codigoData.codigo}</strong>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Referente:</strong> ${codigoData.referente}</small><br>
                        <small><strong>Descripción:</strong> ${codigoData.descripcion || 'Sin descripción'}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Usos:</strong> ${codigoData.usos_actuales} de ${codigoData.limite_usos || '∞'}</small><br>
                        <small><strong>Vigencia:</strong> Hasta ${codigoData.fecha_fin || 'Sin límite'}</small>
                    </div>
                </div>
                <div class="beneficio-aplicable mt-2">
                    <strong><i class="ti ti-gift"></i> Beneficio para el nuevo referido:</strong><br>
                    ${codigoData.beneficio_referido}
                </div>
            </div>
        `;
        estadoCodigo.style.display = 'block';
    }

    function mostrarCodigoInvalido(mensaje) {
        const estadoCodigo = document.getElementById('estadoCodigo');
        estadoCodigo.innerHTML = `
            <div class="alert alert-danger">
                <span class="codigo-invalido-badge">
                    <i class="ti ti-x-circle"></i> Código No Válido
                </span>
                <hr>
                <p class="mb-0">${mensaje}</p>
            </div>
        `;
        estadoCodigo.style.display = 'block';
    }

    // Seleccionar código de lista
    window.seleccionarCodigoLista = function(codigo) {
        codigoReferidoInput.value = codigo;
        validarCodigo();
    };

    // Contador de caracteres para observaciones
    observacionesInput.addEventListener('input', function() {
        const counter = document.getElementById('observaciones-counter');
        counter.textContent = this.value.length;
        
        const parent = counter.parentElement;
        parent.classList.remove('warning', 'danger');
        if (this.value.length > 450) {
            parent.classList.add('danger');
        } else if (this.value.length > 350) {
            parent.classList.add('warning');
        }
    });

    // Validar registro completo
    document.getElementById('btnValidarRegistro').addEventListener('click', function() {
        if (!leadSeleccionadoData) {
            Swal.fire({
                icon: 'warning',
                title: 'Lead No Seleccionado',
                text: 'Debe buscar y seleccionar un lead',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        if (!codigoValidadoData) {
            Swal.fire({
                icon: 'warning',
                title: 'Código No Validado',
                text: 'Debe ingresar y validar un código de referido',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        // Mostrar resumen
        const resumen = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Lead:</strong><br>
                    ${leadSeleccionadoData.nombres_estudiante} ${leadSeleccionadoData.apellidos_estudiante || ''}<br>
                    <small class="text-muted">Contacto: ${leadSeleccionadoData.nombres_contacto}</small>
                </div>
                <div class="col-md-6">
                    <strong>Código:</strong><br>
                    ${codigoValidadoData.codigo}<br>
                    <small class="text-muted">Referente: ${codigoValidadoData.referente}</small>
                </div>
            </div>
            <hr>
            <div class="beneficio-aplicable">
                <strong>Beneficio que recibirá:</strong><br>
                ${codigoValidadoData.beneficio_referido}
            </div>
        `;
        
        document.getElementById('contenidoResumen').innerHTML = resumen;
        document.getElementById('resumenRegistro').style.display = 'block';

        Swal.fire({
            icon: 'success',
            title: 'Registro Válido',
            text: 'Todos los datos son correctos. Puede guardar el registro.',
            confirmButtonColor: '#6f42c1'
        });
    });

    // Validar formulario completo
    function validarFormulario() {
        const leadValido = leadSeleccionadoData !== null;
        const codigoValido = codigoValidadoData !== null;
        
        btnGuardarUso.disabled = !(leadValido && codigoValido);
    }

    // Envío del formulario
    formUso.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!leadSeleccionadoData || !codigoValidadoData) {
            Swal.fire({
                icon: 'error',
                title: 'Formulario Incompleto',
                text: 'Debe completar todos los pasos antes de continuar',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        Swal.fire({
            title: '¿Registrar Uso del Código?',
            html: `
                <div class="text-start">
                    <p><strong>Lead:</strong> ${leadSeleccionadoData.nombres_estudiante}</p>
                    <p><strong>Código:</strong> ${codigoValidadoData.codigo}</p>
                    <p><strong>Beneficio:</strong> ${codigoValidadoData.beneficio_referido}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Limpiar formulario al cerrar
    document.getElementById('modalRegistrarUso').addEventListener('hidden.bs.modal', function() {
        formUso.reset();
        formUso.classList.remove('was-validated');
        leadSeleccionadoData = null;
        codigoValidadoData = null;
        document.getElementById('resultadosBusquedaLead').style.display = 'none';
        document.getElementById('leadSeleccionado').style.display = 'none';
        document.getElementById('estadoCodigo').style.display = 'none';
        document.getElementById('resumenRegistro').style.display = 'none';
        codigoReferidoInput.classList.remove('is-valid', 'is-invalid');
        btnGuardarUso.disabled = true;
    });
});
</script>