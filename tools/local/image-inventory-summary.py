import json, collections
rows = json.load(open('footage/_review/image-inventory-raw.json'))
c = collections.defaultdict(lambda: [0, 0, 0])
for p, sz, w, h in rows:
    parts = p.split('/')
    key = '/'.join(parts[:-1])
    if p.startswith('public/assets/images/_opt'):
        continue
    c[key][0] += 1
    c[key][1] += sz
    c[key][2] += 1 if max(w, h) >= 800 else 0
for k, (n, sz, big) in sorted(c.items()):
    print(f'{n:4d} files {sz/1e6:7.1f} MB  >=800px:{big:4d}  {k}')
print(len(rows), 'total')
