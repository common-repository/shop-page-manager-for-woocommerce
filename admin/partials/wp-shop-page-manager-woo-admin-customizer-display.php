<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/admin/partials
 */

ob_start();
$this->wp_shop_page_manager_generate_html();
$accordion_html = ob_get_clean();
$wrapper_class = '';
if ($this->plugin_api_version === 'Free'){
    $wrapper_class = ' plugin-free-version';
}
?>
<div id="zamartz-customizer-wrapper" class="zamartz-customizer-rulesets zamartz-wrapper<?php echo $wrapper_class; ?>" data-input_prefix="woo_shop_manager_">
    <div class="zamartz-col-mobile-100">
        <?php echo $accordion_html; ?>
    </div>
</div>