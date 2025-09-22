<?php
include("system/bd/conexion.php"); // conexión con la BD

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre  = $conn->real_escape_string($_POST['name']);
    $email   = $conn->real_escape_string($_POST['email']);
    $mensaje = $conn->real_escape_string($_POST['message']);

    // Insertar en la tabla consultas
    $sql = "INSERT INTO consultas (nombre, email, mensaje) 
            VALUES ('$nombre', '$email', '$mensaje')";

    if ($conn->query($sql) === TRUE) {
        // Página con SweetAlert
        echo '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Registro</title>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: "success",
                    title: "¡Consulta enviada!",
                    text: "Tu mensaje ha sido registrado correctamente.",
                    confirmButtonText: "OK"
                }).then(() => {
                    window.location.href = "index.php"; 
                });
            </script>
        </body>
        </html>';
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
