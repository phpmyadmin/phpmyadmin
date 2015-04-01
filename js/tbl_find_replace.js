/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_find_replace.js', function () {
    $('#find_replace_form').unbind('submit');
    $('#toggle_find').unbind('click');
});

/**
 * Bind events
 */
AJAX.registerOnload('tbl_find_replace.js', function () {

    $('<div id="toggle_find_div"><a id="toggle_find"></a></div>')
        .insertAfter('#find_replace_form')
        .hide();

    $('#toggle_find')
        .html(PMA_messages.strHideFindNReplaceCriteria)
        .click(function () {
            var $link = $(this);
            $('#find_replace_form').slideToggle();
            if ($link.text() == PMA_messages.strHideFindNReplaceCriteria) {
                $link.text(PMA_messages.strShowFindNReplaceCriteria);
            } else {
                $link.text(PMA_messages.strHideFindNReplaceCriteria);
            }
            return false;
        });

    $('#find_replace_form').submit(function (e) {
        e.preventDefault();
        var findReplaceForm = $('#find_replace_form');
        PMA_prepareForAjaxRequest(findReplaceForm);
        var $msgbox = PMA_ajaxShowMessage();
        $.post(findReplaceForm.attr('action'), findReplaceForm.serialize(), function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success === true) {
                $('#toggle_find_div').show();
                $('#toggle_find').click();
                $("#sqlqueryresultsouter").html(data.preview);
            } else {
                $("#sqlqueryresultsouter").html(data.error);
            }
        });
    });
});
