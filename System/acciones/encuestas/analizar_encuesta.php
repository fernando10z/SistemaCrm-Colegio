<?php
session_start();
require_once '../../bd/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de encuesta no proporcionado'
    ]);
    exit();
}

$encuesta_id = intval($_GET['id']);

try {
    // Obtener información de la encuesta
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, tipo, dirigido_a, preguntas, 
               fecha_inicio, fecha_fin, activo
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
    
    // Decodificar preguntas
    $preguntas = json_decode($encuesta['preguntas'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar preguntas de la encuesta');
    }
    
    // Obtener todas las respuestas
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.respuestas,
            r.puntaje_calculado,
            r.fecha_respuesta,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN 'Padres'
                WHEN r.lead_id IS NOT NULL THEN 'Leads'
                WHEN r.exalumno_id IS NOT NULL THEN 'Ex-alumnos'
                ELSE 'Estudiantes'
            END as tipo_usuario
        FROM respuestas_encuesta r
        WHERE r.encuesta_id = ?
        ORDER BY r.fecha_respuesta DESC
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result_respuestas = $stmt->get_result();
    
    $respuestas = [];
    while ($row = $result_respuestas->fetch_assoc()) {
        $respuestas[] = $row;
    }
    $stmt->close();
    
    $total_respuestas = count($respuestas);
    
    // Si no hay respuestas, devolver estructura vacía
    if ($total_respuestas === 0) {
        echo json_encode([
            'success' => true,
            'data' => [
                'encuesta' => [
                    'titulo' => $encuesta['titulo'],
                    'descripcion' => $encuesta['descripcion'],
                    'tipo' => $encuesta['tipo'],
                    'dirigido_a' => $encuesta['dirigido_a'],
                    'fecha_inicio' => $encuesta['fecha_inicio'],
                    'fecha_fin' => $encuesta['fecha_fin']
                ],
                'total_respuestas' => 0,
                'promedio_general' => 0,
                'distribucion_usuarios' => [],
                'preguntas_analisis' => []
            ]
        ]);
        exit();
    }
    
    // Procesar estadísticas
    $analisis = procesarAnalisis($preguntas, $respuestas);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'encuesta' => [
                'titulo' => $encuesta['titulo'],
                'descripcion' => $encuesta['descripcion'],
                'tipo' => $encuesta['tipo'],
                'dirigido_a' => $encuesta['dirigido_a'],
                'fecha_inicio' => $encuesta['fecha_inicio'],
                'fecha_fin' => $encuesta['fecha_fin']
            ],
            'total_respuestas' => $total_respuestas,
            'promedio_general' => $analisis['promedio_general'],
            'distribucion_usuarios' => $analisis['distribucion_usuarios'],
            'preguntas_analisis' => $analisis['preguntas_analisis']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function procesarAnalisis($preguntas, $respuestas) {
    $total_respuestas = count($respuestas);
    
    // Distribución por tipo de usuario
    $distribucion = [
        'Padres' => 0,
        'Estudiantes' => 0,
        'Ex-alumnos' => 0,
        'Leads' => 0
    ];
    
    foreach ($respuestas as $respuesta) {
        if (isset($distribucion[$respuesta['tipo_usuario']])) {
            $distribucion[$respuesta['tipo_usuario']]++;
        }
    }
    
    // Eliminar tipos sin respuestas
    $distribucion = array_filter($distribucion, function($v) { return $v > 0; });
    
    // Procesar cada pregunta
    $preguntas_analisis = [];
    $suma_promedios = 0;
    $preguntas_con_promedio = 0;
    
    foreach ($preguntas as $pregunta) {
        $analisis_pregunta = analizarPregunta($pregunta, $respuestas);
        $preguntas_analisis[] = $analisis_pregunta;
        
        // Para promedio general (solo preguntas cuantificables)
        if (isset($analisis_pregunta['promedio']) && $analisis_pregunta['promedio'] > 0) {
            $suma_promedios += $analisis_pregunta['promedio'];
            $preguntas_con_promedio++;
        }
    }
    
    $promedio_general = $preguntas_con_promedio > 0 
        ? $suma_promedios / $preguntas_con_promedio 
        : 0;
    
    return [
        'promedio_general' => $promedio_general,
        'distribucion_usuarios' => $distribucion,
        'preguntas_analisis' => $preguntas_analisis
    ];
}

function analizarPregunta($pregunta, $respuestas) {
    $tipo = $pregunta['tipo'];
    $pregunta_id = $pregunta['id'];
    
    // Recopilar todas las respuestas a esta pregunta
    $respuestas_pregunta = [];
    foreach ($respuestas as $respuesta) {
        $respuestas_data = json_decode($respuesta['respuestas'], true);
        if (json_last_error() === JSON_ERROR_NONE && isset($respuestas_data[$pregunta_id])) {
            $respuestas_pregunta[] = $respuestas_data[$pregunta_id];
        }
    }
    
    $total_respuestas = count($respuestas_pregunta);
    
    $analisis = [
        'pregunta' => $pregunta['pregunta'],
        'tipo' => $tipo,
        'total_respuestas' => $total_respuestas
    ];
    
    // Análisis según tipo
    switch ($tipo) {
        case 'text':
            $analisis['respuestas'] = array_filter($respuestas_pregunta, function($r) {
                return !empty(trim($r));
            });
            break;
            
        case 'select':
        case 'radio':
        case 'checkbox':
        case 'escala':
        case 'si_no':
            $estadisticas = [];
            
            // Inicializar opciones
            if (isset($pregunta['opciones'])) {
                foreach ($pregunta['opciones'] as $opcion) {
                    $estadisticas[$opcion] = ['cantidad' => 0, 'porcentaje' => 0];
                }
            }
            
            // Para si_no
            if ($tipo === 'si_no') {
                $estadisticas = [
                    'Sí' => ['cantidad' => 0, 'porcentaje' => 0],
                    'No' => ['cantidad' => 0, 'porcentaje' => 0]
                ];
            }
            
            // Contar respuestas
            foreach ($respuestas_pregunta as $respuesta) {
                if ($tipo === 'checkbox' && is_array($respuesta)) {
                    // Checkbox puede tener múltiples valores
                    foreach ($respuesta as $valor) {
                        if (isset($estadisticas[$valor])) {
                            $estadisticas[$valor]['cantidad']++;
                        } else {
                            $estadisticas[$valor] = ['cantidad' => 1, 'porcentaje' => 0];
                        }
                    }
                } else {
                    // Otros tipos tienen un solo valor
                    if (isset($estadisticas[$respuesta])) {
                        $estadisticas[$respuesta]['cantidad']++;
                    } else {
                        $estadisticas[$respuesta] = ['cantidad' => 1, 'porcentaje' => 0];
                    }
                }
            }
            
            // Calcular porcentajes
            $total_conteo = array_sum(array_column($estadisticas, 'cantidad'));
            if ($total_conteo > 0) {
                foreach ($estadisticas as $opcion => &$datos) {
                    $datos['porcentaje'] = ($datos['cantidad'] / $total_conteo) * 100;
                }
            }
            
            $analisis['estadisticas'] = $estadisticas;
            
            // Calcular promedio para escalas
            if ($tipo === 'escala' || $tipo === 'rating') {
                $analisis['promedio'] = calcularPromedioEscala($estadisticas);
            }
            
            break;
            
        case 'rating':
            $estadisticas = [
                '1' => ['cantidad' => 0, 'porcentaje' => 0],
                '2' => ['cantidad' => 0, 'porcentaje' => 0],
                '3' => ['cantidad' => 0, 'porcentaje' => 0],
                '4' => ['cantidad' => 0, 'porcentaje' => 0],
                '5' => ['cantidad' => 0, 'porcentaje' => 0]
            ];
            
            foreach ($respuestas_pregunta as $respuesta) {
                $rating = strval($respuesta);
                if (isset($estadisticas[$rating])) {
                    $estadisticas[$rating]['cantidad']++;
                }
            }
            
            // Calcular porcentajes
            if ($total_respuestas > 0) {
                foreach ($estadisticas as &$datos) {
                    $datos['porcentaje'] = ($datos['cantidad'] / $total_respuestas) * 100;
                }
            }
            
            $analisis['estadisticas'] = $estadisticas;
            $analisis['promedio'] = calcularPromedioRating($respuestas_pregunta);
            
            break;
    }
    
    return $analisis;
}

function calcularPromedioEscala($estadisticas) {
    // Mapeo de escalas a valores numéricos
    $valores = [
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
        'Neutral' => 3,
        'Probablemente No' => 2,
        'Definitivamente No' => 1
    ];
    
    $suma = 0;
    $total = 0;
    
    foreach ($estadisticas as $opcion => $datos) {
        if (isset($valores[$opcion])) {
            $suma += $valores[$opcion] * $datos['cantidad'];
            $total += $datos['cantidad'];
        }
    }
    
    return $total > 0 ? $suma / $total : 0;
}

function calcularPromedioRating($respuestas) {
    $suma = 0;
    $total = 0;
    
    foreach ($respuestas as $respuesta) {
        $valor = intval($respuesta);
        if ($valor >= 1 && $valor <= 5) {
            $suma += $valor;
            $total++;
        }
    }
    
    return $total > 0 ? $suma / $total : 0;
}
?>