<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
        throw new Exception('ID de usuario requerido');
    }

    $usuario_id = intval($_POST['usuario_id']);

    // Obtener estadísticas de tareas
    $stats_sql = "SELECT 
        COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as tareas_hoy,
        COUNT(CASE WHEN l.proxima_accion_fecha < CURDATE() AND l.proxima_accion_fecha IS NOT NULL THEN 1 END) as tareas_vencidas,
        COUNT(CASE WHEN l.proxima_accion_fecha BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as tareas_proximas,
        COUNT(CASE WHEN l.proxima_accion_fecha > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as tareas_futuras,
        COUNT(CASE WHEN l.proxima_accion_fecha IS NULL THEN 1 END) as sin_programar
    FROM leads l
    WHERE l.responsable_id = ? AND l.activo = 1";
    
    $stmt = $conn->prepare($stats_sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();

    // Obtener tareas detalladas por fechas
    $tareas_sql = "SELECT 
        l.id,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto,
        l.telefono,
        l.email,
        l.prioridad,
        l.proxima_accion_fecha,
        l.proxima_accion_descripcion,
        el.nombre as estado_nombre,
        el.color as estado_color,
        g.nombre as grado_nombre,
        ne.nombre as nivel_nombre,
        
        -- Clasificar por tipo de tarea
        CASE 
            WHEN l.proxima_accion_fecha IS NULL THEN 'sin_programar'
            WHEN l.proxima_accion_fecha < CURDATE() THEN 'vencida'
            WHEN l.proxima_accion_fecha = CURDATE() THEN 'hoy'
            WHEN l.proxima_accion_fecha BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'proxima'
            ELSE 'futura'
        END as tipo_tarea,
        
        -- Días de diferencia
        CASE 
            WHEN l.proxima_accion_fecha IS NULL THEN NULL
            ELSE DATEDIFF(l.proxima_accion_fecha, CURDATE())
        END as dias_diferencia
        
    FROM leads l
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
    WHERE l.responsable_id = ? AND l.activo = 1
    ORDER BY 
        CASE 
            WHEN l.proxima_accion_fecha IS NULL THEN 5
            WHEN l.proxima_accion_fecha < CURDATE() THEN 1
            WHEN l.proxima_accion_fecha = CURDATE() THEN 2
            WHEN l.proxima_accion_fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 3
            ELSE 4
        END,
        l.prioridad = 'urgente' DESC,
        l.prioridad = 'alta' DESC,
        l.proxima_accion_fecha ASC";

    $stmt2 = $conn->prepare($tareas_sql);
    $stmt2->bind_param("i", $usuario_id);
    $stmt2->execute();
    $tareas_result = $stmt2->get_result();

    // Agrupar tareas por tipo
    $tareas_agrupadas = [
        'vencida' => [],
        'hoy' => [],
        'proxima' => [],
        'futura' => [],
        'sin_programar' => []
    ];

    while($row = $tareas_result->fetch_assoc()) {
        $tareas_agrupadas[$row['tipo_tarea']][] = $row;
    }

    // Generar HTML del calendario de tareas
    $calendario_html = generarCalendarioTareas($tareas_agrupadas);

    echo json_encode([
        'success' => true,
        'data' => [
            'tareas_hoy' => (int)$stats['tareas_hoy'],
            'tareas_vencidas' => (int)$stats['tareas_vencidas'],
            'tareas_proximas' => (int)$stats['tareas_proximas'],
            'tareas_futuras' => (int)$stats['tareas_futuras'],
            'sin_programar' => (int)$stats['sin_programar']
        ],
        'calendario_html' => $calendario_html,
        'tareas_agrupadas' => $tareas_agrupadas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generarCalendarioTareas($tareas_agrupadas) {
    $html = '';
    
    // Tareas Vencidas
    if (!empty($tareas_agrupadas['vencida'])) {
        $html .= '
        <div class="card border-danger mb-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="ti ti-alert-triangle me-1"></i>Tareas Vencidas (' . count($tareas_agrupadas['vencida']) . ')</h6>
            </div>
            <div class="card-body p-2">';
        
        foreach($tareas_agrupadas['vencida'] as $tarea) {
            $dias_vencida = abs($tarea['dias_diferencia']);
            $html .= generarTarjetaTarea($tarea, 'danger', "Vencida hace {$dias_vencida} día(s)");
        }
        
        $html .= '</div></div>';
    }
    
    // Tareas de Hoy
    if (!empty($tareas_agrupadas['hoy'])) {
        $html .= '
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="ti ti-calendar-event me-1"></i>Tareas de Hoy (' . count($tareas_agrupadas['hoy']) . ')</h6>
            </div>
            <div class="card-body p-2">';
        
        foreach($tareas_agrupadas['hoy'] as $tarea) {
            $html .= generarTarjetaTarea($tarea, 'warning', 'Hoy');
        }
        
        $html .= '</div></div>';
    }
    
    // Próximas Tareas (7 días)
    if (!empty($tareas_agrupadas['proxima'])) {
        $html .= '
        <div class="card border-info mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="ti ti-calendar-time me-1"></i>Próximas 7 Días (' . count($tareas_agrupadas['proxima']) . ')</h6>
            </div>
            <div class="card-body p-2">';
        
        foreach($tareas_agrupadas['proxima'] as $tarea) {
            $dias_restantes = $tarea['dias_diferencia'];
            $texto_fecha = $dias_restantes == 1 ? 'Mañana' : "En {$dias_restantes} días";
            $html .= generarTarjetaTarea($tarea, 'info', $texto_fecha);
        }
        
        $html .= '</div></div>';
    }
    
    // Sin Programar
    if (!empty($tareas_agrupadas['sin_programar'])) {
        $html .= '
        <div class="card border-secondary mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ti ti-calendar-off me-1"></i>Sin Programar (' . count($tareas_agrupadas['sin_programar']) . ')</h6>
            </div>
            <div class="card-body p-2">';
        
        foreach($tareas_agrupadas['sin_programar'] as $tarea) {
            $html .= generarTarjetaTarea($tarea, 'secondary', 'Sin fecha programada');
        }
        
        $html .= '</div></div>';
    }
    
    if (empty($html)) {
        $html = '
        <div class="text-center py-4">
            <i class="ti ti-calendar-off" style="font-size: 48px; color: #6c757d;"></i>
            <h6 class="mt-2 text-muted">No hay tareas programadas</h6>
            <p class="text-muted">Este usuario no tiene tareas de seguimiento pendientes.</p>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="programarTarea()">
                <i class="ti ti-plus me-1"></i>Programar Nueva Tarea
            </button>
        </div>';
    }
    
    return $html;
}

function generarTarjetaTarea($tarea, $tipo, $tiempo_texto) {
    $prioridad_class = '';
    switch($tarea['prioridad']) {
        case 'urgente':
            $prioridad_class = 'border-start border-danger border-3';
            break;
        case 'alta':
            $prioridad_class = 'border-start border-warning border-3';
            break;
    }
    
    $descripcion = !empty($tarea['proxima_accion_descripcion']) ? 
        htmlspecialchars($tarea['proxima_accion_descripcion']) : 
        'Seguimiento general';
    
    return '
    <div class="border rounded p-2 mb-2 bg-white ' . $prioridad_class . '">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="fw-bold small">' . htmlspecialchars($tarea['nombre_estudiante']) . '</div>
                <div class="text-muted small">' . htmlspecialchars($tarea['codigo_lead']) . ' - ' . htmlspecialchars($tarea['nivel_nombre'] . ' ' . $tarea['grado_nombre']) . '</div>
                <div class="small mt-1">' . $descripcion . '</div>
                <div class="d-flex gap-2 mt-1">
                    <span class="badge bg-' . $tipo . ' text-white small">' . $tiempo_texto . '</span>
                    <span class="badge" style="background-color: ' . ($tarea['estado_color'] ?? '#6c757d') . '; color: white;">' . htmlspecialchars($tarea['estado_nombre']) . '</span>
                    ' . ($tarea['prioridad'] === 'urgente' ? '<span class="badge bg-danger">URGENTE</span>' : '') . '
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="completarTarea(' . $tarea['id'] . ')">
                        <i class="ti ti-check me-1"></i>Marcar Completada
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="reprogramarTarea(' . $tarea['id'] . ')">
                        <i class="ti ti-calendar me-1"></i>Reprogramar
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="contactarAhora(' . $tarea['id'] . ')">
                        <i class="ti ti-phone me-1"></i>Contactar Ahora
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="verDetallesLead(' . $tarea['id'] . ')">
                        <i class="ti ti-eye me-1"></i>Ver Detalles
                    </a></li>
                </ul>
            </div>
        </div>
    </div>';
}

$conn->close();
?>