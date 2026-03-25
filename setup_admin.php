<?php
// ============================================================
// LUXEBYLUCIA — Admin Setup Script
// ============================================================
// PURPOSE:
//   This script creates the admin account with a CORRECT
//   bcrypt hash generated live on YOUR server.
//
// HOW TO USE:
//   1. Upload this file to your /luxebylucia/ folder
//   2. Open it in your browser:
//      http://localhost/luxebylucia/setup_admin.php
//   3. Done — then DELETE this file immediately!
//
// WHY THIS FILE EXISTS:
//   Bcrypt hashes embedded in SQL files can fail because
//   different PHP versions and server settings produce
//   slightly different hashes. This generates it live.
// ============================================================

// Basic protection — only run once
if (file_exists(__DIR__ . '/.admin_setup_done')) {
    die('<h2 style="color:red;font-family:sans-serif">⛔ Setup already completed. Delete setup_admin.php from your server.</h2>');
}

require_once __DIR__ . '/includes/db.php';

// ── Configuration ─────────────────────────────────────────────
$adminFirstName = 'Lucia';
$adminLastName  = 'Admin';
$adminEmail     = 'admin@luxebylucia.com';
$adminPassword  = 'Admin@1234';
// ─────────────────────────────────────────────────────────────

$errors  = [];
$success = false;

// Generate hash on this server (guaranteed to work)
$hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
$stmt->execute([$adminEmail]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['role'] === 'admin') {
        // Admin exists — just update the password hash
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $adminEmail]);
        $action = 'updated';
    } else {
        // User exists but is not admin — promote and update password
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin', email_verified = 1 WHERE email = ?");
        $stmt->execute([$hash, $adminEmail]);
        $action = 'promoted';
    }
} else {
    // No admin yet — create fresh
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password, role, email_verified)
        VALUES (?, ?, ?, ?, 'admin', 1)
    ");
    $stmt->execute([$adminFirstName, $adminLastName, $adminEmail, $hash]);
    $action = 'created';
}

// Verify the hash works correctly before declaring success
$verify = password_verify($adminPassword, $hash);

if ($verify) {
    // Mark setup as done
    file_put_contents(__DIR__ . '/.admin_setup_done', date('Y-m-d H:i:s'));
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LuxeByLucia — Admin Setup</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: #0a0a0a;
    color: #fff;
    font-family: 'Segoe UI', Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
  }
  .card {
    background: #161616;
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 12px;
    padding: 48px;
    max-width: 520px;
    width: 100%;
    text-align: center;
  }
  .logo { font-size: 2.5rem; margin-bottom: 8px; }
  h1 { font-size: 1.5rem; font-weight: 400; color: #c9a84c; margin-bottom: 32px; }
  .status-ok {
    background: rgba(76,175,77,0.1);
    border: 1px solid #4caf7d;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    color: #4caf7d;
  }
  .status-err {
    background: rgba(224,82,82,0.1);
    border: 1px solid #e05252;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    color: #e05252;
  }
  .credentials {
    background: #0a0a0a;
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 8px;
    padding: 24px;
    margin: 24px 0;
    text-align: left;
  }
  .credentials table { width: 100%; border-collapse: collapse; }
  .credentials td { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
  .credentials td:first-child { color: #888; width: 40%; }
  .credentials td:last-child { color: #c9a84c; font-weight: 600; font-family: monospace; }
  .btn {
    display: inline-block;
    background: linear-gradient(135deg, #9a7a35, #c9a84c, #e8c97a);
    color: #0a0a0a;
    text-decoration: none;
    padding: 14px 36px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 8px;
  }
  .warning {
    background: rgba(224,82,82,0.08);
    border: 1px solid rgba(224,82,82,0.4);
    border-radius: 8px;
    padding: 16px;
    margin-top: 24px;
    font-size: 13px;
    color: #e05252;
    line-height: 1.6;
  }
  .hash-box {
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 12px;
    margin-top: 16px;
    font-family: monospace;
    font-size: 11px;
    color: #888;
    word-break: break-all;
    text-align: left;
  }
  .tag {
    display: inline-block;
    background: rgba(201,168,76,0.15);
    color: #c9a84c;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 12px;
    margin-left: 6px;
  }
</style>
</head>
<body>
<div class="card">
  <div class="logo">🖤</div>
  <h1>LuxeByLucia — Admin Setup</h1>

  <?php if ($success): ?>

  <div class="status-ok">
    <strong>✅ Admin account <?= $action ?> successfully!</strong><br>
    <small>Hash verified and working on this server.</small>
  </div>

  <div class="credentials">
    <p style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c9a84c;margin-bottom:16px">Login Credentials</p>
    <table>
      <tr>
        <td>Email</td>
        <td><?= htmlspecialchars($adminEmail) ?></td>
      </tr>
      <tr>
        <td>Password</td>
        <td><?= htmlspecialchars($adminPassword) ?></td>
      </tr>
      <tr>
        <td>Role</td>
        <td>Admin <span class="tag">Full Access</span></td>
      </tr>
      <tr>
        <td>Table</td>
        <td>users <span class="tag">role = 'admin'</span></td>
      </tr>
    </table>
  </div>

  <p style="font-size:13px;color:#888;margin-bottom:20px;line-height:1.7">
    ℹ️ <strong style="color:#fff">There is no separate "admin" table.</strong><br>
    Admins and customers share the <code style="color:#c9a84c">users</code> table.
    The <code style="color:#c9a84c">role</code> column controls access:
    <code style="color:#c9a84c">'admin'</code> or <code style="color:#c9a84c">'user'</code>.
  </p>

  <a href="admin/index.php" class="btn">Go to Admin Panel →</a>

  <div class="warning">
    ⚠️ <strong>IMPORTANT — Delete this file now!</strong><br>
    Do not leave <code>setup_admin.php</code> on your server.<br>
    It poses a security risk if left accessible.
  </div>

  <div class="hash-box">
    <div style="color:#555;margin-bottom:4px">Generated hash (for your records):</div>
    <?= htmlspecialchars($hash) ?>
  </div>

  <?php else: ?>

  <div class="status-err">
    <strong>❌ Setup failed — hash verification error.</strong><br>
    <small>Your server may have an unusual PHP configuration. Contact your host.</small>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
