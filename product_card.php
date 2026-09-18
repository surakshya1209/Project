<?php
/* Expects $p (product row incl. avg_rating, review_count) in scope */
$rating = round((float)($p['avg_rating'] ?? 0), 1);
$count  = (int)($p['review_count'] ?? 0);
?>
<div class="product-card">
  <a href="product.php?id=<?= $p['id'] ?>" class="product-img-link">
    <img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>"
         onerror="this.src='https://via.placeholder.com/300x220?text=PustakBhawan'">
  </a>
  <div class="product-info">
    <a href="product.php?id=<?= $p['id'] ?>" class="product-name"><?= h($p['name']) ?></a>
    <div class="product-rating">
      <?= renderStars($rating) ?>
      <span class="rating-text"><?= $rating ?> (<?= $count ?>)</span>
    </div>
    <div class="product-price"><?= money($p['price']) ?></div>
    <form method="post" action="add_to_cart.php">
      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
      <button type="submit" class="btn btn-small" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
        <?= $p['stock'] <= 0 ? 'Out of stock' : 'Add to Cart' ?>
      </button>
    </form>
  </div>
</div>
