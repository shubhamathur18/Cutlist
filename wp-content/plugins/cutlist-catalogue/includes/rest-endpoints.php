<?php
/**
 * REST surface for the catalogue. Front end fetches boards from here
 * instead of a hardcoded list, so wp-admin stays the single source of
 * truth.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('rest_api_init', function () {
	register_rest_route('cutlist/v1', '/boards', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_boards',
		'permission_callback' => '__return_true',
	]);

	register_rest_route('cutlist/v1', '/boards/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_board',
		'permission_callback' => '__return_true',
		'args' => [
			'id' => [
				'validate_callback' => function ($value) {
					return is_numeric($value);
				},
			],
		],
	]);

	register_rest_route('cutlist/v1', '/edge-tapes', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_edge_tapes',
		'permission_callback' => '__return_true',
	]);

	register_rest_route('cutlist/v1', '/spray-finishes', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_spray_finishes',
		'permission_callback' => '__return_true',
	]);

	register_rest_route('cutlist/v1', '/machining-options', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_machining_options',
		'permission_callback' => '__return_true',
	]);
});

function cutlist_rest_get_boards(WP_REST_Request $request) {
	$args = [
		'post_type' => 'board',
		'numberposts' => -1,
		'post_status' => 'publish',
	];

	$tax_query = [];

	$brand = $request->get_param('brand');
	if ($brand) {
		$tax_query[] = [
			'taxonomy' => 'board_brand',
			'field' => 'slug',
			'terms' => sanitize_title($brand),
		];
	}

	$collection = $request->get_param('collection');
	if ($collection) {
		$tax_query[] = [
			'taxonomy' => 'board_collection',
			'field' => 'slug',
			'terms' => sanitize_title($collection),
		];
	}

	if ($tax_query) {
		$args['tax_query'] = $tax_query;
	}

	$posts = get_posts($args);

	return array_values(array_map('cutlist_format_board', $posts));
}

function cutlist_rest_get_board(WP_REST_Request $request) {
	$post = get_post((int) $request->get_param('id'));

	if (!$post || $post->post_type !== 'board' || $post->post_status !== 'publish') {
		return new WP_Error('not_found', __('Board not found.', 'cutlist-catalogue'), ['status' => 404]);
	}

	return cutlist_format_board($post);
}

function cutlist_rest_get_edge_tapes(WP_REST_Request $request) {
	$posts = get_posts([
		'post_type' => 'edge_tape',
		'numberposts' => -1,
		'post_status' => 'publish',
	]);

	return array_values(array_map('cutlist_format_edge_tape', $posts));
}

function cutlist_format_edge_tape($post) {
	$board_ids = get_field('boards', $post->ID) ?: [];
	$boards = array_map(function ($board_id) {
		return [
			'id' => $board_id,
			'decor_code' => get_field('decor_code', $board_id),
			'decor_name' => get_field('decor_name', $board_id),
		];
	}, $board_ids);

	// Tapes saved before these two fields existed have no stored value, so
	// get_field() returns null rather than the field's default. Treating
	// null as true keeps those tapes offering both finishes exactly as they
	// did before — only an explicit "No" in wp-admin turns one off.
	$radius = get_field('radius_edge_finish', $post->ID);
	$square = get_field('square_edge_finish', $post->ID);

	return [
		'id' => $post->ID,
		'tape_code' => get_field('tape_code', $post->ID),
		'product_name' => get_field('product_name', $post->ID),
		'size' => get_field('size', $post->ID),
		'unit_price' => (float) get_field('unit_price', $post->ID),
		'radius_edge_finish' => ($radius === null ? true : (bool) $radius),
		'square_edge_finish' => ($square === null ? true : (bool) $square),
		'image' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: null,
		'boards' => $boards,
	];
}

function cutlist_rest_get_machining_options(WP_REST_Request $request) {
	return array_values(array_map('cutlist_format_machining_option', cutlist_get_machining_option_posts()));
}

// Ordered the way the dropdown renders them: group first (so a group's
// items stay together), then menu_order within the group.
function cutlist_get_machining_option_posts() {
	return get_posts([
		'post_type' => 'machining_option',
		'numberposts' => -1,
		'post_status' => 'publish',
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);
}

function cutlist_format_machining_option($post) {
	$terms = get_the_terms($post->ID, 'machining_group');
	$group = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';

	// `available` predates nothing, but an option saved before the field
	// existed returns null — treat that as available so a missing value
	// never silently removes an option from the dropdown.
	$available = get_field('available', $post->ID);

	return [
		'id' => $post->ID,
		// post_name is the stable key rows store in machiningApplied, and
		// what the behaviour switch in cutlist-main.js branches on.
		'slug' => $post->post_name,
		'label' => $post->post_title,
		'group' => $group,
		'behaviour' => get_field('behaviour', $post->ID) ?: 'simple',
		'available' => ($available === null ? true : (bool) $available),
		// Shown in the order summary's "Additional services" table.
		'serviceCode' => (string) get_field('service_code', $post->ID),
		'shortDescription' => (string) get_field('short_description', $post->ID),
		'price' => (float) get_field('price', $post->ID),
		'edgingPrice' => (float) (get_field('edging_price', $post->ID) ?: 28.87),
		'edgingServiceCode' => (string) (get_field('edging_service_code', $post->ID) ?: 'MC-ANG-EDGE'),
	];
}

function cutlist_rest_get_spray_finishes(WP_REST_Request $request) {
	$posts = get_posts([
		'post_type' => 'spray_finish',
		'numberposts' => -1,
		'post_status' => 'publish',
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);

	return array_values(array_map('cutlist_format_spray_finish', $posts));
}

// "Finishes" is a plain textarea (ACF Repeater is PRO-only), one row per
// line as "Title | Sub-label | Price" — same one-line-per-row convention
// already used for Board's "characteristics" field above.
function cutlist_format_spray_finish($post) {
	$finishes = [];
	foreach (preg_split('/\r\n|\r|\n/', get_field('finishes', $post->ID) ?: '') as $line) {
		$parts = array_map('trim', explode('|', $line));
		if (!isset($parts[0]) || $parts[0] === '') {
			continue;
		}
		$finishes[] = [
			'title' => $parts[0],
			'sub' => $parts[1] ?? '',
			'price' => isset($parts[2]) ? (float) $parts[2] : 0,
		];
	}

	$b_side_text = get_field('b_side_text', $post->ID);
	$b_option = $b_side_text ? [
		'text' => $b_side_text,
		'price' => (float) get_field('b_side_price', $post->ID),
	] : null;

	return [
		'id' => $post->ID,
		// post_name (the URL slug) doubles as the option's stable key —
		// same role the hardcoded object keys ("white-primer", ...) used
		// to play, just admin-controlled now via the post's own slug.
		'slug' => $post->post_name,
		'label' => $post->post_title,
		'panelFill' => get_field('panel_fill_colour', $post->ID) ?: '#dddddd',
		'paintFields' => (bool) get_field('show_paint_fields', $post->ID),
		'paintBrands' => array_values(array_filter(array_map(
			'trim',
			explode("\n", get_field('paint_brands', $post->ID) ?: '')
		))),
		'finishes' => $finishes,
		'bOption' => $b_option,
	];
}

function cutlist_format_board($post) {
	$brand_terms = get_the_terms($post->ID, 'board_brand');
	$brand = ($brand_terms && !is_wp_error($brand_terms)) ? $brand_terms[0]->name : null;

	$collection_terms = get_the_terms($post->ID, 'board_collection');
	$collection = ($collection_terms && !is_wp_error($collection_terms)) ? $collection_terms[0]->name : null;

	// Gallery images are picked via the custom "Gallery" sidebar meta box
	// (gallery-meta-box.php) — ACF's own gallery field type is PRO-only.
	$gallery_ids = get_post_meta($post->ID, CUTLIST_BOARD_GALLERY_META, true);
	$gallery_ids = is_array($gallery_ids) ? $gallery_ids : [];
	$images = array_map(function ($id) {
		$id = (int) $id;
		return [
			'url' => wp_get_attachment_url($id),
			'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail') ?: wp_get_attachment_url($id),
			'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
		];
	}, array_unique($gallery_ids));

	// Repeater fields are ACF PRO-only, so "characteristics" is a plain
	// textarea admins fill in as one "Label: Value" line per row.
	$characteristics = [];
	foreach (preg_split('/\r\n|\r|\n/', get_field('characteristics', $post->ID) ?: '') as $line) {
		$line = trim($line);
		if ($line === '' || strpos($line, ':') === false) {
			continue;
		}
		[$label, $value] = explode(':', $line, 2);
		$characteristics[] = ['label' => trim($label), 'value' => trim($value)];
	}

	$download_file = get_field('downloads', $post->ID);
	$downloads = $download_file ? [[
		'label' => $download_file['title'] ?: $download_file['filename'],
		'url' => $download_file['url'],
	]] : [];

	return [
		'id' => $post->ID,
		'brand' => $brand,
		'collection' => $collection,
		'decor_code' => get_field('decor_code', $post->ID),
		'decor_name' => get_field('decor_name', $post->ID),
		'core' => get_field('core', $post->ID) ?: null,
		'name' => $post->post_title,
		'length_mm' => (float) get_field('length_mm', $post->ID),
		'width_mm' => (float) get_field('width_mm', $post->ID),
		'thickness_mm' => (float) get_field('thickness_mm', $post->ID),
		'full_sheet_price' => (float) get_field('full_sheet_price', $post->ID),
		'machining' => [
			'cut_to_size' => (bool) get_field('machining_cut_to_size', $post->ID),
			'edgebanding' => (bool) get_field('machining_edgebanding', $post->ID),
			'cnc' => (bool) get_field('machining_cnc', $post->ID),
		],
		// Machining Option slugs this decor can't take. Slugs rather than
		// post IDs so the front end can compare straight against the
		// data-option values it already uses.
		'machining_excluded' => array_values(array_filter(array_map(function ($id) {
			$option = get_post($id);
			return $option ? $option->post_name : null;
		}, get_field('machining_excluded', $post->ID) ?: []))),
		'spray_finishing' => (bool) get_field('spray_finishing', $post->ID),
		'grain_match' => (bool) get_field('grain_match', $post->ID),
		'description' => get_field('description', $post->ID),
		'b_side_description' => get_field('b_side_description', $post->ID),
		'characteristics' => $characteristics,
		'downloads' => $downloads,
		'manufacturer_url' => get_field('manufacturer_url', $post->ID) ?: null,
		'swatch' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: ($images[0]['url'] ?? null),
		'gallery' => $images,
	];
}
