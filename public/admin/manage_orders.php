<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin', 'logistics_manager', 'support_staff']);

$pdo = mff_db();

$drivers = [];
if ($pdo !== null) {
    $driverStmt = $pdo->query("SELECT name FROM users WHERE role = 'delivery' ORDER BY name ASC");
    $drivers = $driverStmt->fetchAll(PDO::FETCH_COLUMN);
}
if (empty($drivers)) {
    $drivers = ['Chris Allen', 'Jordan Lee'];
}

$statuses = ['pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $driver = trim($_POST['driver'] ?? '') ?: null;

    if (in_array($status, $statuses, true) && $pdo !== null) {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, driver = :driver WHERE id = :id');
        $stmt->execute(['status' => $status, 'driver' => $driver, 'id' => $orderId]);
        mff_set_flash('success', 'Order #MFF-' . $orderId . ' updated.');
    } else {
        mff_set_flash('info', 'No database connected — change not persisted (demo mode).');
    }
    header('Location: ' . BASE_URL . '/admin/manage_orders.php');
    exit;
}

$orders = $pdo !== null
    ? $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll()
    : mff_orders_fallback();

$pageTitle = 'Manage Orders';
$activeNav = 'admin_orders';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<p class="section-eyebrow">Order control</p>
<h1 class="section-title">Manage orders</h1>

<div class="data-table-wrap">
  <table class="data-table">
    <caption class="sr-only">Admin order management table</caption>
    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Driver</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td style="font-weight:700;">#MFF-<?= (int) $order['id'] ?></td>
          <td><?= htmlspecialchars($order['customer_name']) ?></td>
          <form method="post" action="<?= BASE_URL ?>/admin/manage_orders.php" style="display:contents;">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <td>
              <select name="status">
                <?php foreach ($statuses as $status): ?>
                  <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $statusLabels[$status] ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <select name="driver">
                <option value="">Unassigned</option>
                <?php foreach ($drivers as $driver): ?>
                  <option value="<?= htmlspecialchars($driver) ?>" <?= ($order['driver'] ?? '') === $driver ? 'selected' : '' ?>><?= htmlspecialchars($driver) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><button type="submit" class="btn-outline">Save</button></td>
          </form>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
