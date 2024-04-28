import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import { Functions } from './modules/functions.ts';

AJAX.registerOnload('export_output.js', function () {
    $(document).on('keydown', function (e) {
        if ((e.which || e.keyCode) === 116) {
            e.preventDefault();
            $('#export_refresh_form').trigger('submit');
        }
    });

    $('.export_refresh_btn').on('click', function (e) {
        e.preventDefault();
        $('#export_refresh_form').trigger('submit');
    });

    $('.export_copy_to_clipboard_btn').on('click', function (e) {
        e.preventDefault();
        var copyStatus = Functions.copyToClipboard($('textarea#textSQLDUMP').val(), '<textarea>');
        Functions.displayCopyStatus(this, copyStatus);
    });
});
