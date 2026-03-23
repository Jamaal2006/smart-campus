/**
 * Greenfield Local Hub — Main JavaScript
 * Handles: navigation toggle, dark mode, font size, cart AJAX, accessibility
 */
'use strict';

// ── Navigation toggle (mobile) ────────────────────────────────────────────────
(function initNav() {
    const toggle = document.querySelector('.nav-toggle');
    const nav    = document.getElementById('primary-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', String(!expanded));
        nav.classList.toggle('open', !expanded);
    });

    // Close menu on outside click
    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            toggle.setAttribute('aria-expanded', 'false');
            nav.classList.remove('open');
        }
    });
})();

// ── Dark mode toggle ──────────────────────────────────────────────────────────
(function initDarkMode() {
    const btn = document.getElementById('dark-mode-toggle');
    if (!btn) return;

    const KEY = 'glh_dark_mode';
    const pref = localStorage.getItem(KEY);

    if (pref === 'on') {
        document.body.classList.add('dark-mode');
        btn.textContent = '☀️ Light mode';
    }

    btn.addEventListener('click', function () {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem(KEY, isDark ? 'on' : 'off');
        this.textContent = isDark ? '☀️ Light mode' : '🌙 Dark mode';
    });
})();

// ── Font size controls (accessibility) ───────────────────────────────────────
(function initFontSize() {
    const increase = document.getElementById('font-increase');
    const decrease = document.getElementById('font-decrease');
    if (!increase || !decrease) return;

    const KEY = 'glh_font_size';
    const MIN = 80, MAX = 130, STEP = 10;
    let size = parseInt(localStorage.getItem(KEY) || '100', 10);

    applySize(size);

    increase.addEventListener('click', function () {
        if (size < MAX) { size += STEP; applySize(size); }
    });
    decrease.addEventListener('click', function () {
        if (size > MIN) { size -= STEP; applySize(size); }
    });

    function applySize(s) {
        document.documentElement.style.fontSize = s + '%';
        localStorage.setItem(KEY, String(s));
    }
})();

// ── Cart quantity update (AJAX) ───────────────────────────────────────────────
(function initCart() {
    document.addEventListener('change', function (e) {
        if (e.target.matches('.qty-input')) {
            const input   = e.target;
            const form    = input.closest('form');
            const csrf    = form && form.querySelector('[name="csrf_token"]');
            const itemId  = input.dataset.productId;
            const qty     = parseInt(input.value, 10);

            if (!itemId || isNaN(qty)) return;

            fetch('/api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    action: 'update',
                    product_id: itemId,
                    quantity: qty,
                    csrf_token: csrf ? csrf.value : ''
                }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    updateCartBadge(data.cart_count);
                    const totalEl = document.getElementById('cart-total');
                    if (totalEl && data.cart_total !== undefined) {
                        totalEl.textContent = '£' + parseFloat(data.cart_total).toFixed(2);
                    }
                    const lineEl = document.getElementById('line-' + itemId);
                    if (lineEl && data.line_total !== undefined) {
                        lineEl.textContent = '£' + parseFloat(data.line_total).toFixed(2);
                    }
                }
            })
            .catch(function (err) { console.error('Cart update error:', err); });
        }
    });

    function updateCartBadge(count) {
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(function (b) { b.textContent = count; });
        const cartLink = document.querySelector('.cart-link');
        if (cartLink) {
            cartLink.setAttribute('aria-label', 'Cart (' + count + ' item' + (count !== 1 ? 's' : '') + ')');
        }
    }
})();

// ── Alert auto-dismiss ────────────────────────────────────────────────────────
(function initAlerts() {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function () { alert.remove(); }, 500);
        }, parseInt(alert.dataset.autoDismiss || '4000', 10));
    });
})();

// ── Confirm delete / remove actions ──────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (btn) {
        if (!window.confirm(btn.dataset.confirm || 'Are you sure?')) {
            e.preventDefault();
        }
    }
});
