<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin']);

$pdo = mff_db();
if ($pdo !== null) {
    $orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 10')->fetchAll();
    $totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $totalRevenue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
    $pendingOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn();
    $lowStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= low_stock_threshold')->fetchColumn();

    $drivers = $pdo->query("
        SELECT u.id, u.name, u.email, u.contact_number, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status IN ('processing', 'out_for_delivery')) AS active_deliveries,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status = 'delivered') AS completed_deliveries
        FROM users u
        WHERE u.role = 'delivery'
        ORDER BY u.name ASC
    ")->fetchAll();
    $totalDrivers = count($drivers);
} else {
    $orders = mff_orders_fallback();
    $totalOrders = 128;
    $totalRevenue = 8742.00;
    $pendingOrders = 12;
    $products = mff_products_fallback();
    $lowStock = count(array_filter($products, fn($p) => $p['stock'] <= $p['low_stock_threshold']));
    $totalDrivers = 2;
    $drivers = [
        ['id' => 2, 'name' => 'Chris Allen', 'email' => 'driver@maxifinefoods.com.au', 'contact_number' => '0400 000 002', 'active_deliveries' => 1, 'completed_deliveries' => 12],
        ['id' => 3, 'name' => 'Jordan Lee', 'email' => 'jordan@maxifinefoods.com.au', 'contact_number' => '0400 000 003', 'active_deliveries' => 1, 'completed_deliveries' => 8],
    ];
}

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.25rem;">
  <div>
    <p class="section-eyebrow">Store management</p>
    <h1 class="section-title">Admin dashboard</h1>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline">Manage orders</a>
    <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="btn-outline">Manage drivers</a>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-tomato">Add new product</a>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));">
  <div class="stat-card"><p class="stat-card__label">Total orders</p><p class="stat-card__value"><?= number_format($totalOrders) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Revenue</p><p class="stat-card__value"><?= mff_money($totalRevenue) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Pending orders</p><p class="stat-card__value"><?= number_format($pendingOrders) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Delivery drivers</p><p class="stat-card__value"><?= number_format($totalDrivers) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Low stock items</p><p class="stat-card__value"><?= number_format($lowStock) ?></p></div>
</div>

<!-- Delivery Fleet Overview Section -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">🚚 Delivery drivers fleet</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">Active driver team and delivery workloads</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="btn-outline" style="font-size:0.825rem;padding:0.35rem 0.85rem;">+ Add / Manage drivers</a>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Delivery drivers overview</caption>
    <thead>
      <tr>
        <th>Driver name</th>
        <th>Email</th>
        <th>Contact number</th>
        <th>Active deliveries</th>
        <th>Completed</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($drivers)): ?>
        <tr><td colspan="6" style="text-align:center;color:#56715f;padding:2rem;">No delivery drivers registered yet. <a href="<?= BASE_URL ?>/admin/manage_drivers.php" style="color:var(--tomato);font-weight:700;">Add a driver</a></td></tr>
      <?php else: ?>
        <?php foreach ($drivers as $driver): ?>
          <tr>
            <td style="font-weight:700;">
              <span style="display:inline-flex;align-items:center;gap:0.4rem;">
                🚚 <?= htmlspecialchars($driver['name']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($driver['email']) ?></td>
            <td>
              <?php if (!empty($driver['contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $driver['contact_number'])) ?>" style="font-weight:600;color:var(--leaf);text-decoration:underline;">
                  <?= htmlspecialchars($driver['contact_number']) ?>
                </a>
              <?php else: ?>
                <span style="color:#8ba593;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$driver['active_deliveries'] > 0): ?>
                <span class="status status-processing" style="font-weight:700;"><?= (int)$driver['active_deliveries'] ?> in progress</span>
              <?php else: ?>
                <span class="status status-instock" style="background:#e8ede9;color:#56715f;">Available</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int)$driver['completed_deliveries'] ?></strong> orders</td>
            <td>
              <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.65rem;">Assign orders</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Recent Store Orders Section -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">📦 Recent store orders</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">Latest customer purchases and status</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline" style="font-size:0.825rem;padding:0.35rem 0.85rem;">View all orders</a>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Recent store orders</caption>
    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Driver</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td style="font-weight:700;">#MFF-<?= (int) $order['id'] ?></td>
          <td><?= htmlspecialchars($order['customer_name']) ?></td>
          <td><span class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
          <td>
            <?php if (!empty($order['driver'])): ?>
              <span style="font-weight:600;color:var(--ink);">🚚 <?= htmlspecialchars($order['driver']) ?></span>
            <?php else: ?>
              <span style="color:#c84634;font-size:0.825rem;font-weight:600;">Unassigned</span>
            <?php endif; ?>
          </td>
          <td><a href="<?= BASE_URL ?>/admin/manage_orders.php?id=<?= (int) $order['id'] ?>" class="btn-outline">Manage</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
