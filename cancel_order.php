<?php
require 'config.php';
require 'functions.php';
requireLogin();

$orderId = (int)($_POST['order_id'] ?? 0);

$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $orderId, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($order && $order['status'] === 'pending') {
    $upd = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $upd->bind_param('i', $orderId);
    $upd->execute();
    $upd->close();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order cancelled.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'This order can no longer be cancelled.'];
}

header('Location: orders.php');
exit;