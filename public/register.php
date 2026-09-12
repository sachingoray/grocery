<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = 'customer'; // Public registrations are always Customer accounts

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($contactNumber !== '' && preg_match('/[a-zA-Z]/', $contactNumber)) {
        $errors[] = 'Contact number must contain numbers only (no alphabet letters).';
    }
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = mff_db();

        if ($pdo !== null) {
            $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $existing->execute(['email' => $email]);
            if ($existing->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role, contact_number, created_at) VALUES (:name, :email, :hash, :role, :contact, CURRENT_TIMESTAMP)'
                );
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'contact' => $contactNumber,
                ]);
                mff_set_flash('success', 'Account created successfully — please log in.');
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
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="card-artisan" style="max-width:30rem;margin:0 auto;">
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
      <input id="name" name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. John Doe">
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="e.g. john@example.com">
    </div>
    <div class="form-field">
      <label for="contact_number">Contact number</label>
      <input 
        id="contact_number" 
        name="contact_number" 
        type="tel" 
        inputmode="numeric" 
        pattern="[0-9\s\+\-\(\)]*"
        placeholder="e.g. 0412 345 678"
        oninput="this.value = this.value.replace(/[^0-9\+\s\-()]/g, '')"
        onkeypress="return /[0-9\+\s\-\(\)]/.test(event.key)"
        value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="password">Password (min. 8 characters)</label>
      <input id="password" name="password" type="password" required minlength="8">
    </div>
    <div class="form-field">
      <label for="confirm_password">Confirm Password</label>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
    </div>
    <button type="submit" class="btn-tomato" style="width:100%;justify-content:center;">Create account</button>
  </form>

  <p style="margin-top:1rem;text-align:center;font-size:.85rem;color:#56715f;">
    Already have an account? <a href="<?= BASE_URL ?>/login.php" style="color:var(--tomato);font-weight:700;">Log in</a>
  </p>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
