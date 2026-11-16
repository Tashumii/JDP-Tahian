<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$stmt = $pdo->query('SELECT id, customer_name, contact, requested_items, status, created_at FROM quotes ORDER BY id DESC');
json_success($stmt->fetchAll());
