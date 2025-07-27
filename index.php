<?php require_once 'includes/init.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación de Servicios - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php $recaptcha_site_key = get_setting('recaptcha_v3_site_key', $pdo); ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptcha_site_key) ?>"></script>
</head>
<body>
<div class="main-container">
    <header class="main-header">
        <h1>Programación de Alimentación y Transporte</h1>
        <p>Complete el formulario para solicitar los servicios del próximo día hábil.</p>
    </header>

    <?php if (is_form_open($pdo)): ?>
        <form id="programming-form">
            <div id="form-messages"></div>

            <fieldset>
                <legend>1. Datos del Solicitante</legend>
                <div class="form-group">
                    <label for="area-select">Seleccione su Área:</label>
                    <select id="area-select" name="area" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="requester-email">Su Correo para recibir copia:</label>
                    <input type="email" id="requester-email" name="email_solicitante" required placeholder="su.correo@ejemplo.com">
                </div>
                 <div class="form-group">
                    <label for="programming-date">Fecha para la cual programa:</label>
                    <input type="date" id="programming-date" name="fecha_programacion" readonly>
                </div>
            </fieldset>

            <fieldset id="people-fieldset" class="hidden">
                <legend>2. Programación del Personal</legend>
                <div id="people-container">
                    </div>
            </fieldset>

            <input type="hidden" id="recaptcha-response" name="recaptcha_response">
            <button type="submit" class="btn-submit" id="submit-btn">
                <i class="fas fa-paper-plane"></i> Enviar Programación
            </button>
        </form>
    <?php else: ?>
        <div class="form-closed-message">
            <h3>Formulario Cerrado</h3>
            <p><?= htmlspecialchars(get_setting('mensaje_formulario_cerrado', $pdo)) ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Pasamos datos de PHP a JS de forma segura
    const App = {
        recaptchaSiteKey: '<?= htmlspecialchars($recaptcha_site_key) ?>',
        apiUrl: '<?= APP_URL . "/api/handler.php" ?>',
        dataUrl: '<?= APP_URL . "/api/data.php" ?>'
    };
</script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> <script src="assets/js/main.js"></script>
</body>
</html>