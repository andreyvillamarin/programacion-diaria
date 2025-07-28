<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Acción no válida.'];

try {
    if ($action === 'get_initial_data') {
        $stmt = $pdo->query("SELECT id, nombre_area FROM areas WHERE activa = 1 ORDER BY nombre_area");
        $response['areas'] = $stmt->fetchAll();
        $response['success'] = true;
    }

    if ($action === 'get_people_by_area') {
        $area_id = filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT);
        if ($area_id) {
            $stmt = $pdo->prepare("SELECT id, nombre_completo FROM personas WHERE id_area = ? AND activo = 1 ORDER BY nombre_completo");
            $stmt->execute([$area_id]);
            $response['people'] = $stmt->fetchAll();

            $stmt_sedes = $pdo->query("SELECT id, nombre_sede FROM sedes ORDER BY nombre_sede");
            $response['sedes'] = $stmt_sedes->fetchAll();
            
            $result = $pdo->query("SHOW COLUMNS FROM `detalle_programacion` LIKE 'transporte_tipo'");
            preg_match("/^enum\(\'(.*)\'\)$/", $result->fetch()['Type'], $matches);
            $response['transport_options'] = explode("','", $matches[1]);
            
            $response['success'] = true;
        } else {
            $response['message'] = 'ID de área no válido.';
        }
    }
    
    if ($action === 'get_services_only') {
        $stmt_sedes = $pdo->query("SELECT id, nombre_sede FROM sedes ORDER BY nombre_sede");
        $response['sedes'] = $stmt_sedes->fetchAll();
        
        $result = $pdo->query("SHOW COLUMNS FROM `detalle_programacion` LIKE 'transporte_tipo'");
        preg_match("/^enum\(\'(.*)\'\)$/", $result->fetch()['Type'], $matches);
        $response['transport_options'] = explode("','", $matches[1]);
        
        $response['success'] = true;
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $response['message'] = 'Error de base de datos.';
}

echo json_encode($response);
?>