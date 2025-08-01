<?php
// -- BASE DE DATOS -- //
define('DB_HOST', 'localhost');
define('DB_NAME', 'qdosnetw_enel');
define('DB_USER', 'qdosnetw_webmaster');
define('DB_PASS', 'tRVy8pvXVAz8');
define('DB_CHARSET', 'utf8mb4');

// -- APLICACIÓN -- //
define('APP_URL', 'https://qdos.network/demos/enel');
define('APP_NAME', 'Programación Enel');
date_default_timezone_set('America/Bogota');

// -- CONEXIÓN PDO -- //
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    error_log("Error de conexión a la BD: " . $e->getMessage());
    die('Error crítico de conexión. Por favor, contacte al administrador.');
}
?>