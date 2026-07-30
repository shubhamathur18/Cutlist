<?php
/**
 * Multi-image gallery for the Board edit screen, in the sidebar next to
 * Featured Image. ACF's own Gallery field type is PRO-only, so this is a
 * small self-contained replacement built on the core wp.media uploader.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('CUTLIST_BOARD_GALLERY_META', '_board_gallery_ids');

add_action('add_meta_boxes', function () {
	add_meta_box(
		'board_gallery',
		__('Gallery', 'cutlist-catalogue'),
		'cutlist_render_gallery_meta_box',
		'board',
		'side',
		'default'
	);
});

function cutlist_render_gallery_meta_box($post) {
	wp_nonce_field('cutlist_save_gallery', 'cutlist_gallery_nonce');
	$ids = get_post_meta($post->ID, CUTLIST_BOARD_GALLERY_META, true);
	$ids = is_array($ids) ? $ids : [];
	?>
	<p class="description"><?php esc_html_e('Decor/swatch images shown on the front end. The Featured image above is used as the main swatch.', 'cutlist-catalogue'); ?></p>
	<ul id="cutlist-gallery-list" style="display:flex;flex-wrap:wrap;gap:6px;padding:0;margin:0 0 10px;">
		<?php foreach ($ids as $id) : ?>
			<?php cutlist_render_gallery_thumb($id); ?>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" name="cutlist_gallery_ids" id="cutlist-gallery-ids" value="<?php echo esc_attr(implode(',', $ids)); ?>">
	<button type="button" class="button" id="cutlist-gallery-add"><?php esc_html_e('Add images', 'cutlist-catalogue'); ?></button>
	<script>
	(function ($) {
		function currentIds() {
			var raw = $('#cutlist-gallery-ids').val();
			return raw ? raw.split(',').filter(Boolean).map(Number) : [];
		}
		function setIds(ids) {
			$('#cutlist-gallery-ids').val(ids.join(','));
		}
		function addThumb(attachment) {
			var url = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
			var $li = $('<li>').attr('data-id', attachment.id).css({listStyle: 'none', position: 'relative'});
			$li.append($('<img>').attr('src', url).css({width: '60px', height: '60px', objectFit: 'cover', display: 'block'}));
			var $remove = $('<button type="button" class="button-link">&times;</button>').css({
				position: 'absolute', top: '-6px', right: '-6px', background: '#fff',
				border: '1px solid #ccc', borderRadius: '50%', width: '18px', height: '18px',
				lineHeight: '16px', padding: 0, cursor: 'pointer'
			});
			$remove.on('click', function () {
				var ids = currentIds().filter(function (id) { return id !== attachment.id; });
				setIds(ids);
				$li.remove();
			});
			$li.append($remove);
			$('#cutlist-gallery-list').append($li);
		}

		var frame;
		$('#cutlist-gallery-add').on('click', function (e) {
			e.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: '<?php echo esc_js(__('Select gallery images', 'cutlist-catalogue')); ?>',
				button: { text: '<?php echo esc_js(__('Add to gallery', 'cutlist-catalogue')); ?>' },
				multiple: true,
			});
			frame.on('select', function () {
				var selection = frame.state().get('selection');
				var ids = currentIds();
				selection.each(function (attachment) {
					attachment = attachment.toJSON();
					if (ids.indexOf(attachment.id) === -1) {
						ids.push(attachment.id);
						addThumb(attachment);
					}
				});
				setIds(ids);
			});
			frame.open();
		});
	})(jQuery);
	</script>
	<?php
}

function cutlist_render_gallery_thumb($id) {
	$url = wp_get_attachment_image_url($id, 'thumbnail');
	if (!$url) {
		return;
	}
	?>
	<li data-id="<?php echo esc_attr($id); ?>" style="list-style:none;position:relative;">
		<img src="<?php echo esc_url($url); ?>" style="width:60px;height:60px;object-fit:cover;display:block;">
	</li>
	<?php
}

add_action('admin_enqueue_scripts', function ($hook) {
	if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
		return;
	}
	if (get_current_screen()->post_type !== 'board') {
		return;
	}
	wp_enqueue_media();
});

add_action('save_post_board', function ($post_id) {
	if (!isset($_POST['cutlist_gallery_nonce']) || !wp_verify_nonce($_POST['cutlist_gallery_nonce'], 'cutlist_save_gallery')) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['cutlist_gallery_ids']) ? wp_unslash($_POST['cutlist_gallery_ids']) : '';
	$ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
	update_post_meta($post_id, CUTLIST_BOARD_GALLERY_META, $ids);
});
