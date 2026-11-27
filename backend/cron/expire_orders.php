<?php
// backend/cron/expire_orders.php
if (php_sapi_name() !== 'cli') {
    $cronSecret = getenv('CRON_SECRET');
    if (!$cronSecret || ($_GET['cron_secret'] ?? '') !== $cronSecret) {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT id FROM orders WHERE status = 'pending' AND confirmation_expires_at IS NOT NULL AND confirmation_expires_at < UTC_TIMESTAMP() FOR UPDATE");
    $expiredOrderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $restoreStock = $pdo->prepare('UPDATE inventory SET quantity = quantity + ? WHERE id = ?');
    $cancelOrder = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");

    foreach ($expiredOrderIds as $orderId) {
        $itemsStmt = $pdo->prepare('SELECT inventory_id, qty FROM order_items WHERE order_id = ?');
        $itemsStmt->execute([(int)$orderId]);
        foreach ($itemsStmt->fetchAll() as $item) {
            $restoreStock->execute([(int)$item['qty'], (int)$item['inventory_id']]);
        }
        $cancelOrder->execute([(int)$orderId]);
    }

    $pdo->commit();
    echo 'Expired ' . count($expiredOrderIds) . " pending order(s).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order expiry failed: ' . $e->getMessage());
    echo "We could not process expired orders right now.\n";
    exit(1);
}
