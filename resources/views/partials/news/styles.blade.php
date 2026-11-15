{{-- Shared styles for /news and /news/{slug}. Included inside @push('styles'). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    .news-page {
        --news-blue: #3B82F6;
        --news-blue-soft: rgba(59, 130, 246, 0.1);
        --news-navy: #06122b;
        --news-ink: #1f2937;
        --news-muted: #6b7280;
        --news-bg: #f8f9fa;
        --news-radius: 20px;
        --news-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        --news-shadow-hover: 0 14px 34px rgba(30, 64, 175, 0.16);
        background: var(--news-bg);
        color: var(--news-ink);
    }

    /* Amharic: Ge'ez glyphs from Noto Sans Ethiopic, Latin stays Poppins. */
    .news-page,
    .news-page h1, .news-page h2, .news-page h3, .news-page p, .news-page a, .news-page input, .news-page button {
        font-family: 'Poppins', 'Noto Sans Ethiopic', sans-serif;
    }

    [lang="am"] {
        line-height: 1.85;
        letter-spacing: 0;
    }

    /* ----- banner (same family as the About banner) ----- */
    .news-banner {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        min-height: 340px;
        padding: 72px 0 96px;
        color: #fff;
        background: var(--news-navy);
    }

    .news-banner__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 50% 35%;
        z-index: -2;
        animation: newsBannerZoom 26s ease-in-out infinite alternate;
    }

    @keyframes newsBannerZoom {
        from { transform: scale(1); }
        to { transform: scale(1.06); }
    }

    .news-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background:
            linear-gradient(115deg, rgba(59, 130, 246, 0.45) 0%, rgba(59, 130, 246, 0.1) 45%, rgba(59, 130, 246, 0) 70%),
            linear-gradient(180deg, rgba(4, 12, 34, 0.45) 0%, rgba(4, 12, 34, 0.6) 50%, rgba(4, 12, 34, 0.88) 100%);
    }

    .news-banner__curve {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        width: 100%;
        height: 56px;
        fill: var(--news-bg);
    }

    .news-banner h1 {
        color: #fff;
        font-weight: 700;
        font-size: clamp(2rem, 4.5vw, 3.4rem);
        line-height: 1.15;
        margin: 0 0 12px;
        text-shadow: 0 2px 18px rgba(4, 12, 34, 0.5);
    }

    .news-banner p {
        max-width: 720px;
        margin: 0;
        color: rgba(255, 255, 255, 0.88);
        font-size: 1.02rem;
        line-height: 1.7;
    }

    /* ----- chips, search ----- */
    .news-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: -28px 0 32px;
        position: relative;
        z-index: 2;
        background: #fff;
        border-radius: var(--news-radius);
        box-shadow: var(--news-shadow);
        padding: 14px;
    }

    .news-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .news-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 50px;
        background: var(--news-blue-soft);
        color: var(--news-blue);
        font-size: 0.92rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .news-chip:hover {
        background: rgba(59, 130, 246, 0.18);
        color: var(--news-blue);
    }

    .news-chip[aria-current="page"] {
        background: var(--news-blue);
        color: #fff;
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.35);
    }

    .news-chip__count {
        min-width: 24px;
        padding: 1px 7px;
        border-radius: 50px;
        background: rgba(255, 255, 255, 0.75);
        color: var(--news-blue);
        font-size: 0.78rem;
        text-align: center;
    }

    .news-chip[aria-current="page"] .news-chip__count {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    .news-search {
        display: flex;
        flex: 1 1 260px;
        max-width: 380px;
        border: 1px solid #e5e7eb;
        border-radius: 50px;
        overflow: hidden;
        background: #fff;
    }

    .news-search:focus-within {
        border-color: var(--news-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .news-search input {
        flex: 1;
        min-width: 0;
        border: 0;
        padding: 10px 18px;
        font-size: 0.95rem;
        background: transparent;
    }

    .news-search input:focus {
        outline: none;
    }

    .news-search button {
        border: 0;
        background: var(--news-blue);
        color: #fff;
        padding: 0 18px;
    }

    .news-page a:focus-visible,
    .news-page button:focus-visible {
        outline: 3px solid var(--news-blue);
        outline-offset: 3px;
    }

    .news-search button:focus-visible {
        outline-color: var(--news-navy);
    }

    /* ----- cards ----- */
    .news-card {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
        border-radius: var(--news-radius);
        overflow: hidden;
        box-shadow: var(--news-shadow);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .news-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--news-shadow-hover);
    }

    .news-card__media {
        aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        overflow: hidden;
    }

    .news-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .news-card:hover .news-card__media img {
        transform: scale(1.04);
    }

    .news-card__body {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 18px 20px 20px;
        flex: 1;
    }

    .news-tag {
        align-self: flex-start;
        padding: 4px 12px;
        border-radius: 50px;
        background: var(--news-blue-soft);
        color: var(--news-blue);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .news-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 14px;
        color: var(--news-muted);
        font-size: 0.82rem;
    }

    .news-card__title {
        margin: 0;
        font-size: 1.08rem;
        font-weight: 600;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-card__title a {
        color: var(--news-ink);
        text-decoration: none;
    }

    /* The whole card is clickable through the title link. */
    .news-card__title a::after {
        content: '';
        position: absolute;
        inset: 0;
    }

    .news-card__excerpt {
        margin: 0;
        color: var(--news-muted);
        font-size: 0.92rem;
        line-height: 1.65;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-card__more {
        margin-top: auto;
        color: var(--news-blue);
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* ----- featured ----- */
    .news-featured {
        display: grid;
        grid-template-columns: 1.15fr 1fr;
        background: #fff;
        border-radius: var(--news-radius);
        overflow: hidden;
        box-shadow: var(--news-shadow);
        margin-bottom: 36px;
        position: relative;
    }

    .news-featured__media {
        min-height: 320px;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .news-featured__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .news-featured__body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 14px;
        padding: 36px;
    }

    .news-featured__label {
        color: var(--news-blue);
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .news-featured__title {
        margin: 0;
        font-size: clamp(1.4rem, 2.4vw, 1.9rem);
        font-weight: 700;
        line-height: 1.3;
    }

    .news-featured__title a {
        color: var(--news-ink);
        text-decoration: none;
    }

    .news-featured__title a::after {
        content: '';
        position: absolute;
        inset: 0;
    }

    .news-featured__excerpt {
        margin: 0;
        color: var(--news-muted);
        line-height: 1.7;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-btn {
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 24px;
        border-radius: 50px;
        background: linear-gradient(135deg, #3B82F6, rgb(72, 113, 248));
        color: #fff;
        font-weight: 600;
        text-decoration: none;
    }

    @media (max-width: 991.98px) {
        .news-featured {
            grid-template-columns: 1fr;
        }

        .news-featured__media {
            min-height: 0;
            aspect-ratio: 16 / 9;
        }

        .news-featured__body {
            padding: 24px;
        }
    }

    @media (max-width: 575.98px) {
        .news-banner {
            min-height: 260px;
            padding: 48px 0 76px;
        }

        .news-banner__curve {
            height: 40px;
        }

        .news-toolbar {
            margin-top: -20px;
        }

        .news-search {
            max-width: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .news-banner__img {
            animation: none;
        }

        .news-card,
        .news-card__media img {
            transition: none;
        }

        .news-card:hover,
        .news-card:hover .news-card__media img {
            transform: none;
        }
    }
</style>
