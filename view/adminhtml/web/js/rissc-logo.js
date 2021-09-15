require(['jquery',
    'jquery/ui',
    'domReady!'], function ($) {
    'use strict';

    var Controller = Class.create();

    Controller.prototype = {
        defaults: {
            element: $('div.config-nav-block.admin__page-rissc-tab strong'),
            html: '<span class="rissc-logo"></span>'
        },

        addRisscLogo: function () {
            this.defaults.element.before(this.defaults.html);
        },

        initialize: function(options) {
            this.addRisscLogo();
        },
    };

    return new Controller();
});