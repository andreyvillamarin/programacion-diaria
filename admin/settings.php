<?php
require_once '../includes/init.php';
// Solo rol Administrador (ID 1)
if ($_SESSION['user_rol'] != 1) { die('Acceso Denegado'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recorrer todos los campos enviados y guardarlos
    foreach ($_POST as $key => $value) {
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $stmt->execute([$value, $key]);
    }
    $message = "Configuración guardada con éxito.";
}

$page_title = "Configuración del Sistema";
$settings = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
include '../templates/header.php';
?>

<div class="container-fluid">
    <?php if (isset($message)): ?>
        <div class="success-message"><?= $message ?></div>
    <?php endif; ?>

    <form action="settings.php" method="POST">
        <div class="card">
            <div class="card-header"><h4><i class="fas fa-clock"></i> Horario del Formulario Público</h4></div>
            <div class="card-body">
                <div class="form-group"><label>Hora de Apertura:</label><input type="time" name="horario_apertura" value="<?= htmlspecialchars($settings['horario_apertura']) ?>"></div>
                <div class="form-group"><label>Hora de Cierre:</label><input type="time" name="horario_cierre" value="<?= htmlspecialchars($settings['horario_cierre']) ?>"></div>
                <div class="form-group"><label>Mensaje (cuando está cerrado):</label><textarea name="mensaje_formulario_cerrado"><?= htmlspecialchars($settings['mensaje_formulario_cerrado']) ?></textarea></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h4><i class="fas fa-key"></i> Claves de APIs</h4></div>
            <div class="card-body">
                <div class="form-group"><label>Brevo API Key:</label><input type="text" name="brevo_api_key" value="<?= htmlspecialchars($settings['brevo_api_key']) ?>"></div>
                <div class="form-group"><label>Google reCAPTCHA Site Key:</label><input type="text" name="recaptcha_v3_site_key" value="<?= htmlspecialchars($settings['recaptcha_v3_site_key']) ?>"></div>
                <div class="form-group"><label>Google reCAPTCHA Secret Key:</label><input type="text" name="recaptcha_v3_secret_key" value="<?= htmlspecialchars($settings['recaptcha_v3_secret_key']) ?>"></div>
                <div class="form-group"><label>URL de Web App (Google Sheets):</label><input type="text" name="google_sheets_webhook_url" value="<?= htmlspecialchars($settings['google_sheets_webhook_url'] ?? '') ?>"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Guardar toda la Configuración</button>
    </form>
</div>

<?php include '../templates/footer.php'; ?>