<?php
require 'config.php';
require 'functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId   = (int)($_POST['cart_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $conn->prepare(
        "UPDATE cart c
         JOIN products p ON p.id = c.product_id
         SET c.quantity = LEAST(?, p.stock)
         WHERE c.id = ? AND c.user_id = ?"
    );
    $stmt->bind_param('iii', $quantity, $cartId, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

header('Location: cart.php');
exit;
