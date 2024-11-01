<?php

/**
 * Core file responsible for initializing the Zamartz admin
 *
 * @link       https://zamartz.com
 * @since      2.1.3
 * @author     Zachary Martz <zam@zamartz.com>
 * 
 */
class Wp_Woo_Main_Zamartz_Admin
{

	/**
	 * Real path to zamartz folder for the active zamartz admin
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $zamartz_path    Path to zamartz folder.
	 */
	private $zamartz_path;

	/**
	 * Plugin specific data currently accessing Zamartz Admin
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $plugin_data    Plugin data.
	 */
	private $plugin_data;

	/**
	 * Full URL to zamartz folder for the active zamartz admin
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $zamartz_url    Url to zamartz folder.
	 */
	private $zamartz_url;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct($plugin_data)
	{
		/**
		 * Zamartz admin version
		 */
		if (!defined('WP_ZAMARTZ_ADMIN_VERSION')) {
			define('WP_ZAMARTZ_ADMIN_VERSION', '2.1.3');
		}
		$this->zamartz_path = dirname(__FILE__);
		$this->zamartz_url = plugins_url(basename(dirname(__FILE__, 3))) . '/admin/zamartz/';
		$this->plugin_data = $plugin_data;

		//Enqueue Zamartz common JS and CSS files
		add_action('admin_enqueue_scripts', array($this, 'zamartz_enqueue_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'zamartz_enqueue_styles'));

		//Include Core zamartz admin functionality
		require_once $this->zamartz_path . '/helper/core-zamartz-functions.php';

		//Include Trait class file for general paramters and methods
		if (!trait_exists('Zamartz_General')) {
			require_once $this->zamartz_path . '/helper/trait-zamartz-general.php';
		}

		//Include Trait class file for generating HTML templates
		if (!trait_exists('Zamartz_HTML_Template')) {
			require_once $this->zamartz_path . '/helper/trait-zamartz-html-template.php';
		}

		//Include Trait class file for API methods
		if (!trait_exists('Zamartz_API_Methods')) {
			require_once $this->zamartz_path . '/helper/trait-zamartz-api-methods.php';
		}

		//Include Trait class file for RSS methods
		if (!trait_exists('Zamartz_RSS_Methods')) {
			require_once $this->zamartz_path . '/helper/trait-zamartz-rss-methods.php';
		}

		//Set active plugin list currently accessing Zamartz admin
		set_zamartz_active_plugin_list($this->plugin_data['plugin_name']);

		//Initialize Zamartz admin menu
		add_action('admin_menu', array($this, 'init_menu'));

		//Initialize Zamartz network admin menu
		add_action('network_admin_menu', array($this, 'init_network_menu'));

		//Define global variable for multiple zamartz admin initializaion and defining notice box once
		global $is_event_tracker_display;
		//Add admin notice for event tracker
		if ($is_event_tracker_display == null) {
			$is_event_tracker_display = true;
			add_action('admin_notices', array($this, 'get_event_tracker_notice_html'));
		}

		//Event tracking notice box action
		add_action('wp_ajax_wp_zamartz_admin_event_tracker_ajax', array($this, 'wp_zamartz_admin_event_tracker_ajax'));

		//Add ajax action to save form data
		add_action('wp_ajax_wp_zamartz_admin_general_form_data_ajax', array($this, 'save_form_data_ajax'));
	}

	/**
	 * Initialize the zamartz admin menu. 
	 *
	 * @since    1.0.0
	 */
	public function init_menu()
	{
		global $admin_page_hooks, $submenu;
		$main_menu = 'zamartz-admin';
		if (empty($admin_page_hooks[$main_menu])) {
			add_menu_page('ZAMARTZ Admin Dashboard', 'ZAMARTZ', 'manage_options', 'zamartz-admin', array($this, 'zamartz_admin_dashboard'), $this->zamartz_url . 'assets/images/zamartz-icon-menu.png', '50');

			if (!in_array('zamartz-admin', $submenu)) {
				add_submenu_page('zamartz-admin', 'Dashboard', 'Dashboard', 'manage_options', 'zamartz-admin');
			}

			if (!in_array('zamartz-settings', $submenu)) {
				add_submenu_page('zamartz-admin', 'Settings', 'Settings', 'manage_options', 'zamartz-settings', array($this, 'zamartz_admin_settings'));
			}
			if (!in_array('zamartz-status', $submenu)) {
				add_submenu_page('zamartz-admin', 'Status', 'Status', 'manage_options', 'zamartz-status', array($this, 'zamartz_admin_status'));
			}
		}
	}

	/**
	 * Initialize the zamartz network admin menu. 
	 *
	 * @since    1.0.0
	 */
	public function init_network_menu()
	{
		global $admin_page_hooks, $submenu;
		if (empty($admin_page_hooks['zamartz-network-admin'])) {
			add_menu_page('ZAMARTZ Network Admin', 'ZAMARTZ', 'manage_options', 'zamartz-network-admin', array($this, 'zamartz_network_admin_dashboard'), $this->zamartz_url . 'assets/images/zamartz-icon-menu.png', '50');
			if (!in_array('zamartz-network-admin', $submenu)) {
				add_submenu_page('zamartz-network-admin', 'Dashboard', 'Dashboard', 'manage_options', 'zamartz-network-admin');
			}
			if (!in_array('zamartz-network-settings', $submenu)) {
				add_submenu_page('zamartz-network-admin', 'Settings', 'Settings', 'manage_options', 'zamartz-network-settings', array($this, 'zamartz_network_admin_settings'));
			}
		}
	}

	/**
	 * Function is responsible of displaying the data on the dashboard. There are two instances
	 * free and paid version. For either case, the modules will be displayed accordingly.
	 *
	 * @since    1.0.0
	 */
	public function zamartz_admin_dashboard()
	{
		/**
		 * The class responsible for defining all actions that occur in the Zamartz dashboard.
		 */
		require_once $this->zamartz_path . '/class-zamartz-dashboard.php';
		$zamartz_dashboard = new Zamartz_Dashboard;
		$zamartz_dashboard->init();
	}

	/**
	 * Function is responsible of displaying all admin related general settings. 
	 * There are two instances free and paid version. 
	 * For either case, the modules will be displayed accordingly.
	 *
	 * @since    1.0.0
	 */
	public function zamartz_admin_settings()
	{
		/**
		 * The class responsible for defining all actions that occur in the Zamartz settings.
		 */
		require_once $this->zamartz_path . '/class-zamartz-settings.php';
		$zamartz_settings = new Zamartz_Settings;
		$zamartz_settings->init();
	}

	/**
	 * Function is responsible for displaying the current status of the plugins.
	 * 
	 * @since    1.0.0
	 */
	public function zamartz_admin_status()
	{
		/**
		 * The class responsible for defining all actions that occur in the Zamartz status.
		 */
		require_once $this->zamartz_path . '/class-zamartz-status.php';
		$zamartz_status = new Zamartz_Status;
		$zamartz_status->init();
	}

	/**
	 * Function is responsible of displaying the data on the network dashboard for Zamartz admin. 
	 * There are two instances free and paid version. 
	 * For either case, the modules will be displayed accordingly.
	 * 
	 * @since    1.0.0
	 */
	public function zamartz_network_admin_dashboard()
	{
		/**
		 * The class responsible for defining all actions that occur in the Zamartz status.
		 */
		require_once $this->zamartz_path . '/network/class-zamartz-network-dashboard.php';
		$zamartz_dashboard = new Zamartz_Network_Dashboard;
		$zamartz_dashboard->init();
	}

	/**
	 * Function is responsible of displaying all admin related general settings. 
	 * There are two instances free and paid version. 
	 * For either case, the modules will be displayed accordingly.
	 *
	 * @since    1.0.0
	 */
	public function zamartz_network_admin_settings()
	{
		/**
		 * The class responsible for defining all actions that occur in the Zamartz settings.
		 */
		require_once $this->zamartz_path . '/network/class-zamartz-network-settings.php';
		$zamartz_settings = new Zamartz_Network_Settings;
		$zamartz_settings->init();
	}

	/**
	 * Save the form data of settings page
	 * 
	 * @since    1.0.0
	 */
	public function save_form_data_ajax()
	{
		$form_data = filter_input(INPUT_POST, 'form_data', FILTER_SANITIZE_STRING);
		parse_str($form_data, $postArray);

		if (!wp_verify_nonce(wp_unslash($postArray['zamartz_settings_nonce']), 'zamartz-settings')) {
			echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
			die();
		}
		global $wpdb;

		$error = false;
		$message = 'Your settings have been saved.';
		$class = 'updated inline';
		foreach ($postArray as $key => $data) {
			if (empty($key) || strpos($key, 'wp_zamartz_admin') === false) {
				continue;
			}
			if ($key == 'wp_zamartz_admin_event_tracker' && $data == 'yes'){
				$zamartz_active_plugin_list = get_zamartz_active_plugin_list();
				foreach ($zamartz_active_plugin_list as $plugin_name) {
					$event_data = array(
						'ec' => $plugin_name,
						'ea' => 'activate',
						'el' => 'plugin_activated',
					);
					send_zamartz_tracker_request($event_data);
				}
			}
			update_option($key, $data);
			if (!empty($wpdb->last_error)) {
				$error = true;
				$message = 'There was a problem while updating the option data';
				$class = 'error inline';
				break;
			}
		}

		echo json_encode(
			array(
				'status' => !$error,
				'message' => '<p><strong>' . $message . '</strong></p>',
				'class' => $class
			)
		);
		die();
	}

	/**
	 * Add admin event tracker notice
	 */
	public function get_event_tracker_notice_html()
	{
		$event_tracker = get_option('wp_zamartz_admin_event_tracker');
		if ($event_tracker === false) {
			echo '<div class="notice notice-warning zamartz-admin-tracker-notice">
				<strong>Share anonymous events for ZAMARTZ plugins. <a href="https://zamartz.com/privacy-policy/">https://zamartz.com/privacy-policy/</a> Wordpress does not give us meaningful info so please help.</strong>
				<div class="zamartz-admin-tracker-notice-buttons">
					<button data-type="accept" type="button" class="button button-primary button-large zamartz-event-tracker-button">
						Yes Share Anonymously
					</button>
					<button data-type="reject" type="button" class="button button-secondary button-large zamartz-event-tracker-button">
						No Thanks
					</button>
				</div>
			</div>';
		}
	}

	/**
	 * Set the value for the admin tracker share anonmously notice
	 */
	public function wp_zamartz_admin_event_tracker_ajax()
	{
		$btn_type = filter_input(INPUT_POST, 'btn_type', FILTER_SANITIZE_STRING);
		if ($btn_type == '') {
			echo json_encode(array('success' => false));
			wp_die();
		}
		if ($btn_type == 'accept') {
			$zamartz_active_plugin_list = get_zamartz_active_plugin_list();
			foreach ($zamartz_active_plugin_list as $plugin_name) {
				$event_data = array(
					'ec' => $plugin_name,
					'ea' => 'activate',
					'el' => 'plugin_activated',
				);
				send_zamartz_tracker_request($event_data);
			}
			update_option('wp_zamartz_admin_event_tracker', 'yes');
		} else {
			update_option('wp_zamartz_admin_event_tracker', 'no');
		}
		echo json_encode(array('success' => true));
		wp_die();
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function zamartz_enqueue_scripts()
	{
		$zamartz_dir = $this->zamartz_url . 'assets';
		wp_register_script(
			'zamartz-jquery-tiptip',
			$zamartz_dir . '/js/zamartz-tipTip.min.js',
			array('jquery'),
			WP_ZAMARTZ_ADMIN_VERSION,
			true
		);
		wp_enqueue_script(
			'zamartz-admin-js',
			$zamartz_dir . '/js/zamartz-common.js',
			array('jquery', 'zamartz-jquery-tiptip', 'select2'),
			WP_ZAMARTZ_ADMIN_VERSION,
			true
		);
		wp_localize_script(
			'zamartz-admin-js',
			'zamartz_localized_object',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'is_network_admin' => is_network_admin(),
			)
		);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function zamartz_enqueue_styles()
	{
		$zamartz_dir = $this->zamartz_url . 'assets';
		wp_enqueue_style(
			'zamartz-tiptip-css',
			$zamartz_dir . '/css/zamartz-tipTip.css',
			array(),
			WP_ZAMARTZ_ADMIN_VERSION,
			'all'
		);
		wp_enqueue_style(
			'zamartz-admin-css',
			$zamartz_dir . '/css/zamartz-common.css',
			array(),
			WP_ZAMARTZ_ADMIN_VERSION,
			'all'
		);
	}
}
