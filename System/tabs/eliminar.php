<?php
include '../bd/conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Obtener información del tab para eliminar la imagen
    $sql_select = "SELECT imagen FROM tabs WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $tab = $result->fetch_assoc();
    $stmt_select->close();
    
    // Eliminar la imagen si existe y no es una imagen predeterminada
    if (!empty($tab['imagen']) && file_exists("../" . $tab['imagen']) && 
        !str_contains($tab['imagen'], 'choose-us-image')) {
        unlink("../" . $tab['imagen']);
    }
    
    // Eliminar el registro de la base de datos
    $sql = "DELETE FROM tabs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Tab eliminado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar el tab: " . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "ID no proporcionado"]);
}

$conn->close();
?>