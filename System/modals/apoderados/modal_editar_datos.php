<?php
// Obtener familias activas para el select de edición
include 'bd/conexion.php';
$familias_query_edit = "SELECT id, codigo_familia, apellido_principal, nivel_socioeconomico 
                        FROM familias WHERE activo = 1 ORDER BY apellido_principal ASC";
$familias_result_edit = $conn->query($familias_query_edit);
?>

<style>
    /* Estilos específicos para el modal de edición */
    .modal-editar .swal2-container {
        z-index: 9999999 !important;
    }
    .modal-editar .is-invalid {
        border-color: #dc3545 !important;
    }
    .modal-editar .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .modal-editar .familia-selected {
        background: #e8f5e9 !important;
        border-color: #81c784 !important;
    }
    .modal-editar .familia-row:hover {
        background: #f5f5f5;
        cursor: pointer;
    }
    .modal-editar .familia-row.selected {
        background: #e8f5e9 !important;
    }
</style>

<!-- Modal Editar Apoderado -->
<div class="modal fade modal-editar" id="modalEditarApoderado" tabindex="-1" aria-labelledby="modalEditarApoderadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="background: #ffffff; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
      <div class="modal-header" style="background: linear-gradient(135deg, #64b5f6 0%, #81c784 100%); border-bottom: none; border-radius: 12px 12px 0 0; padding: 1.5rem;">
        <div>
          <h5 class="modal-title" id="modalEditarApoderadoLabel" style="color: #2c3e50; font-weight: 600; margin: 0;">
            <i class="ti ti-edit me-2"></i>Editar Datos del Apoderado
          </h5>
          <small style="color: #546e7a; display: block; margin-top: 0.25rem;">Modifique la información del apoderado</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formEditarApoderado">
        <input type="hidden" name="apoderado_id" id="edit_apoderado_id">
        
        <div class="modal-body" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
          
          <!-- Sección: Información Familiar -->
          <div class="mb-4">
            <div class="d-flex align-items-center mb-3" style="border-bottom: 2px solid #e8f5e9; padding-bottom: 0.75rem;">
              <i class="ti ti-home" style="font-size: 1.5rem; color: #81c784; margin-right: 0.75rem;"></i>
              <h6 class="mb-0" style="color: #2c3e50; font-weight: 600;">Información Familiar</h6>
            </div>
            
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Familia <span style="color: #e74c3c;">*</span>
                </label>
                <div class="input-group">
                  <input type="text" id="edit_familia_seleccionada" class="form-control" readonly required
                         placeholder="Haga clic en el botón para buscar"
                         style="border: 1.5px solid #e0e0e0; border-radius: 8px 0 0 8px; padding: 0.6rem; background: #fafafa;">
                  <input type="hidden" name="familia_id" id="edit_familia_id" required>
                  <button type="button" class="btn" id="btnBuscarFamiliaEdit"
                          style="background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%); color: white; border: none; border-radius: 0 8px 8px 0; padding: 0.6rem 1.2rem;">
                    <i class="ti ti-search"></i> Buscar
                  </button>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Haga clic en "Buscar" para cambiar la familia</small>
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Tipo de Apoderado <span style="color: #e74c3c;">*</span>
                </label>
                <select name="tipo_apoderado" id="edit_tipo_apoderado" class="form-select" required 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="">Seleccione tipo</option>
                  <option value="titular">Titular</option>
                  <option value="suplente">Suplente</option>
                  <option value="economico">Económico</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Sección: Datos Personales -->
          <div class="mb-4">
            <div class="d-flex align-items-center mb-3" style="border-bottom: 2px solid #fff3e0; padding-bottom: 0.75rem;">
              <i class="ti ti-user" style="font-size: 1.5rem; color: #ffb74d; margin-right: 0.75rem;"></i>
              <h6 class="mb-0" style="color: #2c3e50; font-weight: 600;">Datos Personales</h6>
            </div>
            
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Tipo Documento <span style="color: #e74c3c;">*</span>
                </label>
                <select name="tipo_documento" id="edit_tipo_documento" class="form-select" required 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="DNI">DNI</option>
                  <option value="CE">Carnet de Extranjería</option>
                  <option value="pasaporte">Pasaporte</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Número Documento <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="numero_documento" id="edit_numero_documento" class="form-control" required maxlength="12"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Ej: 12345678">
                <small class="text-muted" id="edit_doc_help" style="font-size: 0.7rem;">El DNI debe tener exactamente 8 dígitos</small>
              </div>
              
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Nombres <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="nombres" id="edit_nombres" class="form-control" required maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Nombres completos">
              </div>
              
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Apellidos <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="apellidos" id="edit_apellidos" class="form-control" required maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Apellidos completos">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                <small class="text-muted" style="font-size: 0.7rem;">La persona debe tener al menos 20 años</small>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Género</label>
                <select name="genero" id="edit_genero" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="">Seleccione</option>
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Estado Civil</label>
                <select name="estado_civil" id="edit_estado_civil" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="">Seleccione</option>
                  <option value="soltero">Soltero(a)</option>
                  <option value="casado">Casado(a)</option>
                  <option value="divorciado">Divorciado(a)</option>
                  <option value="viudo">Viudo(a)</option>
                  <option value="conviviente">Conviviente</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Sección: Información de Contacto -->
          <div class="mb-4">
            <div class="d-flex align-items-center mb-3" style="border-bottom: 2px solid #e1f5fe; padding-bottom: 0.75rem;">
              <i class="ti ti-phone" style="font-size: 1.5rem; color: #4fc3f7; margin-right: 0.75rem;"></i>
              <h6 class="mb-0" style="color: #2c3e50; font-weight: 600;">Información de Contacto</h6>
            </div>
            
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="ejemplo@correo.com">
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Preferencia de Contacto</label>
                <select name="preferencia_contacto" id="edit_preferencia_contacto" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="whatsapp">WhatsApp</option>
                  <option value="email">Email</option>
                  <option value="llamada">Llamada</option>
                  <option value="sms">SMS</option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Teléfono Principal <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="telefono_principal" id="edit_telefono_principal" class="form-control" required maxlength="9"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="999999999">
                <small class="text-muted" style="font-size: 0.7rem;">Debe tener exactamente 9 dígitos</small>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Teléfono Secundario</label>
                <input type="text" name="telefono_secundario" id="edit_telefono_secundario" class="form-control" maxlength="9"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="999999999">
                <small class="text-muted" style="font-size: 0.7rem;">Opcional - 9 dígitos si lo completa</small>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  WhatsApp <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="whatsapp" id="edit_whatsapp" class="form-control" required maxlength="9"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="999999999">
                <small class="text-muted" style="font-size: 0.7rem;">Debe tener exactamente 9 dígitos</small>
              </div>
            </div>
          </div>

          <!-- Sección: Información Profesional -->
          <div class="mb-4">
            <div class="d-flex align-items-center mb-3" style="border-bottom: 2px solid #f3e5f5; padding-bottom: 0.75rem;">
              <i class="ti ti-briefcase" style="font-size: 1.5rem; color: #ba68c8; margin-right: 0.75rem;"></i>
              <h6 class="mb-0" style="color: #2c3e50; font-weight: 600;">Información Profesional</h6>
            </div>
            
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Ocupación</label>
                <input type="text" name="ocupacion" id="edit_ocupacion" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Ej: Ingeniero, Docente">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Empresa</label>
                <input type="text" name="empresa" id="edit_empresa" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Nombre de la empresa">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Nivel Educativo</label>
                <input type="text" name="nivel_educativo" id="edit_nivel_educativo" class="form-control" maxlength="50"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Ej: Universitario">
              </div>
            </div>
          </div>

          <!-- Sección: Nivel de Participación -->
          <div class="mb-3">
            <div class="d-flex align-items-center mb-3" style="border-bottom: 2px solid #fff9c4; padding-bottom: 0.75rem;">
              <i class="ti ti-star" style="font-size: 1.5rem; color: #ffd54f; margin-right: 0.75rem;"></i>
              <h6 class="mb-0" style="color: #2c3e50; font-weight: 600;">Nivel de Participación</h6>
            </div>
            
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Nivel de Compromiso</label>
                <select name="nivel_compromiso" id="edit_nivel_compromiso" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="medio">Medio</option>
                  <option value="alto">Alto</option>
                  <option value="bajo">Bajo</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Nivel de Participación</label>
                <select name="nivel_participacion" id="edit_nivel_participacion" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="activo">Activo</option>
                  <option value="muy_activo">Muy Activo</option>
                  <option value="poco_activo">Poco Activo</option>
                  <option value="inactivo">Inactivo</option>
                </select>
              </div>
            </div>
          </div>

        </div>
        
        <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 1.25rem; border-radius: 0 0 12px 12px;">
          <button type="button" class="btn" data-bs-dismiss="modal" 
                  style="background: #eceff1; color: #546e7a; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 500;">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn" id="btnActualizarApoderado"
                  style="background: linear-gradient(135deg, #64b5f6 0%, #42a5f5 100%); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 12px rgba(100, 181, 246, 0.3);">
            <i class="ti ti-device-floppy me-1"></i>Actualizar Datos
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Buscar Familia para Edición -->
<div class="modal fade" id="modalBuscarFamiliaEdit" tabindex="-1" aria-labelledby="modalBuscarFamiliaEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="background: #ffffff; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8d5e2 0%, #c9e4ca 100%); border-bottom: none; border-radius: 12px 12px 0 0; padding: 1.5rem;">
        <div>
          <h5 class="modal-title" id="modalBuscarFamiliaEditLabel" style="color: #2c3e50; font-weight: 600; margin: 0;">
            <i class="ti ti-search me-2"></i>Buscar y Seleccionar Familia
          </h5>
          <small style="color: #546e7a; display: block; margin-top: 0.25rem;">Seleccione una familia para continuar</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body" style="padding: 1.5rem;">
        <!-- Buscador -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text" style="background: #f5f5f5; border: 1.5px solid #e0e0e0;">
              <i class="ti ti-search"></i>
            </span>
            <input type="text" id="buscar_familia_input_edit" class="form-control" 
                   placeholder="Buscar por código o apellido..."
                   style="border: 1.5px solid #e0e0e0; padding: 0.6rem;">
          </div>
        </div>
        
        <!-- Tabla de Familias -->
        <div style="max-height: 400px; overflow-y: auto;">
          <table class="table table-hover">
            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
              <tr>
                <th style="color: #546e7a; font-weight: 600; padding: 0.75rem;">Código</th>
                <th style="color: #546e7a; font-weight: 600; padding: 0.75rem;">Apellido</th>
                <th style="color: #546e7a; font-weight: 600; padding: 0.75rem;">NSE</th>
                <th style="color: #546e7a; font-weight: 600; padding: 0.75rem; text-align: center;">Acción</th>
              </tr>
            </thead>
            <tbody id="tabla_familias_edit">
              <?php 
              // Reiniciar el puntero del resultado
              $conn->query($familias_query_edit);
              $familias_result_edit = $conn->query($familias_query_edit);
              while($familia = $familias_result_edit->fetch_assoc()): 
              ?>
                <tr class="familia-row" data-id="<?php echo $familia['id']; ?>" 
                    data-codigo="<?php echo htmlspecialchars($familia['codigo_familia']); ?>"
                    data-apellido="<?php echo htmlspecialchars($familia['apellido_principal']); ?>"
                    data-nse="<?php echo htmlspecialchars($familia['nivel_socioeconomico'] ?? ''); ?>">
                  <td style="padding: 0.75rem;"><?php echo htmlspecialchars($familia['codigo_familia']); ?></td>
                  <td style="padding: 0.75rem;"><?php echo htmlspecialchars($familia['apellido_principal']); ?></td>
                  <td style="padding: 0.75rem;">
                    <?php if($familia['nivel_socioeconomico']): ?>
                      <span class="badge" style="background: #b3e5fc; color: #01579b; padding: 0.4rem 0.8rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($familia['nivel_socioeconomico']); ?>
                      </span>
                    <?php else: ?>
                      <span style="color: #999;">-</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 0.75rem; text-align: center;">
                    <button type="button" class="btn btn-sm btn-seleccionar-familia-edit"
                            style="background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%); color: white; border: none; padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.85rem;">
                      <i class="ti ti-check me-1"></i>Seleccionar
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 1rem; border-radius: 0 0 12px 12px;">
        <button type="button" class="btn" data-bs-dismiss="modal" 
                style="background: #eceff1; color: #546e7a; border: none; padding: 0.5rem 1.2rem; border-radius: 8px; font-weight: 500;">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    
    // Abrir modal de búsqueda de familia en edición
    $('#btnBuscarFamiliaEdit').on('click', function() {
        $('#modalBuscarFamiliaEdit').modal('show');
    });
    
    // Búsqueda en tiempo real de familias para edición
    $('#buscar_familia_input_edit').on('keyup', function() {
        const busqueda = $(this).val().toLowerCase();
        
        $('#tabla_familias_edit tr').each(function() {
            const codigo = $(this).data('codigo').toString().toLowerCase();
            const apellido = $(this).data('apellido').toString().toLowerCase();
            const nse = $(this).data('nse').toString().toLowerCase();
            
            if (codigo.includes(busqueda) || apellido.includes(busqueda) || nse.includes(busqueda)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Seleccionar familia en edición
    $(document).on('click', '.btn-seleccionar-familia-edit', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        const codigo = $row.data('codigo');
        const apellido = $row.data('apellido');
        const nse = $row.data('nse');
        
        // Construir texto descriptivo
        let textoFamilia = codigo + ' - ' + apellido;
        if (nse) {
            textoFamilia += ' [NSE: ' + nse + ']';
        }
        
        // Asignar valores
        $('#edit_familia_id').val(id);
        $('#edit_familia_seleccionada').val(textoFamilia).addClass('familia-selected');
        
        // Cerrar modal
        $('#modalBuscarFamiliaEdit').modal('hide');
        
        // Limpiar búsqueda
        $('#buscar_familia_input_edit').val('');
        $('#tabla_familias_edit tr').show();
        
        // Notificación
        Swal.fire({
            icon: 'success',
            title: 'Familia Seleccionada',
            text: textoFamilia,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
    
    // Configurar fecha de nacimiento para edición
    function configurarFechaNacimientoEdit() {
        const hoy = new Date();
        const añoActual = hoy.getFullYear();
        const mesActual = hoy.getMonth();
        const diaActual = hoy.getDate();
        
        const maxFecha = hoy.toISOString().split('T')[0];
        const fechaInicio = new Date(añoActual - 20, mesActual, diaActual);
        
        $('#edit_fecha_nacimiento').attr('max', maxFecha);
        $('#edit_fecha_nacimiento').attr('min', '1924-01-01');
    }
    
    configurarFechaNacimientoEdit();
    
    // Validación de fecha de nacimiento en edición
    $('#edit_fecha_nacimiento').on('change', function() {
        const fechaSeleccionada = new Date($(this).val());
        const hoy = new Date();
        
        let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
        const mes = hoy.getMonth() - fechaSeleccionada.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
            edad--;
        }
        
        if (fechaSeleccionada > hoy) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha Inválida',
                text: 'No se pueden registrar fechas futuras',
                confirmButtonColor: '#ffb74d'
            });
            $(this).val('');
        } else if (edad < 20) {
            Swal.fire({
                icon: 'warning',
                title: 'Edad Mínima Requerida',
                text: 'El apoderado debe tener al menos 20 años de edad',
                confirmButtonColor: '#ffb74d'
            });
            $(this).val('');
        }
    });

    // Validación de documentos en edición
    function validarDocumento(tipo, valor) {
        switch(tipo) {
            case 'DNI':
                if (!/^\d{8}$/.test(valor)) {
                    return {valido: false, mensaje: 'El DNI debe tener exactamente 8 dígitos'};
                }
                break;
            case 'CE':
                if (!/^\d{12}$/.test(valor)) {
                    return {valido: false, mensaje: 'El Carnet de Extranjería debe tener exactamente 12 dígitos'};
                }
                break;
            case 'pasaporte':
                if (!/^[A-Z0-9]{9}$/.test(valor.toUpperCase())) {
                    return {valido: false, mensaje: 'El Pasaporte debe tener exactamente 9 caracteres (letras y números)'};
                }
                break;
        }
        return {valido: true};
    }

    // Validación en tiempo real del número de documento en edición
    $('#edit_numero_documento').on('input', function() {
        const tipoDoc = $('#edit_tipo_documento').val();
        let valor = $(this).val().toUpperCase();
        
        switch(tipoDoc) {
            case 'DNI':
                valor = valor.replace(/\D/g, '');
                if (valor.length > 8) valor = valor.substr(0, 8);
                $(this).val(valor);
                break;
            case 'CE':
                valor = valor.replace(/\D/g, '');
                if (valor.length > 12) valor = valor.substr(0, 12);
                $(this).val(valor);
                break;
            case 'pasaporte':
                valor = valor.replace(/[^A-Z0-9]/g, '');
                if (valor.length > 9) valor = valor.substr(0, 9);
                $(this).val(valor);
                break;
        }
    });
    
    // Cambiar configuración al cambiar tipo de documento en edición
    $('#edit_tipo_documento').on('change', function() {
        const tipo = $(this).val();
        const $input = $('#edit_numero_documento');
        const $help = $('#edit_doc_help');
        
        $input.removeClass('is-invalid');
        
        switch(tipo) {
            case 'DNI':
                $input.attr('maxlength', '8').attr('placeholder', '12345678');
                $help.text('El DNI debe tener exactamente 8 dígitos');
                break;
            case 'CE':
                $input.attr('maxlength', '12').attr('placeholder', '123456789012');
                $help.text('El CE debe tener exactamente 12 dígitos');
                break;
            case 'pasaporte':
                $input.attr('maxlength', '9').attr('placeholder', 'ABC123456');
                $help.text('El Pasaporte debe tener exactamente 9 caracteres (letras y números)');
                break;
        }
    });

    // Validación en tiempo real de teléfonos en edición
    $('#edit_telefono_principal, #edit_telefono_secundario, #edit_whatsapp').on('input', function() {
        let valor = $(this).val().replace(/\D/g, '');
        
        if (valor.length > 9) {
            valor = valor.substr(0, 9);
        }
        
        $(this).val(valor);
        
        if (valor.length > 0 && valor.length !== 9) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Función para validar teléfonos
    function validarTelefono(valor, esOpcional = false) {
        if (esOpcional && (!valor || valor.trim() === '')) {
            return {valido: true};
        }
        
        if (!/^\d{9}$/.test(valor)) {
            return {valido: false, mensaje: 'El teléfono debe tener exactamente 9 dígitos'};
        }
        
        return {valido: true};
    }

    // Enviar formulario de edición
    $('#formEditarApoderado').on('submit', function(e) {
        e.preventDefault();
        
        // Validar familia
        if (!$('#edit_familia_id').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Familia Requerida',
                text: 'Debe seleccionar una familia',
                confirmButtonColor: '#ffb74d'
            });
            return false;
        }
        
        const tipoDoc = $('#edit_tipo_documento').val();
        const numDoc = $('#edit_numero_documento').val();
        
        // Validar documento
        const validacion = validarDocumento(tipoDoc, numDoc);
        if (!validacion.valido) {
            Swal.fire({
                icon: 'warning',
                title: 'Documento Inválido',
                text: validacion.mensaje,
                confirmButtonColor: '#ffb74d'
            });
            $('#edit_numero_documento').addClass('is-invalid').focus();
            return false;
        }
        
        // Validar fecha si está llena
        const fechaNac = $('#edit_fecha_nacimiento').val();
        if (fechaNac) {
            const fechaSeleccionada = new Date(fechaNac);
            const hoy = new Date();
            
            let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
            const mes = hoy.getMonth() - fechaSeleccionada.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
                edad--;
            }
            
            if (fechaSeleccionada > hoy || edad < 20) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha Inválida',
                    text: 'El apoderado debe tener al menos 20 años y no se permiten fechas futuras',
                    confirmButtonColor: '#ffb74d'
                });
                $('#edit_fecha_nacimiento').addClass('is-invalid').focus();
                return false;
            }
        }
        
        // Validar teléfonos
        const telPrincipal = $('#edit_telefono_principal').val();
        const validacionTelPrincipal = validarTelefono(telPrincipal, false);
        if (!validacionTelPrincipal.valido) {
            Swal.fire({
                icon: 'warning',
                title: 'Teléfono Principal Inválido',
                text: validacionTelPrincipal.mensaje,
                confirmButtonColor: '#ffb74d'
            });
            $('#edit_telefono_principal').addClass('is-invalid').focus();
            return false;
        }
        
        const telSecundario = $('#edit_telefono_secundario').val();
        if (telSecundario && telSecundario.trim() !== '') {
            const validacionTelSecundario = validarTelefono(telSecundario, false);
            if (!validacionTelSecundario.valido) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Teléfono Secundario Inválido',
                    text: validacionTelSecundario.mensaje + ' (o déjelo vacío)',
                    confirmButtonColor: '#ffb74d'
                });
                $('#edit_telefono_secundario').addClass('is-invalid').focus();
                return false;
            }
        }
        
        const whatsapp = $('#edit_whatsapp').val();
        const validacionWhatsApp = validarTelefono(whatsapp, false);
        if (!validacionWhatsApp.valido) {
            Swal.fire({
                icon: 'warning',
                title: 'WhatsApp Inválido',
                text: validacionWhatsApp.mensaje,
                confirmButtonColor: '#ffb74d'
            });
            $('#edit_whatsapp').addClass('is-invalid').focus();
            return false;
        }
        
        // Enviar datos
        $.ajax({
            url: 'actions/procesar_editar_apoderado.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#btnActualizarApoderado').prop('disabled', true)
                    .html('<i class="ti ti-loader ti-spin me-1"></i>Actualizando...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualización Exitosa!',
                        html: `
                            <div style="text-align: left; padding: 1rem;">
                                <p style="margin-bottom: 0.5rem;"><strong>Apoderado:</strong> ${response.nombre_completo}</p>
                                <p style="margin-bottom: 0;">Los datos se han actualizado correctamente</p>
                            </div>
                        `,
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#64b5f6',
                        background: '#f1f8ff',
                        iconColor: '#64b5f6',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        $('#modalEditarApoderado').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Actualizar',
                        text: response.message,
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#ef9a9a',
                        background: '#ffebee',
                        iconColor: '#ef5350'
                    });
                }
            },
            error: function(xhr) {
                console.error('Error completo:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#ef9a9a'
                });
            },
            complete: function() {
                $('#btnActualizarApoderado').prop('disabled', false)
                    .html('<i class="ti ti-device-floppy me-1"></i>Actualizar Datos');
            }
        });
    });
});

// Función para llenar el formulario de edición (llamada desde apoderados.php)
function llenarFormularioEdicion(data) {
    $('#edit_apoderado_id').val(data.id);
    
    // Familia
    let textoFamilia = data.codigo_familia + ' - ' + data.familia_apellido;
    if (data.nivel_socioeconomico) {
        textoFamilia += ' [NSE: ' + data.nivel_socioeconomico + ']';
    }
    $('#edit_familia_id').val(data.familia_id);
    $('#edit_familia_seleccionada').val(textoFamilia).addClass('familia-selected');
    
    // Datos básicos
    $('#edit_tipo_apoderado').val(data.tipo_apoderado);
    $('#edit_tipo_documento').val(data.tipo_documento);
    $('#edit_numero_documento').val(data.numero_documento);
    $('#edit_nombres').val(data.nombres);
    $('#edit_apellidos').val(data.apellidos);
    $('#edit_fecha_nacimiento').val(data.fecha_nacimiento);
    $('#edit_genero').val(data.genero);
    $('#edit_estado_civil').val(data.estado_civil);
    
    // Contacto
    $('#edit_email').val(data.email);
    $('#edit_preferencia_contacto').val(data.preferencia_contacto);
    $('#edit_telefono_principal').val(data.telefono_principal);
    $('#edit_telefono_secundario').val(data.telefono_secundario);
    $('#edit_whatsapp').val(data.whatsapp);
    
    // Profesional
    $('#edit_ocupacion').val(data.ocupacion);
    $('#edit_empresa').val(data.empresa);
    $('#edit_nivel_educativo').val(data.nivel_educativo);
    
    // Participación
    $('#edit_nivel_compromiso').val(data.nivel_compromiso);
    $('#edit_nivel_participacion').val(data.nivel_participacion);
    
    // Abrir modal
    $('#modalEditarApoderado').modal('show');
}
</script>