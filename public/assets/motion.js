// motion.js - ambient motion layer for Maxi Fine Foods.
//
// Four effects, all styled in assets/motion.css:
//   1. a soft cloth/fabric mesh that drapes and ripples under the cursor
//   2. a cursor-tracking glow plus three slowly drifting colour washes
//   3. a thin scroll-progress rail across the top of the viewport
//   4. staggered scroll-in reveals, 3D card tilt and a cursor-follow highlight
//
// The cloth is a real Verlet-integrated particle grid: every node obeys
// inertia and gravity, is pushed by the mouse, then the mesh is relaxed with
// distance constraints, so it behaves like hanging fabric rather than a glow
// that follows the cursor around.
//
// Design constraints (UI/UX Pro Max v2.13.0, Animation + GSAP domains):
//   transform-performance  the cloth lives on ONE <canvas>, so animating
//                          hundreds of points never touches the DOM or layout
//   parallax-decorative   only the background stage reacts to the pointer;
//                          body copy and form controls never do
//   no-blocking-animation the stage is pointer-events:none, so the simulation
//                          can never delay or swallow a click
//   excessive-motion      effects are grouped into one background stage rather
//                          than sprinkled across every element
//   parallax-subtle       disabled outright under reduced-motion and on coarse
//                          pointers, where there is no cursor to follow
//   performance           frames stop when the pointer parks; DPR is capped
//
// No inline <script> or style="" attributes are used, because the site's
// Content-Security-Policy allows neither. Canvas drawing and CSSOM custom
// property writes are both unaffected by CSP.

(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  // Only follow a real hovering cursor. On touch there is no pointer to track,
  // and parallax-subtle warns against motion the user cannot steer.
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

  function motionOff() {
    return reduceMotion.matches || !finePointer.matches;
  }

  /* ---------------------------------------------------- 1. background stage */

  // Builds the fixed, inert layer that sits behind the app shell: the cloth
  // canvas, three drifting colour washes, and the cursor-tracking glow. All of
  // it is decorative and pointer-events:none, so nothing here can block a click
  // or shift the layout.
  function buildStage() {
    var stage = document.createElement('div');
    stage.className = 'mff-stage';
    stage.setAttribute('aria-hidden', 'true');

    var canvas = document.createElement('canvas');
    canvas.className = 'mff-cloth-canvas';
    stage.appendChild(canvas);

    ['a', 'b', 'c'].forEach(function (key) {
      var blob = document.createElement('div');
      blob.className = 'mff-aurora mff-aurora--' + key;
      stage.appendChild(blob);
    });

    var glow = document.createElement('div');
    glow.className = 'mff-pointer-glow';
    stage.appendChild(glow);

    var progress = document.createElement('div');
    progress.className = 'mff-progress';
    document.body.appendChild(progress);   // above the stage, below content

    document.body.insertBefore(stage, document.body.firstChild);
    return { canvas: canvas, glow: glow, progress: progress };
  }

  /* Cursor-tracking glow. Eased toward the pointer in a single rAF loop that
     only runs while the pointer is actually moving, then it stops burning
     frames and parks off-screen. transform-only, so following the pointer
     never triggers a viewport repaint. */
  function initPointerGlow(glow) {
    if (motionOff()) return;

    var OFF = -9999;
    var targetX = OFF, targetY = OFF;
    var curX = OFF, curY = OFF;
    var frame = 0;
    var idle = true;

    function paint(x, y) {
      glow.style.transform =
        'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0)';
    }

    function render() {
      curX += (targetX - curX) * 0.12;
      curY += (targetY - curY) * 0.12;
      paint(curX, curY);

      if (Math.abs(targetX - curX) < 0.4 && Math.abs(targetY - curY) < 0.4) {
        paint(targetX, targetY);  // snap the last sub-pixel, then stop
        idle = true;
        return;
      }
      frame = window.requestAnimationFrame(render);
    }

    function schedule() {
      if (idle) {
        idle = false;
        frame = window.requestAnimationFrame(render);
      }
    }

    window.addEventListener('pointermove', function (e) {
      targetX = e.clientX;
      targetY = e.clientY;
      if (!glow.classList.contains('is-live')) glow.classList.add('is-live');
      schedule();
    }, { passive: true });

    function park() {
      glow.classList.remove('is-live');
      targetX = OFF;
      targetY = OFF;
      if (frame) { window.cancelAnimationFrame(frame); frame = 0; }
      idle = true;
    }
    document.addEventListener('pointerleave', park);
    window.addEventListener('blur', park);
  }

  /* Thin progress rail. Written as a single custom property and consumed by a
     scaleX() in CSS, so scrolling costs one composited transform per frame and
     no layout at all. */
  function initProgress(progress) {
    if (motionOff()) return;
    var queued = false;

    function update() {
      queued = false;
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? Math.min(1, Math.max(0, doc.scrollTop / max)) : 0;
      progress.style.setProperty('--mff-scroll', pct.toFixed(4));
    }

    window.addEventListener('scroll', function () {
      if (queued) return;            // coalesce to one update per frame
      queued = true;
      window.requestAnimationFrame(update);
    }, { passive: true });

    update();
  }

  /* ------------------------------------------------ 2. cloth pointer fabric */

  function initCloth(canvas) {
    if (motionOff()) return;
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) { canvas.remove(); return; }

    var COLS = 26;          // grid density; 26x18 keeps it cheap but smooth
    var ROWS = 18;
    var GRAVITY = 0.028;    // gentle droop so it reads as hanging fabric
    var DAMPING = 0.985;    // inertia retention; near 1 = heavy, silken cloth
    var ITERATIONS = 3;     // constraint relaxation passes per frame
    var POINTER_RADIUS = 190;
    var POINTER_FORCE = 13;

    var w = 0, h = 0, dpr = 1;
    var pts = [];           // {x, y, px, py, ox, oy}
    var frame = 0;
    var idle = true;

    var mx = -999, my = -999;   // smoothed pointer
    var tx = -999, ty = -999;   // raw pointer target

    function resize() {
      w = window.innerWidth;
      h = window.innerHeight;
      // Cap DPR: a full-viewport canvas at 3x is a lot of fill rate for an
      // effect this soft, and this keeps high-DPI laptops cool and smooth.
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.max(1, Math.floor(w * dpr));
      canvas.height = Math.max(1, Math.floor(h * dpr));
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      buildMesh();
    }

    function buildMesh() {
      pts = [];
      var cellW = w / (COLS - 1);
      var cellH = h / (ROWS - 1);
      for (var y = 0; y < ROWS; y++) {
        for (var x = 0; x < COLS; x++) {
          var px = x * cellW;
          var py = y * cellH;

    function step() {
      var i, p;
      for (i = 0; i < pts.length; i++) {
        p = pts[i];
        // Verlet integration: velocity is implied by the gap between the
        // current position and the previous one, so no velocity array is
        // needed and the fabric keeps its own inertia.
        var vx = (p.x - p.px) * DAMPING;
        var vy = (p.y - p.py) * DAMPING;
        p.px = p.x;
        p.py = p.y;
        p.x += vx;
        p.y += vy + GRAVITY;

        // The cursor pushes the cloth away from itself.
        var dx = p.x - mx;
        var dy = p.y - my;
        var d2 = dx * dx + dy * dy;
        if (d2 < POINTER_RADIUS * POINTER_RADIUS && d2 > 0.01) {
          var d = Math.sqrt(d2);
          var f = (1 - d / POINTER_RADIUS) * POINTER_FORCE / d;
          p.x += dx * f;
          p.y += dy * f;
        }

        // Pull gently back toward the resting grid, so the cloth always
        // settles instead of drifting off-screen forever.
        p.x += (p.ox - p.x) * 0.006;
        p.y += (p.oy - p.y) * 0.006;
      }
    }

    function constrain() {
      var cellW = w / (COLS - 1);
      var cellH = h / (ROWS - 1);
      var y, x, a, b, dx, dy, d, diff, ox, oy;

      // Horizontal neighbours, then vertical. Relaxing both directions each
      // pass is enough to keep the mesh visibly woven.
      for (y = 0; y < ROWS; y++) {
        for (x = 0; x < COLS - 1; x++) {
          a = pts[y * COLS + x];
          b = pts[y * COLS + x + 1];
          dx = b.x - a.x; dy = b.y - a.y;
          d = Math.sqrt(dx * dx + dy * dy) || 0.0001;
          diff = (d - cellW) / d * 0.5;
          ox = dx * diff; oy = dy * diff;
          a.x += ox; a.y += oy;
          b.x -= ox; b.y -= oy;
        }
      }
      for (y = 0; y < ROWS - 1; y++) {
        for (x = 0; x < COLS; x++) {
          a = pts[y * COLS + x];
          b = pts[(y + 1) * COLS + x];
          dx = b.x - a.x; dy = b.y - a.y;
          d = Math.sqrt(dx * dx + dy * dy) || 0.0001;
          diff = (d - cellH) / d * 0.5;
          ox = dx * diff; oy = dy * diff;
          a.x += ox; a.y += oy;
          b.x -= ox; b.y -= oy;
        }
      }
    }

          pts.push({ x: px, y: py, px: px, py: py, ox: px, oy: py });
        }
      }
    }


    function draw() {
      var y, x, a, b;
      ctx.clearRect(0, 0, w, h);
      ctx.lineWidth = 1;
      ctx.strokeStyle = 'rgba(47, 96, 65, 0.055)';
      ctx.beginPath();
      for (y = 0; y < ROWS; y++) {
        for (x = 0; x < COLS - 1; x++) {
          a = pts[y * COLS + x];
          b = pts[y * COLS + x + 1];
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
        }
      }
      for (y = 0; y < ROWS - 1; y++) {
        for (x = 0; x < COLS; x++) {
          a = pts[y * COLS + x];
          b = pts[(y + 1) * COLS + x];
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
        }
      }
      ctx.stroke();
    }

    function render() {
      // Ease the mesh pointer toward the raw cursor, so the fabric lags the
      // mouse slightly and feels weighted rather than glued to it.
      mx += (tx - mx) * 0.16;
      my += (ty - my) * 0.16;

      step();
      for (var k = 0; k < ITERATIONS; k++) constrain();
      draw();

      // Once the fabric has caught up with the pointer, stop the loop: there is
      // no reason to keep a rAF alive on an otherwise static page.
      if (Math.abs(tx - mx) < 0.2 && Math.abs(ty - my) < 0.2) {
        idle = true;
        return;
      }
      frame = window.requestAnimationFrame(render);
    }

    function wake() {
      if (idle) {
        idle = false;
        frame = window.requestAnimationFrame(render);
      }
    }

    function onMove(e) {
      tx = e.clientX;
      ty = e.clientY;
      if (!canvas.classList.contains('is-live')) canvas.classList.add('is-live');
      wake();
    }

    function onLeave() { canvas.classList.remove('is-live'); }

    window.addEventListener('pointermove', onMove, { passive: true });
    document.addEventListener('pointerleave', onLeave);
    window.addEventListener('blur', onLeave);

    var resizeTimer = 0;
    window.addEventListener('resize', function () {
      // Debounced: dragging a window edge fires resize continuously, and
      // rebuilding the mesh on every event would be wasteful.
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        resize();
        wake();
      }, 150);
    }, { passive: true });

    resize();
    draw();
  }

  /* ------------------------------------------------------- 3. scroll reveals */

  // NOTE: .product-card is intentionally absent. style.css already parks it at
  // opacity 0 and main.js reveals it via .is-entered, so adding it here would
  // put two reveal systems on one node and they would fight.
  var REVEAL_SELECTOR = [
    '.hero-card',
    '.catalogue-toolbar',
    '.section-eyebrow',
    '.section-title',
    '.newsletter-form'
  ].join(',');

  // Axis is varied per element so a long page does not feel like one effect
  // repeated, while each individual entrance stays simple and legible.
  var VARIANTS = ['', '--left', '--right', '--scale'];

  function initReveals() {
    var targets = document.querySelectorAll(REVEAL_SELECTOR);
    if (!targets.length) return;

    if (motionOff() || !('IntersectionObserver' in window)) {
      // Nothing to do: without .mff-reveal the elements are simply visible.
      return;
    }

    // Group items sharing a parent so a grid staggers among itself.
    var byParent = new Map();
    targets.forEach(function (el) {
      var key = el.parentNode;
      if (!byParent.has(key)) byParent.set(key, []);
      byParent.get(key).push(el);
    });

    var STAGGER_MS = 40; // --mff-stagger; the skill's 30-50ms band
    var MAX_STEPS = 8;    // cap so a long grid never waits seconds

    byParent.forEach(function (group) {
      group.forEach(function (el, i) {
        el.classList.add('mff-reveal');
        el.style.setProperty('--mff-reveal-delay',
          Math.min(i, MAX_STEPS) * STAGGER_MS + 'ms');

        // Only vary the axis for siblings, so related items stay consistent.
        if (group.length > 1) {
          el.classList.add('mff-reveal' + VARIANTS[i % VARIANTS.length]);
        }
      });
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('mff-revealed');
        observer.unobserve(entry.target); // one-shot: never replay on scroll up
      });
    }, {
      // Fire slightly before the element is fully on screen, so the motion is
      // already settling by the time it reaches the viewport centre.
      rootMargin: '0px 0px -8% 0px',
      threshold: 0.08
    });

    targets.forEach(function (el) { observer.observe(el); });
  }



  /* ------------------------------------------------ 4. pointer-tracked cards */

  // Cards lean toward the cursor and carry a highlight that follows it. The
  // box is measured on enter and after any scroll/resize, so pointermove never
  // forces a layout read, and only custom properties are written per frame.
  function initCardPointer() {
    if (motionOff()) return;

    var cards = document.querySelectorAll('.pre-footer-card, .stat-card');
    if (!cards.length) return;

    var MAX_TILT = 6;    // degrees; past ~8 the card starts to look broken
    var LIFT = -5;       // px raised on hover

    cards.forEach(function (card) {
      card.classList.add('mff-tilt', 'mff-spot');

      var rect = null;
      var raf = 0;
      var tx = 0, ty = 0;   // target tilt
      var cx = 0, cy = 0;   // current, eased tilt
      var hx = 50, hy = 50; // highlight position, in %

      function measure() { rect = card.getBoundingClientRect(); }

      function apply() {
        cx += (tx - cx) * 0.18;
        cy += (ty - cy) * 0.18;

        card.style.setProperty('--mff-tilt-x', cy.toFixed(2) + 'deg');
        card.style.setProperty('--mff-tilt-y', cx.toFixed(2) + 'deg');
        card.style.setProperty('--mff-cx', hx.toFixed(1) + '%');
        card.style.setProperty('--mff-cy', hy.toFixed(1) + '%');

        if (Math.abs(tx - cx) < 0.05 && Math.abs(ty - cy) < 0.05) {
          card.style.setProperty('--mff-tilt-x', tx + 'deg');
          card.style.setProperty('--mff-tilt-y', ty + 'deg');
          return;                      // settled: stop the loop
        }
        raf = window.requestAnimationFrame(apply);
      }

      function kick() { if (!raf) raf = window.requestAnimationFrame(apply); }

      card.addEventListener('pointerenter', function () {
        measure();
        card.classList.add('is-settling');
        card.style.setProperty('--mff-ty', LIFT + 'px');
      }, { passive: true });

      card.addEventListener('pointermove', function (e) {
        if (!rect) measure();
        var px = (e.clientX - rect.left) / rect.width;
        var py = (e.clientY - rect.top) / rect.height;

        // Mirror Y so pushing the cursor toward the top of the card tips the
        // card's top edge away, which is how a real object would react.
        tx = (px - 0.5) * 2 * MAX_TILT;
        ty = -(py - 0.5) * 2 * MAX_TILT;

        hx = Math.min(100, Math.max(0, px * 100));
        hy = Math.min(100, Math.max(0, py * 100));
        kick();
      }, { passive: true });

      function release() {
        tx = 0; ty = 0; hx = 50; hy = 50;
        card.classList.remove('is-settling');
        card.style.setProperty('--mff-ty', '0px');
        kick();
      }

      card.addEventListener('pointerleave', release, { passive: true });
      // Keyboard users tabbing away must get the same reset as a mouse exit.
      card.addEventListener('blur', release, true);

      // Any scroll or resize invalidates the cached box; re-measure lazily.
      window.addEventListener('scroll', function () { rect = null; }, { passive: true });
      window.addEventListener('resize', function () { rect = null; }, { passive: true });
    });
  }


  /* ------------------------------------------------------------- boot */

  function init() {
    var parts = buildStage();
    initCloth(parts.canvas);
    initPointerGlow(parts.glow);
    initProgress(parts.progress);
    initCardPointer();
    initReveals();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Respect a live change to the OS reduced-motion setting. Turning it on
  // mid-session must not leave reveals stuck at opacity 0, and must not leave a
  // rAF loop still writing transforms to the DOM.
  function onMotionChange() {
    if (!reduceMotion.matches) return;

    document.querySelectorAll('.mff-reveal').forEach(function (el) {
      el.classList.add('mff-revealed');
    });

    // Remove the whole decorative stage, not just the canvas: the aurora
    // washes and the pointer glow are part of the same paint layer.
    var stage = document.querySelector('.mff-stage');
    if (stage) stage.remove();

    var rail = document.querySelector('.mff-progress');
    if (rail) rail.remove();

    // Strip the tilt/spot classes so no handler keeps mutating the DOM.
    document.querySelectorAll('.mff-tilt, .mff-spot').forEach(function (el) {
      el.classList.remove('mff-tilt', 'mff-spot', 'is-settling');
      ['--mff-tilt-x', '--mff-tilt-y', '--mff-ty', '--mff-cx', '--mff-cy']
        .forEach(function (prop) { el.style.removeProperty(prop); });
    });
  }

  if (typeof reduceMotion.addEventListener === 'function') {
    reduceMotion.addEventListener('change', onMotionChange);
  } else if (typeof reduceMotion.addListener === 'function') {
    reduceMotion.addListener(onMotionChange); // Safari < 14
  }
})();

