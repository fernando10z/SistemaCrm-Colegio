<?php

session_start();
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'crear_encuesta':
            $mensaje_sistema = procesarCrearEncuesta($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'enviar_encuesta':
            $mensaje_sistema = procesarEnviarEncuesta($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'actualizar_estado_encuesta':
            $mensaje_sistema = procesarActualizarEstado($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'procesar_respuesta':
            $mensaje_sistema = procesarRespuestaEncuesta($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
    }
}

// Función para procesar creación de encuesta
function procesarCrearEncuesta($conn, $data) {
    try {
        $titulo = $conn->real_escape_string($data['titulo']);
        $descripcion = $conn->real_escape_string($data['descripcion']);
        $tipo = $conn->real_escape_string($data['tipo']);
        $dirigido_a = $conn->real_escape_string($data['dirigido_a']);
        $fecha_inicio = $conn->real_escape_string($data['fecha_inicio']);
        $fecha_fin = !empty($data['fecha_fin']) ? "'" . $conn->real_escape_string($data['fecha_fin']) . "'" : 'NULL';
        
        // Procesar preguntas en formato JSON
        $preguntas = [];
        if (isset($data['preguntas']) && is_array($data['preguntas'])) {
            foreach ($data['preguntas'] as $index => $pregunta) {
                $preguntas[] = [
                    'id' => $index + 1,
                    'pregunta' => $pregunta['pregunta'],
                    'tipo' => $pregunta['tipo'], // text, select, radio, checkbox, rating
                    'opciones' => $pregunta['opciones'] ?? [],
                    'requerida' => isset($pregunta['requerida']) ? true : false
                ];
            }
        }
        
        $preguntas_json = $conn->real_escape_string(json_encode($preguntas, JSON_UNESCAPED_UNICODE));
        
        $sql = "INSERT INTO encuestas (
                    titulo, descripcion, tipo, dirigido_a, preguntas, 
                    fecha_inicio, fecha_fin, activo
                ) VALUES (
                    '$titulo', '$descripcion', '$tipo', '$dirigido_a', '$preguntas_json', 
                    '$fecha_inicio', $fecha_fin, 1
                )";
        
        if ($conn->query($sql)) {
            $encuesta_id = $conn->insert_id;
            return "Encuesta '$titulo' creada correctamente con ID: $encuesta_id";
        } else {
            return "Error al crear la encuesta: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar envío de encuesta
function procesarEnviarEncuesta($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $dirigido_a = $conn->real_escape_string($data['dirigido_a']);
        $filtros = $data['filtros'] ?? [];
        
        // Construir consulta según destinatarios
        $destinatarios = [];
        
        switch ($dirigido_a) {
            case 'padres':
                $sql_dest = "SELECT DISTINCT a.id, a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                            FROM apoderados a 
                            WHERE a.activo = 1 AND a.email IS NOT NULL AND a.email != ''";
                break;
                
            case 'estudiantes':
                // Los estudiantes no tienen email directo, se envía a los apoderados
                $sql_dest = "SELECT DISTINCT a.id, a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                            FROM apoderados a 
                            INNER JOIN estudiantes e ON a.familia_id = e.familia_id
                            WHERE a.activo = 1 AND e.activo = 1 AND a.email IS NOT NULL AND a.email != ''";
                break;
                
            case 'exalumnos':
                $sql_dest = "SELECT DISTINCT ex.id, ex.email, CONCAT(ex.nombres, ' ', ex.apellidos) as nombre
                            FROM exalumnos ex 
                            WHERE ex.estado_contacto = 'activo' AND ex.email IS NOT NULL AND ex.email != ''";
                break;
                
            default:
                return "Error: Tipo de destinatario no válido.";
        }
        
        // Aplicar filtros adicionales si existen
        if (!empty($filtros['grado_id']) && $dirigido_a == 'estudiantes') {
            $grado_id = $conn->real_escape_string($filtros['grado_id']);
            $sql_dest .= " AND e.grado_id = $grado_id";
        }
        
        $result_dest = $conn->query($sql_dest);
        $total_enviados = 0;
        
        if ($result_dest && $result_dest->num_rows > 0) {
            // Aquí normalmente enviarías emails, por ahora simulamos el envío
            while ($dest = $result_dest->fetch_assoc()) {
                // Simular envío de email con link único a la encuesta
                $link_unico = generateUniqueToken();
                // TODO: Implementar envío real de email
                $total_enviados++;
            }
            
            // Actualizar estado de la encuesta
            $conn->query("UPDATE encuestas SET activo = 1 WHERE id = $encuesta_id");
            
            return "Encuesta enviada exitosamente a $total_enviados destinatarios.";
        } else {
            return "No se encontraron destinatarios válidos para enviar la encuesta.";
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar actualización de estado
function procesarActualizarEstado($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $nuevo_estado = isset($data['activo']) ? 1 : 0;
        
        $sql = "UPDATE encuestas SET activo = $nuevo_estado WHERE id = $encuesta_id";
        
        if ($conn->query($sql)) {
            $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
            return "Encuesta $estado_texto correctamente.";
        } else {
            return "Error al actualizar el estado: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para procesar respuesta de encuesta (simulada)
function procesarRespuestaEncuesta($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $apoderado_id = !empty($data['apoderado_id']) ? $conn->real_escape_string($data['apoderado_id']) : 'NULL';
        $respuestas = $conn->real_escape_string(json_encode($data['respuestas'], JSON_UNESCAPED_UNICODE));
        $puntaje = !empty($data['puntaje_calculado']) ? $conn->real_escape_string($data['puntaje_calculado']) : 'NULL';
        $ip_respuesta = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "INSERT INTO respuestas_encuesta (
                    encuesta_id, apoderado_id, respuestas, puntaje_calculado, 
                    fecha_respuesta, ip_respuesta
                ) VALUES (
                    $encuesta_id, $apoderado_id, '$respuestas', $puntaje, 
                    NOW(), '$ip_respuesta'
                )";
        
        if ($conn->query($sql)) {
            return "Respuesta registrada correctamente.";
        } else {
            return "Error al registrar la respuesta: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función auxiliar para generar token único
function generateUniqueToken() {
    return bin2hex(random_bytes(16));
}

// Consulta para obtener encuestas con estadísticas
$sql = "SELECT 
    e.id,
    e.titulo,
    e.descripcion,
    e.tipo,
    e.dirigido_a,
    e.fecha_inicio,
    e.fecha_fin,
    e.activo,
    e.created_at,
    e.updated_at,
    COUNT(re.id) as total_respuestas,
    COUNT(CASE WHEN re.fecha_respuesta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as respuestas_semana,
    ROUND(AVG(re.puntaje_calculado), 2) as puntaje_promedio,
    MIN(re.puntaje_calculado) as puntaje_minimo,
    MAX(re.puntaje_calculado) as puntaje_maximo,
    -- Calcular estado de la encuesta
    CASE 
        WHEN e.fecha_fin IS NOT NULL AND e.fecha_fin < CURDATE() THEN 'finalizada'
        WHEN e.fecha_inicio > CURDATE() THEN 'programada'
        WHEN e.activo = 1 THEN 'activa'
        ELSE 'inactiva'
    END as estado_encuesta,
    -- Calcular días restantes
    CASE 
        WHEN e.fecha_fin IS NOT NULL THEN DATEDIFF(e.fecha_fin, CURDATE())
        ELSE NULL
    END as dias_restantes,
    -- Calcular tasa de respuesta estimada
    CASE 
        WHEN COUNT(re.id) > 0 THEN ROUND((COUNT(re.id) / GREATEST(COUNT(re.id), 100)) * 100, 1)
        ELSE 0
    END as tasa_respuesta_estimada
FROM encuestas e
LEFT JOIN respuestas_encuesta re ON e.id = re.encuesta_id
GROUP BY e.id, e.titulo, e.descripcion, e.tipo, e.dirigido_a, e.fecha_inicio, e.fecha_fin, e.activo, e.created_at, e.updated_at
ORDER BY e.created_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas generales
$stats_sql = "SELECT 
    COUNT(DISTINCT e.id) as total_encuestas,
    COUNT(DISTINCT CASE WHEN e.activo = 1 THEN e.id END) as encuestas_activas,
    COUNT(DISTINCT CASE WHEN e.fecha_inicio <= CURDATE() AND (e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE()) THEN e.id END) as encuestas_vigentes,
    COUNT(DISTINCT CASE WHEN e.tipo = 'satisfaccion' THEN e.id END) as encuestas_satisfaccion,
    COUNT(DISTINCT CASE WHEN e.tipo = 'feedback' THEN e.id END) as encuestas_feedback,
    COUNT(re.id) as total_respuestas,
    COUNT(CASE WHEN DATE(re.fecha_respuesta) = CURDATE() THEN 1 END) as respuestas_hoy,
    COUNT(CASE WHEN DATE(re.fecha_respuesta) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as respuestas_semana,
    ROUND(AVG(re.puntaje_calculado), 2) as puntaje_general_promedio
FROM encuestas e
LEFT JOIN respuestas_encuesta re ON e.id = re.encuesta_id";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener distribución por tipo
$tipos_sql = "SELECT 
    tipo,
    COUNT(*) as cantidad,
    COUNT(CASE WHEN activo = 1 THEN 1 END) as activas
FROM encuestas 
GROUP BY tipo 
ORDER BY cantidad DESC";

$tipos_result = $conn->query($tipos_sql);
$tipos_stats = [];
while($tipo = $tipos_result->fetch_assoc()) {
    $tipos_stats[] = $tipo;
}

// Obtener nombre del sistema
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
  $nombre_sistema = htmlspecialchars($row_nombre['valor']);
} else {
  $nombre_sistema = "CRM Escolar";
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <title>Encuestas de Satisfacción - <?php echo $nombre_sistema; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Sistema CRM para instituciones educativas - Encuestas de Satisfacción" />
    <meta name="keywords" content="CRM, Educación, Encuestas, Satisfacción, Feedback, Análisis" />
    <meta name="author" content="CRM Escolar" />

    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link" />
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="assets/css/style-preset.css" />
    
    <style>
      .badge-tipo-encuesta {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .tipo-satisfaccion { background-color: #28a745; }
      .tipo-feedback { background-color: #17a2b8; }
      .tipo-evento { background-color: #ffc107; color: #856404; }
      .tipo-general { background-color: #6c757d; }
      
      .badge-dirigido {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
      }
      .dirigido-padres { background-color: #6f42c1; }
      .dirigido-estudiantes { background-color: #20c997; }
      .dirigido-exalumnos { background-color: #fd7e14; }
      .dirigido-general { background-color: #6c757d; }
      
      .badge-estado-encuesta {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
      }
      .estado-activa { background-color: #28a745; color: white; }
      .estado-inactiva { background-color: #6c757d; color: white; }
      .estado-programada { background-color: #ffc107; color: #856404; }
      .estado-finalizada { background-color: #dc3545; color: white; }
      
      .encuesta-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .encuesta-titulo {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .encuesta-descripcion {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .fechas-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .fecha-inicio {
        color: #495057;
        font-weight: 500;
      }
      
      .fecha-fin {
        color: #6c757d;
      }
      
      .dias-restantes {
        font-weight: bold;
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
        border-radius: 8px;
      }
      .dias-criticos { background-color: #f8d7da; color: #721c24; }
      .dias-advertencia { background-color: #fff3cd; color: #856404; }
      .dias-normales { background-color: #d4edda; color: #155724; }
      
      .estadisticas-respuestas {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .total-respuestas {
        font-weight: bold;
        color: #495057;
      }
      
      .respuestas-recientes {
        color: #28a745;
      }
      
      .puntaje-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .puntaje-promedio {
        font-weight: bold;
        font-size: 0.8rem;
      }
      .puntaje-excelente { color: #28a745; }
      .puntaje-bueno { color: #20c997; }
      .puntaje-regular { color: #ffc107; }
      .puntaje-malo { color: #fd7e14; }
      .puntaje-pesimo { color: #dc3545; }
      
      .puntaje-rango {
        color: #6c757d;
        font-size: 0.7rem;
      }
      
      .tasa-respuesta {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .tasa-alta { background-color: #d4edda; color: #155724; }
      .tasa-media { background-color: #fff3cd; color: #856404; }
      .tasa-baja { background-color: #f8d7da; color: #721c24; }
      
      .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        margin-bottom: 20px;
      }
      
      .stats-card .card-body {
        padding: 1.5rem;
      }
      
      .stat-item {
        text-align: center;
        padding: 10px;
      }
      
      .stat-number {
        font-size: 1.3rem;
        font-weight: bold;
        display: block;
      }
      
      .stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
      }
      
      .tipos-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .tipo-item {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        margin: 4px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
        min-width: 120px;
      }
      
      .btn-grupo-encuesta {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-encuesta .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
      }
      
      .progress-respuestas {
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
      }
    </style>
  </head>
  
  <body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <div class="loader-bg">
      <div class="loader-track">
        <div class="loader-fill"></div>
      </div>
    </div>
    
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    
    <section class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="row align-items-center">
              <div class="col-md-12">
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                  <li class="breadcrumb-item"><a href="javascript: void(0)">Satisfacción</a></li>
                  <li class="breadcrumb-item" aria-current="page">Encuestas</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <?php if(!empty($mensaje_sistema)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show alert-mensaje" role="alert">
          <?php echo $mensaje_sistema; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">Gestión de Encuestas de Satisfacción</h3>
                  <small class="text-muted">
                    Crea, administra y analiza encuestas de satisfacción para apoderados, estudiantes y exalumnos.
                    Obtén feedback valioso para mejorar continuamente la experiencia educativa.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-info btn-sm" onclick="analizarResultados()">
                    <i class="ti ti-chart-line me-1"></i>Analizar Resultados
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalGestionarRespuestas">
                    <i class="ti ti-messages me-1"></i>Gestionar Respuestas
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEnviarEncuesta">
                    <i class="ti ti-send me-1"></i>Enviar Encuesta
                  </button> -->
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearEncuesta">
                    <i class="ti ti-plus me-1"></i>Crear Encuesta
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <div class="dt-responsive table-responsive">
                  <table id="encuestas-table" class="table table-striped table-bordered nowrap">
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="15%">Encuesta</th>
                        <th width="8%">Tipo</th>
                        <th width="8%">Dirigido A</th>
                        <th width="8%">Estado</th>
                        <th width="12%">Fechas</th>
                        <th width="10%">Respuestas</th>
                        <th width="10%">Puntajes</th>
                        <th width="8%">Tasa Respuesta</th>
                        <th width="8%">Creación</th>
                        <th width="9%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              $fecha_creacion = date('d/m/Y', strtotime($row['created_at']));
                              $fecha_inicio = date('d/m/Y', strtotime($row['fecha_inicio']));
                              $fecha_fin = $row['fecha_fin'] ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Sin límite';
                              
                              // Clases CSS
                              $tipo_class = 'tipo-' . $row['tipo'];
                              $dirigido_class = 'dirigido-' . $row['dirigido_a'];
                              $estado_class = 'estado-' . $row['estado_encuesta'];
                              
                              // Días restantes
                              $dias = $row['dias_restantes'];
                              $dias_class = '';
                              $dias_texto = '';
                              if ($dias !== null) {
                                  if ($dias < 0) {
                                      $dias_class = 'dias-criticos';
                                      $dias_texto = 'Finalizada';
                                  } elseif ($dias <= 3) {
                                      $dias_class = 'dias-criticos';
                                      $dias_texto = $dias . ' días';
                                  } elseif ($dias <= 7) {
                                      $dias_class = 'dias-advertencia';
                                      $dias_texto = $dias . ' días';
                                  } else {
                                      $dias_class = 'dias-normales';
                                      $dias_texto = $dias . ' días';
                                  }
                              }
                              
                              // Puntaje
                              $puntaje = $row['puntaje_promedio'];
                              $puntaje_class = '';
                              if ($puntaje >= 4.5) $puntaje_class = 'puntaje-excelente';
                              elseif ($puntaje >= 4.0) $puntaje_class = 'puntaje-bueno';
                              elseif ($puntaje >= 3.0) $puntaje_class = 'puntaje-regular';
                              elseif ($puntaje >= 2.0) $puntaje_class = 'puntaje-malo';
                              else $puntaje_class = 'puntaje-pesimo';
                              
                              // Tasa de respuesta
                              $tasa = $row['tasa_respuesta_estimada'];
                              $tasa_class = '';
                              if ($tasa >= 70) $tasa_class = 'tasa-alta';
                              elseif ($tasa >= 40) $tasa_class = 'tasa-media';
                              else $tasa_class = 'tasa-baja';
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='encuesta-info'>
                                        <span class='encuesta-titulo'>" . htmlspecialchars($row['titulo']) . "</span>
                                        <span class='encuesta-descripcion' title='" . htmlspecialchars($row['descripcion']) . "'>" . 
                                        htmlspecialchars($row['descripcion']) . "</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-tipo-encuesta $tipo_class'>" . ucfirst($row['tipo']) . "</span></td>";
                              echo "<td><span class='badge badge-dirigido $dirigido_class'>" . ucfirst($row['dirigido_a']) . "</span></td>";
                              echo "<td><span class='badge badge-estado-encuesta $estado_class'>" . ucfirst($row['estado_encuesta']) . "</span></td>";
                              echo "<td>
                                      <div class='fechas-info'>
                                        <span class='fecha-inicio'>Inicio: " . $fecha_inicio . "</span>
                                        <span class='fecha-fin'>Fin: " . $fecha_fin . "</span>
                                        " . ($dias_texto ? "<span class='dias-restantes $dias_class'>" . $dias_texto . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='estadisticas-respuestas'>
                                        <span class='total-respuestas'>" . number_format($row['total_respuestas']) . " total</span>
                                        <span class='respuestas-recientes'>" . number_format($row['respuestas_semana']) . " esta semana</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='puntaje-info'>
                                        <span class='puntaje-promedio $puntaje_class'>" . 
                                        ($puntaje ? number_format($puntaje, 1) : 'N/A') . "</span>
                                        " . ($row['puntaje_minimo'] && $row['puntaje_maximo'] ? 
                                        "<span class='puntaje-rango'>(" . $row['puntaje_minimo'] . " - " . $row['puntaje_maximo'] . ")</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='tasa-respuesta $tasa_class'>" . $tasa . "%</span></td>";
                              echo "<td><span class='fecha-contacto'>" . $fecha_creacion . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-encuesta'>
                                        <button type='button' class='btn btn-outline-info btn-analizar' 
                                                data-id='" . $row['id'] . "'
                                                title='Analizar Resultados'>
                                          <i class='ti ti-chart-line'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-success btn-enviar-enc' 
                                                data-id='" . $row['id'] . "'
                                                data-titulo='" . htmlspecialchars($row['titulo']) . "'
                                                title='Enviar Encuesta'>
                                          <i class='ti ti-send'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-warning btn-gestionar-resp' 
                                                data-id='" . $row['id'] . "'
                                                title='Gestionar Respuestas'>
                                          <i class='ti ti-messages'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-" . ($row['activo'] ? 'danger' : 'success') . " btn-toggle-estado' 
                                            data-id='" . $row['id'] . "'
                                            data-estado='" . $row['activo'] . "'
                                            title='" . ($row['activo'] ? 'Desactivar' : 'Activar') . "'>
                                          <i class='ti ti-" . ($row['activo'] ? 'circle-x' : 'circle-check') . "'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay encuestas registradas</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <?php include 'modals/encuestas/modal_crear_encuesta.php'; ?>
    <?php include 'modals/encuestas/modal_enviar_encuesta.php'; ?>
    <?php include 'modals/encuestas/modal_analizar_resultados.php'; ?>
    <?php include 'modals/encuestas/modal_gestionar_respuestas.php'; ?>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/fonts/custom-font.js"></script>
    <script src="assets/js/pcoded.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>

    <script>
      layout_change("light");
      change_box_container("false");
      layout_rtl_change("false");
      preset_change("preset-1");
      font_change("Public-Sans");
    </script>

    <?php include 'includes/configuracion.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    
    <script>
      $(document).ready(function() {
        var table = $("#encuestas-table").DataTable({
          "language": {
          "decimal": "",
          "emptyTable": "No hay encuestas disponibles",
          "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
          "infoEmpty": "Mostrando 0 a 0 de 0 registros",
          "infoFiltered": "(filtrado de _MAX_ registros totales)",
          "lengthMenu": "Mostrar _MENU_ registros",
          "loadingRecords": "Cargando...",
          "processing": "Procesando...",
          "search": "Buscar:",
          "zeroRecords": "No se encontraron registros coincidentes",
          "paginate": {
            "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior"
          }
          },
          "pageLength": 25,
          "order": [[ 0, "desc" ]],
          "columnDefs": [{ "orderable": false, "targets": 10 }],
          "initComplete": function () {
          this.api().columns().every(function (index) {
            var column = this;
            if (index < 10) {
            var title = $(column.header()).text();
            var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
              .appendTo($(column.footer()).empty())
              .on('keyup change clear', function () {
              if (column.search() !== this.value) {
                column.search(this.value).draw();
              }
              });
            } else {
            $(column.footer()).html('<strong>Acciones</strong>');
            }
          });
          }
        });

        window.analizarResultados = function() {
          $('#modalAnalizarResultados').modal('show');
        };

        $(document).on('click', '.btn-analizar', function() {
          var id = $(this).data('id');
          cargarAnalisisEncuesta(id);
        });

        $(document).on('click', '.btn-enviar-enc', function() {
          var id = $(this).data('id');
          var titulo = $(this).data('titulo');
          $('#enviar_encuesta_id').val(id);
          $('#enviar_encuesta_titulo').text(titulo);
          $('#modalEnviarEncuesta').modal('show');
        });

        $(document).on('click', '.btn-gestionar-resp', function() {
          var id = $(this).data('id');
          cargarGestionRespuestas(id);
        });

        $(document).on('click', '.btn-toggle-estado', function() {
          var id = $(this).data('id');
          var estadoActual = $(this).data('estado');
          var accion = estadoActual == 1 ? 'desactivar' : 'activar';
          
          if (confirm('¿Está seguro de que desea ' + accion + ' esta encuesta?')) {
            toggleEstadoEncuesta(id, estadoActual);
          }
        });

        function cargarAnalisisEncuesta(id) {
          $.ajax({
          url: 'acciones/encuestas/procesar_encuestas.php',
          method: 'POST',
          data: { accion: 'obtener_analisis', encuesta_id: id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Mostrar análisis de la encuesta
              $('#modalAnalizarResultados .modal-body').html(response.data);
            } else {
              alert('Error al cargar análisis: ' + response.message);
            }
          },
          error: function() {
            alert('Error de conexión al obtener análisis.');
          }
          });
        }

        function cargarGestionRespuestas(id) {
          $('#gestionar_respuestas_encuesta_id').val(id);
          $('#modalGestionarRespuestas').modal('show');
        }

        function toggleEstadoEncuesta(id, estadoActual) {
          var nuevoEstado = estadoActual == 1 ? 0 : 1;
          
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = 'acciones/encuestas/procesar_encuestas.php';
          
          var inputs = [
          { name: 'accion', value: 'actualizar_estado_encuesta' },
          { name: 'encuesta_id', value: id }
          ];
          
          if (nuevoEstado == 1) {
          inputs.push({ name: 'activo', value: '1' });
          }
          
          inputs.forEach(function(input) {
          var inputElement = document.createElement('input');
          inputElement.type = 'hidden';
          inputElement.name = input.name;
          inputElement.value = input.value;
          form.appendChild(inputElement);
          });
          
          document.body.appendChild(form);
          form.submit();
        }

        function mostrarAnalisisCompleto(data) {
          console.log('Análisis de encuesta:', data);
          $('#modalAnalizarResultados').modal('show');
        }

        setInterval(function() {
          var encuestasActivas = $('.estado-activa').length;
          if (encuestasActivas > 0) {
          location.reload();
          }
        }, 300000); // 5 minutos

        $('[title]').tooltip();
      });
    </script>
    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
</html>

<?php $conn->close(); ?>