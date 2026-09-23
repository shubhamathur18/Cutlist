<?php
/**
 * Shortcode and asset handling for CNC Machining page.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enqueue dedicated frontend assets for the CNC Machining page.
 */
function cutlist_enqueue_cnc_assets()
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

	$cnc_css_url = plugins_url('assets/css/cnc-main.css', dirname(__FILE__));
	$cnc_css_path = CUTLIST_CATALOGUE_PATH . 'assets/css/cnc-main.css';
	$cnc_css_ver = file_exists($cnc_css_path) ? filemtime($cnc_css_path) : '1.0.0';

	// Enqueue dedicated CNC styles
	wp_enqueue_style(
		'cnc-main',
		$cnc_css_url,
		array('cutlist-header-tabs-css'),
		$cnc_css_ver
	);

	$cnc_js_url = plugins_url('assets/js/cnc-main.js', dirname(__FILE__));
	$cnc_js_path = CUTLIST_CATALOGUE_PATH . 'assets/js/cnc-main.js';
	$cnc_js_ver = file_exists($cnc_js_path) ? filemtime($cnc_js_path) : '1.0.0';

	// Enqueue dedicated CNC script
	wp_enqueue_script(
		'cnc-main',
		$cnc_js_url,
		array(),
		$cnc_js_ver,
		true
	);

	wp_localize_script('cnc-main', 'cncI18n', array(
		'expand' => __('Expand', 'cutlist-catalogue'),
		'collapse' => __('Collapse', 'cutlist-catalogue'),
		'toastTitle' => __('Previous CNC job restored', 'cutlist-catalogue'),
		'toastDesc' => __('Your saved drawings and job instructions have been reloaded.', 'cutlist-catalogue'),
		'dismiss' => __('Dismiss', 'cutlist-catalogue'),
		'fileLimitError' => __('exceeds the 5 MB limit.', 'cutlist-catalogue'),
		'totalLimitError' => __('Total upload size exceeds the 30 MB limit.', 'cutlist-catalogue'),
		'emptyError' => __('Please upload at least one drawing file or provide details for your CNC job.', 'cutlist-catalogue'),
		'submitSuccess' => __('Thank you! Your CNC machining job details have been submitted.', 'cutlist-catalogue'),
	));
}

/**
 * Main shortcode for CNC Machining: [cnc_machining_table]
 */
add_shortcode('cnc_machining_table', function () {
	cutlist_enqueue_cnc_assets();

	ob_start();
	if (file_exists(CUTLIST_CATALOGUE_PATH . 'templates/cnc-table.php')) {
		include CUTLIST_CATALOGUE_PATH . 'templates/cnc-table.php';
	}
	return ob_get_clean();
});

// Shortcode aliases
add_shortcode('cnc_machining', function () {
	return do_shortcode('[cnc_machining_table]');
});

add_shortcode('cnc_table', function () {
	return do_shortcode('[cnc_machining_table]');
});
