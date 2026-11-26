/* Admin sidebar: stays where you left it when a link opens a new page (scroll position and opened groups). */
(function () {
    'use strict';
    var KEY = 'sky-sidebar';
    var areas = [['desktop', document.querySelector('nav.sidebar')], ['mobile', document.querySelector('#sidebarMenu .offcanvas-body')]];
    var saved = {};
    try { saved = JSON.parse(sessionStorage.getItem(KEY)) || {}; } catch (e) { saved = {}; }

    function store() {
        try { sessionStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) { /* storage unavailable: nothing to remember */ }
    }

    areas.forEach(function (pair) {
        var name = pair[0], area = pair[1];
        if (!area) return;
        var state = saved[name] || (saved[name] = { top: 0, open: [] });

        // Groups first: they change the height the position refers to.
        state.open.forEach(function (id) {
            var group = document.getElementById(id);
            if (group) group.classList.add('show');
        });
        area.scrollTop = state.top;

        function remember() {
            if (!area.offsetParent && name === 'desktop') return;
            state.top = area.scrollTop;
            state.open = Array.prototype.map.call(area.querySelectorAll('.collapse.show'), function (el) { return el.id; });
            store();
        }
        area.addEventListener('scroll', remember, { passive: true });
        area.addEventListener('click', function (e) { if (e.target.closest('a, button')) remember(); }, true);
        area.addEventListener('shown.bs.collapse', remember);
        area.addEventListener('hidden.bs.collapse', remember);

        // The phone menu has no size until it opens.
        if (name === 'mobile') {
            document.getElementById('sidebarMenu').addEventListener('shown.bs.offcanvas', function () { area.scrollTop = state.top; });
        }
    });
})();
