# LOCAL USE ONLY: turns the reviewed contact-sheet ids into (1) a staging folder of source copies (the sources are never touched),
# (2) database/data/gallery-site-photos.php (the list the artisan command reads), (3) the classification table (markdown).
import json, os, shutil
from PIL import Image

C = {c['id']: c for c in json.load(open('footage/_review/image-candidates.json'))['unique']}
# id: (visible, file name used in the staging folder, English caption or None, why / note)
SEL = [
    (19, True, 'expo-booth-team.jpg', 'Our team at the expo stand', 'Real event photo, bright, landscape. Faces of staff and visitors.'),
    (5, True, 'partner-handshake-tech-expo.jpg', None, 'Partnership handshake at a tech expo (event banner partly visible). Faces of a partner.'),
    (130, True, 'team-support.jpg', 'Support team', 'Staff group photo, also used as the Contact banner.'),
    (113, True, 'office-showroom.jpg', None, 'Office showroom with product shelves, our logo on the wall.'),
    (18, True, 'team-hall.jpg', 'Our team', 'Staff group in a hall.'),
    (7, True, 'partner-handshake-flag.jpg', None, 'Partnership handshake, flag and world map. Faces of a partner.'),
    (110, True, 'meeting-room.jpg', None, 'Large meeting with a partner team around a table. Faces.'),
    (14, True, 'marketing-team.jpg', 'Our marketing team', 'Team photo in the office (used on the About page).'),
    (4, False, 'certificates-handover.jpg', None, 'HIDDEN (papers/certificates held up). Two people holding certificates.'),
    (128, True, 'team-software.jpg', 'Software team', 'Staff group photo (used on the About page).'),
    (82, True, 'conference-attendees.jpg', None, 'Conference table with laptops and flowers; attendees from other companies.'),
    (123, True, 'team-office-wall.jpg', None, 'Four people in front of our logo wall.'),
    (74, True, 'site-visit-group.jpg', None, 'Group on a site visit with a worker (client site). Faces.'),
    (17, True, 'promo-meeting.jpg', None, 'Meeting at a table.'),
    (62, True, 'team-office-white-shirts.jpg', None, 'Team around a desk in front of our logo.'),
    (79, True, 'logo-wall-two-men.jpg', None, 'Two people in front of our big logo sign.'),
    (11, False, 'contract-signing.jpg', None, 'HIDDEN (owner rule: no agreement or contract documents visible). Contract signing at a desk.'),
    (116, True, 'partner-handshake-map.jpg', None, 'Partnership handshake, world map. Faces of a partner.'),
    (119, False, 'partner-handshake-office.jpg', None, 'HIDDEN (papers in hands). Handshake with an older client representative.'),
    # hidden: uncertain
    (6, False, 'dire-dawa-administration.jpg', None, 'Client banner (Dire Dawa Administration) prominent; group of client staff.'),
    (118, False, 'team-thank-you.jpg', None, 'Group photo with a THANK YOU overlay text.'),
    (117, False, 'team-yellow-shirts.jpg', None, 'Team photo with a social-media template bar at the bottom.'),
    (114, False, 'team-outdoors.jpg', None, 'Outdoor team photo with a social-media template bar.'),
    (115, False, 'certificates-template.jpg', None, 'Two people with certificates plus a template text bar.'),
    (124, False, 'team-jackets.jpg', None, 'Group in matching jackets, template frame at the bottom.'),
    (127, False, 'team-banner.jpg', None, 'Staff group, near copy of other group photos (used as the About banner).'),
    (129, False, 'team-contact.jpg', None, 'Staff group, near copy of other group photos.'),
    (64, False, 'etex-expo-visitor.jpg', None, 'One person in front of an ETEX expo backdrop (identity and event sign).'),
    (86, False, 'speaker-podium.jpg', None, 'Speaker at a podium with press microphones and partner logos.'),
    (71, False, 'tv-interview.jpg', None, 'Screen capture of a TV interview.'),
    (131, False, 'network-cabling.jpg', 'Network cabling', 'Stock-style photo from the services pages (6000x4000, 58 MB original).'),
    (41, False, 'datacenter-infrastructure.jpg', 'Data center infrastructure', 'Stock-style photo from the services pages.'),
    (40, False, 'datacenter.jpg', 'Data center', 'Stock-style photo from the services pages.'),
    (52, False, 'server-room.jpg', 'Server room', 'Stock-style photo from the services pages.'),
    (38, False, 'cooling-equipment.jpg', 'Cooling equipment', 'Product photo from the services pages.'),
    (31, False, 'workshop-desk.jpg', None, 'Stock photo of people drawing at a table (portfolio page).'),
    (32, False, 'notes-and-laptop.jpg', None, 'Stock photo of notes and a laptop (portfolio page).'),
    (133, False, 'vehicle-monitoring.jpg', None, 'Stock composite photo with interface graphics.'),
]
os.makedirs('storage/app/private/gallery-import-src', exist_ok=True)
data = []
table = ['| # | class | file | size | pixels | source (project path) | note |', '|---|---|---|---|---|---|---|']
for order, (cid, vis, name, cap, note) in enumerate(SEL, 1):
    c = C[cid]
    dst = os.path.join('storage/app/private/gallery-import-src', name)
    if not os.path.exists(dst):
        shutil.copy2(c['path'], dst)  # copy, the source stays untouched
    if os.path.splitext(c['path'])[1].lower() == '.webp' or os.path.splitext(c['path'])[1].lower() == '.png':
        pass
    data.append({'file': name, 'visible': vis, 'caption_en': cap, 'sort': order, 'source': c['path'], 'bytes': c['bytes'], 'w': c['w'], 'h': c['h'], 'note': note})
    table.append('| %d | %s | %s | %.1f MB | %dx%d | `%s` | %s%s |' % (cid, 'IMPORT visible' if vis else 'IMPORT hidden', name, c['bytes'] / 1e6, c['w'], c['h'], c['path'], note, ' Caption: "%s".' % cap if cap else ''))
json.dump(data, open('footage/_review/gallery-import-selection.json', 'w'), indent=1)
php = ["<?php", "", "// Site photos for the About gallery (reviewed by eye, see footage/_review/GALLERY-IMPORT-REPORT.md).", "// Source files live in storage/app/private/gallery-import-src/ (not in git, never modified).", "// 'visible' false = imported hidden: switch it on in Admin > Gallery. Order = sort. Captions: English only, none unless the name says what it is.", "return ["]
for d in data:
    php.append("    ['file' => %s, 'visible' => %s, 'caption_en' => %s, 'sort' => %d]," % ("'" + d['file'] + "'", 'true' if d['visible'] else 'false', ("'" + d['caption_en'] + "'") if d['caption_en'] else 'null', d['sort']))
php.append("];")
os.makedirs('database/data', exist_ok=True)
open('database/data/gallery-site-photos.php', 'w', encoding='utf-8').write('\n'.join(php) + '\n')
open('footage/_review/gallery-import-table.md', 'w', encoding='utf-8').write('\n'.join(table) + '\n')
used = {s[0] for s in SEL}
print(len(SEL), 'selected;', sum(1 for s in SEL if s[1]), 'visible,', sum(1 for s in SEL if not s[1]), 'hidden;', len(C) - len(used), 'candidates excluded')
