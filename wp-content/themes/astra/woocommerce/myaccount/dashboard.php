<?php
/**
 * My Account Dashboard
 *
 * Custom Cutlist Catalogue Dashboard matching the exact receipt & account mockup
 */

defined('ABSPATH') || exit;

$current_user_id = get_current_user_id();
$user = wp_get_current_user();
$display_name = $user ? ($user->first_name ? $user->first_name : $user->display_name) : __('Valued Customer', 'cutlist-catalogue');
$banner_img_url = defined('CUTLIST_CATALOGUE_PATH') ? plugins_url('assets/wood-samples-banner.jpg', CUTLIST_CATALOGUE_PATH . 'cutlist-catalogue.php') : '';

// Fetch recent 5 customer orders
$customer_orders = array();
if ($current_user_id && function_exists('wc_get_orders')) {
	$customer_orders = wc_get_orders(array(
		'customer' => $current_user_id,
		'limit'    => 5,
		'orderby'  => 'date',
		'order'    => 'DESC',
		'return'   => 'objects',
	));
}
?>

<div class="cl-dashboard-wrap">

	<!-- 1. WELCOME HERO BANNER -->
	<div class="cl-hero-banner">
		<div class="cl-hero-text">
			<span class="cl-hero-badge"><?php esc_html_e('WELCOME BACK', 'cutlist-catalogue'); ?></span>
			<h1 class="cl-hero-title"><?php printf(esc_html__('Hello, %s!', 'cutlist-catalogue'), esc_html($display_name)); ?></h1>
			<p class="cl-hero-desc">
				<?php esc_html_e('From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your account details.', 'cutlist-catalogue'); ?>
			</p>
		</div>
		<div class="cl-hero-visual">
			<div class="cl-hero-quote-box">
				<span class="cl-quote-line cl-quote-1">Build</span>
				<span class="cl-quote-line cl-quote-2">Create</span>
				<span class="cl-quote-line cl-quote-3">Bring ideas</span>
				<span class="cl-quote-line cl-quote-4">to life</span>
			</div>
			<div class="cl-hero-img-wrap">
				<img src="<?php echo esc_url($banner_img_url); ?>" alt="Wood board samples" class="cl-hero-wood-img" />
			</div>
		</div>
	</div>

	<!-- 2. QUICK ACTION TILES (4 Columns) -->
	<div class="cl-quick-grid">
		
		<!-- View Orders -->
		<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="cl-quick-card">
			<div class="cl-quick-top">
				<div class="cl-quick-icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
						<polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
						<line x1="12" y1="22.08" x2="12" y2="12"></line>
					</svg>
				</div>
				<span class="cl-quick-arrow">&rarr;</span>
			</div>
			<h3 class="cl-quick-title"><?php esc_html_e('View Orders', 'cutlist-catalogue'); ?></h3>
			<p class="cl-quick-desc"><?php esc_html_e('Check the status of your recent orders.', 'cutlist-catalogue'); ?></p>
		</a>

		<!-- Manage Addresses -->
		<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>" class="cl-quick-card">
			<div class="cl-quick-top">
				<div class="cl-quick-icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
						<circle cx="12" cy="10" r="3"></circle>
					</svg>
				</div>
				<span class="cl-quick-arrow">&rarr;</span>
			</div>
			<h3 class="cl-quick-title"><?php esc_html_e('Manage Addresses', 'cutlist-catalogue'); ?></h3>
			<p class="cl-quick-desc"><?php esc_html_e('Update your shipping and billing addresses.', 'cutlist-catalogue'); ?></p>
		</a>

		<!-- Account Details -->
		<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="cl-quick-card">
			<div class="cl-quick-top">
				<div class="cl-quick-icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
				</div>
				<span class="cl-quick-arrow">&rarr;</span>
			</div>
			<h3 class="cl-quick-title"><?php esc_html_e('Account Details', 'cutlist-catalogue'); ?></h3>
			<p class="cl-quick-desc"><?php esc_html_e('Edit your personal information.', 'cutlist-catalogue'); ?></p>
		</a>

		<!-- Downloads -->
		<a href="<?php echo esc_url(wc_get_account_endpoint_url('downloads')); ?>" class="cl-quick-card">
			<div class="cl-quick-top">
				<div class="cl-quick-icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="7 10 12 15 17 10"></polyline>
						<line x1="12" y1="15" x2="12" y2="3"></line>
					</svg>
				</div>
				<span class="cl-quick-arrow">&rarr;</span>
			</div>
			<h3 class="cl-quick-title"><?php esc_html_e('Downloads', 'cutlist-catalogue'); ?></h3>
			<p class="cl-quick-desc"><?php esc_html_e('Access your downloadable files.', 'cutlist-catalogue'); ?></p>
		</a>

	</div>

	<!-- 3. RECENT ORDERS TABLE CARD -->
	<div class="cl-orders-card">
		<div class="cl-orders-header">
			<h2 class="cl-orders-title"><?php esc_html_e('Recent Orders', 'cutlist-catalogue'); ?></h2>
			<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="cl-orders-view-all">
				<span><?php esc_html_e('View all orders', 'cutlist-catalogue'); ?></span>
				<span class="cl-view-all-arrow">&rarr;</span>
			</a>
		</div>

		<?php if (!empty($customer_orders)) : ?>
			<div class="cl-table-responsive">
				<table class="cl-orders-table">
					<thead>
						<tr>
							<th class="cl-col-num"><?php esc_html_e('Order #', 'cutlist-catalogue'); ?></th>
							<th class="cl-col-date"><?php esc_html_e('Date', 'cutlist-catalogue'); ?></th>
							<th class="cl-col-items"><?php esc_html_e('Items', 'cutlist-catalogue'); ?></th>
							<th class="cl-col-total"><?php esc_html_e('Total', 'cutlist-catalogue'); ?></th>
							<th class="cl-col-status"><?php esc_html_e('Status', 'cutlist-catalogue'); ?></th>
							<th class="cl-col-actions"><?php esc_html_e('Actions', 'cutlist-catalogue'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($customer_orders as $order) :
							$order_id = $order->get_id();
							$order_number = $order->get_order_number();
							$order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format', 'F j, Y')) : '';
							$item_count = $order->get_item_count();
							$order_total = $order->get_formatted_order_total();
							$status_slug = $order->get_status();
							$status_name = wc_get_order_status_name($status_slug);
							$view_url = $order->get_view_order_url();
						?>
							<tr class="cl-order-row">
								<td class="cl-col-num">
									<strong>#<?php echo esc_html($order_number); ?></strong>
								</td>
								<td class="cl-col-date">
									<?php echo esc_html($order_date); ?>
								</td>
								<td class="cl-col-items">
									<?php echo sprintf(_n('%d item', '%d items', $item_count, 'cutlist-catalogue'), $item_count); ?>
								</td>
								<td class="cl-col-total">
									<?php echo wp_kses_post($order_total); ?>
								</td>
								<td class="cl-col-status">
									<span class="cl-status-pill cl-status--<?php echo esc_attr($status_slug); ?>">
										<?php echo esc_html($status_name); ?>
									</span>
								</td>
								<td class="cl-col-actions">
									<a href="<?php echo esc_url($view_url); ?>" class="cl-btn-view-order">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
											<circle cx="12" cy="12" r="3"></circle>
										</svg>
										<span><?php esc_html_e('View order', 'cutlist-catalogue'); ?></span>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="cl-orders-empty">
				<div class="cl-empty-icon">
					<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="9" cy="21" r="1"></circle>
						<circle cx="20" cy="21" r="1"></circle>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
					</svg>
				</div>
				<h3><?php esc_html_e('No orders placed yet', 'cutlist-catalogue'); ?></h3>
				<p><?php esc_html_e('Ready to configure and cut your custom panels?', 'cutlist-catalogue'); ?></p>
				<a href="<?php echo esc_url(home_url('/cutlist/')); ?>" class="cl-btn-start">
					<?php esc_html_e('Start Cutting List', 'cutlist-catalogue'); ?> &rarr;
				</a>
			</div>
		<?php endif; ?>

	</div>

</div>
