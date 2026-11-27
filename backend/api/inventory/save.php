<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = sanitize_input($_POST);
require_fields($input, ['name', 'category', 'price', 'quantity', 'stage', 'item_condition']);

$name = validated_string($input['name'], 'name', 2, 120);
$category = validated_string($input['category'], 'category', 1, 60);

$validStages = ['made', 'packed', 'on_hand'];
$validConditions = ['new', 'returned_good', 'returned_damaged'];
if (!in_array($input['stage'], $validStages, true)) {
    json_error('Invalid inventory stage.', 400);
}
if (!in_array($input['item_condition'], $validConditions, true)) {
    json_error('Invalid inventory condition.', 400);
}

$price = filter_var($input['price'], FILTER_VALIDATE_FLOAT);
if ($price === false || $price <= 0) {
    json_error('Price must be a positive number.', 400);
}

$quantity = filter_var($input['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($quantity === false) {
    json_error('Quantity must be a whole number.', 400);
}

$image_url = null;
if (!empty($input['image_url'])) {
    $rawImageUrl = html_entity_decode((string)$input['image_url'], ENT_QUOTES, 'UTF-8');
    if (!filter_var($rawImageUrl, FILTER_VALIDATE_URL)) {
        json_error('Image URL must be a valid URL.', 400);
    }
    $scheme = parse_url($rawImageUrl, PHP_URL_SCHEME);
    if (!in_array($scheme, ['http', 'https'], true)) {
        json_error('Image URL must use http or https.', 400);
    }
    $image_url = $rawImageUrl;
}

if (!empty($input['id'])) {
    $stmt = $pdo->prepare('UPDATE inventory SET name=?, category=?, price=?, quantity=?, stage=?, item_condition=?, image_url=? WHERE id=?');
    $stmt->execute([
        $name, $category, $price, 
        $quantity, $input['stage'], $input['item_condition'], $image_url, $input['id']
    ]);
    json_success(null, 'Item updated successfully');
} else {
    $stmt = $pdo->prepare('INSERT INTO inventory (name, category, price, quantity, stage, item_condition, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $name, $category, $price, 
        $quantity, $input['stage'], $input['item_condition'], $image_url
    ]);
    json_success(['id' => $pdo->lastInsertId()], 'Item added successfully');
}

