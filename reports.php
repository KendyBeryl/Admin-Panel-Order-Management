<?php
require 'auth_check.php';
require '../config/db.php';

// Default date filter: Last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 1. Sales Summary within Date Range
$salesStmt = $pdo->prepare("
    SELECT 
        COUNT(id) AS total_orders,
        COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END), 0) AS net_revenue,
        COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) AS cancelled_orders
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$salesStmt->execute([$startDate, $endDate]);
$salesSummary = $salesStmt->fetch(PDO::FETCH_ASSOC);

// 2. Top 5 Best-Selling Products
$topProductsStmt = $pdo->prepare("
    SELECT 
        p.name, 
        SUM(oi.quantity) AS units_sold, 
        SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status != 'Cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY units_sold DESC
    LIMIT 5
");
$topProductsStmt->execute([$startDate, $endDate]);
$topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. New Customer Registrations in Period
$newUsersStmt = $pdo->prepare("SELECT COUNT(id) FROM users WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?");
$newUsersStmt->execute([$startDate, $endDate]);
$newCustomersCount = $newUsersStmt->fetchColumn();

// 4. Optional CSV Export of Orders in Date Range
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . $startDate . '_to_' . $endDate . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer Name', 'Amount (KES)', 'Status', 'Order Date']);

    $exportStmt = $pdo->prepare("
        SELECT o.id, u.name, o.total_amount, o.status, o.created_at 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        ORDER BY o.created_at DESC
    ");
    $exportStmt->execute([$startDate, $endDate]);
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Reports</title>
    <style>
        .cards { display: flex; gap: 20px; margin: 20px 0; }
        .card { border: 1px solid #ccc; padding: 15px; border-radius: 5px; min-width: 180px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>

<h2>Sales & Store Reports</h2>
<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<!-- Date Filter Form -->
<form method="GET">
    <label>From: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>To: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Filter</button>
    <button type="submit" name="export" value="csv">Download CSV</button>
</form>

<!-- Summary Cards -->
<div class="cards">
    <div class="card">
        <h4>Net Revenue</h4>
        <p><strong>KES <?= number_format($salesSummary['net_revenue'], 2) ?></strong></p>
    </div>
    <div class="card">
        <h4>Total Orders</h4>
        <p><strong><?= $salesSummary['total_orders'] ?></strong> (<?= $salesSummary['cancelled_orders'] ?> Cancelled)</p>
    </div>
    <div class="card">
        <h4>New Customers</h4>
        <p><strong><?= $newCustomersCount ?></strong></p>
    </div>
</div>

<!-- Top Selling Products Table -->
<h3>Top 5 Best-Selling Products</h3>
<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Units Sold</th>
            <th>Revenue Generated (KES)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($topProducts)): ?>
            <tr><td colspan="3">No sales recorded in this date range.</td></tr>
        <?php else: ?>
            <?php foreach ($topProducts as $prod): ?>
                <tr>
                    <td><?= htmlspecialchars($prod['name']) ?></td>
                    <td><?= $prod['units_sold'] ?></td>
                    <td><?= number_format($prod['revenue'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>