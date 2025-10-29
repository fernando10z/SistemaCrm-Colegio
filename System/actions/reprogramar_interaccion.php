<?php
// Oculta advertencias (como las de 'deprecated') para no dañar el JSON
error_reporting(0); 
// Establece la zona horaria de Perú
date_default_timezone_set('America/Lima'); 

// Incluir conexión a la base de datos (ajusta la ruta si es necesario)
// Asumo que 'bd' está en la carpeta 'System', al mismo nivel que 'actions'.
include '../bd/conexion.php';

header('Content-Type: application/json');

// Preparamos la respuesta por defecto
$response = ['success' => false, 'message' => 'Error desconocido.'];

try {
    // 1. Validar datos de entrada (AHORA USAMOS LOS NOMBRES CORRECTOS)
    if (!isset($_POST['interaccion_id']) || empty($_POST['interaccion_id'])) {
        throw new Exception('No se especificó la interacción.');
    }
    if (empty($_POST['nueva_fecha']) || empty($_POST['nueva_hora'])) {
        throw new Exception('Debe seleccionar una nueva fecha y hora.');
    }
    if (empty($_POST['motivo'])) {
        throw new Exception('El motivo de la reprogramación es obligatorio.');
    }

    // 2. Recolectar y formatear datos
    $interaccion_id = (int)$_POST['interaccion_id'];
    $nueva_fecha = $_POST['nueva_fecha'];
    $nueva_hora = $_POST['nueva_hora'];
    $motivo = trim($_POST['motivo']);
    
    // Combinar fecha y hora en un formato DATETIME para MySQL
    $fecha_programada_sql = date('Y-m-d H:i:s', strtotime($nueva_fecha . ' ' . $nueva_hora));
    $fecha_actual = date('Y-m-d H:i:s');
    
    // 3. Obtener observaciones actuales para no borrarlas
    $stmt_obs = $conn->prepare("SELECT observaciones FROM interacciones WHERE id = ?");
    $stmt_obs->bind_param("i", $interaccion_id);
    $stmt_obs->execute();
    $result_obs = $stmt_obs->get_result();
    $row_obs = $result_obs->fetch_assoc();
    $observaciones_actuales = $row_obs['observaciones'] ?? '';
    $stmt_obs->close();

    // 4. Preparar la actualización
    $sql = "UPDATE interacciones SET 
                fecha_programada = ?,
                estado = 'reagendado',
                observaciones = ?,
                updated_at = ?
            WHERE id = ?";

    // Añadir el motivo a las observaciones
    $nuevas_observaciones = $observaciones_actuales . 
                            "\n--- REPROGRAMADO (" . date('d/m/Y H:i') . ") ---\n" . 
                            $motivo . 
                            "\n----------------------------------\n";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("sssi", $fecha_programada_sql, $nuevas_observaciones, $fecha_actual, $interaccion_id);

    // 5. Ejecutar y verificar
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = [
                'success' => true, 
                'message' => '¡Interacción reprogramada con éxito!'
            ];
        } else {
            // No falló, pero no se actualizó (quizás los datos eran los mismos o el ID no existía)
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