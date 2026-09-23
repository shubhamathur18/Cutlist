<?php
/**
 * Edge Tape Inventory Management & Automated Stock Deduction Engine
 * 
 * Handles internal workshop inventory tracking for the `edge_tape` CPT (Unit/Roll based):
 * - Unit / Roll quantity tracking in workshop
 * - Standalone Edge Tape purchase deductions
 * - Cutlist panel edge tape deductions
 * - Automatic deduction on WooCommerce checkout/payment
 * - Automatic stock restoration on order cancellation/refund
 * - Workshop Stock Activity Log & Admin UI Meta Boxes
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolves an Edge Tape post ID from a tape code, string, or decor code.
 *
 * @param string $tape_str
 * @return int Edge Tape post ID or 0 if not found.
 */
function cutlist_get_edge_tape_by_code($tape_str)
{
	if (empty($tape_str)) {
		return 0;
	}

	$clean_str = trim((string) $tape_str);

	// 1. Check for specific tape code pattern e.g. M1-42 or M1-22 in the string
	if (preg_match('/(M\d+-\d+)/i', $clean_str, $m)) {
		$posts = get_posts(array(
			'post_type'      => 'edge_tape',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'tape_code',
					'value'   => $m[1],
					'compare' => '=',
				),
			),
		));
		if (!empty($posts)) {
			return (int) $posts[0];
		}
	}

	// 2. Search by exact tape_code meta
	$tape_code = trim(explode(' / ', $clean_str)[0]);
	$tape_code = trim(explode(' - ', $tape_code)[0]);
	if (!empty($tape_code)) {
		$posts = get_posts(array(
			'post_type'      => 'edge_tape',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'tape_code',
					'value'   => $tape_code,
					'compare' => '=',
				),
			),
		));
		if (!empty($posts)) {
			return (int) $posts[0];
		}
	}

	// 3. Check if string contains a decor code (e.g. H1227-TM12, 0164-MG, etc.)
	if (preg_match('/([A-Z0-9]+-[A-Z0-9]+)/i', $clean_str, $dm)) {
		$decor_code = $dm[1];
		$all_tapes = get_posts(array(
			'post_type'      => 'edge_tape',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		));
		foreach ($all_tapes as $t) {
			$t_code = get_post_meta($t->ID, 'tape_code', true);
			if ($t_code === $decor_code) {
				return (int) $t->ID;
			}
			$boards = function_exists('get_field') ? get_field('boards', $t->ID) : get_post_meta($t->ID, 'boards', true);
			if (is_array($boards)) {
				foreach ($boards as $b_id) {
					$b_decor = function_exists('get_field') ? get_field('decor_code', $b_id) : get_post_meta($b_id, 'decor_code', true);
					if ($b_decor === $decor_code) {
						return (int) $t->ID;
					}
				}
			}
		}
	}

	// 4. Search by post title
	$title_posts = get_posts(array(
		'post_type'      => 'edge_tape',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'title'          => $tape_code,
	));
	if (!empty($title_posts)) {
		return (int) $title_posts[0];
	}

	// 5. Fallback search by LIKE in post_title
	$all_tapes = get_posts(array(
		'post_type'      => 'edge_tape',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
	));
	foreach ($all_tapes as $t) {
		if (stripos($t->post_title, $tape_code) !== false || stripos($clean_str, $t->post_title) !== false) {
			return (int) $t->ID;
		}
	}

	return 0;
}

/**
 * Resolves the real-time internal stock status for an edge tape.
 *
 * @param int $tape_id
 * @return string 'instock' | 'lowstock' | 'outofstock' | 'on_order' | 'discontinued'
 */
function cutlist_get_edge_tape_stock_status($tape_id)
{
	$tape_id = (int) $tape_id;
	if (!$tape_id || get_post_type($tape_id) !== 'edge_tape') {
		return 'instock';
	}

	$saved_status = get_post_meta($tape_id, 'internal_stock_status', true);
	if (!empty($saved_status) && in_array($saved_status, array('on_order', 'discontinued'), true)) {
		return $saved_status;
	}

	$manage_stock = get_post_meta($tape_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		return !empty($saved_status) ? $saved_status : 'instock';
	}

	$stock_meta = get_post_meta($tape_id, 'stock_units_qty', true);
	if ($stock_meta === '') {
		$stock_meta = get_post_meta($tape_id, 'stock_meters_qty', true);
	}
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$reorder = (int) get_post_meta($tape_id, 'stock_reorder_level', true);
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
 * Checks whether an edge tape has managed stock and returns the available physical units count.
 *
 * @param int $tape_id
 * @return array ['is_managed' => bool, 'stock' => int, 'status' => string, 'is_available' => bool]
 */
function cutlist_get_edge_tape_available_stock($tape_id)
{
	$tape_id = (int) $tape_id;
	if (!$tape_id || get_post_type($tape_id) !== 'edge_tape') {
		return array(
			'is_managed'   => false,
			'stock'        => 999999,
			'status'       => 'instock',
			'is_available' => true,
		);
	}

	$manage_stock = get_post_meta($tape_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		return array(
			'is_managed'   => false,
			'stock'        => 999999,
			'status'       => 'instock',
			'is_available' => true,
		);
	}

	$stock_meta = get_post_meta($tape_id, 'stock_units_qty', true);
	if ($stock_meta === '') {
		$stock_meta = get_post_meta($tape_id, 'stock_meters_qty', true);
	}
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$status = cutlist_get_edge_tape_stock_status($tape_id);

	$is_avail = ($stock > 0 && $status !== 'outofstock');

	return array(
		'is_managed'   => true,
		'stock'        => max(0, $stock),
		'status'       => $status,
		'is_available' => $is_avail,
	);
}

/**
 * Validates the contents of WooCommerce cart against physical edge tape inventory.
 * Respects the edgebanding condition: minimum 10 chargeable units for < 10m actual edgebanding.
 * 
 * @return array Array of stock error strings, or empty array if all items have sufficient stock.
 */
function cutlist_validate_cart_edge_tape_stock()
{
	if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
		return array();
	}

	$standalone_units_by_tape = array();
	$panel_meters_by_tape = array();

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		$item_type = isset($cart_item['cutlist_type']) ? $cart_item['cutlist_type'] : '';
		$qty = isset($cart_item['quantity']) ? max(1, (int) $cart_item['quantity']) : 1;

		// 1. Standalone Edge Tape item (e.g. rolls purchased directly)
		if (stripos($item_type, 'Edge Tape') !== false || stripos($item_type, 'Edging') !== false) {
			$tape_code = isset($cart_item['cutlist_tape_code']) ? $cart_item['cutlist_tape_code'] : ($cart_item['cutlist_title'] ?? '');
			$tape_id = cutlist_get_edge_tape_by_code($tape_code);
			if ($tape_id) {
				if (!isset($standalone_units_by_tape[$tape_id])) $standalone_units_by_tape[$tape_id] = 0;
				$standalone_units_by_tape[$tape_id] += $qty;
			}
		} else {
			// 2. Cutlist Panel item with applied edge banding
			if (isset($cart_item['cutlist_length']) && isset($cart_item['cutlist_width'])) {
				$len_m = ((float) $cart_item['cutlist_length']) / 1000.0;
				$wid_m = ((float) $cart_item['cutlist_width']) / 1000.0;

				$edges = array('L1', 'L2', 'W1', 'W2');
				foreach ($edges as $edge_key) {
					$edge_val = isset($cart_item['cutlist_edging_' . strtolower($edge_key)]) ? $cart_item['cutlist_edging_' . strtolower($edge_key)] : (isset($cart_item['cutlist_' . strtolower($edge_key)]) ? $cart_item['cutlist_' . strtolower($edge_key)] : '');
					
					if (!empty($edge_val) && $edge_val !== '-' && $edge_val !== 'none') {
						$edge_m = (strpos($edge_key, 'L') === 0 ? $len_m : $wid_m) * $qty;

						$tape_id = cutlist_get_edge_tape_by_code($edge_val);
						if (!$tape_id && isset($cart_item['cutlist_decor'])) {
							$tape_id = cutlist_get_edge_tape_by_code($cart_item['cutlist_decor']);
						}
						if ($tape_id && $edge_m > 0) {
							if (!isset($panel_meters_by_tape[$tape_id])) $panel_meters_by_tape[$tape_id] = 0.0;
							$panel_meters_by_tape[$tape_id] += $edge_m;
						}
					}
				}
			}
		}
	}

	// Calculate total required units by combining edgebanding condition (min 10 units) and standalone rolls
	$units_by_tape = array();

	foreach ($panel_meters_by_tape as $tape_id => $total_m) {
		$actual_m = round((float) $total_m, 1);
		if ($actual_m <= 0) continue;

		// Condition: actual amount < 10m -> minimum 10 units; otherwise ceil(actual)
		$eb_units = ($actual_m < 10.0) ? 10 : (int) ceil($actual_m);
		$units_by_tape[$tape_id] = ($units_by_tape[$tape_id] ?? 0) + $eb_units;
	}

	foreach ($standalone_units_by_tape as $tape_id => $rolls) {
		if ($rolls <= 0) continue;
		$units_by_tape[$tape_id] = ($units_by_tape[$tape_id] ?? 0) + $rolls;
	}

	$errors = array();

	foreach ($units_by_tape as $tape_id => $needed_units) {
		$avail_info = cutlist_get_edge_tape_available_stock($tape_id);
		if (!$avail_info['is_managed']) {
			continue;
		}

		$avail_stock = $avail_info['stock'];
		$tape_name = get_the_title($tape_id) ?: ('Edge Tape #' . $tape_id);

		if ($needed_units > $avail_stock) {
			if ($avail_stock <= 0) {
				$errors[] = sprintf(
					__('Sorry, edge tape "%s" is currently out of stock. Please select another edge tape before proceeding to checkout.', 'cutlist-catalogue'),
					$tape_name
				);
			} else {
				$errors[] = sprintf(
					__('Insufficient stock for edge tape "%s". Your order requires %d unit(s), but only %d unit(s) are currently in stock.', 'cutlist-catalogue'),
					$tape_name,
					$needed_units,
					$avail_stock
				);
			}
		}
	}

	return $errors;
}

/**
 * Updates an edge tape's physical unit stock, auto-adjusts stock status, and appends a log entry.
 *
 * @param int $tape_id Edge Tape post ID
 * @param int $qty_change Positive for restock, negative for deduction (e.g. -2)
 * @param string $action_type e.g. 'order_cutlist', 'order_standalone', 'order_cancelled', 'manual_edit', 'manual_restock'
 * @param string $reference e.g. 'Order #1042', 'Manual restock'
 * @param string $note Additional notes
 * @return int New stock quantity
 */
function cutlist_update_edge_tape_stock($tape_id, $qty_change, $action_type, $reference = '', $note = '')
{
	$tape_id = (int) $tape_id;
	if (!$tape_id || get_post_type($tape_id) !== 'edge_tape') {
		return 0;
	}

	$current_stock = (int) get_post_meta($tape_id, 'stock_units_qty', true);
	if ($current_stock === 0 && get_post_meta($tape_id, 'stock_meters_qty', true) !== '') {
		$current_stock = (int) get_post_meta($tape_id, 'stock_meters_qty', true);
	}
	$new_stock = max(0, $current_stock + (int) $qty_change);

	// Update stock meta
	update_post_meta($tape_id, 'stock_units_qty', $new_stock);
	update_post_meta($tape_id, 'stock_meters_qty', $new_stock);
	if (function_exists('update_field')) {
		update_field('stock_units_qty', $new_stock, $tape_id);
	}

	// Auto-update internal stock status based on reorder threshold
	$reorder_level = (int) get_post_meta($tape_id, 'stock_reorder_level', true);
	if ($reorder_level <= 0) $reorder_level = 5;

	$status = 'instock';
	if ($new_stock <= 0) {
		$status = 'outofstock';
	} elseif ($new_stock <= $reorder_level) {
		$status = 'lowstock';
	}

	update_post_meta($tape_id, 'internal_stock_status', $status);
	if (function_exists('update_field')) {
		update_field('internal_stock_status', $status, $tape_id);
	}

	// Append to stock activity history log
	$log = get_post_meta($tape_id, '_cutlist_stock_log', true);
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

	update_post_meta($tape_id, '_cutlist_stock_log', $log);

	return $new_stock;
}

/**
 * Retrieves the stock movement history log for an edge tape.
 *
 * @param int $tape_id
 * @return array
 */
function cutlist_get_edge_tape_stock_log($tape_id)
{
	$log = get_post_meta($tape_id, '_cutlist_stock_log', true);
	return is_array($log) ? $log : array();
}

/**
 * Processes automated stock deduction for Edge Tapes in a WooCommerce Order.
 * Respects the edgebanding details condition:
 * - Actual meters < 10m -> minimum 10 units deducted
 * - Actual meters >= 10m -> ceil(actual meters) units deducted
 *
 * @param int $order_id
 */
function cutlist_process_order_edge_tape_stock_deduction($order_id)
{
	if (!$order_id || !class_exists('WC_Order')) {
		return;
	}

	// Prevent duplicate deductions
	if (get_post_meta($order_id, '_cutlist_edge_tape_stock_deducted', true)) {
		return;
	}

	$order = wc_get_order($order_id);
	if (!$order) {
		return;
	}

	$standalone_units_by_tape = array();
	$panel_meters_by_tape = array();

	foreach ($order->get_items() as $item_id => $item) {
		$item_name = $item->get_name();
		$item_qty = max(1, (int) $item->get_quantity());
		$decor = $item->get_meta(__('Decor', 'cutlist-catalogue'), true) ?: ($item->get_meta('Decor', true) ?: $item->get_meta('cutlist_decor', true));
		$item_type = $item->get_meta(__('Item Type', 'cutlist-catalogue'), true) ?: ($item->get_meta('Item Type', true) ?: $item->get_meta('cutlist_type', true));
		$edging_meta = $item->get_meta(__('Edging Tape', 'cutlist-catalogue'), true) ?: ($item->get_meta('Edging Tape', true) ?: ($item->get_meta('_cutlist_edges', true) ?: $item->get_meta('cutlist_edges', true)));

		// 1. Standalone Edge Tape
		if (stripos($item_type, 'Edge Tape') !== false || stripos($item_type, 'Edging') !== false || stripos($item_name, 'Edging Tape') !== false) {
			$tape_id = cutlist_get_edge_tape_by_code($item_name);
			if (!$tape_id && !empty($decor)) {
				$tape_id = cutlist_get_edge_tape_by_code($decor);
			}
			if ($tape_id) {
				if (!isset($standalone_units_by_tape[$tape_id])) $standalone_units_by_tape[$tape_id] = 0;
				$standalone_units_by_tape[$tape_id] += $item_qty;
			}
		} else {
			// 2. Cutlist Panels with Edge Banding Details
			if (!empty($edging_meta) && $edging_meta !== '-' && $edging_meta !== 'none') {
				// Parse panel dimensions (mm -> m)
				$dims = $item->get_meta(__('Dimensions', 'cutlist-catalogue'), true) ?: ($item->get_meta('Dimensions', true) ?: $item->get_meta('cutlist_dimensions', true));
				$len_mm = (float) ($item->get_meta('cutlist_length', true) ?: 0);
				$wid_mm = (float) ($item->get_meta('cutlist_width', true) ?: 0);

				if (!$len_mm || !$wid_mm) {
					if (!empty($dims)) {
						$parts = explode('x', str_replace('mm', '', strtolower($dims)));
						if (count($parts) >= 2) {
							$len_mm = (float) trim($parts[0]);
							$wid_mm = (float) trim($parts[1]);
						}
					}
				}

				$len_m = $len_mm > 0 ? ($len_mm / 1000.0) : 0;
				$wid_m = $wid_mm > 0 ? ($wid_mm / 1000.0) : 0;

				// Parse each edge segment e.g. "L1: M1, L2: M1, W1: M1, W2: M1"
				$edge_tokens = explode(',', $edging_meta);
				foreach ($edge_tokens as $token) {
					$token = trim($token);
					if (empty($token)) continue;

					$parts = explode(':', $token, 2);
					$edge_key = strtoupper(trim($parts[0]));
					$edge_val = isset($parts[1]) ? trim($parts[1]) : '';

					if ($edge_val === '' || $edge_val === '-' || strtolower($edge_val) === 'none') {
						continue;
					}

					$edge_m = (strpos($edge_key, 'L') === 0 ? $len_m : $wid_m) * $item_qty;

					// Fallback to 1.0m if dimensions couldn't be parsed from metadata
					if ($edge_m <= 0) {
						$edge_m = 1.0 * $item_qty;
					}

					$tape_id = cutlist_get_edge_tape_by_code($edge_val);
					if (!$tape_id && !empty($decor)) {
						$tape_id = cutlist_get_edge_tape_by_code($decor);
					}
					if (!$tape_id) {
						$tape_id = cutlist_get_edge_tape_by_code($item_name);
					}

					if ($tape_id && $edge_m > 0) {
						if (!isset($panel_meters_by_tape[$tape_id])) {
							$panel_meters_by_tape[$tape_id] = 0.0;
						}
						$panel_meters_by_tape[$tape_id] += $edge_m;
					}
				}
			}
		}
	}

	$order_number = $order->get_order_number();
	$cust_name = $order->get_formatted_billing_full_name();
	$order_ref = sprintf(__('Order #%s (%s)', 'cutlist-catalogue'), $order_number, $cust_name ?: __('Customer', 'cutlist-catalogue'));

	$units_by_tape = array();
	$tape_notes = array();
	$pull_summary = array();

	// Process Edgebanding condition: actual < 10m -> minimum 10 units, otherwise ceil(actual)
	foreach ($panel_meters_by_tape as $tape_id => $total_m) {
		$actual_m = round((float) $total_m, 1);
		if ($actual_m <= 0) continue;

		if ($actual_m < 10.0) {
			$charged_units = 10;
			$condition_str = sprintf(__('actual amount %.1fm - min purchase amount 10m', 'cutlist-catalogue'), $actual_m);
		} else {
			$charged_units = (int) ceil($actual_m);
			$condition_str = sprintf(__('actual amount %.1fm', 'cutlist-catalogue'), $actual_m);
		}

		$units_by_tape[$tape_id] = ($units_by_tape[$tape_id] ?? 0) + $charged_units;
		$tape_notes[$tape_id][] = sprintf(__('%d unit(s) for edgebanding (%s)', 'cutlist-catalogue'), $charged_units, $condition_str);
	}

	// Process Standalone Edge Tape rolls
	foreach ($standalone_units_by_tape as $tape_id => $rolls) {
		if ($rolls <= 0) continue;
		$units_by_tape[$tape_id] = ($units_by_tape[$tape_id] ?? 0) + $rolls;
		$tape_notes[$tape_id][] = sprintf(__('%d unit(s) standalone edging tape', 'cutlist-catalogue'), $rolls);
	}

	// Deduct stock and build pull summary
	foreach ($units_by_tape as $tape_id => $units) {
		if ($units > 0) {
			$t_title = get_the_title($tape_id);
			$notes_desc = implode(', ', $tape_notes[$tape_id] ?? array());

			cutlist_update_edge_tape_stock(
				$tape_id,
				-$units,
				'order_cutlist',
				$order_ref,
				sprintf(__('%s in Order #%s', 'cutlist-catalogue'), $notes_desc, $order_number)
			);

			$pull_summary[] = array(
				'tape_id'        => $tape_id,
				'tape_name'      => $t_title,
				'units_required' => $units,
				'notes'          => sprintf(__('%d unit(s) for Order #%s (%s)', 'cutlist-catalogue'), $units, $order_number, $notes_desc),
			);
		}
	}

	// Mark order as processed for edge tape inventory
	update_post_meta($order_id, '_cutlist_edge_tape_stock_deducted', 1);
	update_post_meta($order_id, '_cutlist_edge_tape_pull_summary', $pull_summary);
}

/**
 * Restores deducted stock back to edge tape inventory when an order is cancelled or refunded.
 *
 * @param int $order_id
 */
function cutlist_restore_order_edge_tape_stock_deduction($order_id)
{
	if (!$order_id) {
		return;
	}

	$is_deducted = get_post_meta($order_id, '_cutlist_edge_tape_stock_deducted', true);
	$is_restored = get_post_meta($order_id, '_cutlist_edge_tape_stock_restored', true);

	if (!$is_deducted || $is_restored) {
		return;
	}

	$pull_summary = get_post_meta($order_id, '_cutlist_edge_tape_pull_summary', true);
	if (!is_array($pull_summary) || empty($pull_summary)) {
		return;
	}

	$order = wc_get_order($order_id);
	$order_number = $order ? $order->get_order_number() : $order_id;
	$order_ref = sprintf(__('Cancelled / Refunded Order #%s', 'cutlist-catalogue'), $order_number);

	foreach ($pull_summary as $item) {
		$tape_id = isset($item['tape_id']) ? (int) $item['tape_id'] : 0;
		$units = isset($item['units_required']) ? (int) $item['units_required'] : 0;

		if ($tape_id > 0 && $units > 0) {
			cutlist_update_edge_tape_stock(
				$tape_id,
				$units,
				'order_cancelled',
				$order_ref,
				sprintf(__('%d unit(s) returned to stock due to cancellation/refund of Order #%s', 'cutlist-catalogue'), $units, $order_number)
			);
		}
	}

	update_post_meta($order_id, '_cutlist_edge_tape_stock_restored', 1);
}

// =============================================================================
// WOOCOMMERCE ORDER HOOKS (Automated Stock Deductions & Restorations)
// =============================================================================

add_action('woocommerce_checkout_order_processed', 'cutlist_process_order_edge_tape_stock_deduction', 25, 1);
add_action('woocommerce_order_status_processing', 'cutlist_process_order_edge_tape_stock_deduction', 25, 1);
add_action('woocommerce_order_status_completed', 'cutlist_process_order_edge_tape_stock_deduction', 25, 1);
add_action('woocommerce_payment_complete', 'cutlist_process_order_edge_tape_stock_deduction', 25, 1);
add_action('woocommerce_thankyou', 'cutlist_process_order_edge_tape_stock_deduction', 25, 1);

add_action('woocommerce_order_status_cancelled', 'cutlist_restore_order_edge_tape_stock_deduction', 25, 1);
add_action('woocommerce_order_status_refunded', 'cutlist_restore_order_edge_tape_stock_deduction', 25, 1);

// =============================================================================
// WP-ADMIN META BOX: EDGE TAPE INVENTORY
// =============================================================================

add_action('add_meta_boxes', function () {
	add_meta_box(
		'cutlist_edge_tape_inventory_box',
		__('Inventory', 'cutlist-catalogue'),
		'cutlist_render_edge_tape_inventory_meta_box',
		'edge_tape',
		'normal',
		'high'
	);
});

/**
 * Renders the clean WooCommerce-style Inventory Meta Box on Edge Tape Edit Screen.
 */
function cutlist_render_edge_tape_inventory_meta_box($post)
{
	$post_id = $post->ID;
	$stock_meta = get_post_meta($post_id, 'stock_units_qty', true);
	if ($stock_meta === '') {
		$stock_meta = get_post_meta($post_id, 'stock_meters_qty', true);
	}
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 10;
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

	$log = cutlist_get_edge_tape_stock_log($post_id);

	wp_nonce_field('cutlist_save_edge_tape_inventory', 'cutlist_edge_tape_inventory_nonce');
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
			<label for="cutlist_tape_supplier_sku"><?php esc_html_e('SKU', 'cutlist-catalogue'); ?></label>
			<input name="cutlist_tape_supplier_sku" id="cutlist_tape_supplier_sku" type="text" value="<?php echo esc_attr($sku); ?>" placeholder="e.g. TAPE-M1-42">
			<span class="description"><?php esc_html_e('Supplier/product identifier code.', 'cutlist-catalogue'); ?></span>
		</div>

		<div class="form-field">
			<label for="cutlist_tape_manage_stock"><?php esc_html_e('Manage stock?', 'cutlist-catalogue'); ?></label>
			<label style="font-weight: normal; width: auto; display: inline-flex; align-items: center; cursor: pointer;">
				<input type="checkbox" class="checkbox" name="cutlist_tape_manage_stock" id="cutlist_tape_manage_stock" value="yes" <?php checked($manage_stock, 'yes'); ?> onchange="document.getElementById('cutlist_tape_stock_fields_wrap').style.display = this.checked ? 'block' : 'none';">
				<?php esc_html_e('Track stock quantity in units / rolls', 'cutlist-catalogue'); ?>
			</label>
		</div>

		<div id="cutlist_tape_stock_fields_wrap" style="<?php echo ($manage_stock === 'no') ? 'display:none;' : ''; ?>">
			<div class="form-field">
				<label for="cutlist_stock_units_qty"><?php esc_html_e('Stock quantity', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_stock_units_qty" id="cutlist_stock_units_qty" type="number" step="1" min="0" value="<?php echo esc_attr($stock); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncTapeStockStatus()">
				<span class="description"><?php esc_html_e('Units / rolls in workshop (auto-deducted on checkout).', 'cutlist-catalogue'); ?></span>
			</div>

			<div class="form-field">
				<label for="cutlist_tape_reorder_level"><?php esc_html_e('Low stock threshold', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_tape_reorder_level" id="cutlist_tape_reorder_level" type="number" step="1" min="0" value="<?php echo esc_attr($reorder); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncTapeStockStatus()">
				<span class="description"><?php esc_html_e('When stock reaches this level, status switches to Low Stock.', 'cutlist-catalogue'); ?></span>
			</div>
		</div>

		<div class="form-field">
			<label for="cutlist_tape_internal_stock_status"><?php esc_html_e('Stock status', 'cutlist-catalogue'); ?></label>
			<select name="cutlist_tape_internal_stock_status" id="cutlist_tape_internal_stock_status">
				<option value="instock" <?php selected($status, 'instock'); ?>><?php esc_html_e('In stock', 'cutlist-catalogue'); ?></option>
				<option value="lowstock" <?php selected($status, 'lowstock'); ?>><?php esc_html_e('Low stock', 'cutlist-catalogue'); ?></option>
				<option value="outofstock" <?php selected($status, 'outofstock'); ?>><?php esc_html_e('Out of stock', 'cutlist-catalogue'); ?></option>
				<option value="on_order" <?php selected($status, 'on_order'); ?>><?php esc_html_e('On backorder / Reorder placed', 'cutlist-catalogue'); ?></option>
			</select>
		</div>

		<script>
			function cutlistAutoSyncTapeStockStatus() {
				var qtyInput = document.getElementById('cutlist_stock_units_qty');
				var threshInput = document.getElementById('cutlist_tape_reorder_level');
				var statusSelect = document.getElementById('cutlist_tape_internal_stock_status');
				if (!qtyInput || !threshInput || !statusSelect) return;
				
				var qty = parseInt(qtyInput.value, 10);
				var thresh = parseInt(threshInput.value, 10) || 5;
				if (isNaN(qty)) qty = 0;

				if (statusSelect.value === 'on_order') return;

				if (qty <= 0) {
					statusSelect.value = 'outofstock';
				} else if (qty <= thresh) {
					statusSelect.value = 'lowstock';
				} else {
					statusSelect.value = 'instock';
				}
			}
		</script>

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
									$change_str = ($is_pos ? '+' : '') . $change . ' units';
								?>
								<tr>
									<td style="color: #64748b;"><?php echo esc_html($entry['date']); ?></td>
									<td style="font-weight: 700; color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($change_str); ?></td>
									<td style="font-weight: 600;"><?php echo esc_html($entry['new_stock']); ?> units</td>
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
 * Handles saving Inventory fields when the Edge Tape post is updated in WP-Admin.
 */
add_action('save_post_edge_tape', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!isset($_POST['cutlist_edge_tape_inventory_nonce']) || !wp_verify_nonce($_POST['cutlist_edge_tape_inventory_nonce'], 'cutlist_save_edge_tape_inventory')) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$manage_stock = isset($_POST['cutlist_tape_manage_stock']) ? 'yes' : 'no';
	update_post_meta($post_id, 'manage_stock', $manage_stock);

	if (isset($_POST['cutlist_stock_units_qty']) || isset($_POST['cutlist_stock_meters_qty'])) {
		$old_stock = (int) get_post_meta($post_id, 'stock_units_qty', true);
		$new_stock = max(0, (int) ($_POST['cutlist_stock_units_qty'] ?? $_POST['cutlist_stock_meters_qty'] ?? 0));
		$reorder = max(1, (int) ($_POST['cutlist_tape_reorder_level'] ?? 5));
		$rack = sanitize_text_field($_POST['cutlist_tape_rack_location'] ?? '');
		$status = sanitize_text_field($_POST['cutlist_tape_internal_stock_status'] ?? 'instock');
		$sku = sanitize_text_field($_POST['cutlist_tape_supplier_sku'] ?? '');

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
		update_post_meta($post_id, 'stock_units_qty', $new_stock);
		update_post_meta($post_id, 'stock_meters_qty', $new_stock);
		update_post_meta($post_id, 'stock_reorder_level', $reorder);
		update_post_meta($post_id, 'rack_location', $rack);
		update_post_meta($post_id, 'internal_stock_status', $status);
		update_post_meta($post_id, 'supplier_sku', $sku);

		// Sync with ACF if active
		if (function_exists('update_field')) {
			update_field('stock_units_qty', $new_stock, $post_id);
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
				'note'           => sprintf(__('Stock updated from %d to %d units', 'cutlist-catalogue'), $old_stock, $new_stock),
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
// WP-ADMIN EDGE TAPE LIST TABLE COLUMNS (wp-admin > edit.php?post_type=edge_tape)
// =============================================================================

add_filter('manage_edge_tape_posts_columns', function ($columns) {
	$new_cols = array();
	foreach ($columns as $key => $title) {
		$new_cols[$key] = $title;
		if ($key === 'title') {
			$new_cols['stock_units'] = __('Stock (Units)', 'cutlist-catalogue');
			$new_cols['stock_status'] = __('Stock Status', 'cutlist-catalogue');
		}
	}
	return $new_cols;
}, 20);

add_action('manage_edge_tape_posts_custom_column', function ($column, $post_id) {
	if ($column === 'stock_units') {
		$stock_meta = get_post_meta($post_id, 'stock_units_qty', true);
		if ($stock_meta === '') {
			$stock_meta = get_post_meta($post_id, 'stock_meters_qty', true);
		}
		$stock = ($stock_meta !== '') ? (int) $stock_meta : 10;
		$reorder = (int) get_post_meta($post_id, 'stock_reorder_level', true);
		if ($reorder <= 0) $reorder = 5;

		$color = '#15803d'; // Green
		if ($stock <= 0) {
			$color = '#b91c1c'; // Red
		} elseif ($stock <= $reorder) {
			$color = '#b45309'; // Orange
		}

		echo '<strong style="color:' . esc_attr($color) . '; font-size:14px;">' . esc_html($stock) . '</strong> <span style="color:#64748b; font-size:12px;">units</span>';
	} elseif ($column === 'stock_status') {
		$status = cutlist_get_edge_tape_stock_status($post_id);
		$labels = array(
			'instock'      => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#dcfce7; color:#15803d;">' . __('In Stock', 'cutlist-catalogue') . '</span>',
			'lowstock'     => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#fef3c7; color:#b45309;">' . __('Low Stock', 'cutlist-catalogue') . '</span>',
			'outofstock'   => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#fee2e2; color:#b91c1c;">' . __('Out of Stock', 'cutlist-catalogue') . '</span>',
			'on_order'     => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#e0e7ff; color:#4338ca;">' . __('Reorder Placed', 'cutlist-catalogue') . '</span>',
			'discontinued' => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#f3f4f6; color:#4b5563;">' . __('Discontinued', 'cutlist-catalogue') . '</span>',
		);
		echo isset($labels[$status]) ? $labels[$status] : $labels['instock'];
	}
}, 20, 2);
