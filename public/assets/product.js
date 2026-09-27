// product.js — quantity stepper on the single-product page.
// This stepper submits together with its form (unlike the cart page, whose
// stepper updates the cart over fetch()).
document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('input[name="quantity"]');
  const minus = document.querySelector('.btn-step-minus');
  const plus = document.querySelector('.btn-step-plus');
  if (!input || (!minus && !plus)) return;

  const step = (delta) => {
    const current = parseInt(input.value || '1', 10) || 1;
    input.value = Math.max(1, current + delta);
  };
  if (minus) minus.addEventListener('click', () => step(-1));
  if (plus) plus.addEventListener('click', () => step(1));
});
