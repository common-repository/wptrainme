<?php self::_page_header(); ?>

<div class="wptm-list">

<?php if($updates) { ?>

<?php foreach($updates as $update) { ?>

	<div class="wptm-list-item">
		<div class="wptm-list-item-title">
			<a href="<?php esc_attr_e(esc_url($update['permalink'])); ?>" target="_blank"><?php esc_html_e($update['title']); ?></a>
		</div>
		<div class="wptm-list-item-excerpt"><?php echo $update['excerpt']; ?></div>
	</div>

<?php } ?>

<?php } else { ?>

	<div class="wptm-list-item">
		<div class="wptm-list-item-title"><?php _e('Sorry, no updates were found.'); ?></div>
	</div>

<?php } ?>

</div>

<?php self::_page_footer(); ?>