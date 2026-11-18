{{--
    5-year seal: canvas burst + pause control. One engine for both variants.
    The burst is synced to the seal's CSS loop (the "anniv5-five" keyframe, impact at 50% = 4.0s),
    so CSS and canvas never drift. Off-screen or hidden tab: the CSS loop pauses and the canvas stops.
    Reduced motion or no JS: no canvas; the CSS keeps its still / CSS-only fallback.

    Budgets are large (thousands of sparks on a desktop homepage). Each seal keeps the page responsive
    on its own: it times its frames during every burst and scales the next burst down when the device
    struggles (or back up when it copes), so a slow phone settles on what it can draw smoothly.
--}}
<script>
(function () {
    var seals = Array.prototype.slice.call(document.querySelectorAll('[data-anniv5-seal]'));
    if (!seals.length) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var small = window.matchMedia && window.matchMedia('(max-width: 575.98px), (pointer: coarse)').matches;
    // Palette by index: cyan, blue, violet, magenta, gold, white.
    var COLORS = ['#22D3EE', '#3B82F6', '#8B5CF6', '#EC4899', '#F5C451', '#FFFFFF'];
    var MAIN = [0, 1, 2, 3, 4], ECHO = [4, 5, 4, 0, 3];
    // Cycle moments (ms in the 8s loop): anticipation, impact, glitter window, quiet after.
    var GATHER = 3300, IMPACT = 4000, GLITTER_FROM = 4250, GLITTER_TO = 6200, QUIET = 7700;

    // Full-quality budgets per variant and device; the live cap includes crackle and glitter.
    var CONFIG = {
        feature: small
            ? { primary: 750, echo: 300, crackle: 0.2, crackleEach: 2, glitter: 240, inward: 10, cap: 1500, rings: 3, speed: 2.5, streak: 2.2, reach: 0.65, side: 0.7 }
            : { primary: 2200, echo: 900, crackle: 0.3, crackleEach: 2, glitter: 700, inward: 16, cap: 4200, rings: 3, speed: 2.6, streak: 2.6, reach: 0.8, side: 0.85 },
        compact: small
            ? { primary: 300, echo: 120, crackle: 0.15, crackleEach: 2, glitter: 100, inward: 5, cap: 650, rings: 2, speed: 2.1, streak: 1.5 }
            : { primary: 500, echo: 200, crackle: 0.2, crackleEach: 2, glitter: 160, inward: 6, cap: 1100, rings: 2, speed: 2.2, streak: 1.7 }
    };
    // Starting quality (share of the budget): phones and low-core machines start lower, then adapt.
    var START_QUALITY = (small ? 0.3 : 0.6) * ((navigator.hardwareConcurrency || 8) <= 4 ? 0.6 : 1);
    // Per-frame work budget (ms) for this effect; above it the burst is thinned at once.
    var WORK_BUDGET = small ? 6 : 8;

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
        var dpr = Math.min(window.devicePixelRatio || 1, small ? 1.5 : 2); // phones: less pixel fill per frame
        var duration = anim.effect.getTiming().duration || 8000;
        var parts = [], rings = [], flashes = [], inward = [];
        var W = 0, H = 0, cx = 0, cy = 0, s = 0;
        var raf = 0, timer = 0, lastT = -1, lastNow = 0, running = false, glitterLeft = 0, glitterRate = 0;
        var quality = START_QUALITY, ceiling = 1, frameTimes = [], baseFrames = [], baseline = 16.7, slowRun = 0, spawnAt = 0;
        // Draw buckets reused every frame: [colour][fade level 1..4][thin|thick], glitter by fade level.
        var buckets = [], glitterBuckets = [[], [], [], [], []];
        for (var bi = 0; bi < COLORS.length * 10; bi++) buckets.push([]);

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
            // Never wider than the viewport (a burst layer must not make the page scroll sideways).
            left = Math.max(left, 0); right = Math.min(right, document.documentElement.clientWidth);
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
        function budget(n) { return Math.round(n * quality); }

        function add(x, y, vx, vy, life, r, ci, kind, crackle) {
            if (parts.length >= budget(cfg.cap)) return;
            parts.push({ x: x, y: y, vx: vx, vy: vy, age: 0, life: life, r: r, ci: ci, kind: kind, crackle: crackle, seed: rand(0, 6) });
        }

        function impact() {
            var k = s / 148; // tuned on the 148px homepage seal, scaled for the others
            // The page's own pace, measured during the anticipation just before this burst.
            if (baseFrames.length >= 5) baseline = Math.max(8, median(baseFrames));
            baseFrames.length = 0;
            flashes.push({ kind: 'bloom', age: 0, life: 0.38, r: s * 0.62 });
            flashes.push({ kind: 'streak', age: 0, life: 0.5, r: s * cfg.streak / 2 });
            for (var i = 0; i < cfg.rings; i++) {
                rings.push({ age: -i * 0.08, life: 0.75 + i * 0.18, r0: s * 0.2, r1: s * (variant === 'feature' ? 1.15 - i * 0.18 : 0.78 - i * 0.12), w: (3.2 - i * 0.8) * Math.max(k, 0.55), ci: i });
            }
            spray(budget(cfg.primary), MAIN, function () { return s * cfg.speed * Math.pow(Math.random(), 0.55); });
            glitterLeft = budget(cfg.glitter);
            glitterRate = glitterLeft / ((GLITTER_TO - GLITTER_FROM) / 1000);
            frameTimes.length = 0;
        }

        // Second bang a beat later: a wider, more even ring of mostly gold and white sparks.
        function echo() { spray(budget(cfg.echo), ECHO, function () { return s * cfg.speed * rand(0.75, 1.05); }); }

        function spray(count, palette, speed) {
            // Many sparks: finer strokes so the burst stays sparkly rather than a solid blob.
            var k = Math.max(s / 148, 0.6) * (count > 400 ? 0.7 : 1);
            for (var j = 0; j < count; j++) {
                var a = rand(0, Math.PI * 2), v = speed();
                add(cx, cy, Math.cos(a) * v, Math.sin(a) * v, rand(1.4, 2.4), rand(1.2, 2.8) * k, palette[j % palette.length], 0, Math.random() < cfg.crackle);
            }
        }

        function gather() {
            for (var i = 0; i < cfg.inward; i++) {
                inward.push({ a: (i / cfg.inward) * Math.PI * 2 + rand(-0.2, 0.2), r0: s * rand(0.85, 1.05), ci: i % 5 });
            }
        }

        // kind: 0 = spark, 1 = crackle, 2 = glitter. Dead particles are swap-removed (no array shifting).
        function step(dt, t) {
            var drag = Math.exp(-1.7 * dt), g = s * 0.55, sway = s * 0.06 * dt;
            for (var i = parts.length - 1; i >= 0; i--) {
                var p = parts[i];
                p.age += dt;
                if (p.age >= p.life) { parts[i] = parts[parts.length - 1]; parts.pop(); continue; }
                if (p.kind === 2) { p.y += p.vy * dt; p.x += Math.sin((p.age + p.seed) * 2.2) * sway; continue; }
                p.vx *= drag; p.vy = p.vy * drag + (p.kind === 1 ? g * 0.6 : g) * dt;
                p.x += p.vx * dt; p.y += p.vy * dt;
                // Secondary crackle: small sparks pop near the end of some trails.
                if (p.crackle && p.age > p.life * 0.62) {
                    p.crackle = false;
                    for (var c = 0; c < cfg.crackleEach; c++) {
                        var a = rand(0, Math.PI * 2), v = s * rand(0.25, 0.6);
                        add(p.x, p.y, Math.cos(a) * v, Math.sin(a) * v, rand(0.25, 0.45), p.r * 0.7, c % 2 ? 5 : 4, 1, false);
                    }
                }
            }
            // Gold glitter drifting down while "5 YEARS" settles.
            if (t >= GLITTER_FROM && t < GLITTER_TO && glitterLeft > 0) {
                var n = Math.min(glitterLeft, Math.floor(glitterRate * dt + Math.random()));
                for (var gi = 0; gi < n; gi++) {
                    glitterLeft--;
                    add(cx + rand(-s * 0.8, s * 0.8), cy - s * rand(0.3, 0.95), 0, s * rand(0.14, 0.24), rand(1.6, 2.4), rand(0.6, 1.4), 4, 2, false);
                }
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
                ctx.lineWidth = 1.6;
                for (var i = 0; i < inward.length; i++) {
                    var n = inward[i], r = n.r0 * (1 - e), tail = s * 0.16 * (0.3 + q);
                    ctx.globalAlpha = 0.25 + 0.6 * q; ctx.strokeStyle = COLORS[n.ci];
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
            for (var rr = 0; rr < rings.length; rr++) {
                var rg = rings[rr]; if (rg.age < 0) continue;
                var p = rg.age / rg.life, ease = 1 - Math.pow(1 - p, 3);
                ctx.globalAlpha = 0.85 * Math.pow(1 - p, 1.4); ctx.strokeStyle = COLORS[rg.ci]; ctx.lineWidth = Math.max(0.4, rg.w * (1 - p));
                ctx.beginPath(); ctx.arc(cx, cy, rg.r0 + (rg.r1 - rg.r0) * ease, 0, 6.2832); ctx.stroke();
            }
            // Particles: sorted into reused buckets, then one path per bucket (~60 draw calls whatever the count).
            for (var c0 = 0; c0 < buckets.length; c0++) buckets[c0].length = 0;
            for (var g0 = 0; g0 < 5; g0++) glitterBuckets[g0].length = 0;
            var thick = 2 * Math.max(s / 148, 0.6);
            // Keep the gold 5 readable: sparks fade in only once they leave the numeral's zone, and
            // each spark is dimmer when thousands overlap (additive blending would otherwise glare white).
            var inner = s * 0.26, outer = s * 0.48, inner2 = inner * inner, span = outer - inner;
            var density = Math.min(1, Math.sqrt(400 / Math.max(400, parts.length)) * 1.6);
            for (var j = 0; j < parts.length; j++) {
                var pt = parts[j], life = 1 - pt.age / pt.life;
                if (pt.kind === 2) {
                    var ga = Math.min(1, life * 1.6) * (0.6 + 0.4 * Math.sin((pt.age + pt.seed) * 5));
                    glitterBuckets[Math.min(4, Math.ceil(ga * 4))].push(pt);
                } else {
                    var dx = pt.x - cx, dy = pt.y - cy, d2 = dx * dx + dy * dy;
                    if (d2 < inner2) continue;
                    var zone = d2 >= outer * outer ? 1 : (Math.sqrt(d2) - inner) / span;
                    var level = Math.min(4, Math.ceil(Math.pow(life, 1.3) * zone * density * 4));
                    buckets[pt.ci * 10 + level * 2 + (pt.r > thick ? 1 : 0)].push(pt);
                }
            }
            ctx.lineCap = 'butt'; // flat ends: much cheaper than round caps at thousands of strokes
            var widths = [1.4 * Math.max(s / 148, 0.6), 2.6 * Math.max(s / 148, 0.6)];
            for (var bk = 0; bk < buckets.length; bk++) {
                var list = buckets[bk]; if (!list.length) continue;
                ctx.globalAlpha = ((bk % 10) >> 1) / 4;
                ctx.strokeStyle = COLORS[(bk / 10) | 0];
                ctx.lineWidth = widths[bk & 1];
                ctx.beginPath();
                for (var p2 = 0; p2 < list.length; p2++) { var q3 = list[p2]; ctx.moveTo(q3.x, q3.y); ctx.lineTo(q3.x - q3.vx * 0.07, q3.y - q3.vy * 0.07); }
                ctx.stroke();
            }
            ctx.fillStyle = COLORS[4];
            for (var gl = 1; gl < 5; gl++) {
                var gls = glitterBuckets[gl]; if (!gls.length) continue;
                ctx.globalAlpha = gl / 4;
                ctx.beginPath();
                for (var g2 = 0; g2 < gls.length; g2++) { var q2 = gls[g2]; ctx.rect(q2.x - q2.r, q2.y - q2.r, q2.r * 2, q2.r * 2); }
                ctx.fill();
            }
            ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';
        }

        // The burst is judged by how much it slows frames compared with the page's own pace just
        // before it (measured during the anticipation), so a page that already runs slowly is not
        // blamed on the burst, and drawing cost the browser pays later (rasterising) is counted.
        function median(list) { var a = list.slice().sort(function (x, y) { return x - y; }); return a[a.length >> 1]; }

        // After each burst: noticeable slowdown -> smaller next burst; none -> grow towards full.
        function adapt() {
            if (frameTimes.length < 20) return;
            var sorted = frameTimes.slice().sort(function (a, b) { return a - b; });
            var ratio = sorted[Math.floor(sorted.length * 0.9)] / baseline;
            // A level that proved too heavy becomes a ceiling, so the burst never probes back into jank.
            if (ratio > 1.45) { ceiling = Math.max(0.03, quality * 0.9); quality = Math.max(0.03, quality * (ratio > 2.2 ? 0.5 : 0.75)); }
            else if (ratio < 1.15) quality = Math.min(ceiling, quality * 1.35);
            frameTimes.length = 0;
        }

        // Instant guard: a costly frame (long JS work, or a long gap between frames) thins the live
        // burst right away, so the page stays responsive even during the very first burst.
        function guard(work, elapsed, now) {
            // Ignore the frames right after sparks are created and one-off hiccups (e.g. garbage
            // collection): act on two slow frames in a row.
            var slow = parts.length && now - spawnAt > 60 && (work > WORK_BUDGET || elapsed > Math.max(34, baseline * 2.2));
            slowRun = slow ? slowRun + 1 : 0;
            if (slowRun < 2) return;
            slowRun = 0;
            parts.length = Math.floor(parts.length * 0.65);
            glitterLeft = Math.floor(glitterLeft * 0.5);
            quality = Math.max(0.03, quality * 0.8);
        }

        function frame(now) {
            raf = 0;
            if (!running) return;
            var t = cycleTime();
            var elapsed = lastNow ? now - lastNow : 16.7;
            var dt = Math.min(elapsed / 1000, 0.05);
            lastNow = now;
            if (lastT >= 0) {
                if (crossed(lastT, t, GATHER)) gather();
                if (crossed(lastT, t, IMPACT)) { inward.length = 0; impact(); spawnAt = now; }
                if (crossed(lastT, t, IMPACT + 300)) { echo(); spawnAt = now; }
            }
            lastT = t;
            var w0 = performance.now();
            step(dt, t);
            draw(t);
            var work = performance.now() - w0;
            if (parts.length) frameTimes.push(elapsed);
            else if (t >= GATHER && t < IMPACT) baseFrames.push(elapsed);
            guard(work, elapsed, now);
            var busy = parts.length || rings.length || flashes.length || (t >= GATHER - 150 && t < QUIET);
            if (busy) raf = requestAnimationFrame(frame);
            else { ctx.clearRect(0, 0, W, H); inward.length = 0; adapt(); schedule(t); }
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
                parts.length = rings.length = flashes.length = inward.length = frameTimes.length = baseFrames.length = 0;
                ctx.clearRect(0, 0, W, H);
            },
            relayout: function () { if (running) layout(); },
            particleCount: function () { return parts.length; },
            quality: function () { return quality; }
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
