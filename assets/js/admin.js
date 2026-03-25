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

    // Show/hide ACF mapping table when toggle changes
    $('#acf_css_vars_enabled').on('change', function () {
        if ($(this).is(':checked')) {
            $('#dst-acf-mappings-row').slideDown(200);
        } else {
            $('#dst-acf-mappings-row').slideUp(200);
        }
    });

    // Add new mapping row
    $('#dst-add-mapping').on('click', function () {
        var index = parseInt($(this).data('count'), 10);
        var row = '<tr class="dst-mapping-row">' +
            '<td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][' + index + '][acf_field]" placeholder="acf_field_name"></td>' +
            '<td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][' + index + '][css_var]" placeholder="--css-variable-name"></td>' +
            '<td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][' + index + '][fallback]" placeholder="optional"></td>' +
            '<td><button type="button" class="button dst-remove-mapping" title="Remove">&#x2715;</button></td>' +
            '</tr>';
        $('#dst-mappings-tbody').append(row);
        $(this).data('count', index + 1);
    });

    // Remove a mapping row
    $('#dst-mappings-tbody').on('click', '.dst-remove-mapping', function () {
        $(this).closest('tr').remove();
    });

    // Show/hide Forminator partner docs row when toggle changes
    $('#forminator_email_partner_enabled').on('change', function () {
        if ($(this).is(':checked')) {
            $('#dst-forminator-partner-row').slideDown(200);
        } else {
            $('#dst-forminator-partner-row').slideUp(200);
        }
    });

}(jQuery));
