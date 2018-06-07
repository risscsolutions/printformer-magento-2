define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.printformerPreselection', {
        isDefined: function(value) {
            return typeof value !== typeof undefined;
        },

        preselectOptions: function(selectedOptions) {
            var that = this;
            if (this.isDefined(selectedOptions)) {
                if (this.isDefined(selectedOptions.qty) && this.isDefined(selectedOptions.qty.value)) {
                    var qtySelector = '#qty';
                    if ($(qtySelector).length) {
                        if ($(qtySelector).prop('tagName').toLowerCase() === 'input') {
                            $(qtySelector).val(parseInt(selectedOptions.qty.value));
                        } else {
                            $(qtySelector).val(selectedOptions.qty.value);
                        }
                        $(qtySelector).trigger('change');
                    }
                }
                if (this.isDefined(selectedOptions['options']) && selectedOptions['options'] !== null) {
                    var preselectoptions = $('.product-options-wrapper :input');
                    var inputId = null;
                    $.each(preselectoptions, function (i, opt) {
                        if (
                            $(opt).hasClass('product-custom-option') &&
                            ($(opt).is('textarea') || $(opt).attr('type') === 'text')
                        ) {
                            var regex = new RegExp(/options_([0-9]+)_.*/i);
                            inputId = $(opt).attr('id').replace(regex, '$1');
                            if (that.isDefined(selectedOptions['options'][inputId])) {
                                $(opt).val(selectedOptions['options'][inputId].value);
                            }
                        }
                        if (
                            $(opt).hasClass('product-custom-option') &&
                            $(opt).attr('type') === 'checkbox'
                        ) {
                            var checkboxId = $(opt).attr('id').replace(/options_([0-9]+)_[0-9]+/i, '$1');
                            if (that.isDefined(selectedOptions['options'][checkboxId])) {
                                $.each(selectedOptions['options'][checkboxId].value, function (o, option) {
                                    if ($(opt).val() === option) {
                                        $(opt).prop('checked', true);
                                    }
                                });
                            }
                        }
                        if (
                            $(opt).prop('tagName').toLowerCase() === 'select' &&
                            $(opt).hasClass('product-custom-option')
                        ) {
                            if ($(opt).hasClass('datetime-picker')) {
                                $.each(selectedOptions['options'], function (i, optionValue) {
                                    if ($(opt).data('selector') === 'options[' + i + '][' + $(opt).data('calendar-role') + ']') {
                                        if (that.isDefined(optionValue.value[$(opt).data('calendar-role')])) {
                                            $(opt).val(optionValue.value[$(opt).data('calendar-role')]);
                                        }
                                    }
                                });
                            } else {
                                $.each(selectedOptions['options'], function (i, optionValue) {
                                    if ($(opt).data('selector') === 'options[' + i + ']') {
                                        $(opt).val(optionValue.value);
                                    }
                                });
                            }
                        }
                        if (
                            $(opt).hasClass('product-custom-option') &&
                            $(opt).attr('type') === 'radio'
                        ) {
                            $.each(selectedOptions['options'], function (i, optionValue) {
                                if (
                                    $(opt).data('selector') === 'options[' + i + ']' &&
                                    $(opt).val() === optionValue.value
                                ) {
                                    $(opt).prop('checked', true);
                                }
                            });
                        }

                        $(opt).trigger('change');
                    });
                }
            }
            return this;
        }
    });

    return $.mage.printformerPreselection;
});