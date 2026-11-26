{{--
    Floating contact launcher (bottom left, every page on layouts.master).
    Holds what used to be the top bar: both phone numbers, the address, the
    social links and the "Service Active" badge. Values are the top bar's own.

    Included at the end of <body> by the layout itself, so it outputs its CSS
    and JS inline: a @push from here would run after <head>'s @stack('styles')
    was already rendered and be dropped.

    Placement: bottom-left corner, away from the Tawk.to chat bubble and the
    homepage's floating phone/Telegram buttons (both on the right). The
    panel opens upwards from the launcher.
--}}
@php
    // One source with the navbar: config/company_contact.php
    $contactPhones = config('company_contact.phones');
    $contactAddress = config('company_contact.address');
    $contactSocials = [
        ['label' => 'Facebook', 'icon' => 'fab fa-facebook', 'url' => 'https://www.facebook.com/profile.php?id=61562181474117'],
        ['label' => 'TikTok', 'icon' => 'fab fa-tiktok', 'url' => 'https://www.tiktok.com/@skylink_technology?_t=ZM-8y9cFtAlBX6&_r=1'],
        ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'url' => 'https://www.instagram.com/skylink_technologies?igsh=NXZwMjQyMHo1bHJm&utm_source=qr'],
        ['label' => 'Telegram', 'icon' => 'fab fa-telegram', 'url' => 'https://t.me/Skylinktechnologies'],
    ];
@endphp

<style>
    .sk-contact {
        --sk-blue: var(--primary-blue, rgb(26, 111, 176));
        --sk-size: 56px;
        /* Same 20px corner inset as the Tawk bubble on the opposite side. */
        --sk-left: calc(20px + env(safe-area-inset-left, 0px));
        --sk-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        font-family: var(--font-ui);
    }

    .sk-contact__launcher {
        position: fixed;
        left: var(--sk-left);
        bottom: var(--sk-bottom);
        z-index: 1040;
        width: var(--sk-size);
        height: var(--sk-size);
        border: 0;
        border-radius: 50%;
        background: var(--sk-blue);
        color: #fff;
        font-size: 1.35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .sk-contact__launcher:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 111, 176, 0.4);
    }

    .sk-contact__launcher:focus-visible {
        outline: 3px solid #fff;
        outline-offset: 2px;
        box-shadow: 0 0 0 6px var(--sk-blue);
    }

    /* Subtle pulse ring (stops while the panel is open). */
    .sk-contact__launcher::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid var(--sk-blue);
        opacity: 0;
        animation: skContactPulse 2.6s ease-out infinite;
        pointer-events: none;
    }

    .sk-contact.is-open .sk-contact__launcher::after {
        animation: none;
    }

    @keyframes skContactPulse {
        0% { transform: scale(1); opacity: 0.55; }
        70%, 100% { transform: scale(1.55); opacity: 0; }
    }

    .sk-contact__icon-close,
    .sk-contact.is-open .sk-contact__icon-open {
        display: none;
    }

    .sk-contact.is-open .sk-contact__icon-close {
        display: inline;
    }

    .sk-contact__panel {
        position: fixed;
        z-index: 1040;
        /* Opens upwards from the launcher, left edges aligned. */
        left: var(--sk-left);
        bottom: calc(var(--sk-bottom) + var(--sk-size) + 12px);
        width: min(92vw, 340px);
        max-height: calc(100vh - var(--sk-bottom) - var(--sk-size) - 36px);
        max-height: calc(100dvh - var(--sk-bottom) - var(--sk-size) - 36px);
        overflow-y: auto;
        background: #fff;
        color: #333;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        transform-origin: bottom left;
        opacity: 0;
        visibility: hidden;
        transform: scale(0.92) translateY(6px);
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0s linear 0.18s;
    }

    .sk-contact.is-open .sk-contact__panel {
        opacity: 1;
        visibility: visible;
        transform: none;
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0s;
    }

    .sk-contact__header {
        background: var(--sk-blue);
        color: #fff;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .sk-contact__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: #fff;
    }

    .sk-contact__status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        color: #666;
        border-radius: 50px;
        padding: 3px 4px 3px 10px;
        font-size: 12px;
        white-space: nowrap;
    }

    .sk-contact__status .fa-check-circle {
        color: #1a6fb0;
    }

    .sk-contact__flag {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #ccc;
    }

    .sk-contact__body {
        padding: 16px 18px 18px;
    }

    .sk-contact__social {
        display: flex;
        gap: 10px;
        list-style: none;
        margin: 0 0 14px;
        padding: 0;
    }

    .sk-contact__social a {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(26, 111, 176, 0.1);
        color: #1a6fb0;
        font-size: 1.1rem;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .sk-contact__social a:hover {
        background: var(--sk-blue);
        color: #fff;
    }

    .sk-contact__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .sk-contact__row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-top: 1px solid #eef1f6;
        font-size: 14px;
    }

    .sk-contact__row > .fas {
        color: #1a6fb0;
        width: 18px;
        text-align: center;
        flex: none;
    }

    .sk-contact__value {
        flex: 1;
        min-width: 0;
        color: #333;
        text-decoration: none;
        overflow-wrap: anywhere;
    }

    a.sk-contact__value:hover {
        color: #1a6fb0;
    }

    .sk-contact__copy {
        position: relative;
        flex: none;
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: #f1f4f9;
        color: #666;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .sk-contact__copy:hover {
        background: rgba(26, 111, 176, 0.12);
        color: #1a6fb0;
    }

    .sk-contact__copy .fa-check,
    .sk-contact__copy.is-copied .fa-copy {
        display: none;
    }

    .sk-contact__copy.is-copied .fa-check {
        display: inline;
    }

    .sk-contact__copy.is-copied {
        background: rgba(26, 111, 176, 0.12);
        color: #1a6fb0;
    }

    /* "Copied" confirmation bubble above the copy button. */
    .sk-contact__tip {
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: #fff;
        font-size: 11px;
        line-height: 1;
        padding: 5px 8px;
        border-radius: 6px;
        white-space: nowrap;
        pointer-events: none;
    }

    .sk-contact__tip:empty {
        display: none;
    }

    .sk-contact a:focus-visible,
    .sk-contact__copy:focus-visible {
        outline: 2px solid #1a6fb0;
        outline-offset: 2px;
    }

    .sk-contact__sr {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
        border: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        .sk-contact__launcher,
        .sk-contact__panel,
        .sk-contact.is-open .sk-contact__panel {
            transition: none;
        }

        .sk-contact__panel {
            transform: none;
        }

        .sk-contact__launcher::after {
            animation: none;
        }
    }
</style>

<div class="sk-contact" data-sk-contact>
    <button type="button" class="sk-contact__launcher"
            aria-label="Open contact options: phone, address and social media"
            aria-expanded="false" aria-controls="skContactPanel">
        <i class="fas fa-headset sk-contact__icon-open" aria-hidden="true"></i>
        <i class="fas fa-times sk-contact__icon-close" aria-hidden="true"></i>
    </button>

    <div class="sk-contact__panel" id="skContactPanel" role="dialog" aria-labelledby="skContactTitle">
        <div class="sk-contact__header">
            <h2 class="sk-contact__title" id="skContactTitle">Contact Us</h2>
            <span class="sk-contact__status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Service Active</span>
                <img src="{{ asset('assets/images/eth-flag.png') }}" alt="Ethiopia Flag" class="sk-contact__flag">
            </span>
        </div>

        <div class="sk-contact__body">
            <ul class="sk-contact__social">
                @foreach ($contactSocials as $social)
                    <li>
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                           aria-label="{{ $social['label'] }} (opens in a new tab)">
                            <i class="{{ $social['icon'] }}" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>

            <ul class="sk-contact__list">
                @foreach ($contactPhones as $phone)
                    <li class="sk-contact__row">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <a class="sk-contact__value tel-nowrap" href="{{ \App\Support\PhoneNumber::tel($phone) }}">{{ \App\Support\PhoneNumber::international($phone) }}</a>
                        <button type="button" class="sk-contact__copy" data-copy="{{ \App\Support\PhoneNumber::e164($phone) }}" aria-label="Copy phone number {{ \App\Support\PhoneNumber::international($phone) }}">
                            <i class="far fa-copy" aria-hidden="true"></i>
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <span class="sk-contact__tip" aria-hidden="true"></span>
                        </button>
                    </li>
                @endforeach
                <li class="sk-contact__row">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span class="sk-contact__value">{{ $contactAddress }}</span>
                    <button type="button" class="sk-contact__copy" data-copy="{{ $contactAddress }}" aria-label="Copy address">
                        <i class="far fa-copy" aria-hidden="true"></i>
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <span class="sk-contact__tip" aria-hidden="true"></span>
                    </button>
                </li>
            </ul>
            <p class="sk-contact__sr" role="status" aria-live="polite" data-sk-contact-live></p>
        </div>
    </div>
</div>

<script>
    (function () {
        // Guard: run once even if this markup is ever rendered twice.
        if (window.__skyContactLauncher) return;
        window.__skyContactLauncher = true;

        function init() {
            var root = document.querySelector('[data-sk-contact]');
            if (!root) return;
            var launcher = root.querySelector('.sk-contact__launcher');
            var live = root.querySelector('[data-sk-contact-live]');
            var LABEL_OPEN = launcher.getAttribute('aria-label');
            var LABEL_CLOSE = 'Close contact options';

            function isOpen() {
                return root.classList.contains('is-open');
            }

            function setOpen(open, returnFocus) {
                root.classList.toggle('is-open', open);
                launcher.setAttribute('aria-expanded', String(open));
                launcher.setAttribute('aria-label', open ? LABEL_CLOSE : LABEL_OPEN);
                if (!open && returnFocus) launcher.focus();
            }

            launcher.addEventListener('click', function () {
                setOpen(!isOpen(), false);
            });

            document.addEventListener('keydown', function (e) {
                if ((e.key === 'Escape' || e.key === 'Esc') && isOpen()) {
                    setOpen(false, true);
                }
            });

            // Click/tap outside closes it.
            document.addEventListener('pointerdown', function (e) {
                if (isOpen() && !root.contains(e.target)) setOpen(false, false);
            });

            function copyText(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
                }
                return new Promise(function (resolve, reject) {
                    var area = document.createElement('textarea');
                    area.value = text;
                    area.setAttribute('readonly', '');
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();
                    try {
                        if (document.execCommand('copy')) resolve(); else reject(new Error('copy failed'));
                    } catch (err) {
                        reject(err);
                    }
                    area.remove();
                });
            }

            root.querySelectorAll('[data-copy]').forEach(function (button) {
                var tip = button.querySelector('.sk-contact__tip');
                button.addEventListener('click', function () {
                    var text = button.getAttribute('data-copy');
                    var show = function (ok) {
                        button.classList.toggle('is-copied', ok);
                        tip.textContent = ok ? 'Copied' : 'Copy failed';
                        live.textContent = ok ? 'Copied: ' + text : 'Copy failed';
                        clearTimeout(button.__skTimer);
                        button.__skTimer = setTimeout(function () {
                            button.classList.remove('is-copied');
                            tip.textContent = '';
                        }, 1600);
                    };
                    copyText(text).then(function () { show(true); }, function () { show(false); });
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
