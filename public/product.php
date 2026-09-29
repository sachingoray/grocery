<?php
/**
 * product.php — Single product detail page.
 * Date: 27/09/2026
 * Purpose: Shows full details for one product (by ?id=) and provides an
 * Add to Cart form with a quantity selector.
 */

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
$nutrition = mff_get_nutrition((int) $product['id']);
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<nav aria-label="Breadcrumb" class="u-115">
  <a href="<?= BASE_URL ?>/index.php">Shop</a> &rsaquo;
  <a href="<?= BASE_URL ?>/index.php#catalogue"><?= htmlspecialchars($product['category']) ?></a> &rsaquo;
  <span><?= htmlspecialchars($product['name']) ?></span>
</nav>
<div class="form-grid u-003">
  <div class="product-card__media u-028">
    <img src="<?= htmlspecialchars($product['image_url']) ?>" data-fallback="<?= BASE_URL ?>/assets/placeholder.svg" alt="<?= htmlspecialchars($product['name']) ?>" class="u-224">
  </div>
  <div class="card-artisan">
    <p class="product-card__category"><?= htmlspecialchars($product['category']) ?></p>
    <h1 class="section-title u-167"><?= htmlspecialchars($product['name']) ?></h1>
    <?php 
      $hasSale = !empty($product['original_price']) && (float)$product['original_price'] > (float)$product['price'];
      $savings = $hasSale ? ((float)$product['original_price'] - (float)$product['price']) : 0;
      $pct = $hasSale ? round(($savings / (float)$product['original_price']) * 100) : 0;
    ?>
    <div class="u-049">
      <p class="product-price<?= $hasSale ? ' product-price--sale' : '' ?>">
        <?= mff_money($product['price']) ?>
      </p>
      <?php if ($hasSale): ?>
        <span class="u-130">
          Was <del class="u-217"><?= mff_money($product['original_price']) ?></del>
        </span>
        <span class="product-card__badge product-card__badge--sale u-211">
          Save <?= mff_money($savings) ?> (<?= $pct ?>% OFF)
        </span>
      <?php endif; ?>
    </div>
    <p class="u-168">
      <?php if ($isLowStock): ?>
        <span class="status status-low">Low stock &mdash; <?= (int) $product['stock'] ?> left</span>
      <?php else: ?>
        <span class="status status-instock">In stock</span>
      <?php endif; ?>
    </p>
    <p class="u-195"><?= htmlspecialchars($product['description'] ?? '') ?></p>
<form method="post" action="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>" style="margin-top:1.5rem;display:flex;align-items:center;gap:1rem;">
      <input type="hidden" name="action" value="add">
      <div class="qty-stepper">
        <button type="button" class="btn-step-minus" aria-label="Decrease quantity">&minus;</button>
        <input type="number" name="quantity" value="1" min="1" class="u-228" aria-label="Quantity">
        <button type="button" class="btn-step-plus" aria-label="Increase quantity">+</button>
      </div>
      <button type="submit" class="btn-tomato">Add to cart</button>
    </form>
  </div>
</div>
<?php if ($nutrition !== null): ?>
  <section class="nutrition" aria-labelledby="nutrition-heading">
    <h2 class="section-title nutrition__heading" id="nutrition-heading">Nutrition &amp; product details</h2>
    <p class="nutrition__serving">Average values <strong><?= htmlspecialchars((string) $nutrition['serving']) ?></strong></p>
    <div class="nutrition__grid">
      <div class="nutrition__table-wrap">
        <table class="nutrition__table">
          <caption class="sr-only">Nutrition information</caption>
          <thead>
            <tr>
              <th scope="col">Nutrient</th>
              <th scope="col">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Grocery-style numbers: no trailing ".00" on whole values.
            $fmt = static function ($v): string {
                return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            };
            $nutritionRows = [
                ['Energy', $fmt($nutrition['energy_kj']) . ' kJ', false],
                ['Energy', $fmt($nutrition['energy_kcal']) . ' kcal', true],
                ['Protein', $fmt($nutrition['protein_g']) . ' g', false],
                ['Fat, total', $fmt($nutrition['fat_g']) . ' g', false],
                ['in saturates', $fmt($nutrition['saturated_fat_g']) . ' g', true],
                ['Carbohydrate', $fmt($nutrition['carbohydrates_g']) . ' g', false],
                ['in sugars', $fmt($nutrition['sugars_g']) . ' g', true],
                ['Dietary fibre', $fmt($nutrition['fibre_g']) . ' g', false],
                ['Sodium', $fmt($nutrition['sodium_mg']) . ' mg', false],
            ];
            foreach ($nutritionRows as [$label, $value, $isSub]): ?>
              <tr<?= $isSub ? ' class="nutrition__sub"' : '' ?>>
                <th scope="row"><?= htmlspecialchars($label) ?></th>
                <td><?= htmlspecialchars($value) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <dl class="nutrition__facts">
        <?php if (!empty($nutrition['ingredients'])): ?>
          <div class="nutrition__fact">
            <dt>Ingredients</dt>
            <dd><?= htmlspecialchars((string) $nutrition['ingredients']) ?></dd>
          </div>
        <?php endif; ?>
        <?php if (!empty($nutrition['allergens'])): ?>
          <div class="nutrition__fact">
            <dt>Allergens</dt>
            <dd><?= htmlspecialchars((string) $nutrition['allergens']) ?></dd>
          </div>
        <?php endif; ?>
        <?php if (!empty($nutrition['storage'])): ?>
          <div class="nutrition__fact">
            <dt>Storage</dt>
            <dd><?= htmlspecialchars((string) $nutrition['storage']) ?></dd>
          </div>
        <?php endif; ?>
        <?php if (!empty($nutrition['origin'])): ?>
          <div class="nutrition__fact">
            <dt>Origin</dt>
            <dd><?= htmlspecialchars((string) $nutrition['origin']) ?></dd>
          </div>
        <?php endif; ?>
      </dl>
    </div>
  </section>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/product.js?v=<?= time() ?>" defer></script>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
