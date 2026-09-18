<?php
require 'config.php';
require 'functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p
                         LEFT JOIN categories c ON c.id = p.category_id
                         WHERE p.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: index.php');
    exit;
}

$rating = getProductRating($conn, $id);

// rating breakdown (5 star -> 1 star counts) for the bar chart
$breakdown = array_fill(1, 5, 0);
$res = $conn->prepare("SELECT rating, COUNT(*) AS c FROM reviews WHERE product_id = ? GROUP BY rating");
$res->bind_param('i', $id);
$res->execute();
foreach ($res->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $breakdown[(int)$row['rating']] = (int)$row['c'];
}
$res->close();

// reviews list
$revStmt = $conn->prepare("SELECT r.*, u.name AS user_name FROM reviews r
                            JOIN users u ON u.id = r.user_id
                            WHERE r.product_id = ? ORDER BY r.created_at DESC");
$revStmt->bind_param('i', $id);
$revStmt->execute();
$reviews = $revStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$revStmt->close();

// has current user already reviewed?
$userReview = null;
if (isLoggedIn()) {
    foreach ($reviews as $r) {
        if ((int)$r['user_id'] === (int)$_SESSION['user_id']) { $userReview = $r; break; }
    }
}

$related = getAlsoBoughtProducts($conn, $id, 4);

$pageTitle = $product['name'];
require 'includes/header.php';
?>

<div class="product-detail">
  <div class="product-detail-img">
    <img src="uploads/<?= h($product['image']) ?>" alt="<?= h($product['name']) ?>"
         onerror="this.src='https://via.placeholder.com/450x400?text=StationHub'">
  </div>

  <div class="product-detail-info">
    <p class="breadcrumb"><?= h($product['category_name'] ?? 'Stationery') ?></p>
    <h1><?= h($product['name']) ?></h1>

    <div class="product-rating">
      <?= renderStars($rating['avg']) ?>
      <span class="rating-text"><?= $rating['avg'] ?> out of 5 (<?= $rating['count'] ?> reviews)</span>
    </div>

    <div class="product-price-big"><?= money($product['price']) ?></div>
    <p class="stock-info">
      <?= $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock' ?>
    </p>

    <p class="product-desc"><?= nl2br(h($product['description'])) ?></p>

    <form method="post" action="add_to_cart.php" class="add-cart-form">
      <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
      <label>Qty
        <input type="number" name="quantity" value="1" min="1" max="<?= max(1, $product['stock']) ?>">
      </label>
      <button type="submit" class="btn btn-primary" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
        <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
      </button>
    </form>
  </div>
</div>

<section class="reviews-section">
  <h2>Customer Reviews</h2>

  <div class="rating-breakdown">
    <?php for ($s = 5; $s >= 1; $s--):
        $pct = $rating['count'] > 0 ? round(($breakdown[$s] / $rating['count']) * 100) : 0;
    ?>
      <div class="breakdown-row">
        <span><?= $s ?> star</span>
        <div class="bar-track"><div class="bar-fill" style="width: <?= $pct ?>%"></div></div>
        <span><?= $breakdown[$s] ?></span>
      </div>
    <?php endfor; ?>
  </div>

  <?php if (isLoggedIn()): ?>
    <div class="review-form-box">
      <h3><?= $userReview ? 'Update your review' : 'Write a review' ?></h3>
      <form method="post" action="submit_review.php" class="review-form">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <div class="star-input" id="star-input">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <label>
              <input type="radio" name="rating" value="<?= $s ?>"
                <?= ($userReview && (int)$userReview['rating'] === $s) ? 'checked' : '' ?> required>
              <span>★</span>
            </label>
          <?php endfor; ?>
        </div>
        <textarea name="comment" rows="3" placeholder="Share your experience with this product..."><?= h($userReview['comment'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary btn-small"><?= $userReview ? 'Update Review' : 'Submit Review' ?></button>
      </form>
    </div>
  <?php else: ?>
    <p class="empty-msg"><a href="login.php">Login</a> to write a review.</p>
  <?php endif; ?>

  <div class="review-list">
    <?php if (empty($reviews)): ?>
      <p class="empty-msg">No reviews yet. Be the first to review this product!</p>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="review-item">
        <div class="review-head">
          <strong><?= h($r['user_name']) ?></strong>
          <?= renderStars($r['rating']) ?>
          <span class="review-date"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
        </div>
        <?php if (!empty($r['comment'])): ?>
          <p class="review-comment"><?= nl2br(h($r['comment'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($related)): ?>
<section class="product-section">
  <h2>Customers Also Bought</h2>
  <div class="product-grid">
    <?php foreach ($related as $p): ?>
      <?php include 'includes/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
