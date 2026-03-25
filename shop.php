<?php
// ============================================================
// LUXEBYLUCIA — Shop Page
// ============================================================
$pageTitle = 'Shop All Collections';
require_once __DIR__ . '/includes/header.php';

// ── Query Params ─────────────────────────────────────────────
$categorySlug = trim($_GET['category'] ?? '');
$search       = trim($_GET['q'] ?? '');
$sort         = $_GET['sort'] ?? 'newest';
$featured     = isset($_GET['featured']);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

// ── Build Query ───────────────────────────────────────────────
$where  = ["p.status='active'"];
$params = [];

if ($categorySlug) {
    $where[]  = "c.slug = ?";
    $params[] = $categorySlug;
}
if ($search) {
    $where[]  = "(p.name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($featured) {
    $where[] = "p.featured = 1";
}

$whereSQL = implode(' AND ', $where);

$orderSQL = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => 'avg_rating DESC',
    default      => 'p.created_at DESC',
};

// Count total
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id) FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = ceil($totalProducts / $perPage);

// Fetch products
$productStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           AVG(r.rating) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON r.product_id = p.id AND r.status='approved'
    WHERE $whereSQL
    GROUP BY p.id
    ORDER BY $orderSQL
    LIMIT $perPage OFFSET $offset
");
$productStmt->execute($params);
$products = $productStmt->fetchAll();

// Active category info
$activeCategory = null;
if ($categorySlug) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug=?");
    $stmt->execute([$categorySlug]);
    $activeCategory = $stmt->fetch();
}
?>

<!-- Page Header -->
<div style="padding:120px 0 60px; background:var(--black-soft); border-bottom:1px solid var(--border)">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span>›</span>
            <a href="shop.php">Shop</a>
            <?php if ($activeCategory): ?>
            <span>›</span>
            <span style="color:var(--gold)"><?= e($activeCategory['name']) ?></span>
            <?php elseif ($search): ?>
            <span>›</span>
            <span style="color:var(--gold)">Search: "<?= e($search) ?>"</span>
            <?php endif; ?>
        </div>
        <div style="display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:16px">
            <div>
                <p class="section-label">✦ <?= $featured ? 'Featured Collection' : ($activeCategory ? e($activeCategory['name']) : 'All Products') ?></p>
                <h1 class="section-title" style="margin:0; font-size:2.5rem">
                    <?= $activeCategory ? e($activeCategory['name']) : ($search ? 'Results for "'.$search.'"' : 'Shop All') ?>
                </h1>
                <p style="color:var(--white-muted); margin-top:8px; font-size:13px">
                    <?= $totalProducts ?> <?= $totalProducts === 1 ? 'product' : 'products' ?> found
                </p>
            </div>
            <!-- Sort -->
            <div style="display:flex; align-items:center; gap:12px">
                <label style="font-size:11px; letter-spacing:2px; color:var(--white-muted); text-transform:uppercase">Sort:</label>
                <select onchange="applySortFilter(this.value)" class="form-control" style="padding:10px 16px; min-width:180px">
                    <option value="newest"     <?= $sort==='newest'     ?'selected':'' ?>>Newest First</option>
                    <option value="popular"    <?= $sort==='popular'    ?'selected':'' ?>>Most Popular</option>
                    <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':'' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort==='price_desc' ?'selected':'' ?>>Price: High to Low</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Shop Content -->
<section style="background:var(--black); padding:60px 0 100px">
    <div class="container">
        <div class="shop-layout">
            
            <!-- Sidebar Filter -->
            <aside class="filter-sidebar">
                <h3 style="font-family:var(--font-serif); font-size:1.2rem; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid var(--border)">
                    Filters
                </h3>

                <!-- Categories -->
                <div class="filter-group">
                    <div class="filter-title">Category</div>
                    <div class="filter-options">
                        <a href="shop.php" class="filter-option <?= !$categorySlug ? 'active' : '' ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="shop.php?category=<?= e($cat['slug']) ?><?= $sort !== 'newest' ? '&sort='.$sort : '' ?>"
                           class="filter-option <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                            <?= e($cat['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Gender -->
                <div class="filter-group">
                    <div class="filter-title">For</div>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="checkbox" onchange="applyGenderFilter('female')"> Women
                        </label>
                        <label class="filter-option">
                            <input type="checkbox" onchange="applyGenderFilter('male')"> Men
                        </label>
                        <label class="filter-option">
                            <input type="checkbox" checked onchange="applyGenderFilter('unisex')"> Unisex
                        </label>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <div class="filter-title">Price Range</div>
                    <div class="price-range">
                        <input type="range" min="0" max="500000" step="1000" value="500000" id="priceRange"
                               oninput="document.getElementById('priceMax').textContent='₦'+Number(this.value).toLocaleString()">
                        <div class="price-display">
                            <span>₦0</span>
                            <span id="priceMax">₦500,000</span>
                        </div>
                    </div>
                </div>

                <!-- Featured -->
                <div class="filter-group">
                    <div class="filter-title">Collection</div>
                    <div class="filter-options">
                        <a href="shop.php?featured=1" class="filter-option <?= $featured ? 'active' : '' ?>">
                            ✦ Featured Only
                        </a>
                    </div>
                </div>

                <?php if ($categorySlug || $search || $featured): ?>
                <a href="shop.php" class="btn btn-ghost btn-sm btn-full" style="margin-top:16px">
                    <i class="fa fa-times"></i> Clear Filters
                </a>
                <?php endif; ?>
            </aside>

            <!-- Products Grid -->
            <div>
                <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fa fa-search"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search terms</p>
                    <a href="shop.php" class="btn btn-gold">View All Products</a>
                </div>
                <?php else: ?>

                <div class="products-grid">
                    <?php foreach ($products as $p):
                        $imgs  = getProductImages($p['images']);
                        $price = $p['sale_price'] ?? $p['price'];
                        $inWish = isInWishlist($pdo, $p['id']);
                    ?>
                    <article class="product-card <?= $p['stock'] == 0 ? 'out-of-stock' : '' ?>">
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
                                        onclick="toggleWishlist(<?= $p['id'] ?>, this)" title="Wishlist">
                                    <i class="fa fa-heart"></i>
                                </button>
                                <a href="product.php?slug=<?= e($p['slug']) ?>" class="action-btn" title="View">
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
                            <button class="btn-add-cart" disabled style="opacity:0.4;cursor:not-allowed">Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-btn">
                        <i class="fa fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-btn">
                        <i class="fa fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function applySortFilter(sort) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sort);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
function applyGenderFilter(gender) {
    const url = new URL(window.location.href);
    url.searchParams.set('gender', gender);
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
