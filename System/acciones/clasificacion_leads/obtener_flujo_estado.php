<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

// Establecer tipo de contenido JSON
header('Content-Type: application/json');

// Verificar que se recibió el ID del estado
if (!isset($_POST['estado_id']) || empty($_POST['estado_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID del estado es requerido',
        'data' => null
    ]);
    exit;
}

try {
    $estado_id = (int)$_POST['estado_id'];
    
    // Obtener información del estado actual
    $estado_sql = "SELECT 
        id, nombre, descripcion, color, orden_display, es_final, activo, created_at
    FROM estados_lead 
    WHERE id = ?";
    
    $estado_stmt = $conn->prepare($estado_sql);
    $estado_stmt->bind_param("i", $estado_id);
    $estado_stmt->execute();
    $estado_result = $estado_stmt->get_result();
    
    if ($estado_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Estado no encontrado',
            'data' => null
        ]);
        exit;
    }
    
    $estado_actual = $estado_result->fetch_assoc();
    
    // Obtener todos los estados disponibles para configurar flujos
    $todos_estados_sql = "SELECT 
        id, nombre, descripcion, color, orden_display, es_final, activo
    FROM estados_lead 
    WHERE activo = 1 AND id != ?
    ORDER BY orden_display ASC, nombre ASC";
    
    $todos_stmt = $conn->prepare($todos_estados_sql);
    $todos_stmt->bind_param("i", $estado_id);
    $todos_stmt->execute();
    $todos_result = $todos_stmt->get_result();
    
    $todos_estados = [];
    while ($row = $todos_result->fetch_assoc()) {
        $todos_estados[] = $row;
    }
    
    // Obtener flujos de entrada (estados que pueden llevar a este estado)
    $flujos_entrada_sql = "SELECT 
        ea.id,
        ea.nombre,
        ea.color,
        ea.orden_display,
        ea.es_final,
        COUNT(hel.id) as total_transiciones,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transiciones_mes,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as transiciones_semana,
        AVG(TIMESTAMPDIFF(HOUR, l.created_at, hel.created_at)) as horas_promedio_desde_creacion,
        MAX(hel.created_at) as ultima_transicion,
        MIN(hel.created_at) as primera_transicion,
        COUNT(DISTINCT hel.lead_id) as leads_unicos
    FROM estados_lead ea
    INNER JOIN historial_estados_lead hel ON ea.id = hel.estado_anterior_id
    LEFT JOIN leads l ON hel.lead_id = l.id
    WHERE hel.estado_nuevo_id = ? AND ea.activo = 1
    GROUP BY ea.id, ea.nombre, ea.color, ea.orden_display, ea.es_final
    ORDER BY total_transiciones DESC, ea.orden_display ASC";
    
    $entrada_stmt = $conn->prepare($flujos_entrada_sql);
    $entrada_stmt->bind_param("i", $estado_id);
    $entrada_stmt->execute();
    $entrada_result = $entrada_stmt->get_result();
    
    $flujos_entrada = [];
    while ($row = $entrada_result->fetch_assoc()) {
        $row['ultima_transicion_formatted'] = $row['ultima_transicion'] ? 
            date('d/m/Y H:i', strtotime($row['ultima_transicion'])) : 'N/A';
        $row['primera_transicion_formatted'] = $row['primera_transicion'] ? 
            date('d/m/Y H:i', strtotime($row['primera_transicion'])) : 'N/A';
        $row['horas_promedio_formatted'] = $row['horas_promedio_desde_creacion'] ? 
            round($row['horas_promedio_desde_creacion'], 1) . 'h' : 'N/A';
        $flujos_entrada[] = $row;
    }
    
    // Obtener flujos de salida (estados a los que puede ir desde este estado)
    $flujos_salida_sql = "SELECT 
        en.id,
        en.nombre,
        en.color,
        en.orden_display,
        en.es_final,
        COUNT(hel.id) as total_transiciones,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transiciones_mes,
        COUNT(CASE WHEN hel.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as transiciones_semana,
        AVG(TIMESTAMPDIFF(HOUR, hel_anterior.created_at, hel.created_at)) as horas_promedio_permanencia,
        MAX(hel.created_at) as ultima_transicion,
        MIN(hel.created_at) as primera_transicion,
        COUNT(DISTINCT hel.lead_id) as leads_unicos
    FROM estados_lead en
    INNER JOIN historial_estados_lead hel ON en.id = hel.estado_nuevo_id
    LEFT JOIN historial_estados_lead hel_anterior ON (
        hel.lead_id = hel_anterior.lead_id AND 
        hel_anterior.estado_nuevo_id = ? AND 
        hel_anterior.created_at < hel.created_at
    )
    WHERE hel.estado_anterior_id = ? AND en.activo = 1
    GROUP BY en.id, en.nombre, en.color, en.orden_display, en.es_final
    ORDER BY total_transiciones DESC, en.orden_display ASC";
    
    $salida_stmt = $conn->prepare($flujos_salida_sql);
    $salida_stmt->bind_param("ii", $estado_id, $estado_id);
    $salida_stmt->execute();
    $salida_result = $salida_stmt->get_result();
    
    $flujos_salida = [];
    while ($row = $salida_result->fetch_assoc()) {
        $row['ultima_transicion_formatted'] = $row['ultima_transicion'] ? 
            date('d/m/Y H:i', strtotime($row['ultima_transicion'])) : 'N/A';
        $row['primera_transicion_formatted'] = $row['primera_transicion'] ? 
            date('d/m/Y H:i', strtotime($row['primera_transicion'])) : 'N/A';
        $row['horas_promedio_formatted'] = $row['horas_promedio_permanencia'] ? 
            round($row['horas_promedio_permanencia'], 1) . 'h' : 'N/A';
        $flujos_salida[] = $row;
    }
    
    // Obtener estados sin flujo configurado (potenciales para configurar)
    $estados_sin_flujo_entrada = array_filter($todos_estados, function($estado) use ($flujos_entrada) {
        foreach ($flujos_entrada as $flujo) {
            if ($flujo['id'] == $estado['id']) {
                return false;
            }
        }
        return true;
    });
    
    $estados_sin_flujo_salida = array_filter($todos_estados, function($estado) use ($flujos_salida) {
        foreach ($flujos_salida as $flujo) {
            if ($flujo['id'] == $estado['id']) {
                return false;
            }
        }
        return true;
    });
    
    // Calcular estadísticas generales del flujo
    $stats_flujo_sql = "SELECT 
        -- Estadísticas de entrada
        COUNT(CASE WHEN hel.estado_nuevo_id = ? THEN 1 END) as total_entradas,
        COUNT(CASE WHEN hel.estado_nuevo_id = ? AND hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as entradas_mes,
        COUNT(DISTINCT CASE WHEN hel.estado_nuevo_id = ? THEN hel.estado_anterior_id END) as estados_origen_unicos,
        
        -- Estadísticas de salida
        COUNT(CASE WHEN hel.estado_anterior_id = ? THEN 1 END) as total_salidas,
        COUNT(CASE WHEN hel.estado_anterior_id = ? AND hel.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as salidas_mes,
        COUNT(DISTINCT CASE WHEN hel.estado_anterior_id = ? THEN hel.estado_nuevo_id END) as estados_destino_unicos,
        
        -- Conversiones finales
        COUNT(CASE WHEN hel.estado_anterior_id = ? AND en.es_final = 1 THEN 1 END) as conversiones_finales,
        
        -- Leads únicos involucrados
        COUNT(DISTINCT CASE WHEN (hel.estado_nuevo_id = ? OR hel.estado_anterior_id = ?) THEN hel.lead_id END) as leads_unicos_flujo
    FROM historial_estados_lead hel
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    WHERE hel.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
    
    $stats_stmt = $conn->prepare($stats_flujo_sql);
    $stats_stmt->bind_param("iiiiiiiii", 
        $estado_id, $estado_id, $estado_id, 
        $estado_id, $estado_id, $estado_id, 
        $estado_id, $estado_id, $estado_id
    );
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $estadisticas_flujo = $stats_result->fetch_assoc();
    
    // Calcular métricas adicionales
    $total_entradas = $estadisticas_flujo['total_entradas'];
    $total_salidas = $estadisticas_flujo['total_salidas'];
    
    // Tasa de retención (leads que permanecen en el estado)
    $leads_retenidos = $total_entradas - $total_salidas;
    $tasa_retencion = $total_entradas > 0 ? round(($leads_retenidos / $total_entradas) * 100, 1) : 0;
    
    // Tasa de conversión final
    $tasa_conversion_final = $total_salidas > 0 ? 
        round(($estadisticas_flujo['conversiones_finales'] / $total_salidas) * 100, 1) : 0;
    
    // Eficiencia del flujo (entradas vs salidas este mes)
    $eficiencia_mes = $estadisticas_flujo['entradas_mes'] > 0 ? 
        round(($estadisticas_flujo['salidas_mes'] / $estadisticas_flujo['entradas_mes']) * 100, 1) : 0;
    
    $estadisticas_flujo['tasa_retencion'] = $tasa_retencion;
    $estadisticas_flujo['tasa_conversion_final'] = $tasa_conversion_final;
    $estadisticas_flujo['eficiencia_mes'] = $eficiencia_mes;
    $estadisticas_flujo['leads_retenidos'] = $leads_retenidos;
    
    // Generar HTML para visualización del flujo
    $flujo_html = generarVisualizacionFlujo($estado_actual, $flujos_entrada, $flujos_salida);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'estado_actual' => $estado_actual,
            'flujos_entrada' => $flujos_entrada,
            'flujos_salida' => $flujos_salida,
            'estados_sin_flujo_entrada' => array_values($estados_sin_flujo_entrada),
            'estados_sin_flujo_salida' => array_values($estados_sin_flujo_salida),
            'todos_estados' => $todos_estados,
            'estadisticas_flujo' => $estadisticas_flujo,
            'flujo_html' => $flujo_html
        ],
        'message' => 'Configuración de flujo cargada correctamente'
    ]);

} catch (Exception $e) {
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener configuración de flujo: ' . $e->getMessage(),
        'data' => null
    ]);
} finally {
    // Cerrar statements y conexión
    if (isset($estado_stmt)) {
        $estado_stmt->close();
    }
    if (isset($todos_stmt)) {
        $todos_stmt->close();
    }
    if (isset($entrada_stmt)) {
        $entrada_stmt->close();
    }
    if (isset($salida_stmt)) {
        $salida_stmt->close();
    }
    if (isset($stats_stmt)) {
        $stats_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Función auxiliar para generar HTML de visualización del flujo
function generarVisualizacionFlujo($estado_actual, $flujos_entrada, $flujos_salida) {
    $html = '<div class="flujo-visualization">';
    
    // Sección de estados de entrada
    $html .= '<div class="flujo-seccion">';
    $html .= '<h6 class="flujo-titulo">Estados de Origen</h6>';
    
    if (empty($flujos_entrada)) {
        $html .= '<div class="flujo-vacio">Este estado no recibe leads de otros estados</div>';
    } else {
        foreach ($flujos_entrada as $entrada) {
            $porcentaje = 100; // Aquí podrías calcular un porcentaje relativo
            $html .= '<div class="flujo-item entrada">';
            $html .= '<div class="flujo-estado" style="background-color: ' . $entrada['color'] . ';">';
            $html .= htmlspecialchars($entrada['nombre']);
            $html .= '</div>';
            $html .= '<div class="flujo-arrow">→</div>';
            $html .= '<div class="flujo-stats">';
            $html .= '<small>' . $entrada['total_transiciones'] . ' transiciones</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    
    // Estado actual (centro)
    $html .= '<div class="flujo-seccion estado-central">';
    $html .= '<div class="estado-actual" style="background-color: ' . $estado_actual['color'] . ';">';
    $html .= '<strong>' . htmlspecialchars($estado_actual['nombre']) . '</strong>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Sección de estados de salida
    $html .= '<div class="flujo-seccion">';
    $html .= '<h6 class="flujo-titulo">Estados de Destino</h6>';
    
    if (empty($flujos_salida)) {
        $html .= '<div class="flujo-vacio">Este estado no envía leads a otros estados</div>';
    } else {
        foreach ($flujos_salida as $salida) {
            $html .= '<div class="flujo-item salida">';
            $html .= '<div class="flujo-arrow">→</div>';
            $html .= '<div class="flujo-estado" style="background-color: ' . $salida['color'] . ';">';
            $html .= htmlspecialchars($salida['nombre']);
            if ($salida['es_final'] == 1) {
                $html .= ' 🏁';
            }
            $html .= '</div>';
            $html .= '<div class="flujo-stats">';
            $html .= '<small>' . $salida['total_transiciones'] . ' transiciones</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>