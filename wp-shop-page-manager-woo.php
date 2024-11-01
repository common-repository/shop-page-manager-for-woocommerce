<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://zamartz.com
 * @since             1.0.0
 * @package           Wp_Shop_Page_Manager_Woo
 *
 * @wordpress-plugin
 * Plugin Name:       Shop Page Manager for WooCommerce
 * Plugin URI:        https://zamartz.com/product/
 * Description:       The Shop Page Manager adds additional functionality to the default WooCommerce Shop pages and allows for rules to be set hiding the display of both categories and products. These rules can either be set based on the product, category, sub-categories, or cart / checkout details.
 * Version:           1.2.0
 * Author:            Zachary Martz
 * Author URI:        https://zamartz.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-shop-page-manager-woo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WP_SHOP_PAGE_MANAGER_WOO_VERSION', '1.2.0' );

/**
 * Current plugin directory slug
 */
define('WP_SHOP_PAGE_MANAGER_WOO_DIR_SLUG', plugin_basename(dirname(__FILE__)));

/**
 * Current plugin file path with directory slug
 */
define('WP_SHOP_PAGE_MANAGER_WOO_DIR_FILE_SLUG', plugin_basename(__FILE__));

if (!isset($zamartz_admin_version)){
	$zamartz_admin_version = array();
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-shop-page-manager-woo-activator.php
 */
function activate_wp_shop_page_manager_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-shop-page-manager-woo-activator.php';
	Wp_Shop_Page_Manager_Woo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-shop-page-manager-woo-deactivator.php
 */
function deactivate_wp_shop_page_manager_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-shop-page-manager-woo-deactivator.php';
	Wp_Shop_Page_Manager_Woo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_shop_page_manager_woo' );
register_deactivation_hook( __FILE__, 'deactivate_wp_shop_page_manager_woo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-shop-page-manager-woo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_shop_page_manager_woo() {

	$plugin = new Wp_Shop_Page_Manager_Woo();
	$plugin->run();

}
run_wp_shop_page_manager_woo();
