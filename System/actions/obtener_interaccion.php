<?php
date_default_timezone_set('America/Lima');
include '../bd/conexion.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error desconocido.'];

try {
    // 1. Validar la entrada
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('No se proporcionó ID de interacción.');
    }
    if (!isset($_POST['accion']) || empty($_POST['accion'])) {
        throw new Exception('No se proporcionó la acción a realizar.');
    }

    $id = (int)$_POST['id'];
    $accion = $_POST['accion'];

    // 2. Preparar y ejecutar la consulta (usando la consulta de tu archivo principal)
    // Usamos la consulta completa para tener todos los datos para el modal de "detalle"
    $sql = "SELECT 
        i.id, i.asunto, i.descripcion, i.fecha_programada, i.fecha_realizada,
        i.duracion_minutos, i.resultado, i.observaciones, i.requiere_seguimiento,
        i.fecha_proximo_seguimiento, i.estado, i.created_at,
        ti.nombre as tipo_interaccion, ti.icono as tipo_icono, ti.color as tipo_color,
        CONCAT(u.nombre, ' ', u.apellidos) as usuario_completo,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as estudiante_completo,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as contacto_completo,
        l.telefono as lead_telefono, l.email as lead_email,
        el.nombre as estado_lead,
        CONCAT(a.nombres, ' ', a.apellidos) as apoderado_completo,
        f.apellido_principal as familia_apellido
    FROM interacciones i
    LEFT JOIN tipos_interaccion ti ON i.tipo_interaccion_id = ti.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN leads l ON i.lead_id = l.id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN apoderados a ON i.apoderado_id = a.id
    LEFT JOIN familias f ON i.familia_id = f.id
    WHERE i.id = ? AND i.activo = 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Interacción no encontrada o inactiva (ID: ' . $id . ').');
    }

    $data = $result->fetch_assoc();
    $stmt->close();

    // 3. Devolver la respuesta según la acción
    
    if ($accion === 'reprogramar') {
        // Para reprogramar, solo necesitas los datos básicos
        $response = [
            'success' => true,
            'data' => [
                'id' => $data['id'],
                'asunto' => htmlspecialchars($data['asunto']),
'fecha_programada' => !empty($data['fecha_programada']) ? date('d/m/Y H:i', strtotime($data['fecha_programada'])) : date('d/m/Y H:i')            ]
        ];

    } elseif ($accion === 'detalle') {
        // Para el detalle, generamos el HTML que el JavaScript espera
        
        // --- Identificar contacto ---
        $contacto_principal = '';
        $contacto_secundario = '';
        if ($data['estudiante_completo']) {
            $contacto_principal = "<strong>Estudiante:</strong> " . htmlspecialchars($data['estudiante_completo']);
            if ($data['contacto_completo'] && $data['contacto_completo'] != $data['estudiante_completo']) {
                $contacto_secundario .= "<p><strong>Apoderado (Lead):</strong> " . htmlspecialchars($data['contacto_completo']) . "</p>";
            }
            $contacto_secundario .= "<p><strong>Teléfono:</strong> " . htmlspecialchars($data['lead_telefono'] ?? 'N/A') . "</p>";
            $contacto_secundario .= "<p><strong>Email:</strong> " . htmlspecialchars($data['lead_email'] ?? 'N/A') . "</p>";
        } elseif ($data['apoderado_completo']) {
             $contacto_principal = "<strong>Apoderado:</strong> " . htmlspecialchars($data['apoderado_completo']);
             if($data['familia_apellido']) {
                 $contacto_secundario = "<p><strong>Familia:</strong> " . htmlspecialchars($data['familia_apellido']) . "</p>";
             }
        } else {
            $contacto_principal = "<p class='text-muted'>No hay contacto asociado.</p>";
        }

        // --- Seguimiento ---
        $seguimiento_html = '';
        if ($data['requiere_seguimiento'] == 1) {
            $fecha_seg = empty($data['fecha_proximo_seguimiento']) ? 'Pendiente' : date('d/m/Y', strtotime($data['fecha_proximo_seguimiento']));
            $seguimiento_html = "<p><strong>Próximo Seguimiento:</strong> <span class='badge bg-warning text-dark'>" . $fecha_seg . "</span></p>";
        } else {
            $seguimiento_html = "<p><strong>Próximo Seguimiento:</strong> No requerido</p>";
        }

        // --- Construir HTML ---
        $html = "<div class='container-fluid py-2'>";
        $html .= "<h4 class='mb-3 text-primary'>" . htmlspecialchars($data['asunto']) . "</h4>";
        $html .= "<hr>";

        $html .= "<div class='row'>";
        $html .= "<div class='col-md-6'>";
        $html .= "<h5><i class='ti ti-info-circle me-2'></i>Detalles de Interacción</h5>";
        $html .= "<p><strong>Tipo:</strong> <span class='badge' style='background-color:" . ($data['tipo_color'] ?? '#6c757d') . "; color:white;'><i class='ti ti-" . ($data['tipo_icono'] ?? 'circle') . " me-1'></i>" . htmlspecialchars($data['tipo_interaccion'] ?? 'N/A') . "</span></p>";
        $html .= "<p><strong>Estado:</strong> <span class='badge badge-estado-interaccion estado-" . $data['estado'] . "'>" . ucfirst($data['estado']) . "</span></p>";
        $html .= "<p><strong>Descripción:</strong><br><small class='text-muted' style='white-space: pre-wrap;'>" . (empty($data['descripcion']) ? 'Sin descripción' : htmlspecialchars($data['descripcion'])) . "</small></p>";
        $html .= "<p><strong>Responsable:</strong> " . htmlspecialchars($data['usuario_completo'] ?? 'N/A') . "</p>";
        $html .= "</div>";

        $html .= "<div class='col-md-6'>";
        $html .= "<h5><i class='ti ti-user-circle me-2'></i>Contacto Asociado</h5>";
        $html .= $contacto_principal;
        $html .= $contacto_secundario;
        $html .= "</div>";
        $html .= "</div><hr>";

        $html .= "<div class='row'>";
        $html .= "<div class='col-md-6'>";
        $html .= "<h5><i class='ti ti-calendar-event me-2'></i>Fechas y Duración</h5>";
        $html .= "<p><strong>Programada:</strong> " . (empty($data['fecha_programada']) ? 'N/A' : date('d/m/Y H:i', strtotime($data['fecha_programada']))) . "</p>";
        $html .= "<p><strong>Realizada:</strong> " . (empty($data['fecha_realizada']) ? 'N/A' : date('d/m/Y H:i', strtotime($data['fecha_realizada']))) . "</p>";
        $html .= "<p><strong>Duración:</strong> " . (empty($data['duracion_minutos']) ? 'N/A' : $data['duracion_minutos'] . " minutos") . "</p>";
        $html .= "<p><strong>Creada:</strong> " . (empty($data['created_at']) ? 'N/A' : date('d/m/Y H:i', strtotime($data['created_at']))) . "</p>";
        $html .= "</div>";

        $html .= "<div class='col-md-6'>";
        $html .= "<h5><i class='ti ti-check me-2'></i>Resultado y Seguimiento</h5>";
        $html .= "<p><strong>Resultado:</strong> " . (empty($data['resultado']) ? 'N/A' : "<span class='resultado-interaccion resultado-" . $data['resultado'] . "'>" . ucfirst(str_replace('_', ' ', $data['resultado'])) . "</span>") . "</g></p>";
        $html .= "<p><strong>Observaciones:</strong><br><small class='text-muted'>" . (empty($data['observaciones']) ? 'Sin observaciones' : htmlspecialchars($data['observaciones'])) . "</small></p>";
        $html .= $seguimiento_html;
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "</div>";

        $response = [
            'success' => true,
            'html' => $html
        ];

    } else {
        throw new Exception('Acción no válida o no reconocida.');
    }

} catch (Exception $e) {
    // Capturar cualquier error y enviarlo como JSON
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

$conn->close();
echo json_encode($response);
?>