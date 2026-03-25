<?php
// ============================================================
// LUXEBYLUCIA — AJAX Handler
// ============================================================
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ── SEARCH ──────────────────────────────────────────────
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }

        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.images,
                   c.name AS category
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.status='active' AND (p.name LIKE ? OR c.name LIKE ?)
            LIMIT 8
        ");
        $like = "%$q%";
        $stmt->execute([$like, $like]);
        $products = $stmt->fetchAll();

        $results = array_map(function($p) {
            $imgs  = json_decode($p['images'] ?? '[]', true);
            $thumb = !empty($imgs) ? $imgs[0] : null;
            $price = $p['sale_price'] ?? $p['price'];
            return [
                'id'              => $p['id'],
                'name'            => $p['name'],
                'slug'            => $p['slug'],
                'thumb'           => $thumb,
                'price_formatted' => CURRENCY . number_format($price, 2),
            ];
        }, $products);

        echo json_encode($results);
        break;

    // ── ADD TO CART ──────────────────────────────────────────
    case 'add_to_cart':
        if (!isLoggedIn()) {
            echo json_encode(['success'=>false,'message'=>'Please login to add to cart','redirect'=>SITE_URL.'/login.php']);
            exit;
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

        // Validate product
        $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id=? AND status='active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) { echo json_encode(['success'=>false,'message'=>'Product not found']); exit; }
        if ($product['stock'] < $quantity) {
            echo json_encode(['success'=>false,'message'=>'Not enough stock available']); exit;
        }

        // Insert or update
        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->execute([$_SESSION['user_id'], $productId, $quantity, $quantity]);

        $cartCount = getCartCount($pdo);
        echo json_encode(['success'=>true,'message'=>'Added to cart!','cart_count'=>$cartCount]);
        break;

    // ── UPDATE CART ──────────────────────────────────────────
    case 'update_cart':
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Not logged in']); exit; }
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");
        $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);
        echo json_encode(['success'=>true,'cart_count'=>getCartCount($pdo)]);
        break;

    // ── REMOVE FROM CART ─────────────────────────────────────
    case 'remove_from_cart':
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Not logged in']); exit; }
        $productId = (int)($_POST['product_id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        echo json_encode(['success'=>true,'cart_count'=>getCartCount($pdo)]);
        break;

    // ── TOGGLE WISHLIST ──────────────────────────────────────
    case 'toggle_wishlist':
        if (!isLoggedIn()) {
            echo json_encode(['success'=>false,'message'=>'Please login to save to wishlist','redirect'=>SITE_URL.'/login.php']);
            exit;
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $userId    = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$userId, $productId]);
            echo json_encode(['success'=>true,'wishlisted'=>false,'message'=>'Removed from wishlist']);
        } else {
            $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$userId, $productId]);
            echo json_encode(['success'=>true,'wishlisted'=>true,'message'=>'Added to wishlist ♥']);
        }
        break;

    // ── MOVE WISHLIST TO CART ────────────────────────────────
    case 'move_to_cart':
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Not logged in']); exit; }
        $productId = (int)($_POST['product_id'] ?? 0);
        $userId    = $_SESSION['user_id'];

        // Add to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,1) ON DUPLICATE KEY UPDATE quantity=quantity+1");
        $stmt->execute([$userId, $productId]);

        // Remove from wishlist
        $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$userId, $productId]);
        echo json_encode(['success'=>true,'message'=>'Moved to cart!','cart_count'=>getCartCount($pdo)]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
}
