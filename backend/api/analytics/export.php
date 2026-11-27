<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$period = $_GET['period'] ?? 'all';
if (!in_array($period, ['all', 'day', 'week', 'month'], true)) {
    $period = 'all';
}

$sql = "SELECT order_no, customer_name, email, mobile, address, total, payment_method, status, created_at FROM orders";
$params = [];
if ($period === 'day') {
    $sql .= " WHERE DATE(created_at) = CURDATE()";
} elseif ($period === 'week') {
    $sql .= " WHERE DATE(created_at) >= DATE_ADD(CURDATE(), INTERVAL -7 DAY)";
} elseif ($period === 'month') {
    $sql .= " WHERE DATE(created_at) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$filename = 'orders_report_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fputcsv($output, ['Order No', 'Customer', 'Email', 'Mobile', 'Address', 'Total', 'Payment Method', 'Status', 'Date']);

foreach ($orders as $order) {
    fputcsv($output, [
        $order['order_no'],
        $order['customer_name'],
        $order['email'],
        $order['mobile'],
        $order['address'],
        $order['total'],
        $order['payment_method'],
        $order['status'],
        $order['created_at']
    ]);
}

fclose($output);
exit;
