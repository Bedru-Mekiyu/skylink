{{--
    Site-wide typography system. One font request, semantic role tokens.
    Public site roles:
      heading   = Bodoni Moda       h1-h3, hero/page/section titles, card titles
      card-text = Playfair Display  short card descriptions
      body      = Belleza           base text: paragraphs, nav, footer, links (single weight 400)
      ui        = Manrope           text that needs real bold: buttons, form controls, badges, stats, strong
      reading   = EB Garamond       long-form news article bodies only
    Ethiopic (Amharic) glyphs fall through to Noto Serif/Sans Ethiopic per glyph; none of the
    Latin faces carry Ge'ez.
    $surface = 'admin' keeps the admin on its interface fonts (Manrope/EB Garamond), unchanged.
--}}
@php($surface = $surface ?? 'site')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Belleza&family=Bodoni+Moda:opsz,wght@6..96,400..700&family=EB+Garamond:ital,wght@0,400..700;1,400..700&family=Manrope:wght@400..800&family=Noto+Sans+Ethiopic:wght@400..700&family=Noto+Serif+Ethiopic:wght@400..700&family=Playfair+Display:wght@400..600&display=swap" rel="stylesheet">
<style>
    :root {
        --font-heading: 'Bodoni Moda', 'Noto Serif Ethiopic', 'Nyala', 'Didot', 'Bodoni 72', Georgia, 'Times New Roman', serif;
        --font-card-text: 'Playfair Display', 'Noto Serif Ethiopic', 'Nyala', Georgia, 'Times New Roman', serif;
        --font-body: 'Belleza', 'Noto Sans Ethiopic', 'Nyala', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        --font-ui: 'Manrope', 'Noto Sans Ethiopic', 'Nyala', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        --font-reading: 'EB Garamond', 'Noto Serif Ethiopic', 'Nyala', Garamond, Georgia, 'Times New Roman', serif;
        --font-card-title: var(--font-heading);
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

        --lh-body: 1.65;
        --lh-reading: 1.65;
        --lh-ui: 1.35;
        --lh-heading: 1.15;
        --lh-card-title: 1.15;
        --lh-card-text: 1.6;

        /* Bootstrap reads these for body, inputs and buttons. */
        --bs-body-font-family: {{ $surface === 'admin' ? 'var(--font-reading)' : 'var(--font-body)' }};
        --bs-font-sans-serif: var(--font-ui);
        --bs-font-monospace: var(--font-mono);
    }

    body {
        font-family: var(--bs-body-font-family);
        font-kerning: normal;
        font-variant-ligatures: common-ligatures;
        /* Never fake bold/italic: Belleza ships a single regular weight and no italic. */
        font-synthesis: none;
    }

    p, li, blockquote, figcaption, dd { text-wrap: pretty; }
    code, kbd, pre, samp { font-family: var(--font-mono); }

    /* Columns of numbers line up. */
    td, .table, .stat-card, .stat-card-content, .counter, .price, time {
        font-variant-numeric: lining-nums tabular-nums;
    }

@if ($surface === 'admin')
    /* ---- Admin: interface role everywhere, as before ---- */
    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6,
    nav, .navbar, .nav-link, .navbar-brand, .dropdown-menu, .dropdown-item, .offcanvas-title,
    .btn, button, label, .form-label, .form-check-label, input, select, textarea, .form-control, .form-select, .input-group-text,
    .badge, .breadcrumb, .nav-tabs, .nav-pills, .pagination, .modal-title, .toast-header,
    th, .table thead, .sidebar, .card-header, .accordion-button {
        font-family: var(--font-ui);
        line-height: var(--lh-ui);
    }
    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { line-height: var(--lh-heading); text-wrap: balance; }
    h1, h2, .h1, .h2 { letter-spacing: -0.015em; }
    .text-uppercase, .badge { letter-spacing: 0.04em; }
    .invalid-feedback, .valid-feedback, .form-text, .alert, .toast-body, .modal-body p {
        font-family: var(--font-reading);
        line-height: var(--lh-reading);
    }
@else
    /* ---- Body role: Belleza (inherited from body) ---- */
    body { line-height: var(--lh-body); }
    .navbar .nav-link, .dropdown-item, footer a, .breadcrumb { letter-spacing: 0.03em; }

    /* ---- Interface role: Manrope, kept wherever real bold or form legibility matters ---- */
    .btn, button, label, .form-label, .form-check-label, input, select, textarea, .form-control, .form-select, .input-group-text,
    .invalid-feedback, .valid-feedback, .form-text,
    .badge, .pagination, .nav-tabs, .nav-pills, .accordion-button, th, .table thead, .toast-header,
    .stat-number, .stat-item {
        font-family: var(--font-ui);
    }
    /* Zero specificity: bold text and small headings go to Manrope, but heading/card rules below always win. */
    :where(.fw-bold, .fw-bolder, .fw-semibold, .fw-medium, strong, b, h4, h5, h6, .h4, .h5, .h6) {
        font-family: var(--font-ui);
    }
    /* Small bold labels, chips, tags and call-to-action links: Belleza has no bold and is thin
       below ~14px, so these keep Manrope (found by the typography audit, page by page). */
    :where(.btn-get-started, .read-more, .project-card__link, .pf-hero__cta, .news-hero__watch, .article-back,
           .services-label, .news-tab, .portfolio-label, .product-label, .section-tag, .pf-hero__label,
           .category-badge, .news-chip, .news-tag, .news-hero__kind, .news-hero__chip, .news-featured__label,
           .news-video__platform, .share__label, .tech-icon__name, .nav-contact,
           .skylink-card p, .contact-details p, .contact-feature p, .contact-card p,
           .skylink-timeline-mobile p, .skylink-icon ~ p, .skylink-text-gradient,
           .products-track .product-card p) {
        font-family: var(--font-ui);
    }
    .btn, button { letter-spacing: 0.02em; }
    .text-uppercase, .badge { letter-spacing: 0.04em; }

    /* ---- Heading role: Bodoni Moda with optical sizing ---- */
    h1, h2, h3, .h1, .h2, .h3, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6,
    .hero-title, .page-banner__title, .section-title, .pf-hero__title, .modal-title, .offcanvas-title,
    :is(h1, h2, h3) :is(strong, b, .fw-bold, .fw-semibold) {
        font-family: var(--font-heading);
        font-optical-sizing: auto;
        line-height: var(--lh-heading);
        text-wrap: balance;
        text-rendering: optimizeLegibility;
    }
    h1, .h1, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6, .hero-title, .page-banner__title { letter-spacing: -0.015em; }
    h2, .h2 { letter-spacing: -0.01em; }

    /* ---- Card titles: Bodoni Moda (h4/h5 card headings too) ---- */
    .card-title, .card-body > h3, .card-body > h4, .card-body > h5,
    .service-card h3, .service-content h3, .service-content h4, .news-card h3, .testimonial-card h4, .team-showcase-card h3,
    .product-item h4, .award-title,
    .news-card__title, .project-card__title, .news-featured__title, .news-list-title {
        font-family: var(--font-card-title);
        font-optical-sizing: auto;
        line-height: var(--lh-card-title);
        letter-spacing: 0;
        text-wrap: balance;
        text-rendering: optimizeLegibility;
    }

    /* ---- Card paragraphs: Playfair Display ----
       Parent-qualified selectors so page styles that only set colour/margins don't reset the face.
       Known-long card copy (featured news excerpt, product rows) is left on the body role. */
    .card .card-text, .card-description, .service-card p, .service-content p,
    .news-card .news-card__excerpt, .project-card .project-card__text {
        font-family: var(--font-card-text);
        font-weight: 400;
        /* Never below 15px so descriptions stay legible on mobile. */
        font-size: max(0.9375rem, 1em);
        line-height: var(--lh-card-text);
        letter-spacing: 0;
    }

    /* Phones: paragraph and card text never below 16px. `html body` lifts these above the
       single-class size rules in the page styles that load after this partial. */
    @media (max-width: 575.98px) {
        html body :is(.card-description, .service-content p, .card .card-text, .news-card__excerpt, .project-card__text,
                      .news-featured__excerpt, .testimonial-text, .profession, .about-text, .award-year) {
            font-size: max(1rem, 1em);
        }
    }

    /* ---- Long-form news articles: EB Garamond reads best over many paragraphs ---- */
    .article__body, .article__body p, .article__body li, .article__body blockquote {
        font-family: var(--font-reading);
    }
    .article__body {
        line-height: var(--lh-reading);
        font-variant-numeric: oldstyle-nums proportional-nums;
        hanging-punctuation: first;
    }
    .article__body :is(h2, h3) { font-family: var(--font-heading); }
@endif
</style>
