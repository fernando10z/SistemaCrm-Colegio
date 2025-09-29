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
</style>
<!-- Modal Gestionar Estado del Código -->
<div class="modal fade" id="modalGestionarEstado" tabindex="-1" aria-labelledby="modalGestionarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalGestionarEstadoLabel">
          <i class="ti ti-settings me-2"></i>
          Gestionar Estado del Código
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formGestionarEstado" method="POST" action="acciones/codigos_referido/gestionar_codigo.php" novalidate>
        <input type="hidden" name="accion" value="gestionar_estado">
        <input type="hidden" name="codigo_id" id="estado_codigo_id">
        
        <div class="modal-body">
          
          <!-- Información del Código -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-tag me-1"></i>
                Información del Código
              </h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <small class="text-muted">Código:</small>
                  <div class="fw-bold h5" id="estado_codigo_display">-</div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted">Estado Actual:</small>
                  <div class="fw-bold" id="estado_actual_display">
                    <span class="badge" id="badge_estado_actual">-</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted">Propietario:</small>
                  <div class="fw-bold" id="estado_propietario_display">-</div>
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-md-3">
                  <small class="text-muted">Usos Actuales:</small>
                  <div class="h4 text-primary" id="estado_usos_actuales">0</div>
                </div>
                <div class="col-md-3">
                  <small class="text-muted">Límite:</small>
                  <div class="h4 text-info" id="estado_limite_usos">∞</div>
                </div>
                <div class="col-md-3">
                  <small class="text-muted">Fecha Inicio:</small>
                  <div class="fw-bold" id="estado_fecha_inicio">-</div>
                </div>
                <div class="col-md-3">
                  <small class="text-muted">Fecha Fin:</small>
                  <div class="fw-bold" id="estado_fecha_fin">-</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Acciones de Estado -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-toggle-right me-1"></i>
                Cambiar Estado
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Estado del Código -->
              <div class="mb-3">
                <label class="form-label">
                  Estado del Código <span class="text-danger">*</span>
                </label>
                <div class="btn-group w-100" role="group" id="estado_grupo_botones">
                  <input type="radio" class="btn-check" name="estado" id="estado_activo" value="1" required>
                  <label class="btn btn-outline-success" for="estado_activo">
                    <i class="ti ti-check me-1"></i>
                    Activo
                  </label>

                  <input type="radio" class="btn-check" name="estado" id="estado_inactivo" value="0" required>
                  <label class="btn btn-outline-danger" for="estado_inactivo">
                    <i class="ti ti-x me-1"></i>
                    Inactivo
                  </label>
                </div>
                <div class="invalid-feedback">
                  Debe seleccionar un estado
                </div>
              </div>

              <!-- Motivo del Cambio -->
              <div class="mb-3">
                <label for="estado_motivo" class="form-label">
                  Motivo del Cambio <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="estado_motivo" name="motivo" 
                          rows="3" maxlength="500"
                          minlength="10"
                          required
                          placeholder="Explique brevemente el motivo del cambio de estado"></textarea>
                <div class="invalid-feedback">
                  Debe especificar un motivo (10-500 caracteres)
                </div>
                <div class="form-text">
                  <span id="estado_motivo_count">0</span>/500 caracteres
                </div>
              </div>

              <!-- Alertas de Validación -->
              <div id="alertas_estado"></div>

            </div>
          </div>

          <!-- Historial de Cambios (Si existe) -->
          <div class="card">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="ti ti-history me-1"></i>
                Historial de Cambios de Estado
              </h6>
            </div>
            <div class="card-body" id="historial_estado_container">
              <div class="text-center text-muted">
                <i class="ti ti-loader"></i>
                Cargando historial...
              </div>
            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="submit" class="btn btn-info">
            <i class="ti ti-device-floppy me-1"></i>
            Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formEstado = document.getElementById('formGestionarEstado');
    const motivoTextarea = document.getElementById('estado_motivo');
    const estadoActivo = document.getElementById('estado_activo');
    const estadoInactivo = document.getElementById('estado_inactivo');

    // Función para cargar estado del código
    window.cargarEstadoCodigo = function(codigoId, codigoNombre, estadoActual) {
        Swal.fire({
            title: 'Cargando datos...',
            html: 'Obteniendo información del código',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('acciones/codigos_referido/obtener_codigo.php?id=' + encodeURIComponent(codigoId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            Swal.close();
            
            if (data.success) {
                llenarFormularioEstado(data.data);
                cargarHistorialEstado(codigoId);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'No se pudieron obtener los datos del código',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error completo:', error);
            
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los datos del código',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
    };

    // Llenar formulario con datos
    function llenarFormularioEstado(datos) {
        // ID y código
        document.getElementById('estado_codigo_id').value = datos.id;
        document.getElementById('estado_codigo_display').textContent = datos.codigo;
        
        // Estado actual
        const badgeEstado = document.getElementById('badge_estado_actual');
        if (datos.activo == 1) {
            badgeEstado.textContent = 'ACTIVO';
            badgeEstado.className = 'badge bg-success';
            estadoActivo.checked = true;
        } else {
            badgeEstado.textContent = 'INACTIVO';
            badgeEstado.className = 'badge bg-danger';
            estadoInactivo.checked = true;
        }
        
        // Información adicional
        document.getElementById('estado_propietario_display').textContent = 
            datos.propietario || 'Código General';
        document.getElementById('estado_usos_actuales').textContent = datos.usos_actuales;
        document.getElementById('estado_limite_usos').textContent = 
            datos.limite_usos || '∞';
        document.getElementById('estado_fecha_inicio').textContent = 
            new Date(datos.fecha_inicio).toLocaleDateString('es-PE');
        document.getElementById('estado_fecha_fin').textContent = 
            datos.fecha_fin ? new Date(datos.fecha_fin).toLocaleDateString('es-PE') : 'Sin límite';
        
        // Mostrar alertas si el código está cerca de expirar o agotarse
        mostrarAlertasEstado(datos);
    }

    // Mostrar alertas de estado
    function mostrarAlertasEstado(datos) {
        const alertasDiv = document.getElementById('alertas_estado');
        alertasDiv.innerHTML = '';
        
        const alertas = [];
        
        // Verificar si está cerca de agotarse
        if (datos.limite_usos) {
            const disponibles = datos.limite_usos - datos.usos_actuales;
            const porcentajeUso = (datos.usos_actuales / datos.limite_usos) * 100;
            
            if (porcentajeUso >= 90) {
                alertas.push({
                    tipo: 'danger',
                    mensaje: `⚠️ <strong>Crítico:</strong> Solo quedan ${disponibles} usos disponibles (${porcentajeUso.toFixed(0)}% utilizado)`
                });
            } else if (porcentajeUso >= 70) {
                alertas.push({
                    tipo: 'warning',
                    mensaje: `⚠️ <strong>Atención:</strong> Quedan ${disponibles} usos disponibles (${porcentajeUso.toFixed(0)}% utilizado)`
                });
            }
        }
        
        // Verificar fecha de expiración
        if (datos.fecha_fin) {
            const hoy = new Date();
            const fechaFin = new Date(datos.fecha_fin);
            const diasRestantes = Math.ceil((fechaFin - hoy) / (1000 * 60 * 60 * 24));
            
            if (diasRestantes < 0) {
                alertas.push({
                    tipo: 'danger',
                    mensaje: '⏰ <strong>Expirado:</strong> Este código ya no está vigente'
                });
            } else if (diasRestantes <= 7) {
                alertas.push({
                    tipo: 'warning',
                    mensaje: `⏰ <strong>Próximo a expirar:</strong> Quedan solo ${diasRestantes} días`
                });
            } else if (diasRestantes <= 30) {
                alertas.push({
                    tipo: 'info',
                    mensaje: `ℹ️ El código expira en ${diasRestantes} días`
                });
            }
        }
        
        // Mostrar alertas
        alertas.forEach(alerta => {
            const alertaHTML = `
                <div class="alert alert-${alerta.tipo} mb-2" role="alert">
                    ${alerta.mensaje}
                </div>
            `;
            alertasDiv.innerHTML += alertaHTML;
        });
    }

    // Cargar historial de cambios
    function cargarHistorialEstado(codigoId) {
        const historialDiv = document.getElementById('historial_estado_container');
        
        fetch('acciones/codigos_referido/obtener_historial_estado.php?codigo_id=' + encodeURIComponent(codigoId))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.historial && data.historial.length > 0) {
                    let historialHTML = '<div class="timeline">';
                    
                    data.historial.forEach((item, index) => {
                        const iconoEstado = item.estado_nuevo == 1 ? 
                            '<i class="ti ti-check text-success"></i>' : 
                            '<i class="ti ti-x text-danger"></i>';
                        
                        historialHTML += `
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="me-2">${iconoEstado}</div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong>${item.accion_display}</strong>
                                            <small class="text-muted">${item.fecha_formato}</small>
                                        </div>
                                        <div class="text-muted small">${item.motivo || 'Sin motivo especificado'}</div>
                                        <div class="text-muted small">Por: ${item.usuario_nombre}</div>
                                    </div>
                                </div>
                                ${index < data.historial.length - 1 ? '<hr>' : ''}
                            </div>
                        `;
                    });
                    
                    historialHTML += '</div>';
                    historialDiv.innerHTML = historialHTML;
                } else {
                    historialDiv.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="ti ti-info-circle"></i>
                            No hay cambios de estado registrados
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error cargando historial:', error);
                historialDiv.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        No se pudo cargar el historial de cambios
                    </div>
                `;
            });
    }

    // Contador de caracteres
    motivoTextarea.addEventListener('input', function() {
        document.getElementById('estado_motivo_count').textContent = this.value.length;
    });

    // Confirmar cambio de estado
    estadoActivo.addEventListener('change', function() {
        if (this.checked) {
            motivoTextarea.placeholder = 'Explique por qué se reactiva este código';
        }
    });

    estadoInactivo.addEventListener('change', function() {
        if (this.checked) {
            motivoTextarea.placeholder = 'Explique por qué se desactiva este código';
        }
    });

    // Validación del formulario
    formEstado.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formEstado.checkValidity()) {
            e.stopPropagation();
            formEstado.classList.add('was-validated');
            
            Swal.fire({
                title: 'Errores en el formulario',
                text: 'Por favor corrija los errores marcados antes de continuar',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            
            return false;
        }

        // Determinar acción
        const nuevoEstado = estadoActivo.checked ? 'Activo' : 'Inactivo';
        const codigo = document.getElementById('estado_codigo_display').textContent;
        const motivo = motivoTextarea.value;

        // Confirmación
        Swal.fire({
            title: '¿Cambiar estado del código?',
            html: `
                <div class="text-start">
                    <strong>Código:</strong> ${codigo}<br>
                    <strong>Nuevo Estado:</strong> ${nuevoEstado}<br>
                    <strong>Motivo:</strong> ${motivo}
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                formEstado.submit();
            }
        });
    });

    // Limpiar al cerrar
    document.getElementById('modalGestionarEstado').addEventListener('hidden.bs.modal', function() {
        formEstado.reset();
        formEstado.classList.remove('was-validated');
        document.getElementById('alertas_estado').innerHTML = '';
        document.getElementById('estado_motivo_count').textContent = '0';
    });
});
</script>