<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Privacy Policy';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Legal &amp; Trust</span>
    <h1 class="u-093">Privacy Policy</h1>
    <p class="u-170">Last updated: September 12, 2026 &middot; Compliant with the Australian Privacy Principles (APPs)</p>
  </div>
  <div class="card-artisan u-203">
    <h2 class="u-131">1. Commitment to Your Privacy</h2>
    <p class="u-168">
      Maxi Fine Foods Pty Ltd (ABN 84 192 847 291) is committed to protecting your personal information and respecting your privacy in accordance with the <em>Privacy Act 1988 (Cth)</em> and the Australian Privacy Principles (APPs). This policy details how we collect, store, utilize, and protect personal details when you access our grocery market platform and delivery services.
    </p>
    <h2 class="u-132">2. Information We Collect</h2>
    <p class="u-168">To fulfill grocery orders and deliver fresh produce to your doorstep, we collect the following information:</p>
    <ul class="u-210">
      <li><strong>Account Details:</strong> Full name, email address, contact phone number, and encrypted password credentials.</li>
      <li><strong>Delivery Information:</strong> Physical delivery address, unit/gate codes, and specific driver delivery instructions.</li>
      <li><strong>Order History &amp; Preferences:</strong> Items purchased, order quantities, delivery timestamps, and dietary preferences.</li>
      <li><strong>Payment Details:</strong> Transaction IDs and payment method selections (Note: Credit card numbers are tokenized and processed securely via PCI-DSS certified payment gateways; we never store raw card numbers on our servers).</li>
    </ul>
    <h2 class="u-132">3. How We Use Your Information</h2>
    <p class="u-168">Your personal information is used strictly for operational purposes:</p>
    <ul class="u-210">
      <li>Processing, packing, and dispatching fresh grocery orders.</li>
      <li>Facilitating delivery driver routing and real-time SMS/email status notifications.</li>
      <li>Providing responsive 7-day customer service and order tracking.</li>
      <li>Sending opt-in weekly specials, discount vouchers, and seasonal market recipes (you can opt out anytime).</li>
      <li>Preventing fraudulent transactions and maintaining account security.</li>
    </ul>
    <h2 class="u-132">4. Information Sharing &amp; Third Parties</h2>
    <p class="u-168">
      We do not sell, rent, or trade your personal data to advertisers. We share information only with trusted service partners necessary to complete your order:
    </p>
    <ul class="u-210">
      <li><strong>Delivery Drivers:</strong> Assigned drivers receive your delivery address, recipient name, and drop-off instructions to complete delivery.</li>
      <li><strong>Payment Processors:</strong> Encrypted financial communication for transaction clearance.</li>
      <li><strong>Cloud Infrastructure:</strong> Secure Australian-based servers complying with strict data security standards.</li>
    </ul>
    <h2 class="u-132">5. Data Security &amp; Retention</h2>
    <p class="u-168">
      We employ bank-grade 256-bit SSL encryption, firewalls, and strict database access controls. Your account password is protected using salted one-way hashing algorithms (Bcrypt).
    </p>
    <h2 class="u-132">6. Contact Our Privacy Officer</h2>
    <p class="u-168">
      If you have questions regarding your data or wish to request access or deletion of your records, please contact our privacy desk:
    </p>
    <div class="u-193">
      <p class="u-158">Maxi Fine Foods Privacy Office</p>
      <p class="u-155">42 Market Street, Sydney NSW 2000, Australia</p>
      <p class="u-155">Email: <a href="mailto:privacy@maxifinefoods.com.au" class="u-040">privacy@maxifinefoods.com.au</a> | Phone: 1800 629 436</p>
    </div>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
