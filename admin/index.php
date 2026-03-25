<?php
// ============================================================
// LUXEBYLUCIA — Admin Dashboard
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Stats
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status='paid'")->fetchColumn() ?? 0;
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.*, u.first_name, u.last_name
    FROM orders o JOIN users u ON o.user_id=u.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();

// Low stock
$lowStock = $pdo->query("
    SELECT p.*, c.name AS category_name FROM products p
    JOIN categories c ON p.category_id=c.id
    WHERE p.stock <= 5 AND p.status='active'
    ORDER BY p.stock ASC LIMIT 6
")->fetchAll();

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — LuxeByLucia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
<style>
body { padding-top:0 !important; }
.navbar { display:none; }
</style>
</head>
<body>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div style="padding:24px 24px 32px; text-align:center; border-bottom:1px solid var(--border)">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="LuxeByLucia" style="height:50px; margin:0 auto 8px">
            <div style="font-size:11px; letter-spacing:2px; color:var(--gold); text-transform:uppercase">Admin Panel</div>
        </div>
        <nav class="admin-nav" style="padding-top:16px">
            <a href="index.php" class="active"><i class="fa fa-chart-bar"></i> Dashboard</a>
            <a href="products.php"><i class="fa fa-gem"></i> Products</a>
            <a href="orders.php"><i class="fa fa-box"></i> Orders</a>
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <a href="categories.php"><i class="fa fa-tags"></i> Categories</a>
            <a href="reviews.php"><i class="fa fa-star"></i> Reviews</a>
            <div style="border-top:1px solid var(--border); margin:16px 0"></div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fa fa-home"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px">
            <div>
                <h1 style="font-family:var(--font-serif); font-size:2rem; font-weight:300">Dashboard</h1>
                <p style="color:var(--white-muted); font-size:13px">Welcome back, <?= e($_SESSION['first_name']) ?></p>
            </div>
            <a href="products.php?action=new" class="btn btn-gold btn-sm">
                <i class="fa fa-plus"></i> Add Product
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-shopping-bag"></i></div>
                <div class="stat-val"><?= number_format($totalOrders) ?></div>
                <div class="stat-name">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-naira-sign"></i></div>
                <div class="stat-val">₦<?= number_format($totalRevenue) ?></div>
                <div class="stat-name">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-gem"></i></div>
                <div class="stat-val"><?= number_format($totalProducts) ?></div>
                <div class="stat-name">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-users"></i></div>
                <div class="stat-val"><?= number_format($totalUsers) ?></div>
                <div class="stat-name">Registered Users</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:24px">

            <!-- Recent Orders -->
            <div class="admin-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px">
                    <h3 style="font-family:var(--font-serif); font-size:1.3rem">Recent Orders</h3>
                    <a href="orders.php" style="font-size:12px; color:var(--gold); letter-spacing:1px">View All →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td style="color:var(--gold)"><?= e($o['order_number']) ?></td>
                            <td><?= e($o['first_name'].' '.$o['last_name']) ?></td>
                            <td>₦<?= number_format($o['total']) ?></td>
                            <td><span class="status-badge status-<?= $o['order_status'] ?>"><?= ucfirst($o['order_status']) ?></span></td>
                            <td><span class="status-badge status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                            <td>
                                <a href="orders.php?id=<?= $o['id'] ?>" style="color:var(--gold); font-size:12px">Manage →</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Low Stock Alert -->
            <div class="admin-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px">
                    <h3 style="font-family:var(--font-serif); font-size:1.3rem">Low Stock</h3>
                    <a href="products.php" style="font-size:12px; color:var(--gold); letter-spacing:1px">Manage →</a>
                </div>
                <?php if (empty($lowStock)): ?>
                <p style="color:var(--white-muted); font-size:13px">All products are well stocked! ✓</p>
                <?php else: ?>
                <?php foreach ($lowStock as $lp):
                    $limgs = getProductImages($lp['images']);
                ?>
                <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-soft)">
                    <img src="<?= e($limgs[0]) ?>" alt="<?= e($lp['name']) ?>"
                         style="width:44px; height:55px; object-fit:cover; border-radius:var(--radius); background:var(--black-mid)"
                         onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=88&fit=crop'">
                    <div style="flex:1">
                        <div style="font-size:13px; font-weight:500; margin-bottom:2px"><?= e($lp['name']) ?></div>
                        <div style="font-size:11px; color:var(--white-muted)"><?= e($lp['category_name']) ?></div>
                    </div>
                    <span style="font-size:12px; color:<?= $lp['stock'] === 0 ? 'var(--red)' : 'var(--gold)' ?>; font-weight:700">
                        <?= $lp['stock'] === 0 ? 'OUT' : $lp['stock'].' left' ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
