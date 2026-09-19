<?php
require 'config.php';
require 'functions.php';
requireLogin();

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'My Orders';
require 'includes/header.php';
?>

<h1>My Orders</h1>

<?php if (empty($orders)): ?>
  <p class="empty-msg">You haven't placed any orders yet. <a href="index.php">Start shopping</a>.</p>
<?php else: ?>
  <div class="orders-list">
    <?php foreach ($orders as $o): ?>
      <div class="order-card">
        <div class="order-card-head">
          <div>
            <strong>Order #<?= h($o['transaction_uuid']) ?></strong>
            <span class="order-date"><?= date('M d, Y g:i A', strtotime($o['created_at'])) ?></span>
          </div>
          <span class="status-badge status-<?= h($o['status']) ?>"><?= ucfirst(h($o['status'])) ?></span>
        </div>

        <?php
          $itemStmt = $conn->prepare(
              "SELECT oi.quantity, oi.price, p.name FROM order_items oi
               JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?"
          );
          $itemStmt->bind_param('i', $o['id']);
          $itemStmt->execute();
          $orderItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
          $itemStmt->close();
        ?>
        <ul class="order-items-list">
          <?php foreach ($orderItems as $oi): ?>
            <li><?= h($oi['name']) ?> × <?= $oi['quantity'] ?> — <?= money($oi['price'] * $oi['quantity']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php if (!empty($o['delivery_address'])): ?>
  <p class="delivery-info">
    📍 Delivering to <strong><?= h($o['delivery_name']) ?></strong> (<?= h($o['delivery_phone']) ?>)<br>
    <?= nl2br(h($o['delivery_address'])) ?>
  </p>
<?php endif; ?>
<?php if ($o['status'] === 'pending'): ?>
  <form method="post" action="cancel_order.php" onsubmit="return confirm('Cancel this order?');" style="margin-top:10px;">
    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
    <button type="submit" class="btn btn-small" style="background:#c0392b; width:auto; padding:8px 16px;">Cancel Order</button>
  </form>
<?php endif; ?>

        <div class="order-total">Total: <?= money($o['total_amount']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
