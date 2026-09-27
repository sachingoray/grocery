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
            // New session id on privilege change: prevents session fixation and
            // guarantees the incoming guest session is cleanly separated from
            // the authenticated one.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_contact'] = $user['contact_number'] ?? '';
            mff_set_role($user['role']);
            // Hand this browser's guest cart over to the account that just
            // signed in, and load that account's own saved cart. Without this,
            // a previous account's cart could be inherited via the shared
            // browser session.
            cart_merge_guest_into_user();
            mff_set_flash('success', 'Welcome back, ' . $user['name'] . ' (' . ucfirst($user['role']) . ').');
            if (in_array($user['role'], ['admin', 'logistics_manager'], true)) {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'inventory_manager') {
                header('Location: ' . BASE_URL . '/admin/manage_products.php');
            } elseif ($user['role'] === 'support_staff') {
                header('Location: ' . BASE_URL . '/admin/manage_orders.php');
            } elseif ($user['role'] === 'delivery') {
                header('Location: ' . BASE_URL . '/delivery/my_deliveries.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
        }
        $error = 'Incorrect email or password.';
    } else {
        // No DB configured yet - accept any credentials in dev/demo mode
        mff_set_role('customer');
        mff_set_flash('info', 'Signed in (demo mode - no database connected yet).');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

$pageTitle = 'Log In';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="card-artisan u-202">
  <h1 class="section-title u-139">Welcome back</h1>
  <?php if ($error): ?>
    <div class="flash flash--error u-196"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post" action="<?= BASE_URL ?>/login.php" class="form-grid u-151">
    <div class="form-field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="username">
    </div>
    <div class="form-field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn-tomato u-225">Log in</button>
  </form>
  <p class="u-197">
    New here? <a href="<?= BASE_URL ?>/register.php" class="u-047">Create an account</a>
  </p>
  <div class="u-184">
    <p class="u-100">Role Testing Credentials (All Roles Seeded)</p>
    <div class="u-081">
      <div>ðŸ›¡ï¸ <strong>Admin:</strong><br><code>admin@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>admin123</code></div>
      <div>🚚 <strong>Logistics Mgr:</strong><br><code>logistics@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>logistics123</code></div>
      <div>📦 <strong>Inventory Mgr:</strong><br><code>inventory@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>inventory123</code></div>
      <div>🎧 <strong>Customer Support:</strong><br><code>support@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>support123</code></div>
      <div>🚚 <strong>Delivery Driver:</strong><br><code>driver@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>driver123</code></div>
      <div>ðŸ›ï¸ <strong>Customer:</strong><br><code>customer@maxifinefoods.com.au</code><br><span class="u-033">Pass:</span> <code>customer123</code></div>
    </div>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
