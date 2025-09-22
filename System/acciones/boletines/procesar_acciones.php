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
    case 'crear_boletin':
        $resultado = procesarCrearBoletin($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'programar_envio_masivo':
        $resultado = procesarProgramarEnvioMasivo($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'personalizar_contenido':
        $resultado = procesarPersonalizarContenido($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'analizar_metricas':
        $resultado = procesarAnalizarMetricas($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'obtener_estadisticas_boletines':
        echo json_encode(obtenerEstadisticasBoletines($conn));
        break;
        
    case 'obtener_boletin_edicion':
        echo json_encode(obtenerBoletinParaEdicion($conn, $_POST));
        break;
        
    case 'actualizar_boletin':
        $resultado = procesarActualizarBoletin($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'eliminar_boletin':
        $resultado = procesarEliminarBoletin($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'duplicar_boletin':
        $resultado = procesarDuplicarBoletin($conn, $_POST);
        echo json_encode(['success' => !str_contains($resultado, 'Error'), 'mensaje' => $resultado]);
        break;
        
    case 'vista_previa_boletin':
        echo json_encode(generarVistaPreviaBoletin($conn, $_POST));
        break;
        
    case 'obtener_metricas_detalladas':
        echo json_encode(obtenerMetricasDetalladas($conn, $_POST));
        break;
        
    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}

// ======================= FUNCIONES =======================

function procesarCrearBoletin($conn, $data) {
    try {
        $nombre = $conn->real_escape_string($data['nombre']);
        $asunto = $conn->real_escape_string($data['asunto']);
        $contenido = $conn->real_escape_string($data['contenido']);
        $tipo = 'email'; // Los boletines son principalmente email
        $categoria = $conn->real_escape_string($data['categoria']);
        $variables_disponibles = json_encode($data['variables_disponibles'] ?? []);
        $incluir_eventos = isset($data['incluir_eventos']) ? 1 : 0;
        $nivel_educativo = $conn->real_escape_string($data['nivel_educativo'] ?? 'general');
        
        // Agregar eventos automáticamente si se solicita
        if ($incluir_eventos) {
            $eventos_sql = "SELECT titulo, fecha_inicio, descripcion FROM eventos 
                           WHERE fecha_inicio >= CURDATE() 
                           AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                           AND estado = 'programado' 
                           ORDER BY fecha_inicio ASC LIMIT 5";
            $eventos_result = $conn->query($eventos_sql);
            
            $eventos_html = "\n\n--- PRÓXIMOS EVENTOS ---\n";
            while($evento = $eventos_result->fetch_assoc()) {
                $eventos_html .= "• " . $evento['titulo'] . " - " . date('d/m/Y', strtotime($evento['fecha_inicio'])) . "\n";
                if ($evento['descripcion']) {
                    $eventos_html .= "  " . substr($evento['descripcion'], 0, 100) . "...\n";
                }
            }
            $contenido .= $eventos_html;
        }
        
        $sql = "INSERT INTO plantillas_mensajes (
                    nombre, tipo, asunto, contenido, variables_disponibles, categoria
                ) VALUES (
                    '$nombre', '$tipo', '$asunto', '$contenido', '$variables_disponibles', '$categoria'
                )";
        
        if ($conn->query($sql)) {
            return "Boletín informativo creado correctamente.";
        } else {
            return "Error al crear el boletín: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function procesarProgramarEnvioMasivo($conn, $data) {
    try {
        $plantilla_id = $conn->real_escape_string($data['plantilla_id']);
        $fecha_envio = $conn->real_escape_string($data['fecha_envio']);
        $hora_envio = $conn->real_escape_string($data['hora_envio']);
        $destinatarios_tipo = $conn->real_escape_string($data['destinatarios_tipo']);
        $nivel_educativo_filtro = $conn->real_escape_string($data['nivel_educativo_filtro'] ?? '');
        
        $fecha_completa = $fecha_envio . ' ' . $hora_envio . ':00';
        
        // Obtener destinatarios según el tipo
        $destinatarios_sql = "";
        switch ($destinatarios_tipo) {
            case 'todos_apoderados':
                $destinatarios_sql = "SELECT DISTINCT email, CONCAT(nombres, ' ', apellidos) as nombre, 'apoderado' as tipo, id as contacto_id
                                    FROM apoderados WHERE activo = 1 AND email IS NOT NULL AND email != ''";
                break;
            case 'por_nivel':
                if (!empty($nivel_educativo_filtro)) {
                    $destinatarios_sql = "SELECT DISTINCT a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre, 'apoderado' as tipo, a.id as contacto_id
                                        FROM apoderados a
                                        JOIN familias f ON a.familia_id = f.id
                                        JOIN estudiantes e ON f.id = e.familia_id
                                        JOIN grados g ON e.grado_id = g.id
                                        JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                                        WHERE a.activo = 1 AND a.email IS NOT NULL AND a.email != ''
                                        AND ne.nombre = '$nivel_educativo_filtro'";
                }
                break;
            case 'familias_activas':
                $destinatarios_sql = "SELECT DISTINCT a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre, 'apoderado' as tipo, a.id as contacto_id
                                    FROM apoderados a
                                    JOIN familias f ON a.familia_id = f.id
                                    WHERE a.activo = 1 AND f.activo = 1 AND a.email IS NOT NULL AND a.email != ''
                                    AND a.tipo_apoderado = 'titular'";
                break;
        }
        
        if (empty($destinatarios_sql)) {
            return "Error: Tipo de destinatarios no válido.";
        }
        
        $destinatarios_result = $conn->query($destinatarios_sql);
        if (!$destinatarios_result) {
            return "Error al obtener destinatarios: " . $conn->error;
        }
        
        $total_programados = 0;
        
        // Obtener datos de la plantilla
        $plantilla_sql = "SELECT * FROM plantillas_mensajes WHERE id = $plantilla_id";
        $plantilla_result = $conn->query($plantilla_sql);
        $plantilla = $plantilla_result->fetch_assoc();
        
        // Programar envíos individuales
        while($destinatario = $destinatarios_result->fetch_assoc()) {
            $sql_envio = "INSERT INTO mensajes_enviados (
                            plantilla_id, destinatario_email, asunto, contenido, 
                            estado, created_at, tipo,
                            " . ($destinatarios_tipo === 'todos_apoderados' || $destinatarios_tipo === 'familias_activas' || $destinatarios_tipo === 'por_nivel' ? "apoderado_id" : "") . "
                        ) VALUES (
                            $plantilla_id, 
                            '" . $conn->real_escape_string($destinatario['email']) . "',
                            '" . $conn->real_escape_string($plantilla['asunto']) . "',
                            '" . $conn->real_escape_string($plantilla['contenido']) . "',
                            'pendiente',
                            '$fecha_completa',
                            'email'
                            " . ($destinatario['tipo'] === 'apoderado' ? ", " . $destinatario['contacto_id'] : "") . "
                        )";
            
            if ($conn->query($sql_envio)) {
                $total_programados++;
            }
        }
        
        return "Envío masivo programado correctamente para $total_programados destinatarios.";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function procesarPersonalizarContenido($conn, $data) {
    try {
        $plantilla_id = $conn->real_escape_string($data['plantilla_id']);
        $nivel_educativo = $conn->real_escape_string($data['nivel_educativo']);
        $contenido_personalizado = $conn->real_escape_string($data['contenido_personalizado']);
        $variables_personalizadas = json_encode($data['variables_personalizadas'] ?? []);
        
        // Crear nueva versión personalizada de la plantilla
        $plantilla_sql = "SELECT * FROM plantillas_mensajes WHERE id = $plantilla_id";
        $plantilla_result = $conn->query($plantilla_sql);
        $plantilla = $plantilla_result->fetch_assoc();
        
        $nuevo_nombre = $plantilla['nombre'] . ' - ' . ucfirst($nivel_educativo);
        $nueva_categoria = $plantilla['categoria'] . '_' . $nivel_educativo;
        
        $sql = "INSERT INTO plantillas_mensajes (
                    nombre, tipo, asunto, contenido, variables_disponibles, categoria
                ) VALUES (
                    '$nuevo_nombre',
                    '" . $plantilla['tipo'] . "',
                    '" . $plantilla['asunto'] . " - " . ucfirst($nivel_educativo) . "',
                    '$contenido_personalizado',
                    '$variables_personalizadas',
                    '$nueva_categoria'
                )";
        
        if ($conn->query($sql)) {
            return "Contenido personalizado creado correctamente para nivel $nivel_educativo.";
        } else {
            return "Error al crear contenido personalizado: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function procesarAnalizarMetricas($conn, $data) {
    try {
        $plantilla_id = $conn->real_escape_string($data['plantilla_id']);
        $fecha_inicio = $conn->real_escape_string($data['fecha_inicio']);
        $fecha_fin = $conn->real_escape_string($data['fecha_fin']);
        
        // Obtener métricas de la plantilla
        $metricas_sql = "SELECT 
                            COUNT(*) as total_enviados,
                            COUNT(CASE WHEN estado IN ('entregado', 'leido') THEN 1 END) as entregados,
                            COUNT(CASE WHEN estado = 'leido' THEN 1 END) as abiertos,
                            AVG(CASE WHEN fecha_entrega IS NOT NULL AND fecha_envio IS NOT NULL 
                                THEN TIMESTAMPDIFF(MINUTE, fecha_envio, fecha_entrega) ELSE NULL END) as tiempo_promedio_entrega
                        FROM mensajes_enviados 
                        WHERE plantilla_id = $plantilla_id 
                        AND DATE(created_at) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        
        $metricas_result = $conn->query($metricas_sql);
        $metricas = $metricas_result->fetch_assoc();
        
        $tasa_entrega = $metricas['total_enviados'] > 0 ? 
            round(($metricas['entregados'] / $metricas['total_enviados']) * 100, 2) : 0;
        $tasa_apertura = $metricas['entregados'] > 0 ? 
            round(($metricas['abiertos'] / $metricas['entregados']) * 100, 2) : 0;
        
        return "Métricas analizadas: {$metricas['total_enviados']} enviados, {$tasa_entrega}% entregados, {$tasa_apertura}% apertura.";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function obtenerEstadisticasBoletines($conn) {
    try {
        $stats_sql = "SELECT 
            COUNT(DISTINCT pm.id) as total_boletines,
            COUNT(DISTINCT CASE WHEN pm.activo = 1 THEN pm.id END) as boletines_activos,
            COUNT(me.id) as total_envios_boletines,
            COUNT(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 END) as envios_exitosos,
            COUNT(CASE WHEN me.estado = 'leido' THEN 1 END) as total_aperturas,
            COUNT(CASE WHEN DATE(me.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as envios_semana,
            COUNT(CASE WHEN DATE(pm.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as boletines_nuevos_mes,
            ROUND(AVG(CASE WHEN me.estado IN ('entregado', 'leido') THEN 1 ELSE 0 END) * 100, 2) as tasa_entrega_promedio,
            ROUND(AVG(CASE WHEN me.estado = 'leido' AND me.estado IN ('entregado', 'leido') THEN 1 ELSE 0 END) * 100, 2) as tasa_apertura_promedio
        FROM plantillas_mensajes pm
        LEFT JOIN mensajes_enviados me ON pm.id = me.plantilla_id
        WHERE pm.tipo = 'email' AND (pm.categoria LIKE '%boletin%' OR pm.categoria LIKE '%newsletter%' OR pm.categoria LIKE '%informativo%' OR pm.nombre LIKE '%Boletín%' OR pm.nombre LIKE '%Newsletter%')";
        
        $result = $conn->query($stats_sql);
        $stats = $result->fetch_assoc();
        
        return ['success' => true, 'data' => $stats];
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function obtenerBoletinParaEdicion($conn, $data) {
    try {
        $boletin_id = $conn->real_escape_string($data['boletin_id']);
        
        $sql = "SELECT * FROM plantillas_mensajes WHERE id = $boletin_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $boletin = $result->fetch_assoc();
            $boletin['variables_disponibles'] = json_decode($boletin['variables_disponibles'], true);
            return ['success' => true, 'boletin' => $boletin];
        } else {
            return ['success' => false, 'mensaje' => 'Boletín no encontrado'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function procesarActualizarBoletin($conn, $data) {
    try {
        $boletin_id = $conn->real_escape_string($data['boletin_id']);
        $nombre = $conn->real_escape_string($data['nombre']);
        $asunto = $conn->real_escape_string($data['asunto']);
        $contenido = $conn->real_escape_string($data['contenido']);
        $categoria = $conn->real_escape_string($data['categoria']);
        $variables_disponibles = json_encode($data['variables_disponibles'] ?? []);
        $activo = isset($data['activo']) ? 1 : 0;
        
        $sql = "UPDATE plantillas_mensajes SET 
                    nombre = '$nombre',
                    asunto = '$asunto',
                    contenido = '$contenido',
                    categoria = '$categoria',
                    variables_disponibles = '$variables_disponibles',
                    activo = $activo,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = $boletin_id";
        
        if ($conn->query($sql)) {
            return "Boletín actualizado correctamente.";
        } else {
            return "Error al actualizar el boletín: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function procesarEliminarBoletin($conn, $data) {
    try {
        $boletin_id = $conn->real_escape_string($data['boletin_id']);
        
        // Verificar si tiene envíos asociados
        $check_sql = "SELECT COUNT(*) as envios FROM mensajes_enviados WHERE plantilla_id = $boletin_id";
        $check_result = $conn->query($check_sql);
        $check_data = $check_result->fetch_assoc();
        
        if ($check_data['envios'] > 0) {
            // Solo desactivar si tiene envíos
            $sql = "UPDATE plantillas_mensajes SET activo = 0 WHERE id = $boletin_id";
            $mensaje = "Boletín desactivado (no se puede eliminar porque tiene envíos asociados).";
        } else {
            // Eliminar completamente si no tiene envíos
            $sql = "DELETE FROM plantillas_mensajes WHERE id = $boletin_id";
            $mensaje = "Boletín eliminado correctamente.";
        }
        
        if ($conn->query($sql)) {
            return $mensaje;
        } else {
            return "Error al eliminar el boletín: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function procesarDuplicarBoletin($conn, $data) {
    try {
        $boletin_id = $conn->real_escape_string($data['boletin_id']);
        
        // Obtener datos del boletín original
        $sql_original = "SELECT * FROM plantillas_mensajes WHERE id = $boletin_id";
        $result_original = $conn->query($sql_original);
        
        if (!$result_original || $result_original->num_rows == 0) {
            return "Error: Boletín original no encontrado.";
        }
        
        $original = $result_original->fetch_assoc();
        
        // Crear copia
        $nuevo_nombre = $original['nombre'] . ' (Copia)';
        $sql_duplicar = "INSERT INTO plantillas_mensajes (
                            nombre, tipo, asunto, contenido, variables_disponibles, categoria, activo
                        ) VALUES (
                            '" . $conn->real_escape_string($nuevo_nombre) . "',
                            '" . $original['tipo'] . "',
                            '" . $original['asunto'] . "',
                            '" . $original['contenido'] . "',
                            '" . $original['variables_disponibles'] . "',
                            '" . $original['categoria'] . "',
                            0
                        )";
        
        if ($conn->query($sql_duplicar)) {
            return "Boletín duplicado correctamente como: $nuevo_nombre";
        } else {
            return "Error al duplicar el boletín: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function generarVistaPreviaBoletin($conn, $data) {
    try {
        $boletin_id = $conn->real_escape_string($data['boletin_id']);
        
        $sql = "SELECT nombre, asunto, contenido FROM plantillas_mensajes WHERE id = $boletin_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $boletin = $result->fetch_assoc();
            
            // Generar HTML de vista previa
            $vista_previa = "
                <div class='email-preview'>
                    <div class='email-header'>
                        <h3>{$boletin['asunto']}</h3>
                        <small class='text-muted'>Vista previa del boletín: {$boletin['nombre']}</small>
                    </div>
                    <div class='email-body' style='border: 1px solid #ddd; padding: 20px; background: white; border-radius: 5px;'>
                        " . nl2br(htmlspecialchars($boletin['contenido'])) . "
                    </div>
                    <div class='email-footer mt-3'>
                        <small class='text-muted'>
                            Este es un boletín informativo generado por el sistema CRM.
                        </small>
                    </div>
                </div>
            ";
            
            return ['success' => true, 'vista_previa' => $vista_previa];
        } else {
            return ['success' => false, 'mensaje' => 'Boletín no encontrado'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

function obtenerMetricasDetalladas($conn, $data) {
    try {
        $plantilla_id = $conn->real_escape_string($data['plantilla_id']);
        $fecha_inicio = $data['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $data['fecha_fin'] ?? date('Y-m-d');
        
        // Métricas generales
        $metricas_sql = "SELECT 
                            COUNT(*) as total_enviados,
                            COUNT(CASE WHEN estado IN ('entregado', 'leido') THEN 1 END) as entregados,
                            COUNT(CASE WHEN estado = 'leido' THEN 1 END) as abiertos,
                            COUNT(CASE WHEN estado = 'fallido' THEN 1 END) as fallidos,
                            AVG(CASE WHEN fecha_entrega IS NOT NULL AND fecha_envio IS NOT NULL 
                                THEN TIMESTAMPDIFF(MINUTE, fecha_envio, fecha_entrega) ELSE NULL END) as tiempo_promedio_entrega
                        FROM mensajes_enviados 
                        WHERE plantilla_id = $plantilla_id 
                        AND DATE(created_at) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        
        $result_metricas = $conn->query($metricas_sql);
        $metricas = $result_metricas->fetch_assoc();
        
        // Métricas por día
        $diarias_sql = "SELECT 
                            DATE(created_at) as fecha,
                            COUNT(*) as enviados,
                            COUNT(CASE WHEN estado IN ('entregado', 'leido') THEN 1 END) as entregados,
                            COUNT(CASE WHEN estado = 'leido' THEN 1 END) as abiertos
                        FROM mensajes_enviados 
                        WHERE plantilla_id = $plantilla_id 
                        AND DATE(created_at) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                        GROUP BY DATE(created_at)
                        ORDER BY fecha ASC";
        
        $result_diarias = $conn->query($diarias_sql);
        $metricas_diarias = [];
        
        while ($row = $result_diarias->fetch_assoc()) {
            $metricas_diarias[] = $row;
        }
        
        $tasa_entrega = $metricas['total_enviados'] > 0 ? 
            round(($metricas['entregados'] / $metricas['total_enviados']) * 100, 2) : 0;
        $tasa_apertura = $metricas['entregados'] > 0 ? 
            round(($metricas['abiertos'] / $metricas['entregados']) * 100, 2) : 0;
        
        return [
            'success' => true,
            'metricas' => array_merge($metricas, [
                'tasa_entrega' => $tasa_entrega,
                'tasa_apertura' => $tasa_apertura
            ]),
            'metricas_diarias' => $metricas_diarias
        ];
    } catch (Exception $e) {
        return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
    }
}

?>