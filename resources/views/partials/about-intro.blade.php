@php($aiYears = (int) config('company.years_experience'))
{{-- About intro: floating card + calm text. Motion (rise, word reveal, count-up) is CSS transform/opacity only and is off for reduced motion, Save-Data and 2G (shown static). --}}
<section class="about-intro" id="about-intro" aria-labelledby="about-intro-headline">
    <script>
        (function (r) {
            var c = navigator.connection || {};
            var calm = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!calm && !c.saveData && !/(^|-)2g$/.test(c.effectiveType || '') && 'IntersectionObserver' in window) r.classList.add('ai-anim');
        })(document.documentElement);
    </script>
    <div class="about-intro__wrap">
        <div class="about-intro__card">
            <h2 class="about-intro__headline" id="about-intro-headline">
                <span class="ai-line ai-strong"><span class="ai-w" style="--i:0"><span data-ai-count="{{ $aiYears }}">{{ $aiYears }}</span>+</span> <span class="ai-w" style="--i:1">YEARS</span></span>
                <span class="ai-line ai-light"><span class="ai-w" style="--i:2">of</span> <span class="ai-w" style="--i:3">EXPERIENCE</span> <span class="ai-w" style="--i:4">in</span></span>
                <span class="ai-line ai-strong"><span class="ai-w" style="--i:5">the</span> <span class="ai-w" style="--i:6">IT</span> <span class="ai-w" style="--i:7">INDUSTRY.</span></span>
            </h2>
            <a class="about-intro__more" href="#journey" data-ai-more>MORE INFORMATION <span class="about-intro__arrow" aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="about-intro__body">
            <div class="section-tag">About Us</div>
            <p class="about-intro__lead">The Best IT Solution With Many Years of Experience</p>
            <p class="about-intro__text">
                At Sky Link Technologies, we turn visionary concepts into cutting-edge digital solutions. With a passion for technology and a commitment to excellence, we empower businesses to thrive in the digital age.
            </p>
            <ul class="about-intro__points">
                <li><i class="fas fa-check" aria-hidden="true"></i><span>Award Winning</span></li>
                <li><i class="fas fa-check" aria-hidden="true"></i><span>Dedicated Support</span></li>
                <li><i class="fas fa-check" aria-hidden="true"></i><span>Professional Staff</span></li>
                <li><i class="fas fa-check" aria-hidden="true"></i><span>Fair Prices</span></li>
            </ul>
        </div>
    </div>
    <style>
        .about-intro { background: #fff; padding: clamp(56px, 7vw, 100px) 20px; overflow: hidden; }
        .about-intro__wrap { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: clamp(32px, 5vw, 72px); align-items: center; }
        .about-intro__card { background: #fff; border-radius: 8px; padding: clamp(28px, 3.4vw, 40px); box-shadow: 0 30px 80px rgba(15, 42, 92, 0.12); color: #0f2a5c; transition: transform 0.35s ease, box-shadow 0.35s ease; }
        .about-intro__card:hover { transform: translateY(-4px); box-shadow: 0 38px 90px rgba(15, 42, 92, 0.16); }
        .about-intro__headline { margin: 0 0 clamp(20px, 2.4vw, 28px); font-family: var(--font-heading); font-size: clamp(1.5rem, 0.9rem + 2.8vw, 2.75rem); line-height: 1.12; color: #0f2a5c; letter-spacing: -0.01em; }
        .ai-line { display: block; white-space: nowrap; }
        .ai-strong { font-weight: 800; }
        .ai-light { font-weight: 300; }
        .ai-w { display: inline-block; }
        .ai-w [data-ai-count] { font-variant-numeric: tabular-nums; }
        .about-intro__more { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; font: 500 0.8125rem/1 var(--font-body); letter-spacing: 0.2em; text-transform: uppercase; color: #0f2a5c; text-decoration: none; }
        .about-intro__arrow { display: inline-block; transition: transform 0.25s ease; }
        .about-intro__more:hover .about-intro__arrow, .about-intro__more:focus-visible .about-intro__arrow { transform: translateX(6px); }
        .about-intro__more:focus-visible { outline: 3px solid #3b82f6; outline-offset: 4px; border-radius: 4px; }
        .about-intro__body .section-tag { margin-bottom: 10px; }
        .about-intro__lead { margin: 0 0 14px; font-family: var(--font-heading); font-weight: 700; font-size: clamp(1.125rem, 1rem + 0.5vw, 1.375rem); line-height: 1.35; color: #0f2a5c; }
        .about-intro__text { margin: 0 0 24px; font-size: clamp(1rem, 0.96rem + 0.2vw, 1.0625rem); line-height: 1.8; color: #5f6b7a; }
        .about-intro__points { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 24px; }
        .about-intro__points li { display: flex; align-items: center; gap: 10px; min-height: 28px; }
        .about-intro__points i { color: #3b82f6; font-size: 0.9rem; width: 16px; text-align: center; }
        .about-intro__points span { font-weight: 500; color: #1e293b; font-size: 0.9375rem; }
        @media (max-width: 991.98px) {
            .about-intro__wrap { grid-template-columns: minmax(0, 1fr); }
            .about-intro__card { max-width: 560px; width: 100%; margin: 0 auto; }
            .about-intro__body { max-width: 640px; margin: 0 auto; width: 100%; }
        }
        /* Motion: only when the page script allows it (not reduced motion, not Save-Data, not 2G). */
        .ai-anim .about-intro__card { opacity: 0; transform: translateY(28px); transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.22, 0.7, 0.2, 1), box-shadow 0.35s ease; }
        .ai-anim .about-intro.is-in .about-intro__card { opacity: 1; transform: none; }
        .ai-anim .about-intro.is-in .about-intro__card:hover { transform: translateY(-4px); }
        .ai-anim .ai-w { opacity: 0; transform: translateY(0.45em); transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.22, 0.7, 0.2, 1); transition-delay: calc(0.25s + var(--i, 0) * 0.07s); }
        .ai-anim .about-intro.is-in .ai-w { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .about-intro__card, .about-intro__arrow { transition: none; } .about-intro__card:hover { transform: none; } }
    </style>
    <script>
    (function () {
        var sec = document.getElementById('about-intro'); if (!sec) return;
        var animate = document.documentElement.classList.contains('ai-anim');
        var more = sec.querySelector('[data-ai-more]');
        if (more) more.addEventListener('click', function (e) {
            var t = document.getElementById('journey'); if (!t) return;
            e.preventDefault(); t.scrollIntoView({ behavior: animate ? 'smooth' : 'instant', block: 'start' });
            if (history.replaceState) history.replaceState(null, '', '#journey');
        });
        if (!animate) { sec.classList.add('is-in'); return; }
        var num = sec.querySelector('[data-ai-count]'), to = num ? parseInt(num.getAttribute('data-ai-count'), 10) : 0, started = false;
        function count() {
            if (!num || started) return; started = true;
            var t0 = performance.now(), d = 900;
            num.textContent = '0';
            (function tick(now) { var k = Math.min(1, (now - t0) / d); num.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(tick); })(t0);
        }
        new IntersectionObserver(function (en, ob) {
            if (!en[0].isIntersecting) return;
            sec.classList.add('is-in'); setTimeout(count, 250); ob.disconnect();
        }, { threshold: 0.25 }).observe(sec);
        setTimeout(function () { sec.classList.add('is-in'); }, 4000); // failsafe: never leave it hidden
    })();
    </script>
</section>
