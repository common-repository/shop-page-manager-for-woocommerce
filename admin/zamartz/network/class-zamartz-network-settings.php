<?php

/**
 * The network admin settings specific functionality of the plugin. 
 * The admin-specific functionality of the plugin.
 * Defines the functionality for zamartz network admin
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Network_Settings
{

	/**
	 * Incorporate the trait functionalities for Zamartz General in this class
	 * @see     zamartz/helper/trait-zamartz-general.php
	 * 
	 * Incorporate the trait functionalities for HTML template in this class
	 * @see     zamartz/helper/trait-zamartz-html-template.php
	 */
	use Zamartz_General, Zamartz_HTML_Template;

	/**
	 * Stores the path information to zamartz settings folder
	 * 
	 * @since    1.0.0
	 * @var      string    $zamartz_settings_path    Path to Plugin > admin > zamartz > settings.
	 */
	private $zamartz_network_settings_path;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{

		$base_plugin_path = plugin_dir_path(dirname(__FILE__));
		$this->zamartz_settings_path = $base_plugin_path . 'network/settings';

		$tab_list = array(
			array(
				'title' => 'Extensions',
				'slug' => 'extensions'
			),
			array(
				'title' => 'Plugins',
				'slug' => 'plugins'
			),
			array(
				'title' => 'Add-ons',
				'slug' => 'addons'
			),
		);

		$current_tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING);
		if (empty($current_tab)) {
			$current_tab = 'extensions';
		}

		$current_section = filter_input(INPUT_GET, 'section', FILTER_SANITIZE_STRING);
		if (empty($current_section)) {
			$current_section = '';
		}

		//Get settings of the current accessed page
		$page_settings = $this->get_page_content($current_tab, $current_section);

		//Render the page
		$this->render_settings_page_content($tab_list, $current_tab, $current_section, $page_settings);
	}

	/**
	 * Retrieve the tab or section content of the current settings page
	 * 
	 * @since    1.0.0
	 * @param	string	$current_tab		Current selected tab
	 * @param	string	$current_section	Current selected section
	 */
	public function get_page_content($current_tab, $current_section)
	{
		//Add respective class file
		require_once $this->zamartz_settings_path . "/class-network-settings-{$current_tab}.php";
		//Dynamically define class name
		$class_name = 'Zamartz_Network_Settings_' . ucfirst($current_tab);

		//Class instantiate
		$zamartz_settings = new $class_name;

		//Initialize class core settings
		$zamartz_settings->init();

		//Get page content settings
		$page_content_settings = $zamartz_settings->get_page_settings();

		//Return settings
		return $page_content_settings;
	}

}
