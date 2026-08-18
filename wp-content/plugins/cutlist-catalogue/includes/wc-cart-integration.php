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
	update_post_meta($post_id, '_virtual', 'yes');
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

	// Optionally clear previous cutlist items from cart if requested
	if (!empty($data['clear_cart'])) {
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			if (isset($cart_item['cutlist_unique_key'])) {
				WC()->cart->remove_cart_item($cart_item_key);
			}
		}
	}

	$added_count = 0;

	// 1. Cut Panels
	if (!empty($data['cutItems']) && is_array($data['cutItems'])) {
		foreach ($data['cutItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$decor = !empty($item['decor']) ? sanitize_text_field($item['decor']) : __('Custom Panel', 'cutlist-catalogue');
			$thick = !empty($item['thick']) ? sanitize_text_field($item['thick']) : '';
			$length = !empty($item['length']) ? sanitize_text_field($item['length']) : '';
			$width = !empty($item['width']) ? sanitize_text_field($item['width']) : '';
			$desc = !empty($item['description']) ? sanitize_text_field($item['description']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

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

			// Build spray summary
			$spray_summary = '';
			if (!empty($item['spray']) && is_array($item['spray'])) {
				$s_opt = sanitize_text_field($item['spray']['option_name'] ?? $item['spray']['option'] ?? '');
				$s_sides = array();
				if (!empty($item['spray']['aSide']))
					$s_sides[] = 'A-Side';
				if (!empty($item['spray']['bSide']))
					$s_sides[] = 'B-Side';
				$spray_summary = $s_opt . ($s_sides ? ' (' . implode(' & ', $s_sides) . ')' : '');
			} elseif (!empty($item['spray_summary'])) {
				$spray_summary = sanitize_text_field($item['spray_summary']);
			}

			$cart_item_data = array(
				'cutlist_type' => __('Cut Panel', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Cut Panel: %s (%s)', 'cutlist-catalogue'), $decor, $thick ? $thick . 'mm' : 'Custom'),
				'cutlist_decor' => $decor,
				'cutlist_thick' => $thick ? $thick . 'mm' : '',
				'cutlist_length' => $length,
				'cutlist_width' => $width,
				'cutlist_description' => $desc,
				'cutlist_edges' => $edge_summary,
				'cutlist_machining' => $machining_summary,
				'cutlist_spray' => $spray_summary,
				'cutlist_unit_price' => $price,
				'cutlist_unique_key' => 'cut_' . md5($decor . $thick . $length . $width . $desc . $edge_summary . $machining_summary . $spray_summary . $price . $idx . microtime(true)),
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

			$cart_item_data = array(
				'cutlist_type' => __('Full Sheet', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Full Sheet: %s %s', 'cutlist-catalogue'), $brand, $decor),
				'cutlist_decor' => $decor,
				'cutlist_thick' => $thick ? $thick . 'mm' : '',
				'cutlist_dimensions' => $dims,
				'cutlist_brand' => $brand,
				'cutlist_unit_price' => $price,
				'cutlist_unique_key' => 'fs_' . md5($decor . $thick . $dims . $brand . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 3. Edging Tapes
	if (!empty($data['edgingTapeItems']) && is_array($data['edgingTapeItems'])) {
		foreach ($data['edgingTapeItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$code = !empty($item['code']) ? sanitize_text_field($item['code']) : '';
			$name = !empty($item['name']) ? sanitize_text_field($item['name']) : __('Edging Tape', 'cutlist-catalogue');
			$size = !empty($item['size']) ? sanitize_text_field($item['size']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

			$cart_item_data = array(
				'cutlist_type' => __('Edging Tape', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Edging Tape: %s (%s)', 'cutlist-catalogue'), $name, $code),
				'cutlist_code' => $code,
				'cutlist_name' => $name,
				'cutlist_dimensions' => $size,
				'cutlist_unit_price' => $price,
				'cutlist_unique_key' => 'et_' . md5($code . $name . $size . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 4. Standalone Machining Operations
	if (!empty($data['machiningItems']) && is_array($data['machiningItems'])) {
		foreach ($data['machiningItems'] as $idx => $item) {
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;
			$label = !empty($item['label']) ? sanitize_text_field($item['label']) : __('Machining Operation', 'cutlist-catalogue');
			$unit = !empty($item['unit']) ? sanitize_text_field($item['unit']) : '';
			$price = isset($item['unitPrice']) ? max(0, (float) $item['unitPrice']) : 0;

			$cart_item_data = array(
				'cutlist_type' => __('Machining Operation', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Machining: %s', 'cutlist-catalogue'), $label),
				'cutlist_description' => $unit ? sprintf(__('Unit: %s', 'cutlist-catalogue'), $unit) : '',
				'cutlist_unit_price' => $price,
				'cutlist_unique_key' => 'mc_' . md5($label . $unit . $price . $idx . microtime(true)),
			);

			WC()->cart->add_to_cart($product_id, $qty, 0, array(), $cart_item_data);
			$added_count++;
		}
	}

	// 5. Selected Offcuts
	if (!empty($data['selectedOffcuts']) && is_array($data['selectedOffcuts'])) {
		foreach ($data['selectedOffcuts'] as $idx => $offcut) {
			$qty = isset($offcut['qty']) ? max(1, (int) $offcut['qty']) : 1;
			$dims = is_string($offcut) ? sanitize_text_field($offcut) : sanitize_text_field($offcut['dimensions'] ?? $offcut['label'] ?? 'Offcut');
			$price = is_array($offcut) && isset($offcut['unitPrice']) ? (float) $offcut['unitPrice'] : 0;

			$cart_item_data = array(
				'cutlist_type' => __('Selected Offcut', 'cutlist-catalogue'),
				'cutlist_title' => sprintf(__('Selected Offcut: %s', 'cutlist-catalogue'), $dims),
				'cutlist_dimensions' => $dims,
				'cutlist_unit_price' => $price,
				'cutlist_unique_key' => 'off_' . md5($dims . $price . $idx . microtime(true)),
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
	));
}
add_action('wp_ajax_cutlist_add_to_cart', 'cutlist_ajax_add_to_cart');
add_action('wp_ajax_nopriv_cutlist_add_to_cart', 'cutlist_ajax_add_to_cart');

/**
 * Ensures custom cutlist prices are applied to cart items in WooCommerce.
 */
function cutlist_set_custom_cart_item_prices($cart)
{
	if (is_admin() && !defined('DOING_AJAX')) {
		return;
	}

	foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		if (isset($cart_item['cutlist_unit_price'])) {
			$cart_item['data']->set_price((float) $cart_item['cutlist_unit_price']);
		}
	}
}
add_action('woocommerce_before_calculate_totals', 'cutlist_set_custom_cart_item_prices', 10, 1);

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
 * Displays cutlist item attributes (Decor, Thickness, Dimensions, Edging, Machining, Spray, Offcuts)
 * on WooCommerce Cart and Checkout pages.
 */
function cutlist_render_cart_item_data($item_data, $cart_item)
{
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
			'name' => __('Description / Note', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_description']),
		);
	}
	if (!empty($cart_item['cutlist_edges'])) {
		$item_data[] = array(
			'name' => __('Edging Tape', 'cutlist-catalogue'),
			'value' => esc_html($cart_item['cutlist_edges']),
		);
	}
	if (!empty($cart_item['cutlist_machining'])) {
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
		$item->add_meta_data(__('Description / Note', 'cutlist-catalogue'), $values['cutlist_description']);
	}
	if (!empty($values['cutlist_edges'])) {
		$item->add_meta_data(__('Edging Tape', 'cutlist-catalogue'), $values['cutlist_edges']);
	}
	if (!empty($values['cutlist_machining'])) {
		$item->add_meta_data(__('Machining Operations', 'cutlist-catalogue'), $values['cutlist_machining']);
	}
	if (!empty($values['cutlist_spray'])) {
		$item->add_meta_data(__('Spray Finishing', 'cutlist-catalogue'), $values['cutlist_spray']);
	}
}
add_action('woocommerce_checkout_create_order_line_item', 'cutlist_add_order_line_item_meta', 10, 4);

/**
 * Renders an "Edit Cutting List" banner & link on WooCommerce Cart & Checkout pages
 * so users can easily return to the cutting list table to modify panel sizes/options.
 */
function cutlist_render_edit_cutting_list_button()
{
	$page = get_page_by_path('cutlist');
	$cutlist_url = $page ? get_permalink($page) : site_url('/cutlist/');
	?>
	<div class="cutlist-cart-notice"
		style="background:#eef6ff; border:1px solid #2f78bd; border-radius:6px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
		<div style="font-size:14px; color:#1d5a94; font-weight:500;">
			<strong><?php esc_html_e('Need to change panel sizes, edging, machining, or spray finishing?', 'cutlist-catalogue'); ?></strong>
		</div>
		<a href="<?php echo esc_url($cutlist_url); ?>" class="button"
			style="background:#2f78bd; color:#fff; padding:8px 16px; border-radius:4px; font-weight:600; text-decoration:none; display:inline-block;">
			✏️ <?php esc_html_e('Edit Cutting List', 'cutlist-catalogue'); ?>
		</a>
	</div>
	<?php
}
add_action('woocommerce_before_cart_table', 'cutlist_render_edit_cutting_list_button');
add_action('woocommerce_before_checkout_form', 'cutlist_render_edit_cutting_list_button');
