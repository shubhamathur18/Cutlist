<?php
/**
 * My Addresses
 *
 * Custom Cutlist Catalogue Address List
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __('Billing address', 'woocommerce'),
			'shipping' => __('Shipping address', 'woocommerce'),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __('Billing address', 'woocommerce'),
		),
		$customer_id
	);
}
?>

<div class="cl-addresses-container">

	<!-- Header card -->
	<div class="cl-addresses-header">
		<h2 class="cl-addresses-title"><?php esc_html_e('Addresses', 'cutlist-catalogue'); ?></h2>
		<p class="cl-addresses-desc">
			<?php echo apply_filters('woocommerce_my_account_my_address_description', esc_html__('The following addresses will be used on the checkout page by default.', 'woocommerce')); ?>
		</p>
	</div>

	<!-- 2-Column Address Grid -->
	<div class="cl-addresses-grid">
		<?php foreach ($get_addresses as $name => $address_title) :
			$formatted_address = wc_get_account_formatted_address($name);
			$edit_url = wc_get_endpoint_url('edit-address', $name);
			$is_billing = ($name === 'billing');
		?>
			<div class="cl-address-card cl-address-card--<?php echo esc_attr($name); ?>">
				
				<div class="cl-address-card-top">
					<div class="cl-address-title-box">
						<div class="cl-address-icon">
							<?php if ($is_billing) : ?>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<rect x="2" y="4" width="20" height="16" rx="2"></rect>
									<line x1="2" y1="10" x2="22" y2="10"></line>
								</svg>
							<?php else : ?>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
									<circle cx="12" cy="10" r="3"></circle>
								</svg>
							<?php endif; ?>
						</div>
						<h3 class="cl-address-name"><?php echo esc_html($address_title); ?></h3>
					</div>

					<a href="<?php echo esc_url($edit_url); ?>" class="cl-btn-edit-address">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
							<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
						</svg>
						<span>
							<?php
							printf(
								$formatted_address ? esc_html__('Edit %s', 'cutlist-catalogue') : esc_html__('Add %s', 'cutlist-catalogue'),
								$is_billing ? __('Billing', 'cutlist-catalogue') : __('Shipping', 'cutlist-catalogue')
							);
							?>
						</span>
					</a>
				</div>

				<div class="cl-address-card-body">
					<address class="cl-formatted-address">
						<?php if ($formatted_address) : ?>
							<?php echo wp_kses_post($formatted_address); ?>
						<?php else : ?>
							<span class="cl-no-address-text"><?php esc_html_e('You have not set up this type of address yet.', 'woocommerce'); ?></span>
						<?php endif; ?>
					</address>

					<?php
					/**
					 * Used to output content after core address fields.
					 *
					 * @param string $name Address type.
					 * @since 8.7.0
					 */
					do_action('woocommerce_my_account_after_my_address', $name);
					?>
				</div>

			</div>
		<?php endforeach; ?>
	</div>

</div>
