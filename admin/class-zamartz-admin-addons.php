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
 * Defines the settings for Zamartz admin settings, add-on tab
 *
 * @package    Wp_Shop_Page_Manager
 * @subpackage Wp_Shop_Page_Manager/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Woo_Shop_Admin_Settings_Addons
{

    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     * 
     * Incorporate the trait functionalities for HTML template in this class
     * @see     zamartz/helper/trait-zamartz-html-template.php
     * 
     * Incorporate the trait functionalities for API methods in this class
     * @see     zamartz/helper/trait-zamartz-api-methods.php
     */
    use Zamartz_General, Zamartz_HTML_Template, Zamartz_API_Methods;

    /**
     * Loop order defining which accordion should be given priority with open/close state
     * 
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $loop_order    The loop number of each section.
     */
    protected $loop_order;

    /**
     * Form settings data
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $form_data    Saves the data for our respective section form
     */
    private $form_data;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct($settings_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($settings_instance);

        if (is_multisite()) {
            $blog_id = get_current_blog_id();
            $this->is_cron_log = get_blog_option($blog_id,  $settings_instance->plugin_input_prefix . 'cron_log');
        } else {
            $this->is_cron_log = get_option($settings_instance->plugin_input_prefix . 'cron_log');
        }
        $this->api_license_key = get_option($settings_instance->plugin_input_prefix . 'api_license_key');

        //Set accordion loop number
        $this->set_accordion_loop_order();

        //Set valid product IDs for API integration
        $product_id_array = array(26782, 26783, 26784, 26785);
        $this->set_valid_product_id($product_id_array);
        
        //Set ignore list for paid features
        $this->shop_manager_set_ignore_list();

        //Add filter to add/remove sub-navigation for each tab
        add_filter('zamartz_dashboard_accordion_information', array($this, 'get_dashboard_information'), 10, 1);

        //Add filter to add/remove sub-navigation for each tab
        add_filter('zamartz_dashboard_accordion_settings', array($this, 'get_dashboard_settings'), 10, 1);

        //Add filter to add/remove sub-navigation for each tab - Zamartz Admin (HTML Template trait class)
        add_filter('zamartz_settings_subnav', array($this, 'get_section_tab_settings'), 10, 1);

        //Content display settings for add-ons page
        add_action('zamartz_admin_addon_information', array($this, 'get_addon_information'), 10, 1);

        //Content display settings for add-ons page
        add_action('zamartz_admin_addon_settings', array($this, 'get_addon_settings'), 10, 1);

        //Add ajax action to save form data - Zamartz Admin (General trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'form_data_ajax', array($this, 'save_form_data_ajax'));

        //Add ajax action to activate/deactivate plugin - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'activate_ajax', array($this, 'set_api_license_key_ajax'));

        //Add ajax action to activate/deactivate plugin - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'clear_api_credentials_ajax', array($this, 'clear_api_credentials_ajax'));

        //Add ajax to get plugin status - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'get_api_status_ajax', array($this, 'get_api_status_ajax'));

        //Create twice monthly cron schedule - Zamartz Admin (API trait class)
        add_filter('cron_schedules', array($this, 'zamartz_interval_twice_monthly'));

        //Run the API cron scheduler handler twice a month to check for API handshake - Zamartz Admin (API trait class)
        add_action('zamartz_api_cron_schedule_twice_monthly', array($this, 'zamartz_api_cron_schedule_handler'));

        //Create weekly cron schedule - Zamartz Admin (API trait class)
        add_filter('cron_schedules', array($this, 'zamartz_interval_weekly'));

        //Run the API cron scheduler handler weekly for disabling API paid features (if needed) - Zamartz Admin (API trait class)
        add_action('zamartz_api_cron_schedule_admin_notice', array($this, 'zamartz_disable_paid_features'));

        //Add admin notice if any - Zamartz Admin (API trait class)
        add_action('admin_notices', array($this, 'zamartz_api_admin_notice'));
    }

    /**
     * Get zamartz dasboard add-on accordion settings
     * 
     * @since    1.0.0
     */
    public function get_dashboard_information($dashboard_information)
    {
        if (!empty($dashboard_information) && $dashboard_information != null) {
            return $dashboard_information;
        }
        $dashboard_information = array(
            'title' => __('Dashboard', "wp-shop-page-manager-woo"),
            'description' => __("This dashboard will show all of the most recent update and activity for the ZAMARTZ family of Wordpress extensions.", "wp-shop-page-manager-woo")
        );
        return $dashboard_information;
    }

    /**
     * Get zamartz dasboard add-on accordion settings
     * 
     * @since    1.0.0
     */
    public function get_dashboard_settings($table_row_data)
    {
        $plugin_info = $this->get_plugin_info();
        $addon_settings_link = admin_url() . 'admin.php?page=zamartz-settings&tab=addons&section=' . $this->get_plugin_section_slug();
        $image_url = '<a href="' . $addon_settings_link . '">
                        <img title="' . $this->plugin_display_name . '" alt="Thumbnail for ' . $this->plugin_display_name . ', click for settings" src="' . $this->plugin_url['image_url'] . '/dashboard-default.png">
                        </a>';
        $feed_title = '<a alt="Title for ' . $this->plugin_display_name . ', click for settings" href="' . $addon_settings_link . '">' . $this->plugin_display_name . '</a>';
        $table_row_data[] = array(
            'data' => array(
                $image_url,
                '<p class="feed-item-title">' . $feed_title . '</p>
                 <p tabindex="0">' . $plugin_info['Description'] . '</p>',
            ),
            'row_class' => 'feed-row-content',
        );

        return $table_row_data;
    }

    /**
     * Add-on information for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_addon_information($addon_information)
    {
        $wrapper_class = '';
        if ($this->plugin_api_version === 'Free'){
            $wrapper_class = ' plugin-free-version';
        }
        $addon_information[$this->get_plugin_section_slug()] = array(
            'title' => $this->plugin_display_name,
            'description' => __("These Add-Ons provide functionality to existing Wordpress functionality or other extensions and plugins", "wp-shop-page-manager-woo"),
            'input_prefix' => $this->plugin_input_prefix,
            'wrapper_class' => $wrapper_class
        );
        
        return $addon_information;
    }

    /**
     * Add-on settings for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_addon_settings($addon_settings)
    {
        //Get get_functionality settings
        $content_array['column_array'][] = $this->get_functionality_settings();

        //Get license settings
        $content_array['column_array'][] = $this->get_license_settings();

        //Define page structure
        $content_array['page_structure'] = array(
            'desktop_span' => '75',
            'mobile_span' => '100',
        );

        $plugin_section_slug = $this->get_plugin_section_slug();
        $addon_settings[$plugin_section_slug][] = $content_array;

        //Get sidebar settings
        $addon_settings[$plugin_section_slug]['sidebar-settings'] = $this->get_sidebar_settings();

        return $addon_settings;
    }

    /**
     * Functionality settings inside the add-on tab
     */
    public function get_functionality_settings()
    {
        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => false,
            'accordion_class' => 'zamartz-addon-settings',
            'accordion_loop' => $this->loop_order['zamartz_functionality_settings'],
            'form_section_data' => array(
                'linked_class' => 'zamartz-addon-settings'
            ),
            'title' => __("Functionality", "wp-shop-page-manager-woo")
        );

        /**
         * Required items: 
         * 1) Shop page manager (FV)    - Toggle swtich - additional info: configure
         * 2) Visual Customizer (PV)    - Toggle swtich - additional info: customize
         * 3) Shop page (PV)            - Select2 Dropdown - additional info
         * 4) Add to cart behavior (PV) - Checkbox 
         * -- Add border --
         * 5) Customize Defaults (PV)   - Dropdowns
         */

        $pages_field_option = $this->get_shop_page_field_options();

        //Define table data
        $table_section_array = array(
            array(
                'title' =>  __("Shop Page Manager", "wp-shop-page-manager-woo"),
                'tooltip_desc' =>  __("Enabling will activate add-on functionality to entire shop page manager", "wp-shop-page-manager-woo"),
                'type' => 'toggle_switch',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "ruleset_toggle",
                ),
                'additional_content' => '
                    <div class="additional-content">
                        <a href="' . admin_url() . 'admin.php?page=wc-settings&tab=products&section=shop_page_manager' . '">
                        ' . __("Configure", "wp-shop-page-manager-woo") . '
                        </a>
                    </div>'
            ),
            array(
                'title' =>  __("Visual Customizer", "wp-shop-page-manager-woo"),
                'tooltip_desc' =>  __("Enabling will activate add-on functionality to wordpress visual customizer", "wp-shop-page-manager-woo"),
                'type' => 'toggle_switch',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "visual_customizer",
                    'class' => 'shop-manager-paid-feature paid-feature-parent'
                ),
                'additional_content' => '
                    <div class="additional-content">
                        <a href="' . admin_url('/customize.php?autofocus[section]=shop_page_manager_section') .'">
                        ' . __("Customize", "wp-shop-page-manager-woo") . '
                        </a>
                    </div>'
            ),
            array(
                'title' =>  __("Shop page", "wp-shop-page-manager-woo"),
                'type' => 'select',
                'is_multi' => false,
                'tooltip_desc' =>  __("This sets the base page of your shop - this is where your product archive will be.", "wp-shop-page-manager-woo"),
                'option_settings' => array(
                    'name' => "{$this->plugin_input_prefix}__remove__woocommerce_shop_page_id",
                    'class' => 'wc-enhanced-select shop-manager-paid-feature',
                    'data-params' => array(
                        'minimum_input_length'  => 4,
                    )
                ),
                'field_options' => $pages_field_option,
            ),
            array(
                'title' => __("Add to cart behavior", "wp-shop-page-manager-woo"),
                'type' => 'checkbox',
                'field_options' => array(
                    "{$this->plugin_input_prefix}__remove__woocommerce_cart_redirect_after_add" => array(
                        'label' => __("Redirect to the cart page after successful addition", "wp-shop-page-manager-woo"),
                        'class' => 'shop-manager-paid-feature'
                    ),
                    "{$this->plugin_input_prefix}__remove__woocommerce_enable_ajax_add_to_cart" => array(
                        'label' => __("Enable AJAX add to cart buttons on archives", "wp-shop-page-manager-woo"),
                        'class' => 'shop-manager-paid-feature'
                    ),
                ),
            ),
            array(
                'title' => __("Customizer Defaults", "wp-shop-page-manager-woo"),
                'section_class' => 'zamartz-bordered zamartz-customizer-defaults-multi-column',
                'type' => 'multi_column',
                'option_settings' => array(
                    array(
                        'label' => __("Shop page display", "wp-shop-page-manager-woo"),
                        'type' => 'select',
                        'section_class' => 'zamartz-group-row',
                        'tooltip_desc' => 'Choose what to display on the main shop page.',
                        'option_settings' => array(
                            'name' => "{$this->plugin_input_prefix}__remove__woocommerce_shop_page_display",
                            'class' =>  'zamartz-multi-column-select shop-manager-paid-feature'
                        ),
                        'field_options' => array(
                            '' => 'Show products',
                            'subcategories' => 'Show categories',
                            'both' => 'Show categories & products'
                        ),
                    ),
                    array(
                        'label' => __("Category display", "wp-shop-page-manager-woo"),
                        'type' => 'select',
                        'section_class' => 'zamartz-group-row',
                        'tooltip_desc' => 'Choose what to display on product category pages.',
                        'option_settings' => array(
                            'name' => "{$this->plugin_input_prefix}__remove__woocommerce_category_archive_display",
                            'class' =>  'zamartz-multi-column-select shop-manager-paid-feature'
                        ),
                        'field_options' => array(
                            '' => 'Show products',
                            'subcategories' => 'Show subcategories',
                            'both' => 'Show subcategories & products'
                        ),
                    ),
                    array(
                        'label' => __("Default Sorting", "wp-shop-page-manager-woo"),
                        'type' => 'select',
                        'section_class' => 'zamartz-group-row',
                        'tooltip_desc' => 'How should products be sorted in the catalog by default?',
                        'option_settings' => array(
                            'name' => "{$this->plugin_input_prefix}__remove__woocommerce_default_catalog_orderby",
                            'class' =>  'zamartz-multi-column-select shop-manager-paid-feature'
                        ),
                        'field_options' => array(
                            'menu_order' => 'Default sorting (custom ordering + name)',
                            'popularity' => 'Popularity (sales)',
                            'rating' => 'Average rating',
                            'date' => 'Sort by most recent',
                            'price' => 'Sort by price (asc)',
                            'price-desc' => 'Sort by price (desc)'
                        ),
                    ),
                    array(
                        'label' => __("Products Per Row", "wp-shop-page-manager-woo"),
                        'type' => 'input_number',
                        'section_class' => 'zamartz-group-row',
                        'tooltip_desc' => 'How many products should be shown per row?',
                        'option_settings' => array(
                            'name' => "{$this->plugin_input_prefix}__remove__woocommerce_catalog_columns",
                            'class' =>  'zamartz-multi-column-text-input shop-manager-paid-feature',
                            'min'   =>  1
                        ),
                    )
                ),
            ),
        );

        //Set form data
        $this->set_zamartz_functionality_form_data();

        //Define table parameters
        $table_params = array(
            'form_data' => $this->form_data,
            'section_type' => '',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'table_params' => $table_params,
        );
    }

    /**
     * Retrieve field options based on existing wordpress pages
     * 
     * @since   1.0.0
     */
    public function get_shop_page_field_options()
    {
        $data = array();
        $page_dropdown_args = array(
            'value_field'   => 'ID',
            'sort_order'    => 'ASC',
            'post_status'   => 'publish,private,draft',
        );
        // walk_page_dropdown_tree
        $pages = get_pages($page_dropdown_args);
        foreach ($pages as $page) {
            $data[$page->ID] = __($page->post_title, "wp-shop-page-manager-woo");
        }
        return $data;
    }

    /**
     * Set form data for Add-on functionality accordion
     */
    public function set_zamartz_functionality_form_data()
    {

        //Get plugin values
        $this->form_data["{$this->plugin_input_prefix}ruleset_toggle"] = get_option($this->plugin_input_prefix . 'ruleset_toggle');
        $this->form_data["{$this->plugin_input_prefix}visual_customizer"] = get_option($this->plugin_input_prefix . 'visual_customizer');

        //Get WooCommerce data
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_shop_page_id"] = get_option('woocommerce_shop_page_id');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_cart_redirect_after_add"] = get_option('woocommerce_cart_redirect_after_add');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_enable_ajax_add_to_cart"] = get_option('woocommerce_enable_ajax_add_to_cart');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_shop_page_display"] = get_option('woocommerce_shop_page_display');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_category_archive_display"] = get_option('woocommerce_category_archive_display');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_default_catalog_orderby"] = get_option('woocommerce_default_catalog_orderby');
        $this->form_data["{$this->plugin_input_prefix}__remove__woocommerce_catalog_columns"] = get_option('woocommerce_catalog_columns');
    }

    /**
     * Set the field names that need to be ignored
     */
    public function shop_manager_set_ignore_list()
    {
        //Set ignore list for paid features
        if ($this->plugin_api_version === 'Free') {
            $this->ignore_list[] = "{$this->plugin_input_prefix}visual_customizer";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_shop_page_id";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_enable_ajax_add_to_cart";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_shop_page_display";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_category_archive_display";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_default_catalog_orderby";
            $this->ignore_list[] = "{$this->plugin_input_prefix}__remove__woocommerce_catalog_columns";
        }
    }
}
