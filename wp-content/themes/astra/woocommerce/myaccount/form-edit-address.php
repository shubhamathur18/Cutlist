<?php
/**
 * Edit address form
 *
 * Custom Cutlist Catalogue Edit Address Form
 */

defined('ABSPATH') || exit;

$page_title = ('billing' === $load_address) ? esc_html__('Billing address', 'woocommerce') : esc_html__('Shipping address', 'woocommerce');

do_action('woocommerce_before_edit_account_address_form'); ?>

<?php if (!$load_address) : ?>
	<?php wc_get_template('myaccount/my-address.php'); ?>
<?php else : ?>

	<div class="cl-edit-address-card">
		<div class="cl-edit-address-header">
			<div class="cl-edit-address-header-left">
				<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>" class="cl-back-link">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<line x1="19" y1="12" x2="5" y2="12"></line>
						<polyline points="12 19 5 12 12 5"></polyline>
					</svg>
					<span><?php esc_html_e('Back to Addresses', 'cutlist-catalogue'); ?></span>
				</a>
				<h2 class="cl-edit-address-title"><?php printf(esc_html__('Edit %s', 'cutlist-catalogue'), apply_filters('woocommerce_my_account_edit_address_title', $page_title, $load_address)); ?></h2>
			</div>
		</div>

		<form method="post" class="cl-address-form" novalidate>
			<div class="woocommerce-address-fields">
				<?php do_action("woocommerce_before_edit_address_form_{$load_address}"); ?>

				<div class="woocommerce-address-fields__field-wrapper cl-address-fields-grid">
					<?php
					foreach ($address as $key => $field) {
						woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value']));
					}
					?>
				</div>

				<?php do_action("woocommerce_after_edit_address_form_{$load_address}"); ?>

				<div class="cl-form-submit-row">
					<button type="submit" class="button cl-btn cl-btn-primary" name="save_address" value="<?php esc_attr_e('Save address', 'woocommerce'); ?>">
						<?php esc_html_e('Save address', 'woocommerce'); ?>
					</button>
					<?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
					<input type="hidden" name="action" value="edit_address" />
				</div>
			</div>
		</form>
	</div>

<?php endif; ?>

<?php do_action('woocommerce_after_edit_account_address_form'); ?>
