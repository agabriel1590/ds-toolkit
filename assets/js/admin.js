/* global dstAdmin, wp */
(function ($) {
    'use strict';

    var frame;

    // Show/hide logo row when branding toggle changes
    $('#enable_login_branding').on('change', function () {
        if ($(this).is(':checked')) {
            $('#dst-logo-row').slideDown(200);
        } else {
            $('#dst-logo-row').slideUp(200);
        }
    });

    // Open WP media picker
    $('#dst-logo-select').on('click', function (e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select Login Logo',
            button: { text: 'Use this logo' },
            multiple: false,
            library: { type: 'image' }
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#dst-logo-id').val(attachment.id);
            $('#dst-logo-img').attr('src', attachment.url);
            $('#dst-logo-remove').show();
        });
        frame.open();
    });

    // Reset to default logo
    $('#dst-logo-remove').on('click', function (e) {
        e.preventDefault();
        $('#dst-logo-id').val('');
        $('#dst-logo-img').attr('src', dstAdmin.defaultLogoUrl);
        $(this).hide();
    });

}(jQuery));
