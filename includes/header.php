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

      <nav class="role-switcher" aria-label="Role switcher (Phase 1 demo)">
        <span class="role-switcher__label">Viewing as:</span>
        <?php foreach (['guest' => 'Guest', 'customer' => 'Customer', 'admin' => 'Admin', 'delivery' => 'Delivery'] as $roleKey => $roleLabel): ?>
          <form method="post" action="<?= BASE_URL ?>/set_role.php" class="role-switcher__form">
            <input type="hidden" name="role" value="<?= $roleKey ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php') ?>">
            <button type="submit" class="role-pill <?= $currentRole === $roleKey ? 'role-pill--active' : '' ?>"><?= $roleLabel ?></button>
          </form>
        <?php endforeach; ?>
      </nav>

      <nav class="nav-actions" aria-label="Primary">
        <a class="nav-action <?= ($activeNav ?? '') === 'shop' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/index.php">Shop</a>
        <?php if ($currentRole === 'customer'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'orders' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/my_orders.php">My Orders</a>
        <?php endif; ?>
        <?php if ($currentRole === 'admin'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'admin' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a>
        <?php endif; ?>
        <?php if ($currentRole === 'delivery'): ?>
          <a class="nav-action <?= ($activeNav ?? '') === 'delivery' ? 'nav-action--active' : '' ?>" href="<?= BASE_URL ?>/delivery/my_deliveries.php">Delivery</a>
        <?php endif; ?>
        <?php if ($currentRole !== 'guest'): ?>
          <a class="nav-action" href="<?= BASE_URL ?>/logout.php">Log out</a>
        <?php endif; ?>
        

        <a class="cart-button" href="<?= BASE_URL ?>/cart.php">
          <i data-lucide="shopping-bag" class="icon-sm"></i>
          <span>Cart</span>
          <span class="cart-count" aria-live="polite"><?= $cartCount ?></span>
        </a>
      </nav>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="status">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <main class="page-main">
