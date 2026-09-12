<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Terms of Service';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Customer Agreement</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Terms of Service</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">Effective: September 12, 2026 &middot; Maxi Fine Foods Pty Ltd (ABN 84 192 847 291)</p>
  </div>

  <div class="card-artisan" style="padding: 2.5rem; line-height: 1.7; color: #2c4234; background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--line);">
    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:0;">1. Agreement to Terms</h2>
    <p style="margin-top:.5rem;">
      By accessing or placing an order through the Maxi Fine Foods platform, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">2. Account Registration &amp; Security</h2>
    <p style="margin-top:.5rem;">
      You are responsible for maintaining the confidentiality of your account credentials. You must immediately notify Maxi Fine Foods of any unauthorized access to your account. You must be at least 18 years of age or possess legal parental/guardian consent to purchase.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">3. Pricing, Availability &amp; GST</h2>
    <p style="margin-top:.5rem;">
      All grocery prices are displayed in Australian Dollars (AUD) and are inclusive of 10% Goods and Services Tax (GST) where applicable. Product availability and promotional pricing are subject to stock on hand. While we endeavor to ensure 100% accuracy, in the rare event of a system pricing discrepancy, we will contact you before dispatch.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">4. Orders &amp; Delivery Fulfilment</h2>
    <p style="margin-top:.5rem;">
      Same-day delivery is available for orders confirmed before 2:00 PM local depot time. You agree to provide a clear, accessible delivery address and safe drop-off instructions. Our temperature-controlled delivery vans ensure all chilled and frozen goods arrive in prime condition.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">5. Cancellations &amp; Modifications</h2>
    <p style="margin-top:.5rem;">
      Orders may be cancelled or modified by contacting customer support prior to order dispatch (when the order status is 'Pending' or 'Processing'). Once an order has been marked 'Out for Delivery', cancellations cannot be accommodated due to perishable food safety regulations.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">6. Freshness Guarantee &amp; Australian Consumer Law</h2>
    <p style="margin-top:.5rem;">
      Our goods come with guarantees that cannot be excluded under the Australian Consumer Law (ACL). If any produce, bakery, seafood, or dairy item arrives damaged, sub-standard, or missing, we will promptly provide a replacement or full refund under our 100% Freshness Promise.
    </p>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
