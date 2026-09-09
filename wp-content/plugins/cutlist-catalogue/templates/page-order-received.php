<?php
/**
 * Custom Order Received Page Template for both Classic & Block Themes
 */

defined('ABSPATH') || exit;

if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div class="wp-site-blocks">
		<?php
		if (function_exists('block_header_area')) {
			block_header_area();
		} elseif (function_exists('block_template_part')) {
			block_template_part('header');
		}
		?>
		<main class="wp-block-group alignwide is-layout-constrained" style="max-width: 1240px; margin: 20px auto 60px auto; padding: 0 20px;">
			<?php
			$thankyou_file = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/thankyou.php' : '';
			if ($thankyou_file && file_exists($thankyou_file)) {
				include $thankyou_file;
			}
			?>
		</main>
		<?php
		if (function_exists('block_footer_area')) {
			block_footer_area();
		} elseif (function_exists('block_template_part')) {
			block_template_part('footer');
		}
		?>
	</div>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
} else {
	get_header();
	?>
	<main id="primary" class="site-main cl-order-received-main" style="max-width: 1240px; margin: 0 auto; padding: 20px 20px 60px 20px;">
		<?php
		$thankyou_file = defined('CUTLIST_CATALOGUE_PATH') ? CUTLIST_CATALOGUE_PATH . 'templates/thankyou.php' : '';
		if ($thankyou_file && file_exists($thankyou_file)) {
			include $thankyou_file;
		}
		?>
	</main>
	<?php
	get_footer();
}
