(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define(['jquery', 'jquery/ui', 'Magento_Ui/js/modal/modal', 'mage/translate'], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    /**
     * @param element
     * @param options
     *
     * @returns {printformerTools}
     */
    var printformerTools = function (element, tool, options) {
        this.element = (element instanceof $) ? element : $(element);
        this.options = options;

        return this.init(tool);
    };

    printformerTools.prototype = {
        init: function(tool) {
            if(tool == 'session') {
                return this.sessionData();
            } else if (tool == 'preselect') {
                return this.preselect();
            } else {
                return this;
            }
        },

        isDefined: function(value) {
            return typeof value !== typeof undefined;
        },

        preselect: function() {
            return this.preselectAction.init(this, this.options);
        },

        sessionData: function() {
            return this.sessionDataAction.init(this, this.options);
        }
    };

    printformerTools.prototype.preselectAction = {
        /**
         * @param parent
         * @param options
         *
         * @returns {printformerTools.prototype.preselectAction}
         */
        init: function(parent, options) {
            this.parentObject = parent;
            this.options = options;

            return this;
        },

        initButton: function() {
            var that = this;
            var saveOptionsUrl = that.options.saveUrl;
            $(this.parentObject.element).click(function(){
                var saveOptions = that.getAllOptions();
                $.ajax({
                    url: saveOptionsUrl,
                    method: 'post',
                    data: { product_options : JSON.stringify(saveOptions) }
                }).done(function(data){
                    // console.log(data);
                });
            });

            return this;
        },
        /**
         * @returns {printformerTools}
         */
        getPreselectedOptions: function() {
            var that = this;
            if(
                typeof this.options.preselected != typeof undefined &&
                typeof this.options.preselected.product_options != typeof undefined
            ) {
                var productOptions = this.options.preselected.product_options;
                that.preselectOptions(productOptions);
                $(document).trigger('printformer_preselect_options_after');
            } else {
                $.ajax({
                    url: this.options.optionsUrl,
                    method: 'get',
                    dataType: 'json'
                })
                    .done(function (data) {
                        that.preselectOptions(data);
                        $(document).trigger('printformer_preselect_options_after');
                        return true;
                    });
            }
            return this;
        },
        /**
         * @param selectedOptions
         *
         * @returns {printformerTools}
         */
        preselectOptions: function(selectedOptions) {
            var that = this;
            if(selectedOptions != null) {
                if (this.parentObject.isDefined(selectedOptions)) {
                    if (selectedOptions.product == this.options.currentProduct) {
                        var hasPriceBox = false;
                        var priceModelSelector = '.price-model-box';
                        if ($(priceModelSelector).length) {
                            hasPriceBox = true;
                            var priceModelInputs = $(priceModelSelector).find(':input');
                            $.each(priceModelInputs, function (i, input) {
                                if ($(input).attr('type') == 'radio') {
                                    var elemVal = $(input).val();
                                    if (that.parentObject.isDefined(selectedOptions.pricemodel)) {
                                        if (that.parentObject.isDefined(selectedOptions.pricemodel.value)) {
                                            if (elemVal.length && elemVal == selectedOptions.pricemodel.value) {
                                                $(input).prop('checked', true);
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        if (this.parentObject.isDefined(selectedOptions.qty) && this.parentObject.isDefined(selectedOptions.qty.value)) {
                            var qtySelector = '#qty';
                            if ($(qtySelector).length) {
                                $(qtySelector).val(selectedOptions.qty.value);
                                $(qtySelector).trigger('change');
                            }
                        }

                        var feeSelector = '.product-fees';
                        if($(feeSelector).length) {
                            var fees = $(feeSelector).children('.fee.field');
                            $.each(fees, function(key, fee){
                                var feeInput = $(fee).children('.fee-option');
                                var feeID = $(feeInput).data('fee-id');
                                if(
                                    typeof selectedOptions.product_fees !== typeof undefined &&
                                    typeof selectedOptions.product_fees[feeID] !== typeof undefined
                                ) {
                                    if (selectedOptions.product_fees[feeID].value == true) {
                                        $(feeInput).prop('checked', true);
                                    }
                                }
                            });
                        }

                        var preselectoptions = $('.product-options-wrapper :input');
                        $.each(preselectoptions, function (i, opt) {
                            if (
                                $(opt).hasClass('product-custom-option') &&
                                $(opt).is('textarea')
                            ) {
                                var inputId = $(opt).attr('id');
                                if(that.parentObject.isDefined(selectedOptions[inputId])) {
                                    $(opt).val(selectedOptions[inputId].value);
                                }
                            }
                            if (
                                $(opt).hasClass('product-custom-option') &&
                                $(opt).attr('type') == 'text'
                            ) {
                                var inputId = $(opt).attr('id');
                                if(that.parentObject.isDefined(selectedOptions[inputId])) {
                                    $(opt).prop('value', selectedOptions[inputId].value);
                                }
                            }
                            if (
                                $(opt).hasClass('product-custom-option') &&
                                $(opt).attr('type') == 'checkbox'
                            ) {
                                var checkboxId = $(opt).attr('id');
                                if (that.parentObject.isDefined(selectedOptions[checkboxId]) && selectedOptions[checkboxId].value == $(opt).val()) {
                                    $(opt).prop('checked', true);
                                }
                            }
                            if (
                                $(opt).prop('tagName').toLowerCase() == 'select' &&
                                $(opt).hasClass('product-custom-option')
                            ) {
                                $.each(selectedOptions, function (i, optionValue) {
                                    if ($(opt).data('selector') == 'options[' + i + ']') {
                                        $(opt).val(optionValue.value);
                                    }
                                });
                            }
                            if (
                                $(opt).hasClass('product-custom-option') &&
                                $(opt).attr('type') == 'radio'
                            ) {
                                $.each(selectedOptions, function (i, optionValue) {
                                    if (
                                        $(opt).data('selector') == 'options[' + i + ']' &&
                                        $(opt).val() == optionValue.value
                                    ) {
                                        $(opt).prop('checked', true);
                                    }
                                });
                            }

                            $(opt).trigger('change');
                        });
                    }
                }
            }
            /*var priceCalculate = $(qtySelector).pricecalculate({
             priceContainer: '.price-box .price-container .price-wrapper'
             });*/

            return this;
        },
        /**
         * @returns {{}}
         */
        getAllOptions: function(){
            var options = $('.product-options-wrapper .product-custom-option');
            var saveConfig = {};

            saveConfig['product'] = this.options.currentProduct;
            var checkboxes = [];
            // Loop through all available Options
            $.each($(options), function(i, elem){
                var elemSelector = null,
                    elemId = null,
                    elemVal = null;

                if($(elem).prop('tagName').toLowerCase() == 'select') {
                    // Get the option ID by the elements data-selector attribute
                    elemSelector = $(elem).attr('data-selector').match(/options\[(\d+)\]/);
                    elemId = elemSelector[1];
                    elemVal = $(elem).val();
                    if(elemVal.length) {
                        saveConfig[elemId] = {'value': elemVal};
                    }
                }
                if($(elem).is('textarea')) {
                    elemId = $(elem).attr('id');
                    elemVal = $(elem).val();
                    if(elemVal.length) {
                        saveConfig[elemId] = {'value': elemVal};
                    }
                }
                if($(elem).attr('type') == 'text') {
                    elemId = $(elem).attr('id');
                    elemVal = $(elem).val();
                    if(elemVal.length) {
                        saveConfig[elemId] = {'value': elemVal};
                    }
                }
                if($(elem).attr('type') == 'radio') {
                    if($(elem).prop('checked')) {
                        // Get the option ID by the elements data-selector attribute
                        elemSelector = $(elem).attr('data-selector').match(/options\[(\d+)\]/);
                        elemId = elemSelector[1];
                        elemVal = $(elem).val();
                        if(elemVal.length) {
                            saveConfig[elemId] = {'value': elemVal};
                        }
                    }
                }
                if($(elem).attr('type') == 'checkbox') {
                    checkboxes.push(elem);
                }
            });

            $.each(checkboxes, function(i, checkbox){
                if($(checkbox).prop('checked')) {
                    var elemVal = $(checkbox).val();
                    if(elemVal.length) {
                        saveConfig[$(checkbox).attr('id')] = {'value': elemVal};
                    }
                }
            });

            var qtySelector = '#qty';
            if($(qtySelector).length) {
                var qtyVal = $(qtySelector).val();
                if(qtyVal.length) {
                    saveConfig['qty'] = {'value': qtyVal};
                }
            }

            var feeSelector = '.product-fees';
            if($(feeSelector).length) {
                var fees = $(feeSelector).children('.fee.field');
                $.each(fees, function(key, fee){
                    var feeInput = $(fee).children('.fee-option');
                    var feeID = $(feeInput).data('fee-id');
                    if($(feeInput).prop('checked')) {
                        if(!(typeof saveConfig['product_fees'] == "object")) {
                            saveConfig['product_fees'] = {};
                        }
                        saveConfig['product_fees'][feeID] = {'value': $(feeInput).prop('checked')};
                    }
                });
            }

            var priceModelSelector = '.price-model-box';
            if($(priceModelSelector).length) {
                var priceModelInputs = $(priceModelSelector).find(':input');
                $.each(priceModelInputs, function(i, input){
                    if($(input).attr('type') == 'radio') {
                        if ($(input).prop('checked')) {
                            // Get the option ID by the elements data-selector attribute
                            var elemSelector = $(input).attr('data-selector').match(/options\[(\d+)\]/);
                            var elemVal = $(input).val();
                            if(elemVal.length) {
                                saveConfig['pricemodel'] = {'value': elemVal};
                            }
                        }
                    }
                });
            }

            return saveConfig;
        }
    };

    printformerTools.prototype.sessionDataAction = {
        /**
         * @param parent
         * @param options
         *
         * @returns {printformerTools.prototype.sessionDataAction}
         */
        init: function(parent, options) {
            this.parentObject = parent;
            this.options = options;

            this.initButton();

            return this;
        },
        /**
         * @returns {printformerTools.prototype.sessionDataAction}
         */
        initButton: function() {
            var that = this;

            return this;
        },

        /**
         * @returns {printformerTools.prototype.sessionDataAction}
         */
        getSavedEditorCalls: function(url) {
            var that = this;

            $(this.parentObject.element).click(function(e){
                e.preventDefault();
                $.ajax({
                    url: url,
                    method: 'get',
                    dataType: 'json'
                })
                    .done(function(data){
                        if(data !== null) {
                            var innerHtml = $('<div/>')
                                .html('<h2>' + $.mage.__('You\'ve made two Drafts. Please select the one you want to add to cart.') + '</h2>');
                            var innerSelect = $('<select/>');

                            var allIntentValues = [];
                            var draftCounter = 0;
                            $.each(data, function(i, draft){
                                var draftID = draft.draft_id;
                                var intent = draft.intent;
                                var opt = $('<option/>')
                                    .val(draftID)
                                    .text($.mage.__('draft-' + i))
                                    .data('pf-intent', intent);
                                allIntentValues[draftID] = intent;
                                $(innerSelect).append($(opt));
                                draftCounter++;
                            });
                            $(innerHtml).append($(innerSelect));
                            if(draftCounter > 1) {
                                $(innerHtml).modal({
                                    autoOpen: true,
                                    responsive: true,
                                    clickableOverlay: true,
                                    type: 'popup',
                                    buttons: [{
                                        text: $.mage.__('Confirm'),
                                        attr: {
                                            'data-action': 'confirm'
                                        },
                                        'class': 'action-primary',
                                        click: function () {
                                            var draftInput = $('#printformer_draftid');
                                            var intentInput = $('#printformer_intent');
                                            if ($(draftInput).length && $(intentInput).length) {
                                                $(draftInput).val($(innerSelect).val());
                                                $(intentInput).val(allIntentValues[$(innerSelect).val()]);
                                            }
                                            this.closeModal();
                                            that.deleteDraftSessionAndSubmit(url, $(innerSelect).val());
                                        }
                                    }]
                                });
                            } else {
                                that.deleteDraftSessionAndSubmit(url);
                            }
                        } else {
                            that.deleteDraftSessionAndSubmit(url);
                        }
                    });
                return false;
            });

            return this;
        },

        deleteDraftSessionAndSubmit: function(url, excludeDraft) {

            var deleteUrl = url + '?delete=true';
            if(typeof excludeDraft != typeof undefined && excludeDraft != null) {
                deleteUrl += '&excludeDraft=' + excludeDraft;
            }
            $.ajax({
                url: deleteUrl,
                method: 'get',
                dataType: 'json'
            })
                .done(function(){
                    $('#product_addtocart_form').trigger('submit');
                });
        }
    };

    return printformerTools;
}));