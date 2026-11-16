<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

// Fetch the most recent summary for each period type
$summary = [
    'day' => ['total_orders' => 0, 'total_sales' => 0],
    'week' => ['total_orders' => 0, 'total_sales' => 0],
    'month' => ['total_orders' => 0, 'total_sales' => 0],
    'year' => ['total_orders' => 0, 'total_sales' => 0]
];

$stmt = $pdo->query('
    SELECT period_type, total_orders, total_sales 
    FROM sales_summary 
    WHERE id IN (
        SELECT MAX(id) FROM sales_summary GROUP BY period_type
    )
');

while ($row = $stmt->fetch()) {
    $summary[$row['period_type']] = [
        'total_orders' => (int)$row['total_orders'],
        'total_sales' => (float)$row['total_sales']
    ];
}

json_success($summary);

