<!-- Modal para Compartir Link de Encuesta -->
<div class="modal fade" id="modalCompartirEncuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #E1F5FE 0%, #B3E5FC 100%); border-bottom: 2px solid #81D4FA;">
                <div>
                    <h5 class="modal-title" style="color: #01579B; font-weight: 600;">
                        <i class="ti ti-share"></i> Compartir Encuesta
                    </h5>
                    <small class="text-muted" id="tituloEncuestaCompartir"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #FAFAFA;">
                
                <!-- URL de la Encuesta -->
                <div class="card mb-3" style="border: 1px solid #B3E5FC; background-color: #E1F5FE; border-radius: 12px;">
                    <div class="card-body">
                        <label class="form-label fw-bold" style="color: #01579B;">
                            <i class="ti ti-world"></i> URL de la Encuesta
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="urlEncuesta" readonly 
                                   style="background-color: white; border: 2px solid #81D4FA; border-radius: 8px 0 0 8px; font-family: monospace;">
                            <button class="btn" onclick="copiarURL()" 
                                    style="background: linear-gradient(135deg, #64B5F6 0%, #42A5F5 100%); color: white; border: none; border-radius: 0 8px 8px 0;">
                                <i class="ti ti-copy"></i> Copiar
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="ti ti-info-circle"></i> Comparte este link con los destinatarios
                        </small>
                    </div>
                </div>

                <!-- Opciones de Compartir -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6 style="color: #01579B; border-bottom: 2px solid #B3E5FC; padding-bottom: 8px;">
                            <i class="ti ti-send"></i> Compartir por:
                        </h6>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- WhatsApp -->
                    <div class="col-md-6">
                        <div class="card h-100" style="border: 1px solid #C8E6C9; border-radius: 12px; cursor: pointer;" 
                             onclick="compartirWhatsApp()">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="ti ti-brand-whatsapp" style="font-size: 3rem; color: #25D366;"></i>
                                </div>
                                <h6 style="color: #2E7D32;">WhatsApp</h6>
                                <small class="text-muted">Enviar por WhatsApp Web</small>
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="card h-100" style="border: 1px solid #FFCCBC; border-radius: 12px; cursor: pointer;" 
                             onclick="compartirEmail()">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="ti ti-mail" style="font-size: 3rem; color: #F57C00;"></i>
                                </div>
                                <h6 style="color: #E65100;">Email</h6>
                                <small class="text-muted">Enviar por correo electrónico</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Código QR (Opcional) -->
                <div class="card mt-3" style="border: 1px solid #E1BEE7; border-radius: 12px;">
                    <div class="card-body text-center">
                        <h6 style="color: #6A1B9A;">
                            <i class="ti ti-qrcode"></i> Código QR
                        </h6>
                        <div id="qrcode" class="mt-3"></div>
                        <small class="text-muted">Escanea el código para acceder directamente</small>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="alert alert-info mt-3" style="background-color: #E3F2FD; border: 1px solid #90CAF9; border-radius: 8px;">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold" style="color: #1565C0; font-size: 1.5rem;" id="estadTipo">-</div>
                            <small class="text-muted">Tipo</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold" style="color: #1565C0; font-size: 1.5rem;" id="estadDirigido">-</div>
                            <small class="text-muted">Dirigido a</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold" style="color: #1565C0; font-size: 1.5rem;" id="estadRespuestas">0</div>
                            <small class="text-muted">Respuestas</small>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="background-color: #F5F5F5;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- INCLUIR LIBRERÍA QRCODE (agregar antes del </body>) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let encuestaActual = null;
let qrCodeInstance = null;

// Event listener para botón de obtener link
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-obtener-link')) {
        const btn = e.target.closest('.btn-obtener-link');
        const encuestaId = btn.getAttribute('data-id');
        const encuestaTitulo = btn.getAttribute('data-titulo');
        abrirModalCompartir(encuestaId, encuestaTitulo);
    }
});

function abrirModalCompartir(encuestaId, titulo) {
    encuestaActual = encuestaId;
    
    // Construir URL (ajusta el dominio según tu entorno)
    const baseURL = window.location.origin + window.location.pathname.replace('encuestas.php', '');
    const urlEncuesta = `${baseURL}responder_encuesta.php?id=${encuestaId}`;
    
    // Actualizar modal
    document.getElementById('tituloEncuestaCompartir').textContent = titulo;
    document.getElementById('urlEncuesta').value = urlEncuesta;
    
    // Cargar estadísticas
    cargarEstadisticasEncuesta(encuestaId);
    
    // Generar código QR
    generarQR(urlEncuesta);
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalCompartirEncuesta'));
    modal.show();
}

function copiarURL() {
    const urlInput = document.getElementById('urlEncuesta');
    urlInput.select();
    document.execCommand('copy');
    
    Swal.fire({
        icon: 'success',
        title: '¡Copiado!',
        text: 'URL copiada al portapapeles',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        background: '#E8F5E9',
        iconColor: '#4CAF50'
    });
}

function compartirWhatsApp() {
    const url = document.getElementById('urlEncuesta').value;
    const titulo = document.getElementById('tituloEncuestaCompartir').textContent;
    const mensaje = `🗳️ *${titulo}*\n\nTe invitamos a completar esta encuesta:\n${url}`;
    const whatsappURL = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
    window.open(whatsappURL, '_blank');
}

function compartirEmail() {
    const url = document.getElementById('urlEncuesta').value;
    const titulo = document.getElementById('tituloEncuestaCompartir').textContent;
    const asunto = `Invitación: ${titulo}`;
    const cuerpo = `Hola,\n\nTe invitamos a completar la siguiente encuesta:\n\n${titulo}\n\nAccede aquí: ${url}\n\nGracias por tu participación.`;
    const mailtoURL = `mailto:?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(cuerpo)}`;
    window.location.href = mailtoURL;
}

function generarQR(url) {
    const contenedor = document.getElementById('qrcode');
    contenedor.innerHTML = ''; // Limpiar QR anterior
    
    if (qrCodeInstance) {
        qrCodeInstance.clear();
    }
    
    qrCodeInstance = new QRCode(contenedor, {
        text: url,
        width: 200,
        height: 200,
        colorDark: "#1565C0",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

function cargarEstadisticasEncuesta(encuestaId) {
    fetch(`acciones/encuestas/obtener_estadisticas.php?id=${encuestaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('estadTipo').textContent = data.tipo;
                document.getElementById('estadDirigido').textContent = data.dirigido_a;
                document.getElementById('estadRespuestas').textContent = data.total_respuestas;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Limpiar al cerrar modal
document.getElementById('modalCompartirEncuesta').addEventListener('hidden.bs.modal', function() {
    if (qrCodeInstance) {
        qrCodeInstance.clear();
    }
    encuestaActual = null;
});
</script>

<style>
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}
</style>