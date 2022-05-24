var withEvent = true;

define([
    'jquery',
    'mage/cookies',
    'mage/url'
], function ($, cookie, url) {
    'use strict';

    return function (originWidget) {
        $.widget('mage.SwatchRenderer', originWidget, {
            addBtnEnable: function () {
                let addBtnSelector = '#product-addtocart-button, #product-updatecart-button'
                this.addBtn = $(addBtnSelector);
                this.addBtn.prop('disabled', false);
            },

            addBtnDisable: function () {
                let addBtnSelector = '#product-addtocart-button, #product-updatecart-button'
                this.addBtn = $(addBtnSelector);
                this.addBtn.prop('disabled', true);
            },

            /**
             * Event for swatch options
             *
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnClick: function ($this, $widget) {
                this._super($this, $widget);
                // console.log('loaded custom option event');

                let allAttributesChecked = true;
                let checkedOptionIds = [];
                $('#product-options-wrapper').find('[data-attribute-id]').each(function (index) {
                    let checkedOptions = $(this).find('[aria-checked="true"]');
                    if (checkedOptions.length === 0) {
                        allAttributesChecked = false
                    } else if (checkedOptions.length > 0) {
                        checkedOptionIds.push($(checkedOptions[0]).data('option-id'))
                    }
                });

                let checkedOptions = $('#product-options-wrapper').find('[aria-checked="true"]')
                let clickedOptionId = $($this).data('option-id');
                let pfTemplateContainer = $('[data-pf-template-container]');
                if (allAttributesChecked) {
                    // console.log("All attributes checked, Show correct template for simple product");
                    $(pfTemplateContainer).each(function (index, element) {
                        let allCheckedOptionIdsFound = true;

                        if ($(element).data('productType') === 'simple') {
                            let superAttributesData = $(element).data('super-attributes')
                            if (typeof superAttributesData === 'object') {
                                let superAttributesDataValues = _.values(superAttributesData)
                                if (superAttributesDataValues.length > 0) {
                                    $(superAttributesDataValues).each(function (index, element) {
                                        if ($.inArray(element, checkedOptionIds) !== 1 && $.inArray(element, checkedOptionIds) !== 0) {
                                            allCheckedOptionIdsFound = false;
                                        }
                                    }, checkedOptionIds);
                                } else {
                                    allCheckedOptionIdsFound = false;
                                }
                            }
                        } else {
                            allCheckedOptionIdsFound = false;
                        }

                        if (allCheckedOptionIdsFound) {
                            $('[data-product-type]').hide();
                            $(element).show();
                            if ($(element).children('[data-pf-draft]').data('pf-draft') === 'active') {
                                $widget.addBtnEnable();
                            } else {
                                $widget.addBtnDisable();
                            }
                        }
                    }, checkedOptionIds);

                } else if (checkedOptions.length > 0) {
                    $('[data-product-type]').hide();
                    // console.log("Missing attribute-selections, waiting for completed selection. Hide all templates");
                } else if (checkedOptions.length === 0) {
                    // console.log("No option selected. Show templates of configurable product if possible");
                    $('[data-product-type][data-product-type="simple"]').hide();
                    $('[data-product-type="configurable"][data-product-type]').show('');
                }
            },
        });

        return $.mage.SwatchRenderer;
    }
});
