<?php
/**
 * Tabbed Meta Box for Frame Style Colours, Base Prices, and Per-Colour Galleries.
 * Allows managing colour variants as tabs, each with its own Base Price per Door (£)
 * and normal WordPress drag-and-drop image gallery.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('CUTLIST_FRAME_COLORS_META', '_frame_colors_data');

add_action('add_meta_boxes', function () {
	add_meta_box(
		'sd_frame_colors_tabbed_box',
		__('Frame Profile Colours, Pricing & Galleries', 'cutlist-catalogue'),
		'cutlist_render_frame_colors_tabbed_meta_box',
		'sd_frame_style',
		'normal',
		'high'
	);
});

add_action('admin_enqueue_scripts', function ($hook) {
	if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
		return;
	}
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'sd_frame_style') {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script('jquery-ui-sortable');
});

function cutlist_render_frame_colors_tabbed_meta_box($post) {
	wp_nonce_field('cutlist_save_frame_colors_tabbed', 'cutlist_frame_colors_tabbed_nonce');

	$colors = get_post_meta($post->ID, CUTLIST_FRAME_COLORS_META, true);
	if (empty($colors) || !is_array($colors)) {
		$colors = get_post_meta($post->ID, 'colors', true);
	}
	
	// Default base price from post or fallback
	$post_base_price = (float) get_post_meta($post->ID, 'base_price', true);
	if ($post_base_price <= 0 && function_exists('get_field')) {
		$post_base_price = (float) get_field('base_price', $post->ID);
	}
	if ($post_base_price <= 0) {
		$post_base_price = 74.00;
	}

	if (empty($colors) || !is_array($colors)) {
		$colors = [
			[
				'color_name'   => 'Silver satin',
				'color_hex'    => '#c8c8c8',
				'base_price'   => $post_base_price,
				'gallery_ids'  => [],
			],
			[
				'color_name'   => 'Matt black',
				'color_hex'    => '#1a1a1a',
				'base_price'   => $post_base_price,
				'gallery_ids'  => [],
			],
			[
				'color_name'   => 'Bronze',
				'color_hex'    => '#6e5849',
				'base_price'   => $post_base_price + 15.00,
				'gallery_ids'  => [],
			],
			[
				'color_name'   => 'Brushed brass',
				'color_hex'    => '#c5a059',
				'base_price'   => $post_base_price + 18.00,
				'gallery_ids'  => [],
			],
		];
	}

	$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '£';
	?>
	<style>
		.sd-color-tabs-container {
			margin-top: 5px;
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			overflow: hidden;
		}
		.sd-tabs-nav-bar {
			display: flex;
			align-items: center;
			background: #f1f5f9;
			border-bottom: 1px solid #cbd5e1;
			padding: 8px 10px 0;
			gap: 6px;
			flex-wrap: wrap;
		}
		.sd-tab-nav-btn {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 14px;
			background: #e2e8f0;
			border: 1px solid #cbd5e1;
			border-bottom: none;
			border-radius: 6px 6px 0 0;
			font-size: 13px;
			font-weight: 600;
			color: #475569;
			cursor: pointer;
			text-decoration: none;
			transition: all 0.15s ease;
			position: relative;
			top: 1px;
		}
		.sd-tab-nav-btn:hover {
			background: #ffffff;
			color: #0f172a;
		}
		.sd-tab-nav-btn.active {
			background: #ffffff;
			color: #0284c7;
			border-color: #cbd5e1;
			border-bottom-color: #ffffff;
			box-shadow: 0 -2px 0 #0284c7 inset;
			z-index: 2;
		}
		.sd-tab-dot {
			width: 13px;
			height: 13px;
			border-radius: 50%;
			border: 1px solid rgba(0,0,0,0.2);
			display: inline-block;
			flex-shrink: 0;
		}
		.sd-tab-close-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 16px;
			height: 16px;
			border-radius: 50%;
			color: #94a3b8;
			font-size: 14px;
			line-height: 1;
			cursor: pointer;
			margin-left: 4px;
			transition: all 0.15s;
		}
		.sd-tab-close-btn:hover {
			background: #ef4444;
			color: #ffffff;
		}
		.sd-tab-add-btn {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			padding: 6px 12px;
			background: #0284c7;
			color: #ffffff;
			border: none;
			border-radius: 4px;
			font-size: 12px;
			font-weight: 600;
			cursor: pointer;
			margin-bottom: 8px;
			margin-left: auto;
			transition: background 0.15s;
		}
		.sd-tab-add-btn:hover {
			background: #0369a1;
		}
		.sd-tab-panes-wrap {
			background: #ffffff;
			padding: 18px 20px;
			min-height: 250px;
		}
		.sd-tab-pane {
			display: none;
		}
		.sd-tab-pane.active {
			display: block;
		}
		.sd-pane-fields-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 16px;
			background: #f8fafc;
			padding: 14px 16px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			margin-bottom: 18px;
		}
		.sd-field-item label {
			display: block;
			font-size: 12px;
			font-weight: 600;
			color: #334155;
			margin-bottom: 6px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.sd-picker-group {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.sd-picker-group input[type="color"] {
			width: 36px;
			height: 36px;
			padding: 0;
			border: 1px solid #cbd5e1;
			border-radius: 4px;
			cursor: pointer;
		}
		.sd-price-group {
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.sd-price-sym {
			font-size: 15px;
			font-weight: 600;
			color: #475569;
		}
		.sd-normal-gallery-section {
			margin-top: 10px;
		}
		.sd-normal-gallery-section label {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: #1e293b;
			margin-bottom: 4px;
		}
		.sd-normal-gallery-list {
			margin: 8px 0 12px;
			padding: 0;
			list-style: none;
			min-height: 85px;
		}
		.sd-normal-gallery-list:after {
			content: "";
			display: table;
			clear: both;
		}
		.sd-normal-gallery-item {
			width: 80px;
			height: 80px;
			float: left;
			margin: 0 10px 10px 0;
			border: 1px solid #ccd0d4;
			border-radius: 3px;
			background: #f0f0f1;
			position: relative;
			cursor: grab;
			cursor: -webkit-grab;
			box-sizing: border-box;
			overflow: visible;
			user-select: none;
			-webkit-user-select: none;
		}
		.sd-normal-gallery-item:active {
			cursor: grabbing;
			cursor: -webkit-grabbing;
		}
		.sd-normal-gallery-item:hover {
			border-color: #0073aa;
		}
		.sd-normal-gallery-item img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			border-radius: 2px;
			pointer-events: none;
			-webkit-user-drag: none;
			user-select: none;
		}
		.sd-gallery-remove-btn {
			position: absolute;
			top: -5px;
			right: -5px;
			background: #ffffff;
			border: 1px solid #ccd0d4;
			border-radius: 50%;
			width: 18px;
			height: 18px;
			line-height: 15px;
			font-size: 14px;
			color: #a00;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 0;
			transition: all 0.15s;
			box-shadow: 0 1px 2px rgba(0,0,0,0.15);
			z-index: 5;
		}
		.sd-gallery-remove-btn:hover {
			background: #b32d2e;
			color: #ffffff;
			border-color: #b32d2e;
		}
		.sd-gallery-sortable-placeholder {
			float: left;
			margin: 0 10px 10px 0;
			width: 80px;
			height: 80px;
			border: 2px dashed #0073aa;
			border-radius: 3px;
			background: #e7f3fa;
			box-sizing: border-box;
			list-style: none;
			visibility: visible !important;
		}
		.sd-tab-footer-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-top: 18px;
			padding-top: 14px;
			border-top: 1px solid #e2e8f0;
			clear: both;
		}
		.sd-btn-delete-tab-pane {
			color: #ef4444;
			background: none;
			border: 1px solid #fca5a5;
			border-radius: 4px;
			padding: 4px 10px;
			font-size: 12px;
			cursor: pointer;
			transition: all 0.15s;
		}
		.sd-btn-delete-tab-pane:hover {
			background: #fef2f2;
			border-color: #ef4444;
		}
	</style>

	<div class="sd-color-tabs-container" id="sdColorTabsContainer">
		<!-- Tabs Nav Bar -->
		<div class="sd-tabs-nav-bar" id="sdTabsNavBar">
			<?php foreach ($colors as $idx => $c) :
				$name = $c['color_name'] ?? ($c['name'] ?? 'Colour Finish');
				$hex = $c['color_hex'] ?? ($c['hex'] ?? '#c8c8c8');
				$isActive = ($idx === 0);
			?>
				<button type="button" class="sd-tab-nav-btn <?php echo $isActive ? 'active' : ''; ?>" data-target="sd-pane-<?php echo esc_attr($idx); ?>">
					<span class="sd-tab-dot" style="background: <?php echo esc_attr($hex); ?>;"></span>
					<span class="sd-tab-label"><?php echo esc_html($name); ?></span>
					<span class="sd-tab-close-btn" title="<?php esc_attr_e('Delete Colour', 'cutlist-catalogue'); ?>">&times;</span>
				</button>
			<?php endforeach; ?>

			<button type="button" class="sd-tab-add-btn" id="sdAddColorTabBtn">
				+ <?php esc_html_e('Add New Colour', 'cutlist-catalogue'); ?>
			</button>
		</div>

		<!-- Tab Panes -->
		<div class="sd-tab-panes-wrap" id="sdTabPanesWrap">
			<?php foreach ($colors as $idx => $c) :
				$name = $c['color_name'] ?? ($c['name'] ?? 'Silver satin');
				$hex = $c['color_hex'] ?? ($c['hex'] ?? '#c8c8c8');
				$price = isset($c['base_price']) && (float)$c['base_price'] > 0 ? (float)$c['base_price'] : ($post_base_price + (float)($c['surcharge'] ?? 0));
				$raw_ids = $c['gallery_ids'] ?? [];
				if (is_string($raw_ids)) {
					$raw_ids = array_filter(array_map('trim', explode(',', $raw_ids)));
				}
				if (!is_array($raw_ids)) {
					$raw_ids = [];
				}

				// If gallery_ids is empty, check legacy sample_image
				$legacy_img = $c['sample_image'] ?? ($c['image'] ?? '');
				if (empty($raw_ids) && !empty($legacy_img)) {
					if (is_numeric($legacy_img) && (int)$legacy_img > 0) {
						$raw_ids[] = (int)$legacy_img;
					}
				}

				$isActive = ($idx === 0);
			?>
				<div class="sd-tab-pane <?php echo $isActive ? 'active' : ''; ?>" id="sd-pane-<?php echo esc_attr($idx); ?>" data-idx="<?php echo esc_attr($idx); ?>">
					
					<!-- Top Fields Grid -->
					<div class="sd-pane-fields-grid">
						<div class="sd-field-item">
							<label><?php esc_html_e('Colour Finish Name', 'cutlist-catalogue'); ?> <span style="color:#ef4444;">*</span></label>
							<input type="text" name="sd_colors[<?php echo esc_attr($idx); ?>][name]" value="<?php echo esc_attr($name); ?>" class="regular-text sd-color-name-input" placeholder="e.g. Silver satin" style="width:100%;" required>
						</div>

						<div class="sd-field-item">
							<label><?php esc_html_e('Colour Swatch / Hex', 'cutlist-catalogue'); ?></label>
							<div class="sd-picker-group">
								<input type="color" class="sd-color-picker-input" value="<?php echo esc_attr($hex); ?>">
								<input type="text" name="sd_colors[<?php echo esc_attr($idx); ?>][hex]" class="sd-hex-text-input" value="<?php echo esc_attr($hex); ?>" placeholder="#c8c8c8" style="width:100px;">
							</div>
						</div>

						<div class="sd-field-item">
							<label><?php esc_html_e('Base Price per Door (£)', 'cutlist-catalogue'); ?> <span style="color:#ef4444;">*</span></label>
							<div class="sd-price-group">
								<span class="sd-price-sym"><?php echo esc_html($currency_sym); ?></span>
								<input type="number" step="0.01" name="sd_colors[<?php echo esc_attr($idx); ?>][base_price]" value="<?php echo esc_attr(number_format($price, 2, '.', '')); ?>" style="width:110px;" required>
							</div>
						</div>
					</div>

					<!-- Normal WordPress Gallery Section -->
					<div class="sd-normal-gallery-section">
						<label><?php esc_html_e('Gallery', 'cutlist-catalogue'); ?></label>
						<p class="description" style="margin: 2px 0 8px;">
							<?php esc_html_e('The 1st image is used as the main swatch and primary render for this colour finish. Drag and drop images to re-order.', 'cutlist-catalogue'); ?>
						</p>

						<ul class="sd-normal-gallery-list" data-idx="<?php echo esc_attr($idx); ?>">
							<?php foreach ($raw_ids as $g_id) :
								$img_src = is_numeric($g_id) ? wp_get_attachment_image_url((int)$g_id, 'thumbnail') : $g_id;
								if (!$img_src) continue;
							?>
								<li class="sd-normal-gallery-item" data-id="<?php echo esc_attr($g_id); ?>">
									<img src="<?php echo esc_url($img_src); ?>" alt="Gallery item" draggable="false" style="pointer-events:none;">
									<button type="button" class="sd-gallery-remove-btn" title="<?php esc_attr_e('Remove image', 'cutlist-catalogue'); ?>">&times;</button>
								</li>
							<?php endforeach; ?>
						</ul>

						<input type="hidden" name="sd_colors[<?php echo esc_attr($idx); ?>][gallery_ids]" class="sd-gallery-ids-input" value="<?php echo esc_attr(implode(',', $raw_ids)); ?>">

						<button type="button" class="button sd-btn-add-gallery">
							<?php esc_html_e('Add images', 'cutlist-catalogue'); ?>
						</button>
					</div>

					<!-- Tab Footer -->
					<div class="sd-tab-footer-row">
						<div></div>
						<button type="button" class="sd-btn-delete-tab-pane">
							<?php esc_html_e('Delete This Colour Finish', 'cutlist-catalogue'); ?>
						</button>
					</div>

				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<script>
	(function ($) {
		var currencySym = '<?php echo esc_js($currency_sym); ?>';

		// Initialize jQuery UI Sortable for smooth drag-and-drop gallery reordering
		function initSortable() {
			if (typeof $.fn.sortable !== 'undefined') {
				$('.sd-normal-gallery-list').each(function () {
					var $list = $(this);
					if ($list.hasClass('ui-sortable')) {
						$list.sortable('destroy');
					}
					$list.sortable({
						items: 'li.sd-normal-gallery-item',
						cursor: 'move',
						scrollSensitivity: 40,
						forcePlaceholderSize: true,
						forceHelperSize: true,
						tolerance: 'pointer',
						opacity: 0.75,
						placeholder: 'sd-gallery-sortable-placeholder',
						start: function (e, ui) {
							ui.placeholder.height(ui.item.height());
							ui.placeholder.width(ui.item.width());
						},
						stop: function () {
							syncGalleryInput($(this));
						},
						update: function () {
							syncGalleryInput($(this));
						}
					});
				});
			}
		}

		$(document).ready(function () {
			initSortable();
		});

		// Switch Tab
		$(document).on('click', '.sd-tab-nav-btn', function (e) {
			if ($(e.target).hasClass('sd-tab-close-btn')) return;
			e.preventDefault();
			var targetPaneId = $(this).data('target');
			
			$('.sd-tab-nav-btn').removeClass('active');
			$('.sd-tab-pane').removeClass('active');

			$(this).addClass('active');
			$('#' + targetPaneId).addClass('active');
			initSortable();
		});

		// Live update tab label when colour name changes
		$(document).on('input', '.sd-color-name-input', function () {
			var $pane = $(this).closest('.sd-tab-pane');
			var paneId = $pane.attr('id');
			var val = $(this).val().trim() || '<?php echo esc_js(__('Colour Finish', 'cutlist-catalogue')); ?>';
			var $btn = $('.sd-tab-nav-btn[data-target="' + paneId + '"]');
			$btn.find('.sd-tab-label').text(val);
		});

		// Sync color picker with text hex input and update tab dot
		$(document).on('input', '.sd-color-picker-input', function () {
			var val = $(this).val();
			var $pane = $(this).closest('.sd-tab-pane');
			var paneId = $pane.attr('id');
			$(this).siblings('.sd-hex-text-input').val(val);
			$('.sd-tab-nav-btn[data-target="' + paneId + '"] .sd-tab-dot').css('background', val);
		});

		$(document).on('input', '.sd-hex-text-input', function () {
			var val = $(this).val().trim();
			var $pane = $(this).closest('.sd-tab-pane');
			var paneId = $pane.attr('id');
			if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
				$(this).siblings('.sd-color-picker-input').val(val);
				$('.sd-tab-nav-btn[data-target="' + paneId + '"] .sd-tab-dot').css('background', val);
			}
		});

		// Helper to sync hidden gallery IDs input
		function syncGalleryInput($list) {
			var ids = [];
			$list.find('.sd-normal-gallery-item').each(function () {
				var id = $(this).data('id');
				if (id) ids.push(id);
			});
			var $pane = $list.closest('.sd-tab-pane');
			$pane.find('.sd-gallery-ids-input').val(ids.join(','));
		}

		// Remove Image from Gallery
		$(document).on('click', '.sd-gallery-remove-btn', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.sd-normal-gallery-item');
			var $list = $item.closest('.sd-normal-gallery-list');
			$item.remove();
			syncGalleryInput($list);
		});

		// Add Images to Gallery via wp.media (Multiple Select)
		$(document).on('click', '.sd-btn-add-gallery', function (e) {
			e.preventDefault();
			var $pane = $(this).closest('.sd-tab-pane');
			var $list = $pane.find('.sd-normal-gallery-list');
			var colName = $pane.find('.sd-color-name-input').val() || 'Colour';

			var mediaFrame = wp.media({
				title: '<?php echo esc_js(__('Select gallery images for ', 'cutlist-catalogue')); ?>' + colName,
				button: { text: '<?php echo esc_js(__('Add to gallery', 'cutlist-catalogue')); ?>' },
				multiple: true
			});

			mediaFrame.on('select', function () {
				var selection = mediaFrame.state().get('selection').toJSON();

				selection.forEach(function (att) {
					var thumbUrl = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
					var itemHtml = '<li class="sd-normal-gallery-item" data-id="' + att.id + '">' +
						'<img src="' + thumbUrl + '" alt="Gallery item" draggable="false" style="pointer-events:none;">' +
						'<button type="button" class="sd-gallery-remove-btn" title="<?php echo esc_js(__('Remove image', 'cutlist-catalogue')); ?>">&times;</button>' +
						'</li>';
					$list.append(itemHtml);
				});

				syncGalleryInput($list);
				initSortable();
			});

			mediaFrame.open();
		});

		// Delete Tab / Colour
		function deleteTab(paneId) {
			if ($('.sd-tab-pane').length <= 1) {
				alert('<?php echo esc_js(__('At least one colour finish must remain.', 'cutlist-catalogue')); ?>');
				return;
			}
			if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this colour finish and its gallery?', 'cutlist-catalogue')); ?>')) {
				return;
			}
			var $btn = $('.sd-tab-nav-btn[data-target="' + paneId + '"]');
			var $pane = $('#' + paneId);
			var wasActive = $btn.hasClass('active');

			$btn.remove();
			$pane.remove();

			if (wasActive) {
				var $firstBtn = $('.sd-tab-nav-btn').first();
				var firstTarget = $firstBtn.data('target');
				$firstBtn.addClass('active');
				$('#' + firstTarget).addClass('active');
			}
		}

		$(document).on('click', '.sd-tab-close-btn', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var paneId = $(this).closest('.sd-tab-nav-btn').data('target');
			deleteTab(paneId);
		});

		$(document).on('click', '.sd-btn-delete-tab-pane', function (e) {
			e.preventDefault();
			var paneId = $(this).closest('.sd-tab-pane').attr('id');
			deleteTab(paneId);
		});

		// Add New Colour Tab
		$('#sdAddColorTabBtn').on('click', function (e) {
			e.preventDefault();
			var newIdx = new Date().getTime();
			var paneId = 'sd-pane-' + newIdx;
			var defaultName = '<?php echo esc_js(__('New Finish', 'cutlist-catalogue')); ?>';
			var defaultHex = '#c8c8c8';
			var defaultPrice = '74.00';

			// 1. Create Nav Button
			var btnHtml = '<button type="button" class="sd-tab-nav-btn" data-target="' + paneId + '">' +
				'<span class="sd-tab-dot" style="background:' + defaultHex + ';"></span>' +
				'<span class="sd-tab-label">' + defaultName + '</span>' +
				'<span class="sd-tab-close-btn" title="<?php echo esc_js(__('Delete Colour', 'cutlist-catalogue')); ?>">&times;</span>' +
				'</button>';
			$(this).before(btnHtml);

			// 2. Create Pane
			var paneHtml = '<div class="sd-tab-pane" id="' + paneId + '" data-idx="' + newIdx + '">' +
				'<div class="sd-pane-fields-grid">' +
				'<div class="sd-field-item">' +
				'<label><?php echo esc_js(__('Colour Finish Name', 'cutlist-catalogue')); ?> <span style="color:#ef4444;">*</span></label>' +
				'<input type="text" name="sd_colors[' + newIdx + '][name]" value="' + defaultName + '" class="regular-text sd-color-name-input" placeholder="e.g. Bronze" style="width:100%;" required>' +
				'</div>' +
				'<div class="sd-field-item">' +
				'<label><?php echo esc_js(__('Colour Swatch / Hex', 'cutlist-catalogue')); ?></label>' +
				'<div class="sd-picker-group">' +
				'<input type="color" class="sd-color-picker-input" value="' + defaultHex + '">' +
				'<input type="text" name="sd_colors[' + newIdx + '][hex]" class="sd-hex-text-input" value="' + defaultHex + '" placeholder="#c8c8c8" style="width:100px;">' +
				'</div>' +
				'</div>' +
				'<div class="sd-field-item">' +
				'<label><?php echo esc_js(__('Base Price per Door (£)', 'cutlist-catalogue')); ?> <span style="color:#ef4444;">*</span></label>' +
				'<div class="sd-price-group">' +
				'<span class="sd-price-sym">' + currencySym + '</span>' +
				'<input type="number" step="0.01" name="sd_colors[' + newIdx + '][base_price]" value="' + defaultPrice + '" style="width:110px;" required>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'<div class="sd-normal-gallery-section">' +
				'<label><?php echo esc_js(__('Gallery', 'cutlist-catalogue')); ?></label>' +
				'<p class="description" style="margin: 2px 0 8px;"><?php echo esc_js(__('The 1st image is used as the main swatch and primary render for this colour finish. Drag and drop images to re-order.', 'cutlist-catalogue')); ?></p>' +
				'<ul class="sd-normal-gallery-list" data-idx="' + newIdx + '"></ul>' +
				'<input type="hidden" name="sd_colors[' + newIdx + '][gallery_ids]" class="sd-gallery-ids-input" value="">' +
				'<button type="button" class="button sd-btn-add-gallery"><?php echo esc_js(__('Add images', 'cutlist-catalogue')); ?></button>' +
				'</div>' +
				'<div class="sd-tab-footer-row">' +
				'<div></div>' +
				'<button type="button" class="sd-btn-delete-tab-pane"><?php echo esc_js(__('Delete This Colour Finish', 'cutlist-catalogue')); ?></button>' +
				'</div>' +
				'</div>';
			$('#sdTabPanesWrap').append(paneHtml);

			// Activate newly added tab
			$('.sd-tab-nav-btn').removeClass('active');
			$('.sd-tab-pane').removeClass('active');
			$('.sd-tab-nav-btn[data-target="' + paneId + '"]').addClass('active');
			$('#' + paneId).addClass('active');
			initSortable();
		});
	})(jQuery);
	</script>
	<?php
}

add_action('save_post_sd_frame_style', function ($post_id) {
	if (!isset($_POST['cutlist_frame_colors_tabbed_nonce']) || !wp_verify_nonce($_POST['cutlist_frame_colors_tabbed_nonce'], 'cutlist_save_frame_colors_tabbed')) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['sd_colors']) && is_array($_POST['sd_colors'])) {
		$clean_colors = [];
		$first_img_id = 0;

		foreach ($_POST['sd_colors'] as $c) {
			$name = sanitize_text_field($c['name'] ?? '');
			if (empty($name)) continue;

			$hex = sanitize_text_field($c['hex'] ?? '#c8c8c8');
			$base_price = (float) ($c['base_price'] ?? 74.00);
			
			$raw_ids_str = sanitize_text_field($c['gallery_ids'] ?? '');
			$raw_ids = array_values(array_filter(array_map('trim', explode(',', $raw_ids_str))));
			$gallery_urls = [];

			foreach ($raw_ids as $gid) {
				if (is_numeric($gid) && (int)$gid > 0) {
					$url = wp_get_attachment_image_url((int)$gid, 'full');
					if ($url) {
						$gallery_urls[] = $url;
						if (!$first_img_id) {
							$first_img_id = (int)$gid;
						}
					}
				} elseif (!empty($gid)) {
					$gallery_urls[] = $gid;
				}
			}

			$featured_color_img = !empty($gallery_urls) ? $gallery_urls[0] : '';

			$clean_colors[] = [
				'color_name'   => $name,
				'name'         => $name,
				'color_hex'    => $hex,
				'hex'          => $hex,
				'base_price'   => $base_price,
				'surcharge'    => 0.00,
				'gallery_ids'  => $raw_ids,
				'gallery'      => $gallery_urls,
				'sample_image' => $featured_color_img,
				'image'        => $featured_color_img,
			];
		}

		update_post_meta($post_id, CUTLIST_FRAME_COLORS_META, $clean_colors);
		update_post_meta($post_id, 'colors', $clean_colors);

		// If post has no featured image, set it to the first image of the 1st colour
		if (!has_post_thumbnail($post_id) && $first_img_id > 0) {
			set_post_thumbnail($post_id, $first_img_id);
		}
	}
});
