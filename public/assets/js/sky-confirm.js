/*
 * Sky Link admin confirmation dialog: one accessible in-page modal instead of the browser's confirm()/prompt().
 *
 * Markup (works on a <form> or on a <button> that submits a form, also through the form="" attribute):
 *   <form ... data-sky-confirm="Delete this message? This can't be undone from the admin."
 *              data-sky-title="Delete message?" data-sky-ok="Delete" data-sky-variant="danger"
 *              onsubmit="return confirm('...')">      <- optional no-JavaScript fallback; removed when this script runs
 *
 * Code:   SkyConfirm.ask({ title, message, confirmLabel, cancelLabel, variant: 'danger'|'primary', input: {label, value, placeholder} })
 *         -> Promise: false when cancelled; true when confirmed; the typed text when `input` was given.
 *
 * Keyboard: focus moves into the dialog (to Cancel for dangerous actions), Tab stays inside, Esc and Cancel close it,
 * a click on the backdrop closes it, focus returns to the control that opened it.
 */
(function () {
    'use strict';
    if (window.SkyConfirm) return;

    var root, panel, titleEl, iconEl, msgEl, inputWrap, inputLabel, input, okBtn, cancelBtn;
    var opener = null;
    var resolver = null;
    var hasInput = false;

    function build() {
        if (root) return;
        root = document.createElement('div');
        root.className = 'sky-modal';
        root.hidden = true;
        root.innerHTML =
            '<div class="sky-modal__backdrop" data-sky-cancel></div>' +
            '<div class="sky-modal__panel" role="dialog" aria-modal="true" aria-labelledby="skyModalTitle" aria-describedby="skyModalMsg" tabindex="-1">' +
            '<div class="sky-modal__head"><span class="sky-modal__icon" aria-hidden="true"><i class="fas"></i></span><h2 class="sky-modal__title" id="skyModalTitle"></h2></div>' +
            '<div class="sky-modal__body"><p class="sky-modal__msg" id="skyModalMsg"></p>' +
            '<div class="sky-modal__input" hidden><label class="form-label" for="skyModalInput"></label><input type="text" class="form-control" id="skyModalInput" autocomplete="off"></div></div>' +
            '<div class="sky-modal__actions"><button type="button" class="btn btn-light" data-sky-cancel>Cancel</button><button type="button" class="btn" data-sky-ok>Confirm</button></div>' +
            '</div>';
        document.body.appendChild(root);
        panel = root.querySelector('.sky-modal__panel');
        titleEl = root.querySelector('.sky-modal__title');
        iconEl = root.querySelector('.sky-modal__icon i');
        msgEl = root.querySelector('.sky-modal__msg');
        inputWrap = root.querySelector('.sky-modal__input');
        inputLabel = inputWrap.querySelector('label');
        input = inputWrap.querySelector('input');
        okBtn = root.querySelector('[data-sky-ok]');
        cancelBtn = root.querySelector('button[data-sky-cancel]');

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-sky-cancel]')) close(false);
            else if (e.target.closest('[data-sky-ok]')) close(true);
        });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); close(true); } });
        document.addEventListener('keydown', onKey, true);
    }

    function focusables() {
        return Array.prototype.slice.call(panel.querySelectorAll('input:not([hidden]), button')).filter(function (el) { return !el.disabled && el.offsetParent !== null; });
    }

    function onKey(e) {
        if (!root || root.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(false); return; }
        if (e.key === 'Tab') {
            var f = focusables();
            if (!f.length) return;
            var first = f[0], last = f[f.length - 1];
            if (e.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) { e.preventDefault(); first.focus(); }
        }
    }

    function close(ok) {
        if (!resolver) return;
        var done = resolver;
        resolver = null;
        var value = ok ? (hasInput ? input.value : true) : false;
        root.hidden = true;
        document.documentElement.classList.remove('sky-modal-open');
        var back = opener;
        opener = null;
        if (back && typeof back.focus === 'function' && document.contains(back)) back.focus();
        done(value);
    }

    function ask(o) {
        o = o || {};
        build();
        if (resolver) close(false);
        return new Promise(function (resolve) {
            resolver = resolve;
            opener = document.activeElement;
            hasInput = !!o.input;
            var danger = (o.variant || 'primary') === 'danger';
            titleEl.textContent = o.title || (danger ? 'Are you sure?' : 'Please confirm');
            msgEl.textContent = o.message || '';
            msgEl.hidden = !o.message;
            inputWrap.hidden = !hasInput;
            if (hasInput) {
                inputLabel.textContent = o.input.label || '';
                input.value = o.input.value || '';
                input.placeholder = o.input.placeholder || '';
            }
            okBtn.textContent = o.confirmLabel || (danger ? 'Delete' : 'Confirm');
            cancelBtn.textContent = o.cancelLabel || 'Cancel';
            okBtn.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
            root.classList.toggle('sky-modal--danger', danger);
            iconEl.className = 'fas ' + (hasInput ? 'fa-link' : (danger ? 'fa-trash-alt' : 'fa-question'));
            root.hidden = false;
            document.documentElement.classList.add('sky-modal-open');
            // Dangerous action: start on Cancel so a stray Enter does not delete anything.
            (hasInput ? input : (danger ? cancelBtn : okBtn)).focus();
            if (hasInput) input.select();
        });
    }

    window.SkyConfirm = { ask: ask };

    // ---- declarative use: data-sky-confirm on a <form> or on a submit <button> ----
    function optionsFrom(el) {
        return {
            message: el.getAttribute('data-sky-confirm'),
            title: el.getAttribute('data-sky-title') || undefined,
            confirmLabel: el.getAttribute('data-sky-ok') || undefined,
            variant: el.getAttribute('data-sky-variant') || 'danger'
        };
    }

    function stripFallback(el) {
        // The inline confirm() is only the no-JavaScript fallback: with this script running, the modal replaces it.
        el.removeAttribute('onsubmit');
        el.removeAttribute('onclick');
        el.onsubmit = null;
        el.onclick = null;
    }

    function wire() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-sky-confirm]'), stripFallback);

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-sky-confirm], input[type=submit][data-sky-confirm]');
            if (!btn || btn.disabled) return;
            var form = btn.form;
            if (!form) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            ask(optionsFrom(btn)).then(function (ok) { if (ok) form.submit(); });
        }, true);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.matches || !form.matches('form[data-sky-confirm]')) return;
            if (form.__skyConfirmed) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            ask(optionsFrom(form)).then(function (ok) { if (ok) { form.__skyConfirmed = true; form.submit(); } });
        }, true);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wire); else wire();
})();
