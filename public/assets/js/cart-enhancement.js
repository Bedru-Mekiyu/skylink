/**
 * Sky Link Technologies - Cart Enhancement Engine
 * Progressive Enhancement for asynchronous Cart & Stepper Interactions
 */
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function announce(message) {
        let announcer = document.getElementById('cartStatusAnnouncer');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'cartStatusAnnouncer';
            announcer.setAttribute('aria-live', 'polite');
            announcer.className = 'visually-hidden';
            document.body.appendChild(announcer);
        }
        announcer.textContent = message;
    }

    function updateNavCartBadges(count) {
        const badges = document.querySelectorAll('.nav-cart-badge');
        const vals = document.querySelectorAll('.cart-count-val');
        vals.forEach(el => el.textContent = count);
        badges.forEach(b => {
            if (count > 0) {
                b.style.display = '';
            } else {
                b.style.display = 'none';
            }
        });
    }

    // Debounce tracker for quantity updates per lineKey
    const pendingUpdates = {};

    function setupCartPage() {
        const cartContainer = document.querySelector('.cart-container');
        if (!cartContainer) return;

        // Intercept quantity button clicks
        cartContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('button[name="quantity"]');
            if (!btn) return;

            const form = btn.closest('form');
            if (!form || !form.action.includes('/cart/update')) return;

            e.preventDefault();

            const lineKeyInput = form.querySelector('input[name="line_key"]');
            if (!lineKeyInput) return;
            const lineKey = lineKeyInput.value;
            const newQty = parseInt(btn.value, 10);
            if (isNaN(newQty) || newQty < 0 || newQty > 100) return;

            // Optimistic UI update
            const qtyDisplays = document.querySelectorAll(`.line-qty-val[data-line-key="${lineKey}"]`);
            qtyDisplays.forEach(el => el.textContent = newQty);

            // Update decrease/increase button values in all matching forms (desktop & mobile)
            const decBtns = document.querySelectorAll(`form.cart-qty-form[data-line-key="${lineKey}"] button[aria-label="Decrease quantity"]`);
            const incBtns = document.querySelectorAll(`form.cart-qty-form[data-line-key="${lineKey}"] button[aria-label="Increase quantity"]`);
            decBtns.forEach(b => b.value = Math.max(0, newQty - 1));
            incBtns.forEach(b => b.value = Math.min(100, newQty + 1));

            // Debounced server sync
            if (pendingUpdates[lineKey]) {
                clearTimeout(pendingUpdates[lineKey]);
            }

            pendingUpdates[lineKey] = setTimeout(async () => {
                try {
                    btn.disabled = true;
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            line_key: lineKey,
                            quantity: newQty
                        })
                    });

                    btn.disabled = false;
                    if (!res.ok) throw new Error('Failed to update cart');

                    const data = await res.json();
                    if (data.ok) {
                        updateNavCartBadges(data.count);

                        // If quantity reached 0, remove row
                        if (newQty === 0) {
                            const desktopRow = document.getElementById(`cart-row-${lineKey}`);
                            const mobileCard = document.getElementById(`cart-card-${lineKey}`);
                            if (desktopRow) desktopRow.remove();
                            if (mobileCard) mobileCard.remove();
                        } else {
                            // Update formatted line total
                            const totalDisplays = document.querySelectorAll(`.line-total-val[data-line-key="${lineKey}"]`);
                            totalDisplays.forEach(el => el.textContent = data.line_total_formatted);
                        }

                        // Update cart total
                        const totalDisplay = document.getElementById('cartTotalDisplay');
                        if (totalDisplay && data.totals) {
                            totalDisplay.textContent = `${data.totals.currency} ${Number(data.totals.subtotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        }

                        // Check if cart is now empty
                        if (data.is_empty) {
                            location.reload();
                        }

                        announce(`Cart updated. Total is ${data.totals ? data.totals.currency + ' ' + Number(data.totals.subtotal).toFixed(2) : ''}`);
                    }
                } catch (err) {
                    btn.disabled = false;
                    console.error('Cart update error:', err);
                }
            }, 250);
        });

        // Intercept remove button clicks
        cartContainer.addEventListener('click', async function (e) {
            const btn = e.target.closest('button[aria-label="Remove item"]');
            if (!btn) return;

            const form = btn.closest('form');
            if (!form || !form.action.includes('/cart/remove')) return;

            e.preventDefault();

            const sure = await window.SkyConfirm.ask({
                title: 'Remove this item?', message: 'It will be taken out of your cart.',
                confirmLabel: 'Remove', cancelLabel: 'Keep it', variant: 'danger'
            });
            if (!sure) {
                return;
            }

            const lineKeyInput = form.querySelector('input[name="line_key"]');
            if (!lineKeyInput) return;
            const lineKey = lineKeyInput.value;

            btn.disabled = true;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ line_key: lineKey })
                });

                if (!res.ok) throw new Error('Failed to remove item');

                const data = await res.json();
                if (data.ok) {
                    updateNavCartBadges(data.count);

                    const desktopRow = document.getElementById(`cart-row-${lineKey}`);
                    const mobileCard = document.getElementById(`cart-card-${lineKey}`);
                    if (desktopRow) {
                        desktopRow.style.transition = 'opacity 0.25s ease';
                        desktopRow.style.opacity = '0';
                        setTimeout(() => desktopRow.remove(), 250);
                    }
                    if (mobileCard) {
                        mobileCard.style.transition = 'opacity 0.25s ease';
                        mobileCard.style.opacity = '0';
                        setTimeout(() => mobileCard.remove(), 250);
                    }

                    const totalDisplay = document.getElementById('cartTotalDisplay');
                    if (totalDisplay && data.totals) {
                        totalDisplay.textContent = `${data.totals.currency} ${Number(data.totals.subtotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }

                    if (data.is_empty) {
                        setTimeout(() => location.reload(), 300);
                    }

                    announce('Item removed from cart.');
                }
            } catch (err) {
                btn.disabled = false;
                console.error('Remove item error:', err);
            }
        });
    }

    function setupDetailPages() {
        const addToCartForms = document.querySelectorAll('form.add-to-cart-form');
        addToCartForms.forEach(form => {
            form.addEventListener('submit', async function (e) {
                const submitBtn = e.submitter || form.querySelector('button[type="submit"]');
                if (!submitBtn) return;
                // "Checkout" is a normal form post: the server adds the line and opens the cart page.
                if (submitBtn.name === 'then') return;
                const iconOnly = submitBtn.hasAttribute('data-icon-only');

                e.preventDefault();

                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = iconOnly ? '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>' : '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';

                try {
                    const formData = new FormData(form);
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (!res.ok) throw new Error('Failed to add item to cart');

                    const data = await res.json();
                    if (data.ok) {
                        updateNavCartBadges(data.count);
                        submitBtn.innerHTML = iconOnly ? '<i class="fas fa-check" aria-hidden="true"></i>' : '<i class="fas fa-check me-2"></i> Added to Cart!';
                        const baseClass = submitBtn.classList.contains('btn-primary') ? 'btn-primary' : 'btn-outline-primary';
                        submitBtn.classList.remove(baseClass);
                        submitBtn.classList.add('btn-success');

                        window.skyToast('Added to cart.', { link: '/cart', linkText: 'View cart and checkout' });

                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                            submitBtn.classList.remove('btn-success');
                            submitBtn.classList.add(baseClass);
                        }, 2500);

                    } else {
                        throw new Error(data.message || 'Error adding to cart');
                    }
                } catch (err) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    window.skyToast('Could not add to cart. Please try again.', { kind: 'error' });
                    console.error('Add to cart error:', err);
                }
            });
        });
    }

    // Enquiry forms: one press is enough, and the button shows that something is happening.
    function setupBusyForms() {
        document.querySelectorAll('form[data-busy]').forEach(form => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            const original = button.innerHTML;
            form.addEventListener('submit', () => {
                setTimeout(() => {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + original;
                });
            });
            window.addEventListener('pageshow', () => {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.innerHTML = original;
            });
        });
    }

    // "Back" can show an old copy of a page: bring the cart up to date.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        if (location.pathname === '/cart') {
            location.reload();
            return;
        }
        fetch('/cart/count', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(res => res.ok ? res.json() : null)
            .then(data => { if (data) updateNavCartBadges(data.count); })
            .catch(() => {});
    });

    document.addEventListener('DOMContentLoaded', function () {
        setupBusyForms();
        setupCartPage();
        setupDetailPages();
    });
})();
