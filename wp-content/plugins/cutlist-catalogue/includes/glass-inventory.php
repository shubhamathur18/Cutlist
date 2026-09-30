<?php
/**
 * Glass Panel & Mirror Inventory Management & Automated Stock Deduction Engine
 * 
 * Handles internal workshop inventory tracking for the `sd_glass_panel` CPT:
 * - Stock quantity tracking (full sheets / panels in workshop)
 * - Automatic deduction on WooCommerce checkout/payment for Sliding Door orders
 * - Automatic stock restoration on order cancellation/refund
 * - Workshop Stock Activity Log & Admin UI Meta Boxes
 * - Pre-checkout & cart stock availability validation
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolves a Glass Panel post ID from a name, title, or formatted infill string.
 *
 * @param string $glass_str e.g. "Bronze Mirror [Glass - mirrors]" or "Silver Mirror" or "bronze-mirror"
 * @return int Glass Panel post ID or 0 if not found.
 */
function cutlist_get_glass_panel_by_name($glass_str)
{
	if (empty($glass_str)) {
		return 0;
	}

	$clean = trim((string) $glass_str);

	// Strip "[Glass - mirrors]" or "[Glass]" or "[Glass - colours]" if present
	$clean = preg_replace('/\[Glass.*?\]/i', '', $clean);
	$clean = trim($clean);

	// 1. Search by exact post title
	$title_posts = get_posts(array(
		'post_type'      => 'sd_glass_panel',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'title'          => $clean,
	));

	if (!empty($title_posts)) {
		return (int) $title_posts[0];
	}

	// 2. Search by post_name / slug (e.g. bronze-mirror, silver-mirror)
	$slug = sanitize_title($clean);
	$slug_posts = get_posts(array(
		'post_type'      => 'sd_glass_panel',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'name'           => $slug,
	));

	if (!empty($slug_posts)) {
		return (int) $slug_posts[0];
	}

	// 3. Search by supplier SKU meta
	$sku_posts = get_posts(array(
		'post_type'      => 'sd_glass_panel',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => 'supplier_sku',
				'value'   => $clean,
				'compare' => '=',
			),
		),
	));

	if (!empty($sku_posts)) {
		return (int) $sku_posts[0];
	}

	// 4. Fallback search by LIKE across all glass panels
	$all_glass = get_posts(array(
		'post_type'      => 'sd_glass_panel',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
	));

	foreach ($all_glass as $g) {
		if (stripos($g->post_title, $clean) !== false || stripos($clean, $g->post_title) !== false) {
			return (int) $g->ID;
		}
	}

	return 0;
}

/**
 * Returns the effective stock status for a glass panel.
 *
 * @param int $post_id
 * @return string 'instock' | 'lowstock' | 'outofstock' | 'on_order' | 'discontinued'
 */
function cutlist_get_glass_panel_stock_status($post_id)
{
	$post_id = (int) $post_id;
	if (!$post_id) {
		return 'instock';
	}

	$saved_status = get_post_meta($post_id, 'internal_stock_status', true);
	if (!empty($saved_status) && in_array($saved_status, array('on_order', 'discontinued'), true)) {
		return $saved_status;
	}

	$manage_stock = get_post_meta($post_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		return !empty($saved_status) ? $saved_status : 'instock';
	}

	$stock_meta = get_post_meta($post_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$reorder = (int) get_post_meta($post_id, 'stock_reorder_level', true);
	if ($reorder <= 0) {
		$reorder = 5;
	}

	if ($stock <= 0) {
		return 'outofstock';
	}
	if ($stock <= $reorder) {
		return 'lowstock';
	}

	return 'instock';
}

/**
 * Returns detailed stock availability breakdown for a glass panel.
 *
 * @param int $post_id
 * @return array ['stock' => int, 'is_available' => bool, 'is_managed' => bool, 'status' => string]
 */
function cutlist_get_glass_panel_available_stock($post_id)
{
	$post_id = (int) $post_id;
	if (!$post_id) {
		return array(
			'stock'        => 999999,
			'is_available' => true,
			'is_managed'   => false,
			'status'       => 'instock',
		);
	}

	$manage_stock = get_post_meta($post_id, 'manage_stock', true);
	if ($manage_stock === 'no') {
		$saved_status = get_post_meta($post_id, 'internal_stock_status', true);
		$status = !empty($saved_status) ? $saved_status : 'instock';
		$is_available = ($status !== 'outofstock' && $status !== 'discontinued');
		return array(
			'stock'        => $is_available ? 999999 : 0,
			'is_available' => $is_available,
			'is_managed'   => false,
			'status'       => $status,
		);
	}

	$stock_meta = get_post_meta($post_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$status = cutlist_get_glass_panel_stock_status($post_id);
	$is_available = ($stock > 0 && $status !== 'outofstock' && $status !== 'discontinued');

	return array(
		'stock'        => max(0, $stock),
		'is_available' => $is_available,
		'is_managed'   => true,
		'status'       => $status,
	);
}

/**
 * Updates a glass panel's physical sheet/panel stock, auto-adjusts stock status, and appends a log entry.
 *
 * @param int $glass_id Glass panel post ID
 * @param int $qty_change Positive for restock, negative for deduction (e.g. -2)
 * @param string $action_type e.g. 'order_sliding_doors', 'order_cancelled', 'manual_adjustment', 'manual_restock'
 * @param string $reference e.g. 'Order #1042', 'Manual restock'
 * @param string $note Additional notes
 * @return int New stock quantity
 */
function cutlist_update_glass_panel_stock($glass_id, $qty_change, $action_type, $reference = '', $note = '')
{
	$glass_id = (int) $glass_id;
	if (!$glass_id || get_post_type($glass_id) !== 'sd_glass_panel') {
		return 0;
	}

	$current_stock = (int) get_post_meta($glass_id, 'stock_sheets_qty', true);
	$new_stock = max(0, $current_stock + (int) $qty_change);

	// Update stock meta
	update_post_meta($glass_id, 'stock_sheets_qty', $new_stock);
	if (function_exists('update_field')) {
		update_field('stock_sheets_qty', $new_stock, $glass_id);
	}

	// Auto-update internal stock status based on reorder threshold
	$reorder_level = (int) get_post_meta($glass_id, 'stock_reorder_level', true);
	if ($reorder_level <= 0) {
		$reorder_level = 5;
	}

	$status = 'instock';
	if ($new_stock <= 0) {
		$status = 'outofstock';
	} elseif ($new_stock <= $reorder_level) {
		$status = 'lowstock';
	}

	update_post_meta($glass_id, 'internal_stock_status', $status);
	if (function_exists('update_field')) {
		update_field('internal_stock_status', $status, $glass_id);
	}

	// Append to stock activity history log
	$log = get_post_meta($glass_id, '_cutlist_stock_log', true);
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

	update_post_meta($glass_id, '_cutlist_stock_log', $log);

	return $new_stock;
}

/**
 * Retrieves the stock movement history log for a glass panel.
 *
 * @param int $glass_id
 * @return array
 */
function cutlist_get_glass_panel_stock_log($glass_id)
{
	$log = get_post_meta($glass_id, '_cutlist_stock_log', true);
	return is_array($log) ? $log : array();
}

/**
 * Validates stock for all glass panels in the cart.
 *
 * @return array Array of error message strings.
 */
function cutlist_validate_cart_glass_stock()
{
	if (!function_exists('WC') || !WC()->cart) {
		return array();
	}

	$errors = array();
	$glass_usage = array(); // glass_id => count of panels requested

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		if (empty($cart_item['cutlist_door_details'])) {
			continue;
		}

		$details_text = (string) $cart_item['cutlist_door_details'];
		$lines = explode("\n", str_replace("\r", "", $details_text));

		foreach ($lines as $line) {
			if (stripos($line, '[Glass') !== false) {
				// e.g. "Door 1 - Panel A: Bronze Mirror [Glass - mirrors]" or "Door 2: Silver Mirror [Glass] (Single panel)"
				$colon_parts = explode(':', $line);
				if (count($colon_parts) >= 2) {
					$val = trim(end($colon_parts));
					$val = preg_replace('/\s*\(Single\s*panel.*?\)/i', '', $val);
					$g_id = cutlist_get_glass_panel_by_name($val);
					if ($g_id > 0) {
						if (!isset($glass_usage[$g_id])) {
							$glass_usage[$g_id] = 0;
						}
						$glass_usage[$g_id]++;
					}
				}
			}
		}
	}

	foreach ($glass_usage as $g_id => $needed) {
		$avail = cutlist_get_glass_panel_available_stock($g_id);
		if ($avail['is_managed'] && $needed > $avail['stock']) {
			$g_name = get_the_title($g_id);
			if ($avail['stock'] <= 0) {
				$errors[] = sprintf(
					__('Sorry, "%s" is currently out of stock (0 available in workshop). Please select another finish.', 'cutlist-catalogue'),
					$g_name
				);
			} else {
				$errors[] = sprintf(
					__('Your sliding doors require %d panel(s) of "%s", but only %d are currently in stock.', 'cutlist-catalogue'),
					$needed,
					$g_name,
					$avail['stock']
				);
			}
		} elseif (!$avail['is_available']) {
			$g_name = get_the_title($g_id);
			$errors[] = sprintf(
				__('Sorry, "%s" is currently unavailable. Please choose an in-stock finish.', 'cutlist-catalogue'),
				$g_name
			);
		}
	}

	return $errors;
}

// =============================================================================
// ADMIN INVENTORY META BOX FOR sd_glass_panel CPT
// =============================================================================

add_action('add_meta_boxes', function () {
	add_meta_box(
		'cutlist_glass_panel_inventory_box',
		__('Inventory & Stock Control', 'cutlist-catalogue'),
		'cutlist_render_glass_panel_inventory_meta_box',
		'sd_glass_panel',
		'normal',
		'high'
	);
});

/**
 * Renders the clean WooCommerce-style Inventory Meta Box on Glass Panel Edit Screen.
 */
function cutlist_render_glass_panel_inventory_meta_box($post)
{
	$post_id = $post->ID;
	$stock_meta = get_post_meta($post_id, 'stock_sheets_qty', true);
	$stock = ($stock_meta !== '') ? (int) $stock_meta : 0;
	$reorder = (int) get_post_meta($post_id, 'stock_reorder_level', true);
	if ($reorder <= 0) {
		$reorder = 5;
	}
	$sku = get_post_meta($post_id, 'supplier_sku', true);
	$manage_stock = get_post_meta($post_id, 'manage_stock', true);
	if ($manage_stock === '') {
		$manage_stock = 'yes'; // Default to managed
	}

	$saved_status = get_post_meta($post_id, 'internal_stock_status', true);
	if (!empty($saved_status) && in_array($saved_status, array('on_order', 'discontinued'), true)) {
		$status = $saved_status;
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

	$log = cutlist_get_glass_panel_stock_log($post_id);

	wp_nonce_field('cutlist_save_glass_panel_inventory', 'cutlist_glass_inventory_nonce');
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
			<label for="cutlist_glass_sku"><?php esc_html_e('SKU / Code', 'cutlist-catalogue'); ?></label>
			<input name="cutlist_glass_sku" id="cutlist_glass_sku" type="text" value="<?php echo esc_attr($sku); ?>" placeholder="e.g. GL-MIRR-BRONZE">
			<span class="description"><?php esc_html_e('Supplier or warehouse identifier code.', 'cutlist-catalogue'); ?></span>
		</div>

		<div class="form-field">
			<label for="cutlist_glass_manage_stock"><?php esc_html_e('Manage stock?', 'cutlist-catalogue'); ?></label>
			<label style="font-weight: normal; width: auto; display: inline-flex; align-items: center; cursor: pointer;">
				<input type="checkbox" class="checkbox" name="cutlist_glass_manage_stock" id="cutlist_glass_manage_stock" value="yes" <?php checked($manage_stock, 'yes'); ?> onchange="document.getElementById('cutlist_glass_stock_fields_wrap').style.display = this.checked ? 'block' : 'none';">
				<?php esc_html_e('Track stock quantity for glass/mirror sheets in workshop', 'cutlist-catalogue'); ?>
			</label>
		</div>

		<div id="cutlist_glass_stock_fields_wrap" style="<?php echo ($manage_stock === 'no') ? 'display:none;' : ''; ?>">
			<div class="form-field">
				<label for="cutlist_glass_stock_qty"><?php esc_html_e('Stock quantity', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_glass_stock_qty" id="cutlist_glass_stock_qty" type="number" step="1" min="0" value="<?php echo esc_attr($stock); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncGlassStockStatus()">
				<span class="description"><?php esc_html_e('Full glass sheets/panels in workshop (auto-deducted on checkout).', 'cutlist-catalogue'); ?></span>
			</div>

			<div class="form-field">
				<label for="cutlist_glass_reorder_level"><?php esc_html_e('Low stock threshold', 'cutlist-catalogue'); ?></label>
				<input name="cutlist_glass_reorder_level" id="cutlist_glass_reorder_level" type="number" step="1" min="0" value="<?php echo esc_attr($reorder); ?>" style="max-width: 120px;" oninput="cutlistAutoSyncGlassStockStatus()">
				<span class="description"><?php esc_html_e('When stock reaches this amount, status switches to Low Stock.', 'cutlist-catalogue'); ?></span>
			</div>
		</div>

		<div class="form-field">
			<label for="cutlist_glass_stock_status"><?php esc_html_e('Stock status', 'cutlist-catalogue'); ?></label>
			<select name="cutlist_glass_stock_status" id="cutlist_glass_stock_status">
				<option value="instock" <?php selected($status, 'instock'); ?>><?php esc_html_e('In stock', 'cutlist-catalogue'); ?></option>
				<option value="lowstock" <?php selected($status, 'lowstock'); ?>><?php esc_html_e('Low stock', 'cutlist-catalogue'); ?></option>
				<option value="outofstock" <?php selected($status, 'outofstock'); ?>><?php esc_html_e('Out of stock', 'cutlist-catalogue'); ?></option>
				<option value="on_order" <?php selected($status, 'on_order'); ?>><?php esc_html_e('On backorder / Reorder placed', 'cutlist-catalogue'); ?></option>
				<option value="discontinued" <?php selected($status, 'discontinued'); ?>><?php esc_html_e('Discontinued', 'cutlist-catalogue'); ?></option>
			</select>
		</div>

		<script>
			function cutlistAutoSyncGlassStockStatus() {
				var qtyInput = document.getElementById('cutlist_glass_stock_qty');
				var threshInput = document.getElementById('cutlist_glass_reorder_level');
				var statusSelect = document.getElementById('cutlist_glass_stock_status');
				if (!qtyInput || !threshInput || !statusSelect) return;
				
				var qty = parseInt(qtyInput.value, 10);
				var thresh = parseInt(threshInput.value, 10) || 5;
				if (isNaN(qty)) qty = 0;

				if (statusSelect.value === 'on_order' || statusSelect.value === 'discontinued') return;

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
 * Handles saving Inventory fields when the sd_glass_panel post is updated in WP-Admin.
 */
add_action('save_post_sd_glass_panel', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!isset($_POST['cutlist_glass_inventory_nonce']) || !wp_verify_nonce($_POST['cutlist_glass_inventory_nonce'], 'cutlist_save_glass_panel_inventory')) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$manage_stock = isset($_POST['cutlist_glass_manage_stock']) ? 'yes' : 'no';
	update_post_meta($post_id, 'manage_stock', $manage_stock);

	if (isset($_POST['cutlist_glass_stock_qty'])) {
		$old_stock = (int) get_post_meta($post_id, 'stock_sheets_qty', true);
		$new_stock = max(0, (int) $_POST['cutlist_glass_stock_qty']);
		$reorder = max(1, (int) ($_POST['cutlist_glass_reorder_level'] ?? 5));
		$status = sanitize_text_field($_POST['cutlist_glass_stock_status'] ?? 'instock');
		$sku = sanitize_text_field($_POST['cutlist_glass_sku'] ?? '');

		// Auto-sync status with stock quantity if stock management is enabled
		if ($manage_stock === 'yes' && $status !== 'on_order' && $status !== 'discontinued') {
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
		update_post_meta($post_id, 'internal_stock_status', $status);
		update_post_meta($post_id, 'supplier_sku', $sku);

		// Sync with ACF if active
		if (function_exists('update_field')) {
			update_field('stock_sheets_qty', $new_stock, $post_id);
			update_field('stock_reorder_level', $reorder, $post_id);
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
// WP-ADMIN GLASS PANEL LIST TABLE COLUMNS
// =============================================================================

add_filter('manage_sd_glass_panel_posts_columns', function ($columns) {
	$new_cols = array();
	foreach ($columns as $key => $title) {
		$new_cols[$key] = $title;
		if ($key === 'title') {
			$new_cols['sd_stock_sheets'] = __('Stock', 'cutlist-catalogue');
			$new_cols['sd_stock_status'] = __('Stock Status', 'cutlist-catalogue');
		}
	}
	return $new_cols;
}, 20);

add_action('manage_sd_glass_panel_posts_custom_column', function ($column, $post_id) {
	if ($column === 'sd_stock_sheets') {
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
	} elseif ($column === 'sd_stock_status') {
		$status = cutlist_get_glass_panel_stock_status($post_id);
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

// =============================================================================
// AUTOMATED ORDER DEDUCTION & RESTORATION FOR WOOCOMMERCE
// =============================================================================

/**
 * Automatically deducts glass stock when an order is placed/processing/completed.
 *
 * @param int $order_id
 */
function cutlist_process_order_glass_stock_deduction($order_id)
{
	if (!$order_id || !class_exists('WC_Order')) {
		return;
	}

	// Prevent duplicate deductions
	if (get_post_meta($order_id, '_cutlist_glass_stock_deducted', true)) {
		return;
	}

	$order = wc_get_order($order_id);
	if (!$order) {
		return;
	}

	$deductions = array(); // glass_id => array('count' => int, 'name' => string)

	foreach ($order->get_items() as $item_id => $item) {
		$details_text = $item->get_meta(__('Door Panel Layout & Infills', 'cutlist-catalogue'), true);
		if (empty($details_text)) {
			$details_text = $item->get_meta('_cutlist_door_details', true) ?: $item->get_meta('cutlist_door_details', true);
		}
		if (empty($details_text)) {
			continue;
		}

		$lines = explode("\n", str_replace("\r", "", (string) $details_text));
		foreach ($lines as $line) {
			if (stripos($line, '[Glass') !== false) {
				$colon_parts = explode(':', $line);
				if (count($colon_parts) >= 2) {
					$val = trim(end($colon_parts));
					$val = preg_replace('/\s*\(Single\s*panel.*?\)/i', '', $val);
					$g_id = cutlist_get_glass_panel_by_name($val);
					if ($g_id > 0) {
						if (!isset($deductions[$g_id])) {
							$deductions[$g_id] = array('count' => 0, 'name' => get_the_title($g_id));
						}
						$deductions[$g_id]['count']++;
					}
				}
			}
		}
	}

	if (empty($deductions)) {
		return;
	}

	$deducted_records = array();

	foreach ($deductions as $g_id => $d_info) {
		$qty_to_deduct = $d_info['count'];
		cutlist_update_glass_panel_stock(
			$g_id,
			-$qty_to_deduct,
			'order_sliding_doors',
			sprintf(__('Order #%s', 'cutlist-catalogue'), $order->get_order_number()),
			sprintf(__('Deducted %d panel(s) for order #%s (%s)', 'cutlist-catalogue'), $qty_to_deduct, $order->get_order_number(), $d_info['name'])
		);

		$deducted_records[$g_id] = $qty_to_deduct;
	}

	update_post_meta($order_id, '_cutlist_glass_stock_deducted', true);
	update_post_meta($order_id, '_cutlist_glass_stock_records', $deducted_records);

	$order->add_order_note(
		sprintf(__('Workshop Glass Inventory: Automatically deducted stock for %d glass panel type(s).', 'cutlist-catalogue'), count($deducted_records))
	);
}
add_action('woocommerce_order_status_processing', 'cutlist_process_order_glass_stock_deduction', 20);
add_action('woocommerce_order_status_completed', 'cutlist_process_order_glass_stock_deduction', 20);
add_action('woocommerce_payment_complete', 'cutlist_process_order_glass_stock_deduction', 20);

/**
 * Automatically restores deducted glass stock when an order is cancelled or refunded.
 *
 * @param int $order_id
 */
function cutlist_restore_order_glass_stock($order_id)
{
	if (!$order_id) {
		return;
	}

	// Only restore if stock was previously deducted
	if (!get_post_meta($order_id, '_cutlist_glass_stock_deducted', true)) {
		return;
	}

	$records = get_post_meta($order_id, '_cutlist_glass_stock_records', true);
	if (!is_array($records) || empty($records)) {
		return;
	}

	$order = wc_get_order($order_id);
	$order_num = $order ? $order->get_order_number() : (string) $order_id;

	foreach ($records as $g_id => $qty) {
		$g_name = get_the_title($g_id);
		cutlist_update_glass_panel_stock(
			$g_id,
			$qty,
			'order_cancelled',
			sprintf(__('Order #%s Cancelled', 'cutlist-catalogue'), $order_num),
			sprintf(__('Restored %d panel(s) due to order cancellation/refund (%s)', 'cutlist-catalogue'), $qty, $g_name)
		);
	}

	delete_post_meta($order_id, '_cutlist_glass_stock_deducted');
	delete_post_meta($order_id, '_cutlist_glass_stock_records');

	if ($order) {
		$order->add_order_note(
			sprintf(__('Workshop Glass Inventory: Restored stock for %d glass panel item(s) due to order cancellation/refund.', 'cutlist-catalogue'), count($records))
		);
	}
}
add_action('woocommerce_order_status_cancelled', 'cutlist_restore_order_glass_stock', 20);
add_action('woocommerce_order_status_refunded', 'cutlist_restore_order_glass_stock', 20);

// =============================================================================
// CART & CHECKOUT PRE-SUBMISSION VALIDATION
// =============================================================================

function cutlist_validate_cart_items_glass_stock()
{
	$errors = cutlist_validate_cart_glass_stock();
	foreach ($errors as $err) {
		wc_add_notice($err, 'error');
	}
}
add_action('woocommerce_check_cart_items', 'cutlist_validate_cart_items_glass_stock', 26);
add_action('woocommerce_review_order_before_cart_contents', 'cutlist_validate_cart_items_glass_stock', 26);

function cutlist_checkout_process_glass_stock_validation()
{
	$errors = cutlist_validate_cart_glass_stock();
	foreach ($errors as $err) {
		wc_add_notice($err, 'error');
	}
}
add_action('woocommerce_checkout_process', 'cutlist_checkout_process_glass_stock_validation', 16);

add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
	$stock_errors = cutlist_validate_cart_glass_stock();
	foreach ($stock_errors as $err) {
		$errors->add('glass_stock_error', $err);
	}
}, 16, 2);
