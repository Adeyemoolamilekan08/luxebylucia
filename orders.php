<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ============================================================
// LUXEBYLUCIA — Orders & Tracking Page
// ============================================================
$pageTitle = 'My Orders';
requireLogin();
require_once __DIR__ . '/includes/header.php';

// Single order view
$orderNum = trim($_GET['order'] ?? '');
$singleOrder = null;
$orderItems  = [];

if ($orderNum) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number=? AND user_id=?");
    $stmt->execute([$orderNum, $_SESSION['user_id']]);
    $singleOrder = $stmt->fetch();

    if ($singleOrder) {
        $istmt = $pdo->prepare("SELECT oi.*, p.images, p.slug FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $istmt->execute([$singleOrder['id']]);
        $orderItems = $istmt->fetchAll();
    }
}

// All orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$trackingSteps = [
    'pending'    => ['Ordered',    'fa-clock'],
    'processing' => ['Processing', 'fa-cog'],
    'shipped'    => ['Shipped',    'fa-truck'],
    'delivered'  => ['Delivered',  'fa-check-circle'],
];
$stepKeys = array_keys($trackingSteps);
?>

<div style="padding:120px 0 0; background:var(--black)">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> <span>›</span>
            <a href="orders.php">My Orders</a>
            <?php if ($singleOrder): ?>
            <span>›</span>
            <span style="color:var(--gold)"><?= e($singleOrder['order_number']) ?></span>
            <?php endif; ?>
        </div>
        <h1 class="section-title" style="margin-bottom:48px">
            <?= $singleOrder ? 'Order <em>Details</em>' : 'My <em>Orders</em>' ?>
        </h1>
    </div>
</div>

<section style="background:var(--black); padding:0 0 100px">
    <div class="container">

        <?php if ($singleOrder): ?>
        <!-- ── SINGLE ORDER ──────────────────────────────────────── -->
        <a href="orders.php" style="display:inline-flex; align-items:center; gap:8px; color:var(--white-muted); font-size:13px; margin-bottom:32px; transition:var(--transition);"
           onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--white-muted)'">
            <i class="fa fa-arrow-left"></i> Back to Orders
        </a>

        <div style="display:grid; grid-template-columns:1fr 360px; gap:48px; align-items:start">
            <div>
                <!-- Order Info -->
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-num"><?= e($singleOrder['order_number']) ?></div>
                            <div class="order-date">Placed on <?= date('F j, Y \a\t g:i A', strtotime($singleOrder['created_at'])) ?></div>
                        </div>
                        <span class="status-badge status-<?= $singleOrder['payment_status'] ?>">
                            <?= $singleOrder['payment_status'] === 'paid' ? '✓ Paid' : ucfirst($singleOrder['payment_status']) ?>
                        </span>
                    </div>

                    <!-- Tracking -->
                    <div style="margin-bottom:24px">
                        <h4 style="font-family:var(--font-serif); font-size:1.1rem; margin-bottom:20px; color:var(--gold)">Order Tracking</h4>
                        <div class="tracking-steps">
                            <?php
                            $currentIdx = array_search($singleOrder['order_status'], $stepKeys);
                            foreach ($stepKeys as $idx => $key):
                                if ($key === 'cancelled') continue;
                                $isDone   = $idx < $currentIdx;
                                $isActive = $idx === $currentIdx;
                                $step     = $trackingSteps[$key];
                            ?>
                            <div class="tracking-step <?= $isDone?'done':'' ?> <?= $isActive?'active':'' ?>">
                                <div class="step-dot">
                                    <i class="fa <?= $step[1] ?>"></i>
                                </div>
                                <span class="step-label"><?= $step[0] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="text-align:center; margin-top:16px">
                            <span class="status-badge status-<?= $singleOrder['order_status'] ?>">
                                <?= ucfirst($singleOrder['order_status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Items -->
                    <h4 style="font-family:var(--font-serif); font-size:1.1rem; margin-bottom:16px">Items Ordered</h4>
                    <?php foreach ($orderItems as $item):
                        $oimgs = getProductImages($item['images'] ?? '[]');
                    ?>
                    <div style="display:flex; align-items:center; gap:16px; padding:16px 0; border-top:1px solid var(--border-soft)">
                        <img src="<?= e($oimgs[0]) ?>" alt="<?= e($item['product_name']) ?>"
                             style="width:60px; height:75px; object-fit:cover; border-radius:var(--radius); background:var(--black-mid)"
                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=100&fit=crop'">
                        <div style="flex:1">
                            <div style="font-size:14px; font-weight:500"><?= e($item['product_name']) ?></div>
                            <div style="font-size:12px; color:var(--white-muted)">Qty: <?= $item['quantity'] ?></div>
                        </div>
                        <div style="color:var(--gold); font-family:var(--font-serif)"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div>
                <div class="cart-summary">
                    <h3>Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span class="val"><?= formatPrice($singleOrder['subtotal']) ?></span></div>
                    <div class="summary-row"><span>Shipping</span><span class="val"><?= formatPrice($singleOrder['shipping']) ?></span></div>
                    <div class="summary-row total"><span>Total</span><span class="val"><?= formatPrice($singleOrder['total']) ?></span></div>

                    <hr class="product-divider" style="margin:24px 0">

                    <h4 style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--gold); margin-bottom:16px">Delivery To</h4>
                    <div style="font-size:13px; color:var(--white-muted); line-height:1.8">
                        <strong style="color:var(--white)"><?= e($singleOrder['billing_name']) ?></strong><br>
                        <?= e($singleOrder['billing_address']) ?><br>
                        <?= e($singleOrder['billing_city']) ?><?= $singleOrder['billing_state'] ? ', '.e($singleOrder['billing_state']) : '' ?><br>
                        <?= e($singleOrder['billing_phone'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif (empty($orders)): ?>
        <!-- ── EMPTY ──────────────────────────────────────────────── -->
        <div class="empty-state">
            <i class="fa fa-box"></i>
            <h3>No orders yet</h3>
            <p>Your order history will appear here once you make a purchase.</p>
            <a href="shop.php" class="btn btn-gold btn-lg"><i class="fa fa-gem"></i> Start Shopping</a>
        </div>

        <?php else: ?>
        <!-- ── ALL ORDERS ─────────────────────────────────────────── -->
        <?php foreach ($orders as $o): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-num"><?= e($o['order_number']) ?></div>
                    <div class="order-date"><?= date('M j, Y', strtotime($o['created_at'])) ?></div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <span class="status-badge status-<?= $o['order_status'] ?>"><?= ucfirst($o['order_status']) ?></span>
                    <span class="status-badge status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span>
                </div>
                <div class="order-total"><?= formatPrice($o['total']) ?></div>
                <a href="orders.php?order=<?= urlencode($o['order_number']) ?>" class="btn btn-outline btn-sm">
                    View Details <i class="fa fa-arrow-right"></i>
                </a>
            </div>

            <!-- Mini Tracking -->
            <div class="tracking-steps" style="margin:0; padding:0 24px">
                <?php
                $currentIdx = array_search($o['order_status'], $stepKeys);
                foreach ($stepKeys as $idx => $key):
                    if ($key === 'cancelled') continue;
                    $isDone = $idx < $currentIdx;
                    $isActive = $idx === $currentIdx;
                    $step = $trackingSteps[$key];
                ?>
                <div class="tracking-step <?= $isDone?'done':'' ?> <?= $isActive?'active':'' ?>">
                    <div class="step-dot" style="width:28px;height:28px;font-size:11px">
                        <i class="fa <?= $step[1] ?>"></i>
                    </div>
                    <span class="step-label" style="font-size:9px"><?= $step[0] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
