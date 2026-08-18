<?php
/**
 * Plugin Name: CuttingList Catalogue
 * Description: Live catalogue data behind the CuttingList site — starting with the Board catalogue (decor, sizes, per-thickness pricing, machining options). Custom post types + ACF fields, all editable from wp-admin, exposed over REST for the front end.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Cutlist
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cutlist-catalogue
 */

if (!defined('ABSPATH')) {
	exit;
}

define('CUTLIST_CATALOGUE_PATH', plugin_dir_path(__FILE__));

// ACF field groups are kept as JSON in this plugin's acf-json/ folder so
// the field setup travels with the code (git) instead of only living in
// the database — same pattern the sliding-doors-poc plugin uses.
add_filter('acf/settings/save_json', function () {
	return CUTLIST_CATALOGUE_PATH . 'acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
	$paths[] = CUTLIST_CATALOGUE_PATH . 'acf-json';
	return $paths;
});

require_once CUTLIST_CATALOGUE_PATH . 'includes/cpt-registration.php';
require_once CUTLIST_CATALOGUE_PATH . 'includes/gallery-meta-box.php';
require_once CUTLIST_CATALOGUE_PATH . 'includes/rest-endpoints.php';
require_once CUTLIST_CATALOGUE_PATH . 'includes/cutting-list-shortcode.php';
require_once CUTLIST_CATALOGUE_PATH . 'includes/wc-cart-integration.php';
require_once CUTLIST_CATALOGUE_PATH . 'includes/admin-settings.php';

register_activation_hook(__FILE__, function () {
	cutlist_register_post_types();
	cutlist_register_taxonomies();
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});
