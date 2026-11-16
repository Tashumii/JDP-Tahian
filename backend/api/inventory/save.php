<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = sanitize_input($_POST);
require_fields($input, ['name', 'category', 'price', 'quantity', 'stage', 'item_condition']);

$image_url = !empty($input['image_url']) ? $input['image_url'] : null;

if (!empty($input['id'])) {
    $stmt = $pdo->prepare('UPDATE inventory SET name=?, category=?, price=?, quantity=?, stage=?, item_condition=?, image_url=? WHERE id=?');
    $stmt->execute([
        $input['name'], $input['category'], $input['price'], 
        $input['quantity'], $input['stage'], $input['item_condition'], $image_url, $input['id']
    ]);
    json_success(null, 'Item updated successfully');
} else {
    $stmt = $pdo->prepare('INSERT INTO inventory (name, category, price, quantity, stage, item_condition, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $input['name'], $input['category'], $input['price'], 
        $input['quantity'], $input['stage'], $input['item_condition'], $image_url
    ]);
    json_success(['id' => $pdo->lastInsertId()], 'Item added successfully');
}

