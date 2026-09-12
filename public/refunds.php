<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Refund & Returns Policy';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Freshness Promise</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Refund &amp; Freshness Policy</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">100% Money-Back Guarantee &middot; No-Hassle Resolution within 24 Hours</p>
  </div>

  <div class="card-artisan" style="padding: 2.5rem; line-height: 1.7; color: #2c4234; background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--line);">
    <div style="background:rgba(47,96,65,.08);border:1px solid rgba(47,96,65,.2);border-radius:1rem;padding:1.5rem;margin-bottom:2rem;display:flex;align-items:flex-start;gap:1rem;">
      <i data-lucide="shield-check" style="color:var(--leaf);width:36px;height:36px;flex-shrink:0;"></i>
      <div>
        <h3 style="font-size:1.15rem;font-weight:700;color:var(--ink);margin:0;">The Maxi 100% Farm Fresh Guarantee</h3>
        <p style="font-size:.9rem;color:#2c4234;margin-top:.35rem;">
          If any fruit, vegetable, meat, seafood, bakery, or dairy product does not meet your high standards of freshness, flavor, and quality, we will gladly replace it or issue an instant full refund.
        </p>
      </div>
    </div>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:0;">1. How to Request a Refund or Redelivery</h2>
    <p style="margin-top:.5rem;">Requesting assistance is fast and simple:</p>
    <ol style="padding-left:1.5rem;margin-top:.5rem;">
      <li>Notify our customer service team within <strong>48 hours</strong> of receiving your delivery.</li>
      <li>Provide your Order ID (found on your confirmation screen or in <a href="<?= BASE_URL ?>/my_orders.php" style="color:var(--leaf);font-weight:700;">My Orders</a>) and a brief description (or photo) of the item.</li>
      <li>Our team will process an immediate store credit, original payment method refund, or schedule an express redelivery on our next delivery run.</li>
    </ol>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">2. Incorrect or Missing Items</h2>
    <p style="margin-top:.5rem;">
      In the rare event that an item is omitted by our packing depot or an incorrect SKU is provided, we will immediately credit your account or dispatch a replacement driver at no additional cost.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">3. Australian Consumer Law Guarantee</h2>
    <p style="margin-top:.5rem;">
      Our goods and services come with guarantees that cannot be excluded under the Australian Consumer Law. For major failures with the service, you are entitled to cancel your service contract with us and to a refund for the unused portion, or to compensation for its reduced value.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">4. Need Assistance?</h2>
    <p style="margin-top:.5rem;">Our customer happiness team is available 7 days a week:</p>
    <div style="margin-top:1rem;background:#f6f3eb;padding:1.25rem;border-radius:1rem;border:1px solid var(--line);">
      <p style="margin:0;font-weight:700;color:var(--ink);">Customer Support &amp; Claims Desk</p>
      <p style="margin:.25rem 0 0;font-size:.875rem;color:#56715f;">Toll-Free Phone: <strong>1800 629 436</strong> (Mon–Sun 7am–9pm)</p>
      <p style="margin:.25rem 0 0;font-size:.875rem;color:#56715f;">Email: <a href="mailto:support@maxifinefoods.com.au" style="color:var(--leaf);font-weight:700;">support@maxifinefoods.com.au</a></p>
    </div>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
