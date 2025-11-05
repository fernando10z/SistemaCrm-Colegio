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
    $imagenBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#007bff"/><text x="50%" y="50%" fill="white" text-anchor="middle" dy=".3em" font-size="20">LOGO</text></svg>');
}

// Obtener rol del usuario desde sesión
session_start();
$rolUsuario = isset($_SESSION['rol_id']) ? $_SESSION['rol_id'] : null;
$roles = [1 => "Administrador", 2 => "Coordinador Marketing", 3 => "Tutor", 4 => "Finanzas"];
$nombreRol = isset($roles[$rolUsuario]) ? $roles[$rolUsuario] : "Usuario del Sistema";

// Obtener los datos filtrados desde la tabla
$filteredData = isset($_POST['filteredData']) ? json_decode($_POST['filteredData'], true) : [];

// Si no hay datos, mostrar alerta
if (empty($filteredData)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de responsables.'); window.close();</script>");
}

// Calcular estadísticas generales
$totalResponsables = count($filteredData);
$totalLeadsAsignados = 0;
$totalLeadsConvertidos = 0;
$cargaPromedioTotal = 0;
$responsablesConUrgentes = 0;
$responsablesConTareas = 0;

foreach ($filteredData as $row) {
    // Extraer carga de trabajo (porcentaje)
    $cargaTexto = $row[3];
    preg_match('/([0-9.]+)%/', $cargaTexto, $matchesCarga);
    if (isset($matchesCarga[1])) {
        $cargaPromedioTotal += floatval($matchesCarga[1]);
    }
    
    // Extraer total de leads
    $statsTexto = $row[4];
    preg_match('/Total:\s*([0-9]+)/', $statsTexto, $matchesTotal);
    if (isset($matchesTotal[1])) {
        $totalLeadsAsignados += intval($matchesTotal[1]);
    }
    
    // Extraer convertidos
    $conversionTexto = $row[5];
    preg_match('/([0-9]+)\s*convertidos/', $conversionTexto, $matchesConvertidos);
    if (isset($matchesConvertidos[1])) {
        $totalLeadsConvertidos += intval($matchesConvertidos[1]);
    }
    
    // Contar responsables con urgentes
    $urgentesTexto = $row[6];
    if (preg_match('/[1-9]/', $urgentesTexto)) {
        $responsablesConUrgentes++;
    }
    
    // Contar responsables con tareas
    if (!empty($row[7])) {
        $responsablesConTareas++;
    }
}

$cargaPromedio = $totalResponsables > 0 ? round($cargaPromedioTotal / $totalResponsables, 1) : 0;
$tasaConversionGlobal = $totalLeadsAsignados > 0 ? round(($totalLeadsConvertidos / $totalLeadsAsignados) * 100, 1) : 0;

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-responsables {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
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
        font-size: 12px;
        color: #666;
    }

    .reporte-titulo {
        border: 1px solid #666;
        border-radius: 20px;
        text-align: center;
        padding: 12px;
        display: inline-block;
        background-color: #d6eaff;
    }

    .estadisticas-resumen {
        background-color: #a8e6cf;
        padding: 12px;
        margin: 12px 0;
        border-radius: 8px;
        font-size: 10px;
        text-align: center;
    }

    .stat-item-resumen {
        display: inline-block;
        margin: 0 15px;
        font-weight: bold;
    }

    .stat-number-resumen {
        font-size: 16px;
        color: #2c3e50;
    }

    .seccion-titulo {
        background-color: #dcedc1;
        padding: 10px;
        margin: 18px 0 10px 0;
        font-weight: bold;
        border-radius: 5px;
        font-size: 12px;
        text-align: center;
    }

    #tabla-responsables td, #tabla-responsables th {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 9px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-responsables th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .pie-pagina {
        margin-top: 18px;
        padding: 10px;
        font-size: 10px;
        border: 0.5px solid #333;
        border-radius: 8px;
        text-align: center;
        background-color: #ffd6f0;
    }

    .usuario-nombre {
        font-weight: bold;
        color: #2c3e50;
        font-size: 10px;
        display: block;
        margin-bottom: 2px;
    }

    .usuario-email {
        font-size: 8px;
        color: #6c757d;
        font-style: italic;
        display: block;
        margin-bottom: 2px;
    }

    .usuario-username {
        font-size: 8px;
        color: #495057;
        font-family: "Courier New", monospace;
        display: block;
    }

    .badge-rol {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        color: white;
    }

    .rol-administrador { background-color: #dc3545; }
    .rol-coordinador { background-color: #fd7e14; }
    .rol-tutor { background-color: #20c997; }
    .rol-finanzas { background-color: #6f42c1; }

    .carga-info {
        text-align: center;
    }

    .carga-porcentaje {
        font-weight: bold;
        font-size: 11px;
    }

    .carga-baja { color: #28a745; }
    .carga-media { color: #ffc107; }
    .carga-alta { color: #fd7e14; }
    .carga-critica { color: #dc3545; }

    .carga-barra {
        width: 70px;
        height: 6px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin: 2px auto;
        position: relative;
        overflow: hidden;
    }

    .carga-progreso {
        height: 100%;
        border-radius: 3px;
        position: absolute;
        left: 0;
        top: 0;
    }

    .stats-mini {
        font-size: 8px;
        line-height: 1.4;
        margin-bottom: 1px;
    }

    .stat-label-mini {
        color: #6c757d;
    }

    .stat-valor-mini {
        font-weight: bold;
        color: #2c3e50;
    }

    .conversion-badge {
        padding: 3px 8px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 10px;
        text-align: center;
        display: inline-block;
    }

    .rate-high { background-color: #d4edda; color: #155724; }
    .rate-medium { background-color: #fff3cd; color: #856404; }
    .rate-low { background-color: #f8d7da; color: #721c24; }

    .urgente-badge {
        background-color: #dc3545;
        color: white;
        padding: 3px 6px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: bold;
    }

    .tarea-badge {
        background-color: #fff3cd;
        color: #856404;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 8px;
        font-weight: bold;
        display: block;
        margin: 1px 0;
    }

    .tarea-vencida {
        background-color: #f8d7da;
        color: #721c24;
    }

    .col-id { width: 4%; text-align: center; }
    .col-usuario { width: 20%; }
    .col-rol { width: 11%; text-align: center; }
    .col-carga { width: 12%; text-align: center; }
    .col-stats { width: 17%; }
    .col-conversion { width: 11%; text-align: center; }
    .col-urgentes { width: 8%; text-align: center; }
    .col-tareas { width: 11%; text-align: center; }
    .col-actividad { width: 12%; text-align: center; }
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
                <h4>REPORTE DE RESPONSABLES</h4>
                <div>Asignación y Carga de Trabajo</div>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Resumen**
$html .= '<div class="estadisticas-resumen">
    <div class="stat-item-resumen">
        <div class="stat-number-resumen">' . $totalResponsables . '</div>
        <div>Responsables Activos</div>
    </div>
    <div class="stat-item-resumen">
        <div class="stat-number-resumen">' . $totalLeadsAsignados . '</div>
        <div>Leads Asignados</div>
    </div>
    <div class="stat-item-resumen">
        <div class="stat-number-resumen">' . $cargaPromedio . '%</div>
        <div>Carga Promedio</div>
    </div>
    <div class="stat-item-resumen">
        <div class="stat-number-resumen">' . $tasaConversionGlobal . '%</div>
        <div>Tasa Conversión</div>
    </div>
    <div class="stat-item-resumen">
        <div class="stat-number-resumen">' . $responsablesConUrgentes . '</div>
        <div>Con Urgentes</div>
    </div>
</div>';

// **Sección de listado de responsables**
$html .= '<div class="seccion-titulo">CARGA DE TRABAJO Y DESEMPEÑO DEL EQUIPO</div>';
$html .= '<table id="tabla-responsables">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-usuario">Usuario</th>
            <th class="col-rol">Rol</th>
            <th class="col-carga">Carga<br>Trabajo</th>
            <th class="col-stats">Estadísticas</th>
            <th class="col-conversion">Conversión</th>
            <th class="col-urgentes">Urgentes</th>
            <th class="col-tareas">Tareas</th>
            <th class="col-actividad">Última<br>Actividad</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Extraer ID
    $id = $row[0];
    
    // Extraer información de usuario (viene como texto plano)
    // Formato esperado: "Nombre Completo email@ejemplo.com @username"
    $usuarioTexto = $row[1];
    
    // Parsear el texto del usuario
    $nombreUsuario = '';
    $emailUsuario = '';
    $usernameUsuario = '';
    
    // Buscar email (patrón: algo@algo.algo)
    if (preg_match('/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/', $usuarioTexto, $matchesEmail)) {
        $emailUsuario = $matchesEmail[1];
        $usuarioTexto = str_replace($emailUsuario, '', $usuarioTexto);
    }
    
    // Buscar username (patrón: @algo)
    if (preg_match('/@([a-zA-Z0-9._-]+)/', $usuarioTexto, $matchesUsername)) {
        $usernameUsuario = $matchesUsername[1];
        $usuarioTexto = str_replace('@' . $usernameUsuario, '', $usuarioTexto);
    }
    
    // Lo que queda es el nombre
    $nombreUsuario = trim($usuarioTexto);
    
    // Extraer rol
    $rolTexto = $row[2];
    $rolClass = 'rol-finanzas';
    if (stripos($rolTexto, 'Administrador') !== false) $rolClass = 'rol-administrador';
    elseif (stripos($rolTexto, 'Coordinador') !== false) $rolClass = 'rol-coordinador';
    elseif (stripos($rolTexto, 'Tutor') !== false) $rolClass = 'rol-tutor';
    
    // Extraer carga de trabajo
    $cargaTexto = $row[3];
    preg_match('/([0-9.]+)%/', $cargaTexto, $matchesPorcentaje);
    preg_match('/([0-9]+)\s*leads\s*activos/', $cargaTexto, $matchesLeads);
    
    $porcentajeCarga = isset($matchesPorcentaje[1]) ? floatval($matchesPorcentaje[1]) : 0;
    $leadsActivos = isset($matchesLeads[1]) ? intval($matchesLeads[1]) : 0;
    
    $cargaClass = 'carga-baja';
    $cargaColor = '#28a745';
    if ($porcentajeCarga > 90) {
        $cargaClass = 'carga-critica';
        $cargaColor = '#dc3545';
    } elseif ($porcentajeCarga > 75) {
        $cargaClass = 'carga-alta';
        $cargaColor = '#fd7e14';
    } elseif ($porcentajeCarga > 50) {
        $cargaClass = 'carga-media';
        $cargaColor = '#ffc107';
    }
    
    // Extraer estadísticas
    $statsTexto = $row[4];
    preg_match('/Total:\s*([0-9]+)/', $statsTexto, $matchesTotal);
    preg_match('/Este mes:\s*([0-9]+)/', $statsTexto, $matchesMes);
    preg_match('/Nuevos:\s*([0-9]+)/', $statsTexto, $matchesNuevos);
    preg_match('/Contactados:\s*([0-9]+)/', $statsTexto, $matchesContactados);
    
    $totalLeads = isset($matchesTotal[1]) ? $matchesTotal[1] : '0';
    $leadsMes = isset($matchesMes[1]) ? $matchesMes[1] : '0';
    $leadsNuevos = isset($matchesNuevos[1]) ? $matchesNuevos[1] : '0';
    $leadsContactados = isset($matchesContactados[1]) ? $matchesContactados[1] : '0';
    
    // Extraer conversión
    $conversionTexto = $row[5];
    preg_match('/([0-9.]+)%/', $conversionTexto, $matchesConversion);
    preg_match('/([0-9]+)\s*convertidos/', $conversionTexto, $matchesConvertidos);
    
    $tasaConversion = isset($matchesConversion[1]) ? floatval($matchesConversion[1]) : 0;
    $convertidos = isset($matchesConvertidos[1]) ? $matchesConvertidos[1] : '0';
    
    $conversionClass = 'rate-low';
    if ($tasaConversion >= 20) $conversionClass = 'rate-high';
    elseif ($tasaConversion >= 10) $conversionClass = 'rate-medium';
    
    // Extraer urgentes (ahora es índice 6, no 7)
    $urgentesTexto = $row[6];
    $urgentes = preg_replace('/[^0-9]/', '', $urgentesTexto);
    if (empty($urgentes)) $urgentes = '0';
    
    // Extraer tareas (ahora es índice 7, no 8)
    $tareasTexto = $row[7];
    preg_match('/Hoy:\s*([0-9]+)/', $tareasTexto, $matchesHoy);
    preg_match('/Vencidas:\s*([0-9]+)/', $tareasTexto, $matchesVencidas);
    
    $tareasHoy = isset($matchesHoy[1]) ? $matchesHoy[1] : '';
    $tareasVencidas = isset($matchesVencidas[1]) ? $matchesVencidas[1] : '';
    
    // Extraer última actividad (ahora es índice 8, no 9)
    $ultimaActividad = $row[8];
    
    $html .= '<tr>
        <td class="col-id"><strong>' . htmlspecialchars($id) . '</strong></td>
        <td class="col-usuario">
            <span class="usuario-nombre">' . htmlspecialchars($nombreUsuario) . '</span>
            <span class="usuario-email">' . htmlspecialchars($emailUsuario) . '</span>
            <span class="usuario-username">@' . htmlspecialchars($usernameUsuario) . '</span>
        </td>
        <td class="col-rol">
            <span class="badge-rol ' . $rolClass . '">' . htmlspecialchars($rolTexto) . '</span>
        </td>
        <td class="col-carga">
            <div class="carga-info">
                <div class="carga-porcentaje ' . $cargaClass . '">' . $porcentajeCarga . '%</div>
                <div class="carga-barra">
                    <div class="carga-progreso" style="width: ' . min($porcentajeCarga, 100) . '%; background-color: ' . $cargaColor . ';"></div>
                </div>
                <div style="font-size: 7px; margin-top: 2px;">' . $leadsActivos . ' activos</div>
            </div>
        </td>
        <td class="col-stats">
            <div class="stats-mini">
                <span class="stat-label-mini">Total:</span> 
                <span class="stat-valor-mini">' . $totalLeads . '</span>
            </div>
            <div class="stats-mini">
                <span class="stat-label-mini">Este mes:</span> 
                <span class="stat-valor-mini">' . $leadsMes . '</span>
            </div>
            <div class="stats-mini">
                <span class="stat-label-mini">Nuevos:</span> 
                <span class="stat-valor-mini">' . $leadsNuevos . '</span>
            </div>
            <div class="stats-mini">
                <span class="stat-label-mini">Contactados:</span> 
                <span class="stat-valor-mini">' . $leadsContactados . '</span>
            </div>
        </td>
        <td class="col-conversion">
            <div class="conversion-badge ' . $conversionClass . '">' . $tasaConversion . '%</div>
            <div style="font-size: 7px; margin-top: 2px;">' . $convertidos . ' convertidos</div>
        </td>
        <td class="col-urgentes">
            ' . ($urgentes > 0 ? '<span class="urgente-badge">' . $urgentes . '</span>' : '<span style="color: #6c757d;">0</span>') . '
        </td>
        <td class="col-tareas">
            ' . ($tareasHoy ? '<span class="tarea-badge">Hoy: ' . $tareasHoy . '</span>' : '') . '
            ' . ($tareasVencidas ? '<span class="tarea-badge tarea-vencida">Vencidas: ' . $tareasVencidas . '</span>' : '') . '
            ' . (!$tareasHoy && !$tareasVencidas ? '<span style="color: #6c757d; font-size: 8px;">Sin tareas</span>' : '') . '
        </td>
        <td class="col-actividad">
            <span style="font-size: 8px; color: #6c757d;">' . htmlspecialchars($ultimaActividad) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total responsables:</strong> ' . $totalResponsables . ' | 
    <strong>Leads asignados:</strong> ' . $totalLeadsAsignados . ' | 
    <strong>Convertidos:</strong> ' . $totalLeadsConvertidos . ' (' . $tasaConversionGlobal . '%)
</div>';

// **Configurar DomPDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// **Enviar PDF al navegador**
$filename = 'Reporte_Responsables_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>