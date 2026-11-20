{{--
    Site-wide typography system: readability first, self-hosted variable fonts (no third-party request).
    Five families, each with one role, reached only through the tokens below (never name a font in a component):
      --font-body        Inter              paragraphs, nav, footer, forms, buttons, tables, admin text
      --font-heading     Plus Jakarta Sans  page and section titles (h1-h3), modal titles
      --font-card-title  Plus Jakarta Sans  card titles (same family, one step lighter)
      --font-display     Fraunces           hero headline and anniversary numerals only; never small text
      --font-numeric     Inter (tabular)    prices, dates, stats, tables
    Amharic: Ge'ez glyphs fall through per glyph to Noto Sans Ethiopic (body and headings) and
    Noto Serif Ethiopic (display); the Ethiopic files only download when a page contains Ge'ez text.
    Legacy tokens (--font-ui, --font-reading, --font-card-text) are kept as aliases of --font-body.
    Files live in public/assets/fonts (SIL OFL, see LICENSE-OFL.txt). $surface = 'admin' uses body + heading only.
--}}
@php($surface = $surface ?? 'site')
<link rel="preload" href="{{ asset('assets/fonts/inter-latin-var.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('assets/fonts/plus-jakarta-sans-latin-var.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
    @font-face { font-family: 'Inter'; font-style: normal; font-weight: 100 900; font-display: swap;
        src: url('{{ asset('assets/fonts/inter-latin-var.woff2') }}') format('woff2');
        unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Inter'; font-style: italic; font-weight: 100 900; font-display: swap;
        src: url('{{ asset('assets/fonts/inter-latin-italic-var.woff2') }}') format('woff2');
        unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Plus Jakarta Sans'; font-style: normal; font-weight: 200 800; font-display: swap;
        src: url('{{ asset('assets/fonts/plus-jakarta-sans-latin-var.woff2') }}') format('woff2');
        unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Fraunces'; font-style: normal; font-weight: 100 900; font-display: swap;
        src: url('{{ asset('assets/fonts/fraunces-latin-var.woff2') }}') format('woff2');
        unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
    @font-face { font-family: 'Noto Sans Ethiopic'; font-style: normal; font-weight: 100 900; font-display: swap;
        src: url('{{ asset('assets/fonts/noto-sans-ethiopic-var.woff2') }}') format('woff2');
        unicode-range: U+030E, U+1200-1399, U+2D80-2DDE, U+AB01-AB2E, U+1E7E0-1E7E6, U+1E7E8-1E7EB, U+1E7ED-1E7EE, U+1E7F0-1E7FE; }
    @font-face { font-family: 'Noto Serif Ethiopic'; font-style: normal; font-weight: 100 900; font-display: swap;
        src: url('{{ asset('assets/fonts/noto-serif-ethiopic-var.woff2') }}') format('woff2');
        unicode-range: U+030E, U+1200-1399, U+2D80-2DDE, U+AB01-AB2E, U+1E7E0-1E7E6, U+1E7E8-1E7EB, U+1E7ED-1E7EE, U+1E7F0-1E7FE; }
    /* Metric-matched fallbacks: the system font is stretched to the proportions of the real font so text does not jump when it swaps in. */
    @font-face { font-family: 'Inter Fallback'; src: local('Arial'); size-adjust: 107.4%; ascent-override: 90.2%; descent-override: 22.5%; line-gap-override: 0%; }
    @font-face { font-family: 'Jakarta Fallback'; src: local('Arial'); size-adjust: 104.5%; ascent-override: 96%; descent-override: 24%; line-gap-override: 0%; }
    @font-face { font-family: 'Fraunces Fallback'; src: local('Georgia'); size-adjust: 104%; ascent-override: 92%; descent-override: 24%; line-gap-override: 0%; }

    :root {
        --font-body: 'Inter', 'Inter Fallback', 'Noto Sans Ethiopic', 'Nyala', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        --font-heading: 'Plus Jakarta Sans', 'Jakarta Fallback', 'Noto Sans Ethiopic', 'Nyala', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        --font-card-title: var(--font-heading);
        --font-display: 'Fraunces', 'Fraunces Fallback', 'Noto Serif Ethiopic', 'Nyala', Georgia, 'Times New Roman', serif;
        --font-numeric: var(--font-body);
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        /* Aliases for older rules and tailwind.config.js */
        --font-ui: var(--font-body);
        --font-reading: var(--font-body);
        --font-card-text: var(--font-body);

        /* Size scale (rem + clamp: follows the visitor's browser setting and 200% zoom) */
        --fs-body: clamp(1rem, 0.96rem + 0.2vw, 1.125rem);       /* 16px phones -> 18px desktop */
        --fs-card: clamp(0.9375rem, 0.92rem + 0.1vw, 1rem);      /* 15 -> 16px */
        --fs-small: clamp(0.8125rem, 0.8rem + 0.1vw, 0.875rem);  /* 13 -> 14px labels and meta */
        --fs-h1: clamp(1.875rem, 1.4rem + 2vw, 2.75rem);
        --fs-h2: clamp(1.5rem, 1.25rem + 1vw, 2.125rem);
        --fs-h3: clamp(1.25rem, 1.1rem + 0.6vw, 1.5rem);
        --fs-card-title: clamp(1.125rem, 1.05rem + 0.3vw, 1.3125rem);

        --lh-body: 1.65;
        --lh-reading: 1.7;
        --lh-ui: 1.4;
        --lh-heading: 1.2;
        --lh-card-title: 1.25;
        --lh-card-text: 1.6;

        /* Bootstrap reads these for body, inputs and buttons. */
        --bs-body-font-family: var(--font-body);
        --bs-font-sans-serif: var(--font-body);
        --bs-font-monospace: var(--font-mono);
    }

    html body {
        font-family: var(--font-body);
        font-size: var(--fs-body);
        font-kerning: normal;
        font-variant-ligatures: common-ligatures;
        /* Real weights and italics only: never faked. */
        font-synthesis: none;
        -webkit-text-size-adjust: 100%;
    }

    p, li, blockquote, figcaption, dd { text-wrap: pretty; }
    code, kbd, pre, samp { font-family: var(--font-mono); }

    /* Prices, dates, stats and tables: lining, equal-width digits. */
    td, th, .table, .stat-card, .stat-card-content, .stat-number, .counter, .price, time, [data-numeric] {
        font-family: var(--font-numeric);
        font-variant-numeric: lining-nums tabular-nums;
    }

    /* Bare headings follow the scale; zero specificity so any page-specific size still wins. */
    :where(h1, .h1) { font-size: var(--fs-h1); }
    :where(h2, .h2) { font-size: var(--fs-h2); }
    :where(h3, .h3) { font-size: var(--fs-h3); }

    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6,
    .modal-title, .offcanvas-title {
        font-family: var(--font-heading);
        font-weight: 700;
        line-height: var(--lh-heading);
        text-wrap: balance;
    }
    h1, .h1, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 { letter-spacing: -0.02em; }
    h2, .h2 { letter-spacing: -0.015em; }
    h4, h5, h6, .h4, .h5, .h6 { font-weight: 600; }
    :lang(am) :is(h1, h2, h3, h4, h5, h6), :is(h1, h2, h3, h4, h5, h6):lang(am) { letter-spacing: 0; line-height: 1.35; }

@if ($surface === 'admin')
    /* ---- Admin: body + heading fonts only ---- */
    nav, .navbar, .nav-link, .dropdown-menu, .dropdown-item, .btn, button, label, input, select, textarea, .form-control, .form-select,
    .form-label, .form-check-label, .input-group-text, .badge, .breadcrumb, .nav-tabs, .nav-pills, .pagination, .toast-header, .sidebar, .accordion-button {
        font-family: var(--font-body);
        line-height: var(--lh-ui);
    }
    .invalid-feedback, .valid-feedback, .form-text, .alert, .toast-body, .modal-body p { line-height: var(--lh-reading); }
@else
    /* ---- Public site ---- */
    html body { line-height: var(--lh-body); }
    .navbar .nav-link, .dropdown-item, footer a, .breadcrumb { letter-spacing: 0.01em; }
    .btn, button, label, input, select, textarea, .form-control, .form-select { font-family: var(--font-body); }
    .btn, button { font-weight: 600; letter-spacing: 0.01em; }
    .text-uppercase, .badge { letter-spacing: 0.04em; }
    b, strong, .fw-bold, .fw-bolder { font-weight: 700; }

    /* Hero headline: the only place the display face is used. */
    .hero-title { font-family: var(--font-display); font-weight: 600; font-optical-sizing: auto; line-height: 1.08; letter-spacing: -0.015em; text-wrap: balance; }
    .page-banner__title, .section-title, .pf-hero__title { font-family: var(--font-heading); font-weight: 700; line-height: var(--lh-heading); text-wrap: balance; }

    /* Card titles */
    .card-title, .card-body > h3, .card-body > h4, .card-body > h5,
    .service-card h3, .service-content h3, .service-content h4, .news-card h3, .testimonial-card h4, .team-showcase-card h3,
    .product-item h4, .award-title,
    .news-card__title, .project-card__title, .news-featured__title, .news-list-title {
        font-family: var(--font-card-title);
        font-weight: 650;
        line-height: var(--lh-card-title);
        letter-spacing: -0.005em;
        text-wrap: balance;
    }

    /* Card paragraphs: 15px minimum, 16px on phones. */
    .card .card-text, .card-description, .service-card p, .service-content p,
    .news-card .news-card__excerpt, .project-card .project-card__text {
        font-family: var(--font-body);
        font-size: max(0.9375rem, var(--fs-card));
        line-height: var(--lh-card-text);
        letter-spacing: 0;
    }
    @media (max-width: 575.98px) {
        html body :is(.card-description, .service-content p, .card .card-text, .news-card__excerpt, .project-card__text,
                      .news-featured__excerpt, .testimonial-text, .profession, .about-text, .award-year) {
            font-size: max(1rem, 1em);
        }
    }

    /* Floor for small labels and meta: never below 13px (found by the page-by-page size audit). `html body` lifts these above page styles that load later. */
    html body :is(.category-badge, .news-tag, .news-video__platform, .news-featured__label, .sk-contact__status,
                  .nav-contact__row, .nav-contact__row a, .nav-contact__sep, .contact-card h6) { font-size: 0.8125rem; }
    @media (max-width: 575.98px) {
        html body .product-card p { font-size: 0.9375rem !important; } /* the page sets 12px !important on phones */
    }

    /* Long-form news article: comfortable measure and leading. */
    .article__body, .article__body p, .article__body li, .article__body blockquote { font-family: var(--font-body); }
    .article__body { line-height: var(--lh-reading); font-size: var(--fs-body); }
    .article__body p { max-width: 72ch; }
    .article__body :is(h2, h3) { font-family: var(--font-heading); }
@endif
</style>
