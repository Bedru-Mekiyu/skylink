# LOCAL USE ONLY: crops the top (company signage) off the About banner photo and writes the web copies.
#   python tools/local/build-about-banner.py 0.22      (fraction of the photo height removed from the top)
# Original (4271x2809 PNG) is archived privately in storage/app/private/archive/about-banner/ and never changed.
import os, shutil, sys
from PIL import Image

Image.MAX_IMAGE_PIXELS = None
frac = float(sys.argv[1]) if len(sys.argv) > 1 else 0.22
SRC = 'new video and photo resource/Picsart_26-11-15_05-27-55-871.png'
arch = 'storage/app/private/archive/about-banner'
os.makedirs(arch, exist_ok=True)
if not os.path.exists(arch + '/Picsart_26-11-15_05-27-55-871.png'):
    shutil.copy2(SRC, arch + '/Picsart_26-11-15_05-27-55-871.png')
im = Image.open(SRC).convert('RGB')
w, h = im.size
y0 = int(round(h * frac))
im = im.crop((0, y0, w, h))
# A small tip of the logo shape would remain between two heads at the very top: fill that patch with the plain wall
# (each row interpolated between the wall colours just left and right of it). No face is touched.
px = im.load()
for y in range(0, 140):
    c = px[1700, y]  # plain wall right of the logo remnant (lighting changes by row, hardly by column)
    x_end = 1531 if y < 122 else 1690
    x_start = 1385 if y < 122 else 1531
    for x in range(x_start, x_end if y < 122 else 1690):
        px[x, y] = c
    if y < 122:
        for x in range(1531, 1690):
            px[x, y] = c
print('original', w, h, '-> crop from y', y0, 'size', im.size)
big = im.resize((1920, round(1920 * im.size[1] / w)), Image.LANCZOS)
big.save('public/assets/images/about/team-banner.jpg', quality=85, optimize=True, progressive=True)
for tw in (480, 960, 1440, 1920):
    r = im.resize((tw, round(tw * im.size[1] / w)), Image.LANCZOS)
    r.save('public/assets/images/_opt/about/team-banner-%d.webp' % tw, quality=80, method=6)
print('jpg KB', os.path.getsize('public/assets/images/about/team-banner.jpg') // 1024, 'webp1920 KB', os.path.getsize('public/assets/images/_opt/about/team-banner-1920.webp') // 1024)
