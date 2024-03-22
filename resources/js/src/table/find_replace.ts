import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { Functions } from '../modules/functions.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/find_replace.js', function () {
    $('#find_replace_form').off('submit');
    $('#toggle_find').off('click');
});

/**
 * Bind events
 */
AJAX.registerOnload('table/find_replace.js', function () {
    $('<div id="toggle_find_div"><a id="toggle_find"></a></div>')
        .insertAfter('#find_replace_form')
        .hide();

    $('#toggle_find')
        .html(window.Messages.strHideFindNReplaceCriteria)
        .on('click', function () {
            var $link = $(this);
            $('#find_replace_form').slideToggle();
            if ($link.text() === window.Messages.strHideFindNReplaceCriteria) {
                $link.text(window.Messages.strShowFindNReplaceCriteria);
            } else {
                $link.text(window.Messages.strHideFindNReplaceCriteria);
            }

            return false;
        });

    $('#find_replace_form').on('submit', function (e) {
        e.preventDefault();
        var findReplaceForm = $('#find_replace_form');
        Functions.prepareForAjaxRequest(findReplaceForm);
        var $msgbox = ajaxShowMessage();
        $.post(findReplaceForm.attr('action'), findReplaceForm.serialize(), function (data) {
            ajaxRemoveMessage($msgbox);
            if (data.success === true) {
                $('#toggle_find_div').show();
                $('#toggle_find').trigger('click');
                $('#sqlqueryresultsouter').html(data.preview);
            } else {
                $('#sqlqueryresultsouter').html(data.error);
            }
        });
    });
});
