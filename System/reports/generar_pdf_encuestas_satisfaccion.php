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
$datosEncuestas = isset($_POST['datosEncuestas']) ? json_decode($_POST['datosEncuestas'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosEncuestas)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de encuestas.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosEncuestas);
$tipos = [];
$estados = ['Activa' => 0, 'Inactiva' => 0, 'Programada' => 0, 'Finalizada' => 0];
$dirigidos = ['Padres' => 0, 'Estudiantes' => 0, 'Exalumnos' => 0, 'General' => 0];
$total_respuestas = 0;
$puntajes_acumulados = 0;
$contador_puntajes = 0;

foreach ($datosEncuestas as $row) {
    // Contar tipos (columna 2)
    $tipo = ucfirst(trim($row[2]));
    if (!isset($tipos[$tipo])) {
        $tipos[$tipo] = 0;
    }
    $tipos[$tipo]++;
    
    // Contar dirigidos (columna 3)
    $dirigido = ucfirst(trim($row[3]));
    if (isset($dirigidos[$dirigido])) {
        $dirigidos[$dirigido]++;
    }
    
    // Contar estados (columna 4)
    $estado = ucfirst(trim($row[4]));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Sumar respuestas (columna 6 - formato: "XX total")
    $respuestas_texto = trim($row[6]);
    if (preg_match('/(\d+)\s+total/', $respuestas_texto, $matches)) {
        $total_respuestas += intval($matches[1]);
    }
    
    // Sumar puntajes (columna 7)
    $puntaje = floatval(trim($row[7]));
    if ($puntaje > 0) {
        $puntajes_acumulados += $puntaje;
        $contador_puntajes++;
    }
}

$puntaje_promedio = $contador_puntajes > 0 ? number_format($puntajes_acumulados / $contador_puntajes, 1) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-encuestas {
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

    #tabla-encuestas td, #tabla-encuestas th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-encuestas th {
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
        font-size: 8px;
        text-align: center;
        border-right: 1px solid #999;
    }

    .estadisticas-resumen td:last-child {
        border-right: none;
    }

    .stat-numero {
        font-size: 12px;
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
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .tipo-satisfaccion { background-color: #28a745; }
    .tipo-feedback { background-color: #17a2b8; }
    .tipo-evento { background-color: #ffc107; color: #856404; }
    .tipo-general { background-color: #6c757d; }

    .badge-dirigido {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .dirigido-padres { background-color: #6f42c1; }
    .dirigido-estudiantes { background-color: #20c997; }
    .dirigido-exalumnos { background-color: #fd7e14; }
    .dirigido-general { background-color: #6c757d; }

    .badge-estado {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .estado-activa { background-color: #28a745; }
    .estado-inactiva { background-color: #6c757d; }
    .estado-programada { background-color: #ffc107; color: #856404; }
    .estado-finalizada { background-color: #dc3545; }

    .puntaje-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .puntaje-excelente { background-color: #28a745; color: white; }
    .puntaje-bueno { background-color: #20c997; color: white; }
    .puntaje-regular { background-color: #ffc107; color: #856404; }
    .puntaje-malo { background-color: #fd7e14; color: white; }
    .puntaje-pesimo { background-color: #dc3545; color: white; }

    .tasa-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 6px;
        font-weight: bold;
    }

    .tasa-alta { background-color: #d4edda; color: #155724; }
    .tasa-media { background-color: #fff3cd; color: #856404; }
    .tasa-baja { background-color: #f8d7da; color: #721c24; }

    .col-id { width: 3%; }
    .col-encuesta { width: 17%; }
    .col-tipo { width: 8%; }
    .col-dirigido { width: 9%; }
    .col-estado { width: 8%; }
    .col-fechas { width: 13%; }
    .col-respuestas { width: 11%; }
    .col-puntajes { width: 11%; }
    .col-tasa { width: 9%; }
    .col-creacion { width: 11%; }

    .tipos-resumen {
        background-color: #f8f9fa;
        padding: 8px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        font-size: 8px;
        text-align: center;
    }
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
                <h4>ENCUESTAS DE SATISFACCIÓN</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Resumidas**
$html .= '<div class="estadisticas-resumen">
    <table>
        <tr>
            <td>
                <span class="stat-numero">' . $total_registros . '</span>
                <span class="stat-label">Total Encuestas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $estados['Activa'] . '</span>
                <span class="stat-label">Activas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #ffc107;">' . $estados['Programada'] . '</span>
                <span class="stat-label">Programadas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $estados['Finalizada'] . '</span>
                <span class="stat-label">Finalizadas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . number_format($total_respuestas) . '</span>
                <span class="stat-label">Total Respuestas</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #6f42c1;">' . $dirigidos['Padres'] . '</span>
                <span class="stat-label">Para Padres</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $puntaje_promedio . '</span>
                <span class="stat-label">Puntaje Promedio</span>
            </td>
        </tr>
    </table>
</div>';

// **Tipos de encuestas**
$tipos_html = '<div class="tipos-resumen"><strong>Tipos de Encuestas:</strong> ';
foreach ($tipos as $tipo => $cant) {
    $tipos_html .= htmlspecialchars($tipo) . ' (' . $cant . ') | ';
}
$tipos_html = rtrim($tipos_html, ' | ');
$tipos_html .= '</div>';
$html .= $tipos_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE ENCUESTAS</div>';
$html .= '<table id="tabla-encuestas">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-encuesta">Encuesta</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-dirigido">Dirigido A</th>
            <th class="col-estado">Estado</th>
            <th class="col-fechas">Fechas</th>
            <th class="col-respuestas">Respuestas</th>
            <th class="col-puntajes">Puntajes</th>
            <th class="col-tasa">Tasa Resp.</th>
            <th class="col-creacion">Creación</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosEncuestas as $row) {
    // Determinar clases
    $tipo = strtolower(trim($row[2]));
    $tipoClass = 'tipo-' . $tipo;
    
    $dirigido = strtolower(trim($row[3]));
    $dirigidoClass = 'dirigido-' . $dirigido;
    
    $estado = strtolower(trim($row[4]));
    $estadoClass = 'estado-' . $estado;
    
    // Determinar clase de puntaje
    $puntaje = floatval(trim($row[7]));
    if ($puntaje >= 4.5) $puntajeClass = 'puntaje-excelente';
    elseif ($puntaje >= 4.0) $puntajeClass = 'puntaje-bueno';
    elseif ($puntaje >= 3.0) $puntajeClass = 'puntaje-regular';
    elseif ($puntaje >= 2.0) $puntajeClass = 'puntaje-malo';
    else $puntajeClass = 'puntaje-pesimo';
    
    // Determinar clase de tasa
    $tasa = floatval(str_replace('%', '', trim($row[8])));
    if ($tasa >= 70) $tasaClass = 'tasa-alta';
    elseif ($tasa >= 40) $tasaClass = 'tasa-media';
    else $tasaClass = 'tasa-baja';
    
    // Truncar textos largos
    $encuesta = $row[1];
    if (strlen($encuesta) > 120) {
        $encuesta = substr($encuesta, 0, 117) . '...';
    }
    
    $fechas = $row[5];
    if (strlen($fechas) > 80) {
        $fechas = substr($fechas, 0, 77) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-encuesta" style="font-size: 6px;">' . htmlspecialchars($encuesta) . '</td>
        <td class="col-tipo" style="text-align: center;">
            <span class="badge-tipo ' . $tipoClass . '">' . strtoupper(htmlspecialchars($row[2])) . '</span>
        </td>
        <td class="col-dirigido" style="text-align: center;">
            <span class="badge-dirigido ' . $dirigidoClass . '">' . strtoupper(htmlspecialchars($row[3])) . '</span>
        </td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars($row[4])) . '</span>
        </td>
        <td class="col-fechas" style="font-size: 6px;">' . htmlspecialchars($fechas) . '</td>
        <td class="col-respuestas" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[6]) . '</td>
        <td class="col-puntajes" style="text-align: center;">
            <span class="puntaje-badge ' . $puntajeClass . '">' . htmlspecialchars($row[7]) . '</span>
        </td>
        <td class="col-tasa" style="text-align: center;">
            <span class="tasa-badge ' . $tasaClass . '">' . htmlspecialchars($row[8]) . '</span>
        </td>
        <td class="col-creacion" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[9]) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Activas:</strong> ' . $estados['Activa'] . ' | 
    <strong>Total Respuestas:</strong> ' . number_format($total_respuestas) . ' | 
    <strong>Puntaje Promedio:</strong> ' . $puntaje_promedio . '
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
$filename = 'Reporte_Encuestas_Satisfaccion_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>