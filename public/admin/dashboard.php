<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin', 'logistics_manager', 'inventory_manager', 'support_staff']);

$userRole = mff_role();

$pdo = mff_db();
if ($pdo !== null) {
    $orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 10')->fetchAll();
    $totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $totalRevenue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
    $pendingOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn();
    $processingOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "processing"')->fetchColumn();
    $outForDeliveryOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "out_for_delivery"')->fetchColumn();
    $deliveredOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "delivered"')->fetchColumn();
    $lowStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= low_stock_threshold')->fetchColumn();
    $outOfStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 0')->fetchColumn();
    $totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $totalSpecials = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_special = 1')->fetchColumn();
    $lowStockProducts = $pdo->query('SELECT * FROM products WHERE stock <= low_stock_threshold ORDER BY stock ASC LIMIT 8')->fetchAll();

    $drivers = $pdo->query("
        SELECT u.id, u.name, u.email, u.contact_number, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status IN ('processing', 'out_for_delivery')) AS active_deliveries,
               (SELECT COUNT(*) FROM orders o WHERE o.driver = u.name AND o.status = 'delivered') AS completed_deliveries
        FROM users u
        WHERE u.role = 'delivery'
        ORDER BY u.name ASC
    ")->fetchAll();
    $totalDrivers = count($drivers);
    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $recentUsers = $pdo->query('
        SELECT u.id, u.name, u.email, u.role, u.contact_number, u.created_at,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id OR o.customer_name = u.name) AS order_count
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT 6
    ')->fetchAll();
} else {
    $orders = mff_orders_fallback();
    $totalOrders = 128;
    $totalRevenue = 8742.00;
    $pendingOrders = 12;
    $processingOrders = 8;
    $outForDeliveryOrders = 5;
    $deliveredOrders = 103;
    $products = mff_products_fallback();
    $totalProducts = count($products);
    $totalSpecials = count(array_filter($products, fn($p) => $p['is_special'] == 1));
    $outOfStock = count(array_filter($products, fn($p) => $p['stock'] <= 0));
    $lowStock = count(array_filter($products, fn($p) => $p['stock'] <= $p['low_stock_threshold']));
    $lowStockProducts = array_slice(array_filter($products, fn($p) => $p['stock'] <= $p['low_stock_threshold']), 0, 8);
    $totalDrivers = 2;
    $totalUsers = 8;
    $drivers = [
        ['id' => 2, 'name' => 'Chris Allen', 'email' => 'driver@maxifinefoods.com.au', 'contact_number' => '0400 000 002', 'active_deliveries' => 1, 'completed_deliveries' => 12],
        ['id' => 3, 'name' => 'Jordan Lee', 'email' => 'jordan@maxifinefoods.com.au', 'contact_number' => '0400 000 003', 'active_deliveries' => 1, 'completed_deliveries' => 8],
    ];
    $recentUsers = [
        ['id' => 4, 'name' => 'Sarah Jenkins', 'email' => 'customer@maxifinefoods.com.au', 'role' => 'customer', 'contact_number' => '0412 345 678', 'created_at' => date('Y-m-d H:i:s'), 'order_count' => 3],
        ['id' => 3, 'name' => 'Jordan Lee', 'email' => 'jordan@maxifinefoods.com.au', 'role' => 'delivery', 'contact_number' => '0400 000 003', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'order_count' => 8],
        ['id' => 2, 'name' => 'Chris Allen', 'email' => 'driver@maxifinefoods.com.au', 'role' => 'delivery', 'contact_number' => '0400 000 002', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')), 'order_count' => 12],
        ['id' => 1, 'name' => 'System Administrator', 'email' => 'admin@maxifinefoods.com.au', 'role' => 'admin', 'contact_number' => '0400 000 001', 'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')), 'order_count' => 0],
    ];
}

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];

$pageTitle = mff_role_label($userRole) . ' Dashboard';
$activeNav = 'admin';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.25rem;">
  <div>
    <p class="section-eyebrow"><?= htmlspecialchars(mff_role_label($userRole)) ?> Workspace</p>
    <h1 class="section-title">Operations Dashboard</h1>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
    <?php if (in_array($userRole, ['admin', 'logistics_manager', 'support_staff'], true)): ?>
      <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline">Manage orders</a>
    <?php endif; ?>
    <?php if (in_array($userRole, ['admin', 'logistics_manager'], true)): ?>
      <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="btn-outline">🚚 Manage drivers</a>
    <?php endif; ?>
    <?php if (in_array($userRole, ['admin', 'inventory_manager'], true)): ?>
      <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-tomato">📦 Manage products</a>
    <?php endif; ?>
    <?php if ($userRole === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/manage_users.php" class="btn-outline">👥 Manage users</a>
    <?php endif; ?>
  </div>
</div>

<!-- Tailored Stats Grid by Role -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit, minmax(170px, 1fr));margin-top:1.5rem;">
  <?php if ($userRole === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Total orders ↗</p>
      <p class="stat-card__value"><?= number_format($totalOrders) ?></p>
    </a>
    <div class="stat-card">
      <p class="stat-card__label">Store Revenue</p>
      <p class="stat-card__value"><?= mff_money($totalRevenue) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Pending orders ↗</p>
      <p class="stat-card__value"><?= number_format($pendingOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_users.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;border:1.5px solid var(--leaf-tint, #cfe1d5);">
      <p class="stat-card__label" style="color:var(--leaf);font-weight:700;">👥 Registered Users ↗</p>
      <p class="stat-card__value"><?= number_format($totalUsers) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Delivery drivers ↗</p>
      <p class="stat-card__value"><?= number_format($totalDrivers) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Low stock items ↗</p>
      <p class="stat-card__value"><?= number_format($lowStock) ?></p>
    </a>

  <?php elseif ($userRole === 'logistics_manager'): ?>
    <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Active Fleet Drivers ↗</p>
      <p class="stat-card__value"><?= number_format($totalDrivers) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Out For Delivery ↗</p>
      <p class="stat-card__value"><?= number_format($outForDeliveryOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">In Processing / Dispatch ↗</p>
      <p class="stat-card__value"><?= number_format($processingOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Total Orders Handled ↗</p>
      <p class="stat-card__value"><?= number_format($totalOrders) ?></p>
    </a>

  <?php elseif ($userRole === 'inventory_manager'): ?>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Total Products ↗</p>
      <p class="stat-card__value"><?= number_format($totalProducts) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;border:1.5px solid #fecdd3;">
      <p class="stat-card__label" style="color:var(--tomato);font-weight:700;">⚠️ Low Stock Items ↗</p>
      <p class="stat-card__value"><?= number_format($lowStock) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Out of Stock ↗</p>
      <p class="stat-card__value"><?= number_format($outOfStock) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_products.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Promotions &amp; Specials ↗</p>
      <p class="stat-card__value"><?= number_format($totalSpecials) ?></p>
    </a>

  <?php elseif ($userRole === 'support_staff'): ?>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Total Customer Orders ↗</p>
      <p class="stat-card__value"><?= number_format($totalOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;border:1.5px solid #fef08a;">
      <p class="stat-card__label" style="color:#854d0e;font-weight:700;">Pending Confirmation ↗</p>
      <p class="stat-card__value"><?= number_format($pendingOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">In Processing ↗</p>
      <p class="stat-card__value"><?= number_format($processingOrders) ?></p>
    </a>
    <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="stat-card" style="text-decoration:none;display:block;cursor:pointer;">
      <p class="stat-card__label">Delivered Orders ↗</p>
      <p class="stat-card__value"><?= number_format($deliveredOrders) ?></p>
    </a>
  <?php endif; ?>
</div>

<?php if (in_array($userRole, ['admin', 'inventory_manager'], true)): ?>
<!-- Inventory & Low Stock Overview Section (Product Manager & Admin) -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">📦 Inventory &amp; Stock Alerts</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">Products requiring replenishment and stock monitoring</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-tomato" style="font-size:0.825rem;padding:0.35rem 0.85rem;">+ Add New Product</a>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Low stock products overview</caption>
    <thead>
      <tr>
        <th>Product Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Current Stock</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($lowStockProducts)): ?>
        <tr><td colspan="6" style="text-align:center;color:#56715f;padding:2rem;">All products have healthy stock levels. <a href="<?= BASE_URL ?>/admin/manage_products.php" style="color:var(--leaf);font-weight:700;">View catalog</a></td></tr>
      <?php else: ?>
        <?php foreach ($lowStockProducts as $p): ?>
          <tr>
            <td style="font-weight:700;">
              <div style="display:flex;align-items:center;gap:0.5rem;">
                <?php if (!empty($p['image_url'])): ?>
                  <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;">
                <?php endif; ?>
                <span><?= htmlspecialchars($p['name']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars(ucfirst($p['category'] ?? 'General')) ?></td>
            <td style="font-weight:600;"><?= mff_money($p['price']) ?></td>
            <td>
              <strong style="color:<?= (int)$p['stock'] <= 0 ? 'var(--tomato)' : '#d97706' ?>;">
                <?= (int)$p['stock'] ?> units
              </strong>
            </td>
            <td>
              <?php if ((int)$p['stock'] <= 0): ?>
                <span class="status" style="background:#fee2e2;color:#991b1b;font-weight:700;">Out of Stock</span>
              <?php else: ?>
                <span class="status" style="background:#fef3c7;color:#92400e;font-weight:700;">Low Stock</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/admin/manage_products.php?edit=<?= (int)$p['id'] ?>" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.65rem;">Restock / Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (in_array($userRole, ['admin', 'logistics_manager'], true)): ?>
<!-- Delivery Fleet Overview Section (Logistics Manager & Admin) -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">🚚 Delivery Drivers Fleet</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">Active delivery driver team, phone contacts, and workloads</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_drivers.php" class="btn-outline" style="font-size:0.825rem;padding:0.35rem 0.85rem;">+ Add / Manage Drivers</a>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Delivery drivers overview</caption>
    <thead>
      <tr>
        <th>Driver Name</th>
        <th>Email</th>
        <th>Contact Number</th>
        <th>Active Deliveries</th>
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
<?php endif; ?>

<?php if (in_array($userRole, ['admin', 'logistics_manager', 'support_staff'], true)): ?>
<!-- Recent Store Orders Section (Admin, Logistics, Support) -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">📦 Recent Customer Orders</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">Latest customer purchases, fulfillment status, and driver dispatch</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/manage_orders.php" class="btn-outline" style="font-size:0.825rem;padding:0.35rem 0.85rem;">View all orders</a>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Recent store orders</caption>
    <thead>
      <tr>
        <th>Order</th>
        <th>Customer</th>
        <th>Status</th>
        <th>Assigned Driver</th>
        <th>Total</th>
        <th>Action</th>
      </tr>
    </thead>
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
          <td style="font-weight:600;"><?= isset($order['total']) ? mff_money($order['total']) : '—' ?></td>
          <td><a href="<?= BASE_URL ?>/admin/manage_orders.php?id=<?= (int) $order['id'] ?>" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.65rem;">Manage</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if ($userRole === 'admin'): ?>
<!-- Recently Registered Users Section (Strictly Root Admin Exclusive) -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:2.5rem;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.75rem;">
  <div>
    <h2 style="font-size:1.4rem;font-weight:700;margin:0;color:var(--ink);">👥 Recently Registered Users</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.25rem 0 0;">New customer and staff registrations in real time (Admin exclusive)</p>
  </div>
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/manage_users.php?action=create" class="btn-tomato" style="font-size:0.825rem;padding:0.35rem 0.85rem;">+ Add New User</a>
    <a href="<?= BASE_URL ?>/admin/manage_users.php" class="btn-outline" style="font-size:0.825rem;padding:0.35rem 0.85rem;">View All Users (<?= number_format($totalUsers) ?>)</a>
  </div>
</div>

<div class="data-table-wrap" style="margin-top:0.5rem;">
  <table class="data-table">
    <caption class="sr-only">Recently registered users</caption>
    <thead>
      <tr>
        <th>User Details</th>
        <th>Account Role</th>
        <th>Contact Number</th>
        <th>Orders Placed</th>
        <th>Registration Date &amp; Time</th>
        <th>Admin Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($recentUsers)): ?>
        <tr><td colspan="6" style="text-align:center;color:#56715f;padding:2rem;">No registered users yet. <a href="<?= BASE_URL ?>/admin/manage_users.php?action=create" style="color:var(--tomato);font-weight:700;">Add a user</a></td></tr>
      <?php else: ?>
        <?php foreach ($recentUsers as $u): 
          $regTime = strtotime($u['created_at'] ?? 'now');
          $isRecent = (time() - $regTime) < (86400 * 3); // Within 3 days
        ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:38px;height:38px;border-radius:50%;background:#eef4f0;color:var(--leaf);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;border:1px solid var(--line);flex-shrink:0;">
                  <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--ink);display:flex;align-items:center;gap:0.4rem;font-size:0.95rem;">
                    <?= htmlspecialchars($u['name']) ?>
                    <?php if ($isRecent): ?>
                      <span style="background:var(--gold);color:#4a3200;font-size:0.65rem;font-weight:800;padding:0.15rem 0.5rem;border-radius:999px;text-transform:uppercase;letter-spacing:0.04em;">✨ New</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:0.8rem;color:#56715f;"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span style="background:#d1fae5;color:#065f46;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">🛡️ Admin</span>
              <?php elseif ($u['role'] === 'logistics_manager'): ?>
                <span style="background:#fef3c7;color:#92400e;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">🚚 Fleet Mgr</span>
              <?php elseif ($u['role'] === 'inventory_manager'): ?>
                <span style="background:#dbeafe;color:#1e3a8a;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">📦 Product Mgr</span>
              <?php elseif ($u['role'] === 'support_staff'): ?>
                <span style="background:#f3e8ff;color:#581c87;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">🎧 Support Staff</span>
              <?php elseif ($u['role'] === 'delivery'): ?>
                <span style="background:#fef9c3;color:#854d0e;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">🚚 Driver</span>
              <?php else: ?>
                <span style="background:#e0f2fe;color:#0369a1;padding:.25rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700;">🛍️ Customer</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($u['contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $u['contact_number'])) ?>" style="font-weight:600;color:var(--leaf);text-decoration:underline;">
                  <?= htmlspecialchars($u['contact_number']) ?>
                </a>
              <?php else: ?>
                <span style="color:#8ba593;">—</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int) ($u['order_count'] ?? 0) ?></strong> orders</td>
            <td style="font-size:0.825rem;color:#56715f;">
              <div style="font-weight:600;color:var(--ink);"><?= date('d M Y, h:ia', $regTime) ?></div>
              <div style="font-size:0.75rem;color:#8ba593;">
                <?php
                  $diff = time() - $regTime;
                  if ($diff < 60) echo 'Just now';
                  elseif ($diff < 3600) echo floor($diff / 60) . ' mins ago';
                  elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                  else echo floor($diff / 86400) . ' days ago';
                ?>
              </div>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/admin/manage_users.php?edit=<?= (int)$u['id'] ?>" class="btn-outline" style="font-size:0.775rem;padding:0.3rem 0.65rem;display:inline-flex;align-items:center;gap:0.3rem;">
                <i data-lucide="shield" class="icon-xs"></i> Manage User
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
