<?php
header('Content-Type: application/json');
require_once '../includes/init.php'; // Carga config, funciones y sesión

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Acción no reconocida.'];

//======================================================================
// ACCIÓN PÚBLICA: ENVÍO DEL FORMULARIO
//======================================================================
if ($action === 'submit_form' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aquí puedes añadir la verificación de reCAPTCHA
    
    $email = filter_input(INPUT_POST, 'email_solicitante', FILTER_VALIDATE_EMAIL);
    $fecha = $_POST['fecha_programacion'] ?? date('Y-m-d', strtotime('+1 day'));
    $people_data = $_POST['people'] ?? [];

    if (!$email || empty($people_data)) {
        $response['message'] = 'Faltan datos. Por favor complete el correo y seleccione al menos una persona.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt_prog = $pdo->prepare("INSERT INTO programaciones (fecha_programacion, email_solicitante) VALUES (?, ?)");
        $stmt_prog->execute([$fecha, $email]);
        $prog_id = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare(
            "INSERT INTO detalle_programacion 
            (id_programacion, id_persona, id_sede, desayuno, almuerzo, comida, refrigerio_tipo1, refrigerio_capacitacion, transporte_tipo) 
            VALUES (:prog_id, :persona_id, :sede_id, :desayuno, :almuerzo, :comida, :ref1, :ref_cap, :transporte)"
        );

        foreach ($people_data as $person_id => $details) {
            $stmt_detail->execute([
                ':prog_id' => $prog_id,
                ':persona_id' => $person_id,
                ':sede_id' => $details['id_sede'] ?? null,
                ':desayuno' => isset($details['desayuno']) ? 1 : 0,
                ':almuerzo' => isset($details['almuerzo']) ? 1 : 0,
                ':comida' => isset($details['comida']) ? 1 : 0,
                ':ref1' => isset($details['refrigerio_tipo1']) ? 1 : 0,
                ':ref_cap' => isset($details['refrigerio_capacitacion']) ? 1 : 0,
                ':transporte' => $details['transporte_tipo'] ?? 'No requiere'
            ]);

            // SINCRONIZACIÓN CON GOOGLE SHEETS
            $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
            $person_name_stmt->execute([$person_id]);
            $person_name = $person_name_stmt->fetchColumn();

            $sede_name_stmt = $pdo->prepare("SELECT nombre_sede FROM sedes WHERE id = ?");
            $sede_name_stmt->execute([$details['id_sede']]);
            $sede_name = $sede_name_stmt->fetchColumn();
            
            // ¡IMPORTANTE! El orden de este array debe coincidir con el orden de las columnas en tu Google Sheet.
            $sheet_data = [
                $fecha,
                $email,
                $person_name,
                $sede_name,
                $details['transporte_tipo'] ?? 'No requiere',
                isset($details['desayuno']) ? 'SI' : 'NO',
                isset($details['almuerzo']) ? 'SI' : 'NO',
                isset($details['comida']) ? 'SI' : 'NO',
                isset($details['refrigerio_tipo1']) ? 'SI' : 'NO',
                isset($details['refrigerio_capacitacion']) ? 'SI' : 'NO',
                
            ];
            sync_to_google_sheet($sheet_data, $pdo);
        }
        
        $pdo->commit();
        $response = ['success' => true, 'message' => '¡Programación enviada con éxito! Recibirá una copia en su correo.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en submit_form: " . $e->getMessage());
        $response['message'] = 'Error interno del servidor al guardar los datos. Por favor, intente de nuevo.';
    }
}


//======================================================================
// ACCIONES DEL PANEL DE ADMINISTRACIÓN (SOLO ADMIN)
//======================================================================
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1) {

    // Acción para obtener los datos del dashboard del admin
    if ($action === 'get_admin_dashboard' && isset($_GET['date'])) {
        try {
            $date = $_GET['date'];
            $stmt = $pdo->prepare(
                "SELECT dp.*, p.nombre_completo, s.nombre_sede, pr.email_solicitante 
                 FROM detalle_programacion dp
                 JOIN personas p ON dp.id_persona = p.id
                 JOIN sedes s ON dp.id_sede = s.id
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 WHERE pr.fecha_programacion = ? AND pr.estado = 'pendiente'
                 ORDER BY p.nombre_completo"
            );
            $stmt->execute([$date]);
            $programacion = $stmt->fetchAll();
            $response = ['success' => true, 'programacion' => $programacion];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()];
        }
    }

    // Acción para borrar una fila de detalle
    if ($action === 'delete_row' && isset($_GET['id'])) {
        try {
            $detail_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM detalle_programacion WHERE id = ?");
            $stmt->execute([$detail_id]);
            $response = ['success' => true, 'message' => 'Registro eliminado.'];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }
    
    // Acción para finalizar el día y enviar reportes
    if ($action === 'finalize_and_send' && isset($_POST['date'])) {
        try {
            $date = $_POST['date'];
            $pdo->beginTransaction();

            $chef_reports_stmt = $pdo->prepare(
                "SELECT dp.id_sede, s.nombre_sede,
                    SUM(dp.desayuno) as total_desayunos, SUM(dp.almuerzo) as total_almuerzos,
                    SUM(dp.comida) as total_comidas, SUM(dp.refrigerio_tipo1) as total_ref1,
                    SUM(dp.refrigerio_capacitacion) as total_ref_cap,
                    GROUP_CONCAT(p.nombre_completo SEPARATOR ', ') as personas
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 JOIN personas p ON dp.id_persona = p.id
                 JOIN sedes s ON dp.id_sede = s.id
                 WHERE pr.fecha_programacion = ? AND pr.estado = 'pendiente'
                 GROUP BY dp.id_sede"
            );
            $chef_reports_stmt->execute([$date]);
            $chef_reports = $chef_reports_stmt->fetchAll(PDO::FETCH_GROUP);
            
            $transporter_report_stmt = $pdo->prepare(
                "SELECT p.nombre_completo, s.nombre_sede, dp.transporte_tipo
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 JOIN personas p ON dp.id_persona = p.id
                 JOIN sedes s ON dp.id_sede = s.id
                 WHERE pr.fecha_programacion = ? AND pr.estado = 'pendiente' AND dp.transporte_tipo != 'No requiere'
                 ORDER BY s.nombre_sede, p.nombre_completo"
            );
            $transporter_report_stmt->execute([$date]);
            $transporter_report = $transporter_report_stmt->fetchAll();

            $chefs = $pdo->query("SELECT email, id_sede FROM usuarios WHERE id_rol = 2 AND activo = 1")->fetchAll();
            $transporters = $pdo->query("SELECT email FROM usuarios WHERE id_rol = 3 AND activo = 1")->fetchAll();
            
            foreach ($chefs as $chef) {
                if (isset($chef_reports[$chef['id_sede']])) {
                    $report_data = $chef_reports[$chef['id_sede']][0];
                    $html_body = "<h1>Reporte de Alimentación para {$report_data['nombre_sede']} - Fecha: {$date}</h1>
                                  <h3>Resumen de Cantidades:</h3>
                                  <ul>
                                      <li>Desayunos: <strong>{$report_data['total_desayunos']}</strong></li>
                                      <li>Almuerzos: <strong>{$report_data['total_almuerzos']}</strong></li>
                                      <li>Comidas: <strong>{$report_data['total_comidas']}</strong></li>
                                  </ul>
                                  <h3>Listado de Personal:</h3><p>{$report_data['personas']}</p>";
                    send_brevo_email([['email' => $chef['email']]], "Reporte de Alimentación {$date}", $html_body, $pdo);
                }
            }

            if (!empty($transporter_report) && !empty($transporters)) {
                $html_body = "<h1>Reporte de Transporte - Fecha: {$date}</h1><table border='1' cellpadding='5' cellspacing='0'><thead><tr><th>Persona</th><th>Tipo de Ruta</th><th>Sede Destino</th></tr></thead><tbody>";
                foreach($transporter_report as $row) {
                    $html_body .= "<tr><td>{$row['nombre_completo']}</td><td>{$row['transporte_tipo']}</td><td>{$row['nombre_sede']}</td></tr>";
                }
                $html_body .= "</tbody></table>";
                $transporter_emails = array_map(fn($t) => ['email' => $t['email']], $transporters);
                send_brevo_email($transporter_emails, "Reporte de Transporte {$date}", $html_body, $pdo);
            }
            
            $update_stmt = $pdo->prepare("UPDATE programaciones SET estado = 'finalizada' WHERE fecha_programacion = ? AND estado = 'pendiente'");
            $update_stmt->execute([$date]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => '¡Proceso finalizado! Los reportes han sido enviados.'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response = ['success' => false, 'message' => 'Error Crítico: ' . $e->getMessage()];
        }
    }

    // Acción para obtener datos de analíticas
    if ($action === 'get_analytics_data' && isset($_GET['start']) && isset($_GET['end'])) {
        try {
            $start = $_GET['start'];
            $end = $_GET['end'];
            
            $food_stmt = $pdo->prepare(
                "SELECT SUM(desayuno), SUM(almuerzo), SUM(comida), SUM(refrigerio_tipo1), SUM(refrigerio_capacitacion)
                 FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id
                 WHERE pr.fecha_programacion BETWEEN ? AND ?"
            );
            $food_stmt->execute([$start, $end]);
            $food_data = $food_stmt->fetch(PDO::FETCH_NUM);

            $transport_stmt = $pdo->prepare(
                "SELECT transporte_tipo, COUNT(*) as total
                 FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id
                 WHERE pr.fecha_programacion BETWEEN ? AND ?
                 GROUP BY transporte_tipo"
            );
            $transport_stmt->execute([$start, $end]);
            $transport_data = $transport_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $response = ['success' => true, 'food_data' => $food_data, 'transport_data' => $transport_data];

        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()];
        }
    }
}


echo json_encode($response);
?>