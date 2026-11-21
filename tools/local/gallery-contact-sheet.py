# LOCAL USE ONLY: one labelled contact sheet of every imported photo (visible = green label, hidden = red label).
import json, os
from PIL import Image, ImageDraw, ImageFont, ImageOps

Image.MAX_IMAGE_PIXELS = None
data = json.load(open('footage/_review/gallery-import-selection.json'))
W, H, cols = 380, 290, 5
rows = (len(data) + cols - 1) // cols
sheet = Image.new('RGB', (cols * W, rows * H), (250, 250, 250))
d = ImageDraw.Draw(sheet)
f = ImageFont.load_default()
vis = {x['file']: x['visible'] for x in json.load(open('footage/_review/gallery-import-selection.json'))}
for i, it in enumerate(data):
    x, y = (i % cols) * W, (i // cols) * H
    with Image.open('storage/app/private/gallery-import-src/' + it['file']) as im:
        im = ImageOps.exif_transpose(im).convert('RGB')
        im.thumbnail((W - 12, 240))
        sheet.paste(im, (x + 6, y + 6))
    col = (30, 140, 60) if it['visible'] else (190, 40, 40)
    d.rectangle([x + 6, y + 6, x + 130, y + 22], fill=col)
    d.text((x + 10, y + 9), '%02d  %s' % (it['sort'], 'VISIBLE' if it['visible'] else 'HIDDEN'), fill=(255, 255, 255), font=f)
    d.text((x + 8, y + 252), it['file'][:48], fill=(0, 0, 0), font=f)
    d.text((x + 8, y + 266), '%dx%d  %.1f MB' % (it['w'], it['h'], it['bytes'] / 1e6), fill=(90, 90, 90), font=f)
os.makedirs('footage/_review/gallery-import', exist_ok=True)
sheet.save('footage/_review/gallery-import/contact-sheet.jpg', quality=85)
print(sheet.size)
