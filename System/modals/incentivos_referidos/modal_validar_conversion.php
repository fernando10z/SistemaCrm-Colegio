<!-- modals/incentivos_referidos/modal_validar_conversion.php -->
<style>
.uso-pendiente-card {
    border: 2px solid #FFF4B8;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.uso-pendiente-card:hover {
    border-color: #FFD700;
    background-color: #FFFEF8;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.uso-pendiente-card.selected {
    border-color: #28a745;
    background-color: #D4EDDA;
}

.beneficio-aplicar {
    background: linear-gradient(135deg, #B8E6B8 0%, #B8D4E6 100%);
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
}

.descuento-calculado {
    background-color: #D4EDDA;
    border-left: 4px solid #28a745;
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
}

.timeline-item {
    border-left: 2px solid #D4B8E6;
    padding-left: 20px;
    margin-left: 10px;
    position: relative;
}

.timeline-item::before {
    content: '';
    width: 12px;
    height: 12px;
    background-color: #6f42c1;
    border-radius: 50%;
    position: absolute;
    left: -7px;
    top: 5px;
}
</style>

<!-- Modal Validar Conversión -->
<div class="modal fade" id="modalValidarConversion" tabindex="-1" aria-labelledby="modalValidarConversionLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #FFB8D4 0%, #D4B8E6 100%);">
        <h5 class="modal-title" id="modalValidarConversionLabel" style="color: #8B0042;">
          <i class="ti ti-check-circle me-2"></i>
          Validar Conversión de Referido
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formValidarConversion" method="POST" action="acciones/incentivos_referidos/gestionar_referidos.php" novalidate>
        <input type="hidden" name="accion" value="validar_conversion">
        <input type="hidden" name="uso_id_selected" id="uso_id_selected">
        
        <div class="modal-body">
          
          <!-- Alerta Informativa -->
          <div class="alert alert-warning" role="alert">
            <i class="ti ti-alert-circle me-1"></i>
            <strong>Validación de Conversión:</strong> Confirme que un lead referido se ha convertido en estudiante.
            Se aplicarán automáticamente los beneficios tanto al referente como al nuevo estudiante.
          </div>

          <!-- Paso 1: Seleccionar Uso Pendiente -->
          <div class="card mb-3">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-clock me-1"></i>
                Paso 1: Seleccionar Uso Pendiente de Conversión
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Filtros -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="filtro_codigo" class="form-label">Filtrar por Código</label>
                  <select class="form-select" id="filtro_codigo">
                    <option value="">Todos los códigos</option>
                    <?php
                    $filtro_codigos_sql = "SELECT DISTINCT cr.codigo 
                                           FROM codigos_referido cr
                                           INNER JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
                                           WHERE ur.convertido = 0
                                           ORDER BY cr.codigo";
                    $filtro_codigos = $conn->query($filtro_codigos_sql);
                    while($cod_filtro = $filtro_codigos->fetch_assoc()) {
                        echo "<option value='{$cod_filtro['codigo']}'>{$cod_filtro['codigo']}</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="filtro_fecha" class="form-label">Filtrar por Fecha</label>
                  <select class="form-select" id="filtro_fecha">
                    <option value="">Todas las fechas</option>
                    <option value="hoy">Hoy</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes">Este mes</option>
                    <option value="anterior">Mes anterior</option>
                  </select>
                </div>
              </div>

              <button type="button" class="btn btn-primary btn-sm mb-3" id="btnCargarPendientes">
                <i class="ti ti-refresh"></i> Cargar Usos Pendientes
              </button>

              <!-- Lista de Usos Pendientes -->
              <div id="listaUsosPendientes">
                <div class="alert alert-info">
                  <i class="ti ti-info-circle"></i>
                  Haga clic en "Cargar Usos Pendientes" para ver los referidos que aún no se han convertido
                </div>
              </div>

            </div>
          </div>

          <!-- Paso 2: Confirmar Datos del Lead Convertido -->
          <div class="card mb-3" id="seccionDatosConversion" style="display: none;">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-user-check me-1"></i>
                Paso 2: Confirmar Datos de la Conversión
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Timeline de Conversión -->
              <div class="mb-3">
                <h6><i class="ti ti-timeline"></i> Línea de Tiempo</h6>
                <div class="timeline-item">
                  <strong>Uso del Código:</strong> <span id="timeline_fecha_uso">-</span><br>
                  <small class="text-muted">El lead utilizó el código de referido</small>
                </div>
                <div class="timeline-item">
                  <strong>Conversión Confirmada:</strong> <span id="timeline_fecha_conversion">Hoy</span><br>
                  <small class="text-muted">El lead se ha matriculado como estudiante</small>
                </div>
              </div>

              <!-- Fecha de Conversión -->
              <div class="mb-3">
                <label for="fecha_conversion" class="form-label form-label-required">
                  <i class="ti ti-calendar-check"></i>
                  Fecha de Conversión (Matrícula)
                </label>
                <input type="date" class="form-control" id="fecha_conversion" 
                       name="fecha_conversion" required
                       title="Fecha en que el lead se convirtió en estudiante">
                <div class="invalid-feedback">
                  Debe especificar la fecha de conversión
                </div>
                <small class="form-text text-muted">
                  Fecha en que el lead se matriculó oficialmente
                </small>
              </div>

              <!-- Estudiante Asociado (Opcional) -->
              <div class="mb-3">
                <label for="estudiante_convertido" class="form-label">
                  <i class="ti ti-school"></i>
                  Estudiante Matriculado (Opcional)
                </label>
                <select class="form-select" id="estudiante_convertido" name="estudiante_convertido">
                  <option value="">Seleccionar estudiante (si ya está registrado)</option>
                  <?php
                  $estudiantes_sql = "SELECT e.id, CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
                                      e.codigo_estudiante, g.nombre as grado
                                      FROM estudiantes e
                                      LEFT JOIN grados g ON e.grado_id = g.id
                                      WHERE e.activo = 1
                                      ORDER BY e.apellidos, e.nombres";
                  $estudiantes_result = $conn->query($estudiantes_sql);
                  while($est = $estudiantes_result->fetch_assoc()) {
                      echo "<option value='{$est['id']}'>
                            {$est['codigo_estudiante']} - {$est['nombre_completo']} ({$est['grado']})
                            </option>";
                  }
                  ?>
                </select>
                <small class="form-text text-muted">
                  <i class="ti ti-info-circle"></i> Solo si el estudiante ya está registrado en el sistema
                </small>
              </div>

              <!-- Notas de Conversión -->
              <div class="mb-3">
                <label for="notas_conversion" class="form-label">
                  <i class="ti ti-notes"></i>
                  Notas sobre la Conversión
                </label>
                <textarea class="form-control" id="notas_conversion" name="notas_conversion" 
                          rows="3" maxlength="500"
                          placeholder="Detalles adicionales sobre la conversión y aplicación de beneficios"></textarea>
                <div class="character-counter">
                  <span id="notas-counter">0</span> / 500 caracteres
                </div>
              </div>

            </div>
          </div>

          <!-- Paso 3: Beneficios a Aplicar -->
          <div class="card mb-3" id="seccionBeneficios" style="display: none;">
            <div class="card-header" style="background-color: #F5F5F5;">
              <h6 class="mb-0">
                <i class="ti ti-gift me-1"></i>
                Paso 3: Beneficios a Aplicar
              </h6>
            </div>
            <div class="card-body">
              
              <!-- Beneficio para el Referente -->
              <div class="beneficio-aplicar mb-3">
                <h6 style="color: #2d5016;">
                  <i class="ti ti-user-check"></i> Beneficio para el Referente
                </h6>
                <div id="info_beneficio_referente">
                  <strong>Referente:</strong> <span id="nombre_referente">-</span><br>
                  <strong>Beneficio:</strong> <span id="detalle_beneficio_referente">-</span>
                </div>
              </div>

              <!-- Beneficio para el Referido -->
              <div class="beneficio-aplicar" style="background: linear-gradient(135deg, #FFB8D4 0%, #FFF4B8 100%);">
                <h6 style="color: #8B0042;">
                  <i class="ti ti-user-plus"></i> Beneficio para el Nuevo Estudiante
                </h6>
                <div id="info_beneficio_referido">
                  <strong>Nuevo Estudiante:</strong> <span id="nombre_referido">-</span><br>
                  <strong>Beneficio:</strong> <span id="detalle_beneficio_referido">-</span>
                </div>
              </div>

              <!-- Confirmación de Aplicación -->
              <div class="mt-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="confirmar_aplicacion" 
                         name="confirmar_aplicacion" required>
                  <label class="form-check-label" for="confirmar_aplicacion">
                    <strong>Confirmo que los beneficios serán aplicados correctamente</strong>
                    <br><small class="text-muted">
                      Los descuentos se aplicarán automáticamente en las siguientes transacciones
                    </small>
                  </label>
                </div>
              </div>

              <!-- Método de Aplicación -->
              <div class="mt-3">
                <label class="form-label">
                  <i class="ti ti-settings"></i>
                  ¿Cómo desea aplicar los beneficios?
                </label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="metodo_aplicacion" 
                         id="aplicar_automatico" value="automatico" checked>
                  <label class="form-check-label" for="aplicar_automatico">
                    <strong>Automático</strong> - Se aplicarán en las próximas cuentas por cobrar
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="metodo_aplicacion" 
                         id="aplicar_manual" value="manual">
                  <label class="form-check-label" for="aplicar_manual">
                    <strong>Manual</strong> - Se registrará pero se aplicará manualmente después
                  </label>
                </div>
              </div>

            </div>
          </div>

          <!-- Resumen Final -->
          <div class="card border-success" id="resumenConversion" style="display: none;">
            <div class="card-header" style="background-color: #D4EDDA;">
              <h6 class="mb-0" style="color: #155724;">
                <i class="ti ti-clipboard-check me-1"></i>
                Resumen de la Conversión
              </h6>
            </div>
            <div class="card-body">
              <div id="contenidoResumenConversion"></div>
            </div>
          </div>

        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-info" id="btnValidarConversionFinal">
            <i class="ti ti-check me-1"></i>
            Validar Datos
          </button>
          <button type="submit" class="btn btn-success" id="btnConfirmarConversion" disabled>
            <i class="ti ti-circle-check me-1"></i>
            Confirmar Conversión
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formConversion = document.getElementById('formValidarConversion');
    const fechaConversion = document.getElementById('fecha_conversion');
    const notasConversion = document.getElementById('notas_conversion');
    const confirmarAplicacion = document.getElementById('confirmar_aplicacion');
    const btnConfirmarConversion = document.getElementById('btnConfirmarConversion');
    
    let usoSeleccionadoData = null;

    // Establecer fecha máxima como hoy
    const hoy = new Date().toISOString().split('T')[0];
    fechaConversion.max = hoy;
    fechaConversion.value = hoy;
    document.getElementById('timeline_fecha_conversion').textContent = 
        new Date().toLocaleDateString('es-PE');

    // Cargar usos pendientes
    document.getElementById('btnCargarPendientes').addEventListener('click', cargarUsosPendientes);

    function cargarUsosPendientes() {
        const filtroCodigo = document.getElementById('filtro_codigo').value;
        const filtroFecha = document.getElementById('filtro_fecha').value;

        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo usos pendientes de conversión',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'acciones/incentivos_referidos/obtener_pendientes.php',
            type: 'POST',
            dataType: 'json',
            data: {
                accion: 'obtener_pendientes',
                filtro_codigo: filtroCodigo,
                filtro_fecha: filtroFecha
            },
            success: function(data) {
                Swal.close();
                
                if (data.success && data.pendientes.length > 0) {
                    mostrarUsosPendientes(data.pendientes);
                } else {
                    document.getElementById('listaUsosPendientes').innerHTML = `
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i>
                            ${data.message || 'No hay usos pendientes de conversión'}
                        </div>
                    `;
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Cargar',
                    text: 'No se pudieron obtener los usos pendientes',
                    confirmButtonColor: '#6f42c1'
                });
            }
        });
    }

    function mostrarUsosPendientes(pendientes) {
        const lista = document.getElementById('listaUsosPendientes');
        lista.innerHTML = '';
        
        pendientes.forEach(uso => {
            const diasDesdeUso = Math.floor((new Date() - new Date(uso.fecha_uso)) / (1000 * 60 * 60 * 24));
            const urgencia = diasDesdeUso > 30 ? 'danger' : diasDesdeUso > 14 ? 'warning' : 'info';
            
            const usoCard = document.createElement('div');
            usoCard.className = 'uso-pendiente-card';
            usoCard.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-2">
                            <i class="ti ti-user"></i> 
                            ${uso.lead_estudiante_nombre}
                        </h6>
                        <small class="text-muted d-block">
                            <i class="ti ti-phone"></i> Contacto: ${uso.lead_contacto_nombre}
                        </small>
                        <small class="text-muted d-block">
                            <i class="ti ti-mail"></i> ${uso.lead_email || 'Sin email'}
                        </small>
                    </div>
                    <div class="col-md-3">
                        <strong style="font-family: 'Courier New', monospace; font-size: 1.1rem;">
                            ${uso.codigo}
                        </strong><br>
                        <small class="text-muted">Referente: ${uso.referente_nombre}</small>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="badge bg-${urgencia}">
                            ${diasDesdeUso} días
                        </span><br>
                        <small class="text-muted">${new Date(uso.fecha_uso).toLocaleDateString('es-PE')}</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Beneficio Referente:</strong></small><br>
                        <small>${uso.beneficio_referente}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Beneficio Nuevo:</strong></small><br>
                        <small>${uso.beneficio_referido}</small>
                    </div>
                </div>
            `;
            
            usoCard.addEventListener('click', function() {
                seleccionarUso(uso, this);
            });
            
            lista.appendChild(usoCard);
        });
    }

    function seleccionarUso(uso, elemento) {
        // Remover selección previa
        document.querySelectorAll('.uso-pendiente-card').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Marcar como seleccionado
        elemento.classList.add('selected');
        
        // Guardar datos del uso
        usoSeleccionadoData = uso;
        document.getElementById('uso_id_selected').value = uso.id;
        
        // Mostrar secciones
        document.getElementById('seccionDatosConversion').style.display = 'block';
        document.getElementById('seccionBeneficios').style.display = 'block';
        
        // Llenar datos
        document.getElementById('timeline_fecha_uso').textContent = 
            new Date(uso.fecha_uso).toLocaleDateString('es-PE');
        
        document.getElementById('nombre_referente').textContent = uso.referente_nombre;
        document.getElementById('detalle_beneficio_referente').textContent = uso.beneficio_referente;
        
        document.getElementById('nombre_referido').textContent = uso.lead_estudiante_nombre;
        document.getElementById('detalle_beneficio_referido').textContent = uso.beneficio_referido;
        
        // Establecer fecha mínima como fecha de uso
        fechaConversion.min = uso.fecha_uso;
        
        validarFormulario();
        
        Swal.fire({
            icon: 'success',
            title: 'Uso Seleccionado',
            text: 'Complete los datos de conversión para continuar',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Contador de caracteres
    notasConversion.addEventListener('input', function() {
        const counter = document.getElementById('notas-counter');
        counter.textContent = this.value.length;
        
        const parent = counter.parentElement;
        parent.classList.remove('warning', 'danger');
        if (this.value.length > 450) {
            parent.classList.add('danger');
        } else if (this.value.length > 350) {
            parent.classList.add('warning');
        }
    });

    // Validar fecha de conversión
    fechaConversion.addEventListener('change', function() {
        if (usoSeleccionadoData) {
            const fechaUso = new Date(usoSeleccionadoData.fecha_uso);
            const fechaConv = new Date(this.value);
            
            if (fechaConv < fechaUso) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha Inválida',
                    text: 'La fecha de conversión no puede ser anterior al uso del código',
                    confirmButtonColor: '#6f42c1'
                });
                this.value = hoy;
            }
        }
        
        document.getElementById('timeline_fecha_conversion').textContent = 
            new Date(this.value).toLocaleDateString('es-PE');
        
        validarFormulario();
    });

    // Validar formulario
    function validarFormulario() {
        const usoSeleccionado = usoSeleccionadoData !== null;
        const fechaValida = fechaConversion.value !== '';
        const confirmado = confirmarAplicacion.checked;
        
        btnConfirmarConversion.disabled = !(usoSeleccionado && fechaValida && confirmado);
    }

    confirmarAplicacion.addEventListener('change', validarFormulario);

    // Validar conversión final
    document.getElementById('btnValidarConversionFinal').addEventListener('click', function() {
        if (!usoSeleccionadoData) {
            Swal.fire({
                icon: 'warning',
                title: 'Uso No Seleccionado',
                text: 'Debe seleccionar un uso pendiente',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        if (!fechaConversion.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha Requerida',
                text: 'Debe especificar la fecha de conversión',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        if (!confirmarAplicacion.checked) {
            Swal.fire({
                icon: 'warning',
                title: 'Confirmación Requerida',
                text: 'Debe confirmar la aplicación de beneficios',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        // Mostrar resumen
        const metodoAplicacion = document.querySelector('input[name="metodo_aplicacion"]:checked').value;
        const estudiante = document.getElementById('estudiante_convertido');
        const estudianteTexto = estudiante.value ? 
            estudiante.options[estudiante.selectedIndex].text : 
            'Por registrar';
        
        const resumen = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Lead Convertido:</strong><br>
                    ${usoSeleccionadoData.lead_estudiante_nombre}<br>
                    <small class="text-muted">Estudiante: ${estudianteTexto}</small>
                </div>
                <div class="col-md-6">
                    <strong>Fecha de Conversión:</strong><br>
                    ${new Date(fechaConversion.value).toLocaleDateString('es-PE')}<br>
                    <small class="text-muted">Método: ${metodoAplicacion === 'automatico' ? 'Automático' : 'Manual'}</small>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <div class="beneficio-aplicar">
                        <strong>Referente:</strong> ${usoSeleccionadoData.referente_nombre}<br>
                        <strong>Beneficio:</strong> ${usoSeleccionadoData.beneficio_referente}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="beneficio-aplicar" style="background: linear-gradient(135deg, #FFB8D4 0%, #FFF4B8 100%);">
                        <strong>Nuevo Estudiante:</strong> ${usoSeleccionadoData.lead_estudiante_nombre}<br>
                        <strong>Beneficio:</strong> ${usoSeleccionadoData.beneficio_referido}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('contenidoResumenConversion').innerHTML = resumen;
        document.getElementById('resumenConversion').style.display = 'block';

        Swal.fire({
            icon: 'success',
            title: 'Validación Exitosa',
            text: 'Todos los datos son correctos. Puede confirmar la conversión.',
            confirmButtonColor: '#28a745'
        });
    });

    // Envío del formulario
    formConversion.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!usoSeleccionadoData) {
            Swal.fire({
                icon: 'error',
                title: 'Formulario Incompleto',
                text: 'Debe seleccionar un uso pendiente',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        if (!confirmarAplicacion.checked) {
            Swal.fire({
                icon: 'error',
                title: 'Confirmación Requerida',
                text: 'Debe confirmar la aplicación de beneficios',
                confirmButtonColor: '#6f42c1'
            });
            return;
        }

        Swal.fire({
            title: '¿Confirmar Conversión?',
            html: `
                <div class="text-start">
                    <p><strong>Lead:</strong> ${usoSeleccionadoData.lead_estudiante_nombre}</p>
                    <p><strong>Código:</strong> ${usoSeleccionadoData.codigo}</p>
                    <p><strong>Fecha:</strong> ${new Date(fechaConversion.value).toLocaleDateString('es-PE')}</p>
                    <hr>
                    <p class="text-success"><strong>Se aplicarán los beneficios a:</strong></p>
                    <p>• Referente: ${usoSeleccionadoData.referente_nombre}</p>
                    <p>• Nuevo Estudiante: ${usoSeleccionadoData.lead_estudiante_nombre}</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, confirmar conversión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Limpiar formulario al cerrar
    document.getElementById('modalValidarConversion').addEventListener('hidden.bs.modal', function() {
        formConversion.reset();
        formConversion.classList.remove('was-validated');
        usoSeleccionadoData = null;
        document.getElementById('listaUsosPendientes').innerHTML = `
            <div class="alert alert-info">
                <i class="ti ti-info-circle"></i>
                Haga clic en "Cargar Usos Pendientes" para ver los referidos que aún no se han convertido
            </div>
        `;
        document.getElementById('seccionDatosConversion').style.display = 'none';
        document.getElementById('seccionBeneficios').style.display = 'none';
        document.getElementById('resumenConversion').style.display = 'none';
        btnConfirmarConversion.disabled = true;
        fechaConversion.value = hoy;
    });
});
</script>