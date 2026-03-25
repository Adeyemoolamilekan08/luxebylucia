<?php
// ============================================================
// LUXEBYLUCIA — Admin: Orders Management
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

requireAdmin();

$message = '';

// ── UPDATE ORDER STATUS ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId    = (int)$_POST['order_id'];
    $newStatus  = $_POST['order_status'] ?? '';
    $validStatuses = ['pending','processing','shipped','delivered','cancelled'];

    if (in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE orders SET order_status=? WHERE id=?")->execute([$newStatus, $orderId]);

        // Send email notification safely (no crash on XAMPP)
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
        $stmt->execute([$orderId]);
        $updatedOrder = $stmt->fetch();

        $emailNote = '';
        if ($updatedOrder) {
            $sent = sendOrderStatusUpdate($updatedOrder);
            if (EMAIL_MODE === "log") {
                $emailNote = " &mdash; Email saved to <code>/emails/log.txt</code>";
            } elseif ($sent) {
                $emailNote = " &mdash; Customer notified by email.";
            } else {
                $emailNote = " &mdash; &#9888; Email failed. Check SMTP settings in email.php.";
            }
        }
        $message = "Order status updated to <strong>" . ucfirst($newStatus) . "</strong>" . $emailNote;
    }
}

// ── SINGLE ORDER VIEW ─────────────────────────────────────────
$singleOrder = null;
$orderItems  = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name, u.email AS user_email FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $stmt->execute([(int)$_GET['id']]);
    $singleOrder = $stmt->fetch();

    if ($singleOrder) {
        $stmt = $pdo->prepare("SELECT oi.*, p.images, p.slug FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $stmt->execute([$singleOrder['id']]);
        $orderItems = $stmt->fetchAll();
    }
}

// ── LIST ──────────────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = $statusFilter ? "WHERE o.order_status='$statusFilter'" : '';
$total  = (int)$pdo->query("SELECT COUNT(*) FROM orders o $where")->fetchColumn();
$orders = $pdo->query("
    SELECT o.*, u.first_name, u.last_name
    FROM orders o JOIN users u ON o.user_id=u.id
    $where
    ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset
")->fetchAll();

$statusCounts = $pdo->query("SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders — LuxeByLucia Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
<style>body{padding-top:0}.navbar{display:none}</style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div style="padding:24px 24px 32px;text-align:center;border-bottom:1px solid var(--border)">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="LuxeByLucia" style="height:50px;margin:0 auto 8px">
            <div style="font-size:11px;letter-spacing:2px;color:var(--gold);text-transform:uppercase">Admin Panel</div>
        </div>
        <nav class="admin-nav" style="padding-top:16px">
            <a href="index.php"><i class="fa fa-chart-bar"></i> Dashboard</a>
            <a href="products.php"><i class="fa fa-gem"></i> Products</a>
            <a href="orders.php" class="active"><i class="fa fa-box"></i> Orders</a>
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <a href="categories.php"><i class="fa fa-tags"></i> Categories</a>
            <a href="reviews.php"><i class="fa fa-star"></i> Reviews</a>
            <div style="border-top:1px solid var(--border);margin:16px 0"></div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fa fa-home"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="admin-main">

        <?php if ($message): ?>
        <div style="background:rgba(76,175,77,0.1);border:1px solid var(--green);border-radius:var(--radius);padding:14px;margin-bottom:24px;color:var(--green)">
            <i class="fa fa-check-circle"></i> <?= e($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($singleOrder): ?>
        <!-- ── SINGLE ORDER ──────────────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
            <div>
                <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">Order Details</h1>
                <p style="color:var(--gold);font-size:13px;letter-spacing:1px"><?= e($singleOrder['order_number']) ?></p>
            </div>
            <a href="orders.php" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
            <div>
                <div class="admin-card" style="margin-bottom:20px">
                    <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:16px;color:var(--gold)">Items Ordered</h3>
                    <?php foreach ($orderItems as $item):
                        $oimgs = getProductImages($item['images'] ?? '[]');
                    ?>
                    <div style="display:flex;align-items:center;gap:16px;padding:12px 0;border-bottom:1px solid var(--border-soft)">
                        <img src="<?= e($oimgs[0]) ?>" alt="<?= e($item['product_name']) ?>"
                             style="width:52px;height:65px;object-fit:cover;border-radius:var(--radius);background:var(--black-mid)"
                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=100&fit=crop'">
                        <div style="flex:1">
                            <div style="font-weight:500"><?= e($item['product_name']) ?></div>
                            <div style="font-size:12px;color:var(--white-muted)">Qty: <?= $item['quantity'] ?> × ₦<?= number_format($item['price']) ?></div>
                        </div>
                        <div style="color:var(--gold);font-family:var(--font-serif)">₦<?= number_format($item['price'] * $item['quantity']) ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
                        <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--white-muted)">
                            <span>Subtotal</span><span>₦<?= number_format($singleOrder['subtotal']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--white-muted)">
                            <span>Shipping</span><span>₦<?= number_format($singleOrder['shipping']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:600;padding-top:12px;border-top:1px solid var(--border)">
                            <span>Total</span><span style="color:var(--gold)">₦<?= number_format($singleOrder['total']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Update Status -->
                <div class="admin-card">
                    <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:16px;color:var(--gold)">Update Order Status</h3>
                    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                        <input type="hidden" name="order_id" value="<?= $singleOrder['id'] ?>">
                        <div class="form-group" style="flex:1;min-width:200px">
                            <label>Order Status</label>
                            <select name="order_status" class="form-control">
                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $singleOrder['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-gold" style="white-space:nowrap">
                            <i class="fa fa-check"></i> Update & Notify Customer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Customer & Billing Info -->
            <div>
                <div class="admin-card" style="margin-bottom:20px">
                    <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:16px;color:var(--gold)">Customer</h3>
                    <p style="font-size:14px;margin-bottom:8px"><strong><?= e($singleOrder['first_name'].' '.$singleOrder['last_name']) ?></strong></p>
                    <p style="font-size:13px;color:var(--white-muted)"><?= e($singleOrder['user_email']) ?></p>
                </div>
                <div class="admin-card" style="margin-bottom:20px">
                    <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:16px;color:var(--gold)">Billing & Delivery</h3>
                    <div style="font-size:13px;color:var(--white-muted);line-height:2">
                        <strong style="color:var(--white)"><?= e($singleOrder['billing_name']) ?></strong><br>
                        <?= e($singleOrder['billing_phone'] ?? '') ?><br>
                        <?= e($singleOrder['billing_address']) ?><br>
                        <?= e($singleOrder['billing_city'] ?? '') ?><?= $singleOrder['billing_state'] ? ', '.e($singleOrder['billing_state']) : '' ?>
                    </div>
                </div>
                <div class="admin-card">
                    <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:16px;color:var(--gold)">Payment</h3>
                    <div style="font-size:13px;color:var(--white-muted);line-height:2">
                        <div style="display:flex;justify-content:space-between">
                            <span>Method:</span>
                            <span style="color:var(--white)">Paystack</span>
                        </div>
                        <div style="display:flex;justify-content:space-between">
                            <span>Status:</span>
                            <span class="status-badge status-<?= $singleOrder['payment_status'] ?>"><?= ucfirst($singleOrder['payment_status']) ?></span>
                        </div>
                        <?php if ($singleOrder['payment_reference']): ?>
                        <div style="margin-top:8px;word-break:break-all">
                            <span>Ref: </span>
                            <span style="color:var(--white);font-size:11px"><?= e($singleOrder['payment_reference']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ── ORDER LIST ─────────────────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
            <div>
                <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">Orders</h1>
                <p style="color:var(--white-muted);font-size:13px"><?= $total ?> orders total</p>
            </div>
        </div>

        <!-- Filter tabs -->
        <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap">
            <a href="orders.php" class="btn btn-sm <?= !$statusFilter ? 'btn-gold' : 'btn-ghost' ?>">All</a>
            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
            <a href="orders.php?status=<?= $s ?>" class="btn btn-sm <?= $statusFilter===$s ? 'btn-gold' : 'btn-ghost' ?>">
                <?= ucfirst($s) ?> <?= isset($statusCounts[$s]) ? "({$statusCounts[$s]})" : '' ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="color:var(--gold);font-weight:500"><?= e($o['order_number']) ?></td>
                        <td><?= e($o['first_name'].' '.$o['last_name']) ?></td>
                        <td style="font-size:12px;color:var(--white-muted)"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        <td>₦<?= number_format($o['total']) ?></td>
                        <td><span class="status-badge status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                        <td><span class="status-badge status-<?= $o['order_status'] ?>"><?= ucfirst($o['order_status']) ?></span></td>
                        <td>
                            <a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">
                                <i class="fa fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php $totalPages = ceil($total / $perPage); if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:24px">
                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
                   class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
