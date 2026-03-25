<?php
// ============================================================
// LUXEBYLUCIA — Register Page
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect('/index.php');

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
        'phone'      => trim($_POST['phone']       ?? ''),
        'password'   => $_POST['password']         ?? '',
        'confirm'    => $_POST['confirm_password'] ?? '',
    ];

    if (!$data['first_name']) $errors[] = 'First name is required.';
    if (!$data['last_name'])  $errors[] = 'Last name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($data['password'] !== $data['confirm']) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt   = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?,?,?,?,?)");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $hashed]);

            $userId = $pdo->lastInsertId();

            // Auto-login
            $_SESSION['user_id']    = $userId;
            $_SESSION['first_name'] = $data['first_name'];
            $_SESSION['email']      = $data['email'];
            $_SESSION['role']       = 'user';

            setFlash('success', 'Welcome to LuxeByLucia, ' . $data['first_name'] . '! 🎉');
            redirect('/index.php');
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card" style="max-width:540px">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="LuxeByLucia">
        </div>
        <h2 class="auth-title">Join the Club</h2>
        <p class="auth-sub">Create your exclusive LuxeByLucia account</p>

        <?php if (!empty($errors)): ?>
        <div style="background:rgba(224,82,82,0.1); border:1px solid var(--red); border-radius:var(--radius); padding:14px; margin-bottom:20px">
            <?php foreach ($errors as $err): ?>
            <div style="font-size:13px; color:var(--red); margin-bottom:4px"><i class="fa fa-exclamation-circle"></i> <?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= isset($data['first_name']) ? e($data['first_name']) : '' ?>"
                           placeholder="Jane" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= isset($data['last_name']) ? e($data['last_name']) : '' ?>"
                           placeholder="Doe" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control"
                       value="<?= isset($data['email']) ? e($data['email']) : '' ?>"
                       placeholder="hello@example.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= isset($data['phone']) ? e($data['phone']) : '' ?>"
                       placeholder="+234 000 000 0000">
            </div>
            <div class="form-group">
                <label>Password * (min. 8 characters)</label>
                <div style="position:relative">
                    <input type="password" name="password" id="passwordField" class="form-control"
                           placeholder="••••••••" required minlength="8">
                    <button type="button" onclick="togglePass()" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--white-muted); font-size:14px">
                        <i class="fa fa-eye" id="passIcon"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control"
                       placeholder="••••••••" required minlength="8">
            </div>

            <!-- Password strength -->
            <div id="strengthBar" style="height:3px; border-radius:2px; background:var(--white-faint); overflow:hidden; margin-top:-12px">
                <div id="strengthFill" style="height:100%; width:0; transition:all 0.3s ease; border-radius:2px"></div>
            </div>
            <div id="strengthLabel" style="font-size:11px; color:var(--white-muted); text-align:right; margin-top:4px"></div>

            <div style="font-size:12px; color:var(--white-muted); line-height:1.6">
                By creating an account, you agree to our
                <a href="#" style="color:var(--gold)">Terms of Service</a> and
                <a href="#" style="color:var(--gold)">Privacy Policy</a>.
            </div>

            <button type="submit" class="btn btn-gold btn-full btn-lg">
                <i class="fa fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
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

document.getElementById('passwordField').addEventListener('input', function() {
    const val = this.value;
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = ['','Weak','Fair','Strong','Very Strong'];
    const colors = ['','#e05252','#e8a84c','#4caf7d','#c9a84c'];
    fill.style.width = (score * 25) + '%';
    fill.style.background = colors[score] || 'transparent';
    label.textContent = val.length > 0 ? levels[score] || '' : '';
    label.style.color = colors[score] || 'var(--white-muted)';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
