<?php
// ============================================================
// LUXEBYLUCIA — Admin: Users
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$total   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$users   = $pdo->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users — LuxeByLucia Admin</title>
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
            <a href="users.php" class="active"><i class="fa fa-users"></i> Users</a>
            <a href="categories.php"><i class="fa fa-tags"></i> Categories</a>
            <a href="reviews.php"><i class="fa fa-star"></i> Reviews</a>
            <div style="border-top:1px solid var(--border);margin:16px 0"></div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fa fa-home"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
            <div>
                <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">Users</h1>
                <p style="color:var(--white-muted);font-size:13px"><?= $total ?> registered users</p>
            </div>
        </div>
        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $orderCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
                        $orderCount->execute([$u['id']]);
                        $oc = $orderCount->fetchColumn();
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;color:var(--gold);font-weight:600">
                                    <?= strtoupper(substr($u['first_name'],0,1)) ?>
                                </div>
                                <?= e($u['first_name'].' '.$u['last_name']) ?>
                            </div>
                        </td>
                        <td style="color:var(--white-muted)"><?= e($u['email']) ?></td>
                        <td style="color:var(--white-muted)"><?= e($u['phone'] ?? '—') ?></td>
                        <td style="font-size:12px;color:var(--white-muted)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="orders.php" style="color:var(--gold)"><?= $oc ?> order<?= $oc !== 1 ? 's' : '' ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php $pages = ceil($total/$perPage); if ($pages>1): ?>
            <div class="pagination" style="margin-top:24px">
                <?php for ($i=1;$i<=$pages;$i++): ?>
                <a href="?page=<?= $i ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
