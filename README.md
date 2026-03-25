# 🖤✨ LUXEBYLUCIA — Complete eCommerce Platform

A full-stack, production-ready luxury eCommerce website built with PHP, MySQL, and Paystack.

---

## 📁 Folder Structure

```
/luxebylucia
├── /assets
│   ├── /css
│   │   └── main.css           ← Full luxury black & gold theme
│   ├── /js
│   │   └── main.js            ← AJAX, cart, wishlist, search, Paystack
│   └── /images
│       └── logo.png           ← Your brand logo
├── /includes
│   ├── db.php                 ← PDO database connection + config
│   ├── functions.php          ← All helper functions
│   ├── header.php             ← Navbar, flash messages, search
│   ├── footer.php             ← Footer, social links, newsletter
│   ├── ajax.php               ← AJAX handler (cart, wishlist, search)
│   └── email.php              ← Email notification system
├── /admin
│   ├── index.php              ← Admin dashboard with stats
│   ├── products.php           ← Product CRUD management
│   ├── orders.php             ← Order management + status updates
│   ├── users.php              ← User management
│   ├── categories.php         ← Category CRUD
│   └── reviews.php            ← Review moderation
├── database.sql               ← Complete DB schema + seed data
├── index.php                  ← Homepage (hero, categories, products)
├── shop.php                   ← Shop with filters, search, pagination
├── product.php                ← Product detail + gallery + reviews
├── cart.php                   ← Cart with quantity management
├── wishlist.php               ← Wishlist per user
├── checkout.php               ← Checkout + Paystack integration
├── orders.php                 ← Order history + tracking
├── login.php                  ← User login
├── register.php               ← User registration
└── logout.php                 ← Logout
```

---

## 🚀 Installation

### 1. Prerequisites
- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache/Nginx (XAMPP/WAMP/LAMP)
- cURL enabled in PHP

### 2. Setup Database
```sql
-- In phpMyAdmin or MySQL CLI:
SOURCE /path/to/luxebylucia/database.sql;
```

### 3. Configure Settings
Edit `/includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxebylucia');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('SITE_URL', 'http://yourdomain.com/luxebylucia');
define('SITE_EMAIL', 'hello@yourdomain.com');

// Paystack Keys (get from dashboard.paystack.com)
define('PAYSTACK_PUBLIC_KEY', 'pk_live_XXXXX');
define('PAYSTACK_SECRET_KEY', 'sk_live_XXXXX');
```

### 4. Paystack Setup
1. Sign up at [dashboard.paystack.com](https://dashboard.paystack.com)
2. Get your API keys (use **test** keys for development)
3. Add keys to `includes/db.php`
4. Add your live domain to Paystack's allowed domains

### 5. Admin Login
- **URL**: `/luxebylucia/admin/index.php`
- **Email**: `admin@luxebylucia.com`
- **Password**: `Admin@1234`
- ⚠️ **Change this password immediately after first login!**

---

## 🔑 Default Admin Credentials
| Email | Password |
|-------|----------|
| admin@luxebylucia.com | Admin@1234 |

**To change admin password:**
```php
// Generate a new hash:
echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost' => 12]);
// Then UPDATE users SET password='[hash]' WHERE email='admin@luxebylucia.com';
```

---

## ✨ Features Built

| Feature | Status |
|---------|--------|
| 🏠 Homepage (Hero, Categories, Products, Promo) | ✅ |
| 🛍️ Shop with Filters + Pagination | ✅ |
| 🔍 AJAX Live Search | ✅ |
| 📦 Product Detail + Image Gallery | ✅ |
| ⭐ Reviews & Star Ratings | ✅ |
| 🛒 Shopping Cart (persistent) | ✅ |
| ❤️ Wishlist (saved per user) | ✅ |
| 💳 Checkout + Paystack Payment | ✅ |
| 📧 Email Notifications | ✅ |
| 👤 User Registration + Login | ✅ |
| 📦 Order History + Tracking | ✅ |
| 🔐 Security (PDO, hashing, XSS) | ✅ |
| 🎛️ Admin Dashboard | ✅ |
| 📊 Product CRUD (Admin) | ✅ |
| 📋 Order Management (Admin) | ✅ |
| 👥 User Management (Admin) | ✅ |
| 🏷️ Category CRUD (Admin) | ✅ |
| ⭐ Review Moderation (Admin) | ✅ |
| 📱 Fully Responsive Design | ✅ |
| 🎨 Black + Gold Luxury UI | ✅ |
| ✨ Animations + Transitions | ✅ |

---

## 💳 Paystack Integration Flow

1. User fills checkout form → clicks **Pay**
2. JavaScript creates order via AJAX (saves to DB)
3. Paystack Inline popup opens
4. On payment success → redirects to `/checkout.php?verify={ref}&order_id={id}`
5. PHP verifies with Paystack API (server-side)
6. Order status updated → cart cleared → confirmation email sent

---

## 📧 Email Notifications

Emails sent for:
- ✅ Welcome after registration
- ✅ Order confirmation
- ✅ Payment success
- ✅ Order status updates (Processing, Shipped, Delivered)

**For production**, replace `mail()` in `includes/email.php` with PHPMailer + SMTP:
```bash
composer require phpmailer/phpmailer
```

---

## 🔐 Security Features

- **PDO Prepared Statements** — all queries use parameterized statements
- **Password Hashing** — bcrypt with cost factor 12
- **XSS Prevention** — all output sanitized with `htmlspecialchars()`
- **Session Management** — secure session handling
- **Admin Protection** — role-based access control
- **Input Validation** — all user inputs validated server-side

---

## 🎨 Customization

### Colors (edit `/assets/css/main.css`):
```css
:root {
    --gold:  #c9a84c;  /* Primary gold */
    --black: #0a0a0a;  /* Primary black */
}
```

### Shipping Fee:
```php
// In includes/db.php:
define('SHIPPING_FEE', 3000);  // ₦3,000 flat rate
```

### Free Shipping Threshold:
```php
// In cart.php and checkout.php:
if ($cart['subtotal'] >= 50000)  // Free shipping over ₦50,000
```

---

## 🛠️ Adding Products

**Via Admin Panel:**
1. Go to `/admin/products.php?action=new`
2. Fill in product details
3. Add image URLs (use external hosting like Cloudinary/ImgBB)
4. Mark as Featured if desired

**For image hosting** (free options):
- [Cloudinary](https://cloudinary.com) — recommended
- [ImgBB](https://imgbb.com)
- [Uploadcare](https://uploadcare.com)

---

## 📞 Support

Built for **LuxeByLucia** — Lagos, Nigeria 🇳🇬

For customizations contact: hello@luxebylucia.com
