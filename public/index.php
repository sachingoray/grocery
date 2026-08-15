<?php
require_once __DIR__ . '/../includes/session.php';

$pageTitle = 'Shop';
$activeNav = 'shop';
$products = mff_get_products();
$categories = array_values(array_unique(array_column($products, 'category')));

require __DIR__ . '/../includes/header.php';
?>

<section class="hero-card" aria-labelledby="shop-title">
  <div class="hero-card__copy">
    <p class="hero-card__eyebrow">Fresh &middot; Local &middot; Delivered</p>
    <h1 id="shop-title" class="hero-card__title">Groceries you can trust</h1>
    <p class="hero-card__desc">Hand-picked produce, bakery, seafood and pantry staples, delivered fresh to your door.</p>
    <a href="#catalogue" class="btn-ink" style="margin-top:2rem;width:fit-content;">
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
    <label class="search-field">
      <span class="sr-only">Search grocery products</span>
      <i data-lucide="search" class="icon-sm"></i>
      <input id="product-search" name="product_search" type="search" placeholder="Search the market">
    </label>
  </div>

  <div class="filter-row" role="group" aria-label="Product categories">
    <button class="filter-button filter-button--active" data-category="all" type="button">All goods</button>
    <?php foreach ($categories as $category): ?>
      <button class="filter-button" data-category="<?= htmlspecialchars($category) ?>" type="button"><?= htmlspecialchars($category) ?></button>
    <?php endforeach; ?>
  </div>

  <p id="no-products" class="hidden" style="padding:3.5rem 0;text-align:center;color:#56715f;">No products match your search.</p>

  <div id="product-grid" class="product-grid">
    <?php foreach ($products as $product): ?>
      <?php
        $isLowStock = $product['stock'] <= ($product['low_stock_threshold'] ?? 10);
        $searchBlob = strtolower($product['name'] . ' ' . $product['category'] . ' ' . ($product['badge'] ?? ''));
      ?>
      <article class="product-card" data-category="<?= htmlspecialchars($product['category']) ?>" data-search="<?= htmlspecialchars($searchBlob) ?>">
        <div class="product-card__media">
          <img src="<?= htmlspecialchars($product['image_url']) ?>" data-fallback="<?= BASE_URL ?>/assets/placeholder.svg" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
          <?php if (!empty($product['badge'])): ?>
            <span class="product-card__badge"><?= htmlspecialchars($product['badge']) ?></span>
          <?php endif; ?>
        </div>
        <div class="product-card__body">
          <p class="product-card__category"><?= htmlspecialchars($product['category']) ?></p>
          <div class="product-card__row">
            <h3 class="product-card__name"><a href="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
            <span class="product-card__price"><?= mff_money($product['price']) ?></span>
          </div>
          <p class="product-card__stock">
            <?php if ($isLowStock): ?>
              <span class="status status-low">Low stock &mdash; <?= (int) $product['stock'] ?> left</span>
            <?php else: ?>
              <span class="status status-instock">In stock</span>
            <?php endif; ?>
          </p>
          <form method="post" action="<?= BASE_URL ?>/cart.php">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <button type="submit" class="btn-tomato" style="width:100%;justify-content:center;margin-top:1rem;">Add to cart</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
