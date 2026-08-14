# CuttingList Catalogue — Project Brief

A WordPress plugin (`wp-content/plugins/cutlist-catalogue/`) that powers a **cut-to-size panel ordering app**. A customer builds a cutting list (board decor, dimensions, quantity, edge banding, machining, spray finishing), all rendered on one page via a shortcode and driven by a single large vanilla-JS file. No build step, no framework — plain PHP templates + one big `<script>`.

Local dev URL: `http://cutlist.local/cutlist/` (the page containing the `[cutlist_table]` shortcode). Site root: `wp-content/plugins/cutlist-catalogue/`.

## Mental model

Everything editable in wp-admin (boards, edge tapes, spray finishes, machining options) is a **Custom Post Type + ACF fields**, exposed to the front end in two ways:
1. A **REST API** (`cutlist/v1/...`) for general consumption.
2. Data **inlined into the page** as `window.cutlist*` globals via `wp_add_inline_script()`, which is what the cutting-list table's JS actually reads (avoids an extra round-trip for data needed at first paint).

The front end is **one HTML page** (`templates/cutlist-table.php`) with several overlays/popups (edge finishing, machining, spray finishing) that only one JS file (`cutlist-main.js`) drives — jQuery-free, no React/Vue. Originally ported from a static HTML/CSS/JS prototype (`assets/proto/`), kept structurally close to it on purpose so the prototype's inline JS patterns still apply.

## File map

```
cutlist-catalogue.php              Plugin bootstrap: requires the includes/, registers ACF json paths
includes/
  cpt-registration.php             Registers the 4 CPTs + ACF field groups get loaded from acf-json/
  cutting-list-shortcode.php       [cutlist_table] shortcode: builds $boards, enqueues CSS/JS,
                                    inlines window.cutlistPmProducts / cutlistEdgeTapes /
                                    cutlistSprayFinishes / cutlistMachiningOptions
  rest-endpoints.php               cutlist/v1 REST routes + the *_format_* functions that shape
                                    each CPT's data for both REST and the inline-script path
  gallery-meta-box.php             Custom "image gallery" meta box used on the Board edit screen
templates/
  cutlist-table.php                The entire front-end page markup: cutting list table + every
                                    popup/overlay (edge finishing, machining, spray finishing,
                                    decor picker, panel info, panel summary)
assets/
  css/cutlist-main.css          All styling (one file, ~189KB)
  js/cutlist-main.js                 ~7000 lines — all interactivity. See below.
  js/proto-small.js                Small inline-appended tail script
  js/basket-store.js               Cart/basket state
  js/trade-gate.js                 Trade-login gate
  js/konva.min.js                  Vendored (Konva 9.x, MIT) — draws the Machining overlay's
                                    canvas diagram
  js/xlsx.min.js                   Vendored (SheetJS/xlsx 0.18.5, Apache-2.0) — parses uploaded
                                    .xlsx/.csv cutting lists
  proto/                           The ORIGINAL static prototype (body.html, cut-edge-spray.html)
                                    — reference only, not loaded by WordPress
acf-json/                          ACF field group definitions as JSON (version-controlled, not
                                    DB-only) — group_board_fields.json, group_edge_tape_fields.json,
                                    group_machining_option_fields.json, group_spray_finish_fields.json
                                    (the non-"_fields" siblings, e.g. group_board.json, are stale
                                    duplicate files — ignore them)
```

## Data model (4 CPTs)

| CPT | Purpose | Key ACF fields |
|---|---|---|
| `board` | Decorative board catalogue | decor_code, decor_name, brand (taxonomy), thicknesses[], length/width, price_sheet, grainMatch, sprayFinishing, featured image (swatch) |
| `edge_tape` | Edge banding tape, sold separately | tape_code, product_name, size, unit_price, radius_edge_finish / square_edge_finish (bool), boards (relationship → which decors it matches), featured image |
| `spray_finish` | Options in the Spray Finishing overlay | label (post_title), finishes[] (title/sub/price), paintFields (bool), paintBrands[], bOption, panelFill |
| `machining_option` | Options in the Additional Machining overlay | label, group, **behaviour** (`simple` / `angled-cut` / `groove` / `hinge-holes` / `shelf-holes` / `j-handle`) — the behaviour field is what cutlist-main.js branches its Konva drawing logic on, not the post's slug |

Boards ↔ Edge Tapes is a many-to-many via ACF relationship; `cutlist_proto_get_edge_tape_options()` flattens it into one row per (tape, matched board) pair for the front end.

## The cutting list table

`templates/cutlist-table.php` renders a table where each row is one cut panel: decor, thickness, length, width, qty, description, 4 edge-banding cells (L1/L2/W1/W2), an "Additional machining" cell, a "Spray finishing" cell, a grain-match checkbox, and row actions (move/edit/view/delete).

Row lifecycle in `cutlist-main.js`:
- `createRow()` / `resetRow()` — clone-and-blank a row (also used when the cutting-list **upload** feature reuses existing rows instead of deleting them)
- Clicking the decor cell opens `#decorPopup` (board picker, grouped by brand/tab) — picking a board unlocks the rest of the row, populates thickness options from that board's real thicknesses, and sets `spray-allowed` / `grain-allowed` classes that gate the Spray/Machining "Add" buttons (which also require qty ≥ 1 — see `updateMachiningBtn` / `updateSprayBtn`)
- Length/width input fires `updateEdging()`, which enables/disables the 4 edge cells based on minimum panel size

## The three overlays

All three are absolute/fixed-positioned panels toggled via `.open`/`display` classes, each keyed by row (`WeakMap`s: `edgeState`, `sprayStateByRow`, `machiningAppliedItems` per row).

### 1. Edge finishing popup (`#edgePopup`)
Click an L1/L2/W1/W2 cell → `openEdgePopup(row, edge, anchorEl)`. Shows the matched edge tape (auto-matched by decor, not user-selected) with a product image, radius/square finish choice, and a Standard/Expert mode toggle. Recently rebuilt to a specific pixel-perfect reference design (header bar, green accent `--edge-accent`, product image + radio dot).

### 2. Machining overlay (`#machiningOverlay`)
The complex one — a live **Konva canvas** diagram (`machiningKonvaStage`) drawing the panel to scale with whichever machining options are applied. `behaviour` drives which drawing function runs:

- **`angled-cut`** — corner chamfer; two draggable position arrows (`hLabel`/`vLabel`)
- **`groove`** — a slot; 3 draggable position arrows (end1/end2/distance)
- **`hinge-holes`** ("Blum 35mm Screw" etc.) — N holes evenly spaced along an edge, each with a draggable hole marker + a **position-marker callout** (box with the mm value + triangle pointer). This callout's exact visual design (box + triangle, specific pixel offsets defined as `HINGE_TIP_GAP`/`HINGE_TRI_H`/`HINGE_TRI_HALF_W`/`HINGE_BOX_GAP`) is the **canonical style** — groove and angled-cut's callouts were rebuilt to match it exactly, sharing the same constants (renamed `POS_*` for the shared `buildMachiningPositionLabel()`/`updateMachiningPositionLabel()` widget)
- **`shelf-holes`** — pin-hole clusters, two rows, similar draggable-cluster + callout pattern
- **`j-handle`** — an edge recess between two configurable end insets, same callout pattern

Every draggable arrow triangle across all 5 behaviours now shares one cursor helper, `machiningBindGrabCursor()` (grab on hover, grabbing mid-drag).

**Realism constraint worth knowing:** hinge-hole positions account for a co-located angled cut — `machiningHingeCutClearance()` finds an angled-cut item sharing a corner with the hinge edge and pushes the hole's legal range past the cut line (+50mm clearance), so a hole can't be placed on material the cut has already removed. If you add similar "real-world constraint" work to groove/shelf/j-handle, this is the pattern to follow.

Layout/redraw split: each behaviour has a `machining*Layout()` (pure positioning, safe to re-run on drag) separate from the shape-creation code that runs once per full redraw (`redrawMachiningCanvas()` → `update*()` per behaviour) — this split is what makes dragging performant and is why any new behaviour should follow the same shape.

### 3. Spray finishing overlay (`#sprayOverlay`)
Per-finish options (from the `spray_finish` CPT) with A-side (always on, locked) / B-side toggle, finish cards, optional paint brand/colour fields, live area (sq.m) and total price (`updateSprayVisuals()`), and a "Spray B side with white primer only" checkbox for some finishes.

## Cutting list upload/download

- **Download** (`downloadRowBtn`): exports the table as CSV — column order `Decor code, Thickness, Length, Width, Quantity, Description, L1, L2, W1, W2, Edge finish, Customer note`.
- **Upload** (`uploadRowBtn` → hidden `#uploadRowFileInput`): reads `.xlsx`/`.csv` via vendored SheetJS, maps columns **by header name** (case-insensitive aliases, not position) so a re-uploaded download round-trips, and re-selects each row's board by dispatching a real click on the matching `.product-row` in the decor popup (not reimplementing that selection logic separately, so every side effect — spray/grain eligibility, thickness options — stays correct). Fills into **existing** rows first (in order) and only creates new ones once those run out — never deletes rows the file doesn't cover. Warns via `confirm()` before overwriting any row that already has a board selected.

## Things to know before changing code here

- **No build step.** Edit `cutlist-main.js`/`cutlist-main.css` directly; WordPress cache-busts via `filemtime()` in the `wp_enqueue_*` calls, so a hard refresh always picks up changes.
- **Vendored libraries are self-hosted**, never CDN-loaded — see how Konva and xlsx are enqueued in `cutting-list-shortcode.php` for the pattern to follow if another library is needed.
- **`window.cutlistPmProducts`** keys by decor code and is the single source of truth the JS reads for board data (thicknesses, spray/grain eligibility, swatch image) — not a live REST call.
- The `assets/proto/` folder is the original static prototype this was ported from — useful as a design reference, never loaded by the live site.
