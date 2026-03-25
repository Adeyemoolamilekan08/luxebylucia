<?php
// ============================================================
// LUXEBYLUCIA — Admin: Products Management
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$action  = $_GET['action'] ?? 'list';
$message = '';
$error   = '';

// ── DELETE ───────────────────────────────────────────────────
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    $message = 'Product deleted successfully.';
    $action  = 'list';
}

// ── SAVE (Create / Update) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $salePrice   = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock       = (int)($_POST['stock'] ?? 0);
    $gender      = $_POST['gender'] ?? 'unisex';
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $status      = $_POST['status'] ?? 'active';
    $slug        = slugify($name) . ($id ? '-' . $id : '-' . time());

    // Handle image URLs (comma-separated for simplicity)
    $imagesRaw = trim($_POST['images'] ?? '');
    $imagesArr = array_filter(array_map('trim', explode(',', $imagesRaw)));
    $imagesJson = json_encode(array_values($imagesArr));

    if (!$name || !$categoryId || $price <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?,
                sale_price=?, stock=?, gender=?, featured=?, status=?, images=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$categoryId, $name, $slug, $description, $price, $salePrice, $stock, $gender, $featured, $status, $imagesJson, $id]);
            $message = 'Product updated successfully.';
        } else {
            // Create
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, gender, featured, status, images)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([$categoryId, $name, $slug, $description, $price, $salePrice, $stock, $gender, $featured, $status, $imagesJson]);
            $message = 'Product created successfully.';
        }
        $action = 'list';
    }
}

// ── GET PRODUCT FOR EDITING ───────────────────────────────────
$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editProduct = $stmt->fetch();
    if (!$editProduct) { $action = 'list'; }
}

// ── LIST ──────────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$total    = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$products = $pdo->query("
    SELECT p.*, c.name AS category_name FROM products p
    JOIN categories c ON p.category_id=c.id
    ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Products — LuxeByLucia Admin</title>
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
            <a href="products.php" class="active"><i class="fa fa-gem"></i> Products</a>
            <a href="orders.php"><i class="fa fa-box"></i> Orders</a>
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
        <?php if ($error): ?>
        <div style="background:rgba(224,82,82,0.1);border:1px solid var(--red);border-radius:var(--radius);padding:14px;margin-bottom:24px;color:var(--red)">
            <i class="fa fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
        <!-- ── PRODUCT FORM ─────────────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
            <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">
                <?= $editProduct ? 'Edit Product' : 'Add New Product' ?>
            </h1>
            <a href="products.php" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <div class="admin-card">
            <form method="POST">
                <?php if ($editProduct): ?>
                <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group full">
                        <label>Product Name *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= $editProduct ? e($editProduct['name']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <?php foreach (['unisex','female','male'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($editProduct && $editProduct['gender'] === $g) ? 'selected' : '' ?>>
                                <?= ucfirst($g) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (₦) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0"
                               value="<?= $editProduct ? $editProduct['price'] : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Sale Price (₦) — leave blank for no sale</label>
                        <input type="number" name="sale_price" class="form-control" step="0.01" min="0"
                               value="<?= ($editProduct && $editProduct['sale_price']) ? $editProduct['sale_price'] : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" class="form-control" min="0"
                               value="<?= $editProduct ? $editProduct['stock'] : '0' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active"   <?= ($editProduct && $editProduct['status']==='active')   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editProduct && $editProduct['status']==='inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5"><?= $editProduct ? e($editProduct['description']) : '' ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Image URLs (comma-separated)</label>
                        <input type="text" name="images" class="form-control"
                               value="<?= $editProduct ? e(implode(', ', json_decode($editProduct['images'] ?? '[]', true))) : '' ?>"
                               placeholder="https://example.com/img1.jpg, https://example.com/img2.jpg">
                        <small style="color:var(--white-muted);font-size:11px">Enter full image URLs separated by commas. First image is the main product image.</small>
                    </div>
                    <div class="form-group full">
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
                            <input type="checkbox" name="featured" value="1"
                                   <?= ($editProduct && $editProduct['featured']) ? 'checked' : '' ?>
                                   style="accent-color:var(--gold);width:18px;height:18px">
                            <span>Mark as Featured Product</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:24px">
                    <button type="submit" name="save_product" class="btn btn-gold">
                        <i class="fa fa-save"></i> <?= $editProduct ? 'Update Product' : 'Create Product' ?>
                    </button>
                    <a href="products.php" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- ── PRODUCT LIST ─────────────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
            <div>
                <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300">Products</h1>
                <p style="color:var(--white-muted);font-size:13px"><?= $total ?> products total</p>
            </div>
            <a href="products.php?action=new" class="btn btn-gold btn-sm">
                <i class="fa fa-plus"></i> Add Product
            </a>
        </div>

        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p):
                        $pimgs = getProductImages($p['images']);
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <img src="<?= e($pimgs[0]) ?>" alt="<?= e($p['name']) ?>"
                                     style="width:44px;height:55px;object-fit:cover;border-radius:var(--radius);background:var(--black-mid)"
                                     onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=88&fit=crop'">
                                <div>
                                    <div style="font-weight:500"><?= e($p['name']) ?></div>
                                    <div style="font-size:11px;color:var(--white-muted)"><?= e($p['gender']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gold)"><?= e($p['category_name']) ?></td>
                        <td>
                            ₦<?= number_format($p['price']) ?>
                            <?php if ($p['sale_price']): ?>
                            <br><small style="color:var(--red)">Sale: ₦<?= number_format($p['sale_price']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color:<?= $p['stock'] == 0 ? 'var(--red)' : ($p['stock'] <= 5 ? 'var(--gold)' : 'var(--green)') ?>">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= $p['status']==='active' ? 'status-delivered' : 'status-cancelled' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $p['featured'] ? '<span style="color:var(--gold)"><i class="fa fa-star"></i> Yes</span>' : '<span style="color:var(--white-muted)">No</span>' ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:8px">
                                <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/product.php?slug=<?= e($p['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm"
                                   style="background:rgba(224,82,82,0.1);border:1px solid var(--red);color:var(--red)"
                                   onclick="return confirm('Delete this product? This cannot be undone.')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php $totalPages = ceil($total / $perPage); if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:24px">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
