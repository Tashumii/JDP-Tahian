<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$keys = ['mail_smtp_host', 'mail_smtp_port', 'mail_smtp_username', 'mail_smtp_password',
    'mail_smtp_encryption', 'mail_from_email', 'mail_from_name', 'mail_app_base_url'];

$values = [];
try {
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($keys);
    foreach ($stmt->fetchAll() as $row) {
        $values[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
    // Table may not exist; ignore.
}

$maskPassword = function ($value) {
    if ($value === null || $value === '') return '';
    return str_repeat('*', min(strlen($value), 12));
};

$data = [
    'smtp_host' => $values['mail_smtp_host'] ?? '',
    'smtp_port' => isset($values['mail_smtp_port']) && $values['mail_smtp_port'] !== '' ? (int)$values['mail_smtp_port'] : 587,
    'smtp_username' => $values['mail_smtp_username'] ?? '',
    'smtp_password' => $maskPassword($values['mail_smtp_password'] ?? ''),
    'smtp_encryption' => $values['mail_smtp_encryption'] ?? 'tls',
    'from_email' => $values['mail_from_email'] ?? '',
    'from_name' => $values['mail_from_name'] ?? 'Joey Tahian',
    'app_base_url' => $values['mail_app_base_url'] ?? ''
];

json_success($data);
