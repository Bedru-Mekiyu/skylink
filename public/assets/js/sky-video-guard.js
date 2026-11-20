/*
 * Sky Link video guard. A third-party player (TikTok) can fail inside the visitor's own browser or network
 * ("Unable to load player ... Retry"): privacy settings, a blocked TikTok video server, a removed or private video.
 * The page cannot read inside the foreign iframe, but the TikTok player tells its host page when it is ready or has failed
 * (postMessage: onPlayerReady / onPlayerError). This script listens to that and, if the player does not become ready
 * (error, or nothing within 25 seconds), replaces it with a thumbnail and a "Watch on TikTok" button.
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

    function watch(iframe, o) {
        o = o || {};
        var box = o.box || iframe.parentNode;
        var settled = false;
        var timer;

        function stop() {
            settled = true;
            clearTimeout(timer);
            window.removeEventListener('message', onMessage);
        }
        // "Ready" only ends the start-up timeout; a playback error after that (TikTok's servers refusing the video) still shows the fallback.
        function ready() { clearTimeout(timer); }
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
        timer = setTimeout(fail, o.timeout || 25000); // slow mobile networks need time; a silent player after 25 s gets the fallback
        return { fail: fail, stop: stop };
    }

    window.SkyVideoGuard = { watch: watch, showFallback: showFallback };
})();
