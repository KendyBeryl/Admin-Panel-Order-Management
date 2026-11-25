<?php
require 'auth_check.php';
require '../config/db.php';

// Total Sales (excluding cancelled orders)
$salesStmt = $pdo->query("SELECT SUM(total_amount) AS total_sales, COUNT(id) AS total_orders FROM orders WHERE status != 'Cancelled'");
$summary = $salesStmt->fetch(PDO::FETCH_ASSOC);

// Low-Stock Alerts
$lowStockStmt = $pdo->query("SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC");
$lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Admin Dashboard</h2>
<p>Total Revenue: KES <?= number_format($summary['total_sales'] ?? 0, 2) ?></p>
<p>Total Orders: <?= $summary['total_orders'] ?></p>

<h3>Low Stock Alerts</h3>
<ul>
    <?php foreach ($lowStockProducts as $item): ?>
        <li><?= htmlspecialchars($item['name']) ?> — Only <?= $item['stock'] ?> left!</li>
    <?php endforeach; ?>
</ul>