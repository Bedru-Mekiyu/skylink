{{-- Sky Link 5-Year Anniversary Styles --}}

<style>
/* ==========================================================================
   ANNIVERSARY ROOT TOKENS & BASE
   ========================================================================== */
:root {
    --anniv-navy-deep: #060d19;
    --anniv-navy-dark: #0a1628;
    --anniv-navy-card: #0e1e36;
    --anniv-navy-glass: rgba(16, 26, 53, 0.88);
    --anniv-blue: #3B82F6;
    --anniv-blue-light: #60A5FA;
    --anniv-cyan: #22D3EE;
    --anniv-cyan-glow: #38bdf8;
    --anniv-violet: #8B5CF6;
    --anniv-magenta: #EC4899;
    --anniv-gold: #F5C451;
    --anniv-midnight: #101A35;
    --anniv-text-light: #e2e8f0;
    --anniv-text-muted: #94a3b8;
}

[lang="am"] .anniv-motion__headline,
[lang="am"] .anniv-circ__headline {
    line-height: 1.48;
    letter-spacing: 0.02em;
}

[lang="am"] .anniv-motion__tagline,
[lang="am"] .anniv-circ__tagline {
    line-height: 1.65;
}

[lang="am"] .anniv-motion__kicker,
[lang="am"] .anniv-circ__kicker {
    letter-spacing: 0.14em;
}

[lang="am"] .anniv-motion__sublabel,
[lang="am"] .anniv-circ__brand {
    letter-spacing: 0.12em;
}

/* ==========================================================================
   1. NAVBAR BADGE EMBLEM
   ========================================================================== */
.anniv-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 12px 4px 8px;
    margin-left: 12px;
    background: linear-gradient(135deg, rgba(14, 30, 54, 0.95) 0%, rgba(10, 22, 40, 0.9) 100%);
    border: 1px solid rgba(56, 189, 248, 0.35);
    border-radius: 999px;
    color: #ffffff;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 2px 10px rgba(6, 13, 25, 0.25), inset 0 0 12px rgba(56, 189, 248, 0.08);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.25s ease,
                box-shadow 0.25s ease;
    overflow: hidden;
    vertical-align: middle;
    z-index: 2;
    touch-action: manipulation;
}

.anniv-badge:hover {
    transform: translateY(-1px);
    border-color: rgba(56, 189, 248, 0.7);
    box-shadow: 0 4px 16px rgba(6, 182, 212, 0.3), inset 0 0 16px rgba(56, 189, 248, 0.16);
}

.anniv-badge:focus-visible {
    outline: 2px solid var(--anniv-cyan);
    outline-offset: 2px;
}

.anniv-badge__glow {
    position: absolute;
    inset: -1px;
    border-radius: 999px;
    pointer-events: none;
    background: radial-gradient(circle at 20% 50%, rgba(56, 189, 248, 0.2), transparent 70%);
}

.anniv-badge__sheen {
    position: absolute;
    top: 0;
    left: -120%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
    transform: skewX(-20deg);
    animation: anniv-badge-sheen 7s ease-in-out infinite;
    pointer-events: none;
}

@keyframes anniv-badge-sheen {
    0%, 75% { left: -120%; }
    88%, 100% { left: 160%; }
}

.anniv-badge__num-wrap {
    position: relative;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 10px rgba(6, 182, 212, 0.45);
    flex-shrink: 0;
}

.anniv-badge__num {
    font-size: 13px;
    font-weight: 800;
    line-height: 1;
    color: #ffffff;
    letter-spacing: -0.02em;
}

.anniv-badge__copy {
    display: flex;
    flex-direction: column;
    text-align: left;
    line-height: 1.1;
}

.anniv-badge__label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #38bdf8;
}

.anniv-badge__sub {
    font-size: 8.5px;
    font-weight: 500;
    letter-spacing: 0.05em;
    color: #cbd5e1;
}

/* Badge on small viewports */
@media (max-width: 576px) {
    .anniv-badge {
        margin-left: 6px;
        padding: 3px 10px 3px 6px;
        gap: 6px;
    }
}
@media (max-width: 400px) {
    .anniv-badge {
        margin-left: 4px;
        padding: 3px 8px 3px 5px;
        gap: 5px;
    }
    .anniv-badge__sub {
        display: none;
    }
    .anniv-badge__label {
        font-size: 9px;
        letter-spacing: 0.08em;
    }
}

/* ==========================================================================
   2. CELEBRATION MODAL PANEL
   ========================================================================== */
body.anniv-modal-open {
    overflow: hidden !important;
}

.anniv-modal {
    position: fixed;
    inset: 0;
    z-index: 10500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    visibility: visible;
    opacity: 1;
    transition: opacity 0.25s ease, visibility 0.25s ease;
}

.anniv-modal[hidden] {
    display: none !important;
    visibility: hidden;
    opacity: 0;
}

.anniv-modal__backdrop {
    position: fixed;
    inset: 0;
    background: rgba(4, 9, 18, 0.78);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 1;
}

.anniv-modal__dialog {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 540px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    background: linear-gradient(180deg, #0d1e38 0%, #081324 100%);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 20px;
    padding: 36px 32px 32px;
    color: #ffffff;
    box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.7),
                0 0 30px rgba(6, 182, 212, 0.2);
    animation: anniv-modal-pop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-modal-pop {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(12px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.anniv-modal__close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, transform 0.2s;
}

.anniv-modal__close:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    transform: rotate(90deg);
}

.anniv-modal__close:focus-visible {
    outline: 2px solid var(--anniv-cyan);
    outline-offset: 2px;
}

.anniv-modal__emblem {
    position: relative;
    width: 68px;
    height: 68px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.anniv-modal__emblem::before {
    content: '';
    position: absolute;
    inset: -14px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.anniv-modal__emblem-num {
    font-size: 34px;
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, #ffffff 30%, #38bdf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.6));
    z-index: 2;
}

.anniv-modal__emblem-rings {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 1px solid rgba(56, 189, 248, 0.35);
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.25);
    animation: anniv-spin-slow 20s linear infinite;
}

.anniv-modal__emblem-rings::before {
    content: '';
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    border: 1px dashed rgba(255, 255, 255, 0.2);
}

@keyframes anniv-spin-slow {
    to { transform: rotate(360deg); }
}

.anniv-modal__kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #38bdf8;
    margin-bottom: 6px;
}

.anniv-modal__title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
    line-height: 1.25;
}

.anniv-modal__tagline {
    font-size: 0.95rem;
    color: #cbd5e1;
    margin-bottom: 12px;
    line-height: 1.5;
}

.anniv-modal__founded {
    display: inline-block;
    padding: 3px 12px;
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.25);
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #7dd3fc;
    margin-bottom: 20px;
}

.anniv-modal__timeline {
    margin: 24px 0 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: left;
}

.anniv-modal__section-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 16px;
}

.anniv-mini-timeline {
    list-style: none;
    padding: 0 0 0 16px;
    margin: 0;
    position: relative;
    border-left: 2px solid rgba(56, 189, 248, 0.25);
}

.anniv-mini-timeline__item {
    position: relative;
    padding-left: 18px;
    margin-bottom: 16px;
}

.anniv-mini-timeline__item:last-child {
    margin-bottom: 0;
}

.anniv-mini-timeline__dot {
    position: absolute;
    left: -22px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #06b6d4;
    border: 2px solid #081324;
    box-shadow: 0 0 8px rgba(6, 182, 212, 0.6);
}

.anniv-mini-timeline__year {
    font-size: 11px;
    font-weight: 700;
    color: #38bdf8;
    display: block;
    line-height: 1.2;
}

.anniv-mini-timeline__title {
    font-size: 13.5px;
    font-weight: 600;
    color: #ffffff;
    margin: 2px 0 0;
}

.anniv-mini-timeline__text {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
    line-height: 1.4;
}

.anniv-modal__footer {
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.anniv-modal__thanks {
    font-size: 13px;
    color: #93c5fd;
    margin-bottom: 16px;
    font-style: italic;
}

.anniv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 24px;
    border-radius: 999px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    cursor: pointer;
}

.anniv-btn--glow {
    background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
    color: #ffffff !important;
    border: none;
    box-shadow: 0 4px 18px rgba(6, 182, 212, 0.35);
}

.anniv-btn--glow:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(6, 182, 212, 0.5);
    color: #ffffff;
}

.anniv-btn--glow:focus-visible {
    outline: 2px solid #ffffff;
    outline-offset: 2px;
}

/* ==========================================================================
   3. HOMEPAGE SECTION "OUR 5 YEARS"
   ========================================================================== */
.anniv-home-section {
    position: relative;
    background: linear-gradient(180deg, #07111e 0%, #0c1a2e 50%, #07111e 100%);
    color: #ffffff;
    padding: 90px 0;
    overflow: hidden;
}

.anniv-home-section__bg-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(56, 189, 248, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56, 189, 248, 0.04) 1px, transparent 1px);
    background-size: 48px 48px;
    pointer-events: none;
}

.anniv-home-section__orbit-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 600px;
    height: 600px;
    transform: translate(-50%, -50%);
    background: radial-gradient(circle, rgba(37, 99, 235, 0.18) 0%, rgba(6, 182, 212, 0.08) 45%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.anniv-home-section__rings {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 520px;
    height: 520px;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    border: 1px solid rgba(56, 189, 248, 0.12);
    box-shadow: 0 0 40px rgba(56, 189, 248, 0.05);
    pointer-events: none;
}

.anniv-home-section__rings::before {
    content: '';
    position: absolute;
    inset: 70px;
    border-radius: 50%;
    border: 1px dashed rgba(255, 255, 255, 0.08);
}

.anniv-home-badge-kicker {
    display: inline-block;
    padding: 6px 18px;
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #38bdf8;
    margin-bottom: 16px;
}

.anniv-home-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 14px;
}

.anniv-home-title .highlight {
    background: linear-gradient(135deg, #ffffff 20%, #38bdf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.anniv-home-tagline {
    font-size: clamp(1rem, 1.8vw, 1.2rem);
    color: #cbd5e1;
    max-width: 640px;
    margin: 0 auto 36px;
    line-height: 1.6;
}

/* Counters row */
.anniv-counters-row {
    margin: 36px 0 54px;
}

.anniv-counter-card {
    background: rgba(14, 30, 54, 0.65);
    border: 1px solid rgba(56, 189, 248, 0.22);
    border-radius: 16px;
    padding: 26px 20px;
    text-align: center;
    backdrop-filter: blur(8px);
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.anniv-counter-card:hover {
    transform: translateY(-4px);
    border-color: rgba(56, 189, 248, 0.55);
}

.anniv-counter-number {
    font-size: clamp(2.2rem, 3.5vw, 3rem);
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, #ffffff, #38bdf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 8px;
    font-variant-numeric: tabular-nums;
}

.anniv-counter-label {
    font-size: 13px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
}

/* Timeline on homepage */
.anniv-timeline-wrap {
    position: relative;
    max-width: 820px;
    margin: 40px auto 50px;
    padding: 0 16px;
}

.anniv-timeline {
    position: relative;
    list-style: none;
    padding: 0;
    margin: 0;
}

.anniv-timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, rgba(56, 189, 248, 0.4) 0%, rgba(37, 99, 235, 0.3) 100%);
    transform: translateX(-50%);
}

.anniv-timeline-item {
    position: relative;
    margin-bottom: 36px;
    display: flex;
    align-items: center;
}

.anniv-timeline-item:last-child {
    margin-bottom: 0;
}

.anniv-timeline-item:nth-child(odd) {
    flex-direction: row-reverse;
}

.anniv-timeline-item:nth-child(odd) .anniv-timeline-content {
    text-align: right;
    padding-right: 32px;
}

.anniv-timeline-item:nth-child(even) .anniv-timeline-content {
    text-align: left;
    padding-left: 32px;
}

.anniv-timeline-content {
    width: 50%;
}

.anniv-timeline-card {
    display: inline-block;
    background: rgba(14, 30, 54, 0.75);
    border: 1px solid rgba(56, 189, 248, 0.2);
    border-radius: 12px;
    padding: 16px 20px;
    backdrop-filter: blur(8px);
    max-width: 320px;
}

.anniv-timeline-year {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    color: #38bdf8;
    letter-spacing: 0.06em;
    margin-bottom: 4px;
}

.anniv-timeline-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 4px;
}

.anniv-timeline-text {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
    line-height: 1.4;
}

.anniv-timeline-node {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #06b6d4;
    border: 3px solid #07111e;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 12px rgba(6, 182, 212, 0.7);
    z-index: 2;
}

@media (max-width: 767.98px) {
    .anniv-timeline::before {
        left: 20px;
    }
    .anniv-timeline-item,
    .anniv-timeline-item:nth-child(odd) {
        flex-direction: row;
        align-items: flex-start;
    }
    .anniv-timeline-node {
        left: 20px;
        top: 24px;
    }
    .anniv-timeline-content,
    .anniv-timeline-item:nth-child(odd) .anniv-timeline-content,
    .anniv-timeline-item:nth-child(even) .anniv-timeline-content {
        width: 100%;
        text-align: left;
        padding-left: 48px;
        padding-right: 0;
    }
    .anniv-timeline-card {
        max-width: 100%;
    }
}

.anniv-home-closing {
    margin-top: 48px;
    text-align: center;
}

.anniv-home-thanks {
    font-size: 1.1rem;
    color: #93c5fd;
    font-style: italic;
    margin-bottom: 24px;
}

/* ==========================================================================
   4. CINEMATIC INTRO (5.5s FIRST VISIT ONLY)
   ========================================================================== */
body.anniv-intro-playing {
    overflow: hidden !important;
}

.anniv-intro {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: #050b14;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    cursor: pointer;
    overflow: hidden;
    user-select: none;
    -webkit-user-select: none;
    contain: strict;
    will-change: transform, opacity;
    animation: anniv-intro-stage 5.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.anniv-intro__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(56, 189, 248, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56, 189, 248, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    opacity: 0;
    will-change: opacity;
    animation: anniv-fade-in 0.8s 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.anniv-intro__particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    transform: translate3d(0, 0, 0);
}

.anniv-intro__particle {
    position: absolute;
    border-radius: 50%;
    background: #38bdf8;
    box-shadow: 0 0 10px #38bdf8;
    opacity: 0;
    will-change: transform, opacity;
}

.anniv-intro__particle:nth-child(1) { width: 3px; height: 3px; top: 25%; left: 30%; animation: anniv-float 4s 0.3s ease-in-out infinite; }
.anniv-intro__particle:nth-child(2) { width: 4px; height: 4px; top: 65%; left: 22%; animation: anniv-float 5s 0.6s ease-in-out infinite; }
.anniv-intro__particle:nth-child(3) { width: 2px; height: 2px; top: 35%; left: 75%; animation: anniv-float 4.5s 0.1s ease-in-out infinite; }
.anniv-intro__particle:nth-child(4) { width: 3px; height: 3px; top: 70%; left: 78%; animation: anniv-float 5.2s 0.8s ease-in-out infinite; }

@keyframes anniv-float {
    0%, 100% { transform: translateY(0); opacity: 0.4; }
    50% { transform: translateY(-16px); opacity: 0.8; }
}

.anniv-intro__skip {
    position: absolute;
    top: 24px;
    right: 28px;
    background: rgba(14, 30, 54, 0.8);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 999px;
    color: #94a3b8;
    padding: 6px 16px;
    font-size: 12.5px;
    font-weight: 600;
    letter-spacing: 0.05em;
    cursor: pointer;
    z-index: 10;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.anniv-intro__skip:hover {
    background: rgba(56, 189, 248, 0.2);
    color: #ffffff;
    border-color: rgba(56, 189, 248, 0.6);
}

.anniv-intro__skip:focus-visible {
    outline: 2px solid var(--anniv-cyan);
    outline-offset: 2px;
}

.anniv-intro__center {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 600px;
    padding: 0 24px;
    will-change: transform, opacity;
}

/* Concentric rings expanding in intro */
.anniv-intro__ring {
    position: absolute;
    top: 50%;
    left: 50%;
    border-radius: 50%;
    border: 1px solid rgba(56, 189, 248, 0.35);
    box-shadow: 0 0 25px rgba(6, 182, 212, 0.2);
    transform: translate(-50%, -50%) scale(0.4);
    opacity: 0;
    pointer-events: none;
    will-change: transform, opacity;
}

.anniv-intro__ring--1 {
    width: 260px;
    height: 260px;
    animation: anniv-ring-expand 2.2s 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.anniv-intro__ring--2 {
    width: 340px;
    height: 340px;
    animation: anniv-ring-expand 2.2s 1.1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-ring-expand {
    0% { transform: translate(-50%, -50%) scale(0.4); opacity: 0; }
    30% { opacity: 0.8; }
    100% { transform: translate(-50%, -50%) scale(1.15); opacity: 0; }
}

/* SVG Stroke drawing "5" */
.anniv-intro__svg {
    width: 140px;
    height: 140px;
    margin: 0 auto 12px;
    display: block;
    overflow: visible;
    will-change: transform, opacity;
}

.anniv-intro__path-bg {
    stroke: rgba(56, 189, 248, 0.12);
    stroke-width: 5;
    fill: none;
}

.anniv-intro__path-fill {
    fill: url(#annivFillGrad);
    opacity: 0;
    will-change: opacity;
    animation: anniv-fill-bloom 1.2s 2.0s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-fill-bloom {
    to { opacity: 0.20; }
}

.anniv-intro__path-trace {
    fill: none;
    stroke: url(#annivTraceGrad);
    stroke-width: 5.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 400;
    stroke-dashoffset: 400;
    will-change: stroke-dashoffset;
    animation: anniv-stroke-draw 1.6s 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.85));
}

@keyframes anniv-stroke-draw {
    to { stroke-dashoffset: 0; }
}

.anniv-intro__years-kicker {
    font-size: clamp(13px, 2.2vw, 16px);
    font-weight: 700;
    letter-spacing: 0.38em;
    text-transform: uppercase;
    color: #38bdf8;
    margin-bottom: 6px;
    opacity: 0;
    transform: translateY(10px);
    will-change: transform, opacity;
    animation: anniv-text-reveal 0.9s 2.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.anniv-intro__brand {
    font-size: clamp(20px, 3.4vw, 26px);
    font-weight: 700;
    letter-spacing: -0.01em;
    color: #ffffff;
    margin-bottom: 16px;
    line-height: 1.3;
    opacity: 0;
    transform: translateY(10px);
    will-change: transform, opacity;
    animation: anniv-text-reveal 0.9s 2.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.anniv-intro__tagline {
    font-size: clamp(14px, 2.2vw, 17px);
    color: #cbd5e1;
    line-height: 1.6;
    max-width: 520px;
    margin: 0 auto;
    opacity: 0;
    transform: translateY(10px);
    will-change: transform, opacity;
    animation: anniv-text-reveal 0.9s 3.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-fade-in {
    to { opacity: 1; }
}

@keyframes anniv-text-reveal {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Master stage fade away at 4.8s-5.5s */
@keyframes anniv-intro-stage {
    0% { opacity: 1; transform: scale(1); }
    86% { opacity: 1; transform: scale(1); }
    98% { opacity: 0; transform: scale(1.04); }
    100% { opacity: 0; transform: scale(1.04); visibility: hidden; }
}

/* ==========================================================================
   5. HOMEPAGE HERO CINEMATIC ORBITAL MOTION CENTERPIECE
   ========================================================================== */
.anniv-hero-motion {
    position: relative;
    max-width: 460px;
    margin: 8px auto 0;
    padding: 24px 20px 20px;
    background: radial-gradient(ellipse at 50% 38%, rgba(8, 22, 54, 0.82) 0%, rgba(4, 12, 32, 0.45) 65%, transparent 100%);
    border-radius: 28px;
    text-align: center;
    color: #ffffff;
    z-index: 2;
    transform: translate3d(0, 0, 0);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.anniv-hero-motion:hover {
    transform: translateY(-2px);
}

/* Open Celestial Stage */
.anniv-motion-stage {
    position: relative;
    width: 290px;
    height: 290px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    outline: none;
    user-select: none;
    -webkit-user-select: none;
    transform: translate3d(0, 0, 0);
}

.anniv-motion-stage:focus-visible {
    outline: 2px solid var(--anniv-cyan);
    border-radius: 50%;
}

.anniv-motion-stage:hover .anniv-motion__ring--outer {
    border-color: rgba(56, 189, 248, 0.6);
}

/* Ambient core glow */
.anniv-motion__ambient-glow {
    position: absolute;
    inset: 20px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.18) 0%, rgba(37, 99, 235, 0.08) 45%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.is-settled .anniv-motion__ambient-glow {
    animation: anniv-ambient-breathe 6s ease-in-out infinite;
}

@keyframes anniv-ambient-breathe {
    0%, 100% { opacity: 0.85; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.06); }
}

/* Faint celestial radar grid */
.anniv-motion__grid {
    position: absolute;
    inset: 10px;
    border-radius: 50%;
    border: 1px solid rgba(56, 189, 248, 0.08);
    pointer-events: none;
}

.anniv-motion__grid::before,
.anniv-motion__grid::after {
    content: '';
    position: absolute;
    background: rgba(56, 189, 248, 0.12);
    pointer-events: none;
}

.anniv-motion__grid::before {
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    transform: translateY(-50%);
}

.anniv-motion__grid::after {
    left: 50%;
    top: 0;
    bottom: 0;
    width: 1px;
    transform: translateX(-50%);
}

/* Astronomical Coordinates / Compass Ticks */
.anniv-motion__ticks {
    position: absolute;
    inset: -6px;
    pointer-events: none;
}

.anniv-motion__tick {
    position: absolute;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.22em;
    color: rgba(56, 189, 248, 0.65);
    text-transform: uppercase;
}

.anniv-motion__tick--n { top: 0; left: 50%; transform: translateX(-50%); }
.anniv-motion__tick--s { bottom: 0; left: 50%; transform: translateX(-50%); }
.anniv-motion__tick--e { right: -4px; top: 50%; transform: translateY(-50%); }
.anniv-motion__tick--w { left: -4px; top: 50%; transform: translateY(-50%); }

/* Concentric Orbital Rings */
.anniv-motion__ring {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    transform-origin: center center;
}

.anniv-motion__ring--outer {
    inset: 18px;
    border: 1px dashed rgba(56, 189, 248, 0.35);
    animation: anniv-orbit-spin 48s linear infinite;
}

.anniv-motion__ring--middle {
    inset: 48px;
    border: 1px solid rgba(37, 99, 235, 0.25);
    animation: anniv-orbit-spin-rev 36s linear infinite;
}

.anniv-motion__ring--inner {
    inset: 78px;
    border: 1px dashed rgba(56, 189, 248, 0.18);
    animation: anniv-orbit-spin 28s linear infinite;
}

/* Photon tracer node (visible during idle) */
.anniv-motion__tracer {
    position: absolute;
    top: 15px;
    left: 50%;
    width: 6px;
    height: 6px;
    margin-left: -3px;
    background: #38bdf8;
    border-radius: 50%;
    box-shadow: 0 0 12px #38bdf8, 0 0 20px #38bdf8;
    pointer-events: none;
    transform-origin: 3px 130px;
    animation: anniv-orbit-spin 14s linear infinite;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.is-settled .anniv-motion__tracer {
    opacity: 1;
}

/* Kinetic Year Nodes (01 -> 02 -> 03 -> 04 -> 05) */
.anniv-motion__nodes {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.anniv-motion__node {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 44px;
    height: 44px;
    margin-top: -22px;
    margin-left: -22px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.4);
    pointer-events: none;
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.anniv-motion__node-core {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #38bdf8;
    box-shadow: 0 0 10px #38bdf8, 0 0 18px #0284c7;
    margin-bottom: 2px;
}

.anniv-motion__node-num {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #ffffff;
    text-shadow: 0 0 8px rgba(56, 189, 248, 0.8);
}

/* Staggered Orbital Entrance & Convergence Animations */
.is-animating .anniv-motion__node--1 { animation: anniv-node-track-1 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.is-animating .anniv-motion__node--2 { animation: anniv-node-track-2 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.is-animating .anniv-motion__node--3 { animation: anniv-node-track-3 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.is-animating .anniv-motion__node--4 { animation: anniv-node-track-4 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.is-animating .anniv-motion__node--5 { animation: anniv-node-track-5 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

@keyframes anniv-node-track-1 {
    0% { opacity: 0; transform: rotate(0deg) translateY(-126px) rotate(0deg) scale(0.3); }
    12% { opacity: 1; transform: rotate(45deg) translateY(-126px) rotate(-45deg) scale(1.1); }
    38% { opacity: 1; transform: rotate(180deg) translateY(-126px) rotate(-180deg) scale(1.0); }
    58% { opacity: 1; transform: rotate(320deg) translateY(-60px) rotate(-320deg) scale(0.85); }
    68% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0.2); }
    100% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0); }
}

@keyframes anniv-node-track-2 {
    0%, 14% { opacity: 0; transform: rotate(72deg) translateY(-126px) rotate(-72deg) scale(0.3); }
    24% { opacity: 1; transform: rotate(115deg) translateY(-126px) rotate(-115deg) scale(1.1); }
    45% { opacity: 1; transform: rotate(240deg) translateY(-126px) rotate(-240deg) scale(1.0); }
    60% { opacity: 1; transform: rotate(330deg) translateY(-50px) rotate(-330deg) scale(0.8); }
    68% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0.2); }
    100% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0); }
}

@keyframes anniv-node-track-3 {
    0%, 25% { opacity: 0; transform: rotate(144deg) translateY(-126px) rotate(-144deg) scale(0.3); }
    34% { opacity: 1; transform: rotate(185deg) translateY(-126px) rotate(-185deg) scale(1.1); }
    50% { opacity: 1; transform: rotate(290deg) translateY(-126px) rotate(-290deg) scale(1.0); }
    62% { opacity: 1; transform: rotate(340deg) translateY(-40px) rotate(-340deg) scale(0.75); }
    68% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0.2); }
    100% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0); }
}

@keyframes anniv-node-track-4 {
    0%, 35% { opacity: 0; transform: rotate(216deg) translateY(-126px) rotate(-216deg) scale(0.3); }
    44% { opacity: 1; transform: rotate(255deg) translateY(-126px) rotate(-255deg) scale(1.1); }
    55% { opacity: 1; transform: rotate(330deg) translateY(-100px) rotate(-330deg) scale(0.95); }
    63% { opacity: 1; transform: rotate(350deg) translateY(-30px) rotate(-350deg) scale(0.7); }
    68% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0.2); }
    100% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0); }
}

@keyframes anniv-node-track-5 {
    0%, 45% { opacity: 0; transform: rotate(288deg) translateY(-126px) rotate(-288deg) scale(0.3); }
    52% { opacity: 1; transform: rotate(320deg) translateY(-126px) rotate(-320deg) scale(1.15); }
    62% { opacity: 1; transform: rotate(355deg) translateY(-60px) rotate(-355deg) scale(0.9); }
    68% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0.2); }
    100% { opacity: 0; transform: rotate(360deg) translateY(0) scale(0); }
}

/* Controlled Radial Light Burst Flare */
.anniv-motion__burst {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    margin-top: 0;
    margin-left: 0;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.95) 0%, rgba(56, 189, 248, 0.8) 35%, rgba(37, 99, 235, 0.4) 65%, transparent 100%);
    box-shadow: 0 0 60px rgba(56, 189, 248, 0.9);
    pointer-events: none;
    opacity: 0;
}

.is-animating .anniv-motion__burst {
    animation: anniv-motion-flare 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-motion-flare {
    0%, 65% { opacity: 0; width: 0; height: 0; margin-top: 0; margin-left: 0; }
    67.5% { opacity: 1; width: 190px; height: 190px; margin-top: -95px; margin-left: -95px; }
    70% { opacity: 0.85; width: 280px; height: 280px; margin-top: -140px; margin-left: -140px; }
    76% { opacity: 0.2; width: 330px; height: 330px; margin-top: -165px; margin-left: -165px; }
    84%, 100% { opacity: 0; width: 350px; height: 350px; margin-top: -175px; margin-left: -175px; }
}

/* Central Grand Numeral 5 Container */
.anniv-motion__numeral-wrap {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.75);
    transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.is-settled .anniv-motion__numeral-wrap {
    opacity: 1;
    transform: scale(1);
    animation: anniv-cinematic-breathe 4s ease-in-out infinite;
}

.is-animating .anniv-motion__numeral-wrap {
    animation: anniv-numeral-reveal 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-numeral-reveal {
    0%, 66% { opacity: 0; transform: scale(0.6); }
    72% { opacity: 1; transform: scale(1.08); }
    80%, 100% { opacity: 1; transform: scale(1); }
}

@keyframes anniv-cinematic-breathe {
    0%, 100% { transform: scale(1); filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.7)); }
    50% { transform: scale(1.02); filter: drop-shadow(0 0 20px rgba(56, 189, 248, 0.95)); }
}

/* SVG Paths */
.anniv-motion__num-svg {
    overflow: visible;
}

.anniv-motion__path-bg {
    stroke: rgba(56, 189, 248, 0.14);
    stroke-width: 5;
    fill: none;
}

.anniv-motion__path-fill {
    fill: url(#cinematic5BloomGrad);
}

.anniv-motion__path-trace {
    fill: none;
    stroke: url(#cinematic5TraceGrad);
    stroke-width: 5.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 14px rgba(56, 189, 248, 0.9));
}

.anniv-motion__specular-flare {
    filter: drop-shadow(0 0 6px #ffffff) drop-shadow(0 0 12px #38bdf8);
    animation: anniv-flare-pulse 3s ease-in-out infinite;
}

@keyframes anniv-flare-pulse {
    0%, 100% { r: 3.4; opacity: 1; filter: drop-shadow(0 0 6px #ffffff) drop-shadow(0 0 12px #38bdf8); }
    50% { r: 4.4; opacity: 0.9; filter: drop-shadow(0 0 9px #ffffff) drop-shadow(0 0 18px #0ea5e9); }
}

/* Content Hierarchy & Reveal */
.anniv-motion__content {
    position: relative;
    z-index: 2;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.is-settled .anniv-motion__content {
    opacity: 1;
    transform: translateY(0);
}

.is-animating .anniv-motion__content {
    animation: anniv-content-stagger 5.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes anniv-content-stagger {
    0%, 75% { opacity: 0; transform: translateY(14px); }
    86%, 100% { opacity: 1; transform: translateY(0); }
}

.anniv-motion__badge-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.anniv-motion__kicker {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.12);
    padding: 3px 12px;
    border-radius: 999px;
    border: 1px solid rgba(56, 189, 248, 0.3);
}

.anniv-motion__years {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.08em;
}

.anniv-motion__sublabel {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.22em;
    color: #cbd5e1;
    text-transform: uppercase;
    margin: 0 0 6px;
}

.anniv-motion__headline {
    font-size: clamp(1.2rem, 2vw, 1.45rem);
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 6px;
    line-height: 1.3;
}

.anniv-motion__tagline {
    font-size: 0.88rem;
    color: #94a3b8;
    line-height: 1.55;
    margin: 0 auto 18px;
    max-width: 360px;
}

.anniv-motion__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.anniv-motion__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 999px;
    background: linear-gradient(135deg, #2563eb, #0284c7);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.anniv-motion__cta i {
    transition: transform 0.25s ease;
}

.anniv-motion__cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(6, 182, 212, 0.55);
    color: #ffffff !important;
}

.anniv-motion__cta:hover i {
    transform: translateY(3px);
}

.anniv-motion__overview-trigger {
    display: inline-flex;
    align-items: center;
    padding: 9px 14px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(56, 189, 248, 0.28);
    color: #38bdf8;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.anniv-motion__overview-trigger:hover {
    background: rgba(56, 189, 248, 0.15);
    border-color: rgba(56, 189, 248, 0.5);
}

/* Tablet & Mobile Motion Stage Adaptation */
@media (max-width: 991.98px) {
    .anniv-hero-motion {
        max-width: 440px;
        margin: 20px auto 0;
        padding: 20px 16px;
    }
    .anniv-motion-stage {
        width: 230px;
        height: 230px;
    }
    .anniv-motion__ring--outer { inset: 12px; }
    .anniv-motion__ring--middle { inset: 36px; }
    .anniv-motion__ring--inner { inset: 60px; }
    .anniv-motion__tracer {
        top: 10px;
        transform-origin: 3px 105px;
    }
    .anniv-motion__num-svg {
        transform: scale(0.85);
    }
}

@media (max-width: 576px) {
    .anniv-hero-motion {
        max-width: 340px;
        margin: 16px auto 0;
        padding: 16px 14px;
    }
    .anniv-motion-stage {
        width: 180px;
        height: 180px;
        margin-bottom: 8px;
    }
    .anniv-motion__ticks { display: none; }
    .anniv-motion__ring--outer { inset: 8px; }
    .anniv-motion__ring--middle { inset: 26px; }
    .anniv-motion__ring--inner { inset: 46px; }
    .anniv-motion__tracer {
        top: 6px;
        transform-origin: 3px 84px;
    }
    .anniv-motion__num-svg {
        transform: scale(0.72);
    }
    .anniv-motion__sublabel {
        font-size: 10px;
        letter-spacing: 0.16em;
    }
    .anniv-motion__headline {
        font-size: 1.1rem;
    }
    .anniv-motion__tagline {
        font-size: 0.8rem;
        margin-bottom: 14px;
    }
    .anniv-motion__actions {
        flex-direction: row;
        gap: 6px;
    }
    .anniv-motion__cta {
        padding: 8px 14px;
        font-size: 12px;
        flex: 1 1 auto;
        justify-content: center;
    }
    .anniv-motion__overview-trigger {
        padding: 8px 12px;
        font-size: 11px;
    }
}

@media (max-width: 360px) {
    .anniv-hero-motion {
        max-width: 290px;
        padding: 12px 10px;
    }
    .anniv-motion-stage {
        width: 156px;
        height: 156px;
        margin-bottom: 6px;
    }
    .anniv-motion__ring--outer { inset: 6px; }
    .anniv-motion__ring--middle { inset: 20px; }
    .anniv-motion__ring--inner { inset: 36px; }
    .anniv-motion__tracer {
        top: 4px;
        transform-origin: 3px 74px;
    }
    .anniv-motion__num-svg {
        transform: scale(0.62);
    }
    .anniv-motion__kicker {
        font-size: 9.5px;
        letter-spacing: 0.2em;
        padding: 2px 8px;
    }
    .anniv-motion__sublabel {
        font-size: 9px;
        letter-spacing: 0.12em;
    }
    .anniv-motion__headline {
        font-size: 1.02rem;
    }
    .anniv-motion__tagline {
        font-size: 0.78rem;
        margin-bottom: 12px;
    }
    .anniv-motion__actions {
        flex-direction: column;
        gap: 6px;
        width: 100%;
    }
    .anniv-motion__cta,
    .anniv-motion__overview-trigger {
        width: 100%;
        justify-content: center;
        padding: 8px 12px;
    }
}

/* ==========================================================================
   5b. REUSABLE CIRCULAR CINEMATIC ANNIVERSARY CENTERPIECE (7-PHASE STORY)
   ========================================================================== */
.anniv-circ-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #ffffff;
    z-index: 2;
    transform: translate3d(0, 0, 0);
}

.anniv-circ-stage {
    position: relative;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    outline: none;
    user-select: none;
    -webkit-user-select: none;
    overflow: hidden;
    transform: translate3d(0, 0, 0);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease;
}

/* Sizing profiles */
.anniv-circ-wrap--hero .anniv-circ-stage {
    width: 310px;
    height: 310px;
    box-shadow: 0 10px 40px rgba(16, 26, 53, 0.6), inset 0 0 35px rgba(34, 211, 238, 0.12);
}

.anniv-circ-wrap--compact .anniv-circ-stage {
    width: 250px;
    height: 250px;
    box-shadow: 0 8px 30px rgba(16, 26, 53, 0.55), inset 0 0 25px rgba(34, 211, 238, 0.1);
}

.anniv-circ-wrap--inline .anniv-circ-stage {
    width: 220px;
    height: 220px;
    box-shadow: 0 6px 25px rgba(16, 26, 53, 0.5), inset 0 0 20px rgba(34, 211, 238, 0.08);
}

.anniv-circ-stage:focus-visible {
    outline: 2px solid var(--anniv-cyan);
    outline-offset: 4px;
}

.anniv-circ-stage:hover {
    transform: scale(1.02);
}

/* Atmosphere background */
.anniv-circ__atmosphere {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: radial-gradient(circle at 50% 50%, rgba(16, 26, 53, 0.95) 0%, rgba(8, 14, 30, 0.98) 75%, rgba(6, 10, 22, 1) 100%);
    pointer-events: none;
}

/* Phase 01: Initial ignition core & pulse */
.anniv-circ__ignite-core {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 14px;
    height: 14px;
    margin-top: -7px;
    margin-left: -7px;
    border-radius: 50%;
    background: radial-gradient(circle, #ffffff 0%, var(--anniv-cyan) 60%, transparent 100%);
    box-shadow: 0 0 18px var(--anniv-cyan), 0 0 30px var(--anniv-blue);
    pointer-events: none;
    opacity: 0;
}

.anniv-circ__pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin-top: -10px;
    margin-left: -10px;
    border-radius: 50%;
    border: 1px solid var(--anniv-cyan);
    pointer-events: none;
    opacity: 0;
}

.anniv-circ__perimeter {
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    border: 1px solid rgba(34, 211, 238, 0.2);
    pointer-events: none;
    opacity: 0;
}

.is-animating .anniv-circ__ignite-core {
    animation: anniv-ignite-core 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.is-animating .anniv-circ__pulse-ring {
    animation: anniv-ignite-pulse 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.is-animating .anniv-circ__perimeter {
    animation: anniv-ignite-perimeter 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-ignite-core {
    0% { opacity: 0; transform: scale(0.2); }
    2.5%, 8% { opacity: 1; transform: scale(1.4); }
    12% { opacity: 0.7; transform: scale(1); }
    16% { opacity: 0; transform: scale(0.4); }
    100% { opacity: 0; transform: scale(0); }
}

@keyframes anniv-ignite-pulse {
    0%, 1% { opacity: 0; transform: scale(0.5); }
    5% { opacity: 0.9; transform: scale(2.2); }
    11% { opacity: 0; transform: scale(4); }
    100% { opacity: 0; transform: scale(4); }
}

@keyframes anniv-ignite-perimeter {
    0%, 2% { opacity: 0.2; transform: scale(0.92); }
    8% { opacity: 1; transform: scale(1); }
    90% { opacity: 1; transform: scale(1); }
    96% { opacity: 0.4; transform: scale(0.96); }
    100% { opacity: 0.2; transform: scale(0.92); }
}

/* Phase 02: Three Concentric Orbital Rings */
.anniv-circ__ring {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    transform-origin: center center;
    opacity: 0;
    transition: opacity 0.6s ease;
}

.anniv-circ__ring--outer {
    inset: 18px;
    border: 1px dashed rgba(34, 211, 238, 0.4);
    animation: anniv-orbit-spin 48s linear infinite;
}

.anniv-circ__ring--middle {
    inset: 48px;
    border: 1px solid rgba(59, 130, 246, 0.35);
    animation: anniv-orbit-spin-rev 36s linear infinite;
}

.anniv-circ__ring--inner {
    inset: 78px;
    border: 1px dashed rgba(139, 92, 246, 0.3);
    animation: anniv-orbit-spin 28s linear infinite;
}

.is-animating .anniv-circ__ring,
.is-settled .anniv-circ__ring {
    opacity: 1;
}

/* Traveling photon nodes */
.anniv-circ__traveler {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0;
}

.anniv-circ__traveler--cyan {
    top: 15px;
    left: 50%;
    margin-left: -3px;
    background: var(--anniv-cyan);
    box-shadow: 0 0 10px var(--anniv-cyan), 0 0 18px var(--anniv-blue);
    transform-origin: 3px 140px;
    animation: anniv-orbit-spin 12s linear infinite;
}

.anniv-circ__traveler--violet {
    top: 45px;
    left: 50%;
    margin-left: -3px;
    background: var(--anniv-violet);
    box-shadow: 0 0 10px var(--anniv-violet), 0 0 16px var(--anniv-magenta);
    transform-origin: 3px 110px;
    animation: anniv-orbit-spin-rev 10s linear infinite;
}

.is-animating .anniv-circ__traveler {
    opacity: 1;
}

/* Phase 03: Sequential Number Stages 1 -> 2 -> 3 -> 4 */
.anniv-circ__seq {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.anniv-circ__seq-num {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 60px;
    height: 60px;
    margin-top: -30px;
    margin-left: -30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
    opacity: 0;
    pointer-events: none;
}

.anniv-circ__seq-num--1 {
    color: var(--anniv-cyan);
    text-shadow: 0 0 16px var(--anniv-cyan);
}

.anniv-circ__seq-num--2 {
    color: var(--anniv-blue);
    text-shadow: 0 0 16px var(--anniv-blue);
}

.anniv-circ__seq-num--3 {
    color: var(--anniv-violet);
    text-shadow: 0 0 16px var(--anniv-violet);
}

.anniv-circ__seq-num--4 {
    color: var(--anniv-magenta);
    text-shadow: 0 0 18px var(--anniv-magenta);
}

.is-animating .anniv-circ__seq-num--1 {
    animation: anniv-seq-step1 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.is-animating .anniv-circ__seq-num--2 {
    animation: anniv-seq-step2 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.is-animating .anniv-circ__seq-num--3 {
    animation: anniv-seq-step3 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

.is-animating .anniv-circ__seq-num--4 {
    animation: anniv-seq-step4 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-seq-step1 {
    0%, 19.5% { opacity: 0; transform: scale(0.3) rotate(-20deg); }
    21.5% { opacity: 1; transform: scale(1.15) rotate(0deg); }
    24.5% { opacity: 0.95; transform: scale(1) rotate(5deg); }
    26.5%, 100% { opacity: 0; transform: scale(0.6) translateY(-25px); }
}

@keyframes anniv-seq-step2 {
    0%, 25.5% { opacity: 0; transform: scale(0.3) rotate(-20deg); }
    27.5% { opacity: 1; transform: scale(1.15) rotate(0deg); }
    30.5% { opacity: 0.95; transform: scale(1) rotate(5deg); }
    32.5%, 100% { opacity: 0; transform: scale(0.6) translateY(-25px); }
}

@keyframes anniv-seq-step3 {
    0%, 31.5% { opacity: 0; transform: scale(0.3) rotate(-20deg); }
    33.5% { opacity: 1; transform: scale(1.15) rotate(0deg); }
    36.5% { opacity: 0.95; transform: scale(1) rotate(5deg); }
    38.5%, 100% { opacity: 0; transform: scale(0.6) translateY(-25px); }
}

@keyframes anniv-seq-step4 {
    0%, 37.5% { opacity: 0; transform: scale(0.3) rotate(-20deg); }
    39.5% { opacity: 1; transform: scale(1.18) rotate(0deg); }
    42.5% { opacity: 0.95; transform: scale(1) rotate(5deg); }
    45%, 100% { opacity: 0; transform: scale(0.5) translateY(-30px); }
}

/* Phase 04: Final Convergence Energy Core */
.anniv-circ__converge-core {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 24px;
    height: 24px;
    margin-top: -12px;
    margin-left: -12px;
    border-radius: 50%;
    background: radial-gradient(circle, #ffffff 0%, var(--anniv-cyan) 45%, var(--anniv-violet) 80%, transparent 100%);
    box-shadow: 0 0 25px var(--anniv-cyan), 0 0 45px var(--anniv-magenta);
    pointer-events: none;
    opacity: 0;
}

.is-animating .anniv-circ__converge-core {
    animation: anniv-converge 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-converge {
    0%, 43% { opacity: 0; transform: scale(0.2); }
    47% { opacity: 1; transform: scale(1.8); filter: brightness(1.6); }
    51.5%, 100% { opacity: 0; transform: scale(0.1); }
}

/* Phase 05: Grand Numeral 5 Reveal */
.anniv-circ__numeral-wrap {
    position: relative;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.7);
    transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.anniv-circ__num-svg {
    overflow: visible;
}

.anniv-circ__path-bg {
    stroke: rgba(34, 211, 238, 0.15);
    stroke-width: 5;
    fill: none;
}

.anniv-circ__path-trace {
    fill: none;
    stroke-width: 5.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 12px var(--anniv-cyan)) drop-shadow(0 0 20px rgba(59, 130, 246, 0.8));
}

.anniv-circ__specular-flare {
    filter: drop-shadow(0 0 6px #ffffff) drop-shadow(0 0 12px var(--anniv-cyan));
    animation: anniv-flare-pulse 3s ease-in-out infinite;
}

.is-settled .anniv-circ__numeral-wrap {
    opacity: 1;
    transform: scale(1);
    animation: anniv-cinematic-breathe 4s ease-in-out infinite;
}

.is-animating .anniv-circ__numeral-wrap {
    animation: anniv-numeral5-reveal 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-numeral5-reveal {
    0%, 49% { opacity: 0; transform: scale(0.4); }
    53.5% { opacity: 1; transform: scale(1.14); filter: drop-shadow(0 0 32px var(--anniv-cyan)); }
    57% { opacity: 1; transform: scale(1); filter: drop-shadow(0 0 16px var(--anniv-cyan)); }
    62% { opacity: 1; transform: scale(1.05); filter: drop-shadow(0 0 28px var(--anniv-cyan)); }
    68%, 88% { opacity: 1; transform: scale(1); filter: drop-shadow(0 0 14px var(--anniv-cyan)); }
    94% { opacity: 0.5; transform: scale(0.96); filter: drop-shadow(0 0 8px var(--anniv-cyan)); }
    98.5%, 100% { opacity: 0; transform: scale(0.85); filter: none; }
}

/* Phase 06: Controlled Multi-Color Celebration Energy Burst */
.anniv-circ__burst-wave {
    position: absolute;
    top: 50%;
    left: 50%;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0;
    z-index: 3;
}

.anniv-circ__burst-wave--cyan {
    background: radial-gradient(circle, rgba(255, 255, 255, 0.95) 0%, rgba(34, 211, 238, 0.85) 30%, rgba(59, 130, 246, 0.4) 65%, transparent 100%);
    box-shadow: 0 0 50px var(--anniv-cyan);
}

.anniv-circ__burst-wave--blue {
    border: 2px solid var(--anniv-blue);
    box-shadow: 0 0 35px var(--anniv-violet);
}

.is-animating .anniv-circ__burst-wave--cyan {
    animation: anniv-burst-cyan 9.6s cubic-bezier(0.08, 0.9, 0.2, 1) infinite;
}

.is-animating .anniv-circ__burst-wave--blue {
    animation: anniv-burst-blue 9.6s cubic-bezier(0.08, 0.9, 0.2, 1) infinite;
}

@keyframes anniv-burst-cyan {
    0%, 58% { opacity: 0; width: 0; height: 0; margin-top: 0; margin-left: 0; }
    60.5% { opacity: 1; width: 220px; height: 220px; margin-top: -110px; margin-left: -110px; }
    65.5% { opacity: 0.25; width: 310px; height: 310px; margin-top: -155px; margin-left: -155px; }
    72%, 100% { opacity: 0; width: 330px; height: 330px; margin-top: -165px; margin-left: -165px; }
}

@keyframes anniv-burst-blue {
    0%, 59% { opacity: 0; width: 0; height: 0; margin-top: 0; margin-left: 0; }
    61.5% { opacity: 0.9; width: 260px; height: 260px; margin-top: -130px; margin-left: -130px; }
    68%, 100% { opacity: 0; width: 320px; height: 320px; margin-top: -160px; margin-left: -160px; }
}

/* 8 Radial Spark Rays (Violet & Magenta) */
.anniv-circ__spark-rays {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.anniv-circ__ray {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2px;
    height: 38px;
    margin-left: -1px;
    margin-top: -19px;
    background: linear-gradient(to top, transparent, var(--anniv-violet), var(--anniv-magenta), #ffffff);
    opacity: 0;
    transform-origin: center center;
    border-radius: 999px;
}

.anniv-circ__ray--1 { transform: rotate(0deg) translateY(-85px); }
.anniv-circ__ray--2 { transform: rotate(45deg) translateY(-85px); }
.anniv-circ__ray--3 { transform: rotate(90deg) translateY(-85px); }
.anniv-circ__ray--4 { transform: rotate(135deg) translateY(-85px); }
.anniv-circ__ray--5 { transform: rotate(180deg) translateY(-85px); }
.anniv-circ__ray--6 { transform: rotate(225deg) translateY(-85px); }
.anniv-circ__ray--7 { transform: rotate(270deg) translateY(-85px); }
.anniv-circ__ray--8 { transform: rotate(315deg) translateY(-85px); }

.is-animating .anniv-circ__ray {
    animation: anniv-ray-burst 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-ray-burst {
    0%, 59.5% { opacity: 0; height: 0; }
    61.5% { opacity: 1; height: 45px; filter: drop-shadow(0 0 10px var(--anniv-magenta)); }
    67.5%, 100% { opacity: 0; height: 60px; transform-origin: center 120px; }
}

/* Gold celebratory embers */
.anniv-circ__embers {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.anniv-circ__ember {
    position: absolute;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--anniv-gold);
    box-shadow: 0 0 10px var(--anniv-gold), 0 0 16px #ffffff;
    opacity: 0;
}

.anniv-circ__ember--1 { top: 28%; left: 32%; }
.anniv-circ__ember--2 { top: 24%; right: 30%; }
.anniv-circ__ember--3 { top: 68%; left: 26%; }
.anniv-circ__ember--4 { top: 72%; right: 28%; }
.anniv-circ__ember--5 { top: 48%; left: 18%; }
.anniv-circ__ember--6 { top: 50%; right: 18%; }

.is-animating .anniv-circ__ember {
    animation: anniv-ember-burst 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-ember-burst {
    0%, 59.5% { opacity: 0; transform: scale(0.2); }
    62% { opacity: 1; transform: scale(1.4); }
    70%, 100% { opacity: 0; transform: scale(0.4) translateY(-14px); }
}

/* Phase 07: Settled Resting State */
.anniv-circ__tracer {
    position: absolute;
    top: 15px;
    left: 50%;
    width: 6px;
    height: 6px;
    margin-left: -3px;
    background: var(--anniv-cyan);
    border-radius: 50%;
    box-shadow: 0 0 12px var(--anniv-cyan), 0 0 20px var(--anniv-cyan);
    pointer-events: none;
    transform-origin: 3px 140px;
    animation: anniv-orbit-spin 16s linear infinite;
    opacity: 0;
    transition: opacity 0.8s ease;
    z-index: 2;
}

.is-settled .anniv-circ__tracer {
    opacity: 1;
}

.anniv-circ__label-box {
    position: absolute;
    bottom: 22px;
    left: 0;
    right: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 5;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    pointer-events: none;
}

.is-settled .anniv-circ__label-box {
    opacity: 1;
    transform: translateY(0);
}

.is-animating .anniv-circ__label-box {
    animation: anniv-label-reveal 9.6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes anniv-label-reveal {
    0%, 65% { opacity: 0; transform: translateY(10px); }
    70%, 88% { opacity: 1; transform: translateY(0); }
    94% { opacity: 0.5; transform: translateY(4px); }
    98.5%, 100% { opacity: 0; transform: translateY(8px); }
}

.anniv-circ__kicker {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.32em;
    color: var(--anniv-cyan);
    text-transform: uppercase;
    background: rgba(34, 211, 238, 0.12);
    border: 1px solid rgba(34, 211, 238, 0.35);
    padding: 2px 10px;
    border-radius: 999px;
    margin-bottom: 2px;
}

.anniv-circ__brand {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.18em;
    color: #94a3b8;
    text-transform: uppercase;
}

/* Supporting Corporate Copy Wrap */
.anniv-circ__copy-wrap {
    margin-top: 14px;
    max-width: 420px;
    text-align: center;
}

.anniv-circ__headline {
    font-size: clamp(1.15rem, 2vw, 1.38rem);
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 6px;
    line-height: 1.32;
}

.anniv-circ__tagline {
    font-size: 0.88rem;
    color: #94a3b8;
    line-height: 1.55;
    margin: 0 auto;
}

/* Responsive adjustments for the circular centerpiece */
@media (max-width: 991.98px) {
    .anniv-circ-wrap--hero .anniv-circ-stage {
        width: 250px;
        height: 250px;
    }
    .anniv-circ-wrap--hero .anniv-circ__tracer {
        transform-origin: 3px 110px;
    }
    .anniv-circ-wrap--hero .anniv-circ__num-svg {
        transform: scale(0.85);
    }
}

@media (max-width: 576px) {
    .anniv-circ-wrap--hero .anniv-circ-stage,
    .anniv-circ-wrap--compact .anniv-circ-stage {
        width: 210px;
        height: 210px;
    }
    .anniv-circ-wrap--hero .anniv-circ__ring--outer,
    .anniv-circ-wrap--compact .anniv-circ__ring--outer { inset: 10px; }
    .anniv-circ-wrap--hero .anniv-circ__ring--middle,
    .anniv-circ-wrap--compact .anniv-circ__ring--middle { inset: 32px; }
    .anniv-circ-wrap--hero .anniv-circ__ring--inner,
    .anniv-circ-wrap--compact .anniv-circ__ring--inner { inset: 54px; }
    .anniv-circ-wrap--hero .anniv-circ__tracer,
    .anniv-circ-wrap--compact .anniv-circ__tracer {
        top: 8px;
        transform-origin: 3px 97px;
    }
    .anniv-circ-wrap--hero .anniv-circ__num-svg,
    .anniv-circ-wrap--compact .anniv-circ__num-svg {
        transform: scale(0.72);
    }
    .anniv-circ__label-box {
        bottom: 14px;
    }
    .anniv-circ__kicker {
        font-size: 9.5px;
        letter-spacing: 0.22em;
    }
    .anniv-circ__brand {
        display: none;
    }
    .anniv-circ__headline {
        font-size: 1.05rem;
    }
    .anniv-circ__tagline {
        font-size: 0.8rem;
    }
}

@media (max-width: 360px) {
    .anniv-circ-wrap--hero .anniv-circ-stage,
    .anniv-circ-wrap--compact .anniv-circ-stage {
        width: 175px;
        height: 175px;
    }
    .anniv-circ-wrap--hero .anniv-circ__ring--outer,
    .anniv-circ-wrap--compact .anniv-circ__ring--outer { inset: 6px; }
    .anniv-circ-wrap--hero .anniv-circ__ring--middle,
    .anniv-circ-wrap--compact .anniv-circ__ring--middle { inset: 24px; }
    .anniv-circ-wrap--hero .anniv-circ__ring--inner,
    .anniv-circ-wrap--compact .anniv-circ__ring--inner { inset: 42px; }
    .anniv-circ-wrap--hero .anniv-circ__tracer,
    .anniv-circ-wrap--compact .anniv-circ__tracer {
        top: 5px;
        transform-origin: 3px 82px;
    }
    .anniv-circ-wrap--hero .anniv-circ__num-svg,
    .anniv-circ-wrap--compact .anniv-circ__num-svg {
        transform: scale(0.62);
    }
    .anniv-circ__kicker {
        font-size: 8.5px;
        letter-spacing: 0.16em;
        padding: 1px 6px;
    }
}

/* ==========================================================================
   6. NEWS PAGE EDITORIAL ANNIVERSARY CARD
   ========================================================================== */
.anniv-news-card {
    position: relative;
    margin: 10px 0 26px;
    padding: 22px 26px;
    background: linear-gradient(135deg, rgba(8, 20, 48, 0.96) 0%, rgba(15, 28, 56, 0.94) 100%);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 20px;
    box-shadow: 0 12px 35px rgba(2, 6, 23, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                inset 0 0 20px rgba(56, 189, 248, 0.05);
    overflow: hidden;
    color: #ffffff;
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.anniv-news-card:hover {
    transform: translateY(-2px);
    border-color: rgba(56, 189, 248, 0.5);
    box-shadow: 0 16px 40px rgba(2, 6, 23, 0.35), 0 0 25px rgba(56, 189, 248, 0.15);
}

.anniv-news-card__glow {
    position: absolute;
    top: -40px;
    left: -40px;
    width: 170px;
    height: 170px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.anniv-news-card__sheen {
    position: absolute;
    top: 0;
    left: -120%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    transform: skewX(-20deg);
    animation: anniv-badge-sheen 10s ease-in-out infinite;
    pointer-events: none;
}

.anniv-news-card__inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    flex-wrap: wrap;
    z-index: 1;
}

/* Distinct Circular Orbital 5 Emblem with Sequential Year Markers */
.anniv-news-card__emblem-stage {
    position: relative;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.anniv-news-card__orbit-ring {
    position: absolute;
    inset: -3px;
    border: 1px dashed rgba(56, 189, 248, 0.45);
    border-radius: 50%;
    animation: anniv-orbit-spin 26s linear infinite;
}

.anniv-news-card__dot-year {
    position: absolute;
    font-size: 7px;
    font-weight: 800;
    color: #38bdf8;
    line-height: 1;
    transition: color 0.25s ease, text-shadow 0.25s ease;
}

.anniv-news-card:hover .anniv-news-card__dot-year {
    color: #ffffff;
    text-shadow: 0 0 6px #38bdf8;
}

.anniv-news-card__dot-year--1 { top: -6px; left: 50%; transform: translateX(-50%); }
.anniv-news-card__dot-year--2 { top: 12px; right: -8px; }
.anniv-news-card__dot-year--3 { bottom: 2px; right: -4px; }
.anniv-news-card__dot-year--4 { bottom: 2px; left: -4px; }
.anniv-news-card__dot-year--5 { top: 12px; left: -8px; }

.anniv-news-card__emblem-core {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #06b6d4 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 16px rgba(6, 182, 212, 0.45);
}

.anniv-news-card__emblem-num {
    font-size: 20px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.anniv-news-card__emblem-unit {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.12em;
    color: #38bdf8;
    margin-top: -1px;
}

.anniv-news-card__text {
    flex: 1 1 320px;
}

.anniv-news-card__meta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #38bdf8;
    font-weight: 700;
    margin-bottom: 4px;
}

.anniv-news-card__sep {
    opacity: 0.5;
}

.anniv-news-card__edition {
    color: #94a3b8;
}

.anniv-news-card__title {
    font-size: 1.18rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 4px;
    line-height: 1.35;
}

.anniv-news-card__tagline {
    font-size: 0.88rem;
    color: #cbd5e1;
    margin: 0;
    line-height: 1.5;
}

.anniv-news-card__action {
    flex-shrink: 0;
}

.anniv-news-card__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    border-radius: 999px;
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
}

.anniv-news-card__cta i {
    transition: transform 0.25s ease;
}

.anniv-news-card__cta:hover {
    background: linear-gradient(135deg, #2563eb, #0284c7);
    border-color: transparent;
    color: #ffffff !important;
    transform: translateX(3px);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
}

.anniv-news-card__cta:hover i {
    transform: translateX(3px);
}

@media (max-width: 767.98px) {
    .anniv-news-card {
        padding: 16px 18px;
    }
    .anniv-news-card__action {
        width: 100%;
        margin-top: 6px;
    }
    .anniv-news-card__cta {
        width: 100%;
        justify-content: center;
    }
}

/* ==========================================================================
   7. ABOUT PAGE JOURNEY ANNIVERSARY CULMINATION (FULL CIRCULAR SHOWCASE)
   ========================================================================== */
.anniv-about-showcase {
    position: relative;
    max-width: 920px;
    margin: 50px auto 0;
    padding: 40px 24px;
    background: radial-gradient(circle at 50% 25%, rgba(26, 42, 86, 0.75) 0%, rgba(16, 26, 53, 0.95) 65%, #0d1527 100%);
    border-radius: 24px;
    border: 1px solid rgba(34, 211, 238, 0.35);
    box-shadow: 0 20px 50px rgba(10, 16, 32, 0.6), inset 0 0 40px rgba(34, 211, 238, 0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    overflow: hidden;
}

.anniv-about-showcase__badge-strip {
    margin-bottom: 24px;
}

.anniv-about-showcase__pill {
    display: inline-block;
    padding: 6px 18px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--anniv-cyan);
    background: rgba(34, 211, 238, 0.12);
    border: 1px solid rgba(34, 211, 238, 0.4);
    border-radius: 999px;
    box-shadow: 0 0 16px rgba(34, 211, 238, 0.2);
}

.anniv-about-showcase__centerpiece {
    width: 100%;
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .anniv-about-showcase {
        margin: 35px auto 0;
        padding: 30px 16px;
        border-radius: 18px;
    }
}

.anniv-about-feature {
    position: relative;
    max-width: 820px;
    margin: 40px auto 0;
    padding: 22px 28px;
    background: linear-gradient(145deg, rgba(8, 20, 48, 0.94) 0%, rgba(15, 28, 54, 0.9) 100%);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 18px;
    box-shadow: 0 12px 36px rgba(2, 6, 23, 0.32),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
    overflow: hidden;
    color: #ffffff;
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.anniv-about-feature:hover {
    transform: translateY(-2px);
    border-color: rgba(56, 189, 248, 0.5);
    box-shadow: 0 16px 45px rgba(2, 6, 23, 0.4), 0 0 28px rgba(56, 189, 248, 0.15);
}

.anniv-about-feature__glow {
    position: absolute;
    top: -50px;
    right: -50px;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.anniv-about-feature__inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    flex-wrap: wrap;
    z-index: 1;
}

.anniv-about-feature__badge {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #06b6d4 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 18px rgba(6, 182, 212, 0.45);
}

.anniv-about-feature__num {
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.anniv-about-feature__unit {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.18em;
    color: #38bdf8;
    margin-top: -2px;
}

.anniv-about-feature__text {
    flex: 1 1 320px;
}

.anniv-about-feature__tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #38bdf8;
    font-weight: 700;
    margin-bottom: 4px;
}

.anniv-about-feature__sep {
    opacity: 0.5;
}

.anniv-about-feature__year {
    background: rgba(56, 189, 248, 0.15);
    padding: 2px 8px;
    border-radius: 999px;
}

.anniv-about-feature__title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 4px;
    line-height: 1.35;
}

.anniv-about-feature__desc {
    font-size: 0.88rem;
    color: #cbd5e1;
    margin: 0;
    line-height: 1.5;
}

.anniv-about-feature__cta-wrap {
    flex-shrink: 0;
}

.anniv-about-feature__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 999px;
    background: linear-gradient(135deg, #2563eb, #0284c7);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.anniv-about-feature__cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(6, 182, 212, 0.45);
    color: #ffffff !important;
}

@media (max-width: 767.98px) {
    .anniv-about-feature {
        padding: 18px 18px;
        margin-top: 26px;
    }
    .anniv-about-feature__cta-wrap {
        width: 100%;
        margin-top: 6px;
    }
    .anniv-about-feature__cta {
        width: 100%;
        justify-content: center;
    }
}

/* ==========================================================================
   7b. PRODUCTS, SERVICES & PORTFOLIO / PROJECTS ANNIVERSARY FEATURES
   ========================================================================== */
.anniv-product-feature,
.anniv-services-feature,
.anniv-portfolio-feature {
    position: relative;
    margin: 10px 0 28px;
    padding: 24px 28px;
    background: radial-gradient(ellipse at 80% 50%, rgba(16, 26, 53, 0.96) 0%, rgba(10, 18, 38, 0.94) 100%);
    border: 1px solid rgba(34, 211, 238, 0.3);
    border-radius: 24px;
    box-shadow: 0 12px 35px rgba(2, 6, 23, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.12);
    color: #ffffff;
    overflow: hidden;
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.anniv-product-feature:hover,
.anniv-services-feature:hover,
.anniv-portfolio-feature:hover {
    transform: translateY(-2px);
    border-color: rgba(34, 211, 238, 0.5);
}

.anniv-product-feature__inner,
.anniv-services-feature__inner,
.anniv-portfolio-feature__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.anniv-product-feature__text,
.anniv-services-feature__text,
.anniv-portfolio-feature__text {
    flex: 1 1 360px;
}

.anniv-product-feature__kicker,
.anniv-services-feature__kicker,
.anniv-portfolio-feature__kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--anniv-cyan);
    background: rgba(34, 211, 238, 0.12);
    border: 1px solid rgba(34, 211, 238, 0.3);
    padding: 3px 12px;
    border-radius: 999px;
    margin-bottom: 8px;
}

.anniv-product-feature__title,
.anniv-services-feature__title,
.anniv-portfolio-feature__title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 6px;
    line-height: 1.3;
}

.anniv-product-feature__desc,
.anniv-services-feature__desc,
.anniv-portfolio-feature__desc {
    font-size: 0.92rem;
    color: #cbd5e1;
    margin: 0;
    line-height: 1.55;
}

.anniv-product-feature__centerpiece,
.anniv-services-feature__centerpiece,
.anniv-portfolio-feature__centerpiece {
    flex-shrink: 0;
}

@media (max-width: 767.98px) {
    .anniv-product-feature,
    .anniv-services-feature,
    .anniv-portfolio-feature {
        padding: 18px 16px;
    }
    .anniv-product-feature__inner,
    .anniv-services-feature__inner,
    .anniv-portfolio-feature__inner {
        flex-direction: column-reverse;
        align-items: center;
        text-align: center;
        gap: 16px;
    }
}

/* ==========================================================================
   8. REDUCED MOTION SAFEGUARDS
   ========================================================================== */
@media (prefers-reduced-motion: reduce) {
    .anniv-badge__sheen,
    .anniv-modal__emblem-rings,
    .anniv-intro,
    .anniv-motion__ring,
    .anniv-motion__tracer,
    .anniv-motion__burst,
    .anniv-motion__node,
    .anniv-motion__numeral-wrap,
    .anniv-motion__content,
    .anniv-news-card__sheen,
    .anniv-news-card__orbit-ring,
    .anniv-circ__ring,
    .anniv-circ__traveler,
    .anniv-circ__tracer,
    .anniv-circ__burst-wave,
    .anniv-circ__ray,
    .anniv-circ__ember,
    .anniv-circ__ignite-core,
    .anniv-circ__pulse-ring,
    .anniv-circ__perimeter,
    .anniv-circ__converge-core,
    .anniv-circ__seq-num,
    .anniv-circ__numeral-wrap,
    .anniv-circ__label-box {
        animation: none !important;
    }
    .anniv-intro,
    .anniv-motion__burst,
    .anniv-motion__nodes,
    .anniv-circ__seq,
    .anniv-circ__spark-rays,
    .anniv-circ__embers,
    .anniv-circ__burst-wave,
    .anniv-circ__ignite-core,
    .anniv-circ__pulse-ring,
    .anniv-circ__converge-core {
        display: none !important;
    }
    .anniv-motion__numeral-wrap,
    .anniv-motion__content,
    .anniv-circ__numeral-wrap,
    .anniv-circ__label-box {
        opacity: 1 !important;
        transform: none !important;
    }
    .anniv-badge:hover,
    .anniv-counter-card:hover,
    .anniv-hero-motion:hover,
    .anniv-news-card:hover,
    .anniv-about-feature:hover,
    .anniv-product-feature:hover,
    .anniv-services-feature:hover,
    .anniv-portfolio-feature:hover,
    .anniv-circ-stage:hover {
        transform: none !important;
    }
}
</style>
