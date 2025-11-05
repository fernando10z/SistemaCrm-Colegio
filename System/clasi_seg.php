<?php
// Incluir conexión a la base de datos
include 'bd/conexion.php';

// Procesar acciones POST
$mensaje_sistema = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'evaluar_compromiso':
            $mensaje_sistema = procesarEvaluarCompromiso($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'medir_participacion':
            $mensaje_sistema = procesarMedirParticipacion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'actualizar_clasificacion':
            $mensaje_sistema = procesarActualizarClasificacion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'generar_segmentacion':
            $mensaje_sistema = procesarGenerarSegmentacion($conn, $_POST);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
            
        case 'recalcular_clasificaciones':
            $mensaje_sistema = recalcularTodasLasClasificaciones($conn);
            $tipo_mensaje = strpos($mensaje_sistema, 'Error') !== false ? 'error' : 'success';
            break;
    }
}

// Función para evaluar nivel de compromiso
function procesarEvaluarCompromiso($conn, $data) {
    try {
        $apoderado_id = $conn->real_escape_string($data['apoderado_id']);
        $nuevo_compromiso = $conn->real_escape_string($data['nivel_compromiso']);
        $observaciones = $conn->real_escape_string($data['observaciones'] ?? '');
        
        // Algoritmo de evaluación de compromiso basado en interacciones
        $sql_evaluacion = "SELECT 
            COUNT(i.id) as total_interacciones,
            COUNT(CASE WHEN i.resultado = 'exitoso' THEN 1 END) as interacciones_exitosas,
            COUNT(CASE WHEN i.resultado = 'sin_respuesta' THEN 1 END) as sin_respuesta,
            COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as interacciones_recientes,
            COUNT(CASE WHEN i.requiere_seguimiento = 1 AND i.fecha_proximo_seguimiento < CURDATE() THEN 1 END) as seguimientos_vencidos
            FROM interacciones i 
            WHERE i.apoderado_id = $apoderado_id AND i.activo = 1";
        
        $resultado_eval = $conn->query($sql_evaluacion);
        $metricas = $resultado_eval->fetch_assoc();
        
        // Calcular puntuación automática
        $puntuacion = 0;
        if ($metricas['total_interacciones'] > 0) {
            $tasa_exito = ($metricas['interacciones_exitosas'] / $metricas['total_interacciones']) * 100;
            $puntuacion += $tasa_exito * 0.4; // 40% peso
            $puntuacion += min(($metricas['interacciones_recientes'] / 5) * 30, 30); // 30% peso, máx 5 interacciones
            $puntuacion -= ($metricas['seguimientos_vencidos'] * 10); // Penalización
        }
        
        $nivel_calculado = $puntuacion >= 70 ? 'alto' : ($puntuacion >= 40 ? 'medio' : 'bajo');
        $nivel_final = !empty($nuevo_compromiso) ? $nuevo_compromiso : $nivel_calculado;
        
        $sql = "UPDATE apoderados SET 
                nivel_compromiso = '$nivel_final',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $apoderado_id";
        
        if ($conn->query($sql)) {
            // Registrar la evaluación en historial
            $sql_historial = "INSERT INTO interacciones (
                tipo_interaccion_id, usuario_id, apoderado_id, asunto, descripcion, 
                fecha_realizada, resultado, estado
            ) VALUES (
                1, 1, $apoderado_id, 'Evaluación de Compromiso', 
                'Nivel actualizado a: $nivel_final. Puntuación calculada: " . round($puntuacion, 2) . ". $observaciones',
                NOW(), 'exitoso', 'realizado'
            )";
            $conn->query($sql_historial);
            
            return "Nivel de compromiso evaluado correctamente. Puntuación: " . round($puntuacion, 2) . "%. Nivel asignado: " . strtoupper($nivel_final);
        } else {
            return "Error al actualizar el nivel de compromiso: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para medir nivel de participación
function procesarMedirParticipacion($conn, $data) {
    try {
        $apoderado_id = $conn->real_escape_string($data['apoderado_id']);
        $nueva_participacion = $conn->real_escape_string($data['nivel_participacion']);
        $observaciones = $conn->real_escape_string($data['observaciones'] ?? '');
        
        // Algoritmo de medición de participación
        $sql_medicion = "SELECT 
            COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as interacciones_mes,
            COUNT(CASE WHEN DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as interacciones_semana,
            AVG(i.duracion_minutos) as duracion_promedio,
            COUNT(CASE WHEN i.tipo_interaccion_id = 4 THEN 1 END) as reuniones_presenciales,
            MAX(DATE(i.created_at)) as ultima_interaccion
            FROM interacciones i 
            WHERE i.apoderado_id = $apoderado_id AND i.activo = 1";
        
        $resultado_med = $conn->query($sql_medicion);
        $metricas = $resultado_med->fetch_assoc();
        
        // Calcular puntuación de participación
        $puntuacion = 0;
        $puntuacion += min($metricas['interacciones_mes'] * 15, 60); // Máx 4 interacciones/mes
        $puntuacion += min($metricas['reuniones_presenciales'] * 20, 40); // Máx 2 reuniones
        
        if ($metricas['ultima_interaccion']) {
            $dias_ultima = (strtotime('now') - strtotime($metricas['ultima_interaccion'])) / (60*60*24);
            if ($dias_ultima <= 7) $puntuacion += 20;
            elseif ($dias_ultima <= 30) $puntuacion += 10;
        }
        
        $nivel_calculado = $puntuacion >= 80 ? 'muy_activo' : 
                          ($puntuacion >= 60 ? 'activo' : 
                          ($puntuacion >= 30 ? 'poco_activo' : 'inactivo'));
        
        $nivel_final = !empty($nueva_participacion) ? $nueva_participacion : $nivel_calculado;
        
        $sql = "UPDATE apoderados SET 
                nivel_participacion = '$nivel_final',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $apoderado_id";
        
        if ($conn->query($sql)) {
            return "Nivel de participación medido correctamente. Puntuación: " . round($puntuacion, 2) . "%. Nivel asignado: " . strtoupper(str_replace('_', ' ', $nivel_final));
        } else {
            return "Error al actualizar el nivel de participación: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para actualizar clasificación completa
function procesarActualizarClasificacion($conn, $data) {
    try {
        $apoderado_id = $conn->real_escape_string($data['apoderado_id']);
        $tipo_apoderado = $conn->real_escape_string($data['tipo_apoderado']);
        $nivel_compromiso = $conn->real_escape_string($data['nivel_compromiso']);
        $nivel_participacion = $conn->real_escape_string($data['nivel_participacion']);
        $preferencia_contacto = $conn->real_escape_string($data['preferencia_contacto']);
        $observaciones = $conn->real_escape_string($data['observaciones'] ?? '');
        
        $sql = "UPDATE apoderados SET 
                tipo_apoderado = '$tipo_apoderado',
                nivel_compromiso = '$nivel_compromiso',
                nivel_participacion = '$nivel_participacion',
                preferencia_contacto = '$preferencia_contacto',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $apoderado_id";
        
        if ($conn->query($sql)) {
            // Determinar categoría final
            $categoria = determinarCategoriaApoderado($nivel_compromiso, $nivel_participacion);
            
            // Registrar actualización
            $sql_historial = "INSERT INTO interacciones (
                tipo_interaccion_id, usuario_id, apoderado_id, asunto, descripcion, 
                fecha_realizada, resultado, estado
            ) VALUES (
                1, 1, $apoderado_id, 'Actualización de Clasificación', 
                'Clasificación actualizada. Categoría: $categoria. $observaciones',
                NOW(), 'exitoso', 'realizado'
            )";
            $conn->query($sql_historial);
            
            return "Clasificación actualizada correctamente. Categoría asignada: " . strtoupper($categoria);
        } else {
            return "Error al actualizar la clasificación: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para generar reportes de segmentación
function procesarGenerarSegmentacion($conn, $data) {
    try {
        $criterio = $conn->real_escape_string($data['criterio_segmentacion']);
        $formato = $conn->real_escape_string($data['formato'] ?? 'pantalla');
        
        switch ($criterio) {
            case 'compromiso_participacion':
                $sql = "SELECT 
                    CONCAT(nivel_compromiso, '_', nivel_participacion) as segmento,
                    COUNT(*) as cantidad,
                    ROUND(AVG(YEAR(CURDATE()) - YEAR(fecha_nacimiento)), 1) as edad_promedio
                    FROM apoderados 
                    WHERE activo = 1 
                    GROUP BY nivel_compromiso, nivel_participacion
                    ORDER BY cantidad DESC";
                break;
                
            case 'nivel_socioeconomico':
                $sql = "SELECT 
                    f.nivel_socioeconomico as segmento,
                    COUNT(a.id) as cantidad,
                    a.nivel_compromiso,
                    COUNT(CASE WHEN a.nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos
                    FROM apoderados a
                    LEFT JOIN familias f ON a.familia_id = f.id
                    WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
                    GROUP BY f.nivel_socioeconomico, a.nivel_compromiso
                    ORDER BY f.nivel_socioeconomico, cantidad DESC";
                break;
                
            case 'problematicos_colaboradores':
                $sql = "SELECT 
                    CASE 
                        WHEN a.nivel_compromiso = 'alto' AND a.nivel_participacion IN ('muy_activo', 'activo') THEN 'Colaborador Estrella'
                        WHEN a.nivel_compromiso = 'alto' THEN 'Comprometido'
                        WHEN a.nivel_participacion = 'muy_activo' THEN 'Muy Participativo'
                        WHEN a.nivel_compromiso = 'bajo' AND a.nivel_participacion = 'inactivo' THEN 'Problemático'
                        WHEN a.nivel_compromiso = 'bajo' THEN 'Bajo Compromiso'
                        WHEN a.nivel_participacion = 'inactivo' THEN 'Inactivo'
                        ELSE 'Regular'
                    END as segmento,
                    COUNT(*) as cantidad,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM apoderados WHERE activo = 1), 2) as porcentaje
                    FROM apoderados a
                    WHERE a.activo = 1
                    GROUP BY segmento
                    ORDER BY cantidad DESC";
                break;
                
            default:
                return "Error: Criterio de segmentación no válido.";
        }
        
        if ($formato === 'excel') {
            // Generar archivo Excel (simulado)
            return "Reporte de segmentación generado y enviado por email.";
        } else {
            return "Reporte de segmentación generado correctamente en pantalla.";
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función auxiliar para determinar categoría
function determinarCategoriaApoderado($compromiso, $participacion) {
    if ($compromiso === 'alto' && in_array($participacion, ['muy_activo', 'activo'])) {
        return 'colaborador_estrella';
    } elseif ($compromiso === 'alto') {
        return 'comprometido';
    } elseif ($participacion === 'muy_activo') {
        return 'muy_participativo';
    } elseif ($compromiso === 'bajo' && $participacion === 'inactivo') {
        return 'problematico';
    } elseif ($compromiso === 'bajo') {
        return 'bajo_compromiso';
    } elseif ($participacion === 'inactivo') {
        return 'inactivo';
    } else {
        return 'regular';
    }
}

// Función para recalcular todas las clasificaciones
function recalcularTodasLasClasificaciones($conn) {
    try {
        $sql_apoderados = "SELECT id FROM apoderados WHERE activo = 1";
        $result = $conn->query($sql_apoderados);
        $procesados = 0;
        
        while ($apoderado = $result->fetch_assoc()) {
            procesarEvaluarCompromiso($conn, ['apoderado_id' => $apoderado['id']]);
            procesarMedirParticipacion($conn, ['apoderado_id' => $apoderado['id']]);
            $procesados++;
        }
        
        return "Recálculo completado. $procesados apoderados procesados.";
    } catch (Exception $e) {
        return "Error en recálculo masivo: " . $e->getMessage();
    }
}

// Consulta principal para obtener apoderados con clasificaciones y métricas
$sql = "SELECT 
    a.id,
    a.familia_id,
    f.codigo_familia,
    f.apellido_principal as familia_apellido,
    f.nivel_socioeconomico,
    a.tipo_apoderado,
    a.nombres,
    a.apellidos,
    a.email,
    a.telefono_principal,
    a.nivel_compromiso,
    a.nivel_participacion,
    a.preferencia_contacto,
    a.created_at,
    a.updated_at,
    CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
    -- Métricas calculadas
    (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) as total_interacciones,
    (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1 
     AND DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)) as interacciones_recientes,
    (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.resultado = 'exitoso' AND i.activo = 1) as interacciones_exitosas,
    (SELECT MAX(DATE(i.created_at)) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) as ultima_interaccion,
    -- Puntuación calculada de compromiso
    CASE 
        WHEN (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) = 0 THEN 0
        ELSE ROUND(
            ((SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.resultado = 'exitoso' AND i.activo = 1) * 100.0 / 
             (SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1)) * 0.6 +
            LEAST((SELECT COUNT(*) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1 
                   AND DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)) * 8, 40)
        , 2)
    END as puntuacion_compromiso,
    -- Categorización automática
    CASE 
        WHEN a.nivel_compromiso = 'alto' AND a.nivel_participacion IN ('muy_activo', 'activo') THEN 'colaborador_estrella'
        WHEN a.nivel_compromiso = 'alto' THEN 'comprometido'
        WHEN a.nivel_participacion = 'muy_activo' THEN 'muy_participativo'
        WHEN a.nivel_compromiso = 'bajo' AND a.nivel_participacion = 'inactivo' THEN 'problematico'
        WHEN a.nivel_compromiso = 'bajo' THEN 'bajo_compromiso'
        WHEN a.nivel_participacion = 'inactivo' THEN 'inactivo'
        ELSE 'regular'
    END as categoria_apoderado,
    -- Días desde última interacción
    CASE 
        WHEN (SELECT MAX(DATE(i.created_at)) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1) IS NOT NULL
        THEN DATEDIFF(CURDATE(), (SELECT MAX(DATE(i.created_at)) FROM interacciones i WHERE i.apoderado_id = a.id AND i.activo = 1))
        ELSE NULL
    END as dias_ultima_interaccion
FROM apoderados a
LEFT JOIN familias f ON a.familia_id = f.id
WHERE a.activo = 1
ORDER BY 
    CASE a.nivel_compromiso
        WHEN 'alto' THEN 1
        WHEN 'medio' THEN 2
        WHEN 'bajo' THEN 3
        ELSE 4
    END,
    CASE a.nivel_participacion
        WHEN 'muy_activo' THEN 1
        WHEN 'activo' THEN 2
        WHEN 'poco_activo' THEN 3
        WHEN 'inactivo' THEN 4
        ELSE 5
    END,
    a.updated_at DESC";

$result = $conn->query($sql);

// Obtener estadísticas de clasificación para mostrar
$stats_sql = "SELECT 
    COUNT(*) as total_apoderados,
    COUNT(CASE WHEN nivel_compromiso = 'alto' THEN 1 END) as alto_compromiso,
    COUNT(CASE WHEN nivel_compromiso = 'medio' THEN 1 END) as medio_compromiso,
    COUNT(CASE WHEN nivel_compromiso = 'bajo' THEN 1 END) as bajo_compromiso,
    COUNT(CASE WHEN nivel_participacion = 'muy_activo' THEN 1 END) as muy_activos,
    COUNT(CASE WHEN nivel_participacion = 'activo' THEN 1 END) as activos,
    COUNT(CASE WHEN nivel_participacion = 'poco_activo' THEN 1 END) as poco_activos,
    COUNT(CASE WHEN nivel_participacion = 'inactivo' THEN 1 END) as inactivos,
    -- Categorías especiales
    COUNT(CASE WHEN nivel_compromiso = 'alto' AND nivel_participacion IN ('muy_activo', 'activo') THEN 1 END) as colaboradores_estrella,
    COUNT(CASE WHEN nivel_compromiso = 'bajo' AND nivel_participacion = 'inactivo' THEN 1 END) as problematicos
FROM apoderados 
WHERE activo = 1";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Obtener distribución por nivel socioeconómico
$socioeconomico_sql = "SELECT 
    f.nivel_socioeconomico,
    COUNT(a.id) as cantidad,
    AVG(CASE WHEN a.nivel_compromiso = 'alto' THEN 1 ELSE 0 END) * 100 as porcentaje_alto_compromiso
FROM apoderados a
LEFT JOIN familias f ON a.familia_id = f.id
WHERE a.activo = 1 AND f.nivel_socioeconomico IS NOT NULL
GROUP BY f.nivel_socioeconomico
ORDER BY f.nivel_socioeconomico";

$socioeconomico_result = $conn->query($socioeconomico_sql);
$socioeconomico_data = [];
while($socio = $socioeconomico_result->fetch_assoc()) {
    $socioeconomico_data[] = $socio;
}

// Obtener nombre del sistema para el título
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
  <!-- [Head] start -->
  <head>
    <title>Clasificación y Segmentación - <?php echo $nombre_sistema; ?></title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
    />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Sistema CRM para instituciones educativas - Clasificación y Segmentación"
    />
    <meta
      name="keywords"
      content="CRM, Educación, Clasificación, Segmentación, Apoderados, Análisis"
    />
    <meta name="author" content="CRM Escolar" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <!-- [Page specific CSS] start -->
    <!-- data tables css -->
    <link
      rel="stylesheet"
      href="assets/css/plugins/dataTables.bootstrap5.min.css"
    />
    <!-- [Page specific CSS] end -->
    <!-- [Google Font] Family -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
      id="main-font-link"
    />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link
      rel="stylesheet"
      href="assets/css/style.css"
      id="main-style-link"
    />
    <link rel="stylesheet" href="assets/css/style-preset.css" />
    
    <!-- Custom styles for clasificación -->
    <style>
      .badge-categoria {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        color: white;
      }
      .categoria-colaborador_estrella { 
        background: linear-gradient(45deg, #28a745, #20c997);
        animation: shine-star 3s infinite;
      }
      .categoria-comprometido { background-color: #17a2b8; }
      .categoria-muy_participativo { background-color: #6f42c1; }
      .categoria-regular { background-color: #6c757d; }
      .categoria-bajo_compromiso { background-color: #fd7e14; }
      .categoria-inactivo { background-color: #ffc107; color: #856404; }
      .categoria-problematico { 
        background-color: #dc3545;
        animation: pulse-problem 2s infinite;
      }
      
      @keyframes shine-star {
        0%, 100% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
        50% { box-shadow: 0 0 15px rgba(40, 167, 69, 0.8), 0 0 25px rgba(40, 167, 69, 0.4); }
      }
      
      @keyframes pulse-problem {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
      }
      
      .badge-compromiso {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 8px;
        font-weight: bold;
      }
      .compromiso-alto { background-color: #28a745; color: white; }
      .compromiso-medio { background-color: #ffc107; color: #856404; }
      .compromiso-bajo { background-color: #dc3545; color: white; }
      
      .badge-participacion {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-weight: 500;
      }
      .participacion-muy_activo { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
      }
      .participacion-activo { 
        background-color: #d1ecf1; 
        color: #0c5460; 
        border: 1px solid #bee5eb;
      }
      .participacion-poco_activo { 
        background-color: #fff3cd; 
        color: #856404; 
        border: 1px solid #ffeaa7;
      }
      .participacion-inactivo { 
        background-color: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb;
      }
      
      .puntuacion-score {
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
        border-radius: 8px;
        font-weight: bold;
        text-align: center;
        min-width: 50px;
      }
      .score-excelente { background-color: #28a745; color: white; }
      .score-bueno { background-color: #17a2b8; color: white; }
      .score-regular { background-color: #ffc107; color: #856404; }
      .score-bajo { background-color: #dc3545; color: white; }
      
      .apoderado-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      
      .apoderado-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
      }
      
      .apoderado-contacto {
        font-size: 0.75rem;
        color: #6c757d;
      }
      
      .metricas-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: 0.75rem;
      }
      
      .metrica-principal {
        font-weight: bold;
        color: #495057;
      }
      
      .metrica-secundaria {
        color: #6c757d;
      }
      
      .ultima-interaccion {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        border-radius: 4px;
        font-weight: 500;
      }
      .interaccion-reciente { background-color: #d4edda; color: #155724; }
      .interaccion-antigua { background-color: #fff3cd; color: #856404; }
      .interaccion-muy_antigua { background-color: #f8d7da; color: #721c24; }
      .sin-interaccion { background-color: #f8f9fa; color: #6c757d; }
      
      .familia-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      
      .familia-codigo {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        background-color: #e3f2fd;
        color: #1565c0;
        padding: 1px 4px;
        border-radius: 3px;
      }
      
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
      
      .segmentacion-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .segmento-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        margin: 3px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        color: white;
        cursor: pointer;
        transition: transform 0.2s;
      }
      
      .segmento-item:hover {
        transform: scale(1.05);
      }
      
      .btn-grupo-clasificacion {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      
      .btn-grupo-clasificacion .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
      
      .alert-mensaje {
        margin-bottom: 20px;
      }
      
      .preferencia-contacto {
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
        background-color: #e8f4fd;
        color: #0c5460;
      }
    </style>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->
  <body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
      <div class="loader-track">
        <div class="loader-fill"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->
    
    <!-- [ Sidebar Menu ] start -->
    <?php include 'includes/sidebar.php'; ?>
    <!-- [ Sidebar Menu ] end -->
    
    <!-- [ Header Topbar ] start -->
    <?php include 'includes/header.php'; ?>
    <!-- [ Header ] end -->
    
    <section class="pc-container">
      <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
          <div class="page-block">
            <div class="row align-items-center">
              <div class="col-md-12">
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                  <li class="breadcrumb-item">
                    <a href="javascript: void(0)">Gestión Familiar</a>
                  </li>
                  <li class="breadcrumb-item" aria-current="page">
                    Clasificación y Segmentación
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Mensaje del Sistema ] start -->
        <?php if(!empty($mensaje_sistema)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show alert-mensaje" role="alert">
          <?php echo $mensaje_sistema; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <!-- [ Mensaje del Sistema ] end -->

        <!-- [ Segmentación Panel ] start -->
        <div class="row mb-3">
          <div class="col-sm-12">
            <div class="segmentacion-panel">
              <h6 class="mb-3"><i class="ti ti-chart-donut me-2"></i>Segmentación Rápida por Categorías</h6>

            </div>
          </div>
        </div>
        <!-- [ Segmentación Panel ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">          
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h3 class="mb-1">
                    Clasificación y Segmentación de Apoderados
                  </h3>
                  <small class="text-muted">
                    Sistema inteligente de clasificación basado en compromiso y participación. 
                    Evalúa automáticamente y genera segmentaciones para estrategias personalizadas.
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <!-- <button type="button" class="btn btn-outline-warning btn-sm" onclick="recalcularTodo()">
                    <i class="ti ti-refresh me-1"></i>
                    Recalcular Todo
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalMedirParticipacion">
                    <i class="ti ti-activity me-1"></i>
                    Medir Participación
                  </button>
                  <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalEvaluarCompromiso">
                    <i class="ti ti-heart me-1"></i>
                    Evaluar Compromiso
                  </button> -->
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="exportarInteraccionesPDF()">
                    <i class="ti ti-file-type-pdf me-1"></i>
                    Generar PDF
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerarSegmentacion">
                    <i class="ti ti-chart-pie me-1"></i>
                    Generar Segmentación
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Tabla de clasificación -->
                <div class="dt-responsive table-responsive">
                  <table
                    id="clasificacion-table"
                    class="table table-striped table-bordered nowrap"
                  >
                    <thead>
                      <tr>
                        <th width="4%">ID</th>
                        <th width="14%">Apoderado</th>
                        <th width="10%">Familia</th>
                        <th width="12%">Categoría</th>
                        <th width="8%">Compromiso</th>
                        <th width="8%">Participación</th>
                        <th width="8%">Puntuación</th>
                        <th width="10%">Métricas</th>
                        <th width="8%">Última Interacción</th>
                        <th width="8%">Contacto Pref.</th>
                        <th width="10%">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              // Formatear fechas
                              $fecha_actualizacion = date('d/m/Y', strtotime($row['updated_at']));
                              
                              // Determinar clase de categoría
                              $categoria = $row['categoria_apoderado'] ?? 'regular';
                              $categoria_class = 'categoria-' . $categoria;
                              
                              // Determinar clase de compromiso
                              $compromiso = $row['nivel_compromiso'] ?? 'medio';
                              $compromiso_class = 'compromiso-' . $compromiso;
                              
                              // Determinar clase de participación
                              $participacion = $row['nivel_participacion'] ?? 'activo';
                              $participacion_class = 'participacion-' . $participacion;
                              
                              // Determinar clase de puntuación
                              $puntuacion = (float)$row['puntuacion_compromiso'];
                              if ($puntuacion >= 80) $score_class = 'score-excelente';
                              elseif ($puntuacion >= 60) $score_class = 'score-bueno';
                              elseif ($puntuacion >= 40) $score_class = 'score-regular';
                              else $score_class = 'score-bajo';
                              
                              // Última interacción
                              $dias_ultima = $row['dias_ultima_interaccion'];
                              if ($dias_ultima === null) {
                                  $interaccion_class = 'sin-interaccion';
                                  $interaccion_text = 'Sin interacciones';
                              } elseif ($dias_ultima <= 7) {
                                  $interaccion_class = 'interaccion-reciente';
                                  $interaccion_text = 'Hace ' . $dias_ultima . ' días';
                              } elseif ($dias_ultima <= 30) {
                                  $interaccion_class = 'interaccion-antigua';
                                  $interaccion_text = 'Hace ' . $dias_ultima . ' días';
                              } else {
                                  $interaccion_class = 'interaccion-muy_antigua';
                                  $interaccion_text = 'Hace ' . $dias_ultima . ' días';
                              }
                              
                              echo "<tr>";
                              echo "<td><strong>" . $row['id'] . "</strong></td>";
                              echo "<td>
                                      <div class='apoderado-info'>
                                        <span class='apoderado-nombre'>" . htmlspecialchars($row['nombre_completo']) . "</span>
                                        <span class='apoderado-contacto'>" . htmlspecialchars($row['email'] ?? 'Sin email') . "</span>
                                        <span class='apoderado-contacto'>" . htmlspecialchars($row['telefono_principal'] ?? 'Sin teléfono') . "</span>
                                      </div>
                                    </td>";
                              echo "<td>
                                      <div class='familia-info'>
                                        <span class='familia-codigo'>" . htmlspecialchars($row['codigo_familia'] ?? '') . "</span>
                                        <span class='apoderado-contacto'>Fam. " . htmlspecialchars($row['familia_apellido'] ?? '') . "</span>
                                        " . ($row['nivel_socioeconomico'] ? "<span class='badge badge-nivel-socio nivel-" . $row['nivel_socioeconomico'] . "'>" . $row['nivel_socioeconomico'] . "</span>" : "") . "
                                      </div>
                                    </td>";
                              echo "<td><span class='badge badge-categoria $categoria_class'>" . 
                                   ucwords(str_replace('_', ' ', $categoria)) . "</span></td>";
                              echo "<td><span class='badge badge-compromiso $compromiso_class'>" . 
                                   ucfirst($compromiso) . "</span></td>";
                              echo "<td><span class='badge badge-participacion $participacion_class'>" . 
                                   ucwords(str_replace('_', ' ', $participacion)) . "</span></td>";
                              echo "<td><span class='puntuacion-score $score_class'>" . 
                                   number_format($puntuacion, 1) . "%</span></td>";
                              echo "<td>
                                      <div class='metricas-info'>
                                        <span class='metrica-principal'>" . $row['total_interacciones'] . " total</span>
                                        <span class='metrica-secundaria'>" . $row['interacciones_recientes'] . " (90d)</span>
                                        <span class='metrica-secundaria'>" . $row['interacciones_exitosas'] . " exitosas</span>
                                      </div>
                                    </td>";
                              echo "<td><span class='ultima-interaccion $interaccion_class'>" . 
                                   $interaccion_text . "</span></td>";
                              echo "<td><span class='preferencia-contacto'>" . 
                                   ucfirst($row['preferencia_contacto'] ?? 'email') . "</span></td>";
                              echo "<td>
                                      <div class='btn-grupo-clasificacion'>
                                        <button type='button' class='btn btn-outline-success btn-evaluar-compromiso' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_completo']) . "'
                                                title='Evaluar Compromiso'>
                                          <i class='ti ti-heart'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-info btn-medir-participacion' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_completo']) . "'
                                                title='Medir Participación'>
                                          <i class='ti ti-activity'></i>
                                        </button>
                                        <button type='button' class='btn btn-outline-primary btn-actualizar-clasificacion' 
                                                data-id='" . $row['id'] . "'
                                                data-nombre='" . htmlspecialchars($row['nombre_completo']) . "'
                                                title='Actualizar Clasificación Completa'>
                                          <i class='ti ti-edit'></i>
                                        </button>
                                      </div>
                                    </td>";
                              echo "</tr>";
                          }
                      } else {
                          echo "<tr><td colspan='11' class='text-center'>No hay apoderados para clasificar</td></tr>";
                      }
                      ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>ID</th>
                        <th>Apoderado</th>
                        <th>Familia</th>
                        <th>Categoría</th>
                        <th>Compromiso</th>
                        <th>Participación</th>
                        <th>Puntuación</th>
                        <th>Métricas</th>
                        <th>Última Interacción</th>
                        <th>Contacto Pref.</th>
                        <th>Acciones</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </section>

    <!-- Incluir Modales -->
    <?php include 'modals/clasificacion/modal_generar_segmentacion.php'; ?>
    <?php include 'modals/clasificacion/modal_evaluar_compromiso.php'; ?>
    <?php include 'modals/clasificacion/modal_medir_participacion.php'; ?>
    <?php include 'modals/clasificacion/modal_actualizar_clasificacion.php'; ?>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Required Js -->
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

    <!-- [Page Specific JS] start -->
    <!-- datatable Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    
    <script>
      $(document).ready(function() {
            // Inicializar DataTable con filtros integrados
            var table = $("#clasificacion-table").DataTable({
              "language": {
                "decimal": "",
                "emptyTable": "No hay apoderados disponibles para clasificar",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                  "first": "Primero",
                  "last": "Último",
                  "next": "Siguiente",
                  "previous": "Anterior"
                },
                "aria": {
                  "sortAscending": ": activar para ordenar la columna ascendente",
                  "sortDescending": ": activar para ordenar la columna descendente"
                }
              },
              "pageLength": 25,
              "order": [[ 6, "desc" ]], // Ordenar por puntuación descendente
              "columnDefs": [
                { "orderable": false, "targets": 10 } // Deshabilitar ordenación en columna de acciones
              ],
              "initComplete": function () {
                // Configurar filtros después de que la tabla esté completamente inicializada
                this.api().columns().every(function (index) {
                  var column = this;
                  
                  // Solo aplicar filtros a las primeras 10 columnas (sin acciones)
                  if (index < 10) {
                    var title = $(column.header()).text();
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar ' + title + '" />')
                      .appendTo($(column.footer()).empty())
                      .on('keyup change clear', function () {
                        if (column.search() !== this.value) {
                          column
                            .search(this.value)
                            .draw();
                        }
                      });
                  } else {
                    // Agregar "ACCIONES" en negrita en la columna de acciones
                    $(column.footer()).html('<strong>Acciones</strong>');
                  }
                });
              }
            });

            // Función para recalcular todas las clasificaciones
            window.recalcularTodo = function() {
              if (confirm('¿Está seguro de recalcular TODAS las clasificaciones? Este proceso puede tardar varios minutos.')) {
                $.ajax({
                  url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                  method: 'POST',
                  data: { accion: 'recalcular_clasificaciones' },
                  success: function(response) {
                    alert('Recálculo completado');
                    location.reload();
                  },
                  error: function() {
                    alert('Error en el recálculo masivo');
                  }
                });
              }
            };

            // Función para filtrar por categoría
            window.filtrarPorCategoria = function(categoria) {
              table.column(3).search(categoria.replace('_', ' ')).draw();
            };

            // Manejar click en botón evaluar compromiso
            $(document).on('click', '.btn-evaluar-compromiso', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#evaluar_apoderado_id').val(id);
                $('#evaluar_apoderado_nombre').text(nombre);
                $('#modalEvaluarCompromiso').modal('show');
            });

            // Manejar click en botón medir participación
            $(document).on('click', '.btn-medir-participacion', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#medir_apoderado_id').val(id);
                $('#medir_apoderado_nombre').text(nombre);
                $('#modalMedirParticipacion').modal('show');
            });

            // Manejar click en botón actualizar clasificación
            $(document).on('click', '.btn-actualizar-clasificacion', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#clasificacion_apoderado_id').val(id);
                $('#clasificacion_apoderado_nombre').text(nombre);
                cargarDatosClasificacion(id);
                $('#modalActualizarClasificacion').modal('show');
            });

            // Función para cargar datos actuales de clasificación
            function cargarDatosClasificacion(id) {
              // Obtener datos actuales del apoderado desde la tabla
              var fila = $('button[data-id="' + id + '"]').closest('tr');
              var datos = table.row(fila).data();
              
              // Pre-llenar formulario con datos actuales
              // Esta funcionalidad se implementaría en el modal correspondiente
            }

            // Auto-refresh cada 5 minutos para actualizar métricas
            setInterval(function() {
              // Solo recargar estadísticas sin afectar filtros
              actualizarEstadisticas();
            }, 300000); // 5 minutos

            // Función para actualizar solo estadísticas
            function actualizarEstadisticas() {
              $.ajax({
                url: 'actions/obtener_estadisticas_clasificacion.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                  if (response.success) {
                    // Actualizar números en las tarjetas de estadísticas
                    $('.stats-card .stat-number').each(function(index) {
                      var keys = ['total_apoderados', 'colaboradores_estrella', 'alto_compromiso', 'muy_activos', 'problematicos', 'tasa_positiva'];
                      if (keys[index] && response.data[keys[index]] !== undefined) {
                        $(this).text(response.data[keys[index]]);
                      }
                    });
                  }
                },
                error: function() {
                  console.log('Error al actualizar estadísticas');
                }
              });
            }

            // Tooltip para elementos
            $('[title]').tooltip();
      });
    </script>
    <!-- [Page Specific JS] end -->
    <script src="assets/js/mensajes_sistema.js"></script>
  </body>
  <!-- [Body] end -->
</html>

<?php
// Cerrar conexión
$conn->close();
?>