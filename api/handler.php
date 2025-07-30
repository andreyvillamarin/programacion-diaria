<?php
header('Content-Type: application/json');
require_once '../includes/init.php'; // Carga config, funciones y sesión

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Acción no reconocida.'];

//======================================================================
// ACCIÓN PÚBLICA: ENVÍO DEL FORMULARIO
//======================================================================
if ($action === 'submit_form' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email_solicitante', FILTER_VALIDATE_EMAIL);
    $fecha = $_POST['fecha_programacion'];
    $area_id = $_POST['area'];

    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM programaciones WHERE fecha_programacion = ? AND estado = 'finalizada'");
    $check_stmt->execute([$fecha]);
    if ($check_stmt->fetchColumn() > 0) {
        $response = ['success' => false, 'message' => 'La programación para esta fecha ya ha sido finalizada y no se pueden añadir más registros.'];
        echo json_encode($response);
        exit;
    }

    if (!$fecha || !$area_id) {
        $response['message'] = 'Faltan datos del solicitante. Por favor complete el formulario.';
        echo json_encode($response);
        exit;
    }

    $area_stmt = $pdo->prepare("SELECT nombre_area FROM areas WHERE id = ?");
    $area_stmt->execute([$area_id]);
    $area_nombre = $area_stmt->fetchColumn();

    try {
        $pdo->beginTransaction();
        $stmt_prog = $pdo->prepare("INSERT INTO programaciones (fecha_programacion, email_solicitante) VALUES (?, ?)");
        $stmt_prog->execute([$fecha, $email]);
        $prog_id = $pdo->lastInsertId();

        if (trim(mb_strtolower($area_nombre, 'UTF-8')) == 'otras áreas') {
            $details = $_POST['other'];
            if (empty($details['nombre_manual']) || empty($details['area_wbe']) || empty($details['actividad'])) {
                 throw new Exception("Faltan datos en el formulario de Otras Áreas.");
            }
            $stmt_detail = $pdo->prepare("INSERT INTO detalle_programacion (id_programacion, id_persona, id_sede, desayuno, almuerzo, comida, refrigerio_tipo1, refrigerio_capacitacion, transporte_tipo, nombre_manual, area_wbe, actividad) VALUES (:prog_id, NULL, :sede_id, :desayuno, :almuerzo, :comida, :ref1, :ref_cap, :transporte, :nombre, :area, :actividad)");
            $stmt_detail->execute([':prog_id' => $prog_id, ':sede_id' => $details['id_sede'] ?? null, ':desayuno' => isset($details['desayuno']) ? 1 : 0, ':almuerzo' => isset($details['almuerzo']) ? 1 : 0, ':comida' => isset($details['comida']) ? 1 : 0, ':ref1' => isset($details['refrigerio_tipo1']) ? 1 : 0, ':ref_cap' => isset($details['refrigerio_capacitacion']) ? 1 : 0, ':transporte' => $details['transporte_tipo'] ?? 'No requiere', ':nombre' => $details['nombre_manual'], ':area' => $details['area_wbe'], ':actividad' => $details['actividad']]);
            $sede_name_stmt = $pdo->prepare("SELECT nombre_sede FROM sedes WHERE id = ?"); $sede_name_stmt->execute([$details['id_sede']]); $sede_name = $sede_name_stmt->fetchColumn();
            $sheet_data = [ $fecha, $email, $details['nombre_manual'], $details['area_wbe'], $sede_name, $details['transporte_tipo'] ?? 'No requiere', isset($details['desayuno']) ? 'SI' : 'NO', isset($details['almuerzo']) ? 'SI' : 'NO', isset($details['comida']) ? 'SI' : 'NO', isset($details['refrigerio_tipo1']) ? 'SI' : 'NO', isset($details['refrigerio_capacitacion']) ? 'SI' : 'NO', $details['actividad'] ];
            sync_to_google_sheet($sheet_data, $pdo);

        } else {
            $people_data = $_POST['people'] ?? [];
            if (empty($people_data)) { throw new Exception("No se seleccionó ninguna persona para programar."); }
            $stmt_detail = $pdo->prepare("INSERT INTO detalle_programacion (id_programacion, id_persona, id_sede, desayuno, almuerzo, comida, refrigerio_tipo1, refrigerio_capacitacion, transporte_tipo) VALUES (:prog_id, :persona_id, :sede_id, :desayuno, :almuerzo, :comida, :ref1, :ref_cap, :transporte)");
            foreach ($people_data as $person_id => $details) {
                $has_food = !empty($details['desayuno']) || !empty($details['almuerzo']) || !empty($details['comida']) || !empty($details['refrigerio_tipo1']) || !empty($details['refrigerio_capacitacion']);
                $has_transport = !empty($details['transporte_tipo']) && $details['transporte_tipo'] !== 'No requiere';

                if ($has_food || $has_transport) {
                    if ($has_transport && empty($details['id_sede'])) {
                        $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
                        $person_name_stmt->execute([$person_id]);
                        $person_name = $person_name_stmt->fetchColumn();
                        throw new Exception("Se debe seleccionar una sede de destino para {$person_name} ya que se ha seleccionado un tipo de transporte.");
                    }

                    // Verificar si la persona ya tiene una programación para ese día
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM detalle_programacion dp JOIN programaciones p ON dp.id_programacion = p.id WHERE dp.id_persona = ? AND p.fecha_programacion = ?");
                    $check_stmt->execute([$person_id, $fecha]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
                        $person_name_stmt->execute([$person_id]);
                        $person_name = $person_name_stmt->fetchColumn();
                        throw new Exception("La persona {$person_name} ya tiene una programación para el día {$fecha}.");
                    }

                    $stmt_detail->execute([':prog_id' => $prog_id, ':persona_id' => $person_id, ':sede_id' => $details['id_sede'], ':desayuno' => isset($details['desayuno']) ? 1 : 0, ':almuerzo' => isset($details['almuerzo']) ? 1 : 0, ':comida' => isset($details['comida']) ? 1 : 0, ':ref1' => isset($details['refrigerio_tipo1']) ? 1 : 0, ':ref_cap' => isset($details['refrigerio_capacitacion']) ? 1 : 0, ':transporte' => $details['transporte_tipo'] ?? 'No requiere']);
                    $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?"); $person_name_stmt->execute([$person_id]); $person_name = $person_name_stmt->fetchColumn();
                    $sede_name_stmt = $pdo->prepare("SELECT nombre_sede FROM sedes WHERE id = ?"); $sede_name_stmt->execute([$details['id_sede']]); $sede_name = $sede_name_stmt->fetchColumn();
                    $sheet_data = [ $fecha, $email, $person_name, $area_nombre, $sede_name, $details['transporte_tipo'] ?? 'No requiere', isset($details['desayuno']) ? 'SI' : 'NO', isset($details['almuerzo']) ? 'SI' : 'NO', isset($details['comida']) ? 'SI' : 'NO', isset($details['refrigerio_tipo1']) ? 'SI' : 'NO', isset($details['refrigerio_capacitacion']) ? 'SI' : 'NO', '' ];
                    sync_to_google_sheet($sheet_data, $pdo);
                }
            }
        }
        $pdo->commit();

        if ($email) {
            $programacion_cards = '';
            if (trim(mb_strtolower($area_nombre, 'UTF-8')) == 'otras áreas') {
                $details = $_POST['other'];
                $programacion_cards .= "<div class='card'><h3>{$details['nombre_manual']}</h3><ul>";
                $programacion_cards .= "<li><strong>Área | WBE:</strong> {$details['area_wbe']}</li>";
                $programacion_cards .= "<li><strong>Actividad:</strong> {$details['actividad']}</li>";
                $programacion_cards .= "</ul></div>";
            } else {
                $people_data = $_POST['people'] ?? [];
                foreach ($people_data as $person_id => $details) {
                    if (!empty($details['desayuno']) || !empty($details['almuerzo']) || !empty($details['comida']) || !empty($details['refrigerio_tipo1']) || !empty($details['refrigerio_capacitacion']) || !empty($details['transporte_tipo'])) {
                        $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
                        $person_name_stmt->execute([$person_id]);
                        $person_name = $person_name_stmt->fetchColumn();

                        $sede_name = '';
                        if (!empty($details['id_sede'])) {
                            $sede_name_stmt = $pdo->prepare("SELECT nombre_sede FROM sedes WHERE id = ?");
                            $sede_name_stmt->execute([$details['id_sede']]);
                            $sede_name = $sede_name_stmt->fetchColumn();
                        }

                        $programacion_cards .= "<div class='card'><h3>{$person_name}</h3><ul>";
                        if (!empty($sede_name)) $programacion_cards .= "<li>✔️ Sede: {$sede_name}</li>";
                        if (isset($details['desayuno'])) $programacion_cards .= "<li>✔️ Desayuno</li>";
                        if (isset($details['almuerzo'])) $programacion_cards .= "<li>✔️ Almuerzo</li>";
                        if (isset($details['comida'])) $programacion_cards .= "<li>✔️ Comida</li>";
                        if (isset($details['refrigerio_tipo1'])) $programacion_cards .= "<li>✔️ Refrigerio Tipo 1</li>";
                        if (isset($details['refrigerio_capacitacion'])) $programacion_cards .= "<li>✔️ Refrigerio Capacitación</li>";
                        if (!empty($details['transporte_tipo'])) $programacion_cards .= "<li>✔️ Transporte: {$details['transporte_tipo']}</li>";
                        $programacion_cards .= "</ul></div>";
                    }
                }
            }

            $html_body = file_get_contents('../templates/email/confirmacion_usuario.html');
            $html_body = str_replace(['{{fecha}}', '{{programacion_cards}}'], [$fecha, $programacion_cards], $html_body);
            send_brevo_email([['email' => $email]], 'Confirmación de Programación', $html_body, $pdo);
        }

        $response = ['success' => true, 'message' => '¡Programación enviada con éxito!'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en submit_form: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
    }
}

if ($action === 'download_pdf' && isset($_GET['date'])) {
    require_once '../includes/lib/fpdf/fpdf.php';

    class PDF extends FPDF
    {
        function Header()
        {
            global $date;
            $this->SetFont('Arial','B',15);
            $this->SetFillColor(23, 32, 42);
            $this->SetTextColor(255,255,255);
            $this->Cell(0,15,utf8_decode('Programación para el día: ' . $date),0,1,'C',true);
            $this->Ln(5);
        }

        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
        }

        function TableTitle($title)
        {
            $this->SetFont('Arial','B',13);
            $this->SetFillColor(200, 220, 255);
            $this->Cell(0,10,utf8_decode($title),0,1,'C',true);
            $this->Ln(4);
        }
        
        function SedeTable($header, $data, $sede)
        {
            $this->TableTitle("Sede: " . $sede);
            $this->SetFillColor(23, 32, 42);
            $this->SetTextColor(255);
            $this->SetDrawColor(128);
            $this->SetLineWidth(.3);
            $this->SetFont('','B', 8);

            $w = array(40, 40, 30, 30, 10, 10, 10, 10, 10, 40, 40);
            for($i=0;$i<count($header);$i++)
                $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
            $this->Ln();

            $this->SetTextColor(0);
            $this->SetFont('','', 8);

            $last_zona = null;
            $last_transporte = null;

            foreach($data as $row)
            {
                if ($row['zona'] !== $last_zona) {
                    $this->SetFont('','B', 9);
                    $this->SetFillColor(211, 211, 211);
                    $this->Cell(array_sum($w), 8, "Zona: " . utf8_decode($row['zona']), 1, 1, 'C', true);
                    $last_zona = $row['zona'];
                    $last_transporte = null; // Reset transporte
                }

                if ($row['transporte_tipo'] !== $last_transporte) {
                    $this->SetFont('','BI', 8);
                    $this->SetFillColor(230, 230, 230);
                    $this->Cell(array_sum($w), 7, "Transporte: " . utf8_decode($row['transporte_tipo']), 1, 1, 'L', true);
                    $last_transporte = $row['transporte_tipo'];
                }

                $this->SetFont('','', 8);
                $this->SetFillColor(255, 255, 255);

                $displayName = $row['nombre_completo'] ?: $row['nombre_manual'];
                $areaWbe = $row['id_persona'] ? $row['nombre_area'] : $row['area_wbe'];

                $data_row = array(
                    utf8_decode($displayName),
                    utf8_decode($areaWbe),
                    utf8_decode($row['transporte_tipo']),
                    utf8_decode($row['zona']),
                    $row['desayuno'] ? 'X' : '',
                    $row['almuerzo'] ? 'X' : '',
                    $row['comida'] ? 'X' : '',
                    $row['refrigerio_tipo1'] ? 'X' : '',
                    $row['refrigerio_capacitacion'] ? 'X' : '',
                    utf8_decode($row['actividad']),
                    utf8_decode($row['email_solicitante'])
                );

                $nb=0;
                for($i=0;$i<count($data_row);$i++)
                    $nb = max($nb, $this->NbLines($w[$i], $data_row[$i]));
                $h = 6 * $nb;
                $this->CheckPageBreak($h);

                for($i=0;$i<count($data_row);$i++)
                {
                    $x = $this->GetX();
                    $y = $this->GetY();
                    $this->Rect($x, $y, $w[$i], $h, 'D');
                    $this->MultiCell($w[$i], 6, $data_row[$i], 0, 'C');
                    $this->SetXY($x + $w[$i], $y);
                }
                $this->Ln($h);
            }
            $this->Cell(array_sum($w),0,'','T');
            $this->Ln(10);
        }

        function CheckPageBreak($h)
        {
            if($this->GetY()+$h>$this->PageBreakTrigger)
                $this->AddPage($this->CurOrientation);
        }

        function NbLines($w, $txt)
        {
            $cw = &$this->CurrentFont['cw'];
            if($w==0)
                $w = $this->w-$this->rMargin-$this->x;
            $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            $s = str_replace("\r",'',$txt);
            $nb = strlen($s);
            if($nb>0 && $s[$nb-1]=="\n")
                $nb--;
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while($i<$nb)
            {
                $c = $s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep = $i;
                $l += $cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i = $sep+1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }

    try {
        $date = $_GET['date'];
        $stmt = $pdo->prepare( "SELECT dp.*, p.nombre_completo, p.zona, s.nombre_sede, pr.email_solicitante, a.nombre_area FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id LEFT JOIN areas a ON p.id_area = a.id JOIN sedes s ON dp.id_sede = s.id JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion = ? ORDER BY s.nombre_sede, p.zona, dp.transporte_tipo, p.nombre_completo, dp.nombre_manual" );
        $stmt->execute([$date]);
        $programacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $programacion_por_sede = [];
        foreach ($programacion as $registro) {
            $programacion_por_sede[$registro['nombre_sede']][] = $registro;
        }
        
        $pdf = new PDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial','',10);

        $header = array('Nombre', 'Area | WBE', 'Transporte', 'Zona', 'D', 'A', 'C', 'R1', 'RC', 'Actividad', 'Solicitante');

        if (isset($programacion_por_sede['Betania'])) {
            $pdf->SedeTable($header, $programacion_por_sede['Betania'], 'Betania');
        }
        
        if (isset($programacion_por_sede['Quimbo'])) {
            $pdf->SedeTable($header, $programacion_por_sede['Quimbo'], 'Quimbo');
        }

        $pdf->Output('D', "programacion_$date.pdf");
        exit;

    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Error de base de datos: " . $e->getMessage();
        exit;
    }
}


//======================================================================
// ACCIONES DEL PANEL DE ADMINISTRACIÓN (SOLO ADMIN)
//======================================================================
if (isset($_SESSION['user_rol'])) {

    if ($action === 'get_casino_dashboard' && isset($_GET['date'])) {
        try {
            $date = $_GET['date'];
            $sede_id = $_SESSION['user_sede'] ?? 0;
            $stmt = $pdo->prepare(
                "SELECT 
                    s.nombre_sede,
                    SUM(dp.desayuno) as total_desayunos, SUM(dp.almuerzo) as total_almuerzos,
                    SUM(dp.comida) as total_comidas, SUM(dp.refrigerio_tipo1) as total_ref1,
                    SUM(dp.refrigerio_capacitacion) as total_ref_cap
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 JOIN sedes s ON dp.id_sede = s.id
                 WHERE pr.fecha_programacion = ? AND dp.id_sede = ?
                 GROUP BY dp.id_sede"
            );
            $stmt->execute([$date, $sede_id]);
            $reporte = $stmt->fetch(PDO::FETCH_ASSOC);

            $personas_stmt = $pdo->prepare(
                "SELECT 
                    COALESCE(p.nombre_completo, dp.nombre_manual) as nombre_persona, 
                    COALESCE(a.nombre_area, dp.area_wbe, 'N/A') as area
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 LEFT JOIN personas p ON dp.id_persona = p.id
                 LEFT JOIN areas a ON p.id_area = a.id
                 WHERE pr.fecha_programacion = ? AND dp.id_sede = ? 
                 AND (dp.desayuno=1 OR dp.almuerzo=1 OR dp.comida=1 OR dp.refrigerio_tipo1=1 OR dp.refrigerio_capacitacion=1)
                 ORDER BY nombre_persona"
            );
            $personas_stmt->execute([$date, $sede_id]);
            $personas = $personas_stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'reporte' => $reporte, 'personas' => $personas];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()];
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'download_casino_pdf' && isset($_GET['date'])) {
        require_once '../includes/lib/fpdf/fpdf.php';
        
        try {
            $date = $_GET['date'];
            $sede_id = $_SESSION['user_sede'] ?? 0;
            $sede_id = $_SESSION['user_sede'] ?? 0;

            // Obtener el nombre de la sede
            $sede_stmt = $pdo->prepare("SELECT nombre_sede FROM sedes WHERE id = ?");
            $sede_stmt->execute([$sede_id]);
            $nombre_sede = $sede_stmt->fetchColumn();

            // Obtener el resumen de comidas
            $resumen_stmt = $pdo->prepare(
                "SELECT 
                    SUM(dp.desayuno) as total_desayunos, SUM(dp.almuerzo) as total_almuerzos,
                    SUM(dp.comida) as total_comidas, SUM(dp.refrigerio_tipo1) as total_ref1,
                    SUM(dp.refrigerio_capacitacion) as total_ref_cap
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 WHERE pr.fecha_programacion = ? AND dp.id_sede = ?"
            );
            $resumen_stmt->execute([$date, $sede_id]);
            $resumen = $resumen_stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener el listado de personas
            $personas_stmt = $pdo->prepare(
                "SELECT 
                    COALESCE(p.nombre_completo, dp.nombre_manual) as nombre_persona, 
                    COALESCE(a.nombre_area, dp.area_wbe, 'N/A') as area,
                    dp.desayuno, dp.almuerzo, dp.comida, 
                    dp.refrigerio_tipo1, dp.refrigerio_capacitacion
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 LEFT JOIN personas p ON dp.id_persona = p.id
                 LEFT JOIN areas a ON p.id_area = a.id
                 WHERE pr.fecha_programacion = ? AND dp.id_sede = ?
                 AND (dp.desayuno=1 OR dp.almuerzo=1 OR dp.comida=1 OR dp.refrigerio_tipo1=1 OR dp.refrigerio_capacitacion=1)
                 ORDER BY nombre_persona"
            );
            $personas_stmt->execute([$date, $sede_id]);
            $personas = $personas_stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',16);
            $pdf->Cell(0,10, utf8_decode("Reporte de Casino - Sede: $nombre_sede"), 0, 1, 'C');
            $pdf->SetFont('Arial','',12);
            $pdf->Cell(0,10, "Fecha: " . $date, 0, 1, 'C');
            $pdf->Ln(10);

            // Tabla de Resumen
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(95,10,'Tipo de Comida',1,0,'C');
            $pdf->Cell(95,10,'Cantidad',1,0,'C');
            $pdf->Ln();
            $pdf->SetFont('Arial','',10);
            $data = [
                'Desayunos' => $resumen['total_desayunos'],
                'Almuerzos' => $resumen['total_almuerzos'],
                'Comidas' => $resumen['total_comidas'],
                'Refrigerio Tipo 1' => $resumen['total_ref1'],
                'Refrigerio Capacitacion' => $resumen['total_ref_cap']
            ];
            foreach($data as $label => $value){
                $pdf->Cell(95,8,utf8_decode($label),1);
                $pdf->Cell(95,8,$value,1,0,'C');
                $pdf->Ln();
            }
            $pdf->Ln(10);

            // Tabla de Personal
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,10,'Listado Detallado de Personal',0,1,'C');
            $pdf->Ln(5);

            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(70, 7, 'Nombre', 1, 0, 'C');
            $pdf->Cell(50, 7, utf8_decode('Área'), 1, 0, 'C');
            $pdf->Cell(12, 7, 'D', 1, 0, 'C');
            $pdf->Cell(12, 7, 'A', 1, 0, 'C');
            $pdf->Cell(12, 7, 'C', 1, 0, 'C');
            $pdf->Cell(12, 7, 'R1', 1, 0, 'C');
            $pdf->Cell(12, 7, 'RC', 1, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Arial','',9);
            foreach($personas as $persona){
                $pdf->Cell(70, 6, utf8_decode($persona['nombre_persona']), 1);
                $pdf->Cell(50, 6, utf8_decode($persona['area']), 1);
                $pdf->Cell(12, 6, $persona['desayuno'] ? 'X' : '', 1, 0, 'C');
                $pdf->Cell(12, 6, $persona['almuerzo'] ? 'X' : '', 1, 0, 'C');
                $pdf->Cell(12, 6, $persona['comida'] ? 'X' : '', 1, 0, 'C');
                $pdf->Cell(12, 6, $persona['refrigerio_tipo1'] ? 'X' : '', 1, 0, 'C');
                $pdf->Cell(12, 6, $persona['refrigerio_capacitacion'] ? 'X' : '', 1, 0, 'C');
                $pdf->Ln();
            }
            
            $pdf->Output('D', "reporte_casino_{$nombre_sede}_{$date}.pdf");
            exit;

        } catch (PDOException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Error de base de datos: " . $e->getMessage();
            exit;
        }
    }

    if ($action === 'get_transporter_dashboard' && isset($_GET['date'])) {
        try {
            $date = $_GET['date'];
            $stmt = $pdo->prepare(
                "SELECT p.nombre_completo, s.nombre_sede, dp.transporte_tipo, p.zona
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 JOIN personas p ON dp.id_persona = p.id
                 JOIN sedes s ON dp.id_sede = s.id
                 WHERE pr.fecha_programacion = ? AND dp.transporte_tipo != 'No requiere'
                 ORDER BY s.nombre_sede, p.nombre_completo"
            );
            $stmt->execute([$date]);
            $reporte = $stmt->fetchAll();
            $response = ['success' => true, 'reporte' => $reporte];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()];
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'download_transporter_pdf' && isset($_GET['date'])) {
        require_once '../includes/lib/fpdf/fpdf.php';

        class PDF_Transporter extends FPDF
        {
            var $date;

            function Header()
            {
                $this->SetFont('Arial','B',15);
                $this->SetFillColor(23, 32, 42);
                $this->SetTextColor(255,255,255);
                $this->Cell(0,15,utf8_decode('Rutas de Transporte para el día: ' . $this->date),0,1,'C',true);
                $this->Ln(5);
            }

            function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
            }

            function TableTitle($title)
            {
                $this->SetFont('Arial','B',13);
                $this->SetFillColor(200, 220, 255);
                $this->Cell(0,10,utf8_decode($title),0,1,'C',true);
                $this->Ln(4);
            }
            
            function SedeTable($header, $data, $sede)
            {
                $this->TableTitle("Sede: " . $sede);
                $this->SetFillColor(23, 32, 42);
                $this->SetTextColor(255);
                $this->SetDrawColor(128);
                $this->SetLineWidth(.3);
                $this->SetFont('','B', 10);

                $w = array(70, 50, 50); // Anchos de columna para Nombre, Tipo de Ruta, Zona
                for($i=0;$i<count($header);$i++)
                    $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
                $this->Ln();

                $this->SetTextColor(0);
                $this->SetFont('','', 9);

                $last_zona = null;
                $last_transporte = null;

                foreach($data as $row)
                {
                    if ($row['zona'] !== $last_zona) {
                        $this->SetFont('','B', 10);
                        $this->SetFillColor(211, 211, 211);
                        $this->Cell(array_sum($w), 8, "Zona: " . utf8_decode($row['zona']), 1, 1, 'C', true);
                        $last_zona = $row['zona'];
                        $last_transporte = null;
                    }

                    if ($row['transporte_tipo'] !== $last_transporte) {
                        $this->SetFont('','BI', 9);
                        $this->SetFillColor(230, 230, 230);
                        $this->Cell(array_sum($w), 7, "Transporte: " . utf8_decode($row['transporte_tipo']), 1, 1, 'L', true);
                        $last_transporte = $row['transporte_tipo'];
                    }

                    $this->SetFont('','', 9);
                    $this->SetFillColor(255, 255, 255);
                    
                    $this->Cell($w[0], 6, utf8_decode($row['nombre_completo']), 1);
                    $this->Cell($w[1], 6, utf8_decode($row['transporte_tipo']), 1);
                    $this->Cell($w[2], 6, utf8_decode($row['zona']), 1);
                    $this->Ln();
                }
                $this->Cell(array_sum($w),0,'','T');
                $this->Ln(10);
            }
        }
        
        try {
            $date = $_GET['date'];
            $stmt = $pdo->prepare(
                "SELECT p.nombre_completo, s.nombre_sede, dp.transporte_tipo, p.zona
                 FROM detalle_programacion dp
                 JOIN programaciones pr ON dp.id_programacion = pr.id
                 JOIN personas p ON dp.id_persona = p.id
                 JOIN sedes s ON dp.id_sede = s.id
                 WHERE pr.fecha_programacion = ? AND dp.transporte_tipo != 'No requiere'
                 ORDER BY s.nombre_sede, p.zona, dp.transporte_tipo, p.nombre_completo"
            );
            $stmt->execute([$date]);
            $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $reporte_por_sede = [];
            foreach ($reporte as $registro) {
                $reporte_por_sede[$registro['nombre_sede']][] = $registro;
            }

            $pdf = new PDF_Transporter('P', 'mm', 'A4');
            $pdf->date = $date;
            $pdf->AliasNbPages();
            $pdf->AddPage();
            
            $header = array('Nombre', 'Tipo de Ruta', 'Zona');

            if (isset($reporte_por_sede['Betania'])) {
                $pdf->SedeTable($header, $reporte_por_sede['Betania'], 'Betania');
            }
            
            if (isset($reporte_por_sede['Quimbo'])) {
                $pdf->SedeTable($header, $reporte_por_sede['Quimbo'], 'Quimbo');
            }

            $pdf->Output('D', "rutas_transporte_$date.pdf");
            exit;

        } catch (PDOException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Error de base de datos: " . $e->getMessage();
            exit;
        }
    }
}


if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1) {

    if ($action === 'set_selected_date' && isset($_POST['date'])) {
        $_SESSION['selected_date'] = $_POST['date'];
        $response = ['success' => true];
        echo json_encode($response);
        exit;
    }

    if ($action === 'download_analytics_pdf' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once '../includes/lib/fpdf/fpdf.php';

        $charts = json_decode($_POST['charts']);
        $date_range = $_POST['date_range'];

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,utf8_decode('Reporte de Analíticas'),0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10,$date_range,0,1,'C');
        $pdf->Ln(10);

        $tmpDir = 'tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        foreach ($charts as $chart) {
            $img = str_replace('data:image/png;base64,', '', $chart->image);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            $file = $tmpDir . uniqid() . '.png';
            file_put_contents($file, $data);
            
            $pdf->SetFont('Arial','B',14);
            $pdf->Cell(0,10,utf8_decode($chart->title),0,1,'C');
            if ($chart->title === 'Total de Personas') {
                $pdf->Image($file, 10, null, 277);
            } else {
                $pdf->Image($file, 75, null, 120);
            }
            $pdf->Ln(5);
            unlink($file);

            if (next($charts)) {
                $pdf->AddPage();
            }
        }

        $pdf->Output('D', 'reporte_analiticas.pdf');
        exit;
    }
    
    if ($action === 'add_manual_entry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = filter_input(INPUT_POST, 'email_solicitante', FILTER_VALIDATE_EMAIL);
        $fecha = $_POST['fecha_programacion'];
        $area_id = $_POST['area'];

        if (!$fecha || !$area_id) { $response['message'] = 'Faltan datos generales.'; }
        else {
            try {
                $pdo->beginTransaction();
                $stmt_prog = $pdo->prepare("INSERT INTO programaciones (fecha_programacion, email_solicitante, creado_por_admin) VALUES (?, ?, ?)");
                $stmt_prog->execute([$fecha, $email, $_SESSION['user_id']]);
                $prog_id = $pdo->lastInsertId();

                $area_stmt = $pdo->prepare("SELECT nombre_area FROM areas WHERE id = ?"); $area_stmt->execute([$area_id]); $area_nombre = $area_stmt->fetchColumn();
                if (trim(mb_strtolower($area_nombre, 'UTF-8')) == 'otras áreas') {
                    $details = $_POST['other'];
                    if (empty($details['nombre_manual']) || empty($details['area_wbe']) || empty($details['actividad'])) {
                        throw new Exception("Faltan datos en el formulario de Otras Áreas.");
                    }
                    $stmt_detail = $pdo->prepare("INSERT INTO detalle_programacion (id_programacion, id_persona, id_sede, desayuno, almuerzo, comida, refrigerio_tipo1, refrigerio_capacitacion, transporte_tipo, nombre_manual, area_wbe, actividad) VALUES (:prog_id, NULL, :sede_id, :desayuno, :almuerzo, :comida, :ref1, :ref_cap, :transporte, :nombre, :area, :actividad)");
                    $stmt_detail->execute([':prog_id' => $prog_id, ':sede_id' => $details['id_sede'] ?? null, ':desayuno' => isset($details['desayuno']) ? 1 : 0, ':almuerzo' => isset($details['almuerzo']) ? 1 : 0, ':comida' => isset($details['comida']) ? 1 : 0, ':ref1' => isset($details['refrigerio_tipo1']) ? 1 : 0, ':ref_cap' => isset($details['refrigerio_capacitacion']) ? 1 : 0, ':transporte' => $details['transporte_tipo'] ?? 'No requiere', ':nombre' => $details['nombre_manual'], ':area' => $details['area_wbe'], ':actividad' => $details['actividad']]);
                } else {
                    $people_data = $_POST['people'] ?? [];
                    if (empty($people_data)) { throw new Exception("No se seleccionó ninguna persona para programar."); }
                    $stmt_detail = $pdo->prepare("INSERT INTO detalle_programacion (id_programacion, id_persona, id_sede, desayuno, almuerzo, comida, refrigerio_tipo1, refrigerio_capacitacion, transporte_tipo) VALUES (:prog_id, :persona_id, :sede_id, :desayuno, :almuerzo, :comida, :ref1, :ref_cap, :transporte)");
                    foreach ($people_data as $person_id => $details) {
                        $has_food = !empty($details['desayuno']) || !empty($details['almuerzo']) || !empty($details['comida']) || !empty($details['refrigerio_tipo1']) || !empty($details['refrigerio_capacitacion']);
                        $has_transport = !empty($details['transporte_tipo']) && $details['transporte_tipo'] !== 'No requiere';

                        if ($has_food || $has_transport) {
                            if ($has_transport && empty($details['id_sede'])) {
                                $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
                                $person_name_stmt->execute([$person_id]);
                                $person_name = $person_name_stmt->fetchColumn();
                                throw new Exception("Se debe seleccionar una sede de destino para {$person_name} ya que se ha seleccionado un tipo de transporte.");
                            }

                            // Verificar si la persona ya tiene una programación para ese día
                            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM detalle_programacion dp JOIN programaciones p ON dp.id_programacion = p.id WHERE dp.id_persona = ? AND p.fecha_programacion = ?");
                            $check_stmt->execute([$person_id, $fecha]);
                            if ($check_stmt->fetchColumn() > 0) {
                                $person_name_stmt = $pdo->prepare("SELECT nombre_completo FROM personas WHERE id = ?");
                                $person_name_stmt->execute([$person_id]);
                                $person_name = $person_name_stmt->fetchColumn();
                                throw new Exception("La persona {$person_name} ya tiene una programación para el día {$fecha}.");
                            }
                            $stmt_detail->execute([':prog_id' => $prog_id, ':persona_id' => $person_id, ':sede_id' => $details['id_sede'], ':desayuno' => isset($details['desayuno']) ? 1 : 0, ':almuerzo' => isset($details['almuerzo']) ? 1 : 0, ':comida' => isset($details['comida']) ? 1 : 0, ':ref1' => isset($details['refrigerio_tipo1']) ? 1 : 0, ':ref_cap' => isset($details['refrigerio_capacitacion']) ? 1 : 0, ':transporte' => $details['transporte_tipo'] ?? 'No requiere']);
                        }
                    }
                }
                $pdo->commit();
                $response = ['success' => true, 'message' => 'Registro manual añadido con éxito.'];
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error en add_manual_entry: " . $e->getMessage());
                $response = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
            }
        }
    }

    if ($action === 'get_admin_dashboard' && isset($_GET['date'])) {
        try {
            $date = $_GET['date'];
            $stmt = $pdo->prepare( "SELECT dp.*, p.nombre_completo, p.zona, s.nombre_sede, pr.email_solicitante, a.nombre_area FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id LEFT JOIN areas a ON p.id_area = a.id JOIN sedes s ON dp.id_sede = s.id JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion = ? ORDER BY p.nombre_completo, dp.nombre_manual" );
            $stmt->execute([$date]);
            $programacion = $stmt->fetchAll();

            $finalized_stmt = $pdo->prepare("SELECT COUNT(*) FROM programaciones WHERE fecha_programacion = ? AND estado = 'finalizada'");
            $finalized_stmt->execute([$date]);
            $is_finalized = $finalized_stmt->fetchColumn() > 0;

            $response = ['success' => true, 'programacion' => $programacion, 'is_finalized' => $is_finalized];
        } catch (PDOException $e) { $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]; }
    }

    if ($action === 'delete_row' && isset($_GET['id'])) {
        try {
            $detail_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM detalle_programacion WHERE id = ?");
            $stmt->execute([$detail_id]);
            $response = ['success' => true, 'message' => 'Registro eliminado.'];
        } catch (PDOException $e) { $response = ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]; }
    }

    if ($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = json_decode($_POST['ids']);
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM detalle_programacion WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $response = ['success' => true, 'message' => 'Registros eliminados con éxito.'];
            } catch (PDOException $e) {
                $response = ['success' => false, 'message' => 'Error al eliminar los registros: ' . $e->getMessage()];
            }
        } else {
            $response = ['success' => false, 'message' => 'No se seleccionaron registros para eliminar.'];
        }
    }
    
    if ($action === 'finalize_and_send' && isset($_POST['date'])) {
        try {
            $date = $_POST['date'];
            $pdo->beginTransaction();
            $chef_reports_stmt = $pdo->prepare("SELECT dp.id_sede, s.nombre_sede, SUM(dp.desayuno) as total_desayunos, SUM(dp.almuerzo) as total_almuerzos, SUM(dp.comida) as total_comidas, SUM(dp.refrigerio_tipo1) as total_ref1, SUM(dp.refrigerio_capacitacion) as total_ref_cap FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id JOIN sedes s ON dp.id_sede = s.id WHERE pr.fecha_programacion = ? GROUP BY dp.id_sede");
            $chef_reports_stmt->execute([$date]);
            $chef_reports = $chef_reports_stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            $transporter_report_stmt = $pdo->prepare("SELECT COALESCE(p.nombre_completo, dp.nombre_manual) as nombre_persona, s.nombre_sede, dp.transporte_tipo, p.zona FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id LEFT JOIN personas p ON dp.id_persona = p.id JOIN sedes s ON dp.id_sede = s.id WHERE pr.fecha_programacion = ? AND dp.transporte_tipo != 'No requiere' ORDER BY s.nombre_sede, nombre_persona");
            $transporter_report_stmt->execute([$date]);
            $transporter_report = $transporter_report_stmt->fetchAll();
            $chefs = $pdo->query("SELECT email, id_sede FROM usuarios WHERE id_rol = 2 AND activo = 1")->fetchAll();
            $transporters = $pdo->query("SELECT email FROM usuarios WHERE id_rol = 3 AND activo = 1")->fetchAll();
            foreach ($chefs as $chef) {
                if (isset($chef_reports[$chef['id_sede']])) {
                    $report_data = $chef_reports[$chef['id_sede']][0];
                    
                    $personas_stmt = $pdo->prepare("SELECT COALESCE(p.nombre_completo, dp.nombre_manual) as nombre FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion = ? AND dp.id_sede = ?");
                    $personas_stmt->execute([$date, $chef['id_sede']]);
                    $personas = $personas_stmt->fetchAll(PDO::FETCH_COLUMN);
                    $personas_list = '<ul><li>' . implode('</li><li>', $personas) . '</li></ul>';

                    $html_body = file_get_contents('../templates/email/reporte_casino.html');
                    $html_body = str_replace(
                        ['{{sede_nombre}}', '{{fecha}}', '{{total_desayunos}}', '{{total_almuerzos}}', '{{total_comidas}}', '{{personas}}'],
                        [$report_data['nombre_sede'], $date, $report_data['total_desayunos'], $report_data['total_almuerzos'], $report_data['total_comidas'], $personas_list],
                        $html_body
                    );
                    send_brevo_email([['email' => $chef['email']]], "Reporte de Alimentación {$date}", $html_body, $pdo);
                }
            }
            if (!empty($transporter_report) && !empty($transporters)) {
                $transport_rows = '';
                foreach($transporter_report as $row) {
                    $transport_rows .= "<tr><td>{$row['nombre_persona']}</td><td>{$row['transporte_tipo']}</td><td>{$row['nombre_sede']}</td><td>{$row['zona']}</td></tr>";
                }
                $html_body = file_get_contents('../templates/email/reporte_transporte.html');
                $html_body = str_replace(['{{fecha}}', '{{transport_rows}}'], [$date, $transport_rows], $html_body);
                $transporter_emails = array_map(fn($t) => ['email' => $t['email']], $transporters);
                send_brevo_email($transporter_emails, "Reporte de Transporte {$date}", $html_body, $pdo);
            }
            $admin_emails = $pdo->query("SELECT email FROM usuarios WHERE id_rol = 1 AND activo = 1")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($admin_emails)) {
                $programacion_stmt = $pdo->prepare( "SELECT dp.*, p.nombre_completo, p.zona, s.nombre_sede, pr.email_solicitante, a.nombre_area FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id LEFT JOIN areas a ON p.id_area = a.id JOIN sedes s ON dp.id_sede = s.id JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion = ? ORDER BY p.nombre_completo, dp.nombre_manual" );
                $programacion_stmt->execute([$date]);
                $programacion_completa = $programacion_stmt->fetchAll(PDO::FETCH_ASSOC);

                $programacion_rows = '';
                foreach($programacion_completa as $row) {
                    $displayName = $row['nombre_completo'] ?: $row['nombre_manual'];
                    $areaWbe = $row['id_persona'] ? $row['nombre_area'] : $row['area_wbe'];
                    $programacion_rows .= "<tr>
                        <td>{$displayName}</td>
                        <td>{$areaWbe}</td>
                        <td>{$row['nombre_sede']}</td>
                        <td>{$row['transporte_tipo']}</td>
                        <td>{$row['zona']}</td>
                        <td>".($row['desayuno'] ? 'X' : '')."</td>
                        <td>".($row['almuerzo'] ? 'X' : '')."</td>
                        <td>".($row['comida'] ? 'X' : '')."</td>
                        <td>".($row['refrigerio_tipo1'] ? 'X' : '')."</td>
                        <td>".($row['refrigerio_capacitacion'] ? 'X' : '')."</td>
                        <td>{$row['actividad']}</td>
                        <td>{$row['email_solicitante']}</td>
                    </tr>";
                }

                $html_body = file_get_contents('../templates/email/reporte_admin.html');
                $html_body = str_replace(['{{fecha}}', '{{programacion_rows}}'], [$date, $programacion_rows], $html_body);
                $admin_emails_brevo = array_map(fn($e) => ['email' => $e], $admin_emails);
                send_brevo_email($admin_emails_brevo, "Consolidado de Programación {$date}", $html_body, $pdo);
            }

            $update_stmt = $pdo->prepare("UPDATE programaciones SET estado = 'finalizada' WHERE fecha_programacion = ?");
            $update_stmt->execute([$date]);
            $pdo->commit();
            $response = ['success' => true, 'message' => '¡Proceso finalizado! Los reportes han sido enviados.'];
        } catch (Exception $e) { $pdo->rollBack(); $response = ['success' => false, 'message' => 'Error Crítico: ' . $e->getMessage()]; }
    }

    if ($action === 'get_analytics_data' && isset($_GET['start']) && isset($_GET['end'])) {
        try {
            $start = $_GET['start']; $end = $_GET['end'];
            $food_stmt = $pdo->prepare("SELECT SUM(desayuno), SUM(almuerzo), SUM(comida), SUM(refrigerio_tipo1), SUM(refrigerio_capacitacion) FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion BETWEEN ? AND ?");
            $food_stmt->execute([$start, $end]); $food_data = $food_stmt->fetch(PDO::FETCH_NUM);
            $transport_stmt = $pdo->prepare("SELECT transporte_tipo, COUNT(*) as total FROM detalle_programacion dp JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion BETWEEN ? AND ? GROUP BY transporte_tipo");
            $transport_stmt->execute([$start, $end]); $transport_data = $transport_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $sedes_stmt = $pdo->prepare("SELECT s.nombre_sede, COUNT(dp.id) as count FROM sedes s LEFT JOIN detalle_programacion dp ON s.id = dp.id_sede JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion BETWEEN ? AND ? GROUP BY s.nombre_sede");
            $sedes_stmt->execute([$start, $end]);
            $sedes_data = $sedes_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $areas_stmt = $pdo->prepare("SELECT a.nombre_area, COUNT(dp.id) as count FROM areas a LEFT JOIN personas p ON a.id = p.id_area LEFT JOIN detalle_programacion dp ON p.id = dp.id_persona JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion BETWEEN ? AND ? GROUP BY a.nombre_area");
            $areas_stmt->execute([$start, $end]);
            $areas_data = $areas_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $personas_stmt = $pdo->prepare("SELECT COALESCE(p.nombre_completo, dp.nombre_manual) as nombre, COUNT(*) as count FROM detalle_programacion dp LEFT JOIN personas p ON dp.id_persona = p.id JOIN programaciones pr ON dp.id_programacion = pr.id WHERE pr.fecha_programacion BETWEEN ? AND ? GROUP BY nombre ORDER BY count DESC LIMIT 10");
            $personas_stmt->execute([$start, $end]);
            $personas_data = $personas_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $response = [
                'success' => true, 
                'food_data' => $food_data, 
                'transport_data' => $transport_data,
                'sedes_data' => $sedes_data,
                'areas_data' => $areas_data,
                'personas_data' => $personas_data
            ];
        } catch (PDOException $e) { $response = ['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]; }
    }
}


echo json_encode($response);
?>