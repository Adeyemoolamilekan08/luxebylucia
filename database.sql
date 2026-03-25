-- ============================================================
-- LUXEBYLUCIA - Complete Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS luxebylucia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxebylucia;

-- ─────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
-- PRODUCTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    images JSON,
    featured TINYINT(1) DEFAULT 0,
    gender ENUM('male','female','unisex') DEFAULT 'unisex',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
-- CART (persistent)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- WISHLIST
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- ORDERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'paystack',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    order_status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    -- Billing Info
    billing_name VARCHAR(200) NOT NULL,
    billing_email VARCHAR(255) NOT NULL,
    billing_phone VARCHAR(20),
    billing_address TEXT NOT NULL,
    billing_city VARCHAR(100),
    billing_state VARCHAR(100),
    billing_country VARCHAR(100) DEFAULT 'Nigeria',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ─────────────────────────────────────────
-- ORDER ITEMS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ─────────────────────────────────────────
-- REVIEWS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_review_per_user (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────
INSERT INTO categories (name, slug, description) VALUES
('Hair Accessories', 'hair-accessories', 'Luxurious hair accessories for every occasion'),
('Body Accessories', 'body-accessories', 'Elegant body chains and accessories'),
('Face Accessories', 'face-accessories', 'Premium face jewelry and accessories'),
('Wristwatches', 'wristwatches', 'Luxury timepieces for him and her'),
('Jean Chains', 'jean-chains', 'Trendy jean chains for a bold look'),
('Earrings', 'earrings', 'Statement earrings that define elegance'),
('Jewellery', 'jewellery', 'Fine jewellery crafted to perfection'),
('Rings', 'rings', 'Exquisite rings for every finger');

-- ─────────────────────────────────────────
-- HOW ADMIN LOGIN WORKS
-- ─────────────────────────────────────────
-- There is NO separate "admin" table.
-- Admins and regular users share the ONE "users" table.
-- The "role" column (ENUM 'user' or 'admin') determines access level.
-- role = 'admin'  → can access /admin/ panel
-- role = 'user'   → regular customer account
--
-- The setup_admin.php script will INSERT the admin with the
-- correct password hash generated live on YOUR server.
-- Run it once, then delete it.
-- ─────────────────────────────────────────

-- DO NOT insert admin here — use setup_admin.php instead.
-- (The hash below is intentionally left out to avoid mismatch errors)
