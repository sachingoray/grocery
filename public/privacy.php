<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$pageTitle = 'Privacy Policy';
$activeNav = '';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div class="content-page-wrap" style="max-width: 54rem; margin: 2rem auto; padding: 0 1rem;">
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:.35rem;font-size:.825rem;font-weight:700;color:var(--leaf);margin-bottom:.75rem;">
      <i data-lucide="arrow-left" class="icon-sm"></i> Back to Store
    </a>
    <span class="badge badge-subtle" style="display:inline-block;background:rgba(47,96,65,.1);color:var(--leaf);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-bottom:.5rem;">Legal &amp; Trust</span>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:700;color:var(--ink);margin:0;">Privacy Policy</h1>
    <p style="margin-top:.5rem;color:#56715f;font-size:.9rem;">Last updated: September 12, 2026 &middot; Compliant with the Australian Privacy Principles (APPs)</p>
  </div>

  <div class="card-artisan" style="padding: 2.5rem; line-height: 1.7; color: #2c4234; background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--line);">
    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:0;">1. Commitment to Your Privacy</h2>
    <p style="margin-top:.5rem;">
      Maxi Fine Foods Pty Ltd (ABN 84 192 847 291) is committed to protecting your personal information and respecting your privacy in accordance with the <em>Privacy Act 1988 (Cth)</em> and the Australian Privacy Principles (APPs). This policy details how we collect, store, utilize, and protect personal details when you access our grocery market platform and delivery services.
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">2. Information We Collect</h2>
    <p style="margin-top:.5rem;">To fulfill grocery orders and deliver fresh produce to your doorstep, we collect the following information:</p>
    <ul style="padding-left:1.5rem;margin-top:.5rem;">
      <li><strong>Account Details:</strong> Full name, email address, contact phone number, and encrypted password credentials.</li>
      <li><strong>Delivery Information:</strong> Physical delivery address, unit/gate codes, and specific driver delivery instructions.</li>
      <li><strong>Order History &amp; Preferences:</strong> Items purchased, order quantities, delivery timestamps, and dietary preferences.</li>
      <li><strong>Payment Details:</strong> Transaction IDs and payment method selections (Note: Credit card numbers are tokenized and processed securely via PCI-DSS certified payment gateways; we never store raw card numbers on our servers).</li>
    </ul>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">3. How We Use Your Information</h2>
    <p style="margin-top:.5rem;">Your personal information is used strictly for operational purposes:</p>
    <ul style="padding-left:1.5rem;margin-top:.5rem;">
      <li>Processing, packing, and dispatching fresh grocery orders.</li>
      <li>Facilitating delivery driver routing and real-time SMS/email status notifications.</li>
      <li>Providing responsive 7-day customer service and order tracking.</li>
      <li>Sending opt-in weekly specials, discount vouchers, and seasonal market recipes (you can opt out anytime).</li>
      <li>Preventing fraudulent transactions and maintaining account security.</li>
    </ul>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">4. Information Sharing &amp; Third Parties</h2>
    <p style="margin-top:.5rem;">
      We do not sell, rent, or trade your personal data to advertisers. We share information only with trusted service partners necessary to complete your order:
    </p>
    <ul style="padding-left:1.5rem;margin-top:.5rem;">
      <li><strong>Delivery Drivers:</strong> Assigned drivers receive your delivery address, recipient name, and drop-off instructions to complete delivery.</li>
      <li><strong>Payment Processors:</strong> Encrypted financial communication for transaction clearance.</li>
      <li><strong>Cloud Infrastructure:</strong> Secure Australian-based servers complying with strict data security standards.</li>
    </ul>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">5. Data Security &amp; Retention</h2>
    <p style="margin-top:.5rem;">
      We employ bank-grade 256-bit SSL encryption, firewalls, and strict database access controls. Your account password is protected using salted one-way hashing algorithms (Bcrypt).
    </p>

    <h2 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin-top:2rem;">6. Contact Our Privacy Officer</h2>
    <p style="margin-top:.5rem;">
      If you have questions regarding your data or wish to request access or deletion of your records, please contact our privacy desk:
    </p>
    <div style="margin-top:1rem;background:#f6f3eb;padding:1.25rem;border-radius:1rem;border:1px solid var(--line);">
      <p style="margin:0;font-weight:700;color:var(--ink);">Maxi Fine Foods Privacy Office</p>
      <p style="margin:.25rem 0 0;font-size:.875rem;color:#56715f;">42 Market Street, Sydney NSW 2000, Australia</p>
      <p style="margin:.25rem 0 0;font-size:.875rem;color:#56715f;">Email: <a href="mailto:privacy@maxifinefoods.com.au" style="color:var(--leaf);font-weight:700;">privacy@maxifinefoods.com.au</a> | Phone: 1800 629 436</p>
    </div>
  </div>
</div>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
