import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import {ajaxShowMessage} from "./modules/ajax-message.ts";
import {Functions} from "./modules/functions.ts";

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
        var res = Functions.copyToClipboard($('textarea#textSQLDUMP').val(), '<textarea>');
        if (res) {
            ajaxShowMessage(window.Messages.strCopyQueryButtonSuccess, false, 'success');
        } else {
            ajaxShowMessage(window.Messages.strCopyQueryButtonFailure, false, 'error');
        }
    });
});
