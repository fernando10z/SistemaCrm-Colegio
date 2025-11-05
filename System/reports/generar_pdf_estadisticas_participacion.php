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
$datosParticipacion = isset($_POST['datosParticipacion']) ? json_decode($_POST['datosParticipacion'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($datosParticipacion)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de participación.');</script>");
}

// Calcular estadísticas
$total_registros = count($datosParticipacion);
$tipos_evento = [];
$estados = ['Invitado' => 0, 'Confirmado' => 0, 'Asistio' => 0, 'No asistio' => 0, 'Cancelado' => 0];
$niveles_participacion = ['Alta' => 0, 'Media' => 0, 'Baja' => 0];
$total_asistencias = 0;
$total_ausencias = 0;
$suma_porcentajes = 0;
$contador_porcentajes = 0;

foreach ($datosParticipacion as $row) {
    // Contar tipos de evento (columna 2)
    $tipo = ucfirst(trim(str_replace('_', ' ', $row[2])));
    if (!isset($tipos_evento[$tipo])) {
        $tipos_evento[$tipo] = 0;
    }
    $tipos_evento[$tipo]++;
    
    // Contar estados (columna 4)
    $estado = ucfirst(trim(str_replace('_', ' ', $row[4])));
    if (isset($estados[$estado])) {
        $estados[$estado]++;
    }
    
    // Contar asistencias vs ausencias
    if (stripos($estado, 'Asistio') !== false) {
        $total_asistencias++;
    } elseif (stripos($estado, 'No asistio') !== false) {
        $total_ausencias++;
    }
    
    // Contar niveles de participación (columna 8)
    $nivel = ucfirst(trim($row[8]));
    if (isset($niveles_participacion[$nivel])) {
        $niveles_participacion[$nivel]++;
    }
    
    // Sumar porcentajes (columna 7)
    $porcentaje = floatval(str_replace('%', '', trim($row[7])));
    if ($porcentaje > 0) {
        $suma_porcentajes += $porcentaje;
        $contador_porcentajes++;
    }
}

$porcentaje_asistencia_promedio = $contador_porcentajes > 0 ? number_format($suma_porcentajes / $contador_porcentajes, 1) : 0;
$tasa_asistencia_general = $total_registros > 0 ? number_format(($total_asistencias / $total_registros) * 100, 1) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-participacion {
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

    #tabla-participacion td, #tabla-participacion th {
        border: 0.5px solid #333;
        padding: 4px;
        font-size: 7px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-participacion th {
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

    .badge-tipo-evento {
        padding: 2px 5px;
        border-radius: 8px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .tipo-reunion-padres { background-color: #6f42c1; }
    .tipo-charla-informativa { background-color: #17a2b8; }
    .tipo-evento-social { background-color: #20c997; }
    .tipo-academico { background-color: #fd7e14; }
    .tipo-deportivo { background-color: #28a745; }
    .tipo-otro { background-color: #6c757d; }

    .badge-estado {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
        color: white;
    }

    .estado-invitado { background-color: #6c757d; }
    .estado-confirmado { background-color: #17a2b8; }
    .estado-asistio { background-color: #28a745; }
    .estado-no-asistio { background-color: #dc3545; }
    .estado-cancelado { background-color: #fd7e14; }

    .badge-nivel {
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 6px;
        font-weight: bold;
    }

    .nivel-alta { background-color: #28a745; color: white; }
    .nivel-media { background-color: #ffc107; color: #856404; }
    .nivel-baja { background-color: #dc3545; color: white; }

    .porcentaje-badge {
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 7px;
        font-weight: bold;
    }

    .asistencia-excelente { background-color: #d4edda; color: #155724; }
    .asistencia-buena { background-color: #d1ecf1; color: #0c5460; }
    .asistencia-regular { background-color: #fff3cd; color: #856404; }
    .asistencia-baja { background-color: #f8d7da; color: #721c24; }

    .col-id { width: 3%; }
    .col-evento { width: 16%; }
    .col-tipo { width: 9%; }
    .col-participante { width: 13%; }
    .col-estado { width: 9%; }
    .col-fechas { width: 11%; }
    .col-metricas { width: 9%; }
    .col-porcentaje { width: 8%; }
    .col-nivel { width: 8%; }
    .col-observaciones { width: 14%; }

    .distribucion-resumen {
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
                <h4>ESTADÍSTICAS DE PARTICIPACIÓN</h4>
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
                <span class="stat-label">Total Registros</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $total_asistencias . '</span>
                <span class="stat-label">Asistencias</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #dc3545;">' . $total_ausencias . '</span>
                <span class="stat-label">Ausencias</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #17a2b8;">' . $estados['Confirmado'] . '</span>
                <span class="stat-label">Confirmados</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #6c757d;">' . $estados['Invitado'] . '</span>
                <span class="stat-label">Invitados</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #28a745;">' . $tasa_asistencia_general . '%</span>
                <span class="stat-label">Tasa Asistencia</span>
            </td>
            <td>
                <span class="stat-numero" style="color: #764ba2;">' . $porcentaje_asistencia_promedio . '%</span>
                <span class="stat-label">Promedio Eventos</span>
            </td>
        </tr>
    </table>
</div>';

// **Distribución por tipo de evento y nivel**
$distribucion_html = '<div class="distribucion-resumen"><strong>Tipos de Eventos:</strong> ';
foreach ($tipos_evento as $tipo => $cant) {
    $distribucion_html .= htmlspecialchars($tipo) . ' (' . $cant . ') | ';
}
$distribucion_html = rtrim($distribucion_html, ' | ');
$distribucion_html .= ' | <strong>Niveles de Participación:</strong> ';
foreach ($niveles_participacion as $niv => $cant) {
    $distribucion_html .= htmlspecialchars($niv) . ' (' . $cant . ') | ';
}
$distribucion_html = rtrim($distribucion_html, ' | ');
$distribucion_html .= '</div>';
$html .= $distribucion_html;

// **Sección de listado**
$html .= '<div class="seccion-titulo">REGISTRO DETALLADO DE PARTICIPACIÓN EN EVENTOS</div>';
$html .= '<table id="tabla-participacion">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-evento">Evento</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-participante">Participante</th>
            <th class="col-estado">Estado</th>
            <th class="col-fechas">Fechas</th>
            <th class="col-metricas">Métricas</th>
            <th class="col-porcentaje">% Asist.</th>
            <th class="col-nivel">Nivel Fam.</th>
            <th class="col-observaciones">Observaciones</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datosParticipacion as $row) {
    // Determinar clases
    $tipo = strtolower(str_replace(' ', '-', trim($row[2])));
    $tipoClass = 'tipo-' . $tipo;
    
    $estado = strtolower(str_replace(' ', '-', trim($row[4])));
    $estadoClass = 'estado-' . $estado;
    
    $nivel = strtolower(trim($row[8]));
    $nivelClass = 'nivel-' . $nivel;
    
    // Determinar clase de porcentaje
    $porcentaje = floatval(str_replace('%', '', trim($row[7])));
    if ($porcentaje >= 80) $porcentajeClass = 'asistencia-excelente';
    elseif ($porcentaje >= 60) $porcentajeClass = 'asistencia-buena';
    elseif ($porcentaje >= 40) $porcentajeClass = 'asistencia-regular';
    else $porcentajeClass = 'asistencia-baja';
    
    // Truncar textos largos
    $evento = $row[1];
    if (strlen($evento) > 100) {
        $evento = substr($evento, 0, 97) . '...';
    }
    
    $participante = $row[3];
    if (strlen($participante) > 80) {
        $participante = substr($participante, 0, 77) . '...';
    }
    
    $fechas = $row[5];
    if (strlen($fechas) > 70) {
        $fechas = substr($fechas, 0, 67) . '...';
    }
    
    $observaciones = $row[9];
    if (strlen($observaciones) > 80) {
        $observaciones = substr($observaciones, 0, 77) . '...';
    }
    
    $html .= '<tr>
        <td class="col-id" style="text-align: center;"><strong>' . htmlspecialchars($row[0]) . '</strong></td>
        <td class="col-evento" style="font-size: 6px;">' . htmlspecialchars($evento) . '</td>
        <td class="col-tipo" style="text-align: center;">
            <span class="badge-tipo-evento ' . $tipoClass . '">' . strtoupper(htmlspecialchars(substr($row[2], 0, 12))) . '</span>
        </td>
        <td class="col-participante" style="font-size: 6px;">' . htmlspecialchars($participante) . '</td>
        <td class="col-estado" style="text-align: center;">
            <span class="badge-estado ' . $estadoClass . '">' . strtoupper(htmlspecialchars(substr($row[4], 0, 10))) . '</span>
        </td>
        <td class="col-fechas" style="font-size: 6px;">' . htmlspecialchars($fechas) . '</td>
        <td class="col-metricas" style="font-size: 6px; text-align: center;">' . htmlspecialchars($row[6]) . '</td>
        <td class="col-porcentaje" style="text-align: center;">
            <span class="porcentaje-badge ' . $porcentajeClass . '">' . htmlspecialchars($row[7]) . '</span>
        </td>
        <td class="col-nivel" style="text-align: center;">
            <span class="badge-nivel ' . $nivelClass . '">' . strtoupper(htmlspecialchars($row[8])) . '</span>
        </td>
        <td class="col-observaciones" style="font-size: 6px;">' . htmlspecialchars($observaciones) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de registros:</strong> ' . $total_registros . ' | 
    <strong>Asistencias:</strong> ' . $total_asistencias . ' | 
    <strong>Tasa de Asistencia:</strong> ' . $tasa_asistencia_general . '% | 
    <strong>Participación Alta:</strong> ' . $niveles_participacion['Alta'] . '
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
$filename = 'Reporte_Estadisticas_Participacion_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>