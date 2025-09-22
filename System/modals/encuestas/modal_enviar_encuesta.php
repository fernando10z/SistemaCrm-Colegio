<!-- Modal para enviar encuesta -->
<div class="modal fade" id="modalEnviarEncuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar Encuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEnviarEncuesta" method="POST" action="">
                <input type="hidden" name="accion" value="enviar_encuesta">
                <input type="hidden" name="encuesta_id" id="enviar_encuesta_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Encuesta:</strong> <span id="enviar_encuesta_titulo"></span>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Destinatarios <span class="text-danger">*</span></label>
                        <select class="form-select" name="dirigido_a" id="enviar_dirigido_a" required>
                            <option value="">Seleccionar destinatarios</option>
                            <option value="padres">Padres de Familia</option>
                            <option value="estudiantes">Estudiantes (vía apoderados)</option>
                            <option value="exalumnos">Ex-alumnos</option>
                        </select>
                    </div>
                    
                    <div class="filtros-container" id="filtros-container" style="display: none;">
                        <h6 class="mb-3">Filtros Adicionales</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3" id="filtro-grado" style="display: none;">
                                    <label class="form-label">Filtrar por Grado</label>
                                    <select class="form-select" name="filtros[grado_id]">
                                        <option value="">Todos los grados</option>
                                        <?php
                                        // Obtener grados de la base de datos
                                        $query_grados = "SELECT g.id, g.nombre, ne.nombre as nivel 
                                                        FROM grados g 
                                                        INNER JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id 
                                                        WHERE g.activo = 1 
                                                        ORDER BY ne.orden_display, g.orden_display";
                                        $result_grados = $conn->query($query_grados);
                                        if ($result_grados && $result_grados->num_rows > 0) {
                                            while($grado = $result_grados->fetch_assoc()) {
                                                echo "<option value='" . $grado['id'] . "'>" . $grado['nivel'] . " - " . $grado['nombre'] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="filtro-promocion" style="display: none;">
                                    <label class="form-label">Filtrar por Promoción</label>
                                    <select class="form-select" name="filtros[promocion]">
                                        <option value="">Todas las promociones</option>
                                        <?php
                                        // Obtener promociones de ex-alumnos
                                        $query_promociones = "SELECT DISTINCT promocion_egreso 
                                                            FROM exalumnos 
                                                            WHERE promocion_egreso IS NOT NULL 
                                                            ORDER BY promocion_egreso DESC";
                                        $result_promociones = $conn->query($query_promociones);
                                        if ($result_promociones && $result_promociones->num_rows > 0) {
                                            while($promocion = $result_promociones->fetch_assoc()) {
                                                echo "<option value='" . $promocion['promocion_egreso'] . "'>Promoción " . $promocion['promocion_egreso'] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mensaje Personalizado (Opcional)</label>
                        <textarea class="form-control" name="mensaje_personalizado" rows="3" 
                                  placeholder="Mensaje adicional que acompañará la encuesta..."></textarea>
                    </div>
                    
                    <div class="preview-destinatarios" id="preview-destinatarios" style="display: none;">
                        <h6 class="mb-2">Vista Previa de Destinatarios</h6>
                        <div class="alert alert-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total de destinatarios: <strong id="total-destinatarios">0</strong></span>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="previsualizarDestinatarios()">
                                    <i class="ti ti-refresh"></i> Actualizar
                                </button>
                            </div>
                        </div>
                        <div id="lista-destinatarios" class="mt-3"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-info" onclick="previsualizarDestinatarios()">
                        <i class="ti ti-eye"></i> Vista Previa
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-send"></i> Enviar Encuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('enviar_dirigido_a').addEventListener('change', function() {
    const valor = this.value;
    const filtrosContainer = document.getElementById('filtros-container');
    const filtroGrado = document.getElementById('filtro-grado');
    const filtroPromocion = document.getElementById('filtro-promocion');
    
    if (valor) {
        filtrosContainer.style.display = 'block';
        
        // Mostrar filtros específicos según el tipo de destinatario
        if (valor === 'estudiantes') {
            filtroGrado.style.display = 'block';
            filtroPromocion.style.display = 'none';
        } else if (valor === 'exalumnos') {
            filtroGrado.style.display = 'none';
            filtroPromocion.style.display = 'block';
        } else {
            filtroGrado.style.display = 'block';
            filtroPromocion.style.display = 'none';
        }
    } else {
        filtrosContainer.style.display = 'none';
        filtroGrado.style.display = 'none';
        filtroPromocion.style.display = 'none';
    }
});

function previsualizarDestinatarios() {
    const dirigidoA = document.getElementById('enviar_dirigido_a').value;
    
    if (!dirigidoA) {
        alert('Por favor seleccione el tipo de destinatarios');
        return;
    }
    
    const formData = new FormData(document.getElementById('formEnviarEncuesta'));
    formData.set('accion', 'previsualizar_destinatarios');
    
    fetch('acciones/encuestas/procesar_encuestas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('total-destinatarios').textContent = data.total || 0;
        document.getElementById('preview-destinatarios').style.display = 'block';
        
        const listaContainer = document.getElementById('lista-destinatarios');
        if (data.destinatarios && data.destinatarios.length > 0) {
            let html = '<div class="row">';
            data.destinatarios.slice(0, 10).forEach(dest => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="border rounded p-2">
                            <small class="text-muted">${dest.email}</small><br>
                            <strong>${dest.nombre}</strong>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            if (data.destinatarios.length > 10) {
                html += `<p class="text-muted mt-2">Y ${data.destinatarios.length - 10} destinatarios más...</p>`;
            }
            
            listaContainer.innerHTML = html;
        } else {
            listaContainer.innerHTML = '<p class="text-muted">No se encontraron destinatarios con los filtros seleccionados.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar la vista previa');
    });
}
</script>