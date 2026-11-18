{{--
    Site-wide typography system. One font request, semantic role tokens.
    Roles: reading = EB Garamond, ui = Manrope, card-title = Bodoni Moda, card-text = Manrope.
    Ethiopic (Amharic) glyphs fall through to Noto Serif/Sans Ethiopic per glyph, so every
    role keeps its serif/sans feel in Ge'ez script.
    $surface = 'admin' skips the editorial card roles (admin cards are interface, not content).
--}}
@php($surface = $surface ?? 'site')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400..700&family=EB+Garamond:ital,wght@0,400..700;1,400..700&family=Manrope:wght@400..800&family=Noto+Sans+Ethiopic:wght@400..700&family=Noto+Serif+Ethiopic:wght@400..700&display=swap" rel="stylesheet">
<style>
    :root {
        --font-reading: 'EB Garamond', 'Noto Serif Ethiopic', 'Nyala', Garamond, Georgia, 'Times New Roman', serif;
        --font-ui: 'Manrope', 'Noto Sans Ethiopic', 'Nyala', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        --font-card-title: 'Bodoni Moda', 'Noto Serif Ethiopic', 'Didot', 'Bodoni 72', Georgia, serif;
        --font-card-text: var(--font-ui);
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

        --lh-reading: 1.65;
        --lh-ui: 1.35;
        --lh-heading: 1.15;
        --lh-card-title: 1.15;
        --lh-card-text: 1.55;

        /* Bootstrap reads these for body, inputs and buttons. */
        --bs-body-font-family: var(--font-reading);
        --bs-font-sans-serif: var(--font-ui);
        --bs-font-monospace: var(--font-mono);
    }

    body {
        font-family: var(--font-reading);
        font-kerning: normal;
        font-variant-ligatures: common-ligatures;
        /* Never fake bold/italic the fonts do not ship. */
        font-synthesis: none;
    }

    p, li, blockquote, figcaption, dd { text-wrap: pretty; }
    code, kbd, pre, samp { font-family: var(--font-mono); }

    /* ---- Interface role: Manrope ---- */
    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6,
    nav, .navbar, .nav-link, .navbar-brand, .dropdown-menu, .dropdown-item, .offcanvas-title,
    .btn, button, label, .form-label, .form-check-label, input, select, textarea, .form-control, .form-select, .input-group-text,
    .badge, .breadcrumb, .nav-tabs, .nav-pills, .pagination, .modal-title, .toast-header,
    th, .table thead, .sidebar, .card-header, .accordion-button {
        font-family: var(--font-ui);
        line-height: var(--lh-ui);
    }

    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 {
        line-height: var(--lh-heading);
        text-wrap: balance;
        text-rendering: optimizeLegibility;
    }
    h1, h2, .h1, .h2, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 { letter-spacing: -0.015em; }
    .text-uppercase, .badge { letter-spacing: 0.04em; }

    /* Errors, hints and alerts must stay plainly readable: reading role, never the display faces. */
    .invalid-feedback, .valid-feedback, .form-text, .alert, .toast-body, .modal-body p {
        font-family: var(--font-reading);
        line-height: var(--lh-reading);
    }

    /* Columns of numbers line up. */
    td, .table, .stat-card, .stat-card-content, .counter, .price, time {
        font-variant-numeric: lining-nums tabular-nums;
    }

    /* Long-form article bodies. */
    .article__body {
        line-height: var(--lh-reading);
        font-variant-numeric: oldstyle-nums proportional-nums;
        hanging-punctuation: first;
    }

@if ($surface !== 'admin')
    /* ---- Card headlines: Bodoni Moda with optical sizing ---- */
    .card-title, .card-body > h3, .card-body > h4, .card-body > h5,
    .service-card h3, .news-card h3, .testimonial-card h4, .team-showcase-card h3,
    .product-item h4, .award-title,
    .news-card__title, .project-card__title, .news-featured__title, .news-list-title {
        font-family: var(--font-card-title);
        font-optical-sizing: auto;
        line-height: var(--lh-card-title);
        letter-spacing: 0;
        text-wrap: balance;
        text-rendering: optimizeLegibility;
    }

    /* ---- Card paragraphs: Manrope (clean sans under the high-contrast Bodoni titles) ----
       Parent-qualified selectors so page styles that only set colour/margins don't reset the face. */
    .card .card-text, .card-description, .service-card p,
    .news-card .news-card__excerpt, .news-featured .news-featured__excerpt,
    .project-card .project-card__text, .product-item p {
        font-family: var(--font-card-text);
        font-weight: 400;
        /* Never below 15px so descriptions stay legible on mobile. */
        font-size: max(0.9375rem, 1em);
        line-height: var(--lh-card-text);
        letter-spacing: 0;
    }
@endif
</style>
