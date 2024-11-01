<?php

/**
 * The class is responsible for adding sections inside the WooCommerce settings page.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager
 * @subpackage Wp_Shop_Page_Manager/admin
 */

/**
 * WooCommerce settings specific functionality of the plugin.
 *
 * Defines the settings for Status submenu
 *
 * @package    Wp_Shop_Page_Manager
 * @subpackage Wp_Shop_Page_Manager/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Woo_Shop_Admin_Status
{
    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     * 
     * Incorporate the trait functionalities for API methods in this class
     * @see     zamartz/helper/trait-zamartz-api-methods.php
     */
    use Zamartz_General, Zamartz_API_Methods;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct($settings_instance)
    {
        //Define plugin paramters
        $this->set_plugin_data($settings_instance);

        //Content display settings for add-ons page
        add_filter('zamartz_plugin_status', array($this, 'get_status_settings'), 10, 1);
    }


    /**
     * Status settings for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_status_settings($status_settings_array)
    {

        $plugin_version = WP_SHOP_PAGE_MANAGER_WOO_VERSION;

        $ruleset_toggle = get_option($this->plugin_input_prefix . 'ruleset_toggle');
        $visual_customizer = get_option($this->plugin_input_prefix . 'visual_customizer');

        //Define table data
        $table_section_array = array(
            'row_head' => array(
                'title' =>  __($this->plugin_display_name . " Status", "wp-shop-page-manager-woo"),
                'colspan' => 2
            ),
            'row_data' => array(
                array(
                    'column_data' => array(
                        __("Plugin Version", "wp-shop-page-manager-woo"),
                        $plugin_version
                    ),
                    'tabindex' => 0
                ),
                array(
                    'column_data' => array(
                        __("API Version", "wp-shop-page-manager-woo"),
                        $this->plugin_api_version
                    ),
                    'tabindex' => 0
                ),
                array(
                    'column_data' => array(
                        __("API Authorization", "wp-shop-page-manager-woo"),
                        $this->plugin_api_authorization
                    ),
                    'tabindex' => 0
                ),
                array(
                    'column_data' => array(
                        __("Shop Page Manager", "wp-shop-page-manager-woo"),
                        $ruleset_toggle
                    ),
                    'tabindex' => 0
                ),
                array(
                    'column_data' => array(
                        __("Visual Customizer", "wp-shop-page-manager-woo"),
                        $visual_customizer
                    ),
                    'tabindex' => 0
                )
            )
        );

        $api_get_response = get_option('woo_shop_manager_api_get_response');
        $cron_schedule_details = $this->get_cron_schedule_details($api_get_response);
        if (!empty($cron_schedule_details)) {
            $table_section_array['row_data'][]  = array(
                'column_data' => array(
                    __("Cron current run", "wp-shop-page-manager-woo"),
                    $cron_schedule_details['cron_previous_run']
                ),
                'tabindex' => 0
            );
            $table_section_array['row_data'][]  = array(
                'column_data' => array(
                    __("Cron next run", "wp-shop-page-manager-woo"),
                    $cron_schedule_details['cron_next_run']
                ),
                'tabindex' => 0
            );
        }

        $table_params = array(
            'class' => 'zamartz-simple-table widefat'
        );

        $status_settings_array['woo_shop_manager_table'] = array(
            'table_params' => $table_params,
            'table_section_array' => $table_section_array
        );
        return $status_settings_array;
    }
}
