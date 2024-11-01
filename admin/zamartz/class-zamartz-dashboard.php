<?php

/**
 * The Zamartz admin dashboard specific functionality of the plugin. 
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and all Zamartz dashboard related functionality.
 * 
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Dashboard
{
	/**
	 * Incorporate the trait functionalities for Zamartz General in this class
	 * @see     zamartz/helper/trait-zamartz-general.php
	 * 
	 * Incorporate the trait functionalities for RSS methods in this class
	 * @see     zamartz/helper/trait-zamartz-rss-methods.php
	 */
	use Zamartz_General, Zamartz_RSS_Methods;

	/**
	 * Stores RSS feeds to display.
	 * 
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $rss_feeds    RSS feeds data.
	 */
	public $rss_feeds;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{
		$is_remove_ads = apply_filters('zamartz_is_remove_ads', false);
		$this->is_remove_ads = $is_remove_ads;
		$addon_settings_array = apply_filters('zamartz_dashboard_accordion_settings', array());
		$addon_page_content = apply_filters('zamartz_dashboard_accordion_information', array());

		$page_structure = array();
		$table_section_array = array(
			'row_data' => array(),
		);


		if (!empty($addon_settings_array)) {
			$accordion_settings = array(
				'type' => '',
				'accordion_class' => '',
				'title' => __("My AddOns", "wp-zamartz-admin")
			);
			foreach ($addon_settings_array as $addon_settings) {
				$table_section_array['row_data'][] = $addon_settings;
			}
			$addon_settings_link = admin_url() . 'admin.php?page=zamartz-settings&tab=addons&section=' . $this->get_plugin_section_slug();
			$table_section_array['row_footer'] = array(
				'is_link' => array(
					'link' => $addon_settings_link,
					'title' => __("Manage Add-ons", "wp-zamartz-admin"),
					'alt' => __("Click to see all My AddOns settings", "wp-zamartz-admin")
				)
			);
			ob_start();
			$this->generate_accordion_html($accordion_settings, $table_section_array);
			$addon_content = ob_get_clean();
			$page_structure[] = array(
				'desktop_span' => '50',
				'mobile_span' => '100',
				'content' => $addon_content
			);
		}

		//Render the second column of the dashboard
		$this->render_dashboard_ad_column($is_remove_ads, $page_structure, $addon_page_content);
	}
}
