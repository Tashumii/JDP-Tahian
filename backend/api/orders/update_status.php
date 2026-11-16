<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
if (!isset($input['id'], $input['status'])) {
    json_error('Order ID and new status are required.', 400);
}

$orderId = filter_var($input['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$newStatus = is_string($input['status']) ? trim($input['status']) : '';
$validStatuses = ['pending', 'confirmed', 'packed', 'delivered', 'cancelled'];
if ($orderId === false || !in_array($newStatus, $validStatuses, true)) {
    json_error('Invalid order ID or status.', 400);
}

$transitions = [
    'pending' => ['confirmed', 'cancelled'],
    'confirmed' => ['packed', 'cancelled'],
    'packed' => ['delivered'],
    'delivered' => [],
    'cancelled' => []
];

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        json_error('Order not found.', 404);
    }
    if (!in_array($newStatus, $transitions[$order['status']] ?? [], true)) {
        $pdo->rollBack();
        json_error('That status change is not allowed for this order.', 409);
    }

    if ($newStatus === 'cancelled') {
        $items = $pdo->prepare('SELECT inventory_id, qty FROM order_items WHERE order_id = ?');
        $items->execute([$orderId]);
        $restoreStock = $pdo->prepare('UPDATE inventory SET quantity = quantity + ? WHERE id = ?');
        foreach ($items->fetchAll() as $item) {
            $restoreStock->execute([(int)$item['qty'], (int)$item['inventory_id']]);
        }
    }

    $update = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $orderId]);
    $pdo->commit();

    json_success(null, 'Order status updated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Order status update failed: ' . $e->getMessage());
    json_error('We could not update the order right now. Please try again.', 500);
}
