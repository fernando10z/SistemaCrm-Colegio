<!-- Modal para programar envío masivo -->
<div class="modal fade" id="modalProgramarEnvio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Programar Envío Masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProgramarEnvio" method="POST" action="acciones/boletines/procesar_acciones.php">
                <input type="hidden" name="accion" value="programar_envio_masivo">
                <input type="hidden" name="plantilla_id" id="envio_plantilla_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Boletín seleccionado:</strong> <span id="envio_boletin_nombre"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha de Envío <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_envio" required
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hora de Envío <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="hora_envio" required value="09:00">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Destinatarios <span class="text-danger">*</span></label>
                        <select class="form-select" name="destinatarios_tipo" id="destinatarios_tipo" required>
                            <option value="">Seleccionar tipo de destinatarios</option>
                            <option value="todos_apoderados">Todos los Apoderados</option>
                            <option value="familias_activas">Solo Familias Activas (Apoderados Titulares)</option>
                            <option value="por_nivel">Filtrar por Nivel Educativo</option>
                        </select>
                    </div>

                    <div class="mb-3" id="filtro_nivel_container" style="display: none;">
                        <label class="form-label">Nivel Educativo <span class="text-danger">*</span></label>
                        <select class="form-select" name="nivel_educativo_filtro">
                            <option value="">Seleccionar nivel</option>
                            <?php
                            // Obtener niveles educativos
                            $query_niveles = "SELECT nombre FROM niveles_educativos WHERE activo = 1 ORDER BY orden_display";
                            $result_niveles = $conn->query($query_niveles);
                            if ($result_niveles && $result_niveles->num_rows > 0) {
                                while($nivel = $result_niveles->fetch_assoc()) {
                                    echo "<option value='" . $nivel['nombre'] . "'>" . $nivel['nombre'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Estimación de Destinatarios</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-1" id="total_destinatarios">0</h4>
                                        <small class="text-muted">Total Destinatarios</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border-end">
                                        <h4 class="text-success mb-1" id="emails_validos">0</h4>
                                        <small class="text-muted">Emails Válidos</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="text-info mb-1" id="costo_estimado">S/ 0.00</h4>
                                    <small class="text-muted">Costo Estimado</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="calcularDestinatarios()">
                                    <i class="ti ti-refresh me-1"></i>Calcular Destinatarios
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Mensaje Adicional (Opcional)</label>
                        <textarea class="form-control" name="mensaje_adicional" rows="3"
                                  placeholder="Mensaje personalizado que se agregará al inicio del boletín..."></textarea>
                        <small class="text-muted">Este mensaje aparecerá antes del contenido principal del boletín.</small>
                    </div>

                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enviar_confirmacion" id="enviar_confirmacion" checked>
                            <label class="form-check-label" for="enviar_confirmacion">
                                Enviar confirmación cuando se complete el envío
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <!-- <button type="button" class="btn btn-outline-info" onclick="previsualizarEnvio()">
                        <i class="ti ti-eye me-1"></i>Vista Previa
                    </button> -->
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-send me-1"></i>Programar Envío
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar filtro de nivel educativo
    document.getElementById('destinatarios_tipo').addEventListener('change', function() {
        const filtroNivel = document.getElementById('filtro_nivel_container');
        if (this.value === 'por_nivel') {
            filtroNivel.style.display = 'block';
            document.querySelector('select[name="nivel_educativo_filtro"]').required = true;
        } else {
            filtroNivel.style.display = 'none';
            document.querySelector('select[name="nivel_educativo_filtro"]').required = false;
        }

        // Limpiar estimaciones cuando cambia el tipo
        document.getElementById('total_destinatarios').textContent = '0';
        document.getElementById('emails_validos').textContent = '0';
        document.getElementById('costo_estimado').textContent = 'S/ 0.00';
    });

    // Manejar envío del formulario
    document.getElementById('formProgramarEnvio').addEventListener('submit', function(e) {
        e.preventDefault();

        const totalDestinatarios = parseInt(document.getElementById('total_destinatarios').textContent);

        if (totalDestinatarios === 0) {
            alert('Por favor calcule los destinatarios antes de programar el envío.');
            return;
        }

        if (!confirm(`¿Está seguro de programar el envío a ${totalDestinatarios} destinatarios?`)) {
            return;
        }

        const formData = new FormData(this);

        // Mostrar loader
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Programando...';
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

function calcularDestinatarios() {
    const tipoDestinatarios = document.getElementById('destinatarios_tipo').value;
    const nivelEducativo = document.querySelector('select[name="nivel_educativo_filtro"]').value;

    if (!tipoDestinatarios) {
        alert('Por favor seleccione el tipo de destinatarios.');
        return;
    }

    if (tipoDestinatarios === 'por_nivel' && !nivelEducativo) {
        alert('Por favor seleccione el nivel educativo.');
        return;
    }

    // Mostrar estado de carga
    document.getElementById('total_destinatarios').innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    document.getElementById('emails_validos').innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const formData = new FormData();
    formData.append('accion', 'calcular_destinatarios');
    formData.append('destinatarios_tipo', tipoDestinatarios);
    if (nivelEducativo) {
        formData.append('nivel_educativo_filtro', nivelEducativo);
    }

    fetch('acciones/boletines/procesar_acciones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('total_destinatarios').textContent = data.total || 0;
            document.getElementById('emails_validos').textContent = data.emails_validos || 0;

            // Calcular costo estimado (S/ 0.05 por email)
            const costo = (data.emails_validos || 0) * 0.05;
            document.getElementById('costo_estimado').textContent = 'S/ ' + costo.toFixed(2);
        } else {
            document.getElementById('total_destinatarios').textContent = '0';
            document.getElementById('emails_validos').textContent = '0';
            document.getElementById('costo_estimado').textContent = 'S/ 0.00';
            alert('Error al calcular destinatarios: ' + (data.mensaje || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('total_destinatarios').textContent = '0';
        document.getElementById('emails_validos').textContent = '0';
        document.getElementById('costo_estimado').textContent = 'S/ 0.00';
        alert('Error de conexión al calcular destinatarios.');
    });
}

function previsualizarEnvio() {
    const plantillaId = document.getElementById('envio_plantilla_id').value;
    const fechaEnvio = document.querySelector('input[name="fecha_envio"]').value;
    const horaEnvio = document.querySelector('input[name="hora_envio"]').value;
    const tipoDestinatarios = document.getElementById('destinatarios_tipo').value;
    const totalDestinatarios = document.getElementById('total_destinatarios').textContent;

    if (!plantillaId || !fechaEnvio || !horaEnvio || !tipoDestinatarios) {
        alert('Por favor complete todos los campos requeridos.');
        return;
    }

    // Crear modal de vista previa
    const modalPreview = document.createElement('div');
    modalPreview.innerHTML = `
        <div class="modal fade" id="modalVistaEnvio" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vista Previa del Envío Programado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body">
                                <h6>Resumen del Envío</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Boletín:</strong></td>
                                        <td>${document.getElementById('envio_boletin_nombre').textContent}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha y Hora:</strong></td>
                                        <td>${fechaEnvio} a las ${horaEnvio}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Destinatarios:</strong></td>
                                        <td>${tipoDestinatarios.replace('_', ' ')} (${totalDestinatarios} personas)</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado:</strong></td>
                                        <td><span class="badge bg-warning">Programado</span></td>
                                    </tr>
                                </table>
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

    const modal = new bootstrap.Modal(document.getElementById('modalVistaEnvio'));
    modal.show();

    // Limpiar modal cuando se cierre
    document.getElementById('modalVistaEnvio').addEventListener('hidden.bs.modal', function () {
        modalPreview.remove();
    });
}

// Limpiar formulario al cerrar modal
document.getElementById('modalProgramarEnvio').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formProgramarEnvio').reset();
    document.getElementById('filtro_nivel_container').style.display = 'none';
    document.getElementById('total_destinatarios').textContent = '0';
    document.getElementById('emails_validos').textContent = '0';
    document.getElementById('costo_estimado').textContent = 'S/ 0.00';
});
</script>