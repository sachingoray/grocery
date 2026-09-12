<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Help & FAQs';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Support Desk</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Frequently Asked Questions</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">Everything you need to know about ordering, delivery, cold-chain safety, and customer accounts.</p>
  </div>

  <div style="display:flex;flex-direction:column;gap:1rem;">
    <!-- FAQ 1 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;" open>
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>🛒 How does same-day grocery delivery work?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        Simply place your order before <strong>2:00 PM</strong> any day of the week, and our central depot team will pick, pack, and chill your items for delivery between 2:30 PM and 8:30 PM the very same afternoon. Orders placed after 2:00 PM are scheduled for the next morning run (8:00 AM – 1:00 PM).
      </div>
    </details>

    <!-- FAQ 2 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;">
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>🚚 How much is delivery, and is there a free delivery threshold?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        Delivery is completely <strong>FREE on all orders over $60</strong>! For smaller basket orders under $60, a standard flat fee of $8.50 is applied at checkout.
      </div>
    </details>

    <!-- FAQ 3 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;">
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>🥩 How do you keep seafood, meat, and dairy fresh in transit?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        We operate custom refrigerated vans strictly maintained at 2°C to 4°C. Perishable goods are packed in food-grade thermal insulation with eco-friendly ice gel packs to maintain optimal freshness right up until you unpack your fridge.
      </div>
    </details>

    <!-- FAQ 4 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;">
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>💳 What payment methods do you accept?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        We accept Visa, Mastercard, American Express, PayPal, Apple Pay, and <strong>Cash on Delivery (COD)</strong> upon driver arrival.
      </div>
    </details>

    <!-- FAQ 5 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;">
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>📱 How can I track my order live?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        Navigate to <a href="<?= BASE_URL ?>/my_orders.php" style="color:var(--leaf);font-weight:700;text-decoration:underline;">My Orders</a> to see the live status (<em>Pending &rarr; Processing &rarr; Out for Delivery &rarr; Delivered</em>) and view your designated driver's name in real time.
      </div>
    </details>

    <!-- FAQ 6 -->
    <details class="faq-item card-artisan" style="padding:1.5rem;background:#ffffff;border-radius:1rem;border:1px solid var(--line);cursor:pointer;">
      <summary style="font-weight:700;font-size:1.1rem;color:var(--ink);display:flex;justify-content:space-between;align-items:center;">
        <span>🛡️ What if I am unhappy with an item's freshness?</span>
      </summary>
      <div style="margin-top:1rem;color:#2c4234;font-size:.92rem;line-height:1.6;border-top:1px solid var(--oat);padding-top:.75rem;">
        Under our <strong>100% Farm Fresh Guarantee</strong>, notify us within 48 hours and we will issue an instant replacement or refund with zero fuss. Check our <a href="<?= BASE_URL ?>/refunds.php" style="color:var(--leaf);font-weight:700;">Refund Policy</a> for details.
      </div>
    </details>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
