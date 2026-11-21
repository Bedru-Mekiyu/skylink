# LOCAL USE ONLY: lists every project image (path, bytes, width, height) into footage/_review/image-inventory-raw.json
import os, json, collections
from PIL import Image

Image.MAX_IMAGE_PIXELS = None
roots = ['public/assets', 'public/storage', 'storage/app/public', 'storage/app/private/archive', 'resources/news', 'new video and photo resource']
exts = {'.jpg', '.jpeg', '.png', '.webp', '.gif', '.avif', '.svg'}
rows, seen = [], set()
for r in roots:
    for d, _, fs in os.walk(r):
        parts = d.replace(os.sep, '/').split('/')
        if '_opt' in parts:
            continue
        for f in fs:
            e = os.path.splitext(f)[1].lower()
            if e not in exts:
                continue
            p = os.path.join(d, f).replace(os.sep, '/')
            rp = os.path.realpath(p)
            if rp in seen:
                continue
            seen.add(rp)
            w = h = 0
            if e != '.svg':
                try:
                    with Image.open(p) as im:
                        w, h = im.size
                except Exception:
                    w = h = -1
            rows.append((p, os.path.getsize(p), w, h))
print(len(rows), 'images')
c = collections.Counter('/'.join(p.split('/')[:4]) for p, _, _, _ in rows)
for k, v in sorted(c.items()):
    print(v, k)
json.dump(rows, open('footage/_review/image-inventory-raw.json', 'w'))
