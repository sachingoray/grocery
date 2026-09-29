<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$order = $_SESSION['last_order'] ?? null;
if ($order === null) {
    mff_set_flash('error', 'No recent order to show.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$pageTitle = 'Order Confirmed';
$activeNav = 'orders';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="hero-card u-027">
  <div class="hero-card__copy u-207">
    <div class="u-052">
      <i data-lucide="check-circle-2" class="icon-md u-036"></i>
      <h1 class="u-094">Order confirmed</h1>
    </div>
    <p class="hero-card__desc u-173">Thanks, <?= htmlspecialchars($order['customer_name']) ?> &mdash; your delivery estimate is 45&ndash;60 minutes.</p>
  </div>
</div>
<div class="form-grid u-191">
  <div class="card-artisan">
    <p class="oatmeal-panel u-088">Order reference #MFF-<?= (int) $order['id'] ?></p>
    <h2 class="section-title u-137">Delivering to</h2>
    <p class="u-172"><?= htmlspecialchars($order['delivery_address']) ?></p>
    <?php if (!empty($order['delivery_instructions'])): ?>
      <p class="u-169">Note: <?= htmlspecialchars($order['delivery_instructions']) ?></p>
    <?php endif; ?>
    <p class="u-175">Contact: <?= htmlspecialchars($order['contact_number']) ?></p>
  </div>
  <aside class="oatmeal-panel">
    <h2 class="section-title u-136">Itemised receipt</h2>
    <div class="u-178">
      <?php foreach ($order['items'] as $item): ?>
        <div class="u-075">
          <span><?= (int) $item['quantity'] ?> &times; <?= htmlspecialchars($item['name']) ?></span>
          <strong><?= mff_money($item['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="u-181">
      <div class="u-070"><span>Subtotal</span><strong><?= mff_money($order['subtotal']) ?></strong></div>
      <div class="u-077"><span>GST</span><strong><?= mff_money($order['tax']) ?></strong></div>
      <div class="u-079"><span>Total</span><span><?= mff_money($order['total']) ?></span></div>
    </div>
  </aside>
</div>
<div class="u-190">
  <?php if (($order['payment_method'] ?? '') === 'credit_card' && !empty($order['stripe_session_id'])): ?>
    <a href="<?= BASE_URL ?>/download_receipt.php?id=<?= (int) $order['id'] ?>" class="btn-outline">Download receipt</a>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/my_orders.php" class="btn-tomato">Track this order</a>
  <a href="<?= BASE_URL ?>/index.php" class="btn-ink">Continue shopping</a>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
