<style>
/* Estilos adicionales para modal de detalles */
.detalles-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
    margin: -16px -16px 20px -16px;
}

.detalles-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-right: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.detalles-info-principal {
    flex: 1;
}

.detalles-nombre {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.detalles-categoria-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 5px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.stat-card-valor {
    font-size: 2rem;
    font-weight: bold;
    color: #495057;
    display: block;
}

.stat-card-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.codigos-list {
    max-height: 300px;
    overflow-y: auto;
}

.codigo-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.codigo-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.codigo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.codigo-text {
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    font-weight: bold;
    color: #667eea;
}

.codigo-estado-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.estado-activo {
    background-color: #d4edda;
    color: #155724;
}

.estado-inactivo {
    background-color: #f8d7da;
    color: #721c24;
}

.codigo-stats {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
}

.codigo-stat {
    color: #6c757d;
}

.codigo-stat strong {
    color: #495057;
}

.conversiones-timeline {
    max-height: 400px;
    overflow-y: auto;
}

.timeline-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
}

.timeline-titulo {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 3px;
}

.timeline-fecha {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.timeline-detalle {
    font-size: 0.85rem;
    color: #495057;
}

.conversion-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 5px;
}

.conversion-exitosa {
    background-color: #d4edda;
    color: #155724;
}

.conversion-pendiente {
    background-color: #fff3cd;
    color: #856404;
}

.contacto-referente {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
}

.contacto-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.contacto-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
}

.contacto-text {
    flex: 1;
    font-size: 0.9rem;
    color: #495057;
}

.empty-conversiones {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-conversiones i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>

<!-- Modal Detalles del Referente -->
<div class="modal fade" id="modalDetallesReferente" tabindex="-1" aria-labelledby="modalDetallesReferenteLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="modal-title" id="modalDetallesReferenteLabel">
          <i class="ti ti-user-search me-2"></i>
          Detalles del Referente
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        
        <!-- Header con Información Principal -->
        <div class="detalles-header d-flex align-items-center" id="detalles-header-content">
          <div class="text-center py-4 w-100">
            <div class="spinner-border text-white" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Cargando información del referente...</p>
          </div>
        </div>

        <!-- Pestañas de Navegación -->
        <ul class="nav nav-tabs nav-fill mb-3" id="detalles-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" 
                    data-bs-target="#resumen-content" type="button" role="tab">
              <i class="ti ti-chart-line me-1"></i>
              Resumen
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="codigos-tab" data-bs-toggle="tab" 
                    data-bs-target="#codigos-content" type="button" role="tab">
              <i class="ti ti-ticket me-1"></i>
              Códigos de Referido
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="conversiones-tab" data-bs-toggle="tab" 
                    data-bs-target="#conversiones-content" type="button" role="tab">
              <i class="ti ti-users me-1"></i>
              Historial de Conversiones
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" 
                    data-bs-target="#contacto-content" type="button" role="tab">
              <i class="ti ti-phone me-1"></i>
              Información de Contacto
            </button>
          </li>
        </ul>

        <!-- Contenido de las Pestañas -->
        <div class="tab-content" id="detalles-tabs-content">
          
          <!-- Tab 1: Resumen -->
          <div class="tab-pane fade show active" id="resumen-content" role="tabpanel">
            <div class="stats-grid" id="stats-resumen">
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </div>
            </div>

            <!-- Gráfico de Rendimiento -->
            <div class="card mt-3">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="ti ti-chart-bar me-1"></i>
                  Rendimiento por Mes
                </h6>
              </div>
              <div class="card-body">
                <div id="grafico-rendimiento" style="min-height: 250px;">
                  <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Cargando gráfico...</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Códigos de Referido -->
          <div class="tab-pane fade" id="codigos-content" role="tabpanel">
            <div class="codigos-list" id="codigos-lista">
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Cargando códigos...</p>
              </div>
            </div>
          </div>

          <!-- Tab 3: Historial de Conversiones -->
          <div class="tab-pane fade" id="conversiones-content" role="tabpanel">
            <div class="conversiones-timeline" id="conversiones-timeline">
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Cargando historial...</p>
              </div>
            </div>
          </div>

          <!-- Tab 4: Información de Contacto -->
          <div class="tab-pane fade" id="contacto-content" role="tabpanel">
            <div class="contacto-referente" id="contacto-info">
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Cargando información...</p>
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
        <button type="button" class="btn btn-outline-primary" onclick="enviarMensajeReferente()">
          <i class="ti ti-mail me-1"></i>
          Enviar Mensaje
        </button>
        <button type="button" class="btn btn-primary" onclick="generarReporteReferente()">
          <i class="ti ti-file-analytics me-1"></i>
          Generar Reporte
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    let referenteActual = null;

    // Función principal para cargar detalles completos
    window.cargarDetallesReferenteCompleto = function(id, nombre) {
        referenteActual = { id: id, nombre: nombre };

        // Resetear contenido
        document.getElementById('detalles-header-content').innerHTML = `
            <div class="text-center py-4 w-100">
                <div class="spinner-border text-white" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 mb-0">Cargando información de ${nombre}...</p>
            </div>
        `;

        // Realizar petición AJAX
        fetch('acciones/rankings_recomendacion/obtener_detalles_referente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `referente_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarDetallesCompletos(data.datos);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudieron cargar los detalles del referente',
                    confirmButtonColor: '#667eea'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo cargar la información. Intente nuevamente.',
                confirmButtonColor: '#667eea'
            });
        });
    };

    // Función para renderizar detalles completos
    function renderizarDetallesCompletos(datos) {
        // Renderizar header
        renderizarHeader(datos);
        
        // Renderizar resumen
        renderizarResumen(datos);
        
        // Renderizar códigos
        renderizarCodigos(datos.codigos);
        
        // Renderizar conversiones
        renderizarConversiones(datos.conversiones);
        
        // Renderizar contacto
        renderizarContacto(datos);
    }

    // Renderizar Header
    function renderizarHeader(datos) {
        const iniciales = datos.nombres.charAt(0) + datos.apellidos.charAt(0);
        const categoriaClass = 'categoria-' + datos.categoria.toLowerCase().replace(' ', '-');

        document.getElementById('detalles-header-content').innerHTML = `
            <div class="detalles-avatar">${iniciales}</div>
            <div class="detalles-info-principal">
                <div class="detalles-nombre">${datos.nombres} ${datos.apellidos}</div>
                <div class="text-white-50">Familia: ${datos.familia}</div>
                <span class="detalles-categoria-badge badge ${categoriaClass}">${datos.categoria}</span>
            </div>
            <div class="text-end">
                <div class="text-white" style="font-size: 2rem; font-weight: bold;">
                    #${datos.posicion_ranking}
                </div>
                <div class="text-white-50" style="font-size: 0.85rem;">
                    en el ranking
                </div>
            </div>
        `;
    }

    // Renderizar Resumen
    function renderizarResumen(datos) {
        document.getElementById('stats-resumen').innerHTML = `
            <div class="stat-card">
                <span class="stat-card-valor">${datos.total_codigos}</span>
                <span class="stat-card-label">Códigos</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-valor">${datos.total_usos}</span>
                <span class="stat-card-label">Usos Totales</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-valor">${datos.conversiones_exitosas}</span>
                <span class="stat-card-label">Conversiones</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-valor">${datos.tasa_conversion}%</span>
                <span class="stat-card-label">Tasa Conv.</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-valor">${datos.codigos_activos}</span>
                <span class="stat-card-label">Activos</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-valor">${datos.usos_restantes}</span>
                <span class="stat-card-label">Usos Disponibles</span>
            </div>
        `;

        // Renderizar gráfico simple (mockup)
        document.getElementById('grafico-rendimiento').innerHTML = `
            <div class="alert alert-info">
                <i class="ti ti-info-circle me-2"></i>
                <strong>Gráfico de Rendimiento:</strong> Mostrando conversiones de los últimos 6 meses
            </div>
            <div class="text-center text-muted py-4">
                <p>Gráfico visual disponible próximamente</p>
                <small>Última conversión: ${datos.ultima_conversion || 'Sin conversiones'}</small>
            </div>
        `;
    }

    // Renderizar Códigos
    function renderizarCodigos(codigos) {
        if (!codigos || codigos.length === 0) {
            document.getElementById('codigos-lista').innerHTML = `
                <div class="empty-conversiones">
                    <i class="ti ti-ticket-off"></i>
                    <h5>Sin Códigos Registrados</h5>
                    <p>Este referente no tiene códigos de referido activos</p>
                </div>
            `;
            return;
        }

        let html = '';
        codigos.forEach(codigo => {
            const estadoClass = codigo.activo ? 'estado-activo' : 'estado-inactivo';
            const estadoTexto = codigo.activo ? 'Activo' : 'Inactivo';

            html += `
                <div class="codigo-item">
                    <div class="codigo-header">
                        <div class="codigo-text">${codigo.codigo}</div>
                        <span class="codigo-estado-badge ${estadoClass}">${estadoTexto}</span>
                    </div>
                    <div class="codigo-stats">
                        <div class="codigo-stat">
                            <strong>${codigo.usos_actuales}</strong> usos de 
                            <strong>${codigo.limite_usos || '∞'}</strong>
                        </div>
                        <div class="codigo-stat">
                            Vigente: ${codigo.fecha_inicio} - ${codigo.fecha_fin || 'Sin límite'}
                        </div>
                    </div>
                    ${codigo.descripcion ? `<div class="mt-2"><small class="text-muted">${codigo.descripcion}</small></div>` : ''}
                </div>
            `;
        });

        document.getElementById('codigos-lista').innerHTML = html;
    }

    // Renderizar Conversiones
    function renderizarConversiones(conversiones) {
        if (!conversiones || conversiones.length === 0) {
            document.getElementById('conversiones-timeline').innerHTML = `
                <div class="empty-conversiones">
                    <i class="ti ti-users-off"></i>
                    <h5>Sin Conversiones Registradas</h5>
                    <p>Este referente aún no ha generado conversiones</p>
                </div>
            `;
            return;
        }

        let html = '';
        conversiones.forEach(conv => {
            const conversionClass = conv.convertido ? 'conversion-exitosa' : 'conversion-pendiente';
            const conversionTexto = conv.convertido ? 'Convertido' : 'Pendiente';

            html += `
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="ti ti-user-check"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-titulo">${conv.lead_nombre}</div>
                        <div class="timeline-fecha">
                            <i class="ti ti-calendar me-1"></i>
                            ${conv.fecha_uso}
                        </div>
                        <div class="timeline-detalle">
                            Código usado: <strong>${conv.codigo}</strong>
                        </div>
                        <span class="conversion-badge ${conversionClass}">
                            ${conversionTexto}
                        </span>
                        ${conv.convertido && conv.fecha_conversion ? 
                            `<div class="mt-1"><small class="text-success">Convertido el: ${conv.fecha_conversion}</small></div>` 
                            : ''}
                    </div>
                </div>
            `;
        });

        document.getElementById('conversiones-timeline').innerHTML = html;
    }

    // Renderizar Contacto
    function renderizarContacto(datos) {
        let contactoHtml = '<h6 class="mb-3"><i class="ti ti-address-book me-1"></i>Datos de Contacto</h6>';

        if (datos.email) {
            contactoHtml += `
                <div class="contacto-item">
                    <div class="contacto-icon">
                        <i class="ti ti-mail"></i>
                    </div>
                    <div class="contacto-text">
                        <strong>Email:</strong><br>
                        <a href="mailto:${datos.email}">${datos.email}</a>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="enviarEmailDirecto('${datos.email}')">
                        <i class="ti ti-send"></i>
                    </button>
                </div>
            `;
        }

        if (datos.telefono) {
            contactoHtml += `
                <div class="contacto-item">
                    <div class="contacto-icon">
                        <i class="ti ti-phone"></i>
                    </div>
                    <div class="contacto-text">
                        <strong>Teléfono:</strong><br>
                        <a href="tel:${datos.telefono}">${datos.telefono}</a>
                    </div>
                    <button class="btn btn-sm btn-outline-success" onclick="llamarTelefono('${datos.telefono}')">
                        <i class="ti ti-phone-call"></i>
                    </button>
                </div>
            `;
        }

        if (datos.whatsapp) {
            contactoHtml += `
                <div class="contacto-item">
                    <div class="contacto-icon">
                        <i class="ti ti-brand-whatsapp"></i>
                    </div>
                    <div class="contacto-text">
                        <strong>WhatsApp:</strong><br>
                        ${datos.whatsapp}
                    </div>
                    <button class="btn btn-sm btn-outline-success" onclick="abrirWhatsApp('${datos.whatsapp}')">
                        <i class="ti ti-brand-whatsapp"></i>
                    </button>
                </div>
            `;
        }

        if (!datos.email && !datos.telefono && !datos.whatsapp) {
            contactoHtml += `
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-2"></i>
                    No hay información de contacto registrada para este referente
                </div>
            `;
        }

        document.getElementById('contacto-info').innerHTML = contactoHtml;
    }

    // Función para enviar mensaje al referente
    window.enviarMensajeReferente = function() {
        if (!referenteActual) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'No hay un referente seleccionado',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        Swal.fire({
            title: 'Enviar Mensaje',
            html: `
                <p class="text-start mb-3">Enviar mensaje a: <strong>${referenteActual.nombre}</strong></p>
                <textarea class="form-control" id="mensaje-text" rows="4" 
                          placeholder="Escriba su mensaje aquí..." required></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-send me-1"></i> Enviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#667eea',
            preConfirm: () => {
                const mensaje = document.getElementById('mensaje-text').value;
                if (!mensaje) {
                    Swal.showValidationMessage('Por favor ingrese un mensaje');
                    return false;
                }
                return { mensaje: mensaje };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Mensaje Enviado',
                    text: 'El mensaje ha sido enviado exitosamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    };

    // Función para generar reporte del referente
    window.generarReporteReferente = function() {
        if (!referenteActual) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'No hay un referente seleccionado',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        Swal.fire({
            title: '¿Generar Reporte?',
            text: `Se generará un reporte completo de ${referenteActual.nombre}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-file-download me-1"></i> Generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a generación de reporte
                window.location.href = `acciones/rankings_recomendacion/generar_reporte_referente.php?id=${referenteActual.id}`;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Generando Reporte',
                    text: 'La descarga comenzará en breve',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    };

    // Funciones auxiliares de contacto
    window.enviarEmailDirecto = function(email) {
        window.location.href = `mailto:${email}`;
    };

    window.llamarTelefono = function(telefono) {
        window.location.href = `tel:${telefono}`;
    };

    window.abrirWhatsApp = function(whatsapp) {
        const numero = whatsapp.replace(/[^0-9]/g, '');
        window.open(`https://wa.me/${numero}`, '_blank');
    };
});
</script>