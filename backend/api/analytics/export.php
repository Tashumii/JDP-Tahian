<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die('Method not allowed');
}

$period = $_GET['period'] ?? 'all';

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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Orders Report');

$sheet->setCellValue('A1', 'Order No');
$sheet->setCellValue('B1', 'Customer');
$sheet->setCellValue('C1', 'Email');
$sheet->setCellValue('D1', 'Mobile');
$sheet->setCellValue('E1', 'Address');
$sheet->setCellValue('F1', 'Total (₱)');
$sheet->setCellValue('G1', 'Payment Method');
$sheet->setCellValue('H1', 'Status');
$sheet->setCellValue('I1', 'Date');

$row = 2;
foreach ($orders as $order) {
    $sheet->setCellValue('A' . $row, $order['order_no']);
    $sheet->setCellValue('B' . $row, $order['customer_name']);
    $sheet->setCellValue('C' . $row, $order['email']);
    $sheet->setCellValue('D' . $row, $order['mobile']);
    $sheet->setCellValue('E' . $row, $order['address']);
    $sheet->setCellValue('F' . $row, $order['total']);
    $sheet->setCellValue('G' . $row, $order['payment_method']);
    $sheet->setCellValue('H' . $row, $order['status']);
    $sheet->setCellValue('I' . $row, $order['created_at']);
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="orders_report_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

