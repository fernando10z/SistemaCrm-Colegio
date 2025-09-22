<!-- Modal para verificar y gestionar duplicados -->
<div class="modal fade" id="modalDuplicados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-copy me-2"></i>
                    Verificación de Duplicados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Controles de búsqueda -->
                <div class="card bg-light mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title text-primary mb-3">
                            <i class="ti ti-search me-1"></i>Búsqueda de Duplicados
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Buscar por Email</label>
                                <input type="email" class="form-control" id="buscar_email" placeholder="ejemplo@email.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Buscar por Teléfono</label>
                                <input type="tel" class="form-control" id="buscar_telefono" placeholder="+51 999999999">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Buscar por Nombre</label>
                                <input type="text" class="form-control" id="buscar_nombre" placeholder="Nombre completo">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" id="btn_buscar_duplicados">
                                    <i class="ti ti-search me-1"></i>Buscar Duplicados
                                </button>
                                <button type="button" class="btn btn-warning" id="btn_verificar_todos">
                                    <i class="ti ti-refresh me-1"></i>Verificar Todos los Leads
                                </button>
                                <div class="float-end">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        Se buscan coincidencias por email, teléfono o nombres similares
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loading_duplicados" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <p class="mt-2">Verificando duplicados...</p>
                </div>

                <!-- Resultados -->
                <div id="resultados_duplicados" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="ti ti-alert-triangle me-1 text-warning"></i>
                                Posibles Duplicados Encontrados
                                <span class="badge bg-warning ms-2" id="contador_duplicados">0</span>
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="tabla_duplicados">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" class="form-check-input" id="check_all_duplicados">
                                            </th>
                                            <th width="8%">ID</th>
                                            <th width="15%">Estudiante</th>
                                            <th width="15%">Contacto</th>
                                            <th width="12%">Teléfono</th>
                                            <th width="15%">Email</th>
                                            <th width="10%">Estado</th>
                                            <th width="10%">Registro</th>
                                            <th width="10%">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_duplicados">
                                        <!-- Se llenará dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones para duplicados seleccionados -->
                    <div class="card mt-3" id="acciones_duplicados" style="display: none;">
                        <div class="card-body p-3">
                            <h6 class="card-title text-danger">
                                <i class="ti ti-settings me-1"></i>
                                Acciones para Duplicados Seleccionados
                            </h6>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-warning" id="btn_marcar_principal">
                                            <i class="ti ti-star me-1"></i>Marcar como Principal
                                        </button>
                                        <button type="button" class="btn btn-outline-info" id="btn_fusionar_leads">
                                            <i class="ti ti-git-merge me-1"></i>Fusionar Leads
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" id="btn_desactivar_duplicados">
                                            <i class="ti ti-trash me-1"></i>Desactivar Duplicados
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-info" id="contador_seleccionados">0 seleccionados</span>
                                </div>
                            </div>
                            
                            <!-- Confirmación de fusión -->
                            <div id="confirmacion_fusion" class="mt-3" style="display: none;">
                                <div class="alert alert-warning">
                                    <h6><i class="ti ti-alert-triangle me-1"></i>Confirmación de Fusión</h6>
                                    <p class="mb-2">¿Está seguro de que desea fusionar los leads seleccionados?</p>
                                    <div class="mb-2">
                                        <label class="form-label">Lead principal (se mantendrán sus datos):</label>
                                        <select class="form-select" id="lead_principal_fusion">
                                            <!-- Se llenará dinámicamente -->
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Observaciones de la fusión:</label>
                                        <textarea class="form-control" id="observaciones_fusion" rows="2" 
                                                  placeholder="Explique el motivo de la fusión..."></textarea>
                                    </div>
                                    <button type="button" class="btn btn-warning" id="confirmar_fusion">
                                        <i class="ti ti-check me-1"></i>Confirmar Fusión
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancelar_fusion">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sin duplicados -->
                <div id="sin_duplicados" class="text-center" style="display: none;">
                    <div class="card bg-success bg-opacity-10">
                        <div class="card-body p-4">
                            <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
                            <h5 class="text-success mt-2">¡No se encontraron duplicados!</h5>
                            <p class="text-muted">La base de datos está limpia o no hay coincidencias con los criterios de búsqueda.</p>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div id="estadisticas_duplicados" class="mt-3" style="display: none;">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 text-center">
                                <div class="card-body p-3">
                                    <h4 class="text-primary mb-1" id="stat_total_leads">0</h4>
                                    <small class="text-muted">Total Leads</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 text-center">
                                <div class="card-body p-3">
                                    <h4 class="text-warning mb-1" id="stat_duplicados">0</h4>
                                    <small class="text-muted">Posibles Duplicados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10 text-center">
                                <div class="card-body p-3">
                                    <h4 class="text-info mb-1" id="stat_email_duplicados">0</h4>
                                    <small class="text-muted">Emails Duplicados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger bg-opacity-10 text-center">
                                <div class="card-body p-3">
                                    <h4 class="text-danger mb-1" id="stat_telefono_duplicados">0</h4>
                                    <small class="text-muted">Teléfonos Duplicados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btn_exportar_duplicados">
                    <i class="ti ti-download me-1"></i>Exportar Reporte
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Variables globales
    let duplicadosEncontrados = [];
    let leadsSeleccionados = [];

    // Búsqueda de duplicados
    $('#btn_buscar_duplicados').on('click', function() {
        buscarDuplicados('manual');
    });

    // Verificar todos los leads
    $('#btn_verificar_todos').on('click', function() {
        buscarDuplicados('todos');
    });

    // Función principal de búsqueda
    function buscarDuplicados(tipo) {
        $('#loading_duplicados').show();
        $('#resultados_duplicados').hide();
        $('#sin_duplicados').hide();
        $('#estadisticas_duplicados').hide();

        let data = { accion: 'buscar_duplicados', tipo: tipo };

        if (tipo === 'manual') {
            data.email = $('#buscar_email').val();
            data.telefono = $('#buscar_telefono').val();
            data.nombre = $('#buscar_nombre').val();

            if (!data.email && !data.telefono && !data.nombre) {
                alert('Por favor, ingrese al menos un criterio de búsqueda');
                $('#loading_duplicados').hide();
                return;
            }
        }

        $.ajax({
            url: 'acciones/leads/verificar_duplicados.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                $('#loading_duplicados').hide();
                
                if (response.success) {
                    if (response.duplicados && response.duplicados.length > 0) {
                        mostrarDuplicados(response.duplicados);
                        mostrarEstadisticas(response.estadisticas);
                    } else {
                        $('#sin_duplicados').show();
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                $('#loading_duplicados').hide();
                alert('Error de conexión al verificar duplicados');
            }
        });
    }

    // Mostrar duplicados en la tabla
    function mostrarDuplicados(duplicados) {
        duplicadosEncontrados = duplicados;
        let tbody = $('#tbody_duplicados');
        tbody.empty();

        duplicados.forEach(function(lead, index) {
            let row = `
                <tr class="duplicate-row" data-id="${lead.id}">
                    <td>
                        <input type="checkbox" class="form-check-input duplicate-check" value="${lead.id}">
                    </td>
                    <td>
                        <strong>${lead.id}</strong><br>
                        <small class="text-muted">${lead.codigo_lead || ''}</small>
                    </td>
                    <td>
                        <strong>${lead.nombres_estudiante || ''} ${lead.apellidos_estudiante || ''}</strong><br>
                        <small class="text-muted">${lead.fecha_nacimiento_estudiante || 'Sin fecha'}</small>
                    </td>
                    <td>
                        <strong>${lead.nombres_contacto || ''} ${lead.apellidos_contacto || ''}</strong><br>
                        <small class="text-muted">${lead.parentesco || 'Contacto'}</small>
                    </td>
                    <td>
                        <span class="telefono-info">${lead.telefono || 'Sin teléfono'}</span><br>
                        ${lead.whatsapp ? `<small class="text-success">WA: ${lead.whatsapp}</small>` : ''}
                    </td>
                    <td>
                        <span class="email-info">${lead.email || 'Sin email'}</span><br>
                        <small class="text-muted">${lead.motivo_duplicado || ''}</small>
                    </td>
                    <td>
                        <span class="badge" style="background-color: ${lead.color_estado || '#6c757d'};">
                            ${lead.estado_lead || 'Sin estado'}
                        </span>
                    </td>
                    <td>
                        <small>${lead.fecha_registro || 'Sin fecha'}</small>
                    </td>
                    <td>
                        <div class="btn-group-vertical">
                            <button type="button" class="btn btn-outline-info btn-sm btn-ver-lead" data-id="${lead.id}">
                                <i class="ti ti-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm btn-editar-lead" data-id="${lead.id}">
                                <i class="ti ti-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        $('#contador_duplicados').text(duplicados.length);
        $('#resultados_duplicados').show();
    }

    // Mostrar estadísticas
    function mostrarEstadisticas(stats) {
        $('#stat_total_leads').text(stats.total_leads || 0);
        $('#stat_duplicados').text(stats.duplicados_encontrados || 0);
        $('#stat_email_duplicados').text(stats.email_duplicados || 0);
        $('#stat_telefono_duplicados').text(stats.telefono_duplicados || 0);
        $('#estadisticas_duplicados').show();
    }

    // Manejar selección de duplicados
    $(document).on('change', '.duplicate-check', function() {
        actualizarSeleccionados();
    });

    $('#check_all_duplicados').on('change', function() {
        $('.duplicate-check').prop('checked', $(this).is(':checked'));
        actualizarSeleccionados();
    });

    function actualizarSeleccionados() {
        leadsSeleccionados = [];
        $('.duplicate-check:checked').each(function() {
            leadsSeleccionados.push($(this).val());
        });

        $('#contador_seleccionados').text(leadsSeleccionados.length + ' seleccionados');
        
        if (leadsSeleccionados.length > 0) {
            $('#acciones_duplicados').show();
        } else {
            $('#acciones_duplicados').hide();
            $('#confirmacion_fusion').hide();
        }
    }

    // Acciones para duplicados
    $('#btn_fusionar_leads').on('click', function() {
        if (leadsSeleccionados.length < 2) {
            alert('Debe seleccionar al menos 2 leads para fusionar');
            return;
        }

        // Llenar select de lead principal
        let select = $('#lead_principal_fusion');
        select.empty();
        leadsSeleccionados.forEach(function(id) {
            let lead = duplicadosEncontrados.find(l => l.id == id);
            if (lead) {
                select.append(`<option value="${id}">ID ${id} - ${lead.nombres_contacto} ${lead.apellidos_contacto}</option>`);
            }
        });

        $('#confirmacion_fusion').show();
    });

    $('#cancelar_fusion').on('click', function() {
        $('#confirmacion_fusion').hide();
    });

    $('#confirmar_fusion').on('click', function() {
        let leadPrincipal = $('#lead_principal_fusion').val();
        let observaciones = $('#observaciones_fusion').val();

        if (!leadPrincipal) {
            alert('Debe seleccionar el lead principal');
            return;
        }

        if (!observaciones.trim()) {
            alert('Debe proporcionar observaciones sobre la fusión');
            return;
        }

        fusionarLeads(leadPrincipal, leadsSeleccionados, observaciones);
    });

    // Función para fusionar leads
    function fusionarLeads(principal, duplicados, observaciones) {
        $.ajax({
            url: 'acciones/leads/gestionar_duplicados.php',
            method: 'POST',
            data: {
                accion: 'fusionar',
                lead_principal: principal,
                leads_duplicados: duplicados,
                observaciones: observaciones
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Leads fusionados exitosamente');
                    $('#modalDuplicados').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al fusionar leads');
            }
        });
    }

    // Desactivar duplicados
    $('#btn_desactivar_duplicados').on('click', function() {
        if (leadsSeleccionados.length === 0) {
            alert('Debe seleccionar al menos un lead');
            return;
        }

        if (confirm('¿Está seguro de que desea desactivar los leads seleccionados?')) {
            $.ajax({
                url: 'acciones/leads/gestionar_duplicados.php',
                method: 'POST',
                data: {
                    accion: 'desactivar',
                    leads: leadsSeleccionados
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Leads desactivados exitosamente');
                        buscarDuplicados('todos'); // Refrescar búsqueda
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al desactivar leads');
                }
            });
        }
    });

    // Ver/editar lead desde duplicados
    $(document).on('click', '.btn-ver-lead', function() {
        let id = $(this).data('id');
        cargarDatosLead(id, 'consultar');
    });

    $(document).on('click', '.btn-editar-lead', function() {
        let id = $(this).data('id');
        cargarDatosLead(id, 'editar');
    });

    // Exportar reporte
    $('#btn_exportar_duplicados').on('click', function() {
        window.open('reports/reporte_duplicados.php', '_blank');
    });

    // Limpiar modal al cerrar
    $('#modalDuplicados').on('hidden.bs.modal', function() {
        $('#buscar_email').val('');
        $('#buscar_telefono').val('');
        $('#buscar_nombre').val('');
        $('#resultados_duplicados').hide();
        $('#sin_duplicados').hide();
        $('#estadisticas_duplicados').hide();
        $('#acciones_duplicados').hide();
        $('#confirmacion_fusion').hide();
        duplicadosEncontrados = [];
        leadsSeleccionados = [];
    });
});
</script>