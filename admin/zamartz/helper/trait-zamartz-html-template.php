<?php

/**
 * Generates the HTML template and utilized in multiple class files. 
 * 
 * Trait class added to reduce code redundancy. Methods defined here are utilized in various classes for
 * building the HTML template of the plugin.
 * 
 * @since      1.0.0
 * @author     Zachary Martz <zam@zamartz.com>
 */
trait Zamartz_HTML_Template
{

    /**
     * Generate the desktop and mobile column structure based on the defined parameters
     *
     * @since	1.0.0
     * @param	array	$page_structure		Defined 'div' structure of the current page
     */
    public function generate_column_html($page_structure, $page_content = '')
    {
        if (isset($page_content['title']) && !empty($page_content['title'])) {
            echo '<h2>' . $page_content['title'] . '</h2>';
        }
        if (isset($page_content['description']) && !empty($page_content['description'])) {
            echo '<p>' . $page_content['description'] . '</p>';
        }

        foreach ($page_structure as $data) {
            $class = array();
            if (isset($data['desktop_span']) && !empty($data['desktop_span'])) {
                $class[] = 'zamartz-col-' . $data['desktop_span'];
            }
            if (isset($data['mobile_span']) && !empty($data['mobile_span'])) {
                $class[] = 'zamartz-col-mobile-' . $data['mobile_span'];
            }
            $class_string = ' class="' . implode(' ', $class) . '"';
            echo '<div' . $class_string . '>';
            echo $data['content'];
            echo '</div>';
        }
    }

    /**
     * Generate the accordion HTML based on the provided accordion settings
     * 
     * @since	1.0.0
     * @param 	array	$accordion_settings		Generate respective accordion based on these settings
     * @param 	array	$table_section_array	Generate respective table inside the accordion
     * @param 	array	$table_params			Added information for the table that is generating
     */
    public function generate_accordion_html($accordion_settings, $table_section_array = array(), $table_params = array())
    {
        $aria_expanded = ' aria-expanded="true"';
        if ($accordion_settings['type'] == 'form_table') {
            $loop = isset($accordion_settings['accordion_loop']) ? $accordion_settings['accordion_loop'] : 1;
            if ($loop > 1) {
                $style = ' style="display: none;"';
                $closed = ' closed';
                $aria_expanded = ' aria-expanded="false"';
            } else {
                $style = '';
                $closed = '';
            }
            $is_delete = isset($accordion_settings['is_delete']) ? $accordion_settings['is_delete'] : false;
            $accordion_class = isset($accordion_settings['accordion_class']) ? $accordion_settings['accordion_class'] : '';
            $form_section_data = isset($accordion_settings['form_section_data']) ? $accordion_settings['form_section_data'] : [];
            $accordion_title = isset($accordion_settings['title']) ? $accordion_settings['title'] : '';
            $data_params = [];
            foreach ($form_section_data as $index => $value) {
                $data_params[] = 'data-' . $index . '="' . $value . '"';
            }
            $data_string = implode(' ', $data_params);

            if ($is_delete) {
                $class = 'zamartz-accordion-delete ';
                $toggle_indicator = '<button type="button" class="zamartz-toggle-indicator' . $closed . '"' . $aria_expanded . '>
										' . $accordion_title . '
									</button>
									<button aria-label="Remove ' . wp_strip_all_tags($accordion_title) . '" type="button" class="zamartz-delete-accordion"></button>
                                    ';
            } else {
                $class = 'zamartz-accordion-simple ';
                $toggle_indicator = '<button type="button" class="zamartz-toggle-indicator' . $closed . '"' . $aria_expanded . '>' . $accordion_title . '</button>';
            }

            echo '<div class="' . $class . $accordion_class . '">
					<div class="zamartz-form-section"' . $data_string . '>
						<p class="zamartz-panel-header">
							' . $toggle_indicator . '
						</p>
						<div class="zamartz-form-table"' . $style . '>';
            $this->generate_form_table_html($table_section_array, $table_params);
            echo '</div>
				</div>
			</div>';
        } else {
            $accordion_class = isset($accordion_settings['accordion_class']) ? $accordion_settings['accordion_class'] : '';
            $title = isset($accordion_settings['title']) ? $accordion_settings['title'] : '';
            $form_section_data = isset($accordion_settings['form_section_data']) ? $accordion_settings['form_section_data'] : [];
            $nonce = isset($table_section_array['nonce']) ? $table_section_array['nonce'] : array();
            $row_data = isset($table_section_array['row_data']) ? $table_section_array['row_data'] : array();
            $row_footer = isset($table_section_array['row_footer']) ? $table_section_array['row_footer'] : array();

            $data_params = [];
            foreach ($form_section_data as $index => $value) {
                $data_params[] = 'data-' . $index . '="' . $value . '"';
            }
            $data_string = implode(' ', $data_params);

            $row_html = '';
            foreach ($row_data as $table_row) {

                $row_id = (isset($table_row['row_id']) && !empty($table_row['row_id'])) ? ' id="' . $table_row['row_id'] . '"' : '';
                $row_class = (isset($table_row['row_class']) && !empty($table_row['row_class'])) ? ' class="' . $table_row['row_class'] . '"' : '';

                $col_span = isset($table_row['col_span']) ? ' colspan="' . $table_row['col_span'] . '"' : '';
                $tabindex = isset($table_row['tabindex']) ? ' tabindex="' . $table_row['tabindex'] . '"' : '';
                $row_html .= '<tr valign="top"' . $row_id . $row_class . '>';
                foreach ($table_row['data'] as $table_data) {
                    $row_html .= '<td' . $col_span . $tabindex . '>' . $table_data . '</td>';
                }
                $row_html .= '</tr>';
            }

            $is_link = (isset($row_footer['is_link']) && $row_footer['is_link'] !== false) ? $row_footer['is_link'] : false;
            $is_button = (isset($row_footer['is_button']) && $row_footer['is_button'] !== false) ? $row_footer['is_button'] : false;
            if (!empty($row_footer) && $accordion_settings['type'] == 'save_footer') {
                $row_html .= '<tr><td colspan="2" id="major-publishing-actions">';
                if (is_array($is_link) && !empty($is_link)) {
                    //Define link variables
                    $link = isset($is_link['link']) ? $is_link['link'] : '';
                    $link_title = isset($is_link['title']) ? $is_link['title'] : '';
                    $link_id = isset($is_link['id']) && !empty($is_link['id']) ? ' id="' . $is_link['id'] . '"'  : '';
                    $link_class = isset($is_link['class']) && !empty($is_link['class']) ? ' class="' . $is_link['class'] . '"'  : '';
                    $is_spinner_dashicon = isset($is_link['is_spinner_dashicon']) ? $is_link['is_spinner_dashicon'] : false;
                    $row_html .= '<p class="publishing-settings">';
                    $row_html .= '<a' . $link_id . $link_class . ' href="' . $link . '">' . $link_title . '</a>';
                    if ($is_spinner_dashicon === true) {
                        $row_html .= '<span class="dashicons dashicons-update spin" style="display: none;"></span>';
                    }
                    $row_html .= '</p>';
                }
                if (is_array($is_button) && !empty($is_button)) {
                    $name = isset($is_button['name']) ? $is_button['name'] : '';
                    $type = isset($is_button['type']) ? $is_button['type'] : '';
                    $class = isset($is_button['class']) ? $is_button['class'] : '';
                    $value = isset($is_button['value']) ? $is_button['value'] : '';
                    $action = isset($is_button['action']) ? ' data-action="' . $is_button['action'] . '"' : '';
                    $row_html .= '<p id="publishing-action">';
                    $row_html .= '<span class="spinner"></span>';
                    $row_html .= '<input' . $action . ' name="' . $name . '" type="' . $type . '" class="' . $class . '" value="' . $value . '">';
                    $row_html .= $nonce;
                    $row_html .= '</p>';
                }
                $row_html .= '</td></tr>';
            } elseif (!empty($row_footer) && is_array($is_link) && !empty($is_link)) {
                $row_html .= '<tr class="zamartz-accordion-footer-link"><td colspan="2">';
                $link = isset($is_link['link']) ? $is_link['link'] : '';
                $link_title = isset($is_link['title']) ? $is_link['title'] : '';
                $link_alt = isset($is_link['alt']) ? ' alt="' . $is_link['alt'] . '"' : '';
                $row_html .= '<a' . $link_alt . ' target="_blank" href="' . $link . '">' . $link_title . ' <span class="dashicons dashicons-external"></span></a>';
                $row_html .= '</td></tr>';
            }

            echo '<div class="zamartz-accordion-simple ' . $accordion_class . '">
					<div class="zamartz-form-section"' . $data_string . '>
						<p class="zamartz-panel-header">
							<button type="button" class="zamartz-toggle-indicator"' . $aria_expanded . '>' . $title . '</button>
						</p>
						<div class="zamartz-form-table">
							<table class="form-table">
								<tbody>
									' . $row_html . '
								</tbody>
							</table>
						</div>
					</div>
				</div>';
        }
    }

    /**
     * Generate the form table HTML inside the defined accordion
     * 
     * @since	1.0.0
     * @see		Zamartz_HTML_Template::generate_accordion_html()
     * @param 	array	$table_section_array	Generate respective table inside the accordion
     * @param 	array	$table_params			Added information for the table that is generating
     */
    public function generate_form_table_html($table_section_array, $table_params)
    {
        ob_start();

        $section_type = $table_params['section_type'];
        echo '<table class="form-table"><tbody>';
        $form_data = $table_params['form_data'];
        $key = isset($table_params['key']) ? $table_params['key'] : '';
        $input_prefix = isset($table_params['input_prefix']) ? $table_params['input_prefix'] : '';
        $is_tristate_button = isset($table_params['is_tristate_button']) ? $table_params['is_tristate_button'] : false;
        foreach ($table_section_array as $section_index => $section_data) {

            $field_options = isset($section_data['field_options']) ? $section_data['field_options'] : array();
            $option_settings = isset($section_data['option_settings']) ? $section_data['option_settings'] : array();
            $section_class = isset($section_data['section_class']) && !empty($section_data['section_class']) ? $section_data['section_class'] : '';
            $is_multi = isset($section_data['is_multi']) ? $section_data['is_multi'] : false;
            $is_select2 = isset($section_data['is_select2']) ? $section_data['is_select2'] : false;

            echo '<tr valign="top" class="' . $section_class . '">';
            if (isset($section_data['title']) && !empty($section_data['title'])) {
                $section_title = $section_data['title'];
                echo '<th tabindex="0" scope="row" class="titledesc">' . $section_data['title'];
                echo '</th>';
                $colspan = '';
            } else {
                $colspan = ' colspan="2"';
                $section_title = '';
            }

            echo '<td' . $colspan . '>';
            if (isset($section_data['tooltip_desc']) && !empty($section_data['tooltip_desc'])) {
                $tooltip_id = $option_settings['name'];
                $tooltip_id .= !empty($key) ? '_' . $key : '';
                $tooltip_id_text = $tooltip_id . '_tooltip';
                echo '<span role="tooltip" id="' . $tooltip_id_text . '" aria-label="' . $section_data['tooltip_desc'] . '" data-tip="' . $section_data['tooltip_desc'] . '" class="zamartz-help-tip"></span>';
                $option_settings['tooltip_id_text'] = $tooltip_id_text;
            }

            $type = isset($section_data['type']) && !empty($section_data['type']) ? $section_data['type'] : '';

            $field_settings = array(
                'title' => $section_title,                  //Section title for "Aria label"
                'key' => $key,                              //Current key of loop
                'is_multi' => $is_multi,                    //Is multi?
                'is_select2' => $is_select2,                //Is select2?
                'input_prefix' => $input_prefix,            //Prefix to add prior to name
                'form_data' => $form_data,                  //Current form data retrieved from database
                'section_type' => $section_type,            //The current section eg. Shipping|Billing|Zamartz Settings
                'type' => $type,                            //Field type to apply logic
                'field_options' => $field_options,          //Field list
                'option_settings' => $option_settings,      //Additional settings
                'is_tristate_button' => $is_tristate_button //Check if form has tristate button functionality
            );
            if (isset($section_data['input_value'])) {
                $field_settings['input_value'] = $section_data['input_value'];
            }
            $this->get_field_settings($field_settings);

            if (isset($section_data['additional_content']) && !empty($section_data['additional_content'])) {
                echo $section_data['additional_content'];
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        $form_table_html = ob_get_clean();
        echo $form_table_html;
    }

    /**
     * Generate a simple table with basic parameters
     * @param 	array	$table_section_array	Generate respective table inside the accordion
     * @param 	array	$table_params			Added information for the table that is generating
     */
    public function generate_simple_table_html($table_section_array, $table_params)
    {
        ob_start();
        $class = isset($table_params['class']) && !empty($table_params['class']) ? ' class="' . $table_params['class'] . '"' : '';

        $row_head = isset($table_section_array['row_head']) && !empty($table_section_array['row_head']) ? $table_section_array['row_head'] : array();
        $title = isset($row_head['title']) && !empty($row_head['title']) ? $row_head['title'] : '';
        $colspan = isset($row_head['colspan']) && !empty($row_head['colspan']) ? ' colspan="' . $row_head['colspan'] . '"' : '';

        $row_data = isset($table_section_array['row_data']) && !empty($table_section_array['row_data']) ? $table_section_array['row_data'] : array();
        echo '<div class="zamartz-wrapper zamartz-horizontal-bar zamartz-simple-table-wrapper">
                <table' . $class . '>
                    <thead>
                        <tr>
                            <th' . $colspan . '>
                                <h3>' . $title . '</h3>
                            </th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($row_data as $row) {
            $tabindex = isset($row['tabindex']) && $row['tabindex'] !== false ? ' tabindex="' . $row['tabindex'] . '"' : '';

            echo '<tr' . $tabindex . '>';
            foreach ($row['column_data'] as $column) {
                echo '<td>' . $column . '</td>';
            }
            echo '</tr>';
        }
        echo '      </tbody>
                </table>
            </div>';
        $table_html = ob_get_clean();
        return $table_html;
    }

    /**
     * Generate metabox content
     * @param 	array	$meta_box_settings	    Settings to generate the meta box content
     */
    public function generate_metabox_html($meta_box_settings, $plugin_input_prefix)
    {
        ob_start();
        echo '<div class="zamartz-wrapper" data-input_prefix="' . $plugin_input_prefix . '">';
        foreach ($meta_box_settings as $settings) {
            $class = isset($settings['class']) && !empty($settings['class']) ? $settings['class'] : '';
            echo '<div class="zamartz-metabox-row ' . $class . '">';
            if ($settings['title_location'] == 'top') {
                echo '<div class="zamartz-metabox-top">' . $settings['title'] . '</div>';
            } elseif ($settings['title_location'] == 'left') {
                echo '<div class="zamartz-metabox-left">' . $settings['title'] . '</div>';
            }
            $this->get_field_settings($settings);
            if ($settings['title_location'] == 'right') {
                echo '<span class="zamartz-metabox-right">' . $settings['title'] . '</span>';
            } elseif ($settings['title_location'] == 'bottom') {
                echo '<div class="zamartz-metabox-bottom">' . $settings['title'] . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
        $metabox_html = ob_get_clean();
        echo $metabox_html;
    }

    /**
     * Get fieldset settings
     */
    public function get_field_settings($settings, $is_label = false)
    {

        $type = $settings['type'];
        if (empty($type)) {
            return;
        }

        if (isset($settings['key']) && !empty($settings['key'])) {
            $key = $settings['key'];
            $add_input_name = '[' . $settings['key'] . ']';
        } else {
            $add_input_name = '';
            $key = '';
        }

        $field_options = isset($settings['field_options']) ? $settings['field_options'] : array();
        $form_data = isset($settings['form_data']) ? $settings['form_data'] : array();
        $section_type = isset($settings['section_type']) ? $settings['section_type'] : '';

        if ($is_label) {
            echo '<th tabindex="0" scope="row" class="titledesc">' . $settings['title'] . '</th>
				<td>';
            if (isset($settings['tooltip_desc']) && !empty($settings['tooltip_desc'])) {
                echo '<span aria-label="' . $settings['tooltip_desc'] . '" data-tip="' . $settings['tooltip_desc'] . '" class="zamartz-help-tip"></span>';
            }
        }

        switch ($type) {
            case 'tristate':

                //Retrieve settings
                $title = isset($settings['title']) ? $settings['title'] : '';
                $tristate_button_value = $settings['input_value'];
                $option_settings = $settings['option_settings'];
                $input_name = $option_settings['name'];

                $on_class = '';
                $off_class = '';
                $yes_check = '';
                $no_check = '';
                $default_check = '';

                if ($tristate_button_value == 'yes') {
                    $yes_check = ' checked';
                    $on_class = ' active';
                } elseif ($tristate_button_value == 'default' || $tristate_button_value == '') {
                    $default_check = ' checked';
                } elseif ($tristate_button_value == 'no') {
                    $no_check = ' checked';
                    $off_class = ' active';
                }
                $tristate_class = 'zamartz-tristate-button';
                $tristate_class .= ($tristate_button_value == 'yes' || $tristate_button_value == 'no') ? ' zamartz-tristate-active' : '';
                echo '
                <div class="zamartz-tristate-label-and-btn-wrapper">
                    <span tabindex="0" class="zamartz-tristate-require-label">' . __("Require", "wp-checkout-vis-fields-woo") . '</span>
                    <div class="' . $tristate_class . '" role="radiogroup"                    >
                        <label class="zamartz-tristate-label on-switch-label' . $on_class . '">Y</label>
                        <input tabindex="0" aria-label="' . ($title != '' ? 'Require ' . $title . ' Y' : '') . '" name="' . $input_name . '" type="radio" value="yes"' . $yes_check . '>
                        <input tabindex="0" aria-label="' . ($title != '' ? 'Require ' . $title . ' Default' : '') . '" name="' . $input_name . '" type="radio" value="default"' . $default_check . '>
                        <input tabindex="0" aria-label="' . ($title != '' ? 'Require ' . $title . ' N' : '') . '" name="' . $input_name . '" type="radio" value="no"' . $no_check . '>
                        <label class="zamartz-tristate-label off-switch-label' . $off_class . '">N</label>
                        <div class="zamartz-tristate-indicator"></div>
                    </div>
                </div>';
                break;
            case 'checkbox':
                $input_prefix = isset($settings['input_prefix']) ? $settings['input_prefix'] : 'zamartz_admin_';
                $is_tristate_button = isset($settings['is_tristate_button']) && $settings['is_tristate_button'] != '' ? $settings['is_tristate_button'] : false;

                $checkbox_html = '';
                $checkbox_linked_array = [];

                foreach ($field_options as $option_index => $option_data) {
                    $input_name = $input_prefix . $section_type . $option_index;

                    if (!empty($key)) {
                        $input_value = (isset($form_data[$input_name][$key]) && $form_data[$input_name][$key] == 'yes') ? true : false;
                        $tooltip_id = $input_name . '_' . $key;
                    } else {
                        $tooltip_id = $input_name;
                        $input_value = (isset($form_data[$input_name]) && $form_data[$input_name] == 'yes') ? true : false;
                    }
                    $tooltip_id_text = ' id="' . $tooltip_id . '_tooltip" role="tooltip"';

                    if (is_array($option_data)) {
                        $option_label = $option_data['label'];
                        $checkbox_class = isset($option_data['class']) && $option_data['class'] != '' ? ' ' . $option_data['class'] : false;
                        $is_tristate_active = isset($option_data['is_tristate_active']) && $option_data['is_tristate_active'] != '' ? $option_data['is_tristate_active'] : false;
                        $is_checkbox_enabled = isset($option_data['is_checkbox_enabled']) ? $option_data['is_checkbox_enabled'] : true;
                        $tooltip_span = isset($option_data['tooltip_desc']) ? '<span' . $tooltip_id_text . ' aria-label="' . $option_data['tooltip_desc'] . '" data-tip="' . $option_data['tooltip_desc'] . '" class="zamartz-help-tip"></span>' : "";
                        $desc = isset($option_data['desc']) ? '<p tabindex="0" class="description">' . $option_data['desc'] . '</p>' : "";
                        $is_linked_checkbox = isset($option_data['is_linked_checkbox']) && $option_data['is_linked_checkbox'] != '' ? $option_data['is_linked_checkbox'] : array();
                    } else {
                        $option_label = $option_data;
                        $checkbox_class = '';
                        $tooltip_span = '';
                        $desc = '';
                        $is_checkbox_enabled = true;
                        $is_tristate_active = false;
                        $is_linked_checkbox = array();
                    }

                    $tristate_html = '';
                    $fieldset_class = '';
                    if ($is_tristate_button && $is_tristate_active) {
                        $tristate_button_value = (isset($form_data[$input_name . '_switch'][$key]) ? $form_data[$input_name . '_switch'][$key] : 'default');
                        $tristate_settings = array(
                            'title' => $option_label,
                            'key' => $key,
                            'type' => 'tristate',
                            'input_value' => $tristate_button_value,
                            'option_settings' => array(
                                'name' => $input_name . '_switch' . $add_input_name,
                            )
                        );

                        $fieldset_class = ' class="zamartz-tristate-button-wrapper"';

                        ob_start();
                        $this->get_field_settings($tristate_settings);
                        $tristate_html .= ob_get_clean();
                    }

                    $checkbox_input_html = '';
                    if ($is_checkbox_enabled) {
                        $checkbox_input_html = '
                        <input aria-label="' . $option_label . '" type="checkbox" class="zamartz-checkbox' . $checkbox_class . '"' . ($input_value == true ? ' checked=checked' : '') . ' value="1">
                        <input aria-describedby="' . $tooltip_id . '" name="' . $input_name  . $add_input_name . '" type="hidden">' . $option_label;
                    } else {
                        $checkbox_input_html = $option_label;
                    }

                    $fieldset_html = '
                    <fieldset' . $fieldset_class . '>
                            ' . $tooltip_span . '
                            <label for="' . $input_name . '">
                                ' . $checkbox_input_html . '
                            </label>
                            ' . $desc . '
                            ' . $tristate_html . '
                    </fieldset>';

                    //Is the checkbox linked with another?
                    if (!empty($is_linked_checkbox)) {
                        $checkbox_linked_array[$is_linked_checkbox['id']]['html'][] = $fieldset_html;
                        $checkbox_linked_array[$is_linked_checkbox['id']]['title'] = isset($is_linked_checkbox['title']) ? $is_linked_checkbox['title'] : '';
                    } else {
                        $checkbox_html .= $fieldset_html;
                    }
                }
                if (!empty($checkbox_linked_array)) {
                    foreach ($checkbox_linked_array as $checkbox_linked_name => $checkbox_linked_data) {
                        $linked_input_value = (isset($form_data[$checkbox_linked_name][$key]) && !empty($form_data[$checkbox_linked_name][$key])) ? $form_data[$checkbox_linked_name][$key] : '';
                        $checkbox_settings = array(
                            'title' => '',
                            'key' => $key,
                            'type' => 'toggle_switch',
                            'input_value' => $linked_input_value,
                            'option_settings' => array(
                                'name' => $checkbox_linked_name,
                            )
                        );

                        $checkbox_html .= '<div class="zamartz-linked-checkbox">';

                        ob_start();
                        echo '<div class="zamartz-linked-checkbox-switch">';
                        echo '<span class="zamartz-linked-checkbox-switch-label">' . $checkbox_linked_data['title'] . '</span>';
                        $this->get_field_settings($checkbox_settings);
                        echo '</div>';
                        $checkbox_html .= ob_get_clean();

                        $checkbox_html .= implode('', $checkbox_linked_data['html']);
                        $checkbox_html .= '</div>';
                    }
                }
                echo $checkbox_html;
                break;
            case 'button':
                $input_value = $settings['input_value'];
                $option_settings = $settings['option_settings'];
                $wrapper_class = isset($option_settings['wrapper']['class']) ? $option_settings['wrapper']['class'] : '';
                if (!empty($wrapper_class)) {
                    echo '<div class="' . $wrapper_class . '">';
                }
                $is_spinner_dashicon = isset($option_settings['is_spinner_dashicon']) ? $option_settings['is_spinner_dashicon'] : false;
                if ($is_spinner_dashicon) {
                    $dashicon = '<span class="dashicons dashicons-update spin" style="display: none;"></span>';
                } else {
                    $dashicon = '';
                }
                $data_params = '';
                if (isset($option_settings['data-params']) && !empty($option_settings['data-params'])) {
                    foreach ($option_settings['data-params'] as $data_key => $data_value) {
                        $data_params .= ' data-' . $data_key . '= "' . $data_value . '"';
                    }
                }
                $button_class = isset($option_settings['class']) ? $option_settings['class'] : '';
                echo '<button type="button" class="' . $button_class . '"' . $data_params . '>' . $input_value . '</button>
				' . $dashicon . '
				<span tabindex="0" class="zamartz-message"></span>';
                if (!empty($wrapper_class)) {
                    echo '</div>';
                }
                break;
            case 'dismiss_notice':
                $input_value = $settings['input_value'];
                $option_settings = isset($settings['option_settings']) ? $settings['option_settings'] : array();
                $button_class = isset($option_settings['class']) ? $option_settings['class'] : '';
                echo '
				<div class="notice notice-info zamartz-dismiss-notice">
					<p>
						' . $input_value . '
						<button type="button" class="notice-dismiss ' . $button_class . '">
							<span class="screen-reader-text">' . __('Dismiss this notice.', "wp-zamartz-admin") . '</span>
						</button>
					</p>
				</div>';
                break;
            case 'select':
                $option_settings = $settings['option_settings'];
                $is_select2 = isset($settings['is_select2']) ? $settings['is_select2'] : false;
                $is_multi = isset($settings['is_multi']) ? $settings['is_multi'] : false;

                $selected_value = isset($settings['input_value']) ? $settings['input_value'] : '';
                $input_name = $option_settings['name'];
                if (empty($selected_value) && !empty($key)) {
                    $selected_value = (isset($form_data[$input_name][$key]) && $form_data[$input_name][$key] !== false) ? $form_data[$input_name][$key] : '';
                } elseif (empty($selected_value) && empty($key)) {
                    $selected_value = (isset($form_data[$input_name]) && $form_data[$input_name] !== false) ? $form_data[$input_name] : '';
                }

                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';
                $input_name .= $add_input_name;

                if ($is_multi === true) {
                    $input_name .= '[]';
                    $multiple = ' multiple="multiple"';
                } else {
                    $multiple = '';
                }

                $data_params = '';
                if (isset($option_settings['data-params']) && !empty($option_settings['data-params'])) {
                    foreach ($option_settings['data-params'] as $data_key => $data_value) {
                        $data_params .= ' data-' . $data_key . '= "' . $data_value . '"';
                    }
                }

                $required = isset($option_settings['is_required']) && $option_settings['is_required'] == true ? ' required' : '';
                echo '<select' . $tooltip_id_text . $required . ' name="' . $input_name . '" class="' . $option_settings['class'] . '"' . $multiple . $data_params . '>';
                foreach ($field_options as $option_index => $option_title) {
                    if ($is_select2 === true) {
                        $selected = " selected";
                    } elseif (is_array($selected_value) && in_array($option_index, $selected_value)) {
                        $selected = " selected";
                    } elseif ($selected_value == $option_index) {
                        $selected = " selected";
                    } else {
                        $selected =  '';
                    }
                    echo '<option' . $selected . ' value="' . $option_index . '">' . $option_title . '</option>';
                }
                echo '</select>';

                break;
            case 'input_number':
                $title = $settings['title'];
                $option_settings = $settings['option_settings'];
                $input_name = $option_settings['name'] . $add_input_name;
                $input_min = isset($option_settings['min']) && $option_settings['min'] !== false ? ' min="' . $option_settings['min'] . '"' : '';
                $input_max = isset($option_settings['max']) && $option_settings['max'] !== false ? ' max="' . $option_settings['max'] . '"' : '';
                $required = isset($option_settings['is_required']) && $option_settings['is_required'] == true ? ' required' : '';
                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';

                $class = isset($option_settings['class']) && !empty($option_settings['class']) ? ' class="' . $option_settings['class'] . '"' : '';
                if (isset($settings['input_value'])) {
                    $input_value = $settings['input_value'];
                } elseif (isset($form_data) && !empty($key)) {
                    $input_value = $form_data[$option_settings['name']][$key];
                } elseif (isset($form_data) && empty($key)) {
                    $input_value = $form_data[$input_name];
                } else {
                    $input_value = '';
                }

                echo '<input' . $tooltip_id_text . $required . ' aria-label="' . $title . '"' . $class . $input_min . $input_max . ' name="' . $input_name . '" type="number" value="' . $input_value . '">';
                break;
            case 'input_text':
                $title = $settings['title'];
                $option_settings = $settings['option_settings'];

                $read_only = isset($option_settings['read_only']) && !empty($option_settings['read_only']) ? ' readonly' : '';
                $input_name = $option_settings['name'] . $add_input_name;
                $class = isset($option_settings['class']) && !empty($option_settings['class']) ? ' class="' . $option_settings['class'] . '"' : '';
                $required = isset($option_settings['is_required']) && $option_settings['is_required'] == true ? ' required' : '';
                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';

                if (isset($settings['input_value'])) {
                    $input_value = $settings['input_value'];
                } elseif (isset($form_data) && !empty($key) && isset($form_data[$option_settings['name']][$key])) {
                    $input_value = $form_data[$option_settings['name']][$key];
                } elseif (isset($form_data) && empty($key) && isset($form_data[$input_name])) {
                    $input_value = $form_data[$input_name];
                } else {
                    $input_value = '';
                }

                echo '<input' . $tooltip_id_text . $required . ' aria-label="' . $title . '"' . $class . $read_only . ' name="' . $input_name . '" type="text" value="' . $input_value . '">';
                break;
            case 'input_password':
                $title = $settings['title'];
                $option_settings = $settings['option_settings'];

                $read_only = isset($option_settings['read_only']) && !empty($option_settings['read_only']) ? ' readonly' : '';
                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';
                $input_name = $option_settings['name'] . $add_input_name;

                $class = isset($option_settings['class']) && !empty($option_settings['class']) ? ' class="' . $option_settings['class'] . '"' : '';
                $required = isset($option_settings['is_required']) && $option_settings['is_required'] == true ? ' required' : '';

                if (isset($settings['input_value'])) {
                    $input_value = $settings['input_value'];
                } elseif (isset($form_data) && !empty($key)) {
                    $input_value = $form_data[$option_settings['name']][$key];
                } elseif (isset($form_data) && empty($key)) {
                    $input_value = $form_data[$input_name];
                } else {
                    $input_value = '';
                }

                echo '<input' . $tooltip_id_text . $required . ' aria-label="' . $title . '"' . $class . $read_only . ' name="' . $input_name . '" type="password" value="' . $input_value . '">
				<button type="button" class="zamartz-dashicon-button dashicons dashicons-visibility" aria-expanded="false"></button>';
                break;
            case 'toggle_switch':
                $title = $settings['title'];
                $option_settings = $settings['option_settings'];
                $input_name = $option_settings['name'];
                $class = isset($option_settings['class']) && $option_settings['class'] !== false ? ' ' . $option_settings['class'] : '';

                if (isset($settings['input_value']) && $settings['input_value'] == 'yes') {
                    $input_value = true;
                } elseif (isset($settings['input_value']) && $settings['input_value'] == 'no') {
                    $input_value = false;
                } elseif (!isset($settings['input_value']) && !empty($key) && isset($form_data[$input_name][$key]) && $form_data[$input_name][$key] == 'yes') {
                    $input_value = true;
                } elseif (!isset($settings['input_value']) && empty($key) && isset($form_data[$input_name]) && $form_data[$input_name] == 'yes') {
                    $input_value = true;
                } else {
                    $input_value = false;
                }
                $input_name .= $add_input_name;

                $required = isset($option_settings['is_required']) && $option_settings['is_required'] == true ? ' required' : '';
                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';
                echo '
				<label class="switch">
						<input' . $required . ' aria-label="' . $title . '" type="checkbox" class="zamartz-checkbox' . $class . '"' . ($input_value == true ? ' checked=checked' : '') . ' value="1">
						<span class="slider round"></span>
						<input' . $tooltip_id_text . ' name="' . $input_name . '" type="hidden">
                </label>';
                break;
            case 'notice_box':
                $option_settings = isset($settings['option_settings']) ? $settings['option_settings'] : array();

                $notice_title = isset($option_settings['title']) ? $option_settings['title'] : '';
                $notice_type = isset($option_settings['type']) ? $option_settings['type'] : '';
                $notice_description = isset($option_settings['description']) ? $option_settings['description'] : '';
                $notice_btn_link = isset($option_settings['btn_link']) ? $option_settings['btn_link'] : '';
                $notice_btn_text = isset($option_settings['btn_text']) ? $option_settings['btn_text'] : '';
                $notice_btn_class = isset($option_settings['btn_class']) ? ' ' . $option_settings['btn_class'] : '';
                $data_params = '';
                if (isset($option_settings['data-params']) && !empty($option_settings['data-params'])) {
                    foreach ($option_settings['data-params'] as $data_key => $data_value) {
                        $data_params .= ' data-' . $data_key . '= "' . $data_value . '"';
                    }
                }
                echo '
				<div class="zamartz-notice-box">
					<div class="zamartz-notice notice-' . $notice_type . '">
						<p>
							<strong>' . $notice_title . '</strong>
						</p>
						<p class="zamartz-notice-subcontent">
							' . $notice_description . '
						</p>
						<p class="zamartz-notice-box-btn">
							<a ' . $data_params . ' class="button button-primary' . $notice_btn_class . '" href="' . $notice_btn_link . '">' . $notice_btn_text . '</a>
						</p>
					</div>
				</div>
				';
                break;
            case 'textarea':
                $title = $settings['title'];
                $option_settings = $settings['option_settings'];

                $input_name = $option_settings['name'] . $add_input_name;
                $input_row = isset($option_settings['row']) ? ' rows="' . $option_settings['row'] . '"' : '';
                $tooltip_id_text = isset($option_settings['tooltip_id_text']) ? ' aria-describedby="' . $option_settings['tooltip_id_text'] . '"' : '';

                $class = isset($option_settings['class']) && !empty($option_settings['class']) ? ' class="' . $option_settings['class'] . '"' : '';

                if (isset($settings['input_value'])) {
                    $input_value = $settings['input_value'];
                } elseif (isset($form_data) && !empty($key)) {
                    $input_value = $form_data[$input_name][$key];
                } elseif (isset($form_data) && empty($key)) {
                    $input_value = $form_data[$input_name];
                } else {
                    $input_value = '';
                }
                echo '<textarea' . $tooltip_id_text . $input_row . ' aria-label="' . $title . '"' . $class . ' name="' . $input_name . '"">' . $input_value . '</textarea>';
                break;
            case 'multi_column':

                $section_data = isset($settings['option_settings']) ? $settings['option_settings'] : array();
                $form_data = isset($settings['form_data']) ? $settings['form_data'] : array();

                $depth = isset($settings['depth']) ? $settings['depth'] + 1 : 0;

                foreach ($section_data as $index => $column) {
                    $label = isset($column['label']) ? '<label>' . $column['label'] . '</label>' : '';
                    $field_type = $column['type'];
                    $section_class =  isset($column['section_class']) ? $column['section_class'] : '';
                    $option_settings = $column['option_settings'];

                    $field_options =  isset($column['field_options']) ? $column['field_options'] : array();

                    $row_settings = array(
                        'title' => '',
                        'key' => $key,
                        'type' => $field_type,
                        'option_settings' => $option_settings,
                        'field_options' => $field_options,
                        'form_data' => $form_data,
                        'depth' =>  $depth
                    );

                    if (isset($column['input_value']) && $column['input_value'] != '') {
                        $row_settings['input_value'] = $column['input_value'];
                    }

                    if (isset($column['is_multi']) && $column['is_multi'] != '') {
                        $row_settings['is_multi'] = $column['is_multi'];
                    }

                    if (isset($column['is_select2']) && $column['is_select2'] != '') {
                        $row_settings['is_select2'] = $column['is_select2'];
        }

                    if ($depth == 0) {
                        echo '<div class="' . $section_class . '">';
                    }
                    echo $label;
                    if (isset($column['tooltip_desc']) && !empty($column['tooltip_desc'])) {
                        $tooltip_id = $option_settings['name'];
                        $tooltip_id .= !empty($key) ? '_' . $key : '';
                        $tooltip_id_text = $tooltip_id . '_tooltip';
                        echo '<span role="tooltip" id="' . $tooltip_id_text . '" aria-label="' . $column['tooltip_desc'] . '" data-tip="' . $column['tooltip_desc'] . '" class="zamartz-help-tip"></span>';
                        $option_settings['tooltip_id_text'] = $tooltip_id_text;
                    }
                    $this->get_field_settings($row_settings, false);

                    if ($depth == 0) {
                        echo '</div>';
        }
                }

                break;
        }

        if ($is_label) {
            echo '</td>';
        }
    }

    /**
     * Get Zamartz settings tab navigation
     * @param	array	$tab_list		    In-page sub menu navigation settings
     * @param	string	$current_tab		Current selected tab
     * @param	array	$section_list   	List to populate section navigation link
     * @param	string	$current_section	Current selected section
     */
    public function get_navigation_html($tab_list, $current_tab = '', $section_list = array(), $current_section = '')
    {
        if (!empty($tab_list)) {
            echo '<nav class="nav-tab-wrapper">';
            foreach ($tab_list as $tab) {
                $tab_title = $tab['title'];
                $tab_url = (isset($tab['slug']) && !empty($tab['slug'])) ? '&tab=' . $tab['slug'] : '';
                if (is_network_admin()) {
                    $url = esc_url(network_admin_url('admin.php?page=zamartz-network-settings' . $tab_url));
                } else {
                    $url = esc_url(admin_url('admin.php?page=zamartz-settings' . $tab_url));
                }

                if ($tab['slug'] == $current_tab && !empty($tab['slug'])) {
                    $tab_active_class = ' nav-tab-active';
                } else {
                    $tab_active_class = '';
                }
                echo '<a href="' . $url . '" class="nav-tab' . $tab_active_class . '">' . $tab_title . '</a>';
            }
            echo '</nav>';
            $this->get_zamartz_sub_navigation($section_list, $current_tab, $current_section, $url);
        }
    }

    /**
     * Get Zamartz settings tab subnav
     */
    public function get_zamartz_sub_navigation($section_list, $current_tab, $current_section, $tab_url)
    {
        if (is_array($section_list) && !empty($section_list)) {
            if (!isset($section_list[$current_tab]) || $section_list[$current_tab] == '') {
                return;
            }
            echo '<ul class="subsubsub zamartz-sub-navigation">';
            foreach ($section_list[$current_tab] as $menu_slug => $menu_name) {
                if ($menu_slug === $current_section) {
                    $class = ' class="current"';
                } else {
                    $class = '';
                }
                $section_url = (!empty($menu_slug)) ? $tab_url . '&section=' .  $menu_slug : $tab_url;
                echo '<li><a href="' . $section_url . '"' . $class . '>' . $menu_name . '</a></li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Add modal form for deactivation
     */
    public function get_deactivation_plugin_modal()
    {
        $screen = get_current_screen();
        if ($screen->id != 'plugins' && $screen->id != 'plugins-network') {
            return;
        }

        echo '
		<div data-plugin="' . $this->plugin_url['base_plugin_name'] . '" class="zamartz-modal">
		  <div class="zamartz-modal-content">
                <div class="zamartz-modal-header">
                    <span class="zamartz-modal-close">×</span>
                    <h3>If you have a moment, please let us know why you are deactivating</h3>
                </div>
                <div class="zamartz-modal-body">
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="not_needed" type="radio" class="zamartz-checkbox">
                            <span>I do not need this plugin any longer</span>
                            <span class="zamartz-modal-input">
                                <input type="text" name="zamartz-deactivation-answer" placeholder="What changed?">
                            </span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="better_plugin" type="radio" class="zamartz-checkbox">
                            <span>I found a plugin that better fulfills my needs</span>
                            <span class="zamartz-modal-input">
                                <input type="text" name="zamartz-deactivation-answer" placeholder="What is the alternative plugin\'s name?">
                            </span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="temporary_fix" type="radio" class="zamartz-checkbox">
                            <span>I only needed this for a short time / temporary fix</span>
                            <span class="zamartz-modal-input">
                                <input type="text" name="zamartz-deactivation-answer" placeholder="What was the temporary need?">
                            </span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="plugin_broke" type="radio" class="zamartz-checkbox">
                            <span>This plugin broke my site when activated</span>
                            <span class="zamartz-modal-input">
                                <input type="text" name="zamartz-deactivation-answer" placeholder="What version of wordpress/other-plugins/themes are you using?">
                            </span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="plugin_stopped" type="radio" class="zamartz-checkbox">
                            <span>The plugin stopped working at some point</span>
                            <span class="zamartz-modal-input">
                                <input type="text" name="zamartz-deactivation-answer" placeholder="What did you do prior to it not working?">
                            </span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input name="zamartz-deactivation-option" value="plugin_other" type="radio" class="zamartz-checkbox">
                            <span>Other (OR DO NOT SHARE)</span>
                        </label>
                    </p>
                </div>
                <div class="zamartz-modal-footer">
                    <button data-action="' . $this->plugin_input_prefix . 'deactitvate_plugin" type="button" class="zamartz-submit-deactivate button button-default">
                        Submit & Deactivate
                        <span class="dashicons dashicons-update spin" style="display: none;"></span>
                    </button>
                    <button type="button" class="button button-primary zamartz-modal-close">Cancel</button>
                </div>
		    </div>
		</div>';
    }
}
