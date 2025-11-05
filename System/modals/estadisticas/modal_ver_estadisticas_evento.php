<!-- Modal Ver Estadísticas del Evento -->
<div class="modal fade" id="modalEstadisticasEvento" tabindex="-1" aria-labelledby="modalEstadisticasEventoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #fbc2eb 0%, #fad0c4 100%); border: none;">
        <h5 class="modal-title" id="modalEstadisticasEventoLabel" style="color: #2c3e50; font-weight: 600;">
          <i class="ti ti-chart-pie me-2"></i>
          Estadísticas del Evento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="background-color: #ffffff;" id="contenidoEstadisticasEvento">
        <!-- Loading Spinner -->
        <div class="text-center py-5" id="loadingEstadisticas">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-3 text-muted">Cargando estadísticas del evento...</p>
        </div>

        <!-- Contenido dinámico -->
        <div id="datosEstadisticas" style="display: none;">
          <!-- Información General del Evento -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card" style="background: linear-gradient(135deg, #e0c3fc 0%, #f3e7e9 100%); border: none; border-radius: 12px;">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <h4 class="mb-1" id="evento-titulo" style="color: #2c3e50; font-weight: 600;"></h4>
                      <div class="d-flex flex-wrap gap-3 mt-2">
                        <span id="evento-fecha" style="font-size: 0.9rem; color: #6c757d;">
                          <i class="ti ti-calendar me-1"></i>
                        </span>
                        <span id="evento-tipo" style="font-size: 0.9rem;">
                          <i class="ti ti-category me-1"></i>
                        </span>
                        <span id="evento-dirigido" style="font-size: 0.9rem; color: #6c757d;">
                          <i class="ti ti-users me-1"></i>
                        </span>
                      </div>
                    </div>
                    <div class="col-md-4 text-end">
                      <div class="d-flex flex-column align-items-end">
                        <span class="text-muted small">Capacidad</span>
                        <h3 id="evento-capacidad" class="mb-0" style="color: #667eea; font-weight: 700;"></h3>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Métricas Principales -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #e8f5e9; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-users" style="font-size: 2rem; color: #4caf50;"></i>
                  <h2 class="mt-2 mb-0" id="total-participantes" style="color: #2e7d32; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Total Participantes</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #e3f2fd; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-user-check" style="font-size: 2rem; color: #2196f3;"></i>
                  <h2 class="mt-2 mb-0" id="total-asistentes" style="color: #1565c0; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Asistentes</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #fff3e0; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-clock" style="font-size: 2rem; color: #ff9800;"></i>
                  <h2 class="mt-2 mb-0" id="total-confirmados" style="color: #e65100; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">Confirmados</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center" style="background-color: #ffebee; border: none; border-radius: 10px;">
                <div class="card-body py-3">
                  <i class="ti ti-user-x" style="font-size: 2rem; color: #f44336;"></i>
                  <h2 class="mt-2 mb-0" id="total-ausentes" style="color: #c62828; font-weight: 700;">0</h2>
                  <p class="mb-0 small text-muted">No Asistieron</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tasa de Asistencia -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                      <i class="ti ti-chart-bar me-2"></i>
                      Tasa de Asistencia
                    </h6>
                    <h4 class="mb-0" id="porcentaje-asistencia" style="color: #667eea; font-weight: 700;">0%</h4>
                  </div>
                  <div class="progress" style="height: 20px; border-radius: 10px;">
                    <div class="progress-bar" id="barra-asistencia" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted">0%</small>
                    <small class="text-muted">50%</small>
                    <small class="text-muted">100%</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Distribución por Estado -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-chart-donut me-2"></i>
                    Distribución por Estado
                  </h6>
                </div>
                <div class="card-body">
                  <div id="distribucion-estados">
                    <!-- Se llenará dinámicamente -->
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-users-group me-2"></i>
                    Participación por Tipo
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-around align-items-center" style="height: 100%;">
                    <div class="text-center">
                      <i class="ti ti-home" style="font-size: 2.5rem; color: #667eea;"></i>
                      <h3 class="mt-2 mb-0" id="total-familias" style="color: #495057; font-weight: 700;">0</h3>
                      <p class="mb-0 small text-muted">Familias</p>
                    </div>
                    <div class="text-center">
                      <i class="ti ti-user-star" style="font-size: 2.5rem; color: #764ba2;"></i>
                      <h3 class="mt-2 mb-0" id="total-apoderados" style="color: #495057; font-weight: 700;">0</h3>
                      <p class="mb-0 small text-muted">Apoderados</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Top 5 Familias más Participativas -->
          <div class="row">
            <div class="col-md-12">
              <div class="card" style="border: 1px solid #e9ecef; border-radius: 10px;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                  <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                    <i class="ti ti-trophy me-2"></i>
                    Top 5 Familias Participantes en este Evento
                  </h6>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover" id="tabla-top-familias">
                      <thead style="background-color: #f8f9fa;">
                        <tr>
                          <th width="10%">Posición</th>
                          <th width="30%">Familia</th>
                          <th width="20%">Código</th>
                          <th width="20%">Participantes</th>
                          <th width="20%">Estado</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Se llenará dinámicamente -->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
        <!-- <button type="button" class="btn btn-outline-primary" onclick="exportarEstadisticasEvento()">
          <i class="ti ti-download me-1"></i>
          Exportar Reporte
        </button> -->
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="color: #6c757d;">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
#modalEstadisticasEvento .modal-content {
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

#modalEstadisticasEvento .card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#modalEstadisticasEvento .card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.estado-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  margin: 8px 0;
  border-radius: 8px;
  background-color: #f8f9fa;
  transition: all 0.3s ease;
}

.estado-item:hover {
  background-color: #e9ecef;
  transform: translateX(5px);
}

.estado-item .badge {
  font-size: 0.85rem;
  padding: 0.4rem 0.8rem;
  border-radius: 8px;
}

.familia-item {
  transition: background-color 0.3s ease;
}

.familia-item:hover {
  background-color: #f8f9fa;
}

.posicion-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  font-weight: 700;
  font-size: 1.1rem;
}

.posicion-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #856404; }
.posicion-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); color: #495057; }
.posicion-3 { background: linear-gradient(135deg, #cd7f32 0%, #e6a96e 100%); color: #fff; }
.posicion-default { background-color: #e9ecef; color: #6c757d; }
</style>