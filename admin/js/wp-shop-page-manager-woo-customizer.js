/**
 * Plugin specific JQuery functionality
 *
 */

(function ($) {
    $(document).ready(function () {

        wp.customize.section('shop_page_manager_section', function (section) {
            section.expanded.bind(function (isExpanded) {
                if (isExpanded) {
                    wp.customize.previewer.previewUrl.set(zamartz_customizer_localized_object.shop_url);
                }
            });
        });

        //Set customizer setting section input field value
        function set_settings_serialized_data() {
            var form = $("#zamartz-customizer-wrapper").find("select, textarea, input");
            form.find('.zamartz-checkbox').each(function () {
                if ($(this).is(':checked')) {
                    $(this).parent().find('input[type=hidden]').val('yes');
                } else {
                    $(this).parent().find('input[type=hidden]').val('no');
                }
            });
            var serialized_data = form.serialize();
            $("#zamartz-customizer-wrapper").find('select').each(function () {
                if ($(this).val() == null) {
                    var name = $(this).attr('name');
                    var encodedUrl = encodeURIComponent(name);
                    serialized_data = serialized_data + '&' + encodedUrl;
                }
            });
            $('#_customize-input-zamartz_shop_page_manager_rulesets').val(serialized_data).trigger('change');
        }
        var zamartzMain = window.zamartzMain;
        var shopManager = window.shopManager;

        //Reinitialize
        zamartzMain.init();
        zamartzMain.activateTipTip();
        $(document.body).trigger('wc-enhanced-select-init');

        //Fix accordion field size issue
        $(window).on('load resize', function () {
            shopManager.resize_zamartz_accordion();
        });


        //Trigger change state for customizer on change of form fields
        $('#zamartz-customizer-wrapper').on('change', 'select, textarea, input', function () {
            set_settings_serialized_data();
        });

        $('#customize-control-woo_shop_manager_ruleset_toggle').on('click', '.zamartz-checkbox', function () {
            var hidden_field = $(this).parent().find('input[type=hidden]');
            if ($(this).is(':checked')) {
                hidden_field.val('yes');
            } else {
                hidden_field.val('no');
            }
            hidden_field.trigger('change');
        });

        // The node to be monitored
        var target = $(".zamartz-col-mobile-100")[0];

        if (target != null) {
            // Create an observer instance
            //Trigger change state for customizer on accordion remove and adding new ruleset section
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    var newNode = mutation.addedNodes; // DOM NodeList
                    var removedNode = mutation.removedNodes; // DOM NodeList
                    if (newNode !== null) { // If there are new nodes added
                        var $newNode = $(newNode); // jQuery set
                        if ($newNode.hasClass("zamartz-accordion-delete")) {
                            set_settings_serialized_data();
                        }
                    }
                    if (removedNode !== null) { // If the node is removed
                        var $removedNode = $(removedNode); // jQuery set
                        if ($removedNode.hasClass("zamartz-accordion-delete")) {
                            set_settings_serialized_data();
                        }
                    }
                });
            });

            // Configuration of the observer:
            var config = {
                attributes: true,
                childList: true,
                characterData: true
            };

            // Pass in the target node, as well as the observer options
            observer.observe(target, config);
        }

    });
})(jQuery);