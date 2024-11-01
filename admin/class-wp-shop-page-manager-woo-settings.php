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
 * @package    Wp_Shop_Page_Manager
 * @subpackage Wp_Shop_Page_Manager/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Wp_Shop_Page_Manager_Settings
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
     * Form settings data
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $form_data    Saves the data for our respective section form.
     */
    private $form_data;

    /**
     * Current WooCommerce settings section
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $section_type    Stores the section currently accessed.
     */
    public $section_type;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      object    $core_instance     The instance of Wp_Woo_Dis_Comments_And_Ratings class
     */
    public function __construct($core_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($core_instance);

        //Set plugin paid vs free information
        $this->set_plugin_api_data();

        //Set setting ignore list for paid vs free versions
        $this->woo_shop_manager_set_ignore_list();

        if (class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-admin-addons.php';
            new Woo_Shop_Admin_Settings_Addons($this);
        }

        if (class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-admin-status.php';
            new Woo_Shop_Admin_Status($this);
        }

        // //Content display settings for Network Admin add-ons page
        if ((is_network_admin() || wp_doing_ajax()) && class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-network-admin-addons.php';
            new Woo_Shop_Network_Admin_Settings_Addons($this);
        }

        // Add shop page manager tab to products section on woocommerce settings
        $ruleset_toggle = get_option("{$this->plugin_input_prefix}ruleset_toggle");
        if ($ruleset_toggle === false || $ruleset_toggle == 'yes') {
            add_filter('woocommerce_get_sections_products', array($this, 'add_shop_page_manager_tab'));
            add_filter('woocommerce_get_settings_products', array($this, 'wp_shop_page_manager_get_settings'), 10, 2);
        }

        //Customize WP query
        add_filter('posts_where', array($this, "{$this->plugin_input_prefix}set_post_where_wp_query"), 10, 2);

        // Add ajax functionality
        add_action("wp_ajax_{$this->plugin_input_prefix}get_form_operator_dropdown_ajax", array($this, "{$this->plugin_input_prefix}get_form_operator_dropdown_ajax")); //Get Sub-condition operator dropdown options
        add_action("wp_ajax_woo_shop_manager_get_form_section_ajax", array($this, "woo_shop_manager_get_form_section_ajax")); //On "Add ruleset" button click
        add_action("wp_ajax_woo_shop_manager_product_variation", array($this, "woo_shop_manager_get_select2_dropdown_ajax")); //Get product variation dropdown data via ajax
        add_action("wp_ajax_woo_shop_manager_coupon_is_applied", array($this, "woo_shop_manager_get_select2_dropdown_ajax")); //Get coupon dropdown data via ajax
        add_action("wp_ajax_woo_shop_manager_get_custom_select2_ajax", array($this, "get_custom_select2_dropdown_ajax")); //Get subcategory listing
        add_action("wp_ajax_{$this->plugin_input_prefix}save_form_data_ajax", array($this, "{$this->plugin_input_prefix}save_form_data_ajax")); //save form data

        //Add modal to plugin page
        add_action('admin_footer', array($this, 'get_deactivation_plugin_modal'));

        //Add modal to plugin page
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'deactitvate_plugin', array($this, 'zamartz_deactitvate_plugin'));
    }

    /**
     * Add a shop page manager tab in WooCommerce Settings > Products > Products Options called Shop Page Manager.
     *
     * @since   1.0.0
     * @param   string  $sections   The name of the current WooCommerce section.
     * @return  string
     */

    public function add_shop_page_manager_tab($sections)
    {
        $sections['shop_page_manager'] = __('Shop Page Manager', 'wp-shop-page-manager-woo');
        return $sections;
    }

    /**
     * Define the settings that needs to be displayed for shop page manager section.
     *
     * @since   1.0.0
     * @param   array   $settings           Array of data for form settings to be generated by WooCommerce.
     * @param   string  $current_section    Current WooCommerce section
     * @return  array
     */
    public function wp_shop_page_manager_get_settings($settings, $current_section)
    {
        //Check the current section
        if ($current_section == 'shop_page_manager') {
            $settings_shop_page_manager = array();

            $this->section_type = "";

            $this->wp_shop_page_manager_set_form_data();

            //Display shipping settings html template
            require_once $this->plugin_url['admin_path'] . '/partials/wp-shop-page-manager-woo-admin-display.php';

            return $settings_shop_page_manager;

            /**
             * If not, return the standard settings
             **/
        } else {
            return $settings;
        }
    }

    /**
     * Retrieves the current section and sets the form data.
     *
     * @since   1.0.0
     */
    private function wp_shop_page_manager_set_form_data()
    {
        //Do not show Product section
        $this->form_data["{$this->plugin_input_prefix}show_product_in_category"] = get_option("{$this->plugin_input_prefix}show_product_in_category");
        $this->form_data["{$this->plugin_input_prefix}show_product_is_product"] = get_option("{$this->plugin_input_prefix}show_product_is_product");
        $this->form_data["{$this->plugin_input_prefix}show_product_has_variant"] = get_option("{$this->plugin_input_prefix}show_product_has_variant");
        $this->form_data["{$this->plugin_input_prefix}show_product_has_subattribute"] = get_option("{$this->plugin_input_prefix}show_product_has_subattribute");
        $this->form_data["{$this->plugin_input_prefix}show_product_price_type"] = get_option("{$this->plugin_input_prefix}show_product_price_type");
        $this->form_data["{$this->plugin_input_prefix}show_product_price"] = get_option("{$this->plugin_input_prefix}show_product_price");

        //Do not show Category section
        $this->form_data["{$this->plugin_input_prefix}show_category_is_category"] = get_option("{$this->plugin_input_prefix}show_category_is_category");
        $this->form_data["{$this->plugin_input_prefix}show_category_has_subcategory"] = get_option("{$this->plugin_input_prefix}show_category_has_subcategory");
        $this->form_data["{$this->plugin_input_prefix}show_category_product_in"] = get_option("{$this->plugin_input_prefix}show_category_product_in");
        $this->form_data["{$this->plugin_input_prefix}show_category_has_variant"] = get_option("{$this->plugin_input_prefix}show_category_has_variant");
        $this->form_data["{$this->plugin_input_prefix}show_category_product_count_type"] = get_option("{$this->plugin_input_prefix}show_category_product_count_type");
        $this->form_data["{$this->plugin_input_prefix}show_category_product_count"] = get_option("{$this->plugin_input_prefix}show_category_product_count");

        //Form conditions
        $this->form_data["{$this->plugin_input_prefix}conditions"] = get_option("{$this->plugin_input_prefix}conditions");
        $this->form_data["{$this->plugin_input_prefix}operator"] = get_option("{$this->plugin_input_prefix}operator");
        $this->form_data["{$this->plugin_input_prefix}condition_subfield"] = get_option("{$this->plugin_input_prefix}condition_subfield");

        //Form rule settings
        $this->form_data["{$this->plugin_input_prefix}rule_set_priority"] = get_option("{$this->plugin_input_prefix}rule_set_priority");
        $this->form_data["{$this->plugin_input_prefix}rule_toggle"] = get_option("{$this->plugin_input_prefix}rule_toggle");
    }

    /**
     * Generates the form section to be displayed in the respective tab
     *
     * @since   1.0.0
     * @param   integer     $key    The index of the array
     * @param   integer     $loop   Variable to define rule number
     */
    public function wp_shop_page_manager_get_form_section($key = 0, $loop = 1)
    {
        $table_params = array(
            'form_data' => $this->form_data,
            'section_type'  =>  '',
            'input_prefix' => $this->plugin_input_prefix,
            'key' => $key,
        );

        $table_section_array = [];

        //Do not show Product section
        $table_section_array[] = $this->get_shop_manager_show_product_settings($key);

        //Do not show Category section
        $table_section_array[] = $this->get_shop_manager_show_category_settings($key);

        //Conditions section
        $table_section_array[] = $this->get_form_conditions($key); //Get condition dropdown
        $operator_dropdown = $this->get_form_operator_dropdown($key); //Get operator dropdown
        $operator_dropdown['section_class'] = 'zamartz-form-operator';
        $table_section_array[] = $operator_dropdown;

        $condition_subfield = $this->get_form_condition_subfield($key); //Get condition subfield
        $condition_subfield['section_class'] = 'zamartz-condition-subfield';
        $table_section_array[] = $condition_subfield;

        //Get rule set section
        $rule_set_priority = $this->get_form_rule_set_priority($key);
        $rule_set_priority['section_class'] = 'zamartz-bordered';
        $table_section_array[] = $rule_set_priority;
        $table_section_array[] = $this->get_form_stop_other_rules($key);

        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => true,
            'accordion_class' => 'zamartz-form-rule-section',
            'accordion_loop' => $loop,
            'form_section_data' => array(
                'current_key' => $key,
                'linked_class' => 'zamartz-form-rule-section'
            ),
            'title' => __('Rule Set #') . '<span class="zamartz-loop-number">' . $loop . '</span>'
        );
        $this->generate_accordion_html($accordion_settings, $table_section_array, $table_params);
    }

    /**
     * Get settings for "Do not show Product" section
     * 
     * @since   1.0.0
     */
    public function get_shop_manager_show_product_settings($key)
    {
        // Initialize
        $show_product_is_product_field_options = array();
        $show_product_has_variant_field_options = array();
        $show_product_has_subattribute_field_options = array();
        $show_product_in_category = array();

        //Get show Product "In category" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_product_in_category"][$key])) {
            $show_product_in_category = $this->form_data["{$this->plugin_input_prefix}show_product_in_category"][$key];
        }

        //Get category listing
        $product_cat = get_terms(['taxonomy' => 'product_cat', 'parent' => 0]);
        foreach ($product_cat as $category) {
            $product_categories[$category->term_id] = $category->name;
        }

        //Get show Product "Is product" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_product_is_product"][$key])) {
            $show_product_is_product = $this->form_data["{$this->plugin_input_prefix}show_product_is_product"][$key];
            $show_product_is_product_field_options = $this->get_product_dropdown_info($show_product_is_product);
        }

        //Get show Product "Has variant" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_product_has_variant"][$key])) {
            $show_product_has_variant = $this->form_data["{$this->plugin_input_prefix}show_product_has_variant"][$key];
            $show_product_has_variant_field_options = $this->get_product_dropdown_info($show_product_has_variant, 'variant');
        }

        //Get show Product "Has attribute" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_product_has_subattribute"][$key])) {
            $show_product_has_subattribute = $this->form_data["{$this->plugin_input_prefix}show_product_has_subattribute"][$key];
            $show_product_has_subattribute_field_options = $this->get_product_dropdown_info($show_product_has_subattribute, 'attribute');
        }

        $table_section_array = array(
            'title' => __("Do not show Product", "wp-shop-page-manager-woo"),
            'type' => 'multi_column',
            'option_settings' => array(
                array(
                    'label' => __("In Category", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => false,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show product if they are in the selected category(ies)", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_product_in_category",
                        'class' =>  'wc-enhanced-select zamartz-multi-column-select'
                    ),
                    'input_value' => $show_product_in_category,
                    'field_options' => $product_categories,
                ),
                array(
                    'label' => __("Is Product", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show product if is the product(s)", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_product_is_product",
                        'class' =>  'wc-product-search zamartz-multi-column-select',
                        'data-params' => array(
                            'action'  => 'woocommerce_json_search_products',
                            'minimum_input_length'  => 4,
                        )
                    ),
                    'field_options' => $show_product_is_product_field_options,
                ),
                array(
                    'label' => __("Has Variant", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show product if it has the variant(s)", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_product_has_variant",
                        'class' =>  'zamartz-select2-search-dropdown zamartz-multi-column-select',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}product_variation",
                            'type'  => 'product_variation',
                            'minimum_input_length'  => 4,
                        ),
                    ),
                    'field_options' => $show_product_has_variant_field_options,
                ),
                array(
                    'label' => __("Has Attributes", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show product if it has the attribute(s)", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_product_has_subattribute",
                        'class' =>  'zamartz-multi-column-select zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}get_custom_select2_ajax",
                            'type'  => 'child_attributes',
                            'minimum_input_length'  => 4,
                        ),
                    ),
                    'field_options' => $show_product_has_subattribute_field_options,
                ),
                array(
                    'label' => __("Price", "wp-shop-page-manager-woo"),
                    'type' => 'multi_column',
                    'section_class' => 'zamartz-group-row',
                    'option_settings' => array(
                        array(
                            'type' => 'select',
                            'tooltip_desc' => __("Choose not to show product if it has a certain price", "wp-shop-page-manager-woo"),
                            'option_settings' => array(
                                'name' => "{$this->plugin_input_prefix}show_product_price_type",
                                'class' =>  'zamartz-multi-inner-column'
                            ),
                            'field_options' => array(
                                'less_than' => 'Less than',
                                'greater_than' => 'Greater than',
                                'equal' => 'Equal to',
                            )
                        ),
                        array(
                            'type' => 'input_number',
                            'input_value' => $this->form_data["{$this->plugin_input_prefix}show_product_price"][$key],
                            'option_settings' => array(
                                'name' => "{$this->plugin_input_prefix}show_product_price",
                                'class' =>  'zamartz-multi-inner-column zamartz-multi-inner-column-text-input',
                                'min' => 0
                            )
                        )
                    )
                )
            )
        );
        return $table_section_array;
    }

    /**
     * Get settings for "Do not show category" section
     * 
     * @since   1.0.0
     */
    public function get_shop_manager_show_category_settings($key)
    {
        //Get category listing
        $product_cat = get_terms(['taxonomy' => 'product_cat', 'parent' => 0]);
        foreach ($product_cat as $category) {
            $product_categories[$category->term_id] = $category->name;
        }

        // Initialize
        $show_category_is_category = array();
        $show_category_product_in_field_options = array();
        $show_category_has_subcategory_field_options = array();
        $show_category_has_variant_field_options = array();

        //Get show Product "Is category" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_category_is_category"][$key])) {
            $show_category_is_category = $this->form_data["{$this->plugin_input_prefix}show_category_is_category"][$key];
        }

        //Get show category "Product In" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_category_product_in"][$key])) {
            $show_category_product_in = $this->form_data["{$this->plugin_input_prefix}show_category_product_in"][$key];
            $show_category_product_in_field_options = $this->get_product_dropdown_info($show_category_product_in);
        }

        //Get subcategory listing
        if (isset($this->form_data["{$this->plugin_input_prefix}show_category_has_subcategory"][$key])) {
            $show_category_has_subcategory = $this->form_data["{$this->plugin_input_prefix}show_category_has_subcategory"][$key];
            $show_category_has_subcategory_field_options = $this->get_product_dropdown_info($show_category_has_subcategory, 'subcategory');
        }

        //Get show Product "Has variant" dropdown selected value
        if (isset($this->form_data["{$this->plugin_input_prefix}show_category_has_variant"][$key])) {
            $show_category_has_variant = $this->form_data["{$this->plugin_input_prefix}show_category_has_variant"][$key];
            $show_category_has_variant_field_options = $this->get_product_dropdown_info($show_category_has_variant, 'variant');
        }

        $table_section_array = array(
            'title' => __("Do not show Category", "wp-shop-page-manager-woo"),
            'type' => 'multi_column',
            'option_settings' => array(
                array(
                    'label' => __("Is Category", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => false,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show Category or Sub-Categories if one of the selected", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_category_is_category",
                        'class' =>  'wc-enhanced-select zamartz-multi-column-select'
                    ),
                    'input_value' => $show_category_is_category,
                    'field_options' => $product_categories,
                ),
                array(
                    'label' => __("Has Sub-Category", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show category or sub-category if has one of the selected under it", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_category_has_subcategory",
                        'class' =>  'zamartz-multi-column-select zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}get_custom_select2_ajax",
                            'type'  => 'child_categories',
                            'minimum_input_length'  => 4,
                        ),
                    ),
                    'field_options' => $show_category_has_subcategory_field_options,
                ),
                array(
                    'label' => __("Product In", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show category if has product(s) in it", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_category_product_in",
                        'class' =>  'wc-product-search zamartz-multi-column-select',
                        'data-params' => array(
                            'action'  => 'woocommerce_json_search_products',
                            'minimum_input_length'  => 4,
                        )
                    ),
                    'field_options' => $show_category_product_in_field_options,
                ),
                array(
                    'label' => __("Has Variant", "wp-shop-page-manager-woo"),
                    'type' => 'select',
                    'is_multi' => true,
                    'is_select2' => true,
                    'section_class' => 'zamartz-group-row',
                    'tooltip_desc' => __("Choose not to show product if it has the variant(s)", "wp-shop-page-manager-woo"),
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}show_category_has_variant",
                        'class' =>  'zamartz-select2-search-dropdown zamartz-multi-column-select',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}product_variation",
                            'type'  => 'product_variation',
                            'minimum_input_length'  => 4,
                        ),
                    ),
                    'field_options' => $show_category_has_variant_field_options,
                ),
                array(
                    'label' => __("Product Count", "wp-shop-page-manager-woo"),
                    'type' => 'multi_column',
                    'section_class' => 'zamartz-group-row',
                    'option_settings' => array(
                        array(
                            'type' => 'select',
                            'tooltip_desc' => __("Choose not to show category if the count of products is a certain amount", "wp-shop-page-manager-woo"),
                            'option_settings' => array(
                                'name' => "{$this->plugin_input_prefix}show_category_product_count_type",
                                'class' =>  'zamartz-multi-inner-column',
                            ),
                            'field_options' => array(
                                'less_than' => 'Less than',
                                'greater_than' => 'Greater than',
                                'equal' => 'Equal to',
                            )
                        ),
                        array(
                            'type' => 'input_number',
                            'input_value' => $this->form_data["{$this->plugin_input_prefix}show_category_product_count"][$key],
                            'option_settings' => array(
                                'name' => "{$this->plugin_input_prefix}show_category_product_count",
                                'class' =>  'zamartz-multi-inner-column zamartz-multi-inner-column-text-input',
                                'min' => 0
                            ),
                        )
                    ),
                ),
            ),
        );
        return $table_section_array;
    }

    /**
     * Generates the condition dropdown
     *
     * @since    1.0.0
     * @param   integer     $key                The index of the array
     * @return  string      $row_html  The form HTML containing the respective condition dropdown
     */
    public function get_form_conditions($key)
    {
        $selected = '';
        if (isset($this->form_data["{$this->plugin_input_prefix}conditions"][$key])) {
            $selected = $this->form_data["{$this->plugin_input_prefix}conditions"][$key];
        }

        $add_text = '';
        if ($this->plugin_api_version === 'Free') {
            $add_text = ' (Paid Condition)';
        }

        return array(
            'title' => __("Conditions", "wp-shop-page-manager-woo"),
            'tooltip_desc' => 'The condition determines what will be evaluated on the shop page or checkout to apply the above rules.',
            'type' => 'select',
            'is_multi' => false,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}conditions",
                'class' => 'wc-enhanced-select zamartz-form-conditions',
            ),
            'field_options' => array(
                'active_product_count' => __("Active Product Count on page", "wp-shop-page-manager-woo"),
                'available_product_count' => __("Available Product Count on page" . $add_text, "wp-shop-page-manager-woo"),
                'order_subtotal' => __("Order SubTotal" . $add_text, "wp-shop-page-manager-woo"),
                'customer_roles' => __("Customer Roles" . $add_text, "wp-shop-page-manager-woo"),
                'product_in_cart' => __("Product In Cart" . $add_text, "wp-shop-page-manager-woo"),
                'product_variations' => __("Product Variations in Cart" . $add_text, "wp-shop-page-manager-woo"),
                'product_categories' => __("Product Categories in Cart" . $add_text, "wp-shop-page-manager-woo"),
                'coupon_applied' => __("Coupon is Applied" . $add_text, "wp-shop-page-manager-woo"),
                'total_quantity' => __("Total QTY in Cart" . $add_text, "wp-shop-page-manager-woo"),
            ),
            'input_value' => $selected,
            'section_class' => 'zamartz-bordered'
        );
    }

    /**
     * Generates the operator dropdown
     *
     * @since    1.0.0
     * @param   integer     $key                The index of the array
     * @param   string      $form_condition Existing value of the condition dropdown
     * @return  string      $row_html  The form HTML of the generated operator dropdown
     */
    public function get_form_operator_dropdown($key = 0, $form_condition = '')
    {
        //Get form condition and operator information
        $selected = '';
        if (empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}conditions"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}operator"][$key])) {
            $form_condition = $this->form_data["{$this->plugin_input_prefix}conditions"][$key];
            $selected = $this->form_data["{$this->plugin_input_prefix}operator"][$key];
        }
        //Operator ">,<,>=,<=,==,<>"
        $form_condition_operators = array(
            'active_product_count',
            'available_product_count',
            'order_subtotal',
            'total_quantity'

        );

        //Operator "Contains, Does NOT Contain"
        $form_condition_contains = array(
            'product_in_cart',
            'product_variations',
            'product_categories',
            'coupon_applied'

        );
        if (in_array($form_condition, $form_condition_operators)) {
            return $this->get_form_operator_field_operators($selected, $key);
        } elseif ($form_condition == 'customer_roles') {
            return $this->get_form_operator_field_is_isnot($selected, $key);
        } elseif (in_array($form_condition, $form_condition_contains)) {
            return $this->get_form_operator_field_contains($selected, $key);
        } else {
            return $this->get_form_operator_field_operators('', $key);
        }
    }

    /**
     * An AJAX function to generate the operator dropdown after the condition 
     * dropdown option is changed.
     *
     * @since    1.0.0
     */
    public function woo_shop_manager_get_form_operator_dropdown_ajax()
    {
        $selected_condition = filter_input(INPUT_POST, 'selected_condition', FILTER_SANITIZE_STRING);
        $key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_NUMBER_INT);

        $settings_operator = $this->get_form_operator_dropdown($key, $selected_condition);
        ob_start();
        $this->get_field_settings($settings_operator, true);
        $form_operator_dropdown = ob_get_clean();

        $settings_subfield = $this->get_form_condition_subfield($key, $selected_condition);
        ob_start();
        $this->get_field_settings($settings_subfield, true);
        $form_condition_subfield = ob_get_clean();

        echo json_encode(array(
            'form_operator_dropdown' => $form_operator_dropdown,
            'form_condition_subfield' => $form_condition_subfield
        ));
        die();
    }

    /**
     * Generates a dropdown if the condition selected is for "operator" field
     * 
     * @since   1.0.0
     * @param   string      $selected   The current selected option value.
     * @param   integer     $key        The index of the array.
     * @return  string      $row_html  The form HTML for the operator dropdown
     */
    public function get_form_operator_field_operators($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-shop-page-manager-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'input_value' => $selected,
            'key' => $key,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'less_than' => __("Less Than", "wp-shop-page-manager-woo"),
                'greater_than' => __("Greater Than", "wp-shop-page-manager-woo"),
                'less_than_equal' => __("Less Than Equal To", "wp-shop-page-manager-woo"),
                'greater_than_equal' => __("Greater Than Equal To", "wp-shop-page-manager-woo"),
                'equal' => __("Equal To", "wp-shop-page-manager-woo"),
                'not_equal_to' => __("Not Equal To", "wp-shop-page-manager-woo"),
            )
        );
    }

    /**
     * Generates the "Is"/"Is not" dropdown based on selected condition
     *
     * @since    1.0.0
     * @param   string  $selected           The pre-defined value of the operator.
     * @param   integer $key                The index of the array.
     * @return  string  $row_html  The html of the dropdown
     */
    public function get_form_operator_field_is_isnot($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-shop-page-manager-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'key' => $key,
            'input_value' => $selected,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'is' => __("Is", "wp-shop-page-manager-woo"),
                'is_not' => __("Is NOT", "wp-shop-page-manager-woo"),
            )
        );
    }

    /**
     * Generates the "Contain"/"Does not contain" dropdown based on the selected condition
     *
     * @since    1.0.0
     * @param   string  $selected   The pre-defined value of the operator.
     * @param   integer $key        The index of the array.
     * @return  string  $row_html  The html of the dropdown.
     */
    public function get_form_operator_field_contains($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-shop-page-manager-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'key' => $key,
            'input_value' => $selected,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'contains' => __("Contains", "wp-shop-page-manager-woo"),
                'not_contain' => __("Does NOT contain", "wp-shop-page-manager-woo"),
            )
        );
    }

    /**
     * Generates the subfield option based on the selected condition and operator
     *
     * @since    1.0.0
     * @param   integer   $key                  The index of the array.
     * @param   string    $form_condition   The currently selected condition.
     * @return  string    $row_html    The html of the dropdown.
     */
    public function get_form_condition_subfield($key = 0, $form_condition = '')
    {

        $subfield_value = '';
        if (empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}conditions"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}operator"][$key])) {
            $form_condition = $this->form_data["{$this->plugin_input_prefix}conditions"][$key];
        }
        if (!empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}condition_subfield"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}operator"][$key])) {
            $subfield_value = $this->form_data["{$this->plugin_input_prefix}condition_subfield"][$key];
        }
        switch ($form_condition) {
            case 'active_product_count':
                $subfield_text = 'Active Product Count on page';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the total quantity of all product that are active in the category", "wp-shop-page-manager-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => ($subfield_value == '' ? 0 : $subfield_value),
                );
                if ($this->plugin_api_version === 'Free') {
                    $settings['option_settings']['is_required'] = true;
                }
                break;
            case 'available_product_count':
                $subfield_text = 'Available Product Count on page';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the total quantity of all product that are active in the category that also have inventory", "wp-shop-page-manager-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'order_subtotal':
                $subfield_text = 'Order Subtotal';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the Orders Subtotal before taxes, shipping and fees are applied.", "wp-shop-page-manager-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'customer_roles':
                $subfield_text = 'Customer Roles';
                global $wp_roles;
                $roles = $wp_roles->get_names();
                $settings = array(
                    'type' => 'select',
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the customer role that is applied to the customer or user. This is using OR logic.", "wp-shop-page-manager-woo"),
                    'is_multi' => true,
                    'is_select2' => false,
                    'input_value' => $subfield_value,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'class' => 'wc-enhanced-select',
                    ),
                    'field_options' => array()
                );
                foreach ($roles as $role_slug => $role_name) {
                    $settings['field_options'][$role_slug] = $role_name;
                }
                break;
            case 'product_in_cart':
                $subfield_text = 'Product in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Parent Level product added to the cart. This is using OR logic.", "wp-shop-page-manager-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'class' => 'wc-product-search',
                        'data-params' => array(
                            'action'  => 'woocommerce_json_search_products',
                            'minimum_input_length'  => 4,
                        )
                    ),
                    'field_options' => array()
                );
                if (!empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        $title = get_the_title($subfield_id);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            case 'product_variations':
                $subfield_text = 'Product Variations in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Child Level product added to the cart. This is using OR logic.", "wp-shop-page-manager-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'class' => 'zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}product_variation",
                            'type'  => 'product_variation',
                            'minimum_input_length'  => 4,
                        ),
                    ),
                    'field_options' => array()
                );
                if (is_array($subfield_value) && !empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        $product = get_post($subfield_id); //get_product_variation_title
                        $title = $this->get_product_variation_title($product);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            case 'product_categories':
                $subfield_text = 'Product Categories in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Product Categories in cart. This is using OR logic.", "wp-shop-page-manager-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => false,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'class' => 'wc-enhanced-select',
                    ),
                    'field_options' => array(),
                    'input_value' => $subfield_value
                );
                $categories = get_terms(['taxonomy' => 'product_cat']);
                foreach ($categories as $category) {
                    $settings['field_options'][$category->term_id] = $category->name;
                }
                break;
            case 'coupon_applied':
                $subfield_text = 'Coupon is Applied';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Coupon applied to the order. This is using OR logic.", "wp-shop-page-manager-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'class' => 'zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}coupon_is_applied",
                            'type'  => 'shop_coupon',
                            'minimum_input_length'  => 4
                        ),
                    ),
                    'field_options' => array()
                );
                if (is_array($subfield_value) && !empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        if (empty($subfield_id)) {
                            continue;
                        }
                        $title = get_the_title($subfield_id);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            case 'total_quantity':
                $subfield_text = 'Total QTY in Cart';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the total quantity of all product lines in the cart", "wp-shop-page-manager-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            default:
                $subfield_text = 'Active Product Count on page';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the total quantity of all product that are active in the category", "wp-shop-page-manager-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
        }
        $settings['title'] = __($subfield_text, "wp-shop-page-manager-woo");
        return $settings;
    }

    /**
     * Generate the title of product variation (single|multi).
     * 
     * @since    1.0.0
     * @param    object   $product    The post data of the current form condition selection.
     * @return   string   Return the generated title of the product variation
     */
    private function get_product_variation_title($product)
    {
        $wc_product = wc_get_product($product->ID);
        if (empty($wc_product)) {
            return;
        }
        $available_variations = $wc_product->get_attributes();
        //If product has multiple attributes for each variation
        if (count($available_variations) > 1) {
            $formatted_variation = [];
            foreach ($available_variations as $name => $value) {
                $term = get_term_by('slug', $value, $name);
                $formatted_variation[] = $term->name;
            }
            return $product->post_title . ' - ' . implode(', ', $formatted_variation) . ' (#' . $product->ID . ')';
        } else {
            return $product->post_title . ' (#' . $product->ID . ')';
        }
    }

    /**
     * Function retrieves the select2 dropdown list via ajax search for the selected form condition type
     * 
     * @since    1.0.0
     */
    public function woo_shop_manager_get_select2_dropdown_ajax()
    {

        $post_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);

        if (empty($term)) {
            wp_die();
        }

        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        if (empty($limit)) {
            $limit = 5;
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
        if (empty($page)) {
            $page = 1;
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => ($page - 1) * $limit,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $args['search_prod_title'] = $term;

        $search_results = new WP_Query($args);
        $query_data = [];
        foreach ($search_results->posts as $key => $product) {

            if ($post_type == 'product_variation') {
                $query_data[$key]['text'] = $this->get_product_variation_title($product);
            } else {
                $query_data[$key]['text'] = $product->post_title;
            }

            // Get children product variation IDs in an array
            $query_data[$key]['id'] = $product->ID;
        }

        $total = $search_results->found_posts;
        $more = $page * $limit < $total;

        echo json_encode(array('query_data' => $query_data, 'pagination' => array("more" => $more)));
        die();
    }

    /**
     * Add to post query based on the selected WP Query criteria.
     * See woo_shop_manager_get_select2_dropdown_ajax().
     * 
     * @since    1.0.0
     * @param   string  $where      The WHERE clause of the query
     * @param   object  $wp_query   The instance of WP_Query class
     * @return  string  $where      The updated WHERE clause of the query
     */
    public function woo_shop_manager_set_post_where_wp_query($where, $wp_query)
    {
        global $wpdb;
        if ($search_term = $wp_query->get('search_prod_title')) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql($wpdb->esc_like($search_term)) . '%\'';
        } elseif ($search_term = $wp_query->get('search_prod_excerpt')) {
            $where .= ' AND ' . $wpdb->posts . '.post_excerpt LIKE \'%' . esc_sql($wpdb->esc_like($search_term)) . '%\'';
        }
        return $where;
    }

    /**
     * Function retrieves the select2 dropdown list via ajax search based on the define "type"
     * 
     * @since    1.0.0
     */
    public function get_custom_select2_dropdown_ajax()
    {
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $term_name = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);

        if (empty($term_name)) {
            wp_die();
        }
        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        if (empty($limit)) {
            $limit = 5;
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
        if (empty($page)) {
            $page = 1;
        }

        $search_args = [];
        $total = 0;

        if ($type == 'child_categories') {
            //Get all categories
            $cat_args = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => 0
            );
            $categories  = get_terms($cat_args);

            //Define exlusion list for all categories that are parent
            $exclusion_list = array();
            foreach ($categories as $category) {
                $exclusion_list[] = $category->term_id;
            }

            //Get all categories with term name and excluded categories
            $search_args = [
                'hide_empty' => false,
                'taxonomy' => 'product_cat',
                'name__like' => $term_name,
                'exclude' => $exclusion_list,
                'number' => $limit,
                'offset' => ($page - 1) * $limit,
            ];

            $total = wp_count_terms(
                'product_cat',
                array(
                    'hide_empty' => false,
                    'name__like' => $term_name,
                    'exclude' => $exclusion_list,
                )

            );
        } elseif ($type == 'child_attributes') {
            global $wc_product_attributes;
            $taxonomy_name_array = [];
            foreach ($wc_product_attributes as $parent_attribute_name => $parent_attribute_data) {
                $taxonomy_name = $parent_attribute_name;
                $taxonomy_name_array[] = $taxonomy_name;
            }
            if (!empty($taxonomy_name_array)) {
                $search_args = array(
                    'hide_empty' => false,
                    'taxonomy' => $taxonomy_name_array,
                    'name__like' => $term_name,
                    'number' => $limit,
                    'offset' => ($page - 1) * $limit,
                );
                $total = wp_count_terms(
                    $taxonomy_name_array,
                    array(
                        'hide_empty' => false,
                        'taxonomy' => $taxonomy_name_array,
                        'name__like' => $term_name,
                    )

                );
            }
        }
        if (!empty($search_args)) {

            $search_results  = new WP_Term_Query($search_args);
            $query_data = [];
            if (!empty($search_results->terms)) {
                $i = 0;
                foreach ($search_results->terms as $terms) {
                    $query_data[$i]['id'] = $terms->term_id;
                    $taxonomy_details = get_taxonomy($terms->taxonomy);
                    if ($type == 'child_attributes' && !empty($taxonomy_details->labels->singular_name)) {
                        $query_data[$i]['text'] = $taxonomy_details->labels->singular_name . ' - ' . $terms->name;
                    } else {
                        $query_data[$i]['text'] = $terms->name;
                    }
                    $i++;
                }
            }
            $more = $page * $limit < $total;
        }

        echo json_encode(array('query_data' => $query_data, 'pagination' => array("more" => $more)));
        die();
    }

    /**
     * Generates the rule set priority section and field
     *
     * @since    1.0.0
     * @param   integer  $key                 The index of the array.
     * @return  string   $row_html   HTML of the current input field row.
     */
    public function get_form_rule_set_priority($key)
    {

        $ruleset_value = '';
        if (!empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}rule_set_priority"][$key])) {
            $ruleset_value = $this->form_data["{$this->plugin_input_prefix}rule_set_priority"][$key];
        }

        return array(
            'key' => $key,
            'title' =>  __("Rule Set Priority", "wp-shop-page-manager-woo"),
            'tooltip_desc' => "When use the Rule Set Priority will allow you to reorder the Rules you have created. Rule set priority must be set to a numeric value, the first used being '0' all rules not set will follow the order they were created.",
            'type' => 'input_number',
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}rule_set_priority",
                'class' => 'zamartz-rule-set-priority shop-manager-paid-feature',
                'min' => 0
            ),
            'input_value' => $ruleset_value,
        );
    }

    /**
     * Generates the rule set toggle switch
     *
     * @since    1.0.0
     * @param   integer  $key                 The index of the array.
     * @return  string   $row_html   HTML of the current button row.
     */
    public function get_form_stop_other_rules($key)
    {
        $rulebtn_value = 'no';
        if (!empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}rule_toggle"][$key])) {
            $rulebtn_value = $this->form_data["{$this->plugin_input_prefix}rule_toggle"][$key];
        }

        return array(
            'key' => $key,
            'title' =>  __("Stop other rules", "wp-shop-page-manager-woo"),
            'tooltip_desc' => 'When selected this will prevent any other rule-sets from applying if this one is applicable. If this is set on multiple rule-sets the one higher in the list will take precedence.',
            'type' => 'toggle_switch',
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}rule_toggle",
                'class' =>  'shop-manager-paid-feature paid-feature-parent'
            ),
            'input_value' => $rulebtn_value
        );
    }

    /**
     * Ajax functionality to populate the an accordion section after the rule set
     * button is clicked.
     * 
     * @since    1.0.0
     */
    public function woo_shop_manager_get_form_section_ajax()
    {
        //Add logic to check license key
        if ($this->plugin_api_version === 'Free') {
            echo json_encode(
                array(
                    'status' => false,
                    'message' => __("Error: Use with Paid Version Only", "wp-shop-page-manager-woo"),
                )
            );
            die();
        }

        if ($this->section_type == null) {
            $section_type = filter_input(INPUT_POST, 'section_type', FILTER_SANITIZE_STRING);
            $this->section_type = $section_type;
        }
        $key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);

        $key = !empty($key) ? ($key + 1) : 1;
        ob_start();
        $this->wp_shop_page_manager_get_form_section($key, 2);
        $html = ob_get_clean();
        echo json_encode(
            array(
                'status' => true,
                'message' => $html
            )
        );
        die();
    }

    /**
     * Generates the html to display on shop page manager section based on the defined form data.
     *
     * @since   1.0.0
     */
    public function wp_shop_page_manager_generate_html()
    {
        if (!empty($this->form_data) && !empty($this->form_data["{$this->plugin_input_prefix}show_product_in_category"])) {
            $loop = 1;
            foreach ($this->form_data["{$this->plugin_input_prefix}show_product_in_category"] as $key => $value) {
                $this->wp_shop_page_manager_get_form_section($key, $loop);
                $loop++;
            }
        } else {
            $this->wp_shop_page_manager_get_form_section(1);
        }

        $settings = array(
            'type' => 'button',
            'input_value' => __("Add rule set", "wp-shop-page-manager-woo"),
            'option_settings' => array(
                'class' => 'zamartz-add-rule-set',
                'is_spinner_dashicon' => true,
                'wrapper' => array(
                    'class' => 'zamartz-add-rule-set-wrapper'
                ),
            )
        );
        $this->get_field_settings($settings);
    }

    /**
     * Function defines the settings to populate plugin status information on sidebar
     *
     * @since   1.0.0
     */
    public function wp_shop_page_manager_get_sidebar_section()
    {
        $table_section_array =
            array(
                'row_data' => array(
                    array(
                        'data' => array(
                            __("Version", "wp-shop-page-manager-woo"),
                            $this->plugin_api_version
                        ),
                        'tabindex' => 0
                    ),
                    array(
                        'data' => array(
                            __("Authorization", "wp-shop-page-manager-woo"),
                            $this->plugin_api_authorization
                        ),
                        'tabindex' => 0
                    ),
                ),
                'row_footer' => array(
                    'is_link' => array(
                        'link' => admin_url() . 'admin.php?page=zamartz-settings&tab=addons&section=' . $this->get_plugin_section_slug(),
                        'title' => __("Settings", "wp-shop-page-manager-woo"),
                        'class' => ''
                    ),
                    'is_button' => array(
                        'name' => 'save',
                        'type' => 'submit',
                        'action' => "{$this->plugin_input_prefix}save_form_data_ajax",
                        'class' => 'button button-primary button-large',
                        'value' => __("Save changes", "wp-shop-page-manager-woo"),
                    )
                ),
                'nonce' => wp_nonce_field('zamartz-settings', 'zamartz_settings_nonce', true, false)
            );
        $accordion_settings = array(
            'title' => __("Shop Page Manager Updates", "wp-shop-page-manager-woo"),
            'type' => 'save_footer',
            'accordion_class' => 'zamartz-accordion-sidebar',
            'form_section_data' => array(
                'toggle' => 'affix',
                'custom-affix-height' => '88'
            ),
        );

        $this->generate_accordion_html($accordion_settings, $table_section_array);
    }

    /**
     * Get product information
     * @since   1.0.0
     */
    public function get_product_dropdown_info($data, $type = 'product')
    {
        $info = array();
        if (!empty($data) && $data != '') {
            foreach ($data as $subfield_id) {
                if ($subfield_id == '') {
                    continue;
                }
                if ($type == 'product') {
                    $title = get_the_title($subfield_id);
                } elseif ($type == 'subcategory') {
                    $category = get_term($subfield_id);
                    $title = $category->name;
                } elseif ($type == 'attribute') {
                    $attribute = get_term($subfield_id);
                    $taxonomy_details = get_taxonomy($attribute->taxonomy);
                    $title = $attribute->name;
                    $taxonomy_name = $taxonomy_details->labels->singular_name;
                } else {
                    $product = get_post($subfield_id); //get_product_variation_title
                    $title = $this->get_product_variation_title($product);
                }

                // Show attribute names based on term

                if ($type == 'attribute' && !empty($taxonomy_name)) {
                    $info[$subfield_id] = $taxonomy_name . ' - ' . $title . ' (#' . $subfield_id . ')';
                } else {
                    $info[$subfield_id] = $title . ' (#' . $subfield_id . ')';
                }
            }
        }
        return $info;
    }

    /**
     * Define the settings that needs to be displayed for shop page manager section.
     *
     * @since   1.0.0
     * @param   array   $settings           Array of data for form settings to be generated by WooCommerce.
     * @param   string  $current_section    Current WooCommerce section
     * @return  array
     */
    public function render_customizer_partial()
    {
        //Initialize form generation data
        $settings_shop_page_manager = array();
        $this->section_type = "";
        $this->wp_shop_page_manager_set_form_data();

        require_once $this->plugin_url['admin_path'] . '/partials/wp-shop-page-manager-woo-admin-customizer-display.php';
    }

    /**
     * Ajax functionality to validate form nonce and saving the form into the options table. 
     * Return respective status, message and class
     *
     * @since    1.0.0
     */
    public function woo_shop_manager_save_form_data_ajax()
    {
        $form_data = filter_input(INPUT_POST, 'form_data', FILTER_SANITIZE_STRING);
        parse_str($form_data, $postArray);

        if (!wp_verify_nonce(wp_unslash($postArray['zamartz_settings_nonce']), 'zamartz-settings')) {
            echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
            die();
        }
        global $wpdb;

        $error = false;
        $message = 'Your settings have been saved.';
        $class = 'updated inline';
        $is_active_product_count = false;

        foreach ($postArray as $key => $data) {

            if (empty($key) || strpos($key, $this->plugin_input_prefix) === false) {
                continue;
            }

            // if free version and selected value is 'active_product_count'

            if ($this->plugin_api_version === 'Free') {
                foreach ($data as $id => $value) {
                    if ($key == $this->plugin_input_prefix . 'conditions' && $value == 'active_product_count') {
                        $is_active_product_count = true;
                    }
                    break;
                }
            }

            if (!$is_active_product_count && (!empty($this->ignore_list) && in_array($key, $this->ignore_list))) {
                continue;
            }

            if ($this->plugin_api_version === 'Free' && $key == $this->plugin_input_prefix . 'condition_subfield') {
                $is_active_product_count = false;
            }

            if (strpos($key, $this->plugin_input_prefix . '__remove__') !== false) {
                $key = str_replace($this->plugin_input_prefix . '__remove__', '', $key);
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
            if (!empty($wpdb->last_error)) {
                $error = true;
                $key_name = ucfirst(str_replace(array("{$this->plugin_input_prefix}", '_'), array('', ' '), $key));
                $message = 'There was a problem while updating the option for "' . $key_name . '"';
                $class = 'error inline';
                break;
            }
        }

        echo json_encode(
            array(
                'status' => !$error,
                'message' => '<p><strong>' . $message . '</strong></p>',
                'class' => $class
            )
        );
        die();
    }

    /**
     * Define ignore list to restrict users from updating paid feature settings
     */
    public function woo_shop_manager_set_ignore_list()
    {
        //Set ignore list for paid features
        if ($this->plugin_api_version === 'Free') {

            $this->ignore_list[] = "{$this->plugin_input_prefix}conditions";
            $this->ignore_list[] = "{$this->plugin_input_prefix}operator";
            $this->ignore_list[] = "{$this->plugin_input_prefix}condition_subfield";

            $this->ignore_list[] = "{$this->plugin_input_prefix}rule_set_priority";
            $this->ignore_list[] = "{$this->plugin_input_prefix}rule_toggle";
        }
    }
}
