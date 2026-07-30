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
 * unitPrice } shape the prototype's Select2 picker already expects — one
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
				// Which board (by decor code) this option is matched to — the
				// front end only offers a tape option once its matched board
				// has actually been added to the cutting list.
				'decorCode' => $board['decor_code'],
			];
		}
	}
	return $options;
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
			'downloads' => $board['downloads'],
			'manufacturer_url' => $board['manufacturer_url'] ?: '',
		];
	}
	return $out;
}

add_shortcode('cutlist_table', function () {
	$boards = cutlist_proto_get_boards();

	wp_enqueue_style(
		'cutlist-proto-css',
		cutlist_proto_asset_url('css/proto-extracted.css'),
		[],
		filemtime(cutlist_proto_asset_path('css/proto-extracted.css'))
	);

	// A real PHP template (not a static HTML asset string-replaced at
	// runtime) so it can use translation functions, conditionals, etc.
	// directly — $boards is in scope for it via this include.
	ob_start();
	include CUTLIST_CATALOGUE_PATH . 'templates/cutlist-table.php';
	$body = ob_get_clean();

	$pm_products_json = wp_json_encode(cutlist_proto_build_pm_products($boards));
	$edge_tapes_json = wp_json_encode(cutlist_proto_get_edge_tape_options());

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
	wp_enqueue_script(
		'cutlist-proto-main',
		cutlist_proto_asset_url('js/proto-main.js'),
		['cutlist-proto-basket-store', 'cutlist-konva'],
		filemtime(cutlist_proto_asset_path('js/proto-main.js')),
		true
	);
	wp_add_inline_script(
		'cutlist-proto-main',
		'window.cutlistPmProducts = ' . $pm_products_json . ';' .
			'window.cutlistEdgeTapes = ' . $edge_tapes_json . ';',
		'before'
	);
	wp_add_inline_script(
		'cutlist-proto-main',
		file_get_contents(cutlist_proto_asset_path('js/proto-small.js')),
		'after'
	);
	wp_enqueue_script(
		'cutlist-proto-trade-gate',
		cutlist_proto_asset_url('js/trade-gate.js'),
		['cutlist-proto-main'],
		filemtime(cutlist_proto_asset_path('js/trade-gate.js')),
		true
	);

	return $body;
});
