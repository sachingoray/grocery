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
        if (!in_array($role, ['customer', 'admin', 'delivery', 'inventory_manager', 'logistics_manager', 'support_staff'], true)) $errors[] = 'Invalid role.';
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
                mff_set_flash('success', 'User ' . htmlspecialchars($name) . ' (' . mff_role_label($role) . ') created successfully.');
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
        if (!in_array($role, ['customer', 'admin', 'delivery', 'inventory_manager', 'logistics_manager', 'support_staff'], true)) $errors[] = 'Invalid role.';
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
<div class="u-063">
  <div>
    <p class="section-eyebrow">Administration &amp; Access Control</p>
    <h1 class="section-title">User &amp; Password Management</h1>
    <p class="u-127">Full control to view, edit, reset passwords, change roles, or create user accounts.</p>
  </div>
  <div class="u-066">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn-outline">Back to Dashboard</a>
    <button type="button" data-open-modal="createUserModal" class="btn-tomato">
      <i data-lucide="user-plus" class="icon-sm"></i> Add New User
    </button>
  </div>
</div>
<?php if (!empty($errors)): ?>
  <div class="flash flash--error u-162">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<!-- Quick Search & Filter Controls -->
<div class="catalogue-toolbar u-163">
  <div>
    <span class="u-112">Total Registered Users: <?= count($users) ?></span>
  </div>
  <label class="search-field u-200">
    <i data-lucide="search" class="icon-sm"></i>
    <input id="user-filter-input" type="search" placeholder="Search by name, email, or role..." data-filter-user-table>
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
        <tr><td colspan="7" class="u-214">No users found in database.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): 
          $regTime = strtotime($u['created_at'] ?? 'now');
          $diff = time() - $regTime;
          $isRecent = ($diff < 86400 * 3);
          $searchData = strtolower($u['name'] . ' ' . $u['email'] . ' ' . $u['role'] . ' ' . ($u['contact_number'] ?? '') . ($isRecent ? ' new recent' : ''));
        ?>
          <tr class="user-row" data-search="<?= htmlspecialchars($searchData) ?>">
            <td class="u-145">#<?= (int) $u['id'] ?></td>
            <td>
              <div class="u-054">
                <div class="u-230">
                  <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                  <div class="u-147">
                    <?= htmlspecialchars($u['name']) ?>
                    <?php if ($isRecent): ?>
                      <span class="u-025">✨ New</span>
                    <?php endif; ?>
                  </div>
                  <div class="u-107"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span class="u-006">
                  ðŸ›¡ï¸ Admin
                </span>
              <?php elseif ($u['role'] === 'logistics_manager'): ?>
                <span class="u-020">
                  🚚 Fleet Mgr
                </span>
              <?php elseif ($u['role'] === 'inventory_manager'): ?>
                <span class="u-008">
                  📦 Product Mgr
                </span>
              <?php elseif ($u['role'] === 'support_staff'): ?>
                <span class="u-013">
                  🎧 Support Staff
                </span>
              <?php elseif ($u['role'] === 'delivery'): ?>
                <span class="u-022">
                  🚚 Driver
                </span>
              <?php else: ?>
                <span class="u-010">
                  ðŸ›ï¸ Customer
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($u['contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars($u['contact_number']) ?>" class="u-143">
                  <?= htmlspecialchars($u['contact_number']) ?>
                </a>
              <?php else: ?>
                <span class="u-034">—</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int) $u['order_count'] ?></strong> orders</td>
            <td class="u-107">
              <div class="u-142"><?= date('d M Y, h:ia', $regTime) ?></div>
              <div class="u-118">
                <?php
                  if ($diff < 60) echo 'Just now';
                  elseif ($diff < 3600) echo floor($diff / 60) . ' mins ago';
                  elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                  else echo floor($diff / 86400) . ' days ago';
                ?>
              </div>
            </td>
            <td>
              <div class="u-065">
                <button type="button"
                  id="edit-btn-<?= (int)$u['id'] ?>"
                  data-user-id="<?= (int)$u['id'] ?>"
                  data-user-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                  data-user-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                  data-user-contact="<?= htmlspecialchars($u['contact_number'] ?? '', ENT_QUOTES) ?>"
                  data-user-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
                  data-open-edit-user
                  class="btn-outline u-103">
                  <i data-lucide="edit-3" class="icon-xs"></i> Edit &amp; Password
                </button>
                <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
<form method="post" action="<?= BASE_URL ?>/admin/manage_users.php" data-confirm="Are you sure you want to delete user <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?" class="u-084">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="btn-outline u-102" title="Delete user">
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
<div id="createUserModal" class="modal-overlay hidden" data-dismiss-modal="createUserModal">
  <div class="card-artisan u-227">
    <div class="u-071">
      <h3 class="u-092">Add New User Account</h3>
      <button type="button" data-close-modal="createUserModal" aria-label="Close modal" class="u-023">&times;</button>
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
          data-input-filter="phone">
      </div>
      <div class="form-field">
        <label for="create_role">Account Role *</label>
        <select id="create_role" name="role" required>
          <option value="customer">ðŸ›ï¸ Customer (Standard Store User)</option>
          <option value="delivery">🚚 Delivery Driver (Delivery App Access)</option>
          <option value="support_staff">🎧 Customer Service Staff (Order &amp; Inquiries)</option>
          <option value="inventory_manager">📦 Product &amp; Inventory Manager (Catalog &amp; Stock)</option>
          <option value="logistics_manager">🚚 Fleet &amp; Logistics Manager (Driver &amp; Delivery)</option>
          <option value="admin">ðŸ›¡ï¸ System Administrator (Full System Control)</option>
        </select>
      </div>
      <div class="form-field">
        <label for="create_password">Initial Password *</label>
        <input id="create_password" name="password" type="text" value="Welcome123!" required>
        <p class="u-099">Default password set to <code>Welcome123!</code> (user can change later).</p>
      </div>
      <div class="u-069">
        <button type="button" data-close-modal="createUserModal" class="btn-outline">Cancel</button>
        <button type="submit" class="btn-tomato">Create User</button>
      </div>
    </form>
  </div>
</div>
<!-- ========================================== -->
<!-- MODAL: EDIT USER & CHANGE PASSWORD        -->
<!-- ========================================== -->
<div id="editUserModal" class="modal-overlay hidden" data-dismiss-modal="editUserModal">
  <div class="card-artisan u-227">
    <div class="u-071">
      <h3 class="u-092">Edit User &amp; Change Password</h3>
      <button type="button" data-close-modal="editUserModal" aria-label="Close modal" class="u-023">&times;</button>
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
          data-input-filter="phone">
      </div>
      <div class="form-field">
        <label for="edit_role">Account Role *</label>
        <select id="edit_role" name="role" required>
          <option value="customer">ðŸ›ï¸ Customer (Standard Store User)</option>
          <option value="delivery">🚚 Delivery Driver (Delivery App Access)</option>
          <option value="support_staff">🎧 Customer Service Staff (Order &amp; Inquiries)</option>
          <option value="inventory_manager">📦 Product &amp; Inventory Manager (Catalog &amp; Stock)</option>
          <option value="logistics_manager">🚚 Fleet &amp; Logistics Manager (Driver &amp; Delivery)</option>
          <option value="admin">ðŸ›¡ï¸ System Administrator (Full System Control)</option>
        </select>
      </div>
      <div class="form-field u-016">
        <label for="edit_password" class="u-040">Reset / Change Password</label>
        <input id="edit_password" name="new_password" type="text" placeholder="Leave blank to keep existing password">
        <div class="u-072">
          <p class="u-098">Enter a new password (min 6 characters).</p>
          <button type="button" data-generate-password="edit_password" class="u-101">Generate Password</button>
        </div>
      </div>
      <div class="u-069">
        <button type="button" data-close-modal="editUserModal" class="btn-outline">Cancel</button>
        <button type="submit" class="btn-tomato">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/manage-users.js?v=<?= time() ?>" defer></script>
<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
