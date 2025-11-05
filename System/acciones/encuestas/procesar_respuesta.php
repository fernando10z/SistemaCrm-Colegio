<?php
require_once '../../bd/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit();
}

try {
    $encuesta_id = intval($_POST['encuesta_id'] ?? 0);
    $respuestas_json = $_POST['respuestas'] ?? '';
    
    if ($encuesta_id <= 0) {
        throw new Exception('ID de encuesta no válido');
    }
    
    if (empty($respuestas_json)) {
        throw new Exception('No se recibieron respuestas');
    }
    
    // Decodificar respuestas
    $respuestas = json_decode($respuestas_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al procesar las respuestas');
    }
    
    // Validar que la encuesta existe y está activa
    $stmt = $conn->prepare("
        SELECT id, titulo, preguntas, activo, fecha_inicio, fecha_fin
        FROM encuestas 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Encuesta no encontrada');
    }
    
    $encuesta = $result->fetch_assoc();
    $stmt->close();
    
    // Validar que esté activa
    if (!$encuesta['activo']) {
        throw new Exception('Esta encuesta no está activa');
    }
    
    // Validar fechas
    $hoy = date('Y-m-d');
    if ($hoy < $encuesta['fecha_inicio']) {
        throw new Exception('Esta encuesta aún no ha comenzado');
    }
    if ($encuesta['fecha_fin'] && $hoy > $encuesta['fecha_fin']) {
        throw new Exception('Esta encuesta ha finalizado');
    }
    
    // Decodificar preguntas de la encuesta
    $preguntas = json_decode($encuesta['preguntas'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al procesar la estructura de la encuesta');
    }
    
    // Validar preguntas requeridas
    foreach ($preguntas as $pregunta) {
        if ($pregunta['requerida']) {
            if (!isset($respuestas[$pregunta['id']]) || empty($respuestas[$pregunta['id']])) {
                throw new Exception('Por favor completa todas las preguntas obligatorias');
            }
        }
    }
    
    // Calcular puntaje (si hay preguntas cuantificables)
    $puntaje = calcularPuntaje($preguntas, $respuestas);
    
    // Obtener IP del usuario
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Convertir respuestas a JSON
    $respuestas_json_final = json_encode($respuestas, JSON_UNESCAPED_UNICODE);
    
    // Insertar respuesta en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO respuestas_encuesta (
            encuesta_id, 
            apoderado_id, 
            lead_id, 
            exalumno_id, 
            respuestas, 
            puntaje_calculado, 
            ip_respuesta,
            fecha_respuesta
        ) VALUES (?, NULL, NULL, NULL, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("isds", $encuesta_id, $respuestas_json_final, $puntaje, $ip);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar las respuestas: ' . $stmt->error);
    }
    
    $respuesta_id = $conn->insert_id;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Respuestas guardadas exitosamente',
        'respuesta_id' => $respuesta_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}

function calcularPuntaje($preguntas, $respuestas) {
    $suma_puntos = 0;
    $total_preguntas_cuantificables = 0;
    
    // Mapeo de valores para escalas
    $valores_escala = [
        'Excelente' => 5,
        'Muy Bueno' => 4,
        'Bueno' => 3,
        'Regular' => 2,
        'Deficiente' => 1,
        'Muy Satisfecho' => 5,
        'Satisfecho' => 4,
        'Neutral' => 3,
        'Insatisfecho' => 2,
        'Muy Insatisfecho' => 1,
        'Definitivamente Sí' => 5,
        'Probablemente Sí' => 4,
        'Probablemente No' => 2,
        'Definitivamente No' => 1,
        'Sí' => 5,
        'No' => 1
    ];
    
    foreach ($preguntas as $pregunta) {
        $pregunta_id = $pregunta['id'];
        $tipo = $pregunta['tipo'];
        
        // Solo calcular para tipos cuantificables
        if (!in_array($tipo, ['rating', 'escala', 'si_no'])) {
            continue;
        }
        
        if (!isset($respuestas[$pregunta_id])) {
            continue;
        }
        
        $respuesta = $respuestas[$pregunta_id];
        
        if ($tipo === 'rating') {
            // Rating es directo (1-5)
            $valor = intval($respuesta);
            if ($valor >= 1 && $valor <= 5) {
                $suma_puntos += $valor;
                $total_preguntas_cuantificables++;
            }
        } elseif ($tipo === 'escala' || $tipo === 'si_no') {
            // Buscar valor en el mapeo
            if (isset($valores_escala[$respuesta])) {
                $suma_puntos += $valores_escala[$respuesta];
                $total_preguntas_cuantificables++;
            }
        }
    }
    
    // Calcular promedio
    if ($total_preguntas_cuantificables > 0) {
        return round($suma_puntos / $total_preguntas_cuantificables, 2);
    }
    
    return null;
}
?>