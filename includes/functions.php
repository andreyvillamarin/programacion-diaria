<?php
/**
 * EnvÃ­a un correo usando la API de Brevo.
 */
function send_brevo_email(array $to, string $subject, string $htmlContent, PDO $pdo): bool {
    $apiKey = get_setting('brevo_api_key', $pdo);
    if (empty($apiKey)) {
        error_log('Error Brevo: API Key no configurada.');
        return false;
    }
    // El email remitente debe estar verificado en tu cuenta de Brevo
    $sender = ['name' => APP_NAME, 'email' => 'noreply@qdos.network'];
    $data = ['sender' => $sender, 'to' => $to, 'subject' => $subject, 'htmlContent' => $htmlContent];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['accept: application/json', 'api-key: ' . $apiKey, 'content-type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) return true;
    
    error_log("Error de Brevo (HTTP $httpCode): " . $response);
    return false;
}

/**
 * Obtiene un valor de la tabla de configuraciÃ³n.
 */
function get_setting(string $key, PDO $pdo): string {
    $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?: '';
}

/**
 * Verifica si el formulario debe estar abierto.
 */
function is_form_open(PDO $pdo): bool {
    $apertura = get_setting('horario_apertura', $pdo);
    $cierre = get_setting('horario_cierre', $pdo);
    $now = new DateTime();
    return ($now >= new DateTime($apertura) && $now <= new DateTime($cierre));
}
/**
 * Env¨ªa datos a un Webhook de Google Apps Script.
 */
function sync_to_google_sheet(array $data, PDO $pdo) {
    $webhook_url = get_setting('google_sheets_webhook_url', $pdo);
    if (empty($webhook_url)) {
        return false; // No hacer nada si no hay URL
    }
    
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Seguir redirecciones de Google
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>