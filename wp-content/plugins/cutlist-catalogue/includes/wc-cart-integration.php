<?php
/**
 * WooCommerce Cart & Checkout Integration for Cutlist Catalogue
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Gets or creates a placeholder/virtual WooCommerce product to represent
 * custom cutlist items in the WooCommerce Cart and Checkout.
 */
function cutlist_get_or_create_wc_product()
{
	if (!class_exists('WooCommerce')) {
		return 0;
	}

	$product_id = get_option('cutlist_wc_product_id', 0);
	if ($product_id && get_post_status($product_id) === 'publish') {
		if (get_post_meta($product_id, '_tax_status', true) !== 'taxable') {
			update_post_meta($product_id, '_tax_status', 'taxable');
			update_post_meta($product_id, '_tax_class', '');
		}
		if (get_post_meta($product_id, '_virtual', true) !== 'no') {
			update_post_meta($product_id, '_virtual', 'no');
		}
		return (int) $product_id;
	}

	// Search for existing placeholder product by meta
	$existing = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_key' => '_is_cutlist_placeholder',
		'meta_value' => '1',
		'fields' => 'ids',
	));

	if (!empty($existing)) {
		$product_id = (int) $existing[0];
		update_post_meta($product_id, '_tax_status', 'taxable');
		update_post_meta($product_id, '_tax_class', '');
		update_post_meta($product_id, '_virtual', 'no');
		update_option('cutlist_wc_product_id', $product_id);
		return $product_id;
	}

	// Create placeholder product
	$post_id = wp_insert_post(array(
		'post_title' => __('Cut Panel & Custom Cutting Service', 'cutlist-catalogue'),
		'post_content' => __('Custom panel cut-to-size, edging, machining, and spray finishing order.', 'cutlist-catalogue'),
		'post_status' => 'publish',
		'post_type' => 'product',
	));

	if (is_wp_error($post_id) || !$post_id) {
		return 0;
	}

	update_post_meta($post_id, '_visibility', 'hidden');
	update_post_meta($post_id, '_stock_status', 'instock');
	update_post_meta($post_id, 'total_sales', '0');
	update_post_meta($post_id, '_downloadable', 'no');
	update_post_meta($post_id, '_virtual', 'no');
	update_post_meta($post_id, '_tax_status', 'taxable');
	update_post_meta($post_id, '_tax_class', '');
	update_post_meta($post_id, '_regular_price', '0');
	update_post_meta($post_id, '_sale_price', '');
	update_post_meta($post_id, '_price', '0');
	update_post_meta($post_id, '_sold_individually', 'no');
	update_post_meta($post_id, '_manage_stock', 'no');
	update_post_meta($post_id, '_backorders', 'no');
	update_post_meta($post_id, '_is_cutlist_placeholder', '1');

	update_option('cutlist_wc_product_id', $post_id);
	return $post_id;
}

/**
 * Helper: Resolves the attachment ID and thumbnail URL for a given decor code or name.
 */
function cutlist_get_decor_image($decor_str)
{
	if (empty($decor_str)) {
		return array('id' => 0, 'url' => '');
	}

	$decor_code = trim(explode(' - ', $decor_str)[0]);
	$posts = array();

	// 1. Search board by meta decor_code
	if (!empty($decor_code)) {
		$posts = get_posts(array(
			'post_type' => 'board',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_query' => array(
				array(
					'key' => 'decor_code',
					'value' => $decor_code,
					'compare' => '=',
				),
			),
		));
	}

	// 2. Search board by title
	if (empty($posts) && !empty($decor_code)) {
		$posts = get_posts(array(
			'post_type' => 'board',
			'post_status' => 'publish',
			'numberposts' => 1,
			'title' => $decor_code,
		));
	}

	// 3. Search board by general query
	if (empty($posts) && !empty($decor_code)) {
		$posts = get_posts(array(
			'post_type' => 'board',
			'post_status' => 'publish',
			'numberposts' => 1,
			's' => $decor_code,
		));
	}

	if (!empty($posts)) {
		$board_id = $posts[0]->ID;
		$thumb_id = get_post_thumbnail_id($board_id);

		if (!$thumb_id) {
			$meta_key = defined('CUTLIST_BOARD_GALLERY_META') ? CUTLIST_BOARD_GALLERY_META : '_board_gallery_ids';
			$gallery_ids = get_post_meta($board_id, $meta_key, true);
			if (!empty($gallery_ids) && is_array($gallery_ids)) {
				$thumb_id = (int) $gallery_ids[0];
			}
		}

		if ($thumb_id) {
			return array(
				'id' => (int) $thumb_id,
				'url' => wp_get_attachment_image_url($thumb_id, 'woocommerce_thumbnail') ?: wp_get_attachment_url($thumb_id),
			);
		}
	}

	return array('id' => 0, 'url' => '');
}

/**
 * Helper: Resolves the attachment ID and thumbnail URL for an edge tape.
 */
function cutlist_get_edge_tape_image($tape_str)
{
	if (empty($tape_str)) {
		return array('id' => 0, 'url' => '');
	}

	$tape_code = trim(explode(' / ', $tape_str)[0]);
	$tape_code = trim(explode(' - ', $tape_code)[0]);

	$posts = array();
	if (!empty($tape_code)) {
		$posts = get_posts(array(
			'post_type' => 'edge_tape',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_query' => array(
				array(
					'key' => 'tape_code',
					'value' => $tape_code,
					'compare' => '=',
				),
			),
		));
	}

	if (empty($posts) && !empty($tape_code)) {
		$posts = get_posts(array(
			'post_type' => 'edge_tape',
			'post_status' => 'publish',
			'numberposts' => 1,
			's' => $tape_code,
		));
	}

	if (!empty($posts)) {
		$tape_id = $posts[0]->ID;
		$thumb_id = get_post_thumbnail_id($tape_id);
		if ($thumb_id) {
			return array(
				'id' => (int) $thumb_id,
				'url' => wp_get_attachment_image_url($thumb_id, 'woocommerce_thumbnail') ?: wp_get_attachment_url($thumb_id),
			);
		}
	}

	return array('id' => 0, 'url' => '');
}

/**
 * Helper: Processes and uploads grain matching diagram files submitted with the cutlist.
 * Returns an array of file objects: [ ['name' => ..., 'url' => ..., 'type' => ..., 'is_image' => bool, 'size' => ...] ].
 */
function cutlist_handle_grain_files_upload()
{
	$uploaded_files = array();

	if (!empty($_FILES['grain_files']) && is_array($_FILES['grain_files']['name'])) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		$files = $_FILES['grain_files'];
		$count = count($files['name']);

		for ($i = 0; $i < $count; $i++) {
			if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK && !empty($files['name'][$i])) {
				$file_array = array(
					'name' => $files['name'][$i],
					'type' => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error' => $files['error'][$i],
					'size' => $files['size'][$i],
				);

				$upload_overrides = array(
					'test_form' => false,
					'mimes' => array(
						'jpg|jpeg|jpe' => 'image/jpeg',
						'gif' => 'image/gif',
						'png' => 'image/png',
						'webp' => 'image/webp',
						'pdf' => 'application/pdf',
						'dxf' => 'application/dxf',
						'dwg' => 'image/vnd.dwg',
					),
				);

				$movefile = wp_handle_upload($file_array, $upload_overrides);

				if ($movefile && !isset($movefile['error'])) {
					$url = $movefile['url'];
					$type = $movefile['type'];
					$is_image = (strpos($type, 'image/') === 0) || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url);
					$uploaded_files[] = array(
						'name' => sanitize_file_name($files['name'][$i]),
						'url' => esc_url_raw($url),
						'type' => sanitize_text_field($type),
						'is_image' => (bool) $is_image,
						'size' => (int) $files['size'][$i],
					);
				}
			}
		}
	}
	return $uploaded_files;
}

/**
 * Helper: Formats raw machining items into the 3 popup summary categories:
 * - Panel shaping summary (e.g., Angled cut on L1-W1: 200mm L1 and 769mm W1)
 * - Surface shaping summary (e.g., J handle cutting on A side along L1 edge<br>35mm wide and 1000mm long)
 * - Drilling summary (e.g., Hinge hole drilling on A side<br>2 holes at 100mm, 900mm)
 */
function cutlist_format_machining_categories($machining_items, $panel_len = 0, $panel_width = 0)
{
	$panel_shaping = array();
	$surface_shaping = array();
	$drilling = array();

	if (empty($machining_items) || !is_array($machining_items)) {
		return array('panel' => '', 'surface' => '', 'drilling' => '');
	}

	foreach ($machining_items as $m) {
		if (!is_array($m))
			continue;
		$beh = $m['behaviour'] ?? $m['option'] ?? '';
		$type = $m['type'] ?? $m['kind'] ?? '';
		$side = $m['side'] ?? '';
		$detail = $m['detail'] ?? '';

		if ($beh === 'angled-cut') {
			$corner = $m['corner'] ?? $side ?: 'L1-W1';
			$corner = str_replace('on ', '', $corner);
			$parts = explode('-', $corner);
			$e1 = $parts[0] ?? 'L1';
			$e2 = $parts[1] ?? 'W1';
			$offH = $m['offsetH'] ?? '100';
			$offV = $m['offsetV'] ?? '100';
			if (empty($detail)) {
				$detail = $offH . 'mm ' . $e1 . ' and ' . $offV . 'mm ' . $e2;
			}
			$panel_shaping[] = '<div class="panel-summary-machining-item">Angled cut on ' . esc_html($corner) . ': ' . esc_html($detail) . '</div>';
		} elseif ($beh === 'j-handle') {
			$edge = $m['edge'] ?? 'L1';
			$view = ($m['view'] ?? 'A') === 'B' ? 'B side' : 'A side';
			$label = $m['label'] ?? 'J handle';
			if (empty($detail)) {
				$w = round((float) ($m['width'] ?? 35));
				$len = round((float) ($m['length'] ?? ($panel_len ?: 1000)));
				$detail = $w . 'mm wide and ' . $len . 'mm long';
			}
			$surface_shaping[] = '<div class="panel-summary-machining-item">' . esc_html($label) . ' cutting on ' . esc_html($view . ' along ' . $edge . ' edge') . '<br>' . esc_html($detail) . '</div>';
		} elseif ($beh === 'groove') {
			$view = ($m['view'] ?? 'A') === 'B' ? 'B side' : 'A side';
			if (empty($detail)) {
				$w = $m['width'] ?? '8';
				$d = $m['depth'] ?? '10';
				$len = $m['length'] ?? ($panel_len ? round((float) $panel_len) . 'mm' : '2100mm');
				$detail = $w . 'mm wide, ' . $d . 'mm deep and ' . $len . ' long';
			}
			$surface_shaping[] = '<div class="panel-summary-machining-item">Groove cutting on ' . esc_html($view) . '<br>' . esc_html($detail) . '</div>';
		} elseif ($beh === 'hinge-holes') {
			$view = ($m['view'] ?? 'A') === 'B' ? 'B side' : 'A side';
			if (empty($detail)) {
				$holes = $m['holes'] ?? (isset($m['positions']) && is_array($m['positions']) ? count($m['positions']) : 2);
				$posStr = isset($m['positions']) && is_array($m['positions']) ? ' at ' . implode(', ', array_map('round', $m['positions'])) . 'mm' : '';
				$detail = $holes . ' hole' . ($holes == 1 ? '' : 's') . $posStr;
			}
			$drilling[] = '<div class="panel-summary-machining-item">Hinge hole drilling on ' . esc_html($view) . '<br>' . esc_html($detail) . '</div>';
		} elseif ($beh === 'shelf-holes') {
			$view = ($m['view'] ?? 'A') === 'A' ? 'A side' : (($m['view'] ?? 'A') === 'AB' ? 'A & B side' : 'B side');
			if (empty($detail)) {
				$clusters = $m['clusters'] ?? 2;
				$positions = $m['positions'] ?? 4;
				$detail = '7.5mm Ø diameter hole x ' . $clusters . ' cluster' . ($clusters == 1 ? '' : 's') . ' with ' . $positions . ' position' . ($positions == 1 ? '' : 's');
			}
			$drilling[] = '<div class="panel-summary-machining-item">Adjustable shelf hole drilling on ' . esc_html($view) . '<br>' . esc_html($detail) . '</div>';
		} elseif (!empty($type) || !empty($m['option'])) {
			$optName = $type ?: ($m['option'] ?? 'Machining');
			$optSide = $side ? ' on ' . $side : '';
			$surface_shaping[] = '<div class="panel-summary-machining-item">' . esc_html($optName . $optSide) . ($detail ? '<br>' . esc_html($detail) : '') . '</div>';
		}
	}

	return array(
		'panel' => implode('', $panel_shaping),
		'surface' => implode('', $surface_shaping),
		'drilling' => implode('', $drilling)
	);
}

/**
 * AJAX Handler: Syncs/adds user-selected cutlist items to the WooCommerce Cart session.
 */
function cutlist_ajax_add_to_cart()
{
	check_ajax_referer('cutlist_cart_nonce', 'nonce');

	if (!function_exists('WC') || !WC()->cart) {
		wp_send_json_error(array('message' => __('WooCommerce is not active.', 'cutlist-catalogue')));
	}

	$data = null;
	if (!empty($_POST['data'])) {
		$data = json_decode(wp_unslash($_POST['data']), true);
	}
	if (!$data) {
		$json_raw = file_get_contents('php://input');
		if ($json_raw) {
			$data = json_decode($json_raw, true);
		}
	}

	if (!$data || !is_array($data)) {
		wp_send_json_error(array('message' => __('Invalid data submitted.', 'cutlist-catalogue')));
	}

	$product_id = cutlist_get_or_create_wc_product();
	if (!$product_id) {
		wp_send_json_error(array('message' => __('Could not initialize WooCommerce product.', 'cutlist-catalogue')));
	}

	// Process uploaded grain matching files and info
	$uploaded_grain_files = cutlist_handle_grain_files_upload();
	$grain_info = !empty($data['grainInfo']) ? sanitize_textarea_field($data['grainInfo']) : '';

	// If no new files were uploaded in this request, preserve existing files from session
	if (empty($uploaded_grain_files) && function_exists('WC') && WC()->session) {
		$existing_session_files = WC()->session->get('cutlist_grain_files', array());
		if (!empty($existing_session_files) && is_array($existing_session_files)) {
			$uploaded_grain_files = $existing_session_files;
		}
	}
	if (empty($grain_info) && function_exists('WC') && WC()->session) {
		$existing_info = WC()->session->get('cutlist_grain_info', '');
		if (!empty($existing_info)) {
			$grain_info = $existing_info;
		}
	}

	// Save in WooCommerce session
	if (function_exists('WC') && WC()->session) {
		WC()->session->set('cutlist_grain_files', $uploaded_grain_files);
		WC()->session->set('cutlist_grain_info', $grain_info);
	}

	// Optionally clear previous cutlist items from cart if requested
	if (!empty($data['clear_cart'])) {
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			if (isset($cart_item['cutlist_unique_key'])) {
				WC()->cart->remove_cart_item($cart_item_key);
			}
		}
	}

	$added_count = 0;

	// Validate incoming items against workshop board stock (Strict Stock Control)
	$incoming_full_sheets = array();
	$incoming_cut_panels = array();

	if (!empty($data['fullSheetItems']) && is_array($data['fullSheetItems'])) {
		foreach ($data['fullSheetItems'] as $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$decor = !empty($item['decor']) ? sanitize_text_field($item['decor']) : '';
			$decor_code = !empty($item['decorCode']) ? sanitize_text_field($item['decorCode']) : $decor;
			$board_id = function_exists('cutlist_get_board_by_decor') ? (cutlist_get_board_by_decor($decor_code) ?: cutlist_get_board_by_decor($decor)) : 0;
			if ($board_id) {
				if (!isset($incoming_full_sheets[$board_id])) $incoming_full_sheets[$board_id] = 0;
				$incoming_full_sheets[$board_id] += $qty;
			}
		}
	}

	if (!empty($data['cutItems']) && is_array($data['cutItems'])) {
		foreach ($data['cutItems'] as $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$decor = !empty($item['decor']) ? sanitize_text_field($item['decor']) : '';
			$decor_code = !empty($item['decorCode']) ? sanitize_text_field($item['decorCode']) : $decor;
			$board_id = function_exists('cutlist_get_board_by_decor') ? (cutlist_get_board_by_decor($decor_code) ?: cutlist_get_board_by_decor($decor)) : 0;
			if ($board_id) {
				if (!isset($incoming_cut_panels[$board_id])) $incoming_cut_panels[$board_id] = array();
				$incoming_cut_panels[$board_id][] = array(
					'length' => isset($item['length']) ? (float) $item['length'] : 0,
					'width'  => isset($item['width']) ? (float) $item['width'] : 0,
					'qty'    => $qty,
				);
			}
		}
	}

	$all_incoming_boards = array_unique(array_merge(array_keys($incoming_full_sheets), array_keys($incoming_cut_panels)));
	foreach ($all_incoming_boards as $b_id) {
		if (function_exists('cutlist_get_board_available_stock')) {
			$avail = cutlist_get_board_available_stock($b_id);
			if ($avail['is_managed']) {
				$needed = 0;
				if (isset($incoming_full_sheets[$b_id])) {
					$needed += $incoming_full_sheets[$b_id];
				}
				if (isset($incoming_cut_panels[$b_id]) && function_exists('cutlist_calculate_cutlist_sheet_requirement')) {
					$calc = cutlist_calculate_cutlist_sheet_requirement($incoming_cut_panels[$b_id], $b_id);
					$needed += $calc['sheets_consumed'];
				}

				if ($needed > $avail['stock']) {
					$b_name = get_the_title($b_id);
					if ($avail['stock'] <= 0) {
						wp_send_json_error(array(
							'message' => sprintf(__('Sorry, "%s" is currently out of stock (0 full sheets available in workshop).', 'cutlist-catalogue'), $b_name),
						));
						return;
					} else {
						wp_send_json_error(array(
							'message' => sprintf(__('Your order requires %d sheet(s) of "%s", but only %d sheet(s) are currently in stock.', 'cutlist-catalogue'), $needed, $b_name, $avail['stock']),
						));
						return;
					}
				}
			}
		}
	}

	// 1. Cut Panels
	if (!empty($data['cutItems']) && is_array($data['cutItems'])) {
		foreach ($data['cutItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$decor = !empty($item['decor']) ? sanitize_text_field($item['decor']) : __('Custom Panel', 'cutlist-catalogue');
			$thick = !empty($item['thick']) ? sanitize_text_field($item['thick']) : '';
			$length = !empty($item['length']) ? sanitize_text_field($item['length']) : '';
			$width = !empty($item['width']) ? sanitize_text_field($item['width']) : '';
			$desc = !empty($item['description']) ? sanitize_text_field($item['description']) : '';
			$panel_info = !empty($item['panelInfo']) ? sanitize_text_field($item['panelInfo']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

			// Resolve decor featured image
			$decor_img = cutlist_get_decor_image(!empty($item['decorCode']) ? $item['decorCode'] : $decor);
			$image_url = !empty($item['image']) ? esc_url_raw($item['image']) : $decor_img['url'];

			// Build edging summary
			$edges = array();
			if (!empty($item['edgeL1']))
				$edges[] = 'L1: ' . sanitize_text_field($item['edgeL1']);
			if (!empty($item['edgeL2']))
				$edges[] = 'L2: ' . sanitize_text_field($item['edgeL2']);
			if (!empty($item['edgeW1']))
				$edges[] = 'W1: ' . sanitize_text_field($item['edgeW1']);
			if (!empty($item['edgeW2']))
				$edges[] = 'W2: ' . sanitize_text_field($item['edgeW2']);
			$edge_summary = implode(', ', $edges);

			// Build machining summary
			$machining_summary = '';
			if (!empty($item['machining']) && is_array($item['machining'])) {
				$m_labels = array();
				foreach ($item['machining'] as $m) {
					$m_labels[] = sanitize_text_field(is_array($m) ? ($m['label'] ?? $m['option'] ?? 'Machining') : $m);
				}
				$machining_summary = implode(', ', $m_labels);
			} elseif (!empty($item['machining_summary'])) {
				$machining_summary = sanitize_text_field($item['machining_summary']);
			}

			// Extract 3 shaping categories
			$panel_shaping = !empty($item['panelShaping']) ? wp_kses_post($item['panelShaping']) : '';
			$surface_shaping = !empty($item['surfaceShaping']) ? wp_kses_post($item['surfaceShaping']) : '';
			$drilling = !empty($item['drilling']) ? wp_kses_post($item['drilling']) : '';

			// Fallback: If formatted strings are empty but machining array exists, build them dynamically
			if (empty($panel_shaping) && empty($surface_shaping) && empty($drilling) && !empty($item['machining']) && is_array($item['machining'])) {
				$formatted_m = cutlist_format_machining_categories($item['machining'], (float) $length, (float) $width);
				$panel_shaping = $formatted_m['panel'];
				$surface_shaping = $formatted_m['surface'];
				$drilling = $formatted_m['drilling'];
			}

			// Build spray summary
			$spray_summary = '';
			if (!empty($item['spray']) && is_array($item['spray'])) {
				$s_opt = sanitize_text_field($item['spray']['option_name'] ?? $item['spray']['option'] ?? '');
				$s_sides = array();
				if (!empty($item['spray']['aSide']))
					$s_sides[] = 'A-Side';
				if (!empty($item['spray']['bSide'])) {
					if (!empty($item['spray']['bSideSprayType']) && $item['spray']['bSideSprayType'] === 'return') {
						$checked_edges = array();
						if (!empty($item['spray']['bSideReturnEdges']) && is_array($item['spray']['bSideReturnEdges'])) {
							foreach ($item['spray']['bSideReturnEdges'] as $e_key => $is_on) {
								if (!empty($is_on))
									$checked_edges[] = sanitize_text_field($e_key);
							}
						}
						$b_type = 'B-Side (100mm return' . ($checked_edges ? ' on ' . implode(', ', $checked_edges) : '') . ')';
					} else {
						$b_type = 'B-Side';
					}
					$s_sides[] = $b_type;
				}
				$spray_summary = $s_opt . ($s_sides ? ' (' . implode(' & ', $s_sides) . ')' : '');
			} elseif (!empty($item['spray_summary'])) {
				$spray_summary = sanitize_text_field($item['spray_summary']);
			}

			// Build grain matching summary
			$grain_summary = '';
			$panel_grain_files = array();
			$panel_grain_info = '';
			if (!empty($item['grainMatch'])) {
				$grain_summary = __('Yes (GRN-MTCH)', 'cutlist-catalogue');
				$panel_grain_files = $uploaded_grain_files;
				$panel_grain_info = $grain_info;
			}

			// Extra cuts summary
			$extra_cuts_summary = !empty($item['extraCutSummary']) ? sanitize_text_field($item['extraCutSummary']) : '';

			// Selected offcuts summary (attached to main cut panel)
			$selected_offcuts_summary = !empty($item['selectedOffcuts']) ? sanitize_text_field($item['selectedOffcuts']) : '';

			$section = !empty($item['section']) ? sanitize_text_field($item['section']) : '';

			$title = !empty($section)
				? sprintf(__('Cut Panel [%s]: %s (%s)', 'cutlist-catalogue'), $section, $decor, $thick ? $thick . 'mm' : 'Custom')
				: sprintf(__('Cut Panel: %s (%s)', 'cutlist-catalogue'), $decor, $thick ? $thick . 'mm' : 'Custom');

			$cart_item_data = array(
				'cutlist_type' => __('Cut Panel', 'cutlist-catalogue'),
				'cutlist_title' => $title,
				'cutlist_section' => $section,
				'cutlist_decor' => $decor,
				'cutlist_thick' => $thick ? $thick . 'mm' : '',
				'cutlist_length' => $length,
				'cutlist_width' => $width,
				'cutlist_description' => $desc,
				'cutlist_panel_info' => $panel_info,
				'cutlist_edges' => $edge_summary,
				'cutlist_edging_l1' => !empty($item['edgeL1']) ? sanitize_text_field($item['edgeL1']) : '',
				'cutlist_edging_l2' => !empty($item['edgeL2']) ? sanitize_text_field($item['edgeL2']) : '',
				'cutlist_edging_w1' => !empty($item['edgeW1']) ? sanitize_text_field($item['edgeW1']) : '',
				'cutlist_edging_w2' => !empty($item['edgeW2']) ? sanitize_text_field($item['edgeW2']) : '',
				'cutlist_machining' => $machining_summary,
				'cutlist_panel_shaping' => $panel_shaping,
				'cutlist_surface_shaping' => $surface_shaping,
				'cutlist_drilling' => $drilling,
				'cutlist_spray' => $spray_summary,
				'cutlist_grain' => $grain_summary,
				'cutlist_grain_files' => $panel_grain_files,
				'cutlist_grain_info' => $panel_grain_info,
				'cutlist_extra_cuts' => $extra_cuts_summary,
				'cutlist_selected_offcuts' => $selected_offcuts_summary,
				'cutlist_unit_price' => $price,
				'cutlist_image_id' => $decor_img['id'],
				'cutlist_image_url' => $image_url,
				'cutlist_unique_key' => 'cut_' . md5($decor . $thick . $length . $width . $desc . $panel_info . $section . $edge_summary . $machining_summary . $panel_shaping . $surface_shaping . $drilling . $spray_summary . $grain_summary . json_encode($panel_grain_files) . $panel_grain_info . $extra_cuts_summary . $selected_offcuts_summary . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 2. Full Sheets
	if (!empty($data['fullSheetItems']) && is_array($data['fullSheetItems'])) {
		foreach ($data['fullSheetItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$decor = !empty($item['decor']) ? sanitize_text_field($item['decor']) : __('Full Sheet', 'cutlist-catalogue');
			$thick = !empty($item['thick']) ? sanitize_text_field($item['thick']) : '';
			$dims = !empty($item['length']) && !empty($item['width']) ? sanitize_text_field($item['length'] . ' x ' . $item['width'] . ' mm') : '';
			$brand = !empty($item['brand']) ? sanitize_text_field($item['brand']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

			// Resolve decor featured image
			$decor_img = cutlist_get_decor_image(!empty($item['decorCode']) ? $item['decorCode'] : $decor);
			$image_url = !empty($item['image']) ? esc_url_raw($item['image']) : $decor_img['url'];

			$cart_item_data = array(
				'cutlist_type' => __('Full Sheet', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Full Sheet: %s %s', 'cutlist-catalogue'), $brand, $decor),
				'cutlist_decor' => $decor,
				'cutlist_thick' => $thick ? $thick . 'mm' : '',
				'cutlist_dimensions' => $dims,
				'cutlist_brand' => $brand,
				'cutlist_unit_price' => $price,
				'cutlist_image_id' => $decor_img['id'],
				'cutlist_image_url' => $image_url,
				'cutlist_unique_key' => 'fs_' . md5($decor . $thick . $dims . $brand . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 3. Edging Tapes
	if (!empty($data['edgingTapeItems']) && is_array($data['edgingTapeItems'])) {
		foreach ($data['edgingTapeItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(5, (int) $item['qty']) : 5;
			$code = !empty($item['code']) ? sanitize_text_field($item['code']) : '';
			$name = !empty($item['name']) ? sanitize_text_field($item['name']) : __('Edging Tape', 'cutlist-catalogue');
			$size = !empty($item['size']) ? sanitize_text_field($item['size']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

			// Fallback: Resolve price from edge_tape post if missing or 0 from frontend
			if ($price <= 0 && !empty($code)) {
				$tape_code = trim(explode(' / ', $code)[0]);
				$tape_code = trim(explode(' - ', $tape_code)[0]);
				$t_posts = get_posts(array(
					'post_type' => 'edge_tape',
					'post_status' => 'publish',
					'numberposts' => 1,
					'meta_query' => array(
						array(
							'key' => 'tape_code',
							'value' => $tape_code,
							'compare' => '=',
						),
					),
				));
				if (!empty($t_posts)) {
					$db_price = (float) get_field('unit_price', $t_posts[0]->ID);
					if ($db_price > 0) {
						$price = $db_price;
					}
				}
			}

			// Resolve edge tape image
			$tape_img = cutlist_get_edge_tape_image($code . ' ' . $name);
			$image_url = !empty($item['image']) ? esc_url_raw($item['image']) : $tape_img['url'];

			$cart_item_data = array(
				'cutlist_type' => __('Edging Tape', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Edging Tape: %s (%s)', 'cutlist-catalogue'), $name, $code),
				'cutlist_code' => $code,
				'cutlist_name' => $name,
				'cutlist_dimensions' => $size,
				'cutlist_unit_price' => $price,
				'cutlist_min_qty' => 5,
				'cutlist_image_id' => $tape_img['id'],
				'cutlist_image_url' => $image_url,
				'cutlist_unique_key' => 'et_' . md5($code . $name . $size . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 4. Standalone Service Surcharges (e.g. Spray Finishing Minimum Order Charge SPRAY-MIN)
	$spray_min_added = false;
	if (!empty($data['machiningItems']) && is_array($data['machiningItems'])) {
		foreach ($data['machiningItems'] as $idx => $m_item) {
			$code = !empty($m_item['code']) ? sanitize_text_field($m_item['code']) : '';
			$label = !empty($m_item['label']) ? sanitize_text_field($m_item['label']) : '';
			if ($code === 'SPRAY-MIN' || (!empty($m_item['slug']) && $m_item['slug'] === 'spray_min_charge') || stripos($label, 'Spray finishing minimum') !== false) {
				$qty = isset($m_item['qty']) ? max(1, (int) $m_item['qty']) : 1;
				$min_val_str = function_exists('wc_price') ? wp_strip_all_tags(wc_price(330)) : '£330.00';
				$desc = !empty($m_item['description']) ? sanitize_text_field($m_item['description']) : sprintf(__('Charge added to reach minimum order value of %s for spray finishing', 'cutlist-catalogue'), $min_val_str);
				$price = isset($m_item['unitPrice']) ? max(0, (float) $m_item['unitPrice']) : 0;

				if ($price > 0) {
					$cart_item_data = array(
						'cutlist_type' => __('Additional Service', 'cutlist-catalogue'),
						'cutlist_title' => sprintf(__('Service: %s (%s)', 'cutlist-catalogue'), $label ?: __('Spray finishing minimum order charge', 'cutlist-catalogue'), 'SPRAY-MIN'),
						'cutlist_code' => 'SPRAY-MIN',
						'cutlist_name' => $label ?: __('Spray finishing minimum order charge', 'cutlist-catalogue'),
						'cutlist_description' => $desc,
						'cutlist_unit_price' => $price,
						'cutlist_unique_key' => 'srv_spray_min_' . md5($price . microtime(true)),
					);

					WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
					$added_count++;
					$spray_min_added = true;
				}
			}
		}
	}

	// Fallback: If sprayMinSurcharge is passed directly and not yet added
	if (!$spray_min_added && !empty($data['sprayMinSurcharge']) && (float) $data['sprayMinSurcharge'] > 0) {
		$price = (float) $data['sprayMinSurcharge'];
		$min_val_str = function_exists('wc_price') ? wp_strip_all_tags(wc_price(330)) : '£330.00';
		$cart_item_data = array(
			'cutlist_type' => __('Additional Service', 'cutlist-catalogue'),
			'cutlist_title' => __('Service: Spray finishing minimum order charge (SPRAY-MIN)', 'cutlist-catalogue'),
			'cutlist_code' => 'SPRAY-MIN',
			'cutlist_name' => __('Spray finishing minimum order charge', 'cutlist-catalogue'),
			'cutlist_description' => sprintf(__('Charge added to reach minimum order value of %s for spray finishing', 'cutlist-catalogue'), $min_val_str),
			'cutlist_unit_price' => $price,
			'cutlist_unique_key' => 'srv_spray_min_' . md5($price . microtime(true)),
		);

		WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
		$added_count++;
	}

	// 5. Sliding Doors
	if (!empty($data['slidingDoorsItems']) && is_array($data['slidingDoorsItems'])) {
		foreach ($data['slidingDoorsItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$title = !empty($item['title']) ? sanitize_text_field($item['title']) : __('Custom Sliding Wardrobe Doors', 'cutlist-catalogue');
			$opening_w = !empty($item['openingWidth']) ? sanitize_text_field($item['openingWidth']) : '';
			$opening_h = !empty($item['openingHeight']) ? sanitize_text_field($item['openingHeight']) : '';
			$door_count = !empty($item['doorCount']) ? sanitize_text_field($item['doorCount']) : '';
			$door_w = !empty($item['doorWidth']) ? sanitize_text_field($item['doorWidth']) : '';
			$door_h = !empty($item['doorHeight']) ? sanitize_text_field($item['doorHeight']) : '';
			$frame_finish = !empty($item['frameFinish']) ? sanitize_text_field($item['frameFinish']) : '';
			$track_type = !empty($item['trackType']) ? sanitize_text_field($item['trackType']) : '';
			$soft_close = !empty($item['softClose']) ? sanitize_text_field($item['softClose']) : '';
			$door_details = !empty($item['doorDetails']) ? sanitize_text_field($item['doorDetails']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;
			$image_url = !empty($item['image']) ? esc_url_raw($item['image']) : '';

			$cart_item_data = array(
				'cutlist_type'             => __('Sliding Doors', 'cutlist-catalogue'),
				'cutlist_title'            => $title,
				'cutlist_opening_size'     => ($opening_w && $opening_h) ? ($opening_w . ' x ' . $opening_h . ' mm') : '',
				'cutlist_door_count'       => $door_count ? ($door_count . ' Doors') : '',
				'cutlist_door_size'        => ($door_w && $door_h) ? ($door_w . ' x ' . $door_h . ' mm (each)') : '',
				'cutlist_frame_finish'     => $frame_finish,
				'cutlist_track_type'       => $track_type,
				'cutlist_soft_close'       => $soft_close,
				'cutlist_door_details'     => $door_details,
				'cutlist_unit_price'       => $price,
				'cutlist_image_url'        => $image_url,
				'cutlist_unique_key'       => 'sd_' . md5($title . $opening_w . $opening_h . $door_count . $frame_finish . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	WC()->cart->calculate_totals();

	wp_send_json_success(array(
		'message' => __('Items successfully added to cart.', 'cutlist-catalogue'),
		'added_count' => $added_count,
		'cart_count' => WC()->cart->get_cart_contents_count(),
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_url' => wc_get_cart_url(),
		'checkout_url' => wc_get_checkout_url(),
		'grain_files' => $uploaded_grain_files,
		'grain_info' => $grain_info,
	));
}
add_action('wp_ajax_cutlist_add_to_cart', 'cutlist_ajax_add_to_cart');
add_action('wp_ajax_nopriv_cutlist_add_to_cart', 'cutlist_ajax_add_to_cart');

/**
 * Returns current live WooCommerce cart total and items count via AJAX.
 */
function cutlist_ajax_get_cart_total()
{
	$total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : (function_exists('wc_price') ? wc_price(0) : '&pound;0.00');
	$count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
	wp_send_json_success(array(
		'cart_total' => $total,
		'cart_count' => $count,
	));
}
add_action('wp_ajax_cutlist_get_cart_total', 'cutlist_ajax_get_cart_total');
add_action('wp_ajax_nopriv_cutlist_get_cart_total', 'cutlist_ajax_get_cart_total');

/**
 * Ensures tab basket price updates automatically in all WooCommerce AJAX cart/drawer actions (fragments).
 */
function cutlist_add_cart_fragments($fragments)
{
	$total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : (function_exists('wc_price') ? wc_price(0) : '&pound;0.00');
	$fragments['.tab-basket-price'] = '<span class="tab-basket-price">' . $total . '</span>';
	return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'cutlist_add_cart_fragments');

/**
 * Applies custom cutlist prices, product names, and image IDs to cart items in WooCommerce.
 * This ensures both standard templates and WooCommerce Blocks (Store API) receive the custom
 * board image and custom line-item prices.
 */
function cutlist_apply_custom_cart_item_data($cart)
{
	if (is_admin() && !defined('DOING_AJAX')) {
		return;
	}

	foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		if (isset($cart_item['cutlist_unit_price'])) {
			$cart_item['data']->set_price((float) $cart_item['cutlist_unit_price']);
			$cart_item['data']->set_regular_price((float) $cart_item['cutlist_unit_price']);
		}
		if (!empty($cart_item['cutlist_image_id'])) {
			$cart_item['data']->set_image_id((int) $cart_item['cutlist_image_id']);
		}
		if (!empty($cart_item['cutlist_title'])) {
			$cart_item['data']->set_name($cart_item['cutlist_title']);
		}
	}
}
add_action('woocommerce_before_calculate_totals', 'cutlist_apply_custom_cart_item_data', 10, 1);
add_action('woocommerce_cart_loaded_from_session', 'cutlist_apply_custom_cart_item_data', 10, 1);

/**
 * Ensures custom cutlist prices, titles, and images are applied immediately
 * when individual cart items are reconstructed from session.
 */
function cutlist_get_cart_item_from_session($cart_item, $values, $key)
{
	if (isset($values['cutlist_unit_price'])) {
		$cart_item['cutlist_unit_price'] = $values['cutlist_unit_price'];
		if (isset($cart_item['data']) && is_object($cart_item['data'])) {
			$cart_item['data']->set_price((float) $values['cutlist_unit_price']);
			$cart_item['data']->set_regular_price((float) $values['cutlist_unit_price']);
		}
	}
	if (!empty($values['cutlist_title']) && isset($cart_item['data']) && is_object($cart_item['data'])) {
		$cart_item['data']->set_name($values['cutlist_title']);
	}
	if (!empty($values['cutlist_image_id']) && isset($cart_item['data']) && is_object($cart_item['data'])) {
		$cart_item['data']->set_image_id((int) $values['cutlist_image_id']);
	}
	return $cart_item;
}
add_filter('woocommerce_get_cart_item_from_session', 'cutlist_get_cart_item_from_session', 20, 3);

/**
 * Customizes the cart item thumbnail in WooCommerce Cart and Checkout (Classic & mini-cart).
 */
function cutlist_custom_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key)
{
	if (!empty($cart_item['cutlist_image_id'])) {
		$img = wp_get_attachment_image((int) $cart_item['cutlist_image_id'], 'woocommerce_thumbnail', false, array(
			'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'style' => 'width:64px; height:64px; object-fit:cover; border-radius:6px; display:inline-block;',
		));
		if ($img) {
			return $img;
		}
	}
	if (!empty($cart_item['cutlist_image_url'])) {
		return sprintf(
			'<img src="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="%s" style="width:64px; height:64px; object-fit:cover; border-radius:6px; display:inline-block;" />',
			esc_url($cart_item['cutlist_image_url']),
			esc_attr($cart_item['cutlist_decor'] ?? $cart_item['cutlist_title'] ?? '')
		);
	}
	return $thumbnail;
}
add_filter('woocommerce_cart_item_thumbnail', 'cutlist_custom_cart_item_thumbnail', 10, 3);

/**
 * Displays the custom board/decor/tape featured image thumbnail in WooCommerce Admin Orders & Customer Order pages.
 */
function cutlist_admin_order_item_thumbnail($image, $item_id, $item)
{
	if (!$item instanceof WC_Order_Item_Product) {
		return $image;
	}

	$img_id = (int) $item->get_meta('_cutlist_image_id', true);
	if ($img_id > 0) {
		$custom_img = wp_get_attachment_image($img_id, array(48, 48), false, array(
			'class' => 'attachment-thumbnail size-thumbnail',
			'style' => 'width:48px; height:48px; object-fit:cover; border-radius:4px; display:block; border:1px solid #ddd;',
		));
		if ($custom_img) {
			return $custom_img;
		}
	}

	$img_url = $item->get_meta('_cutlist_image_url', true);
	if (!empty($img_url)) {
		$decor_name = $item->get_meta(__('Decor', 'cutlist-catalogue'), true) ?: $item->get_name();
		return sprintf(
			'<img src="%s" class="attachment-thumbnail size-thumbnail" alt="%s" style="width:48px; height:48px; object-fit:cover; border-radius:4px; display:block; border:1px solid #ddd;" />',
			esc_url($img_url),
			esc_attr($decor_name)
		);
	}

	// Fallback: try to resolve by decor code in item name or meta
	$decor = $item->get_meta(__('Decor', 'cutlist-catalogue'), true);
	if (!empty($decor)) {
		$resolved = cutlist_get_decor_image($decor);
		if (!empty($resolved['id'])) {
			return wp_get_attachment_image($resolved['id'], array(48, 48), false, array(
				'class' => 'attachment-thumbnail size-thumbnail',
				'style' => 'width:48px; height:48px; object-fit:cover; border-radius:4px; display:block; border:1px solid #ddd;',
			));
		} elseif (!empty($resolved['url'])) {
			return sprintf(
				'<img src="%s" class="attachment-thumbnail size-thumbnail" alt="%s" style="width:48px; height:48px; object-fit:cover; border-radius:4px; display:block; border:1px solid #ddd;" />',
				esc_url($resolved['url']),
				esc_attr($decor)
			);
		}
	}

	return $image;
}
add_filter('woocommerce_admin_order_item_thumbnail', 'cutlist_admin_order_item_thumbnail', 10, 3);
add_filter('woocommerce_order_item_thumbnail', function ($image, $item) {
	return is_object($item) ? cutlist_admin_order_item_thumbnail($image, method_exists($item, 'get_id') ? $item->get_id() : 0, $item) : $image;
}, 10, 2);

/**
 * Hides internal cutlist meta keys from the line item meta list in Admin Orders and Emails.
 */
function cutlist_hidden_order_itemmeta($hidden_keys)
{
	$hidden_keys[] = '_cutlist_image_id';
	$hidden_keys[] = '_cutlist_image_url';
	$hidden_keys[] = '_cutlist_grain_files';
	$hidden_keys[] = '_cutlist_unique_key';
	return $hidden_keys;
}
add_filter('woocommerce_hidden_order_itemmeta', 'cutlist_hidden_order_itemmeta', 10, 1);

/**
 * Customizes the cart item title in WooCommerce Cart and Checkout.
 */
function cutlist_custom_cart_item_title($title, $cart_item, $cart_item_key)
{
	if (!empty($cart_item['cutlist_title'])) {
		return esc_html($cart_item['cutlist_title']);
	}
	return $title;
}
add_filter('woocommerce_cart_item_name', 'cutlist_custom_cart_item_title', 10, 3);

/**
 * Displays cutlist item attributes (Decor, Thickness, Dimensions, Edging, Machining, Spray, Grain Matching with drawings, Offcuts)
 * on WooCommerce Cart and Checkout pages.
 */
function cutlist_render_cart_item_data($item_data, $cart_item)
{
	if (!empty($cart_item['cutlist_section'])) {
		$item_data[] = array(
			'key' => __('Section / Room', 'cutlist-catalogue'),
			'name' => __('Section / Room', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_section']),
			'display' => '<span class="cutlist-section-badge" style="background:#f0fdf4; color:#166534; font-weight:600; padding:2px 8px; border-radius:4px; font-size:12px; border:1px solid #bbf7d0; display:inline-block;">' . esc_html($cart_item['cutlist_section']) . '</span>',
		);
	}
	if (!empty($cart_item['cutlist_type'])) {
		$item_data[] = array(
			'name' => __('Item Type', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_type']),
		);
	}
	if (!empty($cart_item['cutlist_decor'])) {
		$item_data[] = array(
			'name' => __('Decor', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_decor']),
		);
	}
	if (!empty($cart_item['cutlist_thick'])) {
		$item_data[] = array(
			'name' => __('Thickness', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_thick']),
		);
	}
	if (!empty($cart_item['cutlist_length']) && !empty($cart_item['cutlist_width'])) {
		$item_data[] = array(
			'name' => __('Dimensions', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_length'] . ' x ' . $cart_item['cutlist_width'] . ' mm'),
		);
	} elseif (!empty($cart_item['cutlist_dimensions'])) {
		$item_data[] = array(
			'name' => __('Dimensions', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_dimensions']),
		);
	}
	if (!empty($cart_item['cutlist_description'])) {
		$item_data[] = array(
			'name' => __('Part Description', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_description']),
		);
	}
	if (!empty($cart_item['cutlist_panel_info'])) {
		$item_data[] = array(
			'name' => __('Additional Note', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_panel_info']),
		);
	}
	if (!empty($cart_item['cutlist_edges'])) {
		$item_data[] = array(
			'name' => __('Edging Tape', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_edges']),
		);
	}
	if (!empty($cart_item['cutlist_panel_shaping'])) {
		$item_data[] = array(
			'name' => __('Panel shaping summary', 'cutlist-catalogue'),
			'value' => $cart_item['cutlist_panel_shaping'],
			'display' => '<div class="cutlist-meta-machining" style="margin-top:2px; line-height:1.45;">' . wp_kses_post($cart_item['cutlist_panel_shaping']) . '</div>',
		);
	}
	if (!empty($cart_item['cutlist_surface_shaping'])) {
		$item_data[] = array(
			'name' => __('Surface shaping summary', 'cutlist-catalogue'),
			'value' => $cart_item['cutlist_surface_shaping'],
			'display' => '<div class="cutlist-meta-machining" style="margin-top:2px; line-height:1.45;">' . wp_kses_post($cart_item['cutlist_surface_shaping']) . '</div>',
		);
	}
	if (!empty($cart_item['cutlist_drilling'])) {
		$item_data[] = array(
			'name' => __('Drilling summary', 'cutlist-catalogue'),
			'value' => $cart_item['cutlist_drilling'],
			'display' => '<div class="cutlist-meta-machining" style="margin-top:2px; line-height:1.45;">' . wp_kses_post($cart_item['cutlist_drilling']) . '</div>',
		);
	}
	if (empty($cart_item['cutlist_panel_shaping']) && empty($cart_item['cutlist_surface_shaping']) && empty($cart_item['cutlist_drilling']) && !empty($cart_item['cutlist_machining'])) {
		$item_data[] = array(
			'name' => __('Machining Operations', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_machining']),
		);
	}
	if (!empty($cart_item['cutlist_spray'])) {
		$item_data[] = array(
			'name' => __('Spray Finishing', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_spray']),
		);
	}
	if (!empty($cart_item['cutlist_grain'])) {
		$display_html = '<span style="font-weight:600; color:#1b5e20;">' . esc_html($cart_item['cutlist_grain']) . '</span>';
		if (!empty($cart_item['cutlist_grain_files']) && is_array($cart_item['cutlist_grain_files'])) {
			$files_html = array();
			foreach ($cart_item['cutlist_grain_files'] as $gfile) {
				$gname = esc_html($gfile['name']);
				$gurl = esc_url($gfile['url']);
				$is_img = !empty($gfile['is_image']) || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $gfile['url']);
				if ($is_img) {
					$files_html[] = '<div class="cutlist-grain-file-badge" style="display:inline-flex; align-items:center; gap:8px; margin-top:6px; padding:4px 8px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">'
						. '<a href="' . $gurl . '" target="_blank" rel="noopener noreferrer" style="display:inline-block; vertical-align:middle;">'
						. '<img src="' . $gurl . '" alt="' . $gname . '" style="width:38px; height:38px; object-fit:cover; border-radius:4px; border:1px solid #86efac; display:block;" />'
						. '</a>'
						. '<div style="display:flex; flex-direction:column; gap:2px;">'
						. '<a href="' . $gurl . '" target="_blank" rel="noopener noreferrer" style="color:#15803d; font-weight:600; font-size:12px; text-decoration:underline;">' . $gname . ' &#x2197;</a>'
						. '<span style="font-size:11px; color:#65a30d;">' . __('Grain diagram drawing', 'cutlist-catalogue') . '</span>'
						. '</div>'
						. '</div>';
				} else {
					$files_html[] = '<div class="cutlist-grain-file-badge" style="display:inline-flex; align-items:center; gap:8px; margin-top:6px; padding:4px 8px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">'
						. '<span style="font-size:20px;">📄</span>'
						. '<div style="display:flex; flex-direction:column; gap:2px;">'
						. '<a href="' . $gurl . '" target="_blank" rel="noopener noreferrer" style="color:#15803d; font-weight:600; font-size:12px; text-decoration:underline;">' . $gname . ' &#x2197;</a>'
						. '<span style="font-size:11px; color:#65a30d;">' . __('Grain diagram file', 'cutlist-catalogue') . '</span>'
						. '</div>'
						. '</div>';
				}
			}
			$display_html .= '<div class="cutlist-grain-files-list" style="margin-top:4px; display:flex; flex-direction:column; gap:4px;">' . implode('', $files_html) . '</div>';
		}

		$item_data[] = array(
			'name' => __('Grain Matching', 'cutlist-catalogue'),
			'value' => $cart_item['cutlist_grain'],
			'display' => $display_html,
		);
	}
	if (!empty($cart_item['cutlist_grain_info'])) {
		$item_data[] = array(
			'name' => __('Grain Match Notes', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_grain_info']),
		);
	}
	if (!empty($cart_item['cutlist_extra_cuts'])) {
		$item_data[] = array(
			'name' => __('Additional Cuts', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_extra_cuts']),
		);
	}
	if (!empty($cart_item['cutlist_selected_offcuts'])) {
		$item_data[] = array(
			'name' => __('Selected Offcuts', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_selected_offcuts']),
			'display' => '<span style="font-weight:600; color:#0369a1;">' . esc_html($cart_item['cutlist_selected_offcuts']) . ' (' . __('Free', 'cutlist-catalogue') . ')</span>',
		);
	}
	if (!empty($cart_item['cutlist_opening_size'])) {
		$item_data[] = array(
			'name' => __('Opening Dimensions', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_opening_size']),
		);
	}
	if (!empty($cart_item['cutlist_door_count'])) {
		$item_data[] = array(
			'name' => __('Door Quantity', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_door_count']),
		);
	}
	if (!empty($cart_item['cutlist_door_size'])) {
		$item_data[] = array(
			'name' => __('Calculated Door Size', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_door_size']),
		);
	}
	if (!empty($cart_item['cutlist_frame_finish'])) {
		$item_data[] = array(
			'name' => __('Frame Profile Finish', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_frame_finish']),
		);
	}
	if (!empty($cart_item['cutlist_track_type'])) {
		$item_data[] = array(
			'name' => __('Track Running Gear', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_track_type']),
		);
	}
	if (!empty($cart_item['cutlist_soft_close'])) {
		$item_data[] = array(
			'name' => __('Soft-Close Dampers', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_soft_close']),
		);
	}
	if (!empty($cart_item['cutlist_door_details'])) {
		$item_data[] = array(
			'name' => __('Door Panel Layout & Infills', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_door_details']),
		);
	}

	return $item_data;
}
add_filter('woocommerce_get_item_data', 'cutlist_render_cart_item_data', 10, 2);

/**
 * Saves cutlist item metadata onto WooCommerce Order Line Items so all details
 * appear in WooCommerce Admin Orders, Customer Emails, and Order Confirmation.
 */
function cutlist_add_order_line_item_meta($item, $cart_item_key, $values, $order)
{
	if (!empty($values['cutlist_title'])) {
		$item->set_name($values['cutlist_title']);
	}
	if (!empty($values['cutlist_section'])) {
		$item->add_meta_data(__('Section / Room', 'cutlist-catalogue'), $values['cutlist_section']);
	}
	if (!empty($values['cutlist_type'])) {
		$item->add_meta_data(__('Item Type', 'cutlist-catalogue'), $values['cutlist_type']);
	}
	if (!empty($values['cutlist_decor'])) {
		$item->add_meta_data(__('Decor', 'cutlist-catalogue'), $values['cutlist_decor']);
	}
	if (!empty($values['cutlist_thick'])) {
		$item->add_meta_data(__('Thickness', 'cutlist-catalogue'), $values['cutlist_thick']);
	}
	if (!empty($values['cutlist_length']) && !empty($values['cutlist_width'])) {
		$item->add_meta_data(__('Dimensions', 'cutlist-catalogue'), $values['cutlist_length'] . ' x ' . $values['cutlist_width'] . ' mm');
	} elseif (!empty($values['cutlist_dimensions'])) {
		$item->add_meta_data(__('Dimensions', 'cutlist-catalogue'), $values['cutlist_dimensions']);
	}
	if (!empty($values['cutlist_description'])) {
		$item->add_meta_data(__('Part Description', 'cutlist-catalogue'), $values['cutlist_description']);
	}
	if (!empty($values['cutlist_panel_info'])) {
		$item->add_meta_data(__('Additional Note', 'cutlist-catalogue'), $values['cutlist_panel_info']);
	}
	if (!empty($values['cutlist_edges'])) {
		$item->add_meta_data(__('Edging Tape', 'cutlist-catalogue'), $values['cutlist_edges']);
	}
	if (!empty($values['cutlist_panel_shaping'])) {
		$item->add_meta_data(__('Panel shaping summary', 'cutlist-catalogue'), $values['cutlist_panel_shaping']);
	}
	if (!empty($values['cutlist_surface_shaping'])) {
		$item->add_meta_data(__('Surface shaping summary', 'cutlist-catalogue'), $values['cutlist_surface_shaping']);
	}
	if (!empty($values['cutlist_drilling'])) {
		$item->add_meta_data(__('Drilling summary', 'cutlist-catalogue'), $values['cutlist_drilling']);
	}
	if (empty($values['cutlist_panel_shaping']) && empty($values['cutlist_surface_shaping']) && empty($values['cutlist_drilling']) && !empty($values['cutlist_machining'])) {
		$item->add_meta_data(__('Machining Operations', 'cutlist-catalogue'), $values['cutlist_machining']);
	}
	if (!empty($values['cutlist_spray'])) {
		$item->add_meta_data(__('Spray Finishing', 'cutlist-catalogue'), $values['cutlist_spray']);
	}
	if (!empty($values['cutlist_grain'])) {
		$item->add_meta_data(__('Grain Matching', 'cutlist-catalogue'), $values['cutlist_grain']);
	}
	if (!empty($values['cutlist_grain_files']) && is_array($values['cutlist_grain_files'])) {
		$links = array();
		foreach ($values['cutlist_grain_files'] as $gfile) {
			$links[] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#1d5a94; font-weight:600;">%s &#x2197;</a>', esc_url($gfile['url']), esc_html($gfile['name']));
		}
		$item->add_meta_data(__('Grain Matching Drawing', 'cutlist-catalogue'), implode('<br>', $links));
		$item->add_meta_data('_cutlist_grain_files', $values['cutlist_grain_files'], true);
	}
	if (!empty($values['cutlist_grain_info'])) {
		$item->add_meta_data(__('Grain Matching Notes', 'cutlist-catalogue'), $values['cutlist_grain_info']);
	}
	if (!empty($values['cutlist_extra_cuts'])) {
		$item->add_meta_data(__('Additional Cuts', 'cutlist-catalogue'), $values['cutlist_extra_cuts']);
	}
	if (!empty($values['cutlist_selected_offcuts'])) {
		$item->add_meta_data(__('Selected Offcuts', 'cutlist-catalogue'), $values['cutlist_selected_offcuts'] . ' (Free)');
	}
	if (!empty($values['cutlist_opening_size'])) {
		$item->add_meta_data(__('Opening Dimensions', 'cutlist-catalogue'), $values['cutlist_opening_size']);
	}
	if (!empty($values['cutlist_door_count'])) {
		$item->add_meta_data(__('Door Quantity', 'cutlist-catalogue'), $values['cutlist_door_count']);
	}
	if (!empty($values['cutlist_door_size'])) {
		$item->add_meta_data(__('Calculated Door Size', 'cutlist-catalogue'), $values['cutlist_door_size']);
	}
	if (!empty($values['cutlist_frame_finish'])) {
		$item->add_meta_data(__('Frame Profile Finish', 'cutlist-catalogue'), $values['cutlist_frame_finish']);
	}
	if (!empty($values['cutlist_track_type'])) {
		$item->add_meta_data(__('Track Running Gear', 'cutlist-catalogue'), $values['cutlist_track_type']);
	}
	if (!empty($values['cutlist_soft_close'])) {
		$item->add_meta_data(__('Soft-Close Dampers', 'cutlist-catalogue'), $values['cutlist_soft_close']);
	}
	if (!empty($values['cutlist_door_details'])) {
		$item->add_meta_data(__('Door Panel Layout & Infills', 'cutlist-catalogue'), $values['cutlist_door_details']);
	}
	if (!empty($values['cutlist_image_id'])) {
		$item->add_meta_data('_cutlist_image_id', (int) $values['cutlist_image_id'], true);
	}
	if (!empty($values['cutlist_image_url'])) {
		$item->add_meta_data('_cutlist_image_url', esc_url_raw($values['cutlist_image_url']), true);
	}
}
add_action('woocommerce_checkout_create_order_line_item', 'cutlist_add_order_line_item_meta', 10, 4);

/**
 * Attaches order-level grain matching metadata to the WooCommerce Order upon checkout.
 */
function cutlist_attach_order_level_meta($order, $data)
{
	if (function_exists('WC') && WC()->session) {
		$grain_files = WC()->session->get('cutlist_grain_files', array());
		$grain_info = WC()->session->get('cutlist_grain_info', '');

		if (!empty($grain_files) && is_array($grain_files)) {
			$order->update_meta_data('_cutlist_grain_matching_files', $grain_files);
			$links = array();
			foreach ($grain_files as $gfile) {
				$links[] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s &#x2197;</a>', esc_url($gfile['url']), esc_html($gfile['name']));
			}
			$order->update_meta_data(__('Grain Matching Drawing', 'cutlist-catalogue'), implode(', ', $links));
		}
		if (!empty($grain_info)) {
			$order->update_meta_data('_cutlist_grain_matching_info', $grain_info);
			$order->update_meta_data(__('Grain Matching Notes', 'cutlist-catalogue'), $grain_info);
		}
	}
}
add_action('woocommerce_checkout_create_order', 'cutlist_attach_order_level_meta', 10, 2);

/**
 * Helper: Checks if a cart item is an Edging Tape item subject to minimum quantity.
 * NOTE: This applies ONLY to standalone/separate edging tape orders (from the Edging tape section),
 * NEVER to Cut Panels that have edge banding details applied.
 */
function cutlist_is_edge_tape_cart_item($cart_item)
{
	if (empty($cart_item) || !is_array($cart_item)) {
		return false;
	}
	// Explicitly exclude Cut Panels, Full Sheets, and Services
	if (!empty($cart_item['cutlist_type']) && (
		$cart_item['cutlist_type'] === 'Cut Panel' ||
		$cart_item['cutlist_type'] === __('Cut Panel', 'cutlist-catalogue') ||
		$cart_item['cutlist_type'] === 'Full Sheet' ||
		$cart_item['cutlist_type'] === __('Full Sheet', 'cutlist-catalogue') ||
		$cart_item['cutlist_type'] === 'Additional Service' ||
		$cart_item['cutlist_type'] === __('Additional Service', 'cutlist-catalogue')
	)) {
		return false;
	}
	if (!empty($cart_item['cutlist_unique_key']) && (
		strpos($cart_item['cutlist_unique_key'], 'cut_') === 0 ||
		strpos($cart_item['cutlist_unique_key'], 'fs_') === 0
	)) {
		return false;
	}
	if (!empty($cart_item['cutlist_title']) && stripos($cart_item['cutlist_title'], 'Cut Panel') !== false) {
		return false;
	}
	if (!empty($cart_item['cutlist_type']) && (
		$cart_item['cutlist_type'] === 'Edging Tape' ||
		$cart_item['cutlist_type'] === __('Edging Tape', 'cutlist-catalogue')
	)) {
		return true;
	}
	if (!empty($cart_item['cutlist_unique_key']) && strpos($cart_item['cutlist_unique_key'], 'et_') === 0) {
		return true;
	}
	if (!empty($cart_item['cutlist_min_qty']) && (int) $cart_item['cutlist_min_qty'] >= 5) {
		return true;
	}
	if (!empty($cart_item['cutlist_title']) && (
		stripos($cart_item['cutlist_title'], 'Edging Tape:') === 0 ||
		stripos($cart_item['cutlist_title'], __('Edging Tape:', 'cutlist-catalogue')) === 0
	)) {
		return true;
	}
	return false;
}

/**
 * Helper: Gets minimum quantity required for a cutlist cart item.
 */
function cutlist_get_cart_item_min_qty($cart_item)
{
	if (cutlist_is_edge_tape_cart_item($cart_item)) {
		return !empty($cart_item['cutlist_min_qty']) ? (int) $cart_item['cutlist_min_qty'] : 5;
	}
	return 1;
}

/**
 * Sets min_value to 5 for Edging Tape cart items in WooCommerce Cart quantity inputs.
 */
function cutlist_cart_item_quantity_input_args($args, $product)
{
	if (!empty($args['input_name']) && preg_match('/cart\[([a-f0-9]+)\]\[qty\]/', $args['input_name'], $matches)) {
		$cart_item_key = $matches[1];
		if (function_exists('WC') && WC()->cart) {
			$cart_item = WC()->cart->get_cart_item($cart_item_key);
			if ($cart_item && cutlist_is_edge_tape_cart_item($cart_item)) {
				$min_qty = cutlist_get_cart_item_min_qty($cart_item);
				$args['min_value'] = $min_qty;
			}
		}
	}
	return $args;
}
add_filter('woocommerce_quantity_input_args', 'cutlist_cart_item_quantity_input_args', 20, 2);

/**
 * Ensures the cart item quantity input HTML enforces minimum 5 for Edging Tape items.
 */
function cutlist_cart_item_quantity_html($product_quantity, $cart_item_key, $cart_item)
{
	if (cutlist_is_edge_tape_cart_item($cart_item)) {
		$min_qty = cutlist_get_cart_item_min_qty($cart_item);
		$_product = $cart_item['data'] ?? null;
		if ($_product) {
			return woocommerce_quantity_input(
				array(
					'input_name'   => "cart[{$cart_item_key}][qty]",
					'input_value'  => max($min_qty, (int) $cart_item['quantity']),
					'max_value'    => $_product->get_max_purchase_quantity(),
					'min_value'    => $min_qty,
					'product_name' => $cart_item['cutlist_title'] ?? $_product->get_name(),
				),
				$_product,
				false
			);
		}
	}
	return $product_quantity;
}
add_filter('woocommerce_cart_item_quantity', 'cutlist_cart_item_quantity_html', 20, 3);

/**
 * Adds CSS classes to WooCommerce cart item rows to clearly distinguish
 * standalone Edging Tape items from Cut Panels and Full Sheets.
 */
function cutlist_add_cart_item_class($class, $cart_item, $cart_item_key)
{
	if (cutlist_is_edge_tape_cart_item($cart_item)) {
		$class .= ' cutlist-item-edging-tape';
	} elseif (!empty($cart_item['cutlist_type']) && (
		$cart_item['cutlist_type'] === 'Cut Panel' ||
		$cart_item['cutlist_type'] === __('Cut Panel', 'cutlist-catalogue')
	)) {
		$class .= ' cutlist-item-cut-panel';
	}
	return $class;
}
add_filter('woocommerce_cart_item_class', 'cutlist_add_cart_item_class', 10, 3);

/**
 * Validates cart item quantity updates: blocks reducing Edging Tape below 5 meters.
 */
function cutlist_validate_cart_item_quantity_update($passed, $cart_item_key, $values, $quantity)
{
	if (cutlist_is_edge_tape_cart_item($values)) {
		$min_qty = cutlist_get_cart_item_min_qty($values);
		// Note: $quantity == 0 means removing the item from cart, which is permitted.
		if ($quantity > 0 && $quantity < $min_qty) {
			$title = $values['cutlist_title'] ?? __('Edging Tape', 'cutlist-catalogue');
			wc_add_notice(
				sprintf(
					__('The minimum order quantity for %s is %d meters.', 'cutlist-catalogue'),
					esc_html($title),
					$min_qty
				),
				'error'
			);
			return false;
		}
	}
	return $passed;
}
add_filter('woocommerce_update_cart_validation', 'cutlist_validate_cart_item_quantity_update', 20, 4);

/**
 * Validates cart items on cart review / checkout to ensure minimum quantities are respected.
 */
function cutlist_validate_cart_items_minimum_quantities()
{
	if (!function_exists('WC') || !WC()->cart) {
		return;
	}
	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		if (cutlist_is_edge_tape_cart_item($cart_item)) {
			$min_qty = cutlist_get_cart_item_min_qty($cart_item);
			if ($cart_item['quantity'] < $min_qty) {
				$title = $cart_item['cutlist_title'] ?? __('Edging Tape', 'cutlist-catalogue');
				wc_add_notice(
					sprintf(
						__('The minimum order quantity for %s is %d meters. Please update your quantity before checkout.', 'cutlist-catalogue'),
						esc_html($title),
						$min_qty
					),
					'error'
				);
			}
		}
	}
}
add_action('woocommerce_check_cart_items', 'cutlist_validate_cart_items_minimum_quantities', 20);
add_action('woocommerce_review_order_before_cart_contents', 'cutlist_validate_cart_items_minimum_quantities', 20);

/**
 * Redirects user from Checkout page back to Cart if any Edging Tape quantity is below minimum (5m).
 */
function cutlist_prevent_checkout_with_invalid_quantities()
{
	if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'))) {
		return;
	}

	if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
		return;
	}

	$has_invalid_qty = false;
	$invalid_titles = array();

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		if (cutlist_is_edge_tape_cart_item($cart_item)) {
			$min_qty = cutlist_get_cart_item_min_qty($cart_item);
			if ($cart_item['quantity'] < $min_qty) {
				$has_invalid_qty = true;
				$invalid_titles[] = sprintf(
					__('%s (minimum %d meters)', 'cutlist-catalogue'),
					esc_html($cart_item['cutlist_title'] ?? __('Edging Tape', 'cutlist-catalogue')),
					$min_qty
				);
			}
		}
	}

	if ($has_invalid_qty) {
		$msg = sprintf(
			__('Please update your cart before proceeding to checkout: %s', 'cutlist-catalogue'),
			implode(', ', $invalid_titles)
		);
		if (!wc_has_notice($msg, 'error')) {
			wc_add_notice($msg, 'error');
		}
		wp_safe_redirect(wc_get_cart_url());
		exit;
	}
}
add_action('template_redirect', 'cutlist_prevent_checkout_with_invalid_quantities', 10);

/**
 * Blocks checkout submission via AJAX if any Edging Tape is below minimum quantity.
 */
function cutlist_checkout_process_quantity_validation()
{
	if (!function_exists('WC') || !WC()->cart) {
		return;
	}

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		if (cutlist_is_edge_tape_cart_item($cart_item)) {
			$min_qty = cutlist_get_cart_item_min_qty($cart_item);
			if ($cart_item['quantity'] < $min_qty) {
				wc_add_notice(
					sprintf(
						__('The minimum order quantity for %s is %d meters. Please update your cart before checkout.', 'cutlist-catalogue'),
						esc_html($cart_item['cutlist_title'] ?? __('Edging Tape', 'cutlist-catalogue')),
						$min_qty
					),
					'error'
				);
			}
		}
	}
}
add_action('woocommerce_checkout_process', 'cutlist_checkout_process_quantity_validation', 10);

/**
 * Validates board stock for all items in the cart on Cart and Checkout review screens.
 */
function cutlist_validate_cart_items_board_stock()
{
	if (function_exists('cutlist_validate_cart_board_stock')) {
		$stock_errors = cutlist_validate_cart_board_stock();
		foreach ($stock_errors as $err) {
			if (!wc_has_notice($err, 'error')) {
				wc_add_notice($err, 'error');
			}
		}
	}
	if (function_exists('cutlist_validate_cart_edge_tape_stock')) {
		$tape_errors = cutlist_validate_cart_edge_tape_stock();
		foreach ($tape_errors as $err) {
			if (!wc_has_notice($err, 'error')) {
				wc_add_notice($err, 'error');
			}
		}
	}
}
add_action('woocommerce_check_cart_items', 'cutlist_validate_cart_items_board_stock', 25);
add_action('woocommerce_review_order_before_cart_contents', 'cutlist_validate_cart_items_board_stock', 25);

/**
 * Prevents checkout submission if cart has out-of-stock or insufficient board or edge tape materials.
 */
function cutlist_checkout_process_board_stock_validation()
{
	if (function_exists('cutlist_validate_cart_board_stock')) {
		$stock_errors = cutlist_validate_cart_board_stock();
		foreach ($stock_errors as $err) {
			if (!wc_has_notice($err, 'error')) {
				wc_add_notice($err, 'error');
			}
		}
	}
	if (function_exists('cutlist_validate_cart_edge_tape_stock')) {
		$tape_errors = cutlist_validate_cart_edge_tape_stock();
		foreach ($tape_errors as $err) {
			if (!wc_has_notice($err, 'error')) {
				wc_add_notice($err, 'error');
			}
		}
	}
}
add_action('woocommerce_checkout_process', 'cutlist_checkout_process_board_stock_validation', 15);
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
	if (function_exists('cutlist_validate_cart_board_stock')) {
		$stock_errors = cutlist_validate_cart_board_stock();
		foreach ($stock_errors as $err) {
			$errors->add('stock_error', $err);
		}
	}
	if (function_exists('cutlist_validate_cart_edge_tape_stock')) {
		$tape_errors = cutlist_validate_cart_edge_tape_stock();
		foreach ($tape_errors as $err) {
			$errors->add('tape_stock_error', $err);
		}
	}
}, 15, 2);

/**
 * Allows HTML links to render safely in WooCommerce Order Item Meta (emails, order view).
 */
function cutlist_display_order_item_meta_value($display_value, $meta, $item)
{
	if (
		in_array($meta->key, array(
			__('Grain Matching Drawing', 'cutlist-catalogue'),
			'Grain Matching Drawing',
			__('Panel shaping summary', 'cutlist-catalogue'),
			'Panel shaping summary',
			__('Surface shaping summary', 'cutlist-catalogue'),
			'Surface shaping summary',
			__('Drilling summary', 'cutlist-catalogue'),
			'Drilling summary',
			__('Selected Offcuts', 'cutlist-catalogue'),
			'Selected Offcuts'
		), true)
	) {
		return wp_kses_post($display_value);
	}
	return $display_value;
}
add_filter('woocommerce_order_item_display_meta_value', 'cutlist_display_order_item_meta_value', 10, 3);

/**
 * Adds a meta box to WooCommerce Order Edit pages (both Classic and HPOS)
 * to showcase Grain Matching Drawings & Customer Notes with rich previews & download links.
 */
function cutlist_add_admin_order_grain_metabox()
{
	$screen = function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop_order') : 'shop_order';

	add_meta_box(
		'cutlist_grain_matching_box',
		__('📐 Grain Matching Drawings & Instructions', 'cutlist-catalogue'),
		'cutlist_render_admin_order_grain_metabox',
		$screen,
		'side',
		'high'
	);

	// Support for HPOS screen
	add_meta_box(
		'cutlist_grain_matching_box',
		__('📐 Grain Matching Drawings & Instructions', 'cutlist-catalogue'),
		'cutlist_render_admin_order_grain_metabox',
		'woocommerce_page_wc-orders',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'cutlist_add_admin_order_grain_metabox', 20);

function cutlist_render_admin_order_grain_metabox($post_or_order_object)
{
	$order = ($post_or_order_object instanceof WC_Order) ? $post_or_order_object : wc_get_order(is_object($post_or_order_object) ? $post_or_order_object->ID : $post_or_order_object);
	if (!$order) {
		return;
	}

	$files = array();
	$order_files = $order->get_meta('_cutlist_grain_matching_files', true);
	if (!empty($order_files) && is_array($order_files)) {
		$files = $order_files;
	}

	// Also check items if order-level is empty
	if (empty($files)) {
		foreach ($order->get_items() as $item) {
			$item_files = $item->get_meta('_cutlist_grain_files', true);
			if (!empty($item_files) && is_array($item_files)) {
				foreach ($item_files as $f) {
					if (!empty($f['url'])) {
						$files[$f['url']] = $f;
					}
				}
			}
		}
		$files = array_values($files);
	}

	$notes = $order->get_meta('_cutlist_grain_matching_info', true);
	if (empty($notes)) {
		$notes = $order->get_meta(__('Grain Matching Notes', 'cutlist-catalogue'), true);
	}
	if (empty($notes)) {
		foreach ($order->get_items() as $item) {
			$item_notes = $item->get_meta(__('Grain Matching Notes', 'cutlist-catalogue'), true);
			if (!empty($item_notes)) {
				$notes = $item_notes;
				break;
			}
		}
	}

	if (empty($files) && empty($notes)) {
		echo '<p style="color:#777; font-style:italic; margin:0;">' . esc_html__('No grain matching drawings or notes attached to this order.', 'cutlist-catalogue') . '</p>';
		return;
	}

	if (!empty($notes)) {
		echo '<div style="margin-bottom:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:10px;">';
		echo '<strong style="display:block; font-size:12px; color:#374151; margin-bottom:4px;">' . esc_html__('Customer Instructions / Notes:', 'cutlist-catalogue') . '</strong>';
		echo '<p style="margin:0; font-size:13px; color:#1f2937; white-space:pre-wrap;">' . esc_html($notes) . '</p>';
		echo '</div>';
	}

	if (!empty($files)) {
		echo '<div style="display:flex; flex-direction:column; gap:10px;">';
		foreach ($files as $f) {
			$name = esc_html($f['name']);
			$url = esc_url($f['url']);
			$is_img = !empty($f['is_image']) || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f['url']);

			echo '<div style="display:flex; align-items:center; gap:10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:8px 10px;">';
			if ($is_img) {
				echo '<a href="' . $url . '" target="_blank"><img src="' . $url . '" alt="' . $name . '" style="width:48px; height:48px; object-fit:cover; border-radius:4px; border:1px solid #86efac; display:block;" /></a>';
			} else {
				echo '<span style="font-size:28px; line-height:1;">📄</span>';
			}
			echo '<div style="flex:1; min-width:0;">';
			echo '<a href="' . $url . '" target="_blank" style="display:block; font-weight:600; color:#15803d; text-decoration:none; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' . $name . '">' . $name . '</a>';
			echo '<a href="' . $url . '" target="_blank" download class="button button-small" style="margin-top:4px; font-size:11px; height:24px; line-height:22px; padding:0 8px;">⬇️ ' . esc_html__('View / Download', 'cutlist-catalogue') . '</a>';
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
	}
}



/**
 * Injects custom CSS on WooCommerce Cart, Checkout, Mini-Cart, and Order pages
 * to format cutlist product attribute details into clean, vertically-stacked lines.
 */
function cutlist_cart_item_details_custom_styles()
{
	static $printed = false;
	if ($printed) {
		return;
	}
	$printed = true;
	?>
	<style id="cutlist-cart-item-details-styles">
		/* Ensure cart and checkout item attributes display cleanly on separate stacked lines */
		.woocommerce table.cart .product-name dl.variation,
		.woocommerce table.woocommerce-checkout-review-order-table dl.variation,
		.woocommerce ul.order_details dl.variation,
		.woocommerce .woocommerce-table--order-details dl.variation,
		.woocommerce .woocommerce-mini-cart-item dl.variation,
		.woocommerce dl.variation,
		dl.variation {
			display: block !important;
			margin: 8px 0 !important;
			padding: 0 !important;
			line-height: 1.5 !important;
			clear: both !important;
			font-size: 13px !important;
		}

		.woocommerce dl.variation dt,
		dl.variation dt {
			font-weight: 600 !important;
			color: #333 !important;
			margin: 3px 0 0 0 !important;
			padding: 0 !important;
			float: left !important;
			clear: left !important;
			display: inline-block !important;
			margin-right: 6px !important;
		}

		.woocommerce dl.variation dd,
		dl.variation dd {
			margin: 3px 0 3px 0 !important;
			padding: 0 !important;
			float: none !important;
			display: block !important;
			color: #444 !important;
		}

		.woocommerce dl.variation dd p,
		dl.variation dd p {
			margin: 0 !important;
			display: inline !important;
		}

		/* Suppress theme-inserted slash (/) or divider characters */
		.woocommerce dl.variation dt::after,
		.woocommerce dl.variation dd::after,
		dl.variation dt::after,
		dl.variation dd::after,
		.wc-block-components-product-details *::after,
		.wc-block-components-product-metadata *::after,
		.wc-block-components-product-details__item::after,
		.wc-block-components-product-details__value::after,
		.wc-block-components-product-metadata__item::after {
			content: "" !important;
			display: none !important;
		}

		/* Block-based Cart & Checkout support */
		.wc-block-components-product-details,
		.wc-block-components-product-metadata {
			display: flex !important;
			flex-direction: column !important;
			gap: 3px !important;
			margin: 6px 0 !important;
		}

		.wc-block-components-product-details>span,
		.wc-block-components-product-metadata>span,
		.wc-block-components-product-details .wc-block-components-product-details__item,
		.wc-block-components-product-metadata .wc-block-components-product-metadata__item {
			display: block !important;
			margin: 1px 0 !important;
			line-height: 1.4 !important;
		}

		.wc-block-components-product-details .wc-block-components-product-details__name,
		.wc-block-components-product-metadata .wc-block-components-product-metadata__name {
			font-weight: 600 !important;
			color: #333 !important;
			margin-right: 4px !important;
			display: inline !important;
		}

		.wc-block-components-product-details .wc-block-components-product-details__value,
		.wc-block-components-product-metadata .wc-block-components-product-metadata__value {
			color: #444 !important;
			display: inline !important;
		}

		/* Hide the separator span ("/") rendered by WooCommerce Block Cart & Checkout */
		.wc-block-components-product-details span[aria-hidden="true"],
		.wc-block-components-product-details span[aria-hidden],
		.wc-block-components-product-metadata span[aria-hidden="true"],
		.wc-block-components-product-metadata span[aria-hidden],
		.wc-block-components-product-details>span>span[aria-hidden="true"],
		.wc-block-components-product-details>span>span:last-child:not(.wc-block-components-product-details__value),
		.wc-block-components-product-details__separator,
		.wc-block-components-product-metadata__separator,
		[class*="product-details__separator"],
		[class*="product-metadata__separator"],
		.wc-block-cart-items span.wc-block-components-product-details__separator,
		.wc-block-components-product-details span.wc-block-components-product-details__separator {
			display: none !important;
			visibility: hidden !important;
			font-size: 0 !important;
			line-height: 0 !important;
			width: 0 !important;
			height: 0 !important;
			opacity: 0 !important;
			pointer-events: none !important;
		}
	</style>
	<script>
	(function () {
		/**
		 * Determines if a cart item row is a standalone Edging Tape order
		 * (from the bottom Edging Tape section) and NOT a Cut Panel with edgebanding details.
		 */
		function isStandaloneEdgingTapeRow(row) {
			if (!row) return false;

			// If explicitly tagged with cutlist classes
			if (row.classList.contains('cutlist-item-cut-panel')) return false;
			if (row.classList.contains('cutlist-item-edging-tape')) return true;

			var text = (row.innerText || '').replace(/\s+/g, ' ');

			// Cut Panels and Full Sheets can NEVER be standalone edging tape
			// (Cut panels display edgebanding details like "Edging Tape: L1:M1" in metadata)
			if (
				text.indexOf('Item Type: Cut Panel') !== -1 ||
				text.indexOf('Cut Panel:') !== -1 ||
				text.indexOf('Cut Panel [') !== -1
			) {
				return false;
			}
			if (
				text.indexOf('Item Type: Full Sheet') !== -1 ||
				text.indexOf('Full Sheet:') !== -1
			) {
				return false;
			}
			if (
				text.indexOf('Item Type: Additional Service') !== -1 ||
				text.indexOf('Additional Service:') !== -1
			) {
				return false;
			}

			// Standalone edging tape metadata explicitly declares "Item Type: Edging Tape"
			if (text.indexOf('Item Type: Edging Tape') !== -1) {
				return true;
			}

			// Inspect the product name element specifically (in both classic cart & block cart)
			var titleEl = row.querySelector(
				'.wc-block-components-product-name, .product-name a, .product-name, [class*="product-name"]'
			);
			if (titleEl) {
				var titleText = (titleEl.innerText || '').trim();
				if (titleText.indexOf('Cut Panel') !== -1) {
					return false;
				}
				if (titleText.indexOf('Edging Tape') === 0 || titleText.indexOf('Edging Tape:') === 0) {
					return true;
				}
			}

			return false;
		}

		function enforceEdgeTapeMinQty() {
			var hasInvalidEdgeTape = false;
			var cartRows = document.querySelectorAll(
				'.woocommerce-cart-form__cart-item, .wc-block-cart-items__row, .wc-block-cart-item, tr.cart_item, .cart_item'
			);
			cartRows.forEach(function (row) {
				var isEdgeTape = isStandaloneEdgingTapeRow(row);
				var qtyInput = row.querySelector('input.qty, input[type="number"], .wc-block-components-quantity-selector__input');
				var minusBtn = row.querySelector('.wc-block-components-quantity-selector__button--minus, .minus, [data-action="minus"], button.minus');

				if (isEdgeTape) {
					if (qtyInput) {
						qtyInput.setAttribute('min', '5');
						var val = parseInt(qtyInput.value, 10);
						if (!isNaN(val) && val < 5 && val > 0) {
							hasInvalidEdgeTape = true;
							qtyInput.classList.add('invalid');
							qtyInput.style.borderColor = '#dc2626';
						} else {
							qtyInput.classList.remove('invalid');
							qtyInput.style.borderColor = '';
						}
						if (minusBtn) {
							if (val <= 5) {
								minusBtn.setAttribute('disabled', 'disabled');
								minusBtn.style.opacity = '0.4';
								minusBtn.style.pointerEvents = 'none';
							} else {
								minusBtn.removeAttribute('disabled');
								minusBtn.style.opacity = '';
								minusBtn.style.pointerEvents = '';
							}
						}
					}
				} else {
					// Non-edge-tape item (Cut Panel, Full Sheet, etc.): ensure no 5m restriction is applied!
					if (qtyInput) {
						if (qtyInput.getAttribute('min') === '5') {
							qtyInput.setAttribute('min', '1');
						}
						qtyInput.classList.remove('invalid');
						if (qtyInput.style.borderColor === 'rgb(220, 38, 38)' || qtyInput.style.borderColor === '#dc2626') {
							qtyInput.style.borderColor = '';
						}
					}
					if (minusBtn) {
						var val = qtyInput ? parseInt(qtyInput.value, 10) : 1;
						if (val > 1) {
							minusBtn.removeAttribute('disabled');
							minusBtn.style.opacity = '';
							minusBtn.style.pointerEvents = '';
						}
					}
				}
			});

			var checkoutBtns = document.querySelectorAll('.checkout-button, .wc-block-cart__submit-button, a[href*="checkout"], .btn-wc-checkout');
			checkoutBtns.forEach(function (btn) {
				if (hasInvalidEdgeTape) {
					btn.classList.add('disabled');
					btn.style.opacity = '0.5';
					btn.style.cursor = 'not-allowed';
				} else {
					btn.classList.remove('disabled');
					btn.style.opacity = '';
					btn.style.cursor = '';
				}
			});
		}

		var isAlerting = false;
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.checkout-button, .wc-block-cart__submit-button, a[href*="checkout"], .btn-wc-checkout');
			if (btn) {
				var invalid = false;
				document.querySelectorAll('input.qty, input[type="number"], .wc-block-components-quantity-selector__input').forEach(function (inp) {
					var row = inp.closest('.woocommerce-cart-form__cart-item, .wc-block-cart-items__row, .wc-block-cart-item, tr.cart_item, .cart_item');
					if (row && isStandaloneEdgingTapeRow(row)) {
						var v = parseInt(inp.value, 10);
						if (!isNaN(v) && v < 5 && v > 0) {
							invalid = true;
						}
					}
				});
				if (invalid) {
					e.preventDefault();
					e.stopPropagation();
					if (!isAlerting) {
						isAlerting = true;
						alert('The minimum order quantity for Edging Tape is 5 meters. Please change the quantity to at least 5 before proceeding to checkout.');
						setTimeout(function () { isAlerting = false; }, 500);
					}
					return false;
				}
			}
		}, true);

		document.addEventListener('DOMContentLoaded', enforceEdgeTapeMinQty);
		window.addEventListener('load', enforceEdgeTapeMinQty);
		document.addEventListener('input', enforceEdgeTapeMinQty);
		document.addEventListener('change', enforceEdgeTapeMinQty);
		if (window.jQuery) {
			jQuery(document).ajaxComplete(function () { setTimeout(enforceEdgeTapeMinQty, 100); });
			jQuery(document.body).on('updated_cart_totals updated_wc_div', function () { setTimeout(enforceEdgeTapeMinQty, 100); });
		}
		var target = document.querySelector('.woocommerce, .wc-block-cart, .entry-content, form.woocommerce-cart-form');
		if (target && window.MutationObserver) {
			new MutationObserver(function () { enforceEdgeTapeMinQty(); }).observe(target, { childList: true, subtree: true });
		}
	})();
	</script>
	<?php
}
add_action('wp_head', 'cutlist_cart_item_details_custom_styles', 99);
add_action('wp_footer', 'cutlist_cart_item_details_custom_styles', 99);

/**
 * Overrides default WooCommerce templates (thank you, myaccount & order details) with custom Cutlist designs.
 */
function cutlist_woocommerce_locate_template($template, $template_name, $template_path)
{
	$custom_templates = array(
		'checkout/thankyou.php' => 'templates/thankyou.php',
		'myaccount/my-account.php' => 'templates/myaccount/my-account.php',
		'myaccount/navigation.php' => 'templates/myaccount/navigation.php',
		'myaccount/dashboard.php' => 'templates/myaccount/dashboard.php',
		'myaccount/orders.php' => 'templates/myaccount/orders.php',
		'myaccount/view-order.php' => 'templates/myaccount/view-order.php',
		'myaccount/my-address.php' => 'templates/myaccount/my-address.php',
		'myaccount/form-edit-address.php' => 'templates/myaccount/form-edit-address.php',
		'order/order-details.php' => 'templates/order/order-details.php',
	);

	if (isset($custom_templates[$template_name])) {
		$plugin_path = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . $custom_templates[$template_name] : '';
		if ($plugin_path && file_exists($plugin_path)) {
			return $plugin_path;
		}
	}

	return $template;
}
add_filter('woocommerce_locate_template', 'cutlist_woocommerce_locate_template', 999, 3);
add_filter('wc_get_template', function ($template, $template_name, $args, $template_path, $default_path) {
	$custom_templates = array(
		'checkout/thankyou.php' => 'templates/thankyou.php',
		'myaccount/my-account.php' => 'templates/myaccount/my-account.php',
		'myaccount/navigation.php' => 'templates/myaccount/navigation.php',
		'myaccount/dashboard.php' => 'templates/myaccount/dashboard.php',
		'myaccount/orders.php' => 'templates/myaccount/orders.php',
		'myaccount/view-order.php' => 'templates/myaccount/view-order.php',
		'myaccount/my-address.php' => 'templates/myaccount/my-address.php',
		'myaccount/form-edit-address.php' => 'templates/myaccount/form-edit-address.php',
		'order/order-details.php' => 'templates/order/order-details.php',
	);

	if (isset($custom_templates[$template_name])) {
		$plugin_path = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . $custom_templates[$template_name] : '';
		if ($plugin_path && file_exists($plugin_path)) {
			return $plugin_path;
		}
	}

	return $template;
}, 999, 5);

/**
 * Filter the checkout page content directly on order-received endpoint
 * to guarantee our custom receipt renders regardless of shortcodes or blocks.
 */
function cutlist_custom_order_received_the_content($content)
{
	if (function_exists('is_checkout') && is_checkout() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
		global $wp;
		$order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
		if (!$order_id && isset($_GET['order_id'])) {
			$order_id = absint($_GET['order_id']);
		}
		if ($order_id && function_exists('wc_get_order')) {
			$order = wc_get_order($order_id);
			if ($order) {
				$custom_template = defined('CUTLIST_CATALOGUE_PATH') ? (CUTLIST_CATALOGUE_PATH . 'templates/thankyou.php') : '';
				if ($custom_template && file_exists($custom_template)) {
					ob_start();
					include $custom_template;
					return ob_get_clean();
				}
			}
		}
	}
	return $content;
}
add_filter('the_content', 'cutlist_custom_order_received_the_content', 9999);

/**
 * Ensures the entire page template on checkout order-received uses Cutlist's custom design.
 */
function cutlist_override_order_received_template($template)
{
	if (function_exists('is_checkout') && is_checkout() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
		$custom_page = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/page-order-received.php' : '';
		if ($custom_page && file_exists($custom_page)) {
			return $custom_page;
		}
	}
	return $template;
}
add_filter('template_include', 'cutlist_override_order_received_template', 9999);

/**
 * Suppress standard WooCommerce duplicate table hooks on thankyou and view-order pages.
 */
add_action('wp', function () {
	if (function_exists('is_checkout') && is_checkout() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
		remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
		remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');
	}
	if (function_exists('is_account_page') && is_account_page() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('view-order')) {
		remove_action('woocommerce_view_order', 'woocommerce_order_details_table', 10);
		remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');
	}
});

/**
 * Replaces default woocommerce_account_view_order action to render the custom template directly.
 */
add_action('init', function () {
	remove_action('woocommerce_account_view_order', 'woocommerce_account_view_order', 10);
	add_action('woocommerce_account_view_order', function ($order_id) {
		$order = wc_get_order($order_id);
		if ($order) {
			$custom_template = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/myaccount/view-order.php' : '';
			if ($custom_template && file_exists($custom_template)) {
				include $custom_template;
				return;
			}
		}
	}, 10);
});

/**
 * Custom CSS Styles for WooCommerce My Account Page matching the Cutlist theme mockup.
 */
function cutlist_my_account_custom_styles()
{
	$is_account = function_exists('is_account_page') && is_account_page();
	$is_order_received = function_exists('is_checkout') && is_checkout() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received');
	$is_view_order = function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('view-order');

	if (!$is_account && !$is_order_received && !$is_view_order) {
		return;
	}

	?>
	<style id="cutlist-my-account-styles">
		/* Import cursive handwriting font for the hero slogan */
		@import url('https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap');

		/* Layout Wrapper */
		.woocommerce-account .entry-title,
		.woocommerce-account .ast-archive-description,
		.woocommerce-account header.entry-header,
		.woocommerce-order-received .entry-title,
		.woocommerce-order-received header.entry-header,
		.woocommerce-checkout.woocommerce-order-received .entry-title,
		.woocommerce-checkout.woocommerce-order-received header.entry-header {
			display: none !important;
		}

		.cl-account-layout-wrap {
			display: flex;
			gap: 32px;
			align-items: flex-start;
			max-width: 1240px;
			width: 100%;
			box-sizing: border-box;
			padding: 0 16px;
			margin: 20px auto 60px auto;
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #111827;
			overflow: visible;
		}

		/* Left Sidebar Column */
		.cl-account-sidebar-col {
			width: 240px;
			flex-shrink: 0;
			box-sizing: border-box;
		}

		.cl-account-main-col {
			flex: 1;
			min-width: 0;
			max-width: 100%;
			box-sizing: border-box;
		}

		.woocommerce-account .woocommerce-MyAccount-navigation {
			width: 100% !important;
			float: none !important;
			margin: 0 !important;
			box-sizing: border-box !important;
		}

		.woocommerce-account .woocommerce-MyAccount-content {
			width: 100% !important;
			max-width: 100% !important;
			float: none !important;
			margin: 0 !important;
			box-sizing: border-box !important;
		}

		.cl-nav-list {
			list-style: none !important;
			margin: 0 !important;
			padding: 0 !important;
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.cl-nav-item {
			list-style: none !important;
			margin: 0 !important;
			padding: 0 !important;
			border-radius: 8px;
			transition: all 0.15s ease;
		}

		.cl-nav-link {
			display: flex !important;
			align-items: center;
			gap: 12px;
			padding: 10px 14px !important;
			font-size: 14px;
			font-weight: 500;
			color: #4b5563 !important;
			text-decoration: none !important;
			border-radius: 8px;
			transition: all 0.15s ease;
		}

		.cl-nav-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 22px;
			height: 22px;
			color: #6b7280;
			transition: color 0.15s ease;
		}

		.cl-nav-item:hover .cl-nav-link {
			background: #f9fafb;
			color: #111827 !important;
		}

		.cl-nav-item:hover .cl-nav-icon {
			color: #111827;
		}

		/* Active Nav Item */
		.cl-nav-item--active,
		.cl-nav-item.is-active,
		.woocommerce-MyAccount-navigation-link.is-active {
			background: #f0fdf4 !important;
			border-left: 3px solid #16a34a !important;
			border-top-left-radius: 2px;
			border-bottom-left-radius: 2px;
		}

		.cl-nav-item--active .cl-nav-link,
		.cl-nav-item.is-active .cl-nav-link,
		.woocommerce-MyAccount-navigation-link.is-active a {
			color: #16a34a !important;
			font-weight: 600 !important;
		}

		.cl-nav-item--active .cl-nav-icon,
		.cl-nav-item.is-active .cl-nav-icon,
		.woocommerce-MyAccount-navigation-link.is-active .cl-nav-icon {
			color: #16a34a !important;
		}

		/* Need Help Card */
		.cl-need-help-card {
			margin-top: 32px;
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		}

		.cl-need-help-img {
			height: 90px;
			background-color: #f3f4f6;
			background-size: cover;
			background-position: center;
			border-bottom: 1px solid #e5e7eb;
		}

		.cl-need-help-content {
			padding: 16px;
		}

		.cl-need-help-title {
			font-size: 15px;
			font-weight: 700;
			color: #111827;
			margin: 0 0 4px 0;
		}

		.cl-need-help-text {
			font-size: 12.5px;
			color: #6b7280;
			line-height: 1.4;
			margin: 0 0 12px 0;
		}

		.cl-need-help-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 14px;
			background: #ffffff;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			font-size: 12.5px;
			font-weight: 600;
			color: #374151 !important;
			text-decoration: none !important;
			transition: all 0.15s ease;
		}

		.cl-need-help-btn:hover {
			background: #f3f4f6;
			border-color: #9ca3af;
			color: #111827 !important;
		}

		/* Right Main Column */
		.cl-account-main-col {
			flex: 1;
			min-width: 0;
		}

		.woocommerce-account .woocommerce-MyAccount-content {
			width: 100% !important;
			float: none !important;
			margin: 0 !important;
		}

		/* 1. Dashboard Hero Banner */
		.cl-hero-banner {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 14px;
			padding: 24px 28px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 24px;
			position: relative;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
		}

		.cl-hero-text {
			max-width: 58%;
			z-index: 2;
		}

		.cl-hero-badge {
			display: inline-block;
			font-size: 11px;
			font-weight: 700;
			color: #16a34a;
			letter-spacing: 0.8px;
			margin-bottom: 6px;
		}

		.cl-hero-title {
			font-size: 28px;
			font-weight: 800;
			color: #111827;
			margin: 0 0 8px 0;
			line-height: 1.2;
		}

		.cl-hero-desc {
			font-size: 13.5px;
			color: #4b5563;
			line-height: 1.5;
			margin: 0;
		}

		.cl-hero-visual {
			display: flex;
			align-items: center;
			gap: 16px;
			position: relative;
			z-index: 2;
		}

		.cl-hero-quote-box {
			font-family: 'Caveat', cursive, sans-serif;
			font-size: 21px;
			line-height: 1.15;
			color: #374151;
			font-weight: 600;
			text-align: right;
			display: flex;
			flex-direction: column;
			transform: rotate(-3deg);
		}

		.cl-quote-4 {
			color: #16a34a;
			font-weight: 700;
		}

		.cl-hero-wood-img {
			width: 170px;
			height: 105px;
			object-fit: cover;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
			display: block;
		}

		/* 2. Quick Actions 4-Column Grid */
		.cl-quick-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
			margin-bottom: 24px;
		}

		.cl-quick-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 18px 16px;
			display: flex;
			flex-direction: column;
			text-decoration: none !important;
			color: inherit !important;
			transition: all 0.15s ease;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		}

		.cl-quick-card:hover {
			border-color: #16a34a;
			transform: translateY(-2px);
			box-shadow: 0 6px 16px rgba(22, 163, 74, 0.08);
		}

		.cl-quick-top {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 12px;
		}

		.cl-quick-icon {
			width: 38px;
			height: 38px;
			background: #f0fdf4;
			border: 1px solid #dcfce7;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.cl-quick-arrow {
			font-size: 16px;
			color: #9ca3af;
			font-weight: 600;
			transition: all 0.15s ease;
		}

		.cl-quick-card:hover .cl-quick-arrow {
			color: #16a34a;
			transform: translateX(3px);
		}

		.cl-quick-title {
			font-size: 14px;
			font-weight: 700;
			color: #111827;
			margin: 0 0 4px 0;
		}

		.cl-quick-desc {
			font-size: 12px;
			color: #6b7280;
			line-height: 1.4;
			margin: 0;
		}

		/* 3. Recent Orders Card & Table */
		.cl-orders-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
			margin-bottom: 24px;
		}

		.cl-orders-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 16px 20px;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-orders-title {
			font-size: 16px;
			font-weight: 700;
			color: #111827;
			margin: 0;
		}

		.cl-orders-view-all {
			font-size: 13px;
			font-weight: 600;
			color: #16a34a !important;
			text-decoration: none !important;
			display: inline-flex;
			align-items: center;
			gap: 4px;
			transition: gap 0.15s ease;
		}

		.cl-orders-view-all:hover {
			gap: 7px;
		}

		.cl-table-responsive {
			width: 100%;
			overflow-x: auto;
		}

		.cl-orders-table {
			width: 100% !important;
			border-collapse: collapse !important;
			margin: 0 !important;
			font-size: 13.5px;
			text-align: left;
		}

		.cl-orders-table th {
			background: #fafafa !important;
			color: #6b7280 !important;
			font-weight: 600 !important;
			font-size: 12px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.5px !important;
			padding: 12px 18px !important;
			border: none !important;
			border-bottom: 1px solid #f3f4f6 !important;
		}

		.cl-orders-table td {
			padding: 14px 18px !important;
			border: none !important;
			border-bottom: 1px solid #f3f4f6 !important;
			color: #374151 !important;
			vertical-align: middle !important;
		}

		.cl-orders-table tbody tr:last-child td {
			border-bottom: none !important;
		}

		.cl-order-number-link {
			color: #111827 !important;
			text-decoration: none !important;
		}

		.cl-order-number-link:hover {
			color: #16a34a !important;
		}

		/* Status Badges */
		.cl-status-pill {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 6px;
			font-size: 12px;
			font-weight: 600;
		}

		.cl-status--processing {
			background: #eff6ff;
			color: #2563eb;
			border: 1px solid #dbeafe;
		}

		.cl-status--completed {
			background: #f0fdf4;
			color: #16a34a;
			border: 1px solid #dcfce7;
		}

		.cl-status--cancelled,
		.cl-status--failed {
			background: #fef2f2;
			color: #dc2626;
			border: 1px solid #fee2e2;
		}

		.cl-status--on-hold {
			background: #fffbeb;
			color: #d97706;
			border: 1px solid #fef3c7;
		}

		.cl-status--pending {
			background: #f3f4f6;
			color: #4b5563;
			border: 1px solid #e5e7eb;
		}

		/* View Order Action Button */
		.cl-btn-view-order {
			display: inline-flex !important;
			align-items: center;
			gap: 6px;
			padding: 6px 12px !important;
			background: #ffffff !important;
			border: 1px solid #d1d5db !important;
			border-radius: 6px !important;
			font-size: 12.5px !important;
			font-weight: 600 !important;
			color: #374151 !important;
			text-decoration: none !important;
			transition: all 0.15s ease !important;
			line-height: 1.2 !important;
		}

		.cl-btn-view-order:hover {
			background: #f9fafb !important;
			border-color: #9ca3af !important;
			color: #111827 !important;
		}

		.cl-orders-empty {
			padding: 40px 20px;
			text-align: center;
		}

		.cl-empty-icon {
			margin-bottom: 12px;
		}

		.cl-orders-empty h3 {
			font-size: 17px;
			font-weight: 700;
			color: #111827;
			margin: 0 0 6px 0;
		}

		.cl-orders-empty p {
			font-size: 13.5px;
			color: #6b7280;
			margin: 0 0 16px 0;
		}

		.cl-btn-start {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 8px 18px;
			background: #16a34a;
			color: #ffffff !important;
			border-radius: 6px;
			font-size: 13.5px;
			font-weight: 600;
			text-decoration: none !important;
			transition: background 0.15s ease;
		}

		.cl-btn-start:hover {
			background: #15803d;
		}

		/* 4. ADDRESSES PAGE & GRID DESIGN */
		.cl-addresses-container {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}

		.cl-addresses-header {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 20px 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
		}

		.cl-addresses-title {
			font-size: 20px;
			font-weight: 800;
			color: #111827;
			margin: 0 0 6px 0;
		}

		.cl-addresses-desc {
			font-size: 13.5px;
			color: #6b7280;
			margin: 0;
			line-height: 1.5;
		}

		.cl-addresses-grid {
			display: grid !important;
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 20px !important;
			align-items: stretch !important;
			float: none !important;
			width: 100% !important;
		}

		/* Reset WooCommerce default broken float columns */
		.woocommerce-account .u-columns,
		.woocommerce-account .col2-set.addresses,
		.woocommerce-account .woocommerce-Addresses {
			display: block !important;
			float: none !important;
			margin: 0 !important;
			width: 100% !important;
		}

		.woocommerce-account .u-column1,
		.woocommerce-account .u-column2,
		.woocommerce-account .col-1,
		.woocommerce-account .col-2 {
			float: none !important;
			width: 100% !important;
			margin: 0 !important;
		}

		.cl-address-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 22px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
			display: flex;
			flex-direction: column;
			justify-content: flex-start;
			min-height: 200px;
			transition: all 0.15s ease;
		}

		.cl-address-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
		}

		.cl-address-card-top {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 16px;
			padding-bottom: 12px;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-address-title-box {
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.cl-address-icon {
			width: 34px;
			height: 34px;
			background: #f0fdf4;
			border: 1px solid #dcfce7;
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}

		.cl-address-name {
			font-size: 16px;
			font-weight: 700;
			color: #111827;
			margin: 0;
		}

		.cl-btn-edit-address {
			display: inline-flex !important;
			align-items: center;
			gap: 6px;
			padding: 5px 12px !important;
			background: #ffffff !important;
			border: 1px solid #d1d5db !important;
			border-radius: 6px !important;
			font-size: 12.5px !important;
			font-weight: 600 !important;
			color: #374151 !important;
			text-decoration: none !important;
			transition: all 0.15s ease !important;
		}

		.cl-btn-edit-address:hover {
			background: #f0fdf4 !important;
			border-color: #16a34a !important;
			color: #16a34a !important;
		}

		.cl-address-card-body {
			flex: 1;
		}

		.cl-address-card-body address,
		.cl-formatted-address,
		.woocommerce-account address {
			font-style: normal !important;
			font-size: 14px !important;
			color: #374151 !important;
			line-height: 1.65 !important;
			margin: 0 !important;
		}

		.cl-no-address-text {
			font-size: 13.5px;
			color: #9ca3af;
			font-style: italic;
		}

		/* 5. EDIT ADDRESS FORM */
		.cl-edit-address-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
			width: 100%;
			max-width: 100%;
			box-sizing: border-box;
			overflow: hidden;
		}

		.cl-edit-address-header {
			margin-bottom: 20px;
			padding-bottom: 14px;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-back-link {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 13px;
			font-weight: 600;
			color: #16a34a !important;
			text-decoration: none !important;
			margin-bottom: 8px;
			transition: gap 0.15s ease;
		}

		.cl-back-link:hover {
			gap: 9px;
		}

		.cl-edit-address-title {
			font-size: 22px;
			font-weight: 800;
			color: #111827;
			margin: 0;
		}

		.cl-address-form,
		.woocommerce-address-fields,
		.woocommerce-address-fields__field-wrapper {
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			float: none !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.cl-address-fields-grid {
			display: grid !important;
			grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
			gap: 16px !important;
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
		}

		/* Reset WooCommerce and theme float / clear / width on form-row */
		.cl-address-fields-grid .form-row {
			float: none !important;
			width: 100% !important;
			max-width: 100% !important;
			min-width: 0 !important;
			margin: 0 !important;
			padding: 0 !important;
			box-sizing: border-box !important;
			clear: none !important;
		}

		.cl-address-fields-grid .form-row::before,
		.cl-address-fields-grid .form-row::after {
			display: none !important;
			content: none !important;
		}

		.cl-address-fields-grid .form-row-wide,
		.cl-address-fields-grid .form-row:first-child:last-child,
		.cl-address-fields-grid #billing_company_field,
		.cl-address-fields-grid #billing_country_field,
		.cl-address-fields-grid #billing_address_1_field,
		.cl-address-fields-grid #billing_address_2_field,
		.cl-address-fields-grid #shipping_company_field,
		.cl-address-fields-grid #shipping_country_field,
		.cl-address-fields-grid #shipping_address_1_field,
		.cl-address-fields-grid #shipping_address_2_field {
			grid-column: 1 / -1 !important;
		}

		.cl-address-fields-grid #billing_first_name_field,
		.cl-address-fields-grid #shipping_first_name_field {
			grid-column: 1 !important;
		}

		.cl-address-fields-grid #billing_last_name_field,
		.cl-address-fields-grid #shipping_last_name_field {
			grid-column: 2 !important;
		}

		.cl-address-fields-grid #billing_city_field,
		.cl-address-fields-grid #shipping_city_field {
			grid-column: 1 !important;
		}

		.cl-address-fields-grid #billing_state_field,
		.cl-address-fields-grid #shipping_state_field {
			grid-column: 2 !important;
		}

		.cl-address-fields-grid #billing_postcode_field,
		.cl-address-fields-grid #shipping_postcode_field {
			grid-column: 1 !important;
		}

		.cl-address-fields-grid #billing_phone_field {
			grid-column: 1 !important;
		}

		.cl-address-fields-grid #billing_email_field {
			grid-column: 2 !important;
		}

		.cl-form-submit-row {
			margin-top: 24px;
			padding-top: 18px;
			border-top: 1px solid #f3f4f6;
			width: 100% !important;
			box-sizing: border-box !important;
			clear: both !important;
		}

		.cl-btn-primary,
		.woocommerce-account button.button,
		.woocommerce-account input.button {
			background: #16a34a !important;
			color: #ffffff !important;
			border: none !important;
			border-radius: 6px !important;
			padding: 10px 24px !important;
			font-weight: 600 !important;
			font-size: 14px !important;
			cursor: pointer !important;
			transition: background 0.15s ease !important;
		}

		.cl-btn-primary:hover,
		.woocommerce-account button.button:hover,
		.woocommerce-account input.button:hover {
			background: #15803d !important;
		}

		/* Form Styling on Addresses & Account Details */
		.woocommerce-account form .form-row label {
			font-size: 13px !important;
			font-weight: 600 !important;
			color: #374151 !important;
			margin-bottom: 4px !important;
			display: block !important;
		}

		.woocommerce-account form .form-row .woocommerce-input-wrapper {
			display: block !important;
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
		}

		.woocommerce-account form .form-row input.input-text,
		.woocommerce-account form .form-row select,
		.woocommerce-account form .form-row textarea {
			border: 1px solid #d1d5db !important;
			border-radius: 6px !important;
			padding: 9px 12px !important;
			font-size: 14px !important;
			width: 100% !important;
			max-width: 100% !important;
			background: #ffffff !important;
			box-sizing: border-box !important;
			line-height: 1.4 !important;
			margin: 0 !important;
		}

		.woocommerce-account form .form-row input.input-text:focus,
		.woocommerce-account form .form-row select:focus,
		.woocommerce-account form .form-row textarea:focus {
			border-color: #16a34a !important;
			outline: 2px solid #dcfce7 !important;
		}

		/* Select2 Container & Dropdown in WooCommerce My Account Forms */
		.woocommerce-account .select2-container,
		.woocommerce-account .select2-container--default,
		.woocommerce-account span.select2 {
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			display: block !important;
		}

		.woocommerce-account .select2-container .select2-selection--single {
			height: 42px !important;
			border: 1px solid #d1d5db !important;
			border-radius: 6px !important;
			padding: 6px 12px !important;
			display: flex !important;
			align-items: center !important;
			box-sizing: border-box !important;
			width: 100% !important;
			max-width: 100% !important;
			background: #ffffff !important;
		}

		.woocommerce-account .select2-container--default .select2-selection--single .select2-selection__rendered {
			color: #111827 !important;
			line-height: normal !important;
			padding-left: 0 !important;
			padding-right: 20px !important;
			font-size: 14px !important;
		}

		.woocommerce-account .select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 40px !important;
			right: 10px !important;
			top: 1px !important;
		}

		/* 6. VIEW ORDER PAGE STYLES (MATCHING MOCKUP) */
		.cl-vo-page-wrap {
			display: flex;
			flex-direction: column;
			gap: 24px;
		}

		.cl-vo-breadcrumbs {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			color: #6b7280;
			margin-bottom: -6px;
		}

		.cl-vo-breadcrumbs a {
			color: #6b7280 !important;
			text-decoration: none !important;
			transition: color 0.15s ease;
		}

		.cl-vo-breadcrumbs a:hover {
			color: #16a34a !important;
		}

		.cl-vo-crumb-sep {
			color: #9ca3af;
			font-size: 14px;
		}

		.cl-vo-crumb-current {
			color: #111827;
			font-weight: 600;
		}

		.cl-vo-header-row {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 16px;
		}

		.cl-vo-title {
			font-size: 28px;
			font-weight: 800;
			color: #111827;
			margin: 0 0 6px 0;
			line-height: 1.2;
		}

		.cl-vo-placement-text {
			font-size: 14px;
			color: #4b5563;
			margin: 0;
			line-height: 1.5;
		}

		.cl-vo-header-right {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-shrink: 0;
		}

		.cl-vo-status-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 7px 16px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
		}

		.cl-vo-status--processing {
			background: #eff6ff;
			color: #2563eb;
			border: 1px solid #dbeafe;
		}

		.cl-vo-status--completed {
			background: #f0fdf4;
			color: #16a34a;
			border: 1px solid #dcfce7;
		}

		.cl-vo-status--cancelled,
		.cl-vo-status--failed {
			background: #fef2f2;
			color: #dc2626;
			border: 1px solid #fee2e2;
		}

		.cl-vo-status--on-hold {
			background: #fffbeb;
			color: #d97706;
			border: 1px solid #fef3c7;
		}

		.cl-vo-btn-print {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 7px 16px;
			background: #ffffff;
			border: 1px solid #d1d5db;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #374151;
			cursor: pointer;
			transition: all 0.15s ease;
		}

		.cl-vo-btn-print:hover {
			background: #f9fafb;
			border-color: #9ca3af;
		}

		/* 3-Column Metric Tiles */
		.cl-vo-metrics-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
		}

		.cl-vo-metric-tile {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 18px 20px;
			display: flex;
			align-items: center;
			gap: 14px;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		}

		.cl-vo-metric-icon {
			width: 44px;
			height: 44px;
			background: #f0fdf4;
			border: 1px solid #dcfce7;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}

		.cl-vo-metric-text {
			display: flex;
			flex-direction: column;
		}

		.cl-vo-metric-label {
			font-size: 12px;
			color: #6b7280;
			font-weight: 500;
			margin-bottom: 3px;
		}

		.cl-vo-metric-val {
			font-size: 15px;
			font-weight: 700;
			color: #111827;
		}

		/* Details Section */
		.cl-vo-details-section {
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.cl-vo-details-heading-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.cl-vo-section-title {
			font-size: 18px;
			font-weight: 700;
			color: #111827;
			margin: 0;
		}

		.cl-vo-items-count {
			font-size: 13.5px;
			font-weight: 500;
			color: #6b7280;
		}

		.cl-vo-items-cards-list {
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.cl-vo-item-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 22px 24px;
			display: flex;
			gap: 22px;
			align-items: flex-start;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
			transition: all 0.15s ease;
		}

		.cl-vo-item-card:hover {
			border-color: #cbd5e1;
		}

		.cl-vo-item-thumb-box {
			flex-shrink: 0;
		}

		.cl-vo-item-img {
			width: 120px;
			height: 120px;
			object-fit: cover;
			border-radius: 8px;
			border: 1px solid #e5e7eb;
			display: block;
		}

		.cl-vo-item-placeholder {
			width: 120px;
			height: 120px;
			background: #f3f4f6;
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.cl-vo-item-details-box {
			flex: 1;
			min-width: 0;
		}

		.cl-vo-item-title {
			font-size: 15.5px;
			font-weight: 700;
			color: #111827;
			margin: 0 0 12px 0;
			line-height: 1.35;
		}

		.cl-vo-meta-grid {
			display: flex;
			flex-direction: column;
			gap: 5px;
			font-size: 13px;
		}

		.cl-vo-meta-row {
			display: flex;
			align-items: baseline;
			gap: 16px;
		}

		.cl-vo-meta-key {
			color: #6b7280;
			width: 110px;
			flex-shrink: 0;
			font-weight: 500;
		}

		.cl-vo-meta-val {
			color: #374151;
			font-weight: 500;
		}

		.cl-vo-meta-row--files {
			flex-direction: column;
			align-items: flex-start;
			gap: 4px;
		}

		.cl-vo-drawing-files {
			display: flex;
			flex-wrap: wrap;
			gap: 6px;
		}

		.cl-vo-item-pricing-box {
			display: flex;
			align-items: center;
			gap: 36px;
			flex-shrink: 0;
			padding-top: 4px;
		}

		.cl-vo-item-qty {
			font-size: 14px;
			font-weight: 600;
			color: #374151;
		}

		.cl-vo-item-total {
			font-size: 16px;
			font-weight: 700;
			color: #111827;
			min-width: 80px;
			text-align: right;
		}

		/* Totals Summary Card */
		.cl-vo-totals-box {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			overflow: hidden;
			margin-top: 8px;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		}

		.cl-vo-totals-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 14px 24px;
			font-size: 14px;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-vo-totals-row:last-child {
			border-bottom: none;
		}

		.cl-vo-totals-row--grand {
			background: #f0fdf4;
			border-top: 1px solid #dcfce7;
			border-bottom: 1px solid #dcfce7;
			padding: 16px 24px;
		}

		.cl-vo-grand-label {
			font-size: 15px;
			font-weight: 700;
			color: #16a34a;
		}

		.cl-vo-grand-val {
			font-size: 18px;
			font-weight: 800;
			color: #16a34a;
		}

		.cl-vo-totals-label {
			font-weight: 600;
			color: #111827;
		}

		.cl-vo-totals-val {
			font-weight: 700;
			color: #111827;
		}

		/* Billing Address Section */
		.cl-vo-address-section {
			margin-top: 16px;
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.cl-vo-address-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
		}

		.cl-vo-address-top {
			display: flex;
			gap: 16px;
			align-items: flex-start;
			margin-bottom: 16px;
		}

		.cl-vo-address-icon {
			width: 40px;
			height: 40px;
			background: #f0fdf4;
			border: 1px solid #dcfce7;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}

		.cl-vo-address-lines {
			font-style: normal !important;
			font-size: 14px !important;
			color: #374151 !important;
			line-height: 1.65 !important;
			margin: 0 !important;
		}

		.cl-vo-address-email-row {
			display: flex;
			align-items: center;
			gap: 10px;
			padding-top: 14px;
			border-top: 1px solid #f3f4f6;
			font-size: 13.5px;
		}

		.cl-vo-email-link {
			color: #374151 !important;
			text-decoration: none !important;
		}

		.cl-vo-email-link:hover {
			color: #16a34a !important;
		}

		/* Responsive */
		@media (max-width: 1024px) {
			.cl-quick-grid {
				grid-template-columns: repeat(2, 1fr);
			}

			.cl-hero-banner {
				flex-direction: column;
				align-items: flex-start;
				gap: 20px;
			}

			.cl-hero-text {
				max-width: 100%;
			}

			.cl-hero-visual {
				width: 100%;
				justify-content: flex-end;
			}

			.cl-vo-metrics-grid {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 768px) {
			.cl-account-layout-wrap {
				flex-direction: column !important;
				gap: 20px !important;
				padding: 0 12px !important;
			}

			.cl-account-sidebar-col {
				width: 100% !important;
			}

			.cl-nav-list {
				flex-direction: row !important;
				flex-wrap: wrap !important;
				gap: 8px !important;
			}

			.cl-need-help-card {
				display: none !important;
			}

			.cl-quick-grid {
				grid-template-columns: 1fr !important;
			}

			.cl-addresses-grid {
				grid-template-columns: 1fr !important;
			}

			.cl-address-fields-grid {
				grid-template-columns: 1fr !important;
				gap: 12px !important;
			}

			.cl-address-fields-grid .form-row,
			.cl-address-fields-grid #billing_first_name_field,
			.cl-address-fields-grid #billing_last_name_field,
			.cl-address-fields-grid #billing_city_field,
			.cl-address-fields-grid #billing_state_field,
			.cl-address-fields-grid #billing_postcode_field,
			.cl-address-fields-grid #billing_phone_field,
			.cl-address-fields-grid #billing_email_field,
			.cl-address-fields-grid #shipping_first_name_field,
			.cl-address-fields-grid #shipping_last_name_field,
			.cl-address-fields-grid #shipping_city_field,
			.cl-address-fields-grid #shipping_state_field,
			.cl-address-fields-grid #shipping_postcode_field {
				grid-column: 1 / -1 !important;
			}

			.cl-edit-address-card {
				padding: 16px !important;
			}

			.cl-vo-header-row {
				flex-direction: column !important;
				align-items: flex-start !important;
			}

			.cl-vo-item-card {
				flex-direction: column !important;
			}

			.cl-vo-item-pricing-box {
				width: 100% !important;
				justify-content: space-between !important;
			}
		}
	</style>
	<?php
}
add_action('wp_head', 'cutlist_my_account_custom_styles', 99);
add_action('wp_footer', 'cutlist_my_account_custom_styles', 99);

/**
 * Standalone Print / PDF Order Confirmation Endpoint Handler
 *
 * Catches requests with `print_order={id}` or `cutlist_print={id}` and directly serves
 * the clean standalone printable template without theme wrapper or extra styles.
 */
function cutlist_handle_print_order_page()
{
	$is_print = isset($_GET['print_order']) || isset($_GET['cutlist_print']) || (isset($_GET['print']) && $_GET['print'] === '1');
	if (!$is_print) {
		return;
	}

	$order_id = 0;
	if (isset($_GET['print_order']) && is_numeric($_GET['print_order']) && (int) $_GET['print_order'] > 0) {
		$order_id = absint($_GET['print_order']);
	} elseif (isset($_GET['cutlist_print']) && is_numeric($_GET['cutlist_print']) && (int) $_GET['cutlist_print'] > 0) {
		$order_id = absint($_GET['cutlist_print']);
	} elseif (isset($_GET['order_id'])) {
		$order_id = absint($_GET['order_id']);
	} else {
		global $wp;
		if (isset($wp->query_vars['view-order'])) {
			$order_id = absint($wp->query_vars['view-order']);
		} elseif (isset($wp->query_vars['order-received'])) {
			$order_id = absint($wp->query_vars['order-received']);
		}
	}

	if (!$order_id) {
		return;
	}

	$order = wc_get_order($order_id);
	if (!$order) {
		return;
	}

	// Security & permission validation
	$order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
	$user_id = get_current_user_id();
	$can_view = false;

	if ($user_id && ($order->get_customer_id() === $user_id || current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders'))) {
		$can_view = true;
	} elseif ($order_key && hash_equals($order->get_order_key(), $order_key)) {
		$can_view = true;
	} elseif (!$order->get_customer_id() || current_user_can('administrator')) {
		$can_view = true;
	}

	if (!$can_view) {
		wp_die(__('You do not have permission to view this order confirmation.', 'cutlist-catalogue'), __('Access Denied', 'cutlist-catalogue'), array('response' => 403));
	}

	$template_file = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/print-order.php' : '';
	if ($template_file && file_exists($template_file)) {
		include $template_file;
		exit;
	}
}
add_action('template_redirect', 'cutlist_handle_print_order_page', 1);

/**
 * Clears the saved cutting list draft from localStorage once an order has been successfully placed.
 */
function cutlist_clear_draft_on_order_received($order_id)
{
	?>
	<script>
	try {
		if (typeof localStorage !== 'undefined') {
			localStorage.removeItem('cutlist_draft');
		}
	} catch (e) {}
	</script>
	<?php
}
add_action('woocommerce_thankyou', 'cutlist_clear_draft_on_order_received', 10, 1);
