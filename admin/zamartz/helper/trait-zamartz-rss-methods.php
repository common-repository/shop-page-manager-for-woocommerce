<?php

/**
 * Common functionality utilized in multiple class files. 
 * 
 * Trait class added to reduce code redundancy. Methods defined here are utilized in various classes for
 * building the RSS feeds for dashboard instance based on defined parameters.
 * 
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
trait Zamartz_RSS_Methods
{

    /**
     * Incorporate the trait functionalities for HTML template in this class
     * 
     * @see     zamartz/helper/trait-zamartz-html-template.php
     */
    use Zamartz_HTML_Template;

    /**
     * Generate the RSS feed settings for each provided RSS url and title
     *
     * @since    1.0.0
     * @param   string	$feed_url			The URL of the current feed
     * @param   string	$feed_title			The title of the current feed
     * @return  array	$row_data_array		Settings for accordion table HTML generation
     */
    public function get_rss_feed_settings($feed_title, $feed_url)
    {
        $rss = fetch_feed($feed_url);
        if (isset($rss->errors)){
            return;
        }

        $image_url = $rss->get_image_url();
        $items = $rss->get_items();
        $row_data_array = [];
        if (!empty($items)) {
            $row_data_array[] = array(
                'data' => array(
                    '<p><strong>' . $feed_title . '</strong></p>'
                ),
                'row_class' => 'feed-title-row',
                'col_span' => 2,
                'tabindex' => 0
            );
            foreach ($items as $item) {
                $enclosure = $item->get_enclosure();
                if ($enclosure) {
                    $image_url = $enclosure->link;
                }
                $title_txt = $item->get_title();
                $link = $item->get_link();

                $utm_campaign = 'utm_campaign=wp-' . strtolower(str_replace(' ', '-', $feed_title));
                $utm_source = '&utm_source=' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', get_bloginfo()), '-'));
                $utm_content = '&utm_content=' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title_txt), '-'));
                $utm_term_image = '&utm_term=image';
                $utm_term_title = '&utm_term=title';
                $add_to_link = '?' . $utm_campaign . $utm_source . $utm_content;

                if (!empty($link)) {
                    $title = '<a alt="Title for ' . $title_txt . ', click for settings" href="' . $link . $add_to_link . $utm_term_title . '">' . $title_txt . '</a>';
                }
                $description = $item->get_description();

                $data = array(
                    'data' => array(
                        '<a href="' . $link . $add_to_link . $utm_term_image . '">
							<img alt="Thumbnail for ' . $title_txt . ', click for settings" title="' . $title_txt . '" src="' . $image_url . '">
						</a>',
                        '<div tabindex="0" class="feed-item-title">' . $title . '</div>
                        <div tabindex="0">' . $description . '</div>',
                    ),
                    'row_class' => 'feed-row-content'
                );
                $row_data_array[] = $data;
            }
        }
        return $row_data_array;
    }

    /**
     * Define feeds for displaying under Ads, partners & affliates accordion 
     *
     * @since    1.0.0
     */
    public function set_ads_feed_url()
    {
        $feed_url = "https://zamartz.com/category";
        $this->rss_feeds['Advertisements'] = "{$feed_url}/advertisement/feed";
        $this->rss_feeds['Featured'] = "{$feed_url}/partners/feed";
        $this->rss_feeds['Partners'] = "{$feed_url}/featured/feed";
        $this->rss_feeds['Affiliate Links'] = "{$feed_url}/affiliate/feed";
    }

    /**
     * Setup accordion for RSS feeds
     *
     * @since    1.0.0
     * @return	 string		$ads_html	Returns the HTML generated accordion for the provided RSS content
     */
    public function rss_feed_setup()
    {
        add_filter('wp_feed_cache_transient_lifetime', function ($a) {
            return 86400;
        });

        $rss_feed = $this->rss_feeds;
        if (empty($rss_feed) || !is_array($rss_feed)) {
            return '';
        }

        $table_section_array = array(
            'row_data' => array()
        );
        $row_data = [];
        $i = 0;
        foreach ($rss_feed as $feed_title => $feed_url) {
            $rss_feed_settings = $this->get_rss_feed_settings($feed_title, $feed_url);
            if (empty($rss_feed_settings)) {
                continue;
            }

            if (!empty($row_data)) {
                $row_data = array_merge($row_data, $rss_feed_settings);
            } else {
                $row_data = $rss_feed_settings;
            }
        }
        $table_section_array['row_data'] = $row_data;
        $table_section_array['row_footer'] = array(
            'is_link' => array(
                'link' => 'https://zamartz.com/category/offers/',
                'title' => __("All Offers", "wp-zamartz-admin"),
            )
        );

        $accordion_settings = array(
            'type' => '',
            'accordion_class' => '',
            'title' => __("Ads, Partners & Affiliates", "wp-zamartz-admin")
        );


        ob_start();
        $this->generate_accordion_html($accordion_settings, $table_section_array);
        $ads_html = ob_get_clean();
        return $ads_html;
    }
}
