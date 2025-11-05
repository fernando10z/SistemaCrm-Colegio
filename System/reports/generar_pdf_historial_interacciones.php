<?php
require_once '../vendor/autoload.php';
require_once '../bd/conexion.php';
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Lima');

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
$datosInteracciones = isset($_POST['datosInteracciones']) ? json_decode($_POST['datosInteracciones'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosInteracciones)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de interacciones.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosInteracciones);
$estados = ['Programado' => 0, 'Realizado' => 0, 'Cancelado' => 0, 'Reagendado' => 0];
$tipos = [];
$con_seguimiento = 0;
$seguimientos_vencidos = 0;
$duracion_total = 0;
$contador_duracion = 0;

foreach ($datosInteracciones as $row) {
    // Contar estados (columna 5)
    $estado = ucfirst(trim($row[5]));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Contar tipos (columna 1)
    $tipo = trim($row[1]);
    if (!isset($tipos[$tipo])) {
        $tipos[$tipo] = 0;
    }
    $tipos[$tipo]++;
    
    // Seguimientos (columna 9)
    $seguimiento = trim($row[9]);
    if (stripos($seguimiento, 'Sí requiere') !== false) {
        $con_seguimiento++;
        if (stripos($seguimiento, 'Vencido') !== false) {
            $seguimientos_vencidos++;
        }
    }
    
    // Duración (columna 7)
    $duracion = trim($row[7]);
    if ($duracion != '-' && strpos($duracion, 'min') !== false) {
        $min = (int)filter_var($duracion, FILTER_SANITIZE_NUMBER_INT);
        $duracion_total += $min;
        $contador_duracion++;
    }
}

$duracion_promedio = $contador_duracion > 0 ? round($duracion_total / $contador_duracion, 0) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-interacciones {
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

    #tabla-interacciones td, #tabla-interacciones th {
        border: 0.5px solid #333;
        padding: 5px;
        font-size: 8px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-interacciones th {
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

    .estadisticas-resumen {
        background-color: #e9ecef;
        padding: 10px;
        margin: 15px 0;
        border-radius: 5px;
        border: 1px solid #666;
    }

    .estadisticas-resumen table {
        width: 100%;
        border-collapse: collapse;
    }

    .estadisticas-resumen td {
        padding: 5px;
        font-size: 9px;
        text-align: center;
        border-right: 1px solid #999;
    }

    .estadisticas-resumen td:last-child {
        border-right: none;
    }

    .stat-numero {
        font-size: 13px;
        font-weight: bold;
        color: #667eea;
        display: block;
    }

    .stat-label {
        font-size: 7px;
        color: #666;
    }

    .badge-tipo {
        padding: 2px 5px;
        border-radius: 8px;
        font-size: 7px;
        font-weight: bold;
        color: white;
        background-color: #6c757d;
    }

    .badge-estado {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 7px;
        font-weight: bold;
        color: white;
    }

    .estado-programado { background-color: #ffc107; color: #856404; }
    .estado-realizado { background-color: #28a745; }
    .estado-cancelado { background-color: #dc3545; }
    .estado-reagendado { background-color: #fd7e14; }

    .badge-resultado {
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .resultado-exitoso { background-color: #d4edda; color: #155724; }
    .resultado-sin-respuesta { background-color: #fff3cd; color: #856404; }
    .resultado-reagendar { background-color: #f8d7da; color: #721c24; }
    .resultado-no-interesado { background-color: #d1ecf1; color: #0c5460; }
    .resultado-convertido { background-color: #d4edda; color: #155724; }

    .seguimiento-urgente {
        color: #dc3545;
        font-weight: bold;
    }

    .seguimiento-normal {
        color: #28a745;
    }

    .duracion-badge {
        background-color: #e8f4fd;
        color: #0c5460;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .col-id { width: 3%; }
    .col-tipo { width: 8%; }
    .col-asunto { width: 18%; }
    .col-contacto { width: 12%; }
    .col-usuario { width: 10%; }
    .col-estado { width: 8%; }
    .col-fechas { width: 12%; }
    .col-duracion { width: 7%; }
    .col-resultado { width: 10%; }
    .col-seguimiento { width: 12%; }
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
                <h4>HISTORIAL DE INTERACCIONES</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Resumidas**
$tipos_html = '';
$contador = 0;
foreach ($tipos as $tipo => $cantidad) {
    if ($contador < 4) { // Mostrar solo los 4 primeros tipos
        $tipos_html .= '<strong>' . htmlspecialchars($tipo) . ':</strong> ' . $cantidad . ' | ';
        $contador++;
    }
}
$tipos_html = rtrim($tipos_html, ' | ');

$html .= '<div class="estadisticas-resumen">
    <table>
        <tr>
            <td>
                <span class="stat-numero">' . $total_registros . '</span>
                <span class="stat-label">Total Interacciones</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #ffc107;">' . $estados['Programado'] . '</span>
                <span class="stat-label">Programadas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $estados['Realizado'] . '</span>
                <span class="stat-label">Realizadas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $estados['Cancelado'] . '</span>
                <span class="stat-label">Canceladas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $con_seguimiento . '</span>
                <span class="stat-label">Con Seguimiento</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #fd7e14;">' . $seguimientos_vencidos . '</span>
                <span class="stat-label">Seg. Vencidos</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . $duracion_promedio . ' min</span>
                <span class="stat-label">Duración Promedio</span>
            </td>
        </tr>
    </table>
    <div style="text-align: center; margin-top: 8px; font-size: 8px; color: #666;">
        <strong>Tipos de Interacción:</strong> ' . $tipos_html . '
    </div>
</div>';

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE INTERACCIONES</div>';
$html .= '<table id="tabla-interacciones">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-asunto">Asunto / Descripción</th>
            <th class="col-contacto">Contacto</th>
            <th class="col-usuario">Usuario</th>
            <th class="col-estado">Estado</th>
            <th class="col-fechas">Fechas</th>
            <th class="col-duracion">Duración</th>
            <th class="col-resultado">Resultado</th>
            <th class="col-seguimiento">Seguimiento</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosInteracciones as $row) {
    // Determinar clase de estado
    $estado = strtolower(trim($row[5]));
    $estadoClass = 'estado-' . $estado;
    
    // Determinar clase de resultado
    $resultado = trim($row[8]);
    $resultadoClass = '';
    if (stripos($resultado, 'Exitoso') !== false) $resultadoClass = 'resultado-exitoso';
    elseif (stripos($resultado, 'Sin respuesta') !== false) $resultadoClass = 'resultado-sin-respuesta';
    elseif (stripos($resultado, 'Reagendar') !== false) $resultadoClass = 'resultado-reagendar';
    elseif (stripos($resultado, 'No interesado') !== false) $resultadoClass = 'resultado-no-interesado';
    elseif (stripos($resultado, 'Convertido') !== false) $resultadoClass = 'resultado-convertido';
    
    // Determinar clase de seguimiento
    $seguimiento = trim($row[9]);
    $seguimientoClass = '';
    if (stripos($seguimiento, 'Vencido') !== false) $seguimientoClass = 'seguimiento-urgente';
    else $seguimientoClass = 'seguimiento-normal';
    
    // Truncar textos largos
    $asunto = $row[2];
    if (strlen($asunto) > 100) {
        $asunto = substr($asunto, 0, 97) . '...';
    }
    
    $contacto = $row[3];
    if (strlen($contacto) > 60) {
        $contacto = substr($contacto, 0, 57) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-tipo" style="text-align: center;">
            <span class="badge-tipo">' . htmlspecialchars($row[1]) . '</span>
        </td>
        <td class="col-asunto" style="font-size: 7px;">' . htmlspecialchars($asunto) . '</td>
        <td class="col-contacto" style="font-size: 7px;">' . htmlspecialchars($contacto) . '</td>
        <td class="col-usuario" style="font-size: 7px;">' . htmlspecialchars($row[4]) . '</td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars($row[5])) . '</span>
        </td>
        <td class="col-fechas" style="font-size: 7px;">' . htmlspecialchars($row[6]) . '</td>
        <td class="col-duracion" style="text-align: center;">' . 
            ($row[7] != '-' ? '<span class="duracion-badge">' . htmlspecialchars($row[7]) . '</span>' : '-') . 
        '</td>
        <td class="col-resultado" style="text-align: center;">' . 
            ($resultado != '-' ? '<span class="badge-resultado ' . $resultadoClass . '">' . htmlspecialchars($resultado) . '</span>' : '-') . 
        '</td>
        <td class="col-seguimiento" style="font-size: 7px;">
            <span class="' . $seguimientoClass . '">' . htmlspecialchars($seguimiento) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Realizadas:</strong> ' . $estados['Realizado'] . ' | 
    <strong>Programadas:</strong> ' . $estados['Programado'] . ' | 
    <strong>Con Seguimiento:</strong> ' . $con_seguimiento . '
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
$filename = 'Reporte_Historial_Interacciones_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>