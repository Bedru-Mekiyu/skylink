{{-- Project card styles, shared by /portfolio and the homepage block. Included inside @push('styles'). --}}
<style>
    .project-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 34px rgba(30, 64, 175, 0.16);
    }

    .project-card__media {
        aspect-ratio: 16 / 10;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        overflow: hidden;
    }

    .project-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .project-card:hover .project-card__media img {
        transform: scale(1.04);
    }

    .project-card__body {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 20px 22px 22px;
        flex: 1;
        text-align: left;
    }

    .project-card__title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 600;
        line-height: 1.4;
        color: #1f2937;
    }

    .project-card__text {
        margin: 0;
        color: #6b7280;
        font-size: 0.93rem;
        line-height: 1.7;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .project-card__link {
        margin-top: auto;
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 20px;
        border-radius: 50px;
        background: rgba(59, 130, 246, 0.1);
        color: #3B82F6;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .project-card__link:hover {
        background: #3B82F6;
        color: #fff;
    }

    .project-card__link:focus-visible {
        outline: 3px solid #3B82F6;
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .project-card,
        .project-card__media img {
            transition: none;
        }

        .project-card:hover,
        .project-card:hover .project-card__media img {
            transform: none;
        }
    }
</style>
