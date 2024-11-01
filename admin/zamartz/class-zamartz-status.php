<?php

/**
 * The Zamartz admin status specific functionality of the plugin. 
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and all Zamartz settings related functionality.
 * 
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Status
{
	/**
	 * Incorporate the trait functionalities for HTML template in this class
	 * 
	 * @see     zamartz/helper/trait-zamartz-html-template.php
	 */
	use Zamartz_HTML_Template;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{

		$status_settings_array = apply_filters('zamartz_plugin_status', array());

		foreach($status_settings_array as $settings){

			$table_section_array = $settings['table_section_array'];
			$table_params = $settings['table_params'];
			
			echo $data = $this->generate_simple_table_html($table_section_array, $table_params);
		}
	}
}
