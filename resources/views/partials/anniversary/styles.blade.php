{{-- Sky Link 5-Year Anniversary Styles --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* ==========================================================================
   ANNIVERSARY ROOT TOKENS & BASE
   ========================================================================== */
:root {
    --anniv-navy-deep: #060d19;
    --anniv-navy-dark: #0a1628;
    --anniv-navy-card: #0e1e36;
    --anniv-navy-glass: rgba(10, 22, 40, 0.88);
    --anniv-blue: #2563eb;
    --anniv-blue-light: #3b82f6;
    --anniv-cyan: #06b6d4;
    --anniv-cyan-glow: #38bdf8;
    --anniv-text-light: #e2e8f0;
    --anniv-text-muted: #94a3b8;
    --anniv-font-ethiopic: 'Noto Sans Ethiopic', 'Nyala', 'Poppins', sans-serif;
}

[lang="am"] {
    font-family: var(--anniv-font-ethiopic) !important;
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
   5. HOMEPAGE HERO ANNIVERSARY CENTERPIECE
   ========================================================================== */
.anniv-hero-card {
    position: relative;
    max-width: 440px;
    margin: 0 auto;
    padding: 30px 26px 26px;
    background: linear-gradient(145deg, rgba(8, 20, 48, 0.78) 0%, rgba(5, 13, 31, 0.84) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(56, 189, 248, 0.28);
    border-radius: 24px;
    box-shadow: 0 25px 60px rgba(2, 6, 23, 0.55),
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                inset 0 0 30px rgba(56, 189, 248, 0.05);
    overflow: hidden;
    text-align: center;
    color: #ffffff;
    z-index: 2;
    transform: translate3d(0, 0, 0);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.35s ease,
                box-shadow 0.35s ease;
}

.anniv-hero-card:hover {
    transform: translateY(-3px);
    border-color: rgba(56, 189, 248, 0.45);
    box-shadow: 0 30px 70px rgba(2, 6, 23, 0.65),
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                0 0 35px rgba(56, 189, 248, 0.15);
}

.anniv-hero-card__ambient-glow {
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

/* Orbital Stage */
.anniv-hero-card__stage {
    position: relative;
    width: 130px;
    height: 130px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.anniv-hero-card__ring {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    transform-origin: center center;
}

.anniv-hero-card__ring--outer {
    inset: 0;
    border: 1px dashed rgba(56, 189, 248, 0.32);
    animation: anniv-orbit-spin 48s linear infinite;
}

.anniv-hero-card__ring--inner {
    inset: 12px;
    border: 1px solid rgba(37, 99, 235, 0.28);
    animation: anniv-orbit-spin-rev 36s linear infinite;
}

.anniv-hero-card__orbit-node {
    position: absolute;
    top: -3px;
    left: 50%;
    width: 7px;
    height: 7px;
    margin-left: -3.5px;
    background: #38bdf8;
    border-radius: 50%;
    box-shadow: 0 0 10px #38bdf8, 0 0 18px #38bdf8;
    pointer-events: none;
    transform-origin: 3.5px 68px;
    animation: anniv-orbit-spin 18s linear infinite;
}

@keyframes anniv-orbit-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes anniv-orbit-spin-rev {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

/* SVG Numeral 5 in hero card */
.anniv-hero-card__num-svg {
    position: relative;
    z-index: 1;
    overflow: visible;
}

.anniv-hero-card__path-bg {
    stroke: rgba(56, 189, 248, 0.12);
    stroke-width: 5;
    fill: none;
}

.anniv-hero-card__path-fill {
    fill: url(#hero5BloomGrad);
}

.anniv-hero-card__path-trace {
    fill: none;
    stroke: url(#hero5TraceGrad);
    stroke-width: 5.2;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.85));
}

/* Card Content */
.anniv-hero-card__badge-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.anniv-hero-card__kicker {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.12);
    padding: 3px 10px;
    border-radius: 999px;
    border: 1px solid rgba(56, 189, 248, 0.28);
}

.anniv-hero-card__years {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.06em;
}

.anniv-hero-card__sublabel {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.22em;
    color: #cbd5e1;
    text-transform: uppercase;
    margin: 0 0 6px;
}

.anniv-hero-card__headline {
    font-size: clamp(1.2rem, 2vw, 1.4rem);
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 8px;
    line-height: 1.3;
}

.anniv-hero-card__tagline {
    font-size: 0.88rem;
    color: #94a3b8;
    line-height: 1.55;
    margin: 0 auto 18px;
    max-width: 360px;
}

.anniv-hero-card__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.anniv-hero-card__cta {
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
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.anniv-hero-card__cta i {
    transition: transform 0.25s ease;
}

.anniv-hero-card__cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(6, 182, 212, 0.5);
    color: #ffffff !important;
}

.anniv-hero-card__cta:hover i {
    transform: translateY(3px);
}

.anniv-hero-card__badge-trigger {
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

.anniv-hero-card__badge-trigger:hover {
    background: rgba(56, 189, 248, 0.15);
    border-color: rgba(56, 189, 248, 0.5);
}

/* Tablet & Mobile Hero Card Adaptation */
@media (min-width: 992px) {
    .anniv-hero-card {
        margin-top: 8px;
    }
}

@media (max-width: 991.98px) {
    .anniv-hero-card {
        max-width: 460px;
        margin: 24px auto 0;
        padding: 24px 20px;
    }
    .anniv-hero-card__stage {
        width: 110px;
        height: 110px;
        margin-bottom: 12px;
    }
    .anniv-hero-card__ring--inner {
        inset: 10px;
    }
    .anniv-hero-card__num-svg {
        transform: scale(0.88);
    }
}

@media (max-width: 576px) {
    .anniv-hero-card {
        max-width: 350px;
        padding: 16px 16px 18px;
        border-radius: 18px;
        margin-top: 18px;
    }
    .anniv-hero-card__stage {
        width: 86px;
        height: 86px;
        margin-bottom: 8px;
    }
    .anniv-hero-card__ring--inner {
        inset: 8px;
    }
    .anniv-hero-card__num-svg {
        transform: scale(0.72);
    }
    .anniv-hero-card__sublabel {
        font-size: 10px;
        letter-spacing: 0.16em;
        margin-bottom: 4px;
    }
    .anniv-hero-card__headline {
        font-size: 1.08rem;
        margin-bottom: 4px;
    }
    .anniv-hero-card__tagline {
        font-size: 0.8rem;
        margin-bottom: 12px;
        line-height: 1.45;
    }
    .anniv-hero-card__actions {
        flex-direction: row;
        gap: 6px;
    }
    .anniv-hero-card__cta {
        padding: 8px 14px;
        font-size: 12px;
        flex: 1 1 auto;
        justify-content: center;
    }
    .anniv-hero-card__badge-trigger {
        padding: 8px 12px;
        font-size: 11px;
    }
}

/* ==========================================================================
   6. NEWS PAGE EDITORIAL ANNIVERSARY CARD
   ========================================================================== */
.anniv-news-card {
    position: relative;
    margin: 10px 0 26px;
    padding: 20px 24px;
    background: linear-gradient(135deg, rgba(8, 20, 48, 0.95) 0%, rgba(15, 28, 54, 0.92) 100%);
    border: 1px solid rgba(56, 189, 248, 0.26);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(2, 6, 23, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
    overflow: hidden;
    color: #ffffff;
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.anniv-news-card:hover {
    transform: translateY(-2px);
    border-color: rgba(56, 189, 248, 0.45);
    box-shadow: 0 14px 35px rgba(2, 6, 23, 0.3), 0 0 25px rgba(56, 189, 248, 0.12);
}

.anniv-news-card__glow {
    position: absolute;
    top: -40px;
    left: -40px;
    width: 160px;
    height: 160px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.anniv-news-card__inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    z-index: 1;
}

.anniv-news-card__emblem-wrap {
    position: relative;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #06b6d4 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 16px rgba(6, 182, 212, 0.4);
}

.anniv-news-card__emblem-orbit {
    position: absolute;
    inset: -4px;
    border: 1px dashed rgba(56, 189, 248, 0.5);
    border-radius: 50%;
    animation: anniv-orbit-spin 24s linear infinite;
}

.anniv-news-card__emblem-num {
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
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
    letter-spacing: 0.12em;
    color: #38bdf8;
    font-weight: 700;
    margin-bottom: 4px;
}

.anniv-news-card__dot {
    opacity: 0.5;
}

.anniv-news-card__edition {
    color: #94a3b8;
}

.anniv-news-card__title {
    font-size: 1.15rem;
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
    padding: 8px 18px;
    border-radius: 999px;
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
}

.anniv-news-card__cta:hover {
    background: linear-gradient(135deg, #2563eb, #0284c7);
    border-color: transparent;
    color: #ffffff !important;
    transform: translateX(3px);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
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
   7. ABOUT PAGE JOURNEY ANNIVERSARY CULMINATION
   ========================================================================== */
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
   8. REDUCED MOTION SAFEGUARDS
   ========================================================================== */
@media (prefers-reduced-motion: reduce) {
    .anniv-badge__sheen,
    .anniv-modal__emblem-rings,
    .anniv-intro,
    .anniv-hero-card__ring,
    .anniv-hero-card__orbit-node,
    .anniv-news-card__emblem-orbit {
        animation: none !important;
    }
    .anniv-intro {
        display: none !important;
    }
    .anniv-badge:hover,
    .anniv-counter-card:hover,
    .anniv-hero-card:hover,
    .anniv-news-card:hover,
    .anniv-about-feature:hover {
        transform: none !important;
    }
}
</style>
