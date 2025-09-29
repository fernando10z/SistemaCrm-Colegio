<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include '../../bd/conexion.php';

try {
    // Obtener parámetros de filtro
    $area = $_GET['area'] ?? 'todas';
    $rango = $_GET['rango'] ?? 'mes';
    $fecha_inicio = $_GET['fecha_inicio'] ?? null;
    $fecha_fin = $_GET['fecha_fin'] ?? null;
    
    // Calcular fechas según el rango
    $fecha_actual = date('Y-m-d');
    $fecha_hora_actual = date('Y-m-d H:i:s');
    
    switch($rango) {
        case 'hoy':
            $fecha_inicio = $fecha_actual;
            $fecha_fin = $fecha_actual;
            break;
        case 'semana':
            $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
            $fecha_fin = $fecha_actual;
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = $fecha_actual;
            break;
        case 'trimestre':
            $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
            $fecha_fin = $fecha_actual;
            break;
        case 'anio':
            $fecha_inicio = date('Y-01-01');
            $fecha_fin = $fecha_actual;
            break;
    }
    
    $response = [
        'success' => true,
        'filtros' => [
            'area' => $area,
            'rango' => $rango,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ],
        'captacion' => [],
        'familias' => [],
        'finanzas' => [],
        'seguimiento' => [],
        'graficos' => []
    ];
    
    // ============================================
    // MÉTRICAS DE CAPTACIÓN
    // ============================================
    if ($area == 'todas' || $area == 'captacion') {
        
        // Total de leads
        $sql = "SELECT COUNT(*) as total FROM leads 
                WHERE DATE(created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $total_leads = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        // Leads por estado
        $sql = "SELECT 
                    e.nombre as estado,
                    e.color,
                    COUNT(l.id) as cantidad
                FROM estados_lead e
                LEFT JOIN leads l ON e.id = l.estado_lead_id 
                    AND DATE(l.created_at) BETWEEN ? AND ?
                GROUP BY e.id, e.nombre, e.color
                ORDER BY e.orden_display";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $leads_por_estado = [];
        while($row = $result->fetch_assoc()) {
            $leads_por_estado[] = $row;
        }
        
        // Leads por canal
        $sql = "SELECT 
                    c.nombre as canal,
                    c.tipo,
                    COUNT(l.id) as cantidad
                FROM canales_captacion c
                LEFT JOIN leads l ON c.id = l.canal_captacion_id 
                    AND DATE(l.created_at) BETWEEN ? AND ?
                GROUP BY c.id, c.nombre, c.tipo
                ORDER BY cantidad DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $leads_por_canal = [];
        while($row = $result->fetch_assoc()) {
            $leads_por_canal[] = $row;
        }
        
        // Tasa de conversión
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_lead_id = 5 THEN 1 ELSE 0 END) as convertidos
                FROM leads 
                WHERE DATE(created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $total_conversion = $result['total'] ?? 0;
        $convertidos = $result['convertidos'] ?? 0;
        $tasa_conversion = $total_conversion > 0 
            ? round(($convertidos / $total_conversion) * 100, 2) 
            : 0;
        
        // Leads por prioridad
        $sql = "SELECT 
                    prioridad,
                    COUNT(*) as cantidad
                FROM leads 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY prioridad
                ORDER BY FIELD(prioridad, 'urgente', 'alta', 'media', 'baja')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $leads_por_prioridad = [];
        while($row = $result->fetch_assoc()) {
            $leads_por_prioridad[] = $row;
        }
        
        $response['captacion'] = [
            'total_leads' => $total_leads,
            'leads_por_estado' => $leads_por_estado,
            'leads_por_canal' => $leads_por_canal,
            'tasa_conversion' => $tasa_conversion,
            'convertidos' => $convertidos,
            'leads_por_prioridad' => $leads_por_prioridad
        ];
    }
    
    // ============================================
    // MÉTRICAS DE FAMILIAS
    // ============================================
    if ($area == 'todas' || $area == 'familias') {
        
        // Total de familias activas
        $sql = "SELECT COUNT(*) as total FROM familias WHERE activo = 1";
        $total_familias = $conn->query($sql)->fetch_assoc()['total'] ?? 0;
        
        // Total de estudiantes activos
        $sql = "SELECT COUNT(*) as total FROM estudiantes 
                WHERE estado_matricula = 'matriculado' AND activo = 1";
        $total_estudiantes = $conn->query($sql)->fetch_assoc()['total'] ?? 0;
        
        // Total de apoderados activos
        $sql = "SELECT COUNT(*) as total FROM apoderados WHERE activo = 1";
        $total_apoderados = $conn->query($sql)->fetch_assoc()['total'] ?? 0;
        
        // Estudiantes por grado
        $sql = "SELECT 
                    g.nombre as grado,
                    ne.nombre as nivel,
                    COUNT(e.id) as cantidad
                FROM grados g
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                LEFT JOIN estudiantes e ON g.id = e.grado_id 
                    AND e.estado_matricula = 'matriculado' AND e.activo = 1
                GROUP BY g.id, g.nombre, ne.nombre, g.orden_display
                ORDER BY g.orden_display";
        $result = $conn->query($sql);
        $estudiantes_por_grado = [];
        while($row = $result->fetch_assoc()) {
            $estudiantes_por_grado[] = $row;
        }
        
        // Familias por nivel socioeconómico
        $sql = "SELECT 
                    COALESCE(nivel_socioeconomico, 'No especificado') as nivel,
                    COUNT(*) as cantidad
                FROM familias 
                WHERE activo = 1
                GROUP BY nivel_socioeconomico
                ORDER BY FIELD(nivel_socioeconomico, 'A', 'B', 'C', 'D', 'E', NULL)";
        $result = $conn->query($sql);
        $familias_por_nivel = [];
        while($row = $result->fetch_assoc()) {
            $familias_por_nivel[] = $row;
        }
        
        // Nivel de compromiso de apoderados
        $sql = "SELECT 
                    nivel_compromiso,
                    COUNT(*) as cantidad
                FROM apoderados 
                WHERE activo = 1
                GROUP BY nivel_compromiso
                ORDER BY FIELD(nivel_compromiso, 'alto', 'medio', 'bajo')";
        $result = $conn->query($sql);
        $compromiso_apoderados = [];
        while($row = $result->fetch_assoc()) {
            $compromiso_apoderados[] = $row;
        }
        
        $response['familias'] = [
            'total_familias' => $total_familias,
            'total_estudiantes' => $total_estudiantes,
            'total_apoderados' => $total_apoderados,
            'estudiantes_por_grado' => $estudiantes_por_grado,
            'familias_por_nivel' => $familias_por_nivel,
            'compromiso_apoderados' => $compromiso_apoderados
        ];
    }
    
    // ============================================
    // MÉTRICAS DE FINANZAS
    // ============================================
    if ($area == 'todas' || $area == 'finanzas') {
        
        // Monto total por cobrar
        $sql = "SELECT 
                    COALESCE(SUM(monto_pendiente), 0) as total_pendiente,
                    COALESCE(SUM(monto_total), 0) as total_facturado,
                    COALESCE(SUM(monto_pagado), 0) as total_pagado
                FROM cuentas_por_cobrar 
                WHERE DATE(fecha_creacion) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $total_pendiente = floatval($result['total_pendiente'] ?? 0);
        $total_facturado = floatval($result['total_facturado'] ?? 0);
        $total_pagado = floatval($result['total_pagado'] ?? 0);
        
        // Cuentas por estado
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    COALESCE(SUM(monto_pendiente), 0) as monto
                FROM cuentas_por_cobrar 
                WHERE DATE(fecha_creacion) BETWEEN ? AND ?
                GROUP BY estado
                ORDER BY FIELD(estado, 'pendiente', 'vencido', 'pagado', 'anulado')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $cuentas_por_estado = [];
        while($row = $result->fetch_assoc()) {
            $cuentas_por_estado[] = [
                'estado' => $row['estado'],
                'cantidad' => intval($row['cantidad']),
                'monto' => floatval($row['monto'])
            ];
        }
        
        // Pagos por método
        $sql = "SELECT 
                    metodo_pago,
                    COUNT(*) as cantidad,
                    COALESCE(SUM(monto), 0) as total
                FROM pagos 
                WHERE DATE(fecha_pago) BETWEEN ? AND ?
                GROUP BY metodo_pago
                ORDER BY total DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $pagos_por_metodo = [];
        while($row = $result->fetch_assoc()) {
            $pagos_por_metodo[] = [
                'metodo' => $row['metodo_pago'],
                'cantidad' => intval($row['cantidad']),
                'total' => floatval($row['total'])
            ];
        }
        
        // Tasa de cobranza
        $tasa_cobranza = $total_facturado > 0 
            ? round(($total_pagado / $total_facturado) * 100, 2) 
            : 0;
        
        // Pagos por concepto
        $sql = "SELECT 
                    cp.nombre as concepto,
                    cp.tipo,
                    COUNT(DISTINCT c.id) as cantidad,
                    COALESCE(SUM(c.monto_pagado), 0) as total_pagado
                FROM conceptos_pago cp
                LEFT JOIN cuentas_por_cobrar c ON cp.id = c.concepto_pago_id 
                    AND DATE(c.fecha_creacion) BETWEEN ? AND ?
                GROUP BY cp.id, cp.nombre, cp.tipo
                ORDER BY total_pagado DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $pagos_por_concepto = [];
        while($row = $result->fetch_assoc()) {
            $pagos_por_concepto[] = [
                'concepto' => $row['concepto'],
                'tipo' => $row['tipo'],
                'cantidad' => intval($row['cantidad']),
                'total' => floatval($row['total_pagado'])
            ];
        }
        
        $response['finanzas'] = [
            'total_pendiente' => $total_pendiente,
            'total_facturado' => $total_facturado,
            'total_pagado' => $total_pagado,
            'tasa_cobranza' => $tasa_cobranza,
            'cuentas_por_estado' => $cuentas_por_estado,
            'pagos_por_metodo' => $pagos_por_metodo,
            'pagos_por_concepto' => $pagos_por_concepto
        ];
    }
    
    // ============================================
    // MÉTRICAS DE SEGUIMIENTO
    // ============================================
    if ($area == 'todas' || $area == 'seguimiento') {
        
        // Total de interacciones
        $sql = "SELECT COUNT(*) as total FROM interacciones 
                WHERE DATE(fecha_programada) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $total_interacciones = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        // Interacciones por tipo
        $sql = "SELECT 
                    t.nombre as tipo,
                    t.color,
                    COUNT(i.id) as cantidad
                FROM tipos_interaccion t
                LEFT JOIN interacciones i ON t.id = i.tipo_interaccion_id 
                    AND DATE(i.fecha_programada) BETWEEN ? AND ?
                GROUP BY t.id, t.nombre, t.color
                ORDER BY cantidad DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $interacciones_por_tipo = [];
        while($row = $result->fetch_assoc()) {
            $interacciones_por_tipo[] = $row;
        }
        
        // Interacciones por estado
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad
                FROM interacciones 
                WHERE DATE(fecha_programada) BETWEEN ? AND ?
                GROUP BY estado
                ORDER BY FIELD(estado, 'programado', 'realizado', 'cancelado', 'reagendado')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $interacciones_por_estado = [];
        while($row = $result->fetch_assoc()) {
            $interacciones_por_estado[] = $row;
        }
        
        // Interacciones por resultado
        $sql = "SELECT 
                    resultado,
                    COUNT(*) as cantidad
                FROM interacciones 
                WHERE DATE(fecha_programada) BETWEEN ? AND ?
                    AND resultado IS NOT NULL
                GROUP BY resultado
                ORDER BY cantidad DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $interacciones_por_resultado = [];
        while($row = $result->fetch_assoc()) {
            $interacciones_por_resultado[] = $row;
        }
        
        // Mensajes enviados
        $sql = "SELECT 
                    tipo,
                    COUNT(*) as cantidad,
                    SUM(CASE WHEN estado IN ('enviado', 'entregado', 'leido') THEN 1 ELSE 0 END) as exitosos
                FROM mensajes_enviados 
                WHERE DATE(fecha_envio) BETWEEN ? AND ?
                GROUP BY tipo";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        $mensajes_enviados = [];
        while($row = $result->fetch_assoc()) {
            $mensajes_enviados[] = [
                'tipo' => $row['tipo'],
                'cantidad' => intval($row['cantidad']),
                'exitosos' => intval($row['exitosos'])
            ];
        }
        
        $response['seguimiento'] = [
            'total_interacciones' => $total_interacciones,
            'interacciones_por_tipo' => $interacciones_por_tipo,
            'interacciones_por_estado' => $interacciones_por_estado,
            'interacciones_por_resultado' => $interacciones_por_resultado,
            'mensajes_enviados' => $mensajes_enviados
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar datos del dashboard',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>