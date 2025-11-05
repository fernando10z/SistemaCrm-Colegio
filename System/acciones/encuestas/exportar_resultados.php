<?php
session_start();
require_once '../../bd/conexion.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID de encuesta no proporcionado');
}

$encuesta_id = intval($_GET['id']);

try {
    // Obtener información de la encuesta
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, tipo, dirigido_a, preguntas, 
               fecha_inicio, fecha_fin
        FROM encuestas 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die('Encuesta no encontrada');
    }
    
    $encuesta = $result->fetch_assoc();
    $stmt->close();
    
    // Decodificar preguntas
    $preguntas = json_decode($encuesta['preguntas'], true);
    
    // Obtener todas las respuestas con información del usuario
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.respuestas,
            r.puntaje_calculado,
            r.fecha_respuesta,
            r.ip_respuesta,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN 'Padre/Apoderado'
                WHEN r.lead_id IS NOT NULL THEN 'Lead'
                WHEN r.exalumno_id IS NOT NULL THEN 'Ex-alumno'
                ELSE 'Estudiante'
            END as tipo_usuario,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN CONCAT(a.nombres, ' ', a.apellidos)
                WHEN r.lead_id IS NOT NULL THEN CONCAT(l.nombres, ' ', l.apellidos)
                WHEN r.exalumno_id IS NOT NULL THEN CONCAT(e.nombres, ' ', e.apellidos)
                ELSE 'Anónimo'
            END as nombre_usuario,
            CASE 
                WHEN r.apoderado_id IS NOT NULL THEN a.email
                WHEN r.lead_id IS NOT NULL THEN l.email
                WHEN r.exalumno_id IS NOT NULL THEN e.email
                ELSE NULL
            END as email_usuario
        FROM respuestas_encuesta r
        LEFT JOIN apoderados a ON r.apoderado_id = a.id
        LEFT JOIN leads l ON r.lead_id = l.id
        LEFT JOIN exalumnos e ON r.exalumno_id = e.id
        WHERE r.encuesta_id = ?
        ORDER BY r.fecha_respuesta DESC
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $result_respuestas = $stmt->get_result();
    
    $respuestas = [];
    while ($row = $result_respuestas->fetch_assoc()) {
        $respuestas[] = $row;
    }
    $stmt->close();
    
    // Generar CSV
    $filename = 'Encuesta_' . limpiarNombre($encuesta['titulo']) . '_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Abrir salida
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados generales
    fputcsv($output, ['INFORMACIÓN DE LA ENCUESTA'], ';');
    fputcsv($output, ['Título', $encuesta['titulo']], ';');
    fputcsv($output, ['Descripción', $encuesta['descripcion']], ';');
    fputcsv($output, ['Tipo', ucfirst($encuesta['tipo'])], ';');
    fputcsv($output, ['Dirigido a', ucfirst($encuesta['dirigido_a'])], ';');
    fputcsv($output, ['Fecha Inicio', formatearFecha($encuesta['fecha_inicio'])], ';');
    fputcsv($output, ['Fecha Fin', $encuesta['fecha_fin'] ? formatearFecha($encuesta['fecha_fin']) : 'Sin fecha fin'], ';');
    fputcsv($output, ['Total Respuestas', count($respuestas)], ';');
    fputcsv($output, ['Fecha Exportación', date('d/m/Y H:i:s')], ';');
    fputcsv($output, [], ';');
    fputcsv($output, [], ';');
    
    // Encabezados de la tabla de respuestas
    $headers = [
        'ID',
        'Fecha Respuesta',
        'Tipo Usuario',
        'Nombre',
        'Email',
        'IP'
    ];
    
    // Agregar cada pregunta como columna
    foreach ($preguntas as $pregunta) {
        $headers[] = 'P' . $pregunta['id'] . ': ' . substr($pregunta['pregunta'], 0, 50);
    }
    
    $headers[] = 'Puntaje Calculado';
    
    fputcsv($output, $headers, ';');
    
    // Datos de respuestas
    foreach ($respuestas as $respuesta) {
        $respuestas_data = json_decode($respuesta['respuestas'], true);
        
        $row = [
            $respuesta['id'],
            date('d/m/Y H:i', strtotime($respuesta['fecha_respuesta'])),
            $respuesta['tipo_usuario'],
            $respuesta['nombre_usuario'],
            $respuesta['email_usuario'] ?? '',
            $respuesta['ip_respuesta'] ?? ''
        ];
        
        // Agregar respuestas a cada pregunta
        foreach ($preguntas as $pregunta) {
            $respuesta_pregunta = $respuestas_data[$pregunta['id']] ?? '';
            
            // Si es array (checkbox), convertir a texto
            if (is_array($respuesta_pregunta)) {
                $respuesta_pregunta = implode(', ', $respuesta_pregunta);
            }
            
            $row[] = $respuesta_pregunta;
        }
        
        $row[] = $respuesta['puntaje_calculado'] ?? '';
        
        fputcsv($output, $row, ';');
    }
    
    // Agregar estadísticas al final
    fputcsv($output, [], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['ESTADÍSTICAS POR PREGUNTA'], ';');
    fputcsv($output, [], ';');
    
    foreach ($preguntas as $pregunta) {
        fputcsv($output, ['Pregunta ' . $pregunta['id'], $pregunta['pregunta']], ';');
        fputcsv($output, ['Tipo', $pregunta['tipo']], ';');
        
        // Contar respuestas
        $conteo = [];
        foreach ($respuestas as $respuesta) {
            $respuestas_data = json_decode($respuesta['respuestas'], true);
            if (isset($respuestas_data[$pregunta['id']])) {
                $valor = $respuestas_data[$pregunta['id']];
                
                if (is_array($valor)) {
                    foreach ($valor as $v) {
                        $conteo[$v] = ($conteo[$v] ?? 0) + 1;
                    }
                } else {
                    $conteo[$valor] = ($conteo[$valor] ?? 0) + 1;
                }
            }
        }
        
        // Mostrar conteo
        if ($pregunta['tipo'] !== 'text') {
            fputcsv($output, ['Opción', 'Cantidad', '%'], ';');
            $total = array_sum($conteo);
            foreach ($conteo as $opcion => $cantidad) {
                $porcentaje = $total > 0 ? round(($cantidad / $total) * 100, 2) : 0;
                fputcsv($output, [$opcion, $cantidad, $porcentaje . '%'], ';');
            }
        } else {
            fputcsv($output, ['Total respuestas de texto libre', count($conteo)], ';');
        }
        
        fputcsv($output, [], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    die('Error al exportar: ' . $e->getMessage());
}

function formatearFecha($fecha) {
    if (!$fecha) return '-';
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

function limpiarNombre($texto) {
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^A-Za-z0-9_\-]/', '_', $texto);
    $texto = preg_replace('/_+/', '_', $texto);
    return substr($texto, 0, 50);
}
?>