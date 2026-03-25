<?php
// ============================================================
// LUXEBYLUCIA — Admin: Categories
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!$name) {
        $error = 'Category name is required.';
    } else {
        $slug = slugify($name);
        if ($id) {
            $pdo->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?")->execute([$name, $slug, $desc, $id]);
            $message = 'Category updated.';
        } else {
            $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?,?,?)")->execute([$name, $slug, $desc]);
            $message = 'Category created.';
        }
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = 'Category deleted.';
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c LEFT JOIN products p ON p.category_id=c.id
    GROUP BY c.id ORDER BY c.name
")->fetchAll();

$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Categories — LuxeByLucia Admin</title>
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
            <a href="categories.php" class="active"><i class="fa fa-tags"></i> Categories</a>
            <a href="reviews.php"><i class="fa fa-star"></i> Reviews</a>
            <div style="border-top:1px solid var(--border);margin:16px 0"></div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fa fa-home"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 style="font-family:var(--font-serif);font-size:2rem;font-weight:300;margin-bottom:28px">Categories</h1>

        <?php if ($message): ?>
        <div style="background:rgba(76,175,77,0.1);border:1px solid var(--green);border-radius:var(--radius);padding:14px;margin-bottom:24px;color:var(--green)">
            <i class="fa fa-check-circle"></i> <?= e($message) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div style="background:rgba(224,82,82,0.1);border:1px solid var(--red);border-radius:var(--radius);padding:14px;margin-bottom:24px;color:var(--red)">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
            <!-- Category List -->
            <div class="admin-card">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="font-weight:500"><?= e($cat['name']) ?></td>
                            <td style="color:var(--white-muted);font-size:12px"><?= e($cat['slug']) ?></td>
                            <td style="color:var(--gold)"><?= $cat['product_count'] ?></td>
                            <td>
                                <div style="display:flex;gap:8px">
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-ghost btn-sm"><i class="fa fa-edit"></i></a>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-sm"
                                       style="background:rgba(224,82,82,0.1);border:1px solid var(--red);color:var(--red)"
                                       onclick="return confirm('Delete this category?')"><i class="fa fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Form -->
            <div class="admin-card">
                <h3 style="font-family:var(--font-serif);font-size:1.2rem;margin-bottom:20px;color:var(--gold)">
                    <?= $editCat ? 'Edit Category' : 'Add Category' ?>
                </h3>
                <form method="POST">
                    <?php if ($editCat): ?>
                    <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom:16px">
                        <label>Category Name *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= $editCat ? e($editCat['name']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= $editCat ? e($editCat['description']) : '' ?></textarea>
                    </div>
                    <button type="submit" name="save_category" class="btn btn-gold btn-full">
                        <i class="fa fa-save"></i> <?= $editCat ? 'Update' : 'Create' ?> Category
                    </button>
                    <?php if ($editCat): ?>
                    <a href="categories.php" class="btn btn-ghost btn-full" style="margin-top:8px">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
