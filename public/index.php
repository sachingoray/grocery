<?php
/**
 * index.php — Product catalogue / shop front page.
 * Date: 27/09/2026
 * Purpose: Lists all products with category filters, search, and per-product
 * Add to Cart forms; shows live quantity-in-cart state per product.
 */

require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Shop';
$activeNav = 'shop';
// Tells includes/header.php to render the sticky-header copy of the catalogue
// search. Only this page has #product-search for that copy to mirror.
$showNavSearch = true;
$products = mff_get_products();
$categories = array_values(array_unique(array_column($products, 'category')));
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<section class="hero-card" aria-labelledby="shop-title">
  <div class="hero-card__copy">
    <p class="hero-card__eyebrow">Fresh &middot; Local &middot; Delivered</p>
    <h1 id="shop-title" class="hero-card__title">Groceries you can trust</h1>
    <p class="hero-card__desc">Hand-picked produce, bakery, seafood and pantry staples, delivered fresh to your door.</p>
    <a href="#catalogue" class="btn-ink u-198">
      <span>Browse the market</span><i data-lucide="arrow-down-right" class="icon-sm"></i>
    </a>
  </div>
<div class="hero-card__media hero-slideshow" data-hero-slideshow>
    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&q=80" alt="Fresh produce display" class="is-active" loading="lazy">
    <img src="https://images.unsplash.com/photo-1610348725531-843dff563e2c?w=1200&q=80" alt="Fresh bakery bread" loading="lazy">
    <img src="https://images.unsplash.com/photo-1519996529931-28324d5a630e?w=1200&q=80" alt="Fresh seafood on ice" loading="lazy">
    <img src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=1200&q=80" alt="Fresh cut meat display" loading="lazy">
    <img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=1200&q=80" alt="Dairy products, milk and cheese" loading="lazy">
    <img src="https://images.unsplash.com/photo-1550989460-0adf9ea622e2?w=1200&q=80" alt="Fresh milk bottles" loading="lazy">
    <img src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=1200&q=80" alt="Grocery store aisle" loading="lazy">
    <button type="button" class="hero-slideshow__nav hero-slideshow__nav--prev" data-hero-prev aria-label="Previous photo">
      <i data-lucide="chevron-left" class="icon-sm"></i>
    </button>
    <button type="button" class="hero-slideshow__nav hero-slideshow__nav--next" data-hero-next aria-label="Next photo">
      <i data-lucide="chevron-right" class="icon-sm"></i>
    </button>
</div>
  <span class="hero-card__stamp">Same-day delivery</span>
</section>
<section id="catalogue" aria-labelledby="catalogue-title">
  <div class="catalogue-toolbar">
    <div>
      <p class="section-eyebrow">The market</p>
      <h2 id="catalogue-title" class="section-title">Today's picks</h2>
    </div>
    <div class="search-actions">
      <label class="search-field">
        <span class="sr-only">Search grocery products</span>
        <i data-lucide="search" class="icon-sm"></i>
        <input id="product-search" name="product_search" type="search" placeholder="Search the market" data-product-search>
      </label>
    </div>
  </div>
  <div class="filter-row" role="group" aria-label="Product categories">
    <button class="filter-button filter-button--active filter-button--specials" data-category="specials" type="button">🔥 Weekly Specials</button>
    <?php foreach ($categories as $category): ?>
      <button class="filter-button" data-category="<?= htmlspecialchars($category) ?>" type="button"><?= htmlspecialchars($category) ?></button>
    <?php endforeach; ?>
  </div>
  <p id="no-products" class="hidden u-208">No products match your search.</p>
  <?php if (empty($products)): ?>
    <div class="u-204">
      <i data-lucide="shopping-basket" class="u-223"></i>
      <h3 class="u-095">Market Catalog is Currently Empty</h3>
      <p class="u-164">All products have been cleared. New grocery stock can be added via the Admin Panel or populated by the system.</p>
    </div>
  <?php else: ?>
  <div id="product-grid" class="product-grid">
    <?php foreach ($products as $product): ?>
      <?php
        $isLowStock = $product['stock'] <= ($product['low_stock_threshold'] ?? 10);
        $isSpecial = !empty($product['is_special']) || (!empty($product['original_price']) && $product['original_price'] > $product['price']);
        $searchBlob = strtolower($product['name'] . ' ' . $product['category'] . ' ' . ($product['badge'] ?? '') . ($isSpecial ? ' special sale deal' : ''));
      ?>
      <article class="product-card <?= $isSpecial ? 'product-card--special' : 'is-hidden' ?>" data-category="<?= htmlspecialchars($product['category']) ?>" data-special="<?= $isSpecial ? '1' : '0' ?>" data-search="<?= htmlspecialchars($searchBlob) ?>">
        <div class="product-card__media">
          <img src="<?= htmlspecialchars($product['image_url']) ?>" data-fallback="<?= BASE_URL ?>/assets/placeholder.svg" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
          <?php if (!empty($product['badge'])): ?>
            <span class="product-card__badge <?= $isSpecial ? 'product-card__badge--sale' : '' ?>"><?= htmlspecialchars($product['badge']) ?></span>
          <?php endif; ?>
        </div>
        <div class="product-card__body">
          <p class="product-card__category"><?= htmlspecialchars($product['category']) ?></p>
          <div class="product-card__row u-002">
            <h3 class="product-card__name"><a href="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
            <div class="u-215">
              <span class="product-card__price <?= $isSpecial ? 'product-card__price--sale' : '' ?>"><?= mff_money($product['price']) ?></span>
              <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                <span class="product-card__was-price">Was <del><?= mff_money($product['original_price']) ?></del></span>
              <?php endif; ?>
            </div>
          </div>
          <p class="product-card__stock">
            <?php if ($isLowStock): ?>
              <span class="status status-low">Low stock &mdash; <?= (int) $product['stock'] ?> left</span>
            <?php else: ?>
              <span class="status status-instock">In stock</span>
            <?php endif; ?>
          </p>
          <div class="card-cart-controls u-192" data-product-id="<?= (int) $product['id'] ?>">
            <?php 
              // Per-user quantity, resolved from the database-backed cart.
              $qtyInCart = cart_qty((int) $product['id']);
            ?>
            <button type="button" class="card-icon-btn" data-quickview
                    data-name="<?= htmlspecialchars($product['name']) ?>"
                    data-category="<?= htmlspecialchars($product['category']) ?>"
                    data-price="<?= mff_money($product['price']) ?>"
                    data-was-price="<?= (!empty($product['original_price']) && $product['original_price'] > $product['price']) ? mff_money($product['original_price']) : '' ?>"
                    data-special="<?= $isSpecial ? '1' : '0' ?>"
                    data-badge="<?= htmlspecialchars($product['badge'] ?? '') ?>"
                    data-image="<?= htmlspecialchars($product['image_url'] ?? '') ?>"
                    data-description="<?= htmlspecialchars($product['description'] ?? '') ?>"
                    data-stock-low="<?= $isLowStock ? '1' : '0' ?>"
                    data-stock-count="<?= (int) $product['stock'] ?>"
                    data-url="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>"
                    aria-label="View details for <?= htmlspecialchars($product['name']) ?>">
              <i data-lucide="receipt" class="icon-sm"></i>
            </button>
<form method="post" action="<?= BASE_URL ?>/cart.php" class="add-to-cart-form <?= $qtyInCart > 0 ? 'is-hidden' : '' ?>">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
              <button type="submit" class="btn-tomato u-225" aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart">
                <i data-lucide="shopping-cart" class="icon-sm"></i>
                <span>Add</span>
              </button>
            </form>
            <div class="qty-stepper <?= $qtyInCart > 0 ? '' : 'is-hidden' ?> u-222" data-product-id="<?= (int) $product['id'] ?>">
              <button type="button" class="btn-step-minus" aria-label="Decrease <?= htmlspecialchars($product['name']) ?>">&minus;</button>
              <span data-qty-display><?= (int) $qtyInCart ?></span>
              <button type="button" class="btn-step-plus" aria-label="Increase <?= htmlspecialchars($product['name']) ?>">+</button>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php if (!empty($products)): ?>
<!-- Quick-view details popup. Static markup (CSP forbids inline scripts);
     main.js fills it from the data-* attributes on the card's [data-quickview]
     button. One instance serves every card. -->
<div class="modal-overlay quickview-overlay" id="quickview-overlay" hidden>
  <div class="quickview" role="dialog" aria-modal="true" aria-labelledby="quickview-title">
    <button type="button" class="quickview__close" id="quickview-close" aria-label="Close details">
      <i data-lucide="x" class="icon-sm"></i>
    </button>
    <div class="quickview__media">
      <img id="quickview-img" alt="" data-fallback="<?= BASE_URL ?>/assets/placeholder.svg">
      <span class="product-card__badge quickview__badge is-hidden" id="quickview-badge"></span>
    </div>
    <div class="quickview__body">
      <p class="product-card__category" id="quickview-category"></p>
      <h3 class="quickview__title" id="quickview-title"></h3>
      <div class="quickview__prices">
        <span class="product-card__price" id="quickview-price"></span>
        <span class="product-card__was-price is-hidden" id="quickview-was-price"></span>
      </div>
      <p class="product-card__stock"><span class="status" id="quickview-stock"></span></p>
      <p class="quickview__desc" id="quickview-desc"></p>
      <a class="btn-ink quickview__cta" id="quickview-link" href="#">
        View full details
        <i data-lucide="arrow-right" class="icon-sm"></i>
      </a>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
