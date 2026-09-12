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
        const res = await fetch((window.MFF_BASE_URL || '') + '/cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams({ action: 'update_qty', product_id: productId, quantity: newQty }),
        });
        const data = await res.json();
        if (data.ok) {
          display.textContent = data.quantity;
          document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = data.cart_count);
          document.querySelectorAll('[data-cart-subtotal]').forEach(el => el.textContent = data.subtotal_formatted);
          document.querySelectorAll('[data-cart-tax]').forEach(el => el.textContent = data.tax_formatted);
          document.querySelectorAll('[data-cart-total]').forEach(el => el.textContent = data.total_formatted);
          if (data.quantity === 0) {
            const row = stepper.closest('[data-cart-row]');
            if (row) row.remove();
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
      card.classList.toggle('is-hidden', !show);
      if (show) visible++;
    });
    noResults?.classList.toggle('hidden', visible > 0);
  };

  search?.addEventListener('input', apply);
  filterButtons.forEach(btn => btn.addEventListener('click', () => {
    filterButtons.forEach(b => b.classList.remove('filter-button--active'));
    btn.classList.add('filter-button--active');
    apply();
  }));

  // Run on page load with the default active filter (Weekly Specials)
  apply();
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

  const goTo = (index) => {
    slides[current].classList.remove('is-active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('is-active');
  };

  const startTimer = () => {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), 4000);
  };

  prevBtn?.addEventListener('click', () => { goTo(current - 1); startTimer(); });
  nextBtn?.addEventListener('click', () => { goTo(current + 1); startTimer(); });

  startTimer();
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
  initQtySteppers();
  initCatalogueFilters();
  initImageFallbacks();
  initHeroSlideshow();

  // Auto-dismiss server-rendered flash messages after a few seconds.

  // Auto-dismiss server-rendered flash messages after a few seconds.
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(() => flash.style.display = 'none', 4000);
  }
});
