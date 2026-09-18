<?php
require 'config.php';
require 'functions.php';
requireLogin();

$stmt = $conn->prepare(
    "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.stock
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ? ORDER BY c.id DESC"
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}
$deliveryCharge = $subtotal > 0 ? 100.00 : 0;
$total = $subtotal + $deliveryCharge;

$pageTitle = 'Your Cart';
require 'includes/header.php';
?>

<h1>Your Cart</h1>

<?php if (empty($items)): ?>
  <p class="empty-msg">Your cart is empty. <a href="index.php">Continue shopping</a>.</p>
<?php else: ?>
  <div class="cart-layout">
    <div class="cart-items">
      <?php foreach ($items as $it): ?>
        <div class="cart-item">
          <img src="uploads/<?= h($it['image']) ?>" alt="<?= h($it['name']) ?>"
               onerror="this.src='https://via.placeholder.com/100?text=Item'">
          <div class="cart-item-info">
            <a href="product.php?id=<?= $it['product_id'] ?>" class="product-name"><?= h($it['name']) ?></a>
            <div class="cart-item-price"><?= money($it['price']) ?> each</div>
          </div>
          <form method="post" action="update_cart.php" class="qty-form">
            <input type="hidden" name="cart_id" value="<?= $it['cart_id'] ?>">
            <input type="number" name="quantity" value="<?= $it['quantity'] ?>" min="1" max="<?= $it['stock'] ?>">
            <button type="submit" class="btn btn-small">Update</button>
          </form>
          <div class="cart-item-total"><?= money($it['price'] * $it['quantity']) ?></div>
          <a href="remove_from_cart.php?cart_id=<?= $it['cart_id'] ?>" class="remove-link">Remove</a>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="cart-summary">
      <h2>Order Summary</h2>
      <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div class="summary-row"><span>Delivery</span><span><?= money($deliveryCharge) ?></span></div>
      <div class="summary-row total-row"><span>Total</span><span><?= money($total) ?></span></div>
      <a href="checkout.php" class="btn btn-primary btn-full">Proceed to Checkout</a>
    </div>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
