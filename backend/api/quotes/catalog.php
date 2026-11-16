<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

// Public catalog: Show all items except returned_damaged
$sql = "SELECT * FROM inventory WHERE item_condition != 'returned_damaged'";
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

