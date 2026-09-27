// checkout.js — keeps the submit button label in sync with the selected
// payment method (was previously an inline <script> + onchange handler).
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('submit-order-btn');
  if (!btn) return;

  const updatePayButton = () => {
    const method = document.querySelector('input[name="payment_method"]:checked')?.value;
    btn.textContent = method === 'credit_card' ? 'Pay with Stripe (Secure Checkout)' : 'Place order';
  };

  document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', updatePayButton);
  });
  updatePayButton();
});
