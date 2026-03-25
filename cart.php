<?php
// ============================================================
// LUXEBYLUCIA — Cart Page
// ============================================================
$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$cart = getCartTotals($pdo);
?>

<div style="padding:120px 0 0; background:var(--black)">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> <span>›</span>
            <a href="shop.php">Shop</a> <span>›</span>
            <span style="color:var(--gold)">Cart</span>
        </div>
        <h1 class="section-title" style="margin-bottom:12px">Shopping <em>Cart</em></h1>
        <p style="color:var(--white-muted); margin-bottom:60px">
            <?= count($cart['items']) ?> item<?= count($cart['items']) !== 1 ? 's' : '' ?> in your bag
        </p>
    </div>
</div>

<section style="background:var(--black); padding:0 0 100px">
    <div class="container">
        <?php if (empty($cart['items'])): ?>
        <div class="empty-state">
            <i class="fa fa-shopping-bag"></i>
            <h3>Your bag is empty</h3>
            <p>Looks like you haven't added anything to your bag yet.</p>
            <a href="shop.php" class="btn btn-gold btn-lg"><i class="fa fa-gem"></i> Start Shopping</a>
        </div>
        <?php else: ?>
        <div class="cart-layout">

            <!-- Cart Items -->
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="hide-mobile">Price</th>
                            <th>Qty</th>
                            <th class="hide-mobile">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart['items'] as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-product">
                                    <a href="product.php?slug=<?= e($item['slug'] ?? '#') ?>">
                                        <img src="<?= e($item['images_arr'][0]) ?>" alt="<?= e($item['name']) ?>"
                                             onerror="this.src='https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=200&h=250&fit=crop'">
                                    </a>
                                    <div>
                                        <div class="cart-product-name"><?= e($item['name']) ?></div>
                                        <div style="font-size:11px; color:var(--white-muted); margin-top:4px">
                                            <?php if ($item['stock'] < 5): ?>
                                            <span style="color:var(--gold)">Only <?= $item['stock'] ?> left!</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="hide-mobile" style="color:var(--gold); font-family:var(--font-serif); font-size:1.1rem">
                                <?= formatPrice($item['unit_price']) ?>
                            </td>
                            <td>
                                <div class="qty-selector">
                                    <button class="qty-btn" data-action="minus">−</button>
                                    <input type="number" class="qty-input"
                                           value="<?= $item['quantity'] ?>"
                                           min="1" max="<?= $item['stock'] ?>"
                                           data-product="<?= $item['product_id'] ?>"
                                           readonly>
                                    <button class="qty-btn" data-action="plus">+</button>
                                </div>
                            </td>
                            <td class="hide-mobile" style="color:var(--white); font-family:var(--font-serif); font-size:1.1rem">
                                <?= formatPrice($item['line_total']) ?>
                            </td>
                            <td>
                                <button class="remove-btn" onclick="removeFromCart(<?= $item['product_id'] ?>)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display:flex; gap:12px; margin-top:24px; flex-wrap:wrap">
                    <a href="shop.php" class="btn btn-ghost btn-sm">
                        <i class="fa fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="wishlist.php" class="btn btn-ghost btn-sm">
                        <i class="fa fa-heart"></i> View Wishlist
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="cart-summary">
                <h3>Order Summary</h3>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="val"><?= formatPrice($cart['subtotal']) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="val">
                        <?php if ($cart['subtotal'] >= 50000): ?>
                        <span style="color:var(--green)">FREE</span>
                        <?php else: ?>
                        <?= formatPrice($cart['shipping']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($cart['subtotal'] < 50000): ?>
                <div style="font-size:12px; color:var(--white-muted); background:var(--gold-dim); border-radius:var(--radius); padding:12px; margin:8px 0">
                    Add <?= formatPrice(50000 - $cart['subtotal']) ?> more for <strong style="color:var(--gold)">free shipping!</strong>
                </div>
                <?php endif; ?>

                <div class="summary-row total">
                    <span>Total</span>
                    <span class="val"><?= formatPrice($cart['subtotal'] >= 50000 ? $cart['subtotal'] : $cart['total']) ?></span>
                </div>

                <!-- Promo Code -->
                <div style="margin:24px 0">
                    <label style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--white-muted); display:block; margin-bottom:8px">Promo Code</label>
                    <div style="display:flex; gap:8px">
                        <input type="text" class="form-control" placeholder="Enter code" style="flex:1">
                        <button class="btn btn-outline btn-sm" onclick="showToast('Promo codes coming soon!','error')">Apply</button>
                    </div>
                </div>

                <a href="checkout.php" class="btn btn-gold btn-full btn-lg" style="margin-top:8px">
                    <i class="fa fa-lock"></i> Proceed to Checkout
                </a>

                <div style="text-align:center; margin-top:16px; display:flex; align-items:center; justify-content:center; gap:8px; font-size:12px; color:var(--white-muted)">
                    <i class="fa fa-lock" style="color:var(--gold)"></i>
                    Secured by Paystack
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
