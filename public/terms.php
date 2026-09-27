<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Terms of Service';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Customer Agreement</span>
    <h1 class="u-093">Terms of Service</h1>
    <p class="u-170">Effective: September 12, 2026 &middot; Maxi Fine Foods Pty Ltd (ABN 84 192 847 291)</p>
  </div>
  <div class="card-artisan u-203">
    <h2 class="u-131">1. Agreement to Terms</h2>
    <p class="u-168">
      By accessing or placing an order through the Maxi Fine Foods platform, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.
    </p>
    <h2 class="u-132">2. Account Registration &amp; Security</h2>
    <p class="u-168">
      You are responsible for maintaining the confidentiality of your account credentials. You must immediately notify Maxi Fine Foods of any unauthorized access to your account. You must be at least 18 years of age or possess legal parental/guardian consent to purchase.
    </p>
    <h2 class="u-132">3. Pricing, Availability &amp; GST</h2>
    <p class="u-168">
      All grocery prices are displayed in Australian Dollars (AUD) and are inclusive of 10% Goods and Services Tax (GST) where applicable. Product availability and promotional pricing are subject to stock on hand. While we endeavor to ensure 100% accuracy, in the rare event of a system pricing discrepancy, we will contact you before dispatch.
    </p>
    <h2 class="u-132">4. Orders &amp; Delivery Fulfilment</h2>
    <p class="u-168">
      Same-day delivery is available for orders confirmed before 2:00 PM local depot time. You agree to provide a clear, accessible delivery address and safe drop-off instructions. Our temperature-controlled delivery vans ensure all chilled and frozen goods arrive in prime condition.
    </p>
    <h2 class="u-132">5. Cancellations &amp; Modifications</h2>
    <p class="u-168">
      Orders may be cancelled or modified by contacting customer support prior to order dispatch (when the order status is 'Pending' or 'Processing'). Once an order has been marked 'Out for Delivery', cancellations cannot be accommodated due to perishable food safety regulations.
    </p>
    <h2 class="u-132">6. Freshness Guarantee &amp; Australian Consumer Law</h2>
    <p class="u-168">
      Our goods come with guarantees that cannot be excluded under the Australian Consumer Law (ACL). If any produce, bakery, seafood, or dairy item arrives damaged, sub-standard, or missing, we will promptly provide a replacement or full refund under our 100% Freshness Promise.
    </p>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
