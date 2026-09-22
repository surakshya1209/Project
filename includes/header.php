<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?>PustakBhawan</title>
<link rel="stylesheet" href="<?= isset($assetBase) ? $assetBase : '' ?>css/style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a href="<?= isset($assetBase) ? $assetBase : '' ?>index.php" class="logo">
  <img src="<?= isset($assetBase) ? $assetBase : '' ?>uploads/logo.png" alt="Pustak Bhawan Logo" class="logo-img">
  Pustak Bhawan
</a>

    <form class="search-form" action="<?= isset($assetBase) ? $assetBase : '' ?>index.php" method="get">
      <input type="text" name="q" placeholder="Search for pens, notebooks..." value="<?= h($_GET['q'] ?? '') ?>">
      <button type="submit">Search</button>
    </form>

    <nav class="main-nav">
      <a href="<?= isset($assetBase) ? $assetBase : '' ?>index.php">Home</a>
      <a href="<?= isset($assetBase) ? $assetBase : '' ?>cart.php" class="cart-link">Cart
        <?php $cc = getCartCount($conn); if ($cc > 0): ?>
          <span class="cart-badge"><?= $cc ?></span>
        <?php endif; ?>
      </a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= isset($assetBase) ? $assetBase : '' ?>orders.php">My Orders</a>
        <span class="hello">Hi, <?= h($_SESSION['user_name']) ?></span>
        <a href="<?= isset($assetBase) ? $assetBase : '' ?>logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= isset($assetBase) ? $assetBase : '' ?>login.php">Login</a>
        <a href="<?= isset($assetBase) ? $assetBase : '' ?>register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash <?= h($_SESSION['flash']['type']) ?>">
    <?= h($_SESSION['flash']['message']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<main class="site-main">
