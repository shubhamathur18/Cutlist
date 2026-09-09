<?php
/**
 * Standalone Print / PDF Order Confirmation Template
 *
 * Pixel-perfect match for the Cutlist Order Confirmation PDF specification:
 * - Brand Header with Logo, Tagline and Wood Samples graphic
 * - Order Confirmation heading and soft green status card (Order #, Date, Status)
 * - 4-Column Customer & Order Details Grid (Customer, Billing Address, Payment Method, Order Date)
 * - Order Details Table (#, Product thumbnail & name, Specs Details, Qty, Unit Price, Total)
 * - Totals Box with Highlighted Green Total
 * - Bottom 2-Column Info Grid (Notes & Need help?)
 * - Professional Footer (Logo, Thank you message with accent bar, domain)
 */

defined('ABSPATH') || exit;

// Ensure $order is resolved
if (!isset($order) || empty($order) || is_numeric($order)) {
	$res_order_id = isset($order_id) ? (int) $order_id : (isset($order) && is_numeric($order) ? (int) $order : 0);
	if (!$res_order_id && isset($_GET['print_order']) && is_numeric($_GET['print_order'])) {
		$res_order_id = absint($_GET['print_order']);
	}
	if (!$res_order_id && isset($_GET['cutlist_print']) && is_numeric($_GET['cutlist_print'])) {
		$res_order_id = absint($_GET['cutlist_print']);
	}
	if (!$res_order_id && isset($_GET['order_id'])) {
		$res_order_id = absint($_GET['order_id']);
	}
	if (!$res_order_id && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('view-order')) {
		global $wp;
		$res_order_id = isset($wp->query_vars['view-order']) ? absint($wp->query_vars['view-order']) : 0;
	}
	if (!$res_order_id && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
		global $wp;
		$res_order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
	}
	if ($res_order_id && function_exists('wc_get_order')) {
		$order = wc_get_order($res_order_id);
	}
}

if (!$order || !is_a($order, 'WC_Order')) {
	wp_die(__('Order not found.', 'cutlist-catalogue'), __('Error', 'cutlist-catalogue'), array('response' => 404));
}

$order_id = $order->get_id();
$order_number = $order->get_order_number();
$order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format', 'F j, Y')) : '';
$order_total = $order->get_formatted_order_total();
$billing_first_name = $order->get_billing_first_name();
$billing_last_name = $order->get_billing_last_name();
$billing_full_name = trim($billing_first_name . ' ' . $billing_last_name) ?: __('Valued Customer', 'cutlist-catalogue');
$billing_email = $order->get_billing_email();
$billing_phone = $order->get_billing_phone() ?: '+91 71234 56789';
$billing_address_1 = $order->get_billing_address_1();
$billing_city = $order->get_billing_city();
$billing_postcode = $order->get_billing_postcode();
$billing_state = $order->get_billing_state();
$billing_country_code = $order->get_billing_country();
$billing_country = function_exists('WC') && WC()->countries ? (WC()->countries->countries[$billing_country_code] ?? $billing_country_code) : $billing_country_code;

$payment_method_title = $order->get_payment_method_title() ?: __('Cash on delivery', 'cutlist-catalogue');
$status_slug = $order->get_status();
$status_name = wc_get_order_status_name($status_slug);

$order_items = $order->get_items();
$item_count = count($order_items);
$subtotal_formatted = wc_price($order->get_subtotal());
$wood_banner_url = defined('CUTLIST_CATALOGUE_PATH') ? plugins_url('assets/wood-samples-banner.jpg', CUTLIST_CATALOGUE_PATH . 'cutlist-catalogue.php') : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php printf(esc_html__('Order #%s - Cutlist Confirmation', 'cutlist-catalogue'), esc_html($order_number)); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	
	<style>
		/* Base & Reset */
		*, *::before, *::after {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
			color-adjust: exact !important;
		}

		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
			background-color: #f3f4f6;
			color: #111827;
			line-height: 1.4;
			font-size: 13px;
			padding: 30px 15px;
			margin: 0;
		}

		/* Screen Action Toolbar */
		.cl-print-screen-toolbar {
			max-width: 820px;
			margin: 0 auto 20px auto;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #ffffff;
			padding: 12px 20px;
			border-radius: 10px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
			border: 1px solid #e5e7eb;
		}

		.cl-toolbar-left {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.cl-toolbar-btn-print {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			background: #16a34a;
			color: #ffffff;
			border: none;
			border-radius: 6px;
			padding: 9px 18px;
			font-size: 13.5px;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.15s ease;
		}

		.cl-toolbar-btn-print:hover {
			background: #15803d;
		}

		.cl-toolbar-tip {
			font-size: 12px;
			color: #6b7280;
		}

		.cl-toolbar-btn-close {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f3f4f6;
			color: #374151;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			padding: 8px 14px;
			font-size: 12.5px;
			font-weight: 600;
			cursor: pointer;
			text-decoration: none;
		}

		.cl-toolbar-btn-close:hover {
			background: #e5e7eb;
			color: #111827;
		}

		/* Main Printable Sheet */
		.cl-print-sheet {
			max-width: 820px;
			margin: 0 auto;
			background: #ffffff;
			padding: 36px 25px 30px 25px;
			border-radius: 8px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
			position: relative;
			overflow: hidden;
		}

		/* 1. Header */
		.cl-print-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 28px;
			position: relative;
		}

		.cl-print-logo-box {
			display: flex;
			flex-direction: column;
		}

		.cl-print-logo-main {
			display: flex;
			align-items: baseline;
			gap: 3px;
			position: relative;
		}

		.cl-print-logo-text {
			font-size: 32px;
			font-weight: 800;
			color: #111827;
			letter-spacing: -0.6px;
			line-height: 1;
		}

		.cl-print-logo-dash {
			display: inline-block;
			width: 10px;
			height: 3.5px;
			background: #16a34a;
			border-radius: 1px;
			margin-left: 2px;
			transform: translateY(-8px);
		}

		.cl-print-logo-tagline {
			font-size: 8.5px;
			font-weight: 700;
			letter-spacing: 2px;
			color: #4b5563;
			margin-top: 5px;
			text-transform: uppercase;
		}

		.cl-print-header-center {
			font-size: 13.5px;
			font-weight: 600;
			color: #374151;
			line-height: 1.35;
			text-align: left;
		}

		.cl-print-header-right {
			display: flex;
			align-items: center;
			justify-content: flex-end;
		}

		.cl-print-header-wood-img {
			width: 175px;
			height: 75px;
			object-fit: cover;
			border-radius: 6px;
			display: block;
		}

		/* 2. Order Confirmation Hero & Status Card */
		.cl-print-hero-row {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 20px;
			margin-bottom: 24px;
		}

		.cl-print-hero-left {
			flex: 1;
		}

		.cl-print-title {
			font-size: 26px;
			font-weight: 800;
			color: #111827;
			margin-bottom: 6px;
			letter-spacing: -0.4px;
		}

		.cl-print-subtitle {
			font-size: 12.5px;
			color: #4b5563;
			line-height: 1.45;
			max-width: 440px;
		}

		.cl-print-hero-right {
			flex-shrink: 0;
		}

		.cl-print-status-card {
			background: #f0fdf4 !important;
			border: 1px solid #dcfce7;
			border-radius: 10px;
			padding: 12px 18px;
			min-width: 220px;
		}

		.cl-print-order-num {
			font-size: 16px;
			font-weight: 800;
			color: #15803d;
			margin-bottom: 6px;
		}

		.cl-print-status-line {
			display: flex;
			justify-content: space-between;
			gap: 8px;
			font-size: 11.5px;
			color: #374151;
			margin-top: 3px;
		}

		.cl-print-status-line span {
			color: #4b5563;
		}

		.cl-print-status-line strong {
			color: #111827;
			font-weight: 600;
		}

		.cl-print-status-val {
			color: #16a34a !important;
			font-weight: 700 !important;
		}

		/* 3. 4-Column Customer & Order Details Grid */
		.cl-print-info-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 14px;
			margin-bottom: 26px;
			padding: 14px 0;
			border-top: 1px solid #f3f4f6;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-print-info-col {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.cl-print-info-header {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.cl-print-icon-circle {
			width: 26px;
			height: 26px;
			background: #f0fdf4 !important;
			border: 1px solid #dcfce7;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}

		.cl-print-info-label {
			font-size: 12px;
			font-weight: 600;
			color: #111827;
		}

		.cl-print-info-body {
			font-size: 11.5px;
			color: #4b5563;
			line-height: 1.45;
		}

		.cl-print-info-body strong {
			color: #111827;
			font-weight: 600;
			display: inline-block;
			margin-bottom: 2px;
		}

		.cl-print-muted {
			color: #6b7280;
		}

		/* 4. Order Details Table */
		.cl-print-table-section {
			margin-bottom: 24px;
		}

		.cl-print-table-header-bar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 10px;
		}

		.cl-print-table-title {
			font-size: 15px;
			font-weight: 700;
			color: #111827;
		}

		.cl-print-table-count {
			font-size: 12px;
			color: #6b7280;
			font-weight: 500;
		}

		.cl-print-items-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 14px;
		}

		.cl-print-items-table th {
			background: #f9fafb !important;
			border-top: 1px solid #e5e7eb;
			border-bottom: 1px solid #e5e7eb;
			font-size: 11.5px;
			font-weight: 700;
			color: #374151;
			text-align: left;
			padding: 9px 8px;
		}

		.cl-print-items-table td {
			padding: 14px 8px;
			border-bottom: 1px solid #f3f4f6;
			vertical-align: top;
			font-size: 12px;
			color: #374151;
		}

		.cl-th-num, .cl-td-num {
			width: 24px;
			font-weight: 700;
			color: #111827;
		}

		.cl-th-prod {
			width: 250px;
		}

		.cl-th-details {
			width: 270px;
		}

		.cl-th-qty, .cl-td-qty {
			width: 40px;
			text-align: center;
		}

		.cl-th-unit, .cl-td-unit {
			width: 75px;
			text-align: right;
		}

		.cl-th-total, .cl-td-total {
			width: 80px;
			text-align: right;
			color: #111827;
			font-weight: 700;
		}

		.cl-print-prod-cell {
			display: flex;
			gap: 10px;
			align-items: flex-start;
		}

		.cl-print-thumb-box {
			flex-shrink: 0;
		}

		.cl-print-item-img {
			width: 50px;
			height: 50px;
			object-fit: cover;
			border-radius: 6px;
			border: 1px solid #e5e7eb;
			display: block;
		}

		.cl-print-item-placeholder {
			width: 50px;
			height: 50px;
			background: #f3f4f6;
			border-radius: 6px;
			border: 1px solid #e5e7eb;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.cl-print-prod-title {
			font-size: 12px;
			font-weight: 700;
			color: #111827;
			line-height: 1.35;
		}

		.cl-print-specs-list {
			display: flex;
			flex-direction: column;
			gap: 3.5px;
			font-size: 11.5px;
		}

		.cl-print-spec-item {
			display: flex;
			gap: 6px;
			line-height: 1.35;
		}

		.cl-print-k {
			color: #6b7280;
			width: 95px;
			flex-shrink: 0;
			font-weight: 500;
		}

		.cl-print-v {
			color: #111827;
			font-weight: 500;
			flex: 1;
		}

		/* Totals Box on Right */
		.cl-print-totals-wrapper {
			display: flex;
			justify-content: flex-end;
			margin-top: 4px;
		}

		.cl-print-totals-table {
			width: 320px;
			border-collapse: collapse;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			overflow: hidden;
		}

		.cl-print-totals-table td {
			padding: 9px 14px;
			font-size: 12.5px;
			border-bottom: 1px solid #f3f4f6;
		}

		.cl-print-totals-table tr:last-child td {
			border-bottom: none;
		}

		.cl-print-tot-label {
			color: #374151;
			font-weight: 600;
			text-align: left;
		}

		.cl-print-tot-val {
			color: #111827;
			font-weight: 700;
			text-align: right;
		}

		.cl-print-tot-grand-row {
			background: #f0fdf4 !important;
			border-top: 1px solid #dcfce7;
			border-bottom: 1px solid #dcfce7;
		}

		.cl-print-tot-grand-row .cl-print-tot-label {
			color: #15803d;
			font-weight: 700;
			font-size: 13px;
		}

		.cl-print-tot-grand-val {
			color: #15803d !important;
			font-size: 15px !important;
			font-weight: 800 !important;
		}

		/* 5. Bottom 2-Column Info Grid */
		.cl-print-bottom-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
			margin-top: 22px;
		}

		.cl-print-bottom-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 10px;
			padding: 16px 18px;
		}

		.cl-print-card-header {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 10px;
		}

		.cl-print-card-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			color: #111827;
		}

		.cl-print-card-title {
			font-size: 13.5px;
			font-weight: 700;
			color: #111827;
			margin: 0;
		}

		.cl-print-notes-list {
			margin: 0;
			padding-left: 18px;
			font-size: 11.5px;
			color: #4b5563;
			line-height: 1.55;
		}

		.cl-print-notes-list li {
			margin-bottom: 3px;
		}

		.cl-print-card-desc {
			font-size: 11.5px;
			color: #4b5563;
			line-height: 1.45;
			margin-bottom: 12px;
		}

		.cl-print-contact-lines {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.cl-print-contact-item {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 12px;
			color: #111827;
		}

		.cl-print-contact-item svg {
			color: #111827;
			flex-shrink: 0;
		}

		/* 6. Footer Brand Bar */
		.cl-print-footer {
			margin-top: 28px;
			padding-top: 18px;
			border-top: 1px solid #e5e7eb;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.cl-print-logo-text--sm {
			font-size: 24px;
		}

		.cl-print-logo-tagline--sm {
			font-size: 7.5px;
			letter-spacing: 1.6px;
		}

		.cl-print-footer-center {
			text-align: center;
		}

		.cl-print-thanks {
			font-size: 13.5px;
			font-weight: 700;
			color: #111827;
			margin: 0;
		}

		.cl-print-accent-line {
			width: 44px;
			height: 2.5px;
			background: #16a34a;
			border-radius: 2px;
			margin: 4px auto 5px auto;
		}

		.cl-print-footer-sub {
			font-size: 11px;
			color: #6b7280;
			display: block;
		}

		.cl-print-domain {
			font-size: 13px;
			font-weight: 500;
			color: #374151;
		}

		/* PRINT MEDIA RULES */
		@page {
			size: A4 portrait;
			margin: 8mm 12mm 8mm 12mm;
		}

		@media print {
			body {
				background-color: #ffffff !important;
				padding: 0 !important;
				margin: 0 !important;
			}

			.cl-print-screen-toolbar {
				display: none !important;
			}

			.cl-print-sheet {
				max-width: 100% !important;
				box-shadow: none !important;
				padding: 0 !important;
				border-radius: 0 !important;
			}

			.cl-print-items-table tr {
				page-break-inside: avoid;
			}

			.cl-print-bottom-grid,
			.cl-print-footer {
				page-break-inside: avoid;
			}
		}
	</style>
</head>
<body>

	<!-- Action Toolbar (Visible on screen only) -->
	<div class="cl-print-screen-toolbar">
		<div class="cl-toolbar-left">
			<button type="button" onclick="window.print();" class="cl-toolbar-btn-print">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e('Print Order', 'cutlist-catalogue'); ?></span>
			</button>
			<span class="cl-toolbar-tip"><?php esc_html_e('Tip: Select "Save as PDF" destination in the print dialog to export a PDF copy.', 'cutlist-catalogue'); ?></span>
		</div>
		<button type="button" onclick="window.close();" class="cl-toolbar-btn-close">
			<span>&times; <?php esc_html_e('Close', 'cutlist-catalogue'); ?></span>
		</button>
	</div>

	<!-- Printable Sheet Content -->
	<div class="cl-print-sheet">

		<!-- 1. BRAND HEADER -->
		<div class="cl-print-header">
			<div class="cl-print-logo-box">
				<div class="cl-print-logo-main">
					<span class="cl-print-logo-text">Cutlist</span>
					<span class="cl-print-logo-dash"></span>
				</div>
				<div class="cl-print-logo-tagline">PANELS &nbsp;MEASURED &nbsp;BETTER</div>
			</div>

			<div class="cl-print-header-center">
				<span>Custom boards.</span><br>
				<span>Smarter spaces.</span>
			</div>

			<div class="cl-print-header-right">
				<?php if ($wood_banner_url) : ?>
					<img src="<?php echo esc_url($wood_banner_url); ?>" alt="Wood boards" class="cl-print-header-wood-img" />
				<?php endif; ?>
			</div>
		</div>

		<!-- 2. ORDER CONFIRMATION & STATUS BADGE -->
		<div class="cl-print-hero-row">
			<div class="cl-print-hero-left">
				<h1 class="cl-print-title"><?php esc_html_e('Order Confirmation', 'cutlist-catalogue'); ?></h1>
				<p class="cl-print-subtitle">
					<?php esc_html_e("Thank you for your order. We've received your order and will start processing it shortly.", 'cutlist-catalogue'); ?>
				</p>
			</div>
			<div class="cl-print-hero-right">
				<div class="cl-print-status-card">
					<h3 class="cl-print-order-num"><?php printf(esc_html__('Order #%s', 'cutlist-catalogue'), esc_html($order_number)); ?></h3>
					<div class="cl-print-status-line">
						<span><?php esc_html_e('Order Date:', 'cutlist-catalogue'); ?></span>
						<strong><?php echo esc_html($order_date); ?></strong>
					</div>
					<div class="cl-print-status-line">
						<span><?php esc_html_e('Order Status:', 'cutlist-catalogue'); ?></span>
						<strong class="cl-print-status-val"><?php echo esc_html($status_name); ?></strong>
					</div>
				</div>
			</div>
		</div>

		<!-- 3. 4-COLUMN INFO TILES -->
		<div class="cl-print-info-grid">
			<!-- Customer -->
			<div class="cl-print-info-col">
				<div class="cl-print-info-header">
					<div class="cl-print-icon-circle">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
							<circle cx="12" cy="7" r="4"></circle>
						</svg>
					</div>
					<span class="cl-print-info-label"><?php esc_html_e('Customer', 'cutlist-catalogue'); ?></span>
				</div>
				<div class="cl-print-info-body">
					<strong><?php echo esc_html($billing_full_name); ?></strong><br>
					<span class="cl-print-muted"><?php echo esc_html($billing_email); ?></span><br>
					<span class="cl-print-muted"><?php echo esc_html($billing_phone); ?></span>
				</div>
			</div>

			<!-- Billing Address -->
			<div class="cl-print-info-col">
				<div class="cl-print-info-header">
					<div class="cl-print-icon-circle">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
							<circle cx="12" cy="10" r="3"></circle>
						</svg>
					</div>
					<span class="cl-print-info-label"><?php esc_html_e('Billing Address', 'cutlist-catalogue'); ?></span>
				</div>
				<div class="cl-print-info-body">
					<strong><?php echo esc_html($billing_full_name); ?></strong><br>
					<?php if ($billing_address_1) : ?><span><?php echo esc_html($billing_address_1); ?></span><br><?php endif; ?>
					<?php if ($billing_city || $billing_postcode) : ?><span><?php echo esc_html(trim($billing_city . ' ' . $billing_postcode)); ?></span><br><?php endif; ?>
					<?php if ($billing_state) : ?><span><?php echo esc_html($billing_state); ?>, </span><?php endif; ?>
					<?php if ($billing_country) : ?><span><?php echo esc_html($billing_country); ?></span><?php endif; ?>
				</div>
			</div>

			<!-- Payment Method -->
			<div class="cl-print-info-col">
				<div class="cl-print-info-header">
					<div class="cl-print-icon-circle">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
							<line x1="1" y1="10" x2="23" y2="10"></line>
						</svg>
					</div>
					<span class="cl-print-info-label"><?php esc_html_e('Payment Method', 'cutlist-catalogue'); ?></span>
				</div>
				<div class="cl-print-info-body">
					<strong><?php echo esc_html($payment_method_title); ?></strong>
				</div>
			</div>

			<!-- Order Date -->
			<div class="cl-print-info-col">
				<div class="cl-print-info-header">
					<div class="cl-print-icon-circle">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
					</div>
					<span class="cl-print-info-label"><?php esc_html_e('Order Date', 'cutlist-catalogue'); ?></span>
				</div>
				<div class="cl-print-info-body">
					<strong><?php echo esc_html($order_date); ?></strong>
				</div>
			</div>
		</div>

		<!-- 4. ORDER DETAILS TABLE -->
		<div class="cl-print-table-section">
			<div class="cl-print-table-header-bar">
				<span class="cl-print-table-title"><?php esc_html_e('Order Details', 'cutlist-catalogue'); ?></span>
				<span class="cl-print-table-count"><?php echo sprintf(_n('%d item', '%d items', $item_count, 'cutlist-catalogue'), $item_count); ?></span>
			</div>

			<table class="cl-print-items-table">
				<thead>
					<tr>
						<th class="cl-th-num">#</th>
						<th class="cl-th-prod"><?php esc_html_e('Product', 'cutlist-catalogue'); ?></th>
						<th class="cl-th-details"><?php esc_html_e('Details', 'cutlist-catalogue'); ?></th>
						<th class="cl-th-qty"><?php esc_html_e('Qty', 'cutlist-catalogue'); ?></th>
						<th class="cl-th-unit"><?php esc_html_e('Unit Price', 'cutlist-catalogue'); ?></th>
						<th class="cl-th-total"><?php esc_html_e('Total', 'cutlist-catalogue'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$row_index = 1;
					foreach ($order_items as $item_id => $item) :
						if (!$item instanceof WC_Order_Item_Product) continue;

						$product = $item->get_product();
						$item_name = $item->get_name();
						$qty = (int) $item->get_quantity();
						$line_total = (float) $item->get_total();
						$unit_price = $qty > 0 ? ($line_total / $qty) : $line_total;
						$unit_price_formatted = wc_price($unit_price);
						$line_total_formatted = $order->get_formatted_line_subtotal($item);

						// Image
						$img_id = (int) $item->get_meta('_cutlist_image_id', true);
						$img_url = $item->get_meta('_cutlist_image_url', true);
						$item_thumb_html = '';

						if ($img_id > 0) {
							$item_thumb_html = wp_get_attachment_image($img_id, 'woocommerce_thumbnail', false, array(
								'class' => 'cl-print-item-img',
							));
						} elseif (!empty($img_url)) {
							$item_thumb_html = sprintf('<img src="%s" class="cl-print-item-img" alt="%s" />', esc_url($img_url), esc_attr($item_name));
						} elseif ($product && $product->get_image_id()) {
							$item_thumb_html = $product->get_image(array(50, 50), array('class' => 'cl-print-item-img'));
						} else {
							$decor_meta = $item->get_meta(__('Decor', 'cutlist-catalogue'), true);
							$resolved = function_exists('cutlist_get_decor_image') ? cutlist_get_decor_image($decor_meta ?: $item_name) : array();
							if (!empty($resolved['url'])) {
								$item_thumb_html = sprintf('<img src="%s" class="cl-print-item-img" alt="%s" />', esc_url($resolved['url']), esc_attr($item_name));
							} else {
								$item_thumb_html = '<div class="cl-print-item-placeholder"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
							}
						}

						// Metadata
						$item_type = $item->get_meta(__('Item Type', 'cutlist-catalogue'), true);
						$decor = $item->get_meta(__('Decor', 'cutlist-catalogue'), true);
						$thickness = $item->get_meta(__('Thickness', 'cutlist-catalogue'), true);
						$dimensions = $item->get_meta(__('Dimensions', 'cutlist-catalogue'), true);
						$edging = $item->get_meta(__('Edging Tape', 'cutlist-catalogue'), true);
						$machining = $item->get_meta(__('Machining Operations', 'cutlist-catalogue'), true);
						$spray = $item->get_meta(__('Spray Finishing', 'cutlist-catalogue'), true);
						$grain = $item->get_meta(__('Grain Matching', 'cutlist-catalogue'), true);
						$grain_notes = $item->get_meta(__('Grain Matching Notes', 'cutlist-catalogue'), true);
						$extra_cuts = $item->get_meta(__('Additional Cuts', 'cutlist-catalogue'), true);
						$grain_files = $item->get_meta('_cutlist_grain_files', true);
					?>
						<tr class="cl-print-row">
							<td class="cl-td-num"><?php echo esc_html($row_index++); ?></td>
							
							<td class="cl-td-prod">
								<div class="cl-print-prod-cell">
									<div class="cl-print-thumb-box">
										<?php echo $item_thumb_html; ?>
									</div>
									<strong class="cl-print-prod-title"><?php echo esc_html($item_name); ?></strong>
								</div>
							</td>

							<td class="cl-td-details">
								<div class="cl-print-specs-list">
									<?php if ($item_type) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Item Type:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($item_type); ?></span></div>
									<?php endif; ?>
									<?php if ($decor) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Decor:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($decor); ?></span></div>
									<?php endif; ?>
									<?php if ($thickness) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Thickness:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($thickness); ?></span></div>
									<?php endif; ?>
									<?php if ($dimensions) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Dimensions:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($dimensions); ?></span></div>
									<?php endif; ?>
									<?php if ($edging) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Edging Tape:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($edging); ?></span></div>
									<?php endif; ?>
									<?php if ($machining) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Machining:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($machining); ?></span></div>
									<?php endif; ?>
									<?php if ($spray) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Spray Finishing:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($spray); ?></span></div>
									<?php endif; ?>
									<?php if ($grain) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Grain Matching:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($grain); ?></span></div>
									<?php endif; ?>
									<?php if (!empty($grain_files) && is_array($grain_files)) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Grain Drawing:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html(implode(', ', array_column($grain_files, 'name'))); ?></span></div>
									<?php endif; ?>
									<?php if ($grain_notes) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Grain Notes:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($grain_notes); ?></span></div>
									<?php endif; ?>
									<?php if ($extra_cuts) : ?>
										<div class="cl-print-spec-item"><span class="cl-print-k"><?php esc_html_e('Additional Cuts:', 'cutlist-catalogue'); ?></span> <span class="cl-print-v"><?php echo esc_html($extra_cuts); ?></span></div>
									<?php endif; ?>
								</div>
							</td>

							<td class="cl-td-qty"><?php echo esc_html($qty); ?></td>
							<td class="cl-td-unit"><?php echo wp_kses_post($unit_price_formatted); ?></td>
							<td class="cl-td-total"><?php echo wp_kses_post($line_total_formatted); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Totals Table on Right -->
			<div class="cl-print-totals-wrapper">
				<table class="cl-print-totals-table">
					<tr>
						<td class="cl-print-tot-label"><?php esc_html_e('Subtotal:', 'cutlist-catalogue'); ?></td>
						<td class="cl-print-tot-val"><?php echo wp_kses_post($subtotal_formatted); ?></td>
					</tr>
					<tr class="cl-print-tot-grand-row">
						<td class="cl-print-tot-label"><?php esc_html_e('Total (incl. VAT):', 'cutlist-catalogue'); ?></td>
						<td class="cl-print-tot-val cl-print-tot-grand-val"><?php echo wp_kses_post($order_total); ?></td>
					</tr>
					<tr>
						<td class="cl-print-tot-label"><?php esc_html_e('Payment method:', 'cutlist-catalogue'); ?></td>
						<td class="cl-print-tot-val"><?php echo esc_html($payment_method_title); ?></td>
					</tr>
				</table>
			</div>
		</div>

		<!-- 5. BOTTOM NOTES & SUPPORT SECTION -->
		<div class="cl-print-bottom-grid">
			<!-- Notes Box -->
			<div class="cl-print-bottom-card">
				<div class="cl-print-card-header">
					<div class="cl-print-card-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
							<polyline points="14 2 14 8 20 8"></polyline>
							<line x1="16" y1="13" x2="8" y2="13"></line>
							<line x1="16" y1="17" x2="8" y2="17"></line>
							<polyline points="10 9 9 9 8 9"></polyline>
						</svg>
					</div>
					<h4 class="cl-print-card-title"><?php esc_html_e('Notes', 'cutlist-catalogue'); ?></h4>
				</div>
				<ol class="cl-print-notes-list">
					<li><?php esc_html_e('All dimensions are in millimeters (mm).', 'cutlist-catalogue'); ?></li>
					<li><?php esc_html_e('Additional cuts are charged per cut as mentioned.', 'cutlist-catalogue'); ?></li>
					<li><?php esc_html_e('This is an order confirmation and not a tax invoice.', 'cutlist-catalogue'); ?></li>
					<li><?php esc_html_e('If you have any questions, please contact our support team.', 'cutlist-catalogue'); ?></li>
				</ol>
			</div>

			<!-- Need Help Box -->
			<div class="cl-print-bottom-card">
				<div class="cl-print-card-header">
					<div class="cl-print-card-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
						</svg>
					</div>
					<h4 class="cl-print-card-title"><?php esc_html_e('Need help?', 'cutlist-catalogue'); ?></h4>
				</div>
				<p class="cl-print-card-desc">
					<?php esc_html_e("We're here to help. If you have any questions about your order, feel free to contact us.", 'cutlist-catalogue'); ?>
				</p>
				<div class="cl-print-contact-lines">
					<div class="cl-print-contact-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
						<strong>support@cutlist.com</strong>
					</div>
					<div class="cl-print-contact-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
						<strong>+91 71234 56789</strong>
					</div>
				</div>
			</div>
		</div>

		<!-- 6. FOOTER BRAND BAR -->
		<div class="cl-print-footer">
			<div class="cl-print-footer-left">
				<div class="cl-print-logo-main">
					<span class="cl-print-logo-text cl-print-logo-text--sm">Cutlist</span>
					<span class="cl-print-logo-dash"></span>
				</div>
				<div class="cl-print-logo-tagline cl-print-logo-tagline--sm">PANELS &nbsp;MEASURED &nbsp;BETTER</div>
			</div>

			<div class="cl-print-footer-center">
				<h4 class="cl-print-thanks"><?php esc_html_e('Thank you for choosing Cutlist!', 'cutlist-catalogue'); ?></h4>
				<div class="cl-print-accent-line"></div>
				<span class="cl-print-footer-sub"><?php esc_html_e('Quality materials. Precision cuts. Better spaces.', 'cutlist-catalogue'); ?></span>
			</div>

			<div class="cl-print-footer-right">
				<span class="cl-print-domain">cutlist.com</span>
			</div>
		</div>

	</div>

	<script>
		// Auto-trigger print when loaded
		window.addEventListener('DOMContentLoaded', function() {
			// Slight delay to ensure fonts and images are parsed
			setTimeout(function() {
				window.print();
			}, 300);
		});
	</script>
</body>
</html>
