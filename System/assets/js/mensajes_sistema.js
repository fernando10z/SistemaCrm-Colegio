// Sistema de mensajes y notificaciones para CRM Escolar - VERSIÓN ANIMADA
// Incluir este script al final de configuracion_sistema.php

$(document).ready(function() {
    // Función para mostrar mensajes toast con animaciones mejoradas
    function mostrarToast(tipo, titulo, mensaje) {
        var iconoClase = '';
        var colorClase = '';
        var bgGradient = '';
        var borderColor = '';
        
        switch(tipo) {
            case 'success':
                iconoClase = 'ti ti-check-circle';
                colorClase = 'text-success';
                bgGradient = 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)';
                borderColor = '#28a745';
                break;
            case 'error':
                iconoClase = 'ti ti-alert-circle';
                colorClase = 'text-danger';
                bgGradient = 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)';
                borderColor = '#dc3545';
                break;
            case 'warning':
                iconoClase = 'ti ti-alert-triangle';
                colorClase = 'text-warning';
                bgGradient = 'linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%)';
                borderColor = '#ffc107';
                break;
            case 'info':
                iconoClase = 'ti ti-info-circle';
                colorClase = 'text-info';
                bgGradient = 'linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%)';
                borderColor = '#17a2b8';
                break;
        }
        
        var uniqueId = 'toast-' + Date.now() + Math.random().toString(36).substr(2, 9);
        
        var toastHtml = `
            <div id="${uniqueId}" class="toast toast-animated align-items-center border-0 shadow-lg" 
                 role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000"
                 style="background: ${bgGradient}; border-left: 4px solid ${borderColor}; transform: translateX(100%); opacity: 0;">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center p-3">
                        <div class="toast-icon-container me-3">
                            <i class="${iconoClase} ${colorClase} toast-icon" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-title fw-bold mb-1" style="color: ${borderColor};">${titulo}</div>
                            <div class="toast-message text-muted small">${mensaje}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-animated me-3 m-auto" 
                            data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
                <div class="toast-progress-bar" style="background-color: ${borderColor};"></div>
            </div>
        `;
        
        // Crear contenedor de toasts si no existe
        if ($('#toast-container').length === 0) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
        }
        
        var $toast = $(toastHtml);
        $('#toast-container').append($toast);
        
        // Animación de entrada con efecto rebote
        setTimeout(function() {
            $toast.css({
                'transform': 'translateX(0)',
                'opacity': '1',
                'transition': 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
            });
            
            // Animación del icono
            $toast.find('.toast-icon').css({
                'animation': 'iconBounce 0.8s ease-out 0.3s both'
            });
            
            // Iniciar barra de progreso
            $toast.find('.toast-progress-bar').css({
                'animation': 'progressBar 6s linear forwards'
            });
        }, 100);
        
        var toast = new bootstrap.Toast($toast[0]);
        toast.show();
        
        // Animación de salida suave
        $toast.on('hide.bs.toast', function() {
            $(this).css({
                'transform': 'translateX(100%) scale(0.8)',
                'opacity': '0',
                'transition': 'all 0.4s ease-in-out'
            });
        });
        
        // Remover el toast del DOM después de que se oculte
        $toast.on('hidden.bs.toast', function() {
            setTimeout(() => {
                $(this).remove();
            }, 400);
        });
        
        // Efecto hover para pausar/reanudar
        $toast.hover(
            function() {
                // Pausar animaciones al hacer hover
                $(this).find('.toast-progress-bar').css('animation-play-state', 'paused');
                $(this).css({
                    'transform': 'translateX(-10px) scale(1.02)',
                    'transition': 'all 0.2s ease-out'
                });
            },
            function() {
                // Reanudar animaciones al quitar hover
                $(this).find('.toast-progress-bar').css('animation-play-state', 'running');
                $(this).css({
                    'transform': 'translateX(0) scale(1)',
                    'transition': 'all 0.2s ease-out'
                });
            }
        );
        
        // Efecto click para cerrar
        $toast.find('.btn-close-animated').on('click', function() {
            $(this).css({
                'transform': 'rotate(90deg) scale(0.8)',
                'transition': 'all 0.2s ease-out'
            });
        });
    }
    
    // Procesar mensajes de la URL
    function procesarMensajesURL() {
        var urlParams = new URLSearchParams(window.location.search);
        
        // Mensajes de éxito
        if (urlParams.has('success')) {
            var success = urlParams.get('success');
            var mensajes = {
                'creado': {
                    titulo: '¡Configuración Creada!',
                    mensaje: 'La nueva configuración ha sido agregada exitosamente.'
                },
                'actualizado': {
                    titulo: '¡Configuración Actualizada!',
                    mensaje: 'Los cambios han sido guardados correctamente.'
                },
                'eliminado': {
                    titulo: '¡Configuración Eliminada!',
                    mensaje: 'La configuración ha sido eliminada del sistema.'
                }
            };
            
            if (mensajes[success]) {
                mostrarToast('success', mensajes[success].titulo, mensajes[success].mensaje);
            }
        }
        
        // Mensajes de error
        if (urlParams.has('error')) {
            var error = urlParams.get('error');
            var tipo = urlParams.get('tipo') || '';
            
            var mensajes = {
                'campos_requeridos': {
                    titulo: 'Campos Requeridos',
                    mensaje: 'Por favor completa todos los campos obligatorios.'
                },
                'clave_existe': {
                    titulo: 'Clave Duplicada',
                    mensaje: 'Ya existe una configuración con esa clave. Usa una clave diferente.'
                },
                'valor_invalido': {
                    titulo: 'Valor Inválido',
                    mensaje: `El valor ingresado no es válido para el tipo "${tipo}".`
                },
                'no_existe': {
                    titulo: 'No Encontrado',
                    mensaje: 'La configuración que intentas modificar no existe.'
                },
                'config_critica': {
                    titulo: 'Configuración Protegida',
                    mensaje: 'Esta configuración es crítica para el sistema y no puede ser eliminada.'
                },
                'error_crear': {
                    titulo: 'Error al Crear',
                    mensaje: 'Ocurrió un error al crear la configuración. Inténtalo de nuevo.'
                },
                'error_actualizar': {
                    titulo: 'Error al Actualizar',
                    mensaje: 'Ocurrió un error al actualizar la configuración. Inténtalo de nuevo.'
                },
                'error_eliminar': {
                    titulo: 'Error al Eliminar',
                    mensaje: 'Ocurrió un error al eliminar la configuración. Inténtalo de nuevo.'
                },
                'error_sistema': {
                    titulo: 'Error del Sistema',
                    mensaje: 'Ocurrió un error interno. Contacta al administrador si persiste.'
                },
                'accion_invalida': {
                    titulo: 'Acción Inválida',
                    mensaje: 'La acción solicitada no es válida.'
                },
                'id_requerido': {
                    titulo: 'ID Requerido',
                    mensaje: 'Se requiere un ID válido para realizar esta operación.'
                }
            };
            
            if (mensajes[error]) {
                mostrarToast('error', mensajes[error].titulo, mensajes[error].mensaje);
            } else {
                mostrarToast('error', 'Error', 'Ocurrió un error desconocido.');
            }
        }
        
        // Limpiar la URL eliminando los parámetros de mensaje
        if (urlParams.has('success') || urlParams.has('error')) {
            urlParams.delete('success');
            urlParams.delete('error');
            urlParams.delete('tipo');
            
            var newUrl = window.location.pathname;
            if (urlParams.toString()) {
                newUrl += '?' + urlParams.toString();
            }
            
            window.history.replaceState({}, document.title, newUrl);
        }
    }
    
    // Ejecutar al cargar la página
    procesarMensajesURL();
    
    // Funciones adicionales para mejorar la experiencia
    
    // Confirmar eliminación con SweetAlert2 (si está disponible)
    $(document).on('click', '.btn-eliminar', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var clave = $(this).data('clave');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `Se eliminará la configuración "${clave}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'procesar_configuracion.php?accion=eliminar&id=' + id;
                }
            });
        } else {
            // Fallback a confirm nativo
            if (confirm('¿Estás seguro de que deseas eliminar la configuración "' + clave + '"?')) {
                window.location.href = 'procesar_configuracion.php?accion=eliminar&id=' + id;
            }
        }
    });
    
    // Validación en tiempo real del formulario
    $('#formNuevo input[name="clave"], #formEditar input[name="clave"]').on('input', function() {
        var clave = $(this).val();
        var regex = /^[a-zA-Z0-9_]+$/;
        
        if (clave && !regex.test(clave)) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
            $(this).after('<div class="invalid-feedback">La clave solo puede contener letras, números y guiones bajos.</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
    
    // Validación del valor según el tipo seleccionado
    function validarValorPorTipo() {
        var tipo = $(this).closest('form').find('select[name="tipo"]').val();
        var valor = $(this).val();
        var esValido = true;
        var mensaje = '';
        
        switch(tipo) {
            case 'numero':
                esValido = !isNaN(valor) && valor !== '';
                mensaje = 'Debe ser un número válido';
                break;
            case 'booleano':
                esValido = ['true', 'false', '1', '0'].includes(valor.toLowerCase());
                mensaje = 'Debe ser: true, false, 1 o 0';
                break;
            case 'json':
                try {
                    if (valor) JSON.parse(valor);
                    esValido = true;
                } catch (e) {
                    esValido = false;
                    mensaje = 'Debe ser un JSON válido';
                }
                break;
        }
        
        if (!esValido && valor) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
            $(this).after('<div class="invalid-feedback">' + mensaje + '</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    }
    
    $('#formNuevo textarea[name="valor"], #formEditar textarea[name="valor"]').on('blur', validarValorPorTipo);
    $('#formNuevo select[name="tipo"], #formEditar select[name="tipo"]').on('change', function() {
        $(this).closest('form').find('textarea[name="valor"]').trigger('blur');
    });
    
    // Auto-resize de textareas
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Búsqueda rápida global
    $('#busqueda-global').on('keyup', function() {
        var table = $('#configuracion-table').DataTable();
        table.search(this.value).draw();
    });
    
    // Exportar datos
    window.exportarConfiguracion = function(formato) {
        var tabla = $('#configuracion-table').DataTable();
        var datos = tabla.data().toArray();
        
        if (formato === 'csv') {
            exportarCSV(datos);
        } else if (formato === 'json') {
            exportarJSON(datos);
        }
    };
    
    function exportarCSV(datos) {
        var csv = 'ID,Clave,Valor,Tipo,Descripcion,Categoria,Actualizado\n';
        datos.forEach(function(fila) {
            // Remover HTML y escapar comillas
            var filaLimpia = fila.map(function(celda, index) {
                if (index === 7) return ''; // Omitir columna de acciones
                return '"' + $(celda).text().replace(/"/g, '""') + '"';
            });
            csv += filaLimpia.slice(0, 7).join(',') + '\n';
        });
        
        descargarArchivo(csv, 'configuracion_sistema.csv', 'text/csv');
    }
    
    function exportarJSON(datos) {
        var json = datos.map(function(fila) {
            return {
                id: $(fila[0]).text(),
                clave: $(fila[1]).text(),
                valor: $(fila[2]).text(),
                tipo: $(fila[3]).text(),
                descripcion: $(fila[4]).text(),
                categoria: $(fila[5]).text(),
                actualizado: $(fila[6]).text()
            };
        });
        
        descargarArchivo(JSON.stringify(json, null, 2), 'configuracion_sistema.json', 'application/json');
    }
    
    function descargarArchivo(contenido, nombreArchivo, tipoMime) {
        var blob = new Blob([contenido], { type: tipoMime });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = nombreArchivo;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
});

// Agregar estilos CSS adicionales con animaciones
var estilosAdicionales = `
<style>
/* Animaciones para toasts */
@keyframes iconBounce {
    0% { transform: scale(0) rotate(-180deg); opacity: 0; }
    50% { transform: scale(1.3) rotate(-90deg); opacity: 0.8; }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

@keyframes progressBar {
    0% { width: 100%; }
    100% { width: 0%; }
}

@keyframes slideInBounce {
    0% { transform: translateX(100%) scale(0.8); opacity: 0; }
    60% { transform: translateX(-15px) scale(1.05); opacity: 0.9; }
    100% { transform: translateX(0) scale(1); opacity: 1; }
}

@keyframes fadeOutSlide {
    0% { transform: translateX(0) scale(1); opacity: 1; }
    100% { transform: translateX(100%) scale(0.8); opacity: 0; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Estilos para toasts animados */
.toast-animated {
    min-width: 350px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.toast-animated::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.toast-icon-container {
    position: relative;
}

.toast-icon {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-size: 0.95rem;
    letter-spacing: 0.2px;
}

.toast-message {
    font-size: 0.85rem;
    line-height: 1.4;
}

.toast-progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    border-radius: 0 0 12px 12px;
    opacity: 0.7;
}

.btn-close-animated {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    opacity: 0.6;
    transition: all 0.3s ease;
    width: 24px;
    height: 24px;
}

.btn-close-animated:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
}

/* Efectos adicionales */
.toast-animated:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15) !important;
}

/* Estilos existentes mejorados */
.is-invalid {
    border-color: #dc3545;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}

.table tbody tr {
    transition: background-color 0.15s ease;
}

.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.03);
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    transition: background-color 0.15s ease;
}

.btn-group .btn:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.code-clave {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.valor-truncado {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}

/* Animaciones para elementos de formulario */
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    transform: scale(1.02);
    transition: all 0.3s ease;
}

.badge {
    transition: all 0.2s ease;
}

.badge:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

/* Responsive con animaciones */
@media (max-width: 768px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .toast-animated {
        min-width: 280px;
        margin: 0 10px;
    }
}

/* Efectos de carga */
.loader-bg {
    background: linear-gradient(45deg, #667eea, #764ba2);
}

.loader-track {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>
`;

$('head').append(estilosAdicionales);