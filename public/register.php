<?php
require_once __DIR__ . '/../includes/session.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!in_array($role, ['customer', 'admin', 'delivery'], true)) $errors[] = 'Choose a valid role.';

    if (empty($errors)) {
        $pdo = mff_db();

        if ($pdo !== null) {
            $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $existing->execute(['email' => $email]);
            if ($existing->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :hash, :role, NOW())'
                );
                $stmt->execute([
                    'name' => $name, 'email' => $email,
                    'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role,
                ]);
                mff_set_flash('success', 'Account created — please log in.');
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
        } else {
            mff_set_role($role);
            mff_set_flash('info', 'Account created (demo mode — no database connected yet).');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>

<div class="card-artisan" style="max-width:28rem;margin:0 auto;">
  <h1 class="section-title" style="font-size:2rem;text-align:center;">Create your account</h1>

  <?php if (!empty($errors)): ?>
    <div class="flash flash--error" style="margin-top:1rem;max-width:none;">
      <ul style="margin:0;padding-left:1.1rem;">
        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/register.php" class="form-grid" style="grid-template-columns:1fr;margin-top:1.5rem;">
    <div class="form-field">
      <label for="name">Full name</label>
      <input id="name" name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required minlength="8">
    </div>
    <div class="form-field">
      <label for="role">I am a</label>
      <select id="role" name="role">
        <option value="customer">Customer</option>
        <option value="admin">Admin</option>
        <option value="delivery">Delivery driver</option>
      </select>
    </div>
    <button type="submit" class="btn-tomato" style="width:100%;justify-content:center;">Create account</button>
  </form>

  <p style="margin-top:1rem;text-align:center;font-size:.85rem;color:#56715f;">
    Already have an account? <a href="<?= BASE_URL ?>/login.php" style="color:var(--tomato);font-weight:700;">Log in</a>
  </p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
