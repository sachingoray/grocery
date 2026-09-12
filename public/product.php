<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$productId = (int) ($_GET['id'] ?? 0);
$product = mff_get_product($productId);

if ($product === null) {
    mff_set_flash('error', 'That product could not be found.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    cart_add($product['id'], $qty);
    mff_set_flash('success', $product['name'] . ' added to your cart.');
    header('Location: ' . BASE_URL . '/product.php?id=' . $product['id']);
    exit;
}

$pageTitle = $product['name'];
$activeNav = 'shop';
$isLowStock = $product['stock'] <= ($product['low_stock_threshold'] ?? 10);

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<nav aria-label="Breadcrumb" style="font-size:.8rem;color:#56715f;margin-bottom:1.25rem;">
  <a href="<?= BASE_URL ?>/index.php">Shop</a> &rsaquo;
  <a href="<?= BASE_URL ?>/index.php#catalogue"><?= htmlspecialchars($product['category']) ?></a> &rsaquo;
  <span><?= htmlspecialchars($product['name']) ?></span>
</nav>

<div class="form-grid" style="align-items:start;">
  <div class="product-card__media" style="border-radius:1.5rem;overflow:hidden;">
    <img src="<?= htmlspecialchars($product['image_url']) ?>" data-fallback="<?= BASE_URL ?>/assets/placeholder.svg" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
  </div>

  <div class="card-artisan">
    <p class="product-card__category"><?= htmlspecialchars($product['category']) ?></p>
    <h1 class="section-title" style="margin-top:.4rem;"><?= htmlspecialchars($product['name']) ?></h1>
    
    <?php 
      $hasSale = !empty($product['original_price']) && (float)$product['original_price'] > (float)$product['price'];
      $savings = $hasSale ? ((float)$product['original_price'] - (float)$product['price']) : 0;
      $pct = $hasSale ? round(($savings / (float)$product['original_price']) * 100) : 0;
    ?>
    <div style="display:flex;align-items:baseline;gap:.85rem;margin-top:.75rem;flex-wrap:wrap;">
      <p style="font-size:2rem;font-weight:800;color:<?= $hasSale ? 'var(--tomato)' : 'inherit' ?>;margin:0;letter-spacing:-.02em;">
        <?= mff_money($product['price']) ?>
      </p>
      <?php if ($hasSale): ?>
        <span style="font-size:1.1rem;color:#56715f;font-weight:600;">
          Was <del style="text-decoration:line-through;text-decoration-color:#d94f26;text-decoration-thickness:2px;color:#8ba593;"><?= mff_money($product['original_price']) ?></del>
        </span>
        <span class="product-card__badge product-card__badge--sale" style="position:static;display:inline-block;font-size:.75rem;padding:.3rem .75rem;">
          Save <?= mff_money($savings) ?> (<?= $pct ?>% OFF)
        </span>
      <?php endif; ?>
    </div>

    <p style="margin-top:.5rem;">
      <?php if ($isLowStock): ?>
        <span class="status status-low">Low stock &mdash; <?= (int) $product['stock'] ?> left</span>
      <?php else: ?>
        <span class="status status-instock">In stock</span>
      <?php endif; ?>
    </p>

    <p style="margin-top:1rem;line-height:1.6;color:#3f5749;"><?= htmlspecialchars($product['description'] ?? '') ?></p>

    <form method="post" action="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>" style="margin-top:1.5rem;display:flex;align-items:center;gap:1rem;">
      <input type="hidden" name="action" value="add">
      <div class="qty-stepper">
        <button type="button" class="btn-step-minus" aria-label="Decrease quantity">&minus;</button>
        <input type="number" name="quantity" value="1" min="1" style="width:2rem;text-align:center;border:none;" aria-label="Quantity">
        <button type="button" class="btn-step-plus" aria-label="Increase quantity">+</button>
      </div>
      <button type="submit" class="btn-tomato">Add to cart</button>
    </form>
  </div>
</div>

<script>
  // Local stepper for the quantity <input> (this one submits with the form, unlike cart.php's live stepper).
  document.querySelector('.btn-step-minus')?.addEventListener('click', () => {
    const input = document.querySelector('input[name="quantity"]');
    input.value = Math.max(1, parseInt(input.value || '1', 10) - 1);
  });
  document.querySelector('.btn-step-plus')?.addEventListener('click', () => {
    const input = document.querySelector('input[name="quantity"]');
    input.value = parseInt(input.value || '1', 10) + 1;
  });
</script>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
