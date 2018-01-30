define(['jquery'], function($){
    return function(originalWidget){
        $.widget('mage.catalogAddToCart', $.mage.catalogAddToCart, {
            /**
             * Handler for the form 'submit' event
             *
             * @param {Object} form
             */
            submitForm: function (form) {
                form.submit();
            }
        });

        return $.mage.catalogAddToCart;
    };
});