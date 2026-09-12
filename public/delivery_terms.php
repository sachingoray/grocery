<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Delivery Terms & Zones';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Logistics &amp; Shipping</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Delivery Information &amp; Terms</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">Fast, temperature-controlled direct-to-door grocery dispatch across Greater Sydney &amp; Regional NSW</p>
  </div>

  <div class="card-artisan" style="padding: 2.5rem; line-height: 1.7; color: #2c4234; background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--line);">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1rem;margin-bottom:2rem;">
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;">
        <i data-lucide="truck" style="color:var(--leaf);margin-bottom:.5rem;width:28px;height:28px;"></i>
        <h3 style="font-size:1.05rem;font-weight:700;color:var(--ink);margin:0;">Free Delivery</h3>
        <p style="font-size:.85rem;color:#56715f;margin-top:.25rem;">Complimentary on all orders over $60. Flat $8.50 for smaller orders.</p>
      </div>
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;">
        <i data-lucide="clock" style="color:var(--gold);margin-bottom:.5rem;width:28px;height:28px;"></i>
        <h3 style="font-size:1.05rem;font-weight:700;color:var(--ink);margin:0;">2:00 PM Cutoff</h3>
        <p style="font-size:.85rem;color:#56715f;margin-top:.25rem;">Order before 2 PM for evening same-day doorstep delivery.</p>
      </div>
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;">
        <i data-lucide="thermometer-snowflake" style="color:#0284c7;margin-bottom:.5rem;width:28px;height:28px;"></i>
        <h3 style="font-size:1.05rem;font-weight:700;color:var(--ink);margin:0;">Cold-Chain Fleet</h3>
        <p style="font-size:.85rem;color:#56715f;margin-top:.25rem;">Refrigerated vehicles keep meats, dairy &amp; produce at 2&deg;C &ndash; 4&deg;C.</p>
      </div>
    </div>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:0;">1. Daily Delivery Windows</h2>
    <p style="margin-top:.5rem;">We operate two convenient delivery shifts 7 days a week:</p>
    <ul style="padding-left:1.5rem;margin-top:.5rem;">
      <li><strong>Morning Dispatch:</strong> 8:00 AM &ndash; 1:00 PM (Orders placed by midnight previous day)</li>
      <li><strong>Evening Same-Day Dispatch:</strong> 2:30 PM &ndash; 8:30 PM (Orders placed by 2:00 PM same day)</li>
    </ul>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">2. Coverage &amp; Delivery Suburbs</h2>
    <p style="margin-top:.5rem;">
      Our fleet services all postcodes across <strong>Sydney CBD, Inner West, Eastern Suburbs, North Shore, Parramatta, Hills District, Canterbury-Bankstown, and St George regions</strong>.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">3. Contactless &amp; Attended Drop-Offs</h2>
    <p style="margin-top:.5rem;">
      You may leave special delivery notes during checkout (e.g. <em>"Leave inside front gate behind the pillar"</em>). If you are not home, our drivers will place your groceries in insulated, recyclable thermal bags with biodegradable ice packs to protect chilled items for up to 4 hours.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">4. Real-Time Tracking &amp; Driver SMS</h2>
    <p style="margin-top:.5rem;">
      Once our depot assigns a dedicated delivery driver to your order, you can track the status live on your <a href="<?= BASE_URL ?>/my_orders.php" style="color:var(--leaf);font-weight:700;text-decoration:underline;">My Orders</a> dashboard.
    </p>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
