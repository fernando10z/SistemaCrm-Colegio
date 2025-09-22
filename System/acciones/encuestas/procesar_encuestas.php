<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Obtener la acción
$accion = $_POST['accion'] ?? '';

// Procesar según la acción
switch ($accion) {
    case 'crear_encuesta':
        echo json_encode(procesarCrearEncuesta($conn, $_POST));
        break;
        
    case 'enviar_encuesta':
        echo json_encode(procesarEnviarEncuesta($conn, $_POST));
        break;
        
    case 'actualizar_estado_encuesta':
        echo json_encode(procesarActualizarEstado($conn, $_POST));
        break;
        
    case 'procesar_respuesta':
        echo json_encode(procesarRespuestaEncuesta($conn, $_POST));
        break;
        
    case 'previsualizar_destinatarios':
        echo json_encode(previsualizarDestinatarios($conn, $_POST));
        break;
        
    case 'obtener_analisis_encuesta':
        echo json_encode(obtenerAnalisisEncuesta($conn, $_POST));
        break;
        
    case 'obtener_respuestas_encuesta':
        echo json_encode(obtenerRespuestasEncuesta($conn, $_POST));
        break;
        
    case 'obtener_detalle_respuesta':
        echo json_encode(obtenerDetalleRespuesta($conn, $_POST));
        break;
        
    case 'exportar_respuestas':
        exportarRespuestas($conn, $_POST);
        break;
        
    case 'eliminar_respuesta':
        echo json_encode(eliminarRespuesta($conn, $_POST));
        break;
        
    case 'eliminar_respuestas_masivo':
        echo json_encode(eliminarRespuestasMasivo($conn, $_POST));
        break;
        
    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}

// ======================= FUNCIONES =======================

function procesarCrearEncuesta($conn, $data) {
    try {
        $titulo = $conn->real_escape_string($data['titulo']);
        $descripcion = $conn->real_escape_string($data['descripcion'] ?? '');
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
                    'tipo' => $pregunta['tipo'],
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
            return [
                'success' => true, 
                'mensaje' => "Encuesta '$titulo' creada correctamente",
                'encuesta_id' => $encuesta_id
            ];
        } else {
            return ['success' => false, 'mensaje' => 'Error al crear la encuesta: ' . $conn->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

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
                return ['success' => false, 'mensaje' => 'Tipo de destinatario no válido'];
        }
        
        // Aplicar filtros adicionales
        if (!empty($filtros['grado_id']) && $dirigido_a == 'estudiantes') {
            $grado_id = $conn->real_escape_string($filtros['grado_id']);
            $sql_dest .= " AND e.grado_id = $grado_id";
        }
        
        if (!empty($filtros['promocion']) && $dirigido_a == 'exalumnos') {
            $promocion = $conn->real_escape_string($filtros['promocion']);
            $sql_dest .= " AND ex.promocion_egreso = '$promocion'";
        }
        
        $result_dest = $conn->query($sql_dest);
        $total_enviados = 0;
        
        if ($result_dest && $result_dest->num_rows > 0) {
            while ($dest = $result_dest->fetch_assoc()) {
                // Aquí simularemos el envío de email
                $link_unico = generateUniqueToken();
                
                // Registrar envío en la tabla de mensajes
                $mensaje_sql = "INSERT INTO mensajes_enviados (
                                    tipo, destinatario_email, asunto, contenido, 
                                    estado, fecha_envio
                                ) VALUES (
                                    'email', 
                                    '" . $conn->real_escape_string($dest['email']) . "',
                                    'Nueva encuesta disponible',
                                    'Estimado/a " . $conn->real_escape_string($dest['nombre']) . ", tiene una nueva encuesta disponible. Link: $link_unico',
                                    'enviado',
                                    NOW()
                                )";
                $conn->query($mensaje_sql);
                $total_enviados++;
            }
            
            // Actualizar estado de la encuesta
            $conn->query("UPDATE encuestas SET activo = 1 WHERE id = $encuesta_id");
            
            return [
                'success' => true, 
                'mensaje' => "Encuesta enviada exitosamente a $total_enviados destinatarios"
            ];
        } else {
            return ['success' => false, 'mensaje' => 'No se encontraron destinatarios válidos'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function procesarActualizarEstado($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $nuevo_estado = isset($data['activo']) ? 1 : 0;
        
        $sql = "UPDATE encuestas SET activo = $nuevo_estado WHERE id = $encuesta_id";
        
        if ($conn->query($sql)) {
            $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
            return ['success' => true, 'mensaje' => "Encuesta $estado_texto correctamente"];
        } else {
            return ['success' => false, 'mensaje' => 'Error al actualizar el estado: ' . $conn->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

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
            return ['success' => true, 'mensaje' => 'Respuesta registrada correctamente'];
        } else {
            return ['success' => false, 'mensaje' => 'Error al registrar la respuesta: ' . $conn->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function previsualizarDestinatarios($conn, $data) {
    try {
        $dirigido_a = $conn->real_escape_string($data['dirigido_a']);
        $filtros = $data['filtros'] ?? [];
        
        switch ($dirigido_a) {
            case 'padres':
                $sql = "SELECT DISTINCT a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                       FROM apoderados a 
                       WHERE a.activo = 1 AND a.email IS NOT NULL AND a.email != ''";
                break;
                
            case 'estudiantes':
                $sql = "SELECT DISTINCT a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                       FROM apoderados a 
                       INNER JOIN estudiantes e ON a.familia_id = e.familia_id
                       WHERE a.activo = 1 AND e.activo = 1 AND a.email IS NOT NULL AND a.email != ''";
                break;
                
            case 'exalumnos':
                $sql = "SELECT DISTINCT ex.email, CONCAT(ex.nombres, ' ', ex.apellidos) as nombre
                       FROM exalumnos ex 
                       WHERE ex.estado_contacto = 'activo' AND ex.email IS NOT NULL AND ex.email != ''";
                break;
                
            default:
                return ['success' => false, 'mensaje' => 'Tipo de destinatario no válido'];
        }
        
        // Aplicar filtros
        if (!empty($filtros['grado_id']) && $dirigido_a == 'estudiantes') {
            $grado_id = $conn->real_escape_string($filtros['grado_id']);
            $sql .= " AND e.grado_id = $grado_id";
        }
        
        if (!empty($filtros['promocion']) && $dirigido_a == 'exalumnos') {
            $promocion = $conn->real_escape_string($filtros['promocion']);
            $sql .= " AND ex.promocion_egreso = '$promocion'";
        }
        
        $sql .= " LIMIT 50"; // Limitar para previsualización
        
        $result = $conn->query($sql);
        $destinatarios = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $destinatarios[] = $row;
            }
        }
        
        // Obtener total sin límite
        $sql_count = str_replace('SELECT DISTINCT a.email, CONCAT(a.nombres, \' \', a.apellidos) as nombre', 'SELECT COUNT(DISTINCT a.id)', $sql);
        $sql_count = str_replace('SELECT DISTINCT ex.email, CONCAT(ex.nombres, \' \', ex.apellidos) as nombre', 'SELECT COUNT(DISTINCT ex.id)', $sql_count);
        $sql_count = str_replace(' LIMIT 50', '', $sql_count);
        
        $result_count = $conn->query($sql_count);
        $total = $result_count ? $result_count->fetch_row()[0] : 0;
        
        return [
            'success' => true,
            'destinatarios' => $destinatarios,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function obtenerAnalisisEncuesta($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $fecha_desde = $data['fecha_desde'] ?? null;
        $fecha_hasta = $data['fecha_hasta'] ?? null;
        
        // Obtener información de la encuesta
        $sql_encuesta = "SELECT * FROM encuestas WHERE id = $encuesta_id";
        $result_encuesta = $conn->query($sql_encuesta);
        
        if (!$result_encuesta || $result_encuesta->num_rows == 0) {
            return ['success' => false, 'mensaje' => 'Encuesta no encontrada'];
        }
        
        $encuesta = $result_encuesta->fetch_assoc();
        
        // Construir condición de fecha
        $fecha_condicion = '';
        if ($fecha_desde && $fecha_hasta) {
            $fecha_desde = $conn->real_escape_string($fecha_desde);
            $fecha_hasta = $conn->real_escape_string($fecha_hasta);
            $fecha_condicion = " AND DATE(re.fecha_respuesta) BETWEEN '$fecha_desde' AND '$fecha_hasta'";
        } elseif ($fecha_desde) {
            $fecha_desde = $conn->real_escape_string($fecha_desde);
            $fecha_condicion = " AND DATE(re.fecha_respuesta) >= '$fecha_desde'";
        } elseif ($fecha_hasta) {
            $fecha_hasta = $conn->real_escape_string($fecha_hasta);
            $fecha_condicion = " AND DATE(re.fecha_respuesta) <= '$fecha_hasta'";
        }
        
        // Obtener estadísticas generales
        $sql_stats = "SELECT 
                        COUNT(*) as total_respuestas,
                        AVG(puntaje_calculado) as promedio_puntaje,
                        MIN(puntaje_calculado) as puntaje_minimo,
                        MAX(puntaje_calculado) as puntaje_maximo,
                        MAX(fecha_respuesta) as ultima_respuesta
                      FROM respuestas_encuesta re 
                      WHERE re.encuesta_id = $encuesta_id $fecha_condicion";
        
        $result_stats = $conn->query($sql_stats);
        $estadisticas = $result_stats->fetch_assoc();
        
        // Calcular tasa de respuesta (estimada)
        $estadisticas['tasa_respuesta'] = $estadisticas['total_respuestas'] > 0 ? 
            round(($estadisticas['total_respuestas'] / max(100, $estadisticas['total_respuestas'])) * 100, 1) : 0;
        
        // Calcular satisfacción general
        $estadisticas['satisfaccion_general'] = $estadisticas['promedio_puntaje'] ? 
            round(($estadisticas['promedio_puntaje'] / 5) * 100, 1) : 0;
        
        // Obtener análisis por preguntas
        $preguntas = json_decode($encuesta['preguntas'], true);
        $preguntas_analisis = [];
        
        foreach ($preguntas as $index => $pregunta) {
            $preguntas_analisis[] = [
                'texto' => $pregunta['pregunta'],
                'tipo' => $pregunta['tipo'],
                'total_respuestas' => $estadisticas['total_respuestas'],
                'promedio' => $pregunta['tipo'] === 'rating' ? $estadisticas['promedio_puntaje'] : null
            ];
        }
        
        // Obtener comentarios (respuestas de texto libre)
        $sql_comentarios = "SELECT 
                              re.respuestas, 
                              re.fecha_respuesta,
                              CONCAT(COALESCE(a.nombres, ''), ' ', COALESCE(a.apellidos, '')) as respondente
                            FROM respuestas_encuesta re
                            LEFT JOIN apoderados a ON re.apoderado_id = a.id
                            WHERE re.encuesta_id = $encuesta_id $fecha_condicion
                            ORDER BY re.fecha_respuesta DESC
                            LIMIT 50";
        
        $result_comentarios = $conn->query($sql_comentarios);
        $comentarios = [];
        
        if ($result_comentarios) {
            while ($row = $result_comentarios->fetch_assoc()) {
                $respuestas_json = json_decode($row['respuestas'], true);
                
                // Extraer comentarios de texto libre
                if (is_array($respuestas_json)) {
                    foreach ($respuestas_json as $respuesta) {
                        if (is_string($respuesta) && strlen(trim($respuesta)) > 10) {
                            $comentarios[] = [
                                'texto' => $respuesta,
                                'fecha' => date('d/m/Y H:i', strtotime($row['fecha_respuesta'])),
                                'respondente' => trim($row['respondente']) ?: 'Anónimo',
                                'sentimiento' => determinarSentimiento($respuesta)
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'datos' => [
                'estadisticas' => $estadisticas,
                'preguntas' => $preguntas_analisis,
                'comentarios' => $comentarios
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function obtenerRespuestasEncuesta($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $pagina = intval($data['pagina'] ?? 1);
        $por_pagina = intval($data['por_pagina'] ?? 25);
        $offset = ($pagina - 1) * $por_pagina;
        
        // Obtener información de la encuesta
        $sql_encuesta = "SELECT titulo, descripcion FROM encuestas WHERE id = $encuesta_id";
        $result_encuesta = $conn->query($sql_encuesta);
        $encuesta = $result_encuesta->fetch_assoc();
        
        // Contar total de respuestas
        $sql_count = "SELECT COUNT(*) as total FROM respuestas_encuesta WHERE encuesta_id = $encuesta_id";
        $result_count = $conn->query($sql_count);
        $total_respuestas = $result_count->fetch_assoc()['total'];
        
        // Obtener respuestas paginadas
        $sql_respuestas = "SELECT 
                            re.id,
                            re.puntaje_calculado,
                            re.fecha_respuesta,
                            re.ip_respuesta,
                            CONCAT(COALESCE(a.nombres, ''), ' ', COALESCE(a.apellidos, '')) as respondente_nombre,
                            a.email as respondente_email,
                            CASE 
                                WHEN re.puntaje_calculado IS NOT NULL THEN 'completa'
                                ELSE 'incompleta'
                            END as estado
                          FROM respuestas_encuesta re
                          LEFT JOIN apoderados a ON re.apoderado_id = a.id
                          WHERE re.encuesta_id = $encuesta_id
                          ORDER BY re.fecha_respuesta DESC
                          LIMIT $por_pagina OFFSET $offset";
        
        $result_respuestas = $conn->query($sql_respuestas);
        $respuestas = [];
        
        if ($result_respuestas) {
            while ($row = $result_respuestas->fetch_assoc()) {
                $row['fecha_respuesta'] = date('d/m/Y H:i', strtotime($row['fecha_respuesta']));
                $respuestas[] = $row;
            }
        }
        
        $total_paginas = ceil($total_respuestas / $por_pagina);
        
        return [
            'success' => true,
            'encuesta' => array_merge($encuesta, ['total_respuestas' => $total_respuestas]),
            'respuestas' => $respuestas,
            'total_paginas' => $total_paginas,
            'paginacion' => [
                'pagina_actual' => $pagina,
                'total_paginas' => $total_paginas,
                'total' => $total_respuestas,
                'desde' => $offset + 1,
                'hasta' => min($offset + $por_pagina, $total_respuestas)
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function obtenerDetalleRespuesta($conn, $data) {
    try {
        $respuesta_id = $conn->real_escape_string($data['respuesta_id']);
        
        // Obtener respuesta con detalles
        $sql = "SELECT 
                    re.*,
                    e.preguntas,
                    CONCAT(COALESCE(a.nombres, ''), ' ', COALESCE(a.apellidos, '')) as respondente_nombre,
                    a.email as respondente_email
                FROM respuestas_encuesta re
                INNER JOIN encuestas e ON re.encuesta_id = e.id
                LEFT JOIN apoderados a ON re.apoderado_id = a.id
                WHERE re.id = $respuesta_id";
        
        $result = $conn->query($sql);
        
        if (!$result || $result->num_rows == 0) {
            return ['success' => false, 'mensaje' => 'Respuesta no encontrada'];
        }
        
        $respuesta = $result->fetch_assoc();
        
        // Procesar respuestas detalladas
        $preguntas = json_decode($respuesta['preguntas'], true);
        $respuestas_usuario = json_decode($respuesta['respuestas'], true);
        
        $respuestas_detalle = [];
        
        if (is_array($preguntas) && is_array($respuestas_usuario)) {
            foreach ($preguntas as $index => $pregunta) {
                $respuestas_detalle[] = [
                    'pregunta' => $pregunta['pregunta'],
                    'tipo' => $pregunta['tipo'],
                    'respuesta' => $respuestas_usuario[$index] ?? 'Sin respuesta'
                ];
            }
        }
        
        $respuesta['respuestas_detalle'] = $respuestas_detalle;
        $respuesta['fecha_respuesta'] = date('d/m/Y H:i', strtotime($respuesta['fecha_respuesta']));
        
        return [
            'success' => true,
            'respuesta' => $respuesta
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function exportarRespuestas($conn, $data) {
    try {
        $encuesta_id = $conn->real_escape_string($data['encuesta_id']);
        $formato = $data['formato'] ?? 'excel';
        
        // Obtener datos para exportar
        $sql = "SELECT 
                    re.id,
                    re.respuestas,
                    re.puntaje_calculado,
                    re.fecha_respuesta,
                    CONCAT(COALESCE(a.nombres, ''), ' ', COALESCE(a.apellidos, '')) as respondente,
                    a.email
                FROM respuestas_encuesta re
                LEFT JOIN apoderados a ON re.apoderado_id = a.id
                WHERE re.encuesta_id = $encuesta_id
                ORDER BY re.fecha_respuesta DESC";
        
        $result = $conn->query($sql);
        
        if ($formato === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="respuestas_encuesta_' . $encuesta_id . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Respondente', 'Email', 'Fecha', 'Puntaje', 'Respuestas']);
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['respondente'],
                    $row['email'],
                    $row['fecha_respuesta'],
                    $row['puntaje_calculado'],
                    $row['respuestas']
                ]);
            }
            
            fclose($output);
        } else {
            // Para Excel y PDF, generar contenido simple
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="respuestas_encuesta_' . $encuesta_id . '.' . $formato . '"');
            
            echo "Exportación de respuestas - Encuesta ID: $encuesta_id\n\n";
            
            while ($row = $result->fetch_assoc()) {
                echo "ID: " . $row['id'] . "\n";
                echo "Respondente: " . $row['respondente'] . "\n";
                echo "Email: " . $row['email'] . "\n";
                echo "Fecha: " . $row['fecha_respuesta'] . "\n";
                echo "Puntaje: " . $row['puntaje_calculado'] . "\n";
                echo "Respuestas: " . $row['respuestas'] . "\n";
                echo "---\n\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error al exportar: " . $e->getMessage();
    }
}

function eliminarRespuesta($conn, $data) {
    try {
        $respuesta_id = $conn->real_escape_string($data['respuesta_id']);
        
        $sql = "DELETE FROM respuestas_encuesta WHERE id = $respuesta_id";
        
        if ($conn->query($sql)) {
            return ['success' => true, 'mensaje' => 'Respuesta eliminada correctamente'];
        } else {
            return ['success' => false, 'mensaje' => 'Error al eliminar la respuesta: ' . $conn->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function eliminarRespuestasMasivo($conn, $data) {
    try {
        $respuestas_ids = json_decode($data['respuestas_ids'], true);
        
        if (!is_array($respuestas_ids) || empty($respuestas_ids)) {
            return ['success' => false, 'mensaje' => 'No hay respuestas seleccionadas'];
        }
        
        $ids_escaped = array_map(function($id) use ($conn) {
            return $conn->real_escape_string($id);
        }, $respuestas_ids);
        
        $ids_string = "'" . implode("','", $ids_escaped) . "'";
        
        $sql = "DELETE FROM respuestas_encuesta WHERE id IN ($ids_string)";
        
        if ($conn->query($sql)) {
            $eliminadas = $conn->affected_rows;
            return ['success' => true, 'mensaje' => "$eliminadas respuestas eliminadas correctamente"];
        } else {
            return ['success' => false, 'mensaje' => 'Error al eliminar las respuestas: ' . $conn->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

// Funciones auxiliares
function generateUniqueToken() {
    return bin2hex(random_bytes(16));
}

function determinarSentimiento($texto) {
    // Análisis básico de sentimiento
    $palabras_positivas = ['excelente', 'bueno', 'genial', 'fantástico', 'perfecto', 'maravilloso', 'increíble'];
    $palabras_negativas = ['malo', 'terrible', 'horrible', 'deficiente', 'pésimo', 'inadecuado'];
    
    $texto_lower = strtolower($texto);
    
    $positivas_encontradas = 0;
    $negativas_encontradas = 0;
    
    foreach ($palabras_positivas as $palabra) {
        if (strpos($texto_lower, $palabra) !== false) {
            $positivas_encontradas++;
        }
    }
    
    foreach ($palabras_negativas as $palabra) {
        if (strpos($texto_lower, $palabra) !== false) {
            $negativas_encontradas++;
        }
    }
    
    if ($positivas_encontradas > $negativas_encontradas) {
        return 'positivo';
    } elseif ($negativas_encontradas > $positivas_encontradas) {
        return 'negativo';
    } else {
        return 'neutral';
    }
}

?>