/**
 * Plugin specific JQuery functionality
 *
 */

(function($) {
    'use strict';
    var shopManager = {
        paid_error: function(element) {
            if (element.parent().find(".zamartz-message.error").length === 0) {
                $('<span class="zamartz-message error">Error: Use with Paid Version Only</span>').appendTo(element.parent());
            }
        },
        resize_zamartz_accordion: function() {
            if ($('.zamartz-wrapper').parents('.customize-pane-child').length) {
                $('.zamartz-wrapper').find('.select2-container').each(function() {
                    $(this).attr('style', 'width: 230px !important');
                });
            }
        },
        clear_message: function(message) {
            message.removeClass('inline');
            message.removeClass('success');
            message.removeClass('updated');
            message.removeClass('error');
            message.html('');
        },
        coreHandler: function(zamartzMain) {
            $(document).ready(function() {

                //Initialize variables
                var zamartz_section_type = zamartzMain.getSectionType();
                var zamartz_select2_args = zamartzMain.getSelect2Args();

                /**
                 * Populate Sub-condition section based on selected "Condition"
                 */
                $('.zamartz-wrapper').on("select2:select", '.zamartz-form-conditions', function(e) {
                    var selected_condition = $(this).val();
                    if ($(this).closest('.zamartz-wrapper').hasClass('plugin-free-version') && selected_condition != 'active_product_count') {
                        shopManager.paid_error($(this));
                    }
                    if (selected_condition == 'active_product_count') {
                        $(this).parent().find('.zamartz-message').remove();
                    }
                    var parent_this = $(this);
                    var key = $(this).closest('.zamartz-form-section').data('current_key')
                    $.ajax({
                        url: zamartz_localized_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'woo_shop_manager_get_form_operator_dropdown_ajax',
                            selected_condition: selected_condition,
                            section_type: zamartz_section_type,
                            key: key
                        },
                        success: function(json) {
                            var response = jQuery.parseJSON(json);
                            parent_this.closest('.form-table').find('.zamartz-form-operator').html(response.form_operator_dropdown);
                            var condition_subfield = parent_this.closest('.form-table').find('.zamartz-condition-subfield');
                            condition_subfield.html(response.form_condition_subfield);
                            condition_subfield.find('.zamartz-select2-search-dropdown').select2(zamartz_select2_args);
                            $('.zamartz-wrapper').trigger('wc-enhanced-select-init');
                            zamartzMain.activateTipTip();
                        }
                    });
                });

                /**
                 * Zamartz Add RuleSet button functionality
                 */
                $('.zamartz-wrapper').on('click', '.zamartz-add-rule-set', function(e) {
                    e.preventDefault();
                    var dashicon = $(this).parent().find('span.dashicons');
                    var key = $('.zamartz-form-rule-section .zamartz-form-section').last().data('current_key');

                    var message = $('.zamartz-add-rule-set-wrapper .zamartz-message');
                    zamartzMain.clear_message(message);
                    dashicon.show();
                    $.ajax({
                        url: zamartz_localized_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'woo_shop_manager_get_form_section_ajax',
                            section_type: zamartz_section_type,
                            key: key
                        },
                        success: function(json) {
                            var response = jQuery.parseJSON(json);
                            if (response.status === true) {
                                $(response.message).insertAfter($('.zamartz-form-rule-section').last());
                                zamartzMain.reset_accordion_number();
                                //Trigger select2 custom and woocommerce defined
                                $('.zamartz-wrapper').trigger('wc-enhanced-select-init');
                                zamartzMain.activateTipTip();
                                $('.zamartz-wrapper .zamartz-accordion-delete').last().find('.zamartz-select2-search-dropdown').each(function() {
                                    $(this).select2(zamartz_select2_args);
                                });
                                shopManager.resize_zamartz_accordion();
                                //Open last accordion
                                $('.zamartz-wrapper').find('.zamartz-accordion-delete .zamartz-panel-header .zamartz-toggle-indicator').last().click().focus();

                            } else if (response.status === false) {
                                message.addClass('error').html(response.message);
                                message.focus();
                            }
                            dashicon.hide();
                        }
                    });
                });

                /**
                 * Check if ruleset priority is already defined
                 */
                $('.zamartz-wrapper').on('change', '.zamartz-rule-set-priority', function(e) {
                    var main_this = $(this);
                    main_this.parent().find('.ruleset-message').remove();
                    var current_value = main_this.val();
                    $('input.zamartz-rule-set-priority').each(function() {
                        if ($(this).val() != '' && !$(this).is(main_this) && $(this).val() === current_value) {
                            main_this.after('<span class="ruleset-message zamartz-message error">No two rules can have same value. All preceding rulesets will be cleared.</span>');
                            return false;
                        }
                    });
                });

                if ($('.zamartz-wrapper').hasClass('plugin-free-version')) {
                    $('.zamartz-wrapper .zamartz-condition-subfield').on('change', 'input, select', function() {
                        var conditions = $(this).closest('table').find('.zamartz-form-conditions');
                        if (conditions.val() == 'active_product_count') {
                            conditions.parent().find('.zamartz-message').remove();
                            return;
                        }
                        shopManager.paid_error(conditions);
                    });
                    $('.zamartz-wrapper .zamartz-form-operator').on('change', 'select', function() {
                        console.log('Form operator changed');
                        var conditions = $(this).closest('table').find('.zamartz-form-conditions');
                        if (conditions.val() == 'active_product_count') {
                            conditions.parent().find('.zamartz-message').remove();
                            return;
                        }
                        shopManager.paid_error(conditions);
                    });
/**
                     * Display error message for all fields with the designated class
                     */
                    $('input.shop-manager-paid-feature').on('click', shop_page_manager_paid_feature_error);
                    $('select.shop-manager-paid-feature').on('focus', shop_page_manager_paid_feature_error );
                    function shop_page_manager_paid_feature_error(e) {
                        e.preventDefault();
                        if (!$(this).hasClass('paid-feature-parent')) {
                            shopManager.paid_error($(this));
                        } else {
                            var td = $(this).closest('td');
                            if (td.find('.additional-content').length !== 0) {
                                shopManager.paid_error(td.find('.additional-content a'));
                            } else {
                                shopManager.paid_error($(this).parent());
                            }
                        }

                    }
                }
                $('#publishing-action').on('click', 'input', function(e) {
                    var message = $('.zamartz-wrapper').find('.zamartz-message');
                    shopManager.clear_message(message);
                });
            });
        }
    };
    shopManager.coreHandler(window.zamartzMain);
    window.shopManager = shopManager;
})(jQuery);