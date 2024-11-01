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
?>

<style>
    .woocommerce #mainform .submit {
        display: none;
    }
</style>
<?php
$add_class = '';
if ($this->plugin_api_version === 'Free') {
    $add_class = ' plugin-free-version';
}
?>
<div class="zamartz-wrapper<?php echo $add_class; ?>" data-input_prefix="<?php echo $this->plugin_input_prefix; ?>">
    <div id="zamartz-message"></div>
    <?php
    ob_start();
    $this->wp_shop_page_manager_generate_html();
    $accordion_html = ob_get_clean();
    ob_start();
    $this->wp_shop_page_manager_get_sidebar_section();
    $side_bar_accordion_html = ob_get_clean();
    $page_structure = array(
        array(
            'desktop_span' => '75',
            'mobile_span' => '100',
            'content' => $accordion_html
        ),
        array(
            'desktop_span' => '25',
            'mobile_span' => '100',
            'content' => $side_bar_accordion_html
        )
    );
    $page_content = array(
        'title' => 'Shop Page Manager',
        'description' => __("When options are configured, they will control how the default WooCommerce Shop page displays categories and products.", "wp-checkout-vis-fields-woo")
    );
    $this->generate_column_html($page_structure, $page_content);
    ?>
</div>