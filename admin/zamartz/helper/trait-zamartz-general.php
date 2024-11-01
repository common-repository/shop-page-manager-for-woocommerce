<?php

/**
 * Common functionality utilized in multiple class files. 
 * 
 * Trait class added to reduce code redundancy. Methods defined here are utilized in various classes for
 * general requirements of the overall Zamartz admin area.
 * 
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
trait Zamartz_General
{
    /**
     * The unique display name of this plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_display_name    The string used to store the display name of this plugin.
     */
    public $plugin_display_name;

    /**
     * The purchase URL of the current plugin on Zamartz marketplace
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $zamartz_plugin_url    Stores the Zamartz marketplace plugin URL
     */
    public $zamartz_plugin_url;

    /**
     * The unique identifier for Zamartz admin to identify between different input fields.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_input_prefix    The string used to uniquely identify input fields in Zamartz admin.
     */
    public $plugin_input_prefix;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_name    The ID of this plugin.
     */
    public $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $version    The current version of the plugin.
     */
    public $version;

    /**
     * Stores all path information of the current plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $plugin_url    Plugin url data.
     */
    public $plugin_url;

    /**
     * The current plugin api version, paid vs free
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_api_version    Stores if the plugin is paid or free.
     */
    public $plugin_api_version;

    /**
     * The current plugin api authorization, active or needs purchasing
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_api_version    Stores the authorization of the plugin
     */
    public $plugin_api_authorization;

    /**
     * The list if input names that need to be ignored while saving. 
     * These settings are available only for paid version
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $ignore_list    Stores ignored input name list
     */
    public $ignore_list;

    /**
     * Determins the boolean value of Zamartz admin dashboard free vs paid
     *
     * @since    1.0.0
     * @access   public
     * @var      boolean    $is_remove_ads    Stores boolean value of ads
     */
    public $is_remove_ads;

    /**
     * General settings for building PAID Add-Ons notice box
     */
    public function get_paid_addons_notice_box_settings()
    {
        return array(
            'type' => 'notice_box',
            'option_settings' => array(
                'title' => 'This section is only active for PAID Add-Ons',
                'type' => 'warning',
                'description' => 'Does it look like something is missing? Maybe, but we offer benifits to anyone who has a paid version of our Plugins / Extensions / Add-ons or Themes. This includes removing ads and activating advanced features.',
                'btn_text' => 'Buy Now',
                'btn_link' => 'https://zamartz.com/shop/'
            )
        );
    }

    /**
     * General settings for building Zamartz Review notice box
     */
    public function get_zamartz_review_notice_box_settings()
    {

        if (is_network_admin()) {
            $current_screen = 'Dashboard-Network';
        } else {
            $current_screen = 'Dashboard-Site';
        }
        if ($this->is_remove_ads === true) {
            $current_status = 'Paid';
        } else {
            $current_status = 'Free';
        }
        return array(
            'type' => 'notice_box',
            'option_settings' => array(
                'title' => 'If you like Zamartz leave us a <span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span> review.',
                'type' => 'info',
                'description' => 'Thank you in advance! <br>This helps us improve our products and hopefully keep the prices low.',
                'btn_text' => 'Review Now',
                'btn_link' => 'javascript:void(0)',
                'btn_class' => 'zamartz-review-now',
                'data-params' => array(
                    'current_screen' => $current_screen,
                    'current_status' => $current_status
                )
            )
        );
    }

    /**
     * Send google analytics event on "review now" button click
     */
    public function zamartz_review_now_ajax()
    {
        $current_screen = filter_input(INPUT_POST, 'current_screen', FILTER_SANITIZE_STRING);
        $current_status = filter_input(INPUT_POST, 'current_status', FILTER_SANITIZE_STRING);

        $zamartz_admin_event_tracker = get_option('wp_zamartz_admin_event_tracker');

        if ($zamartz_admin_event_tracker === 'yes') {
            $event_data = array(
                'ec' => $current_screen,
                'ea' => 'Review',
                'el' => $current_status,
            );
            send_zamartz_tracker_request($event_data);
        }
        echo json_encode(array('status' => true, 'message' => __('Redirecting!')));
        die();
    }

    /**
     * Generate section slug by plugin name
     */
    public function get_plugin_section_slug()
    {
        return str_replace('-', '_', $this->plugin_name);
    }

    /**
     * Sets the plugin data required in various class files
     * @param   object  Settings instance of the relevant plugin file
     */
    public function set_plugin_data($plugin_settings_instance)
    {
        foreach ($plugin_settings_instance as $parameter_name => $value) {
            if (property_exists($this, $parameter_name)) {
                $this->{$parameter_name} = $value;
            }
        }
    }

    /**
     * Sets the plugin API data for differentiation between plugin being free or paid
     */
    public function set_plugin_api_data()
    {
        $is_plugin_paid = false;
        if (is_multisite()) {
            $domain_list = get_sites();
            foreach ($domain_list as $domain) {
                $blog_id = $domain->blog_id;
                $api_license_key = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_license_key');
                $api_get_response = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_get_response');
                //Check if current plugin is paid or free
                if (($api_license_key !== false ) && ($api_get_response !== false ) && isset($api_get_response->activated) && $api_get_response->activated === true) {
                    $is_plugin_paid = true;
                    break;
                }
            }
        } else {
            $api_license_key = get_option($this->plugin_input_prefix . 'api_license_key');
            $api_get_response = get_option($this->plugin_input_prefix . 'api_get_response');
            //Check if current plugin is paid or free
            if (($api_license_key !== false ) && ($api_get_response !== false ) && isset($api_get_response->activated) && $api_get_response->activated === true) {
                $is_plugin_paid = true;
            }
        }
        if ($is_plugin_paid === true) {
            $this->plugin_api_version = __("Paid", "wp-zamartz-admin");
            $this->plugin_api_authorization = __("Active", "wp-zamartz-admin");
            add_filter('zamartz_is_remove_ads', function () {
                return true;
            });
        } else {
            $this->plugin_api_version = __("Free", "wp-zamartz-admin");
            $this->plugin_api_authorization = '<a href="' . $this->zamartz_plugin_url . '">[' . __("Buy full version", "wp-zamartz-admin") . ']</a>';
        }
    }

    /**
     * Sets the default information to display on the Zamartz admin and network setting tabs
     */
    public function set_default_page_settings()
    {
        $page_settings[] = array(
            'row_field_settings' => $this->get_paid_addons_notice_box_settings(),
            'page_structure' => array(
                'desktop_span' => '50',
                'mobile_span' => '100',
            ),
        );
        $page_settings[] = array(
            'row_field_settings' => $this->get_zamartz_review_notice_box_settings(),
            'page_structure' => array(
                'desktop_span' => '50',
                'mobile_span' => '100',
            ),
        );
        return $page_settings;
    }

    /**
     * Render the page content for the Zamartz admin and network setting tabs
     * 
     * @param	array	$tab_list		    In-page sub menu navigation settings
     * @param	string	$current_tab		Current selected tab
     * @param	string	$current_section	Current selected section
     * @param	array	$settings	        Settings of the current page content
     */
    public function render_settings_page_content($tab_list, $current_tab, $current_section, $page_data)
    {
        $page_content = array();
        $page_structure = array();
        $page_content = '';
        $wrapper_class = '';
        $plugin_input_prefix = '';
        $i = 0;
        //Get page settings
        if (isset($page_data['settings']) && !empty($page_data['settings'])) {
            $settings_data = $page_data['settings'];
            if ($current_section == '' || !isset($settings_data[$current_section])) {
                $current_section = key($settings_data);
            }
            $settings = $settings_data[$current_section];

            //Get page information
            if (isset($page_data['information'][$current_section]) && !empty($page_data['information'][$current_section])) {
                $page_content =  $page_data['information'][$current_section];
                $wrapper_class = isset($page_content['wrapper_class']) && !empty($page_content['wrapper_class']) ? $page_content['wrapper_class'] : '';
                $plugin_input_prefix = isset($page_content['input_prefix']) && !empty($page_content['input_prefix']) ? $page_content['input_prefix'] : '';
            }
        } else {
            $settings = $this->set_default_page_settings();
        }
        //Run foreach based on page settings to generate relevant fields
        foreach ($settings as $page_settings) {
            $page_structure[$i] = isset($page_settings['page_structure']) ? $page_settings['page_structure'] : array();
            ob_start();
            if (!empty($page_settings['column_array']) && is_array($page_settings['column_array'])) {
                foreach ($page_settings['column_array'] as $accordion_data) {
                    $accordion_settings = isset($accordion_data['accordion_settings']) ? $accordion_data['accordion_settings'] : array();
                    $table_section_array = isset($accordion_data['table_section_array']) ? $accordion_data['table_section_array'] : array();
                    $table_params = isset($accordion_data['table_params']) ? $accordion_data['table_params'] : array();
                    $this->generate_accordion_html($accordion_settings, $table_section_array, $table_params);
                }
            } elseif (!empty($page_settings['row_field_settings']) && $page_settings['row_field_settings'] != '') {
                $this->get_field_settings($page_settings['row_field_settings']);
            } else {
                $accordion_settings = isset($page_settings['accordion_settings']) ? $page_settings['accordion_settings'] : array();
                $table_section_array = isset($page_settings['table_section_array']) ? $page_settings['table_section_array'] : array();
                $table_params = isset($page_settings['table_params']) ? $page_settings['table_params'] : array();
                $this->generate_accordion_html($accordion_settings, $table_section_array, $table_params);
            }
            $page_structure[$i]['content'] = ob_get_clean();
            $i++;
        }

        //Generate form
        echo '<form method="post" action="" enctype="multipart/form-data">';
        echo '<div class="wrap">';
        echo '<h2>' . __('Settings', 'wp-zamartz-admin') . '</h2>';
        $section_array = apply_filters("zamartz_settings_subnav", array());
        $this->get_navigation_html($tab_list, $current_tab, $section_array, $current_section);
        if (!empty($page_structure)) {
            echo '<div class="zamartz-wrapper' . $wrapper_class . '" data-input_prefix="' . $plugin_input_prefix . '">';
            echo '<div id="zamartz-message"></div>';
            $this->generate_column_html($page_structure, $page_content);
            echo '</div><!-- /.zamartz-wrapper -->';
        }
        echo '</div><!--- /.wrap -->';
        echo '</form><!--- /.form -->';
    }

    /**
     * Render the second column page content for the Zamartz admin and network dashboard
     * 
     * @param   boolean $is_remove_ads      Stores true/false for removing/displaying ads
     * @param   array   $page_structure     Settings for displaying content on page
     * @param   array   $page_content       Title or description to display on page
     */
    public function render_dashboard_ad_column($is_remove_ads, $page_structure, $page_content = '')
    {
        //Review notice box
        $settings = $this->get_zamartz_review_notice_box_settings();
        ob_start();
        $this->get_field_settings($settings);
        $second_column = ob_get_clean();


        //Set RSS feeds
        if ($is_remove_ads !== true) {
            $this->set_ads_feed_url();
            $second_column .= $this->rss_feed_setup();

            //Paid addons notice box
            $settings = $this->get_paid_addons_notice_box_settings();
            ob_start();
            $this->get_field_settings($settings);
            $second_column .= ob_get_clean();
        }

        //Define second column
        $page_structure[] = array(
            'desktop_span' => '50',
            'mobile_span' => '100',
            'content' => $second_column
        );

        if (!empty($page_structure)) {
            echo '<div class="zamartz-wrapper zamartz-dashboard">
			<div id="zamartz-message"></div>';
            $this->generate_column_html($page_structure, $page_content);
            echo '</div>';
        }
    }

    /**
     * Check if plugin is active for the current blog id
     * 
     * @param   string  $blog_id            The current domain id
     */
    public function is_plugin_active_zamartz($blog_id)
    {
        $plugin_to_test = $this->plugin_url['base_plugin_name'];
        if (is_multisite() && is_network_admin()) {
            $plugins = get_site_option('active_sitewide_plugins');
            $is_network_plugin_exists = isset($plugins[$plugin_to_test]);
            //Get active plugin list for current blog
            if (!$is_network_plugin_exists) {
                $active_plugins = get_blog_option($blog_id, 'active_plugins');
                return in_array($plugin_to_test, $active_plugins);
            }
            return $is_network_plugin_exists;
        } elseif (is_multisite()) {
            $active_plugins = get_blog_option($blog_id, 'active_plugins');
            return in_array($plugin_to_test, $active_plugins);
        } else {
            $active_plugins = get_option('active_plugins');
            return in_array($plugin_to_test, $active_plugins);
        }
    }

    /**
     * Check if any of the domain has a paid plugin for network admin settings
     */
    public function is_any_site_plugin_paid_zamartz()
    {
        //If single site
        if (!is_multisite()) {
            $api_license_key = get_option($this->plugin_input_prefix . 'api_license_key');
            $api_get_response = get_option($this->plugin_input_prefix . 'api_get_response');
            if (!empty($api_license_key) && !empty($api_get_response) && isset($api_get_response->activated) && $api_get_response->activated === true) {
                return true;
            } else {
                return false;
            }
        }

        //Get current domain list
        $domain_list = get_sites();
        foreach ($domain_list as $domain) {
            $blog_id = $domain->blog_id;
            $api_license_key = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_license_key');
            $api_get_response = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_get_response');
            if (!empty($api_license_key) && !empty($api_get_response) && isset($api_get_response->activated) && $api_get_response->activated === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get list to populate section navigation link
     * 
     * @since    1.0.0
     */
    public function get_section_tab_settings($section_array)
    {
        $slug = $this->get_plugin_section_slug();
        $section_array['addons'][$slug] = __($this->plugin_display_name, "wp-zamartz-admin");
        return $section_array;
    }

    /**
     * Set the accordion loop order variable based on GET input in url
     * 
     * @since   1.0.0
     */
    public function set_accordion_loop_order()
    {
        $accordion = filter_input(INPUT_GET, 'accordion', FILTER_SANITIZE_STRING);
        switch ($accordion) {
            case 'license':
                $this->loop_order = array(
                    'zamartz_functionality_settings' => 2,
                    'zamartz_license_settings' => 1,
                    'zamartz_advanced_settings' => 3
                );
                break;
            case 'advanced':
                $this->loop_order = array(
                    'zamartz_functionality_settings' => 2,
                    'zamartz_license_settings' => 3,
                    'zamartz_advanced_settings' => 1
                );
                break;
            default:
                $this->loop_order = array(
                    'zamartz_functionality_settings' => 1,
                    'zamartz_license_settings' => 2,
                    'zamartz_advanced_settings' => 3
                );
                break;
        }
    }

    /**
     * Generates the sidebar for Add-on settings
     *
     * @since   1.0.0
     */
    public function get_sidebar_settings()
    {
        //Define type of accordion with relevant information
        $accordion_settings = array(
            'type' => 'save_footer',
            'accordion_class' => 'zamartz-accordion-sidebar',
            'form_section_data' => array(
                'toggle' => 'affix'
            ),
            'title' => __($this->plugin_display_name, "wp-zamartz-admin")
        );

        //Define data to display inside the accordion
        $table_section_array =
            array(
                'row_data' => array(
                    array(
                        'data' => array(
                            __("Version", "wp-zamartz-admin"),
                            $this->plugin_api_version
                        ),
                        'row_id' => 'zamartz-plugin-api-version',
                        'tabindex' => 0
                    ),
                    array(
                        'data' => array(
                            __("Authorization", "wp-zamartz-admin"),
                            $this->plugin_api_authorization
                        ),
                        'row_id' => 'zamartz-plugin-api-authorization',
                        'tabindex' => 0
                    ),
                ),
                'row_footer' => array(
                    'is_link' => array(
                        'link' => 'javascript:void(0)',
                        'title' => __("Status & Debug", "wp-zamartz-admin"),
                        'id' => 'zamartz-status-debug',
                        'is_spinner_dashicon' => true,
                        'class' => ''
                    ),
                    'is_button' => array(
                        'name' => 'save',
                        'type' => 'submit',
                        'action' => $this->plugin_input_prefix . 'form_data_ajax',
                        'class' => 'button button-primary button-large',
                        'value' => __("Save changes", "wp-zamartz-admin"),
                    )
                ),
                'nonce' => wp_nonce_field('zamartz-settings', 'zamartz_settings_nonce')
            );

        //Define page structure
        $page_structure = array(
            'desktop_span' => '25',
            'mobile_span' => '100',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'page_structure' => $page_structure,
        );
    }

    /**
     * Generates the network admin sidebar for Add-on settings
     *
     * @since   1.0.0
     */
    public function get_network_admin_sidebar_settings()
    {
        $network_api_status_data = get_option($this->plugin_input_prefix . 'network_admin_api_status');

        if ($network_api_status_data === false) {
            $total_activations = '-';
            $activations_remaining = '-';
            $count_updated = '-';
            $add_accordion_class = ' zamartz-get-api-data';
        } else {
            $total_activations = $network_api_status_data['total_activations'];
            $activations_remaining = $network_api_status_data['activations_remaining'];
            $count_updated = $network_api_status_data['count_updated'];
            $add_accordion_class = '';
        }

        //Define type of accordion with relevant information
        $accordion_settings = array(
            'type' => 'save_footer',
            'accordion_class' => 'zamartz-accordion-sidebar' . $add_accordion_class,
            'form_section_data' => array(
                'toggle' => 'affix'
            ),
            'title' => __($this->plugin_display_name, "wp-zamartz-admin")
        );
        //Define data to display inside the accordion

        $table_section_array =
            array(
                'row_data' => array(
                    array(
                        'data' => array(
                            __("API Keys Used:", "wp-zamartz-admin"),
                            $total_activations
                        ),
                        'row_id' => 'zamartz-api-key-used',
                        'tabindex' => 0
                    ),
                    array(
                        'data' => array(
                            __("API Keys Available:", "wp-zamartz-admin"),
                            $activations_remaining
                        ),
                        'row_id' => 'zamartz-api-key-available',
                        'tabindex' => 0
                    ),
                    array(
                        'data' => array(
                            __("API Key Count Updated:", "wp-zamartz-admin"),
                            '<span class="zamartz-text-red">' . $count_updated . '</span>' .
                                '<span data-plugin_prefix="' . $this->plugin_input_prefix . '" tabindex="0" aria-label="API Active count refresh, clicking this button will refresh the status of all api keys" id="zamartz-api-key-refresh" class="dashicons dashicons-update"></span>',
                        ),
                        'row_id' => 'zamartz-api-key-count-update',
                        'tabindex' => 0
                    ),
                ),
                'row_footer' => array(
                    'is_button' => array(
                        'name' => 'save',
                        'type' => 'submit',
                        'action' => $this->plugin_input_prefix . 'network_addon_form_data_ajax',
                        'class' => 'button button-primary button-large',
                        'value' => __("Save changes", "wp-zamartz-admin"),
                    )
                ),
                'nonce' => wp_nonce_field('zamartz-settings', 'zamartz_settings_nonce')
            );

        //Define page structure
        $page_structure = array(
            'desktop_span' => '25',
            'mobile_span' => '100',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'page_structure' => $page_structure,
        );
    }

    /**
     * Save the form data of settings page
     * 
     * @since    1.0.0
     */
    public function save_form_data_ajax()
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
        foreach ($postArray as $key => $data) {
            if (empty($key) || strpos($key, $this->plugin_input_prefix) === false || (!empty($this->ignore_list) && in_array($key, $this->ignore_list))) {
                continue;
            }

            if (strpos($key, $this->plugin_input_prefix . '__remove__') !== false) {
                $key = str_replace($this->plugin_input_prefix . '__remove__', '', $key);
            }

            update_option($key, $data);
            if (!empty($wpdb->last_error)) {
                $error = true;
                $message = 'There was a problem while updating the option data';
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
     * Deactivate the current plugin
     */
    public function zamartz_deactitvate_plugin()
    {
        $option_label = filter_input(INPUT_POST, 'option_selected', FILTER_SANITIZE_STRING);
        $option_value = filter_input(INPUT_POST, 'option_value', FILTER_SANITIZE_STRING);
        if ('plugin_other' != $option_label) {
            $is_network_admin = filter_input(INPUT_POST, 'is_network_admin', FILTER_VALIDATE_BOOLEAN);

            $event_data = array(
                'ec' => $this->plugin_name,
                'ea' => 'deactivate',
                'el' => $option_label . '+' . $option_value,
            );
            $remote_request = send_zamartz_tracker_request($event_data);

            if ($remote_request['response']['code'] === 200) {
                echo json_encode(array('status' => true, 'message' => __('Thank you for your feedback!')));
            } else {
                echo json_encode(array('status' => false, 'message' => __('Feedback could not be submitted. Plugin deactivated.')));
            }
        } else {
            echo json_encode(array('status' => true, 'message' => __('Thank you for using our plugin!')));
        }
        deactivate_plugins($this->plugin_url['base_plugin_name'], false, $is_network_admin);
        die();
    }

    /**
     * Set active site list for add-on activated on the respective domain for the current plugin
     */
    public function set_active_addons_site_list($site_list)
    {
        $domain_list = get_sites();

        foreach ($domain_list as $domain) {
            $domain_url = $domain->domain . $domain->path;
            $blog_id = $domain->blog_id;
            $blog_details = get_blog_details(array('blog_id' => $blog_id));

            //Add logic to test for plugin active state for the respective blog
            if (isset($site_list[$domain_url]) || $blog_details->deleted || !$this->is_plugin_active_zamartz($blog_id)) {
                continue;
            }
            $site_list[$domain_url] = $domain;
        }
        return $site_list;
    }

    /**
     * Get plugin core file information
     */
    public function get_plugin_info()
    {
        if (is_admin()) {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_parent_path = dirname($this->plugin_url['base_plugin_path']);
            $plugin_absolute_path = $plugin_parent_path . '/' . $this->plugin_url['base_plugin_name'];
            $plugin_data = get_plugin_data($plugin_absolute_path);
            return $plugin_data;
        }
        return false;
    }
}
