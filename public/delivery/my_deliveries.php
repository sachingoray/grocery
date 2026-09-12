<?php
require_once __DIR__ . '/../../includes/session.php';
mff_require_role(['delivery', 'admin']);

$currentDriver = $_SESSION['user_name'] ?? 'Chris Allen';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $pdo = mff_db();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'processing';

    if ($pdo !== null && in_array($status, ['processing', 'out_for_delivery', 'delivered'], true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, driver = COALESCE(driver, :driver) WHERE id = :id');
        $stmt->execute(['status' => $status, 'driver' => $currentDriver, 'id' => $orderId]);
        mff_set_flash('success', 'Delivery marked ' . str_replace('_', ' ', $status) . '.');
    } else {
        mff_set_flash('info', 'No database connected — status not persisted (demo mode).');
    }
    header('Location: ' . BASE_URL . '/delivery/my_deliveries.php');
    exit;
}

$pdo = mff_db();
if ($pdo !== null) {
    if (mff_role() === 'admin') {
        $orders = $pdo->query("SELECT * FROM orders WHERE status IN ('processing','out_for_delivery') ORDER BY created_at ASC")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE (driver = :driver OR driver IS NULL) AND status IN ('processing','out_for_delivery') ORDER BY created_at ASC");
        $stmt->execute(['driver' => $currentDriver]);
        $orders = $stmt->fetchAll();
    }
} else {
    $orders = array_filter(mff_orders_fallback(), fn($o) => in_array($o['status'], ['processing', 'out_for_delivery'], true));
}

$statusLabels = ['processing' => 'Processing', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];

$pageTitle = 'My Deliveries';
$activeNav = 'delivery';
require __DIR__ . '/../../includes/header.php';
?>

<p class="section-eyebrow">Driver console</p>
<h1 class="section-title">Delivery queue</h1>

<div class="product-grid" style="grid-template-columns:1fr;max-width:40rem;">
  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <i data-lucide="package-check" class="icon-md" style="color:#56715f;"></i>
      <h2 class="empty-state__title">No more deliveries</h2>
      <p class="empty-state__copy">You're all caught up. New assigned orders will appear here.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($orders as $order): ?>
    <article class="card-artisan">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
        <div>
          <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#56715f;">#MFF-<?= (int) $order['id'] ?></p>
          <h2 style="margin-top:.25rem;font-size:1.15rem;font-weight:700;"><?= htmlspecialchars($order['customer_name']) ?></h2>
        </div>
        <span class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></span>
      </div>

      <p style="margin-top:1.25rem;border-left:2px solid var(--gold);padding-left:.75rem;font-size:.875rem;line-height:1.6;">
        <?= htmlspecialchars($order['delivery_address']) ?>
        <?php if (!empty($order['delivery_instructions'])): ?>
          <br><span style="color:#56715f;">Note: <?= htmlspecialchars($order['delivery_instructions']) ?></span>
        <?php endif; ?>
      </p>
      <p style="margin-top:.75rem;font-size:.875rem;font-weight:700;">
        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $order['contact_number'])) ?>">Call <?= htmlspecialchars($order['contact_number']) ?></a>
      </p>

      <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.5rem;">
        <?php foreach (['processing' => 'Processing', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'] as $statusKey => $label): ?>
          <form method="post" action="<?= BASE_URL ?>/delivery/my_deliveries.php">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <input type="hidden" name="status" value="<?= $statusKey ?>">
            <button type="submit" class="<?= $order['status'] === $statusKey ? 'btn-tomato' : 'btn-outline' ?>"><?= $label ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
