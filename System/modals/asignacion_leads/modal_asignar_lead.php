<!-- Modal para asignar lead -->
<div class="modal fade" id="modalAsignarLead" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user-plus me-2"></i>
                    Asignar Lead a Responsable
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAsignarLead" method="POST" action="acciones/asignacion_leads/procesar_asignacion.php">
                <input type="hidden" name="accion" value="asignar_lead">
                <input type="hidden" name="usuario_id" id="asignar_usuario_id">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Asignando lead(s) a: <strong id="asignar_usuario_nombre"></strong>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Asignación <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo_asignacion" id="tipo_asignacion" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="individual">Asignación Individual</option>
                                    <option value="multiple">Asignación Múltiple</option>
                                    <option value="por_criterio">Por Criterio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                                <select class="form-select" name="prioridad_filtro" id="prioridad_filtro">
                                    <option value="">Todas las prioridades</option>
                                    <option value="urgente">Solo urgentes</option>
                                    <option value="alta">Alta y urgente</option>
                                    <option value="media">Media y superior</option>
                                    <option value="baja">Todas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Asignación Individual -->
                    <div id="asignacion_individual" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Lead <span class="text-danger">*</span></label>
                            <select class="form-select" name="lead_id" id="lead_individual">
                                <option value="">Cargando leads...</option>
                            </select>
                            <small class="text-muted">Selecciona un lead específico para asignar</small>
                        </div>
                    </div>

                    <!-- Asignación Múltiple -->
                    <div id="asignacion_multiple" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Leads <span class="text-danger">*</span></label>
                            <select class="form-select" name="leads_ids[]" id="leads_multiple" multiple size="6">
                                <option value="">Cargando leads...</option>
                            </select>
                            <small class="text-muted">Mantén Ctrl/Cmd presionado para seleccionar múltiples leads</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cantidad máxima a asignar</label>
                            <input type="number" class="form-control" name="cantidad_maxima" id="cantidad_maxima" min="1" max="20" value="5">
                        </div>
                    </div>

                    <!-- Por Criterio -->
                    <div id="asignacion_criterio" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Canal de Captación</label>
                                    <select class="form-select" name="canal_id" id="canal_filtro">
                                        <option value="">Todos los canales</option>
                                        <?php
                                        $canales_query = "SELECT id, nombre FROM canales_captacion WHERE activo = 1 ORDER BY nombre";
                                        $canales_result = $conn->query($canales_query);
                                        while($canal = $canales_result->fetch_assoc()) {
                                            echo "<option value='{$canal['id']}'>" . htmlspecialchars($canal['nombre']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estado Actual</label>
                                    <select class="form-select" name="estado_id" id="estado_filtro">
                                        <option value="">Todos los estados</option>
                                        <?php
                                        $estados_query = "SELECT id, nombre FROM estados_lead WHERE activo = 1 ORDER BY orden_display";
                                        $estados_result = $conn->query($estados_query);
                                        while($estado = $estados_result->fetch_assoc()) {
                                            echo "<option value='{$estado['id']}'>" . htmlspecialchars($estado['nombre']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Grado de Interés</label>
                                    <select class="form-select" name="grado_id" id="grado_filtro">
                                        <option value="">Todos los grados</option>
                                        <?php
                                        $grados_query = "SELECT g.id, CONCAT(ne.nombre, ' - ', g.nombre) as grado_completo 
                                                        FROM grados g 
                                                        LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id 
                                                        WHERE g.activo = 1 ORDER BY ne.orden_display, g.orden_display";
                                        $grados_result = $conn->query($grados_query);
                                        while($grado = $grados_result->fetch_assoc()) {
                                            echo "<option value='{$grado['id']}'>" . htmlspecialchars($grado['grado_completo']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Rango de Fechas</label>
                                    <select class="form-select" name="rango_fecha" id="rango_fecha">
                                        <option value="">Todas las fechas</option>
                                        <option value="hoy">Solo de hoy</option>
                                        <option value="semana">Última semana</option>
                                        <option value="mes">Último mes</option>
                                        <option value="personalizado">Rango personalizado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="rango_personalizado" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha desde</label>
                                        <input type="date" class="form-control" name="fecha_desde" id="fecha_desde">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha hasta</label>
                                        <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" id="observaciones_asignacion" rows="3" placeholder="Motivo de la asignación, instrucciones especiales, etc."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notificar_responsable" id="notificar_responsable" checked>
                        <label class="form-check-label" for="notificar_responsable">
                            Notificar al responsable por email/WhatsApp
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btn_preview_asignacion">
                        <i class="ti ti-eye me-1"></i>
                        Vista Previa
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-user-plus me-1"></i>
                        Asignar Lead(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div class="modal fade" id="modalPreviewAsignacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          <i class="ti ti-eye me-2"></i> Vista Previa de Asignación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="previewContent">
          <p class="text-muted">Aquí se mostrarán los leads que serían asignados según tu selección.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
$(document).ready(function() {
    // Manejar cambio de tipo de asignación
    $('#tipo_asignacion').change(function() {
        var tipo = $(this).val();
        $('#asignacion_individual, #asignacion_multiple, #asignacion_criterio').hide();
        
        if (tipo === 'individual') {
            $('#asignacion_individual').show();
            cargarLeadsParaAsignacion('individual');
        } else if (tipo === 'multiple') {
            $('#asignacion_multiple').show();
            cargarLeadsParaAsignacion('multiple');
        } else if (tipo === 'por_criterio') {
            $('#asignacion_criterio').show();
        }
    });

    // Manejar rango de fechas personalizado
    $('#rango_fecha').change(function() {
        if ($(this).val() === 'personalizado') {
            $('#rango_personalizado').show();
        } else {
            $('#rango_personalizado').hide();
        }
    });

    // Vista previa de asignación
    $('#btn_preview_asignacion').click(function() {
        var tipo = $('#tipo_asignacion').val();
        var usuarioId = $('#asignar_usuario_id').val();
        var prioridad = $('#prioridad_filtro').val();
        var previewDiv = $('#previewContent');

        if (!tipo) {
            alert('Por favor selecciona un tipo de asignación');
            return;
        }

        // Armar parámetros según el tipo de asignación
        var params = {
            tipo: tipo,
            usuario_id: usuarioId,
            prioridad: prioridad
        };

        if (tipo === 'individual') {
            params.lead_id = $('#lead_individual').val();
        } else if (tipo === 'multiple') {
            params.leads_ids = $('#leads_multiple').val();
            params.cantidad_maxima = $('#cantidad_maxima').val();
        } else if (tipo === 'por_criterio') {
            params.canal_id = $('#canal_filtro').val();
            params.estado_id = $('#estado_filtro').val();
            params.grado_id = $('#grado_filtro').val();
            params.rango_fecha = $('#rango_fecha').val();
            params.fecha_desde = $('#fecha_desde').val();
            params.fecha_hasta = $('#fecha_hasta').val();
        }

        // Llamar al backend para obtener los leads que cumplen el criterio
        $.ajax({
            url: 'acciones/asignacion_leads/preview_asignacion.php',
            method: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.length > 0) {
                        var tabla = `
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Prioridad</th>
                                        <th>Canal</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        response.data.forEach(function(lead) {
                            tabla += `
                                <tr>
                                    <td>${lead.id}</td>
                                    <td>${lead.nombre}</td>
                                    <td>${lead.estado}</td>
                                    <td>${lead.prioridad || '-'}</td>
                                    <td>${lead.canal || '-'}</td>
                                </tr>
                            `;
                        });
                        tabla += '</tbody></table>';
                        previewDiv.html(tabla);
                    } else {
                        previewDiv.html('<div class="alert alert-warning">No se encontraron leads para asignar con los criterios seleccionados.</div>');
                    }
                } else {
                    previewDiv.html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                }

                // Mostrar modal
                $('#modalPreviewAsignacion').modal('show');
            },
            error: function() {
                previewDiv.html('<div class="alert alert-danger">Error al obtener la vista previa.</div>');
                $('#modalPreviewAsignacion').modal('show');
            }
        });
    });
    
    function cargarLeadsParaAsignacion(tipo) {
        $.ajax({
            url: 'acciones/asignacion_leads/obtener_leads_sin_asignar.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var select = tipo === 'individual' ? $('#lead_individual') : $('#leads_multiple');
                    select.empty();
                    
                    if (response.data.length === 0) {
                        select.append('<option value="">No hay leads sin asignar</option>');
                    } else {
                        if (tipo === 'individual') {
                            select.append('<option value="">Seleccionar lead...</option>');
                        }
                        
                        response.data.forEach(function(lead) {
                            var prioridad = lead.prioridad ? ' [' + lead.prioridad.toUpperCase() + ']' : '';
                            var texto = lead.nombre + ' - ' + lead.estado + prioridad;
                            select.append('<option value="' + lead.id + '">' + texto + '</option>');
                        });
                    }
                }
            },
            error: function() {
                alert('Error al cargar leads sin asignar.');
            }
        });
    }
});
</script>