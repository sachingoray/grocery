  </main>

  <footer class="site-footer">
    <div class="site-footer__inner">
      <div>
        <span class="wordmark wordmark--footer">Maxi Fine Foods</span>
        <p class="site-footer__blurb">Your neighbourhood online grocery, bringing fresh produce and pantry essentials to your door.</p>
      </div>
      <div>
        <p style="font-weight:700;margin-bottom:.5rem;color:var(--cream);">Trading Hours</p>
        <ul style="font-size:.85rem;color:#8ba593;line-height:1.7;list-style:none;padding:0;">
          <li>Mon – Fri: 7:00 AM – 9:00 PM</li>
          <li>Saturday: 8:00 AM – 9:00 PM</li>
          <li>Sunday: 8:00 AM – 7:00 PM</li>
        </ul>
      </div>
    </div>

    <div style="border-top:1px solid rgba(255,255,255,.08);margin-top:1.5rem;padding-top:1.25rem;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;font-size:.8rem;color:#8ba593;">
      <span>&copy; <?= date('Y') ?> Maxi Fine Foods. All rights reserved.</span>
      <span>Organic &amp; Local Grocery Delivery</span>
    </div>
  </footer>

  <div id="toast" class="toast" aria-live="polite"></div>
</div>

<script>window.MFF_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/main.js"></script>
</body>
</html>
