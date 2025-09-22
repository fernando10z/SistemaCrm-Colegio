<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
        throw new Exception('ID de usuario requerido');
    }

    $usuario_id = intval($_POST['usuario_id']);

    // Obtener información detallada del usuario y su carga
    $sql = "SELECT 
        u.id,
        u.nombre,
        u.apellidos,
        u.email,
        u.usuario,
        r.nombre as rol_nombre,
        CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
        
        -- Estadísticas de leads
        COUNT(l.id) as total_leads,
        COUNT(CASE WHEN l.activo = 1 THEN 1 END) as leads_activos,
        COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_mes_actual,
        COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_semana_actual,
        COUNT(CASE WHEN l.proxima_accion_fecha = CURDATE() THEN 1 END) as tareas_hoy,
        COUNT(CASE WHEN l.proxima_accion_fecha < CURDATE() AND l.proxima_accion_fecha IS NOT NULL THEN 1 END) as tareas_vencidas,
        COUNT(CASE WHEN l.proxima_accion_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as tareas_proximas_7dias,
        COUNT(CASE WHEN l.prioridad = 'urgente' THEN 1 END) as leads_urgentes,
        COUNT(CASE WHEN el.es_final = 1 AND el.nombre = 'Matriculado' THEN 1 END) as leads_convertidos,
        
        -- Estadísticas por estado
        COUNT(CASE WHEN el.nombre = 'Nuevo' THEN 1 END) as leads_nuevos,
        COUNT(CASE WHEN el.nombre = 'Contactado' THEN 1 END) as leads_contactados,
        COUNT(CASE WHEN el.nombre = 'Interesado' THEN 1 END) as leads_interesados,
        COUNT(CASE WHEN el.nombre = 'Visita Programada' THEN 1 END) as leads_visita_programada,
        
        -- Promedios y métricas
        AVG(CASE WHEN l.puntaje_interes IS NOT NULL THEN l.puntaje_interes END) as promedio_interes,
        
        -- Fechas importantes
        MAX(l.fecha_ultima_interaccion) as ultima_interaccion,
        MAX(l.updated_at) as ultima_actividad,
        MIN(l.created_at) as primer_lead_asignado,
        
        -- Carga de trabajo
        CASE 
            WHEN r.nombre = 'Coordinador Marketing' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 50.0 * 100
            WHEN r.nombre = 'Tutor' THEN COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 30.0 * 100
            ELSE COUNT(CASE WHEN l.activo = 1 THEN 1 END) / 25.0 * 100
        END as porcentaje_carga
        
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN leads l ON u.id = l.responsable_id
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    WHERE u.id = ? AND u.activo = 1
    GROUP BY u.id, u.nombre, u.apellidos, u.email, u.usuario, r.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    $usuario_data = $result->fetch_assoc();
    
    // Calcular tasa de conversión
    $tasa_conversion = $usuario_data['total_leads'] > 0 ? 
        round(($usuario_data['leads_convertidos'] / $usuario_data['total_leads']) * 100, 1) : 0;
    
    // Determinar nivel de carga
    $porcentaje_carga = round($usuario_data['porcentaje_carga'], 1);
    $nivel_carga = '';
    $color_carga = '';
    
    if ($porcentaje_carga <= 50) {
        $nivel_carga = 'Baja';
        $color_carga = 'success';
    } elseif ($porcentaje_carga <= 75) {
        $nivel_carga = 'Media';
        $color_carga = 'warning';
    } elseif ($porcentaje_carga <= 90) {
        $nivel_carga = 'Alta';
        $color_carga = 'danger';
    } else {
        $nivel_carga = 'Crítica';
        $color_carga = 'dark';
    }
    
    // Formatear fechas
    $ultima_actividad = $usuario_data['ultima_actividad'] ? 
        date('d/m/Y H:i', strtotime($usuario_data['ultima_actividad'])) : 'Sin actividad';
    
    $primer_lead = $usuario_data['primer_lead_asignado'] ? 
        date('d/m/Y', strtotime($usuario_data['primer_lead_asignado'])) : 'N/A';

    // Generar HTML del resumen
    $html = '
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>' . $usuario_data['leads_activos'] . '</h4>
                    <p class="mb-0">Leads Activos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-' . $color_carga . ' text-white">
                <div class="card-body text-center">
                    <h4>' . $porcentaje_carga . '%</h4>
                    <p class="mb-0">Carga de Trabajo</p>
                    <small>Nivel: ' . $nivel_carga . '</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>' . $tasa_conversion . '%</h4>
                    <p class="mb-0">Tasa Conversión</p>
                    <small>' . $usuario_data['leads_convertidos'] . ' convertidos</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Distribución por Estado</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Nuevos:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_nuevos'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Contactados:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_contactados'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Interesados:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_interesados'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Visitas:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_visita_programada'] . '</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Métricas de Actividad</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Este mes:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_mes_actual'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Esta semana:</small>
                            <span class="float-end"><strong>' . $usuario_data['leads_semana_actual'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Urgentes:</small>
                            <span class="float-end"><strong class="text-danger">' . $usuario_data['leads_urgentes'] . '</strong></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Interés prom:</small>
                            <span class="float-end"><strong>' . round($usuario_data['promedio_interes'], 1) . '/5</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Tareas y Seguimiento</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-2 bg-warning text-white rounded">
                                <h5>' . $usuario_data['tareas_hoy'] . '</h5>
                                <small>Tareas Hoy</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-danger text-white rounded">
                                <h5>' . $usuario_data['tareas_vencidas'] . '</h5>
                                <small>Vencidas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-info text-white rounded">
                                <h5>' . $usuario_data['tareas_proximas_7dias'] . '</h5>
                                <small>Próximas (7d)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Información General</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Rol:</strong> ' . htmlspecialchars($usuario_data['rol_nombre']) . '</p>
                            <p><strong>Email:</strong> ' . htmlspecialchars($usuario_data['email']) . '</p>
                            <p><strong>Usuario:</strong> @' . htmlspecialchars($usuario_data['usuario']) . '</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última actividad:</strong> ' . $ultima_actividad . '</p>
                            <p><strong>Primer lead asignado:</strong> ' . $primer_lead . '</p>
                            <p><strong>Total leads histórico:</strong> ' . $usuario_data['total_leads'] . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'data' => $usuario_data,
        'html' => $html,
        'metricas' => [
            'tasa_conversion' => $tasa_conversion,
            'nivel_carga' => $nivel_carga,
            'color_carga' => $color_carga,
            'porcentaje_carga' => $porcentaje_carga
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>