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

	register_rest_route('cutlist/v1', '/orders/(?P<order_id>\d+)/cutlist', [
		'methods' => 'GET',
		'callback' => 'cutlist_rest_get_order_cutlist',
		'permission_callback' => 'cutlist_rest_order_permission_check',
		'args' => [
			'order_id' => [
				'validate_callback' => function ($value) {
					return is_numeric($value) && (int) $value > 0;
				},
			],
		],
	]);

	register_rest_route('cutlist/v1', '/orders/(?P<order_id>\d+)/cutlist', [
		'methods' => 'POST',
		'callback' => 'cutlist_rest_update_order_cutlist',
		'permission_callback' => 'cutlist_rest_order_permission_check',
		'args' => [
			'order_id' => [
				'validate_callback' => function ($value) {
					return is_numeric($value) && (int) $value > 0;
				},
			],
		],
	]);
});

function cutlist_rest_order_permission_check($request) {
	$order_id = (int) $request['order_id'];
	if (function_exists('cutlist_user_can_access_order')) {
		return cutlist_user_can_access_order($order_id);
	}
	return true;
}

function cutlist_rest_get_order_cutlist($request) {
	$order_id = (int) $request['order_id'];
	if (!$order_id) {
		return new WP_Error('invalid_order', __('Invalid order ID.', 'cutlist-catalogue'), ['status' => 400]);
	}

	$saved_data = function_exists('cutlist_get_saved_order_data') ? cutlist_get_saved_order_data($order_id) : null;
	if (!$saved_data) {
		return new WP_REST_Response([
			'success' => true,
			'order_id' => $order_id,
			'data' => null,
		], 200);
	}

	return new WP_REST_Response([
		'success' => true,
		'order_id' => $order_id,
		'data' => $saved_data,
		'updatedAt' => $saved_data['updatedAt'] ?? null,
	], 200);
}

function cutlist_rest_update_order_cutlist($request) {
	$order_id = (int) $request['order_id'];
	if (!$order_id) {
		return new WP_Error('invalid_order', __('Invalid order ID.', 'cutlist-catalogue'), ['status' => 400]);
	}

	$body = $request->get_json_params();
	if (empty($body)) {
		$body = $request->get_body_params();
	}
	if (isset($body['data']) && is_array($body['data'])) {
		$data = $body['data'];
	} else {
		$data = $body;
	}

	if (!is_array($data)) {
		return new WP_Error('invalid_data', __('Invalid cutlist payload.', 'cutlist-catalogue'), ['status' => 400]);
	}

	$data['orderId'] = $order_id;
	$data['updatedAt'] = current_time('mysql');

	if (function_exists('cutlist_save_order_data')) {
		cutlist_save_order_data($order_id, $data);
	}

	return new WP_REST_Response([
		'success' => true,
		'order_id' => $order_id,
		'message' => __('Cutlist saved successfully.', 'cutlist-catalogue'),
		'updatedAt' => $data['updatedAt'],
	], 200);
}

function cutlist_rest_get_boards(WP_REST_Request $request) {
	$page = max(1, (int) $request->get_param('page'));
	$per_page = (int) $request->get_param('per_page');
	if (!$per_page || $per_page < 1) {
		$per_page = 30;
	}

	$args = [
		'post_type' => 'board',
		'post_status' => 'publish',
		'posts_per_page' => $per_page,
		'paged' => $page,
		'orderby' => 'title',
		'order' => 'ASC',
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

	$search = trim((string) ($request->get_param('search') ?: $request->get_param('q')));
	if ($search !== '') {
		$search_filter = function ($where, $wp_query) use ($search) {
			global $wpdb;
			if ($wp_query->get('cutlist_board_search')) {
				$like = '%' . $wpdb->esc_like($search) . '%';
				$where .= $wpdb->prepare(
					" AND ({$wpdb->posts}.post_title LIKE %s 
					   OR {$wpdb->posts}.ID IN (
					       SELECT post_id FROM {$wpdb->postmeta} 
					       WHERE meta_key IN ('decor_code', 'decor_name', 'core') 
					       AND meta_value LIKE %s
					   ))",
					$like,
					$like
				);
			}
			return $where;
		};
		add_filter('posts_where', $search_filter, 10, 2);
		$args['cutlist_board_search'] = true;
		$args['suppress_filters'] = false;
	}

	$query = new WP_Query($args);

	if ($search !== '' && isset($search_filter)) {
		remove_filter('posts_where', $search_filter, 10);
	}
	$posts = $query->posts;
	$total_items = (int) $query->found_posts;
	$total_pages = $per_page > 0 ? (int) ceil($total_items / $per_page) : 1;

	$items = array_values(array_map('cutlist_format_board', $posts));

	return new WP_REST_Response([
		'success' => true,
		'items' => $items,
		'pagination' => [
			'page' => $page,
			'per_page' => $per_page,
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'has_more' => $page < $total_pages,
		],
	], 200);
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

	$stock_status = function_exists('cutlist_get_edge_tape_stock_status') ? cutlist_get_edge_tape_stock_status($post->ID) : 'instock';
	$stock_avail = function_exists('cutlist_get_edge_tape_available_stock') ? cutlist_get_edge_tape_available_stock($post->ID) : ['is_managed' => false, 'stock' => 999999, 'is_available' => true];
	$is_out_of_stock = ($stock_status === 'outofstock' || !$stock_avail['is_available']);

	return [
		'id' => $post->ID,
		'tape_code' => get_field('tape_code', $post->ID),
		'product_name' => get_field('product_name', $post->ID),
		'size' => get_field('size', $post->ID),
		'unit_price' => (float) get_field('unit_price', $post->ID),
		'edgebanding_unit_price' => (float) (get_field('edgebanding_unit_price', $post->ID) ?: get_field('unit_price', $post->ID)),
		'radius_edge_finish' => ($radius === null ? true : (bool) $radius),
		'square_edge_finish' => ($square === null ? true : (bool) $square),
		'image' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: null,
		'boards' => $boards,
		'stock_units' => (int) $stock_avail['stock'],
		'stock_quantity' => (int) $stock_avail['stock'],
		'stock_status' => $stock_status,
		'is_managed_stock' => (bool) $stock_avail['is_managed'],
		'is_out_of_stock' => $is_out_of_stock,
		'supplier_sku' => (string) get_post_meta($post->ID, 'supplier_sku', true),
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

	$lines = [];
	for ($i = 0; $i < count($characteristics); $i += 2) {
		$pair = [$characteristics[$i]['label'] . ': ' . $characteristics[$i]['value']];
		if (isset($characteristics[$i + 1])) {
			$pair[] = $characteristics[$i + 1]['label'] . ': ' . $characteristics[$i + 1]['value'];
		}
		$lines[] = implode(' | ', $pair);
	}

	$slides = [];
	foreach ($images as $img) {
		$slides[] = [
			'label' => $img['alt'] ?: 'Decor',
			'bg' => "url('" . esc_url_raw($img['url']) . "') center/cover no-repeat",
		];
	}
	if (!$slides) {
		$slides[] = ['label' => 'Decor', 'bg' => '#ccc'];
	}

	$download_file = get_field('downloads', $post->ID);
	$downloads = $download_file ? [[
		'label' => $download_file['title'] ?: $download_file['filename'],
		'url' => $download_file['url'],
	]] : [];

	$decor_code = get_field('decor_code', $post->ID);
	$decor_name = get_field('decor_name', $post->ID);
	$full_sheet_price = (float) get_field('full_sheet_price', $post->ID);
	$cutting_list_price = (float) (get_field('cutting_list_price', $post->ID) ?: $full_sheet_price);
	$additional_cut_price = (float) (get_field('additional_cut_price', $post->ID) ?: 3.46);
	$thickness_mm = (float) get_field('thickness_mm', $post->ID);
	$core = get_field('core', $post->ID) ?: null;

		$machining_excluded = array_values(array_filter(array_map(function ($id) {
			$option = get_post($id);
			return $option ? $option->post_name : null;
		}, get_field('machining_excluded', $post->ID) ?: [])));
		$spray_finishing = (bool) get_field('spray_finishing', $post->ID);
		$grain_match = (bool) get_field('grain_match', $post->ID);

		$stock_status = function_exists('cutlist_get_board_stock_status') ? cutlist_get_board_stock_status($post->ID) : 'instock';
		$stock_avail = function_exists('cutlist_get_board_available_stock') ? cutlist_get_board_available_stock($post->ID) : ['stock' => 999999, 'is_available' => true, 'is_managed' => false];
		$is_out_of_stock = ($stock_status === 'outofstock' || !$stock_avail['is_available']);

		$currency_sym = function_exists('get_woocommerce_currency_symbol') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : '£';
		return [
			'id' => $post->ID,
			'brand' => $brand,
			'collection' => $collection,
			'decor_code' => $decor_code,
			'decor_name' => $decor_name,
			'core' => $core,
			'name' => $decor_name ?: $post->post_title,
			'title' => trim(($brand ? $brand . ' ' : '') . $decor_code),
			'fullCode' => $decor_code,
			'material' => $core ?: '–',
			'length_mm' => (float) get_field('length_mm', $post->ID),
			'width_mm' => (float) get_field('width_mm', $post->ID),
			'length' => (float) get_field('length_mm', $post->ID),
			'width' => (float) get_field('width_mm', $post->ID),
			'thickness_mm' => $thickness_mm,
			'thicknesses' => $thickness_mm ? [$thickness_mm] : [],
			'full_sheet_price' => $full_sheet_price,
			'cutting_list_price' => $cutting_list_price,
			'additional_cut_price' => $additional_cut_price,
			'price_sheet' => ($full_sheet_price > 0) ? $currency_sym . number_format($full_sheet_price, 2) : '–',
			'price_cut' => ($cutting_list_price > 0) ? $currency_sym . number_format($cutting_list_price, 2) : (($full_sheet_price > 0) ? $currency_sym . number_format($full_sheet_price, 2) : '–'),
			'stock_status' => $stock_status,
			'stock_sheets' => $stock_avail['stock'],
			'is_managed_stock' => $stock_avail['is_managed'],
			'is_out_of_stock' => $is_out_of_stock,
			'slides' => $slides,
			'chars' => implode("\n", $lines),
			'machining' => [
				'cut_to_size' => (bool) get_field('machining_cut_to_size', $post->ID),
				'edgebanding' => (bool) get_field('machining_edgebanding', $post->ID),
				'cnc' => (bool) get_field('machining_cnc', $post->ID),
			],
			'machining_excluded' => $machining_excluded,
			'machiningExcluded' => $machining_excluded,
			'spray_finishing' => $spray_finishing,
			'sprayFinishing' => $spray_finishing,
			'grain_match' => $grain_match,
			'grainMatch' => $grain_match,
		'description' => get_field('description', $post->ID),
		'desc' => get_field('description', $post->ID),
		'b_side_description' => get_field('b_side_description', $post->ID),
		'bside' => get_field('b_side_description', $post->ID),
		'characteristics' => $characteristics,
		'downloads' => $downloads,
		'manufacturer_url' => get_field('manufacturer_url', $post->ID) ?: null,
		'swatch' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: ($images[0]['url'] ?? null),
		'gallery' => $images,
	];
}
