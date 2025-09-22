<?php
include("system/bd/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $telefono = $conn->real_escape_string($_POST['phone']);

    $sql = "INSERT INTO registros (nombre, email, telefono) 
            VALUES ('$nombre', '$email', '$telefono')";

    if ($conn->query($sql) === TRUE) {
        // Mostramos página con SweetAlert
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
                    title: "¡Guardado correctamente!",
                    text: "Tu registro se ha completado con éxito",
                    confirmButtonText: "OK"
                }).then(() => {
                    window.location.href = "index.php"; // cambia index.php por tu página
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
