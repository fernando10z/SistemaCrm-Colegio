<?php
// Obtener familias activas para el select
include 'bd/conexion.php';
$familias_query = "SELECT id, codigo_familia, apellido_principal, nivel_socioeconomico 
                   FROM familias WHERE activo = 1 ORDER BY apellido_principal ASC";
$familias_result = $conn->query($familias_query);
?>

<!-- Modal Registrar Apoderado -->
<div class="modal fade" id="modalRegistrarApoderado" tabindex="-1" aria-labelledby="modalRegistrarApoderadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="background: #ffffff; border: none; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
      <div class="modal-header" style="background: linear-gradient(135deg, #a8d5e2 0%, #c9e4ca 100%); border-bottom: none; border-radius: 12px 12px 0 0; padding: 1.5rem;">
        <div>
          <h5 class="modal-title" id="modalRegistrarApoderadoLabel" style="color: #2c3e50; font-weight: 600; margin: 0;">
            <i class="ti ti-user-plus me-2"></i>Registrar Nuevo Apoderado
          </h5>
          <small style="color: #546e7a; display: block; margin-top: 0.25rem;">Complete los datos del apoderado</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formRegistrarApoderado">
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
                <select name="familia_id" class="form-select" required 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="">Seleccione una familia</option>
                  <?php while($familia = $familias_result->fetch_assoc()): ?>
                    <option value="<?php echo $familia['id']; ?>">
                      <?php echo htmlspecialchars($familia['codigo_familia'] . ' - ' . $familia['apellido_principal']); ?>
                      <?php echo $familia['nivel_socioeconomico'] ? ' [NSE: ' . $familia['nivel_socioeconomico'] . ']' : ''; ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Tipo de Apoderado <span style="color: #e74c3c;">*</span>
                </label>
                <select name="tipo_apoderado" class="form-select" required 
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
                <select name="tipo_documento" id="tipo_documento" class="form-select" required 
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
                <input type="text" name="numero_documento" id="numero_documento" class="form-control" required maxlength="20"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Ej: 12345678">
                <small class="text-muted" style="font-size: 0.7rem;">El DNI debe tener 8 dígitos</small>
              </div>
              
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Nombres <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="nombres" class="form-control" required maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Nombres completos">
              </div>
              
              <div class="col-md-3">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">
                  Apellidos <span style="color: #e74c3c;">*</span>
                </label>
                <input type="text" name="apellidos" class="form-control" required maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Apellidos completos">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Género</label>
                <select name="genero" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="">Seleccione</option>
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Estado Civil</label>
                <select name="estado_civil" class="form-select" 
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
                <input type="email" name="email" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="ejemplo@correo.com">
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Preferencia de Contacto</label>
                <select name="preferencia_contacto" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="whatsapp">WhatsApp</option>
                  <option value="email">Email</option>
                  <option value="llamada">Llamada</option>
                  <option value="sms">SMS</option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Teléfono Principal</label>
                <input type="text" name="telefono_principal" class="form-control" maxlength="20"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="+51 999 999 999">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Teléfono Secundario</label>
                <input type="text" name="telefono_secundario" class="form-control" maxlength="20"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="+51 999 999 999">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">WhatsApp</label>
                <input type="text" name="whatsapp" class="form-control" maxlength="20"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="+51 999 999 999">
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
                <input type="text" name="ocupacion" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Ej: Ingeniero, Docente">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Empresa</label>
                <input type="text" name="empresa" class="form-control" maxlength="100"
                       style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;"
                       placeholder="Nombre de la empresa">
              </div>
              
              <div class="col-md-4">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Nivel Educativo</label>
                <input type="text" name="nivel_educativo" class="form-control" maxlength="50"
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
                <select name="nivel_compromiso" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="medio" selected>Medio</option>
                  <option value="alto">Alto</option>
                  <option value="bajo">Bajo</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label" style="color: #546e7a; font-weight: 500;">Nivel de Participación</label>
                <select name="nivel_participacion" class="form-select" 
                        style="border: 1.5px solid #e0e0e0; border-radius: 8px; padding: 0.6rem; background: #fafafa;">
                  <option value="activo" selected>Activo</option>
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
          <button type="submit" class="btn" id="btnRegistrarApoderado"
                  style="background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 12px rgba(129, 199, 132, 0.3);">
            <i class="ti ti-device-floppy me-1"></i>Registrar Apoderado
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Validación de DNI en tiempo real
    $('#numero_documento').on('input', function() {
        const tipoDoc = $('#tipo_documento').val();
        const numDoc = $(this).val();
        
        if (tipoDoc === 'DNI') {
            // Solo permitir números
            $(this).val(numDoc.replace(/\D/g, ''));
            
            // Limitar a 8 dígitos
            if ($(this).val().length > 8) {
                $(this).val($(this).val().substr(0, 8));
            }
        }
    });
    
    // Validación al cambiar tipo de documento
    $('#tipo_documento').on('change', function() {
        $('#numero_documento').val('');
        
        if ($(this).val() === 'DNI') {
            $('#numero_documento').attr('maxlength', '8').attr('placeholder', '12345678');
        } else {
            $('#numero_documento').attr('maxlength', '20').attr('placeholder', 'Número de documento');
        }
    });

    // Enviar formulario
    $('#formRegistrarApoderado').on('submit', function(e) {
        e.preventDefault();
        
        // Validación manual de DNI
        const tipoDoc = $('#tipo_documento').val();
        const numDoc = $('#numero_documento').val();
        
        if (tipoDoc === 'DNI' && numDoc.length !== 8) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación de DNI',
                text: 'El DNI debe tener exactamente 8 dígitos',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#ffb74d',
                background: '#fffbf5',
                iconColor: '#ffb74d',
                customClass: {
                    popup: 'border-radius-12',
                    confirmButton: 'btn-custom-pastel'
                }
            });
            $('#numero_documento').focus();
            return false;
        }
        
        $.ajax({
            url: 'actions/procesar_registrar_apoderado.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#btnRegistrarApoderado').prop('disabled', true)
                    .html('<i class="ti ti-loader ti-spin me-1"></i>Registrando...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro Exitoso!',
                        html: `
                            <div style="text-align: left; padding: 1rem;">
                                <p style="margin-bottom: 0.5rem;"><strong>Apoderado:</strong> ${response.nombre_completo}</p>
                                <p style="margin-bottom: 0;"><strong>ID:</strong> ${response.id}</p>
                            </div>
                        `,
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#81c784',
                        background: '#f1f8f4',
                        iconColor: '#81c784',
                        timer: 3000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'border-radius-12',
                            confirmButton: 'btn-custom-pastel'
                        }
                    }).then(() => {
                        $('#modalRegistrarApoderado').modal('hide');
                        $('#formRegistrarApoderado')[0].reset();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Registrar',
                        text: response.message,
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#ef9a9a',
                        background: '#ffebee',
                        iconColor: '#ef5350',
                        customClass: {
                            popup: 'border-radius-12',
                            confirmButton: 'btn-custom-pastel'
                        }
                    });
                }
            },
            error: function(xhr) {
                console.error('Error completo:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, intente nuevamente.',
                    footer: '<small>Revise la consola del navegador para más detalles</small>',
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#ef9a9a',
                    background: '#ffebee',
                    iconColor: '#ef5350',
                    customClass: {
                        popup: 'border-radius-12',
                        confirmButton: 'btn-custom-pastel'
                    }
                });
            },
            complete: function() {
                $('#btnRegistrarApoderado').prop('disabled', false)
                    .html('<i class="ti ti-device-floppy me-1"></i>Registrar Apoderado');
            }
        });
    });
});
</script>

<!-- Estilos personalizados para SweetAlert -->
<style>
.border-radius-12 {
    border-radius: 12px !important;
}

.btn-custom-pastel {
    border-radius: 8px !important;
    font-weight: 500 !important;
    padding: 0.6rem 1.5rem !important;
}

.swal2-popup {
    font-family: 'Public Sans', sans-serif !important;
}

</style>