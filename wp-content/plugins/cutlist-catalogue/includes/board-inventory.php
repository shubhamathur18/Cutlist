<?php
/**
 * Board Inventory Management & Automated Stock Deduction Engine
 * 
 * Handles internal workshop inventory tracking for the `board` CPT:
 * - Full Sheet purchase deductions
 * - Cutlist / Custom Panel sheet consumption calculations (nesting, kerf, and trim waste)
 * - Automatic deduction on WooCommerce checkout/payment
 * - Automatic stock restoration on order cancellation/refund
 * - Workshop Stock Activity Log & Admin UI Meta Boxes
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolves a Board post ID from a decor code or name string.
 *
 * @param string $decor_str
 * @return int Board post ID or 0 if not found.
 */
function cutlist_get_board_by_decor($decor_str)
{
	if (empty($decor_str)) {
		return 0;
	}

	$decor_code = trim(explode(' - ', $decor_str)[0]);
	$decor_code = trim(explode(' / ', $decor_code)[0]);

	// 1. Search by exact decor_code meta
	if (!empty($decor_code)) {
		$posts = get_posts(array(
			'post_type'      => 'board',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'decor_code',
					'value'   => $decor_code,
					'compare' => '=',
				),
			),
		));

		if (!empty($posts)) {
			return (int) $posts[0];
		}
	}

	// 2. Search by post title
	$title_posts = get_posts(array(
		'post_type'      => 'board',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'title'          => $decor_code,
	));

	if (!empty($title_posts)) {
		return (int) $title_posts[0];
	}

	// 3. Fallback search by LIKE in post_title
	$all_boards = get_posts(array(
		'post_type'      => 'board',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
	));

	foreach ($all_boards as $b) {
		if (stripos($b->post_title, $decor_code) !== false) {
			return (int) $b->ID;
		}
		$d_code = get_post_meta($b->ID, 'decor_code', true);
		if ($d_code && stripos($decor_code, $d_code) !== false) {
			return (int) $b->ID;
		}
	}

	return 0;
}

/**
 * Calculates raw full sheets consumed for a given collection of cut panels.
 * Takes panel dimensions, quantities, blade kerf, and perimeter trim into account (1.15x nesting factor).
 *
 * @param array $cut_items Array of items: [ ['length' => 700, 'width' => 500, 'qty' => 2], ... ]
 * @param int $board_id Board post ID
 * @return array Calculation breakdown: ['sheets_consumed' => int, 'raw_fraction' => float, 'total_panel_area_m2' => float, 'panel_count' => int]
 */
function cutlist_calculate_cutlist_sheet_requirement($cut_items, $board_id = 0)
{
	$default_len = 2800;
	$default_wid = 2070;

	if ($board_id > 0) {
		$b_len = (int) get_post_meta($board_id, 'length_mm', true);
		$b_wid = (int) get_post_meta($board_id, 'width_mm', true);
		if ($b_len > 0) $default_len = $b_len;
		if ($b_wid > 0) $default_wid = $b_wid;
	}

	$single_sheet_area_mm2 = (float) $default_len * (float) $default_wid;
	if ($single_sheet_area_mm2 <= 0) {
		$single_sheet_area_mm2 = 2800 * 2070;
	}

	$total_cut_area_mm2 = 0;
	$total_panel_count = 0;

	if (is_array($cut_items)) {
		foreach ($cut_items as $item) {
			$len = isset($item['length']) ? (float) $item['length'] : 0;
			$wid = isset($item['width']) ? (float) $item['width'] : 0;
			$qty = isset($item['qty']) ? max(1, (int) $item['qty']) : 1;

			if ($len > 0 && $wid > 0) {
				$total_cut_area_mm2 += ($len * $wid * $qty);
				$total_panel_count += $qty;
			}
		}
	}

	if ($total_panel_count === 0 || $total_cut_area_mm2 <= 0) {
		return array(
			'sheets_consumed'    => 0,
			'raw_fraction'       => 0,
			'total_panel_area_m2'=> 0,
			'panel_count'        => 0,
		);
	}

	// 1.15x factor for saw blade kerf (3.2mm), panel trimming, and nesting geometry waste
	$nested_area_needed_mm2 = $total_cut_area_mm2 * 1.15;
	$raw_fraction = $nested_area_needed_mm2 / $single_sheet_area_mm2;
	$sheets_consumed = max(1, (int) ceil($raw_fraction));

	return array(
		'sheets_consumed'     => $sheets_consumed,
		'raw_fraction'        => round($raw_fraction, 2),
		'total_panel_area_m2' => round($total_cut_area_mm2 / 1000000, 3),
		'panel_count'         => $total_panel_count,
	);
}

/**
 * Resolves the real-time internal stock status for a board.
 *
 * @param int $board_id
 * @return string 'instock' | 'lowstock' | 'outofstock' | 'on_order' | 'discontinued'
 */
function cutlist_get_board_stock_status($board_id)
{
	$board_id = (int) $board_id;
	if (!$board_id || get_post_type($board_id) !== 'board') {
		return 'instock';
	}

	$saved_status = get_post_meta($board_id, 'internal_stock_status', true);
	if (!empty($saved_status) && in_array($saved_status, array('on_order', 'discontinued'), true)) {
		return $saved_status;
	}

	$manage_stock = get_post_meta($board_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		return !empty($saved_status) ? $saved_status : 'instock';
	}

	$stock_meta = get_post_meta($board_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$reorder = (int) get_post_meta($board_id, 'stock_reorder_level', true);
	if ($reorder <= 0) $reorder = 5;

	if ($stock <= 0) {
		return 'outofstock';
	} elseif ($stock <= $reorder) {
		return 'lowstock';
	} else {
		return 'instock';
	}
}

/**
 * Checks whether a board has managed stock and returns the available physical full sheet count.
 *
 * @param int $board_id
 * @return array ['is_managed' => bool, 'stock' => int, 'status' => string, 'is_available' => bool]
 */
function cutlist_get_board_available_stock($board_id)
{
	$board_id = (int) $board_id;
	if (!$board_id || get_post_type($board_id) !== 'board') {
		return array(
			'is_managed'   => false,
			'stock'        => 999999,
			'status'       => 'instock',
			'is_available' => true,
		);
	}

	$manage_stock = get_post_meta($board_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		return array(
			'is_managed'   => false,
			'stock'        => 999999,
			'status'       => 'instock',
			'is_available' => true,
		);
	}

	$stock_meta = get_post_meta($board_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$status = cutlist_get_board_stock_status($board_id);

	$is_avail = ($stock > 0 && $status !== 'outofstock');

	return array(
		'is_managed'   => true,
		'stock'        => max(0, $stock),
		'status'       => $status,
		'is_available' => $is_avail,
	);
}

/**
 * Validates the contents of WooCommerce cart against physical board inventory.
 * 
 * @return array Array of stock error strings, or empty array if all items have sufficient stock.
 */
function cutlist_validate_cart_board_stock()
{
	if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
		return array();
	}

	$full_sheets_by_board = array();
	$cut_panels_by_board = array();

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		$item_type = isset($cart_item['cutlist_type']) ? $cart_item['cutlist_type'] : '';
		$item_title = isset($cart_item['cutlist_title']) ? $cart_item['cutlist_title'] : '';
		$decor = isset($cart_item['cutlist_decor']) ? $cart_item['cutlist_decor'] : '';
		$qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

		$board_id = 0;
		if (!empty($decor)) {
			$board_id = cutlist_get_board_by_decor($decor);
		}
		if (!$board_id && !empty($item_title)) {
			$board_id = cutlist_get_board_by_decor($item_title);
		}

		if (!$board_id) {
			continue;
		}

		if (stripos($item_type, 'Full Sheet') !== false || stripos($item_title, 'Full Sheet') !== false) {
			if (!isset($full_sheets_by_board[$board_id])) {
				$full_sheets_by_board[$board_id] = 0;
			}
			$full_sheets_by_board[$board_id] += $qty;
		} elseif (stripos($item_type, 'Cut Panel') !== false || isset($cart_item['cutlist_length'])) {
			$len = isset($cart_item['cutlist_length']) ? (float) $cart_item['cutlist_length'] : 0;
			$wid = isset($cart_item['cutlist_width']) ? (float) $cart_item['cutlist_width'] : 0;

			if (!isset($cut_panels_by_board[$board_id])) {
				$cut_panels_by_board[$board_id] = array();
			}
			$cut_panels_by_board[$board_id][] = array(
				'length' => $len,
				'width'  => $wid,
				'qty'    => $qty,
			);
		}
	}

	$all_board_ids = array_unique(array_merge(array_keys($full_sheets_by_board), array_keys($cut_panels_by_board)));
	$errors = array();

	foreach ($all_board_ids as $board_id) {
		$avail_info = cutlist_get_board_available_stock($board_id);
		if (!$avail_info['is_managed']) {
			continue;
		}

		$avail_stock = $avail_info['stock'];
		$board_name = get_the_title($board_id);

		$needed_sheets = 0;
		if (isset($full_sheets_by_board[$board_id])) {
			$needed_sheets += $full_sheets_by_board[$board_id];
		}
		if (isset($cut_panels_by_board[$board_id])) {
			$calc = cutlist_calculate_cutlist_sheet_requirement($cut_panels_by_board[$board_id], $board_id);
			$needed_sheets += $calc['sheets_consumed'];
		}

		if ($needed_sheets > $avail_stock) {
			if ($avail_stock <= 0) {
				$errors[] = sprintf(
					__('Sorry, "%s" is currently out of stock. Please remove it from your cart before proceeding to checkout.', 'cutlist-catalogue'),
					$board_name
				);
			} else {
				$errors[] = sprintf(
					__('Insufficient stock for "%s". Your order requires %d sheet(s), but only %d sheet(s) are currently in stock.', 'cutlist-catalogue'),
					$board_name,
					$needed_sheets,
					$avail_stock
				);
			}
		}
	}

	return $errors;
}

/**
 * Updates a board's physical sheet stock, auto-adjusts stock status, and appends a log entry.
 *
 * @param int $board_id Board post ID
 * @param int $qty_change Positive for restock, negative for deduction (e.g. -3)
 * @param string $action_type e.g. 'order_full_sheet', 'order_cutlist', 'order_cancelled', 'manual_adjustment', 'manual_restock'
 * @param string $reference e.g. 'Order #1042', 'Manual restock'
 * @param string $note Additional notes
 * @return int New stock quantity
 */
function cutlist_update_board_stock($board_id, $qty_change, $action_type, $reference = '', $note = '')
{
	$board_id = (int) $board_id;
	if (!$board_id || get_post_type($board_id) !== 'board') {
		return 0;
	}

	$current_stock = (int) get_post_meta($board_id, 'stock_sheets_qty', true);
	$new_stock = max(0, $current_stock + (int) $qty_change);

	// Update stock meta
	update_post_meta($board_id, 'stock_sheets_qty', $new_stock);
	if (function_exists('update_field')) {
		update_field('stock_sheets_qty', $new_stock, $board_id);
	}

	// Auto-update internal stock status based on reorder threshold
	$reorder_level = (int) get_post_meta($board_id, 'stock_reorder_level', true);
	if ($reorder_level <= 0) $reorder_level = 5;

	$status = 'instock';
	if ($new_stock <= 0) {
		$status = 'outofstock';
	} elseif ($new_stock <= $reorder_level) {
		$status = 'lowstock';
	}

	update_post_meta($board_id, 'internal_stock_status', $status);
	if (function_exists('update_field')) {
		update_field('internal_stock_status', $status, $board_id);
	}

	// Append to stock activity history log
	$log = get_post_meta($board_id, '_cutlist_stock_log', true);
	if (!is_array($log)) {
		$log = array();
	}

	$current_user = wp_get_current_user();
	$user_name = ($current_user && $current_user->ID) ? $current_user->display_name : 'System / Automated';

	$log_entry = array(
		'timestamp'      => current_time('mysql'),
		'date'           => current_time('d/m/Y H:i'),
		'qty_change'     => (int) $qty_change,
		'previous_stock' => (int) $current_stock,
		'new_stock'      => (int) $new_stock,
		'action_type'    => sanitize_text_field($action_type),
		'reference'      => sanitize_text_field($reference),
		'note'           => sanitize_text_field($note),
		'user'           => $user_name,
	);

	// Keep last 100 log entries
	array_unshift($log, $log_entry);
	if (count($log) > 100) {
		$log = array_slice($log, 0, 100);
	}

	update_post_meta($board_id, '_cutlist_stock_log', $log);

	return $new_stock;
}

/**
 * Retrieves the stock movement history log for a board.
 *
 * @param int $board_id
 * @return array
 */
function cutlist_get_board_stock_log($board_id)
{
	$log = get_post_meta($board_id, '_cutlist_stock_log', true);
	return is_array($log) ? $log : array();
}

/**
 * Processes automated stock deduction for a WooCommerce Order.
 * Group order items into Full Sheets and Cutlist jobs, then deducts from matching boards.
 *
 * @param int $order_id
 */
function cutlist_process_order_stock_deduction($order_id)
{
	if (!$order_id || !class_exists('WC_Order')) {
		return;
	}

	// Prevent duplicate deductions
	if (get_post_meta($order_id, '_cutlist_stock_deducted', true)) {
		return;
	}

	$order = wc_get_order($order_id);
	if (!$order) {
		return;
	}

	$full_sheets_by_board = array();
	$cut_panels_by_board = array();
	$material_pull_summary = array();

	foreach ($order->get_items() as $item_id => $item) {
		$item_name = $item->get_name();
		$item_qty = (int) $item->get_quantity();
		$decor = $item->get_meta(__('Decor', 'cutlist-catalogue'), true) ?: $item->get_meta('Decor', true);
		$item_type = $item->get_meta(__('Item Type', 'cutlist-catalogue'), true) ?: $item->get_meta('Item Type', true);
		$dims = $item->get_meta(__('Dimensions', 'cutlist-catalogue'), true) ?: $item->get_meta('Dimensions', true);

		// Resolve Board ID
		$board_id = 0;
		if (!empty($decor)) {
			$board_id = cutlist_get_board_by_decor($decor);
		}
		if (!$board_id) {
			$board_id = cutlist_get_board_by_decor($item_name);
		}

		if (!$board_id) {
			continue;
		}

		// Check if Full Sheet item
		if (stripos($item_type, 'Full Sheet') !== false || stripos($item_name, 'Full Sheet') !== false) {
			if (!isset($full_sheets_by_board[$board_id])) {
				$full_sheets_by_board[$board_id] = 0;
			}
			$full_sheets_by_board[$board_id] += $item_qty;
		} else {
			// Cutlist / Custom Panel item
			$len = 0;
			$wid = 0;
			if (!empty($dims)) {
				$parts = explode('x', str_replace('mm', '', strtolower($dims)));
				if (count($parts) >= 2) {
					$len = (float) trim($parts[0]);
					$wid = (float) trim($parts[1]);
				}
			}

			if (!isset($cut_panels_by_board[$board_id])) {
				$cut_panels_by_board[$board_id] = array();
			}

			$cut_panels_by_board[$board_id][] = array(
				'length' => $len,
				'width'  => $wid,
				'qty'    => $item_qty,
			);
		}
	}

	$order_number = $order->get_order_number();
	$cust_name = $order->get_formatted_billing_full_name();
	$order_ref = sprintf(__('Order #%s (%s)', 'cutlist-catalogue'), $order_number, $cust_name ?: __('Customer', 'cutlist-catalogue'));

	// 1. Deduct Full Sheet Orders
	foreach ($full_sheets_by_board as $board_id => $qty_sheets) {
		if ($qty_sheets > 0) {
			$b_title = get_the_title($board_id);
			$rack = get_post_meta($board_id, 'rack_location', true) ?: __('Workshop Floor', 'cutlist-catalogue');

			cutlist_update_board_stock(
				$board_id,
				-$qty_sheets,
				'order_full_sheet',
				$order_ref,
				sprintf(__('%d full sheet(s) purchased uncut in Order #%s', 'cutlist-catalogue'), $qty_sheets, $order_number)
			);

			$material_pull_summary[] = array(
				'board_id'        => $board_id,
				'board_name'      => $b_title,
				'rack_location'   => $rack,
				'sheets_required' => $qty_sheets,
				'order_type'      => 'full_sheet',
				'notes'           => sprintf(__('%d Full Sheet(s)', 'cutlist-catalogue'), $qty_sheets),
			);
		}
	}

	// 2. Deduct Cutlist / Panel Job Orders
	foreach ($cut_panels_by_board as $board_id => $panels) {
		$calc = cutlist_calculate_cutlist_sheet_requirement($panels, $board_id);
		$sheets_needed = $calc['sheets_consumed'];

		if ($sheets_needed > 0) {
			$b_title = get_the_title($board_id);
			$rack = get_post_meta($board_id, 'rack_location', true) ?: __('Workshop Floor', 'cutlist-catalogue');

			cutlist_update_board_stock(
				$board_id,
				-$sheets_needed,
				'order_cutlist',
				$order_ref,
				sprintf(
					__('Cutlist job: %d panel(s), %s m² net area -> %d sheet(s) consumed (Order #%s)', 'cutlist-catalogue'),
					$calc['panel_count'],
					$calc['total_panel_area_m2'],
					$sheets_needed,
					$order_number
				)
			);

			$material_pull_summary[] = array(
				'board_id'        => $board_id,
				'board_name'      => $b_title,
				'rack_location'   => $rack,
				'sheets_required' => $sheets_needed,
				'order_type'      => 'cutlist',
				'notes'           => sprintf(__('%d sheet(s) for %d cut panel(s) (%s m²)', 'cutlist-catalogue'), $sheets_needed, $calc['panel_count'], $calc['total_panel_area_m2']),
			);
		}
	}

	// Mark order as processed for inventory
	update_post_meta($order_id, '_cutlist_stock_deducted', 1);
	update_post_meta($order_id, '_cutlist_material_pull_summary', $material_pull_summary);
}

/**
 * Restores deducted stock back to board inventory when an order is cancelled or refunded.
 *
 * @param int $order_id
 */
function cutlist_restore_order_stock_deduction($order_id)
{
	if (!$order_id) {
		return;
	}

	$is_deducted = get_post_meta($order_id, '_cutlist_stock_deducted', true);
	$is_restored = get_post_meta($order_id, '_cutlist_stock_restored', true);

	if (!$is_deducted || $is_restored) {
		return;
	}

	$pull_summary = get_post_meta($order_id, '_cutlist_material_pull_summary', true);
	if (!is_array($pull_summary) || empty($pull_summary)) {
		return;
	}

	$order = wc_get_order($order_id);
	$order_number = $order ? $order->get_order_number() : $order_id;
	$order_ref = sprintf(__('Cancelled / Refunded Order #%s', 'cutlist-catalogue'), $order_number);

	foreach ($pull_summary as $item) {
		$board_id = isset($item['board_id']) ? (int) $item['board_id'] : 0;
		$sheets = isset($item['sheets_required']) ? (int) $item['sheets_required'] : 0;

		if ($board_id > 0 && $sheets > 0) {
			cutlist_update_board_stock(
				$board_id,
				$sheets,
				'order_cancelled',
				$order_ref,
				sprintf(__('%d sheet(s) returned to stock due to cancellation/refund of Order #%s', 'cutlist-catalogue'), $sheets, $order_number)
			);
		}
	}

	update_post_meta($order_id, '_cutlist_stock_restored', 1);
}

// =============================================================================
// WOOCOMMERCE ORDER HOOKS (Automated Stock Deductions & Restorations)
// =============================================================================

add_action('woocommerce_checkout_order_processed', 'cutlist_process_order_stock_deduction', 20, 1);
add_action('woocommerce_order_status_processing', 'cutlist_process_order_stock_deduction', 20, 1);
add_action('woocommerce_order_status_completed', 'cutlist_process_order_stock_deduction', 20, 1);
add_action('woocommerce_payment_complete', 'cutlist_process_order_stock_deduction', 20, 1);

add_action('woocommerce_order_status_cancelled', 'cutlist_restore_order_stock_deduction', 20, 1);
add_action('woocommerce_order_status_refunded', 'cutlist_restore_order_stock_deduction', 20, 1);

// =============================================================================
// WP-ADMIN META BOX: INVENTORY (Clean, Standard WooCommerce Style)
// =============================================================================

add_action('add_meta_boxes', function () {
	add_meta_box(
		'cutlist_board_inventory_box',
		__('Inventory', 'cutlist-catalogue'),
		'cutlist_render_board_inventory_meta_box',
		'board',
		'normal',
		'high'
	);
});

/**
 * Renders the clean WooCommerce-style Inventory Meta Box on Board Edit Screen.
 */
function cutlist_render_board_inventory_meta_box($post)
{
	$post_id = $post->ID;
	$stock_meta = get_post_meta($post_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$reorder = (int) get_post_meta($post_id, 'stock_reorder_level', true);
	if ($reorder <= 0) $reorder = 5;
	$rack = get_post_meta($post_id, 'rack_location', true);
	$sku = get_post_meta($post_id, 'supplier_sku', true);
	$manage_stock = get_post_meta($post_id, 'manage_stock', true);
	if ($manage_stock === '') $manage_stock = 'yes'; // Default to managed

	$saved_status = get_post_meta($post_id, 'internal_stock_status', true);
	if (!empty($saved_status) && $saved_status === 'on_order') {
		$status = 'on_order';
	} elseif ($manage_stock === 'yes') {
		if ($stock <= 0) {
			$status = 'outofstock';
		} elseif ($stock <= $reorder) {
			$status = 'lowstock';
		} else {
			$status = !empty($saved_status) ? $saved_status : 'instock';
		}
	} else {
		$status = !empty($saved_status) ? $saved_status : 'instock';
	}

	$log = cutlist_get_board_stock_log($post_id);

	wp_nonce_field('cutlist_save_board_inventory', 'cutlist_inventory_nonce');
	?>
	<div class="cutlist-wc-options-panel" style="padding: 10px 14px; background: #fff;">
		<style>
			.cutlist-wc-options-panel .form-field {
				margin: 12px 0;
				display: flex;
				align-items: center;
				flex-wrap: wrap;
			}
			.cutlist-wc-options-panel .form-field label {
				width: 170px;
				font-size: 13px;
				font-weight: 600;
				color: #1e293b;
				margin: 0;
				padding-right: 12px;
				box-sizing: border-box;
			}
			.cutlist-wc-options-panel .form-field input[type="text"],
			.cutlist-wc-options-panel .form-field input[type="number"],
			.cutlist-wc-options-panel .form-field select {
				max-width: 260px;
				width: 100%;
				height: 32px;
				padding: 0 8px;
				font-size: 13px;
				border: 1px solid #cbd5e1;
				border-radius: 4px;
				box-sizing: border-box;
			}
			.cutlist-wc-options-panel .form-field .description {
				margin-left: 10px;
				color: #64748b;
				font-size: 12px;
			}
			.cutlist-wc-options-panel .form-field .checkbox {
				margin: 0 6px 0 0;
			}
		</style>

		<div class="form-field">
			<label for="cutlist_supplier_sku"><?php esc_html_e('SKU', 'cutlist-catalogue'); ?></label>
			<input name="cutlist_supplier_sku" id="cutlist_supplier_sku" type="text" value="<?php echo esc_attr($sku); ?>" placeholder="e.g. H1180-ST37">
			<span class="description"><?php esc_html_e('Supplier/product identifier code.', 'cutlist-catalogue'); ?></span>
		</div>

		<div class="form-field">
			<label for="cutlist_manage_stock"><?php esc_html_e('Manage stock?', 'cutlist-catalogue'); ?></label>
			<label style="font-weight: normal; width: auto; display: inline-flex; align-items: center; cursor: pointer;">
				<input type="checkbox" class="checkbox" name="cutlist_manage_stock" id="cutlist_manage_stock" value="yes" <?php checked($manage_stock, 'yes'); ?> onchange="document.getElementById('cutlist_stock_fields_wrap').style.display = this.checked ? 'block' : 'none';">
				<?php esc_html_e('Track stock quantity for whole sheets', 'cutlist-catalogue'); ?>
			</label>
		</div>

		<div id="cutlist_stock_fields_wrap" style="<?php echo ($manage_stock === 'no') ? 'display:none;' : ''; ?>">
			<div class="form-field">
				<label for="cutlist_stock_sheets_qty"><?php esc_html_e('Stock quantity', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_stock_sheets_qty" id="cutlist_stock_sheets_qty" type="number" step="1" min="0" value="<?php echo esc_attr($stock); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncStockStatus()">
				<span class="description"><?php esc_html_e('Full uncut sheets in workshop (auto-deducted on checkout).', 'cutlist-catalogue'); ?></span>
			</div>

			<div class="form-field">
				<label for="cutlist_stock_reorder_level"><?php esc_html_e('Low stock threshold', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_stock_reorder_level" id="cutlist_stock_reorder_level" type="number" step="1" min="0" value="<?php echo esc_attr($reorder); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncStockStatus()">
				<span class="description"><?php esc_html_e('When stock reaches this amount, status switches to Low Stock.', 'cutlist-catalogue'); ?></span>
			</div>
		</div>

		<div class="form-field">
			<label for="cutlist_internal_stock_status"><?php esc_html_e('Stock status', 'cutlist-catalogue'); ?></label>
			<select name="cutlist_internal_stock_status" id="cutlist_internal_stock_status">
				<option value="instock" <?php selected($status, 'instock'); ?>><?php esc_html_e('In stock', 'cutlist-catalogue'); ?></option>
				<option value="lowstock" <?php selected($status, 'lowstock'); ?>><?php esc_html_e('Low stock', 'cutlist-catalogue'); ?></option>
				<option value="outofstock" <?php selected($status, 'outofstock'); ?>><?php esc_html_e('Out of stock', 'cutlist-catalogue'); ?></option>
				<option value="on_order" <?php selected($status, 'on_order'); ?>><?php esc_html_e('On backorder / Reorder placed', 'cutlist-catalogue'); ?></option>
			</select>
		</div>

		<script>
			function cutlistAutoSyncStockStatus() {
				var qtyInput = document.getElementById('cutlist_stock_sheets_qty');
				var threshInput = document.getElementById('cutlist_stock_reorder_level');
				var statusSelect = document.getElementById('cutlist_internal_stock_status');
				if (!qtyInput || !threshInput || !statusSelect) return;
				
				var qty = parseInt(qtyInput.value, 10);
				var thresh = parseInt(threshInput.value, 10) || 5;
				if (isNaN(qty)) qty = 0;

				if (statusSelect.value === 'on_order') return; // Don't override if manually set to on order

				if (qty <= 0) {
					statusSelect.value = 'outofstock';
				} else if (qty <= thresh) {
					statusSelect.value = 'lowstock';
				} else {
					statusSelect.value = 'instock';
				}
			}
		</script>

		<div class="form-field">
			<label for="cutlist_rack_location"><?php esc_html_e('Rack / Storage location', 'cutlist-catalogue'); ?></label>
			<input name="cutlist_rack_location" id="cutlist_rack_location" type="text" value="<?php echo esc_attr($rack); ?>" placeholder="e.g. Rack A - Bay 02">
			<span class="description"><?php esc_html_e('Workshop bay or shelf number.', 'cutlist-catalogue'); ?></span>
		</div>

		<!-- Collapsible Stock Movement History Log -->
		<details style="margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
			<summary style="cursor: pointer; font-weight: 600; color: #2271b1; font-size: 13px; padding: 4px 0; user-select: none;">
				<?php printf(esc_html__('Stock movement history (%d entries)', 'cutlist-catalogue'), count($log)); ?>
			</summary>
			
			<div style="margin-top: 10px;">
				<?php if (!empty($log)): ?>
					<table class="widefat striped" style="font-size: 12px;">
						<thead>
							<tr>
								<th style="font-weight: 600; width: 130px;"><?php esc_html_e('Date', 'cutlist-catalogue'); ?></th>
								<th style="font-weight: 600; width: 90px;"><?php esc_html_e('Change', 'cutlist-catalogue'); ?></th>
								<th style="font-weight: 600; width: 100px;"><?php esc_html_e('Balance', 'cutlist-catalogue'); ?></th>
								<th style="font-weight: 600;"><?php esc_html_e('Reference / Details', 'cutlist-catalogue'); ?></th>
								<th style="font-weight: 600; width: 130px;"><?php esc_html_e('Source', 'cutlist-catalogue'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($log as $entry): ?>
								<?php 
									$change = isset($entry['qty_change']) ? (int) $entry['qty_change'] : 0;
									$is_pos = $change > 0;
									$color = $is_pos ? '#15803d' : '#b91c1c';
									$change_str = ($is_pos ? '+' : '') . $change . ' sheets';
								?>
								<tr>
									<td style="color: #64748b;"><?php echo esc_html($entry['date']); ?></td>
									<td style="font-weight: 700; color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($change_str); ?></td>
									<td style="font-weight: 600;"><?php echo esc_html($entry['new_stock']); ?> sheets</td>
									<td>
										<strong><?php echo esc_html($entry['reference']); ?></strong>
										<?php if (!empty($entry['note'])): ?>
											<div style="color: #64748b; font-size: 11px; margin-top: 2px;"><?php echo esc_html($entry['note']); ?></div>
										<?php endif; ?>
									</td>
									<td style="color: #64748b;"><?php echo esc_html($entry['user']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<p style="color: #64748b; font-style: italic; margin: 8px 0; font-size: 12px;">
						<?php esc_html_e('No stock movements recorded yet. Changes will be logged automatically upon order checkout or manual stock edits.', 'cutlist-catalogue'); ?>
					</p>
				<?php endif; ?>
			</div>
		</details>
	</div>
	<?php
}

/**
 * Handles saving Inventory fields when the Board post is updated in WP-Admin.
 */
add_action('save_post_board', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!isset($_POST['cutlist_inventory_nonce']) || !wp_verify_nonce($_POST['cutlist_inventory_nonce'], 'cutlist_save_board_inventory')) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$manage_stock = isset($_POST['cutlist_manage_stock']) ? 'yes' : 'no';
	update_post_meta($post_id, 'manage_stock', $manage_stock);

	if (isset($_POST['cutlist_stock_sheets_qty'])) {
		$old_stock = (int) get_post_meta($post_id, 'stock_sheets_qty', true);
		$new_stock = max(0, (int) $_POST['cutlist_stock_sheets_qty']);
		$reorder = max(1, (int) ($_POST['cutlist_stock_reorder_level'] ?? 5));
		$rack = sanitize_text_field($_POST['cutlist_rack_location'] ?? '');
		$status = sanitize_text_field($_POST['cutlist_internal_stock_status'] ?? 'instock');
		$sku = sanitize_text_field($_POST['cutlist_supplier_sku'] ?? '');

		// Auto-sync status with stock quantity if stock management is enabled
		if ($manage_stock === 'yes' && $status !== 'on_order') {
			if ($new_stock <= 0) {
				$status = 'outofstock';
			} elseif ($new_stock <= $reorder) {
				$status = 'lowstock';
			} else {
				$status = 'instock';
			}
		}

		// Save meta values
		update_post_meta($post_id, 'stock_sheets_qty', $new_stock);
		update_post_meta($post_id, 'stock_reorder_level', $reorder);
		update_post_meta($post_id, 'rack_location', $rack);
		update_post_meta($post_id, 'internal_stock_status', $status);
		update_post_meta($post_id, 'supplier_sku', $sku);

		// Sync with ACF if active
		if (function_exists('update_field')) {
			update_field('stock_sheets_qty', $new_stock, $post_id);
			update_field('stock_reorder_level', $reorder, $post_id);
			update_field('rack_location', $rack, $post_id);
			update_field('internal_stock_status', $status, $post_id);
			update_field('supplier_sku', $sku, $post_id);
		}

		// Log change if stock quantity was manually modified in the edit screen
		$diff = $new_stock - $old_stock;
		if ($diff !== 0) {
			$log = get_post_meta($post_id, '_cutlist_stock_log', true);
			if (!is_array($log)) $log = array();

			$current_user = wp_get_current_user();
			$user_name = ($current_user && $current_user->ID) ? $current_user->display_name : 'Admin';

			$log_entry = array(
				'timestamp'      => current_time('mysql'),
				'date'           => current_time('d/m/Y H:i'),
				'qty_change'     => (int) $diff,
				'previous_stock' => (int) $old_stock,
				'new_stock'      => (int) $new_stock,
				'action_type'    => 'manual_edit',
				'reference'      => __('Manual Admin Edit', 'cutlist-catalogue'),
				'note'           => sprintf(__('Stock updated from %d to %d sheets', 'cutlist-catalogue'), $old_stock, $new_stock),
				'user'           => $user_name,
			);

			array_unshift($log, $log_entry);
			if (count($log) > 100) {
				$log = array_slice($log, 0, 100);
			}
			update_post_meta($post_id, '_cutlist_stock_log', $log);
		}
	}
});

// =============================================================================
// WP-ADMIN BOARD LIST TABLE COLUMNS (wp-admin > edit.php?post_type=board)
// =============================================================================

add_filter('manage_board_posts_columns', function ($columns) {
	$new_cols = array();
	foreach ($columns as $key => $title) {
		$new_cols[$key] = $title;
		if ($key === 'title') {
			$new_cols['stock_sheets'] = __('Stock (Sheets)', 'cutlist-catalogue');
			$new_cols['rack_location'] = __('Rack Location', 'cutlist-catalogue');
			$new_cols['stock_status'] = __('Stock Status', 'cutlist-catalogue');
		}
	}
	return $new_cols;
});

add_action('manage_board_posts_custom_column', function ($column, $post_id) {
	if ($column === 'stock_sheets') {
		$stock = (int) get_post_meta($post_id, 'stock_sheets_qty', true);
		$reorder = (int) get_post_meta($post_id, 'stock_reorder_level', true);
		if ($reorder <= 0) $reorder = 5;

		$color = '#15803d'; // Green
		if ($stock <= 0) {
			$color = '#b91c1c'; // Red
		} elseif ($stock <= $reorder) {
			$color = '#b45309'; // Orange
		}

		echo '<strong style="color:' . esc_attr($color) . '; font-size:14px;">' . esc_html($stock) . '</strong> <span style="color:#64748b; font-size:12px;">sheets</span>';
	} elseif ($column === 'rack_location') {
		$rack = get_post_meta($post_id, 'rack_location', true);
		echo $rack ? '📍 <strong>' . esc_html($rack) . '</strong>' : '<span style="color:#94a3b8;">—</span>';
	} elseif ($column === 'stock_status') {
		$status = cutlist_get_board_stock_status($post_id);
		$labels = array(
			'instock'      => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#dcfce7; color:#15803d;">' . __('In Stock', 'cutlist-catalogue') . '</span>',
			'lowstock'     => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#fef3c7; color:#b45309;">' . __('Low Stock', 'cutlist-catalogue') . '</span>',
			'outofstock'   => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#fee2e2; color:#b91c1c;">' . __('Out of Stock', 'cutlist-catalogue') . '</span>',
			'on_order'     => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#e0e7ff; color:#4338ca;">' . __('Reorder Placed', 'cutlist-catalogue') . '</span>',
			'discontinued' => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#f3f4f6; color:#4b5563;">' . __('Discontinued', 'cutlist-catalogue') . '</span>',
		);
		echo isset($labels[$status]) ? $labels[$status] : $labels['instock'];
	}
}, 10, 2);
