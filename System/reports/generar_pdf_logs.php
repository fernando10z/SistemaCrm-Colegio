<?php
require_once '../vendor/autoload.php';
require_once '../bd/conexion.php';
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Lima'); // Configurar zona horaria

// Obtener datos del sistema desde configuración
$configQuery = "SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('nombre_institucion', 'email_principal', 'telefono_principal', 'imagen')";
$configResult = $conn->query($configQuery);

$config = [
    'nombre_institucion' => 'Sistema CRM',
    'email_principal' => 'info@sistema.com',
    'telefono_principal' => '+51 000000000',
    'imagen' => 'assets/images/logocolgio.jpeg'
];

if ($configResult && $configResult->num_rows > 0) {
    while ($row = $configResult->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }
}

// Construir ruta completa de la imagen
$rutaImagen = '../' . $config['imagen']; 

// Convertir imagen a base64 para DomPDF
$imagenBase64 = '';
if (file_exists($rutaImagen)) {
    $imageData = file_get_contents($rutaImagen);
    $imagenBase64 = 'data:image/' . pathinfo($rutaImagen, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
} else {
    // Si no existe, usar placeholder
    $imagenBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#007bff"/><text x="50%" y="50%" fill="white" text-anchor="middle" dy=".3em" font-size="20">LOGO</text></svg>');
}

// Obtener rol del usuario desde sesión
session_start();
$rolUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : "Desconocido";
$roles = [1 => "Administrador", 2 => "Coordinador Marketing", 3 => "Tutor", 4 => "Finanzas"];
$nombreRol = isset($roles[$rolUsuario]) ? $roles[$rolUsuario] : "Usuario del Sistema";

// Obtener los datos filtrados desde la tabla
$filteredData = isset($_POST['filteredData']) ? json_decode($_POST['filteredData'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($filteredData)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de logs.');</script>");
}

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-logs {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    #tabla-cabecera {
        text-align: center;
        letter-spacing: 0.5px;
        color: #333;
    }

    #tabla-cabecera h3 {
        font-size: 18px;
        margin-bottom: 2px;
        color: #444;
    }

    .logo-empresa {
        border: 1px solid #666;
        border-radius: 20px;
        text-align: center;
        padding: 12px;
        display: inline-block;
    }

    .info-empresa {
        font-size: 13px;
        color: #666;
    }

    .reporte-titulo {
        border: 1px solid #666;
        border-radius: 20px;
        text-align: center;
        padding: 12px;
        display: inline-block;
        background-color: #f8f9fa;
    }

    #tabla-logs td, #tabla-logs th {
        border: 0.5px solid #333;
        padding: 8px;
        font-size: 10px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-logs th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .seccion-titulo {
        background-color: #f2f2f2;
        padding: 15px;
        margin: 25px 0 15px 0;
        font-weight: bold;
        border-radius: 5px;
        font-size: 14px;
        text-align: center;
    }

    .pie-pagina {
        margin-top: 25px;
        padding: 12px;
        font-size: 11px;
        border: 0.5px solid #333;
        border-radius: 10px;
        text-align: center;
        background-color: #f8f9fa;
    }

    .resultado-exitoso {
        color: #28a745;
        font-weight: bold;
    }

    .resultado-fallido {
        color: #dc3545;
        font-weight: bold;
    }

    .usuario-info {
        font-size: 10px;
    }

    .usuario-nombre {
        font-weight: bold;
        color: #495057;
    }

    .usuario-username {
        font-style: italic;
        color: #6c757d;
    }

    .ip-address {
        font-family: "Courier New", monospace;
        background-color: #f8f9fa;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 9px;
    }

    .accion-badge {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        color: white;
    }

    .accion-login { background-color: #007bff; }
    .accion-logout { background-color: #6c757d; }
    .accion-editar { background-color: #fd7e14; }
    .accion-crear { background-color: #28a745; }
    .accion-eliminar { background-color: #dc3545; }
    .accion-otro { background-color: #6f42c1; }

    .detalles-truncados {
        font-size: 9px;
        color: #6c757d;
        max-height: 30px;
        overflow: hidden;
    }

    .fecha-log {
        font-size: 9px;
        color: #495057;
    }

    .estadisticas-resumen {
        background-color: #e9ecef;
        padding: 10px;
        margin: 15px 0;
        border-radius: 5px;
        font-size: 11px;
    }

    .col-id { width: 5%; }
    .col-usuario { width: 15%; }
    .col-ip { width: 10%; }
    .col-accion { width: 12%; }
    .col-resultado { width: 8%; }
    .col-detalles { width: 30%; }
    .col-useragent { width: 12%; }
    .col-fecha { width: 8%; }
</style>';

// **Cabecera del reporte**
$html .= '<table id="tabla-cabecera">
    <tr>
        <td class="logo-empresa" style="width: 30%;">
            <img src="' . $imagenBase64 . '" alt="Logo" style="max-width: 100px; max-height: 100px; object-fit: contain;">
        </td>
        <td style="width: 40%;">
            <h3>' . htmlspecialchars($config['nombre_institucion']) . '</h3>
            <div class="info-empresa">Sistema de Gestión CRM Escolar</div>
            <div class="info-empresa">' . htmlspecialchars($config['email_principal']) . '</div>
            <div class="info-empresa">Telf. ' . htmlspecialchars($config['telefono_principal']) . '</div>
        </td>
        <td style="width: 30%;">
            <div class="reporte-titulo">
                <h4>REPORTE DE LOGS DE ACCESO</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Sección de listado de logs**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE LOGS DE ACCESO</div>';
$html .= '<table id="tabla-logs">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-usuario">Usuario</th>
            <th class="col-ip">IP</th>
            <th class="col-accion">Acción</th>
            <th class="col-resultado">Resultado</th>
            <th class="col-detalles">Detalles</th>
            <th class="col-useragent">User Agent</th>
            <th class="col-fecha">Fecha</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Procesar datos de usuario (viene como "Nombre Apellido @username")
    $usuarioCompleto = $row[1];
    $partes = explode(' @', $usuarioCompleto);
    $nombreUsuario = isset($partes[0]) ? $partes[0] : $usuarioCompleto;
    $username = isset($partes[1]) ? '@' . $partes[1] : '';

    // Determinar clase de resultado
    $resultado = strtolower(trim($row[4]));
    $resultadoClass = '';
    if (stripos($resultado, 'exitoso') !== false) {
        $resultadoClass = 'resultado-exitoso';
        $resultadoTexto = 'EXITOSO';
    } elseif (stripos($resultado, 'fallido') !== false) {
        $resultadoClass = 'resultado-fallido';
        $resultadoTexto = 'FALLIDO';
    } else {
        $resultadoTexto = strtoupper($resultado);
    }

    // Determinar clase de acción
    $accion = strtolower($row[3]);
    $accionClass = 'accion-otro';
    if (strpos($accion, 'login') !== false) $accionClass = 'accion-login';
    elseif (strpos($accion, 'logout') !== false) $accionClass = 'accion-logout';
    elseif (strpos($accion, 'editar') !== false) $accionClass = 'accion-editar';
    elseif (strpos($accion, 'crear') !== false) $accionClass = 'accion-crear';
    elseif (strpos($accion, 'eliminar') !== false) $accionClass = 'accion-eliminar';

    // Truncar detalles largos
    $detalles = $row[5];
    if (strlen($detalles) > 100) {
        $detalles = substr($detalles, 0, 97) . '...';
    }

    // Truncar User Agent
    $userAgent = $row[6];
    if (strlen($userAgent) > 50) {
        $userAgent = substr($userAgent, 0, 47) . '...';
    }

    $html .= '<tr>
        <td class="col-id">' . htmlspecialchars($row[0]) . '</td>
        <td class="col-usuario">
            <div class="usuario-info">
                <div class="usuario-nombre">' . htmlspecialchars($nombreUsuario) . '</div>
                <div class="usuario-username">' . htmlspecialchars($username) . '</div>
            </div>
        </td>
        <td class="col-ip">
            <span class="ip-address">' . htmlspecialchars($row[2]) . '</span>
        </td>
        <td class="col-accion">
            <span class="accion-badge ' . $accionClass . '">' . strtoupper(htmlspecialchars($row[3])) . '</span>
        </td>
        <td class="col-resultado">
            <span class="' . $resultadoClass . '">' . $resultadoTexto . '</span>
        </td>
        <td class="col-detalles">
            <div class="detalles-truncados">' . htmlspecialchars($detalles) . '</div>
        </td>
        <td class="col-useragent" style="font-size: 8px; color: #6c757d;">
            ' . htmlspecialchars($userAgent) . '
        </td>
        <td class="col-fecha">
            <div class="fecha-log">' . htmlspecialchars($row[7]) . '</div>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
</div>';

// **Configurar DomPDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para más columnas
$dompdf->render();

// **Enviar PDF al navegador**
$filename = 'Reporte_Logs_Acceso_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>