<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin']);

$pdo = mff_db();
$errors = [];
$success = null;

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE USER
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $password = $_POST['password'] ?? '';

        if ($name === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!in_array($role, ['customer', 'admin', 'delivery'], true)) $errors[] = 'Invalid role.';

        if (empty($errors) && $pdo !== null) {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                $errors[] = 'An account with email ' . htmlspecialchars($email) . ' already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, contact_number, created_at) VALUES (:name, :email, :hash, :role, :contact, CURRENT_TIMESTAMP)');
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'contact' => $contactNumber,
                ]);
                mff_set_flash('success', 'User ' . htmlspecialchars($name) . ' (' . ucfirst($role) . ') created successfully.');
                header('Location: ' . BASE_URL . '/admin/manage_users.php');
                exit;
            }
        }
    }

    // 2. EDIT USER DETAILS & PASSWORD
    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0) $errors[] = 'Invalid user ID.';
        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!in_array($role, ['customer', 'admin', 'delivery'], true)) $errors[] = 'Invalid role.';

        if (empty($errors) && $pdo !== null) {
            // Check for duplicate email on other users
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
            $check->execute(['email' => $email, 'id' => $userId]);
            if ($check->fetch()) {
                $errors[] = 'Another user with email ' . htmlspecialchars($email) . ' already exists.';
            } else {
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 6) {
                        $errors[] = 'New password must be at least 6 characters.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, contact_number = :contact, role = :role, password_hash = :hash WHERE id = :id');
                        $stmt->execute([
                            'name' => $name,
                            'email' => $email,
                            'contact' => $contactNumber,
                            'role' => $role,
                            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                            'id' => $userId,
                        ]);
                        mff_set_flash('success', 'User details and password updated for ' . htmlspecialchars($name) . '.');
                        header('Location: ' . BASE_URL . '/admin/manage_users.php');
                        exit;
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, contact_number = :contact, role = :role WHERE id = :id');
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'contact' => $contactNumber,
                        'role' => $role,
                        'id' => $userId,
                    ]);
                    mff_set_flash('success', 'User details updated for ' . htmlspecialchars($name) . '.');
                    header('Location: ' . BASE_URL . '/admin/manage_users.php');
                    exit;
                }
            }
        }
    }

    // 3. DELETE USER
    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $currentLoggedInId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId === $currentLoggedInId) {
            mff_set_flash('error', 'You cannot delete your own active admin account.');
        } elseif ($pdo !== null && $userId > 0) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            mff_set_flash('success', 'User account successfully deleted.');
        }
        header('Location: ' . BASE_URL . '/admin/manage_users.php');
        exit;
    }
}

// Fetch all users with order counts
$users = [];
if ($pdo !== null) {
    $users = $pdo->query('
        SELECT u.id, u.name, u.email, u.role, u.contact_number, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id OR o.customer_name = u.name) as order_count
        FROM users u
        ORDER BY u.created_at DESC
    ')->fetchAll();
}

$pageTitle = 'Manage Users & Passwords';
$activeNav = 'admin_users';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.25rem;margin-bottom:1.5rem;">
  <div>
    <p class="section-eyebrow">Administration &amp; Access Control</p>
    <h1 class="section-title">User &amp; Password Management</h1>
    <p style="font-size:0.9rem;color:#56715f;margin-top:0.25rem;">Full control to view, edit, reset passwords, change roles, or create user accounts.</p>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn-outline">Back to Dashboard</a>
    <button type="button" onclick="openCreateUserModal();" class="btn-tomato">
      <i data-lucide="user-plus" class="icon-sm"></i> Add New User
    </button>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error" style="margin-bottom:1.5rem;">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Quick Search & Filter Controls -->
<div class="catalogue-toolbar" style="margin-bottom:1rem;">
  <div>
    <span style="font-size:.85rem;font-weight:700;color:var(--ink);">Total Registered Users: <?= count($users) ?></span>
  </div>
  <label class="search-field" style="max-width:22rem;">
    <i data-lucide="search" class="icon-sm"></i>
    <input id="user-filter-input" type="search" placeholder="Search by name, email, or role..." oninput="filterUserTable(this.value)">
  </label>
</div>

<!-- Users Table -->
<div class="data-table-wrap">
  <table class="data-table" id="users-table">
    <caption class="sr-only">All system users</caption>
    <thead>
      <tr>
        <th>ID</th>
        <th>User Details</th>
        <th>Role</th>
        <th>Contact Number</th>
        <th>Orders Placed</th>
        <th>Registered On</th>
        <th>Admin Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:#56715f;">No users found in database.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): 
          $regTime = strtotime($u['created_at'] ?? 'now');
          $diff = time() - $regTime;
          $isRecent = ($diff < 86400 * 3);
          $searchData = strtolower($u['name'] . ' ' . $u['email'] . ' ' . $u['role'] . ' ' . ($u['contact_number'] ?? '') . ($isRecent ? ' new recent' : ''));
        ?>
          <tr class="user-row" data-search="<?= htmlspecialchars($searchData) ?>">
            <td style="font-weight:700;color:#56715f;">#<?= (int) $u['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:0.65rem;">
                <div style="width:34px;height:34px;border-radius:50%;background:#eef4f0;color:var(--leaf);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;border:1px solid var(--line);flex-shrink:0;">
                  <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--ink);font-size:.95rem;display:flex;align-items:center;gap:0.35rem;">
                    <?= htmlspecialchars($u['name']) ?>
                    <?php if ($isRecent): ?>
                      <span style="background:var(--gold);color:#4a3200;font-size:0.65rem;font-weight:800;padding:0.12rem 0.45rem;border-radius:999px;text-transform:uppercase;letter-spacing:0.04em;">✨ New</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:.825rem;color:#56715f;"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span style="background:#d1fae5;color:#065f46;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
                  🛡️ Admin
                </span>
              <?php elseif ($u['role'] === 'delivery'): ?>
                <span style="background:#fef3c7;color:#92400e;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
                  🚚 Delivery Driver
                </span>
              <?php else: ?>
                <span style="background:#e0f2fe;color:#0369a1;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
                  🛍️ Customer
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($u['contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars($u['contact_number']) ?>" style="font-weight:600;color:var(--leaf);text-decoration:underline;">
                  <?= htmlspecialchars($u['contact_number']) ?>
                </a>
              <?php else: ?>
                <span style="color:#a0b6a7;">—</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int) $u['order_count'] ?></strong> orders</td>
            <td style="font-size:.825rem;color:#56715f;">
              <div style="font-weight:600;color:var(--ink);"><?= date('d M Y, h:ia', $regTime) ?></div>
              <div style="font-size:0.75rem;color:#8ba593;">
                <?php
                  if ($diff < 60) echo 'Just now';
                  elseif ($diff < 3600) echo floor($diff / 60) . ' mins ago';
                  elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                  else echo floor($diff / 86400) . ' days ago';
                ?>
              </div>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;align-items:center;">
                <button type="button"
                  id="edit-btn-<?= (int)$u['id'] ?>"
                  data-user-id="<?= (int)$u['id'] ?>"
                  onclick="openEditUserModal(<?= (int)$u['id'] ?>, '<?= addslashes(htmlspecialchars($u['name'])) ?>', '<?= addslashes(htmlspecialchars($u['email'])) ?>', '<?= addslashes(htmlspecialchars($u['contact_number'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($u['role'])) ?>')"
                  class="btn-outline" style="font-size:.775rem;padding:.3rem .65rem;display:inline-flex;align-items:center;gap:.3rem;">
                  <i data-lucide="edit-3" class="icon-xs"></i> Edit &amp; Password
                </button>
                <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                  <form method="post" action="<?= BASE_URL ?>/admin/manage_users.php" onsubmit="return confirm('Are you sure you want to delete user <?= addslashes(htmlspecialchars($u['name'])) ?>?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="btn-outline" style="font-size:.775rem;padding:.3rem .65rem;color:var(--tomato);border-color:var(--tomato);" title="Delete user">
                      <i data-lucide="trash-2" class="icon-xs"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD NEW USER                       -->
<!-- ========================================== -->
<div id="createUserModal" class="modal-overlay hidden" style="display:none;" onclick="if(event.target===this) closeCreateUserModal();">
  <div class="card-artisan" style="width:100%;max-width:32rem;max-height:90vh;overflow-y:auto;background:#fff;border-radius:1.5rem;padding:2rem;box-shadow:0 20px 40px rgba(0,0,0,0.25);position:relative;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;border-bottom:1px solid var(--line);padding-bottom:.75rem;">
      <h3 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:var(--ink);margin:0;">Add New User Account</h3>
      <button type="button" onclick="closeCreateUserModal();" aria-label="Close modal" style="background:none;border:none;font-size:1.75rem;line-height:1;color:#56715f;cursor:pointer;padding:0.25rem 0.5rem;">&times;</button>
    </div>

    <form method="post" action="<?= BASE_URL ?>/admin/manage_users.php" class="form-grid" style="grid-template-columns:1fr;gap:.9rem;">
      <input type="hidden" name="action" value="create_user">

      <div class="form-field">
        <label for="create_name">Full Name *</label>
        <input id="create_name" name="name" type="text" placeholder="e.g. Alex Morgan" required>
      </div>

      <div class="form-field">
        <label for="create_email">Email Address *</label>
        <input id="create_email" name="email" type="email" placeholder="e.g. alex@example.com" required>
      </div>

      <div class="form-field">
        <label for="create_contact">Contact Phone Number</label>
        <input 
          id="create_contact" 
          name="contact_number" 
          type="tel" 
          inputmode="numeric" 
          pattern="[0-9\s\+\-\(\)]*" 
          placeholder="e.g. 0412 345 678"
          oninput="this.value = this.value.replace(/[^0-9\+\s\-()]/g, '')"
          onkeypress="return /[0-9\+\s\-\(\)]/.test(event.key)">
      </div>

      <div class="form-field">
        <label for="create_role">Account Role *</label>
        <select id="create_role" name="role" required>
          <option value="customer">🛍️ Customer (Standard User)</option>
          <option value="delivery">🚚 Delivery Driver (Queue &amp; Status Access)</option>
          <option value="admin">🛡️ Administrator (Full Control)</option>
        </select>
      </div>

      <div class="form-field">
        <label for="create_password">Initial Password *</label>
        <input id="create_password" name="password" type="text" value="Welcome123!" required>
        <p style="font-size:.75rem;color:#56715f;margin-top:.25rem;">Default password set to <code>Welcome123!</code> (user can change later).</p>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.25rem;">
        <button type="button" onclick="closeCreateUserModal();" class="btn-outline">Cancel</button>
        <button type="submit" class="btn-tomato">Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT USER & CHANGE PASSWORD        -->
<!-- ========================================== -->
<div id="editUserModal" class="modal-overlay hidden" style="display:none;" onclick="if(event.target===this) closeEditUserModal();">
  <div class="card-artisan" style="width:100%;max-width:32rem;max-height:90vh;overflow-y:auto;background:#fff;border-radius:1.5rem;padding:2rem;box-shadow:0 20px 40px rgba(0,0,0,0.25);position:relative;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;border-bottom:1px solid var(--line);padding-bottom:.75rem;">
      <h3 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:var(--ink);margin:0;">Edit User &amp; Change Password</h3>
      <button type="button" onclick="closeEditUserModal();" aria-label="Close modal" style="background:none;border:none;font-size:1.75rem;line-height:1;color:#56715f;cursor:pointer;padding:0.25rem 0.5rem;">&times;</button>
    </div>

    <form method="post" action="<?= BASE_URL ?>/admin/manage_users.php" class="form-grid" style="grid-template-columns:1fr;gap:.9rem;">
      <input type="hidden" name="action" value="update_user">
      <input type="hidden" id="edit_user_id" name="user_id" value="">

      <div class="form-field">
        <label for="edit_name">Full Name *</label>
        <input id="edit_name" name="name" type="text" required>
      </div>

      <div class="form-field">
        <label for="edit_email">Email Address *</label>
        <input id="edit_email" name="email" type="email" required>
      </div>

      <div class="form-field">
        <label for="edit_contact">Contact Phone Number</label>
        <input 
          id="edit_contact" 
          name="contact_number" 
          type="tel" 
          inputmode="numeric" 
          pattern="[0-9\s\+\-\(\)]*" 
          placeholder="e.g. 0412 345 678"
          oninput="this.value = this.value.replace(/[^0-9\+\s\-()]/g, '')"
          onkeypress="return /[0-9\+\s\-\(\)]/.test(event.key)">
      </div>

      <div class="form-field">
        <label for="edit_role">Account Role *</label>
        <select id="edit_role" name="role" required>
          <option value="customer">🛍️ Customer (Standard User)</option>
          <option value="delivery">🚚 Delivery Driver (Delivery Queue Access)</option>
          <option value="admin">🛡️ Administrator (Full Control)</option>
        </select>
      </div>

      <div class="form-field" style="background:#fdfcf9;border:1px dashed var(--line);padding:1rem;border-radius:0.75rem;">
        <label for="edit_password" style="color:var(--leaf);font-weight:700;">Reset / Change Password</label>
        <input id="edit_password" name="new_password" type="text" placeholder="Leave blank to keep existing password">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.4rem;">
          <p style="font-size:.75rem;color:#56715f;margin:0;">Enter a new password (min 6 characters).</p>
          <button type="button" onclick="generateRandomPass()" style="font-size:.75rem;font-weight:700;color:var(--leaf);text-decoration:underline;">Generate Password</button>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.25rem;">
        <button type="button" onclick="closeEditUserModal();" class="btn-outline">Cancel</button>
        <button type="submit" class="btn-tomato">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterUserTable(query) {
  query = query.toLowerCase().trim();
  document.querySelectorAll('.user-row').forEach(row => {
    const searchBlob = row.dataset.search || '';
    row.style.display = searchBlob.includes(query) ? '' : 'none';
  });
}

function openCreateUserModal() {
  const modal = document.getElementById('createUserModal');
  if (modal) {
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
  }
}

function closeCreateUserModal() {
  const modal = document.getElementById('createUserModal');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.add('hidden');
  }
}

function openEditUserModal(id, name, email, contact, role) {
  document.getElementById('edit_user_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_email').value = email;
  document.getElementById('edit_contact').value = contact;
  document.getElementById('edit_role').value = role;
  document.getElementById('edit_password').value = '';
  const modal = document.getElementById('editUserModal');
  if (modal) {
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
  }
}

function closeEditUserModal() {
  const modal = document.getElementById('editUserModal');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.add('hidden');
  }
}

function generateRandomPass() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
  let pass = '';
  for (let i = 0; i < 10; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  document.getElementById('edit_password').value = pass;
}

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeCreateUserModal();
    closeEditUserModal();
  }
});

// Auto open modal if requested via URL parameter (?action=create or ?edit=ID)
window.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('action') === 'create') {
    openCreateUserModal();
  } else if (params.get('edit')) {
    const editId = params.get('edit');
    const btn = document.getElementById('edit-btn-' + editId);
    if (btn) btn.click();
  }
});
</script>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
