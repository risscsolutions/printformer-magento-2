define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (Element) {
    'use strict';

    return Element.extend({
        defaults: {
            listens: {
                disabled: 'changeVisibility'
            },
            modules: {
                samplesFieldset: '${ $.samplesFieldset }',
                linksFieldset: '${ $.linksFieldset}',
                printformerFieldset: '${ $.printformerFieldset}'
            }
        },

        /**
         * Change visibility for samplesFieldset, linksFieldset and printformerFieldset based on current statuses of checkbox.
         */
        changeVisibility: function () {
            if (this.samplesFieldset() && this.linksFieldset() && this.printformerFieldset()) {
                if (this.checked() && !this.disabled()) {
                    this.samplesFieldset().visible(true);
                    this.linksFieldset().visible(true);
                    this.printformerFieldset().visible(true);
                } else {
                    this.samplesFieldset().visible(false);
                    this.linksFieldset().visible(false);
                    this.printformerFieldset().visible(false);
                }
            }
        },

        /**
         * Handle checked state changes for checkbox / radio button.
         *
         * @param {Boolean} newChecked
         */
        onCheckedChanged: function (newChecked) {
            this.changeVisibility();
            this._super(newChecked);
        }
    });
});
