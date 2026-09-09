<?php
/**
 * My Account page wrapper
 *
 * Custom Cutlist Catalogue My Account Layout
 */

defined('ABSPATH') || exit;

$banner_url = defined('CUTLIST_CATALOGUE_PATH') ? plugins_url('assets/wood-samples-banner.jpg', CUTLIST_CATALOGUE_PATH . 'cutlist-catalogue.php') : '';
?>

<div class="cl-account-layout-wrap">
	<div class="cl-account-sidebar-col">
		<?php
		/**
		 * My Account navigation.
		 *
		 * @since 2.6.0
		 */
		do_action('woocommerce_account_navigation');
		?>

		<!-- Need help? Card -->
		<div class="cl-need-help-card">
			<div class="cl-need-help-img" style="background-image: url('<?php echo esc_url($banner_url); ?>');"></div>
			<div class="cl-need-help-content">
				<h4 class="cl-need-help-title"><?php esc_html_e('Need help?', 'cutlist-catalogue'); ?></h4>
				<p class="cl-need-help-text"><?php esc_html_e("If you have any questions, we're here to help.", 'cutlist-catalogue'); ?></p>
				<a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cl-need-help-btn">
					<span><?php esc_html_e('Contact us', 'cutlist-catalogue'); ?></span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<line x1="7" y1="17" x2="17" y2="7"></line>
						<polyline points="7 7 17 7 17 17"></polyline>
					</svg>
				</a>
			</div>
		</div>
	</div>

	<div class="cl-account-main-col">
		<div class="woocommerce-MyAccount-content cl-account-main-content">
			<?php
			/**
			 * My Account content.
			 *
			 * @since 2.6.0
			 */
			do_action('woocommerce_account_content');
			?>
		</div>
	</div>
</div>
