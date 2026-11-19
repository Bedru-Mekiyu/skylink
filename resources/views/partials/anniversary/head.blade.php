{{-- 5-year anniversary styles. Included in <head> only while the campaign is active. --}}
<style>
    .anniv5, .anniv5-intro {
        --anniv5-cyan: #22D3EE;
        --anniv5-blue: #3B82F6;
        --anniv5-violet: #8B5CF6;
        --anniv5-magenta: #EC4899;
        --anniv5-gold: #F5C451;
        --anniv5-midnight: #101A35;
        --anniv5-ink: #E8EEFA;
        --anniv5-cycle: 8s;
    }

    /* ---------- Seal mark (shared by both variants) ---------- */
    .anniv5-mark { display: block; flex: none; overflow: visible; }
    .anniv5-mark * { transform-box: view-box; transform-origin: 100px 100px; }
    .anniv5-mark__ring { fill: none; stroke-width: 2.5; }
    .anniv5-mark__orbit { fill: none; stroke: rgba(232, 238, 250, 0.28); stroke-width: 1.2; stroke-dasharray: 1.5 9; }
    /* Orbit: calm drift, speeds up and tightens before the 5, celebrates after, eases back (360deg = seamless). */
    .anniv5-mark__orbit-g { animation: anniv5-orbit var(--anniv5-cycle) linear infinite; }
    .anniv5-mark__node { opacity: .85; animation: anniv5-node-a var(--anniv5-cycle) cubic-bezier(.55, 0, .85, .4) infinite; }
    .anniv5-mark__node--b { animation-name: anniv5-node-b; }
    .anniv5-mark__flash { fill: #fff; opacity: 0; animation: anniv5-flash var(--anniv5-cycle) ease-out infinite; }
    .anniv5-mark__rays line {
        stroke-width: 3; stroke-linecap: round; stroke-dasharray: 32 64; stroke-dashoffset: 32; opacity: 0;
        transform: rotate(var(--a));
        animation: anniv5-ray var(--anniv5-cycle) cubic-bezier(.2, .7, .3, 1) infinite;
    }
    .anniv5-mark__sparks circle {
        opacity: 0; transform: rotate(var(--a));
        animation: anniv5-spark var(--anniv5-cycle) cubic-bezier(.15, .8, .3, 1) infinite;
    }
    .anniv5-mark__n {
        font-family: var(--font-card-title, 'Bodoni Moda', Georgia, serif);
        font-optical-sizing: auto; font-size: 104px; font-weight: 600; fill: var(--anniv5-ink); opacity: 0;
        animation: anniv5-count var(--anniv5-cycle) cubic-bezier(.2, .7, .3, 1) infinite both;
    }
    .anniv5-mark__n--2 { animation-delay: .96s; }
    .anniv5-mark__n--3 { animation-delay: 1.92s; }
    .anniv5-mark__n--4 { animation-delay: 2.88s; }
    .anniv5-mark__n--5 { fill: var(--anniv5-gold); animation-name: anniv5-five; animation-delay: 0s; }
    .anniv5-mark__years {
        font-family: var(--font-ui, 'Manrope', sans-serif); font-size: 15px; font-weight: 700;
        letter-spacing: .32em; fill: var(--anniv5-ink); opacity: 0;
        animation: anniv5-years var(--anniv5-cycle) ease-out infinite;
    }

    /* Timeline (8s): 1-4 at 0/12/24/36%; anticipation 42-50% (orbit tightens, nodes race in);
       IMPACT at 50% (4.0s): 5 punches 1 -> 1.08 -> 1, flash, rays; the canvas burst in the scripts
       partial is synced to this keyframe; settled "5 YEARS" 56-92%; crossfade back to 1. */
    @keyframes anniv5-count {
        0% { opacity: 0; transform: translateY(16px) scale(.92); }
        3%, 9% { opacity: 1; transform: none; }
        12%, 100% { opacity: 0; transform: translateY(-16px) scale(1.04); }
    }
    @keyframes anniv5-five {
        0%, 47% { opacity: 0; transform: scale(1.6); }
        50% { opacity: 1; transform: scale(1); }
        51.2% { opacity: 1; transform: scale(1.08); }
        53% { opacity: 1; transform: scale(1); }
        57%, 92% { opacity: 1; transform: translateY(-10px) scale(.86); }
        100% { opacity: 0; transform: translateY(-10px) scale(.86); }
    }
    @keyframes anniv5-years {
        0%, 56% { opacity: 0; }
        61%, 92% { opacity: 1; }
        100% { opacity: 0; }
    }
    @keyframes anniv5-flash {
        0%, 49.5% { opacity: 0; transform: scale(.4); }
        50.5% { opacity: .42; }
        58%, 100% { opacity: 0; transform: scale(1.3); }
    }
    @keyframes anniv5-ray {
        0%, 50% { opacity: 0; stroke-dashoffset: 32; }
        51% { opacity: 1; }
        55% { stroke-dashoffset: 0; }
        64%, 100% { opacity: 0; stroke-dashoffset: -32; }
    }
    @keyframes anniv5-spark {
        0%, 50% { opacity: 0; transform: rotate(var(--a)) translateY(-18px); }
        52% { opacity: 1; }
        68%, 100% { opacity: 0; transform: rotate(var(--a)) translateY(-90px); }
    }
    @keyframes anniv5-orbit {
        0% { transform: rotate(0deg); }
        42% { transform: rotate(50deg); }
        50% { transform: rotate(150deg) scale(.9); }
        62% { transform: rotate(290deg) scale(1.02); }
        70% { transform: rotate(315deg) scale(1); }
        100% { transform: rotate(360deg); }
    }
    @keyframes anniv5-node-a {
        0%, 42% { opacity: .85; transform: none; }
        49.5% { opacity: 1; transform: translateY(72px) scale(.5); }
        50%, 55% { opacity: 0; transform: none; }
        62%, 100% { opacity: .85; transform: none; }
    }
    @keyframes anniv5-node-b {
        0%, 42% { opacity: .85; transform: none; }
        49.5% { opacity: 1; transform: translateY(-72px) scale(.5); }
        50%, 55% { opacity: 0; transform: none; }
        62%, 100% { opacity: .85; transform: none; }
    }

    /* The canvas burst (scripts partial) replaces the CSS sparks when JS runs; without JS they stay. */
    .anniv5.has-fx .anniv5-mark__sparks { display: none; }
    .anniv5__fx {
        position: absolute; pointer-events: none; z-index: 1;
        /* Soft edges so particles fade out instead of being cut at the layer's border. */
        -webkit-mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 66%, transparent 100%);
                mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 66%, transparent 100%);
    }
    .anniv5.is-paused .anniv5__fx { visibility: hidden; }

    /* Off-screen or hidden tab: freeze (scripts partial toggles the class). */
    .anniv5.is-paused .anniv5-mark * { animation-play-state: paused; }

    /* ---------- Copy ---------- */
    .anniv5 .anniv5__kicker {
        margin: 0; font-family: var(--font-ui, 'Manrope', sans-serif); font-size: .72rem; font-weight: 700;
        letter-spacing: .26em; text-transform: uppercase; color: var(--anniv5-gold); line-height: 1.3;
    }
    .anniv5 .anniv5__line {
        margin: .15rem 0 0; font-family: var(--font-reading, 'EB Garamond', serif); font-style: italic;
        font-size: 1.08rem; line-height: 1.35; color: var(--anniv5-ink); opacity: 1;
    }
    .anniv5 .anniv5__line:lang(am) { font-style: normal; font-size: 1rem; }

    /* ---------- Compact strip (all public pages except home) ---------- */
    /* Pages that size their banner to one screen subtract this strip's height. */
    :root { --top-strip-h: 84px; }
    .anniv5--compact {
        position: relative; overflow: hidden; color: var(--anniv5-ink); /* keeps the burst inside the strip */
        background:
            radial-gradient(120% 220% at 0% 50%, rgba(34, 211, 238, .16), transparent 55%),
            radial-gradient(90% 200% at 100% 50%, rgba(139, 92, 246, .18), transparent 60%),
            var(--anniv5-midnight);
    }
    .anniv5--compact::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px;
        background: linear-gradient(90deg, var(--anniv5-cyan), var(--anniv5-blue), var(--anniv5-violet), var(--anniv5-magenta), var(--anniv5-gold));
    }
    .anniv5--compact .anniv5__inner { display: flex; align-items: center; gap: 1rem; padding-top: .6rem; padding-bottom: .6rem; }
    .anniv5--compact .anniv5-mark { width: 64px; height: 64px; }
    .anniv5--compact .anniv5__copy { min-width: 0; }

    /* ---------- Feature panel (homepage): no card, it sits straight on the hero / page ---------- */
    .anniv5--feature {
        position: relative; z-index: 2; width: 100%; max-width: 400px; margin-inline: auto;
        text-align: center; color: var(--anniv5-ink);
    }
    /* On the hero photo: light text, with a soft shadow so it stays readable over any frame. */
    .anniv5-hero-slot .anniv5--feature .anniv5__kicker,
    .anniv5-hero-slot .anniv5--feature .anniv5__title,
    .anniv5-hero-slot .anniv5--feature .anniv5__message {
        text-shadow: 0 0 1px rgba(4, 10, 28, 1), 0 1px 3px rgba(4, 10, 28, .95), 0 0 10px rgba(4, 10, 28, .85), 0 0 24px rgba(4, 10, 28, .7);
    }
    .anniv5-hero-slot .anniv5--feature .anniv5__kicker { color: #FFD97A; }
    .anniv5-hero-slot .anniv5--feature .anniv5__message { color: #fff; }
    .anniv5--feature .anniv5-mark { width: 148px; height: 148px; margin: 0 auto 1rem; }
    .anniv5--feature .anniv5__title {
        margin: .55rem 0 .7rem; font-family: var(--font-card-title, 'Bodoni Moda', serif); font-optical-sizing: auto;
        font-size: clamp(1.3rem, 1.05rem + .8vw, 1.6rem); font-weight: 600; line-height: 1.2; color: #fff;
        letter-spacing: 0; text-wrap: balance; padding: 0;
    }
    .anniv5--feature .anniv5__message {
        margin: 0; font-family: var(--font-reading, 'EB Garamond', serif); font-size: 1.02rem; line-height: 1.6;
        color: rgba(232, 238, 250, .88); opacity: 1; text-wrap: pretty;
    }
    .anniv5--feature .anniv5__message:lang(am), .anniv5--feature .anniv5__title:lang(am) { line-height: 1.75; }
    .anniv5-home-band { padding: 1.75rem 0 .5rem; overflow-x: clip; }
    /* Below the hero (phones/tablets) the panel sits on the light page: dark text for AA contrast. */
    .anniv5-home-band .anniv5--feature .anniv5__kicker { color: #7A5200; }
    .anniv5-home-band .anniv5--feature .anniv5__title { color: var(--anniv5-midnight); }
    .anniv5-home-band .anniv5--feature .anniv5__message { color: #334155; }
    /* Hero slot: the panel is centred on the hero text without setting the row height,
       so a web-font swap inside the panel never moves the hero text (no layout shift). */
    @media (min-width: 992px) {
        .anniv5-hero-slot { position: relative; align-self: stretch; }
        .anniv5-hero-slot > .anniv5--feature {
            position: absolute; top: 50%; left: calc(var(--bs-gutter-x, 1.5rem) * .5); right: calc(var(--bs-gutter-x, 1.5rem) * .5);
            width: auto; transform: translateY(-50%);
        }
    }

    @media (max-width: 575.98px) {
        .anniv5--compact .anniv5__inner { gap: .75rem; }
        .anniv5--compact .anniv5-mark { width: 52px; height: 52px; }
        .anniv5 .anniv5__kicker { font-size: .66rem; letter-spacing: .2em; }
        .anniv5 .anniv5__line { font-size: 1rem; }
        .anniv5--feature .anniv5-mark { width: 120px; height: 120px; }
    }

    /* ---------- Entry intro ---------- */
    .anniv5-intro {
        position: fixed; inset: 0; z-index: 2147483000; overflow: hidden; color: var(--anniv5-ink);
        background: radial-gradient(120% 90% at 50% 100%, #1B2A55 0%, var(--anniv5-midnight) 55%, #070C1C 100%);
        transition: opacity .55s ease;
    }
    .anniv5-intro[hidden] { display: none; }
    .anniv5-intro[data-phase="out"], .anniv5-intro.is-leaving { opacity: 0; }
    /* Everything that moves with the detonation shake lives in the world layer (skip button and copy stay still). */
    .anniv5-intro__world { position: absolute; inset: 0; }
    .anniv5-intro.is-boom .anniv5-intro__world { animation: anniv5-shake .28s steps(1, end) 1; }
    @keyframes anniv5-shake {
        0% { transform: translate(3px, -2px); } 17% { transform: translate(-3px, 2px); } 34% { transform: translate(2px, 3px); }
        51% { transform: translate(-2px, -3px); } 68% { transform: translate(2px, -1px); } 85% { transform: translate(-1px, 1px); } 100% { transform: none; }
    }
    .anniv5-intro__sky { position: absolute; inset: 0; width: 100%; height: 100%; }
    .anniv5-intro__rocket {
        position: absolute; left: 50%; top: 0; width: 14px; height: 46px; opacity: 0; will-change: transform;
        border-radius: 7px 7px 4px 4px;
        background: linear-gradient(180deg, #fff 0%, #CFE9FF 40%, var(--anniv5-cyan) 100%);
        box-shadow: 0 0 18px rgba(34, 211, 238, .85), 0 0 46px rgba(59, 130, 246, .55);
    }
    .anniv5-intro__rocket::after {
        content: ''; position: absolute; left: 50%; top: 100%; width: 8px; height: 26px; transform: translateX(-50%);
        border-radius: 0 0 50% 50%; background: linear-gradient(180deg, var(--anniv5-gold), rgba(236, 72, 153, 0));
    }
    /* Build-up: the screen dims toward the edges and light gathers into the core. */
    .anniv5-intro__dim {
        position: absolute; inset: 0; pointer-events: none; opacity: 0; transition: opacity .5s ease;
        background: radial-gradient(circle at 50% 36%, transparent 18%, rgba(3, 6, 18, .7) 100%);
    }
    .anniv5-intro.is-build .anniv5-intro__dim { opacity: 1; }
    .anniv5-intro.is-boom .anniv5-intro__dim { opacity: 0; transition-duration: 1.2s; }
    .anniv5-intro__core, .anniv5-intro__flare, .anniv5-intro__rays, .anniv5-intro__ring {
        position: absolute; left: 50%; top: 36%; pointer-events: none; opacity: 0;
    }
    .anniv5-intro__core {
        width: min(60vmin, 520px); aspect-ratio: 1; border-radius: 50%; transform: translate(-50%, -50%) scale(1.6);
        background: radial-gradient(circle, rgba(245, 196, 81, .85) 0, rgba(236, 72, 153, .4) 30%, rgba(59, 130, 246, .18) 52%, transparent 70%);
    }
    .anniv5-intro.is-boom .anniv5-intro__core { opacity: 0 !important; transition: opacity .4s ease; }
    /* Bloom of colour (never white), once, at detonation. */
    .anniv5-intro__bloom {
        position: absolute; inset: 0; pointer-events: none; opacity: 0;
        background:
            radial-gradient(circle at 50% 36%, rgba(245, 196, 81, .5) 0, rgba(236, 72, 153, .3) 14%, rgba(139, 92, 246, .22) 32%, rgba(34, 211, 238, .12) 55%, transparent 75%);
    }
    .anniv5-intro.is-boom .anniv5-intro__bloom { animation: anniv5-bloom 1s ease-out 1 both; }
    @keyframes anniv5-bloom { 0% { opacity: 0; transform: scale(.6); } 18% { opacity: 1; } 100% { opacity: 0; transform: scale(1.25); } }
    /* Light rays turning slowly behind the 5. */
    .anniv5-intro__rays {
        width: 120vmax; height: 120vmax; translate: -50% -50%; border-radius: 50%;
        background: repeating-conic-gradient(from 0deg, rgba(34, 211, 238, .38) 0deg 2.4deg, transparent 2.4deg 18deg, rgba(139, 92, 246, .34) 18deg 20.4deg, transparent 20.4deg 36deg, rgba(236, 72, 153, .3) 36deg 38.4deg, transparent 38.4deg 54deg);
        -webkit-mask-image: radial-gradient(circle, #000 0, rgba(0, 0, 0, .5) 26%, transparent 60%);
        mask-image: radial-gradient(circle, #000 0, rgba(0, 0, 0, .5) 26%, transparent 60%);
    }
    .anniv5-intro.is-boom .anniv5-intro__rays { animation: anniv5-rays 2.8s ease-out 1 both; }
    @keyframes anniv5-rays { 0% { opacity: 0; rotate: 0deg; } 15% { opacity: .9; } 100% { opacity: 0; rotate: 28deg; } }
    /* Lens flare: a long horizontal streak. */
    .anniv5-intro__flare {
        width: min(96vw, 1400px); height: 10px; transform: translate(-50%, -50%);
        background: linear-gradient(90deg, transparent, rgba(34, 211, 238, .55) 25%, rgba(245, 196, 81, .85) 50%, rgba(236, 72, 153, .55) 75%, transparent);
        filter: blur(3px);
    }
    .anniv5-intro.is-boom .anniv5-intro__flare { animation: anniv5-flare 1.3s cubic-bezier(.2, .7, .3, 1) 1 both; }
    @keyframes anniv5-flare { 0% { opacity: 0; transform: translate(-50%, -50%) scaleX(.1); } 20% { opacity: 1; } 100% { opacity: 0; transform: translate(-50%, -50%) scaleX(1); } }
    /* Shockwaves: four rings, each reaching the screen edges. */
    .anniv5-intro__ring {
        width: 160vmax; aspect-ratio: 1; border-radius: 50%; transform: translate(-50%, -50%) scale(.02);
        border: 3px solid var(--rc, var(--anniv5-cyan)); box-shadow: 0 0 12px var(--rc, var(--anniv5-cyan));
        will-change: transform, opacity;
    }
    .anniv5-intro__ring[data-ring="1"] { --rc: var(--anniv5-violet); }
    .anniv5-intro__ring[data-ring="2"] { --rc: var(--anniv5-magenta); }
    .anniv5-intro__ring[data-ring="3"] { --rc: var(--anniv5-gold); }
    .anniv5-intro__ring.go { animation: anniv5-ring 1.5s cubic-bezier(.15, .6, .3, 1) 1 both; }
    @keyframes anniv5-ring { 0% { opacity: .85; transform: translate(-50%, -50%) scale(.02); } 100% { opacity: 0; transform: translate(-50%, -50%) scale(1); } }

    .anniv5-intro__stage {
        position: absolute; left: 0; right: 0; top: 36%; transform: translateY(-50%);
        display: grid; place-items: center; pointer-events: none;
    }
    .anniv5-intro__n, .anniv5-intro__title { grid-area: 1 / 1; }
    .anniv5-intro__n {
        font-family: var(--font-card-title, 'Bodoni Moda', Georgia, serif); font-optical-sizing: auto;
        font-size: clamp(6rem, 22vw, 13rem); font-weight: 600; line-height: 1; opacity: 0;
        transform: scale(.85); transition: opacity .12s ease, transform .3s cubic-bezier(.2, .7, .3, 1);
    }
    .anniv5-intro[data-phase="n1"] [data-n="1"], .anniv5-intro[data-phase="n2"] [data-n="2"],
    .anniv5-intro[data-phase="n3"] [data-n="3"], .anniv5-intro[data-phase="n4"] [data-n="4"] { opacity: 1; transform: none; }
    .anniv5-intro [data-n="5"] { color: var(--anniv5-gold); text-shadow: 0 0 40px rgba(245, 196, 81, .55); }
    /* Detonation: the 5 punches in (1.0 -> 1.15 -> 1.0); it then gives way to the headline. */
    .anniv5-intro[data-phase="five"] [data-n="5"] { opacity: 1; animation: anniv5-punch .5s cubic-bezier(.2, .7, .3, 1) 1 both; transition-duration: .08s; }
    @keyframes anniv5-punch { 0% { transform: scale(1); } 40% { transform: scale(1.15); } 100% { transform: scale(1); } }
    .anniv5-intro__title { text-align: center; width: min(94vw, 1100px); padding: 0 .5rem; }
    .anniv5-intro__kicker {
        margin: 0 0 clamp(.6rem, 2vw, 1.1rem); font-family: var(--font-ui, 'Manrope', sans-serif); font-size: clamp(.68rem, 2.2vw, .95rem);
        font-weight: 700; letter-spacing: .3em; text-transform: uppercase; color: var(--anniv5-cyan); line-height: 1.5; text-wrap: balance;
        opacity: 0; transform: translateY(10px); transition: opacity .6s ease, transform .7s cubic-bezier(.2, .7, .3, 1);
    }
    .anniv5-intro__headline {
        margin: 0; font-family: var(--font-card-title, 'Bodoni Moda', Georgia, serif); font-optical-sizing: auto;
        font-size: clamp(1.75rem, 1.1rem + 3.6vw, 4.25rem); font-weight: 600; line-height: 1.12; color: #fff; text-wrap: balance;
        text-shadow: 0 0 22px rgba(245, 196, 81, .4), 0 2px 12px rgba(4, 10, 28, .8);
    }
    .anniv5-intro__headline:lang(am) { line-height: 1.4; }
    .anniv5-intro__kicker:lang(am) { font-size: clamp(.85rem, 2.8vw, 1.05rem); letter-spacing: .06em; }
    /* Words rise in one after another with a soft gold glow; the kicker lands just before them. */
    .anniv5-intro__w {
        display: inline-block; opacity: 0; transform: translateY(.7em); filter: blur(6px);
        transition: opacity .7s ease, transform .8s cubic-bezier(.2, .7, .3, 1), filter .7s ease;
    }
    .anniv5-intro[data-phase="title"] .anniv5-intro__kicker, .anniv5-intro[data-phase="out"] .anniv5-intro__kicker { opacity: 1; transform: none; }
    .anniv5-intro[data-phase="title"] .anniv5-intro__w, .anniv5-intro[data-phase="out"] .anniv5-intro__w {
        opacity: 1; transform: none; filter: none; transition-delay: calc(.3s + var(--i) * .11s);
    }
    .anniv5-intro__skip {
        position: absolute; z-index: 3; top: max(16px, env(safe-area-inset-top)); right: max(16px, env(safe-area-inset-right));
        min-height: 44px; padding: .55rem 1.1rem; border-radius: 999px; cursor: pointer;
        font-family: var(--font-ui, 'Manrope', sans-serif); font-size: .85rem; font-weight: 600; letter-spacing: .04em;
        color: var(--anniv5-ink); background: rgba(16, 26, 53, .6); border: 1px solid rgba(232, 238, 250, .35);
    }
    .anniv5-intro__skip:hover { background: rgba(59, 130, 246, .35); }
    .anniv5-intro__skip:focus-visible { outline: 3px solid var(--anniv5-gold); outline-offset: 3px; }
    @media (max-width: 575.98px) { .anniv5-intro__kicker { letter-spacing: .2em; } }

    /* ---------- Reduced motion: no intro, a still and settled seal ---------- */
    @media (prefers-reduced-motion: reduce) {
        .anniv5-intro { display: none !important; }
        .anniv5-mark *, .anniv5-mark__n { animation: none !important; }
        .anniv5-mark__n--5 { opacity: 1; transform: translateY(-10px) scale(.86); }
        .anniv5-mark__years { opacity: 1; }
        .anniv5-mark__flash { opacity: .1; transform: none; } /* soft static glow behind the still 5 */
        .anniv5__fx { display: none; }
    }
</style>
