define([
    'jquery',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'printformerTools'
], function ($, $ui, $pq, $modal, $t, pfTools) {
    'use strict';

    $.widget('mage.printformer', {
        options: {
            qtySelector: '#qty',
            addBtnSelector: '#product-addtocart-button, #product-updatecart-button',
            editBtnSelector: '#printformer-edit-button',
            uploadBtnSelector: '#printformer-upload-button',
            editorMainSelector: '#printformer-editor-main',
            editorCloseSelector: '#printformer-editor-close',
            editorNoticeSelector: '#printformer-editor-notice',
            variationsFront: [],
            variations: []
        },
        formatChange: false,

        getUploadEditorUrl: function() {
            return this.options.UploadEditorUrl;
        },

        getEditorUrl: function() {
            // prepare URL
            var options = this.options,
                urlParts = options.urlTemplate.split('?'),
                url = urlParts[0],
                inputQty = $(options.qtySelector),
                params = $('[data-action="add-to-wishlist"]').data('post'),
                formKey = $('[name="form_key"]'),
                updateWishlistItemOptions = '';
            var action = undefined;
            if(params) {
                action = params.action.split('/');
            }
            /**
             * todo: Check why it's not working?
             * for (var k in this.options.variations) {
                if (!this.options.variations[k].length) {
                    continue;
                }
                url += '/';
                url +=  k;
                url += '/';
                url += this.options.variations[k];
            }*/
            url += '?';
            url += urlParts[1];

            if (inputQty.val()) {
                url += encodeURIComponent('qty/' + inputQty.val());
            }

            if (formKey) {
                url += encodeURIComponent('/form_key/' + formKey.val());
            }

            if(action != undefined) {
                $.each(action, function (index, val) {
                    if (val == 'wishlist' || val == 'index') {
                        updateWishlistItemOptions += val + '/';
                    } else if (val == 'updateItemOptions') {
                        updateWishlistItemOptions += val;
                    }
                });
            }

            url += encodeURIComponent('?');
            if(params) {
                $.each(params.data, function (index, val) {
                    if (index !== 'product' && index !== 'qty' && index !== 'uenc') {
                        url += encodeURIComponent('&' + index + '=' + val);
                    }
                });
            }

            if (updateWishlistItemOptions.length) {
                url += encodeURIComponent('&updateWishlistItemOptions=' + updateWishlistItemOptions);
            }

            return url;
        },

        initPersonalisationQty: function() {
            if(this.options.personalizations > 1) {
                var oldQtyTrans = $(this.options.qtySelector);
                $(oldQtyTrans).val(this.options.personalizations);

                if (!$('#personalisation_qty').length) {
                    var newQtyTrans = $('<input/>')
                        .attr('type', 'text')
                        .attr('class', $(oldQtyTrans).attr('class'))
                        .attr('id', 'personalisation_qty')
                        .val(this.options.personalizations)
                        .prop('disabled', true);
                    $(newQtyTrans).insertAfter($(oldQtyTrans));
                }
                $(oldQtyTrans).trigger('change').hide();

                if (!$('#printformer_personalisations').length) {
                    var personalisationsInput = $('<input value="' + this.options.personalizations + '" type="hidden" id="printformer_personalisations" name="printformer_personalisations"/>');
                    $(this.options.qtySelector).after(personalisationsInput);
                }
            }
        },

        _create: function () {
            this._initEditorMain();
            this._initAddBtn();
            this._initEditBtn();
            this._initUploadBtn();
            this._initVariations();
            if(this.options.isConfigure) {
                this.hideSecondButton();
                this.setPrimaryButton($t('Edit draft'));
            }

            var that = this;
            if(this.options.personalizations > 0) {
                this.initPersonalisationQty();
                $(document).on('printformer_preselect_options_after', function() {
                    that.initPersonalisationQty();
                });
            }

            $(document).trigger('printformer:loaded');
        },

        hideSecondButton: function(){
            if($(this.uploaBtn).length) {
                $(this.uploaBtn).hide();
            }
        },

        setPrimaryButton: function(text) {
            var editDraftMasterId = this.options.draftMasterId;
            var UploadMasterId = this.options.UploadMasterId;
            var draftType = 'editor';
            if(editDraftMasterId == UploadMasterId) {
                draftType = 'upload';
            }

            $(this.editBtn).attr('data-pf-masterid', editDraftMasterId);
            $(this.editBtn).attr('data-pf-type', draftType);

            if($(this.editBtn).length) {
                var span = $(this.editBtn).children('span');
                $(span).text(text);
            }
        },

        _initEditorMain: function () {
            var options = this.options;
            this.editorMain = $(options.editorMainSelector);
            this.editorMain.modal({
                modalClass: 'printformer-editor-main-modal',
                title: options.productTitle,
                buttons: [],
                modalCloseBtnHandler: this.editorCloseOpen.bind(this)
            });
            this._initEditorClose();
            this._initEditorNotice();
        },

        editorMainOpen: function(editorUrl) {
            $('html, body').css({
                'overflow': 'hidden',
                'height': '100%',
                'width': '100%'
            });
            this.editorMain.modal('openModal');
            this.editorMain
                .html($('<iframe width="100%" height="100%"/>'))
                .find('iframe').first()
                .attr('src', editorUrl)
            ;
        },

        _initEditorClose: function () {
            var options = this.options;
            this.editorClose = $(options.editorCloseSelector);
            this.editorClose.modal({
                modalClass: "printformer-editor-close-modal",
                modalCloseBtnHandler: this.editorCloseCancel.bind(this),
                buttons: [{
                    'text': $t('Yes'),
                    'class': 'ok-btn',
                    'click': this.editorCloseOk.bind(this)
                },
                    {
                        'text': $t('No'),
                        'class': 'cancel-btn',
                        'click': this.editorCloseCancel.bind(this)
                    }]
            });
        },

        editorCloseOpen: function() {
            this.editorClose.modal('openModal');
        },

        editorCloseOk: function() {
            this.editorMain.modal('closeModal');
            this.editorClose.modal('closeModal');
            $('html, body').css({
                'overflow': 'auto',
                'height': 'auto',
                'width': 'auto'
            });
            this.editorMain.html('');
        },

        editorCloseCancel: function () {
            this.editorClose.modal('closeModal');
        },

        _initEditorNotice: function () {
            var options = this.options;
            this.editorNotice = $(options.editorNoticeSelector);
            if (!this.editorNotice) {
                return;
            }
            this.editorNotice.modal({
                modalClass: "printformer-editor-notice-modal",
                modalCloseBtnHandler: this.editorNoticeCancel.bind(this),
                buttons: [{
                    'text': 'OK',
                    'class': 'ok-btn',
                    'click': this.editorNoticeOk.bind(this)
                },
                    {
                        'text': 'Cancel',
                        'class': 'cancel-btn',
                        'click': this.editorNoticeCancel.bind(this)
                    }]
            });
        },

        editorNoticeOpen: function () {
            this.editorNotice.modal('openModal');
        },

        editorNoticeOk: function () {
            this.formatChange = true;
            this.editorNotice.modal('closeModal');
        },

        editorNoticeCancel: function () {
            this.formatChange = false;
            this.editorNotice.modal('closeModal');
            if(!this.formatChange) {
                for (var id in this.options.variationsFront) {
                    $("#" + id).val(this.options.variationsFront[id]).trigger('change', {skip: true});
                }
            }
        },

        setButtonText: function(button, text) {
            var span = $(button).children('span');
            var icon = $(span).children('i');
            $(span).text(text);
            if($(icon).length) {
                $(span).text(' ' + $(span).text());
                $(span).prepend($(icon))
            }
        },

        isUploadProduct: function() {
            return this.options.draftMasterId == this.options.UploadMasterId;
        },

        _initAddBtn: function () {
            var options = this.options;

            this.addBtn = $(options.addBtnSelector);
            var draftIdInput = null;
            if (options.draftId) {
                draftIdInput = $('<input value="' + options.draftId + '" type="hidden" id="printformer_draftid" name="printformer_draftid"/>');
                $(options.qtySelector).after(draftIdInput);
            }
            var draftMasterIdInput = null;
            if (options.draftMasterId) {
                var afterElem = options.qtySelector;
                if(draftIdInput !== null) {
                    afterElem = draftIdInput;
                }
                var draftMasterIdInput = $('<input value="' + options.draftMasterId + '" type="hidden" id="printformer_masterid" name="printformer_masterid"/>');
                $(afterElem).after(draftMasterIdInput);
            }
            if(draftIdInput !== null && draftMasterIdInput !== null && options.draftId) {
                var elementToSave = null;
                var preselectButtons = $('.printformer-preselect');
                $.each(preselectButtons, function (i, elem) {
                    if($(elem).data('pf-masterid') == $(draftMasterIdInput).val()) {
                        elementToSave = elem;
                    }
                });
                var sessionTools = new pfTools($(draftIdInput), 'session', {saveUrl: options.DraftsSaveUrl, currentProduct: options.ProductId});
                sessionTools.addDraftIdToSession($(elementToSave), options.draftId);
            }

            this.addBtnDisable();
        },

        addBtnEnable: function () {
            this.addBtn.prop('disabled', false);
        },

        addBtnDisable: function () {
            var options = this.options;
            if (!options.allowAddCart) {
                this.addBtn.prop('disabled', true);
            }
        },

        _initEditBtn: function () {
            var that = this;
            var options = this.options;
            this.editBtn = $(this.options.editBtnSelector);
            this.editBtn.click({printformer: this}, function(event) {
                event.data.printformer.editorMainOpen(that.getEditorUrl());
            })
            /**
             * Removed moving button because we need it in another Container.
             * .insertBefore(this.addBtn)
             */
                .show();

            if (options.draftId && !this.isUploadProduct()) {
                this.setButtonText($(this.editBtn), $t('View draft'));
            }
        },

        editBtnEnable: function () {
            this.editBtn.prop('disabled', false);
        },

        editBtnDisable: function () {
            this.editBtn.prop('disabled', true);
        },

        _initUploadBtn: function () {
            var that = this;
            var options = this.options;
            this.uploaBtn = $(this.options.uploadBtnSelector);
            this.uploaBtn.click({printformer: this}, function(event) {
                event.data.printformer.editorMainOpen(that.getUploadEditorUrl());
            })
            /**
             * Removed moving button because we need it in another Container.
             * .insertBefore(this.addBtn)
             */
                .show();

            if(options.draftId && this.isUploadProduct()) {
                this.setButtonText($(this.uploaBtn), $t('View upload'));
            }
        },

        uploadBtnEnable: function () {
            this.uploadBtn.prop('disabled', false);
        },

        uploadBtnDisable: function () {
            $(this.uploadBtn).prop('disabled', true);
        },

        _initVariations: function () {
            var varConf = this.options.variationsConfig;
            if (typeof varConf !== 'object') {
                return;
            }
            for (var id in varConf) {
                var input = $('#' + id);
                if (!input.length) {
                    continue;
                }
                this.editBtnDisable();
                this.uploadBtnDisable();
                input.change({printformer: this}, function(event, skip){
                    if (skip) {
                        return;
                    }
                    event.data.printformer.setVariation($(this).attr('id'), $(this).val());
                });
                for (var k in this.options.variations) {
                    if (varConf[id]['param'] == k) {
                        for (var mk in varConf[id]['map']) {
                            if (varConf[id]['map'][mk] == this.options.variations[k]) {
                                input.val(mk);
                            }
                        }
                    }
                }
                input.change();
            }

            if (
                this.options.qty &&
                $(this.options.qtySelector).prop('tagName') != 'SELECT'
            ) {
                $(this.options.qtySelector).val(this.options.qty);
            }
        },

        setVariation: function (id, value) {
            var varConf = this.options.variationsConfig;
            if (!id
                || !varConf[id]
                || !varConf[id]['param'].length) {
                return;
            }
            if (!this.formatChange
                && varConf[id]['notice']
                && this.options.variationsFront[id] !== undefined
                && this.options.variationsFront[id].length) {
                this.editorNotice.modal('openModal');
                return;
            }
            this.editBtnEnable();
            this.uploadBtnEnable();
            this.options.variationsFront[id] = value;
            this.options.variations[varConf[id]['param']] = varConf[id]['map'][value];
            for (var k in this.options.variations) {
                if (this.options.variations[k] === undefined || !this.options.variations[k].length) {
                    this.editBtnDisable();
                    this.uploadBtnDisable();
                }
            }
        }
    });

    return $.mage.printformer;
});
