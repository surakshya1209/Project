<?php
require 'config.php';
require 'functions.php';
requireLogin();

$pageTitle = 'Payment Result';

$rawData = $_GET['data'] ?? '';
$decoded = $rawData ? json_decode(base64_decode($rawData), true) : null;

$success = false;
$message = 'We could not confirm your payment. Please contact support if you were charged.';
$order   = null;

if ($decoded && isset($decoded['transaction_uuid'], $decoded['total_amount'], $decoded['signature'])) {

    // 1) Verify the signature eSewa sent back, using the same field order
    //    eSewa reports in signed_field_names
    $fields = explode(',', $decoded['signed_field_names'] ?? 'transaction_code,status,total_amount,transaction_uuid,product_code');
    $parts = [];
    foreach ($fields as $f) {
        $parts[] = $f . '=' . ($decoded[$f] ?? '');
    }
    $message_to_sign = implode(',', $parts);
    $expectedSignature = base64_encode(hash_hmac('sha256', $message_to_sign, ESEWA_SECRET_KEY, true));

    $signatureValid = hash_equals($expectedSignature, $decoded['signature']);

    // 2) Look up the matching pending order
    $stmt = $conn->prepare(
        "SELECT * FROM orders WHERE transaction_uuid = ? AND user_id = ?"
    );
    $txUuid = $decoded['transaction_uuid'];
    $stmt->bind_param('si', $txUuid, $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order && $signatureValid && $decoded['status'] === 'COMPLETE') {

        // 3) Double-check directly with eSewa's transaction status API (server-to-server)
        $statusUrl = ESEWA_STATUS_CHECK_URL . '?' . http_build_query([
            'product_code'     => ESEWA_MERCHANT_CODE,
            'total_amount'     => $order['total_amount'],
            'transaction_uuid' => $order['transaction_uuid'],
        ]);

        $verified = false;
        $ch = curl_init($statusUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $statusData = json_decode($response, true);
            if (isset($statusData['status']) && $statusData['status'] === 'COMPLETE') {
                $verified = true;
            }
        }

        if ($verified && $order['status'] !== 'paid') {
            $conn->begin_transaction();
            try {
                $upd = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
                $upd->bind_param('i', $order['id']);
                $upd->execute();
                $upd->close();

                // reduce stock for each item
                $itemsRes = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsRes->bind_param('i', $order['id']);
                $itemsRes->execute();
                $orderItems = $itemsRes->get_result()->fetch_all(MYSQLI_ASSOC);
                $itemsRes->close();

                foreach ($orderItems as $oi) {
                    $dec = $conn->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?");
                    $dec->bind_param('ii', $oi['quantity'], $oi['product_id']);
                    $dec->execute();
                    $dec->close();
                }

                // empty the user's cart
                $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $clearCart->bind_param('i', $_SESSION['user_id']);
                $clearCart->execute();
                $clearCart->close();

                $conn->commit();
                $success = true;
                $message = 'Payment successful! Your order has been placed.';
            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Payment was verified but we could not finalize your order. Please contact support.';
            }
        } elseif ($order['status'] === 'paid') {
            $success = true;
            $message = 'This order has already been marked as paid.';
        } else {
            $message = 'We could not verify this payment with eSewa. Please contact support.';
        }
    }
}

require 'includes/header.php';
?>

<div class="result-box <?= $success ? 'success' : 'error' ?>">
  <h1><?= $success ? '✅ Payment Successful' : '❌ Payment Not Confirmed' ?></h1>
  <p><?= h($message) ?></p>
  <?php if ($order): ?>
    <p>Order Reference: <strong><?= h($order['transaction_uuid']) ?></strong></p>
    <p>Amount: <strong><?= money($order['total_amount']) ?></strong></p>
  <?php endif; ?>
  <a href="<?= $success ? 'orders.php' : 'cart.php' ?>" class="btn btn-primary">
    <?= $success ? 'View My Orders' : 'Back to Cart' ?>
  </a>
</div>

<?php require 'includes/footer.php'; ?>
