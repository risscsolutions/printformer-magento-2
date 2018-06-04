define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/components/insert-listing'
], function ($, _, registry, insertListing) {
    'use strict';

    return insertListing.extend({
        defaults: {
            printformerProductsGrid: null,
            printformerProducts: null,
            currentIndex: 0
        },

        initialize: function() {
            this._super();
            this.printformerProductsGrid = registry.get("product_form.product_form.printformer_products.printformer_products");
            return this;
        },

        getPrintformerProductData: function(id) {
            var data = null;

            _.each(this.printformerProducts.items, function (row) {
                if (row['id'] == id) {
                    data = row;
                    return;
                }
            });

            return data;
        },

        isPrintformerProductInGrid: function(id) {
            var isInGrid = false;
            _.each(this.printformerProductsGrid.recordData(), function (record) {
                if (record['id'] == id) {
                    isInGrid = true;
                    return;
                }
            });

            return isInGrid;
        },

        insertPrintformerProductGridRow: function(id) {
            // Check if printformer product is already in grid
            if (!this.isPrintformerProductInGrid(id)) {
                // Get printformer product data
                var printformerProductData = this.getPrintformerProductData(id);

                if (printformerProductData != null) {
                    // Create empty child
                    this.printformerProductsGrid.addChild(false, this.currentIndex, false);

                    // Get object of currently added row
                    var recordScope = this.printformerProductsGrid._elems.last();

                    // When it's created, fill the values
                    $.when(registry.promise(recordScope)).then(function (record) {
                        record.getChild('id').value(printformerProductData['id']);
                        record.getChild('name').value(printformerProductData['name']);
                        record.getChild('master_id').value(printformerProductData['master_id']);
                        record.getChild('intent').value(printformerProductData['intent']);
                    });

                    // Increase index
                    this.currentIndex++;
                }
            }
        },

        save: function () {
            var object = this;
            _.each(this.selections().selected(), function (selection) {
                object.insertPrintformerProductGridRow(selection);
            });
        }
    });
});