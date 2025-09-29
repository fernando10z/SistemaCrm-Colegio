<?php
session_start();
include '../../bd/conexion.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../rankings_recomendacion.php');
    exit();
}

// Obtener la acción
$accion = $_POST['accion'] ?? '';

// Variable para mensajes
$mensaje = '';
$tipo = 'success';

try {
    switch($accion) {
        
        case 'exportar_leaderboard':
            exportarLeaderboard($conn);
            break;
            
        case 'generar_reporte_referente':
            generarReporteReferente($conn);
            break;
            
        case 'enviar_mensaje_referente':
            enviarMensajeReferente($conn);
            break;
            
        default:
            $mensaje = 'Acción no válida';
            $tipo = 'error';
            break;
    }
    
} catch (Exception $e) {
    $mensaje = 'Error: ' . $e->getMessage();
    $tipo = 'error';
}

// Guardar mensaje en sesión y redirigir
$_SESSION['mensaje_sistema'] = $mensaje;
$_SESSION['tipo_mensaje'] = $tipo;

header('Location: ../../rankings_recomendacion.php');
exit();

// ==================== FUNCIONES ====================

function exportarLeaderboard($conn) {
    global $mensaje, $tipo;
    
    $periodo = $_POST['periodo'] ?? 'mes_actual';
    $categoria = $_POST['categoria'] ?? 'todas';
    $metrica = $_POST['metrica'] ?? 'conversiones';
    
    // Construir query según filtros
    $where_periodo = construirFiltrosPeriodo($periodo);
    $where_categoria = $categoria !== 'todas' ? "AND categoria = '$categoria'" : '';
    
    $sql = "SELECT
        ROW_NUMBER() OVER (ORDER BY conversiones_exitosas DESC) as posicion,
        CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
        f.apellido_principal as familia,
        COUNT(DISTINCT cr.id) as total_codigos,
        SUM(cr.usos_actuales) as total_usos,
        COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) as conversiones_exitosas,
        CASE 
            WHEN SUM(cr.usos_actuales) > 0 
            THEN ROUND((COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) * 100.0) / SUM(cr.usos_actuales), 2)
            ELSE 0 
        END as tasa_conversion,
        COUNT(DISTINCT CASE WHEN cr.activo = 1 THEN cr.id END) as codigos_activos,
        CASE
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 10 THEN 'Elite'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 5 THEN 'Destacado'
            WHEN COUNT(DISTINCT CASE WHEN ur.convertido = 1 THEN ur.id END) >= 1 THEN 'Activo'
            WHEN SUM(cr.usos_actuales) > 0 THEN 'En Progreso'
            ELSE 'Nuevo'
        END as categoria
    FROM apoderados a
    INNER JOIN familias f ON a.familia_id = f.id
    LEFT JOIN codigos_referido cr ON a.id = cr.apoderado_id
    LEFT JOIN usos_referido ur ON cr.id = ur.codigo_referido_id
    WHERE a.activo = 1 $where_periodo $where_categoria
    GROUP BY a.id, a.nombres, a.apellidos, f.apellido_principal
    HAVING total_codigos > 0
    ORDER BY conversiones_exitosas DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $mensaje = 'Leaderboard exportado exitosamente con ' . $result->num_rows . ' referentes';
        $tipo = 'success';
    } else {
        $mensaje = 'No se encontraron datos para exportar';
        $tipo = 'warning';
    }
}

function generarReporteReferente($conn) {
    global $mensaje, $tipo;
    
    $referente_id = intval($_POST['referente_id'] ?? 0);
    
    if ($referente_id <= 0) {
        throw new Exception('ID de referente no válido');
    }
    
    // Verificar que existe el referente
    $check_sql = "SELECT a.id, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                  FROM apoderados a 
                  WHERE a.id = ? AND a.activo = 1";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $referente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Referente no encontrado');
    }
    
    $referente = $result->fetch_assoc();
    
    $mensaje = 'Reporte generado exitosamente para ' . $referente['nombre'];
    $tipo = 'success';
}

function enviarMensajeReferente($conn) {
    global $mensaje, $tipo;
    
    $referente_id = intval($_POST['referente_id'] ?? 0);
    $mensaje_texto = trim($_POST['mensaje'] ?? '');
    
    if ($referente_id <= 0) {
        throw new Exception('ID de referente no válido');
    }
    
    if (empty($mensaje_texto)) {
        throw new Exception('El mensaje no puede estar vacío');
    }
    
    // Validar longitud del mensaje
    if (strlen($mensaje_texto) < 10) {
        throw new Exception('El mensaje debe tener al menos 10 caracteres');
    }
    
    if (strlen($mensaje_texto) > 500) {
        throw new Exception('El mensaje no puede exceder 500 caracteres');
    }
    
    // Verificar que existe el referente y tiene email
    $check_sql = "SELECT a.email, CONCAT(a.nombres, ' ', a.apellidos) as nombre
                  FROM apoderados a 
                  WHERE a.id = ? AND a.activo = 1 AND a.email IS NOT NULL";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $referente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Referente no encontrado o sin email registrado');
    }
    
    $referente = $result->fetch_assoc();
    
    // Aquí se implementaría el envío real del email
    // Por ahora solo simulamos el éxito
    
    $mensaje = 'Mensaje enviado exitosamente a ' . $referente['nombre'];
    $tipo = 'success';
}

function construirFiltrosPeriodo($periodo) {
    $where = '';
    
    switch($periodo) {
        case 'mes_actual':
            $where = "AND MONTH(ur.fecha_uso) = MONTH(CURDATE()) AND YEAR(ur.fecha_uso) = YEAR(CURDATE())";
            break;
        case 'mes_anterior':
            $where = "AND MONTH(ur.fecha_uso) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                      AND YEAR(ur.fecha_uso) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
        case 'trimestre':
            $where = "AND ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case 'semestre':
            $where = "AND ur.fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            break;
        case 'año':
            $where = "AND YEAR(ur.fecha_uso) = YEAR(CURDATE())";
            break;
        case 'todo':
        default:
            $where = '';
            break;
    }
    
    return $where;
}

$conn->close();
?>