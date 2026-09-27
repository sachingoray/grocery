<?php
require_once __DIR__ . '/session.php';
$flash = mff_get_flash();
$currentRole = mff_role();
$cartCount = cart_count();
// The nav badge shows the running total as well as the item count, so a
// shopper sees the money without opening the cart. cart_contents() reuses the
// already-cached cart rows (see mff_cart_rows), and only runs when the cart
// actually has something in it, so an empty cart costs no extra queries.
$cartTotal = $cartCount > 0 ? mff_money(cart_contents()['total']) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Maxi Fine Foods</title>
  <!-- Build marker: confirms which code THIS host is actually serving. If this
       tag is missing, the host is running an older build. -->
  <meta name="mff-build" content="<?= htmlspecialchars(MFF_BUILD) ?>">
  <?php /* Tailwind Play CDN removed: the site's own stylesheet is the only styling
         dependency (no Tailwind utility classes are used), and a third-party
         script that injects its own <style> would force 'unsafe-inline' into
         style-src. The preflight rules it used to provide now live in
         assets/style.css. See includes/session.php for the CSP. */ ?>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/ai-chat.css?v=<?= time() ?>">
  <!-- Ambient motion layer (pointer tracking, aurora, scroll reveals). Purely
       additive and loaded last so it can refine, but never fight, style.css.
       Remove this one line to return to the previous, animation-free look. -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/motion.css?v=<?= time() ?>">
</head>
<!-- data-base-url is read by main.js (window.MFF_BASE_URL) to build the cart
     fetch URLs. Without it every cart AJAX call hits the domain root and 404s,
     which falls back to a full page reload. Do not remove. -->
<body data-base-url="<?= BASE_URL ?>">
<div class="app-shell">
  <div class="announcement-bar">
    <span>Free delivery on orders over $50 &middot; Same-day slots available</span>
  </div>
  <header class="site-header">
    <div class="site-header__inner">
      <a href="<?= BASE_URL ?>/index.php" class="wordmark-link" aria-label="Maxi Fine Foods home">
        <span class="wordmark">Maxi Fine Foods</span>
        <span class="wordmark-tagline">Online Grocery Market</span>
      </a>
      <?php if ($showNavSearch ?? false): ?>
        <!-- Sticky-header copy of the homepage catalogue search. It stays hidden
             until main.js sees the real search bar scroll up behind this bar,
             then mirrors whatever the shopper types (both inputs are kept in
             sync). Only pages that set $showNavSearch render it. -->
        <div class="nav-search" data-nav-search>
          <div class="search-actions">
            <label class="search-field">
              <span class="sr-only">Search grocery products</span>
              <i data-lucide="search" class="icon-sm"></i>
              <input id="nav-product-search" name="nav_product_search" type="search"
                     placeholder="Search the market" data-product-search autocomplete="off">
            </label>
          </div>
        </div>
      <?php endif; ?>
      <nav class="nav-actions" aria-label="Primary">
        <?php if ($currentRole === 'guest'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'shop' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/index.php">Shop</a>
          <a class="nav-action" href="<?= BASE_URL ?>/login.php">Log in</a>
          <a class="nav-action" href="<?= BASE_URL ?>/register.php">Register</a>
          <a class="cart-button" href="<?= BASE_URL ?>/cart.php">
            <i data-lucide="shopping-bag" class="icon-sm"></i>
            <span>Cart</span>
            <span class="cart-count" data-cart-count aria-live="polite"><?= $cartCount ?></span>
            <span class="cart-total"<?= $cartTotal === '' ? ' hidden' : '' ?>><span class="sr-only">Cart total </span><span data-cart-total><?= htmlspecialchars($cartTotal) ?></span></span>
          </a>
        <?php elseif ($currentRole === 'customer'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'shop' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/index.php">Shop</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/my_orders.php">My Orders</a>
          <span class="u-105">
            ðŸ‘¤ <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>
          </span>
          <a class="cart-button" href="<?= BASE_URL ?>/cart.php">
            <i data-lucide="shopping-bag" class="icon-sm"></i>
            <span>Cart</span>
            <span class="cart-count" data-cart-count aria-live="polite"><?= $cartCount ?></span>
            <span class="cart-total"<?= $cartTotal === '' ? ' hidden' : '' ?>><span class="sr-only">Cart total </span><span data-cart-total><?= htmlspecialchars($cartTotal) ?></span></span>
          </a>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php elseif ($currentRole === 'delivery'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'delivery' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/delivery/my_deliveries.php">
            <i data-lucide="truck" class="icon-sm u-085"></i>Delivery Queue
          </a>
          <span class="u-110">
            ðŸšš <?= htmlspecialchars($_SESSION['user_name'] ?? 'Driver') ?> (Delivery)
          </span>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php elseif ($currentRole === 'logistics_manager'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_drivers' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_drivers.php">Drivers Fleet</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_orders.php">Dispatch Orders</a>
          <a class="nav-action" href="<?= BASE_URL ?>/index.php">Storefront</a>
          <span class="u-109">
            ðŸšš <?= htmlspecialchars($_SESSION['user_name'] ?? 'Logistics Mgr') ?> (Fleet Mgr)
          </span>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php elseif ($currentRole === 'inventory_manager'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_products' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_products.php">Product Catalog</a>
          <a class="nav-action" href="<?= BASE_URL ?>/index.php">Storefront</a>
          <span class="u-106">
            ðŸ“¦ <?= htmlspecialchars($_SESSION['user_name'] ?? 'Inventory Mgr') ?> (Product Mgr)
          </span>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php elseif ($currentRole === 'support_staff'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_orders.php">Customer Orders</a>
          <a class="nav-action" href="<?= BASE_URL ?>/index.php">Storefront</a>
          <span class="u-108">
            ðŸŽ§ <?= htmlspecialchars($_SESSION['user_name'] ?? 'Support') ?> (Support Staff)
          </span>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php elseif ($currentRole === 'admin'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_orders.php">Orders</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_products' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_products.php">Products</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_drivers' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_drivers.php">Drivers</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_users' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_users.php">Staff &amp; Users</a>
          <a class="nav-action" href="<?= BASE_URL ?>/index.php">Storefront</a>
          <span class="u-104">
            ðŸ›¡ï¸ <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> (Admin)
          </span>
          <a class="nav-action u-045" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="status">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>
  <main class="page-main">
