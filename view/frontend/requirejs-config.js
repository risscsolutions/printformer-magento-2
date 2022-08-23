var config = {
    map: {
        '*': {
            'printformer': "Rissc_Printformer/js/printformer",
            'custom-printformer': "Rissc_Printformer/js/custom-printformer"
        }
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Rissc_Printformer/js/mixins/swatch-renderer.mixin': true
            }
        }
    }
};