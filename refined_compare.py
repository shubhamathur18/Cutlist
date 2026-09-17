import csv
import io
import re

with open('compare_sheets.py', 'r') as f:
    code = f.read()

pattern = r'sheet1_text = """(.*?)"""'
match = re.search(pattern, code, re.DOTALL)
sheet1_text = match.group(1)

reader1 = csv.DictReader(io.StringIO(sheet1_text.strip()))
sheet1_items = []
for idx, r in enumerate(reader1, 1):
    name = r.get('Name', '').strip()
    if not name or name == '87':
        continue
    sheet1_items.append({
        'row_idx': idx,
        'Name': name,
        'Size': r.get('Size', '').strip(),
        'Finish': r.get('Finish', '').strip(),
        'Area': r.get('Area', '').strip(),
        'Application': r.get('Application', '').strip(),
        'Look': r.get('Look', '').strip(),
        'Color': r.get('Color', '').strip()
    })

sheet2_items = []
with open(r'c:\xampp\htdocs\milestone\woocommerce_products_attributes_specified_only.csv', mode='r', encoding='utf-8-sig', errors='ignore') as f:
    reader2 = csv.DictReader(f)
    for idx, r in enumerate(reader2, 1):
        attrs = {}
        for i in range(1, 10):
            kn = f'Attribute {i} name'
            kv = f'Attribute {i} value(s)'
            if r.get(kn):
                attrs[r[kn].strip()] = r.get(kv, '').strip()
        name = r.get('Name', '').strip()
        if not name:
            continue
        sheet2_items.append({
            'row_idx': idx,
            'ID': r.get('ID', '').strip(),
            'Name': name,
            'Size': attrs.get('Size', ''),
            'Finish': attrs.get('Finish', ''),
            'Area': attrs.get('Area', ''),
            'Application': attrs.get('Application', ''),
            'Look': attrs.get('Look', ''),
            'Color': attrs.get('Color', '')
        })

# Let's inspect all items in Sheet 1 and find their closest candidate in Sheet 2
def norm(s):
    return re.sub(r'[^a-zA-Z0-9]', '', s).lower()

matches = []
sheet2_claimed = set()

# Explicit known renames / aliases
alias_map = {
    # ('Sheet 1 Name', 'Size', 'Finish'): ('Sheet 2 Name', 'Size', 'Finish')
    ('Poise Brown', '300x600', 'Cladding'): ('Poise Bronze', '300x600', 'Stone Cladding'),
    ('Dark Travertine P3', '600x600', 'Matt'): ('Dark Travertine Grey', '600x600', 'Matt'),
    ('Whangarei Matt Tile', '600x600', 'Matt'): ('Stone Bianco (whangarei) Matt', '600x600', 'Matt'),
    ('Romano Creme', '600x600', 'Matt Antislip'): ('Romano Crema Matt', '600x600', 'Matt'),
    ('Travertine Vein Cut Beige 600x1200', '600x1200', 'Matt'): ('Travertine Beige Vein Cut', '600x1200', 'Matt'),
    ('Travertine Vein Cut Grey 600x1200', '600x1200', 'Matt'): ('Travertine Grey Vein Cut', '600x1200', 'Matt'),
    ('Brecia Iconic Beige', '600x1200', 'Honed'): ('Brescia Iconic Beige Matt (Honed)', '600x1200', 'Matt'),
    ('Brecia Iconic Ivory', '600x1200', 'Honed'): ('Brescia Iconic Ivory Matt (Honed)', '600x1200', 'Matt'),
    ('Oxide Metal Pearl', '600x1200', 'Matt'): ('Oxide Pearl Rust Matt', '600x1200', 'Matt'),
    ('Oxide Metal Grey', '600x1200', 'Matt'): ('Oxide Grey Rust Matt', '600x1200', 'Matt'),
    ('Oxide Metal Iron', '600x1200', 'Matt'): ('Oxide Iron Rust Matt', '600x1200', 'Matt'),
    ('Dominque', '600x1200', 'Matt'): ('Dominique Natural Matt', '600x600', 'Matt'), # Note size difference
    ('Nuovi Marmi', '600x1200', 'Matt'): ('Carrara Marmi Gold Matt', '600x600', 'Matt'), # Note size difference
    ('Nuovi Marmi', '600x600', 'Polished'): ('Carrara Marmi Gold Polished', '600x600', 'Polished'),
    ('Aston Ivory Decor Tiles', '600x1200', 'Matt Carving'): ('Aston Ivory Decor', '600x1200', 'Matt Carving'),
    ('Antalya Bianco', '600x1200', 'Honed'): ('Antalya Bianco Matt (Soft Honed)', '600x1200', 'Matt'),
    ('Alpine Pearl', '600x1200', 'Satin Matt'): ('Alpine Pearl Matt Carving', '600x1200', 'Matt Carving'),
    ('Livante Bianco Matt', '600x600', 'Matt'): ('Livanto Bianco Matt', '600x600', 'Matt'),
    ('Livante Bianco Polished', '600x600', 'Polished'): ('Livanto Bianco Polished', '600x600', 'Polished'),
    ('WI-4006', '600x1200', 'Polished'): ('WI-4006 Royal Onyx', '600x1200', 'Polished'),
    ('Terrazo Milano', '600x600', 'Matt Antislip'): ('Terrazzo Milano Matt', '600x600', 'Matt'),
    ('Terrazo Mist', '600x600', 'Matt Antislip'): ('Terrazzo Mist', '600x600', 'Matt'),
    ('Terrazo Luxe', '600x600', 'Matt Antislip'): ('Terrazzo Luxe Matt', '600x600', 'Matt'),
    ('Terrazo Elite', '600x600', 'Matt Antislip'): ('Terrazzo Elite Matt', '600x600', 'Matt'),
    ('Terrazzo Ice', '600x600', 'Matt Antislip'): ('Terrazzo Elite Ice', '600x600', 'Matt'),
    ('Terrazzo Beige', '600x600', 'Matt Antislip'): ('Terrazzo Beige Matt', '600x600', 'Matt'),
    ('Astron Bianco', '600x600', 'Matt'): ('Astron Bianco Matt', '600x600', 'Matt'),
    ('Astron Bianco Polished', '600x600', 'Polished'): ('Astron Bianco Polished', '600x600', 'Polished'),
    ('Bosco Beige Matt', '600x600', 'Matt'): ('Bosco Beige', '600x600', 'Matt'),
    ('Magma Grey', '600x600', 'cladding'): ('Magma Grey', '300x600', 'Stone Cladding'),
    ('Loki Anthracite Matt Tile', '600x600', 'Matt'): ('Loki Anthracite Matt', '600x600', 'Matt'),
    ('Botticino Matt Tile', '600x600', 'Matt'): ('Botticino Matt', '600x600', 'Matt'),
    ('Cosmic Beige', '600x600', 'Matt Antislip'): ('Cosmic Beige Matt', '600x600', 'Matt'),
    ('Cosmic White', '600x600', 'Matt Antislip'): ('Cosmic White Matt', '600x600', 'Matt'),
    ('Cosmic Crema', '600x600', 'Matt Antislip'): ('Cosmic Crema Matt', '600x600', 'Matt'),
    ('Cosmic Grey', '600x600', 'Matt Antislip'): ('Cosmic Grey Matt', '600x600', 'Matt'),
    ('Luxor White', '600x600', 'Matt Antislip'): ('Luxor White Matt', '600x600', 'Matt'),
    ('Luxor Light Grey', '600x600', 'Matt Antislip'): ('Luxor Light Grey Matt', '600x600', 'Matt'),
    ('Luxor Sand', '600x600', 'Matt Antislip'): ('Luxor Sand Matt', '600x600', 'Matt'),
    ('Majestic Bianco', '600x600', 'Matt'): ('Majestic Bianco Matt', '600x600', 'Matt'),
    ('Majestic Beige', '600x600', 'Matt'): ('Majestic Beige Matt', '600x600', 'Matt'),
    ('Majestic Silver', '600x600', 'Matt'): ('Majestic Silver Matt', '600x600', 'Matt'),
    ('Avorio Oak', '200x1200', 'Matt Antislip'): ('Avorio Oak Matt', '200x1200', 'Matt'),
    ('Grey Oak', '200x1200', 'Matt Antislip'): ('Grey Oak Matt', '200x1200', 'Matt'),
    ('White Oak', '200x1200', 'Matt Antislip'): ('White Oak Matt', '200x1200', 'Matt'),
    ('Natural Oak', '200x1200', 'Matt Antislip'): ('Natural Oak Matt', '200x1200', 'Matt'),
    ('Titan Grey', '600x600', 'Matt Antislip'): ('Titan Grey Matt', '600x600', 'Matt'),
    ('Titan Beige', '600x600', 'Matt Antislip'): ('Titan Beige Matt', '600x600', 'Matt'),
    ('Lakestone White', '600x1200', 'Matt'): ('Lakestone White Matt', '600x1200', 'Matt'),
    ('Lakestone Sand', '600x1200', 'Matt'): ('Lakestone Sand Matt', '600x1200', 'Matt'),
    ('Lakestone Grey', '600x1200', 'Matt'): ('Lakestone Grey Matt', '600x1200', 'Matt'),
    ('Lakestone Smoke', '600x1200', 'Matt'): ('Lakestone Smoke Matt', '600x1200', 'Matt'),
    ('Lakestone natural', '600x1200', 'Matt'): ('Lakestone Natural Matt', '600x1200', 'Matt'),
    ('Coastal Sand', '600x600', 'Matt Antislip'): ('Coastal Sand Matt', '600x600', 'Matt'),
    ('Coastal white', '600x600', 'Matt Antislip'): ('Coastal White Matt', '600x600', 'Matt'),
    ('Byron White', '600x600', 'Matt'): ('Byron White Matt', '600x600', 'Matt'),
    ('Byron Grey', '600x600', 'Matt'): ('Byron Grey Matt', '600x600', 'Matt'),
    ('Byron Beige', '600x600', 'Matt'): ('Byron Beige Matt', '600x600', 'Matt'),
    ('Siena white', '600x600', 'Matt'): ('Siena White Matt', '600x600', 'Matt'),
    ('Siena Taupe', '600x600', 'Matt'): ('Siena Taupe Matt', '600x600', 'Matt'),
    ('Pearl Sand', '600x600', 'Matt'): ('Pearl Sand Matt', '600x600', 'Matt'),
    ('Pearl Bianco', '600x600', 'Matt'): ('Pearl Bianco Matt', '600x600', 'Matt'),
    ('Nestar Sand', '600x600', 'Matt'): ('Nestar Sand Matt', '600x600', 'Matt'),
    ('Nestar Harbour', '600x600', 'Matt'): ('Nestar Harbour Matt', '600x600', 'Matt'),
    ('Ice Stone', '600x1200', 'Matt'): ('Ice Stone Matt', '600x1200', 'Matt'),
    ('Ice Stone', '600x600', 'Matt'): ('Ice Stone Matt', '600x600', 'Matt'),
}

# Perform mapping
mapped_s1 = []
unmapped_s1 = []

for s1 in sheet1_items:
    # 1. Check direct match in sheet2
    found = None
    for idx, s2 in enumerate(sheet2_items):
        if idx in sheet2_claimed:
            continue
        if norm(s1['Name']) == norm(s2['Name']) and norm(s1['Size']) == norm(s2['Size']) and norm(s1['Finish']) == norm(s2['Finish']):
            found = (idx, s2, 'Exact Match')
            break
            
    # 2. Check alias map
    if not found:
        key = (s1['Name'], s1['Size'], s1['Finish'])
        if key in alias_map:
            target_name, target_size, target_finish = alias_map[key]
            for idx, s2 in enumerate(sheet2_items):
                if idx in sheet2_claimed:
                    continue
                if norm(target_name) == norm(s2['Name']):
                    found = (idx, s2, f"Aliased Match: '{s2['Name']}'")
                    break
                    
    # 3. Fuzzy norm match
    if not found:
        for idx, s2 in enumerate(sheet2_items):
            if idx in sheet2_claimed:
                continue
            if norm(s1['Name']) == norm(s2['Name']):
                found = (idx, s2, f"Name Match (Attribute Diff)")
                break

    if found:
        idx, s2, mtype = found
        sheet2_claimed.add(idx)
        mapped_s1.append({'s1': s1, 's2': s2, 'type': mtype})
    else:
        unmapped_s1.append(s1)

extra_in_s2 = [s2 for idx, s2 in enumerate(sheet2_items) if idx not in sheet2_claimed]

print(f"=== RESULTS ===")
print(f"Sheet 1 total: {len(sheet1_items)}")
print(f"Sheet 2 total: {len(sheet2_items)}")
print(f"Mapped products: {len(mapped_s1)}")
print(f"Truly Missing from Sheet 1: {len(unmapped_s1)}")
print(f"Extra in Sheet 2: {len(extra_in_s2)}")

print("\n--- TRULY MISSING FROM SHEET 1 (NOT IN SHEET 2) ---")
for s in unmapped_s1:
    print(f"- {s['Name']} | Size: {s['Size']} | Finish: {s['Finish']} | Look: {s['Look']} | Color: {s['Color']}")

print("\n--- EXTRA PRODUCTS IN SHEET 2 (NOT IN SHEET 1) ---")
for s in extra_in_s2:
    print(f"- [ID {s['ID']}] {s['Name']} | Size: {s['Size']} | Finish: {s['Finish']} | Look: {s['Look']} | Color: {s['Color']}")
