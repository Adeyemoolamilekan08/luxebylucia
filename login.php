<?php
// ============================================================
// LUXEBYLUCIA — Login Page
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) redirect('/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['role']       = $user['role'];

            $redirect = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="LuxeByLucia">
        </div>
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-sub">Sign in to your LuxeByLucia account</p>

        <?php if ($error): ?>
        <div style="background:rgba(224,82,82,0.1); border:1px solid var(--red); border-radius:var(--radius); padding:14px; margin-bottom:20px; font-size:13px; color:var(--red); text-align:center">
            <i class="fa fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                       placeholder="hello@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div style="position:relative">
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                    <button type="button" onclick="togglePass()" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--white-muted); font-size:14px">
                        <i class="fa fa-eye" id="passIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-gold btn-full btn-lg">
                <i class="fa fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Create one</a>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const f = document.getElementById('passwordField');
    const i = document.getElementById('passIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
