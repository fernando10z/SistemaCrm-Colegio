<!-- Modal para estado de entrega -->
<div class="modal fade" id="modalEstadoEntrega" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-truck me-2"></i>
                    Estado de Entrega y Métricas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- Estadísticas Generales -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="text-primary"><i class="ti ti-chart-bar me-1"></i> Estadísticas Generales</h6>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="card bg-primary text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0"><?php echo $stats['total_mensajes'] ?? 0; ?></h4>
                                        <small>Total Mensajes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-info text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0"><?php echo $stats['mensajes_enviados'] ?? 0; ?></h4>
                                        <small>Enviados</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-success text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0"><?php echo $stats['mensajes_entregados'] ?? 0; ?></h4>
                                        <small>Entregados</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-warning text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0"><?php echo $stats['mensajes_leidos'] ?? 0; ?></h4>
                                        <small>Leídos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-danger text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0"><?php echo $stats['mensajes_fallidos'] ?? 0; ?></h4>
                                        <small>Fallidos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-secondary text-white text-center">
                                    <div class="card-body py-2">
                                        <h4 class="mb-0">S/ <?php echo number_format($stats['costo_total'] ?? 0, 2); ?></h4>
                                        <small>Costo Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métricas por Tipo -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="ti ti-messages me-1"></i> Mensajes por Tipo</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-danger">Email</span></td>
                                        <td><?php echo $stats['total_emails'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['total_emails'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">WhatsApp</span></td>
                                        <td><?php echo $stats['total_whatsapp'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['total_whatsapp'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">SMS</span></td>
                                        <td><?php echo $stats['total_sms'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['total_sms'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info"><i class="ti ti-percentage me-1"></i> Tasas de Éxito</h6>
                        <div class="mb-3">
                            <label class="form-label">Tasa de Entrega</label>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $stats['tasa_entrega'] ?? 0; ?>%">
                                    <?php echo $stats['tasa_entrega'] ?? 0; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tasa de Lectura</label>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: <?php echo $stats['tasa_lectura'] ?? 0; ?>%">
                                    <?php echo $stats['tasa_lectura'] ?? 0; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-light">
                            <small>
                                <strong>Hoy:</strong> <?php echo $stats['mensajes_hoy'] ?? 0; ?> mensajes<br>
                                <strong>Esta semana:</strong> <?php echo $stats['mensajes_semana'] ?? 0; ?> mensajes
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Análisis de Fallos -->
                <?php if (!empty($fallos_stats)): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="text-danger"><i class="ti ti-alert-triangle me-1"></i> Análisis de Fallos</h6>
                        <div class="row">
                            <?php foreach ($fallos_stats as $tipo => $fallo): ?>
                            <div class="col-md-4">
                                <div class="card border-danger">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-danger"><?php echo strtoupper($tipo); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Total fallos:</strong> <?php echo $fallo['total_fallos']; ?></p>
                                        <small class="text-muted">
                                            • Conexión: <?php echo $fallo['fallos_conexion']; ?><br>
                                            • Formato: <?php echo $fallo['fallos_formato']; ?><br>
                                            • Límites: <?php echo $fallo['fallos_limite']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Estados Detallados -->
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-primary"><i class="ti ti-list me-1"></i> Estados Detallados</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estado</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Porcentaje</th>
                                        <th>Acción Sugerida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-secondary">Pendiente</span></td>
                                        <td>Mensajes en cola de envío</td>
                                        <td><?php echo $stats['total_mensajes'] - $stats['mensajes_enviados'] - $stats['mensajes_entregados'] - $stats['mensajes_leidos'] - $stats['mensajes_fallidos']; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round((($stats['total_mensajes'] - $stats['mensajes_enviados'] - $stats['mensajes_entregados'] - $stats['mensajes_leidos'] - $stats['mensajes_fallidos']) / $stats['total_mensajes']) * 100, 1) : 0; ?>%</td>
                                        <td><small class="text-muted">Verificar cola de envío</small></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info">Enviado</span></td>
                                        <td>Enviado al proveedor</td>
                                        <td><?php echo $stats['mensajes_enviados'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['mensajes_enviados'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                        <td><small class="text-muted">Esperando confirmación</small></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">Entregado</span></td>
                                        <td>Entregado al destinatario</td>
                                        <td><?php echo $stats['mensajes_entregados'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['mensajes_entregados'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                        <td><small class="text-success">Estado óptimo</small></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning">Leído</span></td>
                                        <td>Leído por el destinatario</td>
                                        <td><?php echo $stats['mensajes_leidos'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['mensajes_leidos'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                        <td><small class="text-success">Excelente engagement</small></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger">Fallido</span></td>
                                        <td>Error en el envío</td>
                                        <td><?php echo $stats['mensajes_fallidos'] ?? 0; ?></td>
                                        <td><?php echo $stats['total_mensajes'] > 0 ? round(($stats['mensajes_fallidos'] ?? 0) / $stats['total_mensajes'] * 100, 1) : 0; ?>%</td>
                                        <td><small class="text-danger">Requiere revisión</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recomendaciones -->
                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading"><i class="ti ti-bulb me-1"></i> Recomendaciones:</h6>
                    <ul class="mb-0">
                        <li><strong>Tasa de entrega baja (&lt;85%):</strong> Verificar configuración de proveedores</li>
                        <li><strong>Muchos fallos:</strong> Revisar formato de números de teléfono y emails</li>
                        <li><strong>Baja tasa de lectura:</strong> Mejorar asuntos y horarios de envío</li>
                        <li><strong>Mensajes pendientes:</strong> Verificar estado de la cola de procesamiento</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="actualizarEstadisticas()">
                    <i class="ti ti-refresh me-1"></i>
                    Actualizar Datos
                </button>
                <button type="button" class="btn btn-outline-success" onclick="exportarMetricas()">
                    <i class="ti ti-download me-1"></i>
                    Exportar Métricas
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarEstadisticas() {
    // Recargar la página para obtener datos actualizados
    location.reload();
}

function exportarMetricas() {
    // Implementar exportación de métricas
    const fechaActual = new Date().toISOString().split('T')[0];
    const url = `reports/generar_metricas_comunicacion.php?fecha=${fechaActual}`;
    window.open(url, '_blank');
}
</script>