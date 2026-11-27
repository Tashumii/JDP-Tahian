<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$pdo->exec('SET SESSION group_concat_max_len = 1000000');
$stmt = $pdo->query("
    SELECT o.id, o.order_no, o.customer_name, o.email, o.mobile, o.address,
           o.total, o.payment_method, o.status, o.created_at,
           o.email_confirmed_at, o.confirmation_email_sent_at,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
    (SELECT GROUP_CONCAT(CONCAT(i.name, ' × ', oi.qty) ORDER BY i.name SEPARATOR ', ')
       FROM order_items oi JOIN inventory i ON i.id = oi.inventory_id
       WHERE oi.order_id = o.id) as items_summary
    FROM orders o ORDER BY o.id DESC
");
$orders = $stmt->fetchAll();

json_success($orders);

