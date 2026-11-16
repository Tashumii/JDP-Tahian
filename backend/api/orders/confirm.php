<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
$token = isset($input['token']) && is_string($input['token']) ? trim($input['token']) : '';
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    json_error('This confirmation link is invalid.', 400);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, order_no, status, confirmation_expires_at, email_confirmed_at FROM orders WHERE confirmation_token_hash = ? FOR UPDATE');
    $stmt->execute([hash('sha256', strtolower($token))]);
    $order = $stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        json_error('This confirmation link is invalid or has expired.', 404);
    }

    if ($order['email_confirmed_at'] !== null) {
        $pdo->commit();
        json_success(['order_no' => $order['order_no']], 'Your order is already confirmed.');
    }

    $expiresAt = $order['confirmation_expires_at'];
    if (!$expiresAt || new DateTimeImmutable($expiresAt, new DateTimeZone('UTC')) <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
        $pdo->rollBack();
        json_error('This confirmation link has expired. Contact us to confirm your order.', 410);
    }
    if ($order['status'] === 'cancelled') {
        $pdo->rollBack();
        json_error('This order was cancelled and cannot be confirmed.', 409);
    }
    if (!in_array($order['status'], ['pending', 'confirmed', 'packed', 'delivered'], true)) {
        $pdo->rollBack();
        json_error('This order cannot be confirmed in its current state.', 409);
    }

    $update = $pdo->prepare("UPDATE orders SET status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END, email_confirmed_at = UTC_TIMESTAMP() WHERE id = ?");
    $update->execute([(int)$order['id']]);
    $pdo->commit();

    json_success(['order_no' => $order['order_no']], 'Your order is confirmed.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Order email confirmation failed: ' . $e->getMessage());
    json_error('We could not confirm the order right now. Please try again.', 500);
}
