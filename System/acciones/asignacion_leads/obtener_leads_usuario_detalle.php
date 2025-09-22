<?php
// Incluir conexión a la base de datos
include '../../bd/conexion.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
        throw new Exception('ID de usuario requerido');
    }

    $usuario_id = intval($_POST['usuario_id']);
    $estado_filtro = isset($_POST['estado_filtro']) ? intval($_POST['estado_filtro']) : null;
    $prioridad_filtro = isset($_POST['prioridad_filtro']) ? $_POST['prioridad_filtro'] : null;

    // Construir la consulta con filtros
    $where_conditions = ["l.responsable_id = ?", "l.activo = 1"];
    $params = [$usuario_id];
    $param_types = "i";

    if ($estado_filtro) {
        $where_conditions[] = "l.estado_lead_id = ?";
        $params[] = $estado_filtro;
        $param_types .= "i";
    }

    if ($prioridad_filtro) {
        $where_conditions[] = "l.prioridad = ?";
        $params[] = $prioridad_filtro;
        $param_types .= "s";
    }

    $where_clause = implode(" AND ", $where_conditions);

    $sql = "SELECT 
        l.id,
        l.codigo_lead,
        CONCAT(l.nombres_estudiante, ' ', l.apellidos_estudiante) as nombre_estudiante,
        CONCAT(l.nombres_contacto, ' ', l.apellidos_contacto) as nombre_contacto,
        l.telefono,
        l.whatsapp,
        l.email,
        l.prioridad,
        l.puntaje_interes,
        l.created_at,
        l.fecha_ultima_interaccion,
        l.proxima_accion_fecha,
        l.proxima_accion_descripcion,
        l.observaciones,
        
        -- Información del estado
        el.nombre as estado_nombre,
        el.color as estado_color,
        el.descripcion as estado_descripcion,
        
        -- Información del grado
        g.nombre as grado_nombre,
        ne.nombre as nivel_nombre,
        
        -- Información del canal
        cc.nombre as canal_nombre,
        cc.tipo as canal_tipo,
        
        -- Información del colegio
        l.colegio_procedencia,
        l.motivo_cambio,
        
        -- Días en el sistema
        DATEDIFF(NOW(), l.created_at) as dias_en_sistema,
        
        -- Días desde última interacción
        CASE 
            WHEN l.fecha_ultima_interaccion IS NULL THEN DATEDIFF(NOW(), l.created_at)
            ELSE DATEDIFF(NOW(), l.fecha_ultima_interaccion)
        END as dias_sin_interaccion,
        
        -- Contar interacciones
        (SELECT COUNT(*) FROM interacciones i WHERE i.lead_id = l.id) as total_interacciones,
        (SELECT COUNT(*) FROM interacciones i WHERE i.lead_id = l.id AND i.estado = 'realizado') as interacciones_realizadas
        
    FROM leads l
    LEFT JOIN estados_lead el ON l.estado_lead_id = el.id
    LEFT JOIN grados g ON l.grado_interes_id = g.id
    LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
    LEFT JOIN canales_captacion cc ON l.canal_captacion_id = cc.id
    WHERE $where_clause
    ORDER BY 
        CASE l.prioridad 
            WHEN 'urgente' THEN 1 
            WHEN 'alta' THEN 2 
            WHEN 'media' THEN 3 
            WHEN 'baja' THEN 4 
            ELSE 5 
        END,
        l.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $html = '';
    
    if ($result->num_rows > 0) {
        $html .= '
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Interés</th>
                        <th>Actividad</th>
                        <th>Próxima Acción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';

        while($row = $result->fetch_assoc()) {
            // Formatear información
            $nombre_estudiante = htmlspecialchars($row['nombre_estudiante']);
            $nombre_contacto = htmlspecialchars($row['nombre_contacto']);
            
            // Prioridad con colores
            $prioridad_class = '';
            switch($row['prioridad']) {
                case 'urgente':
                    $prioridad_class = 'bg-danger text-white';
                    break;
                case 'alta':
                    $prioridad_class = 'bg-warning text-dark';
                    break;
                case 'media':
                    $prioridad_class = 'bg-info text-white';
                    break;
                default:
                    $prioridad_class = 'bg-light text-dark';
            }
            
            // Estrellas de interés
            $puntaje_interes = (int)($row['puntaje_interes'] ?? 0);
            $estrellas = '';
            for($i = 1; $i <= 5; $i++) {
                $clase = $i <= $puntaje_interes ? 'text-warning' : 'text-muted';
                $estrellas .= "<i class='ti ti-star-filled $clase' style='font-size: 0.8rem;'></i>";
            }
            
            // Días sin interacción
            $dias_sin_interaccion = (int)$row['dias_sin_interaccion'];
            $actividad_class = '';
            $actividad_texto = '';
            
            if ($dias_sin_interaccion == 0) {
                $actividad_texto = 'Hoy';
                $actividad_class = 'text-success';
            } elseif ($dias_sin_interaccion <= 3) {
                $actividad_texto = "Hace {$dias_sin_interaccion}d";
                $actividad_class = 'text-info';
            } elseif ($dias_sin_interaccion <= 7) {
                $actividad_texto = "Hace {$dias_sin_interaccion}d";
                $actividad_class = 'text-warning';
            } else {
                $actividad_texto = "Hace {$dias_sin_interaccion}d";
                $actividad_class = 'text-danger';
            }
            
            // Próxima acción
            $proxima_accion = '';
            if ($row['proxima_accion_fecha']) {
                $fecha_accion = date('d/m/Y', strtotime($row['proxima_accion_fecha']));
                $es_hoy = $row['proxima_accion_fecha'] == date('Y-m-d');
                $es_vencida = $row['proxima_accion_fecha'] < date('Y-m-d');
                
                if ($es_vencida) {
                    $proxima_accion = "<span class='badge bg-danger'>Vencida: $fecha_accion</span>";
                } elseif ($es_hoy) {
                    $proxima_accion = "<span class='badge bg-warning'>Hoy</span>";
                } else {
                    $proxima_accion = "<span class='badge bg-light text-dark'>$fecha_accion</span>";
                }
            } else {
                $proxima_accion = "<span class='text-muted'>Sin programar</span>";
            }
            
            // Información de contacto
            $contacto_info = '';
            if ($row['telefono']) {
                $contacto_info .= "<div class='small text-muted'>📞 " . htmlspecialchars($row['telefono']) . "</div>";
            }
            if ($row['email']) {
                $contacto_info .= "<div class='small text-muted'>📧 " . htmlspecialchars($row['email']) . "</div>";
            }
            
            $html .= "
            <tr>
                <td>
                    <span class='font-monospace small'>" . htmlspecialchars($row['codigo_lead']) . "</span>
                    <div class='small text-muted'>" . $row['dias_en_sistema'] . " días</div>
                </td>
                <td>
                    <div class='fw-bold'>{$nombre_estudiante}</div>
                    <div class='small text-muted'>" . htmlspecialchars($row['nivel_nombre'] . ' - ' . $row['grado_nombre']) . "</div>
                </td>
                <td>
                    <div>{$nombre_contacto}</div>
                    {$contacto_info}
                </td>
                <td>
                    <span class='badge' style='background-color: " . ($row['estado_color'] ?? '#6c757d') . "; color: white;'>
                        " . htmlspecialchars($row['estado_nombre']) . "
                    </span>
                </td>
                <td>
                    <span class='badge {$prioridad_class}'>
                        " . ucfirst($row['prioridad']) . "
                    </span>
                </td>
                <td>
                    <div>{$estrellas}</div>
                    <div class='small text-muted'>{$puntaje_interes}/5</div>
                </td>
                <td>
                    <span class='{$actividad_class} fw-bold'>{$actividad_texto}</span>
                    <div class='small text-muted'>" . $row['total_interacciones'] . " interacciones</div>
                </td>
                <td>{$proxima_accion}</td>
                <td>
                    <div class='btn-group btn-group-sm'>
                        <button type='button' class='btn btn-outline-primary btn-sm' onclick='verDetallesLead(" . $row['id'] . ")' title='Ver Detalles'>
                            <i class='ti ti-eye'></i>
                        </button>
                        <button type='button' class='btn btn-outline-success btn-sm' onclick='contactarLead(" . $row['id'] . ")' title='Contactar'>
                            <i class='ti ti-phone'></i>
                        </button>
                        <button type='button' class='btn btn-outline-warning btn-sm' onclick='reasignarLead(" . $row['id'] . ")' title='Reasignar'>
                            <i class='ti ti-refresh'></i>
                        </button>
                    </div>
                </td>
            </tr>";
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
        
        // Agregar resumen al final
        $total_leads = $result->num_rows;
        $html .= "
        <div class='mt-3 p-3 bg-light rounded'>
            <div class='row text-center'>
                <div class='col-md-3'>
                    <h6 class='text-primary'>{$total_leads}</h6>
                    <small class='text-muted'>Total Leads</small>
                </div>
                <div class='col-md-3'>
                    <h6 class='text-success'>" . ($result->num_rows > 0 ? "Activos" : "0") . "</h6>
                    <small class='text-muted'>Estado</small>
                </div>
                <div class='col-md-3'>
                    <h6 class='text-warning'>Filtrado</h6>
                    <small class='text-muted'>Vista</small>
                </div>
                <div class='col-md-3'>
                    <button type='button' class='btn btn-outline-primary btn-sm' onclick='exportarLeadsUsuario()'>
                        <i class='ti ti-download'></i> Exportar
                    </button>
                </div>
            </div>
        </div>";
        
    } else {
        $html = '
        <div class="text-center py-4">
            <i class="ti ti-users-off" style="font-size: 48px; color: #6c757d;"></i>
            <h6 class="mt-2 text-muted">No hay leads asignados</h6>
            <p class="text-muted">Este usuario no tiene leads asignados con los filtros seleccionados.</p>
        </div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => $result->num_rows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>