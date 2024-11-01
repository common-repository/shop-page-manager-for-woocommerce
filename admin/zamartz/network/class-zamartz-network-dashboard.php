<?php

/**
 * The network admin dashboard specific functionality of the plugin. 
 * The admin-specific functionality of the plugin.
 * Defines the functionality for zamartz network admin
 * 
 * @link       https://zamartz.com
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Zamartz_Network_Dashboard
{
    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     * 
     * Incorporate the trait functionalities for RSS methods in this class
     * @see     zamartz/helper/trait-zamartz-rss-methods.php
     */
    use Zamartz_General, Zamartz_RSS_Methods;

    /**
     * Stores RSS feeds to display.
     * 
     * @since    1.0.0
     * @access   public
     * @var      array    $rss_feeds    RSS feeds data.
     */
    public $rss_feeds;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function init()
    {

        $is_remove_ads = apply_filters('zamartz_network_is_remove_ads', false);
        $this->is_remove_ads = $is_remove_ads;
        $table_section_array = apply_filters('zamartz_network_dashboard_accordion_settings', array());
        $addon_page_content = apply_filters('zamartz_network_dashboard_accordion_information', array());
        $site_list = apply_filters('zamartz_network_dashboard_active_addons_site_list', array());

        $table_section_array['row_footer'] = array(
            'is_link' => array(
                'link' => network_admin_url() . 'admin.php?page=zamartz-network-settings&tab=addons',
                'title' => __("Manage Add-ons", "wp-zamartz-admin"),
                'alt' => __("Click to see all {$this->plugin_display_name} settings", "wp-zamartz-admin"),
            )
        );
        $accordion_settings = array(
            'type' => 'simple_accordion',
            'accordion_class' => '',
            'title' => __('My Add-ons', "wp-zamartz-admin")
        );
        ob_start();
        $this->generate_accordion_html($accordion_settings, $table_section_array);
        $html_content = ob_get_clean();

        $addon_content = $this->render_active_addons_site_list($site_list, $html_content);

        $page_structure[] = array(
            'desktop_span' => '50',
            'mobile_span' => '100',
            'content' => $addon_content
        );

        //Render the second column of the dashboard
        $this->render_dashboard_ad_column($is_remove_ads, $page_structure, $addon_page_content);
    }

    /**
     * Render the accordion for Site with Active Add-ons
     */
    public function render_active_addons_site_list($site_list, $html_content = '')
    {
        //Define settings for Add-on network dashboard accordion
        $table_section_array =  $this->get_active_addons_site_list($site_list);

        if (empty($table_section_array['row_data'])) {
            return '';
        }

        $accordion_settings = array(
            'type' => 'simple_accordion',
            'accordion_class' => 'site-active-add-ons',
            'title' => __("Site with Active Add-ons", "wp-zamartz-admin")
        );
        ob_start();
        $this->generate_accordion_html($accordion_settings, $table_section_array);
        return $html_content .= ob_get_clean();
    }

    /**
     * Generate the table section array settings for the current sites with the add-on active
     */
    public function get_active_addons_site_list($site_list)
    {
        //Define settings for Add-on network dashboard accordion
        $table_section_array = array();
        foreach ($site_list as $domain) {
            $domain_url = $domain->domain . $domain->path;
            $blog_id = $domain->blog_id;
            $blog_details = get_blog_details(array('blog_id' => $blog_id));

            $title = $blog_details->blogname;
            $settings_url = '//' . $domain_url . 'wp-admin/admin.php?page=zamartz-settings&tab=addons';
            $table_section_array['row_data'][] = array(
                'data' => array(
                    '<strong>' . __($title . ' (' . $domain_url . ')', "wp-zamartz-admin") . '</strong>',
                    '<a href="' . $settings_url . '">' . __("Site Settings", "wp-zamartz-admin") . '</a>',
                ),
            );
        }
        return $table_section_array;
    }
}
