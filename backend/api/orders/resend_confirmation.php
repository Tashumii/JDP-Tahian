<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
$orderId = isset($input['id']) ? filter_var($input['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if ($orderId === false) {
    json_error('A valid order ID is required.', 400);
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expiresAt = gmdate('Y-m-d H:i:s', time() + 86400);

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, order_no, customer_name, email, total, status FROM orders WHERE id = ? FOR UPDATE');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        $pdo->rollBack();
        json_error('Order not found.', 404);
    }
    if ($order['status'] !== 'pending') {
        $pdo->rollBack();
        json_error('Confirmation emails can only be resent for pending orders.', 409);
    }

    $update = $pdo->prepare('UPDATE orders SET confirmation_token_hash = ?, confirmation_expires_at = ?, confirmation_email_sent_at = NULL WHERE id = ?');
    $update->execute([$tokenHash, $expiresAt, $orderId]);
    $pdo->commit();

    try {
        $itemStmt = $pdo->prepare('SELECT i.name, oi.qty, oi.unit_price FROM order_items oi JOIN inventory i ON i.id = oi.inventory_id WHERE oi.order_id = ? ORDER BY i.name');
        $itemStmt->execute([$orderId]);
        send_order_confirmation_email($order['email'], $order['customer_name'], $order['order_no'], $order['total'], $token, $itemStmt->fetchAll());
        $sentAt = $pdo->prepare('UPDATE orders SET confirmation_email_sent_at = UTC_TIMESTAMP() WHERE id = ?');
        $sentAt->execute([$orderId]);
        json_success(null, 'Confirmation email sent.');
    } catch (Throwable $mailError) {
        error_log('Confirmation email resend failed for ' . $order['order_no'] . ': ' . $mailError->getMessage());
        json_error('The email could not be sent. Check the SMTP settings and try again.', 503);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Order confirmation resend failed: ' . $e->getMessage());
    json_error('We could not prepare a confirmation email right now.', 500);
}
