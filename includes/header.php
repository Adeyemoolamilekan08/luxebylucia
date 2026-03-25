<?php
// ============================================================
// LUXEBYLUCIA - Header Include
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$cartCount     = getCartCount($pdo);
$wishlistCount = getWishlistCount($pdo);
$categories    = getCategories($pdo);
$flash         = getFlash();
$currentPage   = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>LuxeByLucia — Elegance · Style · Exclusivity</title>
    <meta name="description" content="<?= isset($pageDesc) ? e($pageDesc) : 'Discover luxury fashion and accessories at LuxeByLucia. Premium quality. Timeless elegance.' ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    
    <?= isset($extraHead) ? $extraHead : '' ?>
</head>
<body class="<?= str_replace('.php', '', $currentPage) ?>-page">

<!-- Loader -->
<div id="page-loader">
    <div class="loader-logo">
        <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="LuxeByLucia">
        <div class="loader-bar"><span></span></div>
    </div>
</div>

<!-- Flash Toast -->
<?php if ($flash): ?>
<div class="toast toast-<?= e($flash['type']) ?>" id="flash-toast">
    <i class="fa <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <span><?= e($flash['message']) ?></span>
    <button onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
</div>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <!-- Mobile Menu Toggle -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

        <!-- Logo -->
        <a href="<?= SITE_URL ?>/index.php" class="nav-logo">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="LuxeByLucia">
        </a>

        <!-- Nav Links -->
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">Home</a></li>
                <li class="has-dropdown">
                    <a href="<?= SITE_URL ?>/shop.php">Shop <i class="fa fa-chevron-down"></i></a>
                    <ul class="dropdown">
                        <li><a href="<?= SITE_URL ?>/shop.php">All Products</a></li>
                        <?php foreach ($categories as $cat): ?>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="<?= SITE_URL ?>/shop.php?featured=1">Collections</a></li>
                <li><a href="<?= SITE_URL ?>/index.php#contact">Contact</a></li>
            </ul>
        </div>

        <!-- Nav Actions -->
        <div class="nav-actions">
            <!-- Search Toggle -->
            <button class="nav-action-btn" id="searchToggle" aria-label="Search">
                <i class="fa fa-search"></i>
            </button>

            <!-- Wishlist -->
            <a href="<?= SITE_URL ?>/wishlist.php" class="nav-action-btn" aria-label="Wishlist">
                <i class="fa fa-heart"></i>
                <?php if ($wishlistCount > 0): ?>
                <span class="badge"><?= $wishlistCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Cart -->
            <a href="<?= SITE_URL ?>/cart.php" class="nav-action-btn" aria-label="Cart">
                <i class="fa fa-shopping-bag"></i>
                <?php if ($cartCount > 0): ?>
                <span class="badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <!-- User Menu -->
            <?php if (isLoggedIn()): ?>
            <div class="user-dropdown">
                <button class="nav-action-btn">
                    <i class="fa fa-user"></i>
                </button>
                <ul class="user-menu">
                    <li><span class="user-name">Hello, <?= e($_SESSION['first_name']) ?></span></li>
                    <li><a href="<?= SITE_URL ?>/orders.php"><i class="fa fa-box"></i> My Orders</a></li>
                    <li><a href="<?= SITE_URL ?>/wishlist.php"><i class="fa fa-heart"></i> Wishlist</a></li>
                    <?php if (isAdmin()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/index.php"><i class="fa fa-cog"></i> Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php" class="nav-action-btn" aria-label="Login">
                <i class="fa fa-user"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar" id="searchBar">
        <div class="search-inner">
            <input type="text" id="searchInput" placeholder="Search products, categories..." autocomplete="off">
            <button id="closeSearch"><i class="fa fa-times"></i></button>
        </div>
        <div class="search-results" id="searchResults"></div>
    </div>
</nav>
