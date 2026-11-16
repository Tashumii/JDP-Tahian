<?php
// backend/cron/refresh_sales.php
require_once __DIR__ . '/../config/db.php';

// Helper function to insert/update period
function refreshPeriod($pdo, $period_type, $period_start, $orders_count, $sales_total) {
    $stmt = $pdo->prepare('
        INSERT INTO sales_summary (period_type, period_start, total_orders, total_sales)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE total_orders = VALUES(total_orders), total_sales = VALUES(total_sales)
    ');
    $stmt->execute([$period_type, $period_start, $orders_count, $sales_total]);
}

try {
    // We will refresh today, this week (starting Monday), this month, this year
    
    // Day
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT COUNT(*) as c, COALESCE(SUM(total), 0) as s FROM orders WHERE DATE(created_at) = ? AND status != "cancelled"');
    $stmt->execute([$today]);
    $dayData = $stmt->fetch();
    refreshPeriod($pdo, 'day', $today, $dayData['c'], $dayData['s']);
    
    // Week (assuming Monday start)
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $stmt = $pdo->prepare('SELECT COUNT(*) as c, COALESCE(SUM(total), 0) as s FROM orders WHERE DATE(created_at) >= ? AND status != "cancelled"');
    $stmt->execute([$week_start]);
    $weekData = $stmt->fetch();
    refreshPeriod($pdo, 'week', $week_start, $weekData['c'], $weekData['s']);
    
    // Month
    $month_start = date('Y-m-01');
    $stmt = $pdo->prepare('SELECT COUNT(*) as c, COALESCE(SUM(total), 0) as s FROM orders WHERE DATE(created_at) >= ? AND status != "cancelled"');
    $stmt->execute([$month_start]);
    $monthData = $stmt->fetch();
    refreshPeriod($pdo, 'month', $month_start, $monthData['c'], $monthData['s']);
    
    // Year
    $year_start = date('Y-01-01');
    $stmt = $pdo->prepare('SELECT COUNT(*) as c, COALESCE(SUM(total), 0) as s FROM orders WHERE DATE(created_at) >= ? AND status != "cancelled"');
    $stmt->execute([$year_start]);
    $yearData = $stmt->fetch();
    refreshPeriod($pdo, 'year', $year_start, $yearData['c'], $yearData['s']);
    
    echo "Sales summary refreshed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

