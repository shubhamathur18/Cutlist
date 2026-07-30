<?php
/**
 * `board`: the decorative board catalogue. Its own CPT (not folded into
 * a shared "option" bucket) because it's the large/growing list — many
 * manufacturers, many decors, each with its own thickness/pricing table.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', 'cutlist_register_post_types');
add_action('init', 'cutlist_register_taxonomies');

function cutlist_register_post_types() {

	register_post_type('board', [
		'label' => __('Boards', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Boards', 'cutlist-catalogue'),
			'singular_name' => __('Board', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Board', 'cutlist-catalogue'),
			'edit_item' => __('Edit Board', 'cutlist-catalogue'),
			'search_items' => __('Search Boards', 'cutlist-catalogue'),
			'all_items' => __('All Boards', 'cutlist-catalogue'),
		],
		'public' => true,
		// Classic (non-block) edit screen: the catalogue's own REST routes
		// (registered separately below) don't need the CPT's REST
		// controller, and a plain meta-box screen matches how the rest of
		// the Board fields are edited instead of mixing in Gutenberg.
		'show_in_rest' => false,
		'menu_icon' => 'dashicons-grid-view',
		'supports' => ['title', 'thumbnail'],
		'has_archive' => false,
		'rewrite' => false,
	]);

	// `edge_tape`: edge banding tape is its own sellable product (code,
	// size, unit price), matched to one or more Boards via a Relationship
	// field rather than living as text on the Board itself, since the
	// same tape (e.g. a solid colour) is often reused across many decors.
	register_post_type('edge_tape', [
		'label' => __('Edge Tapes', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Edge Tapes', 'cutlist-catalogue'),
			'singular_name' => __('Edge Tape', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Edge Tape', 'cutlist-catalogue'),
			'edit_item' => __('Edit Edge Tape', 'cutlist-catalogue'),
			'search_items' => __('Search Edge Tapes', 'cutlist-catalogue'),
			'all_items' => __('All Edge Tapes', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => false,
		'menu_icon' => 'dashicons-editor-outdent',
		'supports' => ['title'],
		'has_archive' => false,
		'rewrite' => false,
	]);
}

function cutlist_register_taxonomies() {

	// Brand (Egger, Kronospan, ...) as a taxonomy rather than a free-text
	// field, so it stays consistent across boards and can be filtered/
	// searched instead of relying on everyone typing the same spelling.
	register_taxonomy('board_brand', 'board', [
		'label' => __('Brand', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Brands', 'cutlist-catalogue'),
			'singular_name' => __('Brand', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => true,
		'hierarchical' => false,
		'show_admin_column' => true,
	]);

	// Collection (PerfectSense Matt, Uni Colours, Feelwood, ...) as a
	// taxonomy for the same reason as Brand: kept consistent and
	// filterable instead of a free-text field.
	register_taxonomy('board_collection', 'board', [
		'label' => __('Collection', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Collections', 'cutlist-catalogue'),
			'singular_name' => __('Collection', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => true,
		'hierarchical' => false,
		'show_admin_column' => true,
	]);
}

// ---- Admin list usability ----

// Filter dropdown: narrow the Boards list to a single Brand.
add_action('restrict_manage_posts', function ($post_type) {
	if ($post_type !== 'board') {
		return;
	}
	$selected_brand = isset($_GET['board_brand']) ? sanitize_text_field(wp_unslash($_GET['board_brand'])) : '';
	$selected_collection = isset($_GET['board_collection']) ? sanitize_text_field(wp_unslash($_GET['board_collection'])) : '';

	wp_dropdown_categories([
		'show_option_all' => __('All brands', 'cutlist-catalogue'),
		'taxonomy' => 'board_brand',
		'name' => 'board_brand',
		'orderby' => 'name',
		'selected' => $selected_brand,
		'hierarchical' => false,
		'hide_empty' => false,
		'value_field' => 'slug',
	]);
	wp_dropdown_categories([
		'show_option_all' => __('All collections', 'cutlist-catalogue'),
		'taxonomy' => 'board_collection',
		'name' => 'board_collection',
		'orderby' => 'name',
		'selected' => $selected_collection,
		'hierarchical' => false,
		'hide_empty' => false,
		'value_field' => 'slug',
	]);
});

add_filter('parse_query', function ($query) {
	global $pagenow;
	if (!is_admin() || $pagenow !== 'edit.php') {
		return;
	}
	if (($query->query['post_type'] ?? '') !== 'board') {
		return;
	}

	$tax_query = [];

	if (!empty($_GET['board_brand'])) {
		$tax_query[] = [
			'taxonomy' => 'board_brand',
			'field' => 'slug',
			'terms' => sanitize_title(wp_unslash($_GET['board_brand'])),
		];
	}

	if (!empty($_GET['board_collection'])) {
		$tax_query[] = [
			'taxonomy' => 'board_collection',
			'field' => 'slug',
			'terms' => sanitize_title(wp_unslash($_GET['board_collection'])),
		];
	}

	if ($tax_query) {
		$query->query_vars['tax_query'] = $tax_query;
	}
});

// WooCommerce isn't a hard dependency of this plugin — it's only used here
// to format admin-list prices the same way WooCommerce prices are shown
// elsewhere. Falls back to a plain formatted number if it's not active, so
// these list screens never fatal-error on an undefined function. Note
// wc_price() returns markup (a wrapped <span>), so it must go through
// wp_kses_post(), not esc_html() — esc_html() would entity-encode the tags
// and print them as visible text instead of a formatted price.
function cutlist_format_price_html($price) {
	if (function_exists('wc_price')) {
		return wp_kses_post(wc_price($price));
	}
	return esc_html(number_format_i18n((float) $price, 2));
}

// "Decor code" + "Sizes" columns so the list is scannable without
// opening every board.
add_filter('manage_board_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $key => $label) {
		$new[$key] = $label;
		if ($key === 'title') {
			$new['sdpoc_decor_code'] = __('Decor code', 'cutlist-catalogue');
			$new['sdpoc_size'] = __('Size', 'cutlist-catalogue');
			$new['sdpoc_price'] = __('Full sheet price', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_board_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sdpoc_decor_code') {
		echo esc_html(get_field('decor_code', $post_id) ?: '—');
		return;
	}

	if ($column === 'sdpoc_size') {
		$length = get_field('length_mm', $post_id);
		$width = get_field('width_mm', $post_id);
		echo ($length && $width) ? esc_html($length . ' x ' . $width . 'mm') : '—';
		return;
	}

	if ($column === 'sdpoc_price') {
		$price = get_field('full_sheet_price', $post_id);
		$thickness = get_field('thickness_mm', $post_id);
		if (!$price) {
			echo '—';
			return;
		}
		echo cutlist_format_price_html($price) . ($thickness ? ' <span style="color:#999">(' . esc_html($thickness) . 'mm)</span>' : '');
	}
}, 10, 2);

// "Tape code", "Size" and "Price" columns for the Edge Tapes list.
add_filter('manage_edge_tape_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $key => $label) {
		$new[$key] = $label;
		if ($key === 'title') {
			$new['sdpoc_tape_code'] = __('Tape code', 'cutlist-catalogue');
			$new['sdpoc_tape_size'] = __('Size', 'cutlist-catalogue');
			$new['sdpoc_tape_price'] = __('Unit price', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_edge_tape_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sdpoc_tape_code') {
		echo esc_html(get_field('tape_code', $post_id) ?: '—');
		return;
	}

	if ($column === 'sdpoc_tape_size') {
		echo esc_html(get_field('size', $post_id) ?: '—');
		return;
	}

	if ($column === 'sdpoc_tape_price') {
		$price = get_field('unit_price', $post_id);
		echo $price ? cutlist_format_price_html($price) : '—';
	}
}, 10, 2);

// Bigger, bolder styling for the plain-text section headers (Machining /
// Pricing / Details) on the Board edit screen, so they read as section
// breaks in the flat field list rather than just another field label.
add_action('admin_head-post.php', 'cutlist_board_section_heading_css');
add_action('admin_head-post-new.php', 'cutlist_board_section_heading_css');

function cutlist_board_section_heading_css() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'board') {
		return;
	}
	?>
	<style>
		.acf-field.sdpoc-section-heading {
			border-top: 2px solid #dcdcde;
			margin-top: 10px;
			padding-top: 10px;
		}
		.acf-field.sdpoc-section-heading .acf-label label {
			font-size: 18px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			color: #1d2327;
		}
		.acf-field.sdpoc-section-heading .acf-input {
			display: none;
		}
	</style>
	<?php
}
