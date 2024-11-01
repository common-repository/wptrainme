<?php self::_page_header(); ?>

<p>
	<?php printf(__('You currently have access to %s tutorials as a %s level user. Tutorials you do not have access to are displayed in grey below. View all tutorials below:'), $accessible_number, strtoupper($access_level)); ?>
</p>

<?php echo wp_train_me_walk_categories_tree($categories); ?>

<?php self::_page_footer(); ?>