<?php
require 'config.php';
require 'functions.php';
requireLogin();

$cartId = (int)($_GET['cart_id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $cartId, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed from cart.'];
header('Location: cart.php');
exit;
