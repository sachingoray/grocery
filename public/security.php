<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Security & Encryption';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Platform Protection</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Security &amp; Data Protection</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">Bank-grade 256-bit SSL encryption, PCI-DSS compliance, and zero stored card data</p>
  </div>

  <div class="card-artisan" style="padding: 2.5rem; line-height: 1.7; color: #2c4234; background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--line);">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:2rem;">
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;text-align:center;">
        <i data-lucide="lock" style="color:var(--leaf);width:32px;height:32px;margin:0 auto .5rem;display:block;"></i>
        <strong style="color:var(--ink);font-size:1rem;display:block;">256-Bit SSL Encryption</strong>
        <span style="font-size:.8rem;color:#56715f;">End-to-end data encryption</span>
      </div>
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;text-align:center;">
        <i data-lucide="credit-card" style="color:var(--gold);width:32px;height:32px;margin:0 auto .5rem;display:block;"></i>
        <strong style="color:var(--ink);font-size:1rem;display:block;">PCI-DSS Level 1</strong>
        <span style="font-size:.8rem;color:#56715f;">Encrypted tokenized gateways</span>
      </div>
      <div style="background:#f4efe6;border:1px solid #dfd4bf;padding:1.25rem;border-radius:1rem;text-align:center;">
        <i data-lucide="shield-alert" style="color:#0284c7;width:32px;height:32px;margin:0 auto .5rem;display:block;"></i>
        <strong style="color:var(--ink);font-size:1rem;display:block;">Strict Role Auth</strong>
        <span style="font-size:.8rem;color:#56715f;">Isolated driver &amp; admin sessions</span>
      </div>
    </div>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:0;">1. Encrypted Communications</h2>
    <p style="margin-top:.5rem;">
      All traffic to and from the Maxi Fine Foods platform is encrypted using modern Transport Layer Security (TLS 1.3 / 256-bit SSL). This safeguards your login credentials, personal addresses, and transaction details from eavesdropping or interception.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">2. Payment Gateway &amp; Zero Storage Policy</h2>
    <p style="margin-top:.5rem;">
      We strictly adhere to the Payment Card Industry Data Security Standard (PCI-DSS). We do not store, view, or retain your raw 16-digit credit card number or CVV codes on our servers. All transactions are tokenized and processed via certified tier-1 banking infrastructure.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">3. Password Hashing &amp; Account Protection</h2>
    <p style="margin-top:.5rem;">
      User passwords are protected with secure cryptographic hashing functions with random unique salts. Even our database administrators cannot view plaintext passwords.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">4. Role-Based Access Control (RBAC)</h2>
    <p style="margin-top:.5rem;">
      Our platform isolates customer, delivery driver, and administrative access into separate role-gated zones to ensure that drivers only see active fulfillment instructions and administrative controls remain strictly restricted.
    </p>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
