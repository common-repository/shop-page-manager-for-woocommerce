<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Wp_Shop_Page_Manager_Woo
 * @subpackage Wp_Shop_Page_Manager_Woo/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */

if (class_exists('WP_Customize_Control')) {

	class Wp_Shop_Page_Manager_Custom_Controls extends WP_Customize_Control
	{

		/**
		 * Current WooCommerce settings section object
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      object    $settings_instance    Class object of settings instance
		 * @see	Wp_Shop_Page_Manager_Settings class
		 */
		public $settings_instance;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string   $manager       			Customizer bootstrap instance.
		 * @param      string   $id       				Control ID.
		 * @param      array    $args   				Array of properties for the new Control object. Default empty array.
		 * @param      array    $settings_instance   	Class object of settings instance
		 */

		public function __construct($manager, $id, $args = array(), $settings_instance)
		{
			parent::__construct($manager, $id, $args);

			if (empty($this->settings_instance)) {
				$this->settings_instance = $settings_instance;
			}
		}

		/**
		 * Render the control's content.
		 *
		 * @since 3.4.0
		 */

		public function render_content()
		{
			$prefix = $this->settings_instance->plugin_input_prefix;
			switch ($this->type) {
				case 'toggle_switch': ?>
					<div class="zamartz-wrapper zamartz-shop-manager-customizer-toggle">
						<span class="customize-control-title zamartz-switch-label"><?php echo esc_html($this->label); ?></span>
						<?php if (!empty($this->description)) : ?>
							<span class="description customize-control-description"><?php echo $this->description; ?></span>
						<?php endif;
						$checkbox_value = $this->value() == 'yes' ? true : false;
						?>
						<label class="switch">
							<input type="checkbox" class="zamartz-checkbox" <?php checked($checkbox_value); ?>>
							<span class="slider round"></span>
							<input <?php $this->link(); ?> value="<?php echo $this->value(); ?>" type="hidden">
						</label>
					</div>
				<?php
					break;
				case 'rulesets':
				?>
					<input id="_customize-input-zamartz_shop_page_manager_rulesets" style="display: none;" type="text" data-customize-setting-link="zamartz_shop_page_manager_rulesets">
					<?php if (!empty($this->description)) { ?>
						<span class="description customize-control-description">
							<?php echo $this->description; ?>
						</span>
<?php
					}
					$this->settings_instance->render_customizer_partial();
					break;
			}
		}
	}
}
