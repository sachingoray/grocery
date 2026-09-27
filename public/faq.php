<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Help & FAQs';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Support Desk</span>
    <h1 class="u-093">Frequently Asked Questions</h1>
    <p class="u-170">Everything you need to know about ordering, delivery, cold-chain safety, and customer accounts.</p>
  </div>
  <div class="u-060">
    <!-- FAQ 1 -->
    <details class="faq-item card-artisan u-206" open>
      <summary class="u-148">
        <span>🛒 How does same-day grocery delivery work?</span>
      </summary>
      <div class="u-194">
        Simply place your order before <strong>2:00 PM</strong> any day of the week, and our central depot team will pick, pack, and chill your items for delivery between 2:30 PM and 8:30 PM the very same afternoon. Orders placed after 2:00 PM are scheduled for the next morning run (8:00 AM – 1:00 PM).
      </div>
    </details>
    <!-- FAQ 2 -->
    <details class="faq-item card-artisan u-206">
      <summary class="u-148">
        <span>🚚 How much is delivery, and is there a free delivery threshold?</span>
      </summary>
      <div class="u-194">
        Delivery is completely <strong>FREE on all orders over $60</strong>! For smaller basket orders under $60, a standard flat fee of $8.50 is applied at checkout.
      </div>
    </details>
    <!-- FAQ 3 -->
    <details class="faq-item card-artisan u-206">
      <summary class="u-148">
        <span>🥩 How do you keep seafood, meat, and dairy fresh in transit?</span>
      </summary>
      <div class="u-194">
        We operate custom refrigerated vans strictly maintained at 2°C to 4°C. Perishable goods are packed in food-grade thermal insulation with eco-friendly ice gel packs to maintain optimal freshness right up until you unpack your fridge.
      </div>
    </details>
    <!-- FAQ 4 -->
    <details class="faq-item card-artisan u-206">
      <summary class="u-148">
        <span>💳 What payment methods do you accept?</span>
      </summary>
      <div class="u-194">
        We accept Visa, Mastercard, American Express, PayPal, Apple Pay, and <strong>Cash on Delivery (COD)</strong> upon driver arrival.
      </div>
    </details>
    <!-- FAQ 5 -->
    <details class="faq-item card-artisan u-206">
      <summary class="u-148">
        <span>📱 How can I track my order live?</span>
      </summary>
      <div class="u-194">
        Navigate to <a href="<?= BASE_URL ?>/my_orders.php" class="u-041">My Orders</a> to see the live status (<em>Pending &rarr; Processing &rarr; Out for Delivery &rarr; Delivered</em>) and view your designated driver's name in real time.
      </div>
    </details>
    <!-- FAQ 6 -->
    <details class="faq-item card-artisan u-206">
      <summary class="u-148">
        <span>🛡️ What if I am unhappy with an item's freshness?</span>
      </summary>
      <div class="u-194">
        Under our <strong>100% Farm Fresh Guarantee</strong>, notify us within 48 hours and we will issue an instant replacement or refund with zero fuss. Check our <a href="<?= BASE_URL ?>/refunds.php" class="u-040">Refund Policy</a> for details.
      </div>
    </details>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
