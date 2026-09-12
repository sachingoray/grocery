<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

mff_require_role(['customer', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {
    $pdo = mff_db();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if ($pdo !== null) {
        $stmt = $pdo->prepare('UPDATE orders SET status = "cancelled" WHERE id = :id AND user_id = :uid AND status = "pending"');
        $stmt->execute(['id' => $orderId, 'uid' => $_SESSION['user_id'] ?? 0]);
    }
    mff_set_flash('success', 'Order cancelled.');
    header('Location: ' . BASE_URL . '/my_orders.php');
    exit;
}

$pageTitle = 'My Orders';
$activeNav = 'orders';

$pdo = mff_db();
if ($pdo !== null) {
    $userId = $_SESSION['user_id'] ?? null;
    $userName = $_SESSION['user_name'] ?? '';

    if (mff_role() === 'admin') {
        $stmt = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :uid OR customer_name = :cname ORDER BY created_at DESC');
        $stmt->execute(['uid' => $userId, 'cname' => $userName]);
    }
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $itemStmt->execute(['id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    unset($order);
} else {
    $orders = mff_orders_fallback();
}

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];
$statusProgress = ['pending' => 10, 'processing' => 40, 'out_for_delivery' => 75, 'delivered' => 100, 'cancelled' => 0];

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<p class="section-eyebrow">Track your order</p>
<h1 class="section-title">My orders</h1>

<?php if (empty($orders)): ?>
  <div class="empty-state" style="margin-top:1.75rem;">
    <i data-lucide="package" class="icon-md" style="color:#56715f;"></i>
    <h2 class="empty-state__title">No orders yet</h2>
    <p class="empty-state__copy">Once you place an order it'll show up here with live tracking.</p>
  </div>
<?php endif; ?>

<?php foreach ($orders as $order): ?>
  <?php
    $items = $order['items'] ?? [];
    $itemSummary = implode(', ', array_map(fn($i) => (int) $i['quantity'] . ' \u00d7 ' . $i['name'], $items));
    $status = $order['status'];
  ?>
  <article class="card-artisan" style="margin-top:1.5rem;">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;">
      <div>
        <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#56715f;">
          Order #MFF-<?= (int) $order['id'] ?> &middot; <?= htmlspecialchars(date('j M Y', strtotime($order['created_at']))) ?>
        </p>
        <h2 style="margin-top:.5rem;font-size:1.15rem;font-weight:700;"><?= htmlspecialchars($order['customer_name']) ?></h2>
        <p style="margin-top:.25rem;font-size:.875rem;color:#56715f;"><?= htmlspecialchars($itemSummary) ?></p>
      </div>
      <span class="status status-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status) ?></span>
    </div>

    <?php if ($status !== 'cancelled'): ?>
      <div class="timeline-track"><div class="timeline-fill" style="width:<?= (int) $statusProgress[$status] ?>%;"></div></div>
      <div class="timeline-labels"><span>Confirmed</span><span>On the way</span><span>Delivered</span></div>
    <?php endif; ?>

    <?php if ($status === 'pending'): ?>
      <form method="post" action="<?= BASE_URL ?>/my_orders.php" style="margin-top:1.25rem;">
        <input type="hidden" name="action" value="cancel_order">
        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
        <button type="submit" class="btn-outline">Cancel order</button>
      </form>
    <?php endif; ?>
  </article>
<?php endforeach; ?>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
