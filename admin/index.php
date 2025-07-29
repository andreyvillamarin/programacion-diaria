<?php
require_once '../includes/init.php';

// Asegurarse de que el usuario est芍 logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener el nombre del rol para mostrarlo en el header
$stmt = $pdo->prepare("SELECT nombre_rol FROM roles WHERE id = ?");
$stmt->execute([$_SESSION['user_rol']]);
$_SESSION['user_rol_name'] = $stmt->fetchColumn();


$role_id = $_SESSION['user_rol'];

// Dependiendo del rol, se carga una vista (template) diferente
switch ($role_id) {
    case 1: // Administrador
        $page_title = "Dashboard de Administración";
        include '../templates/dashboard_admin.php';
        break;
    case 2: // Casino
        $page_title = "Dashboard de Casino";
        include '../templates/dashboard_casino.php';
        break;
    case 3: // Transportador
        $page_title = "Dashboard de Transporte";
        include '../templates/dashboard_transporter.php';
        break;
    default:
        // Si el rol no es v芍lido, se cierra la sesi車n
        session_destroy();
        die('Rol no configurado. Por favor, contacte al administrador.');
}