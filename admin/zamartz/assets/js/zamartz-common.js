/**
 * Zamartz common JS logic
 *
 */
(function ($) {
    'use strict';
    var zamartzMain = {
        activateTipTip: function () {
            jQuery('.zamartz-wrapper .zamartz-help-tip').tipTip({
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        },
        clear_message: function (message) {
            message.removeClass('inline');
            message.removeClass('success');
            message.removeClass('updated');
            message.removeClass('error');
            message.html('');
        },
        init: function () {
            var zamartz_section_type = zamartzMain.getSectionType();
            var zamartz_select2_args = zamartzMain.getSelect2Args();
            zamartzMain.mainHandler(zamartz_section_type, zamartz_select2_args);
            zamartzMain.affixHandler();
        },
        getSectionType: function () {
            return $('.zamartz-wrapper').data('section_type');
        },
        getSelect2Args: function () {
            return {
                ajax: {
                    url: zamartz_localized_object.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term,
                            page: params.page || 1,
                            action: $(this).data('action'),
                            type: $(this).data('type'),
                            limit: $(this).data('limit') || 5,
                        };
                    },
                    processResults: function (data) {
                        var terms = [];
                        var pagination = [];
                        if (data) {
                            terms = data.query_data;
                            pagination = data.pagination
                        }
                        return {
                            results: terms,
                            pagination: pagination
                        };
                    },
                    cache: true
                },
                escapeMarkup: function (markup) { return markup; },
                allowClear: $(this).data('allow_clear') ? true : false,
                placeholder: $(this).data('placeholder'),
                minimumInputLength: 4
            }
        },
        getValidation: function (form) {
            var return_value = true;
            //Reset all form accordion errors
            $('.zamartz-form-section').each(function () {
                $(this).removeClass('zamartz-accordion-error');
            });
            form.find('input:not([type="checkbox"]), textarea, select').each(function () {
                var element = $(this);
                var form_accordion = element.closest('.zamartz-form-section');
                var attr = element.attr('required');
                if (typeof attr !== typeof undefined && attr !== false && element.val() === '') {
                    if (element.parent().find(".zamartz-message.error").length === 0) {
                        $('<span class="zamartz-message error">Field is required and cannot be empty</span>').appendTo(element.parent());
                        form_accordion.addClass('zamartz-accordion-error');
                        return_value = false;
                    }
                } else {
                    element.parent().find(".zamartz-message.error").remove();
                }
            });
            return return_value;
        },
        reset_accordion_number: function () {
            var loop = 1;
            $('.zamartz-wrapper .zamartz-loop-number').each(function () {
                $(this).html(loop);
                loop++;
            });
        },
        mainHandler: function (zamartz_section_type, zamartz_select2_args) {
            var input_prefix = $('.zamartz-wrapper').data('input_prefix');

            $(".zamartz-select2-search-dropdown").each(function () {
                $(this).select2(zamartz_select2_args);
            });

            $('.zamartz-wrapper #publishing-action input[name=save]').on('click', function (e) {
                e.preventDefault();
                var current_object = $(this);
                if (current_object.hasClass('disabled')) {
                    return;
                }

                var form_action = current_object.data('action');

                $('#publishing-action .spinner').addClass('is-active');
                current_object.addClass('disabled');
                current_object.val('Saving...');

                //Get form data
                var form = current_object.closest('form');

                //Add validation function here
                var return_value = zamartzMain.getValidation(form);
                if (return_value === false) {
                    current_object.val('Error!');
                    $('#publishing-action .spinner').removeClass('is-active');
                    setTimeout(function () {
                        current_object.removeClass('disabled');
                        current_object.val('Save Changes');
                    }, 1500);
                    return false;
                }

                form.find('.zamartz-checkbox').each(function () {
                    if ($(this).is(':checked')) {
                        $(this).parent().find('input[type=hidden]').val('yes');
                    } else {
                        $(this).parent().find('input[type=hidden]').val('no');
                    }
                });
                var serialize_data = form.serialize();
                
                var pageChk = form.find('input[name=_wp_http_referer]').val();
                if( pageChk.indexOf("billing_field_visibility") >= 0 ){
                    form.find('select').each(function () {
                        if ( $(this).val() == null ) {
                            var name = $(this).attr('name');
                            var encodedUrl = encodeURIComponent(name);
                            serialize_data = serialize_data + '&' + encodedUrl;
                        }
                    });
                }
                else {
                    form.find('select').each(function () {
                        if ( $(this).val().length === 0 ) {
                            var name = $(this).attr('name');
                            var encodedUrl = encodeURIComponent(name);
                            serialize_data = serialize_data + '&' + encodedUrl;
                        }
                    });
                }

                var data = {
                    form_data: serialize_data
                }
                if (typeof form_action != 'undefined') {
                    data['action'] = form_action;
                }
                if (typeof zamartz_section_type != 'undefined') {
                    data['section_type'] = zamartz_section_type;
                }

                //Pass to ajax
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function (json) {
                        var response = jQuery.parseJSON(json);
                        $('#zamartz-message').html(response.message).removeClass();
                        $('#zamartz-message').html(response.message).addClass(response.class);
                        current_object.val('Saved!');
                        $('#publishing-action .spinner').removeClass('is-active');
                        setTimeout(function () {
                            current_object.removeClass('disabled');
                            current_object.val('Save Changes');
                        }, 1500);
                        setTimeout(function () {
                            $('#zamartz-message').html('').removeClass();
                        }, 3000);
                    }
                })
            });

            $('.zamartz-wrapper').on('click', '.zamartz-panel-header', function () {
                var current_this = $(this);
                var current_toggle_indicator = current_this.find('.zamartz-toggle-indicator');
                var parent_section = current_this.closest('.zamartz-form-section');
                if (parent_section.data('linked_class')) {
                    //Linked accordion class logic

                    //Store linked class name
                    var linked_class_string = parent_section.data('linked_class');
                    var linked_class_obj = $('.zamartz-wrapper').find('.' + linked_class_string);

                    if (current_toggle_indicator.hasClass('closed')) {

                        linked_class_obj.each(function () {
                            $(this).find('.zamartz-form-table').slideUp();
                            $(this).find('.zamartz-toggle-indicator').addClass('closed').attr("aria-expanded", "false");
                        });
                        current_toggle_indicator.removeClass('closed').attr("aria-expanded", "true");
                        parent_section.find('.zamartz-form-table').slideToggle();
                    } else {
                        current_toggle_indicator.addClass('closed').attr("aria-expanded", "false");
                        parent_section.find('.zamartz-form-table').slideUp();
                    }
                } else {
                    //Simple accordion logic
                    if (current_toggle_indicator.hasClass('closed')) {
                        current_toggle_indicator.removeClass('closed').attr("aria-expanded", "true");
                        parent_section.find('.zamartz-form-table').slideToggle();
                    } else {
                        current_toggle_indicator.addClass('closed').attr("aria-expanded", "false");
                        parent_section.find('.zamartz-form-table').slideUp();
                    }
                }
            });

            $('.zamartz-wrapper').on('click', '.zamartz-dismiss-notice button.notice-dismiss', function () {
                $(this).closest('.zamartz-dismiss-notice').hide('slow', function ($) {
                    $(this).remove();
                });
            });

            /**
             * AJAX call run to update event tracking information on first run
             */
            $('.zamartz-event-tracker-button').on('click', function (e) {
                e.preventDefault();
                var btn_type = $(this).data('type');
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wp_zamartz_admin_event_tracker_ajax',
                        btn_type: btn_type,
                    },
                    success: function (response) {
                        var responseData = jQuery.parseJSON(response);
                        if (responseData.success == true) {
                            $('.zamartz-admin-tracker-notice').hide('slow');
                        }
                    }
                });
            });

            $('.zamartz-wrapper').on('click', '.zamartz-dashicon-button.dashicons', function () {
                if ($(this).hasClass('dashicons-visibility')) {
                    $(this).siblings('input[type="password"]').attr('type', 'text');
                    $(this).addClass('dashicons-hidden').removeClass('dashicons-visibility');
                    $(this).attr("aria-expanded", "true");
                } else if ($(this).hasClass('dashicons-hidden')) {
                    $(this).siblings('input[type="text"]').attr('type', 'password');
                    $(this).addClass('dashicons-visibility').removeClass('dashicons-hidden');
                    $(this).attr("aria-expanded", "false");
                }
            });

            $('.zamartz-wrapper').on('keyup', 'input[type="number"]', function () {
                let this_value = $(this).val();
                if (isNaN(parseInt(this_value))) {
                    $(this).val('')
                }
            });
            $('.zamartz-wrapper').on('change', 'input[type="number"]', function () {
                var this_value = parseInt($(this).val());
                if ($(this).get(0).hasAttribute('min')) {
                    var min_value = parseInt($(this).attr('min'));
                    if (this_value < min_value) {
                        $(this).val(min_value);
                    }
                }
                if ($(this).get(0).hasAttribute('max')) {
                    var max_value = $(this).attr('max');
                    if (this_value > max_value) {
                        $(this).val(max_value);
                    }
                }
                // var this_value = $(this).val();
            });

            $('.zamartz-wrapper').on('click', '.zamartz-clear-api-credentials', function () {
                var link = $(this);
                var parent = link.closest('.zamartz-enable-disable-api');
                var button = parent.find('button');
                var dashicon = parent.find('span.dashicons');
                var message = parent.find('.zamartz-message');
                var settings_nonce = $('#zamartz_settings_nonce').val();


                message.removeClass().html('').addClass('zamartz-message'); //Clear message
                dashicon.show(); //Display spin icon
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: input_prefix + 'clear_api_credentials_ajax',
                        input_prefix: input_prefix,
                        settings_nonce: settings_nonce
                    }
                }).always(function (json) {
                    //Hide spinner
                    dashicon.hide();

                    //Get AJAX response
                    var response = jQuery.parseJSON(json);

                    if (response.status === false) {
                        message.addClass('error').html(response.message);
                    } else {
                        //Display success message
                        message.addClass('success').html(response.message);

                        $('input[name="' + input_prefix + 'api_license_key"]').val('').removeAttr('readonly');
                        $('input[name="' + input_prefix + 'api_password"]').val('').removeAttr('readonly');
                        $('input[name="' + input_prefix + 'api_product_id"]').val('').removeAttr('readonly');
                        $('input[name="' + input_prefix + 'api_purchase_emails"]').val('').removeAttr('readonly');

                        //Update button content
                        button.removeClass(); //Clear all classes
                        button.addClass(response.data.button_class);
                        button.html(response.data.button_text);
                        button.data('type', response.data.button_attr);

                        //Update sidebar plugin information
                        $('#zamartz-plugin-api-version td:nth-of-type(2)').html(response.data.plugin_api_version);
                        $('#zamartz-plugin-api-authorization td:nth-of-type(2)').html(response.data.plugin_api_authorization);
                    }
                    message.focus();
                });
            });

            $('.zamartz-wrapper .zamartz-enable-disable-api').on('click', 'button', function (e) {
                var button = $(this);
                var type = button.data('type');
                var license_key = $('input[name="' + input_prefix + 'api_license_key"]').val();
                var api_password = $('input[name="' + input_prefix + 'api_password"]').val();
                var product_id = $('input[name="' + input_prefix + 'api_product_id"]').val();
                var purchase_email = $('input[name="' + input_prefix + 'api_purchase_emails"]').val();
                var settings_nonce = $('#zamartz_settings_nonce').val();
                var dashicon = button.parent().find('span.dashicons');
                var message = button.parent().find('.zamartz-message');
                //Clear message
                zamartzMain.clear_message(message);
                dashicon.show();

                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: input_prefix + 'activate_ajax',
                        license_key: license_key,
                        product_id: product_id,
                        purchase_email: purchase_email,
                        api_password: api_password,
                        settings_nonce: settings_nonce,
                        type: type,
                    }
                }).always(function (json) {
                    //Hide spinner
                    dashicon.hide();

                    //Get AJAX response
                    var response = jQuery.parseJSON(json);
                    if (response.status === false) {
                        message.addClass('error').html(response.message);
                    } else {
                        //Display success message
                        message.addClass('success').html(response.message);

                        //Update button content
                        button.removeClass(); //Clear all classes
                        button.addClass(response.data.button_class);
                        button.html(response.data.button_text);
                        button.data('type', response.data.button_attr);

                        if (response.data.button_attr == 'activation') {
                            $('input[name="' + input_prefix + 'api_license_key"]').val('').removeAttr('readonly');
                            $('input[name="' + input_prefix + 'api_password"]').val('').removeAttr('readonly');
                            $('input[name="' + input_prefix + 'api_product_id"]').val('').removeAttr('readonly');
                            $('input[name="' + input_prefix + 'api_purchase_emails"]').val('').removeAttr('readonly');
                        } else {
                            $('input[name="' + input_prefix + 'api_license_key"]').attr('readonly', true);
                            $('input[name="' + input_prefix + 'api_password"]').attr('readonly', true);
                            $('input[name="' + input_prefix + 'api_product_id"]').attr('readonly', true);
                            $('input[name="' + input_prefix + 'api_purchase_emails"]').attr('readonly', true);
                        }

                        //Update sidebar plugin information
                        $('#zamartz-plugin-api-version td:nth-of-type(2)').html(response.data.plugin_api_version);
                        $('#zamartz-plugin-api-authorization td:nth-of-type(2)').html(response.data.plugin_api_authorization);
                    }
                    message.focus();
                });
            });

            $('.zamartz-wrapper .zamartz-import-settings').on('click', 'button', function (e) {
                var button = $(this);
                var settings_nonce = $('#zamartz_settings_nonce').val();
                var dashicon = button.parent().find('span.dashicons');
                var message = button.parent().find('.zamartz-message');
                //Clear message
                zamartzMain.clear_message(message);

                dashicon.show();
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: input_prefix + 'import_settings_ajax',
                        settings_nonce: settings_nonce,
                    }
                }).always(function (json) {
                    //Hide spinner
                    dashicon.hide();
                    //Get AJAX response
                    var response = jQuery.parseJSON(json);
                    if (response.status === false) {
                        message.addClass('error').html(response.message);
                    } else {
                        //Display success message
                        message.addClass('success').html(response.message);
                    }
                    message.focus();
                });
            });

            $('.zamartz-wrapper').on('click', '.zamartz-delete-accordion', function (e) {
                e.stopImmediatePropagation();
                if ($('.zamartz-wrapper .zamartz-delete-accordion').length == 1) {
                    $('#zamartz-message').html('').removeClass();
                    $('#zamartz-message').addClass('error inline').html('<p>Error: One rule set must be active. If there are no rulesets deactivate section or set a rule that would not happen in normal use.</p>');
                    setTimeout(function () {
                        $('#zamartz-message').html('').removeClass();
                    }, 7000);
                    return;
                }
                $(this).closest('.zamartz-accordion-delete').hide('slow', function () {
                    $(this).remove();
                    zamartzMain.reset_accordion_number();
                });

            });

            $('#zamartz-status-debug').on('click', function () {
                var message = $('#zamartz-message');
                zamartzMain.clear_message(message);

                var dashicon = $(this).parent().find('.dashicons');
                dashicon.show();
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: input_prefix + 'get_api_status_ajax',
                    }
                }).always(function (json) {
                    console.log(json);
                    //Hide dashicon spinnger
                    dashicon.hide();

                    //Get AJAX response
                    var response = jQuery.parseJSON(json);

                    if (response.status === false) {
                        message.html(response.message).addClass(response.class);
                    } else {
                        //Display success message
                        message.html(response.message);
                    }
                });
            });


            $('.zamartz-wrapper').on('click', '#zamartz-api-key-refresh', function () {

                var refresh_spinner = $(this);
                var settings_nonce = $('#zamartz_settings_nonce').val();
                var message = $('#zamartz-message');
                var plugin_prefix = $(this).data('plugin_prefix');

                refresh_spinner.addClass('spin');
                //Run ajax call to retrieve API data
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: plugin_prefix + 'get_network_api_status_ajax',
                        settings_nonce: settings_nonce,
                    }
                }).always(function (json) {
                    refresh_spinner.removeClass('spin');
                    //Get AJAX response
                    var response = jQuery.parseJSON(json);
                    message.html(response.message).addClass(response.class);
                    setTimeout(function () {
                        message.html('').removeClass(response.class);
                    }, 5000);
                    if (response.status !== false) {
                        $('#zamartz-api-key-used td:nth-of-type(2)').html(response.data.total_activations);
                        $('#zamartz-api-key-available td:nth-of-type(2)').html(response.data.activations_remaining);
                        $('#zamartz-api-key-count-update td:nth-of-type(2) .zamartz-text-red').html(response.data.count_updated);
                    }
                });
            });

            $('tr').on('click', '.deactivate a', function (e) {
                var data_plugin = $(this).closest('tr').data('plugin');
                if ($('.zamartz-modal[data-plugin="' + data_plugin + '"]').length) {
                    e.preventDefault();
                    $('.zamartz-modal[data-plugin="' + data_plugin + '"]').show();
                }
            });
            $('.zamartz-modal-close').on('click', function () {
                $(this).closest('.zamartz-modal').hide();
            });
            $('input[name="zamartz-deactivation-option"]').on('click', function () {
                $(this).closest('.zamartz-modal-body').find('.zamartz-modal-input').each(function () {
                    $(this).find('input').hide();
                });
                $(this).parent().find('.zamartz-modal-input input').show();
            });
            $('.zamartz-submit-deactivate').on('click', function (e) {
                var modal = $(this).closest('.zamartz-modal');
                var action = $(this).data('action');
                var option_selector = modal.find('.zamartz-checkbox:checked');
                var option_selected = option_selector.val();
                var option_value = option_selector.parent().find('.zamartz-modal-input input').val();
                var dashicon = $(this).find('.dashicons');
                //Show spinner
                dashicon.show();
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: action,
                        option_selected: option_selected,
                        option_value: option_value,
                        is_network_admin: zamartz_localized_object.is_network_admin,
                    }
                }).always(function (json) {

                    //Hide spinner
                    dashicon.hide();
                    //Get AJAX response
                    location.reload();

                });
            });

            $('.zamartz-review-now').on('click', function () {
                var current_screen = $(this).data('current_screen');
                var current_status = $(this).data('current_status');
                $.ajax({
                    url: zamartz_localized_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zamartz_review_now_ajax',
                        current_screen: current_screen,
                        current_status: current_status
                    }
                }).always(function (json) {
                    //Get AJAX response
                    var response = jQuery.parseJSON(json);
                    if (response.status === true) {
                        console.log(response.message);
                        window.location.href = "https://profiles.wordpress.org/zamartz/#content-plugins";
                    }
                });
            });

            /** Update Tristate button **/
            $('.zamartz-wrapper').on('click', '.zamartz-tristate-button input', function () {
                var current_input = $(this).val();
                var zamartz_tristate_button = $(this).parent();
                if (current_input == 'yes') {
                    zamartz_tristate_button.addClass('zamartz-tristate-active');
                    zamartz_tristate_button.find('.on-switch-label').addClass('active');
                    zamartz_tristate_button.find('.off-switch-label').removeClass('active');
                } else if (current_input == 'no') {
                    zamartz_tristate_button.addClass('zamartz-tristate-active');
                    zamartz_tristate_button.find('.on-switch-label').removeClass('active');
                    zamartz_tristate_button.find('.off-switch-label').addClass('active');
                } else {
                    zamartz_tristate_button.removeClass('zamartz-tristate-active');
                    zamartz_tristate_button.find('.on-switch-label').removeClass('active');
                    zamartz_tristate_button.find('.off-switch-label').removeClass('active');
                }
            });

            $(window).on('load resize', function () {
                var win = $(this); //this = window

                if (win.width() < 1200) {
                    $('.zamartz-wrapper').find('.select2-container').each(function () {
                        $(this).attr('style', 'min-width: 60% !important');
                    });
                } else if (win.width() >= 1200 && win.width() < 1375) {
                    $('.zamartz-wrapper').find('.select2-container').each(function () {
                        $(this).attr('style', 'min-width: 300px !important');
                    });
                } else {
                    $('.zamartz-wrapper').find('.select2-container').each(function () {
                        $(this).removeAttr('style');
                    });
                }
            });
        },
        affixHandler: function () {
            $(document).ready(function () {

                var toggleAffix = function (affixElement, scrollElement, wrapper, custom_height_add) {

                    var height = affixElement.outerHeight(),
                        top = wrapper.offset().top - custom_height_add;

                    if (scrollElement.scrollTop() >= top) {
                        wrapper.height(height);
                        affixElement.addClass("affix");
                    } else {
                        affixElement.removeClass("affix");
                        wrapper.height('auto');
                    }

                };


                $('[data-toggle="affix"]').each(function () {
                    var ele = $(this),
                        wrapper = $('<div></div>'),
                        custom_height_add = 0;
                    if (ele.data('custom-affix-height')) {
                        custom_height_add = ele.data('custom-affix-height')
                    }

                    ele.before(wrapper);
                    $(window).on('scroll resize', function () {
                        toggleAffix(ele, $(this), wrapper, custom_height_add);
                    });

                    // init
                    toggleAffix(ele, $(window), wrapper, custom_height_add);
                });

            });
        }
    };
    zamartzMain.init();
    zamartzMain.activateTipTip();
    window.zamartzMain = zamartzMain;
})(jQuery);