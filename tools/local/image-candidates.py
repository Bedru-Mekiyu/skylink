# LOCAL USE ONLY: builds the candidate list (>= 600 px, outside logo/partner/derived folders), removes exact and near duplicates,
# and writes numbered contact sheets so each picture can be looked at and classified.
import json, hashlib, os
from PIL import Image, ImageDraw, ImageFont, ImageOps

Image.MAX_IMAGE_PIXELS = None
rows = json.load(open('footage/_review/image-inventory-raw.json'))
SKIP_PARTS = ['public/storage/partners', 'public/assets/images/trusted', 'public/assets/videos/hero', 'public/storage/news/thumbs',
              'public/storage/news/share', 'public/storage/news/web', 'public/storage/gallery', 'storage/app/private/archive/ceo',
              'storage/app/private/archive/team', 'storage/app/private/archive/partners', 'public/assets/images/services-top',
              'storage/app/private/archive/product-sources', 'resources/news', 'public/assets/videos/portfolio']
cands = []
for p, sz, w, h in rows:
    if p.endswith('.svg') or max(w, h) < 600 or min(w, h) < 300:
        continue
    if any(p.startswith(s) for s in SKIP_PARTS):
        continue
    cands.append([p, sz, w, h])


def dhash(path, n=9):
    with Image.open(path) as im:
        im = ImageOps.exif_transpose(im).convert('L').resize((n, n - 1), Image.LANCZOS)
        px = list(im.getdata())
    bits = 0
    for y in range(n - 1):
        for x in range(n - 1):
            bits = (bits << 1) | (px[y * n + x] > px[y * n + x + 1])
    return bits


for c in cands:
    c.append(hashlib.sha1(open(c[0], 'rb').read()).hexdigest())
    c.append(dhash(c[0]))
cands.sort(key=lambda c: -(c[2] * c[3]))
kept, dups = [], []
for c in cands:
    match = next((k for k in kept if k[4] == c[4] or bin(k[5] ^ c[5]).count('1') <= 5), None)
    if match:
        dups.append((c[0], match[0]))
    else:
        kept.append(c)
print(len(cands), 'candidates,', len(kept), 'unique,', len(dups), 'duplicates removed')
kept.sort(key=lambda c: c[0])
out = [{'id': i + 1, 'path': c[0], 'bytes': c[1], 'w': c[2], 'h': c[3]} for i, c in enumerate(kept)]
json.dump({'unique': out, 'duplicates': dups}, open('footage/_review/image-candidates.json', 'w'), indent=1)
os.makedirs('footage/_review/image-sheets', exist_ok=True)
font = ImageFont.load_default()
per = 20
for s in range(0, len(out), per):
    sheet = Image.new('RGB', (5 * 330, 4 * 250), (245, 245, 245))
    d = ImageDraw.Draw(sheet)
    for j, it in enumerate(out[s:s + per]):
        with Image.open(it['path']) as im:
            im = ImageOps.exif_transpose(im).convert('RGB')
            im.thumbnail((320, 215))
            x, y = (j % 5) * 330 + 5, (j // 5) * 250 + 5
            sheet.paste(im, (x, y))
        d.rectangle([x, y, x + 44, y + 16], fill=(0, 0, 0))
        d.text((x + 4, y + 2), '#%d' % it['id'], fill=(255, 255, 0), font=font)
        d.text((x, y + 218), '%dx%d %s' % (it['w'], it['h'], it['path'].split('/')[-1][:34]), fill=(0, 0, 0), font=font)
    sheet.save('footage/_review/image-sheets/sheet-%02d.jpg' % (s // per + 1), quality=82)
print('sheets:', (len(out) + per - 1) // per)
