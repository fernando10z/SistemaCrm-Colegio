<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../bd/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Obtener y sanitizar datos del formulario
        $tipo_interaccion_id = filter_var($_POST['tipo_interaccion_id'] ?? null, FILTER_VALIDATE_INT);
        $lead_id = filter_var($_POST['lead_id'] ?? null, FILTER_VALIDATE_INT);
        $usuario_id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $asunto = trim($_POST['asunto'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_programada = $_POST['fecha_programada'] ?? '';
        $hora_programada = $_POST['hora_programada'] ?? '';
        $duracion_minutos = filter_var($_POST['duracion_minutos'] ?? 30, FILTER_VALIDATE_INT);
        $estado = $_POST['estado'] ?? 'programado';
        $requiere_seguimiento = isset($_POST['requiere_seguimiento']) ? 1 : 0;
        $fecha_proximo_seguimiento = !empty($_POST['fecha_proximo_seguimiento']) ? $_POST['fecha_proximo_seguimiento'] : null;
        $observaciones = trim($_POST['observaciones'] ?? '');

        // Validaciones estrictas
        if (!$tipo_interaccion_id || !$lead_id || !$usuario_id) {
            throw new Exception('IDs inválidos. Verifique tipo de interacción, lead y usuario.');
        }

        if (empty($asunto) || strlen($asunto) > 255) {
            throw new Exception('El asunto es obligatorio y debe tener máximo 255 caracteres');
        }

        if (empty($fecha_programada) || empty($hora_programada)) {
            throw new Exception('Fecha y hora son obligatorias');
        }

        if ($duracion_minutos === false || $duracion_minutos < 1 || $duracion_minutos > 999) {
            throw new Exception('La duración debe estar entre 1 y 999 minutos');
        }

        // Validar que la fecha no sea pasada
        $fecha_hora_programada = $fecha_programada . ' ' . $hora_programada . ':00';
        $fecha_obj = new DateTime($fecha_hora_programada);
        $ahora = new DateTime();
        
        if ($fecha_obj < $ahora) {
            throw new Exception('No se puede programar una interacción en el pasado');
        }

        // Validar estado
        $estados_validos = ['programado', 'reagendado'];
        if (!in_array($estado, $estados_validos)) {
            $estado = 'programado';
        }

        // Preparar consulta con manejo correcto de NULL
        if ($fecha_proximo_seguimiento !== null) {
            $sql = "INSERT INTO interacciones (
                tipo_interaccion_id, 
                lead_id, 
                usuario_id,
                asunto, 
                descripcion, 
                fecha_programada, 
                duracion_minutos, 
                estado, 
                requiere_seguimiento, 
                fecha_proximo_seguimiento, 
                observaciones,
                activo,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Error al preparar consulta: ' . $conn->error);
            }
            
            $stmt->bind_param(
                "iiisssissss",
                $tipo_interaccion_id,
                $lead_id,
                $usuario_id,
                $asunto,
                $descripcion,
                $fecha_hora_programada,
                $duracion_minutos,
                $estado,
                $requiere_seguimiento,
                $fecha_proximo_seguimiento,
                $observaciones
            );
        } else {
            $sql = "INSERT INTO interacciones (
                tipo_interaccion_id, 
                lead_id, 
                usuario_id,
                asunto, 
                descripcion, 
                fecha_programada, 
                duracion_minutos, 
                estado, 
                requiere_seguimiento, 
                fecha_proximo_seguimiento, 
                observaciones,
                activo,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 1, NOW())";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Error al preparar consulta: ' . $conn->error);
            }
            
            $stmt->bind_param(
                "iiisssisss",
                $tipo_interaccion_id,
                $lead_id,
                $usuario_id,
                $asunto,
                $descripcion,
                $fecha_hora_programada,
                $duracion_minutos,
                $estado,
                $requiere_seguimiento,
                $observaciones
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Error al insertar: ' . $stmt->error);
        }

        $interaccion_id = $stmt->insert_id;
        
        // Log de éxito para debugging (opcional)
        error_log("Interacción creada exitosamente con ID: " . $interaccion_id);
        
        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true, 
            'message' => 'Interacción programada exitosamente',
            'interaccion_id' => $interaccion_id
        ]);

    } catch (Exception $e) {
        // Log del error para debugging
        error_log("Error en programar_interaccion.php: " . $e->getMessage());
        
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
        
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Método HTTP no permitido. Use POST.'
    ]);
}
?>