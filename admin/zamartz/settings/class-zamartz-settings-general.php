<?php

/**
 * The Zamartz admin general settings specific functionality of the plugin. 
 *
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and all Zamartz settings related functionality.
 * 
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Settings_General
{
	/**
	 * Stores all settings for rendering the current section.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $page_settings    Current page settings.
	 */
	private $page_settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{
		//Get page data
		$this->page_settings['information'] = $this->get_general_information();
		$this->page_settings['settings'] = $this->get_general_settings();
	}

    /**
     * General information for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_general_information()
    {
        $information['wp_zamartz_admin'] = array(
            'title' => __("ZAMARTZ General Settings", "wp-zamartz-admin"),
            'description' => __("This section covers general settings for ZAMARTZ settings that are more global in nature.", "wp-zamartz-admin"),
            'input_prefix' => 'wp_zamartz_admin'
        );
        return $information;
	}

    /**
     * General settings for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_general_settings()
    {
        //Get get_functionality settings
        $content_array['column_array'][] = $this->get_functionality_settings();

        //Define page structure
        $content_array['page_structure'] = array(
            'desktop_span' => '75',
            'mobile_span' => '100',
        );

        $general_settings['wp_zamartz_admin'][] = $content_array;

        //Get sidebar settings
        $general_settings['wp_zamartz_admin']['sidebar-settings'] = $this->get_sidebar_settings();

        return $general_settings;
	}
	
    /**
     * Functionality settings inside the general tab
	 * 
     */
    public function get_functionality_settings()
    {
		//Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => false,
            'accordion_class' => 'zamartz-general-settings',
            'accordion_loop' => 1,
            'form_section_data' => array(
                'linked_class' => 'zamartz-general-settings'
            ),
            'title' => __("Functionality", "wp-zamartz-admin")
		);
		$event_tracker = get_option('wp_zamartz_admin_event_tracker');
		//Define table data
        $table_section_array = array(
            array(
                'title' =>  __("Send Anonymous Activate, Deactivate, Uninstall Event Track", "wp-zamartz-admin"),
                'type' => 'toggle_switch',
                'option_settings' => array(
                    'name' => "wp_zamartz_admin_event_tracker",
				),
				'input_value' => $event_tracker
			),
		);
		
        //Define table parameters
        $table_params = array(
            'form_data' => array(),
            'section_type' => '',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'table_params' => $table_params,
        );
	}

    /**
     * Generates the sidebar for General settings
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
            'title' => __('ZAMARTZ Settings', "wp-zamartz-admin")
        );

        //Define data to display inside the accordion
        $table_section_array =
            array(
                'row_data' => array(),
                'row_footer' => array(
                    'is_button' => array(
                        'name' => 'save',
                        'type' => 'submit',
                        'action' => 'wp_zamartz_admin_general_form_data_ajax',
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
	 * Get $page_settings variable data
	 * 
	 * @since    1.0.0
	 */
	public function get_page_settings()
	{
		return $this->page_settings;
	}
}
