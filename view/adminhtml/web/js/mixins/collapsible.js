define(['jquery'], function($) {
    'use strict';
    var loaded = false;
    return function (Form) {
        return Form.extend({
            toggleOpened: function () {
                if(this.index === 'printformer' && !loaded) {
                    //encode the json string with the intents
                    var intentsArray = JSON.parse(intentsArrayString);

                    //encode the json string with the intent values
                    var intentValueArray = JSON.parse(intentsValueString);

                    //find button "Printformer" to listen on the on click event
                    var printformerButton = null;

                    $('.admin__collapsible-block-wrapper').each(function (i, element) {
                        if ($(element).data('index') === 'printformer') {
                            printformerButton = element;
                        }
                    });

                    //listen to the on click event on the printformer button
                    var selectProductFieldId = null;
                    var selectCapabilitiesFieldId = null;
                    if (printformerButton !== null) {
                        $(printformerButton).click(function () {
                            //search in all elements with the class'admin__field' after the data-index 'printformer_product'
                            $.each($('.admin__field'), function (i, element) {
                                //get elements of type label
                                var children = $(element).children('label');
                                //check if the data-index is 'printformer_product'
                                if ($(children).length > 0 && $(element).data('index') == 'printformer_product') {
                                    //get the select id from the labels for attribute
                                    selectProductFieldId = $(children).attr('for');
                                    //listen to change events of the product select field
                                    $("#" + selectProductFieldId).change(function () {
                                        //this.value is the master id of the printformer product
                                        if (selectCapabilitiesFieldId !== null) {
                                            //empty the select fiel
                                            $(selectCapabilitiesFieldId).empty()
                                            intentsArray[this.value].forEach(function (item, index) {
                                                //create new option tag
                                                var option = $('<option></option>').attr("value", intentValueArray[item]).attr("data-titel", item).text(item);
                                                //append the new option tag
                                                $(selectCapabilitiesFieldId).append(option);
                                            });
                                        }
                                    });
                                    //get the id of the capabilities select field
                                } else if ($(children).length > 0 && $(element).data('index') == 'printformer_capabilities') {
                                    selectCapabilitiesFieldId = "#" + $(children).attr('for');
                                }
                            });
                        });
                    }
                    loaded = true
                }
                this.opened() ?
                    this.close() :
                    this.open();
                return this;
            }
        });
    }
});