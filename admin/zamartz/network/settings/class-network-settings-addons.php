<?php

/**
 * The Zamartz admin settings specific functionality of the plugin. 
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and all Zamartz settings related functionality.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Network_Settings_Addons
{

	/**
	 * Stores all settings for rendering the current section.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $page_settings    Current page settings.
	 */
	private $page_settings;

	/**
	 * Initialize current class settings
	 *
	 * @since    1.0.0
	 */
	public function init()
	{
		//Get page settings
		$this->page_settings['settings'] = $this->get_addon_settings();
		$this->page_settings['information'] = $this->get_addon_information();
	}

	/**
	 * Get $page_settings variable data
	 * 
	 * @since    1.0.0
	 */
	public function get_page_settings()
	{
		return $this->page_settings;
	}

	/**
	 * Get section settings.
	 *
	 * @since    1.0.0
	 */
	public function get_addon_settings($addon_settings = array())
	{
		$addon_settings = apply_filters('zamartz_network_addon_settings', $addon_settings);
		return $addon_settings;
	}
	
	/**
	 * Get section information.
	 *
	 * @since    1.0.0
	 */
	public function get_addon_information()
	{
		$addon_information = apply_filters('zamartz_network_addon_information', array());
		return $addon_information;
	}
}
