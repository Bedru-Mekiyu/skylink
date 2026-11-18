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
    .anniv5-mark__orbit {
        fill: none; stroke: rgba(232, 238, 250, 0.28); stroke-width: 1.2; stroke-dasharray: 1.5 9;
        animation: anniv5-spin 24s linear infinite;
    }
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

    /* Timeline (8s): 1-4 at 0/12/24/36%, 5 lands at 48%, burst 48-66%, settled 56-92%, crossfade to 1. */
    @keyframes anniv5-count {
        0% { opacity: 0; transform: translateY(16px) scale(.92); }
        3%, 9% { opacity: 1; transform: none; }
        12%, 100% { opacity: 0; transform: translateY(-16px) scale(1.04); }
    }
    @keyframes anniv5-five {
        0%, 47% { opacity: 0; transform: scale(1.7); }
        51% { opacity: 1; transform: scale(.95); }
        55%, 92% { opacity: 1; transform: translateY(-10px) scale(.86); }
        100% { opacity: 0; transform: translateY(-10px) scale(.86); }
    }
    @keyframes anniv5-years {
        0%, 54% { opacity: 0; }
        60%, 92% { opacity: 1; }
        100% { opacity: 0; }
    }
    @keyframes anniv5-flash {
        0%, 48% { opacity: 0; transform: scale(.4); }
        51% { opacity: .32; }
        60%, 100% { opacity: 0; transform: scale(1.25); }
    }
    @keyframes anniv5-ray {
        0%, 48% { opacity: 0; stroke-dashoffset: 32; }
        51% { opacity: 1; }
        56% { stroke-dashoffset: 0; }
        64%, 100% { opacity: 0; stroke-dashoffset: -32; }
    }
    @keyframes anniv5-spark {
        0%, 49% { opacity: 0; transform: rotate(var(--a)) translateY(-18px); }
        52% { opacity: 1; }
        68%, 100% { opacity: 0; transform: rotate(var(--a)) translateY(-90px); }
    }
    @keyframes anniv5-spin { to { transform: rotate(360deg); } }

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
    .anniv5--compact {
        position: relative; color: var(--anniv5-ink);
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
    .anniv5-home-band { padding: 1.75rem 0 .5rem; }
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
    .anniv5-intro[data-phase="five"] [data-n="5"] { opacity: 1; transform: scale(1.06); transition-duration: .1s, .5s; }
    .anniv5-intro__title { text-align: center; opacity: 0; transform: translateY(12px); transition: opacity .6s ease, transform .8s cubic-bezier(.2, .7, .3, 1); }
    .anniv5-intro[data-phase="title"] .anniv5-intro__title, .anniv5-intro[data-phase="out"] .anniv5-intro__title { opacity: 1; transform: none; }
    .anniv5-intro__big {
        display: block; font-family: var(--font-card-title, 'Bodoni Moda', Georgia, serif); font-optical-sizing: auto;
        font-size: clamp(3rem, 11vw, 6.5rem); font-weight: 600; line-height: 1.05; color: #fff;
    }
    .anniv5-intro__big b { color: var(--anniv5-gold); font-weight: inherit; }
    .anniv5-intro__kicker {
        display: block; margin-top: .9rem; font-family: var(--font-ui, 'Manrope', sans-serif); font-size: clamp(.7rem, 2.4vw, .9rem);
        font-weight: 700; letter-spacing: .34em; text-transform: uppercase; color: var(--anniv5-cyan);
    }
    .anniv5-intro__skip {
        position: absolute; top: max(16px, env(safe-area-inset-top)); right: max(16px, env(safe-area-inset-right));
        min-height: 44px; padding: .55rem 1.1rem; border-radius: 999px; cursor: pointer;
        font-family: var(--font-ui, 'Manrope', sans-serif); font-size: .85rem; font-weight: 600; letter-spacing: .04em;
        color: var(--anniv5-ink); background: rgba(16, 26, 53, .6); border: 1px solid rgba(232, 238, 250, .35);
    }
    .anniv5-intro__skip:hover { background: rgba(59, 130, 246, .35); }
    .anniv5-intro__skip:focus-visible { outline: 3px solid var(--anniv5-gold); outline-offset: 3px; }

    /* ---------- Reduced motion: no intro, a still and settled seal ---------- */
    @media (prefers-reduced-motion: reduce) {
        .anniv5-intro { display: none !important; }
        .anniv5-mark *, .anniv5-mark__n { animation: none !important; }
        .anniv5-mark__n--5 { opacity: 1; transform: translateY(-10px) scale(.86); }
        .anniv5-mark__years { opacity: 1; }
    }
</style>
