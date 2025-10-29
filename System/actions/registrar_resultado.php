<?php
// Oculta advertencias (como las de 'deprecated') para no dañar el JSON
error_reporting(0); 
// Establece la zona horaria de Perú
date_default_timezone_set('America/Lima'); 

// Incluir conexión a la base de datos (ajusta la ruta si es necesario)
include '../bd/conexion.php';

header('Content-Type: application/json');

// Preparamos la respuesta por defecto
$response = ['success' => false, 'message' => 'Error desconocido.'];

try {
    // 1. Validar datos de entrada
    // Revisa que los 'name' de tu modal_registrar_resultado.php coincidan
    if (!isset($_POST['resultado_interaccion_id']) || empty($_POST['resultado_interaccion_id'])) {
        throw new Exception('No se proporcionó el ID de la interacción.');
    }
    if (!isset($_POST['resultado']) || empty($_POST['resultado'])) {
        throw new Exception('Debe seleccionar un resultado.');
    }
    if (!isset($_POST['fecha_realizada']) || empty($_POST['fecha_realizada'])) {
        throw new Exception('Debe especificar la fecha y hora de realización.');
    }
    
    // Validar seguimiento
    $requiere_seguimiento = isset($_POST['resultado_requiere_seguimiento']) ? 1 : 0;
    if ($requiere_seguimiento == 1 && empty($_POST['resultado_fecha_proximo_seguimiento'])) {
        throw new Exception('Marcó "requiere seguimiento" pero no especificó la fecha.');
    }

    // 2. Recolectar y formatear datos
    $interaccion_id = (int)$_POST['resultado_interaccion_id'];
    $resultado = $_POST['resultado'];
    $fecha_realizada_sql = date('Y-m-d H:i:s', strtotime($_POST['fecha_realizada']));
    $duracion = empty($_POST['duracion_minutos']) ? null : (int)$_POST['duracion_minutos'];
    $observaciones = $_POST['resultado_observaciones'] ?? '';
    $estado = 'realizado'; // Marcamos la interacción como completada
    $fecha_actual = date('Y-m-d H:i:s');
    
    $fecha_proximo_seguimiento = null;
    if ($requiere_seguimiento == 1) {
        $fecha_proximo_seguimiento = date('Y-m-d', strtotime($_POST['resultado_fecha_proximo_seguimiento']));
    }

    // 3. Obtener observaciones actuales para AÑADIR las nuevas (no sobrescribir)
    $stmt_obs = $conn->prepare("SELECT observaciones FROM interacciones WHERE id = ?");
    $stmt_obs->bind_param("i", $interaccion_id);
    $stmt_obs->execute();
    $result_obs = $stmt_obs->get_result();
    $row_obs = $result_obs->fetch_assoc();
    $observaciones_actuales = $row_obs['observaciones'] ?? '';
    $stmt_obs->close();

    // 4. Preparar la actualización
    $sql = "UPDATE interacciones SET 
                estado = ?,
                fecha_realizada = ?,
                resultado = ?,
                duracion_minutos = ?,
                observaciones = ?,
                requiere_seguimiento = ?,
                fecha_proximo_seguimiento = ?,
                updated_at = ?
            WHERE id = ?";

    // Añadir el motivo a las observaciones
    $nuevas_observaciones = $observaciones_actuales . 
                            "\n--- RESULTADO (" . date('d/m/Y H:i') . ") ---\n" . 
                            $observaciones . 
                            "\n----------------------------------\n";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    // Tipos: s(estado), s(fecha_real), s(resultado), i(duracion), s(obs), i(req_seg), s(fecha_seg), s(updated_at), i(id)
    $stmt->bind_param("sssisissi", 
        $estado, $fecha_realizada_sql, $resultado, $duracion, 
        $nuevas_observaciones, $requiere_seguimiento, 
        $fecha_proximo_seguimiento, $fecha_actual, $interaccion_id
    );

    // 5. Ejecutar y verificar
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = [
                'success' => true, 
                'message' => '¡Resultado registrado con éxito!'
            ];
        } else {
            $response = [
                'success' => false, 
                'message' => 'No se realizaron cambios. Verifique los datos.'
            ];
        }
    } else {
        throw new Exception("Error al ejecutar la actualización: " . $stmt->error);
    }

    $stmt->close();

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