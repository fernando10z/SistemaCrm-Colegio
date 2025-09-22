<!-- Modal para filtrar por fechas -->
<div class="modal fade" id="modalFiltrarFechas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-calendar me-2"></i>
                    Filtrar por Fechas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFiltrarFechas" method="GET" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Filtro rápido:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="filtro_rapido" id="hoy" value="hoy" onchange="aplicarFiltroRapido('hoy')">
                            <label class="btn btn-outline-primary btn-sm" for="hoy">Hoy</label>

                            <input type="radio" class="btn-check" name="filtro_rapido" id="ayer" value="ayer" onchange="aplicarFiltroRapido('ayer')">
                            <label class="btn btn-outline-primary btn-sm" for="ayer">Ayer</label>

                            <input type="radio" class="btn-check" name="filtro_rapido" id="semana" value="semana" onchange="aplicarFiltroRapido('semana')">
                            <label class="btn btn-outline-primary btn-sm" for="semana">Esta Semana</label>

                            <input type="radio" class="btn-check" name="filtro_rapido" id="mes" value="mes" onchange="aplicarFiltroRapido('mes')">
                            <label class="btn btn-outline-primary btn-sm" for="mes">Este Mes</label>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha desde: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio_filtro" 
                                       value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin_filtro" 
                                       value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de fecha:</label>
                        <select class="form-select" name="tipo_fecha" id="tipo_fecha">
                            <option value="created_at">Fecha de Creación</option>
                            <option value="fecha_envio">Fecha de Envío</option>
                            <option value="fecha_entrega">Fecha de Entrega</option>
                            <option value="fecha_lectura">Fecha de Lectura</option>
                        </select>
                        <div class="form-text">Selecciona qué fecha usar para el filtro</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de mensaje:</label>
                                <select class="form-select" name="tipo_mensaje">
                                    <option value="">Todos los tipos</option>
                                    <option value="email" <?php echo (isset($_GET['tipo_mensaje']) && $_GET['tipo_mensaje'] == 'email') ? 'selected' : ''; ?>>Email</option>
                                    <option value="whatsapp" <?php echo (isset($_GET['tipo_mensaje']) && $_GET['tipo_mensaje'] == 'whatsapp') ? 'selected' : ''; ?>>WhatsApp</option>
                                    <option value="sms" <?php echo (isset($_GET['tipo_mensaje']) && $_GET['tipo_mensaje'] == 'sms') ? 'selected' : ''; ?>>SMS</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado:</label>
                                <select class="form-select" name="estado_mensaje">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" <?php echo (isset($_GET['estado_mensaje']) && $_GET['estado_mensaje'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="enviado" <?php echo (isset($_GET['estado_mensaje']) && $_GET['estado_mensaje'] == 'enviado') ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="entregado" <?php echo (isset($_GET['estado_mensaje']) && $_GET['estado_mensaje'] == 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                                    <option value="leido" <?php echo (isset($_GET['estado_mensaje']) && $_GET['estado_mensaje'] == 'leido') ? 'selected' : ''; ?>>Leído</option>
                                    <option value="fallido" <?php echo (isset($_GET['estado_mensaje']) && $_GET['estado_mensaje'] == 'fallido') ? 'selected' : ''; ?>>Fallido</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Nota:</strong> Los filtros por fecha pueden afectar la velocidad de carga si el rango es muy amplio.
                        Se recomienda usar rangos de fechas específicos para mejor rendimiento.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="limpiarFiltros()">
                        <i class="ti ti-refresh me-1"></i>
                        Limpiar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i>
                        Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aplicarFiltroRapido(tipo) {
    const fechaInicio = document.getElementById('fecha_inicio_filtro');
    const fechaFin = document.getElementById('fecha_fin_filtro');
    const hoy = new Date();
    
    switch(tipo) {
        case 'hoy':
            fechaInicio.value = fechaFin.value = formatearFecha(hoy);
            break;
        case 'ayer':
            const ayer = new Date(hoy);
            ayer.setDate(hoy.getDate() - 1);
            fechaInicio.value = fechaFin.value = formatearFecha(ayer);
            break;
        case 'semana':
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - hoy.getDay());
            fechaInicio.value = formatearFecha(inicioSemana);
            fechaFin.value = formatearFecha(hoy);
            break;
        case 'mes':
            const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fechaInicio.value = formatearFecha(inicioMes);
            fechaFin.value = formatearFecha(hoy);
            break;
    }
}

function formatearFecha(fecha) {
    return fecha.toISOString().split('T')[0];
}

function limpiarFiltros() {
    document.getElementById('formFiltrarFechas').reset();
    // Deseleccionar filtros rápidos
    document.querySelectorAll('input[name="filtro_rapido"]').forEach(radio => {
        radio.checked = false;
    });
    // Restaurar fechas por defecto
    const hoy = new Date();
    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    document.getElementById('fecha_inicio_filtro').value = formatearFecha(inicioMes);
    document.getElementById('fecha_fin_filtro').value = formatearFecha(hoy);
}
</script>