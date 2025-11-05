<?php
header('Content-Type: application/json; charset=utf-8');
include '../bd/conexion.php';

// Validar que se recibieron los datos necesarios
if (!isset($_POST['apoderado_id']) || empty($_POST['apoderado_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de apoderado no proporcionado']);
    exit;
}

$apoderado_id = intval($_POST['apoderado_id']);
$familia_id = intval($_POST['familia_id']);
$tipo_apoderado = trim($_POST['tipo_apoderado']);
$tipo_documento = trim($_POST['tipo_documento']);
$numero_documento = trim($_POST['numero_documento']);
$nombres = trim($_POST['nombres']);
$apellidos = trim($_POST['apellidos']);
$fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
$genero = !empty($_POST['genero']) ? $_POST['genero'] : null;
$email = !empty($_POST['email']) ? trim($_POST['email']) : null;
$telefono_principal = trim($_POST['telefono_principal']);
$telefono_secundario = !empty($_POST['telefono_secundario']) ? trim($_POST['telefono_secundario']) : null;
$whatsapp = trim($_POST['whatsapp']);
$ocupacion = !empty($_POST['ocupacion']) ? trim($_POST['ocupacion']) : null;
$empresa = !empty($_POST['empresa']) ? trim($_POST['empresa']) : null;
$nivel_educativo = !empty($_POST['nivel_educativo']) ? trim($_POST['nivel_educativo']) : null;
$estado_civil = !empty($_POST['estado_civil']) ? $_POST['estado_civil'] : null;
$nivel_compromiso = trim($_POST['nivel_compromiso']);
$nivel_participacion = trim($_POST['nivel_participacion']);
$preferencia_contacto = trim($_POST['preferencia_contacto']);

try {
    // Verificar que el apoderado existe
    $check_sql = "SELECT id FROM apoderados WHERE id = ? AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $apoderado_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Apoderado no encontrado']);
        exit;
    }
    
    // Verificar si el documento ya existe en otro apoderado
    $doc_check_sql = "SELECT id FROM apoderados WHERE numero_documento = ? AND tipo_documento = ? AND id != ? AND activo = 1";
    $doc_check_stmt = $conn->prepare($doc_check_sql);
    $doc_check_stmt->bind_param("ssi", $numero_documento, $tipo_documento, $apoderado_id);
    $doc_check_stmt->execute();
    if ($doc_check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otro apoderado registrado con este documento']);
        exit;
    }
    
    // Actualizar apoderado
    $sql = "UPDATE apoderados SET 
            familia_id = ?,
            tipo_apoderado = ?,
            tipo_documento = ?,
            numero_documento = ?,
            nombres = ?,
            apellidos = ?,
            fecha_nacimiento = ?,
            genero = ?,
            email = ?,
            telefono_principal = ?,
            telefono_secundario = ?,
            whatsapp = ?,
            ocupacion = ?,
            empresa = ?,
            nivel_educativo = ?,
            estado_civil = ?,
            nivel_compromiso = ?,
            nivel_participacion = ?,
            preferencia_contacto = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    // CORRECCIÓN: Cambiar de "isssssssssssssssssi" a "issssssssssssssssssi"
    // Son 20 parámetros: 1 int + 18 strings + 1 int = "issssssssssssssssssi"
    $stmt->bind_param("issssssssssssssssssi", 
        $familia_id,            // 1 - int (i)
        $tipo_apoderado,        // 2 - string (s)
        $tipo_documento,        // 3 - string (s)
        $numero_documento,      // 4 - string (s)
        $nombres,               // 5 - string (s)
        $apellidos,             // 6 - string (s)
        $fecha_nacimiento,      // 7 - string (s)
        $genero,                // 8 - string (s)
        $email,                 // 9 - string (s)
        $telefono_principal,    // 10 - string (s)
        $telefono_secundario,   // 11 - string (s)
        $whatsapp,              // 12 - string (s)
        $ocupacion,             // 13 - string (s)
        $empresa,               // 14 - string (s)
        $nivel_educativo,       // 15 - string (s)
        $estado_civil,          // 16 - string (s)
        $nivel_compromiso,      // 17 - string (s)
        $nivel_participacion,   // 18 - string (s)
        $preferencia_contacto,  // 19 - string (s)
        $apoderado_id           // 20 - int (i)
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Apoderado actualizado correctamente',
            'id' => $apoderado_id,
            'nombre_completo' => $nombres . ' ' . $apellidos
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>