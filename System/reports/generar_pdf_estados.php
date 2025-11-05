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
    // Placeholder si no existe la imagen
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
    die("<script>window.alert('No hay registros disponibles para generar el reporte de estados.'); window.close();</script>");
}

// Calcular estadísticas generales
$totalEstados = count($filteredData);
$totalLeads = 0;
$estadosFinales = 0;
$leadsMesActual = 0;

foreach ($filteredData as $row) {
    // Extraer números del texto (porque viene formateado de la tabla)
    $totalLeads += intval(strip_tags($row[3]));
    if (strpos($row[2], '🏁') !== false) {
        $estadosFinales++;
    }
}

// Estilos CSS del PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
        margin: 0;
        padding: 0;
    }

    #tabla-cabecera, #tabla-estados {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
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
        background-color: #d6eaff; /* Azul pastel */
    }

    .estadisticas-resumen {
        background-color: #a8e6cf; /* Verde pastel */
        padding: 12px;
        margin: 15px 0;
        border-radius: 8px;
        font-size: 11px;
        text-align: center;
    }

    .stat-item {
        display: inline-block;
        margin: 0 15px;
        font-weight: bold;
    }

    .stat-number {
        font-size: 18px;
        color: #2c3e50;
    }

    .seccion-titulo {
        background-color: #dcedc1; /* Verde claro pastel */
        padding: 12px;
        margin: 20px 0 12px 0;
        font-weight: bold;
        border-radius: 5px;
        font-size: 13px;
        text-align: center;
    }

    #tabla-estados td, #tabla-estados th {
        border: 0.5px solid #333;
        padding: 7px;
        font-size: 10px;
        text-align: left;
        vertical-align: top;
    }

    #tabla-estados th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .pie-pagina {
        margin-top: 20px;
        padding: 10px;
        font-size: 10px;
        border: 0.5px solid #333;
        border-radius: 8px;
        text-align: center;
        background-color: #ffd6f0; /* Rosa pastel */
    }

    .estado-nombre {
        font-weight: bold;
        color: #2c3e50;
        font-size: 11px;
    }

    .estado-descripcion {
        font-size: 9px;
        color: #6c757d;
        font-style: italic;
    }

    .badge-estado {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: bold;
        color: white;
    }

    .badge-final::after {
        content: " 🏁";
    }

    .orden-badge {
        display: inline-block;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
        text-align: center;
        line-height: 25px;
        font-size: 11px;
    }

    .stat-mini {
        font-size: 9px;
        line-height: 1.4;
    }

    .stat-label {
        color: #6c757d;
    }

    .stat-valor {
        font-weight: bold;
        color: #2c3e50;
    }

    .estrella {
        color: #ffc107;
        font-size: 8px;
    }

    .urgente-text {
        color: #dc3545;
        font-weight: bold;
    }

    .col-orden { width: 6%; text-align: center; }
    .col-estado { width: 22%; }
    .col-badge { width: 14%; text-align: center; }
    .col-stats { width: 16%; }
    .col-interes { width: 12%; text-align: center; }
    .col-urgentes { width: 10%; text-align: center; }
    .col-acciones { width: 10%; text-align: center; }
    .col-ultimo { width: 10%; text-align: center; }
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
                <h4>REPORTE DE ESTADOS DE LEADS</h4>
                <div>Pipeline de Captación</div>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';

// **Estadísticas Resumen**
$html .= '<div class="estadisticas-resumen">
    <div class="stat-item">
        <div class="stat-number">' . $totalEstados . '</div>
        <div>Estados Configurados</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">' . $totalLeads . '</div>
        <div>Total de Leads</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">' . $estadosFinales . '</div>
        <div>Estados Finales</div>
    </div>
</div>';

// **Sección de listado de estados**
$html .= '<div class="seccion-titulo">CONFIGURACIÓN DEL PIPELINE DE CAPTACIÓN</div>';
$html .= '<table id="tabla-estados">
    <thead>
        <tr>
            <th class="col-orden">Orden</th>
            <th class="col-estado">Estado</th>
            <th class="col-badge">Badge</th>
            <th class="col-stats">Estadísticas</th>
            <th class="col-interes">Promedio<br>Interés</th>
            <th class="col-urgentes">Leads<br>Urgentes</th>
            <th class="col-acciones">Acciones<br>Hoy</th>
            <th class="col-ultimo">Último<br>Lead</th>
        </tr>
    </thead>
    <tbody>';

foreach ($filteredData as $row) {
    // Extraer orden
    $orden = strip_tags($row[0]);
    
    // Extraer nombre y descripción del estado
    preg_match('/<span class=["\']estado-nombre["\']>(.*?)<\/span>/', $row[1], $matchesNombre);
    preg_match('/<span class=["\']estado-descripcion["\']>(.*?)<\/span>/', $row[1], $matchesDesc);
    $nombreEstado = isset($matchesNombre[1]) ? strip_tags($matchesNombre[1]) : strip_tags($row[1]);
    $descripcionEstado = isset($matchesDesc[1]) ? strip_tags($matchesDesc[1]) : '';
    
    // Extraer badge (color y si es final)
    $esFinal = strpos($row[2], '🏁') !== false;
    preg_match('/background-color:\s*([^;]+)/', $row[2], $matchesColor);
    $colorBadge = isset($matchesColor[1]) ? $matchesColor[1] : '#007bff';
    
    // Extraer estadísticas (las 3 líneas)
    $statsHtml = strip_tags($row[3], '<br>');
    $statsLineas = explode('<br>', $statsHtml);
    $totalLeadsEstado = isset($statsLineas[0]) ? preg_replace('/[^0-9]/', '', $statsLineas[0]) : '0';
    $leadsMes = isset($statsLineas[1]) ? preg_replace('/[^0-9]/', '', $statsLineas[1]) : '0';
    $leadsAsignados = isset($statsLineas[2]) ? preg_replace('/[^0-9]/', '', $statsLineas[2]) : '0';
    
    // Extraer promedio de interés
    preg_match('/>([0-9.]+)</', $row[4], $matchesInteres);
    $promedioInteres = isset($matchesInteres[1]) ? floatval($matchesInteres[1]) : 0;
    $estrellasCompletas = floor($promedioInteres);
    $estrellasHtml = str_repeat('★', $estrellasCompletas) . str_repeat('☆', 5 - $estrellasCompletas);
    
    // Extraer urgentes
    $urgentes = preg_replace('/[^0-9]/', '', strip_tags($row[5]));
    
    // Extraer acciones hoy
    $accionesHoy = preg_replace('/[^0-9]/', '', strip_tags($row[6]));
    
    // Extraer último lead
    $ultimoLead = strip_tags($row[7]);
    
    $html .= '<tr>
        <td class="col-orden">
            <div class="orden-badge">' . htmlspecialchars($orden) . '</div>
        </td>
        <td class="col-estado">
            <div class="estado-nombre">' . htmlspecialchars($nombreEstado) . '</div>
            <div class="estado-descripcion">' . htmlspecialchars($descripcionEstado) . '</div>
        </td>
        <td class="col-badge">
            <span class="badge-estado' . ($esFinal ? ' badge-final' : '') . '" style="background-color: ' . $colorBadge . ';">
                ' . htmlspecialchars($nombreEstado) . '
            </span>
        </td>
        <td class="col-stats">
            <div class="stat-mini">
                <span class="stat-label">Total:</span> 
                <span class="stat-valor">' . $totalLeadsEstado . '</span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Este mes:</span> 
                <span class="stat-valor">' . $leadsMes . '</span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Asignados:</span> 
                <span class="stat-valor">' . $leadsAsignados . '</span>
            </div>
        </td>
        <td class="col-interes">
            <div class="estrella">' . $estrellasHtml . '</div>
            <div style="font-weight: bold; margin-top: 2px;">' . number_format($promedioInteres, 1) . '</div>
        </td>
        <td class="col-urgentes">
            ' . ($urgentes > 0 ? '<span class="urgente-text">' . $urgentes . '</span>' : '<span style="color: #6c757d;">0</span>') . '
        </td>
        <td class="col-acciones">
            ' . ($accionesHoy > 0 ? '<span style="background-color: #fff3cd; padding: 2px 6px; border-radius: 4px; font-weight: bold;">' . $accionesHoy . '</span>' : '<span style="color: #6c757d;">0</span>') . '
        </td>
        <td class="col-ultimo">
            <span style="font-size: 9px; color: #6c757d;">' . htmlspecialchars($ultimoLead) . '</span>
        </td>
    </tr>';
}

$html .= '</tbody></table>';

// **Pie de página**
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Total de estados en el reporte:</strong> ' . $totalEstados . ' | <strong>Total de leads activos:</strong> ' . $totalLeads . '
</div>';

// **Configurar DomPDF**
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// **Enviar PDF al navegador**
$filename = 'Reporte_Estados_Leads_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();

$conn->close();
exit;
?>