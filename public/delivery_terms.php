<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Delivery Terms & Zones';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Logistics &amp; Shipping</span>
    <h1 class="u-093">Delivery Information &amp; Terms</h1>
    <p class="u-170">Fast, temperature-controlled direct-to-door grocery dispatch across Greater Sydney &amp; Regional NSW</p>
  </div>
  <div class="card-artisan u-203">
    <div class="u-083">
      <div class="u-014">
        <i data-lucide="truck" class="u-042"></i>
        <h3 class="u-128">Free Delivery</h3>
        <p class="u-111">Complimentary on all orders over $60. Flat $8.50 for smaller orders.</p>
      </div>
      <div class="u-014">
        <i data-lucide="clock" class="u-037"></i>
        <h3 class="u-128">2:00 PM Cutoff</h3>
        <p class="u-111">Order before 2 PM for evening same-day doorstep delivery.</p>
      </div>
      <div class="u-014">
        <i data-lucide="thermometer-snowflake" class="u-029"></i>
        <h3 class="u-128">Cold-Chain Fleet</h3>
        <p class="u-111">Refrigerated vehicles keep meats, dairy &amp; produce at 2&deg;C &ndash; 4&deg;C.</p>
      </div>
    </div>
    <h2 class="u-131">1. Daily Delivery Windows</h2>
    <p class="u-168">We operate two convenient delivery shifts 7 days a week:</p>
    <ul class="u-210">
      <li><strong>Morning Dispatch:</strong> 8:00 AM &ndash; 1:00 PM (Orders placed by midnight previous day)</li>
      <li><strong>Evening Same-Day Dispatch:</strong> 2:30 PM &ndash; 8:30 PM (Orders placed by 2:00 PM same day)</li>
    </ul>
    <h2 class="u-132">2. Coverage &amp; Delivery Suburbs</h2>
    <p class="u-168">
      Our fleet services all postcodes across <strong>Sydney CBD, Inner West, Eastern Suburbs, North Shore, Parramatta, Hills District, Canterbury-Bankstown, and St George regions</strong>.
    </p>
    <h2 class="u-132">3. Contactless &amp; Attended Drop-Offs</h2>
    <p class="u-168">
      You may leave special delivery notes during checkout (e.g. <em>"Leave inside front gate behind the pillar"</em>). If you are not home, our drivers will place your groceries in insulated, recyclable thermal bags with biodegradable ice packs to protect chilled items for up to 4 hours.
    </p>
    <h2 class="u-132">4. Real-Time Tracking &amp; Driver SMS</h2>
    <p class="u-168">
      Once our depot assigns a dedicated delivery driver to your order, you can track the status live on your <a href="<?= BASE_URL ?>/my_orders.php" class="u-041">My Orders</a> dashboard.
    </p>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
