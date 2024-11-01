<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Initialize input prefix
$zamartz_admin_event_tracker = get_option('wp_zamartz_admin_event_tracker');
$plugin_input_prefix = 'woo_shop_manager_';

// Options List
$option_list = array(
	// Api credentials
	$plugin_input_prefix . 'cron_log',
	$plugin_input_prefix . 'api_license_key',
	$plugin_input_prefix . 'api_password',
	$plugin_input_prefix . 'api_product_id',
	$plugin_input_prefix . 'api_purchase_emails',
	$plugin_input_prefix . 'api_get_response',
	$plugin_input_prefix . 'zamartz_api_admin_notice_data',
	$plugin_input_prefix . 'network_admin_api_status',

	// Admin fields
	$plugin_input_prefix . 'ruleset_toggle',
	$plugin_input_prefix . 'visual_customizer',

	// Rulesets fields
	$plugin_input_prefix . 'show_product_in_category',
	$plugin_input_prefix . 'show_product_is_product',
	$plugin_input_prefix . 'show_product_has_variant',
	$plugin_input_prefix . 'show_product_has_subattribute',
	$plugin_input_prefix . 'show_product_price_type',
	$plugin_input_prefix . 'show_product_price',

	$plugin_input_prefix . 'show_category_is_category',
	$plugin_input_prefix . 'show_category_has_subcategory',
	$plugin_input_prefix . 'show_category_product_in',
	$plugin_input_prefix . 'show_category_has_variant',
	$plugin_input_prefix . 'show_category_product_count_type',
	$plugin_input_prefix . 'show_category_product_count',

	$plugin_input_prefix . 'conditions',
	$plugin_input_prefix . 'operator',
	$plugin_input_prefix . 'condition_subfield',
	$plugin_input_prefix . 'rule_set_priority',
	$plugin_input_prefix . 'rule_toggle',
);

if (!is_multisite()) {
	//Clear all options
	foreach ($option_list as $option_name) {
		delete_option($plugin_input_prefix . $option_name);
	}
} else {
	// get database of multisites
	global $wpdb;
	// get blog id list
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	// store original id list
	$original_blog_id = get_current_blog_id();
	// cycle through blog ids
	foreach ($blog_ids as $blog_id) {
		switch_to_blog($blog_id);
		//cycle through options
		foreach ($option_list as $option_name) {
			delete_option($plugin_input_prefix . $option_name);
		}
	}
	// Set Back to Current Blog
	restore_current_blog($original_blog_id);
}

if ($zamartz_admin_event_tracker === 'yes') {
	
	$cache_string = time();
    $tracker_url =  'https://zamartz.com/?api-secure-refrence&nocache='.$cache_string;

	$site_url = get_site_url();
	$site_hash_url = hash('sha256', $site_url);

	$tracker_data = array(
		'v'    => '1',
		'cid' => $site_hash_url,
		't' => 'event',
		'ec' => 'wp-shop-page-manager-woo',
		'ea' => 'delete',
		'el' => 'plugin options deleted',
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
