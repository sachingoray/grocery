<?php
require_once __DIR__ . '/../../includes/session.php';
mff_require_role(['admin']);

$pdo = mff_db();
if ($pdo !== null) {
    $orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 10')->fetchAll();
    $totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $totalRevenue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
    $pendingOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn();
    $lowStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= low_stock_threshold')->fetchColumn();
} else {
    $orders = mff_orders_fallback();
    $totalOrders = 128;
    $totalRevenue = 8742.00;
    $pendingOrders = 12;
    $products = mff_products_fallback();
    $lowStock = count(array_filter($products, fn($p) => $p['stock'] <= $p['low_stock_threshold']));
}

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin';
require __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.25rem;">
  <div>
    <p class="section-eyebrow">Store management</p>
    <h1 class="section-title">Admin dashboard</h1>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-tomato">Add new product</a>
</div>

<div class="stat-grid">
  <div class="stat-card"><p class="stat-card__label">Total orders</p><p class="stat-card__value"><?= number_format($totalOrders) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Revenue</p><p class="stat-card__value"><?= mff_money($totalRevenue) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Pending orders</p><p class="stat-card__value"><?= number_format($pendingOrders) ?></p></div>
  <div class="stat-card"><p class="stat-card__label">Low stock items</p><p class="stat-card__value"><?= number_format($lowStock) ?></p></div>
</div>

<div class="data-table-wrap">
  <table class="data-table">
    <caption class="sr-only">Recent store orders</caption>
    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Driver</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td style="font-weight:700;">#MFF-<?= (int) $order['id'] ?></td>
          <td><?= htmlspecialchars($order['customer_name']) ?></td>
          <td><span class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
          <td><?= htmlspecialchars($order['driver'] ?? 'Unassigned') ?></td>
          <td><a href="<?= BASE_URL ?>/admin/manage_orders.php?id=<?= (int) $order['id'] ?>" class="btn-outline">Manage</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
