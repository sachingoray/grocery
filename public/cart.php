<?php
require_once __DIR__ . '/../includes/session.php';

/* ------------------------------------------------------ handle mutations */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    switch ($action) {
        case 'add':
            cart_add($productId, max(1, (int) ($_POST['quantity'] ?? 1)));
            break;
        case 'update_qty':
            cart_set_qty($productId, (int) ($_POST['quantity'] ?? 1));
            break;
        case 'remove':
            cart_remove($productId);
            break;
        case 'clear':
            cart_clear();
            break;
    }

    if (mff_wants_json()) {
        $contents = cart_contents();
        $product = mff_get_product($productId);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'quantity' => $_SESSION['cart'][$productId] ?? 0,
            'cart_count' => cart_count(),
            'subtotal_formatted' => mff_money($contents['subtotal']),
            'tax_formatted' => mff_money($contents['tax']),
            'total_formatted' => mff_money($contents['total']),
        ]);
        exit;
    }

    // Regular form post (e.g. "Add to cart" button on the catalog) — redirect back.
    mff_set_flash('success', 'Cart updated.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/cart.php'));
    exit;
}

/* ----------------------------------------------------------------- view */

$pageTitle = 'Your Cart';
$activeNav = 'shop';
$cart = cart_contents();

require __DIR__ . '/../includes/header.php';
?>

<div>
  <p class="section-eyebrow">Your basket</p>
  <h1 class="section-title">Shopping cart</h1>
</div>

<div class="form-grid" style="align-items:start;margin-top:1.5rem;grid-template-columns:1.35fr .65fr;">
  <div class="card-artisan" style="padding:0;">
    <?php if (empty($cart['items'])): ?>
      <div style="padding:3rem;text-align:center;color:#56715f;font-weight:600;">Your cart is ready for market finds.</div>
    <?php endif; ?>

    <?php foreach ($cart['items'] as $item): ?>
      <div class="qty-stepper-row" data-cart-row style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--oat);padding:1.25rem;">
        <div>
          <p style="font-weight:700;"><?= htmlspecialchars($item['name']) ?></p>
          <p style="margin-top:.25rem;font-size:.875rem;color:#56715f;"><?= mff_money($item['price']) ?> each</p>
        </div>
        <div style="display:flex;align-items:center;gap:1.25rem;">
          <div class="qty-stepper" data-product-id="<?= (int) $item['product_id'] ?>">
            <button type="button" class="btn-step-minus" aria-label="Decrease <?= htmlspecialchars($item['name']) ?>">&minus;</button>
            <span data-qty-display><?= (int) $item['quantity'] ?></span>
            <button type="button" class="btn-step-plus" aria-label="Increase <?= htmlspecialchars($item['name']) ?>">+</button>
          </div>
          <strong><?= mff_money($item['line_total']) ?></strong>
          <form method="post" action="<?= BASE_URL ?>/cart.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
            <button type="submit" style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--tomato);">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <aside class="oatmeal-panel" style="height:fit-content;">
    <h2 class="section-title" style="font-size:1.75rem;">Order summary</h2>
    <div style="margin-top:1.25rem;border-top:1px solid #cfc4ac;border-bottom:1px solid #cfc4ac;padding:1.25rem 0;font-size:.875rem;">
      <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong data-cart-subtotal><?= mff_money($cart['subtotal']) ?></strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:.75rem;"><span>GST (10%)</span><strong data-cart-tax><?= mff_money($cart['tax']) ?></strong></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:1rem;font-size:1.15rem;font-weight:700;">
      <span>Total</span><span data-cart-total><?= mff_money($cart['total']) ?></span>
    </div>
    <a href="<?= BASE_URL ?>/checkout.php" class="btn-tomato" style="width:100%;justify-content:center;margin-top:1.5rem;">Proceed to checkout</a>
  </aside>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
