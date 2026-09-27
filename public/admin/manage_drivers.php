<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin', 'logistics_manager']);
$pdo = mff_db();
$errors = [];
// Handle Driver Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // 1. Add New Driver
    if ($action === 'create_driver') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name === '') $errors[] = 'Driver name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (empty($errors) && $pdo !== null) {
            $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $existing->execute(['email' => $email]);
            if ($existing->fetch()) {
                $errors[] = 'A user with that email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role, contact_number, created_at)
                     VALUES (:name, :email, :hash, "delivery", :contact, CURRENT_TIMESTAMP)'
                );
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'contact' => $contactNumber,
                ]);
                mff_set_flash('success', 'Driver ' . htmlspecialchars($name) . ' added successfully.');
                header('Location: ' . BASE_URL . '/admin/manage_drivers.php');
                exit;
            }
        } elseif (empty($errors)) {
            mff_set_flash('info', 'No database connected â€” demo mode.');
            header('Location: ' . BASE_URL . '/admin/manage_drivers.php');
            exit;
        }
    }
    // 2. Delete Driver
    if ($action === 'delete_driver') {
        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $driverName = trim($_POST['driver_name'] ?? '');
        if ($pdo !== null && $driverId > 0) {
            // Unassign orders currently held by this driver
            if ($driverName !== '') {
                $unassignStmt = $pdo->prepare('UPDATE orders SET driver = NULL WHERE driver = :driver AND status IN ("pending", "processing", "out_for_delivery")');
                $unassignStmt->execute(['driver' => $driverName]);
            }
            // Delete user
            $delStmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "delivery"');
            $delStmt->execute(['id' => $driverId]);
            mff_set_flash('success', 'Driver removed and their pending deliveries were unassigned.');
        } else {
            mff_set_flash('info', 'No database connected (demo mode).');
        }
        header('Location: ' . BASE_URL . '/admin/manage_drivers.php');
        exit;
    }
}
// Fetch Drivers with Workload
if ($pdo !== null) {
    $drivers = $pdo->query("
        SELECT u.id, u.name, u.email, u.contact_number, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status IN ('processing', 'out_for_delivery')) AS active_deliveries,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status = 'delivered') AS completed_deliveries,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name) AS total_deliveries
        FROM users u
        WHERE u.role = 'delivery'
        ORDER BY u.name ASC
    ")->fetchAll();
    // Fetch active orders assigned per driver for quick drill-down
    $activeOrders = $pdo->query("
        SELECT id, customer_name, delivery_address, status, driver, total
        FROM orders
        WHERE status IN ('processing', 'out_for_delivery') AND driver IS NOT NULL
        ORDER BY created_at ASC
    ")->fetchAll();
} else {
    $drivers = [
        ['id' => 2, 'name' => 'Chris Allen', 'email' => 'driver@maxifinefoods.com.au', 'contact_number' => '0400 000 002', 'created_at' => '2026-08-01 10:00:00', 'active_deliveries' => 1, 'completed_deliveries' => 12, 'total_deliveries' => 13],
        ['id' => 3, 'name' => 'Jordan Lee', 'email' => 'jordan@maxifinefoods.com.au', 'contact_number' => '0400 000 003', 'created_at' => '2026-08-05 11:30:00', 'active_deliveries' => 1, 'completed_deliveries' => 8, 'total_deliveries' => 9],
    ];
    $activeOrders = [];
}
$pageTitle = 'Manage Delivery Drivers';
$activeNav = 'admin_drivers';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>
<div class="u-062">
  <div>
    <p class="section-eyebrow">Fleet logistics</p>
    <h1 class="section-title">Manage delivery drivers</h1>
  </div>
  <div class="u-066">
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline">Manage orders</a>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn-outline">Dashboard</a>
  </div>
</div>
<?php if (!empty($errors)): ?>
  <div class="flash flash--error u-196">
    <ul class="u-159">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<!-- Add New Driver Card -->
<div class="card-artisan u-188">
  <h2 class="u-134">+ Add new delivery driver account</h2>
  <p class="u-125">Create a login account for a new courier or delivery personnel</p>
<form method="post" action="<?= BASE_URL ?>/admin/manage_drivers.php" class="form-grid">
    <input type="hidden" name="action" value="create_driver">
    <div class="form-field">
      <label for="driver-name">Driver full name</label>
      <input id="driver-name" name="name" required type="text" placeholder="e.g. Alex Shrestha" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="driver-email">Email (Login username)</label>
      <input id="driver-email" name="email" required type="email" placeholder="e.g. alex@maxifinefoods.com.au" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="driver-phone">Contact phone number</label>
      <input id="driver-phone" name="contact_number" type="tel" placeholder="e.g. 0412 345 678" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
    </div>
    <div class="form-field">
      <label for="driver-password">Temporary password (min. 6 chars)</label>
      <input id="driver-password" name="password" required type="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
    </div>
    <div class="form-field--full u-176">
      <button type="submit" class="btn-tomato">Create driver account</button>
    </div>
  </form>
</div>
<!-- Active Driver Fleet Table -->
<h2 class="u-133">ðŸšš Registered delivery drivers (<?= count($drivers) ?>)</h2>
<div class="data-table-wrap u-177">
  <table class="data-table">
    <caption class="sr-only">Delivery drivers roster</caption>
    <thead>
      <tr>
        <th>Driver</th>
        <th>Email</th>
        <th>Phone number</th>
        <th>Active in progress</th>
        <th>Completed orders</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($drivers)): ?>
        <tr><td colspan="6" class="u-213">No delivery drivers registered yet.</td></tr>
      <?php else: ?>
        <?php foreach ($drivers as $driver): ?>
          <tr>
            <td class="u-144">
              ðŸšš <?= htmlspecialchars($driver['name']) ?>
            </td>
            <td><?= htmlspecialchars($driver['email']) ?></td>
            <td>
              <?php if (!empty($driver['contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $driver['contact_number'])) ?>" class="u-143">
                  <?= htmlspecialchars($driver['contact_number']) ?>
                </a>
              <?php else: ?>
                <span class="u-033">â€”</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$driver['active_deliveries'] > 0): ?>
                <span class="status status-processing u-144"><?= (int)$driver['active_deliveries'] ?> active</span>
              <?php else: ?>
                <span class="status status-instock u-011">0 idle</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int)$driver['completed_deliveries'] ?></strong> delivered</td>
            <td>
              <div class="u-067">
                <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline u-119">Assign</a>
<form method="post" action="<?= BASE_URL ?>/admin/manage_drivers.php" data-confirm="Are you sure you want to remove driver <?= htmlspecialchars($driver['name'], ENT_QUOTES) ?>?" class="u-157">
                  <input type="hidden" name="action" value="delete_driver">
                  <input type="hidden" name="driver_id" value="<?= (int) $driver['id'] ?>">
                  <input type="hidden" name="driver_name" value="<?= htmlspecialchars($driver['name']) ?>">
                  <button type="submit" class="btn-outline u-046">Remove</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
