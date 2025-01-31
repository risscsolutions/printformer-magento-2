define([
    'jquery',
    'jquery/jquery.parsequery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Rissc_Printformer/js/printformer'
], function ($, $pq, $modal, $t, printformer) {
    'use strict';

    $.widget('mage.customPrintformer', {
        formatChange: false,
        currentDrafts: null,

        isDefined: function(value) {
            return typeof value !== typeof undefined;
        },

        _create: function () {
            this._initEditorMain();
            this._initEditBtn();
        },

        _initEditorMain: function () {
            var options = this.options;
            this.editorMain = $(options.editorMainSelector);
            this.editorMain.modal({
                modalClass: 'printformer-editor-main-modal',
                title: options.productTitle,
                buttons: []
            });
            this._initEditorClose();
            this._initEditorNotice();
        },

        editorMainOpen: function(editorUrl, beforeHtml, afterHtml) {
            printformer.editorMain.css({
                'width': '100% !important',
                'height': '100% !important'
            });
            printformer.editorMainOpen(editorUrl, beforeHtml, afterHtml);
        },

        _initEditorClose: function () {
            var options = this.options;
            this.editorClose = $(options.editorCloseSelector);
            this.editorClose.modal({
                modalClass: "printformer-editor-close-modal",
                modalCloseBtnHandler: this.editorCloseCancel.bind(this),
                buttons: [
                    {
                        'text': $t('Yes'),
                        'class': 'ok-btn',
                        'click': this.editorCloseOk.bind(this)
                    },
                    {
                        'text': $t('No'),
                        'class': 'cancel-btn'
                    }
                ]
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
                buttons: [
                    {
                        'text': 'OK',
                        'class': 'ok-btn',
                        'click': this.editorNoticeOk.bind(this)
                    },
                    {
                        'text': 'Cancel',
                        'class': 'cancel-btn'
                    }
                ]
            });
        },

        editorNoticeOpen: function () {
            this.editorNotice.modal('openModal');
        },

        editorNoticeOk: function () {
            this.formatChange = true;
            this.editorNotice.modal('closeModal');
        },

        _initEditBtn: function () {
            var that = this;
            this.editBtn = $(this.options.editBtnSelector);

            this.editBtn.click({printformer: this}, function(event) {
                event.data.printformer.editorMainOpen($(that.editBtn).attr('href'));
                return false;
            }).show();
        }
    });

    return $.mage.customPrintformer;
});
