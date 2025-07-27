<?php
// Redirige si no hay sesión activa (excepto en login)
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: ' . APP_URL . '/admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> </head>
<body>
    <div class="admin-wrapper">
        <nav class="admin-nav">
            <div class="nav-header">
                <h3><?= APP_NAME ?></h3>
                <small>Rol: <?= $_SESSION['user_rol_name'] ?? '' ?></small>
            </div>
            <ul>
                <li><a href="<?= APP_URL ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <?php if ($_SESSION['user_rol'] == 1): // Menú solo para Administradores ?>
                    <li class="nav-separator">Gestión</li>
                    <li><a href="<?= APP_URL ?>/admin/areas.php"><i class="fas fa-building"></i> Áreas</a></li>
                    <li><a href="<?= APP_URL ?>/admin/personas.php"><i class="fas fa-users"></i> Personas</a></li>
                    <li><a href="<?= APP_URL ?>/admin/usuarios.php"><i class="fas fa-user-shield"></i> Usuarios</a></li>
                    <li class="nav-separator">Sistema</li>
                    <li><a href="<?= APP_URL ?>/admin/analytics.php"><i class="fas fa-chart-bar"></i> Analíticas</a></li>
                    <li><a href="<?= APP_URL ?>/admin/settings.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-footer">
                <a href="<?= APP_URL ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </nav>
        <main class="admin-main">
            <header class="main-content-header">
                <h1><?= $page_title ?? 'Dashboard' ?></h1>
                <span>Bienvenido, <?= htmlspecialchars($_SESSION['user_nombre'] ?? '') ?></span>
            </header>
            <div class="main-content">