<!-- Modal Ver Historial Familia -->
<div class="modal fade" id="modalHistorialFamilia" tabindex="-1" aria-labelledby="modalHistorialFamiliaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border: none;">
        <h5 class="modal-title" id="modalHistorialFamiliaLabel" style="color: #2c3e50; font-weight: 600;">
          <i class="ti ti-history me-2"></i>
          Historial de Participación Familiar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="background-color: #ffffff;" id="contenidoHistorialFamilia">
        <!-- Loading Spinner -->
        <div class="text-center py-5" id="loadingHistorial">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-3 text-muted">Cargando historial familiar...</p>
        </div>

        <!-- Contenido dinámico -->
        <div id="datosHistorial" style="display: none;">
          <!-- Información de la Familia -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card" style="background: linear-gradient(135deg, #cfd9df 0%, #e2ebf0 100%); border: none; border-radius: 12px;">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-md-8">
                      <div class="d-flex align-items-center mb-2">
                        <div class="bg-white rounded-circle p-3 me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                          <i class="ti ti-home" style="font-size: 2rem; color: #667eea;"></i>
                        </div>
                        <div>
                          <h4 class="mb-1" id="familia-nombre" style="color: #2c3e50; font-weight: 600;"></h4>
                          <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary" id="familia-codigo"></span>
                            <span class="badge" id="familia-nivel-badge"></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 text-end">
                      <div id="familia-contacto" class="small text-muted">
                        <!-- Se llenará dinámicamente -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Estadísticas Resumidas -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #fff3e0; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-calendar-event" style="font-size: 2rem; color: #ff9800;"></i>
                  <h2 class="mt-2 mb-0" id="familia-total-eventos" style="color: #e65100; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Eventos Totales</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #e8f5e9; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-check-circle" style="font-size: 2rem; color: #4caf50;"></i>
                  <h2 class="mt-2 mb-0" id="familia-total-asistencias" style="color: #2e7d32; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Asistencias</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #ffebee; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-x-circle" style="font-size: 2rem; color: #f44336;"></i>
                  <h2 class="mt-2 mb-0" id="familia-total-ausencias" style="color: #c62828; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Ausencias</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #e3f2fd; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-percentage" style="font-size: 2rem; color: #2196f3;"></i>
                  <h2 class="mt-2 mb-0" id="familia-tasa-asistencia" style="color: #1565c0; font-weight: 700;">0%</h2>
                  <p class="mb-0 small text-muted">Tasa de Asistencia</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Gráfica de Tendencia -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-chart-line me-2"></i>
                    Tendencia de Participación (Últimos 12 meses)
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <canvas id="chartTendencia" height="80"></canvas>
                    </div>
                    <div class="col-md-4">
                      <h6 class="mb-3" style="color: #495057;">Resumen Trimestral</h6>
                      <div id="resumen-trimestral">
                        <!-- Se llenará dinámicamente -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Miembros de la Familia -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-users me-2"></i>
                    Miembros de la Familia
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row" id="miembros-familia">
                    <!-- Se llenará dinámicamente -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Timeline de Eventos -->
          <div class="row">
            <div class="col-md-12">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-timeline me-2"></i>
                    Historial de Eventos
                  </h6>
                  <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="filtro-eventos" id="filtro-todos" value="todos" checked>
                    <label class="btn btn-outline-primary" for="filtro-todos">Todos</label>
                    
                    <input type="radio" class="btn-check" name="filtro-eventos" id="filtro-asistio" value="asistio">
                    <label class="btn btn-outline-success" for="filtro-asistio">Asistió</label>
                    
                    <input type="radio" class="btn-check" name="filtro-eventos" id="filtro-ausente" value="no_asistio">
                    <label class="btn btn-outline-danger" for="filtro-ausente">Ausente</label>
                  </div>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                  <div class="timeline" id="timeline-eventos">
                    <!-- Se llenará dinámicamente -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
        <button type="button" class="btn btn-outline-primary" onclick="exportarHistorialFamilia()">
          <i class="ti ti-download me-1"></i>
          Exportar Historial
        </button>
        <button type="button" class="btn btn-outline-info" onclick="enviarReporteFamilia()">
          <i class="ti ti-mail me-1"></i>
          Enviar por Email
        </button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="color: #6c757d;">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
#modalHistorialFamilia .modal-content {
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

#modalHistorialFamilia .card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#modalHistorialFamilia .card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

/* Timeline Styles */
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
}

.timeline-item {
  position: relative;
  padding-bottom: 25px;
  margin-bottom: 15px;
  transition: all 0.3s ease;
}

.timeline-item:hover {
  transform: translateX(5px);
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -26px;
  top: 5px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #fff;
  border: 3px solid;
  z-index: 1;
}

.timeline-item.asistio::before {
  border-color: #28a745;
  background-color: #28a745;
}

.timeline-item.no_asistio::before {
  border-color: #dc3545;
  background-color: #dc3545;
}

.timeline-item.confirmado::before {
  border-color: #17a2b8;
  background-color: #17a2b8;
}

.timeline-item.invitado::before {
  border-color: #6c757d;
  background-color: #fff;
}

.timeline-item.cancelado::before {
  border-color: #fd7e14;
  background-color: #fd7e14;
}

.timeline-content {
  padding: 15px;
  border-radius: 8px;
  background-color: #f8f9fa;
  border-left: 3px solid #e9ecef;
  transition: all 0.3s ease;
}

.timeline-item:hover .timeline-content {
  background-color: #e9ecef;
  border-left-color: #667eea;
}

.timeline-date {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 5px;
}

.timeline-title {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.timeline-details {
  font-size: 0.85rem;
  color: #6c757d;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.miembro-card {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
}

.miembro-card:hover {
  background-color: #e9ecef;
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.miembro-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  font-weight: 700;
  margin-bottom: 10px;
}

.trimestre-item {
  padding: 10px;
  border-radius: 8px;
  background-color: #f8f9fa;
  margin-bottom: 10px;
  border-left: 3px solid;
  transition: all 0.3s ease;
}

.trimestre-item:hover {
  background-color: #e9ecef;
  transform: translateX(5px);
}

.trimestre-q1 { border-left-color: #28a745; }
.trimestre-q2 { border-left-color: #17a2b8; }
.trimestre-q3 { border-left-color: #ffc107; }
.trimestre-q4 { border-left-color: #dc3545; }
</style>