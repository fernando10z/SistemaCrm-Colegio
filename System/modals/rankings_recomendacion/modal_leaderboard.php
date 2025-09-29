<style>
/* Estilos para SweetAlert2 */
.swal2-container {
    z-index: 9999999 !important;
}

.swal2-popup {
    z-index: 99999999 !important;
}

.modal {
    z-index: 9999 !important;
}

.modal-backdrop {
    z-index: 9998 !important;
}

/* Estilos para Leaderboard */
.leaderboard-filters {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.leaderboard-item {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.leaderboard-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.leaderboard-posicion {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    flex-shrink: 0;
}

.pos-1 {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    color: white;
    box-shadow: 0 4px 8px rgba(255, 215, 0, 0.5);
}

.pos-2 {
    background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
    color: white;
    box-shadow: 0 4px 8px rgba(192, 192, 192, 0.5);
}

.pos-3 {
    background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
    color: white;
    box-shadow: 0 4px 8px rgba(205, 127, 50, 0.5);
}

.pos-otros {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    color: #495057;
}

.leaderboard-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.leaderboard-nombre {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
}

.leaderboard-familia {
    font-size: 0.85rem;
    color: #6c757d;
    font-style: italic;
}

.leaderboard-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.leaderboard-stat {
    text-align: center;
    min-width: 70px;
}

.stat-valor {
    display: block;
    font-size: 1.3rem;
    font-weight: bold;
    color: #495057;
}

.stat-etiqueta {
    display: block;
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.leaderboard-categoria {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.periodo-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 10px;
}

.filtro-activo {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    border-color: #667eea !important;
}

.empty-leaderboard {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-leaderboard i {
    font-size: 4rem;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>

<!-- Modal Leaderboard Completo -->
<div class="modal fade" id="modalLeaderboard" tabindex="-1" aria-labelledby="modalLeaderboardLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="modal-title" id="modalLeaderboardLabel">
          <i class="ti ti-trophy me-2"></i>
          Leaderboard de Referentes
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        
        <!-- Filtros del Leaderboard -->
        <div class="leaderboard-filters">
          <div class="row g-3">
            
            <!-- Filtro por Período -->
            <div class="col-md-4">
              <label for="filtro_periodo" class="form-label fw-bold">
                <i class="ti ti-calendar me-1"></i>
                Período
              </label>
              <select class="form-select" id="filtro_periodo" onchange="filtrarLeaderboard()">
                <option value="todo">Todo el tiempo</option>
                <option value="mes_actual" selected>Mes actual</option>
                <option value="mes_anterior">Mes anterior</option>
                <option value="trimestre">Último trimestre</option>
                <option value="semestre">Último semestre</option>
                <option value="año">Este año</option>
              </select>
            </div>

            <!-- Filtro por Categoría -->
            <div class="col-md-4">
              <label for="filtro_categoria" class="form-label fw-bold">
                <i class="ti ti-category me-1"></i>
                Categoría
              </label>
              <select class="form-select" id="filtro_categoria" onchange="filtrarLeaderboard()">
                <option value="todas">Todas las categorías</option>
                <option value="Elite">Elite</option>
                <option value="Destacado">Destacado</option>
                <option value="Activo">Activo</option>
                <option value="En Progreso">En Progreso</option>
                <option value="Nuevo">Nuevo</option>
              </select>
            </div>

            <!-- Filtro por Métrica -->
            <div class="col-md-4">
              <label for="filtro_metrica" class="form-label fw-bold">
                <i class="ti ti-sort-descending me-1"></i>
                Ordenar por
              </label>
              <select class="form-select" id="filtro_metrica" onchange="filtrarLeaderboard()">
                <option value="conversiones" selected>Conversiones</option>
                <option value="tasa_conversion">Tasa de conversión</option>
                <option value="total_usos">Total de usos</option>
                <option value="codigos_activos">Códigos activos</option>
              </select>
            </div>

          </div>

          <!-- Botones de Acción -->
          <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
              <span class="periodo-badge" id="periodo_actual">Mes Actual - Septiembre 2025</span>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-light" onclick="limpiarFiltrosLeaderboard()">
                <i class="ti ti-reload me-1"></i>
                Limpiar Filtros
              </button>
              <button type="button" class="btn btn-sm btn-light" onclick="exportarLeaderboard()">
                <i class="ti ti-download me-1"></i>
                Exportar
              </button>
            </div>
          </div>
        </div>

        <!-- Lista del Leaderboard -->
        <div id="leaderboard-lista">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando leaderboard...</span>
            </div>
            <p class="mt-2 text-muted">Cargando datos del ranking...</p>
          </div>
        </div>

        <!-- Estadísticas del Período -->
        <div class="row mt-4" id="stats-periodo" style="display: none;">
          <div class="col-md-12">
            <div class="card" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
              <div class="card-body">
                <h6 class="mb-3">
                  <i class="ti ti-chart-bar me-1"></i>
                  Estadísticas del Período
                </h6>
                <div class="row text-center">
                  <div class="col-md-3">
                    <div class="stat-valor" id="stat_total_referentes">0</div>
                    <div class="stat-etiqueta">Referentes</div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-valor" id="stat_total_conversiones">0</div>
                    <div class="stat-etiqueta">Conversiones</div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-valor" id="stat_tasa_promedio">0%</div>
                    <div class="stat-etiqueta">Tasa Promedio</div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-valor" id="stat_mejor_mes">-</div>
                    <div class="stat-etiqueta">Mejor del Mes</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>
          Cerrar
        </button>
        <button type="button" class="btn btn-primary" onclick="compartirLeaderboard()">
          <i class="ti ti-share me-1"></i>
          Compartir Ranking
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Cargar leaderboard cuando se abre el modal
    $('#modalLeaderboard').on('shown.bs.modal', function () {
        cargarLeaderboard();
    });

    // Función para cargar el leaderboard
    window.cargarLeaderboard = function() {
        const periodo = document.getElementById('filtro_periodo').value;
        const categoria = document.getElementById('filtro_categoria').value;
        const metrica = document.getElementById('filtro_metrica').value;

        // Mostrar loading
        document.getElementById('leaderboard-lista').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Actualizando ranking...</p>
            </div>
        `;

        // Realizar petición AJAX
        fetch('acciones/rankings_recomendacion/obtener_leaderboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `periodo=${periodo}&categoria=${categoria}&metrica=${metrica}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarLeaderboard(data.leaderboard);
                actualizarEstadisticasPeriodo(data.estadisticas);
                actualizarPeriodoBadge(periodo);
            } else {
                mostrarLeaderboardVacio(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo cargar el leaderboard. Intente nuevamente.',
                confirmButtonColor: '#667eea'
            });
        });
    };

    // Función para renderizar el leaderboard
    function renderizarLeaderboard(datos) {
        if (!datos || datos.length === 0) {
            mostrarLeaderboardVacio();
            return;
        }

        let html = '';
        
        datos.forEach((item, index) => {
            const posicion = index + 1;
            let posicionClass = 'pos-otros';
            
            if (posicion === 1) posicionClass = 'pos-1';
            else if (posicion === 2) posicionClass = 'pos-2';
            else if (posicion === 3) posicionClass = 'pos-3';

            const categoriaClass = 'categoria-' + item.categoria.toLowerCase().replace(' ', '-');

            html += `
                <div class="leaderboard-item" data-id="${item.apoderado_id}">
                    <div class="leaderboard-posicion ${posicionClass}">
                        #${posicion}
                    </div>
                    <div class="leaderboard-info">
                        <div class="leaderboard-nombre">${item.nombre_completo}</div>
                        <div class="leaderboard-familia">Familia: ${item.familia}</div>
                        <span class="leaderboard-categoria badge ${categoriaClass}">${item.categoria}</span>
                    </div>
                    <div class="leaderboard-stats">
                        <div class="leaderboard-stat">
                            <span class="stat-valor">${item.conversiones}</span>
                            <span class="stat-etiqueta">Conversiones</span>
                        </div>
                        <div class="leaderboard-stat">
                            <span class="stat-valor">${item.tasa_conversion}%</span>
                            <span class="stat-etiqueta">Tasa Conv.</span>
                        </div>
                        <div class="leaderboard-stat">
                            <span class="stat-valor">${item.total_usos}</span>
                            <span class="stat-etiqueta">Usos</span>
                        </div>
                        <div class="leaderboard-stat">
                            <span class="stat-valor">${item.codigos_activos}</span>
                            <span class="stat-etiqueta">Códigos</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" 
                            onclick="verDetallesDesdeLeaderboard(${item.apoderado_id}, '${item.nombre_completo}')">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            `;
        });

        document.getElementById('leaderboard-lista').innerHTML = html;
    }

    // Función para mostrar leaderboard vacío
    function mostrarLeaderboardVacio(mensaje = 'No se encontraron referentes con los filtros seleccionados') {
        document.getElementById('leaderboard-lista').innerHTML = `
            <div class="empty-leaderboard">
                <i class="ti ti-trophy-off"></i>
                <h5>Sin Resultados</h5>
                <p class="text-muted">${mensaje}</p>
                <button class="btn btn-sm btn-outline-primary" onclick="limpiarFiltrosLeaderboard()">
                    <i class="ti ti-reload me-1"></i>
                    Limpiar Filtros
                </button>
            </div>
        `;
        document.getElementById('stats-periodo').style.display = 'none';
    }

    // Función para actualizar estadísticas del período
    function actualizarEstadisticasPeriodo(stats) {
        if (!stats) return;

        document.getElementById('stat_total_referentes').textContent = stats.total_referentes || 0;
        document.getElementById('stat_total_conversiones').textContent = stats.total_conversiones || 0;
        document.getElementById('stat_tasa_promedio').textContent = (stats.tasa_promedio || 0) + '%';
        document.getElementById('stat_mejor_mes').textContent = stats.mejor_mes || '-';
        
        document.getElementById('stats-periodo').style.display = 'block';
    }

    // Función para actualizar el badge de período
    function actualizarPeriodoBadge(periodo) {
        const periodos = {
            'todo': 'Todo el Tiempo',
            'mes_actual': 'Mes Actual - Septiembre 2025',
            'mes_anterior': 'Mes Anterior - Agosto 2025',
            'trimestre': 'Último Trimestre',
            'semestre': 'Último Semestre',
            'año': 'Este Año - 2025'
        };

        document.getElementById('periodo_actual').textContent = periodos[periodo] || 'Período Seleccionado';
    }

    // Función para filtrar leaderboard
    window.filtrarLeaderboard = function() {
        Swal.fire({
            title: 'Aplicando filtros...',
            text: 'Actualizando el ranking',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            cargarLeaderboard();
            Swal.close();
        }, 800);
    };

    // Función para limpiar filtros
    window.limpiarFiltrosLeaderboard = function() {
        document.getElementById('filtro_periodo').value = 'mes_actual';
        document.getElementById('filtro_categoria').value = 'todas';
        document.getElementById('filtro_metrica').value = 'conversiones';
        
        Swal.fire({
            icon: 'success',
            title: 'Filtros Limpiados',
            text: 'Se han restaurado los filtros por defecto',
            timer: 1500,
            showConfirmButton: false
        });

        filtrarLeaderboard();
    };

    // Función para exportar leaderboard
    window.exportarLeaderboard = function() {
        const periodo = document.getElementById('filtro_periodo').value;
        
        Swal.fire({
            title: '¿Exportar Leaderboard?',
            text: 'Se descargará un archivo Excel con el ranking actual',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-download me-1"></i> Exportar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a exportación
                window.location.href = `acciones/rankings_recomendacion/exportar_leaderboard.php?periodo=${periodo}`;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Exportando...',
                    text: 'La descarga comenzará en breve',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    };

    // Función para compartir leaderboard
    window.compartirLeaderboard = function() {
        Swal.fire({
            title: 'Compartir Leaderboard',
            html: `
                <div class="text-start">
                    <p>Seleccione cómo desea compartir el ranking:</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="compartirPorEmail()">
                            <i class="ti ti-mail me-2"></i>Enviar por Email
                        </button>
                        <button class="btn btn-outline-success" onclick="compartirPorWhatsApp()">
                            <i class="ti ti-brand-whatsapp me-2"></i>Compartir en WhatsApp
                        </button>
                        <button class="btn btn-outline-info" onclick="copiarEnlaceLeaderboard()">
                            <i class="ti ti-link me-2"></i>Copiar Enlace
                        </button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: '400px'
        });
    };

    // Función para ver detalles desde leaderboard
    window.verDetallesDesdeLeaderboard = function(id, nombre) {
        $('#modalLeaderboard').modal('hide');
        
        setTimeout(() => {
            // Cargar y mostrar modal de detalles
            cargarDetallesReferenteCompleto(id, nombre);
            $('#modalDetallesReferente').modal('show');
        }, 500);
    };

    // Funciones auxiliares de compartir
    window.compartirPorEmail = function() {
        Swal.fire({
            icon: 'info',
            title: 'Función en Desarrollo',
            text: 'La función de compartir por email estará disponible pronto'
        });
    };

    window.compartirPorWhatsApp = function() {
        Swal.fire({
            icon: 'info',
            title: 'Función en Desarrollo',
            text: 'La función de compartir por WhatsApp estará disponible pronto'
        });
    };

    window.copiarEnlaceLeaderboard = function() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Enlace Copiado',
                text: 'El enlace se ha copiado al portapapeles',
                timer: 2000,
                showConfirmButton: false
            });
        });
    };
});
</script>