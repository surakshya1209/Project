<?php
/* Helper functions used across the site */

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && !empty($_SESSION['is_admin']);
}


function money($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function getProductRating($conn, $productId) {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt, COALESCE(AVG(rating),0) AS avg_rating
         FROM reviews WHERE product_id = ?"
    );
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'count' => (int)$res['cnt'],
        'avg'   => round((float)$res['avg_rating'], 1)
    ];
}

// Render star icons for a given average rating (0-5)
function renderStars($avg) {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($avg >= $i) {
            $html .= '★';
        } elseif ($avg >= $i - 0.5) {
            $html .= '⯪';
        } else {
            $html .= '☆';
        }
    }
    $html .= '</span>';
    return $html;
}
function getRecommendedProducts($conn, $limit = 4, $excludeId = null, $categoryId = null) {
    $sql = "SELECT p.*,
                   COALESCE(AVG(r.rating),0) AS avg_rating,
                   COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN reviews r ON r.product_id = p.id
            WHERE 1=1 ";
    $types = '';
    $params = [];

    if ($excludeId) {
        $sql .= " AND p.id != ? ";
        $types .= 'i';
        $params[] = $excludeId;
    }
    if ($categoryId) {
        $sql .= " AND p.category_id = ? ";
        $types .= 'i';
        $params[] = $categoryId;
    }

    $sql .= " GROUP BY p.id
              ORDER BY avg_rating DESC, review_count DESC, p.created_at DESC
              LIMIT ?";
    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}
