<?php

/**
 * Fired during plugin activation
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/includes
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Wp_Shop_Page_Manager_Woo_Activator
{

	/**
	 * Activated the plugin and send google analytics
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		$event_tracker = get_option('wp_zamartz_admin_event_tracker');
		if ($event_tracker === 'yes') {
			$cache_string = time();
			$ec = WP_SHOP_PAGE_MANAGER_WOO_DIR_SLUG;
    		$tracker_url =  'https://zamartz.com/?api-secure-refrence&nocache='.$cache_string;

			$site_url = get_site_url();
			$site_hash_url = hash('sha256', $site_url);

			$tracker_data = array(
				'v'    => '1',
				'cid' => $site_hash_url,
				't' => 'event',
				'ec' =>  $ec,
				'ea' => 'activate',
				'el' => 'plugin_activated',
				'ev' => '1',
			);

			wp_remote_request(
				$tracker_url,
				array(
					'method'      => 'GET',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded'
					),
					'body'        => $tracker_data,
					'cookies' => array()
				)
			);
		}
	}
}
