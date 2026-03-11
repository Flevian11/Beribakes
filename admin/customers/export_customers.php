<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query (same as index.php)
$sql = "SELECT c.*, 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.order_date) as last_order_date
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_filter == 'today') {
    $sql .= " AND DATE(c.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter == 'year') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

$sql .= " GROUP BY c.id";

// Sorting
if ($sort == 'newest') {
    $sql .= " ORDER BY c.created_at DESC";
} elseif ($sort == 'oldest') {
    $sql .= " ORDER BY c.created_at ASC";
} elseif ($sort == 'most_orders') {
    $sql .= " ORDER BY total_orders DESC";
} elseif ($sort == 'highest_spent') {
    $sql .= " ORDER BY total_spent DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=customers_export_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Customer ID',
    'Name',
    'Email',
    'Phone',
    'Registration Date',
    'Total Orders',
    'Total Spent (KES)',
    'Last Order Date'
]);

// Add data rows
foreach ($customers as $customer) {
    fputcsv($output, [
        $customer['id'],
        $customer['name'],
        $customer['email'],
        $customer['phone'],
        date('Y-m-d', strtotime($customer['created_at'])),
        $customer['total_orders'],
        number_format($customer['total_spent'], 2),
        $customer['last_order_date'] ? date('Y-m-d', strtotime($customer['last_order_date'])) : 'Never'
    ]);
}

fclose($output);
exit;