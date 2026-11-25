<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$myOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>My Orders</h2>
<table>
    <tr><th>Order #</th><th>Date</th><th>Total (KES)</th><th>Status</th></tr>
    <?php foreach ($myOrders as $order): ?>
        <tr>
            <td>#<?= $order['id'] ?></td>
            <td><?= $order['created_at'] ?></td>
            <td><?= number_format($order['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>