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

function cutlist_proto_asset_url($relative)
{
	return plugins_url('assets/' . $relative, CUTLIST_CATALOGUE_PATH . 'cutlist-catalogue.php');
}

function cutlist_proto_asset_path($relative)
{
	return CUTLIST_CATALOGUE_PATH . 'assets/' . $relative;
}

/**
 * Every published board, in the same shape rest-endpoints.php's
 * cutlist_format_board() already produces (brand, decor_code,
 * characteristics, gallery, etc.) — reused here so the popup and the
 * REST API never drift apart.
 */
function cutlist_proto_get_boards()
{
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
function cutlist_proto_get_edge_tape_options()
{
	$posts = get_posts([
		'post_type' => 'edge_tape',
		'numberposts' => -1,
		'post_status' => 'publish',
	]);
	$tapes = array_map('cutlist_format_edge_tape', $posts);

	$options = [];
	foreach ($tapes as $tape) {
		$size = $tape['size'];

		if (!empty($tape['boards'])) {
			foreach ($tape['boards'] as $board) {
				$options[] = [
					'code' => $tape['tape_code'] . ($board['decor_code'] ? (' / ' . $board['decor_code']) : ''),
					'name' => $tape['product_name'] . ($board['decor_name'] ? (' - ' . $board['decor_name']) : ''),
					'size' => $size,
					'unitPrice' => $tape['unit_price'],
					'price' => $tape['unit_price'],
					'image' => $tape['image'],
					'decorCode' => $board['decor_code'] ?? '',
					'radiusEdgeFinish' => $tape['radius_edge_finish'],
					'squareEdgeFinish' => $tape['square_edge_finish'],
					'stock_units' => (int) ($tape['stock_units'] ?? 10),
					'stock_quantity' => (int) ($tape['stock_quantity'] ?? 10),
					'stock_status' => $tape['stock_status'] ?? 'instock',
					'is_managed_stock' => $tape['is_managed_stock'] ?? false,
					'is_out_of_stock' => !empty($tape['is_out_of_stock']),
					'rack_location' => $tape['rack_location'] ?? '',
					'supplier_sku' => $tape['supplier_sku'] ?? '',
				];
			}
		} else {
			$options[] = [
				'code' => $tape['tape_code'],
				'name' => $tape['product_name'],
				'size' => $size,
				'unitPrice' => $tape['unit_price'],
				'price' => $tape['unit_price'],
				'image' => $tape['image'],
				'decorCode' => '',
				'radiusEdgeFinish' => $tape['radius_edge_finish'],
				'squareEdgeFinish' => $tape['square_edge_finish'],
				'stock_units' => (int) ($tape['stock_units'] ?? 10),
				'stock_quantity' => (int) ($tape['stock_quantity'] ?? 10),
				'stock_status' => $tape['stock_status'] ?? 'instock',
				'is_managed_stock' => $tape['is_managed_stock'] ?? false,
				'is_out_of_stock' => !empty($tape['is_out_of_stock']),
				'rack_location' => $tape['rack_location'] ?? '',
				'supplier_sku' => $tape['supplier_sku'] ?? '',
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
function cutlist_proto_get_spray_finishes()
{
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
function cutlist_get_board_brands()
{
	$brands = [];
	$terms = get_terms([
		'taxonomy' => 'board_brand',
		'hide_empty' => false,
	]);
	if (!empty($terms) && !is_wp_error($terms)) {
		foreach ($terms as $t) {
			$brands[$t->slug] = $t->name;
		}
	}
	if (!$brands) {
		$posts = get_posts([
			'post_type' => 'board',
			'numberposts' => -1,
			'post_status' => 'publish',
		]);
		foreach ($posts as $p) {
			$bterms = get_the_terms($p->ID, 'board_brand');
			if ($bterms && !is_wp_error($bterms)) {
				foreach ($bterms as $bt) {
					$brands[$bt->slug] = $bt->name;
				}
			}
		}
	}
	return $brands;
}

/**
 * Supplier-tabs + AJAX-ready product-list markup for #decorPopup, grouped by brand.
 */
function cutlist_proto_render_decor_popup_inner($boards)
{
	$brands = cutlist_get_board_brands();

	if (!$brands) {
		// Fallback if no brands taxonomy exists
		$by_brand = [];
		foreach ($boards as $board) {
			$brand = $board['brand'] ?: 'Other';
			$by_brand[sanitize_title($brand)] = $brand;
		}
		$brands = $by_brand;
	}

	if (!$brands) {
		return '<div class="product-list active"><div class="product-row" style="cursor:default;"><span>' . esc_html__('No boards yet — add one in wp-admin under Boards.', 'cutlist-catalogue') . '</span></div></div>';
	}

	$brand_slugs = array_keys($brands);

	ob_start();
	?>
	<div class="decor-popup-search-wrap">
		<span class="decor-popup-search-icon" aria-hidden="true">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="11" cy="11" r="8"></circle>
				<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
			</svg>
		</span>
		<input type="text" class="decor-popup-search-input" placeholder="<?php esc_attr_e('Search decor code, name, or core...', 'cutlist-catalogue'); ?>" autocomplete="off" spellcheck="false">
		<button type="button" class="decor-popup-search-clear" style="display:none;" aria-label="<?php esc_attr_e('Clear search', 'cutlist-catalogue'); ?>">&times;</button>
	</div>
	<div class="supplier-tabs">
		<?php foreach ($brand_slugs as $i => $slug): ?>
			<div class="supplier-tab<?php echo $i === 0 ? ' active' : ''; ?>"
				data-tab="<?php echo esc_attr($slug); ?>"
				data-brand="<?php echo esc_attr($slug); ?>">
				<?php echo esc_html($brands[$slug]); ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php foreach ($brand_slugs as $i => $slug): ?>
		<div class="product-list<?php echo $i === 0 ? ' active' : ''; ?>" id="<?php echo esc_attr($slug); ?>" data-brand="<?php echo esc_attr($slug); ?>">
			<div class="decor-popup-loading">
				<span class="decor-popup-spinner"></span>
				<span><?php esc_html_e('Loading boards...', 'cutlist-catalogue'); ?></span>
			</div>
		</div>
	<?php endforeach; ?>
	<div class="product-list decor-search-results" id="decorSearchResults" style="display:none;"></div>
	<?php
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
function cutlist_proto_render_decor_cell()
{
	ob_start();
	?>
	<input type="hidden" class="decor-real-input" value="" placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>">
	<div class="decor-placeholder">
		<span class="decor-placeholder-icon" aria-hidden="true">
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
				stroke-linecap="round">
				<circle cx="10.5" cy="10.5" r="6.5"></circle>
				<line x1="21" y1="21" x2="15.5" y2="15.5"></line>
			</svg>
		</span>
		<span class="decor-card-body">
			<input type="text" class="decor-placeholder-input" placeholder="<?php esc_attr_e('Search decor code or name', 'cutlist-catalogue'); ?>" autocomplete="off" spellcheck="false" />
			<span class="decor-placeholder-action"><?php esc_html_e('Select a board', 'cutlist-catalogue'); ?></span>
		</span>
		<span class="decor-chevron" aria-hidden="true">
			<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
				stroke-linecap="round">
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
function cutlist_proto_render_panel_info_popup()
{
	ob_start();
	?>
	<div class="panel-info-popup">
		<div class="panel-info-title"><?php esc_html_e('Additional panel information', 'cutlist-catalogue'); ?></div>
		<textarea class="panel-info-textarea" maxlength="50"
			placeholder="<?php esc_attr_e('Type here', 'cutlist-catalogue'); ?>"></textarea>
		<div class="panel-info-counter"><span class="panel-info-counter-value">50</span>
			<?php esc_html_e('Characters left', 'cutlist-catalogue'); ?></div>
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
function cutlist_proto_build_pm_products($boards)
{
	$out = [];
	$currency_sym = function_exists('get_woocommerce_currency_symbol') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : '£';
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
			'full_sheet_price' => (float) ($board['full_sheet_price'] ?? 0),
			'cutting_list_price' => (float) (!empty($board['cutting_list_price']) ? $board['cutting_list_price'] : ($board['full_sheet_price'] ?? 0)),
			'additional_cut_price' => (float) (!empty($board['additional_cut_price']) ? $board['additional_cut_price'] : 3.46),
			'price_sheet' => (!empty($board['full_sheet_price']) && (float) $board['full_sheet_price'] > 0) ? $currency_sym . number_format((float) $board['full_sheet_price'], 2) : '–',
			'price_cut' => (!empty($board['cutting_list_price']) && (float) $board['cutting_list_price'] > 0) ? $currency_sym . number_format((float) $board['cutting_list_price'], 2) : ((!empty($board['full_sheet_price']) && (float) $board['full_sheet_price'] > 0) ? $currency_sym . number_format((float) $board['full_sheet_price'], 2) : '–'),
			'stock_status' => $board['stock_status'] ?? 'instock',
			'stock_sheets' => $board['stock_sheets'] ?? 0,
			'is_managed_stock' => $board['is_managed_stock'] ?? false,
			'is_out_of_stock' => !empty($board['is_out_of_stock']),
			'slides' => $slides,
			'thicknesses' => $board['thickness_mm'] ? [$board['thickness_mm']] : [],
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

function cutlist_get_cutlist_page_url()
{
	$page = get_page_by_path('cutlist');
	return $page ? get_permalink($page) : home_url('/cutlist/');
}

function cutlist_get_cnc_page_url()
{
	$page = get_page_by_path('cnc-machining');
	return $page ? get_permalink($page) : home_url('/cnc-machining/');
}

function cutlist_enqueue_frontend_assets($boards, $spray_finishes)
{
	wp_enqueue_style(
		'cutlist-header-tabs-css',
		cutlist_proto_asset_url('css/header-tabs.css'),
		[],
		filemtime(cutlist_proto_asset_path('css/header-tabs.css'))
	);

	wp_enqueue_style(
		'cutlist-css',
		cutlist_proto_asset_url('css/cutlist-main.css'),
		['cutlist-header-tabs-css'],
		filemtime(cutlist_proto_asset_path('css/cutlist-main.css'))
	);

	$pm_products_json = wp_json_encode(cutlist_proto_build_pm_products($boards));
	$edge_tapes_json = wp_json_encode(cutlist_proto_get_edge_tape_options());
	$spray_finishes_json = wp_json_encode($spray_finishes);
	$machining_options_json = wp_json_encode(
		array_map('cutlist_format_machining_option', cutlist_get_machining_option_posts())
	);

	wp_enqueue_script(
		'cutlist-proto-basket-store',
		cutlist_proto_asset_url('js/basket-store.js'),
		[],
		filemtime(cutlist_proto_asset_path('js/basket-store.js')),
		true
	);

	wp_enqueue_script(
		'cutlist-konva',
		cutlist_proto_asset_url('js/konva.min.js'),
		[],
		filemtime(cutlist_proto_asset_path('js/konva.min.js')),
		true
	);

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

	wp_localize_script(
		'cutlist-main',
		'cutlistWcVars',
		[
			'ajax_url' => admin_url('admin-ajax.php'),
			'cart_url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
			'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
			'cutlist_url' => cutlist_get_cutlist_page_url(),
			'cnc_url' => cutlist_get_cnc_page_url(),
			'nonce' => wp_create_nonce('cutlist_cart_nonce'),
			'rest_url' => esc_url_raw(rest_url('cutlist/v1/')),
			'rest_nonce' => wp_create_nonce('wp_rest'),
			'grain_match_price' => (float) get_option('cutlist_grain_match_price', 12.70),
			'grain_match_code' => get_option('cutlist_grain_match_code', 'GRN-MTCH'),
			'grain_match_name' => get_option('cutlist_grain_match_name', 'Grain-matching of panels in clusters'),
			'grain_match_desc' => get_option('cutlist_grain_match_desc', 'Grain-matching of panels based on customer requirements – charged per panel'),
			'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : '£',
		]
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
}

add_shortcode('cutlist_table', function () {
	$boards = cutlist_proto_get_boards();
	$spray_finishes = cutlist_proto_get_spray_finishes();

	cutlist_enqueue_frontend_assets($boards, $spray_finishes);

	ob_start();
	include CUTLIST_CATALOGUE_PATH . 'templates/cutlist-table.php';
	return ob_get_clean();
});



