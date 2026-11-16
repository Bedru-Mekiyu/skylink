// Shared settings for check-layout.mjs and check-speed.mjs. LOCAL USE ONLY.
// The pages every check visits. Edit a slug here if a record is renamed.
export const BASE = process.env.SITE_URL || 'http://127.0.0.1:8000';

export const PAGES = [
    ['home', '/'],
    ['about', '/about'],
    ['services', '/services'],
    ['category', '/services/category/software-service'],
    ['detail', '/services/detail/access-management'],
    ['products', '/products'],
    ['product', '/products/industry-solutions/fleet-management'],
    ['portfolio', '/portfolio'],
    ['news', '/news'],
    ['article', '/news/800-chatgpt-1762140511'],
    ['contact', '/contact'],
    ['subscribe', '/subscribe/premium'],
];

// Nominal screen sizes (CSS px at 100%). Up to 820 px wide is treated as a
// touch device (phone/tablet); wider is a desktop with a classic scrollbar.
export const PORTRAIT = [
    [320, 568], [360, 740], [390, 844], [430, 932],
    [600, 960], [768, 1024], [820, 1180],
    [1024, 768], [1180, 820], [1280, 800], [1366, 768], [1440, 900], [1920, 1080], [2560, 1440],
];
export const LANDSCAPE_PHONES = [[568, 320], [740, 360], [844, 390], [932, 430]];
export const ZOOMS = [1, 1.25, 1.5];
export const SHOT_WIDTHS = [360, 768, 1024, 1440];

// Windows-style scrollbar that is always visible (desktop views only).
export const SCROLLBAR_CSS = 'html{overflow-y:scroll!important}::-webkit-scrollbar{width:15px;height:15px;background:#f1f1f1}::-webkit-scrollbar-thumb{background:#c1c1c1}';

export function args() {
    const out = {};
    for (const a of process.argv.slice(2)) {
        const m = a.match(/^--([^=]+)(?:=(.*))?$/);
        if (m) out[m[1]] = m[2] ?? true;
    }
    return out;
}

export async function siteIsUp() {
    try {
        const r = await fetch(BASE + '/', { redirect: 'manual' });
        return r.status < 500;
    } catch {
        return false;
    }
}

export function stamp() {
    const d = new Date(), p = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}-${p(d.getHours())}${p(d.getMinutes())}`;
}
