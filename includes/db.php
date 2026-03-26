<?php
// ============================================================
// LUXEBYLUCIA - Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'luxebylucia');
define('DB_USER', 'root');          // Change to your DB username
define('DB_PASS', '');              // Change to your DB password
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'LuxeByLucia');
define('SITE_URL', 'http://localhost/luxebylucia');  // Change to your domain
define('SITE_EMAIL', 'hello@luxebylucia.com');
define('CURRENCY', '₦');
define('SHIPPING_FEE', 3000);       // In Naira

// Paystack
define('PAYSTACK_PUBLIC_KEY', 'pk_test_fb44af4dc6172d9ee69fd452d4d3f188a9738b9e');
define('PAYSTACK_SECRET_KEY', 'sk_test_e4edaef1498d8037f49a728c59535f10b0b86738');

// PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}
