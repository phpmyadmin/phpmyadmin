/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_variables.js', function() {
    $('#serverVariables .var-row').unbind('hover');
    $('#filterText').unbind('keyup');
});

AJAX.registerOnload('server_variables.js', function() {
    var textFilter = null, odd_row = false;

    // Global vars
    $editLink = $('a.editLink');
    $saveLink = $('a.saveLink');
    $cancelLink = $('a.cancelLink');

    /* Variable editing */
    $('#serverVariables .var-row').hover(
        function() {
            var $elm = $(this).find('.var-value');
            // Only add edit element if the element is not being edited
            if ($elm.hasClass('editable') && ! $elm.hasClass('edit')) {
                $elm.prepend($editLink.clone().show());
            }
        },
        function() {
            $(this).find('a.editLink').remove();
        }
    );

    $('#filterText').keyup(function(e) {
        if ($(this).val().length == 0) {
            textFilter=null;
        } else {
            textFilter = new RegExp("(^| )"+$(this).val().replace(/_/g,' '),'i');
        }
        filterVariables();
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
});

/* Called by inline js. Allows the user to edit a server variable */
function editVariable(link)
{
    var varName = $(link).closest('.var-row').find('.var-name').text().replace(/ /g,'_');
    var $mySaveLink = $saveLink.clone().show();
    var $myCancelLink = $cancelLink.clone().show();
    var $cell = $(link).parent();
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
            }, 'json');

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
                $cell.data('content', $cell.html()).html('');

                // put edit field and save/cancel link
                $cell.prepend(
                    '<table class="serverVariableEditTable" border="0">'
                    + '<tr><td><input type="text" value="' + data.message + '" /></td>'
                    + '<td></td></tr>'
                    + '</table>'
                );
                $cell.find('table td:last').append($mySaveLink);
                $cell.find('table td:last').append(' ');
                $cell.find('table td:last').append($myCancelLink);

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

    return false;
}
