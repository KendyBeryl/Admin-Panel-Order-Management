<?php
require 'auth_check.php';
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}

$orders = $pdo->query("SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>