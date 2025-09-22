<?php
// actions/gestionar_duplicados.php
include '../../bd/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'fusionar':
            fusionarLeads($conn);
            break;
        case 'desactivar':
            desactivarLeads($conn);
            break;
        case 'marcar_principal':
            marcarComoPrincipal($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function fusionarLeads($conn) {
    $lead_principal = $_POST['lead_principal'] ?? '';
    $leads_duplicados = $_POST['leads_duplicados'] ?? [];
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if (empty($lead_principal)) {
        echo json_encode(['success' => false, 'message' => 'Debe especificar el lead principal']);
        return;
    }

    if (empty($leads_duplicados) || !is_array($leads_duplicados)) {
        echo json_encode(['success' => false, 'message' => 'Debe especificar los leads duplicados']);
        return;
    }

    if (empty($observaciones)) {
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar observaciones sobre la fusión']);
        return;
    }

    // Quitar el lead principal de la lista de duplicados
    $leads_duplicados = array_filter($leads_duplicados, function($id) use ($lead_principal) {
        return $id != $lead_principal;
    });

    if (empty($leads_duplicados)) {
        echo json_encode(['success' => false, 'message' => 'No hay leads duplicados para fusionar']);
        return;
    }

    // Verificar que todos los leads existen
    $placeholders = str_repeat('?,', count($leads_duplicados) + 1);
    $placeholders = rtrim($placeholders, ',');
    
    $all_leads = array_merge([$lead_principal], $leads_duplicados);
    $check_sql = "SELECT id FROM leads WHERE id IN ($placeholders) AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param(str_repeat('i', count($all_leads)), ...$all_leads);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows !== count($all_leads)) {
        echo json_encode(['success' => false, 'message' => 'Algunos leads no existen o ya están inactivos']);
        return;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Obtener datos del lead principal
        $principal_sql = "SELECT * FROM leads WHERE id = ?";
        $principal_stmt = $conn->prepare($principal_sql);
        $principal_stmt->bind_param("i", $lead_principal);
        $principal_stmt->execute();
        $principal_data = $principal_stmt->get_result()->fetch_assoc();

        if (!$principal_data) {
            throw new Exception('Lead principal no encontrado');
        }

        // Obtener datos de los leads duplicados
        $duplicados_placeholders = str_repeat('?,', count($leads_duplicados));
        $duplicados_placeholders = rtrim($duplicados_placeholders, ',');
        
        $duplicados_sql = "SELECT * FROM leads WHERE id IN ($duplicados_placeholders)";
        $duplicados_stmt = $conn->prepare($duplicados_sql);
        $duplicados_stmt->bind_param(str_repeat('i', count($leads_duplicados)), ...$leads_duplicados);
        $duplicados_stmt->execute();
        $duplicados_result = $duplicados_stmt->get_result();

        $leads_data = [];
        while ($row = $duplicados_result->fetch_assoc()) {
            $leads_data[] = $row;
        }

        // Consolidar información del lead principal con la mejor información disponible
        $datos_consolidados = consolidarDatos($principal_data, $leads_data);

        // Actualizar el lead principal con los datos consolidados
        $update_principal_sql = "UPDATE leads SET 
            nombres_estudiante = ?, apellidos_estudiante = ?, fecha_nacimiento_estudiante = ?, genero_estudiante = ?,
            nombres_contacto = ?, apellidos_contacto = ?, telefono = ?, whatsapp = ?, email = ?,
            colegio_procedencia = ?, motivo_cambio = ?, observaciones = ?, grado_interes_id = ?,
            canal_captacion_id = ?, prioridad = ?, puntaje_interes = ?, 
            proxima_accion_fecha = ?, proxima_accion_descripcion = ?,
            utm_source = ?, utm_medium = ?, utm_campaign = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $update_stmt = $conn->prepare($update_principal_sql);
        $update_stmt->bind_param("ssssssssssssiisisssssi",
            $datos_consolidados['nombres_estudiante'], $datos_consolidados['apellidos_estudiante'], 
            $datos_consolidados['fecha_nacimiento_estudiante'], $datos_consolidados['genero_estudiante'],
            $datos_consolidados['nombres_contacto'], $datos_consolidados['apellidos_contacto'],
            $datos_consolidados['telefono'], $datos_consolidados['whatsapp'], $datos_consolidados['email'],
            $datos_consolidados['colegio_procedencia'], $datos_consolidados['motivo_cambio'], 
            $datos_consolidados['observaciones'], $datos_consolidados['grado_interes_id'],
            $datos_consolidados['canal_captacion_id'], $datos_consolidados['prioridad'], 
            $datos_consolidados['puntaje_interes'], $datos_consolidados['proxima_accion_fecha'], 
            $datos_consolidados['proxima_accion_descripcion'], $datos_consolidados['utm_source'], 
            $datos_consolidados['utm_medium'], $datos_consolidados['utm_campaign'], $lead_principal
        );

        if (!$update_stmt->execute()) {
            throw new Exception('Error al actualizar el lead principal: ' . $update_stmt->error);
        }

        // Migrar interacciones de los duplicados al lead principal
        foreach ($leads_duplicados as $dup_id) {
            $migrate_interacciones_sql = "UPDATE interacciones SET lead_id = ?, observaciones = CONCAT(IFNULL(observaciones, ''), ' [Migrado desde Lead ID: $dup_id]') WHERE lead_id = ?";
            $migrate_stmt = $conn->prepare($migrate_interacciones_sql);
            $migrate_stmt->bind_param("ii", $lead_principal, $dup_id);
            $migrate_stmt->execute();
        }

        // Crear registro en historial de estados para documentar la fusión
        $historial_sql = "INSERT INTO historial_estados_lead (lead_id, estado_anterior_id, estado_nuevo_id, usuario_id, observaciones) 
                         VALUES (?, ?, ?, 1, ?)";
        $observaciones_historial = "FUSIÓN: " . $observaciones . " - Leads fusionados: " . implode(', ', $leads_duplicados);
        $historial_stmt = $conn->prepare($historial_sql);
        $historial_stmt->bind_param("iiis", $lead_principal, $principal_data['estado_lead_id'], $principal_data['estado_lead_id'], $observaciones_historial);
        $historial_stmt->execute();

        // Agregar observaciones de fusión al lead principal
        $obs_fusion = "\n\n--- FUSIÓN REALIZADA ---\n";
        $obs_fusion .= "Fecha: " . date('d/m/Y H:i:s') . "\n";
        $obs_fusion .= "Leads fusionados: " . implode(', ', $leads_duplicados) . "\n";
        $obs_fusion .= "Motivo: " . $observaciones . "\n";
        $obs_fusion .= "------------------------\n";

        $update_obs_sql = "UPDATE leads SET observaciones = CONCAT(IFNULL(observaciones, ''), ?) WHERE id = ?";
        $update_obs_stmt = $conn->prepare($update_obs_sql);
        $update_obs_stmt->bind_param("si", $obs_fusion, $lead_principal);
        $update_obs_stmt->execute();

        // Desactivar los leads duplicados
        foreach ($leads_duplicados as $dup_id) {
            $deactivate_sql = "UPDATE leads SET activo = 0, observaciones = CONCAT(IFNULL(observaciones, ''), '\n\n[FUSIONADO CON LEAD ID: $lead_principal el " . date('d/m/Y H:i:s') . "]') WHERE id = ?";
            $deactivate_stmt = $conn->prepare($deactivate_sql);
            $deactivate_stmt->bind_param("i", $dup_id);
            $deactivate_stmt->execute();
        }

        // Confirmar transacción
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Leads fusionados exitosamente',
            'lead_principal' => $lead_principal,
            'leads_fusionados' => $leads_duplicados
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error en la fusión: ' . $e->getMessage()]);
    }
}

function consolidarDatos($principal, $duplicados) {
    $consolidado = $principal;

    foreach ($duplicados as $dup) {
        // Consolidar usando la información más completa disponible
        
        // Información del estudiante
        if (empty($consolidado['nombres_estudiante']) && !empty($dup['nombres_estudiante'])) {
            $consolidado['nombres_estudiante'] = $dup['nombres_estudiante'];
        }
        if (empty($consolidado['apellidos_estudiante']) && !empty($dup['apellidos_estudiante'])) {
            $consolidado['apellidos_estudiante'] = $dup['apellidos_estudiante'];
        }
        if (empty($consolidado['fecha_nacimiento_estudiante']) && !empty($dup['fecha_nacimiento_estudiante'])) {
            $consolidado['fecha_nacimiento_estudiante'] = $dup['fecha_nacimiento_estudiante'];
        }
        if (empty($consolidado['genero_estudiante']) && !empty($dup['genero_estudiante'])) {
            $consolidado['genero_estudiante'] = $dup['genero_estudiante'];
        }

        // Información de contacto
        if (empty($consolidado['whatsapp']) && !empty($dup['whatsapp'])) {
            $consolidado['whatsapp'] = $dup['whatsapp'];
        }
        if (empty($consolidado['colegio_procedencia']) && !empty($dup['colegio_procedencia'])) {
            $consolidado['colegio_procedencia'] = $dup['colegio_procedencia'];
        }
        if (empty($consolidado['motivo_cambio']) && !empty($dup['motivo_cambio'])) {
            $consolidado['motivo_cambio'] = $dup['motivo_cambio'];
        }

        // Usar el puntaje de interés más alto
        if (($dup['puntaje_interes'] ?? 0) > ($consolidado['puntaje_interes'] ?? 0)) {
            $consolidado['puntaje_interes'] = $dup['puntaje_interes'];
        }

        // Usar la prioridad más alta (urgente > alta > media > baja)
        $prioridades = ['baja' => 1, 'media' => 2, 'alta' => 3, 'urgente' => 4];
        $prioridad_actual = $prioridades[$consolidado['prioridad']] ?? 1;
        $prioridad_dup = $prioridades[$dup['prioridad']] ?? 1;
        if ($prioridad_dup > $prioridad_actual) {
            $consolidado['prioridad'] = $dup['prioridad'];
        }

        // Consolidar observaciones
        if (!empty($dup['observaciones'])) {
            $obs_adicionales = "\n\n--- OBSERVACIONES DE LEAD " . $dup['id'] . " ---\n" . $dup['observaciones'];
            $consolidado['observaciones'] = ($consolidado['observaciones'] ?? '') . $obs_adicionales;
        }

        // Información UTM (usar la más reciente/completa)
        if (empty($consolidado['utm_source']) && !empty($dup['utm_source'])) {
            $consolidado['utm_source'] = $dup['utm_source'];
        }
        if (empty($consolidado['utm_medium']) && !empty($dup['utm_medium'])) {
            $consolidado['utm_medium'] = $dup['utm_medium'];
        }
        if (empty($consolidado['utm_campaign']) && !empty($dup['utm_campaign'])) {
            $consolidado['utm_campaign'] = $dup['utm_campaign'];
        }
    }

    return $consolidado;
}

function desactivarLeads($conn) {
    $leads = $_POST['leads'] ?? [];

    if (empty($leads) || !is_array($leads)) {
        echo json_encode(['success' => false, 'message' => 'Debe especificar los leads a desactivar']);
        return;
    }

    // Verificar que los leads existen
    $placeholders = str_repeat('?,', count($leads));
    $placeholders = rtrim($placeholders, ',');
    
    $check_sql = "SELECT id FROM leads WHERE id IN ($placeholders) AND activo = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param(str_repeat('i', count($leads)), ...$leads);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows !== count($leads)) {
        echo json_encode(['success' => false, 'message' => 'Algunos leads no existen o ya están inactivos']);
        return;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        foreach ($leads as $lead_id) {
            // Desactivar el lead
            $deactivate_sql = "UPDATE leads SET 
                activo = 0, 
                observaciones = CONCAT(IFNULL(observaciones, ''), '\n\n[DESACTIVADO POR DUPLICADO el " . date('d/m/Y H:i:s') . "]'),
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
            $deactivate_stmt = $conn->prepare($deactivate_sql);
            $deactivate_stmt->bind_param("i", $lead_id);
            
            if (!$deactivate_stmt->execute()) {
                throw new Exception('Error al desactivar lead ID: ' . $lead_id);
            }

            // Cancelar interacciones programadas pendientes
            $cancel_interactions_sql = "UPDATE interacciones SET 
                estado = 'cancelado',
                observaciones = CONCAT(IFNULL(observaciones, ''), ' [Lead desactivado por duplicado]')
                WHERE lead_id = ? AND estado = 'programado'";
            $cancel_stmt = $conn->prepare($cancel_interactions_sql);
            $cancel_stmt->bind_param("i", $lead_id);
            $cancel_stmt->execute();
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Leads desactivados exitosamente',
            'leads_desactivados' => $leads
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al desactivar leads: ' . $e->getMessage()]);
    }
}

function marcarComoPrincipal($conn) {
    $leads = $_POST['leads'] ?? [];
    $lead_principal = $_POST['lead_principal'] ?? '';

    if (empty($leads) || !is_array($leads) || empty($lead_principal)) {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes']);
        return;
    }

    if (!in_array($lead_principal, $leads)) {
        echo json_encode(['success' => false, 'message' => 'El lead principal debe estar en la lista de seleccionados']);
        return;
    }

    try {
        // Marcar el lead como alta prioridad y agregar observación
        $update_sql = "UPDATE leads SET 
            prioridad = 'alta',
            observaciones = CONCAT(IFNULL(observaciones, ''), '\n\n[MARCADO COMO PRINCIPAL entre duplicados el " . date('d/m/Y H:i:s') . "]'),
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $lead_principal);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Lead marcado como principal exitosamente',
                'lead_principal' => $lead_principal
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al marcar como principal: ' . $stmt->error]);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

$conn->close();
?>