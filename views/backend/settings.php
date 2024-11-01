<?php self::_page_header(); ?>

<form action="options.php" method="post">
	<h3><?php _e('Authentication'); ?></h3>

	<?php if('invalid' === $access_level) { ?>
	<p>
		<?php printf(__('Please sign up for an account on <a href="%s" target="_blank">WPTrainMe</a>. Once you do so, enter your username and password below to access tutorials.'), self::URL__MEMBERS); ?>
	</p>
	<?php } ?>

	<table class="form-table" style="clear: none;">
		<tbody>
			<tr valign="top">
				<th scope="row"><label for="<?php self::_settings_id('username'); ?>"><?php _e('Username'); ?></label></th>
				<td>
					<input type="text"
						class="code regular-text"
						id="<?php self::_settings_id('username'); ?>"
						name="<?php self::_settings_name('username'); ?>"
						value="<?php esc_attr_e($settings['username']); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="<?php self::_settings_id('password'); ?>"><?php _e('Password'); ?></label></th>
				<td>
					<input type="text"
						class="code regular-text"
						id="<?php self::_settings_id('password'); ?>"
						name="<?php self::_settings_name('password'); ?>"
						value="<?php esc_attr_e($settings['password']); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="<?php self::_settings_id('affiliate_id'); ?>"><?php _e('Referrer ID (optional)'); ?></label></th>
				<td>
					<input type="text"
						class="code regular-text"
						id="<?php self::_settings_id('affiliate_id'); ?>"
						name="<?php self::_settings_name('affiliate_id'); ?>"
						value="<?php esc_attr_e($settings['affiliate_id']); ?>" />
				</td>
			</tr>
		</tbody>
	</table>

	<p class="submit">
		<?php settings_fields(self::SETTINGS_NAME); ?>
		<input type="submit" class="button button-primary" value="<?php _e('Save Changes'); ?>" />
	</p>
</form>

<?php self::_page_footer(); ?>