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
add_action('init', 'cutlist_seed_default_machining_options', 20);
add_action('init', 'cutlist_seed_dummy_egger_boards', 25);
add_action('init', 'cutlist_seed_default_hinged_doors', 25);
add_action('init', 'cutlist_seed_default_sliding_doors', 25);
add_action('init', 'cutlist_cleanup_duplicate_boards', 30);

function cutlist_register_post_types()
{

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
		'supports' => ['title', 'thumbnail'],
		'has_archive' => false,
		'rewrite' => false,
	]);

	// `spray_finish`: the options offered in the Spray Finishing overlay —
	// previously a hardcoded SPRAY_OPTIONS object in cutlist-main.js. Each
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

	// Machining options — the "Additional machining" dropdown. Was hardcoded
	// markup in templates/cutlist-table.php; now a catalogue so options can
	// be added, renamed, reordered, priced and switched off from wp-admin.
	//
	// Note what admin does NOT control: each option's *behaviour* (its
	// parameters, detail panel and drawing) is implemented in code and
	// chosen via the "behaviour" field. A new option can reuse any
	// implemented behaviour; a genuinely new kind of machining still needs
	// a developer.
	register_post_type('machining_option', [
		'label' => __('Machining Options', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Machining Options', 'cutlist-catalogue'),
			'singular_name' => __('Machining Option', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Machining Option', 'cutlist-catalogue'),
			'edit_item' => __('Edit Machining Option', 'cutlist-catalogue'),
			'search_items' => __('Search Machining Options', 'cutlist-catalogue'),
			'all_items' => __('All Machining Options', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => false,
		'menu_icon' => 'dashicons-hammer',
		'supports' => ['title', 'page-attributes'],
		'has_archive' => false,
		'rewrite' => false,
	]);


	// Register Hinged Doors CPT
	register_post_type('hinged_door', [
		'label' => __('Hinged Doors', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Hinged Doors', 'cutlist-catalogue'),
			'singular_name' => __('Hinged Door', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Door', 'cutlist-catalogue'),
			'edit_item' => __('Edit Door', 'cutlist-catalogue'),
			'all_items' => __('All Hinged Doors', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => false,
		'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="16" y1="12" x2="16" y2="12.01" stroke-width="4"/></svg>'),
		'supports' => ['title', 'thumbnail', 'page-attributes'], // title = door code (SHAKER-1), thumbnail = door photo, page-attributes = order
		'has_archive' => false,
		'rewrite' => false,
	]);

	// =========================================================================
	// SLIDING DOORS DEDICATED CPTS
	// =========================================================================

	// 1. Frame Styles (Shaker, Palermo, Supreme, Prestige, Slim)
	register_post_type('sd_frame_style', [
		'label' => __('Frame Styles', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Frame Styles', 'cutlist-catalogue'),
			'singular_name' => __('Frame Style', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Frame Style', 'cutlist-catalogue'),
			'edit_item' => __('Edit Frame Style', 'cutlist-catalogue'),
			'search_items' => __('Search Frame Styles', 'cutlist-catalogue'),
			'all_items' => __('Frame Styles', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => 'sliding_doors_hub',
		'show_in_rest' => false,
		'supports' => ['title', 'thumbnail', 'page-attributes'],
		'has_archive' => false,
		'rewrite' => false,
	]);

	// 2. Door Designs (Single panel, Two panels, Three panels, Four panels, Five panels)
	register_post_type('sd_door_design', [
		'label' => __('Door Designs', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Door Designs', 'cutlist-catalogue'),
			'singular_name' => __('Door Design', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Door Design', 'cutlist-catalogue'),
			'edit_item' => __('Edit Door Design', 'cutlist-catalogue'),
			'search_items' => __('Search Door Designs', 'cutlist-catalogue'),
			'all_items' => __('Door Designs', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => 'sliding_doors_hub',
		'show_in_rest' => false,
		'supports' => ['title', 'thumbnail', 'page-attributes'],
		'has_archive' => false,
		'rewrite' => false,
	]);

	// 3. Coloured Glass & Mirrors Finishes
	register_post_type('sd_glass_panel', [
		'label' => __('Coloured Glass', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Coloured Glass', 'cutlist-catalogue'),
			'singular_name' => __('Glass Finish', 'cutlist-catalogue'),
			'add_new_item' => __('Add New Glass Finish', 'cutlist-catalogue'),
			'edit_item' => __('Edit Glass Finish', 'cutlist-catalogue'),
			'search_items' => __('Search Glass Finishes', 'cutlist-catalogue'),
			'all_items' => __('Coloured Glass & Mirrors', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => 'sliding_doors_hub',
		'show_in_rest' => false,
		'supports' => ['title', 'thumbnail', 'page-attributes'],
		'has_archive' => false,
		'rewrite' => false,
	]);

}

/**
 * Register top-level Sliding Doors Admin Sidebar Menu Hub and Submenus
 */
add_action('admin_menu', 'cutlist_register_sliding_doors_admin_menu', 20);
function cutlist_register_sliding_doors_admin_menu()
{
	// Top-level "Sliding Doors" parent menu in admin sidebar
	add_menu_page(
		__('Sliding Doors', 'cutlist-catalogue'),
		__('Sliding Doors', 'cutlist-catalogue'),
		'edit_posts',
		'sliding_doors_hub',
		function () {
			wp_safe_redirect(admin_url('edit.php?post_type=sd_frame_style'));
			exit;
		},
		'dashicons-columns',
		28
	);

	// Decorative Boards submenu (pointing directly to existing board CPT)
	add_submenu_page(
		'sliding_doors_hub',
		__('Decorative Boards', 'cutlist-catalogue'),
		__('Decorative Boards', 'cutlist-catalogue'),
		'edit_posts',
		'edit.php?post_type=board'
	);

	// System Settings & Pricing submenu
	add_submenu_page(
		'sliding_doors_hub',
		__('System Settings & Pricing', 'cutlist-catalogue'),
		__('Settings & Pricing', 'cutlist-catalogue'),
		'manage_options',
		'sliding_doors_settings',
		'cutlist_render_sliding_doors_settings_page'
	);
}

// Remove duplicate parent slug submenu item created by add_menu_page
add_action('admin_menu', function () {
	remove_submenu_page('sliding_doors_hub', 'sliding_doors_hub');
}, 999);

function cutlist_register_taxonomies()
{

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

	// Groups the machining dropdown ("Panel shaping", "Hinge holes", ...).
	// A taxonomy rather than a fixed list so admin can add a new heading
	// without a code change. Ordering within a group comes from the
	// option's own menu_order.
	register_taxonomy('machining_group', 'machining_option', [
		'label' => __('Machining Group', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Machining Groups', 'cutlist-catalogue'),
			'singular_name' => __('Machining Group', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => true,
		'hierarchical' => true,
		'show_admin_column' => true,
	]);


	// Register Door Style / Category (e.g. Shaker, Slab, Handleless)
	register_taxonomy('door_category', 'hinged_door', [
		'label' => __('Door Style', 'cutlist-catalogue'),
		'labels' => [
			'name' => __('Door Styles', 'cutlist-catalogue'),
			'singular_name' => __('Door Style', 'cutlist-catalogue'),
		],
		'public' => true,
		'show_in_rest' => true,
		'hierarchical' => true, // Functions like post categories
		'show_admin_column' => true,
	]);

}

// Same idea as the spray finish seeding below: fill the Machining Options
// catalogue with the seven entries that used to be hardcoded in
// templates/cutlist-table.php, so the dropdown doesn't go empty the moment
// it starts reading from the CPT.
//
// The slugs here are load-bearing. Rows store their applied machining as
// {option: "<slug>"} in row.dataset.machiningApplied, so these must match
// the old data-option values exactly or previously saved cutting lists
// stop resolving. post_name is passed explicitly rather than left to
// WordPress to derive from the title ("5mm ⌀ diameter hole" would not
// sanitise to "hole-5mm").
function cutlist_seed_default_machining_options()
{
	if (!post_type_exists('machining_option') || wp_count_posts('machining_option')->publish > 0) {
		return;
	}

	$defaults = [
		[
			'slug' => 'angled-cut',
			'title' => 'Angled cut',
			'group' => 'Panel shaping',
			'behaviour' => 'angled-cut',
			'available' => 1,
		],
		[
			'slug' => 'groove',
			'title' => 'Groove',
			'group' => 'Surface shaping',
			'behaviour' => 'groove',
			'available' => 1,
		],
		[
			// Carried over switched off — it was hardcoded with a "disabled"
			// class, and that state now lives here instead of in markup.
			'slug' => 'j-handle',
			'title' => 'J handle',
			'group' => 'Surface shaping',
			'behaviour' => 'j-handle',
			'available' => 0,
		],
		[
			'slug' => 'blum-screw-on',
			'title' => 'Blum 35mm Screw-On',
			'group' => 'Hinge holes',
			'behaviour' => 'hinge-holes',
			'available' => 1,
		],
		[
			'slug' => 'blum-inserta',
			'title' => 'Blum 35mm INSERTA',
			'group' => 'Hinge holes',
			'behaviour' => 'hinge-holes',
			'available' => 1,
		],
		[
			'slug' => 'hole-5mm',
			'title' => '5mm ⌀ diameter hole',
			'group' => 'Shelf holes',
			'behaviour' => 'shelf-holes',
			'available' => 1,
		],
		[
			'slug' => 'hole-7-5mm',
			'title' => '7.5mm ⌀ diameter hole',
			'group' => 'Shelf holes',
			'behaviour' => 'shelf-holes',
			'available' => 1,
		],
		[
			'slug' => 'grain-matching',
			'title' => 'Grain-matching of panels in clusters',
			'group' => 'Grain matching',
			'behaviour' => 'grain-matching',
			'available' => 1,
			'serviceCode' => 'GRN-MTCH',
			'shortDescription' => 'Grain-matching of panels based on customer requirements – charged per panel',
			'price' => 12.70,
		],
	];

	foreach ($defaults as $i => $d) {
		$post_id = wp_insert_post([
			'post_type' => 'machining_option',
			'post_title' => $d['title'],
			'post_name' => $d['slug'],
			'post_status' => 'publish',
			'menu_order' => $i,
		]);
		if (!$post_id || is_wp_error($post_id)) {
			continue;
		}

		wp_set_object_terms($post_id, $d['group'], 'machining_group');

		update_field('behaviour', $d['behaviour'], $post_id);
		update_field('available', $d['available'], $post_id);
		update_field('price', 0, $post_id);
	}
}

// Runs once: if no Spray Finish posts exist yet (a fresh install, or this
// site right after the CPT was introduced), seed the three finishes that
// used to be hardcoded so the Spray Finishing overlay doesn't suddenly go
// empty. Does nothing once at least one post exists — from then on the
// list is entirely wp-admin's to manage.
function cutlist_seed_default_spray_finishes()
{
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
			'paint_brands' => "Farrow & Ball\nDulux\nLittle Greene\nRAL Classic",
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
		update_field('paint_brands', $d['paint_brands'] ?? '', $post_id);
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
function cutlist_format_price_html($price)
{
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

function cutlist_board_section_heading_css()
{
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

add_action('acf/init', function () {
	if (function_exists('acf_add_local_field_group')) {
		$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '£';
		acf_add_local_field_group(array(
			'key' => 'group_machining_option_fields',
			'title' => 'Machining Option Details',
			'fields' => array(
				array(
					'key' => 'field_machining_option_behaviour',
					'label' => 'Behaviour',
					'name' => 'behaviour',
					'type' => 'select',
					'instructions' => 'Which set of settings and on-screen drawing this option uses.',
					'choices' => array(
						'angled-cut' => 'Angled cut (corner chamfer)',
						'groove' => 'Groove (slot)',
						'hinge-holes' => 'Hinge holes (Blum etc.)',
						'shelf-holes' => 'Shelf holes (pin rows)',
						'j-handle' => 'J handle (edge recess)',
						'simple' => 'No settings (label only)',
					),
					'default_value' => 'simple',
				),
				array(
					'key' => 'field_machining_option_available',
					'label' => 'Available',
					'name' => 'available',
					'type' => 'true_false',
					'default_value' => 1,
					'ui' => 1,
				),
				array(
					'key' => 'field_machining_option_service_code',
					'label' => 'Service code',
					'name' => 'service_code',
					'type' => 'text',
					'placeholder' => 'ANG-CUT',
				),
				array(
					'key' => 'field_machining_option_short_description',
					'label' => 'Short description',
					'name' => 'short_description',
					'type' => 'text',
				),
				array(
					'key' => 'field_machining_option_price',
					'label' => 'Price',
					'name' => 'price',
					'type' => 'number',
					'step' => '0.01',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_machining_option_edging_price',
					'label' => 'Edging Price (Optional)',
					'name' => 'edging_price',
					'type' => 'number',
					'step' => '0.01',
					'default_value' => 28.87,
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_machining_option_edging_service_code',
					'label' => 'Edging Service Code (Optional)',
					'name' => 'edging_service_code',
					'type' => 'text',
					'default_value' => 'MC-ANG-EDGE',
					'placeholder' => 'MC-ANG-EDGE',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'machining_option',
					),
				),
			),
		));

		acf_add_local_field_group(array(
			'key' => 'group_board_cutting_list_price_patch',
			'title' => 'Cutting List Price Patch',
			'fields' => array(
				array(
					'key' => 'field_board_cutting_list_price',
					'label' => 'Cutting list price',
					'name' => 'cutting_list_price',
					'type' => 'number',
					'step' => '0.01',
					'instructions' => 'Price per sheet used for cut-to-size cutting list calculations.',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_board_additional_cut_price',
					'label' => 'Additional cut price (after 20 cuts per sheet)',
					'name' => 'additional_cut_price',
					'type' => 'number',
					'step' => '0.01',
					'default_value' => '3.46',
					'instructions' => 'Additional charge per single cut when the number of panels included in a single sheet cutting charge (20 cuts per sheet) is exceeded.',
					'prepend' => $currency_sym,
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'board',
					),
				),
			),
			'menu_order' => 10,
		));

		acf_add_local_field_group(array(
			'key' => 'group_hinged_door_fields',
			'title' => 'Hinged Door Details',
			'fields' => array(
				// TAB 1: GENERAL / PRODUCT INFORMATION
				array(
					'key' => 'field_hd_tab_general',
					'label' => 'General / Product Information',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				),
				array(
					'key' => 'field_hinged_door_subtitle',
					'label' => 'Subtitle',
					'name' => 'subtitle',
					'type' => 'text',
					'instructions' => 'e.g. Shaker with one panel',
					'wrapper' => array('width' => '50'),
					'placeholder' => 'e.g. Shaker with one panel',
				),
				array(
					'key' => 'field_hinged_door_price_from',
					'label' => 'Catalogue "From Price" (£)',
					'name' => 'price_from',
					'type' => 'number',
					'step' => '0.01',
					'instructions' => 'Display starting price on catalog card (e.g. 41.50)',
					'wrapper' => array('width' => '50'),
					'placeholder' => '41.50',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_description',
					'label' => 'Product Description',
					'name' => 'description',
					'type' => 'textarea',
					'instructions' => 'Full description displayed on card and in configurator popup.',
					'rows' => 3,
					'default_value' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
					'placeholder' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
				),
				array(
					'key' => 'field_hinged_door_panels_count',
					'label' => 'Panels Count',
					'name' => 'panels_count',
					'type' => 'number',
					'instructions' => 'Number of panels (1, 2, 3, 4, etc.)',
					'wrapper' => array('width' => '33.33'),
					'step' => '1',
					'min' => 1,
					'max' => 10,
					'default_value' => 1,
				),
				array(
					'key' => 'field_hinged_door_feature_1',
					'label' => 'Card Feature 1 (with Stack icon)',
					'name' => 'feature_1',
					'type' => 'text',
					'instructions' => 'First bullet point on product card.',
					'wrapper' => array('width' => '33.33'),
					'default_value' => 'Mortise and tenon structure',
					'placeholder' => 'Mortise and tenon structure',
				),
				array(
					'key' => 'field_hinged_door_feature_2',
					'label' => 'Card Feature 2 (with Leaf icon)',
					'name' => 'feature_2',
					'type' => 'text',
					'instructions' => 'Second bullet point on product card.',
					'wrapper' => array('width' => '33.33'),
					'default_value' => 'Built using moisture resistant MDF.',
					'placeholder' => 'Built using moisture resistant MDF.',
				),
				array(
					'key' => 'field_hinged_door_spec_material',
					'label' => 'Material Spec',
					'name' => 'spec_material',
					'type' => 'text',
					'instructions' => 'e.g. Moisture-resistant MDF',
					'wrapper' => array('width' => '50'),
					'default_value' => 'Moisture-resistant MDF',
					'placeholder' => 'Moisture-resistant MDF',
				),
				array(
					'key' => 'field_hinged_door_spec_build',
					'label' => 'Build Spec',
					'name' => 'spec_build',
					'type' => 'text',
					'instructions' => 'e.g. Mortise and tenon',
					'wrapper' => array('width' => '50'),
					'default_value' => 'Mortise and tenon',
					'placeholder' => 'Mortise and tenon',
				),
				array(
					'key' => 'field_hinged_door_spec_overall_thickness',
					'label' => 'Overall Thickness Spec',
					'name' => 'spec_overall_thickness',
					'type' => 'text',
					'instructions' => 'e.g. 21mm',
					'wrapper' => array('width' => '33.33'),
					'default_value' => '21mm',
					'placeholder' => '21mm',
				),
				array(
					'key' => 'field_hinged_door_spec_panel_thickness',
					'label' => 'Panel Thickness Spec',
					'name' => 'spec_panel_thickness',
					'type' => 'text',
					'instructions' => 'e.g. 9mm',
					'wrapper' => array('width' => '33.33'),
					'default_value' => '9mm',
					'placeholder' => '9mm',
				),
				array(
					'key' => 'field_hinged_door_spec_finish',
					'label' => 'Finish Options Spec',
					'name' => 'spec_finish',
					'type' => 'text',
					'instructions' => 'e.g. can be supplied raw, primed or painted',
					'wrapper' => array('width' => '33.33'),
					'default_value' => 'can be supplied raw, primed or painted',
					'placeholder' => 'can be supplied raw, primed or painted',
				),
				array(
					'key' => 'field_hinged_door_spec_disclaimer',
					'label' => 'Disclaimer / Note',
					'name' => 'spec_disclaimer',
					'type' => 'textarea',
					'instructions' => 'Notice text below details specs.',
					'rows' => 2,
					'default_value' => 'No fixings are supplied; they are to be purchased separately.',
					'placeholder' => 'No fixings are supplied; they are to be purchased separately.',
				),
				array(
					'key' => 'field_hinged_door_price_per_hinge',
					'label' => 'Hinge Hole Drilling Price Per Hole (£)',
					'name' => 'price_per_hinge_hole',
					'type' => 'number',
					'instructions' => 'Drilling charge per 35mm hinge hole.',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 3.00,
					'placeholder' => '3.00',
					'prepend' => $currency_sym,
				),

				// TAB 2: DOOR (-D)
				array(
					'key' => 'field_hd_tab_door',
					'label' => 'Door (-D)',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				),
				array(
					'key' => 'field_hd_door_acc_settings',
					'label' => 'PRODUCT SETTINGS',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_price_door',
					'label' => 'Door Starting Price (£)',
					'name' => 'price_door',
					'type' => 'number',
					'step' => '0.01',
					'instructions' => 'Starting base price for Door (e.g. 41.50)',
					'wrapper' => array('width' => '20'),
					'default_value' => 41.50,
					'placeholder' => '41.50',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_door_min_height',
					'label' => 'Door Min Height (mm)',
					'name' => 'door_min_height',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 250,
					'placeholder' => '250',
				),
				array(
					'key' => 'field_hinged_door_door_max_height',
					'label' => 'Door Max Height (mm)',
					'name' => 'door_max_height',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 2800,
					'placeholder' => '2800',
				),
				array(
					'key' => 'field_hinged_door_door_min_width',
					'label' => 'Door Min Width (mm)',
					'name' => 'door_min_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 200,
					'placeholder' => '200',
				),
				array(
					'key' => 'field_hinged_door_door_max_width',
					'label' => 'Door Max Width (mm)',
					'name' => 'door_max_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 1200,
					'placeholder' => '1200',
				),
				array(
					'key' => 'field_hd_door_acc_drilling',
					'label' => 'DRILLING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_door_allow_drilling',
					'label' => 'Door Hinge Drilling Available',
					'name' => 'door_allow_drilling',
					'type' => 'true_false',
					'instructions' => 'Allow hinge hole drilling for Doors',
					'wrapper' => array('width' => '50'),
					'default_value' => 1,
					'ui' => 1,
				),
				array(
					'key' => 'field_hd_door_drilling_holes',
					'label' => 'Door Allowed Hinge Hole Counts',
					'name' => 'door_drilling_holes',
					'type' => 'checkbox',
					'instructions' => 'Select which hole count options to offer for Doors',
					'wrapper' => array('width' => '50'),
					'choices' => array(
						'2' => '2 holes',
						'3' => '3 holes',
						'4' => '4 holes',
						'5' => '5 holes',
					),
					'default_value' => array('2', '3', '4', '5'),
					'layout' => 'horizontal',
				),
				array(
					'key' => 'field_hd_door_acc_finish',
					'label' => 'FINISH PRICING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_door_price_primer',
					'label' => 'Door White Primer Extra (£)',
					'name' => 'door_price_primer',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 18.50,
					'placeholder' => '18.50',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_door_price_painted',
					'label' => 'Door Fully Painted Extra (£)',
					'name' => 'door_price_painted',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 38.00,
					'placeholder' => '38.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hd_door_acc_gallery',
					'label' => 'IMAGES & GALLERY',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hd_door_quick_view_image',
					'label' => 'Door Quick View / High-Res Image',
					'name' => 'door_quick_view_image',
					'type' => 'image',
					'instructions' => 'Dedicated high-res zoom image for Door. If empty, the post Featured Image is used.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_door_gallery_1',
					'label' => 'Door Gallery Image 1',
					'name' => 'door_gallery_1',
					'type' => 'image',
					'instructions' => 'First gallery thumbnail for Door.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_door_gallery_2',
					'label' => 'Door Gallery Image 2',
					'name' => 'door_gallery_2',
					'type' => 'image',
					'instructions' => 'Second gallery thumbnail for Door.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_door_gallery_3',
					'label' => 'Door Gallery Image 3',
					'name' => 'door_gallery_3',
					'type' => 'image',
					'instructions' => 'Third gallery thumbnail for Door.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),

				// TAB 3: END PANEL (-EP)
				array(
					'key' => 'field_hd_tab_ep',
					'label' => 'End Panel (-EP)',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				),
				array(
					'key' => 'field_hd_ep_acc_settings',
					'label' => 'PRODUCT SETTINGS',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_price_end_panel',
					'label' => 'End Panel Starting Price (£)',
					'name' => 'price_end_panel',
					'type' => 'number',
					'step' => '0.01',
					'instructions' => 'Starting base price for End Panel (e.g. 48.00)',
					'wrapper' => array('width' => '20'),
					'default_value' => 48.00,
					'placeholder' => '48.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_ep_min_height',
					'label' => 'End Panel Min Height (mm)',
					'name' => 'ep_min_height',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 300,
					'placeholder' => '300',
				),
				array(
					'key' => 'field_hinged_door_ep_max_height',
					'label' => 'End Panel Max Height (mm)',
					'name' => 'ep_max_height',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 3000,
					'placeholder' => '3000',
				),
				array(
					'key' => 'field_hinged_door_ep_min_width',
					'label' => 'End Panel Min Width (mm)',
					'name' => 'ep_min_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 200,
					'placeholder' => '200',
				),
				array(
					'key' => 'field_hinged_door_ep_max_width',
					'label' => 'End Panel Max Width (mm)',
					'name' => 'ep_max_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 1500,
					'placeholder' => '1500',
				),
				array(
					'key' => 'field_hd_ep_acc_drilling',
					'label' => 'DRILLING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_ep_allow_drilling',
					'label' => 'End Panel Hinge Drilling Available',
					'name' => 'ep_allow_drilling',
					'type' => 'true_false',
					'instructions' => 'Enable only if hinge drilling should be allowed for End panels (Default: No)',
					'wrapper' => array('width' => '50'),
					'default_value' => 0,
					'ui' => 1,
				),
				array(
					'key' => 'field_hd_ep_drilling_holes',
					'label' => 'End Panel Allowed Hinge Hole Counts',
					'name' => 'ep_drilling_holes',
					'type' => 'checkbox',
					'instructions' => 'Select which hole count options to offer for End Panels if drilling enabled',
					'wrapper' => array('width' => '50'),
					'choices' => array(
						'2' => '2 holes',
						'3' => '3 holes',
						'4' => '4 holes',
						'5' => '5 holes',
					),
					'default_value' => array('2', '3', '4', '5'),
					'layout' => 'horizontal',
				),
				array(
					'key' => 'field_hd_ep_acc_finish',
					'label' => 'FINISH PRICING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_ep_price_primer',
					'label' => 'End Panel White Primer Extra (£)',
					'name' => 'ep_price_primer',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 22.50,
					'placeholder' => '22.50',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_ep_price_painted',
					'label' => 'End Panel Fully Painted Extra (£)',
					'name' => 'ep_price_painted',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 45.00,
					'placeholder' => '45.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hd_ep_acc_gallery',
					'label' => 'IMAGES & GALLERY',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hd_ep_quick_view_image',
					'label' => 'End Panel Quick View / High-Res Image',
					'name' => 'ep_quick_view_image',
					'type' => 'image',
					'instructions' => 'Dedicated high-res zoom image for End Panel. If empty, the post Featured Image is used.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_ep_gallery_1',
					'label' => 'End Panel Gallery Image 1',
					'name' => 'ep_gallery_1',
					'type' => 'image',
					'instructions' => 'First gallery thumbnail for End Panel.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_ep_gallery_2',
					'label' => 'End Panel Gallery Image 2',
					'name' => 'ep_gallery_2',
					'type' => 'image',
					'instructions' => 'Second gallery thumbnail for End Panel.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_ep_gallery_3',
					'label' => 'End Panel Gallery Image 3',
					'name' => 'ep_gallery_3',
					'type' => 'image',
					'instructions' => 'Third gallery thumbnail for End Panel.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),

				// TAB 4: DRAWER FRONT (-DF)
				array(
					'key' => 'field_hd_tab_df',
					'label' => 'Drawer Front (-DF)',
					'name' => '',
					'type' => 'tab',
					'placement' => 'top',
				),
				array(
					'key' => 'field_hd_df_acc_settings',
					'label' => 'PRODUCT SETTINGS',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_price_drawer_front',
					'label' => 'Drawer Front Starting Price (£)',
					'name' => 'price_drawer_front',
					'type' => 'number',
					'step' => '0.01',
					'instructions' => 'Starting base price for Drawer Front (e.g. 28.00)',
					'wrapper' => array('width' => '20'),
					'default_value' => 28.00,
					'placeholder' => '28.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_df_min_height',
					'label' => 'Drawer Front Min Height (mm)',
					'name' => 'df_min_height',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 100,
					'placeholder' => '100',
				),
				array(
					'key' => 'field_hinged_door_df_max_height',
					'label' => 'Drawer Front Max Height (mm)',
					'name' => 'df_max_height',
					'type' => 'number',
					'instructions' => 'Max height constraint for drawer front',
					'wrapper' => array('width' => '20'),
					'default_value' => 450,
					'placeholder' => '450',
				),
				array(
					'key' => 'field_hinged_door_df_min_width',
					'label' => 'Drawer Front Min Width (mm)',
					'name' => 'df_min_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 150,
					'placeholder' => '150',
				),
				array(
					'key' => 'field_hinged_door_df_max_width',
					'label' => 'Drawer Front Max Width (mm)',
					'name' => 'df_max_width',
					'type' => 'number',
					'wrapper' => array('width' => '20'),
					'default_value' => 1200,
					'placeholder' => '1200',
				),
				array(
					'key' => 'field_hd_df_acc_drilling',
					'label' => 'DRILLING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_df_allow_drilling',
					'label' => 'Drawer Front Hinge Drilling Available',
					'name' => 'df_allow_drilling',
					'type' => 'true_false',
					'instructions' => 'Enable only if hinge drilling should be allowed for Drawer fronts (Default: No)',
					'wrapper' => array('width' => '50'),
					'default_value' => 0,
					'ui' => 1,
				),
				array(
					'key' => 'field_hd_df_drilling_holes',
					'label' => 'Drawer Front Allowed Hinge Hole Counts',
					'name' => 'df_drilling_holes',
					'type' => 'checkbox',
					'instructions' => 'Select which hole count options to offer for Drawer Fronts if drilling enabled',
					'wrapper' => array('width' => '50'),
					'choices' => array(
						'2' => '2 holes',
						'3' => '3 holes',
						'4' => '4 holes',
						'5' => '5 holes',
					),
					'default_value' => array('2', '3', '4', '5'),
					'layout' => 'horizontal',
				),
				array(
					'key' => 'field_hd_df_acc_finish',
					'label' => 'FINISH PRICING',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hinged_door_df_price_primer',
					'label' => 'Drawer Front White Primer Extra (£)',
					'name' => 'df_price_primer',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 12.00,
					'placeholder' => '12.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hinged_door_df_price_painted',
					'label' => 'Drawer Front Fully Painted Extra (£)',
					'name' => 'df_price_painted',
					'type' => 'number',
					'wrapper' => array('width' => '50'),
					'step' => '0.01',
					'default_value' => 25.00,
					'placeholder' => '25.00',
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_hd_df_acc_gallery',
					'label' => 'IMAGES & GALLERY',
					'name' => '',
					'type' => 'accordion',
					'open' => 1,
					'multi_expand' => 1,
				),
				array(
					'key' => 'field_hd_df_quick_view_image',
					'label' => 'Drawer Front Quick View / High-Res Image',
					'name' => 'df_quick_view_image',
					'type' => 'image',
					'instructions' => 'Dedicated high-res zoom image for Drawer Front. If empty, the post Featured Image is used.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_df_gallery_1',
					'label' => 'Drawer Front Gallery Image 1',
					'name' => 'df_gallery_1',
					'type' => 'image',
					'instructions' => 'First gallery thumbnail for Drawer Front.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_df_gallery_2',
					'label' => 'Drawer Front Gallery Image 2',
					'name' => 'df_gallery_2',
					'type' => 'image',
					'instructions' => 'Second gallery thumbnail for Drawer Front.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
				array(
					'key' => 'field_hd_df_gallery_3',
					'label' => 'Drawer Front Gallery Image 3',
					'name' => 'df_gallery_3',
					'type' => 'image',
					'instructions' => 'Third gallery thumbnail for Drawer Front.',
					'wrapper' => array('width' => '25'),
					'return_format' => 'array',
					'preview_size' => 'medium',
					'library' => 'all',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'hinged_door',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => 'Structured independent variations for Hinged Doors (Door, End Panel, Drawer Front)',
			'show_in_rest' => 1,
		));

		acf_add_local_field_group(array(
			'key' => 'group_door_category_fields',
			'title' => 'Door Style Category Settings',
			'fields' => array(
				array(
					'key' => 'field_door_category_subtitle',
					'label' => 'Category Subtitle',
					'name' => 'category_subtitle',
					'type' => 'text',
					'instructions' => 'Description text below the category header title.',
					'default_value' => 'Choose a shaker style for your door. All options are made using moisture resistant MDF with a mortise and tenon structure.',
					'placeholder' => 'Choose a shaker style for your door. All options are made using moisture resistant MDF with a mortise and tenon structure.',
				),
				array(
					'key' => 'field_door_category_notice',
					'label' => 'Bottom Info Notice Banner',
					'name' => 'category_notice',
					'type' => 'textarea',
					'instructions' => 'Notice text in the info alert box below the category doors grid.',
					'rows' => 2,
					'default_value' => 'All shaker doors are made using moisture resistant MDF with a mortise and tenon structure.',
					'placeholder' => 'All shaker doors are made using moisture resistant MDF with a mortise and tenon structure.',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'taxonomy',
						'operator' => '==',
						'value' => 'door_category',
					),
				),
			),
			'menu_order' => 0,
		));

		// =====================================================================
		// 1. FRAME STYLES ACF FIELD GROUP
		// =====================================================================
		acf_add_local_field_group(array(
			'key' => 'group_sd_frame_style_fields',
			'title' => 'Frame Style Details',
			'fields' => array(
				array(
					'key' => 'field_sd_frame_code',
					'label' => 'Style Code / Slug',
					'name' => 'frame_code',
					'type' => 'text',
					'required' => 1,
					'wrapper' => array('width' => '50'),
					'placeholder' => 'e.g. shaker',
				),
				array(
					'key' => 'field_sd_frame_description',
					'label' => 'Profile Description / Specs',
					'name' => 'description',
					'type' => 'textarea',
					'rows' => 2,
					'wrapper' => array('width' => '50'),
					'placeholder' => 'Clean contemporary aluminium frame profile with built-in ergonomic finger pull.',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'sd_frame_style',
					),
				),
			),
			'menu_order' => 0,
		));

		// =====================================================================
		// 2. DOOR DESIGNS ACF FIELD GROUP
		// =====================================================================
		acf_add_local_field_group(array(
			'key' => 'group_sd_door_design_fields',
			'title' => 'Door Design / Panel Split Configuration',
			'fields' => array(
				array(
					'key' => 'field_sd_design_panels_count',
					'label' => 'Panels Count',
					'name' => 'panels_count',
					'type' => 'number',
					'min' => 1,
					'max' => 8,
					'default_value' => 2,
					'wrapper' => array('width' => '33.33'),
				),
				array(
					'key' => 'field_sd_design_orientation',
					'label' => 'Allowed Orientations',
					'name' => 'orientation',
					'type' => 'select',
					'choices' => array(
						'horizontal' => 'Horizontal Only',
						'vertical' => 'Vertical Only',
						'both' => 'Horizontal or Vertical (User Selectable)',
					),
					'default_value' => 'horizontal',
					'wrapper' => array('width' => '33.33'),
				),
				array(
					'key' => 'field_sd_design_h_bar_price',
					'label' => 'H-bar Divider Price per Split (£)',
					'name' => 'h_bar_price',
					'type' => 'number',
					'step' => '0.01',
					'default_value' => 16.00,
					'wrapper' => array('width' => '33.33'),
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_sd_design_subtitle',
					'label' => 'Subtitle / Badge Text',
					'name' => 'subtitle',
					'type' => 'text',
					'wrapper' => array('width' => '50'),
					'placeholder' => 'e.g. Horizontal or Vertical',
				),
				array(
					'key' => 'field_sd_design_icon',
					'label' => 'Custom Icon / Diagram (Optional)',
					'name' => 'design_icon',
					'type' => 'image',
					'return_format' => 'url',
					'wrapper' => array('width' => '50'),
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'sd_door_design',
					),
				),
			),
			'menu_order' => 0,
		));

		// =====================================================================
		// 3. COLOURED GLASS & MIRRORS ACF FIELD GROUP
		// =====================================================================
		acf_add_local_field_group(array(
			'key' => 'group_sd_glass_panel_fields',
			'title' => 'Glass & Mirror Finish Details',
			'fields' => array(
				array(
					'key' => 'field_sd_glass_category',
					'label' => 'Category',
					'name' => 'glass_category',
					'type' => 'select',
					'choices' => array(
						'mirrors' => 'Mirrors',
						'colours' => 'Colours',
						'premium_colours' => 'Premium colours',
					),
					'default_value' => 'mirrors',
					'wrapper' => array('width' => '33.33'),
				),
				array(
					'key' => 'field_sd_glass_price_sqm',
					'label' => 'Price per m² (£/m²)',
					'name' => 'price_per_sqm',
					'type' => 'number',
					'step' => '0.01',
					'default_value' => 30.00,
					'wrapper' => array('width' => '33.33'),
					'prepend' => $currency_sym,
				),
				array(
					'key' => 'field_sd_glass_swatch_color',
					'label' => 'Swatch Hex / Fallback Colour',
					'name' => 'swatch_color',
					'type' => 'text',
					'default_value' => '#e2e8f0',
					'wrapper' => array('width' => '33.33'),
				),
				array(
					'key' => 'field_sd_glass_thickness',
					'label' => 'Thickness Spec',
					'name' => 'thickness_spec',
					'type' => 'text',
					'default_value' => '4mm safety backed',
					'wrapper' => array('width' => '50'),
				),
				array(
					'key' => 'field_sd_glass_image',
					'label' => 'Full Preview / Texture Image',
					'name' => 'glass_image',
					'type' => 'image',
					'return_format' => 'url',
					'wrapper' => array('width' => '50'),
				),
				array(
					'key' => 'field_sd_glass_description',
					'label' => 'Specification / Safety Info',
					'name' => 'description',
					'type' => 'textarea',
					'rows' => 2,
					'default_value' => 'All our glass is 4mm backed with safety film meeting BS EN 12600 2B2 safety class. The biggest available single glass panel size is 2440mm in height.',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'sd_glass_panel',
					),
				),
			),
			'menu_order' => 0,
		));
	}
});

// =========================================================================
// POST DUPLICATION FOR CATALOGUE POST TYPES
// =========================================================================

add_filter('post_row_actions', 'cutlist_duplicate_post_row_action', 10, 2);
add_filter('page_row_actions', 'cutlist_duplicate_post_row_action', 10, 2);

function cutlist_duplicate_post_row_action($actions, $post)
{
	$allowed = ['board', 'edge_tape', 'spray_finish', 'machining_option', 'hinged_door', 'sd_frame_style', 'sd_door_design', 'sd_glass_panel'];
	if (!$post || !in_array($post->post_type, $allowed, true)) {
		return $actions;
	}
	if (!current_user_can('edit_post', $post->ID)) {
		return $actions;
	}

	$url = wp_nonce_url(
		admin_url('admin.php?action=cutlist_duplicate_post&post=' . $post->ID),
		'cutlist_duplicate_post_' . $post->ID
	);

	$actions['duplicate'] = '<a href="' . esc_url($url) . '" title="' . esc_attr__('Duplicate this item', 'cutlist-catalogue') . '" rel="permalink">' . esc_html__('Duplicate', 'cutlist-catalogue') . '</a>';

	return $actions;
}

add_action('admin_action_cutlist_duplicate_post', 'cutlist_handle_duplicate_post');

function cutlist_handle_duplicate_post()
{
	$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
	if (!$post_id) {
		wp_die(__('No post to duplicate has been supplied.', 'cutlist-catalogue'));
	}

	check_admin_referer('cutlist_duplicate_post_' . $post_id);

	$post = get_post($post_id);
	if (!$post || !current_user_can('edit_post', $post_id)) {
		wp_die(__('You do not have permission to duplicate this item.', 'cutlist-catalogue'));
	}

	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;

	$args = [
		'post_author' => $new_post_author,
		'post_content' => $post->post_content,
		'post_title' => $post->post_title . ' (Copy)',
		'post_excerpt' => $post->post_excerpt,
		'post_status' => 'draft',
		'post_type' => $post->post_type,
		'post_parent' => $post->post_parent,
		'post_password' => $post->post_password,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'menu_order' => $post->menu_order,
	];

	$new_post_id = wp_insert_post($args);

	if (is_wp_error($new_post_id) || !$new_post_id) {
		wp_die(__('Failed to duplicate post.', 'cutlist-catalogue'));
	}

	// Duplicate all custom post meta (ACF fields, gallery meta, etc.)
	$meta_keys = get_post_custom_keys($post_id);
	if (!empty($meta_keys)) {
		foreach ($meta_keys as $meta_key) {
			$meta_values = get_post_custom_values($meta_key, $post_id);
			foreach ($meta_values as $meta_value) {
				$meta_value = maybe_unserialize($meta_value);
				add_post_meta($new_post_id, $meta_key, $meta_value);
			}
		}
	}

	// Duplicate taxonomies (board_brand, board_collection, etc.)
	$taxonomies = get_object_taxonomies($post->post_type);
	if (!empty($taxonomies)) {
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
		}
	}

	// Duplicate featured image
	$thumbnail_id = get_post_thumbnail_id($post_id);
	if ($thumbnail_id) {
		set_post_thumbnail($new_post_id, $thumbnail_id);
	}

	// Redirect to the duplicated post edit screen
	wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
	exit;
}

add_action('post_submitbox_start', function () {
	global $post;
	if (!$post || !in_array($post->post_type, ['board', 'edge_tape', 'spray_finish', 'machining_option', 'hinged_door', 'sliding_door'], true)) {
		return;
	}
	if ($post->post_status === 'auto-draft') {
		return;
	}
	$url = wp_nonce_url(
		admin_url('admin.php?action=cutlist_duplicate_post&post=' . $post->ID),
		'cutlist_duplicate_post_' . $post->ID
	);
	echo '<div id="cutlist-duplicate-action" style="padding: 10px 0 6px; border-bottom: 1px solid #f0f0f1; margin-bottom: 6px;">' .
		'<a class="button button-secondary" href="' . esc_url($url) . '" style="width:100%; text-align:center;">' .
		esc_html__('Duplicate to new draft', 'cutlist-catalogue') .
		'</a></div>';
});

function cutlist_seed_dummy_egger_boards()
{
	if (get_option('cutlist_dummy_egger_seeded_v1')) {
		return;
	}

	$dummy_boards = [
		['code' => 'H1180-ST37', 'name' => 'Natural Halifax Oak', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 168.50, 'cut_price' => 215.00],
		['code' => 'H3303-ST10', 'name' => 'Natural Hamilton Oak', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 164.00, 'cut_price' => 212.00],
		['code' => 'H3730-ST10', 'name' => 'Natural Hickory', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 170.00, 'cut_price' => 218.00],
		['code' => 'W980-SM', 'name' => 'Platinum White', 'core' => 'MDF Board', 'thickness' => 18, 'sheet_price' => 135.00, 'cut_price' => 180.00],
		['code' => 'U708-ST9', 'name' => 'Light Grey', 'core' => 'Chipboard', 'thickness' => 18, 'sheet_price' => 142.00, 'cut_price' => 188.00],
		['code' => 'U999-ST89', 'name' => 'Black Mineral', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 155.00, 'cut_price' => 198.00],
		['code' => 'F186-ST9', 'name' => 'Light Grey Chicago Concrete', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 175.00, 'cut_price' => 222.00],
		['code' => 'H3131-ST12', 'name' => 'Natural Davos Oak', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 166.00, 'cut_price' => 214.00],
		['code' => 'U732-ST9', 'name' => 'Dust Grey', 'core' => 'MDF Board', 'thickness' => 18, 'sheet_price' => 145.00, 'cut_price' => 190.00],
		['code' => 'H1145-ST10', 'name' => 'Natural Bardolino Oak', 'core' => 'Chipboard', 'thickness' => 19, 'sheet_price' => 162.00, 'cut_price' => 210.00],
	];

	foreach ($dummy_boards as $d) {
		$post_id = wp_insert_post([
			'post_type' => 'board',
			'post_title' => $d['code'] . ' ' . $d['name'],
			'post_status' => 'publish',
		]);

		if ($post_id && !is_wp_error($post_id)) {
			// Brand taxonomy
			wp_set_object_terms($post_id, 'Egger', 'board_brand', false);

			// ACF fields
			update_field('decor_code', $d['code'], $post_id);
			update_field('decor_name', $d['name'], $post_id);
			update_field('core', $d['core'], $post_id);
			update_field('length_mm', 2800, $post_id);
			update_field('width_mm', 2070, $post_id);
			update_field('thickness_mm', $d['thickness'], $post_id);
			update_field('full_sheet_price', $d['sheet_price'], $post_id);
			update_field('cutting_list_price', $d['cut_price'], $post_id);
			update_field('additional_cut_price', 3.46, $post_id);
			update_field('machining_cut_to_size', true, $post_id);
			update_field('machining_edgebanding', true, $post_id);
			update_field('machining_cnc', true, $post_id);
			update_field('spray_finishing', false, $post_id);
			update_field('grain_match', true, $post_id);
		}
	}

	update_option('cutlist_dummy_egger_seeded_v1', 1);
}

function cutlist_cleanup_duplicate_boards()
{
	if (get_option('cutlist_duplicate_boards_cleaned_v2')) {
		return;
	}

	$boards = get_posts([
		'post_type' => 'board',
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby' => 'ID',
		'order' => 'ASC',
	]);

	$seen = [];
	foreach ($boards as $b) {
		$brand_terms = get_the_terms($b->ID, 'board_brand');
		$brand_slug = ($brand_terms && !is_wp_error($brand_terms)) ? $brand_terms[0]->slug : '';
		$code = trim((string) get_post_meta($b->ID, 'decor_code', true));
		if (!$code) {
			$code = trim((string) $b->post_title);
		}
		$key = $brand_slug . '|' . strtolower($code);

		if (isset($seen[$key])) {
			$orig_id = $seen[$key];
			$gallery_meta_key = defined('CUTLIST_BOARD_GALLERY_META') ? CUTLIST_BOARD_GALLERY_META : '_board_gallery_ids';
			$orig_has_img = has_post_thumbnail($orig_id) || get_post_meta($orig_id, $gallery_meta_key, true);
			$curr_has_img = has_post_thumbnail($b->ID) || get_post_meta($b->ID, $gallery_meta_key, true);

			if ($curr_has_img && !$orig_has_img) {
				wp_delete_post($orig_id, true);
				$seen[$key] = $b->ID;
			} else {
				wp_delete_post($b->ID, true);
			}
		} else {
			$seen[$key] = $b->ID;
		}
	}

	update_option('cutlist_duplicate_boards_cleaned_v2', 1);
}

/**
 * Attaches an image file from the plugin directory to a post as a Featured Image thumbnail or Media attachment.
 */
function cutlist_attach_plugin_image($image_path, $post_id = 0, $title = '')
{
	if (!file_exists($image_path)) {
		return 0;
	}

	$filename = basename($image_path);
	$upload_dir = wp_upload_dir();

	// Check if this file was already imported to avoid duplicate media attachments
	$existing = get_posts([
		'post_type' => 'attachment',
		'meta_key' => '_cutlist_source_file',
		'meta_value' => $filename,
		'posts_per_page' => 1,
		'fields' => 'ids',
		'post_status' => 'inherit',
	]);

	if (!empty($existing)) {
		if ($post_id > 0 && !has_post_thumbnail($post_id)) {
			set_post_thumbnail($post_id, $existing[0]);
		}
		return (int) $existing[0];
	}

	$target_file = $upload_dir['path'] . '/' . $filename;
	if (!copy($image_path, $target_file)) {
		return 0;
	}

	$filetype = wp_check_filetype($filename, null);
	$attachment = [
		'post_mime_type' => $filetype['type'],
		'post_title' => sanitize_file_name($title ? $title : $filename),
		'post_content' => '',
		'post_status' => 'inherit',
	];

	require_once(ABSPATH . 'wp-admin/includes/image.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/media.php');

	$attach_id = wp_insert_attachment($attachment, $target_file, $post_id);
	if (!is_wp_error($attach_id) && $attach_id) {
		$attach_data = wp_generate_attachment_metadata($attach_id, $target_file);
		wp_update_attachment_metadata($attach_id, $attach_data);
		update_post_meta($attach_id, '_cutlist_source_file', $filename);
		if ($post_id > 0) {
			set_post_thumbnail($post_id, $attach_id);
		}
		return (int) $attach_id;
	}

	return 0;
}

/**
 * Automatically seeds the 4 default Shaker doors (SHAKER-1 to SHAKER-4) into WordPress.
 */
function cutlist_seed_default_hinged_doors()
{
	if (!post_type_exists('hinged_door')) {
		return;
	}

	if (get_option('cutlist_hinged_doors_seeded_v1')) {
		return;
	}

	// Ensure 'Shaker' taxonomy term exists
	if (!term_exists('Shaker', 'door_category')) {
		wp_insert_term('Shaker', 'door_category', ['slug' => 'shaker']);
	}

	$defaults = [
		[
			'code' => 'SHAKER-1',
			'subtitle' => 'Shaker with one panel',
			'description' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
			'price_from' => 41.50,
			'panels_count' => 1,
			'menu_order' => 1,
			'image' => CUTLIST_CATALOGUE_PATH . 'assets/images/shaker-1.jpeg',
		],
		[
			'code' => 'SHAKER-2',
			'subtitle' => 'Shaker with two panels',
			'description' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
			'price_from' => 65.00,
			'panels_count' => 2,
			'menu_order' => 2,
			'image' => CUTLIST_CATALOGUE_PATH . 'assets/images/shaker-2.jpeg',
		],
		[
			'code' => 'SHAKER-3',
			'subtitle' => 'Shaker with three panels',
			'description' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
			'price_from' => 85.00,
			'panels_count' => 3,
			'menu_order' => 3,
			'image' => CUTLIST_CATALOGUE_PATH . 'assets/images/shaker-3.jpeg',
		],
		[
			'code' => 'SHAKER-4',
			'subtitle' => 'Shaker with four panels',
			'description' => 'Mortise and tenon structure. Built using moisture resistant MDF.',
			'price_from' => 107.00,
			'panels_count' => 4,
			'menu_order' => 4,
			'image' => CUTLIST_CATALOGUE_PATH . 'assets/images/shaker-4.jpeg',
		],
	];

	foreach ($defaults as $d) {
		// Check if door already exists by title
		$existing = get_posts([
			'post_type' => 'hinged_door',
			'title' => $d['code'],
			'post_status' => 'any',
			'numberposts' => 1,
			'fields' => 'ids',
		]);

		$post_id = !empty($existing) ? $existing[0] : 0;

		if (!$post_id) {
			$post_id = wp_insert_post([
				'post_type' => 'hinged_door',
				'post_title' => $d['code'],
				'post_status' => 'publish',
				'menu_order' => $d['menu_order'],
			]);
		}

		if ($post_id && !is_wp_error($post_id)) {
			wp_set_object_terms($post_id, 'Shaker', 'door_category');

			if (function_exists('update_field')) {
				update_field('subtitle', $d['subtitle'], $post_id);
				update_field('price_from', $d['price_from'], $post_id);
				update_field('description', $d['description'], $post_id);
				update_field('panels_count', $d['panels_count'], $post_id);
			} else {
				update_post_meta($post_id, 'subtitle', $d['subtitle']);
				update_post_meta($post_id, 'price_from', $d['price_from']);
				update_post_meta($post_id, 'description', $d['description']);
				update_post_meta($post_id, 'panels_count', $d['panels_count']);
			}

			if (!empty($d['image']) && file_exists($d['image'])) {
				cutlist_attach_plugin_image($d['image'], $post_id, $d['code']);
			}
		}
	}

	update_option('cutlist_hinged_doors_seeded_v1', 1);
}

// =========================================================================
// CUSTOM ADMIN COLUMNS FOR SLIDING DOORS CPTS
// =========================================================================

// 1. Frame Styles Columns
add_filter('manage_sd_frame_style_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $k => $label) {
		$new[$k] = $label;
		if ($k === 'title') {
			$new['sd_base_price'] = __('Base Price (£/door)', 'cutlist-catalogue');
			$new['sd_colors'] = __('Available Colours', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_sd_frame_style_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sd_base_price') {
		$price = get_field('base_price', $post_id) ?: get_post_meta($post_id, 'base_price', true);
		echo $price ? cutlist_format_price_html($price) : '£68.00';
		return;
	}
	if ($column === 'sd_colors') {
		$colors = get_field('colors', $post_id) ?: get_post_meta($post_id, 'colors', true);
		if (!empty($colors) && is_array($colors)) {
			$chips = [];
			foreach ($colors as $c) {
				$hex = $c['color_hex'] ?? '#cccccc';
				$name = $c['color_name'] ?? '';
				$chips[] = '<span style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;font-size:12px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . esc_attr($hex) . ';border:1px solid #999;"></span> ' . esc_html($name) . '</span>';
			}
			echo implode(' ', $chips);
		} else {
			echo '—';
		}
	}
}, 10, 2);

// 2. Door Designs Columns
add_filter('manage_sd_door_design_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $k => $label) {
		$new[$k] = $label;
		if ($k === 'title') {
			$new['sd_panels'] = __('Panels', 'cutlist-catalogue');
			$new['sd_orientation'] = __('Orientation', 'cutlist-catalogue');
			$new['sd_hbar_price'] = __('H-bar Price', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_sd_door_design_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sd_panels') {
		$panels = get_field('panels_count', $post_id) ?: get_post_meta($post_id, 'panels_count', true);
		echo esc_html($panels ?: '1');
		return;
	}
	if ($column === 'sd_orientation') {
		$orient = get_field('orientation', $post_id) ?: get_post_meta($post_id, 'orientation', true);
		echo esc_html(ucfirst($orient ?: 'horizontal'));
		return;
	}
	if ($column === 'sd_hbar_price') {
		$hbar = get_field('h_bar_price', $post_id) ?: get_post_meta($post_id, 'h_bar_price', true);
		echo $hbar ? cutlist_format_price_html($hbar) : '£0.00';
	}
}, 10, 2);

// 3. Coloured Glass Columns
add_filter('manage_sd_glass_panel_posts_columns', function ($columns) {
	$new = [];
	foreach ($columns as $k => $label) {
		if ($k === 'title') {
			$new['sd_swatch'] = __('Swatch', 'cutlist-catalogue');
		}
		$new[$k] = $label;
		if ($k === 'title') {
			$new['sd_category'] = __('Category', 'cutlist-catalogue');
			$new['sd_price_sqm'] = __('Price / m²', 'cutlist-catalogue');
		}
	}
	return $new;
});

add_action('manage_sd_glass_panel_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sd_swatch') {
		$hex = get_field('swatch_color', $post_id) ?: get_post_meta($post_id, 'swatch_color', true);
		$img = get_field('glass_image', $post_id) ?: get_post_meta($post_id, 'glass_image', true);
		if ($img) {
			echo '<span style="display:inline-block;width:28px;height:28px;border-radius:4px;border:1px solid #ccc;background:url(' . esc_url($img) . ') center/cover;"></span>';
		} elseif ($hex) {
			echo '<span style="display:inline-block;width:28px;height:28px;border-radius:4px;border:1px solid #ccc;background:' . esc_attr($hex) . ';"></span>';
		} else {
			echo '—';
		}
		return;
	}
	if ($column === 'sd_category') {
		$cat = get_field('glass_category', $post_id) ?: get_post_meta($post_id, 'glass_category', true);
		$badge_color = ($cat === 'mirrors') ? '#0284c7' : (($cat === 'premium_colours') ? '#7c3aed' : '#16a34a');
		echo '<span style="background:' . esc_attr($badge_color) . ';color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;text-transform:capitalize;">' . esc_html(str_replace('_', ' ', $cat ?: 'colours')) . '</span>';
		return;
	}
	if ($column === 'sd_price_sqm') {
		$price = get_field('price_per_sqm', $post_id) ?: get_post_meta($post_id, 'price_per_sqm', true);
		echo $price ? cutlist_format_price_html($price) . ' / m²' : '—';
	}
}, 10, 2);

/**
 * Settings & Pricing admin page for Sliding Doors
 */
function cutlist_render_sliding_doors_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	if (isset($_POST['cutlist_sd_settings_save']) && check_admin_referer('cutlist_sd_settings_nonce')) {
		update_option('cutlist_sd_track_price_per_meter', (float) $_POST['track_price_per_meter']);
		update_option('cutlist_sd_soft_close_pair_price', (float) $_POST['soft_close_pair_price']);
		update_option('cutlist_sd_mfc_base_price_sqm', (float) $_POST['mfc_base_price_sqm']);
		update_option('cutlist_sd_min_width', (int) $_POST['min_opening_width']);
		update_option('cutlist_sd_max_width', (int) $_POST['max_opening_width']);
		update_option('cutlist_sd_min_height', (int) $_POST['min_opening_height']);
		update_option('cutlist_sd_max_height', (int) $_POST['max_opening_height']);
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Sliding doors settings saved successfully.', 'cutlist-catalogue') . '</p></div>';
	}

	$track_p = (float) get_option('cutlist_sd_track_price_per_meter', 42.00);
	$soft_p = (float) get_option('cutlist_sd_soft_close_pair_price', 34.00);
	$mfc_p = (float) get_option('cutlist_sd_mfc_base_price_sqm', 28.00);
	$min_w = (int) get_option('cutlist_sd_min_width', 1000);
	$max_w = (int) get_option('cutlist_sd_max_width', 5500);
	$min_h = (int) get_option('cutlist_sd_min_height', 1000);
	$max_h = (int) get_option('cutlist_sd_max_height', 3000);
	?>
	<div class="wrap">
		<h1><?php echo esc_html__('Sliding Doors &ndash; System Settings &amp; Pricing', 'cutlist-catalogue'); ?></h1>
		<form method="post" action="">
			<?php wp_nonce_field('cutlist_sd_settings_nonce'); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label
								for="track_price_per_meter"><?php esc_html_e('Aluminium Running Track Price (£/m)', 'cutlist-catalogue'); ?></label>
						</th>
						<td>
							<input name="track_price_per_meter" type="number" step="0.01" id="track_price_per_meter"
								value="<?php echo esc_attr($track_p); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e('Pair of top &amp; bottom running tracks per meter of opening width.', 'cutlist-catalogue'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label
								for="soft_close_pair_price"><?php esc_html_e('Soft Close Damper Pair Price (£)', 'cutlist-catalogue'); ?></label>
						</th>
						<td>
							<input name="soft_close_pair_price" type="number" step="0.01" id="soft_close_pair_price"
								value="<?php echo esc_attr($soft_p); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e('Price per pair of soft close dampers.', 'cutlist-catalogue'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label
								for="mfc_base_price_sqm"><?php esc_html_e('Default MFC Board Infill Price (£/m²)', 'cutlist-catalogue'); ?></label>
						</th>
						<td>
							<input name="mfc_base_price_sqm" type="number" step="0.01" id="mfc_base_price_sqm"
								value="<?php echo esc_attr($mfc_p); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e('Base MFC wood decor infill panel price per square meter.', 'cutlist-catalogue'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label><?php esc_html_e('Opening Width Limits (mm)', 'cutlist-catalogue'); ?></label></th>
						<td>
							Min: <input name="min_opening_width" type="number" value="<?php echo esc_attr($min_w); ?>"
								style="width:100px;"> mm &nbsp;&nbsp;&nbsp;
							Max: <input name="max_opening_width" type="number" value="<?php echo esc_attr($max_w); ?>"
								style="width:100px;"> mm
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label><?php esc_html_e('Opening Height Limits (mm)', 'cutlist-catalogue'); ?></label></th>
						<td>
							Min: <input name="min_opening_height" type="number" value="<?php echo esc_attr($min_h); ?>"
								style="width:100px;"> mm &nbsp;&nbsp;&nbsp;
							Max: <input name="max_opening_height" type="number" value="<?php echo esc_attr($max_h); ?>"
								style="width:100px;"> mm
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="cutlist_sd_settings_save" id="submit" class="button button-primary"
					value="<?php esc_attr_e('Save Changes', 'cutlist-catalogue'); ?>">
			</p>
		</form>
	</div>
	<?php
}

/**
 * Automatically seeds default items for sd_frame_style, sd_door_design, and sd_glass_panel CPTs.
 */
function cutlist_seed_default_sliding_doors()
{
	// 1. Seed Frame Styles with Media Attachments
	if (post_type_exists('sd_frame_style')) {
		$img_dir = CUTLIST_CATALOGUE_PATH . 'assets/images/frames/';

		$frame_defaults = [
			[
				'title' => 'Shaker',
				'code' => 'shaker',
				'base_price' => 68.00,
				'description' => 'Classic Shaker profile with clean borders and built-in finger pull.',
				'profile_image' => $img_dir . 'shaker-cashmere.jpg',
				'lifestyle_image' => $img_dir . 'lifestyle-1.jpg',
				'lifestyle_image_2' => $img_dir . 'lifestyle-2.jpg',
				'colors' => [
					['color_name' => 'White', 'color_hex' => '#f5f5f5', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-white.jpg'],
					['color_name' => 'Silver satin', 'color_hex' => '#c8c8c8', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-silver.jpg'],
					['color_name' => 'Cashmere', 'color_hex' => '#d9d0c7', 'surcharge' => 12.00, 'image_file' => $img_dir . 'shaker-cashmere.jpg'],
					['color_name' => 'Matt black', 'color_hex' => '#1a1a1a', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-black.jpg'],
				],
			],
			[
				'title' => 'Palermo',
				'code' => 'palermo',
				'base_price' => 74.00,
				'description' => 'Soft curved ergonomic profile providing modern elegance and ease of glide.',
				'profile_image' => $img_dir . 'palermo-silver.jpg',
				'lifestyle_image' => $img_dir . 'lifestyle-1.jpg',
				'lifestyle_image_2' => $img_dir . 'lifestyle-2.jpg',
				'colors' => [
					['color_name' => 'Silver satin', 'color_hex' => '#c8c8c8', 'surcharge' => 0.00, 'image_file' => $img_dir . 'palermo-silver.jpg'],
					['color_name' => 'Matt black', 'color_hex' => '#1a1a1a', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-black.jpg'],
					['color_name' => 'Bronze', 'color_hex' => '#6e5849', 'surcharge' => 15.00, 'image_file' => $img_dir . 'prestige-bronze.jpg'],
					['color_name' => 'Brushed brass', 'color_hex' => '#c5a059', 'surcharge' => 18.00, 'image_file' => $img_dir . 'palermo-silver.jpg'],
				],
			],
			[
				'title' => 'Supreme',
				'code' => 'supreme',
				'base_price' => 82.00,
				'description' => 'Sturdy architectural profile with deep recessed channel for large wardrobes.',
				'profile_image' => $img_dir . 'supreme-silver.jpg',
				'lifestyle_image' => $img_dir . 'lifestyle-1.jpg',
				'lifestyle_image_2' => $img_dir . 'lifestyle-2.jpg',
				'colors' => [
					['color_name' => 'Silver satin', 'color_hex' => '#c8c8c8', 'surcharge' => 0.00, 'image_file' => $img_dir . 'supreme-silver.jpg'],
					['color_name' => 'Matt black', 'color_hex' => '#1a1a1a', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-black.jpg'],
					['color_name' => 'Anthracite', 'color_hex' => '#383e42', 'surcharge' => 10.00, 'image_file' => $img_dir . 'slim-black.jpg'],
					['color_name' => 'White', 'color_hex' => '#f5f5f5', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-white.jpg'],
				],
			],
			[
				'title' => 'Prestige',
				'code' => 'prestige',
				'base_price' => 88.00,
				'description' => 'Sculpted luxury profile with premium anodised and painted finishes.',
				'profile_image' => $img_dir . 'prestige-bronze.jpg',
				'lifestyle_image' => $img_dir . 'lifestyle-1.jpg',
				'lifestyle_image_2' => $img_dir . 'lifestyle-2.jpg',
				'colors' => [
					['color_name' => 'Silver satin', 'color_hex' => '#c8c8c8', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-silver.jpg'],
					['color_name' => 'Matt black', 'color_hex' => '#1a1a1a', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-black.jpg'],
					['color_name' => 'Bronze', 'color_hex' => '#6e5849', 'surcharge' => 15.00, 'image_file' => $img_dir . 'prestige-bronze.jpg'],
					['color_name' => 'Brushed brass', 'color_hex' => '#c5a059', 'surcharge' => 18.00, 'image_file' => $img_dir . 'prestige-bronze.jpg'],
				],
			],
			[
				'title' => 'Slim',
				'code' => 'slim',
				'base_price' => 78.00,
				'description' => 'Minimalist slimline edge maximizing visible surface of glass and decorative decors.',
				'profile_image' => $img_dir . 'slim-black.jpg',
				'lifestyle_image' => $img_dir . 'lifestyle-1.jpg',
				'lifestyle_image_2' => $img_dir . 'lifestyle-2.jpg',
				'colors' => [
					['color_name' => 'Matt black', 'color_hex' => '#1a1a1a', 'surcharge' => 0.00, 'image_file' => $img_dir . 'slim-black.jpg'],
					['color_name' => 'Silver satin', 'color_hex' => '#c8c8c8', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-silver.jpg'],
					['color_name' => 'Anthracite', 'color_hex' => '#383e42', 'surcharge' => 10.00, 'image_file' => $img_dir . 'slim-black.jpg'],
					['color_name' => 'White', 'color_hex' => '#f5f5f5', 'surcharge' => 0.00, 'image_file' => $img_dir . 'shaker-white.jpg'],
				],
			],
		];

		// Fetch existing frame posts
		$existing_fps = get_posts([
			'post_type' => 'sd_frame_style',
			'posts_per_page' => -1,
			'post_status' => 'any',
		]);

		$synced_flag = get_option('cutlist_sd_gallery_synced_v8');

		foreach ($frame_defaults as $i => $fd) {
			$p_id = 0;
			foreach ($existing_fps as $efp) {
				$efp_code = get_post_meta($efp->ID, 'frame_code', true);
				if (strcasecmp($efp->post_title, $fd['title']) === 0 || strcasecmp($efp_code, $fd['code']) === 0) {
					$p_id = $efp->ID;
					break;
				}
			}

			if (!$p_id) {
				$p_id = wp_insert_post([
					'post_type' => 'sd_frame_style',
					'post_title' => $fd['title'],
					'post_status' => 'publish',
					'menu_order' => $i + 1,
				]);
			}

			if ($p_id && !is_wp_error($p_id)) {
				if (!get_field('frame_code', $p_id)) {
					update_field('frame_code', $fd['code'], $p_id);
				}
				if (!get_field('base_price', $p_id)) {
					update_field('base_price', $fd['base_price'], $p_id);
				}
				if (!get_field('description', $p_id)) {
					update_field('description', $fd['description'], $p_id);
				}

				// Always ensure media attachments & Gallery meta are populated
				if (!$synced_flag || isset($_GET['sync_sd_images'])) {
					$gallery_ids = [];
					// 1. Profile / Featured render
					$profile_attach = 0;
					if (!empty($fd['profile_image']) && file_exists($fd['profile_image'])) {
						$profile_attach = cutlist_attach_plugin_image($fd['profile_image'], $p_id, $fd['title'] . ' Profile');
						if ($profile_attach) {
							set_post_thumbnail($p_id, $profile_attach);
						}
					}

					// 2. Shared Lifestyle images
					$life1_id = 0;
					if (!empty($fd['lifestyle_image']) && file_exists($fd['lifestyle_image'])) {
						$life1_id = cutlist_attach_plugin_image($fd['lifestyle_image'], 0, $fd['title'] . ' Lifestyle 1');
					}
					$life2_id = 0;
					if (!empty($fd['lifestyle_image_2']) && file_exists($fd['lifestyle_image_2'])) {
						$life2_id = cutlist_attach_plugin_image($fd['lifestyle_image_2'], 0, $fd['title'] . ' Lifestyle 2');
					}

					// 3. Populate per-colour tabs with pricing and gallery
					$colors_data = [];
					foreach ($fd['colors'] as $c) {
						$c_gallery_ids = [];
						$c_attach_id = 0;
						if (!empty($c['image_file']) && file_exists($c['image_file'])) {
							$c_attach_id = cutlist_attach_plugin_image($c['image_file'], 0, $fd['title'] . ' ' . $c['color_name']);
							if ($c_attach_id) {
								$c_gallery_ids[] = (int) $c_attach_id;
							}
						} elseif ($profile_attach) {
							$c_gallery_ids[] = (int) $profile_attach;
						}

						if ($life1_id) $c_gallery_ids[] = (int) $life1_id;
						if ($life2_id) $c_gallery_ids[] = (int) $life2_id;

						$c_price = $fd['base_price'] + (float) ($c['surcharge'] ?? 0);
						$c_gallery_urls = [];
						foreach ($c_gallery_ids as $gid) {
							$gurl = wp_get_attachment_image_url($gid, 'full');
							if ($gurl) $c_gallery_urls[] = $gurl;
						}

						$colors_data[] = [
							'color_name'   => $c['color_name'],
							'name'         => $c['color_name'],
							'color_hex'    => $c['color_hex'],
							'hex'          => $c['color_hex'],
							'base_price'   => $c_price,
							'surcharge'    => (float) ($c['surcharge'] ?? 0),
							'gallery_ids'  => $c_gallery_ids,
							'gallery'      => $c_gallery_urls,
							'sample_image' => !empty($c_gallery_urls) ? $c_gallery_urls[0] : '',
							'image'        => !empty($c_gallery_urls) ? $c_gallery_urls[0] : '',
						];
					}

					update_post_meta($p_id, '_frame_colors_data', $colors_data);
					update_post_meta($p_id, 'colors', $colors_data);
				}
			}
		}

		if (!$synced_flag || isset($_GET['sync_sd_images'])) {
			update_option('cutlist_sd_gallery_synced_v9', 1);
		}
	}

	// 2. Seed Door Designs
	if (post_type_exists('sd_door_design') && !get_option('cutlist_sd_door_designs_seeded_v1')) {
		$design_defaults = [
			['title' => 'Single panel', 'panels_count' => 1, 'orientation' => 'horizontal', 'subtitle' => '', 'h_bar_price' => 0.00],
			['title' => 'Two panels', 'panels_count' => 2, 'orientation' => 'both', 'subtitle' => 'Horizontal or Vertical', 'h_bar_price' => 16.00],
			['title' => 'Three panels', 'panels_count' => 3, 'orientation' => 'both', 'subtitle' => 'Horizontal or Vertical', 'h_bar_price' => 32.00],
			['title' => 'Four panels', 'panels_count' => 4, 'orientation' => 'horizontal', 'subtitle' => '', 'h_bar_price' => 48.00],
			['title' => 'Five panels', 'panels_count' => 5, 'orientation' => 'horizontal', 'subtitle' => '', 'h_bar_price' => 64.00],
		];

		foreach ($design_defaults as $i => $dd) {
			$exists = get_posts([
				'post_type' => 'sd_door_design',
				'title' => $dd['title'],
				'fields' => 'ids',
				'numberposts' => 1,
				'post_status' => 'any',
			]);
			if (empty($exists)) {
				$p_id = wp_insert_post([
					'post_type' => 'sd_door_design',
					'post_title' => $dd['title'],
					'post_status' => 'publish',
					'menu_order' => $i + 1,
				]);
				if ($p_id && !is_wp_error($p_id)) {
					update_field('panels_count', $dd['panels_count'], $p_id);
					update_field('orientation', $dd['orientation'], $p_id);
					update_field('subtitle', $dd['subtitle'], $p_id);
					update_field('h_bar_price', $dd['h_bar_price'], $p_id);
				}
			}
		}
		update_option('cutlist_sd_door_designs_seeded_v1', 1);
	}

	// 3. Seed Glass Panels
	if (post_type_exists('sd_glass_panel') && !get_option('cutlist_sd_glass_panels_seeded_v1')) {
		$glass_defaults = [
			['title' => 'Pure White Glass', 'category' => 'coloured_glass', 'price' => 34.00, 'hex' => '#f8f9fa'],
			['title' => 'Soft White Glass', 'category' => 'coloured_glass', 'price' => 34.00, 'hex' => '#eceff1'],
			['title' => 'Classic Black Glass', 'category' => 'coloured_glass', 'price' => 34.00, 'hex' => '#18181b'],
			['title' => 'Anthracite Glass', 'category' => 'coloured_glass', 'price' => 34.00, 'hex' => '#334155'],
			['title' => 'Silver Mirror', 'category' => 'mirrors', 'price' => 30.00, 'hex' => '#cbd5e1'],
			['title' => 'Bronze Mirror', 'category' => 'mirrors', 'price' => 38.00, 'hex' => '#78350f'],
			['title' => 'Grey Mirror', 'category' => 'mirrors', 'price' => 38.00, 'hex' => '#475569'],
			['title' => 'Frosted Satin Mirror', 'category' => 'mirrors', 'price' => 40.00, 'hex' => '#e2e8f0'],
		];

		foreach ($glass_defaults as $i => $gd) {
			$exists = get_posts([
				'post_type' => 'sd_glass_panel',
				'title' => $gd['title'],
				'fields' => 'ids',
				'numberposts' => 1,
				'post_status' => 'any',
			]);
			if (empty($exists)) {
				$p_id = wp_insert_post([
					'post_type' => 'sd_glass_panel',
					'post_title' => $gd['title'],
					'post_status' => 'publish',
					'menu_order' => $i + 1,
				]);
				if ($p_id && !is_wp_error($p_id)) {
					update_field('glass_category', $gd['category'], $p_id);
					update_field('price_per_sqm', $gd['price'], $p_id);
					update_field('swatch_color', $gd['hex'], $p_id);
					update_field('thickness_spec', '4mm safety backed', $p_id);
				}
			}
		}
		update_option('cutlist_sd_glass_panels_seeded_v1', 1);
	}
}

/**
 * Returns the dynamic Sliding Door configuration queried from individual CPTs with fallback defaults.
 */
function cutlist_get_sliding_doors_admin_config()
{
	$config = [
		'frames' => [],
		'designs' => [],
		'glass' => [],
		'pricing' => [
			'trackPerMeter' => (float) get_option('cutlist_sd_track_price_per_meter', 42.00),
			'softClosePair' => (float) get_option('cutlist_sd_soft_close_pair_price', 34.00),
			'mfcBasePerSqm' => (float) get_option('cutlist_sd_mfc_base_price_sqm', 28.00),
			'minWidth' => (int) get_option('cutlist_sd_min_width', 1000),
			'maxWidth' => (int) get_option('cutlist_sd_max_width', 5500),
			'minHeight' => (int) get_option('cutlist_sd_min_height', 1000),
			'maxHeight' => (int) get_option('cutlist_sd_max_height', 3000),
			'vatRate' => 0.20,
		],
	];

	// 1. Fetch Frame Styles
	$frame_posts = get_posts([
		'post_type' => 'sd_frame_style',
		'post_status' => 'publish',
		'posts_per_page' => 50,
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);

	$frames_base_url = plugins_url('assets/images/frames/', dirname(__FILE__));

	if (!empty($frame_posts)) {
		foreach ($frame_posts as $f) {
			$f_code = get_field('frame_code', $f->ID) ?: sanitize_title($f->post_title);
			$f_price = (float) get_post_meta($f->ID, 'base_price', true);
			if ($f_price <= 0 && function_exists('get_field')) {
				$f_price = (float) get_field('base_price', $f->ID);
			}
			$f_desc = get_field('description', $f->ID) ?: '';

			// 1. Featured Image (used on Main Configurator Step 2 card)
			$feat_id = get_post_thumbnail_id($f->ID);
			$feat_url = $feat_id ? wp_get_attachment_image_url($feat_id, 'medium_large') : '';
			if (empty($feat_url)) {
				$feat_url = $frames_base_url . $f_code . '-silver.jpg';
			}

			// 2. Fetch Colours & Per-Colour Galleries
			$colors_raw = get_post_meta($f->ID, '_frame_colors_data', true);
			if (empty($colors_raw) || !is_array($colors_raw)) {
				$colors_raw = get_post_meta($f->ID, 'colors', true);
			}
			if (empty($colors_raw) || !is_array($colors_raw)) {
				$colors_raw = function_exists('get_field') ? get_field('colors', $f->ID) : [];
			}

			$f_colors = [];
			if (!empty($colors_raw) && is_array($colors_raw)) {
				foreach ($colors_raw as $c) {
					$c_name = $c['color_name'] ?? ($c['name'] ?? 'Silver satin');
					$c_hex = $c['color_hex'] ?? ($c['hex'] ?? '#c8c8c8');
					$c_price = isset($c['base_price']) && (float)$c['base_price'] > 0 ? (float)$c['base_price'] : ($f_price > 0 ? $f_price : 74.00);

					// Per-color gallery URLs
					$c_gallery_urls = [];
					$c_raw_ids = $c['gallery_ids'] ?? [];
					if (is_string($c_raw_ids)) {
						$c_raw_ids = array_filter(array_map('trim', explode(',', $c_raw_ids)));
					}
					if (is_array($c_raw_ids) && !empty($c_raw_ids)) {
						foreach ($c_raw_ids as $gid) {
							if (is_numeric($gid) && (int)$gid > 0) {
								$gurl = wp_get_attachment_image_url((int)$gid, 'full');
								if ($gurl) $c_gallery_urls[] = $gurl;
							} elseif (!empty($gid)) {
								$c_gallery_urls[] = $gid;
							}
						}
					}

					// If gallery URLs is empty, fallback to legacy sample_image
					if (empty($c_gallery_urls)) {
						$c_img_raw = $c['sample_image'] ?? ($c['image'] ?? '');
						if (is_numeric($c_img_raw) && (int)$c_img_raw > 0) {
							$c_img_url = wp_get_attachment_image_url((int)$c_img_raw, 'full');
						} else {
							$c_img_url = $c_img_raw;
						}
						if ($c_img_url) {
							$c_gallery_urls[] = $c_img_url;
						}
					}

					// If still empty, use fallback images based on slug
					if (empty($c_gallery_urls)) {
						$c_slug = sanitize_title($c_name);
						if (strpos($c_slug, 'white') !== false && file_exists(CUTLIST_CATALOGUE_PATH . 'assets/images/frames/shaker-white.jpg')) {
							$c_gallery_urls[] = $frames_base_url . 'shaker-white.jpg';
						} elseif (strpos($c_slug, 'cashmere') !== false && file_exists(CUTLIST_CATALOGUE_PATH . 'assets/images/frames/shaker-cashmere.jpg')) {
							$c_gallery_urls[] = $frames_base_url . 'shaker-cashmere.jpg';
						} elseif (strpos($c_slug, 'black') !== false) {
							$c_gallery_urls[] = $frames_base_url . ($f_code === 'slim' ? 'slim-black.jpg' : 'shaker-black.jpg');
						} elseif (strpos($c_slug, 'bronze') !== false && file_exists(CUTLIST_CATALOGUE_PATH . 'assets/images/frames/prestige-bronze.jpg')) {
							$c_gallery_urls[] = $frames_base_url . 'prestige-bronze.jpg';
						} elseif (strpos($c_slug, 'brass') !== false && file_exists(CUTLIST_CATALOGUE_PATH . 'assets/images/frames/palermo-silver.jpg')) {
							$c_gallery_urls[] = $frames_base_url . 'palermo-silver.jpg';
						} else {
							$c_gallery_urls[] = $feat_url;
						}
					}

					// Ensure lifestyle photos are attached if only 1 image exists
					if (count($c_gallery_urls) === 1) {
						$c_gallery_urls[] = $frames_base_url . 'lifestyle-1.jpg';
						$c_gallery_urls[] = $frames_base_url . 'lifestyle-2.jpg';
					}

					// 1st image in gallery is the featured swatch preview for this color
					$c_feat_img = $c_gallery_urls[0];

					$f_colors[] = [
						'name'       => $c_name,
						'hex'        => $c_hex,
						'basePrice'  => $c_price,
						'surcharge'  => (float) ($c['surcharge'] ?? 0),
						'image'      => $c_feat_img,
						'gallery'    => $c_gallery_urls,
					];
				}
			}

			// First color and primary defaults
			$first_color = !empty($f_colors) ? $f_colors[0] : null;
			$default_gallery = ($first_color && !empty($first_color['gallery'])) ? $first_color['gallery'] : [$feat_url, $frames_base_url . 'lifestyle-1.jpg', $frames_base_url . 'lifestyle-2.jpg'];
			$default_base_price = ($first_color && $first_color['basePrice'] > 0) ? $first_color['basePrice'] : ($f_price > 0 ? $f_price : 74.00);

			$config['frames'][] = [
				'id'             => $f_code,
				'name'           => $f->post_title,
				'code'           => $f_code,
				'basePrice'      => $default_base_price,
				'description'    => $f_desc,
				'featuredImage'  => $feat_url,
				'profileImage'   => $default_gallery[0] ?? $feat_url,
				'gallery'        => $default_gallery,
				'lifestyleImage' => $default_gallery[1] ?? ($frames_base_url . 'lifestyle-1.jpg'),
				'lifestyleImage2'=> $default_gallery[2] ?? ($frames_base_url . 'lifestyle-2.jpg'),
				'colors'         => $f_colors,
			];
		}
	}

	// Fallbacks if empty
	if (empty($config['frames'])) {
		$config['frames'] = [
			[
				'id' => 'shaker',
				'name' => 'Shaker',
				'code' => 'shaker',
				'basePrice' => 68.00,
				'description' => 'Classic Shaker profile with clean borders and built-in finger pull.',
				'profileImage' => $frames_base_url . 'shaker-cashmere.jpg',
				'lifestyleImage' => $frames_base_url . 'lifestyle-1.jpg',
				'lifestyleImage2' => $frames_base_url . 'lifestyle-2.jpg',
				'colors' => [
					['name' => 'White', 'hex' => '#f5f5f5', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-white.jpg'],
					['name' => 'Silver satin', 'hex' => '#c8c8c8', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-silver.jpg'],
					['name' => 'Cashmere', 'hex' => '#d9d0c7', 'surcharge' => 12.00, 'image' => $frames_base_url . 'shaker-cashmere.jpg'],
					['name' => 'Matt black', 'hex' => '#1a1a1a', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-black.jpg'],
				]
			],
			[
				'id' => 'palermo',
				'name' => 'Palermo',
				'code' => 'palermo',
				'basePrice' => 74.00,
				'description' => 'Soft curved ergonomic profile providing modern elegance and ease of glide.',
				'profileImage' => $frames_base_url . 'palermo-silver.jpg',
				'lifestyleImage' => $frames_base_url . 'lifestyle-1.jpg',
				'lifestyleImage2' => $frames_base_url . 'lifestyle-2.jpg',
				'colors' => [
					['name' => 'Silver satin', 'hex' => '#c8c8c8', 'surcharge' => 0.00, 'image' => $frames_base_url . 'palermo-silver.jpg'],
					['name' => 'Matt black', 'hex' => '#1a1a1a', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-black.jpg'],
					['name' => 'Bronze', 'hex' => '#6e5849', 'surcharge' => 15.00, 'image' => $frames_base_url . 'prestige-bronze.jpg'],
					['name' => 'Brushed brass', 'hex' => '#c5a059', 'surcharge' => 18.00, 'image' => $frames_base_url . 'palermo-silver.jpg'],
				]
			],
			[
				'id' => 'supreme',
				'name' => 'Supreme',
				'code' => 'supreme',
				'basePrice' => 82.00,
				'description' => 'Sturdy architectural profile with deep recessed channel for large wardrobes.',
				'profileImage' => $frames_base_url . 'supreme-silver.jpg',
				'lifestyleImage' => $frames_base_url . 'lifestyle-1.jpg',
				'lifestyleImage2' => $frames_base_url . 'lifestyle-2.jpg',
				'colors' => [
					['name' => 'Silver satin', 'hex' => '#c8c8c8', 'surcharge' => 0.00, 'image' => $frames_base_url . 'supreme-silver.jpg'],
					['name' => 'Matt black', 'hex' => '#1a1a1a', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-black.jpg'],
					['name' => 'Anthracite', 'hex' => '#383e42', 'surcharge' => 10.00, 'image' => $frames_base_url . 'slim-black.jpg'],
					['name' => 'White', 'hex' => '#f5f5f5', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-white.jpg'],
				]
			],
			[
				'id' => 'prestige',
				'name' => 'Prestige',
				'code' => 'prestige',
				'basePrice' => 88.00,
				'description' => 'Sculpted luxury profile with premium anodised and painted finishes.',
				'profileImage' => $frames_base_url . 'prestige-bronze.jpg',
				'lifestyleImage' => $frames_base_url . 'lifestyle-1.jpg',
				'lifestyleImage2' => $frames_base_url . 'lifestyle-2.jpg',
				'colors' => [
					['name' => 'Silver satin', 'hex' => '#c8c8c8', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-silver.jpg'],
					['name' => 'Matt black', 'hex' => '#1a1a1a', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-black.jpg'],
					['name' => 'Bronze', 'hex' => '#6e5849', 'surcharge' => 15.00, 'image' => $frames_base_url . 'prestige-bronze.jpg'],
					['name' => 'Brushed brass', 'hex' => '#c5a059', 'surcharge' => 18.00, 'image' => $frames_base_url . 'prestige-bronze.jpg'],
				]
			],
			[
				'id' => 'slim',
				'name' => 'Slim',
				'code' => 'slim',
				'basePrice' => 78.00,
				'description' => 'Minimalist slimline edge maximizing visible surface of glass and decorative decors.',
				'profileImage' => $frames_base_url . 'slim-black.jpg',
				'lifestyleImage' => $frames_base_url . 'lifestyle-1.jpg',
				'lifestyleImage2' => $frames_base_url . 'lifestyle-2.jpg',
				'colors' => [
					['name' => 'Matt black', 'hex' => '#1a1a1a', 'surcharge' => 0.00, 'image' => $frames_base_url . 'slim-black.jpg'],
					['name' => 'Silver satin', 'hex' => '#c8c8c8', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-silver.jpg'],
					['name' => 'Anthracite', 'hex' => '#383e42', 'surcharge' => 10.00, 'image' => $frames_base_url . 'slim-black.jpg'],
					['name' => 'White', 'hex' => '#f5f5f5', 'surcharge' => 0.00, 'image' => $frames_base_url . 'shaker-white.jpg'],
				]
			],
		];
	}

	// 2. Fetch Door Designs
	$design_posts = get_posts([
		'post_type' => 'sd_door_design',
		'post_status' => 'publish',
		'posts_per_page' => 50,
		'orderby' => 'menu_order panels_count',
		'order' => 'ASC',
	]);

	if (!empty($design_posts)) {
		foreach ($design_posts as $d) {
			$d_count = (int) get_field('panels_count', $d->ID);
			if ($d_count <= 0)
				$d_count = 1;
			$d_orient = get_field('orientation', $d->ID) ?: 'horizontal';
			$d_sub = get_field('subtitle', $d->ID);
			$d_hbar = (float) get_field('h_bar_price', $d->ID);

			$config['designs'][] = [
				'id' => $d_count . '-panel',
				'name' => $d->post_title,
				'panels' => $d_count,
				'orientation' => $d_orient,
				'subtitle' => $d_sub,
				'hBarPrice' => $d_hbar,
			];
		}
	}

	// 3. Fetch Coloured Glass
	$glass_posts = get_posts([
		'post_type' => 'sd_glass_panel',
		'post_status' => 'publish',
		'posts_per_page' => 100,
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);

	if (!empty($glass_posts)) {
		foreach ($glass_posts as $g) {
			$g_cat = get_field('glass_category', $g->ID) ?: 'colours';
			$g_price = (float) get_field('price_per_sqm', $g->ID);
			$g_hex = get_field('swatch_color', $g->ID) ?: '#e2e8f0';
			$g_img = get_field('glass_image', $g->ID) ?: '';
			$g_thick = get_field('thickness_spec', $g->ID) ?: '4mm safety backed';

			$config['glass'][] = [
				'id' => sanitize_title($g->post_title),
				'name' => $g->post_title,
				'category' => $g_cat,
				'pricePerSqm' => $g_price > 0 ? $g_price : 30.00,
				'swatchColor' => $g_hex,
				'thickness' => $g_thick,
				'image' => $g_img,
			];
		}
	}

	if (empty($config['designs'])) {
		$config['designs'] = [
			['id' => '1-panel', 'name' => 'Single panel', 'panels' => 1, 'orientation' => 'horizontal', 'subtitle' => '', 'hBarPrice' => 0.00],
			['id' => '2-panel', 'name' => 'Two panels', 'panels' => 2, 'orientation' => 'both', 'subtitle' => 'Horizontal or Vertical', 'hBarPrice' => 16.00],
			['id' => '3-panel', 'name' => 'Three panels', 'panels' => 3, 'orientation' => 'both', 'subtitle' => 'Horizontal or Vertical', 'hBarPrice' => 32.00],
			['id' => '4-panel', 'name' => 'Four panels', 'panels' => 4, 'orientation' => 'horizontal', 'subtitle' => '', 'hBarPrice' => 48.00],
			['id' => '5-panel', 'name' => 'Five panels', 'panels' => 5, 'orientation' => 'horizontal', 'subtitle' => '', 'hBarPrice' => 64.00],
		];
	}

	if (empty($config['glass'])) {
		$config['glass'] = [
			['id' => 'silver-mirror', 'name' => 'Silver Mirror', 'category' => 'mirrors', 'pricePerSqm' => 30.00, 'swatchColor' => '#dbe4eb', 'thickness' => '4mm safety backed'],
			['id' => 'grey-mirror', 'name' => 'Grey Mirror', 'category' => 'mirrors', 'pricePerSqm' => 35.00, 'swatchColor' => '#5c646b', 'thickness' => '4mm safety backed'],
			['id' => 'bronze-mirror', 'name' => 'Bronze Mirror', 'category' => 'mirrors', 'pricePerSqm' => 38.00, 'swatchColor' => '#9e7358', 'thickness' => '4mm safety backed'],
			['id' => 'pure-white', 'name' => 'Pure White', 'category' => 'colours', 'pricePerSqm' => 45.00, 'swatchColor' => '#ffffff', 'thickness' => '4mm safety backed'],
		];
	}

	return $config;
}


