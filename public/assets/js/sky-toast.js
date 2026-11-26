/* Pop-ups that leave by themselves. window.skyToast(text, {kind, link, linkText, timeout}) adds one. */
(function () {
    'use strict';
    var box = document.getElementById('skyToasts');
    if (!box) return;

    function leave(toast) {
        toast.classList.add('sky-toast--leaving');
        setTimeout(function () { toast.remove(); }, 260);
    }

    function start(toast) {
        var timeout = Number(toast.dataset.timeout);
        var timer = null;
        var stop = function () { clearTimeout(timer); };
        var run = function () {
            stop();
            if (timeout > 0) timer = setTimeout(function () { leave(toast); }, timeout);
        };
        toast.querySelector('.sky-toast__close').addEventListener('click', function () { stop(); leave(toast); });
        // Reading or reaching for the link keeps it on screen.
        toast.addEventListener('mouseenter', stop);
        toast.addEventListener('mouseleave', run);
        toast.addEventListener('focusin', stop);
        toast.addEventListener('focusout', run);
        run();
    }

    window.skyToast = function (text, options) {
        options = options || {};
        var kind = options.kind || 'success';
        var toast = document.createElement('div');
        toast.className = 'sky-toast sky-toast--' + kind;
        toast.dataset.toast = kind;
        // An error waits to be closed.
        toast.dataset.timeout = options.timeout !== undefined ? options.timeout : (kind === 'error' ? 0 : 5000);
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        toast.innerHTML = '<i class="fas ' + (kind === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle') + '" aria-hidden="true"></i>' +
            '<div class="sky-toast__text"></div><button type="button" class="sky-toast__close" aria-label="Close">&times;</button>';
        var body = toast.querySelector('.sky-toast__text');
        body.textContent = text;
        if (options.link) {
            var link = document.createElement('a');
            link.href = options.link;
            link.textContent = options.linkText;
            body.appendChild(document.createElement('br'));
            body.appendChild(link);
        }
        box.querySelectorAll('[data-source="script"]').forEach(function (old) { old.remove(); });
        toast.dataset.source = 'script';
        box.appendChild(toast);
        start(toast);
    };

    box.querySelectorAll('.sky-toast').forEach(start);

    // "Back" can restore a page as it was: an old message is not news any more.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) box.textContent = '';
    });
})();
