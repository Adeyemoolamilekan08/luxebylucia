<?php
// ============================================================
// LUXEBYLUCIA - Helper Functions
// ============================================================

/**
 * Format price with currency symbol
 */
function formatPrice($amount) {
    return CURRENCY . number_format($amount, 2);
}

/**
 * Sanitize output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a slug from a string
 */
function slugify($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

/**
 * Generate unique order number
 */
function generateOrderNumber() {
    return 'LBL-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login.php');
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

/**
 * Get cart count for current user
 */
function getCartCount($pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get wishlist count for current user
 */
function getWishlistCount($pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get all categories
 */
function getCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

/**
 * Get product's average rating
 */
function getAverageRating($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

/**
 * Check if product is in wishlist
 */
function isInWishlist($pdo, $productId) {
    if (!isLoggedIn()) return false;
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    return $stmt->fetch() ? true : false;
}

/**
 * Render star rating HTML
 */
function renderStars($rating) {
    $html = '<div class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'star filled' : 'star';
        $html .= "<span class=\"$class\">★</span>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Get product images array
 */
function getProductImages($images) {
    if (empty($images)) return ['assets/images/placeholder.jpg'];
    $decoded = json_decode($images, true);
    return is_array($decoded) ? $decoded : ['assets/images/placeholder.jpg'];
}

/**
 * Set a flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Calculate cart totals
 */
function getCartTotals($pdo) {
    if (!isLoggedIn()) return ['subtotal' => 0, 'shipping' => 0, 'total' => 0, 'items' => []];
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.images, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    
    $subtotal = 0;
    foreach ($items as &$item) {
        $price = $item['sale_price'] ?? $item['price'];
        $item['unit_price'] = $price;
        $item['line_total'] = $price * $item['quantity'];
        $subtotal += $item['line_total'];
        $item['images_arr'] = getProductImages($item['images']);
    }
    
    $shipping = $subtotal > 0 ? SHIPPING_FEE : 0;
    
    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total'    => $subtotal + $shipping
    ];
}
