define([
    'jquery',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, $ui, $pq, $modal, $t) {
    'use strict';

    $.widget('mage.customPrintformer', {
        formatChange: false,
        currentDrafts: null,

        isDefined: function(value) {
            return typeof value !== typeof undefined;
        },

        _create: function () {
            this.initEditorMain();
            this.initEditBtn();

            $('.modal-popup.printformer-editor-main-modal .action-close').click(function(){
                $('html, body').css({
                    'overflow': 'auto',
                    'height': 'auto',
                    'width': 'auto'
                });
            });
        },

        initEditorMain: function () {
            var options = this.options;
            this.editorMain = $(options.editorMainSelector);
            this.editorMain.modal({
                modalClass: 'printformer-editor-main-modal',
                title: options.productTitle,
                buttons: [],
                close: function()  {
                    alert('close');
                }
            });
            this.initEditorClose();
            this.initEditorNotice();
        },

        editorMainOpen: function(editorUrl) {
            $('html, body').css({
                'overflow': 'hidden',
                'height': '100%',
                'width': '100%'
            });
            this.editorMain.modal('openModal');
            this.editorMain.css({
                'width': '100% !important',
                'height': '100% !important'
            }).html($('<iframe width="100%" height="100%" src="' + editorUrl + '" name="printformer-main-frame" id="printformer-main-frame"/>'));
        },

        initEditorClose: function () {
            var options = this.options;
            this.editorClose = $(options.editorCloseSelector);
            this.editorClose.modal({
                modalClass: "printformer-editor-close-modal",
                modalCloseBtnHandler: this.editorCloseCancel.bind(this),
                buttons: [
                    {
                        'text': $t('Yes'),
                        'class': 'ok-btn',
                        'click': function(){
                            this.editorCloseOk.bind(this);
                            $('html, body').css({
                                'overflow': 'auto',
                                'height': 'auto',
                                'width': 'auto'
                            });
                        }
                    },
                    {
                        'text': $t('No'),
                        'class': 'cancel-btn'
                    }
                ],
                close: function() {
                    alert('closed');
                }
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

        initEditorNotice: function () {
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

        initEditBtn: function () {
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
