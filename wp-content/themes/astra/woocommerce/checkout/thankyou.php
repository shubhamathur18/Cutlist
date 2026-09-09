<?php
/**
 * Thank You / Order Received Template Override
 */

defined('ABSPATH') || exit;

$custom_template = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/thankyou.php' : '';
if ($custom_template && file_exists($custom_template)) {
	include $custom_template;
	return;
}

// Fallback to direct view-order layout
include dirname(__DIR__) . '/myaccount/view-order.php';
