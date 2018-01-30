var config = {
    map: {
        '*': {
            'printformer': "Rissc_Printformer/js/printformer",
            'custom-printformer': "Rissc_Printformer/js/custom-printformer.min"
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Rissc_Printformer/js/mixins/addtocart': true
            }
        }
    }
};