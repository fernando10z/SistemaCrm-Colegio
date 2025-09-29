<?php
// acciones/incentivos_referidos/gestionar_referidos.php
session_start();
header('Content-Type: application/json');
include '../../bd/conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? '';
$response = ['success' => false, 'message' => 'Acción no válida'];

try {
    switch ($accion) {
        
        // ============================================
        // ACCIÓN 1: CREAR O EDITAR CÓDIGO
        // ============================================
        case 'crear_codigo':
        case 'editar_codigo':
            // Validar campos requeridos
            $campos_requeridos = ['codigo', 'beneficio_referente', 'beneficio_referido', 'fecha_inicio'];
            foreach ($campos_requeridos as $campo) {
                if (empty($_POST[$campo])) {
                    throw new Exception("El campo {$campo} es requerido");
                }
            }

            $codigo = strtoupper(trim($_POST['codigo']));
            $tipo_codigo = $_POST['tipo_codigo'] ?? 'general';
            $apoderado_id = !empty($_POST['apoderado_id']) ? intval($_POST['apoderado_id']) : null;
            $familia_id = !empty($_POST['familia_id']) ? intval($_POST['familia_id']) : null;
            $descripcion = trim($_POST['descripcion'] ?? '');
            $beneficio_referente = trim($_POST['beneficio_referente']);
            $beneficio_referido = trim($_POST['beneficio_referido']);
            $limite_usos = !empty($_POST['limite_usos']) ? intval($_POST['limite_usos']) : null;
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
            $activo = isset($_POST['activo']) ? 1 : 0;
            $codigo_id = !empty($_POST['codigo_id']) ? intval($_POST['codigo_id']) : null;

            // Validar formato del código
            if (!preg_match('/^[A-Z0-9]{4,20}$/', $codigo)) {
                throw new Exception("El código debe tener entre 4 y 20 caracteres alfanuméricos");
            }

            // Validar fechas
            $fecha_inicio_obj = new DateTime($fecha_inicio);
            if ($fecha_fin) {
                $fecha_fin_obj = new DateTime($fecha_fin);
                if ($fecha_fin_obj <= $fecha_inicio_obj) {
                    throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio");
                }
            }

            // Validar que el código no exista (excepto si es edición)
            $check_sql = "SELECT id FROM codigos_referido WHERE codigo = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_id = $codigo_id ?? 0;
            $check_stmt->bind_param("si", $codigo, $check_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                throw new Exception("El código '{$codigo}' ya está en uso");
            }

            // Validar que si es personalizado, tenga apoderado o familia
            if ($tipo_codigo === 'personalizado' && !$apoderado_id && !$familia_id) {
                throw new Exception("Para código personalizado debe seleccionar un apoderado o familia");
            }

            // Si es código general, limpiar apoderado y familia
            if ($tipo_codigo === 'general') {
                $apoderado_id = null;
                $familia_id = null;
            }

            // Si seleccionó apoderado, obtener su familia
            if ($apoderado_id && !$familia_id) {
                $fam_sql = "SELECT familia_id FROM apoderados WHERE id = ?";
                $fam_stmt = $conn->prepare($fam_sql);
                $fam_stmt->bind_param("i", $apoderado_id);
                $fam_stmt->execute();
                $fam_result = $fam_stmt->get_result();
                if ($fam_row = $fam_result->fetch_assoc()) {
                    $familia_id = $fam_row['familia_id'];
                }
            }

            $conn->begin_transaction();

            if ($codigo_id) {
                // Editar código existente
                $sql = "UPDATE codigos_referido SET
                        codigo = ?,
                        apoderado_id = ?,
                        familia_id = ?,
                        descripcion = ?,
                        beneficio_referente = ?,
                        beneficio_referido = ?,
                        limite_usos = ?,
                        fecha_inicio = ?,
                        fecha_fin = ?,
                        activo = ?,
                        updated_at = NOW()
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siisssissii",
                    $codigo,
                    $apoderado_id,
                    $familia_id,
                    $descripcion,
                    $beneficio_referente,
                    $beneficio_referido,
                    $limite_usos,
                    $fecha_inicio,
                    $fecha_fin,
                    $activo,
                    $codigo_id
                );

                $mensaje = "Código '{$codigo}' actualizado exitosamente";
            } else {
                // Crear nuevo código
                $sql = "INSERT INTO codigos_referido (
                        codigo, apoderado_id, familia_id, descripcion,
                        beneficio_referente, beneficio_referido,
                        limite_usos, usos_actuales, fecha_inicio, fecha_fin, activo
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siisssissi",
                    $codigo,
                    $apoderado_id,
                    $familia_id,
                    $descripcion,
                    $beneficio_referente,
                    $beneficio_referido,
                    $limite_usos,
                    $fecha_inicio,
                    $fecha_fin,
                    $activo
                );

                $mensaje = "Código '{$codigo}' creado exitosamente";
            }

            if (!$stmt->execute()) {
                throw new Exception("Error al guardar el código: " . $stmt->error);
            }

            $conn->commit();

            $response = [
                'success' => true,
                'message' => $mensaje,
                'redirect' => 'incentivos_referidos.php'
            ];
            break;

        // ============================================
        // ACCIÓN 2: REGISTRAR USO DE CÓDIGO
        // ============================================
        case 'registrar_uso':
            // Validar campos requeridos
            if (empty($_POST['lead_id_selected']) || empty($_POST['codigo_referido'])) {
                throw new Exception("Debe seleccionar un lead y un código de referido");
            }

            $lead_id = intval($_POST['lead_id_selected']);
            $codigo = strtoupper(trim($_POST['codigo_referido']));
            $observaciones = trim($_POST['observaciones_uso'] ?? '');

            // Validar que el lead exista y esté activo
            $lead_check = "SELECT id, estado_lead_id FROM leads WHERE id = ? AND activo = 1";
            $lead_stmt = $conn->prepare($lead_check);
            $lead_stmt->bind_param("i", $lead_id);
            $lead_stmt->execute();
            $lead_result = $lead_stmt->get_result();
            
            if ($lead_result->num_rows === 0) {
                throw new Exception("El lead seleccionado no es válido");
            }

            // Validar que el código exista y esté disponible
            $codigo_check = "SELECT id, limite_usos, usos_actuales, fecha_inicio, fecha_fin, activo
                            FROM codigos_referido 
                            WHERE codigo = ?";
            $codigo_stmt = $conn->prepare($codigo_check);
            $codigo_stmt->bind_param("s", $codigo);
            $codigo_stmt->execute();
            $codigo_result = $codigo_stmt->get_result();
            
            if ($codigo_result->num_rows === 0) {
                throw new Exception("El código ingresado no existe");
            }

            $codigo_data = $codigo_result->fetch_assoc();

            // Validar que el código esté activo
            if ($codigo_data['activo'] != 1) {
                throw new Exception("El código está inactivo");
            }

            // Validar fecha de vigencia
            $hoy = new DateTime();
            $fecha_inicio = new DateTime($codigo_data['fecha_inicio']);
            if ($hoy < $fecha_inicio) {
                throw new Exception("El código aún no está vigente");
            }

            if ($codigo_data['fecha_fin']) {
                $fecha_fin = new DateTime($codigo_data['fecha_fin']);
                if ($hoy > $fecha_fin) {
                    throw new Exception("El código ha vencido");
                }
            }

            // Validar límite de usos
            if ($codigo_data['limite_usos']) {
                if ($codigo_data['usos_actuales'] >= $codigo_data['limite_usos']) {
                    throw new Exception("El código ha alcanzado su límite de usos");
                }
            }

            // Validar que el lead no haya usado este código antes
            $uso_previo = "SELECT id FROM usos_referido WHERE codigo_referido_id = ? AND lead_id = ?";
            $uso_stmt = $conn->prepare($uso_previo);
            $uso_stmt->bind_param("ii", $codigo_data['id'], $lead_id);
            $uso_stmt->execute();
            $uso_result = $uso_stmt->get_result();
            
            if ($uso_result->num_rows > 0) {
                throw new Exception("Este lead ya utilizó este código anteriormente");
            }

            $conn->begin_transaction();

            // Registrar el uso del código
            $insert_uso = "INSERT INTO usos_referido (
                          codigo_referido_id, lead_id, convertido, observaciones, fecha_uso
                          ) VALUES (?, ?, 0, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_uso);
            $insert_stmt->bind_param("iis", $codigo_data['id'], $lead_id, $observaciones);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Error al registrar el uso: " . $insert_stmt->error);
            }

            // Incrementar contador de usos del código
            $update_codigo = "UPDATE codigos_referido SET usos_actuales = usos_actuales + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_codigo);
            $update_stmt->bind_param("i", $codigo_data['id']);
            $update_stmt->execute();

            $conn->commit();

            $response = [
                'success' => true,
                'message' => "Uso del código '{$codigo}' registrado exitosamente. El beneficio se aplicará cuando el lead se convierta.",
                'redirect' => 'incentivos_referidos.php'
            ];
            break;

        // ============================================
        // ACCIÓN 3: VALIDAR CONVERSIÓN
        // ============================================
        case 'validar_conversion':
            // Validar campos requeridos
            if (empty($_POST['uso_id_selected']) || empty($_POST['fecha_conversion'])) {
                throw new Exception("Debe seleccionar un uso y especificar la fecha de conversión");
            }

            if (!isset($_POST['confirmar_aplicacion'])) {
                throw new Exception("Debe confirmar la aplicación de beneficios");
            }

            $uso_id = intval($_POST['uso_id_selected']);
            $fecha_conversion = $_POST['fecha_conversion'];
            $estudiante_id = !empty($_POST['estudiante_convertido']) ? intval($_POST['estudiante_convertido']) : null;
            $notas = trim($_POST['notas_conversion'] ?? '');
            $metodo_aplicacion = $_POST['metodo_aplicacion'] ?? 'automatico';

            // Validar que el uso exista y esté pendiente
            $uso_check = "SELECT ur.*, cr.codigo, cr.beneficio_referente, cr.beneficio_referido,
                          cr.apoderado_id, cr.familia_id, l.nombres_estudiante
                          FROM usos_referido ur
                          INNER JOIN codigos_referido cr ON ur.codigo_referido_id = cr.id
                          INNER JOIN leads l ON ur.lead_id = l.id
                          WHERE ur.id = ? AND ur.convertido = 0";
            $uso_stmt = $conn->prepare($uso_check);
            $uso_stmt->bind_param("i", $uso_id);
            $uso_stmt->execute();
            $uso_result = $uso_stmt->get_result();
            
            if ($uso_result->num_rows === 0) {
                throw new Exception("El uso seleccionado no es válido o ya fue convertido");
            }

            $uso_data = $uso_result->fetch_assoc();

            // Validar fecha de conversión
            $fecha_uso = new DateTime($uso_data['fecha_uso']);
            $fecha_conv = new DateTime($fecha_conversion);
            
            if ($fecha_conv < $fecha_uso) {
                throw new Exception("La fecha de conversión no puede ser anterior al uso del código");
            }

            $conn->begin_transaction();

            // Actualizar el uso como convertido
            $update_uso = "UPDATE usos_referido SET 
                          convertido = 1,
                          fecha_conversion = ?,
                          observaciones = CONCAT(COALESCE(observaciones, ''), '\n\n[Conversión] ', ?)
                          WHERE id = ?";
            $update_stmt = $conn->prepare($update_uso);
            $update_stmt->bind_param("ssi", $fecha_conversion, $notas, $uso_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Error al actualizar el uso: " . $update_stmt->error);
            }

            // Si se especificó el método automático, crear descuentos
            if ($metodo_aplicacion === 'automatico') {
                // Aquí se podrían crear registros en una tabla de descuentos/beneficios pendientes
                // Por ahora solo registramos en observaciones
                
                // Registrar beneficio para el referente
                if ($uso_data['familia_id']) {
                    $log_beneficio = "INSERT INTO interacciones (
                                     tipo_interaccion_id, usuario_id, familia_id,
                                     asunto, descripcion, fecha_realizada, estado
                                     ) VALUES (
                                     3, ?, ?, 
                                     'Beneficio por Referido Exitoso',
                                     'Familia recibe beneficio por conversión de referido: {$uso_data['beneficio_referente']}. Código: {$uso_data['codigo']}',
                                     NOW(), 'realizado'
                                     )";
                    $log_stmt = $conn->prepare($log_beneficio);
                    $log_stmt->bind_param("ii", $usuario_id, $uso_data['familia_id']);
                    $log_stmt->execute();
                }
            }

            // Actualizar el estado del lead
            $estado_convertido = 5; // ID del estado "Matriculado"
            $update_lead = "UPDATE leads SET 
                           estado_lead_id = ?,
                           fecha_conversion = ?
                           WHERE id = ?";
            $lead_stmt = $conn->prepare($update_lead);
            $lead_stmt->bind_param("isi", $estado_convertido, $fecha_conversion, $uso_data['lead_id']);
            $lead_stmt->execute();

            $conn->commit();

            $response = [
                'success' => true,
                'message' => "Conversión validada exitosamente. Los beneficios han sido registrados y se aplicarán según el método seleccionado.",
                'redirect' => 'incentivos_referidos.php'
            ];
            break;

        // ============================================
        // ACCIÓN AUXILIAR: TOGGLE ESTADO
        // ============================================
        case 'toggle_estado':
            if (empty($_POST['codigo_id'])) {
                throw new Exception("ID de código no especificado");
            }

            $codigo_id = intval($_POST['codigo_id']);
            $nuevo_estado = intval($_POST['nuevo_estado']);

            $sql = "UPDATE codigos_referido SET activo = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nuevo_estado, $codigo_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al cambiar el estado");
            }

            $estado_texto = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            $response = [
                'success' => true,
                'message' => "Código {$estado_texto} correctamente"
            ];
            break;

        default:
            throw new Exception("Acción no reconocida: {$accion}");
    }

} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Si es una petición POST normal (no AJAX), redirigir con mensaje
if (!empty($response['redirect'])) {
    $_SESSION['mensaje_sistema'] = $response['message'];
    $_SESSION['tipo_mensaje'] = $response['success'] ? 'success' : 'error';
    header("Location: ../../{$response['redirect']}");
    exit();
}

echo json_encode($response);
?>