<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo = mff_db();

    if ($pdo !== null) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_contact'] = $user['contact_number'] ?? '';
            mff_set_role($user['role']);
            mff_set_flash('success', 'Welcome back, ' . $user['name'] . ' (' . ucfirst($user['role']) . ').');

            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'delivery') {
                header('Location: ' . BASE_URL . '/delivery/my_deliveries.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
        }
        $error = 'Incorrect email or password.';
    } else {
        // No DB configured yet — accept any credentials in dev/demo mode
        mff_set_role('customer');
        mff_set_flash('info', 'Signed in (demo mode — no database connected yet).');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

$pageTitle = 'Log In';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="card-artisan" style="max-width:28rem;margin:0 auto;">
  <h1 class="section-title" style="font-size:2rem;text-align:center;">Welcome back</h1>

  <?php if ($error): ?>
    <div class="flash flash--error" style="margin-top:1rem;max-width:none;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/login.php" class="form-grid" style="grid-template-columns:1fr;margin-top:1.5rem;">
    <div class="form-field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required>
    </div>
    <div class="form-field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit" class="btn-tomato" style="width:100%;justify-content:center;">Log in</button>
  </form>

  <p style="margin-top:1rem;text-align:center;font-size:.85rem;color:#56715f;">
    New here? <a href="<?= BASE_URL ?>/register.php" style="color:var(--tomato);font-weight:700;">Create an account</a>
  </p>

  <div style="margin-top:1.5rem;border-top:1px solid var(--line);padding-top:1.25rem;">
    <p style="font-size:.75rem;font-weight:700;color:var(--leaf);margin-bottom:.5rem;">Demo Testing Credentials</p>
    <div style="font-size:0.75rem;color:#56715f;line-height:1.6;background:#fdfcf9;border:1px solid var(--line);border-radius:0.5rem;padding:0.75rem;">
      <div>🛡️ <strong>Admin:</strong> <code>admin@maxifinefoods.com.au</code> / <code>admin123</code></div>
      <div>🚚 <strong>Driver:</strong> <code>driver@maxifinefoods.com.au</code> / <code>driver123</code></div>
      <div>🛍️ <strong>Customer:</strong> <code>customer@maxifinefoods.com.au</code> / <code>customer123</code></div>
    </div>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
