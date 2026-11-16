<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$stmt = $pdo->query("SELECT
    COALESCE(SUM(email_confirmed_at IS NOT NULL), 0) AS email_confirmed,
    COALESCE(SUM(status = 'pending'), 0) AS pending_confirmation
    FROM orders");
$counts = $stmt->fetch();
$counts['email_confirmed'] = (int)$counts['email_confirmed'];
$counts['pending_confirmation'] = (int)$counts['pending_confirmation'];
json_success($counts);
