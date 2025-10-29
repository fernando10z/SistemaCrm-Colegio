<?php
include '../bd/conexion.php';

header('Content-Type: application/json');

// Obtener el rango de fechas (semana actual)
$fecha_inicio = date('Y-m-d', strtotime('monday this week'));
$fecha_fin = date('Y-m-d', strtotime('sunday this week'));

// Consulta para obtener interacciones de la semana
$sql = "SELECT 
    i.id,
    i.asunto,
    i.fecha_programada,
    i.estado,
    i.duracion_minutos,
    ti.nombre as tipo_interaccion,
    ti.color as tipo_color,
    ti.icono as tipo_icono,
    CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as estudiante,
    CONCAT(u.nombre, ' ', u.apellidos) as responsable
FROM interacciones i
LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
LEFT JOIN leads l ON i.lead_id = l.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
WHERE i.activo = 1 
    AND i.estado IN ('programado', 'reagendado')
    AND DATE(i.fecha_programada) BETWEEN '$fecha_inicio' AND '$fecha_fin'
ORDER BY i.fecha_programada ASC";

$result = $conn->query($sql);

// Organizar por día
$agenda_semanal = [];
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

for ($i = 0; $i < 7; $i++) {
    $fecha = date('Y-m-d', strtotime("monday this week +$i days"));
    $agenda_semanal[$fecha] = [
        'dia' => $dias[$i],
        'fecha' => date('d/m/Y', strtotime($fecha)),
        'interacciones' => []
    ];
}

// Llenar con interacciones
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fecha = date('Y-m-d', strtotime($row['fecha_programada']));
        if (isset($agenda_semanal[$fecha])) {
            $agenda_semanal[$fecha]['interacciones'][] = $row;
        }
    }
}

// Generar HTML
$html = '<div class="row">';

foreach ($agenda_semanal as $fecha => $dia_data) {
    $es_hoy = ($fecha == date('Y-m-d')) ? 'border-primary' : '';
    $total = count($dia_data['interacciones']);
    
    $html .= '<div class="col-md-12 mb-3">
        <div class="card ' . $es_hoy . '">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="ti ti-calendar"></i> 
                    ' . $dia_data['dia'] . ' - ' . $dia_data['fecha'] . '
                    <span class="badge bg-primary ms-2">' . $total . ' interacción' . ($total != 1 ? 'es' : '') . '</span>
                </h6>
            </div>
            <div class="card-body">';
    
    if ($total > 0) {
        $html .= '<div class="list-group">';
        foreach ($dia_data['interacciones'] as $int) {
            $hora = date('H:i', strtotime($int['fecha_programada']));
            $html .= '<div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">
                            <span class="badge" style="background-color: ' . $int['tipo_color'] . ';">
                                <i class="ti ti-' . $int['tipo_icono'] . '"></i> ' . $int['tipo_interaccion'] . '
                            </span>
                        </h6>
                        <p class="mb-1"><strong>' . htmlspecialchars($int['asunto']) . '</strong></p>
                        <small class="text-muted">
                            <i class="ti ti-user"></i> ' . htmlspecialchars($int['estudiante'] ?? 'Sin asignar') . ' | 
                            <i class="ti ti-clock"></i> ' . $hora . ' 
                            (' . ($int['duracion_minutos'] ?? 30) . ' min)
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-info">' . ucfirst($int['estado']) . '</span><br>
                        <small class="text-muted">' . htmlspecialchars($int['responsable'] ?? 'Sin asignar') . '</small>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<p class="text-muted text-center mb-0">
            <i class="ti ti-calendar-off"></i> Sin interacciones programadas
        </p>';
    }
    
    $html .= '</div></div></div>';
}

$html .= '</div>';

echo json_encode([
    'success' => true,
    'html' => $html,
    'total_semana' => array_sum(array_column($agenda_semanal, 'interacciones'))
]);

$conn->close();
?>