<?php
// ============================================================
// LUXEBYLUCIA — Email System (XAMPP Compatible)
// ============================================================
//
// HOW IT WORKS:
//   - By default emails are LOGGED to /emails/log.txt (works on XAMPP with no mail server)
//   - To send REAL emails: set EMAIL_MODE to 'smtp' and fill in SMTP settings below
//   - PHPMailer is NOT required for log mode — only needed for smtp mode
//
// TO SEND REAL EMAILS (2 options):
//
//   OPTION A — Gmail SMTP (easiest for testing):
//     1. Set EMAIL_MODE = 'smtp'
//     2. Fill SMTP_* settings with your Gmail details
//     3. Enable 2FA on your Gmail account
//     4. Create an App Password at: https://myaccount.google.com/apppasswords
//     5. Use that App Password as SMTP_PASS (NOT your real Gmail password)
//     6. Download PHPMailer: https://github.com/PHPMailer/PHPMailer
//        Place /PHPMailer/src/ folder inside /luxebylucia/includes/
//
//   OPTION B — Keep logging (safe for development):
//     Leave EMAIL_MODE = 'log' — emails are saved to /emails/log.txt
//     You can read them there to see what would have been sent.
//
// ============================================================

// ── Email Configuration ──────────────────────────────────────
// Change 'log' to 'smtp' when you are ready to send real emails
define('EMAIL_MODE',  'log');        // 'log' = save to file | 'smtp' = send via Gmail

// Gmail SMTP settings (only used when EMAIL_MODE = 'smtp')
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'your_gmail@gmail.com');    // Your Gmail address
define('SMTP_PASS',   'your_app_password_here');  // Gmail App Password (NOT your real password)
define('SMTP_FROM',   'your_gmail@gmail.com');    // Must match SMTP_USER for Gmail
define('SMTP_NAME',   'LuxeByLucia');

// ── Core Send Function ────────────────────────────────────────
function sendEmail(string $to, string $subject, string $html): bool {

    if (EMAIL_MODE === 'smtp') {
        return sendEmailSMTP($to, $subject, $html);
    }

    // Default: log to file (works on XAMPP with no mail server)
    return logEmail($to, $subject, $html);
}

// ── Log email to file (XAMPP / development) ──────────────────
function logEmail(string $to, string $subject, string $html): bool {
    $logDir  = dirname(__DIR__) . '/emails';
    $logFile = $logDir . '/log.txt';

    // Create emails folder if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $divider = str_repeat('=', 70);
    $entry   = "\n{$divider}\n";
    $entry  .= "DATE:    " . date('Y-m-d H:i:s') . "\n";
    $entry  .= "TO:      {$to}\n";
    $entry  .= "SUBJECT: {$subject}\n";
    $entry  .= "MODE:    LOGGED (EMAIL_MODE=log — not actually sent)\n";
    $entry  .= $divider . "\n";
    $entry  .= strip_tags($html) . "\n";
    $entry  .= "{$divider}\n";

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    return true; // Always succeeds — no mail server needed
}

// ── Send via Gmail SMTP using PHPMailer ───────────────────────
function sendEmailSMTP(string $to, string $subject, string $html): bool {
    $phpmailerPath = __DIR__ . '/PHPMailer/src/';

    // Check PHPMailer is installed
    if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
        error_log('PHPMailer not found at: ' . $phpmailerPath . 'PHPMailer.php');
        // Fall back to logging so the site doesn't break
        return logEmail($to, $subject, $html . "\n\n[PHPMailer not installed — email was logged instead]");
    }

    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host        = SMTP_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = SMTP_USER;
        $mail->Password    = SMTP_PASS;
        $mail->SMTPSecure  = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = SMTP_PORT;

        // Sender & recipient
        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM, SMTP_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($html);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        // Fall back to logging so the site doesn't crash
        logEmail($to, $subject, $html . "\n\n[SMTP FAILED: " . $mail->ErrorInfo . "]");
        return false;
    }
}

// ── Email Template ─────────────────────────────────────────────
function emailTemplate(string $title, string $body): string {
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>{$title}</title>
<style>
  body { margin:0; padding:0; background:#0a0a0a; font-family:'Helvetica Neue',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:0 auto; }
  .header { background:#0a0a0a; padding:40px 40px 20px; text-align:center; border-bottom:2px solid #c9a84c; }
  .logo-text { font-size:28px; color:#c9a84c; font-weight:300; letter-spacing:4px; text-transform:uppercase; }
  .content { background:#111111; padding:40px; color:#e8e8e8; line-height:1.7; }
  .content h2 { color:#c9a84c; font-size:24px; margin-bottom:16px; font-weight:300; }
  .content p { color:#aaaaaa; font-size:14px; margin-bottom:16px; }
  .btn { display:inline-block; background:linear-gradient(135deg,#9a7a35,#c9a84c,#e8c97a);
         color:#0a0a0a; text-decoration:none; padding:14px 36px; border-radius:4px;
         font-weight:700; font-size:13px; letter-spacing:1.5px; text-transform:uppercase; margin:20px 0; }
  .divider { border:none; border-top:1px solid rgba(201,168,76,0.2); margin:28px 0; }
  .footer { background:#0d0d0d; padding:24px 40px; text-align:center; color:#555; font-size:12px; }
  .gold { color:#c9a84c; }
  .order-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #222; font-size:13px; }
  .badge { display:inline-block; background:rgba(201,168,76,0.15); color:#c9a84c;
           padding:4px 12px; border-radius:20px; font-size:12px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo-text">✦ LuxeByLucia ✦</div>
    <div style="font-size:11px;color:#666;letter-spacing:3px;margin-top:6px">ELEGANCE · STYLE · EXCLUSIVITY</div>
  </div>
  <div class="content">
    {$body}
  </div>
  <div class="footer">
    <p>&copy; <?= date('Y') ?> LuxeByLucia. All rights reserved.</p>
    <p>Lagos, Nigeria &nbsp;|&nbsp; hello@luxebylucia.com</p>
    <p style="margin-top:8px;font-size:11px;color:#444">
      You received this email because you have an account with LuxeByLucia.
    </p>
  </div>
</div>
</body>
</html>
HTML;
}

// ── Welcome Email ─────────────────────────────────────────────
function sendWelcomeEmail(array $user): bool {
    $name    = htmlspecialchars($user['first_name']);
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';

    $body = <<<HTML
<h2>Welcome to LuxeByLucia, {$name}! ✨</h2>
<p>Thank you for joining our exclusive community. You now have access to our curated collection of luxury fashion and accessories.</p>
<p>Explore our latest collections and discover pieces that define elegance.</p>
<div style="text-align:center">
  <a href="{$siteUrl}/shop.php" class="btn">Shop Now</a>
</div>
<hr class="divider">
<p style="font-size:13px">As a member, you'll enjoy:</p>
<ul style="color:#aaa;font-size:13px;padding-left:20px;line-height:2.2">
  <li>Early access to new collections</li>
  <li>Exclusive member-only offers</li>
  <li>Order tracking &amp; history</li>
  <li>Wishlist &amp; favourites</li>
</ul>
HTML;

    return sendEmail(
        $user['email'],
        'Welcome to LuxeByLucia — Where Elegance Meets Exclusivity',
        emailTemplate('Welcome', $body)
    );
}

// ── Order Confirmation Email ──────────────────────────────────
function sendOrderConfirmation(array $order, array $items): bool {
    $name    = htmlspecialchars($order['billing_name']);
    $num     = htmlspecialchars($order['order_number']);
    $total   = (defined('CURRENCY') ? CURRENCY : '₦') . number_format($order['total'], 2);
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';

    $itemsHtml = '';
    foreach ($items as $item) {
        $itemName  = htmlspecialchars($item['product_name']);
        $itemPrice = (defined('CURRENCY') ? CURRENCY : '₦') . number_format($item['price'] * $item['quantity'], 2);
        $itemsHtml .= "<div class='order-row'><span>{$itemName} ×{$item['quantity']}</span><span class='gold'>{$itemPrice}</span></div>";
    }

    $body = <<<HTML
<h2>Order Confirmed! 🎉</h2>
<p>Hi {$name}, your order has been received and is being processed.</p>
<div style="background:#0a0a0a;border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:20px;margin:20px 0">
  <p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#c9a84c">Order Number</p>
  <p style="margin:0;font-size:22px;color:#fff;font-weight:300">{$num}</p>
</div>
{$itemsHtml}
<div class="order-row" style="color:#fff;font-weight:600;border-bottom:none">
  <span>Total Paid</span>
  <span class="gold" style="font-size:18px">{$total}</span>
</div>
<div style="text-align:center;margin-top:24px">
  <a href="{$siteUrl}/orders.php?order={$num}" class="btn">Track Your Order</a>
</div>
HTML;

    return sendEmail(
        $order['billing_email'],
        "Order Confirmed — {$num} | LuxeByLucia",
        emailTemplate('Order Confirmed', $body)
    );
}

// ── Payment Success Email ─────────────────────────────────────
function sendPaymentSuccess(array $order): bool {
    $name    = htmlspecialchars($order['billing_name']);
    $num     = htmlspecialchars($order['order_number']);
    $total   = (defined('CURRENCY') ? CURRENCY : '₦') . number_format($order['total'], 2);
    $ref     = htmlspecialchars($order['payment_reference'] ?? 'N/A');
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';

    $body = <<<HTML
<h2>Payment Successful! 💳</h2>
<p>Hi {$name}, your payment of <span class="gold"><strong>{$total}</strong></span> has been confirmed.</p>
<div style="background:#0a0a0a;border-radius:8px;padding:20px;margin:20px 0">
  <div class="order-row"><span>Order Number</span><span class="gold">{$num}</span></div>
  <div class="order-row"><span>Amount Paid</span><span class="gold">{$total}</span></div>
  <div class="order-row"><span>Payment Reference</span><span>{$ref}</span></div>
  <div class="order-row"><span>Status</span><span><span class="badge">Processing</span></span></div>
</div>
<p>We'll notify you when your order ships. Expected delivery within 2–5 business days within Lagos.</p>
<div style="text-align:center">
  <a href="{$siteUrl}/orders.php?order={$num}" class="btn">View Order</a>
</div>
HTML;

    return sendEmail(
        $order['billing_email'],
        "Payment Confirmed — {$num} | LuxeByLucia",
        emailTemplate('Payment Confirmed', $body)
    );
}

// ── Order Status Update Email ─────────────────────────────────
function sendOrderStatusUpdate(array $order): bool {
    $name    = htmlspecialchars($order['billing_name']);
    $num     = htmlspecialchars($order['order_number']);
    $status  = ucfirst($order['order_status']);
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';

    $statusMessages = [
        'processing' => 'Your order is being packed and prepared for dispatch.',
        'shipped'    => '🚚 Great news! Your order is on its way to you.',
        'delivered'  => '✅ Your order has been delivered. We hope you love your new pieces!',
        'cancelled'  => 'Your order has been cancelled. If you have any questions, please contact us.',
    ];
    $msg = $statusMessages[$order['order_status']] ?? 'Your order status has been updated.';

    $body = <<<HTML
<h2>Order Update: {$status}</h2>
<p>Hi {$name}, here's an update on your order <span class="gold"><strong>{$num}</strong></span>.</p>
<div style="text-align:center;margin:32px 0">
  <span class="badge" style="font-size:16px;padding:12px 28px">{$status}</span>
</div>
<p>{$msg}</p>
<div style="text-align:center">
  <a href="{$siteUrl}/orders.php?order={$num}" class="btn">Track Order</a>
</div>
HTML;

    return sendEmail(
        $order['billing_email'],
        "Order Update: {$status} — {$num} | LuxeByLucia",
        emailTemplate('Order Update', $body)
    );
}
