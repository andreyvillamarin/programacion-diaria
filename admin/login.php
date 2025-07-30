<?php
require_once '../includes/init.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, ingrese correo y contraseña.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre, password, id_rol, id_sede FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Previene la fijación de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['id_rol'];
            $_SESSION['user_sede'] = $user['id_sede']; // Importante para el Casino
            header('Location: index.php');
            exit;
        } else {
            $error_message = 'Credenciales incorrectas o usuario inactivo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Administrativo - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <style>
        @media (max-width: 768px) {
            .login-container {
                width: 95% !important;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Acceso Administrativo</h2>
        <p><?= APP_NAME ?></p>
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn-submit">Ingresar</button>
        </form>
    </div>
</body>
</html>