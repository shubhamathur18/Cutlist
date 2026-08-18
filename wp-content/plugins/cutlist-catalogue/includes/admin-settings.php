<?php
/**
 * Admin Settings Page for Cutlist Catalogue
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_menu', function () {
	add_options_page(
		__('Cutlist Settings', 'cutlist-catalogue'),
		__('Cutlist Settings', 'cutlist-catalogue'),
		'manage_options',
		'cutlist-settings',
		'cutlist_render_admin_settings_page'
	);
});

add_action('admin_init', function () {
	register_setting('cutlist_settings_group', 'cutlist_grain_match_price', array(
		'type'              => 'number',
		'sanitize_callback' => 'floatval',
		'default'           => 12.70,
	));
	register_setting('cutlist_settings_group', 'cutlist_grain_match_code', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'GRN-MTCH',
	));
	register_setting('cutlist_settings_group', 'cutlist_grain_match_name', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Grain-matching of panels in clusters',
	));
	register_setting('cutlist_settings_group', 'cutlist_grain_match_desc', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Grain-matching of panels based on customer requirements – charged per panel',
	));
});

function cutlist_render_admin_settings_page() {
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Cutlist Catalogue Settings', 'cutlist-catalogue'); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields('cutlist_settings_group');
			do_settings_sections('cutlist_settings_group');
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="cutlist_grain_match_price"><?php esc_html_e('Grain Match Price (£)', 'cutlist-catalogue'); ?></label>
					</th>
					<td>
						<input type="number" step="0.01" min="0" id="cutlist_grain_match_price" name="cutlist_grain_match_price" value="<?php echo esc_attr(get_option('cutlist_grain_match_price', 12.70)); ?>" class="regular-text">
						<p class="description"><?php esc_html_e('Additional fee charged per panel when the Grain match checkbox is ticked on a table row.', 'cutlist-catalogue'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cutlist_grain_match_code"><?php esc_html_e('Grain Match Service Code', 'cutlist-catalogue'); ?></label>
					</th>
					<td>
						<input type="text" id="cutlist_grain_match_code" name="cutlist_grain_match_code" value="<?php echo esc_attr(get_option('cutlist_grain_match_code', 'GRN-MTCH')); ?>" class="regular-text">
						<p class="description"><?php esc_html_e('Service code shown in order summary and cart for Grain Matching.', 'cutlist-catalogue'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cutlist_grain_match_name"><?php esc_html_e('Grain Match Service Name', 'cutlist-catalogue'); ?></label>
					</th>
					<td>
						<input type="text" id="cutlist_grain_match_name" name="cutlist_grain_match_name" value="<?php echo esc_attr(get_option('cutlist_grain_match_name', 'Grain-matching of panels in clusters')); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cutlist_grain_match_desc"><?php esc_html_e('Grain Match Description', 'cutlist-catalogue'); ?></label>
					</th>
					<td>
						<input type="text" id="cutlist_grain_match_desc" name="cutlist_grain_match_desc" value="<?php echo esc_attr(get_option('cutlist_grain_match_desc', 'Grain-matching of panels based on customer requirements – charged per panel')); ?>" class="regular-text">
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
