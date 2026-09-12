  </main>

  <!-- Modern Professional Site Footer -->
  <footer class="site-footer">
    <!-- Value Propositions / Perks Bar -->
    <div class="footer-perks-bar">
      <div class="footer-perk-item">
        <div class="footer-perk-icon">
          <i data-lucide="truck" class="icon-md"></i>
        </div>
        <div>
          <h4 class="footer-perk-title">Free Same-Day Delivery</h4>
          <p class="footer-perk-desc">On all grocery orders over $60 placed by 2 PM</p>
        </div>
      </div>
      <div class="footer-perk-item">
        <div class="footer-perk-icon">
          <i data-lucide="leaf" class="icon-md"></i>
        </div>
        <div>
          <h4 class="footer-perk-title">100% Farm Fresh Guarantee</h4>
          <p class="footer-perk-desc">Direct from Australian regional family growers</p>
        </div>
      </div>
      <div class="footer-perk-item">
        <div class="footer-perk-icon">
          <i data-lucide="shield-check" class="icon-md"></i>
        </div>
        <div>
          <h4 class="footer-perk-title">Hygienic Temperature Control</h4>
          <p class="footer-perk-desc">Chilled vans ensure dairy &amp; seafood arrive pristine</p>
        </div>
      </div>
      <div class="footer-perk-item">
        <div class="footer-perk-icon">
          <i data-lucide="headphones" class="icon-md"></i>
        </div>
        <div>
          <h4 class="footer-perk-title">Dedicated Local Support</h4>
          <p class="footer-perk-desc">7 days customer care ready to assist you</p>
        </div>
      </div>
    </div>

    <!-- Newsletter Bar -->
    <div class="footer-newsletter-wrap">
      <div class="footer-newsletter-inner">
        <div>
          <span class="footer-badge">✨ Fresh Club Perks</span>
          <h3 class="footer-newsletter-title">Join the Maxi Fine Foods Club</h3>
          <p class="footer-newsletter-subtitle">Get <strong>$10 off</strong> your first order, seasonal farm recipes, and exclusive member-only weekly specials.</p>
        </div>
        <form class="footer-newsletter-form" onsubmit="event.preventDefault(); showToast('🎉 Thank you for subscribing! Your $10 voucher code is FRESH10'); this.reset();">
          <label class="sr-only" for="newsletter-email">Email address</label>
          <input id="newsletter-email" type="email" placeholder="Enter your email address" required class="footer-newsletter-input">
          <button type="submit" class="btn-tomato footer-newsletter-btn">
            <span>Subscribe</span>
            <i data-lucide="arrow-right" class="icon-sm"></i>
          </button>
        </form>
      </div>
    </div>

    <!-- Main 5-Column Navigation Grid -->
    <div class="site-footer__main">
      <!-- Column 1: Brand & Socials -->
      <div class="footer-col footer-col--brand">
        <a href="<?= BASE_URL ?>/index.php" class="wordmark wordmark--footer">Maxi Fine Foods</a>
        <p class="site-footer__blurb">Your premier local grocer bringing hand-selected farm produce, artisan bakeries, prime cuts, and sustainable dairy straight to your doorstep.</p>
        <div class="footer-social-links">
          <a href="#" aria-label="Facebook" class="social-icon-btn"><i data-lucide="facebook" class="icon-sm"></i></a>
          <a href="#" aria-label="Instagram" class="social-icon-btn"><i data-lucide="instagram" class="icon-sm"></i></a>
          <a href="#" aria-label="Twitter / X" class="social-icon-btn"><i data-lucide="twitter" class="icon-sm"></i></a>
          <a href="#" aria-label="YouTube" class="social-icon-btn"><i data-lucide="youtube" class="icon-sm"></i></a>
        </div>
        <div class="footer-cert-tags">
          <span class="cert-tag"><i data-lucide="check-circle-2" class="icon-xs"></i> 100% Australian Owned</span>
          <span class="cert-tag"><i data-lucide="check-circle-2" class="icon-xs"></i> Certified Organic Partner</span>
        </div>
      </div>

      <!-- Column 2: Departments -->
      <div class="footer-col">
        <h4 class="footer-heading">Fresh Departments</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🔥</span> Weekly Specials</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🍎</span> Farm Fresh Produce</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🥖</span> Artisan Bakery</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🥩</span> Meat &amp; Seafood</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🧀</span> Dairy &amp; Farm Eggs</a></li>
          <li><a href="<?= BASE_URL ?>/index.php"><span class="footer-link-dot">🧃</span> Cold Beverages &amp; Pantry</a></li>
        </ul>
      </div>

      <!-- Column 3: Customer Care & Services -->
      <div class="footer-col">
        <h4 class="footer-heading">Customer Care</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/my_orders.php"><i data-lucide="package" class="icon-xs"></i> Track My Orders</a></li>
          <li><a href="<?= BASE_URL ?>/cart.php"><i data-lucide="shopping-bag" class="icon-xs"></i> Shopping Basket</a></li>
          <li><a href="<?= BASE_URL ?>/login.php"><i data-lucide="user" class="icon-xs"></i> My Account</a></li>
          <li><a href="<?= BASE_URL ?>/register.php"><i data-lucide="user-plus" class="icon-xs"></i> Create Customer Account</a></li>
          <li><a href="#catalogue"><i data-lucide="help-circle" class="icon-xs"></i> FAQs &amp; Help Desk</a></li>
          <li><a href="#catalogue"><i data-lucide="refresh-cw" class="icon-xs"></i> Refund &amp; Returns Policy</a></li>
        </ul>
      </div>

      <!-- Column 4: Trading & Delivery Hours -->
      <div class="footer-col">
        <h4 class="footer-heading">Trading &amp; Delivery Hours</h4>
        <div class="footer-hours-card">
          <div class="hours-row">
            <span class="hours-day">Monday &ndash; Friday</span>
            <span class="hours-time">7:00 AM &ndash; 9:00 PM</span>
          </div>
          <div class="hours-row">
            <span class="hours-day">Saturday</span>
            <span class="hours-time">8:00 AM &ndash; 9:00 PM</span>
          </div>
          <div class="hours-row">
            <span class="hours-day">Sunday &amp; Holidays</span>
            <span class="hours-time">8:00 AM &ndash; 7:00 PM</span>
          </div>
          <div class="hours-cutoff-note">
            <i data-lucide="clock" class="icon-xs"></i>
            <span><strong>Same-Day Cutoff:</strong> Order by 2:00 PM</span>
          </div>
        </div>
      </div>

      <!-- Column 5: Contact & Depot -->
      <div class="footer-col">
        <h4 class="footer-heading">Contact &amp; Location</h4>
        <ul class="footer-contact-list">
          <li>
            <i data-lucide="map-pin" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Depot &amp; Fresh Market:</strong>
              <p>42 Market Street, Sydney NSW 2000, Australia</p>
            </div>
          </li>
          <li>
            <i data-lucide="phone" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Support Phone:</strong>
              <p><a href="tel:1800629436">1800 629 436</a> (Toll-Free)</p>
            </div>
          </li>
          <li>
            <i data-lucide="mail" class="icon-sm footer-contact-icon"></i>
            <div>
              <strong>Email Inquiries:</strong>
              <p><a href="mailto:support@maxifinefoods.com.au">support@maxifinefoods.com.au</a></p>
            </div>
          </li>
        </ul>
        <div class="footer-live-status">
          <span class="status-pulse-dot"></span>
          <span>Online Ordering is <strong>Live &amp; Accepting Orders</strong></span>
        </div>
      </div>
    </div>

    <!-- Bottom Legal & Payment Options -->
    <div class="site-footer__bottom">
      <div class="footer-legal-copy">
        <p>&copy; <?= date('Y') ?> Maxi Fine Foods Pty Ltd. ABN 84 192 847 291. All rights reserved.</p>
        <div class="footer-legal-links">
          <a href="#">Privacy Policy</a>
          <span class="divider-dot">&bull;</span>
          <a href="#">Terms of Service</a>
          <span class="divider-dot">&bull;</span>
          <a href="#">Security &amp; Encryption</a>
          <span class="divider-dot">&bull;</span>
          <a href="#">Delivery Terms</a>
        </div>
      </div>
      <div class="footer-payments-wrap">
        <span class="footer-payments-label">Accepted Payments:</span>
        <div class="payment-badges">
          <span class="pay-badge" title="Visa">💳 Visa</span>
          <span class="pay-badge" title="Mastercard">💳 Mastercard</span>
          <span class="pay-badge" title="American Express">💳 AMEX</span>
          <span class="pay-badge" title="PayPal">🅿️ PayPal</span>
          <span class="pay-badge" title="Apple Pay">🍎 Apple Pay</span>
          <span class="pay-badge" title="Cash on Delivery">💵 Cash on Delivery</span>
        </div>
      </div>
    </div>
  </footer>

  <div id="toast" class="toast" aria-live="polite"></div>
</div>

<script>window.MFF_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/main.js"></script>
</body>
</html>
