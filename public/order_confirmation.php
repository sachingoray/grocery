<?php
require_once __DIR__ . '/../includes/session.php';

$order = $_SESSION['last_order'] ?? null;
if ($order === null) {
    mff_set_flash('error', 'No recent order to show.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Order Confirmed';
$activeNav = 'orders';

require __DIR__ . '/../includes/header.php';
?>

<div class="hero-card" style="background:var(--leaf);min-height:180px;">
  <div class="hero-card__copy" style="padding:2.5rem;">
    <div style="display:flex;align-items:center;gap:.75rem;">
      <i data-lucide="check-circle-2" class="icon-md" style="color:var(--gold);"></i>
      <h1 style="font-family:'Playfair Display',serif;font-size:2rem;margin:0;">Order confirmed</h1>
    </div>
    <p class="hero-card__desc" style="margin-top:.75rem;">Thanks, <?= htmlspecialchars($order['customer_name']) ?> &mdash; your delivery estimate is 45&ndash;60 minutes.</p>
  </div>
</div>

<div class="form-grid" style="margin-top:1.75rem;grid-template-columns:1.2fr .8fr;align-items:start;">
  <div class="card-artisan">
    <p class="oatmeal-panel" style="display:inline-block;padding:.75rem 1.25rem;font-weight:700;">Order reference #MFF-<?= (int) $order['id'] ?></p>
    <h2 class="section-title" style="font-size:1.5rem;margin-top:1.25rem;">Delivering to</h2>
    <p style="margin-top:.5rem;line-height:1.6;"><?= htmlspecialchars($order['delivery_address']) ?></p>
    <?php if (!empty($order['delivery_instructions'])): ?>
      <p style="margin-top:.5rem;color:#56715f;font-size:.875rem;">Note: <?= htmlspecialchars($order['delivery_instructions']) ?></p>
    <?php endif; ?>
    <p style="margin-top:.75rem;font-weight:700;">Contact: <?= htmlspecialchars($order['contact_number']) ?></p>
  </div>

  <aside class="oatmeal-panel">
    <h2 class="section-title" style="font-size:1.5rem;">Itemised receipt</h2>
    <div style="margin-top:1.25rem;">
      <?php foreach ($order['items'] as $item): ?>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;margin-top:.6rem;">
          <span><?= (int) $item['quantity'] ?> &times; <?= htmlspecialchars($item['name']) ?></span>
          <strong><?= mff_money($item['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:1.25rem;border-top:1px solid #cfc4ac;padding-top:1rem;font-size:.875rem;">
      <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong><?= mff_money($order['subtotal']) ?></strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:.5rem;"><span>GST</span><strong><?= mff_money($order['tax']) ?></strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:.75rem;font-size:1.05rem;font-weight:700;"><span>Total</span><span><?= mff_money($order['total']) ?></span></div>
    </div>
  </aside>
</div>

<div style="margin-top:1.75rem;display:flex;gap:.75rem;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/my_orders.php" class="btn-tomato">Track this order</a>
  <a href="<?= BASE_URL ?>/index.php" class="btn-ink">Continue shopping</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
