<?php
require 'config.php';
require 'functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$rating    = (int)($_POST['rating'] ?? 0);
$comment   = trim($_POST['comment'] ?? '');
$userId    = $_SESSION['user_id'];

if ($productId <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please choose a rating between 1 and 5.'];
    header('Location: product.php?id=' . $productId);
    exit;
}

// Upsert: one review per user per product
$stmt = $conn->prepare(
    "INSERT INTO reviews (product_id, user_id, rating, comment)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = CURRENT_TIMESTAMP"
);
$stmt->bind_param('iiis', $productId, $userId, $rating, $comment);
$stmt->execute();
$stmt->close();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Thank you for your review!'];
header('Location: product.php?id=' . $productId . '#reviews');
exit;
