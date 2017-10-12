define([
    "jquery",
    "jquery/ui",
    "Magento_Catalog/js/product/list/toolbar"
], function($) {
    
    $.widget('mage.productListToolbarForm', $.mage.productListToolbarForm, {
        
        _create: function () {
            this._bind($(this.options.modeControl), this.options.mode, this.options.modeDefault);
            this._bind($(this.options.directionControl), this.options.direction, this.options.directionDefault);
            this._bind($(this.options.orderControl), this.options.order, this.options.orderDefault);
            this._bind($(this.options.limitControl), this.options.limit, this.options.limitDefault);
            
            this._createFilterBinds();
        },
        
        _createFilterBinds: function() {
            var _this = this;
            $(document).on('click', '.block.filter a', function(e) {
                e.preventDefault();
                _this.ajaxLoad.call(_this, $(this).attr('href'));
            });
        },
        
        changeUrl: function (paramName, paramValue, defaultValue) {
            var decode = window.decodeURIComponent;
            var urlPaths = this.options.url.split('?'),
                baseUrl = urlPaths[0],
                urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                paramData = {},
                parameters;
            for (var i = 0; i < urlParams.length; i++) {
                parameters = urlParams[i].split('=');
                paramData[decode(parameters[0])] = parameters[1] !== undefined
                    ? decode(parameters[1].replace(/\+/g, '%20'))
                    : '';
            }
            paramData[paramName] = paramValue;
            if (paramValue == defaultValue) {
                delete paramData[paramName];
            }
            paramData = $.param(paramData);
            
            var ajaxUrl = baseUrl + (paramData.length ? '?' + paramData : '');
            this.ajaxLoad.call(this, ajaxUrl);
        },
        
        ajaxLoad: function(ajaxUrl) {
            if (window.productListAjaxLoading) {
                return;
            }
            window.productListAjaxLoading = true;
            $.get(ajaxUrl).done(function(response) {
                $(document).find('#maincontent > .columns').replaceWith($(response).find('#maincontent > .columns'));
                $(document).find('#maincontent > .columns').trigger('contentUpdated');
                window.productListAjaxLoading = false;
            });
        }
    });

    return $.mage.productListToolbarForm;
});
