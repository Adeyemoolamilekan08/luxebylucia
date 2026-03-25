<?php
// ============================================================
// LUXEBYLUCIA — Admin: Reviews
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'approve') {
        $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([$id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    }
    header('Location: reviews.php');
    exit;
}

$reviews = $pdo->query("
    SELECT r.*, u.first_name, u.last_name, p.name AS product_name
    FROM reviews r
    JOIN users u ON r.user_id=u.id
    JOIN products p ON r.product_id=p.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reviews — LuxeByLucia Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
<style>body{padding-top:0}.navbar{display:none}</style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div style="padding:24px 24px 32px;text-align:center;border-bottom:1px solid var(--border)">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" style="height:50px;margin:0 auto 8px">
            <div style="font-size:11px;letter-spacing:2px;color:var(--gold);text-transform:uppercase">Admin Panel</div>
        </div>
        <nav class="admin-nav" style="padding-top:16px">
            <a href="index.php"><i class="fa fa-chart-bar"></i> Dashboard</a>
            <a href="products.php"><i class="fa fa-gem"></i> Products</a>
            <a href="orders.php"><i class="fa fa-box"></i> Orders</a>
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <a href="categories.php"><i class="fa fa-tags"></i> Categories</a>
            <a href="reviews.php" class="active"><i class="fa fa-star"></i> Reviews</a>
            <div style="border-top:1px solid var(--border);margin:16px 0"></div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fa fa-home"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div style="margin-bottom:28px">
            <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">Product Reviews</h1>
            <p style="color:var(--white-muted);font-size:13px"><?= count($reviews) ?> reviews</p>
        </div>
        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr><th>Reviewer</th><th>Product</th><th>Rating</th><th>Comment</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
                        <td style="color:var(--gold)"><?= e($r['product_name']) ?></td>
                        <td><?= renderStars($r['rating']) ?></td>
                        <td style="max-width:200px;font-size:12px;color:var(--white-muted)"><?= $r['comment'] ? e(substr($r['comment'],0,80)).'...' : '—' ?></td>
                        <td style="font-size:12px;color:var(--white-muted)"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $r['status']==='approved'?'delivered':($r['status']==='rejected'?'cancelled':'pending') ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <?php if ($r['status'] !== 'approved'): ?>
                                <a href="reviews.php?action=approve&id=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(76,175,77,0.1);border:1px solid var(--green);color:var(--green)">
                                    <i class="fa fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'rejected'): ?>
                                <a href="reviews.php?action=reject&id=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(201,168,76,0.1);border:1px solid var(--gold);color:var(--gold)">
                                    <i class="fa fa-ban"></i>
                                </a>
                                <?php endif; ?>
                                <a href="reviews.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm"
                                   style="background:rgba(224,82,82,0.1);border:1px solid var(--red);color:var(--red)"
                                   onclick="return confirm('Delete this review?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
