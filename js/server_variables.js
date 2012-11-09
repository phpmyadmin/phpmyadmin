/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_variables.js', function() {
    $('#serverVariables .var-row').unbind('hover');
    $('#filterText').unbind('keyup');
    $('a.editLink').die('click');
});

AJAX.registerOnload('server_variables.js', function() {
    var textFilter = null;
    var odd_row = false;
    var $editLink = $('a.editLink');
    var $saveLink = $('a.saveLink');
    var $cancelLink = $('a.cancelLink');

    /* Variable editing */
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
    });

    $('#filterText').keyup(function(e) {
        if ($(this).val().length == 0) {
            textFilter=null;
        } else {
            textFilter = new RegExp("(^| )"+$(this).val().replace(/_/g,' '),'i');
        }
        filterVariables();
    });

    $('a.editLink').live('click', function (event) {
        event.preventDefault();
        editVariable.call(this);
    });

    /* FIXME: this seems broken as we now use the hash for the microhistory
    if (location.hash.substr(1).split('=')[0] == 'filter') {
        var name = location.hash.substr(1).split('=')[1];
        // Only allow variable names
        if (! name.match(/[^0-9a-zA-Z_]+/)) {
            $('#filterText').val(name).trigger('keyup');
        }
    }*/

    /* Filters the rows by the user given regexp */
    function filterVariables() {
        var mark_next = false, firstCell, odd_row = false;
        $('#serverVariables .var-row').not('.var-header').each(function() {
            firstCell = $(this).children(':first');
            if (mark_next || textFilter == null || textFilter.exec(firstCell.text())) {
                // If current global value is different from session value
                // (has class diffSession), then display that one too
                mark_next = $(this).hasClass('diffSession') && ! mark_next;

                odd_row = ! odd_row;
                $(this).css('display','');
                if (odd_row) {
                    $(this).addClass('odd');
                    $(this).removeClass('even');
                } else {
                    $(this).addClass('even');
                    $(this).removeClass('odd');
                }
            } else {
                $(this).css('display','none');
            }
        });
    }

    /* Allows the user to edit a server variable */
    function editVariable() {
        var $cell = $(this).parent();
        var varName = $cell.parent().find('.var-name').text().replace(/ /g,'_');
        var $mySaveLink = $saveLink.clone().show();
        var $myCancelLink = $cancelLink.clone().show();
        var $msgbox = PMA_ajaxShowMessage();

        $cell.addClass('edit');
        // remove edit link
        $cell.find('a.editLink').remove();

        $mySaveLink.click(function() {
            var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.get($(this).attr('href'), {
                    ajax_request: true,
                    type: 'setval',
                    varName: varName,
                    varValue: $cell.find('input').val()
                }, function(data) {
                    if (data.success) {
                        $cell.html(data.variable);
                        $cell.data('content', data.variable);
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
            $cell.html($cell.data('content'));
            $cell.removeClass('edit');
            return false;
        });

        $.get($mySaveLink.attr('href'), {
                ajax_request: true,
                type: 'getval',
                varName: varName
            }, function(data) {
                if (data.success == true) {
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
                    $cell.data('content', $cell.html()).html($editor);
                    // Keyboard shortcuts to the rescue
                    $cell.find('input').focus().keydown(function(event) {
                        // Enter key
                        if (event.keyCode == 13) {
                            $mySaveLink.trigger('click');
                        }
                        // Escape key
                        if (event.keyCode == 27) {
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
