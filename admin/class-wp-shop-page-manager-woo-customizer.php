<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Wp_Shop_Page_Manager_Woo_Customizer
{

	/**
	 * Current WooCommerce settings section object
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      object    $settings_instance    Class object of settings instance
	 * @see	Wp_Shop_Page_Manager_Settings class
	 */
	public $settings_instance;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      object    $settings_instance   Class object of settings instance
	 */
	public function __construct($settings_instance)
	{
		$this->settings_instance = $settings_instance;

		//Add scripts for the customizer section
		add_action('customize_controls_enqueue_scripts', array($this, 'shop_manager_customizer_enqueue_scripts'), 99);

		// add shop page manager settings to the WordPress customizer
		add_action('customize_register', array($this, 'register_shop_page_manager_customizer_settings'));
	}

	/**
	 * 
	 */
	public function shop_manager_customizer_enqueue_scripts()
	{
		//WooCommerce styles and scripts
		wp_enqueue_style('woocommerce_admin_styles_customizer', WC()->plugin_url() . '/assets/css/admin.css', array());

		if (wp_script_is('wc-enhanced-select')) {
			wp_dequeue_script('wc-enhanced-select');
			wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.js', array('jquery', 'selectWoo'));
		} else {
			wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.js', array('jquery', 'selectWoo'));
		}

		$file_dir = plugins_url(plugin_basename(dirname(__FILE__)));
		wp_enqueue_script(
			'zamartz-customizer-admin',
			$file_dir . '/js/wp-shop-page-manager-woo-customizer.js',
			array('jquery', 'zamartz-admin-js'),
			$this->settings_instance->version,
			true
		);
		wp_localize_script(
			'zamartz-customizer-admin',
			'zamartz_customizer_localized_object',
			array(
				'shop_url' => esc_js(wc_get_page_permalink('shop')),
			)
		);
	}

	/**
	 * Add shop page manager settings to the customizer.
	 *
	 * @param $wp_customize Theme Customizer object.
	 */

	public function register_shop_page_manager_customizer_settings($wp_customize)
	{

		// Include the custom control file for toggle switch
		require_once 'class-wp-shop-page-manager-woo-custom-controls.php';

		$this->add_shop_page_manager_product_catalog_section($wp_customize);

		//Get visual customizer toggle option from add-on settings
		$visual_customizer = get_option($this->settings_instance->plugin_input_prefix . "visual_customizer");
		if ($visual_customizer == 'yes') {
			$this->add_shop_page_manager_customizer_section($wp_customize);
			add_action('customize_save_zamartz_shop_page_manager_rulesets', array($this, $this->settings_instance->plugin_input_prefix . 'save_form_data_ajax'));
		}
	}

	/**
	 * Ajax functionality to validate form nonce and saving the form into the options table. 
	 * Return respective status, message and class
	 *
	 * @since    1.0.0
	 */
	public function woo_shop_manager_save_form_data_ajax($wp_customize_settings_obj)
	{
		$form_data = $wp_customize_settings_obj->post_value();
		parse_str($form_data, $postArray);

		global $wpdb;

		$settings_instance = $this->settings_instance;

		foreach ($postArray as $key => $data) {
			if (empty($key) || strpos($key, $settings_instance->plugin_input_prefix) === false || (!empty($settings_instance->ignore_list) && in_array($key, $settings_instance->ignore_list))) {
				continue;
			}

			if (strpos($key, $settings_instance->plugin_input_prefix . '__remove__') !== false) {
				$key = str_replace($settings_instance->plugin_input_prefix . '__remove__', '', $key);
			}

			//Logic to clear ruleset priorities if duplicate exists
			if (strpos($key, 'rule_set_priority') !== false) {
				$unique_ruleset_array = array();
				foreach ($data as $index => $priority) {
					if (in_array($priority, $unique_ruleset_array)) {
						$searched_index = array_search($priority, $unique_ruleset_array);
						unset($unique_ruleset_array[$searched_index]);
						$data[$searched_index] = '';
					}
					$unique_ruleset_array[$index] = $priority;
				}
			}
			update_option($key, $data);
		}
	}

	/**
	 * Add shop page manager customizer section.
	 *
	 * @param $wp_customize Theme Customizer object.
	 */

	public function add_shop_page_manager_customizer_section($wp_customize)
	{

		// add section to existing 'woocommerce' panel
		$wp_customize->add_section('shop_page_manager_section', array(
			'title'    => __('Shop Page Manager', 'wp-shop-page-manager-woo'),
			'panel'    => 'woocommerce'
		));

		$wp_customize->add_setting(
			'zamartz_shop_page_manager_rulesets',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce'
			)
		);

		$shop_manager_custom_controls = new Wp_Shop_Page_Manager_Custom_Controls(
			$wp_customize,
			'zamartz_shop_page_manager_rulesets',
			array(
				'label'       => __('Shop page manager', 'wp-shop-page-manager-woo'),
				'description' => __('This is an area to add/remove the rulesets.', 'wp-shop-page-manager-woo'),
				'type'	=>	'rulesets',
				'section'     => 'shop_page_manager_section',
				'settings'    => 'zamartz_shop_page_manager_rulesets',
				'active_callback' => function () {
					$is_ruleset_toggle_enabled = get_option($this->settings_instance->plugin_input_prefix . "ruleset_toggle");
					if ($is_ruleset_toggle_enabled == 'yes') {
						return true;
					}
					return false;
				}
			),
			$this->settings_instance
		);

		$wp_customize->add_control($shop_manager_custom_controls);
	}

	/**
	 * Add shop page manager toggle to the product catalog section.
	 *
	 * @param $wp_customize Theme Customizer object.
	 */

	public function add_shop_page_manager_product_catalog_section($wp_customize)
	{
		$wp_customize->add_setting(
			'woo_shop_manager_ruleset_toggle',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce'
			)
		);
		$shop_manager_custom_controls = new Wp_Shop_Page_Manager_Custom_Controls(
			$wp_customize,
			'woo_shop_manager_ruleset_toggle',
			array(
				'label'       => __('Shop page manager', 'wp-shop-page-manager-woo'),
				'description' => __('Enables or Disables the ZAMARTZ Shop page manager rule sets.', 'wp-shop-page-manager-woo'),
				'type'	=>	'toggle_switch',
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woo_shop_manager_ruleset_toggle',
			),
			$this->settings_instance
		);
		$wp_customize->add_control($shop_manager_custom_controls);
	}
}
