<?php
require 'config.php';
require 'functions.php';
requireLogin();

$pageTitle = 'Payment Failed';
require 'includes/header.php';
?>

<div class="result-box error">
  <h1>❌ Payment Failed</h1>
  <p>Your eSewa payment was not completed. No amount has been deducted from your cart contents — you can try again.</p>
  <a href="cart.php" class="btn btn-primary">Back to Cart</a>
</div>

<?php require 'includes/footer.php'; ?>
