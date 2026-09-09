<?php
/**
 * View Order
 *
 * Custom Cutlist Catalogue View Order template matching the exact specification:
 * - Breadcrumbs: Home > My account > Orders > Order #XXX
 * - Top header with Order title, placement text, Status pill badge, and Print Order button
 * - 3 Metric summary tiles (Order date, Order total, Payment method)
 * - Order details with individual item cards (square image, metadata table, qty, line total)
 * - Totals breakdown table with highlighted green grand total row
 * - Billing address card with green pin icon and email footer
 */

defined('ABSPATH') || exit;

// Ensure $order is resolved
if (!isset($order) || empty($order) || is_numeric($order)) {
	$res_order_id = isset($order_id) ? (int) $order_id : (isset($order) && is_numeric($order) ? (int) $order : 0);
	if (!$res_order_id && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('view-order')) {
		global $wp;
		$res_order_id = isset($wp->query_vars['view-order']) ? absint($wp->query_vars['view-order']) : 0;
	}
	if ($res_order_id && function_exists('wc_get_order')) {
		$order = wc_get_order($res_order_id);
	}
}

if (!$order || !is_a($order, 'WC_Order')) {
	?>
	<div class="woocommerce-notice woocommerce-notice--error" style="padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; margin: 20px 0;">
		<p style="margin: 0; font-size: 15px; color: #b91c1c; font-weight: 600;">
			<?php esc_html_e('Invalid order or order not found.', 'woocommerce'); ?>
		</p>
	</div>
	<?php
	return;
}

$order_id = $order->get_id();
$order_number = $order->get_order_number();
$order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format', 'F j, Y')) : '';
$order_total = $order->get_formatted_order_total();
$billing_email = $order->get_billing_email();
$billing_phone = $order->get_billing_phone();
$payment_method_title = $order->get_payment_method_title() ?: __('Cash on delivery', 'cutlist-catalogue');
$status_slug = $order->get_status();
$status_name = wc_get_order_status_name($status_slug);
$order_items = $order->get_items();
$item_count = count($order_items);
$subtotal_formatted = wc_price($order->get_subtotal());
?>

<div class="cl-vo-page-wrap">

	<!-- 0. BREADCRUMBS -->
	<nav class="cl-vo-breadcrumbs">
		<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cutlist-catalogue'); ?></a>
		<span class="cl-vo-crumb-sep">&rsaquo;</span>
		<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('My account', 'cutlist-catalogue'); ?></a>
		<span class="cl-vo-crumb-sep">&rsaquo;</span>
		<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('Orders', 'cutlist-catalogue'); ?></a>
		<span class="cl-vo-crumb-sep">&rsaquo;</span>
		<span class="cl-vo-crumb-current"><?php printf(esc_html__('Order #%s', 'cutlist-catalogue'), esc_html($order_number)); ?></span>
	</nav>

	<!-- 1. TOP HEADER ROW -->
	<div class="cl-vo-header-row">
		<div class="cl-vo-header-left">
			<h1 class="cl-vo-title"><?php printf(esc_html__('Order #%s', 'cutlist-catalogue'), esc_html($order_number)); ?></h1>
			<p class="cl-vo-placement-text">
				<?php
				printf(
					__('Order <strong>#%1$s</strong> was placed on <strong>%2$s</strong> and is currently <strong>%3$s</strong>.', 'cutlist-catalogue'),
					esc_html($order_number),
					esc_html($order_date),
					esc_html($status_name)
				);
				?>
			</p>
		</div>
		<div class="cl-vo-header-right">
			<span class="cl-vo-status-badge cl-vo-status--<?php echo esc_attr($status_slug); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"></circle>
					<polyline points="12 6 12 12 16 14"></polyline>
				</svg>
				<span><?php echo esc_html($status_name); ?></span>
			</span>
			<?php
			$print_url = add_query_arg(array(
				'print_order' => $order_id,
				'key'         => $order->get_order_key(),
			), home_url('/'));
			?>
			<button type="button" onclick="window.open('<?php echo esc_url($print_url); ?>', '_blank', 'width=950,height=1000,scrollbars=yes,resizable=yes');" class="cl-vo-btn-print">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span><?php esc_html_e('Print order', 'cutlist-catalogue'); ?></span>
			</button>
		</div>
	</div>

	<!-- 2. 3-COLUMN METRIC TILES -->
	<div class="cl-vo-metrics-grid">
		<!-- Order date -->
		<div class="cl-vo-metric-tile">
			<div class="cl-vo-metric-icon">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
			</div>
			<div class="cl-vo-metric-text">
				<span class="cl-vo-metric-label"><?php esc_html_e('Order date', 'cutlist-catalogue'); ?></span>
				<strong class="cl-vo-metric-val"><?php echo esc_html($order_date); ?></strong>
			</div>
		</div>

		<!-- Order total -->
		<div class="cl-vo-metric-tile">
			<div class="cl-vo-metric-icon">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
					<polyline points="14 2 14 8 20 8"></polyline>
					<line x1="16" y1="13" x2="8" y2="13"></line>
					<line x1="16" y1="17" x2="8" y2="17"></line>
					<polyline points="10 9 9 9 8 9"></polyline>
				</svg>
			</div>
			<div class="cl-vo-metric-text">
				<span class="cl-vo-metric-label"><?php esc_html_e('Order total', 'cutlist-catalogue'); ?></span>
				<strong class="cl-vo-metric-val"><?php echo wp_kses_post($order_total); ?></strong>
			</div>
		</div>

		<!-- Payment method -->
		<div class="cl-vo-metric-tile">
			<div class="cl-vo-metric-icon">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
					<line x1="1" y1="10" x2="23" y2="10"></line>
				</svg>
			</div>
			<div class="cl-vo-metric-text">
				<span class="cl-vo-metric-label"><?php esc_html_e('Payment method', 'cutlist-catalogue'); ?></span>
				<strong class="cl-vo-metric-val"><?php echo esc_html($payment_method_title); ?></strong>
			</div>
		</div>
	</div>

	<!-- 3. ORDER DETAILS SECTION -->
	<div class="cl-vo-details-section">
		<div class="cl-vo-details-heading-row">
			<h2 class="cl-vo-section-title"><?php esc_html_e('Order details', 'cutlist-catalogue'); ?></h2>
			<span class="cl-vo-items-count"><?php echo sprintf(_n('%d item', '%d items', $item_count, 'cutlist-catalogue'), $item_count); ?></span>
		</div>

		<!-- Item Cards List -->
		<div class="cl-vo-items-cards-list">
			<?php
			foreach ($order_items as $item_id => $item) :
				if (!$item instanceof WC_Order_Item_Product) continue;

				$product = $item->get_product();
				$item_name = $item->get_name();
				$qty = $item->get_quantity();
				$line_total_formatted = $order->get_formatted_line_subtotal($item);

				// Image resolution
				$img_id = (int) $item->get_meta('_cutlist_image_id', true);
				$img_url = $item->get_meta('_cutlist_image_url', true);
				$item_thumb_html = '';

				if ($img_id > 0) {
					$item_thumb_html = wp_get_attachment_image($img_id, 'woocommerce_thumbnail', false, array(
						'class' => 'cl-vo-item-img',
						'style' => 'width:115px; height:115px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; display:block;'
					));
				} elseif (!empty($img_url)) {
					$item_thumb_html = sprintf('<img src="%s" class="cl-vo-item-img" alt="%s" style="width:115px; height:115px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; display:block;" />', esc_url($img_url), esc_attr($item_name));
				} elseif ($product && $product->get_image_id()) {
					$item_thumb_html = $product->get_image(array(115, 115), array('class' => 'cl-vo-item-img', 'style' => 'width:115px; height:115px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; display:block;'));
				} else {
					$decor_meta = $item->get_meta(__('Decor', 'cutlist-catalogue'), true);
					$resolved = function_exists('cutlist_get_decor_image') ? cutlist_get_decor_image($decor_meta ?: $item_name) : array();
					if (!empty($resolved['url'])) {
						$item_thumb_html = sprintf('<img src="%s" class="cl-vo-item-img" alt="%s" style="width:115px; height:115px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; display:block;" />', esc_url($resolved['url']), esc_attr($item_name));
					} else {
						$item_thumb_html = '<div class="cl-vo-item-placeholder"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
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
				<div class="cl-vo-item-card">
					<div class="cl-vo-item-thumb-box">
						<?php echo $item_thumb_html; ?>
					</div>

					<div class="cl-vo-item-details-box">
						<h3 class="cl-vo-item-title"><?php echo esc_html($item_name); ?></h3>
						
						<div class="cl-vo-meta-grid">
							<?php if ($item_type) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Item Type:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($item_type); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($decor) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Decor:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($decor); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($thickness) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Thickness:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($thickness); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($dimensions) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Dimensions:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($dimensions); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($edging) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Edging Tape:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($edging); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($machining) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Machining:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($machining); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($spray) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Spray Finishing:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($spray); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($grain) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Grain Matching:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($grain); ?></span>
								</div>
							<?php endif; ?>

							<?php if (!empty($grain_files) && is_array($grain_files)) : ?>
								<div class="cl-vo-meta-row cl-vo-meta-row--files">
									<span class="cl-vo-meta-key"><?php esc_html_e('Grain Drawings:', 'cutlist-catalogue'); ?></span>
									<div class="cl-vo-drawing-files">
										<?php foreach ($grain_files as $gfile) : ?>
											<a href="<?php echo esc_url($gfile['url']); ?>" target="_blank" rel="noopener noreferrer" class="cl-drawing-chip">
												<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
												<span><?php echo esc_html($gfile['name']); ?></span>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>

							<?php if ($grain_notes) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Grain Notes:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($grain_notes); ?></span>
								</div>
							<?php endif; ?>

							<?php if ($extra_cuts) : ?>
								<div class="cl-vo-meta-row">
									<span class="cl-vo-meta-key"><?php esc_html_e('Additional Cuts:', 'cutlist-catalogue'); ?></span>
									<span class="cl-vo-meta-val"><?php echo esc_html($extra_cuts); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="cl-vo-item-pricing-box">
						<span class="cl-vo-item-qty">&times; <?php echo esc_html($qty); ?></span>
						<span class="cl-vo-item-total"><?php echo wp_kses_post($line_total_formatted); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Totals Table Box -->
		<div class="cl-vo-totals-box">
			<div class="cl-vo-totals-row">
				<span class="cl-vo-totals-label"><?php esc_html_e('Subtotal:', 'cutlist-catalogue'); ?></span>
				<span class="cl-vo-totals-val"><?php echo wp_kses_post($subtotal_formatted); ?></span>
			</div>
			<div class="cl-vo-totals-row cl-vo-totals-row--grand">
				<span class="cl-vo-grand-label"><?php esc_html_e('Total:', 'cutlist-catalogue'); ?></span>
				<span class="cl-vo-grand-val"><?php echo wp_kses_post($order_total); ?></span>
			</div>
			<div class="cl-vo-totals-row">
				<span class="cl-vo-totals-label"><?php esc_html_e('Payment method:', 'cutlist-catalogue'); ?></span>
				<span class="cl-vo-totals-val"><?php echo esc_html($payment_method_title); ?></span>
			</div>
		</div>

	</div>

	<!-- 4. BILLING ADDRESS SECTION -->
	<div class="cl-vo-address-section">
		<h2 class="cl-vo-section-title"><?php esc_html_e('Billing address', 'cutlist-catalogue'); ?></h2>
		
		<div class="cl-vo-address-card">
			<div class="cl-vo-address-top">
				<div class="cl-vo-address-icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
						<circle cx="12" cy="10" r="3"></circle>
					</svg>
				</div>
				<div class="cl-vo-address-content">
					<address class="cl-vo-address-lines">
						<?php
						$formatted_address = $order->get_formatted_billing_address();
						if ($formatted_address) {
							echo wp_kses_post($formatted_address);
						} else {
							echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '<br>';
							if ($order->get_billing_address_1()) echo esc_html($order->get_billing_address_1()) . '<br>';
							if ($order->get_billing_city()) echo esc_html($order->get_billing_city()) . ' ';
							if ($order->get_billing_postcode()) echo esc_html($order->get_billing_postcode()) . '<br>';
							if ($order->get_billing_country()) echo esc_html(WC()->countries->countries[$order->get_billing_country()] ?? $order->get_billing_country());
						}
						?>
					</address>
				</div>
			</div>

			<?php if ($billing_email) : ?>
				<div class="cl-vo-address-email-row">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
						<polyline points="22,6 12,13 2,6"></polyline>
					</svg>
					<a href="mailto:<?php echo esc_attr($billing_email); ?>" class="cl-vo-email-link"><?php echo esc_html($billing_email); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>

</div>
