<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();

$host = isset($input['smtp_host']) ? trim((string)$input['smtp_host']) : '';
$port = isset($input['smtp_port']) ? filter_var($input['smtp_port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) : false;
$username = isset($input['smtp_username']) ? trim((string)$input['smtp_username']) : '';
$passwordInput = isset($input['smtp_password']) ? (string)$input['smtp_password'] : '';
$encryption = isset($input['smtp_encryption']) ? strtolower(trim((string)$input['smtp_encryption'])) : 'tls';
$fromEmail = isset($input['from_email']) ? trim((string)$input['from_email']) : '';
$fromName = isset($input['from_name']) ? trim((string)$input['from_name']) : 'Joey Tahian';
$appBaseUrl = isset($input['app_base_url']) ? rtrim(trim((string)$input['app_base_url']), '/') : '';

if ($host === '' || $port === false) {
    json_error('SMTP host and a valid port are required.', 400);
}
if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
    json_error('SMTP encryption must be tls, ssl, or none.', 400);
}
if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
    json_error('From email must be a valid email address.', 400);
}
if (preg_match('/[\r\n]/', $fromName)) {
    json_error('From name cannot contain line breaks.', 400);
}

// Keep existing password if a masked/empty value is submitted.
$existingPassword = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'mail_smtp_password'");
    $stmt->execute();
    $existingPassword = (string)$stmt->fetchColumn();
} catch (Throwable $e) {
    // ignore
}

$masked = str_repeat('*', min(strlen($existingPassword), 12));
if ($passwordInput === '' || $passwordInput === $masked) {
    $password = $existingPassword;
} else {
    $password = $passwordInput;
}

if (($username === '') !== ($password === '')) {
    json_error('Both SMTP username and password must be configured.', 400);
}
if ($username !== '' && $encryption === 'none') {
    json_error('SMTP authentication requires TLS or SSL encryption.', 400);
}

$settings = [
    'mail_smtp_host' => $host,
    'mail_smtp_port' => (string)$port,
    'mail_smtp_username' => $username,
    'mail_smtp_password' => $password,
    'mail_smtp_encryption' => $encryption,
    'mail_from_email' => $fromEmail,
    'mail_from_name' => $fromName,
    'mail_app_base_url' => $appBaseUrl
];

try {
    $upsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $key => $value) {
        $upsert->execute([$key, $value]);
    }
} catch (Throwable $e) {
    error_log('Mail settings save failed: ' . $e->getMessage());
    json_error('Could not save mail settings.', 500);
}

json_success(null, 'Mail settings saved.');
