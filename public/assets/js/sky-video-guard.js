/*
 * Sky Link video guard. A third-party player (TikTok) can fail inside the visitor's own browser or network
 * ("Unable to load player ... Retry"): privacy settings, a blocked TikTok video server, a removed or private video.
 * The page cannot read inside the foreign iframe, but the TikTok player tells its host page when it is ready or has failed
 * (postMessage: onPlayerReady / onPlayerError). This script listens to that.
 *   - A REAL error from the player (onPlayerError) replaces it with a thumbnail and a "Watch on TikTok" button.
 *   - A SLOW start does NOT replace it: measured on phones, TikTok's player needs about 12 s (fast), 27 s (slow 4G) and
 *     up to 60 s (iPhone/WebKit) to become ready. After 15 s a "still loading" bar with a "Watch on TikTok" link appears
 *     on top while the player keeps loading; it disappears when the player is ready. Only after 3 minutes of silence
 *     is the player replaced by the fallback.
 *
 *   SkyVideoGuard.watch(iframe, { box, link, thumb, platform, title })   box = element that holds the iframe
 */
(function () {
    'use strict';

    var LABELS = { tiktok: 'TikTok', youtube: 'YouTube' };

    function showFallback(box, o) {
        if (!box || box.querySelector('.sky-video-fallback')) return;
        var name = LABELS[o.platform] || 'the original site';
        var wrap = document.createElement('div');
        wrap.className = 'sky-video-fallback';
        wrap.setAttribute('role', 'group');
        wrap.setAttribute('aria-label', 'Video could not be played here');
        if (o.thumb) wrap.style.backgroundImage = 'url("' + String(o.thumb).replace(/"/g, '%22') + '")';

        var inner = document.createElement('div');
        inner.className = 'sky-video-fallback__inner';
        var msg = document.createElement('p');
        msg.className = 'sky-video-fallback__msg';
        msg.textContent = 'This video cannot be played here right now. You can still watch it on ' + name + '.';
        inner.appendChild(msg);
        if (o.link) {
            var a = document.createElement('a');
            a.className = 'sky-video-fallback__btn';
            a.href = o.link;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.innerHTML = '<span class="sky-video-fallback__icon" aria-hidden="true">&#9654;</span> Watch on ' + name;
            a.setAttribute('aria-label', 'Watch on ' + name + (o.title ? ': ' + o.title : '') + ' (opens in a new tab)');
            inner.appendChild(a);
        }
        wrap.appendChild(inner);
        box.innerHTML = '';
        box.appendChild(wrap);
    }

    function injectStyle() {
        if (document.getElementById('sky-video-slow-css')) return;
        var st = document.createElement('style');
        st.id = 'sky-video-slow-css';
        st.textContent = '.sky-video-slow{position:absolute;left:8px;right:8px;bottom:8px;z-index:5;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;' +
            'padding:.6rem .8rem;border-radius:12px;background:rgba(6,18,43,.88);color:#fff;font:500 .875rem/1.35 system-ui,sans-serif}' +
            '.sky-video-slow a{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.5rem 1rem;border-radius:999px;background:#fff;color:#06122b;font-weight:700;text-decoration:none}';
        document.head.appendChild(st);
    }

    function showSlow(box, o) {
        if (!box || box.querySelector('.sky-video-slow') || box.querySelector('.sky-video-fallback')) return;
        injectStyle();
        var bar = document.createElement('div');
        bar.className = 'sky-video-slow';
        bar.setAttribute('role', 'status');
        var t = document.createElement('span');
        t.textContent = 'Still loading. Slow connections can take a minute.';
        bar.appendChild(t);
        if (o.link) {
            var a = document.createElement('a');
            a.href = o.link; a.target = '_blank'; a.rel = 'noopener noreferrer';
            a.textContent = 'Watch on ' + (LABELS[o.platform] || 'the original site');
            bar.appendChild(a);
        }
        box.appendChild(bar);
    }

    function watch(iframe, o) {
        o = o || {};
        var box = o.box || iframe.parentNode;
        var settled = false;
        var timer, slowTimer;

        function stop() {
            settled = true;
            clearTimeout(timer);
            clearTimeout(slowTimer);
            window.removeEventListener('message', onMessage);
        }
        // "Ready" only ends the start-up timeout; a playback error after that (TikTok's servers refusing the video) still shows the fallback.
        function ready() {
            clearTimeout(timer);
            clearTimeout(slowTimer);
            var bar = box && box.querySelector ? box.querySelector('.sky-video-slow') : null;
            if (bar) bar.parentNode.removeChild(bar);
        }
        function fail() {
            if (settled) return;
            stop();
            showFallback(box, o);
        }
        function onMessage(e) {
            if (e.source !== iframe.contentWindow) return;
            var d = e.data;
            if (typeof d === 'string') { try { d = JSON.parse(d); } catch (x) { return; } }
            if (!d || !d['x-tiktok-player']) return;
            if (d.type === 'onPlayerReady') { ready(); return; }
            if (d.type === 'onPlayerError') {
                var code = d.value && d.value.errorCode;
                // 3002 = autoplay blocked by the browser: the player itself is fine. 1001/2001/3001 = TikTok cannot serve or play the video.
                if (code !== 3002) {
                    if (window.console && console.info) console.info('SkyVideoGuard: TikTok player error', d.value);
                    fail();
                }
            }
        }

        window.addEventListener('message', onMessage);
        slowTimer = setTimeout(function () { if (!settled) showSlow(box, o); }, o.slowAfter || 15000); // slow start: help, but keep loading
        timer = setTimeout(fail, o.timeout || 180000); // only 3 minutes of silence means the player will not come
        return { fail: fail, stop: stop };
    }

    window.SkyVideoGuard = { watch: watch, showFallback: showFallback };
})();
