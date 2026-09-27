<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Refund & Returns Policy';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Freshness Promise</span>
    <h1 class="u-093">Refund &amp; Freshness Policy</h1>
    <p class="u-170">100% Money-Back Guarantee &middot; No-Hassle Resolution within 24 Hours</p>
  </div>
  <div class="card-artisan u-203">
    <div class="u-024">
      <i data-lucide="shield-check" class="u-044"></i>
      <div>
        <h3 class="u-129">The Maxi 100% Farm Fresh Guarantee</h3>
        <p class="u-116">
          If any fruit, vegetable, meat, seafood, bakery, or dairy product does not meet your high standards of freshness, flavor, and quality, we will gladly replace it or issue an instant full refund.
        </p>
      </div>
    </div>
    <h2 class="u-131">1. How to Request a Refund or Redelivery</h2>
    <p class="u-168">Requesting assistance is fast and simple:</p>
    <ol class="u-210">
      <li>Notify our customer service team within <strong>48 hours</strong> of receiving your delivery.</li>
      <li>Provide your Order ID (found on your confirmation screen or in <a href="<?= BASE_URL ?>/my_orders.php" class="u-040">My Orders</a>) and a brief description (or photo) of the item.</li>
      <li>Our team will process an immediate store credit, original payment method refund, or schedule an express redelivery on our next delivery run.</li>
    </ol>
    <h2 class="u-132">2. Incorrect or Missing Items</h2>
    <p class="u-168">
      In the rare event that an item is omitted by our packing depot or an incorrect SKU is provided, we will immediately credit your account or dispatch a replacement driver at no additional cost.
    </p>
    <h2 class="u-132">3. Australian Consumer Law Guarantee</h2>
    <p class="u-168">
      Our goods and services come with guarantees that cannot be excluded under the Australian Consumer Law. For major failures with the service, you are entitled to cancel your service contract with us and to a refund for the unused portion, or to compensation for its reduced value.
    </p>
    <h2 class="u-132">4. Need Assistance?</h2>
    <p class="u-168">Our customer happiness team is available 7 days a week:</p>
    <div class="u-193">
      <p class="u-158">Customer Support &amp; Claims Desk</p>
      <p class="u-155">Toll-Free Phone: <strong>1800 629 436</strong> (Mon–Sun 7am–9pm)</p>
      <p class="u-155">Email: <a href="mailto:support@maxifinefoods.com.au" class="u-040">support@maxifinefoods.com.au</a></p>
    </div>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
