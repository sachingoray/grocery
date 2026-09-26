// main.js — Maxi Fine Foods front end helpers.
// Talks to cart.php over fetch() so quantity changes don't need a full reload.

function showToast(message) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => toast.classList.remove('show'), 2400);
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

        const res = await fetch((window.MFF_BASE_URL || '') + '/cart.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await res.json();
        if (data.ok) {
          display.textContent = data.quantity;
          display.classList.remove('qty-pop');
          void display.offsetWidth; // trigger reflow
          display.classList.add('qty-pop');

          const flashEl = (el, val) => {
            el.textContent = val;
            el.classList.remove('highlight-flash');
            void el.offsetWidth;
            el.classList.add('highlight-flash');
          };

          document.querySelectorAll('[data-cart-count]').forEach(el => flashEl(el, data.cart_count));
          document.querySelectorAll('[data-cart-subtotal]').forEach(el => flashEl(el, data.subtotal_formatted));
          document.querySelectorAll('[data-cart-tax]').forEach(el => flashEl(el, data.tax_formatted));
          document.querySelectorAll('[data-cart-total]').forEach(el => flashEl(el, data.total_formatted));
          
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
        showToast('Network error updating cart.');
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
    const query = (search?.value || '').toLowerCase().trim();
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

  search?.addEventListener('input', apply);
  filterButtons.forEach(btn => btn.addEventListener('click', () => {
    filterButtons.forEach(b => b.classList.remove('filter-button--active'));
    btn.classList.add('filter-button--active');
    apply();
  }));

  // Run on page load
  apply();
}

/** Wire up "Add to cart" buttons to use AJAX instead of reloading */
function initAddToCart() {
  document.querySelectorAll('form[action$="/cart.php"]').forEach(form => {
    const actionInput = form.querySelector('input[name="action"]');
    if (!actionInput || actionInput.value !== 'add') return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      if (!btn) return;
      const originalText = btn.textContent;
      btn.textContent = 'Adding...';
      btn.disabled = true;

      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: new FormData(form)
        });
        const data = await res.json();
        
        if (data.ok) {
          showToast('Added to cart!');
          const controls = form.closest('.card-cart-controls');
          if (controls) {
            form.classList.add('is-hidden');
            const stepper = controls.querySelector('.qty-stepper');
            stepper.classList.remove('is-hidden');
            stepper.querySelector('[data-qty-display]').textContent = data.quantity || 1;
          }
          // Update cart counts and animate
          const flashEl = (el, val) => {
            el.textContent = val;
            el.classList.remove('highlight-flash');
            void el.offsetWidth;
            el.classList.add('highlight-flash');
          };
          document.querySelectorAll('[data-cart-count]').forEach(el => flashEl(el, data.cart_count));
          document.querySelectorAll('[data-cart-subtotal]').forEach(el => flashEl(el, data.subtotal_formatted));
          document.querySelectorAll('[data-cart-tax]').forEach(el => flashEl(el, data.tax_formatted));
          document.querySelectorAll('[data-cart-total]').forEach(el => flashEl(el, data.total_formatted));
        } else {
          showToast(data.error || 'Failed to add to cart.');
        }
      } catch (err) {
        console.error("Add to cart error:", err);
        showToast('Network error.');
      } finally {
        btn.textContent = originalText;
        btn.disabled = false;
      }
    });
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

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
  initQtySteppers();
  initCatalogueFilters();
  initAddToCart();
  initImageFallbacks();
  initHeroSlideshow();

  // Auto-dismiss server-rendered flash messages after a few seconds.

  // Auto-dismiss server-rendered flash messages after a few seconds.
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(() => flash.style.display = 'none', 4000);
  }
});
