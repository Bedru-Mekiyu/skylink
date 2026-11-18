{{--
    5-year seal: canvas burst + pause control. One engine for both variants.
    The burst is synced to the seal's CSS loop (the "anniv5-five" keyframe, impact at 50% = 4.0s),
    so CSS and canvas never drift. Off-screen or hidden tab: the CSS loop pauses and the canvas stops.
    Reduced motion or no JS: no canvas; the CSS keeps its still / CSS-only fallback.
--}}
<script>
(function () {
    var seals = Array.prototype.slice.call(document.querySelectorAll('[data-anniv5-seal]'));
    if (!seals.length) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var small = window.matchMedia && window.matchMedia('(max-width: 575.98px), (pointer: coarse)').matches;
    var COLORS = ['#22D3EE', '#3B82F6', '#8B5CF6', '#EC4899', '#F5C451'];
    // Cycle moments (ms in the 8s loop): anticipation, impact, glitter window, quiet after.
    var GATHER = 3300, IMPACT = 4000, GLITTER_FROM = 4250, GLITTER_TO = 6200, QUIET = 7700;

    // Particle budgets per variant and device (live cap includes crackle and glitter).
    var CONFIG = {
        feature: small
            ? { primary: 64, crackle: 0.25, crackleEach: 2, glitter: 20, inward: 6, cap: 80, rings: 3, speed: 2.5, streak: 2.2, reach: 0.65, side: 0.7 }
            : { primary: 140, crackle: 0.3, crackleEach: 3, glitter: 46, inward: 10, cap: 180, rings: 3, speed: 2.6, streak: 2.6, reach: 0.8, side: 0.85 },
        compact: small
            ? { primary: 18, crackle: 0, crackleEach: 0, glitter: 6, inward: 4, cap: 26, rings: 2, speed: 2.1, streak: 1.5 }
            : { primary: 30, crackle: 0.15, crackleEach: 2, glitter: 10, inward: 5, cap: 44, rings: 2, speed: 2.2, streak: 1.7 }
    };

    function rand(a, b) { return a + Math.random() * (b - a); }
    function crossed(prev, now, at) { return prev <= now ? (prev < at && at <= now) : (at > prev || at <= now); }

    function Burst(seal) {
        var variant = seal.classList.contains('anniv5--feature') ? 'feature' : 'compact';
        var cfg = CONFIG[variant];
        var mark = seal.querySelector('.anniv5-mark');
        var five = seal.querySelector('.anniv5-mark__n--5');
        var anim = five && five.getAnimations && five.getAnimations().filter(function (a) { return a.animationName === 'anniv5-five'; })[0];
        if (!mark || !anim) return null; // CSS-only fallback stays

        var canvas = document.createElement('canvas');
        canvas.className = 'anniv5__fx';
        canvas.setAttribute('aria-hidden', 'true');
        seal.appendChild(canvas);
        seal.classList.add('has-fx');
        var ctx = canvas.getContext('2d');
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        var duration = anim.effect.getTiming().duration || 8000;
        var parts = [], rings = [], flashes = [], inward = [];
        var W = 0, H = 0, cx = 0, cy = 0, s = 0;
        var raf = 0, timer = 0, lastT = -1, lastNow = 0, running = false, glitterLeft = 0;

        // Place the canvas: homepage = the seal plus the empty space above and beside it (never over
        // the copy below or outside the panel column); strip = left part of the strip, clear of the text.
        function layout() {
            var host = seal.getBoundingClientRect(), m = mark.getBoundingClientRect();
            if (!m.width) return false;
            s = m.width;
            var left, top, right, bottom;
            if (variant === 'feature') {
                left = Math.max(host.left, m.left - s * cfg.side);
                right = Math.min(host.right, m.right + s * cfg.side);
                top = m.top - s * cfg.reach;
                bottom = m.bottom + 2;
            } else {
                left = host.left;
                right = m.right + 10;
                top = host.top;
                bottom = host.bottom;
            }
            W = right - left; H = bottom - top;
            canvas.style.left = (left - host.left) + 'px';
            canvas.style.top = (top - host.top) + 'px';
            canvas.style.width = W + 'px';
            canvas.style.height = H + 'px';
            canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            cx = m.left + s / 2 - left; cy = m.top + s / 2 - top;
            return true;
        }

        function cycleTime() { var t = anim.currentTime || 0; return ((t % duration) + duration) % duration; }

        function impact() {
            var k = s / 148; // tuned on the 148px homepage seal, scaled for the others
            flashes.push({ kind: 'bloom', age: 0, life: 0.38, r: s * 0.62 });
            flashes.push({ kind: 'streak', age: 0, life: 0.5, r: s * cfg.streak / 2 });
            var ringColors = ['#22D3EE', '#3B82F6', '#8B5CF6'];
            for (var i = 0; i < cfg.rings; i++) {
                rings.push({ age: -i * 0.08, life: 0.75 + i * 0.18, r0: s * 0.2, r1: s * (variant === 'feature' ? 1.15 - i * 0.18 : 0.78 - i * 0.12), w: (3.2 - i * 0.8) * Math.max(k, 0.55), c: ringColors[i] });
            }
            for (var j = 0; j < cfg.primary && parts.length < cfg.cap; j++) {
                var a = rand(0, Math.PI * 2), v = s * cfg.speed * Math.pow(Math.random(), 0.55);
                parts.push({ x: cx, y: cy, vx: Math.cos(a) * v, vy: Math.sin(a) * v, age: 0, life: rand(1.4, 2.4),
                    r: rand(1.4, 3) * Math.max(k, 0.6), c: COLORS[j % 5], crackle: Math.random() < cfg.crackle, popped: false, kind: 'p' });
            }
            glitterLeft = cfg.glitter;
        }

        function gather() {
            for (var i = 0; i < cfg.inward; i++) {
                inward.push({ a: (i / cfg.inward) * Math.PI * 2 + rand(-0.2, 0.2), r0: s * rand(0.85, 1.05), c: COLORS[i % 5] });
            }
        }

        function step(dt, t) {
            var drag = Math.exp(-1.7 * dt), g = s * 0.55;
            for (var i = parts.length - 1; i >= 0; i--) {
                var p = parts[i];
                p.age += dt;
                if (p.age >= p.life) { parts.splice(i, 1); continue; }
                if (p.kind === 'glitter') {
                    p.y += p.vy * dt; p.x += Math.sin((p.age + p.seed) * 2.2) * s * 0.06 * dt;
                    continue;
                }
                p.vx *= drag; p.vy = p.vy * drag + (p.kind === 'c' ? g * 0.6 : g) * dt;
                p.x += p.vx * dt; p.y += p.vy * dt;
                // Secondary crackle: small sparks pop near the end of some trails.
                if (p.crackle && !p.popped && p.age > p.life * 0.62) {
                    p.popped = true;
                    for (var c = 0; c < cfg.crackleEach && parts.length < cfg.cap; c++) {
                        var a = rand(0, Math.PI * 2), v = s * rand(0.25, 0.6);
                        parts.push({ x: p.x, y: p.y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, age: 0, life: rand(0.25, 0.45), r: p.r * 0.7, c: c % 2 ? '#FFFFFF' : '#F5C451', kind: 'c' });
                    }
                }
            }
            // Gold glitter drifting down while "5 YEARS" settles.
            if (t >= GLITTER_FROM && t < GLITTER_TO && glitterLeft > 0 && Math.random() < dt * cfg.glitter / 1.6) {
                glitterLeft--;
                parts.push({ x: cx + rand(-s * 0.7, s * 0.7), y: cy - s * rand(0.35, 0.85), vy: s * rand(0.14, 0.24), age: 0, life: rand(1.6, 2.4), r: rand(0.7, 1.5), c: '#F5C451', kind: 'glitter', seed: rand(0, 6) });
            }
            for (var r = rings.length - 1; r >= 0; r--) { rings[r].age += dt; if (rings[r].age >= rings[r].life) rings.splice(r, 1); }
            for (var f = flashes.length - 1; f >= 0; f--) { flashes[f].age += dt; if (flashes[f].age >= flashes[f].life) flashes.splice(f, 1); }
        }

        function draw(t) {
            ctx.clearRect(0, 0, W, H);
            ctx.globalCompositeOperation = 'lighter';
            // Anticipation: light streaks race inward and a core glow gathers.
            if (t >= GATHER && t < IMPACT && inward.length) {
                var q = (t - GATHER) / (IMPACT - GATHER), e = Math.pow(q, 2.2);
                for (var i = 0; i < inward.length; i++) {
                    var n = inward[i], r = n.r0 * (1 - e), tail = s * 0.16 * (0.3 + q);
                    ctx.globalAlpha = 0.25 + 0.6 * q; ctx.strokeStyle = n.c; ctx.lineWidth = 1.6;
                    ctx.beginPath();
                    ctx.moveTo(cx + Math.cos(n.a) * r, cy + Math.sin(n.a) * r);
                    ctx.lineTo(cx + Math.cos(n.a) * (r + tail), cy + Math.sin(n.a) * (r + tail));
                    ctx.stroke();
                }
                var core = ctx.createRadialGradient(cx, cy, 0, cx, cy, s * 0.32);
                core.addColorStop(0, 'rgba(255,255,255,' + (0.35 * e) + ')'); core.addColorStop(1, 'rgba(59,130,246,0)');
                ctx.globalAlpha = 1; ctx.fillStyle = core; ctx.fillRect(cx - s, cy - s, s * 2, s * 2);
            }
            // Impact: one bloom + one anamorphic streak (a single soft flash per 8s loop).
            for (var f = 0; f < flashes.length; f++) {
                var fl = flashes[f], k = 1 - fl.age / fl.life;
                if (fl.kind === 'bloom') {
                    var b = ctx.createRadialGradient(cx, cy, 0, cx, cy, fl.r * (1 + 0.4 * (1 - k)));
                    b.addColorStop(0, 'rgba(255,244,214,' + (0.55 * k) + ')'); b.addColorStop(0.4, 'rgba(245,196,81,' + (0.25 * k) + ')'); b.addColorStop(1, 'rgba(139,92,246,0)');
                    ctx.globalAlpha = 1; ctx.fillStyle = b; ctx.fillRect(cx - fl.r * 1.5, cy - fl.r * 1.5, fl.r * 3, fl.r * 3);
                } else {
                    ctx.save(); ctx.translate(cx, cy); ctx.scale(1, 0.035);
                    var st = ctx.createRadialGradient(0, 0, 0, 0, 0, fl.r);
                    st.addColorStop(0, 'rgba(255,255,255,' + (0.8 * k) + ')'); st.addColorStop(0.35, 'rgba(34,211,238,' + (0.35 * k) + ')'); st.addColorStop(1, 'rgba(34,211,238,0)');
                    ctx.fillStyle = st; ctx.beginPath(); ctx.arc(0, 0, fl.r, 0, 6.2832); ctx.fill(); ctx.restore();
                }
            }
            // Shockwaves: different speeds, thinning and fading as they grow.
            for (var r = 0; r < rings.length; r++) {
                var rg = rings[r]; if (rg.age < 0) continue;
                var p = rg.age / rg.life, ease = 1 - Math.pow(1 - p, 3);
                ctx.globalAlpha = 0.85 * Math.pow(1 - p, 1.4); ctx.strokeStyle = rg.c; ctx.lineWidth = Math.max(0.4, rg.w * (1 - p));
                ctx.beginPath(); ctx.arc(cx, cy, rg.r0 + (rg.r1 - rg.r0) * ease, 0, 6.2832); ctx.stroke();
            }
            // Particles with short motion trails; glitter as twinkling points.
            for (var j = 0; j < parts.length; j++) {
                var pt = parts[j], life = 1 - pt.age / pt.life;
                ctx.strokeStyle = ctx.fillStyle = pt.c;
                if (pt.kind === 'glitter') {
                    ctx.globalAlpha = Math.min(1, life * 1.6) * (0.6 + 0.4 * Math.sin((pt.age + pt.seed) * 5));
                    ctx.beginPath(); ctx.arc(pt.x, pt.y, pt.r, 0, 6.2832); ctx.fill();
                    continue;
                }
                ctx.globalAlpha = Math.pow(life, 1.3); ctx.lineWidth = pt.r; ctx.lineCap = 'round';
                ctx.beginPath(); ctx.moveTo(pt.x, pt.y); ctx.lineTo(pt.x - pt.vx * 0.07, pt.y - pt.vy * 0.07); ctx.stroke();
            }
            ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';
        }

        function frame(now) {
            raf = 0;
            if (!running) return;
            var t = cycleTime();
            var dt = Math.min((now - (lastNow || now)) / 1000, 0.05);
            lastNow = now;
            if (lastT >= 0) {
                if (crossed(lastT, t, GATHER)) gather();
                if (crossed(lastT, t, IMPACT)) { inward.length = 0; impact(); }
            }
            lastT = t;
            step(dt, t);
            draw(t);
            var busy = parts.length || rings.length || flashes.length || (t >= GATHER - 150 && t < QUIET);
            if (busy) raf = requestAnimationFrame(frame);
            else { ctx.clearRect(0, 0, W, H); inward.length = 0; schedule(t); }
        }

        // Idle between bursts: one timer until the next anticipation, no animation frames.
        function schedule(t) {
            clearTimeout(timer);
            timer = setTimeout(function () { timer = 0; if (running) { lastNow = 0; raf = requestAnimationFrame(frame); } }, Math.max(0, ((GATHER - 100 - t) + duration) % duration));
        }

        return {
            start: function () {
                if (running) return;
                running = true;
                if (!layout()) { running = false; return; }
                lastT = -1; lastNow = 0;
                raf = requestAnimationFrame(frame);
            },
            stop: function () {
                running = false;
                if (raf) cancelAnimationFrame(raf); raf = 0;
                clearTimeout(timer); timer = 0;
                parts.length = rings.length = flashes.length = inward.length = 0;
                ctx.clearRect(0, 0, W, H);
            },
            relayout: function () { if (running) layout(); },
            particleCount: function () { return parts.length; }
        };
    }

    var fx = new Map();
    if (!reduce) {
        seals.forEach(function (seal) { var b = Burst(seal); if (b) fx.set(seal, b); });
        window.__anniv5Fx = Array.from(fx.values()); // read by tools/local/capture-burst.mjs
        if ('ResizeObserver' in window) {
            var ro = new ResizeObserver(function (entries) { entries.forEach(function (e) { var b = fx.get(e.target); if (b) b.relayout(); }); });
            fx.forEach(function (b, seal) { ro.observe(seal); });
        }
    }

    var visible = new WeakMap();
    function apply(seal) {
        var paused = document.hidden || visible.get(seal) === false;
        seal.classList.toggle('is-paused', paused);
        var b = fx.get(seal);
        if (b) { if (paused || !seal.offsetParent) b.stop(); else b.start(); }
    }

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { visible.set(e.target, e.isIntersecting); apply(e.target); });
        });
        seals.forEach(function (seal) { io.observe(seal); });
    } else {
        seals.forEach(apply);
    }
    document.addEventListener('visibilitychange', function () { seals.forEach(apply); });
})();
</script>
