<?php
/**
 * Custom post types for the catalogue: `board` (the decorative board
 * catalogue), `edge_tape` (edge banding), and `spray_finish` (Spray
 * Finishing overlay options). Each is its own CPT rather than a shared
 * "option" bucket, since they're independently growing lists with their
 * own fields.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', 'cutlist_register_post_types');
add_action('init', 'cutlist_register_taxonomies');
add_action('init', 'cutlist_seed_default_spray_finishes', 20);

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

	// `spray_finish`: the options offered in the Spray Finishing overlay —
	// previously a hardcoded SPRAY_OPTIONS object in proto-main.js. Each
	// post is one finish type (White primer, Solid colour paint, ...); its
	// post_title is used directly as the customer-facing label, so there's
	// no separate "label" field to keep in sync. 'page-attributes' gives it
	// a Menu order field so admins can control the picker's display order.
	register_post_type('spray_finish', [
		'label' => __('Spray Finishes', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Spray Finishes', 'cutlist-catalogue'),
			'singular_name' => __('Spray Finish', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Spray Finish', 'cutlist-catalogue'),
			'edit_item' => __('Edit Spray Finish', 'cutlist-catalogue'),
			'search_items' => __('Search Spray Finishes', 'cutlist-catalogue'),
			'all_items' => __('All Spray Finishes', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => false,
		'menu_icon' => 'dashicons-art',
		'supports' => ['title', 'page-attributes'],
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

// Runs once: if no Spray Finish posts exist yet (a fresh install, or this
// site right after the CPT was introduced), seed the three finishes that
// used to be hardcoded so the Spray Finishing overlay doesn't suddenly go
// empty. Does nothing once at least one post exists — from then on the
// list is entirely wp-admin's to manage.
function cutlist_seed_default_spray_finishes() {
	if (!post_type_exists('spray_finish') || wp_count_posts('spray_finish')->publish > 0) {
		return;
	}

	$defaults = [
		[
			'title' => 'White primer',
			'panel_fill_colour' => '#c9d6e8',
			'show_paint_fields' => 0,
			'finishes' => "White primer | | 25.00",
			'b_side_text' => '',
			'b_side_price' => '',
		],
		[
			'title' => 'Solid colour paint',
			'panel_fill_colour' => '#dcc8dc',
			'show_paint_fields' => 1,
			'finishes' => "Satin finish | colour, 25% sheen | 60.35\nMatt finish | colour, 5% sheen | 62.95",
			'b_side_text' => 'Spray B side with white primer only',
			'b_side_price' => '25.00',
		],
		[
			'title' => 'Clear lacquer',
			'panel_fill_colour' => '#cfe3d6',
			'show_paint_fields' => 0,
			'finishes' => "Satin finish | clear lacquer, 25% sheen | 40.95\nMatt finish | clear lacquer, 5% sheen | 41.95",
			'b_side_text' => 'Spray B side with clear sealant only',
			'b_side_price' => '20.00',
		],
	];

	foreach ($defaults as $i => $d) {
		$post_id = wp_insert_post([
			'post_type' => 'spray_finish',
			'post_title' => $d['title'],
			'post_status' => 'publish',
			'menu_order' => $i,
		]);
		if (!$post_id || is_wp_error($post_id)) {
			continue;
		}
		update_field('panel_fill_colour', $d['panel_fill_colour'], $post_id);
		update_field('show_paint_fields', $d['show_paint_fields'], $post_id);
		update_field('finishes', $d['finishes'], $post_id);
		update_field('b_side_text', $d['b_side_text'], $post_id);
		update_field('b_side_price', $d['b_side_price'], $post_id);
	}
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

// "Panel colour" + "Finishes" columns for the Spray Finishes list.
add_filter('manage_spray_finish_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $key => $label) {
		$new[$key] = $label;
		if ($key === 'title') {
			$new['sdpoc_spray_colour'] = __('Panel colour', 'cutlist-catalogue');
			$new['sdpoc_spray_finishes'] = __('Finishes', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_spray_finish_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sdpoc_spray_colour') {
		$colour = get_field('panel_fill_colour', $post_id);
		echo $colour
			? '<span style="display:inline-block;width:16px;height:16px;border:1px solid #ccc;vertical-align:middle;background:' . esc_attr($colour) . '"></span> ' . esc_html($colour)
			: '—';
		return;
	}

	if ($column === 'sdpoc_spray_finishes') {
		$lines = preg_split('/\r\n|\r|\n/', get_field('finishes', $post_id) ?: '');
		$titles = array_filter(array_map(function ($line) {
			$parts = explode('|', $line);
			return trim($parts[0]);
		}, $lines));
		echo $titles ? esc_html(implode(', ', $titles)) : '—';
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
