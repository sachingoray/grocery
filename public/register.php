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
            mff_set_flash('info', 'Account created (demo mode - no database connected yet).');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
$pageTitle = 'Register';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
// Only repopulate after a failed POST — a fresh GET must always render empty
// fields. Without this guard the browser's autofill + sticky $_POST can make
// the email look "already filled".
$isPostback = ($_SERVER['REQUEST_METHOD'] === 'POST') && !empty($errors);
$oldName = $isPostback ? ($_POST['name'] ?? '') : '';
$oldEmail = $isPostback ? ($_POST['email'] ?? '') : '';
$oldContact = $isPostback ? ($_POST['contact_number'] ?? '') : '';
?>
<div class="card-artisan u-201">
  <h1 class="section-title u-139">Create your account</h1>
  <?php if (!empty($errors)): ?>
    <div class="flash flash--error u-196">
      <ul class="u-159">
        <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
<form method="post" action="<?= BASE_URL ?>/register.php" class="form-grid" style="grid-template-columns:1fr;margin-top:1.5rem;">
    <div class="form-field">
      <label for="name">Full name</label>
      <input id="name" name="name" type="text" required autocomplete="name" maxlength="100" value="<?= htmlspecialchars($oldName) ?>" placeholder="e.g. John Doe">
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="email" autocapitalize="off" spellcheck="false" maxlength="255" value="<?= htmlspecialchars($oldEmail) ?>" placeholder="e.g. john@example.com">
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
        autocomplete="tel"
        data-input-filter="phone"
        value="<?= htmlspecialchars($oldContact) ?>">
    </div>
    <div class="form-field">
      <label for="password">Password (min. 8 characters)</label>
      <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" value="">
    </div>
    <div class="form-field">
      <label for="confirm_password">Confirm Password</label>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password" value="">
    </div>
    <button type="submit" class="btn-tomato u-225">Create account</button>
  </form>
  <p class="u-197">
    Already have an account? <a href="<?= BASE_URL ?>/login.php" class="u-047">Log in</a>
  </p>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
