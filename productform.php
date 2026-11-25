<?php
require 'auth_check.php';
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    // Handle Image Upload
    $imageName = '';
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $imageName = uniqid('prod_', true) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imageName);
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, price, stock, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$category_id, $name, $price, $stock, $imageName]);
    header("Location: products.php?success=1");
    exit();
}
?>