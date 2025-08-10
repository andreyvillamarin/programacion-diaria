<?php require_once 'includes/init.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación de Servicios - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <?php $recaptcha_site_key = get_setting('recaptcha_v3_site_key', $pdo); ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptcha_site_key) ?>"></script>
</head>
<body class="public-body">

<div class="public-container">
    <div class="info-pane">
        <div class="info-header">
            <h2><?= APP_NAME ?></h2>
            <p>Plataforma para la programación diaria de servicios de alimentación y transporte centrales Betania | Quimbo.</p>
        </div>
        <div class="info-steps">
            <h4>Sigue estos pasos:</h4>
            <ol>
                <li><span>1</span> Elige "Tu Área". Si no la encuentras, selecciona "Otras Áreas".</li>
                <li><span>2</span> "Opcional" Si quieres recibir copia de la programación, escribe tu correo .</li>
                <li><span>3</span> Selecciona la fecha que quieres programar.</li>
                <li><span>4</span> Selecciona los servicios que requieres y la central.</li>
                <li><span>5</span> Haz click en "Enviar Programación". ¡Listo!</li>
            </ol>
        </div>
        <div class="info-footer">
            <p>Cualquier cambio o modificación favor informar en el chat de WhatsApp.</p>
        </div>
    </div>

    <div class="form-pane">
        <?php if (is_form_open($pdo)): ?>
            <form id="programming-form" autocomplete="off">
                <div class="form-header">
                    <h3>Programar Servicios</h3>
                    <p>Los campos marcados con * son obligatorios.</p>
                </div>

                <fieldset>
                    <legend>Datos del Solicitante</legend>
                    <div class="form-group">
                        <label for="area-select">Tu Área *</label>
                        <select id="area-select" name="area" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="requester-email">Tu Correo Electrónico</label>
                        <input type="email" id="requester-email" name="email_solicitante" placeholder="tu.correo@ejemplo.com">
                    </div>
                     <div class="form-group">
                        <label for="programming-date">Fecha de Programación</label>
                        <input type="date" id="programming-date" name="fecha_programacion">
                    </div>
                </fieldset>

                <fieldset id="people-fieldset" class="hidden">
                    <legend>Personal a Programar</legend>
                    <div id="people-container"></div>
                </fieldset>

                <fieldset id="other-area-fieldset" class="hidden">
                    <legend>Datos de la Persona y Servicios</legend>
                    <div id="other-area-services"></div>
                </fieldset>

                <input type="hidden" id="recaptcha-response" name="recaptcha_response">
                <button type="submit" class="btn-submit" id="submit-btn">
                    <i class="fas fa-paper-plane"></i> Enviar Programación
                </button>
                <div id="form-messages" class="form-messages-container"></div>
            </form>
        <?php else: ?>
            <div class="form-closed-message">
                <div class="alert-icon">&#9888;</div>
                <h3>Formulario Cerrado</h3>
                <p><?= htmlspecialchars(get_setting('mensaje_formulario_cerrado', $pdo)) ?></p>
                <p>Horario de atención: de <?= htmlspecialchars(get_setting('horario_apertura', $pdo)) ?> a <?= htmlspecialchars(get_setting('horario_cierre', $pdo)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="assets/js/main.js"></script>
</body>
</html>