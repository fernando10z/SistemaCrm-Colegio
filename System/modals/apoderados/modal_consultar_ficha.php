<style>
    .ficha-section {
        background: #ffffff;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        border: 1.5px solid #e9ecef;
    }
    
    .ficha-section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid;
    }
    
    .ficha-section-header i {
        font-size: 1.75rem;
        margin-right: 0.75rem;
    }
    
    .ficha-section-header h6 {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .ficha-datos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .ficha-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .ficha-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .ficha-value {
        font-size: 0.95rem;
        color: #2c3e50;
        font-weight: 500;
    }
    
    .ficha-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* Colores para badges */
    .badge-titular { background: #d4edda; color: #155724; }
    .badge-suplente { background: #fff3cd; color: #856404; }
    .badge-economico { background: #e7d4f5; color: #6f42c1; }
    
    .badge-nse-A { background: #cfe2ff; color: #084298; }
    .badge-nse-B { background: #d1e7dd; color: #0a3622; }
    .badge-nse-C { background: #fff3cd; color: #664d03; }
    .badge-nse-D { background: #ffe5d0; color: #984c0c; }
    .badge-nse-E { background: #f8d7da; color: #842029; }
    
    .badge-compromiso-alto { background: #d4edda; color: #155724; }
    .badge-compromiso-medio { background: #fff3cd; color: #856404; }
    .badge-compromiso-bajo { background: #f8d7da; color: #842029; }
    
    .badge-participacion-muy_activo { background: #d4edda; color: #155724; }
    .badge-participacion-activo { background: #cfe2ff; color: #084298; }
    .badge-participacion-poco_activo { background: #fff3cd; color: #856404; }
    .badge-participacion-inactivo { background: #f8d7da; color: #842029; }
    
    .badge-calificacion-excelente { background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%); color: white; }
    .badge-calificacion-buena { background: linear-gradient(135deg, #64b5f6 0%, #42a5f5 100%); color: white; }
    .badge-calificacion-regular { background: linear-gradient(135deg, #ffb74d 0%, #ffa726 100%); color: white; }
    
    .estudiantes-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .estudiante-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        border-left: 4px solid #81c784;
    }
    
    .estudiante-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }
    
    .estudiante-detalle {
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .apoderado-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        border-left: 4px solid #64b5f6;
    }
    
    .apoderado-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .apoderado-detalle {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .empty-message {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        font-style: italic;
    }
    
    /* Secciones con colores pastel */
    .section-personal .ficha-section-header {
        border-color: #fff3e0;
    }
    .section-personal .ficha-section-header i {
        color: #ffb74d;
    }
    
    .section-contacto .ficha-section-header {
        border-color: #e1f5fe;
    }
    .section-contacto .ficha-section-header i {
        color: #4fc3f7;
    }
    
    .section-familia .ficha-section-header {
        border-color: #e8f5e9;
    }
    .section-familia .ficha-section-header i {
        color: #81c784;
    }
    
    .section-profesional .ficha-section-header {
        border-color: #f3e5f5;
    }
    .section-profesional .ficha-section-header i {
        color: #ba68c8;
    }
    
    .section-participacion .ficha-section-header {
        border-color: #fff9c4;
    }
    .section-participacion .ficha-section-header i {
        color: #ffd54f;
    }
    
    .section-estudiantes .ficha-section-header {
        border-color: #e0f2f1;
    }
    .section-estudiantes .ficha-section-header i {
        color: #4db6ac;
    }
</style>

<!-- Modal Consultar Ficha Completa -->
<div class="modal fade" id="modalConsultarFicha" tabindex="-1" aria-labelledby="modalConsultarFichaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="background: #fafafa; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8d5e2 0%, #c9e4ca 100%); border-bottom: none; border-radius: 12px 12px 0 0; padding: 1.5rem;">
        <div>
          <h5 class="modal-title" id="modalConsultarFichaLabel" style="color: #2c3e50; font-weight: 600; margin: 0;">
            <i class="ti ti-id-badge me-2"></i>Ficha Completa del Apoderado
          </h5>
          <small id="ficha_nombre_apoderado" style="color: #546e7a; display: block; margin-top: 0.5rem; font-weight: 500;"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="padding: 1.5rem; max-height: 75vh; overflow-y: auto;">
        
        <!-- Sección: Datos Personales -->
        <div class="ficha-section section-personal">
          <div class="ficha-section-header">
            <i class="ti ti-user"></i>
            <h6>Datos Personales</h6>
          </div>
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Nombres Completos</span>
              <span class="ficha-value" id="ficha_nombre_completo">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Tipo de Documento</span>
              <span class="ficha-value" id="ficha_tipo_documento">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Número de Documento</span>
              <span class="ficha-value" id="ficha_numero_documento">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Fecha de Nacimiento</span>
              <span class="ficha-value" id="ficha_fecha_nacimiento">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Edad</span>
              <span class="ficha-value" id="ficha_edad">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Género</span>
              <span class="ficha-value" id="ficha_genero">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Estado Civil</span>
              <span class="ficha-value" id="ficha_estado_civil">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Tipo de Apoderado</span>
              <span class="ficha-badge" id="ficha_tipo_apoderado">-</span>
            </div>
          </div>
        </div>

        <!-- Sección: Información de Contacto -->
        <div class="ficha-section section-contacto">
          <div class="ficha-section-header">
            <i class="ti ti-phone"></i>
            <h6>Información de Contacto</h6>
          </div>
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Teléfono Principal</span>
              <span class="ficha-value" id="ficha_telefono_principal">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Teléfono Secundario</span>
              <span class="ficha-value" id="ficha_telefono_secundario">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">WhatsApp</span>
              <span class="ficha-value" id="ficha_whatsapp">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Email</span>
              <span class="ficha-value" id="ficha_email">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Preferencia de Contacto</span>
              <span class="ficha-badge" id="ficha_preferencia_contacto">-</span>
            </div>
          </div>
        </div>

        <!-- Sección: Información Familiar -->
        <div class="ficha-section section-familia">
          <div class="ficha-section-header">
            <i class="ti ti-home"></i>
            <h6>Información Familiar</h6>
          </div>
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Código de Familia</span>
              <span class="ficha-value" id="ficha_codigo_familia">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Apellido Familiar</span>
              <span class="ficha-value" id="ficha_familia_apellido">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Nivel Socioeconómico</span>
              <span class="ficha-badge" id="ficha_nivel_socioeconomico">-</span>
            </div>
            <div class="ficha-item" style="grid-column: 1 / -1;">
              <span class="ficha-label">Dirección Completa</span>
              <span class="ficha-value" id="ficha_direccion_completa">-</span>
            </div>
            <div class="ficha-item" style="grid-column: 1 / -1;" id="ficha_observaciones_container">
              <span class="ficha-label">Observaciones de la Familia</span>
              <span class="ficha-value" id="ficha_familia_observaciones">-</span>
            </div>
          </div>
        </div>

        <!-- Sección: Información Profesional -->
        <div class="ficha-section section-profesional">
          <div class="ficha-section-header">
            <i class="ti ti-briefcase"></i>
            <h6>Información Profesional</h6>
          </div>
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Ocupación</span>
              <span class="ficha-value" id="ficha_ocupacion">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Empresa</span>
              <span class="ficha-value" id="ficha_empresa">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Nivel Educativo</span>
              <span class="ficha-value" id="ficha_nivel_educativo">-</span>
            </div>
          </div>
        </div>

        <!-- Sección: Nivel de Participación -->
        <div class="ficha-section section-participacion">
          <div class="ficha-section-header">
            <i class="ti ti-star"></i>
            <h6>Nivel de Participación y Compromiso</h6>
          </div>
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Nivel de Compromiso</span>
              <span class="ficha-badge" id="ficha_nivel_compromiso">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Nivel de Participación</span>
              <span class="ficha-badge" id="ficha_nivel_participacion">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Calificación General</span>
              <span class="ficha-badge" id="ficha_calificacion_participacion">-</span>
            </div>
          </div>
        </div>

        <!-- Sección: Estudiantes Vinculados -->
        <div class="ficha-section section-estudiantes">
          <div class="ficha-section-header">
            <i class="ti ti-users"></i>
            <h6>Estudiantes Vinculados (<span id="ficha_total_estudiantes">0</span>)</h6>
          </div>
          <div class="estudiantes-list" id="ficha_lista_estudiantes">
            <div class="empty-message">No hay estudiantes vinculados</div>
          </div>
        </div>

        <!-- Sección: Otros Apoderados de la Familia -->
        <div class="ficha-section section-estudiantes" id="ficha_otros_apoderados_section">
          <div class="ficha-section-header">
            <i class="ti ti-user-check"></i>
            <h6>Otros Apoderados de la Familia (<span id="ficha_total_otros">0</span>)</h6>
          </div>
          <div class="estudiantes-list" id="ficha_lista_otros_apoderados">
            <div class="empty-message">No hay otros apoderados registrados</div>
          </div>
        </div>

        <!-- Sección: Información del Registro -->
        <div class="ficha-section" style="background: #f8f9fa; border: 1px dashed #dee2e6;">
          <div class="ficha-datos">
            <div class="ficha-item">
              <span class="ficha-label">Fecha de Registro</span>
              <span class="ficha-value" id="ficha_fecha_registro">-</span>
            </div>
            <div class="ficha-item">
              <span class="ficha-label">Última Actualización</span>
              <span class="ficha-value" id="ficha_fecha_actualizacion">-</span>
            </div>
          </div>
        </div>

      </div>
      
      <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 1rem 1.5rem; border-radius: 0 0 12px 12px;">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="btnEditarDesdeFicha">
          <i class="ti ti-edit me-1"></i>Editar Datos
        </button>
        <button type="button" class="btn btn-success btn-sm" id="btnVincularDesdeFicha">
          <i class="ti ti-link me-1"></i>Vincular Estudiantes
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Función para mostrar la ficha completa
function mostrarFichaCompleta(data) {
    const apoderado = data.apoderado;
    const estudiantes = data.estudiantes;
    const otros_apoderados = data.otros_apoderados;
    
    // Datos Personales
    $('#ficha_nombre_apoderado').text(apoderado.nombre_completo);
    $('#ficha_nombre_completo').text(apoderado.nombre_completo || '-');
    $('#ficha_tipo_documento').text(apoderado.tipo_documento || '-');
    $('#ficha_numero_documento').text(apoderado.numero_documento || '-');
    $('#ficha_fecha_nacimiento').text(apoderado.fecha_nacimiento_formato || '-');
    $('#ficha_edad').text(apoderado.edad ? apoderado.edad + ' años' : '-');
    $('#ficha_genero').text(apoderado.genero_texto || '-');
    $('#ficha_estado_civil').text(apoderado.estado_civil_texto || '-');
    
    // Tipo de Apoderado con badge
    const tipoClasses = {
        'titular': 'badge-titular',
        'suplente': 'badge-suplente',
        'economico': 'badge-economico'
    };
    $('#ficha_tipo_apoderado')
        .text(apoderado.tipo_apoderado_texto || '-')
        .removeClass('badge-titular badge-suplente badge-economico')
        .addClass(tipoClasses[apoderado.tipo_apoderado] || '');
    
    // Información de Contacto
    $('#ficha_telefono_principal').text(apoderado.telefono_principal || 'No registrado');
    $('#ficha_telefono_secundario').text(apoderado.telefono_secundario || 'No registrado');
    $('#ficha_whatsapp').text(apoderado.whatsapp || 'No registrado');
    $('#ficha_email').text(apoderado.email || 'No registrado');
    $('#ficha_preferencia_contacto').text(apoderado.preferencia_contacto_texto || '-');
    
    // Información Familiar
    $('#ficha_codigo_familia').text(apoderado.codigo_familia || '-');
    $('#ficha_familia_apellido').text(apoderado.familia_apellido || '-');
    $('#ficha_direccion_completa').text(apoderado.direccion_completa || 'No registrada');
    
    // Nivel Socioeconómico con badge
    if (apoderado.nivel_socioeconomico) {
        const nseClasses = {
            'A': 'badge-nse-A',
            'B': 'badge-nse-B',
            'C': 'badge-nse-C',
            'D': 'badge-nse-D',
            'E': 'badge-nse-E'
        };
        $('#ficha_nivel_socioeconomico')
            .text('NSE ' + apoderado.nivel_socioeconomico)
            .removeClass('badge-nse-A badge-nse-B badge-nse-C badge-nse-D badge-nse-E')
            .addClass(nseClasses[apoderado.nivel_socioeconomico] || '');
    } else {
        $('#ficha_nivel_socioeconomico').text('No especificado');
    }
    
    // Observaciones de la familia
    if (apoderado.familia_observaciones && apoderado.familia_observaciones.trim() !== '') {
        $('#ficha_familia_observaciones').text(apoderado.familia_observaciones);
        $('#ficha_observaciones_container').show();
    } else {
        $('#ficha_observaciones_container').hide();
    }
    
    // Información Profesional
    $('#ficha_ocupacion').text(apoderado.ocupacion || 'No especificada');
    $('#ficha_empresa').text(apoderado.empresa || 'No especificada');
    $('#ficha_nivel_educativo').text(apoderado.nivel_educativo || 'No especificado');
    
    // Nivel de Participación
    const compromisoClasses = {
        'alto': 'badge-compromiso-alto',
        'medio': 'badge-compromiso-medio',
        'bajo': 'badge-compromiso-bajo'
    };
    $('#ficha_nivel_compromiso')
        .text(apoderado.nivel_compromiso_texto || 'Medio')
        .removeClass('badge-compromiso-alto badge-compromiso-medio badge-compromiso-bajo')
        .addClass(compromisoClasses[apoderado.nivel_compromiso] || 'badge-compromiso-medio');
    
    const participacionClasses = {
        'muy_activo': 'badge-participacion-muy_activo',
        'activo': 'badge-participacion-activo',
        'poco_activo': 'badge-participacion-poco_activo',
        'inactivo': 'badge-participacion-inactivo'
    };
    $('#ficha_nivel_participacion')
        .text(apoderado.nivel_participacion_texto || 'Activo')
        .removeClass('badge-participacion-muy_activo badge-participacion-activo badge-participacion-poco_activo badge-participacion-inactivo')
        .addClass(participacionClasses[apoderado.nivel_participacion] || 'badge-participacion-activo');
    
    const calificacionClasses = {
        'excelente': 'badge-calificacion-excelente',
        'buena': 'badge-calificacion-buena',
        'regular': 'badge-calificacion-regular'
    };
    $('#ficha_calificacion_participacion')
        .text(apoderado.calificacion_participacion ? apoderado.calificacion_participacion.charAt(0).toUpperCase() + apoderado.calificacion_participacion.slice(1) : 'Regular')
        .removeClass('badge-calificacion-excelente badge-calificacion-buena badge-calificacion-regular')
        .addClass(calificacionClasses[apoderado.calificacion_participacion] || 'badge-calificacion-regular');
    
    // Estudiantes Vinculados
    $('#ficha_total_estudiantes').text(estudiantes.length);
    if (estudiantes.length > 0) {
        let estudiantesHtml = '';
        estudiantes.forEach(function(estudiante) {
            estudiantesHtml += `
                <div class="estudiante-card">
                    <div class="estudiante-nombre">${estudiante.nombre_completo}</div>
                    <div class="estudiante-detalle">
                        <strong>Código:</strong> ${estudiante.codigo_estudiante || 'N/A'} | 
                        <strong>Edad:</strong> ${estudiante.edad || 'N/A'} años | 
                        <strong>Sección:</strong> ${estudiante.seccion || 'N/A'} | 
                        <strong>Estado:</strong> ${estudiante.estado_matricula || 'N/A'}
                    </div>
                </div>
            `;
        });
        $('#ficha_lista_estudiantes').html(estudiantesHtml);
    } else {
        $('#ficha_lista_estudiantes').html('<div class="empty-message">No hay estudiantes vinculados a esta familia</div>');
    }
    
    // Otros Apoderados
    $('#ficha_total_otros').text(otros_apoderados.length);
    if (otros_apoderados.length > 0) {
        let otrosHtml = '';
        otros_apoderados.forEach(function(otro) {
            const tipoTexto = otro.tipo_apoderado === 'titular' ? 'Titular' : 
                            otro.tipo_apoderado === 'suplente' ? 'Suplente' : 'Económico';
            otrosHtml += `
                <div class="apoderado-card">
                    <div class="apoderado-nombre">${otro.nombre_completo} - <span class="badge badge-${otro.tipo_apoderado}" style="font-size: 0.75rem;">${tipoTexto}</span></div>
                    <div class="apoderado-detalle">
                        <strong>${otro.tipo_documento}:</strong> ${otro.numero_documento} | 
                        <strong>Tel:</strong> ${otro.telefono_principal || 'N/A'} | 
                        <strong>Email:</strong> ${otro.email || 'N/A'}
                    </div>
                </div>
            `;
        });
        $('#ficha_lista_otros_apoderados').html(otrosHtml);
        $('#ficha_otros_apoderados_section').show();
    } else {
        $('#ficha_otros_apoderados_section').hide();
    }
    
    // Información del Registro
    $('#ficha_fecha_registro').text(apoderado.fecha_registro_formato || '-');
    $('#ficha_fecha_actualizacion').text(apoderado.fecha_actualizacion_formato || '-');
    
    // Configurar botones de acción
    $('#btnEditarDesdeFicha').off('click').on('click', function() {
        $('#modalConsultarFicha').modal('hide');
        cargarDatosEdicion(apoderado.id);
    });
    
    $('#btnVincularDesdeFicha').off('click').on('click', function() {
        $('#modalConsultarFicha').modal('hide');
        $('#vincular_apoderado_id').val(apoderado.id);
        $('#vincular_apoderado_nombre').text(apoderado.nombre_completo);
        cargarEstudiantesDisponibles(apoderado.id);
        $('#modalVincularEstudiantes').modal('show');
    });
    
    // Mostrar el modal
    $('#modalConsultarFicha').modal('show');
}
</script>