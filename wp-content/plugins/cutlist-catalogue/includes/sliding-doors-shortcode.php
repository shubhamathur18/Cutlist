<?php
/**
 * Shortcode, asset handling, and page initialization for Sliding Doors Configurator.
 *
 * Provides:
 * - [sliding_doors] / [sliding_doors_configurator] shortcodes
 * - Dedicated CSS/JS asset enqueuing
 * - Automatic page creation on initialization
 * - Decors & materials feed for sliding door infills
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns the URL for the Sliding Doors page.
 */
function cutlist_get_sliding_doors_page_url()
{
	$page = get_page_by_path('sliding-doors');
	return $page ? get_permalink($page) : home_url('/sliding-doors/');
}

/**
 * Enqueue dedicated frontend assets for the Sliding Doors configurator page.
 */
function cutlist_enqueue_sliding_doors_assets()
{
	$tabs_css_url = plugins_url('assets/css/header-tabs.css', dirname(__FILE__));
	$tabs_css_path = CUTLIST_CATALOGUE_PATH . 'assets/css/header-tabs.css';
	$tabs_css_ver = file_exists($tabs_css_path) ? filemtime($tabs_css_path) : '1.0.0';

	// Enqueue shared Header Tabs styles
	wp_enqueue_style(
		'cutlist-header-tabs-css',
		$tabs_css_url,
		array(),
		$tabs_css_ver
	);

	$sd_css_url = plugins_url('assets/css/sliding-doors.css', dirname(__FILE__));
	$sd_css_path = CUTLIST_CATALOGUE_PATH . 'assets/css/sliding-doors.css';
	$sd_css_ver = file_exists($sd_css_path) ? filemtime($sd_css_path) : '1.0.0';

	// Enqueue dedicated Sliding Doors styles
	wp_enqueue_style(
		'sliding-doors-main',
		$sd_css_url,
		array('cutlist-header-tabs-css'),
		$sd_css_ver
	);

	$sd_js_url = plugins_url('assets/js/sliding-doors.js', dirname(__FILE__));
	$sd_js_path = CUTLIST_CATALOGUE_PATH . 'assets/js/sliding-doors.js';
	$sd_js_ver = file_exists($sd_js_path) ? filemtime($sd_js_path) : '1.0.0';

	// Enqueue dedicated Sliding Doors script
	wp_enqueue_script(
		'sliding-doors-main',
		$sd_js_url,
		array(),
		$sd_js_ver,
		true
	);

	$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '£';

	// Fetch dynamic Sliding Doors configuration from admin CPT/ACF
	$sd_config = function_exists('cutlist_get_sliding_doors_admin_config')
		? cutlist_get_sliding_doors_admin_config()
		: array();

	// Fetch board decors from Board CPT for sliding door infills
	$boards_data = array();
	$board_posts = get_posts(array(
		'post_type'      => 'board',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	));

	foreach ($board_posts as $b) {
		$decor_code = get_post_meta($b->ID, 'decor_code', true) ?: $b->post_title;
		$image_id = get_post_thumbnail_id($b->ID);
		if (!$image_id) {
			$gallery_ids = get_post_meta($b->ID, '_board_gallery_ids', true);
			if (!empty($gallery_ids) && is_array($gallery_ids)) {
				$image_id = (int) $gallery_ids[0];
			}
		}
		if (!$image_id) {
			$image_id = get_post_meta($b->ID, 'decor_image', true);
		}
		$image_url = $image_id ? (is_numeric($image_id) ? wp_get_attachment_image_url($image_id, 'medium') : $image_id) : '';
		
		$brand_terms = get_the_terms($b->ID, 'board_brand');
		$brand = ($brand_terms && !is_wp_error($brand_terms)) ? $brand_terms[0]->name : (get_post_meta($b->ID, 'brand', true) ?: 'Egger');

		$collection_terms = get_the_terms($b->ID, 'board_collection');
		$collection = ($collection_terms && !is_wp_error($collection_terms)) ? $collection_terms[0]->name : (get_post_meta($b->ID, 'collection', true) ?: '');
		
		$price = (float) get_post_meta($b->ID, 'price_full_sheet', true);
		if ($price <= 0) $price = (float) get_post_meta($b->ID, 'sheet_price', true);
		if ($price <= 0) $price = (float) get_field('full_sheet_price', $b->ID);
		if ($price <= 0) $price = 78.50;

		$stock_status = function_exists('cutlist_get_board_stock_status') ? cutlist_get_board_stock_status($b->ID) : 'instock';
		$stock_avail = function_exists('cutlist_get_board_available_stock') ? cutlist_get_board_available_stock($b->ID) : array('stock' => 999999, 'is_available' => true, 'is_managed' => false);
		$is_out_of_stock = ($stock_status === 'outofstock' || !$stock_avail['is_available']);

		$boards_data[] = array(
			'id'           => $b->ID,
			'title'        => $b->post_title,
			'code'         => $decor_code,
			'brand'        => $brand,
			'collection'   => $collection,
			'image'        => $image_url,
			'price'        => $price,
			'stockStatus'  => $stock_status,
			'stock'        => $stock_avail['stock'],
			'isOutOfStock' => $is_out_of_stock,
			'isManaged'    => $stock_avail['is_managed'],
		);
	}

	$brand_terms_all = get_terms(array('taxonomy' => 'board_brand', 'hide_empty' => false));
	$brands_list = array();
	if ($brand_terms_all && !is_wp_error($brand_terms_all)) {
		foreach ($brand_terms_all as $bt) {
			$brands_list[] = $bt->name;
		}
	}

	wp_localize_script('sliding-doors-main', 'slidingDoorsData', array(
		'currencySymbol' => $currency_sym,
		'ajaxUrl'        => admin_url('admin-ajax.php'),
		'nonce'          => wp_create_nonce('cutlist_cart_nonce'),
		'cartUrl'        => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
		'checkoutUrl'    => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
		'frames'         => $sd_config['frames'] ?? array(),
		'designs'        => $sd_config['designs'] ?? array(),
		'glass'          => $sd_config['glass'] ?? array(),
		'boards'         => $boards_data,
		'boardBrands'    => $brands_list,
		'assetsUrl'      => plugins_url('assets/', dirname(__FILE__)),
		'roomImageUrl'   => plugins_url('assets/images/room-reflection.jpg', dirname(__FILE__)),
		'pricing'        => $sd_config['pricing'] ?? array(
			'trackPerMeter'     => 42.00,
			'softClosePair'     => 34.00,
			'mfcBasePerSqm'     => 28.00,
			'minWidth'          => 1000,
			'maxWidth'          => 5500,
			'minHeight'         => 1000,
			'maxHeight'         => 3000,
			'vatRate'           => 0.20,
		),
		'i18n' => array(
			'doors'          => __('doors', 'cutlist-catalogue'),
			'door'           => __('door', 'cutlist-catalogue'),
			'doorWidth'      => __('Door Width', 'cutlist-catalogue'),
			'doorHeight'     => __('Door Height', 'cutlist-catalogue'),
			'openingWidth'   => __('Opening Width', 'cutlist-catalogue'),
			'openingHeight'  => __('Opening Height', 'cutlist-catalogue'),
			'overlap'        => __('Overlap', 'cutlist-catalogue'),
			'addedToBasket'  => __('Sliding Doors configuration added to basket!', 'cutlist-catalogue'),
			'addingToBasket' => __('Adding to basket...', 'cutlist-catalogue'),
		),
	));
}

/**
 * Ensures the WordPress Page for Sliding Doors exists.
 */
function cutlist_ensure_sliding_doors_page()
{
	$page_slug = 'sliding-doors';
	$existing = get_page_by_path($page_slug);

	if (!$existing) {
		$page_id = wp_insert_post(array(
			'post_title'     => 'Sliding Doors',
			'post_name'      => $page_slug,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '[sliding_doors]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		));

		if (!is_wp_error($page_id) && $page_id) {
			// If Astra or standard theme, set clean layout
			update_post_meta($page_id, '_wp_page_template', 'default');
			update_post_meta($page_id, 'site-post-title', 'disabled');
			update_post_meta($page_id, 'site-sidebar-layout', 'no-sidebar');
			update_post_meta($page_id, 'site-content-layout', 'page-builder');
		}
	}
}
add_action('init', 'cutlist_ensure_sliding_doors_page', 20);

/**
 * Main shortcode for Sliding Doors: [sliding_doors]
 */
add_shortcode('sliding_doors', function () {
	cutlist_enqueue_sliding_doors_assets();

	ob_start();
	if (file_exists(CUTLIST_CATALOGUE_PATH . 'templates/sliding-doors.php')) {
		include CUTLIST_CATALOGUE_PATH . 'templates/sliding-doors.php';
	}
	return ob_get_clean();
});

// Shortcode aliases
add_shortcode('sliding_doors_configurator', function () {
	return do_shortcode('[sliding_doors]');
});

add_shortcode('sliding_doors_table', function () {
	return do_shortcode('[sliding_doors]');
});
