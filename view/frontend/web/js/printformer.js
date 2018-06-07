var printformerInstance = null;

define([
    'jquery',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Rissc_Printformer/js/printformer/preselection'
], function ($, $ui, $pq, $modal, $t, $preselection) {
    'use strict';

    $.widget('mage.printformer', {
        formatChange: false,
        currentDrafts: null,

        _create: function () {
            var that = this;
            this.form = this.element;
            this.callbacks = {};
            this.addToCartFormUrl = null;
            this.persoOptionAdded = false;

            this.printformerOptions = this.options;

            if(this.isDefined(printformerInstance)) {
                printformerInstance = this;
            }
            $(document).trigger('printformer:loaded:before');

            this.runCallbacks('printformer:loaded:before');
            this._initEditorMain();
            $.ajax({
                url: that.printformerOptions.DraftsGetUrl + 'product/' + that.printformerOptions.ProductId + '/',
                method: 'get',
                dataType: 'json'
            }).done(function(data){
                that.currentDrafts = data;
                $.each(that.printformerOptions.printformerProducts, function (index, printformerProduct) {
                    that.initButton(printformerProduct);
                });
                that._initAddBtn();
                that._initVariations();
                if(that.printformerOptions.isConfigure) {
                    that.hideSecondButton();
                    that.setPrimaryButton();
                }

                if (
                    that.isDefined(that.printformerOptions.personalizations_conf) &&
                    that.printformerOptions.personalizations_conf &&
                    that.isDefined(that.printformerOptions.personalizations) &&
                    that.printformerOptions.personalizations > 1
                ) {
                    that.initPersonalisationQty();
                }

                if(that.isDefined(that.printformerOptions.preselection) && that.printformerOptions.preselection !== null) {
                    that.runCallbacks('printformer:preselection:before');
                    if (that.printformerOptions.preselection.product === that.printformerOptions.ProductId) {
                        $preselection.preselectOptions(that.printformerOptions.preselection);
                    }
                    that.runCallbacks('printformer:preselection:after');
                }

                if (that.isDefined(that.printformerOptions.personalizations) && that.printformerOptions.personalizations > 1) {
                    that.initPersonalisationQty();
                }

                $(document).trigger('printformer:loaded');
                that.runCallbacks('printformer:loaded:after');
            });
        },

        isDefined: function(value) {
            return typeof value !== typeof undefined;
        },

        getUploadUrl: function() {
            return this.printformerOptions.urls.upload;
        },

        getUploadAndEditorUrl: function() {
            return this.printformerOptions.urls.uploadAndEditor;
        },

        getPersonalizeUrl: function() {
            return this.printformerOptions.urls.personalize;
        },

        getEditorUrl: function() {
            // prepare URL
            var options = this.printformerOptions,
                urlParts = options.urls.customize.split('?'),
                url = urlParts[0],
                inputQty = $(options.qtySelector),
                params = $('[data-action="add-to-wishlist"]').data('post'),
                formKey = $('[name="form_key"]'),
                updateWishlistItemOptions = '';
            var action = null;
            if(params) {
                action = params.action.split('/');
            }

            url += '?';
            url += urlParts[1];

            if (inputQty.val()) {
                url += encodeURIComponent('qty/' + inputQty.val());
            }

            if (formKey) {
                url += encodeURIComponent('/form_key/' + formKey.val());
            }

            if(action !== null) {
                $.each(action, function (index, val) {
                    if (val === 'wishlist' || val === 'index') {
                        updateWishlistItemOptions += val + '/';
                    } else if (val === 'updateItemOptions') {
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
            var instance = this;
            if(this.isDefined(this.printformerOptions.personalizations) && this.printformerOptions.personalizations > 1) {
                var oldQtyTrans = $(this.printformerOptions.qtySelector);
                if ($(oldQtyTrans).prop('tagName').toLowerCase() === 'select' && !this.persoOptionAdded) {
                    var persoOption = $('<option/>');
                    $(persoOption).val(parseFloat(this.printformerOptions.personalizations));
                    $(persoOption).text(this.printformerOptions.personalizations);
                    var selectChilds = $(oldQtyTrans).children();
                    for(var i = 0; i < selectChilds.length; i++) {
                        var qty = parseInt($(selectChilds[i]).val());
                        var nextQty = parseInt($(selectChilds[i + 1]).val());
                        if(
                            qty < parseInt(instance.printformerOptions.personalizations) &&
                            nextQty > parseInt(instance.printformerOptions.personalizations)
                        ) {
                            $(selectChilds[i + 1]).after($(persoOption));
                            this.persoOptionAdded = true;
                            break;
                        }
                    }
                }
                $(oldQtyTrans).val(this.printformerOptions.personalizations);
                $(oldQtyTrans).data('pf-perso-count', this.printformerOptions.personalizations);
                var newQtyTrans = null;
                if ($('#personalisation_qty').length < 1) {
                    newQtyTrans = $('<input/>')
                        .attr('type', 'text')
                        .attr('class', $(oldQtyTrans).attr('class'))
                        .attr('id', 'personalisation_qty')
                        .val(this.printformerOptions.personalizations)
                        .prop('disabled', true);
                    $(newQtyTrans).insertAfter($(oldQtyTrans));
                }
                $(oldQtyTrans).data('pf-personalized', 'true');
                $(oldQtyTrans).trigger('change').hide();

                if ($('#printformer_personalisations').length < 1) {
                    var personalisationsInput = $('<input value="' + this.printformerOptions.personalizations + '" type="hidden" id="printformer_personalisations" name="printformer_personalisations"/>');
                    $(personalisationsInput).insertAfter($(newQtyTrans));
                }
            }
        },

        hideSecondButton: function(){
            if($(this.uploaBtn).length) {
                $(this.uploaBtn).hide();
            }
        },

        setPrimaryButton: function() {
            var that = this;

            var editDraftIntent = this.printformerOptions.currentSessionIntent;
            var draftType = 'editor';
            var ButtonText = $t('View draft');
            if(editDraftIntent === 'upload' || editDraftIntent === 'upload-and-editor') {
                draftType = 'upload';
                ButtonText = $t('View upload');
            }

            $(this.editBtn).attr('data-pf-masterid', this.printformerOptions.masterId);
            $(this.editBtn).attr('data-pf-type', draftType);
            $(this.editBtn).attr('data-pf-intent', editDraftIntent);

            if(draftType === 'upload') {
                $(this.editBtn).unbind('click');
                $(this.editBtn).click({printformer: this}, function(event) {
                    var url = editDraftIntent === 'upload-and-editor' ? that.getUploadAndEditorUrl() : that.getUploadUrl();
                    event.data.printformer.editorMainOpen(url);
                });
            }

            if($(this.editBtn).length) {
                var span = $(this.editBtn).children('span');
                $(span).text(ButtonText);
            }
        },

        _initEditorMain: function () {
            var options = this.printformerOptions;
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
            this.addToCartFormUrl = $(this.form).attr('action');
            $(this.form).attr('action', editorUrl);
            $(this.form).attr('target', 'printformer-main-frame');

            $('html, body').css({
                'overflow': 'hidden',
                'height': '100%',
                'width': '100%'
            });
            this.editorMain.modal('openModal');
            this.editorMain
                .html($('<iframe width="100%" height="100%" name="printformer-main-frame"/>'));

            $(this.form).off().submit();
        },

        resetForm: function() {
            $(this.form).attr('action', this.addToCartFormUrl);
            $(this.form).removeAttr('target');
        },

        _initEditorClose: function () {
            var options = this.printformerOptions;
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

            this.resetForm();
        },

        editorCloseCancel: function () {
            this.editorClose.modal('closeModal');
            this.resetForm();
        },

        _initEditorNotice: function () {
            var options = this.printformerOptions;
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
            this.resetForm();
        },

        editorNoticeCancel: function () {
            this.formatChange = false;
            this.editorNotice.modal('closeModal');
            if(!this.formatChange) {
                for (var id in this.printformerOptions.variationsFront) {
                    $("#" + id).val(this.printformerOptions.variationsFront[id]).trigger('change', {skip: true});
                }
            }
            this.resetForm();
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

        _initAddBtn: function () {
            var options = this.printformerOptions;

            this.addBtn = $(options.addBtnSelector);
            var draftIdInput = null;
            if (options.draftId) {
                draftIdInput = $('<input value="' + options.draftId + '" type="hidden" id="printformer_draftid" name="printformer_draftid"/>');
                $(options.qtySelector).after(draftIdInput);
            }
            var intentInput = null;
            if (draftIdInput && options.intent) {
                intentInput = $('<input value="' + options.intent + '" type="hidden" id="printformer_intent" name="printformer_intent"/>');
                $(draftIdInput).after(intentInput);
            }
            var uniqueIdInput = null;
            if (draftIdInput && intentInput && options.unique_id) {
                uniqueIdInput = $('<input value="' + options.unique_id + '" type="hidden" id="printformer_unique_session_id" name="printformer_unique_session_id"/>');
                $(intentInput).after(uniqueIdInput);
            }

            this.addBtnDisable();
        },

        addBtnEnable: function () {
            this.addBtn.prop('disabled', false);
        },

        addBtnDisable: function () {
            var options = this.printformerOptions;
            if (!options.allowAddCart) {
                this.addBtn.prop('disabled', true);
            }
        },

        initButton: function(printformerProduct) {
            var button = $(this.printformerOptions.buttonSelector + printformerProduct['id']);

            var url = printformerProduct['url'];

            button.click({printformer: this}, function(event) {
                event.data.printformer.editorMainOpen(url);
            });

            button.prop('disabled', false);

            if (this.currentDrafts) {
                if (this.isDefined(this.currentDrafts['customize']) || this.isDefined(this.currentDrafts['personalize'])) {
                    this.setButtonText($(button), $t('View draft'));
                }

                if (this.isDefined(this.currentDrafts[printformerProduct['intent']])) {
                    this.setButtonText($(button), $t('View upload'));
                }
            }
        },

        _initVariations: function () {
            var varConf = this.printformerOptions.variationsConfig;
            if (typeof varConf !== 'object') {
                return;
            }
            for (var id in varConf) {
                var input = $('#' + id);
                if (!input.length) {
                    continue;
                }
                //@Todo disable button
                input.change({printformer: this}, function(event, skip){
                    if (skip) {
                        return;
                    }
                    event.data.printformer.setVariation($(this).attr('id'), $(this).val());
                });
                for (var k in this.printformerOptions.variations) {
                    if (varConf[id]['param'] === k) {
                        for (var mk in varConf[id]['map']) {
                            if (varConf[id]['map'][mk] === this.printformerOptions.variations[k]) {
                                input.val(mk);
                            }
                        }
                    }
                }
                input.change();
            }

            if(this.isDefined(this.printformerOptions.preselection.qty) && this.printformerOptions.preselection !== null) {
                if(parseInt(this.printformerOptions.preselection.qty.value) !==  parseInt(this.printformerOptions.qty)) {
                    this.printformerOptions.qty = this.printformerOptions.preselection.qty.value
                }
            }

            if (
                this.printformerOptions.qty &&
                $(this.printformerOptions.qtySelector).prop('tagName') !== 'SELECT'
            ) {
                $(this.printformerOptions.qtySelector).val(this.printformerOptions.qty);
            }
        },

        setVariation: function (id, value) {
            var varConf = this.printformerOptions.variationsConfig;
            if (!id
                || !varConf[id]
                || !varConf[id]['param'].length) {
                return;
            }
            if (!this.formatChange
                && varConf[id]['notice']
                && this.printformerOptions.variationsFront[id] !== undefined
                && this.printformerOptions.variationsFront[id].length) {
                this.editorNotice.modal('openModal');
                return;
            }
            this.editBtnEnable();
            this.uploadBtnEnable();
            this.printformerOptions.variationsFront[id] = value;
            this.printformerOptions.variations[varConf[id]['param']] = varConf[id]['map'][value];
            for (var k in this.printformerOptions.variations) {
                if (this.printformerOptions.variations[k] === undefined || !this.printformerOptions.variations[k].length) {
                    //@todo disable button
                }
            }
        },

        registerCallback: function(event, name, method) {
            if(!this.isDefined(this.callbacks[event])) {
                this.callbacks[event] = {};
            }
            this.callbacks[event][name] = method;
            $(document).trigger('printformer_callback_registered', event, name, method);
        },

        runCallbacks: function(event) {
            if(!this.isDefined(event)) {
                $.each(this.callbacks, function(i, eventCallbacks){
                    $.each(eventCallbacks, function(i, methodCallback){
                        methodCallback();
                    });
                });
            } else {
                $.each(this.callbacks[event], function(i, methodCallback){
                    methodCallback();
                });
            }
        }
    });

    return $.mage.printformer;
});