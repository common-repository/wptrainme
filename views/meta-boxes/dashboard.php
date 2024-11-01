<p><?php _e('Here are some suggested tutorials to get you started with your WordPress site:'); ?></p>

<ul class="wptm-tutorials">
	<?php echo wp_train_me_walk_tutorials_tree($tutorials); ?>
</ul>