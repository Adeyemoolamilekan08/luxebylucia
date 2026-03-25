<?php // LUXEBYLUCIA - Footer Include ?>

<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-col footer-brand">
                    <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="LuxeByLucia" class="footer-logo">
                    <p>Where elegance meets exclusivity. Curated luxury accessories for those who dare to be different.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php">Shop All</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?featured=1">Collections</a></li>
                        <li><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
                        <li><a href="<?= SITE_URL ?>/wishlist.php">Wishlist</a></li>
                        <li><a href="<?= SITE_URL ?>/orders.php">Track Order</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="footer-col">
                    <h4 class="footer-heading">Categories</h4>
                    <ul class="footer-links">
                        <?php foreach ($categories as $cat): ?>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact & Newsletter -->
                <div class="footer-col">
                    <h4 class="footer-heading">Stay Connected</h4>
                    <div class="footer-contact">
                        <p><i class="fa fa-envelope"></i> hello@luxebylucia.com</p>
                        <p><i class="fab fa-whatsapp"></i> +234 000 000 0000</p>
                        <p><i class="fa fa-map-marker-alt"></i> Lagos, Nigeria</p>
                    </div>
                    <div class="newsletter">
                        <p>Join our exclusive list</p>
                        <form class="newsletter-form" id="newsletterForm">
                            <input type="email" placeholder="Your email address" required>
                            <button type="submit"><i class="fa fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> LuxeByLucia. All rights reserved. Crafted with <span class="gold">♥</span></p>
            <div class="footer-payment">
                <img src="https://img.shields.io/badge/Paystack-Secured-blue?style=flat" alt="Paystack Secured">
                <span>Secure Checkout</span>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Scripts -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?= isset($extraScripts) ? $extraScripts : '' ?>
</body>
</html>
