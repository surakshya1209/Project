<?php
require 'config.php';
require 'functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
     FROM cart c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// Validate stock again before creating the order
foreach ($items as $it) {
    if ($it['quantity'] > $it['stock']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $it['name'] . ' only has ' . $it['stock'] . ' left in stock.'];
        header('Location: cart.php');
        exit;
    }
}

$subtotal       = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['quantity'];
$deliveryCharge = 100.00;
$taxAmount      = 0.00; // adjust if VAT applies
$totalAmount    = $subtotal + $deliveryCharge + $taxAmount;

$errors = [];

// -------- STEP 1: show the delivery-address form (not submitted yet) --------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    $pageTitle = 'Checkout';
    require 'includes/header.php';
    ?>

    <div class="checkout-box checkout-form-box">
      <h1>Delivery Details</h1>
      <p class="checkout-subtitle">Please enter where this order should be delivered before you proceed to payment.</p>

      <?php foreach ($errors as $e): ?>
        <div class="flash error"><?= h($e) ?></div>
      <?php endforeach; ?>

      <form method="post" action="checkout.php" class="auth-form checkout-address-form">
        <label>Full Name
          <input type="text" name="delivery_name" value="<?= h($_SESSION['user_name'] ?? '') ?>" required>
        </label>
        <label>Phone Number
          <input type="tel" name="delivery_phone" placeholder="98XXXXXXXX" pattern="[0-9+ ]{7,15}" required>
        </label>
        <label>Delivery Location / Address
          <textarea name="delivery_address" rows="3" placeholder="City, Municipality/Ward, Street, Landmark..." required></textarea>
        </label>

        <div class="checkout-summary-mini">
          <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <div class="summary-row"><span>Delivery Charge</span><span><?= money($deliveryCharge) ?></span></div>
          <div class="summary-row total-row"><span>Total</span><span><?= money($totalAmount) ?></span></div>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Continue to Payment</button>
      </form>
    </div>

    <?php
    require 'includes/footer.php';
    exit;
}

// -------- STEP 2: address form was submitted - validate it --------
$deliveryName    = trim($_POST['delivery_name'] ?? '');
$deliveryPhone   = trim($_POST['delivery_phone'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');

if ($deliveryName === '' || $deliveryPhone === '' || $deliveryAddress === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill in your name, phone number and delivery address.'];
    header('Location: checkout.php');
    exit;
}

// Unique transaction id for this order
$transactionUuid = date('Ymd-His') . '-' . $userId . '-' . bin2hex(random_bytes(3));

$conn->begin_transaction();
try {
    $orderStmt = $conn->prepare(
        "INSERT INTO orders (user_id, transaction_uuid, total_amount, payment_method, status,
                              delivery_name, delivery_phone, delivery_address)
         VALUES (?, ?, ?, 'esewa', 'pending', ?, ?, ?)"
    );
    $orderStmt->bind_param(
        'isdsss',
        $userId, $transactionUuid, $totalAmount,
        $deliveryName, $deliveryPhone, $deliveryAddress
    );
    $orderStmt->execute();
    $orderId = $orderStmt->insert_id;
    $orderStmt->close();

    $itemStmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
    );
    foreach ($items as $it) {
        $itemStmt->bind_param('iiid', $orderId, $it['product_id'], $it['quantity'], $it['price']);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not create order. Please try again.'];
    header('Location: cart.php');
    exit;
}

// ---------------- Build the eSewa v2 payment form ----------------
$productCode          = ESEWA_MERCHANT_CODE;
$successUrl           = SITE_URL . '/esewa_success.php';
$failureUrl           = SITE_URL . '/esewa_failure.php';
$signedFieldNames      = 'total_amount,transaction_uuid,product_code';

// Signature message must follow exact order of signed_field_names
$message = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
$signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));

$pageTitle = 'Checkout';
require 'includes/header.php';
?>

<div class="checkout-box">
  <h1>Redirecting to eSewa...</h1>
  <p>Delivering to: <strong><?= h($deliveryName) ?></strong>, <?= h($deliveryPhone) ?><br><?= nl2br(h($deliveryAddress)) ?></p>
  <p>Please wait, you are being redirected to eSewa to complete your payment of <strong><?= money($totalAmount) ?></strong>.</p>
  <p>If you are not redirected automatically, click the button below.</p>

  <form id="esewaForm" action="<?= h(ESEWA_FORM_URL) ?>" method="POST">
    <input type="hidden" name="amount" value="<?= h($subtotal + $taxAmount) ?>">
    <input type="hidden" name="tax_amount" value="<?= h($taxAmount) ?>">
    <input type="hidden" name="total_amount" value="<?= h($totalAmount) ?>">
    <input type="hidden" name="transaction_uuid" value="<?= h($transactionUuid) ?>">
    <input type="hidden" name="product_code" value="<?= h($productCode) ?>">
    <input type="hidden" name="product_service_charge" value="0">
    <input type="hidden" name="product_delivery_charge" value="<?= h($deliveryCharge) ?>">
    <input type="hidden" name="success_url" value="<?= h($successUrl) ?>">
    <input type="hidden" name="failure_url" value="<?= h($failureUrl) ?>">
    <input type="hidden" name="signed_field_names" value="<?= h($signedFieldNames) ?>">
    <input type="hidden" name="signature" value="<?= h($signature) ?>">

    <button type="submit" class="btn btn-primary">Pay with eSewa</button>
  </form>
</div>

<script>
  // Auto-submit to eSewa after a short delay
  setTimeout(function () {
    document.getElementById('esewaForm').submit();
  }, 1200);
</script>

<?php require 'includes/footer.php'; ?>