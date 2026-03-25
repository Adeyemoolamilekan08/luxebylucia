<?php
// ============================================================
// LUXEBYLUCIA — Wishlist Page
// ============================================================
$pageTitle = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count,
           w.created_at AS wishlisted_at
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON r.product_id = p.id AND r.status='approved'
    WHERE w.user_id = ?
    GROUP BY p.id, w.created_at
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
?>

<div style="padding:120px 0 0; background:var(--black)">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> <span>›</span>
            <span style="color:var(--gold)">Wishlist</span>
        </div>
        <h1 class="section-title" style="margin-bottom:12px">My <em>Wishlist</em></h1>
        <p style="color:var(--white-muted); margin-bottom:48px"><?= count($items) ?> saved item<?= count($items) !== 1 ? 's' : '' ?></p>
    </div>
</div>

<section style="background:var(--black); padding:0 0 100px">
    <div class="container">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="fa fa-heart"></i>
            <h3>Your wishlist is empty</h3>
            <p>Save items you love by clicking the heart icon on any product.</p>
            <a href="shop.php" class="btn btn-gold btn-lg"><i class="fa fa-gem"></i> Start Shopping</a>
        </div>
        <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($items as $p):
                $imgs  = getProductImages($p['images']);
                $price = $p['sale_price'] ?? $p['price'];
            ?>
            <article class="product-card" id="wish-item-<?= $p['id'] ?>">
                <div class="product-image">
                    <a href="product.php?slug=<?= e($p['slug']) ?>">
                        <img src="<?= e($imgs[0]) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=400&h=500&fit=crop'">
                    </a>
                    <div class="product-badges">
                        <?php if ($p['sale_price']): ?>
                        <span class="badge-tag badge-sale">Sale</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions" style="opacity:1; transform:none">
                        <button class="action-btn wishlisted"
                                onclick="removeFromWishlist(<?= $p['id'] ?>, this)"
                                title="Remove from Wishlist">
                            <i class="fa fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <p class="product-category"><?= e($p['category_name']) ?></p>
                    <h3 class="product-name">
                        <a href="product.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a>
                    </h3>
                    <?php if ($p['review_count'] > 0): ?>
                    <div class="product-rating">
                        <?= renderStars(round($p['avg_rating'])) ?>
                        <span class="rating-count">(<?= (int)$p['review_count'] ?>)</span>
                    </div>
                    <?php endif; ?>
                    <div class="product-price">
                        <span class="price-current"><?= formatPrice($price) ?></span>
                        <?php if ($p['sale_price']): ?>
                        <span class="price-original"><?= formatPrice($p['price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Added date -->
                    <p style="font-size:11px; color:var(--white-muted); margin-bottom:12px">
                        Saved <?= date('M d, Y', strtotime($p['wishlisted_at'])) ?>
                    </p>

                    <?php if ($p['stock'] > 0): ?>
                    <div style="display:flex; gap:8px">
                        <button class="btn-add-cart" style="flex:1" onclick="moveToCart(<?= $p['id'] ?>, this)">
                            <i class="fa fa-shopping-bag"></i> Move to Cart
                        </button>
                    </div>
                    <?php else: ?>
                    <button class="btn-add-cart" disabled style="opacity:0.4">Out of Stock</button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:48px">
            <a href="shop.php" class="btn btn-outline">
                <i class="fa fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
async function removeFromWishlist(productId, btn) {
    await toggleWishlist(productId, btn);
    // Remove card from UI
    setTimeout(() => {
        const card = document.getElementById('wish-item-' + productId);
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            card.style.transition = 'all 0.4s ease';
            setTimeout(() => card.remove(), 400);
        }
    }, 500);
}

async function moveToCart(productId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    try {
        const res = await fetch('/luxebylucia/includes/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=move_to_cart&product_id=${productId}`
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            const card = document.getElementById('wish-item-' + productId);
            if (card) {
                card.style.opacity = '0'; card.style.transform = 'scale(0.9)';
                card.style.transition = 'all 0.4s ease';
                setTimeout(() => card.remove(), 400);
            }
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-shopping-bag"></i> Move to Cart';
        }
    } catch(e) {
        showToast('Something went wrong', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
