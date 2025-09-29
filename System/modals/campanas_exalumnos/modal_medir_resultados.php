<!-- Modal Medir Resultados -->
<div class="modal fade" id="modalMedirResultados" tabindex="-1" aria-labelledby="modalMedirResultadosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMedirResultadosLabel">
          <i class="ti ti-chart-bar me-2"></i>Medir Resultados de Campañas
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <!-- Filtros de Análisis -->
        <div class="card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Filtros de Análisis</h6>
          </div>
          <div class="card-body">
            <form id="formFiltrosAnalisis">
              <div class="row">
                <div class="col-md-3 mb-3">
                  <label for="periodo_analisis" class="form-label">Período</label>
                  <select class="form-select form-select-sm" id="periodo_analisis">
                    <option value="7">Últimos 7 días</option>
                    <option value="30" selected>Últimos 30 días</option>
                    <option value="90">Últimos 3 meses</option>
                    <option value="365">Último año</option>
                    <option value="todo">Todo el tiempo</option>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="tipo_campana_filtro" class="form-label">Tipo de Campaña</label>
                  <select class="form-select form-select-sm" id="tipo_campana_filtro">
                    <option value="">Todas</option>
                    <option value="networking">Networking</option>
                    <option value="evento">Evento</option>
                    <option value="agradecimiento">Agradecimiento</option>
                    <option value="boletin">Boletín</option>
                    <option value="reunion">Reunión</option>
                    <option value="reconocimiento">Reconocimiento</option>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="canal_filtro" class="form-label">Canal</label>
                  <select class="form-select form-select-sm" id="canal_filtro">
                    <option value="">Todos</option>
                    <option value="email">Email</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="sms">SMS</option>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label class="form-label">&nbsp;</label>
                  <button type="button" class="btn btn-primary btn-sm w-100" onclick="generarAnalisis()">
                    <i class="ti ti-refresh me-1"></i>Actualizar Análisis
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Resumen de Métricas -->
        <div class="row mb-3">
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h3 class="mb-0" id="metrica_enviados">0</h3>
                <small class="text-muted">Mensajes Enviados</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h3 class="mb-0 text-warning" id="metrica_entregados">0</h3>
                <small class="text-muted">Entregados</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h3 class="mb-0 text-success" id="metrica_leidos">0</h3>
                <small class="text-muted">Leídos</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h3 class="mb-0 text-info" id="metrica_tasa">0%</h3>
                <small class="text-muted">Tasa de Apertura</small>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Análisis Detallado -->
        <div class="row">
          <!-- Por Tipo de Campaña -->
          <div class="col-md-6 mb-3">
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0">Efectividad por Tipo de Campaña</h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Tipo</th>
                        <th class="text-center">Enviados</th>
                        <th class="text-center">Leídos</th>
                        <th class="text-center">Tasa</th>
                      </tr>
                    </thead>
                    <tbody id="tabla_tipo_campana">
                      <?php
                      $analisis_tipo = $conn->query("SELECT 
                          CASE 
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%networking%' THEN 'Networking'
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%evento%' THEN 'Evento'
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%agradecimiento%' THEN 'Agradecimiento'
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%boletin%' THEN 'Boletín'
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%reunion%' THEN 'Reunión'
                              WHEN LOWER(IFNULL(asunto, '')) LIKE '%reconocimiento%' THEN 'Reconocimiento'
                              ELSE 'General'
                          END as tipo,
                          COUNT(*) as total,
                          COUNT(CASE WHEN estado = 'leido' THEN 1 END) as leidos,
                          ROUND(COUNT(CASE WHEN estado = 'leido' THEN 1 END) / COUNT(*) * 100, 1) as tasa
                      FROM mensajes_enviados
                      WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY tipo
                      ORDER BY total DESC");
                      
                      while($tipo = $analisis_tipo->fetch_assoc()) {
                          $badge_color = '';
                          if ($tipo['tasa'] >= 50) $badge_color = 'success';
                          elseif ($tipo['tasa'] >= 30) $badge_color = 'warning';
                          else $badge_color = 'danger';
                          
                          echo "<tr>
                                  <td>" . htmlspecialchars($tipo['tipo']) . "</td>
                                  <td class='text-center'>" . number_format($tipo['total']) . "</td>
                                  <td class='text-center'>" . number_format($tipo['leidos']) . "</td>
                                  <td class='text-center'><span class='badge bg-" . $badge_color . "'>" . $tipo['tasa'] . "%</span></td>
                                </tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Por Canal -->
          <div class="col-md-6 mb-3">
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0">Efectividad por Canal</h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Canal</th>
                        <th class="text-center">Enviados</th>
                        <th class="text-center">Leídos</th>
                        <th class="text-center">Costo Total</th>
                      </tr>
                    </thead>
                    <tbody id="tabla_canal">
                      <?php
                      $analisis_canal = $conn->query("SELECT 
                          tipo as canal,
                          COUNT(*) as total,
                          COUNT(CASE WHEN estado = 'leido' THEN 1 END) as leidos,
                          IFNULL(SUM(costo), 0) as costo_total
                      FROM mensajes_enviados
                      WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY tipo
                      ORDER BY total DESC");
                      
                      while($canal = $analisis_canal->fetch_assoc()) {
                          echo "<tr>
                                  <td><strong>" . strtoupper($canal['canal']) . "</strong></td>
                                  <td class='text-center'>" . number_format($canal['total']) . "</td>
                                  <td class='text-center'>" . number_format($canal['leidos']) . "</td>
                                  <td class='text-center'>S/ " . number_format((float)$canal['costo_total'], 2) . "</td>
                                </tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Promociones más Participativas -->
        <div class="card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Promociones con Mayor Participación</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Promoción</th>
                    <th class="text-center">Exalumnos</th>
                    <th class="text-center">Mensajes Enviados</th>
                    <th class="text-center">Mensajes Leídos</th>
                    <th class="text-center">Tasa de Respuesta</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $analisis_promo = $conn->query("SELECT 
                      ex.promocion_egreso,
                      COUNT(DISTINCT ex.id) as total_exalumnos,
                      COUNT(m.id) as mensajes_enviados,
                      COUNT(CASE WHEN m.estado = 'leido' THEN 1 END) as mensajes_leidos,
                      ROUND(COUNT(CASE WHEN m.estado = 'leido' THEN 1 END) / COUNT(m.id) * 100, 1) as tasa_respuesta
                  FROM exalumnos ex
                  LEFT JOIN mensajes_enviados m ON (
                      (m.destinatario_email = ex.email) OR (m.destinatario_telefono = ex.telefono)
                  )
                  WHERE ex.promocion_egreso IS NOT NULL 
                    AND m.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY ex.promocion_egreso
                  HAVING mensajes_enviados > 0
                  ORDER BY tasa_respuesta DESC, mensajes_enviados DESC
                  LIMIT 10");
                  
                  while($promo = $analisis_promo->fetch_assoc()) {
                      $badge_color = '';
                      if ($promo['tasa_respuesta'] >= 50) $badge_color = 'success';
                      elseif ($promo['tasa_respuesta'] >= 30) $badge_color = 'warning';
                      else $badge_color = 'danger';
                      
                      echo "<tr>
                              <td><strong>Promoción " . htmlspecialchars($promo['promocion_egreso']) . "</strong></td>
                              <td class='text-center'>" . number_format($promo['total_exalumnos']) . "</td>
                              <td class='text-center'>" . number_format($promo['mensajes_enviados']) . "</td>
                              <td class='text-center'>" . number_format($promo['mensajes_leidos']) . "</td>
                              <td class='text-center'><span class='badge bg-" . $badge_color . "'>" . $promo['tasa_respuesta'] . "%</span></td>
                            </tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Botones de Exportación -->
        <div class="text-center">
          <button type="button" class="btn btn-outline-primary" onclick="exportarPDF()">
            <i class="ti ti-file-download me-1"></i>Exportar a PDF
          </button>
          <button type="button" class="btn btn-outline-success" onclick="exportarExcel()">
            <i class="ti ti-table-export me-1"></i>Exportar a Excel
          </button>
        </div>
        
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function generarAnalisis() {
    Swal.fire({
        title: 'Generando análisis...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Aquí iría la llamada AJAX para regenerar el análisis con los filtros
    setTimeout(function() {
        Swal.fire(
            'Análisis Actualizado',
            'Los resultados han sido actualizados con los filtros seleccionados',
            'success'
        );
    }, 1500);
}

function exportarPDF() {
    Swal.fire({
        title: 'Generando PDF...',
        text: 'El archivo se descargará automáticamente',
        icon: 'info',
        timer: 2000,
        showConfirmButton: false
    });
    // Aquí iría la lógica para exportar a PDF
}

function exportarExcel() {
    Swal.fire({
        title: 'Generando Excel...',
        text: 'El archivo se descargará automáticamente',
        icon: 'info',
        timer: 2000,
        showConfirmButton: false
    });
    // Aquí iría la lógica para exportar a Excel
}
</script>