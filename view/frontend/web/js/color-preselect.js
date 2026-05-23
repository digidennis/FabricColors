define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var productId = config.productId;
        var paramName = config.paramName || 'color';

        $(function () {
            var params = new URLSearchParams(window.location.search);
            var colorSlug = params.get(paramName);
            if (!colorSlug) {
                return;
            }

            var colors = window.digidennisFabricColors && window.digidennisFabricColors[productId];
            if (!colors) {
                return;
            }

            var match = colors.find(function (c) {
                return c.url_key === colorSlug;
            });

            if (!match) {
                return;
            }

            // Her: find det relevante custom option‑felt og preselect
            // samt skifte billede via din egen logik.
        });
    };
});
