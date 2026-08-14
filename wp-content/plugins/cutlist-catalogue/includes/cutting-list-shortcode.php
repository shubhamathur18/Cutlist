<?php
/**
 * [cutlist_table] — the "Cut, Edge & Spray" page, ported from the
 * Cutlist Proto (cut-edge-spray.html) prototype. The markup lives in
 * templates/cutlist-table.php — a real PHP template (translatable,
 * conditionals allowed) rather than a static HTML asset — kept as close
 * to the prototype's structure/classes/ids as possible so the CSS/JS
 * (assets/css, assets/js) need no changes. The only real integration
 * points are: the decor picker + "More info" modal (from the live Board
 * catalogue) and the edging tape picker (from the live Edge Tape
 * catalogue) — both from wp-admin instead of the prototype's hardcoded
 * mock lists. Everything else (spray finishing, machining diagrams,
 * pricing summary, grain matching) is unmodified prototype behaviour
 * with no backend yet.
 */

if (!defined('ABSPATH')) {
	exit;
}

function cutlist_proto_asset_url($relative) {
	return plugins_url('assets/' . $relative, CUTLIST_CATALOGUE_PATH . 'cutlist-catalogue.php');
}

function cutlist_proto_asset_path($relative) {
	return CUTLIST_CATALOGUE_PATH . 'assets/' . $relative;
}

/**
 * Every published board, in the same shape rest-endpoints.php's
 * cutlist_format_board() already produces (brand, decor_code,
 * characteristics, gallery, etc.) — reused here so the popup and the
 * REST API never drift apart.
 */
function cutlist_proto_get_boards() {
	$posts = get_posts([
		'post_type' => 'board',
		'numberposts' => -1,
		'post_status' => 'publish',
	]);
	return array_map('cutlist_format_board', $posts);
}

/**
 * Flattens the Edge Tape catalogue into the exact { code, name, size,
 * unitPrice, image } shape the prototype's Select2 picker already expects — one
 * option per (tape, matched board) pair, e.g. tape "G1-22" matched to
 * both H1227-TM12 and H1228-TM12 produces two selectable rows, same as
 * the prototype's hardcoded convention. A tape with no matched boards
 * yet isn't shown at all — nothing to match it to on the cutting list.
 */
function cutlist_proto_get_edge_tape_options() {
	$posts = get_posts([
		'post_type' => 'edge_tape',
		'numberposts' => -1,
		'post_status' => 'publish',
	]);
	$tapes = array_map('cutlist_format_edge_tape', $posts);

	$options = [];
	foreach ($tapes as $tape) {
		$size = $tape['size'];

		if (!$tape['boards']) {
			continue;
		}

		foreach ($tape['boards'] as $board) {
			$options[] = [
				'code' => $tape['tape_code'] . ' / ' . $board['decor_code'],
				'name' => $tape['product_name'] . ' - ' . $board['decor_name'],
				'size' => $size,
				'unitPrice' => $tape['unit_price'],
				'image' => $tape['image'],
				// Which board (by decor code) this option is matched to — the
				// front end only offers a tape option once its matched board
				// has actually been added to the cutting list.
				'decorCode' => $board['decor_code'],
				// Which edge finishes this tape supports, for the Radius /
				// Square toggle shown on an angled cut.
				'radiusEdgeFinish' => $tape['radius_edge_finish'],
				'squareEdgeFinish' => $tape['square_edge_finish'],
			];
		}
	}
	return $options;
}

/**
 * Every published Spray Finish, in the shape cutlist-main.js's SPRAY_OPTIONS
 * object used to be hardcoded as (see cutlist_format_spray_finish() in
 * rest-endpoints.php) — the front end keys this array by `slug` to rebuild
 * that same object at runtime.
 */
function cutlist_proto_get_spray_finishes() {
	$posts = get_posts([
		'post_type' => 'spray_finish',
		'numberposts' => -1,
		'post_status' => 'publish',
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);
	return array_map('cutlist_format_spray_finish', $posts);
}

/**
 * Supplier-tabs + product-list markup for #decorPopup, grouped by brand —
 * structurally identical to the prototype's hardcoded version (same
 * classes/data-attributes) so the existing inline JS (supplier-tab
 * switching, product-row selection) needs no changes.
 */
function cutlist_proto_render_decor_popup_inner($boards) {
	$by_brand = [];
	foreach ($boards as $board) {
		$brand = $board['brand'] ?: 'Other';
		$by_brand[$brand][] = $board;
	}

	if (!$by_brand) {
		return '<div class="product-list active"><div class="product-row" style="cursor:default;"><span>' . esc_html__('No boards yet — add one in wp-admin under Boards.', 'cutlist-catalogue') . '</span></div></div>';
	}

	$brands = array_keys($by_brand);

	ob_start();
	?>
	<div class="supplier-tabs">
		<?php foreach ($brands as $i => $brand) : ?>
			<div class="supplier-tab<?php echo $i === 0 ? ' active' : ''; ?>" data-tab="<?php echo esc_attr(sanitize_title($brand)); ?>">
				<?php echo esc_html($brand); ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php foreach ($brands as $i => $brand) : ?>
		<div class="product-list<?php echo $i === 0 ? ' active' : ''; ?>" id="<?php echo esc_attr(sanitize_title($brand)); ?>">
			<?php foreach ($by_brand[$brand] as $board) : ?>
				<div class="product-row" data-size="<?php echo esc_attr(($board['length_mm'] ?: '') . 'x' . ($board['width_mm'] ?: '')); ?>" data-brand="<?php echo esc_attr($brand); ?>">
					<span><?php echo esc_html($board['decor_code']); ?></span>
					<span><?php echo esc_html($board['decor_name']); ?></span>
					<!-- <span class="prod-size"><?php echo esc_html(($board['length_mm'] ?: '?') . ' x ' . ($board['width_mm'] ?: '?')); ?></span> -->
					<span><?php echo esc_html($board['core'] ?: '—'); ?></span>
					<span class="more">More info</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach;
	return ob_get_clean();
}

/**
 * Inner markup for a decor cell, shared by the cutting-list and full-sheet
 * rows. Three layers live here: the input, which is never visible but
 * still carries the value every other reader parses the decor code out of;
 * the empty-state placeholder; and — added by renderDecorCard() in
 * cutlist-main.js once a board is picked — the decor card. CSS picks which
 * of the last two shows, keyed off .has-decor on the cell.
 */
function cutlist_proto_render_decor_cell() {
	ob_start();
	?>
	<input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>">
	<div class="decor-placeholder">
		<span class="decor-placeholder-icon" aria-hidden="true">
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
				<circle cx="10.5" cy="10.5" r="6.5"></circle>
				<line x1="21" y1="21" x2="15.5" y2="15.5"></line>
			</svg>
		</span>
		<span class="decor-card-body">
			<span class="decor-placeholder-hint"><?php esc_html_e('Search decor code or name', 'cutlist-catalogue'); ?></span>
			<span class="decor-placeholder-action"><?php esc_html_e('Select a board', 'cutlist-catalogue'); ?></span>
		</span>
		<span class="decor-chevron" aria-hidden="true">
			<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
				<path d="M4.5 7.5 10 13l5.5-5.5"></path>
			</svg>
		</span>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * The "Additional panel information" popup, nested inside a row's own
 * .actions-inner (right after the edit icon) rather than living once as a
 * shared body-level overlay the way the decor/edge popups do. That's
 * deliberate: it lets the popup be positioned purely with CSS — .actions
 * -inner is `position: relative` and the popup anchors to it with
 * `right: 0`, which pins it to the Actions column's own right edge (the
 * last column, so that edge never overflows the table) instead of needing
 * JS to measure the viewport and clamp a body-level element into it.
 * Every row gets its own instance, so createRow()'s cloneNode duplicates
 * it automatically — same reasoning as the decor cell above, just without
 * a JS-rendered state to clear (clearPanelInfoPopup() in cutlist-main.js
 * only has to blank the textarea and close it).
 */
function cutlist_proto_render_panel_info_popup() {
	ob_start();
	?>
	<div class="panel-info-popup">
		<div class="panel-info-title"><?php esc_html_e('Additional panel information', 'cutlist-catalogue'); ?></div>
		<textarea class="panel-info-textarea" maxlength="50" placeholder="<?php esc_attr_e('Type here', 'cutlist-catalogue'); ?>"></textarea>
		<div class="panel-info-counter"><span class="panel-info-counter-value">50</span> <?php esc_html_e('Characters left', 'cutlist-catalogue'); ?></div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * pmProducts object for the "More info" panel modal — same shape the
 * prototype's hardcoded mock object used (fullCode/title/name/brand/
 * length/width/material/desc/bside/chars/thicknesses/price_sheet/
 * price_cut/slides), built from real board data instead.
 */
function cutlist_proto_build_pm_products($boards) {
	$out = [];
	foreach ($boards as $board) {
		$chars = $board['characteristics'];
		$lines = [];
		for ($i = 0; $i < count($chars); $i += 2) {
			$pair = [$chars[$i]['label'] . ': ' . $chars[$i]['value']];
			if (isset($chars[$i + 1])) {
				$pair[] = $chars[$i + 1]['label'] . ': ' . $chars[$i + 1]['value'];
			}
			$lines[] = implode(' | ', $pair);
		}

		$slides = [];
		foreach ($board['gallery'] as $img) {
			$slides[] = [
				'label' => $img['alt'] ?: 'Decor',
				'bg' => "url('" . esc_url_raw($img['url']) . "') center/cover no-repeat",
			];
		}
		if (!$slides) {
			$slides[] = ['label' => 'Decor', 'bg' => '#ccc'];
		}

		$out[$board['decor_code']] = [
			'fullCode' => $board['decor_code'],
			'title' => trim($board['brand'] . ' ' . $board['decor_code']),
			'name' => $board['decor_name'],
			'brand' => $board['brand'],
			// Both feed the decor card the cutting list shows once a board
			// is picked (swatch + title + name + collection).
			'collection' => $board['collection'],
			'swatch' => $board['swatch'] ?: '',
			'length' => $board['length_mm'],
			'width' => $board['width_mm'],
			'material' => $board['core'] ?: '–',
			'desc' => $board['description'] ?: '',
			'bside' => $board['b_side_description'] ?: '',
			'chars' => implode("\n", $lines),
			'thicknesses' => $board['thickness_mm'] ? [$board['thickness_mm']] : [],
			// No cutting-price field exists yet (see full_sheet_price's
			// ACF instructions) — only the full-sheet price is real.
			'price_sheet' => $board['full_sheet_price'] ? '£' . number_format($board['full_sheet_price'], 2) : '–',
			'price_cut' => '–',
			'slides' => $slides,
			'machining' => $board['machining'],
			'machiningExcluded' => $board['machining_excluded'],
			'sprayFinishing' => $board['spray_finishing'],
			'grainMatch' => $board['grain_match'],
			'downloads' => $board['downloads'],
			'manufacturer_url' => $board['manufacturer_url'] ?: '',
		];
	}
	return $out;
}

add_shortcode('cutlist_table', function () {
	$boards = cutlist_proto_get_boards();
	$spray_finishes = cutlist_proto_get_spray_finishes();

	wp_enqueue_style(
		'cutlist-css',
		cutlist_proto_asset_url('css/cutlist-main.css'),
		[],
		filemtime(cutlist_proto_asset_path('css/cutlist-main.css'))
	);

	// A real PHP template (not a static HTML asset string-replaced at
	// runtime) so it can use translation functions, conditionals, etc.
	// directly — $boards/$spray_finishes are in scope for it via this include.
	ob_start();
	include CUTLIST_CATALOGUE_PATH . 'templates/cutlist-table.php';
	$body = ob_get_clean();

	$pm_products_json = wp_json_encode(cutlist_proto_build_pm_products($boards));
	$edge_tapes_json = wp_json_encode(cutlist_proto_get_edge_tape_options());
	$spray_finishes_json = wp_json_encode($spray_finishes);
	$machining_options_json = wp_json_encode(
		array_map('cutlist_format_machining_option', cutlist_get_machining_option_posts())
	);

	// JS must go through wp_enqueue_script/wp_add_inline_script, never be
	// echoed inline inside the shortcode's returned HTML: content returned
	// from a shortcode is still post-processed by 'the_content' filters
	// (wptexturize, convert_chars, ...) after the shortcode expands, which
	// mangle raw JS syntax — e.g. "&&" was getting rewritten to
	// "&#038;&#038;", breaking the script with a SyntaxError.
	wp_enqueue_script(
		'cutlist-proto-basket-store',
		cutlist_proto_asset_url('js/basket-store.js'),
		[],
		filemtime(cutlist_proto_asset_path('js/basket-store.js')),
		true
	);
	// Vendored locally (assets/js/konva.min.js, Konva 9.x, MIT-licensed) —
	// same self-hosted pattern as every other script here, no CDN/build
	// step. Draws the Machining overlay's technical diagram (panel,
	// dimension lines, the draggable angled-cut corner handle) on native
	// <canvas>, replacing the plain Canvas 2D redraw-from-scratch approach
	// so the corner handle can be dragged interactively.
	wp_enqueue_script(
		'cutlist-konva',
		cutlist_proto_asset_url('js/konva.min.js'),
		[],
		filemtime(cutlist_proto_asset_path('js/konva.min.js')),
		true
	);
	// Vendored locally (assets/js/xlsx.min.js, SheetJS Community Edition
	// 0.18.5, Apache-2.0) — same self-hosted pattern as Konva above. Reads
	// both .xlsx and .csv uploads for the "Upload cutting list" button.
	wp_enqueue_script(
		'cutlist-xlsx',
		cutlist_proto_asset_url('js/xlsx.min.js'),
		[],
		filemtime(cutlist_proto_asset_path('js/xlsx.min.js')),
		true
	);
	wp_enqueue_script(
		'cutlist-main',
		cutlist_proto_asset_url('js/cutlist-main.js'),
		['cutlist-proto-basket-store', 'cutlist-konva', 'cutlist-xlsx'],
		filemtime(cutlist_proto_asset_path('js/cutlist-main.js')),
		true
	);
	wp_add_inline_script(
		'cutlist-main',
		'window.cutlistPmProducts = ' . $pm_products_json . ';' .
			'window.cutlistEdgeTapes = ' . $edge_tapes_json . ';' .
			'window.cutlistSprayFinishes = ' . $spray_finishes_json . ';' .
			'window.cutlistMachiningOptions = ' . $machining_options_json . ';',
		'before'
	);
	wp_add_inline_script(
		'cutlist-main',
		file_get_contents(cutlist_proto_asset_path('js/proto-small.js')),
		'after'
	);
	wp_enqueue_script(
		'cutlist-proto-trade-gate',
		cutlist_proto_asset_url('js/trade-gate.js'),
		['cutlist-main'],
		filemtime(cutlist_proto_asset_path('js/trade-gate.js')),
		true
	);

	return $body;
});
