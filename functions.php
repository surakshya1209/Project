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
