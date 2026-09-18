<?php
require 'config.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please login to add items to your cart.'];
    header('Location: login.php?redirect=' . urlencode('product.php?id=' . $productId));
    exit;
}

// confirm product exists and has stock
$stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product || $product['stock'] <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'This product is currently unavailable.'];
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)"
);
$maxQty = $product['stock'];
$stmt->bind_param('iiii', $userId, $productId, $quantity, $maxQty);
$stmt->execute();
$stmt->close();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Item added to cart.'];
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
