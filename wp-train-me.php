<?php
/*
 Plugin Name: WPTrainMe
 Plugin URI: http://wptrainme.com/
 Description: WPTrainMe provides instant access to hundreds of detailed WordPress step-by-step tutorials from your WordPress Dashboard. For user documentation, see our <a title="WPTrainMe Plugin User Guide" href="http://wptrainme.com/docs" target="_blank"><strong>WPTrainMe Plugin User Guide</strong></a>.
 Version: 1.0.2
 Author: Martin Aranovitch
 Author URI: https://wpcompendium.org/
 */

if(!class_exists('WP_Train_Me')) {
	class WP_Train_Me {
		/// Constants

		//// Plugin Version
		const VERSION = '1.0.1';

		//// Keys
		const SETTINGS_NAME = '_wptm_settings';
		const TUTORIAL_DATA = '_wptm_tutorial_data';

		//// Slugs
		const MENU_SLUG = 'wptm';
		const MENU_SLUG__ABOUT = 'wptm-about';
		const MENU_SLUG__SETTINGS = 'wptm-settings';
		const MENU_SLUG__UPDATES = 'wptm-updates';
		const MENU_SLUG__UPGRADE = 'wptm-upgrade';

		//// URLs
		const URL__MEMBERS = 'http://wptrain.me/members/';

		//// Data
		private static $cached_data = null;

		//// Defaults
		private static $default_settings = null;

		public static function init() {
			self::add_actions();
			self::add_filters();

			if(!defined('WPTM_API')) {
				define('WPTM_API', 'http://wptrain.me/members/tutorials.json');
			}

			if(!defined('WPTM_API_CACHE')) {
				define('WPTM_API_CACHE', 6 * HOUR_IN_SECONDS); // 6 hours
			}

			if(!defined('WPTM_DEBUG')) {
				define('WPTM_DEBUG', false);
			}
		}

		private static function add_actions() {
			// Common actions
			add_action('init', array(__CLASS__, 'register_resources'), 0);

			if(is_admin()) {
				// Administrative only actions
				add_action('add_meta_boxes', array(__CLASS__, 'add_meta_box'));
				add_action('admin_enqueue_scripts', array(__CLASS__, 'load'));
				add_action('admin_init', array(__CLASS__, 'register_settings'));
				add_action('admin_menu', array(__CLASS__, 'add_administrative_interface_items'));
				add_action('current_screen', array(__CLASS__, 'add_help_tab'));
				add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_meta_box'));
			} else {
				// Frontend only actions
			}
		}

		private static function add_filters() {
			// Common filters

			if(is_admin()) {
				// Administrative only filters
				add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'add_settings_link'));
			} else {
				// Frontend only filters
			}
		}

		/// Callbacks

		//// Generic operation

		public static function register_resources() {
			wp_register_script('wptm-stickyfloat', plugins_url('resources/backend/vendor/stickyfloat.js', __FILE__), array('jquery'), '7.5', true);
			wp_register_script('wptm-backend', plugins_url('resources/backend/wptm.js', __FILE__), array('jquery', 'wptm-stickyfloat'), self::VERSION, true);
			wp_register_style('wptm-backend', plugins_url('resources/backend/wptm.css', __FILE__), array(), self::VERSION);
		}

		//// Administrative UI

		public static function add_administrative_interface_items() {
			$suffixes = array();

			add_menu_page(__('WPTrainMe'), __('WPTrainMe'), 'edit_posts', self::MENU_SLUG);

			$access_level = self::_get_access_level();
			$authenticated = self::_is_authenticated();

			$suffixes[] = add_submenu_page(self::MENU_SLUG, __('WPTrainMe - View Tutorials'), __('View Tutorials'), 'edit_posts', self::MENU_SLUG, array(__CLASS__, 'display_page__tutorials'));
			$suffixes[] = add_submenu_page(self::MENU_SLUG, __('WPTrainMe - Updates'), __('Updates'), 'edit_posts', self::MENU_SLUG__UPDATES, array(__CLASS__, 'display_page__updates'));
			$suffixes[] = add_submenu_page(self::MENU_SLUG, __('WPTrainMe - About'), __('About'), 'edit_posts', self::MENU_SLUG__ABOUT, array(__CLASS__, 'display_page__about'));
			$suffixes[] = add_submenu_page(self::MENU_SLUG, __('WPTrainMe - Settings'), __('Settings'), 'manage_options', self::MENU_SLUG__SETTINGS, array(__CLASS__, 'display_page__settings'));

			if($authenticated && 'pro' !== $level) {
				$suffixes[] = add_submenu_page(self::MENU_SLUG, __('WPTrainMe - Upgrade'), __('Upgrade'), 'manage_options', self::MENU_SLUG__UPGRADE, array(__CLASS__, 'display_page__upgrade'));
			}
		}

		public static function add_dashboard_meta_box() {
			wp_add_dashboard_widget('wp-train-me-dashboard', __('WPTrainMe'), array(__CLASS__, 'display_meta_box__dashboard'));
		}

		private static $contextual_tutorials = array();

		public static function add_help_tab($screen) {
			$contexts = self::_get_tutorials_contexts();

			$context = false;
			switch($screen->base) {
				case 'comment':
				    $context = 'comment_editing';
				    break;
				case 'edit-comments':
				    $context = 'comment_management';
				    break;
				case 'post':
				    $context = 'content_type_editing';
				    break;
				case 'edit':
				    $context = 'content_type_management';
				    break;
				case 'dashboard':
				    $context = 'dashboard';
				    break;
				case 'export':
				    $context = 'export';
				    break;
				case 'import':
				    $context = 'import';
				    break;
				case 'nav-menus':
				    $context = 'menus';
				    break;
				case 'plugin-editor':
				case 'plugin-install':
				case 'plugins':
				    $context = 'plugins';
				    break;
				case 'options-discussion':
				    $context = 'settings_discussion';
				    break;
				case 'options-general':
				    $context = 'settings_general';
				    break;
				case 'options-media':
				    $context = 'settings_media';
				    break;
				case 'options-permalink':
				    $context = 'settings_permalinks';
				    break;
				case 'options-reading':
				    $context = 'settings_reading';
				    break;
				case 'options-writing':
				    $context = 'settings_writing';
				    break;
				case 'edit-tags':
				    $context = 'taxonomy_management';
				    break;
				case 'theme-editor':
				case 'theme-install':
				case 'themes':
				    $context = 'themes';
				    break;
				case 'update-core':
				    $context = 'updates';
				    break;
				case 'profile':
				case 'user':
				case 'users':
				    $context = 'users';
				    break;
				case 'widgets':
				    $context = 'widgets';
				    break;
				default:
					$context = false;
					break;
			}

			$ids = isset($contexts[$context]) && isset($contexts[$context]['tutorials']) && is_array($contexts[$context]['tutorials']) ? $contexts[$context]['tutorials'] : array();
			$tutorials = empty($ids) ? array() : self::_get_tutorials($ids, array('suggested' => true));

			if(!empty($tutorials)) {
				self::$contextual_tutorials = $tutorials;

				$screen->add_help_tab(array(
					'id' => 'wp-train-me',
					'title' => __('WPTrainMe'),
					'callback' => array(__CLASS__, 'add_help_tab_content'),
				));
			}
		}

		public static function add_help_tab_content($screen, $tab) {
			echo '<p>' . __('The following are suggested tutorials for this page:') . '</p>';
			echo wp_train_me_walk_tutorials_tree(self::$contextual_tutorials);
		}

		public static function add_meta_box($post_type) {
			if(in_array($post_type, array('page', 'post'))) {
				add_meta_box('wp-train-me-content-editing', __('WPTrainMe'), array(__CLASS__, 'display_meta_box__content_editing'), $post_type, 'side');
			}
		}

		public static function add_settings_link($actions) {
			$actions = array_merge(array_slice($actions, 0, 1), array('settings' => sprintf('<a href="%s" title="%s">%s</a>', self::_get_link__settings(), __('Configure WPTrainMe'), __('Settings'))), array_slice($actions, 1));

			return $actions;
		}

		public static function display_meta_box__dashboard() {
			$conditions = array('suggested' => true);
			$contexts = self::_get_data('tutorials_contexts');
			$ids = isset($contexts['dashboard']) && is_array($contexts['dashboard']) && isset($contexts['dashboard']['tutorials']) && is_array($contexts['dashboard']['tutorials']) ? $contexts['dashboard']['tutorials'] : array();

			$tutorials = self::_get_tutorials($ids, $conditions);

			include('views/meta-boxes/dashboard.php');
		}

		public static function display_meta_box__content_editing() {
			$conditions = array('suggested' => true);
			$contexts = self::_get_data('tutorials_contexts');
			$ids = isset($contexts['content_type_editing']) && is_array($contexts['content_type_editing']) && isset($contexts['content_type_editing']['tutorials']) && is_array($contexts['content_type_editing']['tutorials']) ? $contexts['content_type_editing']['tutorials'] : array();

			$tutorials = self::_get_tutorials($ids, $conditions);

			include('views/meta-boxes/content-editing.php');
		}

		public static function display_page__about() {
			$about_content = self::_get_data('about');

			include('views/backend/about.php');
		}

		public static function display_page__settings() {
			$access_level = self::_get_access_level();
			$settings = self::_get_settings();

			include('views/backend/settings.php');
		}

		public static function display_page__tutorials() {
			if(self::_is_authenticated()) {
				$access_level = self::_get_access_level();

				$accessible = self::_get_tutorials(null, array('accessible' => true));
				$accessible_number = number_format_i18n(count($accessible));
				$categories = self::_get_tutorials_categories();

				include('views/backend/tutorials-authenticated.php');
			} else {
				$settings_link = self::_get_link__settings();

				include('views/backend/tutorials.php');
			}
		}

		public static function display_page__updates() {
			$urls = self::_get_data('urls');
			$urls_login = $urls['login'];

			$updates = self::_get_data('updates');

			$username = self::_get_settings('username');
			$password = self::_get_settings('password');

			foreach($updates as $key => $update) {
				$updates[$key]['permalink'] = add_query_arg(array('redirect' => urlencode($update['permalink']), 'wptm_username' => $username, 'wptm_password' => $password), $urls_login);
			}

			include('views/backend/updates.php');
		}

		public static function load() {
			wp_enqueue_script('wptm-backend');
			wp_enqueue_style('wptm-backend');
		}

		private static function _page_header() {
			$authenticated = self::_is_authenticated();
			$access_level = self::_get_access_level();
			$page = self::_get_page();

			$manage_options = current_user_can('manage_options');

			$link_about = self::_get_link__about();
			$link_settings = self::_get_link__settings();
			$link_tutorials = self::_get_link__tutorials();
			$link_updates = self::_get_link__updates();
			$link_upgrade = self::_get_link__upgrade();

			$advertisements = self::_get_advertisements();

			include('views/backend/_inc/header.php');
		}

		private static function _page_footer() {
			$advertisements = self::_get_advertisements();

			include('views/backend/_inc/footer.php');
		}

		//// Settings related

		public static function register_settings() {
			register_setting(self::SETTINGS_NAME, self::SETTINGS_NAME, array(__CLASS__, 'sanitize_settings'));
		}

		public static function sanitize_settings($settings) {
			$defaults = self::_get_settings_default();
			$previous = self::_get_settings();

			delete_option(self::TUTORIAL_DATA);

			return shortcode_atts($defaults, $settings);
		}

		private static function _get_page() {
			if(isset($_GET['page'])) {
				switch($_GET['page']) {
					case self::MENU_SLUG__ABOUT:
						return 'about';
					case self::MENU_SLUG__SETTINGS:
						return 'settings';
					case self::MENU_SLUG:
					case self::MENU_SLUG:
						return 'tutorials';
					case self::MENU_SLUG__UPDATES:
						return 'updates';
					case self::MENU_SLUG__UPGRADE:
						return 'upgrade';
				}
			}

			return false;
		}

		private static function _get_settings($settings_key = null) {
			$defaults = self::_get_settings_default();

			$settings = get_option(self::SETTINGS_NAME, $defaults);
			$settings = shortcode_atts($defaults, $settings);

			return is_null($settings_key) ? $settings : (isset($settings[$settings_key]) ? $settings[$settings_key] : false);
		}

		private static function _get_settings_default() {
			if(is_null(self::$default_settings)) {
				self::$default_settings = array(
					'username' => '',
					'password' => '',
					'affiliate_id' => '',
				);
			}

			return self::$default_settings;
		}

		private static function _settings_id($key, $echo = true) {
			$settings_name = self::SETTINGS_NAME;

			$id = "{$settings_name}-{$key}";
			if($echo) {
				echo $id;
			}

			return $id;
		}

		private static function _settings_name($key, $echo = true) {
			$settings_name = self::SETTINGS_NAME;

			$name = "{$settings_name}[{$key}]";
			if($echo) {
				echo $name;
			}

			return $name;
		}

		/// Authentication / Data

		private static function _is_authenticated() {
			$access_level = self::_get_access_level();

			return in_array($access_level, array('free', 'basic', 'business', 'pro'));
		}

		private static function _get_data($data_key = null) {
			if(is_null(self::$cached_data)) {
				$stored = get_option(self::TUTORIAL_DATA, false);

				$username = self::_get_settings('username');
				$password = self::_get_settings('password');
				$credentials = md5($username . $password);

				if(false === $stored || !is_array($stored) || !isset($stored['credentials']) || $credentials !== $stored['credentials'] || !isset($stored['data']) || !is_array($stored['data']) || self::_should_refresh($stored)) {
					$url = add_query_arg(array('wptm_username' => self::_get_settings('username'), 'wptm_password' => self::_get_settings('password')), WPTM_API);
					$response = wp_remote_post($url);

					if(is_wp_error($response)) {
						$data = false;
					} else {
						$data = json_decode(wp_remote_retrieve_body($response), true);
					}

					if(false !== $data) {
						$stored = array(
							'credentials' => $credentials,
							'data' => $data,
							'timestamp' => time(),
						);

						update_option(self::TUTORIAL_DATA, $stored);
					}
				} else {
					$data = $stored['data'];
				}

				self::$cached_data = $data;
			}

			return is_null($data_key) ? self::$cached_data : (isset(self::$cached_data[$data_key]) ? self::$cached_data[$data_key] : false);
		}

		private static function _get_access_level() {
			$data = self::_get_data();

			return (is_array($data) && isset($data['access_level'])) ? $data['access_level'] : 'none';
		}

		private static function _get_advertisements($advertisement_key = null) {
			$advertisements = self::_get_data('advertisements');

			return is_array($advertisements) ? (is_null($advertisement_key) ? $advertisements : (isset($advertisements[$advertisement_key]) ? $advertisements[$advertisement_key] : false)) : false;
		}

		public static function _get_tutorials($ids = null, $conditions = array()) {
			$tutorials = self::_get_data('tutorials');
			$tutorials = is_array($tutorials) ? $tutorials : array();

			$ids = is_array($ids) ? $ids : array_keys($tutorials);
			$tutorials = array_intersect_key($tutorials, array_flip($ids));
			$tutorials = json_decode(json_encode(array_values($tutorials)), false);

			if(!empty($conditions)) {
				$tutorials = wp_filter_object_list($tutorials, $conditions);
			}

			$have_children = array();
			foreach($tutorials as $tutorial) {
				if(!in_array($tutorial->parent, $have_children)) {
					$have_children[] = $tutorial->parent;
				}
			}

			$need_rooting = array_diff($have_children, $ids);
			foreach($tutorials as $tutorial) {
				if(in_array($tutorial->parent, $need_rooting)) {
					$tutorial->parent = 0;
				}
			}

			$username = self::_get_settings('username');
			$password = self::_get_settings('password');
			$urls_login = self::_get_urls('login');

			foreach($tutorials as $tutorial) {
				$tutorial->url = add_query_arg(array('redirect' => urlencode($tutorial->url), 'wptm_username' => $username, 'wptm_password' => $password), $urls_login);
			}

			return $tutorials;
		}

		private static function _get_tutorials_categories() {
			$tutorials_categories = self::_get_data('tutorials_categories');
			$tutorials_categories = is_array($tutorials_categories) ? $tutorials_categories : array();

			return json_decode(json_encode(array_values($tutorials_categories)), false);
		}

		private static function _get_tutorials_contexts() {
			$tutorials_contexts = self::_get_data('tutorials_contexts');

			return is_array($tutorials_contexts) ? $tutorials_contexts : array();
		}

		private static function _get_urls($url_key = null) {
			$urls = self::_get_data('urls');

			return is_array($urls) ? (is_null($url_key) ? $urls : (isset($urls[$url_key]) ? $urls[$url_key] : false)) : false;
		}

		private static function _should_refresh($stored) {
			return !isset($stored['timestamp']) || (time() - WPTM_API_CACHE > $stored['timestamp']);
		}

		/// Links

		private static function _get_link__about($query_args = array()) {
			$query_args = array_merge(array('page' => self::MENU_SLUG__ABOUT), $query_args);

			return add_query_arg($query_args, admin_url('admin.php'));
		}

		private static function _get_link__settings($query_args = array()) {
			$query_args = array_merge(array('page' => self::MENU_SLUG__SETTINGS), $query_args);

			return add_query_arg($query_args, admin_url('admin.php'));
		}

		private static function _get_link__tutorials($query_args = array()) {
			$query_args = array_merge(array('page' => self::MENU_SLUG), $query_args);

			return add_query_arg($query_args, admin_url('admin.php'));
		}

		private static function _get_link__updates($query_args = array()) {
			$query_args = array_merge(array('page' => self::MENU_SLUG__UPDATES), $query_args);

			return add_query_arg($query_args, admin_url('admin.php'));
		}

		private static function _get_link__upgrade($query_args = array()) {
			$url = self::_get_urls('signup');

			$affiliate_id = self::_get_settings('affiliate_id');
			$affiliate_url = self::_get_urls('affiliate');

			if(!empty($affiliate_id) && !empty($affiliate_url)) {
				$url = str_replace('[AFFID]', $affiliate_id, $affiliate_url);
			}

			return $url;
		}
	}

	WP_Train_Me::init();

	class WP_Train_Me_Walker_Categories extends Walker {
		var $db_fields = array('id' => 'id', 'parent' => 'parent');

		function start_lvl(&$output, $depth = 0, $args = array()) {
			$output .= '<div class="wptm-category wptm-category-child">';
		}

		function end_lvl(&$output, $depth = 0, $args = array()) {
			$output .= '</div>';
		}

		function start_el(&$output, $category, $depth = 0, $args = array(), $current_page = 0) {
			$output .= sprintf('<div class="wptm-category"><h3 class="wptm-category-name"><a class="wptm-category-name-link" href="#">%s</a></h3>', $category->name);

			if(!empty($category->tutorials)) {
				$tutorials = WP_Train_Me::_get_tutorials($category->tutorials);

				$output .= sprintf('<ul class="wptm-tutorials">%s</ul>', wp_train_me_walk_tutorials_tree($tutorials));
			}
		}

		function end_el(&$output, $category, $depth = 0, $args = array()) {
			$output .= '</div>';
		}
	}

	class WP_Train_Me_Walker_Tutorials extends Walker {
		var $db_fields = array('id' => 'id', 'parent' => 'parent');

		function start_lvl(&$output, $depth = 0, $args = array()) {
			$output .= '<ul class="wptm-tutorials wptm-tutorials-children">';
		}

		function end_lvl(&$output, $depth = 0, $args = array()) {
			$output .= '</ul>';
		}

		function start_el(&$output, $tutorial, $depth = 0, $args = array(), $current_page = 0) {
			$output .= sprintf('<li class="wptm-tutorial"><a class="wptm-tutorial-link %s" href="%s" target="_blank" data-parent="%d">%s</a>', ($tutorial->accessible ? 'wptm-tutorial-link-accessible' : 'wptm-tutorial-link-inaccessible'), $tutorial->url, $tutorial->parent, $tutorial->title, $tutorial->id, $tutorial->parent);
		}

		function end_el(&$output, $tutorial, $depth = 0, $args = array()) {
			$output .= '</li>';
		}
	}

	function wp_train_me_walk_categories_tree($categories) {
		$walker = new WP_Train_Me_Walker_Categories;

		return $walker->walk($categories, 0);
	}

	function wp_train_me_walk_tutorials_tree($tutorials) {
		$walker = new WP_Train_Me_Walker_Tutorials;

		return $walker->walk($tutorials, 0);
	}
}
