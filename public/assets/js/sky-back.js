/* "Back" returns to the page you came from when it was part of this site; otherwise the link goes to the page above. */
(function () {
    'use strict';
    var link = document.querySelector('a[data-back]');
    if (!link) return;

    var from = null;
    try { from = document.referrer ? new URL(document.referrer) : null; } catch (e) { from = null; }
    // Not for a reload or a redirect to the same address: that would go "back" to where you already are.
    if (!from || from.origin !== location.origin || from.pathname === location.pathname || history.length < 2) return;

    link.setAttribute('aria-label', 'Go back to the previous page');
    link.setAttribute('title', 'Go back to the previous page');
    link.addEventListener('click', function (event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
        event.preventDefault();
        history.back();
    });
})();
