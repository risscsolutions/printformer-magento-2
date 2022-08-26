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

                            let selectedProductId = $(element).data('productId');
                            let simpleProductDrafts = $widget.options.printformerProducts[selectedProductId];
                            var draftIds = [];

                            $.each($widget.options.printformerProducts, function (index, printformerProduct) {
                                if (printformerProduct['product_id'] == selectedProductId) {
                                    if (printformerProduct['draft_ids']) {
                                        $.each(printformerProduct['draft_ids'], function( index, value ) {
                                            if (value != '') {
                                                draftIds.push(value);
                                            }
                                        });
                                    }
                                }
                            });

                            if (draftIds.length > 0) {
                                $('#printformer_draftid').prop('value', draftIds);
                                $widget.element.parents('.product-info-main').find('[data-action="add-to-wishlist"]').data('post').data.printformer_draftid = draftIds;
                            }

                            let dataPfTemplateDraftContainer = $(':visible[data-pf-template-container]').children('[data-pf-draft]');
                            let addBtnSelector = '#product-addtocart-button, #product-updatecart-button';
                            if (dataPfTemplateDraftContainer.length === 0) {
                                $(addBtnSelector).prop('disabled', false)
                            } else {
                                $(addBtnSelector).prop('disabled', true)

                                $.each(dataPfTemplateDraftContainer, function(index, dataPfTemplate){
                                    if ($(dataPfTemplate).data('pf-draft') === 'active'){
                                        $(addBtnSelector).prop('disabled', false)
                                    }
                                }, addBtnSelector);
                            }
                        }
                    }, checkedOptionIds);

                } else if (checkedOptions.length > 0) {
                    $('[data-product-type]').hide();
                } else if (checkedOptions.length === 0) {
                    $('[data-product-type][data-product-type="simple"]').hide();
                    $('[data-product-type="configurable"][data-product-type]').show('');
                }
            },
        });

        return $.mage.SwatchRenderer;
    }
});
