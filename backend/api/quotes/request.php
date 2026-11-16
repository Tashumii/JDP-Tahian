<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
foreach (['customer_name', 'contact', 'requested_items'] as $field) {
    if (!isset($input[$field])) {
        json_error("Missing required field: $field", 400);
    }
}

$customerName = validated_string($input['customer_name'], 'name', 2, 100);
if (!preg_match('/\p{L}/u', $customerName)) {
    json_error('Enter a valid name.', 400);
}

$contact = validated_string($input['contact'], 'contact information', 5, 100);
$isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
$isPhone = preg_match('/^\+?[0-9][0-9\s().-]{5,28}$/', $contact) === 1
    && strlen(preg_replace('/\D/', '', $contact)) >= 7;
if (!$isEmail && !$isPhone) {
    json_error('Enter a valid email address or phone number.', 400);
}

$requestedItems = validated_string($input['requested_items'], 'request description', 10, 2000);
if (!preg_match('/\p{L}/u', $requestedItems)) {
    json_error('Describe the items you are looking for.', 400);
}

try {
    $stmt = $pdo->prepare('INSERT INTO quotes (customer_name, contact, requested_items) VALUES (?, ?, ?)');
    $stmt->execute([$customerName, $contact, $requestedItems]);
    json_success(['quote_id' => (int)$pdo->lastInsertId()], 'Quote request submitted.');
} catch (Throwable $e) {
    error_log('Quote request failed: ' . $e->getMessage());
    json_error('We could not submit your quote request right now. Please try again.', 500);
}
