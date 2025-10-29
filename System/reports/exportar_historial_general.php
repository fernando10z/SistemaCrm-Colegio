<?php
require_once('../bd/conexion.php');
require_once('../vendor/autoload.php');

// Obtener filtros
$fecha_desde = $_POST['fecha_desde'] ?? null;
$fecha_hasta = $_POST['fecha_hasta'] ?? null;
$usuario_id = $_POST['usuario_id'] ?? null;
$estado_id = $_POST['estado_id'] ?? null;

// Construir query con filtros
$where_clauses = [];
$params = [];
$types = '';

if ($fecha_desde) {
    $where_clauses[] = "DATE(hel.created_at) >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

if ($fecha_hasta) {
    $where_clauses[] = "DATE(hel.created_at) <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

if ($usuario_id) {
    $where_clauses[] = "hel.usuario_id = ?";
    $params[] = $usuario_id;
    $types .= 'i';
}

if ($estado_id) {
    $where_clauses[] = "(hel.estado_anterior_id = ? OR hel.estado_nuevo_id = ?)";
    $params[] = $estado_id;
    $params[] = $estado_id;
    $types .= 'ii';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener datos
$sql = "SELECT 
    hel.created_at,
    l.codigo_lead,
    CONCAT(l.nombres_estudiante, ' ', IFNULL(l.apellidos_estudiante, '')) as lead_nombre,
    IFNULL(ea.nombre, 'Nuevo') as estado_anterior,
    en.nombre as estado_nuevo,
    CONCAT(u.nombre, ' ', u.apellidos) as usuario_nombre,
    hel.observaciones,
    TIMESTAMPDIFF(DAY, 
        (SELECT MAX(h2.created_at) 
         FROM historial_estados_lead h2 
         WHERE h2.lead_id = hel.lead_id 
         AND h2.created_at < hel.created_at),
        hel.created_at
    ) as dias_en_estado_anterior
    FROM historial_estados_lead hel
    LEFT JOIN leads l ON hel.lead_id = l.id
    LEFT JOIN estados_lead ea ON hel.estado_anterior_id = ea.id
    LEFT JOIN estados_lead en ON hel.estado_nuevo_id = en.id
    LEFT JOIN usuarios u ON hel.usuario_id = u.id
    $where_sql
    ORDER BY hel.created_at DESC
    LIMIT 500";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Obtener nombre de la institución
$query_nombre = "SELECT valor FROM configuracion_sistema WHERE clave = 'nombre_institucion' LIMIT 1";
$result_nombre = $conn->query($query_nombre);
$nombre_institucion = 'CRM Escolar';
if ($result_nombre && $row_nombre = $result_nombre->fetch_assoc()) {
    $nombre_institucion = $row_nombre['valor'];
}

// Crear PDF
class PDF extends FPDF {
    private $institucion;
    
    function __construct($institucion) {
        parent::__construct('L', 'mm', 'A4');
        $this->institucion = $institucion;
    }
    
    function Header() {
        // Logo y título
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->institucion), 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Historial General de Estados'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Generado el: ' . date('d/m/Y H:i')), 0, 1, 'C');
        
        $this->Ln(5);
        
        // Encabezados de tabla
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(52, 73, 94);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(30, 8, 'Fecha', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Lead', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Estado Anterior', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Estado Nuevo', 1, 0, 'C', true);
        $this->Cell(45, 8, 'Usuario', 1, 0, 'C', true);
        $this->Cell(60, 8, 'Observaciones', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Tiempo', 1, 1, 'C', true);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}

$pdf = new PDF($nombre_institucion);
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// Agregar datos
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(44, 62, 80);

$fill = false;
while($row = $result->fetch_assoc()) {
    // Formatear fecha
    $fecha = new DateTime($row['created_at']);
    $fecha_formateada = $fecha->format('d/m/Y H:i');
    
    // Calcular tiempo transcurrido
    $tiempo_transcurrido = '-';
    if ($row['dias_en_estado_anterior'] !== null) {
        $dias = (int)$row['dias_en_estado_anterior'];
        if ($dias === 0) {
            $tiempo_transcurrido = 'Mismo día';
        } elseif ($dias === 1) {
            $tiempo_transcurrido = '1 día';
        } else {
            $tiempo_transcurrido = $dias . ' días';
        }
    }
    
    // Ajustar altura de fila según contenido
    $observaciones = $row['observaciones'] ?? 'Sin observaciones';
    $observaciones = substr($observaciones, 0, 80); // Limitar longitud
    
    if ($fill) {
        $pdf->SetFillColor(248, 249, 250);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    
    $pdf->Cell(30, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $fecha_formateada), 1, 0, 'L', true);
    $pdf->Cell(35, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['codigo_lead']), 1, 0, 'L', true);
    $pdf->Cell(40, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['estado_anterior']), 1, 0, 'L', true);
    $pdf->Cell(40, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['estado_nuevo']), 1, 0, 'L', true);
    $pdf->Cell(45, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['usuario_nombre']), 1, 0, 'L', true);
    $pdf->Cell(60, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $observaciones), 1, 0, 'L', true);
    $pdf->Cell(20, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $tiempo_transcurrido), 1, 1, 'C', true);
    
    $fill = !$fill;
}

// Verificar si hay datos
if ($result->num_rows === 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'No se encontraron registros con los filtros aplicados'), 0, 1, 'C');
}

// Output
$pdf->Output('D', 'Historial_Estados_' . date('Y-m-d_His') . '.pdf');

$conn->close();