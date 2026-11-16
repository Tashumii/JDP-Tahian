<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

// Recommend inventory where quantity > 0 and stage IN ('made','packed','on_hand') and item_condition != 'returned_damaged'
$sql = "SELECT * FROM inventory WHERE quantity > 0 AND item_condition != 'returned_damaged'";
$params = [];

if (!empty($_GET['category'])) {
    $sql .= " AND category = ?";
    $params[] = sanitize_input($_GET['category']);
}
if (!empty($_GET['search'])) {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . sanitize_input($_GET['search']) . '%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

json_success($items);

