<?php

global $zamartz_active_plugin_list;
$zamartz_active_plugin_list = array();
/**
 * Get the response for the POST request sent to Zamartz.com 
 * 
 * @since   1.0.0
 */
function send_zamartz_tracker_request($event_data)
{
    /**
     * Tracker URL used for sending POST request of google analytics information
     * Used during plugin deactivation/review button
     */
    $cache_string = time();
    $tracker_url =  'https://zamartz.com/?api-secure-refrence&nocache='.$cache_string;

    $site_url = get_site_url();
    $site_hash_url = hash('sha256', $site_url);

    $tracker_data = array(
        'v'    => '1',
        'cid' => $site_hash_url,
        't' => 'event',
        'ec' =>  $event_data['ec'],
        'ea' => $event_data['ea'],
        'el' => $event_data['el'],
        'ev' => '1',
    );

    return wp_remote_request(
        $tracker_url,
        array(
            'method'      => 'GET',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body'        => $tracker_data,
            'cookies' => array()
        )
    );
}

/**
 * Set currently active plugins accessing Zamartz Admin
 */
function set_zamartz_active_plugin_list($current_plugin)
{
    global $zamartz_active_plugin_list;
    if (is_array($zamartz_active_plugin_list) && !in_array($current_plugin, $zamartz_active_plugin_list)) {
        $zamartz_active_plugin_list[] = $current_plugin;
    }
}

/**
 * Get currently active plugins accessing Zamartz Admin
 */
function get_zamartz_active_plugin_list()
{
    global $zamartz_active_plugin_list;
    return $zamartz_active_plugin_list;
}
