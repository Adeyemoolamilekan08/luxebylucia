<?php
// ============================================================
// LUXEBYLUCIA — Homepage
// ============================================================
$pageTitle = 'Luxury Fashion & Accessories';
$pageDesc  = 'Discover luxury fashion and accessories at LuxeByLucia. Premium quality. Timeless elegance. Shop hair accessories, watches, jewellery and more.';
require_once __DIR__ . '/includes/header.php';

// Featured products
$featuredStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON r.product_id = p.id AND r.status='approved'
    WHERE p.featured=1 AND p.status='active'
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 8
");
$featuredProducts = $featuredStmt->fetchAll();

// New arrivals
$newStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON r.product_id = p.id AND r.status='approved'
    WHERE p.status='active'
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 4
");
$newProducts = $newStmt->fetchAll();
?>

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-particles"></div>

    <div class="hero-content">
        <div class="hero-text">
            <p class="section-label hero-label">✦ New Collection 2025</p>

            <h1 class="hero-title">
                <span class="script">Luxury</span>
                Redefined
            </h1>

            <p class="hero-tagline">Where elegance meets exclusivity — curated accessories for the discerning few.</p>

            <div class="hero-cta">
                <a href="shop.php" class="btn btn-gold btn-lg">
                    <i class="fa fa-gem"></i> Explore Collections
                </a>
                <a href="shop.php?featured=1" class="btn btn-outline btn-lg">
                    View Featured
                </a>
            </div>

            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-num">500+</span>
                    <span class="stat-label">Pieces Curated</span>
                </div>
                <div class="stat">
                    <span class="stat-num">98%</span>
                    <span class="stat-label">Happy Clients</span>
                </div>
                <div class="stat">
                    <span class="stat-num">8</span>
                    <span class="stat-label">Collections</span>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-image-frame">
                <img src="assets/images/hero-bg.jpg" alt="LuxeByLucia Collection"
                     onerror="this.src='https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&h=800&fit=crop'">
            </div>
            <div class="hero-badge">
                <div class="badge-text">Free Shipping</div>
                <div class="badge-val">On orders over ₦50k</div>
            </div>
        </div>
    </div>
</section>

<div class="gold-divider"></div>

<!-- ── CATEGORIES ────────────────────────────────────────────── -->
<section class="categories-section" style="padding:0">
    <div class="categories-grid">
        <?php
        $catIcons = ['💎','💍','⌚','👑','💛','✨','🌟','💫'];
        $catImgs  = [
            'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&fit=crop',
            'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800&fit=crop',
            'https://images.unsplash.com/photo-1609873814058-a8928924184a?w=800&fit=crop',
            'https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=800&fit=crop',
            'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&fit=crop',
            'https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w=800&fit=crop',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&fit=crop',
            'https://images.unsplash.com/photo-1535556116002-6281ff3e9f36?w=800&fit=crop',
        ];
        foreach ($categories as $i => $cat):
        ?>
        <a href="shop.php?category=<?= e($cat['slug']) ?>" class="category-card">
            <img src="<?= $catImgs[$i % count($catImgs)] ?>" alt="<?= e($cat['name']) ?>"
                 loading="lazy">
            <div class="category-card-content">
                <div class="cat-icon"><?= $catIcons[$i % count($catIcons)] ?></div>
                <h3><?= e($cat['name']) ?></h3>
                <span class="cat-count">EXPLORE →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── FEATURED PRODUCTS ──────────────────────────────────────── -->
<?php if (!empty($featuredProducts)): ?>
<section style="padding:100px 0; background:var(--black)">
    <div class="container">
        <div class="section-header" data-animate>
            <p class="section-label">✦ Handpicked For You</p>
            <h2 class="section-title">Featured <em>Pieces</em></h2>
            <p class="section-sub">Each piece is selected for its quality, craftsmanship and timeless appeal.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($featuredProducts as $p):
                $imgs  = getProductImages($p['images']);
                $price = $p['sale_price'] ?? $p['price'];
                $inWish = isInWishlist($pdo, $p['id']);
            ?>
            <article class="product-card <?= $p['stock'] == 0 ? 'out-of-stock' : '' ?>" data-animate>
                <div class="product-image">
                    <a href="product.php?slug=<?= e($p['slug']) ?>">
                        <img src="<?= e($imgs[0]) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=400&h=500&fit=crop'">
                    </a>
                    <div class="product-badges">
                        <?php if ($p['created_at'] > date('Y-m-d', strtotime('-14 days'))): ?>
                        <span class="badge-tag badge-new">New</span>
                        <?php endif; ?>
                        <?php if ($p['sale_price']): ?>
                        <span class="badge-tag badge-sale">Sale</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <button class="action-btn <?= $inWish ? 'wishlisted' : '' ?>"
                                onclick="toggleWishlist(<?= $p['id'] ?>, this)"
                                title="<?= $inWish ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                            <i class="fa fa-heart"></i>
                        </button>
                        <a href="product.php?slug=<?= e($p['slug']) ?>" class="action-btn" title="Quick View">
                            <i class="fa fa-eye"></i>
                        </a>
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
                    <?php if ($p['stock'] > 0): ?>
                    <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>)">
                        <i class="fa fa-shopping-bag"></i> Add to Bag
                    </button>
                    <?php else: ?>
                    <button class="btn-add-cart" disabled style="opacity:0.5;cursor:not-allowed">
                        Out of Stock
                    </button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:60px">
            <a href="shop.php" class="btn btn-outline btn-lg">View All Products</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── PROMO BANNER ───────────────────────────────────────────── -->
<section style="padding:40px 0 100px; background:var(--black)">
    <div class="container">
        <div class="promo-section">
            <p class="section-label" style="position:relative">✦ Limited Offer</p>
            <h2 class="section-title" style="position:relative">
                Free Shipping on Orders<br>Over <span style="color:var(--gold)">₦50,000</span>
            </h2>
            <p style="color:var(--white-muted); margin:16px 0 32px; position:relative">
                Use code <strong style="color:var(--gold);letter-spacing:2px">LUXESHIP</strong> at checkout
            </p>
            <a href="shop.php" class="btn btn-gold btn-lg" style="position:relative">
                Shop Now <i class="fa fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ── NEW ARRIVALS ───────────────────────────────────────────── -->
<?php if (!empty($newProducts)): ?>
<section style="padding:0 0 100px; background:var(--black-soft)">
    <div class="container" style="padding-top:80px">
        <div class="section-header" data-animate>
            <p class="section-label">✦ Just Arrived</p>
            <h2 class="section-title">New <em>Arrivals</em></h2>
        </div>
        <div class="products-grid">
            <?php foreach ($newProducts as $p):
                $imgs = getProductImages($p['images']);
                $price = $p['sale_price'] ?? $p['price'];
                $inWish = isInWishlist($pdo, $p['id']);
            ?>
            <article class="product-card" data-animate>
                <div class="product-image">
                    <a href="product.php?slug=<?= e($p['slug']) ?>">
                        <img src="<?= e($imgs[0]) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w=400&h=500&fit=crop'">
                    </a>
                    <div class="product-badges"><span class="badge-tag badge-new">New</span></div>
                    <div class="product-actions">
                        <button class="action-btn <?= $inWish ? 'wishlisted' : '' ?>"
                                onclick="toggleWishlist(<?= $p['id'] ?>, this)">
                            <i class="fa fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <p class="product-category"><?= e($p['category_name']) ?></p>
                    <h3 class="product-name">
                        <a href="product.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?= formatPrice($price) ?></span>
                    </div>
                    <?php if ($p['stock'] > 0): ?>
                    <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>)">
                        <i class="fa fa-shopping-bag"></i> Add to Bag
                    </button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── WHY LUXEBYLUCIA ────────────────────────────────────────── -->
<section style="background:var(--black); border-top:1px solid var(--border)">
    <div class="container">
        <div class="section-header">
            <p class="section-label">✦ Our Promise</p>
            <h2 class="section-title">The LuxeByLucia <em>Experience</em></h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:32px">
            <?php
            $features = [
                ['fa-gem',        'Premium Quality',     'Every piece is carefully curated for quality and lasting elegance.'],
                ['fa-shield-alt', 'Secure Shopping',     'Your transactions are 100% protected with Paystack encryption.'],
                ['fa-truck',      'Fast Delivery',       'Lagos-wide delivery with real-time order tracking for you.'],
                ['fa-undo',       'Easy Returns',        'Not satisfied? Return within 7 days, no questions asked.'],
            ];
            foreach ($features as $f): ?>
            <div style="text-align:center; padding:40px 24px; border:1px solid var(--border-soft); border-radius:var(--radius-md); transition:var(--transition);"
                 onmouseenter="this.style.borderColor='var(--border)'" onmouseleave="this.style.borderColor='var(--border-soft)'">
                <div style="font-size:2.5rem; color:var(--gold); margin-bottom:20px"><i class="fa <?= $f[0] ?>"></i></div>
                <h3 style="font-family:var(--font-serif); font-size:1.3rem; font-weight:400; margin-bottom:10px"><?= $f[1] ?></h3>
                <p style="font-size:13px; color:var(--white-muted); line-height:1.8"><?= $f[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CONTACT ───────────────────────────────────────────────── -->
<section id="contact" style="background:var(--black-soft); border-top:1px solid var(--border)">
    <div class="container" style="max-width:700px">
        <div class="section-header">
            <p class="section-label">✦ Get In Touch</p>
            <h2 class="section-title">We'd Love to <em>Hear From You</em></h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px; text-align:center; margin-bottom:48px">
            <?php
            $contacts = [
                ['fa-whatsapp fab', 'WhatsApp', '+234 000 000 0000'],
                ['fa-envelope',     'Email',    'hello@luxebylucia.com'],
                ['fa-instagram fab','Instagram','@luxebylucia'],
            ];
            foreach ($contacts as $c): ?>
            <a href="#" style="padding:32px 16px; border:1px solid var(--border-soft); border-radius:var(--radius-md);
               display:flex; flex-direction:column; align-items:center; gap:12px; transition:var(--transition);"
               onmouseenter="this.style.borderColor='var(--gold)'" onmouseleave="this.style.borderColor='var(--border-soft)'">
                <i class="<?= $c[0] ?> fa" style="font-size:2rem; color:var(--gold)"></i>
                <span style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--white-muted)"><?= $c[1] ?></span>
                <span style="font-size:13px; color:var(--white)"><?= $c[2] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
/* Animate-on-scroll */
[data-animate] { opacity:0; transform:translateY(30px); transition:opacity 0.7s ease, transform 0.7s ease; }
[data-animate].in-view { opacity:1; transform:translateY(0); }
</style>
