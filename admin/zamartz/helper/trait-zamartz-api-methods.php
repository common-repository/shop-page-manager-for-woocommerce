<?php

/**
 * Common functionality utilized in multiple class files. 
 * 
 * Trait class added to reduce code redundancy. Methods defined here are utilized in various classes for
 * building the API request and response based on defined parameters.
 * 
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
trait Zamartz_API_Methods
{

    /**
     * Defines the current api license key of the plugin
     * 
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_license_key    API key of the activated plugin
     */
    protected $api_license_key;

    /**
     * Defines whether the cron log can be generated or not for the defined domain
     * 
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $is_cron_log    Yes/No based on toggle switch value.
     */
    protected $is_cron_log;

    /**
     * Build the API URL to send request to, based on the respective parameters
     * 
     * @since   1.0.0
     */
    public function get_zamartz_api_url($type, $api_license_key, $api_instance, $api_product_id, $api_purchase_email)
    {
        if (empty($type)) {
            return array(
                'message' => 'Get request type not defined',
                'status' => false
            );
        } elseif (empty($api_license_key)) {
            return array(
                'message' => 'API key not defined',
                'status' => false
            );
        } elseif (empty($api_instance)) {
            return array(
                'message' => 'API password not defined',
                'status' => false
            );
        } elseif (empty($api_product_id)) {
            return array(
                'message' => 'API product id not defined',
                'status' => false
            );
        } elseif (empty($api_purchase_email)) {
            return array(
                'message' => 'API purchase email not defined',
                'status' => false
            );
        }

        //Verify product id
        if (!empty($this->valid_plugin_product_id) && !in_array($api_product_id, $this->valid_plugin_product_id)) {
            return array(
                'message' => 'Entered product ID is invalid for the selected plugin API credentials.',
                'status' => false
            );
        }
        
        //Get current domain
        $platform = str_replace(array('http://', 'https://'), '', get_site_url());

        $api_purchase_url = 'https://zamartz.com/?';

        $build_url_array = array(
            'wc-api=am-software-api',
            'request=' . $type,
            'email=' . $api_purchase_email,
            'licence_key=' . $api_license_key,
            'product_id=' . $api_product_id,
            'platform=' . $platform,
            'instance=' . $api_instance,
            'software_version=',
        );
        $url = $api_purchase_url . implode('&', $build_url_array);
        return array(
            'message' => $url,
            'status' => true
        );
    }

    /**
     * Use wordpress functionality to get API response based on provided API URL
     * 
     * @param   string  $api_url    URL to get the API response from
     * @return  string  Return the json data of the API response
     * @since   1.0.0
     */
    public function get_api_response($api_url)
    {
        $request = wp_remote_get($api_url);
        if (is_wp_error($request)) {
            $json = new stdClass;
            $json->status = false;
            $json->error = $request->get_error_message();
            return json_encode($json);
        }

        return wp_remote_retrieve_body($request);
    }

    /**
     * Define cron schedule for payment handshaking
     * 
     * @since   1.0.0
     */
    public function zamartz_schedule_api_cron($hook_type, $recurrence, $start_timer = '')
    {

        if ($start_timer === '') {
            $start_timer = time();
        }

        //Unschedule existing API cron to avoid duplication
        $this->zamartz_unschedule_api_cron($hook_type);

        //Schedule an action if it's not already scheduled - double check to avoid cron duplication
        if (!wp_next_scheduled("zamartz_api_cron_schedule_$hook_type")) {
            wp_schedule_event($start_timer, $recurrence, "zamartz_api_cron_schedule_$hook_type");
        }
    }

    /**
     * Define cron schedule for payment handshaking
     * 
     * @since   1.0.0
     */
    public function zamartz_unschedule_api_cron($hook_type)
    {
        $timestamp = wp_next_scheduled("zamartz_api_cron_schedule_$hook_type");
        wp_unschedule_event($timestamp, "zamartz_api_cron_schedule_$hook_type");
    }

    /**
     * Define custom interval of Cron schedule - twice monthly
     */
    public function zamartz_interval_twice_monthly($schedules)
    {
        $schedules['zamartz_twice_monthly'] = array(
            'interval' => 1209600, //14 days interval
            'display'  => esc_html__('Zamartz twice Monthly'),
        );

        return $schedules;
    }

    /**
     * Define custom interval of Cron schedule - once in seven days
     */
    public function zamartz_interval_weekly($schedules)
    {
        $schedules['zamartz_weekly'] = array(
            'interval' => 604800, //7 days interval
            'display'  => esc_html__('Zamartz once weekly'),
        );

        return $schedules;
    }

    /**
     * Create admin notification
     * 
     */
    public function zamartz_api_admin_notice()
    {
        $admin_notice_data = get_option('zamartz_api_admin_notice_data');
        if ($admin_notice_data === false || empty($admin_notice_data)) {
            return;
        }

        $add_class = '';
        if (isset($admin_notice_data['admin_notice_dismissible']) && $admin_notice_data['admin_notice_dismissible'] === true) {
            $add_class =  ' is-dismissible';
            update_option('zamartz_api_admin_notice_data', '');
        }

        $class = "notice notice-{$admin_notice_data['admin_notice_type']}" . $add_class;
        $message = __($admin_notice_data['admin_notice_message'], "wp-zamartz-admin");
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
    }

    /**
     * Create debug log
     * 
     * @param   string  $message    Message to display on the debug log
     * @param  string  $status     Status of the current log - error/success
     */
    public function create_api_debug_log($message, $status = 'error')
    {
        if ($message == '' || get_blog_option(get_current_blog_id(), $this->plugin_input_prefix . 'cron_log') !== 'yes' || WP_DEBUG !== true || WP_DEBUG_LOG != true) {
            return;
        }

        // wp-slug | local.zamartz.com | 0123456 | 2004-02-12T15:19:21+00:00 | error | $message
        $message_array[] = $this->plugin_name;

        if (is_multisite()) {
            $blog_info = get_blog_details();
            $message_array[] = $blog_info->domain;
        } else {
            $message_array[] = home_url();
        }

        if ($this->api_license_key != '') {
            $message_array[] = substr($this->api_license_key, -7);
        } else {
            $message_array[] = '';
        }
        $message_array[] = gmdate(DATE_W3C);
        $message_array[] = $status;
        $message_array[] = $message;

        $log_message = implode(' | ', $message_array);

        //Generate debug log inside the path specified
        error_log($log_message);
    }

    /**
     * Display cron schedule details if API plugin is active
     * 
     * @param   object   $api_get_response   Json encode get resposne of API
     */
    public function get_cron_schedule_details($api_get_response)
    {
        if ($api_get_response !== false && $api_get_response != '' && isset($api_get_response->activated) && $api_get_response->activated == true) {
            $wp_get_schedules = wp_get_schedules('zamartz_twice_monthly');
            $twice_monthly_interval = $wp_get_schedules['zamartz_twice_monthly']['interval'];
            $cron_schedule = wp_next_scheduled("zamartz_api_cron_schedule_twice_monthly");
            $cron_previous_run = date('Y-m-d h:i:s', ($cron_schedule - $twice_monthly_interval));
            $cron_next_run = date('Y-m-d h:i:s', $cron_schedule);

            return array(
                'cron_previous_run' => $cron_previous_run,
                'cron_next_run' => $cron_next_run
            );
        } else {
            return array();
        }
    }

    /**
     * Clears API key data for the current domain
     */
    public function clear_api_credentials_ajax()
    {
        //Verify nonce
        $settings_nonce = filter_input(INPUT_POST, 'settings_nonce', FILTER_SANITIZE_STRING);
        $input_prefix = filter_input(INPUT_POST, 'input_prefix', FILTER_SANITIZE_STRING);

        if (!wp_verify_nonce(wp_unslash($settings_nonce), 'zamartz-settings')) {
            echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
            die();
        }
        try {
            $blog_id = get_current_blog_id();
            update_blog_option($blog_id, $input_prefix . 'api_get_response', '');
            update_blog_option($blog_id, $input_prefix . 'api_license_key', '');
            update_blog_option($blog_id, $input_prefix . 'api_password', '');
            update_blog_option($blog_id, $input_prefix . 'api_product_id', '');
            update_blog_option($blog_id, $input_prefix . 'api_purchase_emails', '');
            $message = 'API key information has been cleared.';
            $button_class = 'zamartz-disable';
            $button_attr = 'activation';
            $button_text = __("Activate API Key", "wp-zamartz-admin");
            $plugin_api_version = __("Free", "wp-zamartz-admin");
            $plugin_api_authorization = '<a href="' . $this->zamartz_plugin_url . '">[' . __("Buy full version", "wp-zamartz-admin") . ']</a>';
            echo json_encode(
                array(
                    'status' => true,
                    'message' => $message,
                    'data' => array(
                        'button_class' => $button_class,
                        'button_attr' => $button_attr,
                        'button_text' => $button_text,
                        'plugin_api_version' => $plugin_api_version,
                        'plugin_api_authorization' => $plugin_api_authorization,
                    )
                )
            );
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'status' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        die();
    }

    /**
     * License settings inside the add-on tab
     */
    public function get_license_settings()
    {
        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => false,
            'accordion_class' => 'zamartz-addon-settings',
            'accordion_loop' => $this->loop_order['zamartz_license_settings'],
            'form_section_data' => array(
                'linked_class' => 'zamartz-addon-settings'
            ),
            'title' => __("Activation", "wp-zamartz-admin")
        );

        $api_license_key = $this->api_license_key;
        $api_password = get_option($this->plugin_input_prefix . 'api_password');
        $api_product_id = get_option($this->plugin_input_prefix . 'api_product_id');
        $purchase_emails = get_option($this->plugin_input_prefix . 'api_purchase_emails');

        $api_get_response = get_option($this->plugin_input_prefix . 'api_get_response');
        if (!empty($api_license_key) && !empty($api_get_response) && isset($api_get_response->activated) && $api_get_response->activated === true) {
            $activate_btn_text = __("Deactivate API Key", "wp-zamartz-admin");
            $activate_btn_class = 'zamartz-enable';
            $activate_btn_type = 'deactivation';
            $read_only = true;
        } else {
            $activate_btn_text = __("Activate API Key", "wp-zamartz-admin");
            $activate_btn_class = 'zamartz-disable';
            $activate_btn_type = 'activation';
            $read_only = false;
        }

        //Define table data
        $table_section_array = array(
            array(
                'title' =>  __("API License Key", "wp-zamartz-admin"),
                'tooltip_desc' =>  __("Enter the license key that was provided in your email or 'my-account' for your paid purchase.", "wp-zamartz-admin"),
                'type' => 'input_text',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "api_license_key",
                    'read_only' => $read_only
                ),
                'input_value' => $api_license_key,
            ),
            array(
                'title' =>  __("API Password", "wp-zamartz-admin"),
                'tooltip_desc' =>  __("Enter a password unique to this activation to create a unique handshake and prevent unauthorized use of your key or unauthorized activations.", "wp-zamartz-admin"),
                'type' => 'input_password',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "api_password",
                    'read_only' => $read_only
                ),
                'input_value' => $api_password,
            ),
            array(
                'title' =>  __("Product ID", "wp-zamartz-admin"),
                'tooltip_desc' =>  __("Endter a product ID unique to this activation.", "wp-zamartz-admin"),
                'type' => 'input_text',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "api_product_id",
                    'read_only' => $read_only
                ),
                'input_value' => $api_product_id,
            ),
            array(
                'title' =>  __("Purchase Emails", "wp-zamartz-admin"),
                'tooltip_desc' =>  __("Enter the email used to purchase the paid version of the plugin. This is also you account email if you registered an account.", "wp-zamartz-admin"),
                'type' => 'input_text',
                'option_settings' => array(
                    'name' => $this->plugin_input_prefix . "api_purchase_emails",
                    'read_only' => $read_only
                ),
                'input_value' => $purchase_emails,
            ),
            array(
                'section_class' => 'zamartz-bordered',
                'type' => 'button',
                'option_settings' => array(
                    'class' => $activate_btn_class,
                    'is_spinner_dashicon' => true,
                    'wrapper' => array(
                        'class' => 'zamartz-full-width-row zamartz-enable-disable-api'
                    ),
                    'data-params' => array(
                        'type'  => $activate_btn_type,
                    ),
                ),
                'input_value' => $activate_btn_text,
            ),
        );

        //Define table parameters
        $table_params = array(
            'form_data' => [],
            'section_type' => 'zamartz_license_settings',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'table_params' => $table_params,
        );
    }

    /**
     * Advanced settings inside the add-on tab
     */
    public function get_advanced_settings($description = '')
    {
        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => false,
            'accordion_class' => 'zamartz-addon-settings',
            'accordion_loop' => $this->loop_order['zamartz_advanced_settings'],
            'form_section_data' => array(
                'linked_class' => 'zamartz-addon-settings'
            ),
            'title' => __("Advanced", "wp-zamartz-admin")
        );

        if ($description == '') {
            $description = 'Use this button to import settings from ' . $this->plugin_display_name . '.';
        }
        //Define table data
        $content = '
        <p tabindex="0"><strong>Legacy Add-On Import</strong></p>
        <p tabindex="0">
            ' . $description . '
        </p>
        ';
        $table_section_array = array(
            array(
                'section_class' => 'zamartz-table-content',
                'additional_content' => $content
            ),
            array(
                'type' => 'button',
                'option_settings' => array(
                    'class' => '',
                    'is_spinner_dashicon' => true,
                    'wrapper' => array(
                        'class' => 'zamartz-full-width-row zamartz-import-settings'
                    ),
                ),
                'input_value' => 'Import Settings',
            ),
        );

        //Define table parameters
        $table_params = array(
            'form_data' => [],
            'section_type' => 'zamartz_advanced_settings',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'table_params' => $table_params,
        );
    }

    /**
     * Functionality to test API key and email and activate plugin full functionality
     * 
     * @since   1.0.0
     */
    public function set_api_license_key_ajax()
    {
        // Get POST data from ajax
        $api_license_key = filter_input(INPUT_POST, 'license_key', FILTER_SANITIZE_STRING);
        $api_instance = filter_input(INPUT_POST, 'api_password', FILTER_SANITIZE_STRING);
        $api_product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING);
        $api_purchase_email = filter_input(INPUT_POST, 'purchase_email', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

        //Verify nonce
        $settings_nonce = filter_input(INPUT_POST, 'settings_nonce', FILTER_SANITIZE_STRING);
        if (!wp_verify_nonce(wp_unslash($settings_nonce), 'zamartz-settings')) {
            echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
            die();
        }

        //Build API URL
        $zamartz_api_url = $this->get_zamartz_api_url($type, $api_license_key, $api_instance, $api_product_id, $api_purchase_email);
        if ($zamartz_api_url['status'] === false) {
            echo json_encode(array('status' => false, 'message' => $zamartz_api_url['message']));
            die();
        }

        //Define GET URL for wp_remote_get()
        $api_url = $zamartz_api_url['message'];
        $json_response = $this->get_api_response($api_url);
        $response_data = json_decode($json_response);

        if (!empty($response_data->success) && $response_data->success === true) {
            if (isset($response_data->activated) && $response_data->activated === true) {
                update_option($this->plugin_input_prefix . 'api_license_key', $api_license_key);
                update_option($this->plugin_input_prefix . 'api_password', $api_instance);
                update_option($this->plugin_input_prefix . 'api_product_id', $api_product_id);
                update_option($this->plugin_input_prefix . 'api_purchase_emails', $api_purchase_email);
                update_option($this->plugin_input_prefix . 'api_get_response', $response_data);
                $button_class = 'zamartz-enable';
                $button_attr = 'deactivation';
                $button_text = __("Deactivate API Key", "wp-zamartz-admin");
                $plugin_api_version = __("Paid", "wp-zamartz-admin");
                $plugin_api_authorization = __("Active", "wp-zamartz-admin");

                //Schedule the API cron for security
                $start_time = strtotime("+1209600 seconds", time()); // Cron start timer to run in 14-days
                $this->zamartz_schedule_api_cron('twice_monthly', 'zamartz_twice_monthly', $start_time);

                //Unschedule the admin notice cron API
                $this->zamartz_unschedule_api_cron('admin_notice');

                //Clear zamartz admin notice cache
                update_option('zamartz_api_admin_notice_data', '');
                $message = __('Success: Activation of your API has been successfull', "wp-zamartz-admin");
            } else {
                delete_option($this->plugin_input_prefix . 'api_license_key');
                delete_option($this->plugin_input_prefix . 'api_password',);
                delete_option($this->plugin_input_prefix . 'api_product_id');
                delete_option($this->plugin_input_prefix . 'api_purchase_emails');

                //Unschedule the twice monthly cron API
                $this->zamartz_unschedule_api_cron('twice_monthly');

                //Unschedule the admin notice cron API
                $this->zamartz_unschedule_api_cron('admin_notice');

                //Clear zamartz admin notice cache
                update_option('zamartz_api_admin_notice_data', '');

                if ($response_data->deactivated === true) {
                    delete_option($this->plugin_input_prefix . 'api_get_response', $response_data);
                } else {
                    delete_option($this->plugin_input_prefix . 'api_get_response', '');
                }
                $button_class = 'zamartz-disable';
                $button_attr = 'activation';
                $button_text = __("Activate API Key", "wp-zamartz-admin");
                $plugin_api_version = __("Free", "wp-zamartz-admin");
                $plugin_api_authorization = '<a href="' . $this->zamartz_plugin_url . '">[' . __("Buy full version", "wp-zamartz-admin") . ']</a>';
                $message = __('Success: Deactivation of your API has been successfull', "wp-zamartz-admin");
            }
            $this->create_api_debug_log($message, 'success');

            echo json_encode(
                array(
                    'status' => true,
                    'message' => $message,
                    'data' => array(
                        'button_class' => $button_class,
                        'button_attr' => $button_attr,
                        'button_text' => $button_text,
                        'plugin_api_version' => $plugin_api_version,
                        'plugin_api_authorization' => $plugin_api_authorization,
                        'response' => $response_data
                    )
                )
            );
            die();
        } else {
            //Error message
            $message =  $response_data->error;

            //Create API debug log
            $this->create_api_debug_log($message, 'error:' . $response_data->code);

            //Add button to clear crendetials on deactivation
            if ($type === 'deactivation') {
                $message .= ' Click <a class="zamartz-clear-api-credentials" href="javascript:void(0)">here</a> to clear API credentials for this domain.';
            }
            echo json_encode(
                array(
                    'status' => false,
                    'message' => $message,
                    'data' => $response_data
                )
            );
            die();
        }
    }

    /**
     * Get plugin version API status
     * 
     * @since   1.0.0
     */
    public function get_api_status_ajax()
    {
        $type = 'status';
        $api_license_key = $this->api_license_key;
        $api_instance = get_option($this->plugin_input_prefix . 'api_password');
        $api_purchase_email = get_option($this->plugin_input_prefix . 'api_purchase_emails');
        $api_product_id = get_option($this->plugin_input_prefix . 'api_product_id');

        //Build API URL
        $zamartz_api_url = $this->get_zamartz_api_url($type, $api_license_key, $api_instance, $api_product_id, $api_purchase_email);
        if ($zamartz_api_url['status'] === false) {
            $this->create_api_debug_log($zamartz_api_url['message'], 'error');
            echo json_encode(
                array(
                    'status' => false,
                    'message' => '<p>' . $zamartz_api_url['message'] . '</p>',
                    'class' => 'error inline'
                )
            );
            die();
        }

        //Define GET URL for wp_remote_get()
        $api_url = $zamartz_api_url['message'];
        $json_response = $this->get_api_response($api_url);

        $build_html = '';

        $settings_json_response = array(
            'type' => 'dismiss_notice',
            'input_value' => $json_response,
        );
        ob_start();
        $this->get_field_settings($settings_json_response);
        $build_html .= ob_get_clean();

        $settings_api_url = array(
            'type' => 'dismiss_notice',
            'input_value' => $api_url,
        );
        ob_start();
        $this->get_field_settings($settings_api_url);
        $build_html .= ob_get_clean();

        $api_get_response = get_option($this->plugin_input_prefix . 'api_get_response');
        $cron_schedule_details = $this->get_cron_schedule_details($api_get_response);

        if (!empty($cron_schedule_details)) {
            $cron_message = '<strong>Cron current run</strong>: ' . $cron_schedule_details['cron_previous_run'] . '<br>';
            $cron_message .= '<strong>Cron next run</strong>: ' . $cron_schedule_details['cron_next_run'];
            $settings_cron_status = array(
                'type' => 'dismiss_notice',
                'input_value' => $cron_message,
            );
            ob_start();
            $this->get_field_settings($settings_cron_status);
            $build_html .= ob_get_clean();
        }

        echo json_encode(array('status' => true, 'message' => $build_html));

        die();
    }

    /**
     * Cron Job handler that runs twice monthly to check plugin API status
     * 
     * @since   1.0.0
     */
    public function zamartz_api_cron_schedule_handler()
    {

        $api_get_response = get_option($this->plugin_input_prefix . 'api_get_response');

        //Get if API response is defined and API is activated
        if ($api_get_response !== false && $api_get_response != '' && isset($api_get_response->activated) && $api_get_response->activated == true) {
            $type = 'status';
            $api_license_key = $this->api_license_key;
            $api_instance = get_option($this->plugin_input_prefix . 'api_password');
            $api_purchase_email = get_option($this->plugin_input_prefix . 'api_purchase_emails');
            $api_product_id = get_option($this->plugin_input_prefix . 'api_product_id');

            $start_timer = strtotime("+604800 seconds", time());

            //Build API URL
            $zamartz_api_data = $this->get_zamartz_api_url($type, $api_license_key, $api_instance, $api_product_id, $api_purchase_email);


            //Check if data is missing for the activated API key
            if ($zamartz_api_data['status'] === false) {
                //Create notification for invalid API data, update cron timer
                $notice_end_date = date("M/d/Y", strtotime("+1 week", time()));
                $message = $this->plugin_display_name . ' Plugin API warning: <strong>' . $zamartz_api_data['message'] . '</strong>. Plugin paid features will be deactivated on ' . $notice_end_date . '.';
                $admin_notice_data = array(
                    'admin_notice_type' => 'warning',
                    'admin_notice_message' => $message
                );
                update_option('zamartz_api_admin_notice_data', $admin_notice_data);
                $this->zamartz_schedule_api_cron('admin_notice', 'zamartz_weekly', $start_timer);

                $this->create_api_debug_log('Cron: ' . $zamartz_api_data['message'], 'error');

                //No need for twice monthly check since admin notice cron is now scheduled
                $this->zamartz_unschedule_api_cron('twice_monthly');
                return;
            }

            $api_url = $zamartz_api_data['message'];
            $json_response = $this->get_api_response($api_url);
            $response_data = json_decode($json_response);

            //API information is invalid
            if (isset($response_data->error) && isset($response_data->code) && $response_data->code == 100) {
                $this->create_api_debug_log('Cron: ' . $response_data->error, 'error:' . $response_data->code);
                $notice_end_date = date("M/d/Y", strtotime("+1 week", time()));
                $message = $this->plugin_display_name . ' Plugin API error: <strong>API credentials are invalid</strong>. Plugin paid features will be deactivated on ' . $notice_end_date . '.';
                $admin_notice_data = array(
                    'admin_notice_type' => 'warning',
                    'admin_notice_message' => $message
                );
                update_option('zamartz_api_admin_notice_data', $admin_notice_data);
                $this->zamartz_schedule_api_cron('admin_notice', 'zamartz_weekly', $start_timer);

                //No need for twice monthly check since admin notice cron is now scheduled
                $this->zamartz_unschedule_api_cron('twice_monthly');
                return;
            } elseif (isset($response_data->error) && !isset($response_data->code)) {
                $this->create_api_debug_log('Cron: ' . $response_data->error, 'error');
            }
        }
        return;
    }

    /**
     * Cron job that runs to disable paid features on invalid API handshake
     * 
     * @since   1.0.0
     */
    public function zamartz_disable_paid_features()
    {
        //Unschedule API crons
        $this->zamartz_unschedule_api_cron('admin_notice');
        $this->zamartz_unschedule_api_cron('twice_monthly');

        //Display message of plugin features being disabled
        $message = $this->plugin_display_name . ' Plugin paid features have been disabled.';
        $admin_notice_data = array(
            'admin_notice_type' => 'error',
            'admin_notice_message' => $message,
            'admin_notice_dismissible' => true
        );
        update_option($this->plugin_input_prefix . 'api_admin_notice_data', $admin_notice_data);
        update_option($this->plugin_input_prefix . 'api_get_response', '');
    }

    /**
     * Get API key data for used and available keys
     * 
     * @since   1.0.0
     */
    public function get_network_api_status_ajax()
    {
        $settings_nonce = filter_input(INPUT_POST, 'settings_nonce', FILTER_SANITIZE_STRING);

        if (!wp_verify_nonce(wp_unslash($settings_nonce), 'zamartz-settings')) {
            echo json_encode(array(
                'status' => false,
                'message' => __('<p>Nonce could not be verified!</p>'),
                'class' => 'error inline'
            ));
            die();
        }

        //Get current domain list
        $domain_list = get_sites();

        // Initialize data
        $data = [];
        $status = false;
        $message = '<p><strong>API data not found for any domain.</strong></p>';
        $class = 'error inline';

        //Run foreach domain list to retrieve the get response
        foreach ($domain_list as $domain) {
            $blog_id = $domain->blog_id;
            $blog_details = get_blog_details(array('blog_id' => $blog_id));

            //Add logic to check if blog is deleted/deactivated
            if ($blog_details->deleted) {
                continue;
            }
            $api_get_response = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_get_response');
            //Get if API response is defined and API is activated
            if ($api_get_response !== false && $api_get_response != '' && isset($api_get_response->activated) && $api_get_response->activated == true) {

                $api_license_key = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_license_key');
                $api_instance = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_password');
                $api_purchase_email = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_purchase_emails');
                $api_product_id = get_blog_option($blog_id, $this->plugin_input_prefix . 'api_product_id');

                $zamartz_api_url = $this->get_zamartz_api_url('status', $api_license_key, $api_instance, $api_product_id, $api_purchase_email);
                if ($zamartz_api_url['status'] === false) {
                    echo json_encode(
                        array(
                            'status' => false,
                            'message' => $zamartz_api_url['message'],
                            'class' => 'error inline'
                        )
                    );

                    $admin_notice = get_option('zamartz_api_admin_notice_data');
                    if ($admin_notice === false || $admin_notice === '') {

                        //Plugin active but API response is false
                        //Create 7-day notification
                        $notice_end_date = date("M/d/Y", strtotime("+1 week", time()));
                        $start_timer = strtotime("+150 seconds", time()); //Start time to 7-days
                        $message = $this->plugin_display_name . ' Plugin API error: <strong>' . $zamartz_api_url['message'] . '</strong>. Plugin paid features will be deactivated on ' . $notice_end_date . '.';
                        $admin_notice_data = array(
                            'admin_notice_type' => 'warning',
                            'admin_notice_message' => $message
                        );
                        update_option('zamartz_api_admin_notice_data', $admin_notice_data);
                        $this->zamartz_schedule_api_cron('admin_notice', 'zamartz_weekly', $start_timer);

                        //No need for twice monthly check since admin notice cron is now scheduled
                        $this->zamartz_unschedule_api_cron('twice_monthly');
                    }

                    die();
                }

                //Define GET URL for wp_remote_get()
                $api_url = $zamartz_api_url['message'];
                $json_response = $this->get_api_response($api_url);
                $json_decode = json_decode($json_response);
                if (isset($json_decode->success) && $json_decode->success == '1') {
                    $data = array(
                        'total_activations' => $json_decode->data->total_activations,
                        'activations_remaining' => $json_decode->data->activations_remaining,
                        'count_updated' => date('m/d/Y')
                    );
                    update_option($this->plugin_input_prefix . 'network_admin_api_status', $data);
                    $status = true;
                    $message = '<p><strong>API data retrieved, API status saved.</strong></p>';
                    $class = 'updated inline';

                    //Reset cron timer
                    $start_time = strtotime("+300 seconds", time()); //Start time = 14-days
                    $this->zamartz_schedule_api_cron('twice_monthly', 'zamartz_twice_monthly', $start_time);

                    //Clear any and all zamartz admin notice cache
                    update_option('zamartz_api_admin_notice_data', '');

                    //Unschedule the admin notice cron API
                    $this->zamartz_unschedule_api_cron('admin_notice');
                } else {
                    $admin_notice = get_option('zamartz_api_admin_notice_data');
                    if ($admin_notice === false || $admin_notice === '') {
                        //Plugin API response is invalid
                        //Create 7-day notification
                        $start_timer = strtotime("+150 seconds", time()); //Start time to 7-days
                        $notice_end_date = date("M/d/Y", strtotime("+1 week", time()));
                        $message = $this->plugin_display_name . ' Plugin API error: <strong>API credentials are invalid</strong>. Plugin paid features will be deactivated on ' . $notice_end_date . '.';
                        $admin_notice_data = array(
                            'admin_notice_type' => 'error',
                            'admin_notice_message' => $message
                        );
                        update_option('zamartz_api_admin_notice_data', $admin_notice_data);
                        $this->zamartz_schedule_api_cron('admin_notice', 'zamartz_weekly', $start_timer);

                        //No need for twice monthly check since admin notice cron is now scheduled
                        $this->zamartz_unschedule_api_cron('twice_monthly');
                    }
                    $status = false;
                    $message = '<p><strong>' . $json_decode->error . '</strong></p>';
                    $message .= '<p><strong>API key: ' . $api_license_key . '</strong></p>';
                    $class = 'error inline';
                }
                break;  //Exit loop
            }
        }

        //Add logic to reset cron

        echo json_encode(
            array(
                'status' => $status,
                'message' => $message,
                'class' => $class,
                'data' => $data
            )
        );
        die();
    }

    /**
     * Set API ID list for the plugin
     * @param   array   $product_id_array   Stores information of all valid IDs
     */
    public function set_valid_product_id($product_id_array)
    {
        $this->valid_plugin_product_id = $product_id_array;
    }
}
