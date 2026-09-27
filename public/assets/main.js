// main.js â€” Maxi Fine Foods front end helpers.
// Talks to cart.php over fetch() so quantity changes don't need a full reload.

// BASE_URL is published by PHP on <body data-base-url="..."> (see
// includes/header.php). Reading it from the DOM keeps the bootstrap free of
// inline <script> blocks, which the Content-Security-Policy forbids.
window.MFF_BASE_URL = (document.body && document.body.dataset.baseUrl) || '';

function showToast(message) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => toast.classList.remove('show'), 2400);
}

/** Append the ajax flag so cart.php always answers with JSON, even if a proxy strips the XHR header. */
function withAjaxFlag(url) {
  return url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
}

/**
 * Parse a JSON response body, returning null when the reply is not JSON.
 *
 * Reads the body as text and strips any leading UTF-8 BOM(s) before parsing:
 * a BOM (or two, if both a script and its include emit one) survives the
 * browser's single-BOM strip inside res.json(), makes JSON.parse throw, and
 * would otherwise send the caller down the reload/redirect fallback.
 */
async function readJson(res) {
  const contentType = (res.headers.get('content-type') || '').toLowerCase();
  if (!contentType.includes('application/json')) return null;
  try {
    const text = await res.text();
    return JSON.parse(text.replace(/^\uFEFF+/, ''));
  } catch (err) {
    return null;
  }
}

/**
 * Push a cart.php JSON payload into every element that displays cart state.
 *
 * The nav badge shows the item count AND the running total, so a shopper can
 * see the money without opening the cart. cart.php already returns
 * total_formatted, so nothing new is requested over the wire.
 *
 * One helper serves both call sites (add-to-cart and quantity stepper) so the
 * two can never drift apart.
 */
function syncCartUI(data) {
  const flashEl = (el, val) => {
    el.textContent = val;
    el.classList.remove('highlight-flash');
    void el.offsetWidth; // force reflow so the flash animation replays
    el.classList.add('highlight-flash');
  };

  document.querySelectorAll('[data-cart-count]')
    .forEach(el => flashEl(el, data.cart_count));
  document.querySelectorAll('[data-cart-subtotal]')
    .forEach(el => flashEl(el, data.subtotal_formatted));
  document.querySelectorAll('[data-cart-tax]')
    .forEach(el => flashEl(el, data.tax_formatted));

  // The nav badge total is hidden while the cart is empty, so a first-time
  // visitor never sees a bare "$0.00". cart.php does not send that state on
  // its own, so derive it from the item count and reveal the badge as soon as
  // something is in the cart (and hide it again if the last item is removed).
  // Only the nav badge carries .cart-total, so the cart page's own Total row
  // is never touched here.
  const navEmpty = Number(data.cart_count) === 0;
  document.querySelectorAll('.cart-total').forEach(badge => {
    badge.hidden = navEmpty;
    const value = badge.querySelector('[data-cart-total]');
    if (value) flashEl(value, data.total_formatted);
  });

  // The cart page summary has no .cart-total wrapper; update it as before.
  document.querySelectorAll('[data-cart-total]').forEach(el => {
    if (!el.closest('.cart-total')) flashEl(el, data.total_formatted);
  });
}

/**
 * POST cart data, retrying once if the request dies at the transport layer.
 * A browser never auto-retries a POST (it is not idempotent), so a pooled
 * keep-alive socket that the server has already closed â€” the usual reason for
 * a bogus "Failed to fetch" on shared hosting â€” would otherwise swallow the
 * click and surface as a fake "check your connection" error.
 */
async function postCartForm(url, formData) {
  const options = {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  };
  try {
    return await fetch(url, options);
  } catch (err) {
    await new Promise(resolve => setTimeout(resolve, 200));
    return fetch(url, options); // second failure is handled by the caller
  }
}

/** Wire up a .qty-stepper element to POST cart updates and refresh totals. */
function initQtySteppers() {
  document.querySelectorAll('.qty-stepper').forEach(stepper => {
    const productId = stepper.dataset.productId;
    const display = stepper.querySelector('[data-qty-display]');
    const minus = stepper.querySelector('.btn-step-minus');
    const plus = stepper.querySelector('.btn-step-plus');
    if (!productId || !display) return;

    const updateQty = async (newQty) => {
      try {
        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('product_id', productId);
        formData.append('quantity', newQty);

        const res = await postCartForm(withAjaxFlag((window.MFF_BASE_URL || '') + '/cart.php'), formData);
        const data = await readJson(res);
        if (data === null) {
          // Server answered with a page instead of JSON â€” resync so the UI matches the real cart.
          if (res.ok || res.redirected) {
            window.location.reload();
          } else {
            showToast('Could not update cart. Please refresh and try again.');
          }
          return;
        }
        if (data.ok) {
          display.textContent = data.quantity;
          display.classList.remove('qty-pop');
          void display.offsetWidth; // trigger reflow
          display.classList.add('qty-pop');

          // Update cart counts and totals across the page (nav badge included)
          syncCartUI(data);
          
          if (data.quantity === 0) {
            const row = stepper.closest('[data-cart-row]');
            if (row) {
              row.style.transition = 'opacity 0.3s, height 0.3s, padding 0.3s, margin 0.3s';
              row.style.opacity = '0';
              row.style.overflow = 'hidden';
              row.style.height = row.offsetHeight + 'px';
              requestAnimationFrame(() => {
                row.style.height = '0';
                row.style.paddingTop = '0';
                row.style.paddingBottom = '0';
                row.style.marginTop = '0';
                row.style.marginBottom = '0';
                row.style.border = 'none';
              });
              setTimeout(() => row.remove(), 300);
            }
            const controls = stepper.closest('.card-cart-controls');
            if (controls) {
              stepper.classList.add('is-hidden');
              controls.querySelector('.add-to-cart-form').classList.remove('is-hidden');
            }
          }
        } else {
          showToast(data.error || 'Could not update cart.');
        }
      } catch (err) {
        showToast('Could not update cart â€” please check your connection.');
      }
    };

    minus?.addEventListener('click', () => {
      const current = parseInt(display.textContent, 10) || 0;
      updateQty(Math.max(0, current - 1));
    });
    plus?.addEventListener('click', () => {
      const current = parseInt(display.textContent, 10) || 0;
      updateQty(current + 1);
    });
  });
}

/** Catalogue search + category filter (index.php). */
function initCatalogueFilters() {
  const search = document.getElementById('product-search');
  const filterButtons = document.querySelectorAll('.filter-button');
  const cards = document.querySelectorAll('.product-card');
  const noResults = document.getElementById('no-products');
  if (!search && filterButtons.length === 0) return;

  // Every search box on the page: the catalogue's own field plus the sticky
  // header copy (index.php renders it, initStickyNavSearch reveals it).
  const searchInputs = Array.from(document.querySelectorAll('[data-product-search]'));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Intersection Observer for scroll-based entrance
  const observer = new IntersectionObserver((entries) => {
    let delay = 0;
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('is-entered'), delay * 75);
        delay++;
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  cards.forEach(card => observer.observe(card));

  const apply = () => {
    const query = (search?.value || searchInputs[0]?.value || '').toLowerCase().trim();
    const activeCategory = document.querySelector('.filter-button--active')?.dataset.category || 'specials';
    let visible = 0;
    cards.forEach(card => {
      const isSpecial = card.dataset.special === '1';
      let matchesCategory = false;
      if (activeCategory === 'specials') {
        matchesCategory = isSpecial;
      } else {
        matchesCategory = card.dataset.category === activeCategory;
      }
      const matchesSearch = (card.dataset.search || '').includes(query);
      const show = matchesCategory && matchesSearch;
      
      if (show) {
        if (card.classList.contains('is-hidden') || card.classList.contains('is-hiding')) {
          card.style.display = '';
          card.classList.remove('is-hidden');
          card.classList.add('is-hiding'); // Start in hiding state
          requestAnimationFrame(() => requestAnimationFrame(() => card.classList.remove('is-hiding')));
        }
        visible++;
      } else {
        if (!card.classList.contains('is-hidden')) {
          card.classList.add('is-hiding');
          setTimeout(() => {
            if (card.classList.contains('is-hiding')) {
              card.classList.add('is-hidden');
              card.style.display = 'none';
            }
          }, 250);
        }
      }
    });
    noResults?.classList.toggle('hidden', visible > 0);
  };

  // Typing in either box mirrors the value into the others, so the query
  // survives scrolling between them and apply() always reads the same text no
  // matter which box the shopper used.
  searchInputs.forEach((input) => {
    input.addEventListener('input', () => {
      searchInputs.forEach((other) => {
        if (other !== input) other.value = input.value;
      });
      apply();
    });
  });
  filterButtons.forEach(btn => btn.addEventListener('click', () => {
    filterButtons.forEach(b => b.classList.remove('filter-button--active'));
    btn.classList.add('filter-button--active');
    apply();
  }));

  searchInputs.forEach((input) => {
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        apply();
        document.getElementById('catalogue')?.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
      }
    });
  });

  // Run on page load
  apply();
  initStickyNavSearch(search);
}

/**
 * Reveal the header's copy of the search bar once the catalogue's own search
 * bar has scrolled up behind the sticky header, and hide it again on the way
 * back. The test compares against the header's *live* bottom edge: showing the
 * box only ever pushes that edge further down, which keeps the condition true,
 * and hiding it pulls the edge back up, which keeps it false - so the state is
 * self-stabilising and cannot flicker at the boundary.
 */
function initStickyNavSearch(catalogueSearch) {
  const navSearch = document.querySelector('[data-nav-search]');
  const header = document.querySelector('.site-header');
  if (!navSearch || !header || !catalogueSearch) return;

  // Measure the whole label, not just the input, so the trigger point matches
  // what the shopper actually sees.
  const anchor = catalogueSearch.closest('.search-field') || catalogueSearch;
  let ticking = false;

  const update = () => {
    ticking = false;
    const past = anchor.getBoundingClientRect().bottom <= header.getBoundingClientRect().bottom;
    navSearch.classList.toggle('is-visible', past);
  };

  const requestUpdate = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  };

  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate);
  // Covers scroll restoration and late image layout shifts after first paint.
  window.addEventListener('load', requestUpdate);
  update();
}

/** Wire up "Add to cart" buttons to use AJAX instead of reloading */
function initAddToCart() {
  document.querySelectorAll('form[action$="/cart.php"]').forEach(form => {
    const actionInput = form.querySelector('input[name="action"]');
    if (!actionInput || actionInput.value !== 'add') return;
    // form.action is shadowed by the hidden <input name="action"> control (named
    // form properties lose to same-named inputs), so read the attribute.
    const action = form.getAttribute('action') || form.action;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      if (!btn) return;
      // Save innerHTML (not textContent): the button now contains a lucide
      // icon, and a textContent round-trip would drop it after the first click.
      const originalHTML = btn.innerHTML;
      btn.textContent = 'Adding...';
      btn.disabled = true;

      try {
        const res = await postCartForm(withAjaxFlag(action), new FormData(form));
        const data = await readJson(res);
        if (data === null) {
          // Server answered with a full page instead of JSON (stripped XHR header,
          // WAF block, PHP notice noise, ...). If the request reached cart.php the
          // item is already saved, so sync the UI; otherwise fall back to a normal
          // form POST so the click is never silently lost.
          if (res.ok || res.redirected) {
            window.location.reload();
          } else {
            form.submit();
          }
          return;
        }
        
        if (data.ok) {
          showToast('Added to cart!');
          const controls = form.closest('.card-cart-controls');
          if (controls) {
            const stepper = controls.querySelector('.qty-stepper');
            const qtyDisplay = stepper ? stepper.querySelector('[data-qty-display]') : null;
            if (stepper && qtyDisplay) {
              form.classList.add('is-hidden');
              stepper.classList.remove('is-hidden');
              qtyDisplay.textContent = data.quantity || 1;
            }
          }
          // Update cart counts and totals across the page (nav badge included)
          syncCartUI(data);
        } else {
          showToast(data.error || 'Failed to add to cart.');
        }
      } catch (err) {
        console.error('Add to cart error:', err);
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        // The POST failed twice at the transport layer (socket closed, request
        // blocked, ...). Never leave the click dead: re-submit the form the
        // normal way so the item still reaches cart.php, which is the no-JS
        // path â€” the server saves the item and redirects back to this page
        // with the badge already updated.
        if (navigator.onLine !== false) {
          showToast('Connection hiccup â€” saving your itemâ€¦');
          form.submit();
          return;
        }
        showToast('You appear to be offline â€” reconnect and try again.');
      } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
      }
    });
  });
}

/**
 * Quick-view details popup for catalogue cards (index.php).
 *
 * The first icon in the card's action row opens this shared modal; the fields
 * come from the data-* attributes on the clicked [data-quickview] button, so no
 * extra request is needed. Closes via the X, backdrop click, or Escape, and
 * returns focus to the icon that opened it.
 */
function initQuickView() {
  const overlay = document.getElementById('quickview-overlay');
  if (!overlay) return;

  const closeBtn = document.getElementById('quickview-close');
  const img = document.getElementById('quickview-img');
  const badge = document.getElementById('quickview-badge');
  const category = document.getElementById('quickview-category');
  const title = document.getElementById('quickview-title');
  const price = document.getElementById('quickview-price');
  const wasPrice = document.getElementById('quickview-was-price');
  const stock = document.getElementById('quickview-stock');
  const desc = document.getElementById('quickview-desc');
  const link = document.getElementById('quickview-link');
  let lastTrigger = null;

  const close = () => {
    overlay.hidden = true;
    document.body.style.overflow = '';
    if (lastTrigger) {
      lastTrigger.focus();
      lastTrigger = null;
    }
  };

  const open = (btn) => {
    const d = btn.dataset;

    img.src = d.image || img.dataset.fallback;
    img.alt = d.name || '';
    img.onerror = () => {
      img.src = img.dataset.fallback;
      img.onerror = null;
    };

    if (d.badge) {
      badge.textContent = d.badge;
      badge.classList.toggle('product-card__badge--sale', d.special === '1');
      badge.classList.remove('is-hidden');
    } else {
      badge.classList.add('is-hidden');
    }

    category.textContent = d.category || '';
    title.textContent = d.name || '';
    price.textContent = d.price || '';
    price.classList.toggle('product-card__price--sale', d.special === '1');

    if (d.wasPrice) {
      wasPrice.innerHTML = 'Was <del>' + d.wasPrice + '</del>';
      wasPrice.classList.remove('is-hidden');
    } else {
      wasPrice.classList.add('is-hidden');
    }

    stock.textContent = d.stockLow === '1'
      ? 'Low stock \u2014 ' + (d.stockCount || '0') + ' left'
      : 'In stock';
    stock.className = 'status ' + (d.stockLow === '1' ? 'status-low' : 'status-instock');

    desc.textContent = d.description || 'No description has been added for this item yet.';
    link.href = d.url || '#';

    lastTrigger = btn;
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  };

  document.querySelectorAll('[data-quickview]').forEach(btn => {
    btn.addEventListener('click', () => open(btn));
  });

  closeBtn?.addEventListener('click', close);
  // Close only when the backdrop itself (not the panel) is clicked.
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !overlay.hidden) close();
  });
}

/** Swap in a neutral placeholder if a product photo fails to load. */
function initImageFallbacks() {
  document.querySelectorAll('img[data-fallback]').forEach(img => {
    img.addEventListener('error', () => {
      img.src = img.dataset.fallback;
    }, { once: true });
  });
}

/** Auto-rotate the hero banner images with a crossfade, plus manual prev/next controls. */
function initHeroSlideshow() {
  const slideshow = document.querySelector('[data-hero-slideshow]');
  if (!slideshow) return;
  const slides = slideshow.querySelectorAll('img');
  const prevBtn = slideshow.querySelector('[data-hero-prev]');
  const nextBtn = slideshow.querySelector('[data-hero-next]');
  if (slides.length < 2) return;

  let current = 0;
  let timer = null;

  const dotsContainer = document.createElement('div');
  dotsContainer.className = 'hero-slideshow__dots';
  slides.forEach((_, i) => {
    const dot = document.createElement('div');
    dot.className = 'hero-slideshow__dot' + (i === 0 ? ' is-active' : '');
    dot.addEventListener('click', () => { goTo(i); startTimer(); });
    dotsContainer.appendChild(dot);
  });
  slideshow.appendChild(dotsContainer);

  const goTo = (index) => {
    slides[current].classList.remove('is-active');
    dotsContainer.children[current].classList.remove('is-active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('is-active');
    dotsContainer.children[current].classList.add('is-active');
  };

  const startTimer = () => {
    clearInterval(timer);
    // Auto-rotation disabled per user request
    // timer = setInterval(() => goTo(current + 1), 5000);
  };

  prevBtn?.addEventListener('click', () => { goTo(current - 1); startTimer(); });
  nextBtn?.addEventListener('click', () => { goTo(current + 1); startTimer(); });

  startTimer();
}

/**
 * Shared behaviours that used to be inline event handlers (onclick/onsubmit/
 * oninput). Delegated from document so pages need no inline JavaScript, which
 * is what lets the CSP run without 'unsafe-inline'.
 */
function initGlobalHandlers() {
  // <form data-confirm="..."> â€” ask before submitting (delete/remove actions).
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) e.preventDefault();
  });

  // <input data-input-filter="phone"> â€” keep contact numbers to digits.
  const phoneFilter = (input) => input.value.replace(/[^0-9\+\s\-()]/g, '');
  document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-input-filter="phone"]');
    if (!input) return;
    const cleaned = phoneFilter(input);
    if (cleaned !== input.value) input.value = cleaned;
  });
  document.addEventListener('keypress', (e) => {
    const input = e.target.closest('[data-input-filter="phone"]');
    if (input && !/[0-9\+\s\-\(\)]/.test(e.key)) e.preventDefault();
  });

  // <form data-newsletter-form> â€” front-end only sign-up confirmation.
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-newsletter-form]');
    if (!form) return;
    e.preventDefault();
    showToast('ðŸŽ‰ Welcome! Your $10 voucher code is FRESH10');
    form.reset();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
  initGlobalHandlers();
  initQtySteppers();
  initCatalogueFilters();
  initAddToCart();
  initQuickView();
  initImageFallbacks();
  initHeroSlideshow();

  // Auto-dismiss server-rendered flash messages after a few seconds.
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(() => flash.style.display = 'none', 4000);
  }
});
