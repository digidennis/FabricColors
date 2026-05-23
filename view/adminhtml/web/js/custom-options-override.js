define([
    'jquery',
    'mage/url',
    'uiRegistry',
    'Magento_Ui/js/modal/alert'
], function ($, urlBuilder, registry, alert) {
    'use strict';

    return function (Component) {
        return Component.extend({

            initialize: function () {
                this._super();

                this.on('afterRender', this.addImageUI.bind(this));

                return this;
            },

            addImageUI: function () {
                var self = this;

                $('.admin__field-option .admin__field-option-value').each(function () {

                    var row = $(this);

                    if (row.find('.digidennis-color-images').length) {
                        return;
                    }

                    var optionTypeId = row.find('input[name*="[option_type_id]"]').val();

                    row.append(
                        '<div class="digidennis-color-images" data-option-type-id="' + optionTypeId + '">' +
                        '   <label>Farvebilleder</label>' +
                        '   <div class="digidennis-images-list"></div>' +
                        '   <input type="file" class="digidennis-image-upload" multiple />' +
                        '   <input type="hidden" name="digidennis_color_images[' + optionTypeId + ']" class="digidennis-images-hidden" />' +
                        '</div>'
                    );

                    self.loadExistingImages(optionTypeId, row);
                    self.bindUploadHandler(row, optionTypeId);
                });
            },

            loadExistingImages: function (optionTypeId, row) {
                $.ajax({
                    url: urlBuilder.build('fabriccolors/index/load'),
                    type: 'GET',
                    data: { option_type_id: optionTypeId },
                    success: function (response) {
                        if (response.images) {
                            var list = row.find('.digidennis-images-list');
                            response.images.forEach(function (img) {
                                list.append('<img src="' + img.url + '" class="digidennis-thumb" />');
                            });
                            row.find('.digidennis-images-hidden').val(JSON.stringify(response.images));
                        }
                    }
                });
            },

            bindUploadHandler: function (row, optionTypeId) {
                row.find('.digidennis-image-upload').on('change', function () {
                    var files = this.files;
                    var list = row.find('.digidennis-images-list');
                    var hidden = row.find('.digidennis-images-hidden');

                    Array.from(files).forEach(function (file) {
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('option_type_id', optionTypeId);

                        $.ajax({
                            url: urlBuilder.build('fabriccolors/index/upload'),
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.url) {
                                    list.append('<img src="' + response.url + '" class="digidennis-thumb" />');

                                    var current = hidden.val() ? JSON.parse(hidden.val()) : [];
                                    current.push(response);
                                    hidden.val(JSON.stringify(current));
                                }
                            }
                        });
                    });
                });
            }
        });
    };
});
