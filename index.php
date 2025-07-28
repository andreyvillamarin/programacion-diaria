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
            <p>Plataforma para la programación diaria de servicios de alimentación y transporte.</p>
        </div>
        <div class="info-steps">
            <h4>Sigue estos pasos:</h4>
            <ol>
                <li><span>1</span> Elige tu área. Si no la encuentras, selecciona "Otras Áreas".</li>
                <li><span>2</span> Completa la programación según el formulario que aparezca.</li>
                <li><span>3</span> Ingresa tu correo y envía. ¡Listo!</li>
            </ol>
        </div>
        <div class="info-footer">
            <p>Si tienes problemas, contacta al administrador del sistema.</p>
        </div>
    </div>

    <div class="form-pane">
        <?php if (is_form_open($pdo)): ?>
            <form id="programming-form" autocomplete="off">
                <div class="form-header">
                    <h3>Programar Servicios</h3>
                    <p>Los campos marcados con * son obligatorios.</p>
                </div>

                <div id="form-messages"></div>

                <fieldset>
                    <legend>Datos del Solicitante</legend>
                    <div class="form-group">
                        <label for="area-select">Tu Área *</label>
                        <select id="area-select" name="area" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="requester-email">Tu Correo Electrónico *</label>
                        <input type="email" id="requester-email" name="email_solicitante" required placeholder="tu.correo@ejemplo.com">
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
                    <legend>Datos de la Persona</legend>
                    <div class="form-group">
                        <label for="manual-name">Nombre Completo *</label>
                        <input type="text" id="manual-name" name="other[nombre_manual]">
                    </div>
                    <div class="form-group">
                        <label for="manual-area">Área | WBE *</label>
                        <input type="text" id="manual-area" name="other[area_wbe]">
                    </div>
                    <div class="form-group">
                        <label for="manual-activity">Actividad a Realizar *</label>
                        <textarea id="manual-activity" name="other[actividad]"></textarea>
                    </div>
                    <div id="other-area-services"></div>
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
                <p>Horario de atención: de <?= htmlspecialchars(get_setting('horario_apertura', $pdo)) ?> a <?= htmlspecialchars(get_setting('horario_cierre', $pdo)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="assets/js/main.js"></script>
</body>
</html>