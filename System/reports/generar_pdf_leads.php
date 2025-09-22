<?php
require_once '../vendor/autoload.php';
require_once '../bd/conexion.php';
use Dompdf\Dompdf;
use Dompdf\Options;
date_default_timezone_set('America/Lima'); // Configurar zona horaria
// Obtener datos del sistema desde configuración
$configQuery = "SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('nombre_institucion', 'email_principal', 'telefono_principal')";
$configResult = $conn->query($configQuery);
$config = [
    'nombre_institucion' => 'Sistema CRM Escolar',
    'email_principal' => 'contacto@crm.edu.pe',
    'telefono_principal' => '+51 1 234-5678'
];
if ($configResult) {
    while ($cfg = $configResult->fetch_assoc()) {
        if (isset($config[$cfg['clave']])) {
            $config[$cfg['clave']] = $cfg['valor'];
        }
    }
}
// Obtener rol del usuario desde sesión
session_start();
$rolUsuario = isset($_SESSION['rol_id']) ? $_SESSION['rol_id'] : "Desconocido";
$roles = [1 => "Administrador", 2 => "Coordinador Marketing", 3 => "Tutor", 4 => "Finanzas"];
$nombreRol = isset($roles[$rolUsuario]) ? $roles[$rolUsuario] : "Usuario del Sistema";
// Obtener los datos filtrados desde la tabla
$filteredData = isset($_POST['filteredData']) ? json_decode($_POST['filteredData'], true) : [];
// Si no hay datos, mostrar alerta

if (empty($filteredData)) {
    die("<script>window.alert('No hay registros disponibles para generar el reporte de leads.'); window.close();</script>");
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
    #tabla-cabecera, #tabla-leads {
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
    #tabla-leads td, #tabla-leads th {
        border: 0.5px solid #333;
        padding: 6px 8px;
        font-size: 10px;
        text-align: left;
        vertical-align: top;
    }
    #tabla-leads th {
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
    .badge-estado {
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 9px;
        display: inline-block;
    }
    .prioridad-baja { background-color: #d4edda; color: #155724; }
    .prioridad-media { background-color: #fff3cd; color: #856404; }
    .prioridad-alta { background-color: #f8d7da; color: #721c24; }
    .prioridad-urgente { background-color: #dc3545; color: white; }
    .puntaje-interes {
        font-weight: bold;
        font-size: 10px;
        text-align: center;
    }
    .estrella-activa { color: #ffc107; }
    .estrella-inactiva { color: #e9ecef; }
    .interes-bajo { color: #dc3545; }
    .interes-medio { color: #fd7e14; }
    .interes-alto { color: #28a745; }
</style>';

// Cabecera del reporte
$html .= '<table id="tabla-cabecera">
    <tr>
        <td class="logo-empresa" style="width: 30%;">
            <div style="width: 80px; height: 80px; background-color: #007bff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: bold;">
                CRM
            </div>
        </td>
        <td style="width: 40%;">
            <h3>' . htmlspecialchars($config['nombre_institucion']) . '</h3>
            <div class="info-empresa">Sistema de Gestión CRM Escolar</div>
            <div class="info-empresa">' . htmlspecialchars($config['email_principal']) . '</div>
            <div class="info-empresa">Telf. ' . htmlspecialchars($config['telefono_principal']) . '</div>
        </td>
        <td style="width: 30%;">
            <div class="reporte-titulo">
                <h4>REPORTE DE LEADS FILTRADOS</h4>
                <div>Fecha: ' . date('d/m/Y') . '</div>
                <div>Hora: ' . date('H:i:s') . '</div>
            </div>
        </td>
    </tr>
</table>';
// Título sección
$html .= '<div class="seccion-titulo">LISTADO DE LEADS</div>';

// Tabla de leads
$html .= '<table id="tabla-leads">
    <thead>
        <tr>
            <th>ID</th>
            <th>Código</th>
            <th>Estudiante</th>
            <th>Contacto</th>
            <th>Teléfono</th>
            <th>Grado</th>
            <th>Canal</th>
            <th>Estado</th>
            <th>Prioridad</th>
            <th>Interés</th>
            <th>Responsable</th>
            <th>Próxima Acción</th>
            <th>Registro</th>
        </tr>
    </thead>
    <tbody>';
// Función para generar estrellas HTML según puntaje (0-5)
function generarEstrellas($puntaje) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $puntaje) {
            $html .= '<span class="estrella-activa">&#9733;</span>'; // estrella llena
        } else {
            $html .= '<span class="estrella-inactiva">&#9733;</span>'; // estrella vacía
        }
    }
    return $html;
}

foreach ($filteredData as $row) {
    // Los datos vienen en orden de columnas de la tabla HTML, por ejemplo:
    // [0] ID, [1] Código, [2] Estudiante, [3] Contacto, [4] Teléfono/WA, [5] Grado, [6] Canal, [7] Estado, [8] Prioridad, [9] Interés (estrellas), [10] Responsable, [11] Próxima Acción, [12] Registro
    $id = htmlspecialchars($row[0] ?? '');
    $codigo = htmlspecialchars($row[1] ?? '');
    $estudiante = htmlspecialchars($row[2] ?? '');
    $contacto = htmlspecialchars($row[3] ?? '');
    $telefono = htmlspecialchars($row[4] ?? '');
    $grado = htmlspecialchars($row[5] ?? '');
    $canal = htmlspecialchars($row[6] ?? '');
    $estado = htmlspecialchars($row[7] ?? '');
    $prioridad = strtolower(trim($row[8] ?? 'media'));
    $interes_html = $row[9] ?? ''; // ya viene con estrellas en HTML, pero para PDF mejor generar de nuevo
    $responsable = htmlspecialchars($row[10] ?? '');
    $proxima_accion = htmlspecialchars($row[11] ?? '');
    $registro = htmlspecialchars($row[12] ?? '');
    // Extraer puntaje numérico de interés (aprox) para generar estrellas
    // Intentamos contar estrellas activas en $interes_html
    $puntaje_interes = 0;
    if (preg_match_all('/estrella-activa/', $interes_html, $matches)) {
        $puntaje_interes = count($matches[0]);
    }
    // Color de prioridad
    $prioridad_class = 'prioridad-media';
    switch ($prioridad) {
        case 'baja': $prioridad_class = 'prioridad-baja'; break;
        case 'media': $prioridad_class = 'prioridad-media'; break;
        case 'alta': $prioridad_class = 'prioridad-alta'; break;
        case 'urgente': $prioridad_class = 'prioridad-urgente'; break;
    }

    // Color de estado: no tenemos color exacto, solo mostramos texto con fondo gris
    $estado_html = '<span class="badge-estado" style="background-color:#6c757d;">' . $estado . '</span>';
    $html .= '<tr>
        <td style="text-align:center;">' . $id . '</td>
        <td style="text-align:center; font-family: monospace;">' . $codigo . '</td>
        <td>' . $estudiante . '</td>
        <td>' . $contacto . '</td>
        <td style="font-family: monospace;">' . $telefono . '</td>
        <td style="text-align:center;">' . $grado . '</td>
        <td style="text-align:center;">' . $canal . '</td>
        <td style="text-align:center;">' . $estado_html . '</td>
        <td style="text-align:center;"><span class="' . $prioridad_class . '">' . ucfirst($prioridad) . '</span></td>
        <td class="puntaje-interes" style="text-align:center;">' . generarEstrellas($puntaje_interes) . '</td>
        <td>' . $responsable . '</td>
        <td style="text-align:center;">' . $proxima_accion . '</td>
        <td style="text-align:center;">' . $registro . '</td>
    </tr>';
}
$html .= '</tbody></table>';
// Pie de página
$html .= '<div class="pie-pagina">
    <strong>Reporte generado por:</strong> ' . htmlspecialchars($nombreRol) . '<br>
    <strong>Fecha y hora de generación:</strong> ' . date('d/m/Y H:i:s') . '<br>
</div>';
// Configurar DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para más columnas
$dompdf->render();
// Enviar PDF al navegador
$filename = 'Reporte_Leads_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=$filename");
echo $dompdf->output();
$conn->close();
exit;
?>