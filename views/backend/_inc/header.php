<div class="wrap wptm-wrap">
	<div class="wptm-ad wptm-ad-header"><?php echo $advertisements['banner']; ?></div>

	<h2 class="nav-tab-wrapper">
		<a class="nav-tab <?php if('tutorials' === $page) { echo 'nav-tab-active'; } ?>" href="<?php esc_attr_e(esc_url($link_tutorials)); ?>"><?php _e('View Tutorials'); ?></a>
		<a class="nav-tab <?php if('updates' === $page) { echo 'nav-tab-active'; } ?>" href="<?php esc_attr_e(esc_url($link_updates)); ?>"><?php _e('Updates'); ?></a>
		<a class="nav-tab <?php if('about' === $page) { echo 'nav-tab-active'; } ?>" href="<?php esc_attr_e(esc_url($link_about)); ?>"><?php _e('About'); ?></a>

		<?php if($manage_options) { ?>
		<a class="nav-tab <?php if('settings' === $page) { echo 'nav-tab-active'; } ?>" href="<?php esc_attr_e(esc_url($link_settings)); ?>"><?php _e('Settings'); ?></a>
		<?php } ?>

		<?php if($authenticated && 'pro' !== $access_level) { ?>
		<a class="nav-tab <?php if('upgrade' === $page) { echo 'nav-tab-active'; } ?>" href="<?php esc_attr_e(esc_url($link_upgrade)); ?>"><?php _e('Upgrade'); ?></a>
		<?php } ?>

		<?php do_action('wptm_page_header_tabs'); ?>
	</h2>

	<?php settings_errors(); ?>

	<div class="wptm-wrap-inner">
		<div class="wptm-sidebar">
			<div class="wptm-sidebar-inner">
				<?php echo $advertisements['sidebar']; ?>
			</div>
		</div>

		<div class="wptm-main">