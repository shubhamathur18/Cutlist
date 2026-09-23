<?php
/**
 * Shortcode and asset handling for the Hinged Doors catalog page.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns the URL for the Hinged Doors page.
 */
function cutlist_get_hinged_doors_page_url()
{
	$page = get_page_by_path('hinged-doors');
	return $page ? get_permalink($page) : home_url('/hinged-doors/');
}

/**
 * Enqueue dedicated frontend assets for the Hinged Doors page.
 */
function cutlist_enqueue_hinged_doors_assets()
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

	$hd_css_url = plugins_url('assets/css/hinged-doors.css', dirname(__FILE__));
	$hd_css_path = CUTLIST_CATALOGUE_PATH . 'assets/css/hinged-doors.css';
	$hd_css_ver = file_exists($hd_css_path) ? filemtime($hd_css_path) : '1.0.0';

	// Enqueue dedicated Hinged Doors styles
	wp_enqueue_style(
		'hinged-doors-main',
		$hd_css_url,
		array('cutlist-header-tabs-css'),
		$hd_css_ver
	);

	$hd_js_url = plugins_url('assets/js/hinged-doors.js', dirname(__FILE__));
	$hd_js_path = CUTLIST_CATALOGUE_PATH . 'assets/js/hinged-doors.js';
	$hd_js_ver = file_exists($hd_js_path) ? filemtime($hd_js_path) : '1.0.0';

	// Enqueue dedicated Hinged Doors script
	wp_enqueue_script(
		'hinged-doors-main',
		$hd_js_url,
		array(),
		$hd_js_ver,
		true
	);

	wp_localize_script('hinged-doors-main', 'hingedDoorsI18n', array(
		'collapse' => __('Collapse', 'cutlist-catalogue'),
		'expand'   => __('Expand', 'cutlist-catalogue'),
		'quickView' => __('Quick View', 'cutlist-catalogue'),
		'close'    => __('Close', 'cutlist-catalogue'),
	));
}

/**
 * Retrieves all published hinged doors grouped by category term.
 */
function cutlist_get_hinged_doors_catalogue()
{
	$categories = get_terms(array(
		'taxonomy'   => 'door_category',
		'hide_empty' => false,
	));

	$catalogue = array();

	if (!empty($categories) && !is_wp_error($categories)) {
		foreach ($categories as $cat) {
			$posts = get_posts(array(
				'post_type'      => 'hinged_door',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'door_category',
						'field'    => 'term_id',
						'terms'    => $cat->term_id,
					),
				),
			));

			$doors = array();
			foreach ($posts as $post) {
				$doors[] = cutlist_format_door_item($post);
			}

			if (!empty($doors)) {
				$cat_sub = function_exists('get_field') ? get_field('category_subtitle', 'door_category_' . $cat->term_id) : get_term_meta($cat->term_id, 'category_subtitle', true);
				$cat_note = function_exists('get_field') ? get_field('category_notice', 'door_category_' . $cat->term_id) : get_term_meta($cat->term_id, 'category_notice', true);

				$catalogue[] = array(
					'id'       => $cat->slug,
					'name'     => $cat->name,
					'subtitle' => $cat_sub ? $cat_sub : __('Choose a shaker style for your door. All options are made using moisture resistant MDF with a mortise and tenon structure.', 'cutlist-catalogue'),
					'notice'   => $cat_note ? $cat_note : __('All shaker doors are made using moisture resistant MDF with a mortise and tenon structure.', 'cutlist-catalogue'),
					'doors'    => $doors,
				);
			}
		}
	}

	// Fallback if no categories or doors exist yet
	if (empty($catalogue)) {
		$all_posts = get_posts(array(
			'post_type'      => 'hinged_door',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		));

		$doors = array();
		foreach ($all_posts as $post) {
			$doors[] = cutlist_format_door_item($post);
		}

		if (!empty($doors)) {
			$catalogue[] = array(
				'id'       => 'shaker',
				'name'     => __('Shaker', 'cutlist-catalogue'),
				'subtitle' => __('Choose a shaker style for your door. All options are made using moisture resistant MDF with a mortise and tenon structure.', 'cutlist-catalogue'),
				'notice'   => __('All shaker doors are made using moisture resistant MDF with a mortise and tenon structure.', 'cutlist-catalogue'),
				'doors'    => $doors,
			);
		}
	}

	return $catalogue;
}

/**
 * Formats a single door post object into a structured data array with all admin ACF fields.
 */
function cutlist_format_door_item($post)
{
	$id = $post->ID;
	$title = get_the_title($id);
	$subtitle = function_exists('get_field') ? get_field('subtitle', $id) : get_post_meta($id, 'subtitle', true);
	$description = function_exists('get_field') ? get_field('description', $id) : get_post_meta($id, 'description', true);
	$price_from = function_exists('get_field') ? get_field('price_from', $id) : get_post_meta($id, 'price_from', true);
	$panels_count = function_exists('get_field') ? get_field('panels_count', $id) : get_post_meta($id, 'panels_count', true);
	$price_per_hinge = function_exists('get_field') ? get_field('price_per_hinge_hole', $id) : get_post_meta($id, 'price_per_hinge_hole', true);

	// Features
	$feature_1 = function_exists('get_field') ? get_field('feature_1', $id) : get_post_meta($id, 'feature_1', true);
	$feature_2 = function_exists('get_field') ? get_field('feature_2', $id) : get_post_meta($id, 'feature_2', true);

	// Modal Specs
	$spec_material = function_exists('get_field') ? get_field('spec_material', $id) : get_post_meta($id, 'spec_material', true);
	$spec_build = function_exists('get_field') ? get_field('spec_build', $id) : get_post_meta($id, 'spec_build', true);
	$spec_overall_thick = function_exists('get_field') ? get_field('spec_overall_thickness', $id) : get_post_meta($id, 'spec_overall_thickness', true);
	$spec_panel_thick = function_exists('get_field') ? get_field('spec_panel_thickness', $id) : get_post_meta($id, 'spec_panel_thickness', true);
	$spec_finish = function_exists('get_field') ? get_field('spec_finish', $id) : get_post_meta($id, 'spec_finish', true);
	$spec_disclaimer = function_exists('get_field') ? get_field('spec_disclaimer', $id) : get_post_meta($id, 'spec_disclaimer', true);

	// Fallback Featured Image
	$thumbnail_url = get_the_post_thumbnail_url($id, 'large');
	if (!$thumbnail_url) {
		$code_lower = strtolower(sanitize_title($title));
		if (file_exists(CUTLIST_CATALOGUE_PATH . 'assets/images/' . $code_lower . '.jpeg')) {
			$thumbnail_url = plugins_url('assets/images/' . $code_lower . '.jpeg', dirname(__FILE__));
		}
	}

	$extract_img = function ($key, $fallback) use ($id) {
		$img = function_exists('get_field') ? get_field($key, $id) : get_post_meta($id, $key, true);
		if (is_array($img) && !empty($img['url'])) return $img['url'];
		if (is_numeric($img) && $img > 0) {
			$url = wp_get_attachment_image_url($img, 'large');
			if ($url) return $url;
		}
		if (is_string($img) && $img && filter_var($img, FILTER_VALIDATE_URL)) return $img;
		return $fallback;
	};

	$get_meta_num = function ($key, $default) use ($id) {
		$val = function_exists('get_field') ? get_field($key, $id) : get_post_meta($id, $key, true);
		return is_numeric($val) ? intval($val) : $default;
	};

	$get_holes_array = function ($key, $default = array(2, 3, 4, 5)) use ($id) {
		$val = function_exists('get_field') ? get_field($key, $id) : get_post_meta($id, $key, true);
		if (is_array($val) && !empty($val)) {
			return array_values(array_map('intval', $val));
		}
		if (is_string($val) && $val !== '') {
			$parts = explode(',', $val);
			return array_values(array_map('intval', array_map('trim', $parts)));
		}
		return $default;
	};

	$base_price = is_numeric($price_from) ? floatval($price_from) : 41.50;

	// 1. DOOR VARIATION (-D)
	$door_price_val = function_exists('get_field') ? get_field('price_door', $id) : get_post_meta($id, 'price_door', true);
	$door_price = is_numeric($door_price_val) && floatval($door_price_val) > 0 ? floatval($door_price_val) : $base_price;
	$door_min_h = $get_meta_num('door_min_height', 250);
	$door_max_h = $get_meta_num('door_max_height', 2800);
	$door_min_w = $get_meta_num('door_min_width', 200);
	$door_max_w = $get_meta_num('door_max_width', 1200);
	$door_drilling = function_exists('get_field') ? get_field('door_allow_drilling', $id) : get_post_meta($id, 'door_allow_drilling', true);
	$door_drilling = ($door_drilling === '' || $door_drilling === null) ? 1 : intval($door_drilling);
	$door_holes = $get_holes_array('door_drilling_holes', array(2, 3, 4, 5));
	$door_primer = function_exists('get_field') ? get_field('door_price_primer', $id) : get_post_meta($id, 'door_price_primer', true);
	$door_painted = function_exists('get_field') ? get_field('door_price_painted', $id) : get_post_meta($id, 'door_price_painted', true);
	$door_primer_price = is_numeric($door_primer) ? floatval($door_primer) : 18.50;
	$door_painted_price = is_numeric($door_painted) ? floatval($door_painted) : 38.00;

	$door_qv = $extract_img('door_quick_view_image', $extract_img('quick_view_image', $thumbnail_url));
	$door_g1 = $extract_img('door_gallery_1', $extract_img('gallery_image_1', $thumbnail_url));
	$door_g2 = $extract_img('door_gallery_2', $extract_img('gallery_image_2', $thumbnail_url));
	$door_g3 = $extract_img('door_gallery_3', $extract_img('gallery_image_3', $thumbnail_url));

	// 2. END PANEL VARIATION (-EP)
	$ep_price_val = function_exists('get_field') ? get_field('price_end_panel', $id) : get_post_meta($id, 'price_end_panel', true);
	$ep_price = is_numeric($ep_price_val) && floatval($ep_price_val) > 0 ? floatval($ep_price_val) : 48.00;
	$ep_min_h = $get_meta_num('ep_min_height', 300);
	$ep_max_h = $get_meta_num('ep_max_height', 3000);
	$ep_min_w = $get_meta_num('ep_min_width', 200);
	$ep_max_w = $get_meta_num('ep_max_width', 1500);
	$ep_drilling = function_exists('get_field') ? get_field('ep_allow_drilling', $id) : get_post_meta($id, 'ep_allow_drilling', true);
	$ep_drilling = intval($ep_drilling);
	$ep_holes = $get_holes_array('ep_drilling_holes', array(2, 3, 4, 5));
	$ep_primer = function_exists('get_field') ? get_field('ep_price_primer', $id) : get_post_meta($id, 'ep_price_primer', true);
	$ep_painted = function_exists('get_field') ? get_field('ep_price_painted', $id) : get_post_meta($id, 'ep_price_painted', true);
	$ep_primer_price = is_numeric($ep_primer) ? floatval($ep_primer) : 22.50;
	$ep_painted_price = is_numeric($ep_painted) ? floatval($ep_painted) : 45.00;

	$ep_qv = $extract_img('ep_quick_view_image', $door_qv);
	$ep_g1 = $extract_img('ep_gallery_1', $door_g1);
	$ep_g2 = $extract_img('ep_gallery_2', $door_g2);
	$ep_g3 = $extract_img('ep_gallery_3', $door_g3);

	// 3. DRAWER FRONT VARIATION (-DF)
	$df_price_val = function_exists('get_field') ? get_field('price_drawer_front', $id) : get_post_meta($id, 'price_drawer_front', true);
	$df_price = is_numeric($df_price_val) && floatval($df_price_val) > 0 ? floatval($df_price_val) : 28.00;
	$df_min_h = $get_meta_num('df_min_height', 100);
	$df_max_h = $get_meta_num('df_max_height', 450);
	$df_min_w = $get_meta_num('df_min_width', 150);
	$df_max_w = $get_meta_num('df_max_width', 1200);
	$df_drilling = function_exists('get_field') ? get_field('df_allow_drilling', $id) : get_post_meta($id, 'df_allow_drilling', true);
	$df_drilling = intval($df_drilling);
	$df_holes = $get_holes_array('df_drilling_holes', array(2, 3, 4, 5));
	$df_primer = function_exists('get_field') ? get_field('df_price_primer', $id) : get_post_meta($id, 'df_price_primer', true);
	$df_painted = function_exists('get_field') ? get_field('df_price_painted', $id) : get_post_meta($id, 'df_price_painted', true);
	$df_primer_price = is_numeric($df_primer) ? floatval($df_primer) : 12.00;
	$df_painted_price = is_numeric($df_painted) ? floatval($df_painted) : 25.00;

	$df_qv = $extract_img('df_quick_view_image', $door_qv);
	$df_g1 = $extract_img('df_gallery_1', $door_g1);
	$df_g2 = $extract_img('df_gallery_2', $door_g2);
	$df_g3 = $extract_img('df_gallery_3', $door_g3);

	$variations = array(
		'door' => array(
			'key'                => 'door',
			'name'               => __('Door', 'cutlist-catalogue'),
			'code_suffix'        => '-D',
			'code'               => $title . '-D',
			'starting_price'     => $door_price,
			'min_height'         => $door_min_h,
			'max_height'         => $door_max_h,
			'min_width'          => $door_min_w,
			'max_width'          => $door_max_w,
			'drilling_available' => (bool)$door_drilling,
			'drilling_holes'     => $door_holes,
			'finish'             => array(
				'raw'     => 0,
				'primer'  => $door_primer_price,
				'painted' => $door_painted_price,
			),
			'gallery'            => array(
				'quick_view' => $door_qv,
				'image_1'    => $door_g1,
				'image_2'    => $door_g2,
				'image_3'    => $door_g3,
			),
		),
		'end_panel' => array(
			'key'                => 'end_panel',
			'name'               => __('End Panel', 'cutlist-catalogue'),
			'code_suffix'        => '-EP',
			'code'               => $title . '-EP',
			'starting_price'     => $ep_price,
			'min_height'         => $ep_min_h,
			'max_height'         => $ep_max_h,
			'min_width'          => $ep_min_w,
			'max_width'          => $ep_max_w,
			'drilling_available' => (bool)$ep_drilling,
			'drilling_holes'     => $ep_holes,
			'finish'             => array(
				'raw'     => 0,
				'primer'  => $ep_primer_price,
				'painted' => $ep_painted_price,
			),
			'gallery'            => array(
				'quick_view' => $ep_qv,
				'image_1'    => $ep_g1,
				'image_2'    => $ep_g2,
				'image_3'    => $ep_g3,
			),
		),
		'drawer_front' => array(
			'key'                => 'drawer_front',
			'name'               => __('Drawer Front', 'cutlist-catalogue'),
			'code_suffix'        => '-DF',
			'code'               => $title . '-DF',
			'starting_price'     => $df_price,
			'min_height'         => $df_min_h,
			'max_height'         => $df_max_h,
			'min_width'          => $df_min_w,
			'max_width'          => $df_max_w,
			'drilling_available' => (bool)$df_drilling,
			'drilling_holes'     => $df_holes,
			'finish'             => array(
				'raw'     => 0,
				'primer'  => $df_primer_price,
				'painted' => $df_painted_price,
			),
			'gallery'            => array(
				'quick_view' => $df_qv,
				'image_1'    => $df_g1,
				'image_2'    => $df_g2,
				'image_3'    => $df_g3,
			),
		),
	);

	$general = array(
		'title'                  => $title,
		'subtitle'               => $subtitle ? $subtitle : '',
		'description'            => $description ? $description : '',
		'panels_count'           => intval($panels_count),
		'feature_1'              => $feature_1 ? $feature_1 : __('Mortise and tenon structure', 'cutlist-catalogue'),
		'feature_2'              => $feature_2 ? $feature_2 : __('Built using moisture resistant MDF.', 'cutlist-catalogue'),
		'price_from'             => $base_price,
		'price_per_hinge_hole'   => is_numeric($price_per_hinge) ? floatval($price_per_hinge) : 3.00,
		'spec_material'          => $spec_material ? $spec_material : __('Moisture-resistant MDF', 'cutlist-catalogue'),
		'spec_build'             => $spec_build ? $spec_build : __('Mortise and tenon', 'cutlist-catalogue'),
		'spec_overall_thickness' => $spec_overall_thick ? $spec_overall_thick : '21mm',
		'spec_panel_thickness'   => $spec_panel_thick ? $spec_panel_thick : '9mm',
		'spec_finish'            => $spec_finish ? $spec_finish : __('can be supplied raw, primed or painted', 'cutlist-catalogue'),
		'spec_disclaimer'        => $spec_disclaimer ? $spec_disclaimer : __('No fixings are supplied; they are to be purchased separately.', 'cutlist-catalogue'),
		'thumbnail_url'          => $thumbnail_url,
	);

	return array(
		'id'                     => $id,
		'title'                  => $title,
		'subtitle'               => $general['subtitle'],
		'description'            => $general['description'],
		'price_from'             => $base_price,
		'price_html'             => function_exists('wc_price') && $base_price ? wc_price($base_price) : ('&pound;' . number_format($base_price, 2)),
		'panels_count'           => $general['panels_count'],
		'feature_1'              => $general['feature_1'],
		'feature_2'              => $general['feature_2'],
		'image_url'              => $thumbnail_url,
		'quick_view_url'         => $door_qv,
		'gallery_1'              => $door_g1,
		'gallery_2'              => $door_g2,
		'gallery_3'              => $door_g3,
		'spec_material'          => $general['spec_material'],
		'spec_build'             => $general['spec_build'],
		'spec_overall_thickness' => $general['spec_overall_thickness'],
		'spec_panel_thickness'   => $general['spec_panel_thickness'],
		'spec_finish'            => $general['spec_finish'],
		'spec_disclaimer'        => $general['spec_disclaimer'],
		'price_per_hinge_hole'   => $general['price_per_hinge_hole'],
		// Structured format
		'general'                => $general,
		'variations'             => $variations,
		// Flat keys for backwards compatibility
		'price_door'             => $door_price,
		'price_end_panel'        => $ep_price,
		'price_drawer_front'     => $df_price,
		'door_min_h'             => $door_min_h,
		'door_max_h'             => $door_max_h,
		'door_min_w'             => $door_min_w,
		'door_max_w'             => $door_max_w,
		'door_allow_drilling'    => $door_drilling,
		'door_price_primer'      => $door_primer_price,
		'door_price_painted'     => $door_painted_price,
		'ep_min_h'               => $ep_min_h,
		'ep_max_h'               => $ep_max_h,
		'ep_min_w'               => $ep_min_w,
		'ep_max_w'               => $ep_max_w,
		'ep_allow_drilling'      => $ep_drilling,
		'ep_price_primer'        => $ep_primer_price,
		'ep_price_painted'       => $ep_painted_price,
		'df_min_h'               => $df_min_h,
		'df_max_h'               => $df_max_h,
		'df_min_w'               => $df_min_w,
		'df_max_w'               => $df_max_w,
		'df_allow_drilling'      => $df_drilling,
		'df_price_primer'        => $df_primer_price,
		'df_price_painted'       => $df_painted_price,
		'price_white_primer'     => $door_primer_price,
		'price_painted'          => $door_painted_price,
	);
}

/**
 * Main shortcode for Hinged Doors: [hinged_doors] / [hinged_doors_catalogue]
 */
add_shortcode('hinged_doors', function () {
	cutlist_enqueue_hinged_doors_assets();

	$catalogue = cutlist_get_hinged_doors_catalogue();

	ob_start();
	if (file_exists(CUTLIST_CATALOGUE_PATH . 'templates/hinged-doors.php')) {
		include CUTLIST_CATALOGUE_PATH . 'templates/hinged-doors.php';
	}
	return ob_get_clean();
});

add_shortcode('hinged_doors_catalogue', function () {
	return do_shortcode('[hinged_doors]');
});
