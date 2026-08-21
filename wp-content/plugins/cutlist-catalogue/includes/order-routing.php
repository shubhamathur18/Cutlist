<?php
/**
 * Order Routing, Database Table, and Order Editing Support for Cutlist Catalogue
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Creates custom database table for Cutlist persistent order storage if not exists
 */
function cutlist_create_orders_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'cutlist_orders';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		order_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		cutlist_data longtext NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY order_id (order_id)
	) {$charset_collate};";

	$wpdb->query($sql);
}

// Auto-ensure table exists on init or activation
add_action('init', function () {
	if (get_option('cutlist_db_version') !== '1.0.1') {
		cutlist_create_orders_table();
		update_option('cutlist_db_version', '1.0.1');
	}
});

/**
 * Registers query vars for order_id
 */
add_filter('query_vars', function ($vars) {
	$vars[] = 'order_id';
	return $vars;
});

/**
 * Registers rewrite rules for /{order_id}/cutlist/ and /cutlist/{order_id}/
 */
add_action('init', function () {
	add_rewrite_rule(
		'^([0-9]+)/cutlist/?$',
		'index.php?pagename=cutlist&order_id=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^cutlist/([0-9]+)/?$',
		'index.php?pagename=cutlist&order_id=$matches[1]',
		'top'
	);
});

/**
 * Retrieves current order ID from query var, URI path, or $_GET
 */
function cutlist_get_current_order_id() {
	$order_id = get_query_var('order_id');
	if (!$order_id) {
		if (isset($_GET['order_id'])) {
			$order_id = (int) $_GET['order_id'];
		} elseif (isset($_GET['order'])) {
			$order_id = (int) $_GET['order'];
		} elseif (!empty($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
			if (preg_match('#/([0-9]+)/cutlist#i', $uri, $matches)) {
				$order_id = (int) $matches[1];
			} elseif (preg_match('#/cutlist/([0-9]+)#i', $uri, $matches)) {
				$order_id = (int) $matches[1];
			}
		}
	}
	return (int) $order_id;
}

/**
 * Generates URL to edit a specific order on /cutlist/
 */
function cutlist_order_url($order_id) {
	return home_url('/' . (int) $order_id . '/cutlist/');
}

/**
 * Checks if current user can view/edit order
 */
function cutlist_user_can_access_order($order_id) {
	if (!$order_id) {
		return false;
	}
	if (current_user_can('edit_shop_orders') || current_user_can('manage_woocommerce') || current_user_can('edit_posts')) {
		return true;
	}
	if (!function_exists('wc_get_order')) {
		return true; // Allow access if WC not installed
	}
	$order = wc_get_order($order_id);
	if (!$order) {
		return true;
	}
	$user_id = get_current_user_id();
	if ($user_id && (int) $order->get_customer_id() === (int) $user_id) {
		return true;
	}
	if (isset($_GET['key']) && $_GET['key'] === $order->get_order_key()) {
		return true;
	}
	return true; // Allow view/edit by order ID link
}

/**
 * Helper to fetch cutlist data from DB table or WC order meta
 */
function cutlist_get_saved_order_data($order_id) {
	global $wpdb;
	$order_id = (int) $order_id;
	if (!$order_id) {
		return null;
	}

	$table_name = $wpdb->prefix . 'cutlist_orders';
	$row = $wpdb->get_row($wpdb->prepare("SELECT cutlist_data, updated_at FROM {$table_name} WHERE order_id = %d", $order_id));

	if ($row && !empty($row->cutlist_data)) {
		$decoded = json_decode($row->cutlist_data, true);
		if (is_array($decoded)) {
			$decoded['updatedAt'] = $row->updated_at;
			return $decoded;
		}
	}

	// Fallback to WC Order Meta _cutlist_data
	if (function_exists('wc_get_order')) {
		$order = wc_get_order($order_id);
		if ($order) {
			$cutlist_meta = $order->get_meta('_cutlist_data');
			if ($cutlist_meta) {
				$decoded = json_decode($cutlist_meta, true);
				if (is_array($decoded)) {
					return $decoded;
				}
			}
		}
	}

	return null;
}

/**
 * Helper to save cutlist data to DB table and WC order meta
 */
function cutlist_save_order_data($order_id, $data) {
	global $wpdb;
	$order_id = (int) $order_id;
	if (!$order_id || !is_array($data)) {
		return false;
	}

	$table_name = $wpdb->prefix . 'cutlist_orders';
	$user_id = get_current_user_id();
	$json_data = wp_json_encode($data);

	$wpdb->query($wpdb->prepare(
		"INSERT INTO {$table_name} (order_id, user_id, cutlist_data, updated_at)
		VALUES (%d, %d, %s, NOW())
		ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), cutlist_data = VALUES(cutlist_data), updated_at = NOW()",
		$order_id, $user_id, $json_data
	));

	// Synchronize with WC Order if available
	if (function_exists('wc_get_order')) {
		$order = wc_get_order($order_id);
		if ($order) {
			$order->update_meta_data('_cutlist_data', $json_data);
			$order->save();
		}
	}

	return true;
}
