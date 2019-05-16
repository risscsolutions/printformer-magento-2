/*global FORM_KEY*/
define([
    'jquery',
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, modal, registry, $t, alert) {
    'use strict';

    return modal.extend({
        synchronizeTemplates: function(){
            $.ajax({
                url: this.syncUrl,
                method: 'GET',
                data: {
                    'isAjax': true,
                    'form_key': FORM_KEY
                },
                showLoader: true,
            }).done(function(response){
                if (response && response.message){
                    alert({
                        title: '',
                        content: response.message,
                        actions: {
                            always: function(){}
                        }
                    });
                }
                if (response.success === 'true') {
                    var listing = registry.get("product_form.product_form.pftemplates.printformer_templates.modal.printformer_templates_listing");
                    listing.reload();
                }
            });
        }
    });
});