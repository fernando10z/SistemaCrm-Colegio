<!-- Modal para consultar/ver detalles del lead -->
<div class="modal fade" id="modalConsultar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-eye me-2"></i>
                    Detalles del Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Información general -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title text-primary">
                                    <i class="ti ti-user me-1"></i>Información del Lead
                                </h6>
                                <div class="row">
                                    <div class="col-sm-4"><strong>ID:</strong></div>
                                    <div class="col-sm-8" id="view_id">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Código:</strong></div>
                                    <div class="col-sm-8" id="view_codigo_lead">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Registro:</strong></div>
                                    <div class="col-sm-8" id="view_fecha_registro">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Estado:</strong></div>
                                    <div class="col-sm-8" id="view_estado">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title text-success">
                                    <i class="ti ti-target me-1"></i>Seguimiento
                                </h6>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Responsable:</strong></div>
                                    <div class="col-sm-8" id="view_responsable">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Prioridad:</strong></div>
                                    <div class="col-sm-8" id="view_prioridad">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Interés:</strong></div>
                                    <div class="col-sm-8" id="view_puntaje_interes">-</div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4"><strong>Canal:</strong></div>
                                    <div class="col-sm-8" id="view_canal">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs" id="viewLeadTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="view-estudiante-tab" data-bs-toggle="tab" 
                                data-bs-target="#view-estudiante-tab-pane" type="button" role="tab">
                            <i class="ti ti-school me-1"></i>Estudiante
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="view-contacto-tab" data-bs-toggle="tab" 
                                data-bs-target="#view-contacto-tab-pane" type="button" role="tab">
                            <i class="ti ti-phone me-1"></i>Contacto
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="view-seguimiento-tab" data-bs-toggle="tab" 
                                data-bs-target="#view-seguimiento-tab-pane" type="button" role="tab">
                            <i class="ti ti-calendar me-1"></i>Seguimiento
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="view-adicional-tab" data-bs-toggle="tab" 
                                data-bs-target="#view-adicional-tab-pane" type="button" role="tab">
                            <i class="ti ti-info-circle me-1"></i>Adicional
                        </button>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content" id="viewLeadTabContent">
                    <!-- Pestaña Estudiante -->
                    <div class="tab-pane fade show active" id="view-estudiante-tab-pane" role="tabpanel">
                        <div class="table-responsive mt-3">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td width="30%"><strong>Nombres:</strong></td>
                                        <td id="view_nombres_estudiante">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Apellidos:</strong></td>
                                        <td id="view_apellidos_estudiante">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha de Nacimiento:</strong></td>
                                        <td id="view_fecha_nacimiento">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Género:</strong></td>
                                        <td id="view_genero">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Grado de Interés:</strong></td>
                                        <td id="view_grado_interes">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Colegio Procedencia:</strong></td>
                                        <td id="view_colegio_procedencia">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Motivo del Cambio:</strong></td>
                                        <td id="view_motivo_cambio">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Contacto -->
                    <div class="tab-pane fade" id="view-contacto-tab-pane" role="tabpanel">
                        <div class="table-responsive mt-3">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td width="30%"><strong>Nombres:</strong></td>
                                        <td id="view_nombres_contacto">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Apellidos:</strong></td>
                                        <td id="view_apellidos_contacto">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Teléfono:</strong></td>
                                        <td>
                                            <span id="view_telefono">-</span>
                                            <a href="#" id="link_telefono" class="btn btn-outline-primary btn-sm ms-2" style="display: none;">
                                                <i class="ti ti-phone"></i> Llamar
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>WhatsApp:</strong></td>
                                        <td>
                                            <span id="view_whatsapp">-</span>
                                            <a href="#" id="link_whatsapp" target="_blank" class="btn btn-outline-success btn-sm ms-2" style="display: none;">
                                                <i class="ti ti-brand-whatsapp"></i> WhatsApp
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>
                                            <span id="view_email">-</span>
                                            <a href="#" id="link_email" class="btn btn-outline-info btn-sm ms-2" style="display: none;">
                                                <i class="ti ti-mail"></i> Enviar Email
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Seguimiento -->
                    <div class="tab-pane fade" id="view-seguimiento-tab-pane" role="tabpanel">
                        <div class="table-responsive mt-3">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td width="30%"><strong>Próxima Acción:</strong></td>
                                        <td id="view_proxima_accion_fecha">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Descripción Acción:</strong></td>
                                        <td id="view_proxima_accion_descripcion">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Última Interacción:</strong></td>
                                        <td id="view_ultima_interaccion">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha Conversión:</strong></td>
                                        <td id="view_fecha_conversion">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Observaciones:</strong></td>
                                        <td id="view_observaciones">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Adicional -->
                    <div class="tab-pane fade" id="view-adicional-tab-pane" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Información de Tracking</h6>
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <td width="40%"><strong>UTM Source:</strong></td>
                                            <td id="view_utm_source">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>UTM Medium:</strong></td>
                                            <td id="view_utm_medium">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>UTM Campaign:</strong></td>
                                            <td id="view_utm_campaign">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>IP Origen:</strong></td>
                                            <td id="view_ip_origen">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Auditoría</h6>
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <td width="40%"><strong>Creado:</strong></td>
                                            <td id="view_created_at">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Actualizado:</strong></td>
                                            <td id="view_updated_at">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Estado:</strong></td>
                                            <td id="view_activo">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btn_editar_desde_consulta">
                    <i class="ti ti-edit me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-outline-success" id="btn_contactar_desde_consulta">
                    <i class="ti ti-phone me-1"></i>Contactar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar botón editar desde consulta
    $('#btn_editar_desde_consulta').on('click', function() {
        var leadId = $('#view_id').text();
        $('#modalConsultar').modal('hide');
        // Cargar datos en modal de edición
        cargarDatosLead(leadId, 'editar');
    });

    // Manejar botón contactar desde consulta
    $('#btn_contactar_desde_consulta').on('click', function() {
        var leadId = $('#view_id').text();
        var nombre = $('#view_nombres_contacto').text() + ' ' + $('#view_apellidos_contacto').text();
        var telefono = $('#view_telefono').text();
        var email = $('#view_email').text();
        
        $('#modalConsultar').modal('hide');
        
        // Abrir modal de contacto
        $('#contacto_lead_id').val(leadId);
        $('#contacto_nombre_lead').text(nombre);
        $('#contacto_telefono_lead').text(telefono);
        $('#contacto_email_lead').text(email);
        $('#modalContactar').modal('show');
    });
});
</script>