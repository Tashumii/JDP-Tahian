<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = sanitize_input($_POST);
require_fields($input, ['id', 'quantity', 'condition']);

$quantity = filter_var($input['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($quantity === false) {
    json_error('Quantity must be a positive whole number.', 400);
}

if (!in_array($input['condition'], ['returned_good', 'returned_damaged'])) {
    json_error('Invalid condition for return', 400);
}

// Get item details
$stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ?');
$stmt->execute([$input['id']]);
$item = $stmt->fetch();

if (!$item) {
    json_error('Item not found', 404);
}

if ($quantity > (int)$item['quantity']) {
    json_error('Return quantity cannot exceed available stock.', 400);
}

// Option A: Update existing item (if condition matches) 
// Option B: Create a new inventory row for returned items to separate them from "new" stock
// The plan says "log + categorize return". Let's insert as a new row so it's tracked separately, 
// unless we just want to update the condition. 
// "return_item.php sets item_condition to returned_good or returned_damaged" -> 
// Let's create a new record for the returned batch so we don't mix new and returned stock in the same row.
$stmt = $pdo->prepare('INSERT INTO inventory (name, category, price, quantity, stage, item_condition) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $item['name'] . ' (Return)', 
    $item['category'], 
    $item['price'], // maybe discounted? keep same for now
    $quantity, 
    'on_hand', 
    $input['condition']
]);

json_success(['id' => $pdo->lastInsertId()], 'Return logged successfully');

