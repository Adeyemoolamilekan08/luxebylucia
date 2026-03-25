<?php
// ============================================================
// LUXEBYLUCIA — Product Detail Page
// ============================================================
require_once __DIR__ . '/includes/header.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect('/shop.php');

// Get product
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 'active'
");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) { setFlash('error', 'Product not found.'); redirect('/shop.php'); }

$imgs     = getProductImages($product['images']);
$price    = $product['sale_price'] ?? $product['price'];
$rating   = getAverageRating($pdo, $product['id']);
$inWish   = isInWishlist($pdo, $product['id']);

// Reviews
$reviewStmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$product['id']]);
$reviews = $reviewStmt->fetchAll();

// Rating breakdown
$breakdownStmt = $pdo->prepare("
    SELECT rating, COUNT(*) as cnt FROM reviews
    WHERE product_id = ? AND status='approved'
    GROUP BY rating ORDER BY rating DESC
");
$breakdownStmt->execute([$product['id']]);
$breakdown = $breakdownStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    requireLogin();
    $ratingVal = (int)($_POST['rating'] ?? 0);
    $comment   = trim($_POST['comment'] ?? '');

    if ($ratingVal < 1 || $ratingVal > 5) {
        setFlash('error', 'Please select a rating between 1 and 5.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, user_id, rating, comment)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)
            ");
            $stmt->execute([$product['id'], $_SESSION['user_id'], $ratingVal, $comment]);
            setFlash('success', 'Thank you for your review!');
        } catch (PDOException $e) {
            setFlash('error', 'Could not submit review. Please try again.');
        }
    }
    header("Location: product.php?slug={$slug}#reviews");
    exit;
}

// Related products
$relatedStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.status='active'
    ORDER BY RAND() LIMIT 4
");
$relatedStmt->execute([$product['category_id'], $product['id']]);
$related = $relatedStmt->fetchAll();

$pageTitle = e($product['name']);
?>

<!-- Breadcrumb -->
<div style="padding:100px 0 0; background:var(--black)">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> <span>›</span>
            <a href="shop.php">Shop</a> <span>›</span>
            <a href="shop.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a>
            <span>›</span>
            <span style="color:var(--gold)"><?= e($product['name']) ?></span>
        </div>
    </div>
</div>

<!-- Product Detail -->
<section style="background:var(--black); padding:40px 0 100px">
    <div class="container">
        <div class="product-layout">

            <!-- Gallery -->
            <div class="product-gallery">
                <div class="gallery-main">
                    <img id="galleryMain" src="<?= e($imgs[0]) ?>" alt="<?= e($product['name']) ?>"
                         style="transition:opacity 0.2s ease"
                         onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=600&h=800&fit=crop'">
                </div>
                <?php if (count($imgs) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($imgs as $i => $img): ?>
                    <div class="gallery-thumb <?= $i===0?'active':'' ?>" data-src="<?= e($img) ?>">
                        <img src="<?= e($img) ?>" alt="Image <?= $i+1 ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=100&h=130&fit=crop'">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="product-detail-info">
                <p class="product-category"><?= e($product['category_name']) ?></p>
                <h1 class="product-detail-name"><?= e($product['name']) ?></h1>

                <!-- Rating Summary -->
                <?php if ($rating['count'] > 0): ?>
                <div class="product-rating" style="margin-bottom:0">
                    <?= renderStars(round($rating['avg'])) ?>
                    <span class="rating-count">
                        <?= number_format($rating['avg'], 1) ?> (<?= (int)$rating['count'] ?> reviews)
                    </span>
                </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="product-detail-price">
                    <span class="detail-price"><?= formatPrice($price) ?></span>
                    <?php if ($product['sale_price']): ?>
                    <span class="detail-original"><?= formatPrice($product['price']) ?></span>
                    <?php
                    $save = round((1 - ($product['sale_price'] / $product['price'])) * 100);
                    ?>
                    <span class="save-badge">Save <?= $save ?>%</span>
                    <?php endif; ?>
                </div>

                <hr class="product-divider">

                <!-- Description -->
                <?php if ($product['description']): ?>
                <p class="product-description"><?= nl2br(e($product['description'])) ?></p>
                <?php endif; ?>

                <!-- Gender -->
                <div style="margin-bottom:20px">
                    <span style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--white-muted)">For: </span>
                    <span style="font-size:13px; color:var(--gold); text-transform:capitalize"><?= e($product['gender']) ?></span>
                </div>

                <!-- Stock -->
                <div style="margin-bottom:24px; display:flex; align-items:center; gap:8px">
                    <?php if ($product['stock'] > 0): ?>
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block"></span>
                    <span style="font-size:13px; color:var(--green)">
                        In Stock <?= $product['stock'] < 5 ? '— Only '.$product['stock'].' left!' : '' ?>
                    </span>
                    <?php else: ?>
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--red);display:inline-block"></span>
                    <span style="font-size:13px; color:var(--red)">Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Qty & Cart -->
                <?php if ($product['stock'] > 0): ?>
                <div class="qty-selector" style="margin-bottom:24px">
                    <button class="qty-btn" data-action="minus">−</button>
                    <input type="number" class="qty-input" id="productQty" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                    <button class="qty-btn" data-action="plus">+</button>
                </div>

                <div class="add-to-cart-section">
                    <button class="btn btn-gold btn-lg" onclick="addProductToCart()">
                        <i class="fa fa-shopping-bag"></i> Add to Bag
                    </button>
                    <button class="btn btn-icon btn-ghost <?= $inWish ? 'wishlisted' : '' ?>"
                            id="wishBtn"
                            onclick="toggleWishlist(<?= $product['id'] ?>, this)"
                            style="width:56px; height:56px; border-radius:var(--radius); font-size:1.2rem;"
                            title="<?= $inWish ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                        <i class="fa fa-heart" style="<?= $inWish ? 'color:var(--gold)' : '' ?>"></i>
                    </button>
                </div>
                <?php else: ?>
                <button class="btn btn-ghost btn-lg btn-full" disabled style="opacity:0.5;cursor:not-allowed">
                    <i class="fa fa-times-circle"></i> Out of Stock
                </button>
                <?php endif; ?>

                <hr class="product-divider">

                <!-- Meta Info -->
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--white-muted)">
                    <div><strong style="color:var(--white);letter-spacing:1px">Category:</strong>
                        <a href="shop.php?category=<?= e($product['category_slug']) ?>" style="color:var(--gold)"><?= e($product['category_name']) ?></a>
                    </div>
                    <div>
                        <strong style="color:var(--white);letter-spacing:1px">Share:</strong>
                        <span style="display:inline-flex; gap:12px; margin-left:8px">
                            <a href="#" style="color:var(--white-muted)"><i class="fab fa-whatsapp"></i></a>
                            <a href="#" style="color:var(--white-muted)"><i class="fab fa-instagram"></i></a>
                            <a href="#" style="color:var(--white-muted)"><i class="fab fa-twitter"></i></a>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── REVIEWS ────────────────────────────────────────────── -->
        <div class="reviews-section" id="reviews">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px">
                <h2 style="font-family:var(--font-serif); font-size:2rem; font-weight:300">
                    Customer <em>Reviews</em>
                </h2>
                <?php if (isLoggedIn()): ?>
                <a href="#review-form" class="btn btn-outline btn-sm">Write a Review</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($reviews)): ?>
            <!-- Summary -->
            <div class="review-summary">
                <div>
                    <div class="avg-score"><?= number_format($rating['avg'], 1) ?></div>
                    <?= renderStars(round($rating['avg'])) ?>
                    <div class="avg-out-of">out of 5 (<?= (int)$rating['count'] ?> reviews)</div>
                </div>
                <div class="rating-bars">
                    <?php for ($s = 5; $s >= 1; $s--):
                        $cnt  = $breakdown[$s] ?? 0;
                        $pct  = $rating['count'] > 0 ? ($cnt / $rating['count'] * 100) : 0;
                    ?>
                    <div class="rating-bar-row">
                        <span style="width:10px; color:var(--white-muted)"><?= $s ?></span>
                        <i class="fa fa-star" style="color:var(--gold); font-size:11px"></i>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span style="width:24px; color:var(--white-muted)"><?= $cnt ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Review List -->
            <?php foreach ($reviews as $rev): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar"><?= strtoupper(substr($rev['first_name'],0,1)) ?></div>
                        <div>
                            <div class="reviewer-name"><?= e($rev['first_name'].' '.$rev['last_name']) ?></div>
                            <?= renderStars($rev['rating']) ?>
                        </div>
                    </div>
                    <span class="review-date"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                </div>
                <?php if ($rev['comment']): ?>
                <p style="color:var(--white-muted); font-size:14px; line-height:1.7"><?= e($rev['comment']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div style="padding:40px; text-align:center; color:var(--white-muted); border:1px solid var(--border-soft); border-radius:var(--radius-md)">
                No reviews yet. Be the first to review this product!
            </div>
            <?php endif; ?>

            <!-- Review Form -->
            <?php if (isLoggedIn()): ?>
            <form class="review-form" method="POST" id="review-form">
                <h3>Share Your Experience</h3>
                <div>
                    <p style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--white-muted); margin-bottom:12px">Your Rating</p>
                    <div class="star-picker">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <span class="star-pick" data-val="<?= $s ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>
                <div class="form-group">
                    <label>Your Review (Optional)</label>
                    <textarea name="comment" class="form-control" rows="4"
                              placeholder="Tell others what you think about this product..."></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-gold" style="margin-top:8px">
                    <i class="fa fa-paper-plane"></i> Submit Review
                </button>
            </form>
            <?php else: ?>
            <div style="text-align:center; padding:32px; border:1px solid var(--border-soft); border-radius:var(--radius-md); margin-top:24px">
                <p style="color:var(--white-muted); margin-bottom:16px">Please login to leave a review</p>
                <a href="login.php?redirect=product.php?slug=<?= e($slug) ?>" class="btn btn-gold btn-sm">Login to Review</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related)): ?>
        <div style="margin-top:100px">
            <div class="section-header">
                <p class="section-label">✦ You May Also Like</p>
                <h2 class="section-title">Related <em>Pieces</em></h2>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $rp):
                    $rimgs = getProductImages($rp['images']);
                    $rprice = $rp['sale_price'] ?? $rp['price'];
                ?>
                <article class="product-card">
                    <div class="product-image">
                        <a href="product.php?slug=<?= e($rp['slug']) ?>">
                            <img src="<?= e($rimgs[0]) ?>" alt="<?= e($rp['name']) ?>" loading="lazy"
                                 onerror="this.src='https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop'">
                        </a>
                    </div>
                    <div class="product-info">
                        <p class="product-category"><?= e($rp['category_name']) ?></p>
                        <h3 class="product-name"><a href="product.php?slug=<?= e($rp['slug']) ?>"><?= e($rp['name']) ?></a></h3>
                        <div class="product-price"><span class="price-current"><?= formatPrice($rprice) ?></span></div>
                        <button class="btn-add-cart" onclick="addToCart(<?= $rp['id'] ?>)">
                            <i class="fa fa-shopping-bag"></i> Add to Bag
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
function addProductToCart() {
    const qty = parseInt(document.getElementById('productQty').value) || 1;
    addToCart(<?= $product['id'] ?>, qty);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
