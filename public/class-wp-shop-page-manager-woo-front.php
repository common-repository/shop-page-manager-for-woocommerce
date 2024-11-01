<?php

/**
 * The class is responsible for adding sections inside the WooCommerce settings page.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/public
 */

/**
 * Functionality for front-end shop page to hide/display based on defined
 * admin ruleset settings
 *
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/public
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Wp_Shop_Page_Manager_Woo_Front
{
    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     */
    use Zamartz_General;

    /**
     * Form settings data
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $form_data    Saves the data for our respective section form.
     */
    private $form_data;

    /**
     * Form data based on ruleset as 'key' and price type & price as 'value'
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $price_filter_ruleset    Price data based on ruleset key.
     */
    private $price_filter_ruleset = [];

    /**
     * Exlude the category(s) from shop page if array has category id.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $exclude_category_ids    Array of category ids.
     */
    public $exclude_category_ids = [];

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct($core_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($core_instance);

        //Set plugin paid vs free information
        $this->set_plugin_api_data();

        $ruleset_toggle = get_option("{$this->plugin_input_prefix}ruleset_toggle");
        if ($ruleset_toggle === 'yes') {
            //Setup functionality
            add_action('woocommerce_product_query', array($this, 'init_ruleset'), 20, 1);
            add_action('customize_preview_init', array($this, 'init_customizer'), 20, 1);
        }
    }

    public function init_customizer($object)
    {
        $setting = $object->unsanitized_post_values();
        
        if( sizeof( $setting ) > 0 ) {
            $form_data = $setting['zamartz_shop_page_manager_rulesets'];
            parse_str($form_data, $this->form_data);
            global $wp_query;
            $this->init_ruleset($wp_query);
        }
    }

    /**
     * Retrieves the data from database and sets the form data.
     *
     * @since   1.0.0
     */
    private function wp_shop_page_manager_set_form_data()
    {

        $this->form_data = []; //clear data
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
     * Get the stauts of the plugin. Check if plugin is paid or free.
     */
    public function get_plugin_status()
    {
        $api_license_key = get_option("{$this->plugin_input_prefix}api_license_key");
        $api_get_response = get_option("{$this->plugin_input_prefix}api_get_response");
        if (!empty($api_license_key) && !empty($api_get_response) && isset($api_get_response->activated) && $api_get_response->activated === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Toggle hide/show product and category items on shop and category page.
     * Function responsible for
     * ** Checking if plugin is paid/free,
     * ** Testing if ruleset condition is true
     * ** Applying ruleset logic of 'Do not show product(s)' and 'Do not show category(s)'
     * 
     * @since    1.0.0
     */
    public function init_ruleset($wp_query_obj)
    {
        if (empty($this->form_data)){
            $this->wp_shop_page_manager_set_form_data();
        }

        $plugin_status = $this->get_plugin_status();

        if ($plugin_status === false) {

            $show_product_in_category = $this->form_data["{$this->plugin_input_prefix}show_product_in_category"];

            //Is settings empty?
            if ($show_product_in_category == false) {
                return;
            }

            //Get ruleset index of first ruleset
            $array_key_list = array_keys($show_product_in_category);
            $ruleset_index = $array_key_list[0];

            //Get condition
            $condition = $this->form_data["{$this->plugin_input_prefix}conditions"][$ruleset_index];

            //If condition is not the first one
            if ('active_product_count' !== $condition) {
                return;
            }

            $is_condition_true = $this->ruleset_condition_check($ruleset_index, $wp_query_obj);
            if ($is_condition_true == true) {
                //Apply ruleset logic for respective section
                $this->set_ruleset_condition($wp_query_obj, $ruleset_index);
                $this->set_exclude_category($ruleset_index);
            }
            return; //Either the conditions is not the free one or the is_condition_true failed
        }

        $_rule_set_priority =  $this->form_data["{$this->plugin_input_prefix}rule_set_priority"];

        if ($_rule_set_priority == false) {
            return;
        }

        //Define order in which the ruleset accordion are run - works in paid version of the plugin
        $rule_set_priority = $this->sort_key_by_value_ascending($_rule_set_priority);

        foreach ($rule_set_priority as $ruleset_index => $value) {
            $break_loop = false;
            $rule_toggle = $this->form_data["{$this->plugin_input_prefix}rule_toggle"][$ruleset_index];
            $condition_subfield = $this->form_data["{$this->plugin_input_prefix}condition_subfield"][$ruleset_index];

            if ($rule_toggle == 'yes' && $condition_subfield != '') {
                // Stop other rules if current is toggled as yes
                $break_loop = true;
            } elseif ($condition_subfield == '') {
                continue;
            }

            //Check if current condition matches
            $is_condition_true = $this->ruleset_condition_check($ruleset_index, $wp_query_obj);

            if ($is_condition_true === false && $break_loop !== true) {
                continue;
            } elseif ($is_condition_true === false && $break_loop === true) {
                break;
            }

            //Condition is true, apply functionality
            $this->set_ruleset_condition($wp_query_obj, $ruleset_index);
            $this->set_exclude_category($ruleset_index);

            if ($break_loop) {
                break;
            }
        }

        //Return fields
        return;
    }

    /**
     * Check if ruleset condition is true/false for the respective accordion and condition case
     * 
     * @since   1.0.0
     */
    public function ruleset_condition_check($ruleset_index, $wp_query_obj)
    {
        // Check if operator or condition subfield are empty
        $operator = $this->form_data["{$this->plugin_input_prefix}operator"][$ruleset_index];
        $condition_subfield = $this->form_data["{$this->plugin_input_prefix}condition_subfield"][$ruleset_index];
        if ($operator == '' || $condition_subfield == '') {
            return false;   //Condition failed
        }

        global $woocommerce;
        $condition = $this->form_data["{$this->plugin_input_prefix}conditions"][$ruleset_index];

        $query_vars = $wp_query_obj->query_vars;
        $args['post_type'] = 'product';
        $args['post_status'] = 'publish';
        
        if( $query_vars != NULL ) {
            $args['tax_query'] = $query_vars['tax_query'];
            $args['meta_query'] = $query_vars['meta_query'];
        }

        $return = false;
        switch ($condition) {
            case 'active_product_count':
                //Case 1: Active Product count on page
                $product_data = new WP_Query($args);
                $product_count = $product_data->found_posts;
                $return = $this->check_quantity_condition($product_count, $operator, $condition_subfield);
                break;
            case 'available_product_count':
                //Case 2: Available Product count on page
                $args['meta_query']['relation'] = 'OR';
                //Get all products that are in stock
                $args['meta_query'][] = array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                );
                //Get all products that have stock available in back order
                $args['meta_query'][] = array(
                    'key' => '_stock_status',
                    'value' => 'onbackorder'
                );
                //Get all products that are only availble in back order
                $args['meta_query'][] = array(
                    'key' => '_backorders',
                    'value' => 'yes'
                );
                $product_data = new WP_Query($args);
                $product_count = $product_data->found_posts;
                $return = $this->check_quantity_condition($product_count, $operator, $condition_subfield);
                break;
            case 'order_subtotal':
                //Case 3: Order sub-total
                $quantity = $woocommerce->cart->get_cart_subtotal();
                $return = $this->check_quantity_condition($quantity, $operator, $condition_subfield);
                break;
            case 'customer_roles':
                //Case 4: Customer roles
                $return = $this->check_cart_data($woocommerce, $condition, $operator, $condition_subfield);
                break;
            case 'product_in_cart':
                //Case 5: Product in cart
                $return = $this->check_cart_data($woocommerce, $condition, $operator, $condition_subfield);
                break;
            case 'product_variations':
                //Case 6: Product variations in cart
                $return = $this->check_cart_data($woocommerce, $condition, $operator, $condition_subfield);
                break;
            case 'product_categories':
                //Case 7: Product categories in cart
                $return = $this->check_cart_data($woocommerce, $condition, $operator, $condition_subfield);
                break;
            case 'coupon_applied':
                //Case 8: Coupon is applied
                $return = $this->check_cart_data($woocommerce, $condition, $operator, $condition_subfield);
                break;
            case 'total_quantity':
                //Case 9: Total QTY in cart
                $quantity = $woocommerce->cart->cart_contents_count;
                $return = $this->check_quantity_condition($quantity, $operator, $condition_subfield);
                break;
        }
        return $return;
    }

    /**
     * Return boolean based on selected operator and value
     */
    public function check_quantity_condition($quantity, $operator, $condition_subfield)
    {
        //Remove any symbols prior to actual value
        $quantity = floatval(preg_replace('#[^\d.]#', '', $quantity));

        //Operator based conditional check
        if ($operator == 'less_than') {
            return ($quantity < $condition_subfield);
        } elseif ($operator == 'greater_than') {
            return ($quantity > $condition_subfield);
        } elseif ($operator == 'less_than_equal') {
            return ($quantity <= $condition_subfield);
        } elseif ($operator == 'greater_than_equal') {
            return ($quantity >= $condition_subfield);
        } elseif ($operator == 'equal') {
            return ($quantity == $condition_subfield);
        } else {
            return false;
        }
    }

    /**
     * Check if data exists in cart. Return list of ids if cart data exists
     * 
     * @see cart_is_id_exists()
     * @since   1.0.0
     */
    public function check_cart_data($woocommerce, $condition, $operator, $condition_subfield)
    {
        $cart_data = $woocommerce->cart->get_cart();
        if (!empty($cart_data)) {
            return $this->cart_is_id_exists($woocommerce, $cart_data, $condition, $operator, $condition_subfield);
        } else {
            return false;
        }
    }

    /**
     * Check if id exists in the cart. ID can be either product, variation, category or coupon
     * 
     * @since   1.0.0
     */
    public function cart_is_id_exists($woocommerce, $cart_data, $condition, $operator, $condition_subfield)
    {
        $ids_in_cart = $this->get_cart_id_array($woocommerce, $cart_data, $condition);
        foreach ($condition_subfield as $id) {
            if (($operator === 'contains' || $operator === 'is') && in_array($id, $ids_in_cart)) {
                return true;
            } elseif (($operator === 'not_contain' || $operator === 'is_not') && in_array($id, $ids_in_cart)) {
                return false;
            }
        }
        if ($operator === 'contains' || $operator === 'is') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks cart and creates an array with list of all existing ids based on condition type
     * 
     * @since   1.0.0
     */
    public function get_cart_id_array($woocommerce, $cart_data, $condition_type)
    {
        $id_array_list = [];
        switch ($condition_type) {
            case 'product_in_cart':
                foreach ($cart_data as $cart) {
                    $product_id = $cart['product_id'];
                    if (!in_array($product_id, $id_array_list)) {
                        $id_array_list[] = $product_id;
                    }
                }
                break;
            case 'product_variations':
                foreach ($cart_data as $cart) {
                    $variation_id = $cart['variation_id'];
                    if ($variation_id !== 0 && !in_array($variation_id, $id_array_list)) {
                        $id_array_list[] = $variation_id;
                    }
                }
                break;
            case 'product_categories':
                foreach ($cart_data as $cart) {
                    $product_id = $cart['product_id'];
                    $product_category_array = get_the_terms($product_id, 'product_cat');
                    foreach ($product_category_array as $category) {
                        if (!in_array($category->term_id, $id_array_list)) {
                            $id_array_list[] = $category->term_id;
                        }
                    }
                }
                break;
            case 'coupon_applied':
                $coupons = $woocommerce->cart->get_applied_coupons();
                foreach ($coupons as $coupon_code) {
                    $id_array_list[] = wc_get_coupon_id_by_code($coupon_code);
                }
                break;
            case 'customer_roles':
                if (is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $id_array_list = (array) $user->roles;
                } else {
                    $id_array_list = array();
                }
                break;
        }
        return $id_array_list;
    }

    /**
     * Sort in ascending order of value, move empty value at the end of array
     * 
     * @since    1.0.0
     */
    public function sort_key_by_value_ascending($to_sort)
    {
        if (empty($to_sort)) return $to_sort;
        $empty_value_array = array();
        asort($to_sort); //Sort by value in ascending order
        foreach ($to_sort as $key => $value) {
            if (empty($value) && $value !== '0') {
                $empty_value_array[$key] = $value;
                unset($to_sort[$key]);
            }
        }
        return ($to_sort + $empty_value_array);
    }

    /**
     * Function is access if ruleset condition is true and logic needs to be applied
     * 
     * @see init_ruleset()
     * @since   1.0.0
     */
    public function set_ruleset_condition($wp_query_obj, $ruleset_index)
    {
        /**
         * Do not show Product section
         * Set conditional logic for 'In Category', 'Is Product', 'Has Variant', 'Has Attributes',
         * and 'Product Price'
         */
        $product_id_array = [];
        
        $show_product_in_category = [];
        $show_product_is_product = [];
        $show_product_has_variant = [];
        $show_product_has_subattribute = [];
        $show_product_price_type = [];
        $show_product_price = [];
        
        
        
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_in_category", $this->form_data ) ) {
            $show_product_in_category = $this->form_data["{$this->plugin_input_prefix}show_product_in_category"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_is_product", $this->form_data ) ) {
            $show_product_is_product = $this->form_data["{$this->plugin_input_prefix}show_product_is_product"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_has_variant", $this->form_data ) ) {
            $show_product_has_variant = $this->form_data["{$this->plugin_input_prefix}show_product_has_variant"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_has_subattribute", $this->form_data ) ) {
            $show_product_has_subattribute = $this->form_data["{$this->plugin_input_prefix}show_product_has_subattribute"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_price_type", $this->form_data ) ) {
            $show_product_price_type = $this->form_data["{$this->plugin_input_prefix}show_product_price_type"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_product_price", $this->form_data ) ) {
            $show_product_price = $this->form_data["{$this->plugin_input_prefix}show_product_price"][$ruleset_index];
        }
        
        /* Do not display products in the following categories on the shop page. */
        if ($show_product_in_category != '' && is_array($show_product_in_category)) {
            $tax_query = (array) $wp_query_obj->get('tax_query');

            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field' => 'ID',
                'terms' => $show_product_in_category,
                'operator' => 'NOT IN'
            );
            $wp_query_obj->set('tax_query', $tax_query);
        }

        /* Do not display products with the following ids. */
        if ($show_product_is_product != '' && is_array($show_product_is_product)) {
            $product_id_array = array_merge($show_product_is_product, $product_id_array);
        }

        /* Do not display products with the following variations. */
        if ($show_product_has_variant != '' && is_array($show_product_has_variant)) {
            foreach ($show_product_has_variant as $key => $variant_id) {
                $parent_product_id = wp_get_post_parent_id($variant_id);
                if (!in_array($parent_product_id, $product_id_array)) {
                    $product_id_array[] = $parent_product_id;
                }
            }
        }

        /* Do not display products with the following attributes. */
        if ($show_product_has_subattribute != '' && is_array($show_product_has_subattribute)) {

            $attribute_product_ids = [];
            foreach ($show_product_has_subattribute as $key => $attribute_id) {
                
                if ( $attribute_id != "" ) {
                    $term_data = get_term($attribute_id);
                    
                    $term_taxonomy_id = $term_data->term_taxonomy_id;
                
                    $args = array(
                        'post_type' => 'product',
                        'tax_query' => array(
                            array(
                                'field' => 'term_taxonomy_id',
                                'terms' => $term_taxonomy_id
                            )
                        )
                    );
                    $query = new WP_Query($args);

                    $attribute_product_ids = array_unique(array_merge(wp_list_pluck($query->posts, 'ID'), $attribute_product_ids));
                }
            }
            
            $product_id_array = array_unique(array_merge($attribute_product_ids, $product_id_array));
        }

        /* Do not display products with following price type and price value  */
        if ($show_product_price != '' && $show_product_price_type != '') {
            $this->price_filter_ruleset[$ruleset_index]['show_product_price'] = $show_product_price;
            $this->price_filter_ruleset[$ruleset_index]['show_product_price_type'] = $show_product_price_type;
            if (!has_filter('posts_clauses', array($this, 'price_filter_ruleset_condition'))) {
                add_filter('posts_clauses', array($this, 'price_filter_ruleset_condition'), 20, 2);
            }
        }

        /* Set product id array to exclude in dataset */
        if (!empty($product_id_array) && is_array($product_id_array)) {
            $wp_query_obj->set('post__not_in', $product_id_array);
        }

        /**
         * Do not show Category section .
         * Set conditional logic for 'Is Category', 'Has Sub-Category', 'Product In', 'Has Variant', 
         * and 'Product Count'
         */
        $category_id_array = [];
        $show_category_is_category = [];
        $show_category_has_subcategory = [];
        $show_category_product_in = [];
        $show_category_has_variant = [];
        $show_category_product_count_type = [];
        $show_category_product_count = [];
        
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_is_category", $this->form_data ) ) {
            $show_category_is_category = $this->form_data["{$this->plugin_input_prefix}show_category_is_category"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_has_subcategory", $this->form_data ) ) {
            $show_category_has_subcategory = $this->form_data["{$this->plugin_input_prefix}show_category_has_subcategory"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_product_in", $this->form_data ) ) {
            $show_category_product_in = $this->form_data["{$this->plugin_input_prefix}show_category_product_in"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_has_variant", $this->form_data ) ) {
            $show_category_has_variant = $this->form_data["{$this->plugin_input_prefix}show_category_has_variant"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_product_count_type", $this->form_data ) ) {
            $show_category_product_count_type = $this->form_data["{$this->plugin_input_prefix}show_category_product_count_type"][$ruleset_index];
        }
        if( array_key_exists( "{$this->plugin_input_prefix}show_category_product_count", $this->form_data ) ) {
            $show_category_product_count = $this->form_data["{$this->plugin_input_prefix}show_category_product_count"][$ruleset_index];
        }
        
        /* Do not display categories with the following ID(s). */
        if ($show_category_is_category != '' && is_array($show_category_is_category)) {
            $category_id_array = array_merge($show_category_is_category, $category_id_array);
        }

        /* Do not display categories with the following sub-category ID(s). */
        if ($show_category_has_subcategory != '' && is_array($show_category_has_subcategory)) {
            foreach ($show_category_has_subcategory as $category_id) {
                if( $category_id != "" ){
                    $child_category = get_term($category_id);

                    $parent_category_id = $child_category->parent;
                    if ($parent_category_id != '' && !in_array($parent_category_id, $category_id_array)) {
                        $category_id_array[] = $parent_category_id;
                    }
                }
            }
        }

        /* Do not display categories that has the following products. */
        if ($show_category_product_in != '' && is_array($show_category_product_in)) {
            foreach ($show_category_product_in as $product_id) {
                $category_array = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                $category_id_array = array_unique(array_merge($category_array, $category_id_array));
            }
        }

        /* Do not display categories that has the following variants. */
        if ($show_category_has_variant != '' && is_array($show_category_has_variant)) {
            foreach ($show_category_has_variant as $variant_id) {
                $parent_product_id = wp_get_post_parent_id($variant_id);
                $category_array = wp_get_post_terms($parent_product_id, 'product_cat', array('fields' => 'ids'));
                $category_id_array = array_unique(array_merge($category_array, $category_id_array));
            }
        }

        /* Do not display categories that has the product count compared with the product type (operator). */
        if ($show_category_product_count_type != '' && $show_category_product_count != '') {
            $category_data = get_terms(
                array(
                    'taxonomy' => 'product_cat',
                )
            );
            foreach ($category_data as $category) {
                $count = $category->count;
                $product_return = $this->check_quantity_condition($count, $show_category_product_count_type, $show_category_product_count);
                if ($product_return && !in_array($category->term_id, $category_id_array)) {
                    $category_id_array[] = $category->term_id;
                }
            }
        }

        /* Add filter to exclude the categories that matches our ruleset condition */
        $this->exclude_category_ids = array_unique(array_merge($this->exclude_category_ids, $category_id_array));
        add_filter('woocommerce_product_subcategories_args', array($this, 'set_exclude_category'));
    }

    /**
     * Filter hook for updating shop page category display arguments
     * @see woocommerce_get_product_subcategories()
     * 
     * @since   1.0.0
     */
    public function set_exclude_category($args)
    {
        
        if( !( is_array($args) ) ){
            $args = array();
        }
        
        if (!empty($this->exclude_category_ids)) {
            $args['exclude'] = $this->exclude_category_ids;
        }
        return $args;
    }

    /**
     * Function responsible for adding a posts where clause if price filter is applied in
     * ruleset conditions
     * 
     * @see set_ruleset_condition()
     * @since   1.0.0
     */
    public function price_filter_ruleset_condition($args, $wp_query)
    {
        if (empty($this->price_filter_ruleset)) {
            return;
        }
        global $wpdb;
        if (strstr($args['join'], 'postmeta')  === false) {
            $args['join'] .= " LEFT JOIN {$wpdb->prefix}postmeta ON {$wpdb->posts}.ID = {$wpdb->prefix}postmeta.post_id";
        }
        foreach ($this->price_filter_ruleset as $ruleset_index => $price_data) {
            $show_product_price_type = $price_data['show_product_price_type'];
            $show_product_price = $price_data['show_product_price'];
            if ($show_product_price_type === 'less_than') {
                $operator = "<";
            } elseif ($show_product_price_type === 'greater_than') {
                $operator = ">";
            } elseif ($show_product_price_type === 'equal') {
                $operator = "=";
            }

            $sql_add = "
                AND {$wpdb->posts}.ID NOT IN (
                SELECT post_id
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = '_price' AND meta_value {$operator} %d
                ) 
            ";
            $args['where'] .= $wpdb->prepare(
                $sql_add,
                $show_product_price
            );
        }
        $args['where'] .= " AND {$wpdb->prefix}postmeta.meta_key = '_price'";
        return $args;
    }
}
