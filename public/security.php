<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
$pageTitle = 'Security & Encryption';
$activeNav = '';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div class="content-page-wrap u-199">
  <div class="u-161">
    <a href="<?= BASE_URL ?>/index.php" class="u-089">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle u-086">Platform Protection</span>
    <h1 class="u-093">Security &amp; Data Protection</h1>
    <p class="u-170">Bank-grade 256-bit SSL encryption, PCI-DSS compliance, and zero stored card data</p>
  </div>
  <div class="card-artisan u-203">
    <div class="u-082">
      <div class="u-015">
        <i data-lucide="lock" class="u-043"></i>
        <strong class="u-039">256-Bit SSL Encryption</strong>
        <span class="u-114">End-to-end data encryption</span>
      </div>
      <div class="u-015">
        <i data-lucide="credit-card" class="u-038"></i>
        <strong class="u-039">PCI-DSS Level 1</strong>
        <span class="u-114">Encrypted tokenized gateways</span>
      </div>
      <div class="u-015">
        <i data-lucide="shield-alert" class="u-030"></i>
        <strong class="u-039">Strict Role Auth</strong>
        <span class="u-114">Isolated driver &amp; admin sessions</span>
      </div>
    </div>
    <h2 class="u-131">1. Encrypted Communications</h2>
    <p class="u-168">
      All traffic to and from the Maxi Fine Foods platform is encrypted using modern Transport Layer Security (TLS 1.3 / 256-bit SSL). This safeguards your login credentials, personal addresses, and transaction details from eavesdropping or interception.
    </p>
    <h2 class="u-132">2. Payment Gateway &amp; Zero Storage Policy</h2>
    <p class="u-168">
      We strictly adhere to the Payment Card Industry Data Security Standard (PCI-DSS). We do not store, view, or retain your raw 16-digit credit card number or CVV codes on our servers. All transactions are tokenized and processed via certified tier-1 banking infrastructure.
    </p>
    <h2 class="u-132">3. Password Hashing &amp; Account Protection</h2>
    <p class="u-168">
      User passwords are protected with secure cryptographic hashing functions with random unique salts. Even our database administrators cannot view plaintext passwords.
    </p>
    <h2 class="u-132">4. Role-Based Access Control (RBAC)</h2>
    <p class="u-168">
      Our platform isolates customer, delivery driver, and administrative access into separate role-gated zones to ensure that drivers only see active fulfillment instructions and administrative controls remain strictly restricted.
    </p>
  </div>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
