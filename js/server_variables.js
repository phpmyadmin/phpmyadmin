/* vim: set expandtab sw=4 ts=4 sts=4: */
$(function() {
    var textFilter = null, odd_row = false;
    var testString = 'abcdefghijklmnopqrstuvwxyz0123456789,ABCEFGHIJKLMOPQRSTUVWXYZ';
    var $tmpDiv, charWidth;

    // Global vars
    editLink = '<a href="#" class="editLink" onclick="return editVariable(this);">' + PMA_getImage('b_edit.png') + ' ' + PMA_messages['strEdit'] + '</a>';
    saveLink = '<a href="#" class="saveLink">' + PMA_getImage('b_save.png') + ' ' + PMA_messages['strSave'] + '</a> ';
    cancelLink = '<a href="#" class="cancelLink">' + PMA_getImage('b_close.png') + ' ' + PMA_messages['strCancel'] + '</a> ';

    /* Variable editing */
    if (is_superuser) {
        $('table.data tbody tr td:nth-child(2)').hover(
            function() {
                // Only add edit element if it is the global value, not session value and not when the element is being edited
                if ($(this).parent().children('th').length > 0 && ! $(this).hasClass('edit')) {
                    $(this).prepend(editLink);
                }
            },
            function() {
                $(this).find('a.editLink').remove();
            }
        );
    }

    // Filter options are invisible for disabled js users
    $('fieldset#tableFilter').css('display','');

    $('#filterText').keyup(function(e) {
        if ($(this).val().length == 0) {
            textFilter=null;
        } else {
            textFilter = new RegExp("(^| )"+$(this).val().replace(/_/g,' '),'i');
        }
        filterVariables();
    });

    if (location.hash.substr(1).split('=')[0] == 'filter') {
        var name = location.hash.substr(1).split('=')[1];
        // Only allow variable names
        if (! name.match(/[^0-9a-zA-Z_]+/)) {
            $('#filterText').val(name).trigger('keyup');
        }
    }

    /* Table width limiting */
    $('table.data').after($tmpDiv=$('<span>'+testString+'</span>'));
    charWidth = $tmpDiv.width() / testString.length;
    $tmpDiv.remove();

    $(window).resize(limitTableWidth);
    limitTableWidth();

    /* This function chops of long variable values to keep the table from overflowing horizontally
     * It does so by taking a test string and calculating an average font width and removing 'excess width / average font width'
     * chars, so it is not very accurate.
     */
    function limitTableWidth() {
        var fulltext;
        var charDiff;
        var maxTableWidth;
        var $tmpTable;

        $('table.data').after($tmpTable = $('<table id="testTable" style="width:100%;"><tr><td>' + testString + '</td></tr></table>'));
        maxTableWidth = $('#testTable').width();
        $tmpTable.remove();
        charDiff =  ($('table.data').width() - maxTableWidth) / charWidth;

        if ($('body').innerWidth() < $('table.data').width() + 10 || $('body').innerWidth() > $('table.data').width() + 20) {
            var maxChars = 0;

            $('table.data tbody tr td:nth-child(2)').each(function() {
                maxChars = Math.max($(this).text().length, maxChars);
            });

            // Do not resize smaller if there's only 50 chars displayed already
            if (charDiff > 0 && maxChars < 50) { return; }

            $('table.data tbody tr td:nth-child(2)').each(function() {
                if ((charDiff > 0 && $(this).text().length > maxChars - charDiff) || (charDiff < 0 && $(this).find('abbr.cutoff').length > 0)) {
                    if ($(this).find('abbr.cutoff').length > 0) {
                        fulltext = $(this).find('abbr.cutoff').attr('title');
                    } else {
                        fulltext = $(this).text();
                        // Do not cut off elements with html in it and hope they are not too long
                        if (fulltext.length != $(this).html().length) { return 0; }
                    }

                    if (fulltext.length < maxChars - charDiff) {
                        $(this).html(fulltext);
                    } else {
                        $(this).html('<abbr class="cutoff" title="' + fulltext + '">' + fulltext.substr(0, maxChars - charDiff - 3) + '...</abbr>');
                    }
                }
            });
        }
    }

    /* Filters the rows by the user given regexp */
    function filterVariables() {
        var mark_next = false, firstCell;
        odd_row = false;

        $('table.filteredData tbody tr').each(function() {
            firstCell = $(this).children(':first');

            if (mark_next || textFilter == null || textFilter.exec(firstCell.text())) {
                // If current global value is different from session value (=has class diffSession), then display that one too
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
    var varName = $(link).parent().parent().find('th:first').first().text().replace(/ /g,'_');
    var mySaveLink = $(saveLink);
    var myCancelLink = $(cancelLink);
    var $cell = $(link).parent();
    var $msgbox = PMA_ajaxShowMessage();

    $cell.addClass('edit');
    // remove edit link
    $cell.find('a.editLink').remove();

    mySaveLink.click(function() {
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        $.get('server_variables.php?' + url_query, {
                ajax_request: true,
                type: 'setval',
                varName: varName,
                varValue: $cell.find('input').val()
            }, function(data) {
                if (data.success) {
                    $cell.html(data.variable);
                    PMA_ajaxRemoveMessage($msgbox);
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                    $cell.html($cell.find('span.oldContent').html());
                }
                $cell.removeClass('edit');
            }, 'json');

        return false;
    });

    myCancelLink.click(function() {
        $cell.html($cell.find('span.oldContent').html());
        $cell.removeClass('edit');
        return false;
    });

    $.get('server_variables.php?' + url_query, {
            ajax_request: true,
            type: 'getval',
            varName: varName
        }, function(data) {
            // hide original content
            $cell.html('<span class="oldContent" style="display:none;">' + $cell.html() + '</span>');
            // put edit field and save/cancel link
            $cell.prepend('<table class="serverVariableEditTable" border="0"><tr><td></td><td style="width:100%;">' +
                          '<input type="text" id="variableEditArea" value="' + data.message + '" /></td></tr></table>');
            $cell.find('table td:first').append(mySaveLink);
            $cell.find('table td:first').append(' ');
            $cell.find('table td:first').append(myCancelLink);

            // Keyboard shortcuts to the rescue
            $('input#variableEditArea').focus();
            $('input#variableEditArea').keydown(function(event) {
                // Enter key
                if (event.keyCode == 13) {
                    mySaveLink.trigger('click');
                }
                // Escape key
                if (event.keyCode == 27) {
                    myCancelLink.trigger('click');
                }
            });
            PMA_ajaxRemoveMessage($msgbox);
        });

    return false;
}
