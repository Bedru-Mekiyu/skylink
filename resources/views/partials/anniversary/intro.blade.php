{{--
    5-year entry intro: ignition, rocket launch, 1-4 countdown, build-up, gold 5 detonation (five-colour
    burst, shockwaves, chain bursts, rays, glitter), headline, fade into the site. Rendered `hidden`:
    without JS, on repeat views in the same browser session, or with reduced motion it never shows.
    Placed right after <body> so it paints before the page; the page keeps loading underneath.
--}}
<div id="anniv5-intro" class="anniv5-intro" role="dialog" aria-modal="true" aria-label="{{ __('anniversary.intro_label') }}" hidden>
    <button type="button" class="anniv5-intro__skip" aria-label="{{ __('anniversary.close') }}"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
    <div class="anniv5-intro__world" aria-hidden="true">
        <canvas class="anniv5-intro__sky"></canvas>
        <div class="anniv5-intro__core"></div>
        <div class="anniv5-intro__bloom"></div>
        <div class="anniv5-intro__rays"></div>
        <div class="anniv5-intro__flare"></div>
        @for ($i = 0; $i < 4; $i++)
            <div class="anniv5-intro__ring" data-ring="{{ $i }}"></div>
        @endfor
        <div class="anniv5-intro__rocket"></div>
    </div>
    <div class="anniv5-intro__dim" aria-hidden="true"></div>
    <div class="anniv5-intro__stage">
        @foreach ([1, 2, 3, 4, 5] as $n)
            <span class="anniv5-intro__n" data-n="{{ $n }}" aria-hidden="true">{{ $n }}</span>
        @endforeach
        <div class="anniv5-intro__title">
            <p class="anniv5-intro__kicker">{{ __('anniversary.kicker') }}</p>
            <h2 class="anniv5-intro__headline">
                @foreach (preg_split('/\s+/u', trim(__('anniversary.headline'))) as $i => $word)
                    <span class="anniv5-intro__w" style="--i: {{ $i }}">{{ $word }}</span>
                @endforeach
            </h2>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('anniv5-intro');
    if (!root) return;

    var KEY = 'skylink.anniv5.introSeen';
    var seen = false;
    try { seen = sessionStorage.getItem(KEY) === '1'; sessionStorage.setItem(KEY, '1'); } catch (e) {}
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var world = root.querySelector('.anniv5-intro__world');
    var canvas = root.querySelector('canvas');
    var ctx = canvas.getContext && canvas.getContext('2d');
    if (seen || reduce || !ctx || !window.requestAnimationFrame) { root.remove(); return; }

    // Timeline in ms (virtual clock). Countdown digits are 300ms each.
    //   0-1.6s ignition and climb | 1.6-2.8s 1,2,3,4 | 2.8-3.3s build-up (light gathers, screen dims)
    //   3.3s detonation (5 punches in, bloom, shake, main burst, rings) | 3.5-4.1s chain bursts
    //   4.3s kicker + headline rise in | 6.2s fade out | 6.8s done
    var T = { launch: 600, apex: 1600, count: 1600, build: 2800, boom: 3300, title: 4300, out: 6200, end: 6800 };
    var COLORS = ['#22D3EE', '#3B82F6', '#8B5CF6', '#EC4899', '#F5C451'];
    var rocket = root.querySelector('.anniv5-intro__rocket');
    var core = root.querySelector('.anniv5-intro__core');
    var rings = root.querySelectorAll('.anniv5-intro__ring');
    var parts = [], W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var elapsed = 0, last = 0, raf = 0, phase = '', done = false, timers = [];
    var sprites = null, q = 1, slowRun = 0, ema = 16.7, probeSum = 0, probeN = 0, probed = false;

    // ---- Adaptive quality: q scales every particle count (1 = strong desktop, ~0.45 mid phone, ~0.2 weak).
    function initialQuality() {
        var cores = navigator.hardwareConcurrency || 4, mem = navigator.deviceMemory || 4;
        var small = Math.min(window.innerWidth, window.innerHeight) < 700;
        var v = cores >= 8 ? 1 : cores >= 6 ? 0.8 : cores >= 4 ? 0.55 : 0.3;
        if (mem <= 2) v = Math.min(v, 0.25); else if (mem <= 4) v = Math.min(v, 0.7);
        if (small) v = Math.min(v, 0.5);
        if (window.innerWidth * window.innerHeight * dpr * dpr > 5000000) v = Math.min(v, 0.7); // very large canvases
        return v;
    }
    q = initialQuality();

    function size() {
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function rand(a, b) { return a + Math.random() * (b - a); }

    // ---- Pre-rendered glow sprites (cheap drawImage per particle): sharp for the background layer,
    // soft and larger for the foreground layer, which reads as depth of field without any blur filter.
    function makeSprites() {
        sprites = COLORS.map(function (c) {
            var out = {};
            [['sharp', 16, 0.5], ['soft', 48, 0.12]].forEach(function (d) {
                var cv = document.createElement('canvas'), s = d[1];
                cv.width = cv.height = s;
                var g = cv.getContext('2d'), grad = g.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
                grad.addColorStop(0, c);
                grad.addColorStop(d[2], c);
                grad.addColorStop(d[0] === 'sharp' ? 0.7 : 1, 'rgba(0,0,0,0)');
                g.fillStyle = grad; g.fillRect(0, 0, s, s);
                out[d[0]] = cv;
            });
            return out;
        });
    }

    var MAX = 2800;
    function add(o) { if (parts.length < MAX * q + 200) parts.push(o); }
    function trail(x, y, n, spread, hot) {
        for (var i = 0; i < n; i++) {
            add({ x: x + rand(-spread, spread), y: y, vx: rand(-0.5, 0.5), vy: rand(1, 3 + hot * 3), g: 0, d: 1,
                life: 1, decay: rand(0.025, 0.05), r: rand(1.4, 2.6 + hot * 2.2), z: 0.3, c: i % 3 ? 4 : 0, a: 0.9, sp: 0 });
        }
    }
    // Main burst. Speeds scale with the screen so the cloud reaches every edge; z = depth layer.
    function burst(x, y, n, S, opt) {
        opt = opt || {};
        n = Math.max(8, Math.round(n * q));
        for (var i = 0; i < n; i++) {
            var a = rand(0, 6.2832), s = S * (0.12 + Math.pow(Math.random(), 0.7) * 0.88), z = Math.random();
            add({ x: x, y: y, vx: Math.cos(a) * s * (0.6 + z * 0.6), vy: Math.sin(a) * s * (0.6 + z * 0.6),
                g: opt.g == null ? 0.045 : opt.g, d: rand(0.978, 0.988), life: 1, decay: rand(opt.fast ? 0.014 : 0.008, opt.fast ? 0.026 : 0.017),
                r: 0.8 + z * 2.6, z: z, c: (i + (opt.shift || 0)) % 5, a: 0.55 + Math.random() * 0.35, sp: Math.random() < 0.09 ? 1 : 0 });
        }
    }
    function gather(x, y, n) {            // build-up: light is pulled into the core
        n = Math.round(n * q);
        for (var i = 0; i < n; i++) {
            var a = rand(0, 6.2832), dist = rand(Math.min(W, H) * 0.12, Math.min(W, H) * 0.4), life = rand(22, 34);
            add({ x: x + Math.cos(a) * dist, y: y + Math.sin(a) * dist, vx: -Math.cos(a) * dist / life, vy: -Math.sin(a) * dist / life,
                g: 0, d: 1, life: 1, decay: 1 / life, r: rand(1, 2.2), z: 0.2, c: i % 5, a: 0.8, sp: 0 });
        }
    }
    function glitter(n) {                 // slow gold confetti behind the headline
        n = Math.max(1, Math.round(n * q));
        for (var i = 0; i < n; i++) {
            add({ x: rand(0, W), y: rand(-20, H * 0.25), vx: rand(-0.3, 0.3), vy: rand(0.5, 1.5), g: 0.002, d: 0.999,
                life: 1, decay: rand(0.0035, 0.006), r: rand(1.2, 2.6), z: Math.random() * 0.6, c: 4, a: 0.9, sp: 0, tw: rand(0, 6.28) });
        }
    }
    function setPhase(p) { if (p !== phase) { phase = p; root.setAttribute('data-phase', p); } }
    function run(el, cls) { el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); }

    // ---- One-shot events on the virtual clock (so a busy main thread delays them with the rest).
    var cx0 = 0, apexY0 = 0, S = 0;
    var events = [];
    function schedule() {
        cx0 = W / 2; apexY0 = H * 0.36; S = Math.hypot(W, H) * 0.0125;
        var chain = [[0.18, 0.22], [0.82, 0.28], [0.3, 0.74], [0.72, 0.7], [0.5, 0.12], [0.12, 0.55]];
        events = [
            { at: T.build, fn: function () { root.classList.add('is-build'); } },
            { at: T.boom, fn: function () {
                root.classList.add('is-boom');
                burst(cx0, apexY0, 1200, S);
                burst(cx0, apexY0, 160, S * 0.45, { g: 0.02, shift: 2 });
            } }
        ];
        rings.forEach(function (ring, i) { events.push({ at: T.boom + i * 150, fn: function () { run(ring, 'go'); } }); });
        chain.forEach(function (c, i) {
            events.push({ at: T.boom + 200 + i * 120 + (i % 2) * 60, fn: function () {
                burst(W * c[0], H * c[1], 150, S * 0.55, { shift: i, fast: true });
            } });
        });
        events.sort(function (a, b) { return a.at - b.at; });
    }

    function frame(now) {
        if (done) return;
        // Virtual clock: a frame advances at most 50ms, so a busy main thread (page still
        // parsing) slows the sequence down instead of skipping countdown beats.
        if (!last) { last = now; }
        var raw = now - last, dt = Math.min(raw, 50), k = dt / 16.67;
        last = now;
        elapsed += dt;
        var t = elapsed;

        // Measure frame time during the first second, then keep a running average.
        if (raw > 0) ema = ema * 0.9 + Math.min(raw, 100) * 0.1;
        if (!probed) {
            probeSum += Math.min(raw, 100); probeN++;
            if (t > 1000 && probeN > 10) {
                probed = true;
                var avg = probeSum / probeN;
                if (avg > 30) q = Math.min(q, 0.2); else if (avg > 22) q = Math.min(q, 0.4); else if (avg > 18.5) q = Math.min(q, 0.65);
            }
        }
        // Below 50fps for a stretch of frames: cut the particle budget and drop the oldest ones.
        if (t > T.boom && ema > 20) {
            if (++slowRun > 12 && q > 0.12) { q = Math.max(0.12, q * 0.72); parts.splice(0, Math.floor(parts.length * 0.3)); slowRun = 0; ema = 17; }
        } else { slowRun = 0; }

        var cx = cx0, apexY = apexY0;
        if (t < T.apex) {
            var p = t < T.launch ? 0 : Math.pow((t - T.launch) / (T.apex - T.launch), 1.8);
            var y = H + 30 - (H + 30 - apexY) * p;
            rocket.style.opacity = t < T.launch ? String(t / T.launch) : '1';
            rocket.style.transform = 'translate3d(-50%,' + (y - 23) + 'px,0)';
            // The trail grows hotter and thicker as the rocket nears its apex.
            trail(cx, t < T.launch ? H - 6 : y + 28, t < T.launch ? 2 : 4, t < T.launch ? 14 : 3, t < T.launch ? 0 : p);
        } else if (rocket.style.opacity !== '0') {
            rocket.style.opacity = '0';
        }

        if (t < T.count) setPhase('launch');
        else if (t < T.build) setPhase('n' + (1 + Math.min(3, Math.floor((t - T.count) / 300))));
        else if (t < T.boom) setPhase('build');
        else if (t < T.title) setPhase('five');
        else if (t < T.out) setPhase('title');
        else setPhase('out');

        // Core glow: a faint ember during the countdown, gathering hard in the 0.5s build-up.
        if (t >= T.apex && t < T.boom) {
            var b = t < T.build ? 0.18 * (t - T.apex) / (T.build - T.apex) : 0.18 + 0.82 * Math.pow((t - T.build) / (T.boom - T.build), 1.5);
            core.style.opacity = String(b);
            core.style.transform = 'translate(-50%,-50%) scale(' + (t < T.build ? 1.6 : 1.6 - 1.1 * (b - 0.18) / 0.82) + ')';
            if (t >= T.build) gather(cx, apexY, 7);
        }

        while (events.length && t >= events[0].at) events.shift().fn();
        if (t >= T.boom + 200 && t < T.out - 400 && Math.random() < 0.55) glitter(3);

        // Particles on the persistent canvas: fading the previous frame (instead of clearing) leaves
        // glowing trails behind every particle for free.
        ctx.globalCompositeOperation = 'destination-out';
        ctx.fillStyle = 'rgba(0,0,0,' + Math.min(0.5, 0.2 * k + 0.04) + ')';
        ctx.fillRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'lighter';
        var drag;
        for (var i = parts.length - 1; i >= 0; i--) {
            var o = parts[i];
            drag = Math.pow(o.d, k);
            o.x += o.vx * k; o.y += o.vy * k; o.vy += o.g * k; o.vx *= drag; o.vy *= drag; o.life -= o.decay * k;
            if (o.life <= 0) { parts[i] = parts[parts.length - 1]; parts.pop(); continue; }
            // Crackling sparkles shed from the tips of burst trails as they die out.
            if (o.sp && o.life < 0.6 && Math.random() < 0.12 && parts.length < MAX * q) {
                parts.push({ x: o.x, y: o.y, vx: rand(-1.2, 1.2), vy: rand(-1.2, 1.2), g: 0.02, d: 0.95, life: 1, decay: rand(0.06, 0.12),
                    r: rand(0.8, 1.6), z: 0.1, c: 4, a: 0.95, sp: 0 });
            }
            var spr = sprites[o.c], big = o.z > 0.62, img = big ? spr.soft : spr.sharp, s = o.r * (big ? 5 : 3);
            var al = o.life * o.a;
            if (o.tw != null) { al *= 0.55 + 0.45 * Math.sin(o.tw += 0.25 * k); }
            ctx.globalAlpha = al > 1 ? 1 : al;
            ctx.drawImage(img, o.x - s, o.y - s, s * 2, s * 2);
        }
        ctx.globalAlpha = 1;
        ctx.globalCompositeOperation = 'source-over';

        if (t >= T.end) { finish(); return; }
        raf = window.requestAnimationFrame(frame);
    }

    function onKey(e) { if (e.key === 'Escape') finish(); }
    function onVisibility() { if (document.hidden) finish(); }
    function onResize() { size(); schedule(); events = events.filter(function (e) { return e.at > elapsed; }); }
    function finish() {
        if (done) return;
        done = true;
        window.cancelAnimationFrame(raf);
        window.removeEventListener('resize', onResize);
        document.removeEventListener('keydown', onKey);
        document.removeEventListener('visibilitychange', onVisibility);
        root.querySelector('.anniv5-intro__skip').removeEventListener('click', finish);
        root.classList.add('is-leaving');
        // Release everything now: particles, sprites and the canvas bitmap.
        parts.length = 0; events.length = 0; sprites = null;
        canvas.width = canvas.height = 0;
        timers.push(window.setTimeout(function () { root.remove(); }, 600));
    }

    root.hidden = false;
    size();
    makeSprites();
    schedule();
    window.addEventListener('resize', onResize);
    document.addEventListener('keydown', onKey);
    document.addEventListener('visibilitychange', onVisibility);
    root.querySelector('.anniv5-intro__skip').addEventListener('click', finish);
    raf = window.requestAnimationFrame(frame);
})();
</script>
