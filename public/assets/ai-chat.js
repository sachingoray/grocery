// ai-chat.js — floating widget. Talks ONLY to same-origin api/chat.php.
(function () {
  var base = (document.body && document.body.dataset.baseUrl) || '';
  var endpoint = base + '/api/chat.php';
  var fab, panel, log, form, input, sendBtn, closeBtn;
  var sending = false;
  function el(tag, cls, text) { var d = document.createElement(tag); if (cls) d.className = cls; if (text !== undefined) d.textContent = text; return d; }
  function scrollDown() { log.scrollTop = log.scrollHeight; }
  function addMsg(text, who) { var m = el('div', 'ai-msg ai-msg--' + who, text); log.appendChild(m); scrollDown(); return m; }
  function showTyping() { var t = el('div', 'ai-msg ai-msg--bot ai-msg--typing'); for (var i = 0; i < 3; i++) t.appendChild(el('span', 'dot')); log.appendChild(t); scrollDown(); return t; }
  function setOpen(open) {
    panel.hidden = !open;
    fab.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) { if (window.lucide) lucide.createIcons(); input.focus(); scrollDown(); }
    else fab.focus();
  }
  async function send(text) {
    if (sending || !text) return;
    sending = true; sendBtn.disabled = true;
    addMsg(text, 'user');
    input.value = '';
    var typing = showTyping();
    try {
      var res = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ message: text }) });
      var data = null;
      try { data = await res.json(); } catch (e) { data = null; }
      typing.remove();
      if (!res.ok || !data || typeof data.reply !== 'string' || !data.reply.trim()) {
        addMsg((data && data.error) || 'Sorry, the assistant is unavailable. Please try again.', 'bot');
      } else { addMsg(data.reply.trim(), 'bot'); }
    } catch (e) { typing.remove(); addMsg('Network error. Check your connection and try again.', 'bot'); }
    sending = false; sendBtn.disabled = false; input.focus();
  }
  function init() {
    fab = document.getElementById('ai-chat-fab');
    panel = document.getElementById('ai-chat-panel');
    if (!fab || !panel) return;
    log = panel.querySelector('[data-ai-log]');
    form = panel.querySelector('[data-ai-form]');
    input = panel.querySelector('[data-ai-input]');
    sendBtn = panel.querySelector('[data-ai-send]');
    closeBtn = panel.querySelector('[data-ai-close]');
    fab.addEventListener('click', function () { setOpen(panel.hidden); });
    closeBtn.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) setOpen(false); });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var v = (input.value || '').trim();
      if (!v) return;
      if (v.length > 1000) { addMsg('Please keep messages under 1000 characters.', 'bot'); return; }
      send(v.slice(0, 1000));
    });
    addMsg('Hi! I am the Maxi Fine Foods assistant. Ask me about products, prices, ordering or delivery.', 'bot');
  }
  document.addEventListener('DOMContentLoaded', init);
})();
