<?php
/**
 * cart.php — Shopping cart view and mutation endpoint.
 * Author: Sachin Shrestha
 * Date: 27/09/2026
 * Purpose: Displays current cart contents and handles add/update/remove
 * actions posted from index.php, product.php, and this page's own forms.
 */

require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
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
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            // Read back through cart_qty() so the value reported to the browser
            // comes from the database, scoped to the current authenticated user.
            'quantity' => cart_qty($productId),
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
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div>
  <p class="section-eyebrow">Your basket</p>
  <h1 class="section-title">Shopping cart</h1>
</div>
<div class="form-grid u-004">
  <div class="card-artisan u-205">
    <?php if (empty($cart['items'])): ?>
      <div class="u-209">Your cart is ready for market finds.</div>
    <?php endif; ?>
    <?php foreach ($cart['items'] as $item): ?>
      <div class="qty-stepper-row u-061" data-cart-row>
        <div>
          <p class="u-144"><?= htmlspecialchars($item['name']) ?></p>
          <p class="u-165"><?= mff_money($item['price']) ?> each</p>
        </div>
        <div class="u-057">
          <div class="qty-stepper" data-product-id="<?= (int) $item['product_id'] ?>">
            <button type="button" class="btn-step-minus" aria-label="Decrease <?= htmlspecialchars($item['name']) ?>">&minus;</button>
            <span data-qty-display><?= (int) $item['quantity'] ?></span>
            <button type="button" class="btn-step-plus" aria-label="Increase <?= htmlspecialchars($item['name']) ?>">+</button>
          </div>
          <strong><?= mff_money($item['line_total']) ?></strong>
<form method="post" action="<?= BASE_URL ?>/cart.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
            <button type="submit" class="u-097">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <aside class="oatmeal-panel u-154">
    <h2 class="section-title u-138">Order summary</h2>
    <div class="u-180">
      <div class="u-070"><span>Subtotal</span><strong data-cart-subtotal><?= mff_money($cart['subtotal']) ?></strong></div>
      <div class="u-078"><span>GST (10%)</span><strong data-cart-tax><?= mff_money($cart['tax']) ?></strong></div>
    </div>
    <div class="u-080">
      <span>Total</span><span data-cart-total><?= mff_money($cart['total']) ?></span>
    </div>
    <a href="<?= BASE_URL ?>/checkout.php" class="btn-tomato u-226">Proceed to checkout</a>
  </aside>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
