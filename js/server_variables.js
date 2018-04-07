/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_variables.js', function () {
    $(document).off('click', 'a.editLink');
    $('#serverVariables').find('.var-name').find('a img').remove();
});

AJAX.registerOnload('server_variables.js', function () {
    var $editLink = $('a.editLink');
    var $saveLink = $('a.saveLink');
    var $cancelLink = $('a.cancelLink');

    $('#serverVariables').find('.var-name').find('a').append(
        $('#docImage').clone().show()
    );

    /* Launches the variable editor */
    $(document).on('click', 'a.editLink', function (event) {
        event.preventDefault();
        editVariable(this);
    });

    /* Allows the user to edit a server variable */
    function editVariable (link) {
        var $link = $(link);
        var $cell = $link.parent();
        var $valueCell = $link.parents('.var-row').find('.var-value');
        var varName = $link.data('variable');
        var $mySaveLink = $saveLink.clone().show();
        var $myCancelLink = $cancelLink.clone().show();
        var $msgbox = PMA_ajaxShowMessage();
        var $myEditLink = $cell.find('a.editLink');

        $cell.addClass('edit'); // variable is being edited
        $myEditLink.remove(); // remove edit link

        $mySaveLink.click(function () {
            var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.post($(this).attr('href'), {
                ajax_request: true,
                type: 'setval',
                varName: varName,
                varValue: $valueCell.find('input').val()
            }, function (data) {
                if (data.success) {
                    $valueCell
                        .html(data.variable)
                        .data('content', data.variable);
                    PMA_ajaxRemoveMessage($msgbox);
                } else {
                    if (data.error === '') {
                        PMA_ajaxShowMessage(PMA_messages.strRequestFailed, false);
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                    }
                    $valueCell.html($valueCell.data('content'));
                }
                $cell.removeClass('edit').html($myEditLink);
            });
            return false;
        });

        $myCancelLink.click(function () {
            $valueCell.html($valueCell.data('content'));
            $cell.removeClass('edit').html($myEditLink);
            return false;
        });

        $.get($mySaveLink.attr('href'), {
            ajax_request: true,
            type: 'getval',
            varName: varName
        }, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $links = $('<div />')
                    .append($myCancelLink)
                    .append('&nbsp;&nbsp;&nbsp;')
                    .append($mySaveLink);
                var $editor = $('<div />', { 'class': 'serverVariableEditor' })
                    .append(
                        $('<div/>').append(
                            $('<input />', { type: 'text' }).val(data.message)
                        )
                    );
                    // Save and replace content
                $cell
                    .html($links);
                $valueCell
                    .data('content', $valueCell.html())
                    .html($editor)
                    .find('input')
                    .focus()
                    .keydown(function (event) { // Keyboard shortcuts
                        if (event.keyCode === 13) { // Enter key
                            $mySaveLink.trigger('click');
                        } else if (event.keyCode === 27) { // Escape key
                            $myCancelLink.trigger('click');
                        }
                    });
                PMA_ajaxRemoveMessage($msgbox);
            } else {
                $cell.removeClass('edit').html($myEditLink);
                PMA_ajaxShowMessage(data.error);
            }
        });
    }
});
