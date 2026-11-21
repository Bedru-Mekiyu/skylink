# LOCAL USE ONLY: studio-style PORTRAIT PLACEHOLDER (not a real person) for the Head of Marketing card, 4:5, 960x1200.
#   python tools/local/build-marketing-placeholder.py   (then copy the printed sha1 into config/team.php 'placeholder_sha1')
import hashlib, math
from PIL import Image, ImageDraw, ImageFilter, ImageChops

W, H = 960, 1200
S = 2  # supersample for smooth edges
w, h = W * S, H * S

# 1. soft studio backdrop in the site's blues: light falloff from the upper left, vignette at the corners
bg = Image.new('RGB', (w, h))
px = bg.load()
cx, cy = w * 0.42, h * 0.30
for y in range(h):
    for x in range(w):
        d = math.hypot((x - cx) / (w * 0.85), (y - cy) / (h * 0.80))
        t = min(1.0, d)
        r = int(74 + (16 - 74) * t)
        g = int(132 + (38 - 132) * t)
        b = int(214 + (92 - 214) * t)
        px[x, y] = (r, g, b)
# floor/background gradient darkening toward the bottom
shade = Image.linear_gradient('L').resize((w, h)).point(lambda v: int(v * 0.35))
bg = ImageChops.multiply(bg, Image.merge('RGB', [Image.new('L', (w, h), 255).point(lambda v: v)] * 3)) if False else bg
dark = Image.new('RGB', (w, h), (6, 18, 43))
bg = Image.composite(dark, bg, shade)

# 2. neutral head-and-shoulders silhouette (generic, no face), soft edges
mask = Image.new('L', (w, h), 0)
d = ImageDraw.Draw(mask)
hx, hy = w // 2, int(h * 0.38)
d.ellipse([hx - int(w * 0.125), hy - int(h * 0.135), hx + int(w * 0.125), hy + int(h * 0.115)], fill=255)          # head
d.polygon([(hx - int(w * 0.09), hy + int(h * 0.05)), (hx + int(w * 0.09), hy + int(h * 0.05)), (hx + int(w * 0.15), int(h * 0.64)), (hx - int(w * 0.15), int(h * 0.64))], fill=255)  # neck, widening into the shoulders
d.rounded_rectangle([hx - int(w * 0.44), int(h * 0.57), hx + int(w * 0.44), int(h * 1.30)], radius=int(w * 0.34), fill=255)   # shoulders
mask = mask.filter(ImageFilter.GaussianBlur(S * 5))

# 3. silhouette colour: deep slate-blue with a soft highlight on the upper left (light falloff)
fig = Image.new('RGB', (w, h), (10, 24, 58))
fp = fig.load()
for y in range(int(h * 0.20), h):
    for x in range(w):
        t = max(0.0, 1 - math.hypot((x - w * 0.36) / (w * 0.55), (y - h * 0.40) / (h * 0.45)))
        base = fp[x, y]
        fp[x, y] = (int(base[0] + 30 * t), int(base[1] + 44 * t), int(base[2] + 64 * t))
img = Image.composite(fig, bg, mask)

# 4. subtle grain so it does not look flat, then a light vignette over everything
grain = Image.effect_noise((w, h), 9).convert('RGB')
img = Image.blend(img, ImageChops.add(img, grain, scale=1, offset=-128), 0.18)
vig = Image.new('L', (w, h), 0)
vd = ImageDraw.Draw(vig)
vd.ellipse([-w * 0.25, -h * 0.20, w * 1.25, h * 1.20], fill=255)
vig = vig.filter(ImageFilter.GaussianBlur(w * 0.12))
img = Image.composite(img, Image.new('RGB', (w, h), (4, 12, 30)), vig.point(lambda v: 150 + int(v * 105 / 255)))

img = img.resize((W, H), Image.LANCZOS)
out = 'public/assets/images/about/head-of-marketing.jpg'
img.save(out, quality=88, optimize=True, progressive=True)
print(out, round(len(open(out, 'rb').read()) / 1024), 'KB', hashlib.sha1(open(out, 'rb').read()).hexdigest())
