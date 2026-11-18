{{--
    5-year entry intro: ignition, rocket launch, 1-4 countdown, gold 5, five-colour burst,
    "5 Years" title, fade into the site. Rendered `hidden`: without JS, on repeat views in the
    same browser session, or with reduced motion it never shows. Placed right after <body> so it
    paints before the page; the page keeps loading underneath.
--}}
<div id="anniv5-intro" class="anniv5-intro" role="dialog" aria-modal="true" aria-label="{{ __('anniversary.intro_label') }}" hidden>
    <button type="button" class="anniv5-intro__skip">{{ __('anniversary.skip') }}</button>
    <canvas class="anniv5-intro__sky" aria-hidden="true"></canvas>
    <div class="anniv5-intro__rocket" aria-hidden="true"></div>
    <div class="anniv5-intro__stage">
        @foreach ([1, 2, 3, 4, 5] as $n)
            <span class="anniv5-intro__n" data-n="{{ $n }}" aria-hidden="true">{{ $n }}</span>
        @endforeach
        <p class="anniv5-intro__title">
            <span class="anniv5-intro__big"><b>5</b> {{ __('anniversary.years') }}</span>
            <span class="anniv5-intro__kicker">Sky Link ICT Solutions</span>
        </p>
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
    var canvas = root.querySelector('canvas');
    var ctx = canvas.getContext && canvas.getContext('2d');
    if (seen || reduce || !ctx || !window.requestAnimationFrame) { root.remove(); return; }

    // Timeline in ms. Countdown digits are 300ms each; fully gone at about 5.2s.
    var T = { launch: 600, apex: 1600, count: 1600, five: 2800, title: 3450, out: 4650, end: 5200 };
    var COLORS = ['#22D3EE', '#3B82F6', '#8B5CF6', '#EC4899', '#F5C451'];
    var rocket = root.querySelector('.anniv5-intro__rocket');
    var parts = [], W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var elapsed = 0, last = 0, raf = 0, phase = '', bursts = 0, done = false;

    function size() {
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function rand(a, b) { return a + Math.random() * (b - a); }
    function trail(x, y, n, spread) {
        for (var i = 0; i < n; i++) {
            parts.push({ x: x + rand(-spread, spread), y: y, vx: rand(-0.5, 0.5), vy: rand(1, 3), g: 0,
                life: 1, decay: rand(0.025, 0.05), r: rand(1, 2.6), c: i % 3 ? '#F5C451' : '#22D3EE' });
        }
    }
    function burst(x, y, n, speed) {
        for (var i = 0; i < n; i++) {
            var a = rand(0, Math.PI * 2), s = rand(speed * 0.35, speed);
            parts.push({ x: x, y: y, vx: Math.cos(a) * s, vy: Math.sin(a) * s, g: 0.05,
                life: 1, decay: rand(0.007, 0.016), r: rand(1.2, 3), c: COLORS[i % 5] });
        }
    }
    function setPhase(p) { if (p !== phase) { phase = p; root.setAttribute('data-phase', p); } }

    function frame(now) {
        // Virtual clock: a frame advances at most 50ms, so a busy main thread (page still
        // parsing) slows the sequence down instead of skipping countdown beats.
        if (!last) { last = now; }
        var dt = Math.min(now - last, 50), k = dt / 16.67;
        last = now;
        elapsed += dt;
        var t = elapsed;
        var cx = W / 2, apexY = H * 0.36;

        if (t < T.apex) {
            var p = t < T.launch ? 0 : Math.pow((t - T.launch) / (T.apex - T.launch), 1.8);
            var y = H + 30 - (H + 30 - apexY) * p;
            rocket.style.opacity = t < T.launch ? String(t / T.launch) : '1';
            rocket.style.transform = 'translate3d(-50%,' + (y - 23) + 'px,0)';
            trail(cx, t < T.launch ? H - 6 : y + 28, t < T.launch ? 2 : 4, t < T.launch ? 14 : 3);
        } else if (rocket.style.opacity !== '0') {
            rocket.style.opacity = '0';
        }

        if (t < T.count) setPhase('launch');
        else if (t < T.five) setPhase('n' + (1 + Math.min(3, Math.floor((t - T.count) / 300))));
        else if (t < T.title) setPhase('five');
        else if (t < T.out) setPhase('title');
        else setPhase('out');

        if (bursts === 0 && t >= T.five) { burst(cx, apexY, 170, 8); bursts = 1; }
        if (bursts === 1 && t >= T.five + 280) { burst(cx - W * 0.22, apexY + H * 0.08, 70, 5); burst(cx + W * 0.22, apexY + H * 0.05, 70, 5); bursts = 2; }

        ctx.clearRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'lighter';
        for (var i = parts.length - 1; i >= 0; i--) {
            var q = parts[i];
            q.x += q.vx * k; q.y += q.vy * k; q.vy += q.g * k; q.vx *= 0.985; q.life -= q.decay * k;
            if (q.life <= 0) { parts.splice(i, 1); continue; }
            ctx.globalAlpha = q.life; ctx.fillStyle = q.c;
            ctx.beginPath(); ctx.arc(q.x, q.y, q.r, 0, 6.2832); ctx.fill();
        }
        ctx.globalAlpha = 1;

        if (t >= T.end) { finish(); return; }
        raf = window.requestAnimationFrame(frame);
    }

    function onKey(e) { if (e.key === 'Escape') finish(); }
    function onVisibility() { if (document.hidden) finish(); }
    function finish() {
        if (done) return;
        done = true;
        window.cancelAnimationFrame(raf);
        window.removeEventListener('resize', size);
        document.removeEventListener('keydown', onKey);
        document.removeEventListener('visibilitychange', onVisibility);
        root.classList.add('is-leaving');
        window.setTimeout(function () { root.remove(); }, 600);
    }

    root.hidden = false;
    size();
    window.addEventListener('resize', size);
    document.addEventListener('keydown', onKey);
    document.addEventListener('visibilitychange', onVisibility);
    root.querySelector('.anniv5-intro__skip').addEventListener('click', finish);
    raf = window.requestAnimationFrame(frame);
})();
</script>
