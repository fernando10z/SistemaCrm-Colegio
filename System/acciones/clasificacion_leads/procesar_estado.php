<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión a la base de datos
require_once '../../bd/conexion.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Verificar que exista la acción
if (!isset($_POST['accion'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acción no especificada'
    ]);
    exit;
}

$accion = $_POST['accion'];

// ====================== CREAR ESTADO ======================
if ($accion === 'crear') {
    try {
        // Validar campos obligatorios
        if (empty($_POST['nombre'])) {
            throw new Exception('El nombre del estado es obligatorio');
        }

        if (empty($_POST['color'])) {
            throw new Exception('El color del estado es obligatorio');
        }

        if (!isset($_POST['orden_display']) || $_POST['orden_display'] < 1) {
            throw new Exception('El orden de visualización es obligatorio');
        }

        // Sanitizar y validar datos
        $nombre = trim($_POST['nombre']);
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
        $color = trim($_POST['color']);
        $orden_display = intval($_POST['orden_display']);
        $es_final = isset($_POST['es_final']) ? 1 : 0;

        // Validar formato de color hexadecimal
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            throw new Exception('El formato del color no es válido');
        }

        // Verificar que no exista un estado con el mismo nombre
        $stmt_check = $conn->prepare("SELECT id FROM estados_lead WHERE nombre = ? AND activo = 1");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception('Ya existe un estado con este nombre');
        }
        $stmt_check->close();

        // Insertar el nuevo estado
        $stmt = $conn->prepare("INSERT INTO estados_lead (nombre, descripcion, color, orden_display, es_final, activo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssii", $nombre, $descripcion, $color, $orden_display, $es_final);
        
        if ($stmt->execute()) {
            $nuevo_id = $stmt->insert_id;
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Estado creado exitosamente',
                'id' => $nuevo_id
            ]);
        } else {
            throw new Exception('Error al crear el estado: ' . $stmt->error);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ====================== EDITAR ESTADO ======================
else if ($accion === 'editar') {
    try {
        // Validar campos obligatorios
        if (empty($_POST['id'])) {
            throw new Exception('ID del estado no especificado');
        }

        if (empty($_POST['nombre'])) {
            throw new Exception('El nombre del estado es obligatorio');
        }

        if (empty($_POST['color'])) {
            throw new Exception('El color del estado es obligatorio');
        }

        // Sanitizar y validar datos
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
        $color = trim($_POST['color']);
        $orden_display = intval($_POST['orden_display']);
        $es_final = isset($_POST['es_final']) ? 1 : 0;

        // Validar formato de color hexadecimal
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            throw new Exception('El formato del color no es válido');
        }

        // Verificar que no exista otro estado con el mismo nombre
        $stmt_check = $conn->prepare("SELECT id FROM estados_lead WHERE nombre = ? AND id != ? AND activo = 1");
        $stmt_check->bind_param("si", $nombre, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception('Ya existe otro estado con este nombre');
        }
        $stmt_check->close();

        // Actualizar el estado
        $stmt = $conn->prepare("UPDATE estados_lead SET nombre = ?, descripcion = ?, color = ?, orden_display = ?, es_final = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $nombre, $descripcion, $color, $orden_display, $es_final, $id);
        
        if ($stmt->execute()) {
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ]);
        } else {
            throw new Exception('Error al actualizar el estado: ' . $stmt->error);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ====================== ELIMINAR ESTADO ======================
else if ($accion === 'eliminar') {
    try {
        // Validar ID
        if (empty($_POST['id'])) {
            throw new Exception('ID del estado no especificado');
        }

        $id = intval($_POST['id']);

        // Verificar que el estado no esté siendo usado por ningún lead
        $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE estado_lead_id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        
        if ($row_check['total'] > 0) {
            throw new Exception('No se puede eliminar el estado porque está siendo usado por ' . $row_check['total'] . ' lead(s)');
        }
        $stmt_check->close();

        // Realizar eliminación lógica (cambiar activo a 0)
        $stmt = $conn->prepare("UPDATE estados_lead SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Estado eliminado exitosamente'
            ]);
        } else {
            throw new Exception('Error al eliminar el estado: ' . $stmt->error);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


// ====================== DESACTIVAR ESTADO ======================
else if ($accion === 'desactivar') {
    try {
        // Validar ID
        if (empty($_POST['id'])) {
            throw new Exception('ID del estado no especificado');
        }

        $id = intval($_POST['id']);

        // Verificar que el estado no esté siendo usado por ningún lead
        $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE estado_lead_id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        
        if ($row_check['total'] > 0) {
            throw new Exception('No se puede desactivar el estado porque tiene ' . $row_check['total'] . ' lead(s) asociados');
        }
        $stmt_check->close();

        // Desactivar el estado
        $stmt = $conn->prepare("UPDATE estados_lead SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Estado desactivado exitosamente'
            ]);
        } else {
            throw new Exception('Error al desactivar el estado: ' . $stmt->error);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ====================== ACCIÓN NO VÁLIDA ======================
else {
    echo json_encode([
        'success' => false,
        'message' => 'Acción no válida'
    ]);
}


$conn->close();
?>