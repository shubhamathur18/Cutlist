<?php
/**
 * Orders
 *
 * Custom Cutlist Catalogue Orders list template
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_orders', $has_orders); ?>

<div class="cl-orders-card cl-orders-page-card">
	<div class="cl-orders-header">
		<h2 class="cl-orders-title"><?php esc_html_e('All Orders', 'cutlist-catalogue'); ?></h2>
	</div>

	<?php if ($has_orders) : ?>
		<div class="cl-table-responsive">
			<table class="cl-orders-table">
				<thead>
					<tr>
						<?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
							<th class="cl-col-<?php echo esc_attr($column_id); ?>"><?php echo esc_html($column_name); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($customer_orders->orders as $customer_order) :
						$order      = wc_get_order($customer_order);
						$item_count = $order->get_item_count();
						$status_slug = $order->get_status();
						$status_name = wc_get_order_status_name($status_slug);
					?>
						<tr class="cl-order-row">
							<?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
								<td class="cl-col-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
									<?php if (has_action('woocommerce_my_account_my_orders_column_' . $column_id)) : ?>
										<?php do_action('woocommerce_my_account_my_orders_column_' . $column_id, $order); ?>

									<?php elseif ('order-number' === $column_id) : ?>
										<a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="cl-order-number-link">
											<strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
										</a>

									<?php elseif ('order-date' === $column_id) : ?>
										<time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>">
											<?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format', 'F j, Y'))); ?>
										</time>

									<?php elseif ('order-status' === $column_id) : ?>
										<span class="cl-status-pill cl-status--<?php echo esc_attr($status_slug); ?>">
											<?php echo esc_html($status_name); ?>
										</span>

									<?php elseif ('order-total' === $column_id) : ?>
										<?php
										echo wp_kses_post($order->get_formatted_order_total());
										?>
										<span class="cl-order-item-count">
											(<?php echo sprintf(_n('%d item', '%d items', $item_count, 'cutlist-catalogue'), $item_count); ?>)
										</span>

									<?php elseif ('order-actions' === $column_id) : ?>
										<?php
										$actions = wc_get_account_orders_actions($order);
										if (!empty($actions)) {
											foreach ($actions as $key => $action) {
												echo '<a href="' . esc_url($action['url']) . '" class="cl-btn-view-order ' . sanitize_html_class($key) . '">';
												echo '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> ';
												echo esc_html($action['name']);
												echo '</a>';
											}
										}
										?>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php do_action('woocommerce_before_account_orders_pagination'); ?>

		<?php if (1 < $customer_orders->max_num_pages) : ?>
			<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination cl-pagination">
				<?php if (1 !== $current_page) : ?>
					<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button cl-btn" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>"><?php esc_html_e('Previous', 'woocommerce'); ?></a>
				<?php endif; ?>

				<?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
					<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button cl-btn" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>"><?php esc_html_e('Next', 'woocommerce'); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

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

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
