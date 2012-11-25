/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_variables.js', function() {
    $('#serverVariables .var-row').unbind('hover');
    $('#filterText').unbind('keyup');
    $('a.editLink').die('click');
    $('#serverVariables').find('.var-name').find('a img').remove();
});

AJAX.registerOnload('server_variables.js', function() {
    var $editLink = $('a.editLink');
    var $saveLink = $('a.saveLink');
    var $cancelLink = $('a.cancelLink');
    var $filterField = $('#filterText');

    /* Show edit link on hover */
    $('#serverVariables').delegate('.var-row', 'hover', function(event) {
        if (event.type === 'mouseenter') {
            var $elm = $(this).find('.var-value');
            // Only add edit element if the element is not being edited
            if ($elm.hasClass('editable') && ! $elm.hasClass('edit')) {
                $elm.prepend($editLink.clone().show());
            }
        } else {
            $(this).find('a.editLink').remove();
        }
    }).find('.var-name').find('a').append(
        $('#docImage').clone().show()
    );

    /* Launches the variable editor */
    $editLink.live('click', function (event) {
        event.preventDefault();
        editVariable(this);
    });

    /* Event handler for variables filter */
    $filterField.keyup(function() {
        var textFilter = null, val = $(this).val();
        if (val.length !== 0) {
            textFilter = new RegExp("(^| )"+val.replace(/_/g,' '),'i');
        }
        filterVariables(textFilter);
    });

    /* Trigger filtering of the list based on incoming variable name */
    if ($filterField.val()) {
        $filterField.trigger('keyup').select();
    }

    /* Filters the rows by the user given regexp */
    function filterVariables(textFilter) {
        var mark_next = false, $row, odd_row = false;
        $('#serverVariables .var-row').not('.var-header').each(function() {
            $row = $(this);
            if (   mark_next
                || textFilter === null
                || textFilter.exec($row.find('.var-name').text())
            ) {
                // If current global value is different from session value
                // (has class diffSession), then display that one too
                mark_next = $row.hasClass('diffSession') && ! mark_next;

                odd_row = ! odd_row;
                $row.css('display', '');
                if (odd_row) {
                    $row.addClass('odd').removeClass('even');
                } else {
                    $row.addClass('even').removeClass('odd');
                }
            } else {
                $row.css('display', 'none');
            }
        });
    }

    /* Allows the user to edit a server variable */
    function editVariable(link) {
        var $cell = $(link).parent();
        var varName = $cell.parent().find('.var-name').text().replace(/ /g,'_');
        var $mySaveLink = $saveLink.clone().show();
        var $myCancelLink = $cancelLink.clone().show();
        var $msgbox = PMA_ajaxShowMessage();

        $cell
            .addClass('edit') // variable is being edited
            .find('a.editLink')
            .remove(); // remove edit link

        $mySaveLink.click(function() {
            var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.get($(this).attr('href'), {
                    ajax_request: true,
                    type: 'setval',
                    varName: varName,
                    varValue: $cell.find('input').val()
                }, function(data) {
                    if (data.success) {
                        $cell
                            .html(data.variable)
                            .data('content', data.variable);
                        PMA_ajaxRemoveMessage($msgbox);
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                        $cell.html($cell.data('content'));
                    }
                    $cell.removeClass('edit');
                });
            return false;
        });

        $myCancelLink.click(function() {
            $cell
                .html($cell.data('content'))
                .removeClass('edit');
            return false;
        });

        $.get($mySaveLink.attr('href'), {
                ajax_request: true,
                type: 'getval',
                varName: varName
            }, function(data) {
                if (data.success === true) {
                    var $editor = $('<div />', {'class':'serverVariableEditor'})
                        .append($myCancelLink)
                        .append(' ')
                        .append($mySaveLink)
                        .append(' ')
                        .append(
                            $('<div/>').append(
                                $('<input />', {type: 'text'}).val(data.message)
                            )
                        );
                    // Save and replace content
                    $cell
                    .data('content', $cell.html())
                    .html($editor)
                    .find('input')
                    .focus()
                    .keydown(function(event) { // Keyboard shortcuts
                        if (event.keyCode === 13) { // Enter key
                            $mySaveLink.trigger('click');
                        } else if (event.keyCode === 27) { // Escape key
                            $myCancelLink.trigger('click');
                        }
                    });
                    PMA_ajaxRemoveMessage($msgbox);
                } else {
                    $cell.removeClass('edit');
                    PMA_ajaxShowMessage(data.error);
                }
            });
    }
});
