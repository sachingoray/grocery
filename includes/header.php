<?php
require_once __DIR__ . '/session.php';
$flash = mff_get_flash();
$currentRole = mff_role();
$cartCount = cart_count();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Maxi Fine Foods</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
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

      <nav class="nav-actions" aria-label="Primary">
        <?php if ($currentRole === 'guest'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'shop' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/index.php">Shop</a>
          <a class="nav-action" href="<?= BASE_URL ?>/login.php">Log in</a>
          <a class="nav-action" href="<?= BASE_URL ?>/register.php">Register</a>
          <a class="cart-button" href="<?= BASE_URL ?>/cart.php">
            <i data-lucide="shopping-bag" class="icon-sm"></i>
            <span>Cart</span>
            <span class="cart-count" aria-live="polite"><?= $cartCount ?></span>
          </a>
        <?php elseif ($currentRole === 'customer'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'shop' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/index.php">Shop</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/my_orders.php">My Orders</a>
          <span style="font-size:.825rem;color:#1e3a29;background:#e8ede9;padding:.35rem .75rem;border-radius:999px;font-weight:600;">
            👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>
          </span>
          <a class="cart-button" href="<?= BASE_URL ?>/cart.php">
            <i data-lucide="shopping-bag" class="icon-sm"></i>
            <span>Cart</span>
            <span class="cart-count" aria-live="polite"><?= $cartCount ?></span>
          </a>
          <a class="nav-action" href="<?= BASE_URL ?>/logout.php" style="color:var(--tomato);">Log out</a>
        <?php elseif ($currentRole === 'delivery'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'delivery' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/delivery/my_deliveries.php">
            <i data-lucide="truck" class="icon-sm" style="display:inline;vertical-align:middle;margin-right:.25rem;"></i>Delivery Queue
          </a>
          <span style="font-size:.825rem;color:#92400e;background:#fef3c7;padding:.35rem .75rem;border-radius:999px;font-weight:600;">
            🚚 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Driver') ?> (Delivery)
          </span>
          <a class="nav-action" href="<?= BASE_URL ?>/logout.php" style="color:var(--tomato);">Log out</a>
        <?php elseif ($currentRole === 'admin'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_orders.php">Orders</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_products' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_products.php">Products</a>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin_drivers' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_drivers.php">Drivers</a>
          <a class="nav-action" href="<?= BASE_URL ?>/index.php">Storefront</a>
          <span style="font-size:.825rem;color:#065f46;background:#d1fae5;padding:.35rem .75rem;border-radius:999px;font-weight:600;">
            🛡️ <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> (Admin)
          </span>
          <a class="nav-action" href="<?= BASE_URL ?>/logout.php" style="color:var(--tomato);">Log out</a>
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
