<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$stmt = $pdo->query('SELECT * FROM inventory ORDER BY id DESC');
$inventory = $stmt->fetchAll();

json_success($inventory);

