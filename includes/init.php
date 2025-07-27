<?php
// Iniciar sesión en todas las páginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos esenciales
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>