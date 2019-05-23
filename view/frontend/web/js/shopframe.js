define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    $.widget('mage.pfshopframe', {
        _mainContentContainer: null,
        _wrapperContainer: null,
        _fullscreenButton: null,
        _frame: 0,

        _create: function (){
            this._mainContentContainer = $('#maincontent');
            this._wrapperContainer = this._mainContentContainer.find('.column.main');
            this._frame = $('#printformer_shopframe_container');
            var fullscreenToggleSelector = '.printformer-fullscreen-toggle';
            if ($(fullscreenToggleSelector).length) {
                this._fullscreenButton = $(fullscreenToggleSelector);
            }

            this.initEditorIframe();
            this.initFullscreenButton();
        },

        initEditorIframe: function(){
            this.handleResize();
            this.initResizeEvent();
        },

        initResizeEvent: function(){
            var instance = this;

            $(window).resize(_.debounce(function(){
                instance.handleResize();
            },100));
        },

        handleResize: function(){
            this._wrapperContainer.css({
                'height': this._mainContentContainer.outerHeight() + 'px'
            });
        },

        initFullscreenButton: function(){
            if (!this._fullscreenButton) {
                return;
            }

            var instance = this;
            this._fullscreenButton.click(function(e){
                e.preventDefault();

                instance.toggleFullScreen();

                return false;
            });
        },

        toggleFullScreen: function(){
            if (this._frame.hasClass('frame-fullscreen')) {
                this._fullscreenButton.removeClass('shrink');
                this._frame.removeClass('frame-fullscreen');
                this._frame.css({
                    'height': this._wrapperContainer.outerHeight() + 'px'
                });
            } else {
                this._fullscreenButton.addClass('shrink');
                this._frame.addClass('frame-fullscreen');
                this._frame.css({
                    'height': $('body').outerHeight() + 'px'
                });
            }
        }
    });

    return $.mage.pfshopframe;
});