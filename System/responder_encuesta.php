<?php
require_once 'bd/conexion.php';

$encuesta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje_error = '';
$encuesta = null;

if ($encuesta_id > 0) {
    // Obtener encuesta
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, tipo, dirigido_a, preguntas, 
               fecha_inicio, fecha_fin, activo
        FROM encuestas 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $encuesta = $result->fetch_assoc();
        
        // Validar que esté activa
        if (!$encuesta['activo']) {
            $mensaje_error = 'Esta encuesta no está disponible actualmente.';
        }
        
        // Validar fechas
        $hoy = date('Y-m-d');
        if ($hoy < $encuesta['fecha_inicio']) {
            $mensaje_error = 'Esta encuesta aún no ha comenzado.';
        }
        if ($encuesta['fecha_fin'] && $hoy > $encuesta['fecha_fin']) {
            $mensaje_error = 'Esta encuesta ha finalizado.';
        }
        
        // Decodificar preguntas
        $preguntas = json_decode($encuesta['preguntas'], true);
    } else {
        $mensaje_error = 'Encuesta no encontrada.';
    }
    $stmt->close();
} else {
    $mensaje_error = 'ID de encuesta no válido.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $encuesta ? htmlspecialchars($encuesta['titulo']) : 'Encuesta'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #E8EAF6 0%, #C5CAE9 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .encuesta-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .encuesta-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .encuesta-header {
            background: linear-gradient(135deg, #5C6BC0 0%, #3F51B5 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .encuesta-body {
            padding: 40px;
        }
        .pregunta-card {
            background: #FAFAFA;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        .pregunta-card:hover {
            border-color: #B39DDB;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .pregunta-numero {
            background: linear-gradient(135deg, #B39DDB 0%, #9575CD 100%);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
        }
        .pregunta-titulo {
            font-size: 1.1rem;
            font-weight: 600;
            color: #424242;
            display: inline-block;
        }
        .requerido {
            color: #EF5350;
            margin-left: 4px;
        }
        .form-check-input:checked {
            background-color: #5C6BC0;
            border-color: #5C6BC0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #B39DDB;
            box-shadow: 0 0 0 0.2rem rgba(179, 157, 219, 0.25);
        }
        .rating-stars {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 16px 0;
        }
        .rating-stars input[type="radio"] {
            display: none;
        }
        .rating-stars label {
            font-size: 2.5rem;
            color: #E0E0E0;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .rating-stars label:hover,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #FFD54F;
            transform: scale(1.1);
        }
        .escala-options {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 16px 0;
        }
        .escala-option {
            flex: 1;
            min-width: 120px;
        }
        .escala-option input[type="radio"] {
            display: none;
        }
        .escala-option label {
            display: block;
            padding: 16px;
            background: white;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .escala-option label:hover {
            border-color: #B39DDB;
            background: #F3E5F5;
        }
        .escala-option input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #B39DDB 0%, #9575CD 100%);
            color: white;
            border-color: #9575CD;
        }
        .btn-enviar {
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            color: white;
            border: none;
            padding: 16px 48px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.3);
        }
        .error-container {
            text-align: center;
            padding: 60px 20px;
        }
        .error-icon {
            font-size: 5rem;
            color: #EF5350;
        }
    </style>
</head>
<body>
    <div class="encuesta-container">
        <?php if ($mensaje_error): ?>
            <!-- Mensaje de Error -->
            <div class="encuesta-card">
                <div class="error-container">
                    <i class="ti ti-alert-circle error-icon"></i>
                    <h2 class="mt-4 text-danger">Encuesta no disponible</h2>
                    <p class="text-muted mt-3"><?php echo htmlspecialchars($mensaje_error); ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulario de Encuesta -->
            <form id="formEncuesta" onsubmit="enviarEncuesta(event)">
                <div class="encuesta-card">
                    <!-- Header -->
                    <div class="encuesta-header">
                        <h1><?php echo htmlspecialchars($encuesta['titulo']); ?></h1>
                        <?php if ($encuesta['descripcion']): ?>
                            <p class="mt-3 mb-0 opacity-90"><?php echo nl2br(htmlspecialchars($encuesta['descripcion'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Body -->
                    <div class="encuesta-body">
                        <input type="hidden" name="encuesta_id" value="<?php echo $encuesta_id; ?>">
                        
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="pregunta-card">
                                <div class="mb-3">
                                    <span class="pregunta-numero"><?php echo $index + 1; ?></span>
                                    <span class="pregunta-titulo">
                                        <?php echo htmlspecialchars($pregunta['pregunta']); ?>
                                        <?php if ($pregunta['requerida']): ?>
                                            <span class="requerido">*</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php
                                $pregunta_name = "pregunta_" . $pregunta['id'];
                                $requerido = $pregunta['requerida'] ? 'required' : '';
                                
                                switch ($pregunta['tipo']):
                                    case 'text':
                                ?>
                                        <textarea class="form-control" name="<?php echo $pregunta_name; ?>" rows="4" 
                                                  placeholder="Escribe tu respuesta aquí..." <?php echo $requerido; ?>></textarea>
                                <?php
                                        break;
                                    
                                    case 'select':
                                ?>
                                        <select class="form-select" name="<?php echo $pregunta_name; ?>" <?php echo $requerido; ?>>
                                            <option value="">Selecciona una opción</option>
                                            <?php foreach ($pregunta['opciones'] as $opcion): ?>
                                                <option value="<?php echo htmlspecialchars($opcion); ?>">
                                                    <?php echo htmlspecialchars($opcion); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                <?php
                                        break;
                                    
                                    case 'radio':
                                ?>
                                        <div class="mt-3">
                                            <?php foreach ($pregunta['opciones'] as $i => $opcion): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" 
                                                           name="<?php echo $pregunta_name; ?>" 
                                                           id="<?php echo $pregunta_name . '_' . $i; ?>" 
                                                           value="<?php echo htmlspecialchars($opcion); ?>" <?php echo $requerido; ?>>
                                                    <label class="form-check-label" for="<?php echo $pregunta_name . '_' . $i; ?>">
                                                        <?php echo htmlspecialchars($opcion); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                <?php
                                        break;
                                    
                                    case 'checkbox':
                                ?>
                                        <div class="mt-3">
                                            <?php foreach ($pregunta['opciones'] as $i => $opcion): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="<?php echo $pregunta_name; ?>[]" 
                                                           id="<?php echo $pregunta_name . '_' . $i; ?>" 
                                                           value="<?php echo htmlspecialchars($opcion); ?>">
                                                    <label class="form-check-label" for="<?php echo $pregunta_name . '_' . $i; ?>">
                                                        <?php echo htmlspecialchars($opcion); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                <?php
                                        break;
                                    
                                    case 'rating':
                                ?>
                                        <div class="rating-stars">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="<?php echo $pregunta_name; ?>" 
                                                       id="<?php echo $pregunta_name . '_' . $i; ?>" 
                                                       value="<?php echo $i; ?>" <?php echo $requerido; ?>>
                                                <label for="<?php echo $pregunta_name . '_' . $i; ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                <?php
                                        break;
                                    
                                    case 'si_no':
                                ?>
                                        <div class="escala-options">
                                            <div class="escala-option">
                                                <input type="radio" name="<?php echo $pregunta_name; ?>" 
                                                       id="<?php echo $pregunta_name; ?>_si" value="Sí" <?php echo $requerido; ?>>
                                                <label for="<?php echo $pregunta_name; ?>_si">Sí</label>
                                            </div>
                                            <div class="escala-option">
                                                <input type="radio" name="<?php echo $pregunta_name; ?>" 
                                                       id="<?php echo $pregunta_name; ?>_no" value="No" <?php echo $requerido; ?>>
                                                <label for="<?php echo $pregunta_name; ?>_no">No</label>
                                            </div>
                                        </div>
                                <?php
                                        break;
                                    
                                    case 'escala':
                                ?>
                                        <div class="escala-options">
                                            <?php foreach ($pregunta['opciones'] as $i => $opcion): ?>
                                                <div class="escala-option">
                                                    <input type="radio" name="<?php echo $pregunta_name; ?>" 
                                                           id="<?php echo $pregunta_name . '_' . $i; ?>" 
                                                           value="<?php echo htmlspecialchars($opcion); ?>" <?php echo $requerido; ?>>
                                                    <label for="<?php echo $pregunta_name . '_' . $i; ?>">
                                                        <?php echo htmlspecialchars($opcion); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                <?php
                                        break;
                                endswitch;
                                ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Botón Enviar -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-enviar">
                                <i class="ti ti-send"></i> Enviar Respuestas
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    function enviarEncuesta(event) {
        event.preventDefault();
        
        const form = document.getElementById('formEncuesta');
        const formData = new FormData(form);
        
        // Convertir FormData a objeto
        const respuestas = {};
        for (let [key, value] of formData.entries()) {
            if (key !== 'encuesta_id') {
                const preguntaId = key.replace('pregunta_', '');
                
                // Si ya existe, convertir a array (para checkboxes)
                if (respuestas[preguntaId]) {
                    if (!Array.isArray(respuestas[preguntaId])) {
                        respuestas[preguntaId] = [respuestas[preguntaId]];
                    }
                    respuestas[preguntaId].push(value);
                } else {
                    respuestas[preguntaId] = value;
                }
            }
        }
        
        // Preparar datos para enviar
        const datos = new FormData();
        datos.append('encuesta_id', formData.get('encuesta_id'));
        datos.append('respuestas', JSON.stringify(respuestas));
        
        // Mostrar loading
        Swal.fire({
            title: 'Enviando respuestas...',
            html: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Enviar al servidor
        fetch('acciones/encuestas/procesar_respuesta.php', {
            method: 'POST',
            body: datos
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Gracias!',
                    html: 'Tus respuestas han sido enviadas correctamente.<br><br>Apreciamos tu participación.',
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#66BB6A'
                }).then(() => {
                    // Opcional: redirigir o limpiar formulario
                    form.reset();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.mensaje || 'Hubo un error al enviar las respuestas',
                    confirmButtonColor: '#EF5350'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#EF5350'
            });
        });
    }
    </script>
</body>
</html>