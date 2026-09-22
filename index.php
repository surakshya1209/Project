<?php
require 'config.php';
require 'functions.php';

$search     = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT p.*, COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE 1=1 ";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?) ";
    $types .= 'ss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($categoryId > 0) {
    $sql .= " AND p.category_id = ? ";
    $types .= 'i';
    $params[] = $categoryId;
}
$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$recommended = getRecommendedProducts($conn, 4);

$pageTitle = 'Home';
require 'includes/header.php';
?>

<section class="hero">
  <br>
  <h1>Everything <span class="highlight">You</span> Need for Learning, All in One Place</h1>
  <p>From school supplies to novels and academic books — your trusted stationery store, now online.</p><br>
  <div class="hero-actions">
    <a href="index.php" class="btn btn-primary">Buy Now</a>
    <a href="#all-products" class="btn btn-outline">Browse Products</a>
  </div>
</section>

<p class="divider-caption">Trusted by students, teachers &amp; families for generations</p><br>

<section class="category-bar">
  <a href="index.php" class="chip <?= $categoryId === 0 ? 'active' : '' ?>">All</a>
  <?php foreach ($categories as $cat): ?>
    <a href="index.php?category=<?= $cat['id'] ?>" class="chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
      <?= h($cat['name']) ?>
    </a>
  <?php endforeach; ?>
</section>

<?php if ($search === '' && $categoryId === 0 && !empty($recommended)): ?>
<section class="product-section">
  <h2>Recommended <span class="highlight">For You</span></h2>
  <div class="product-grid">
    <?php foreach ($recommended as $p): ?>
      <?php include 'includes/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="product-section" id="all-products">
  <h2>
    <?php if ($search !== ''): ?>
      Search results for "<?= h($search) ?>"
    <?php elseif ($categoryId > 0): ?>
      <?= h($categories[array_search($categoryId, array_column($categories, 'id'))]['name'] ?? 'Products') ?>
    <?php else: ?>
      All Products
    <?php endif; ?>
  </h2>

  <?php if (empty($products)): ?>
    <p class="empty-msg">No products found.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <?php include 'includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>