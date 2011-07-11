function editVariable(link) {
    var varName = $(link).parent().parent().find('th:first').first().text().replace(/ /g,'_');
    var mySaveLink = $(saveLink);
    var myCancelLink = $(cancelLink);
    var $cell = $(link).parent();
    
    $cell.addClass('edit');
    // remove edit link
    $cell.find('a.editLink').remove();
    
    mySaveLink.click(function() {
        $.get('server_variables.php?' + url_query,
          { ajax_request: true, type: 'setval', varName: varName, varValue: $cell.find('input').attr('value') },
          function(data) {
            if(data.success) $cell.html(data.variable);
            else {
                PMA_ajaxShowMessage(data.error);
                $cell.html($cell.find('span.oldContent').html());
            }
            $cell.removeClass('edit');
          },
          'json'
        );
        return false;
    });
    
    myCancelLink.click(function() {
        $cell.html($cell.find('span.oldContent').html());
        $cell.removeClass('edit');
        return false;
    });
          
    
    $.get('server_variables.php?' + url_query,
          { ajax_request: true, type: 'getval', varName: varName },
          function(data) {
              // hide original content
              $cell.html('<span class="oldContent" style="display:none;">' + $cell.html() + '</span>');
              // put edit field and save/cancel link
              $cell.prepend('<table class="serverVariableEditTable" border="0"><tr><td></td><td style="width:100%;"><input type="text" value="' + data + '"/></td></tr</table>');
              $cell.find('table td:first').append(mySaveLink);
              $cell.find('table td:first').append(myCancelLink);
          }
    );
          
    return false;
}

$(function() {    
    var textFilter=null;
    var odd_row=false;
    var testString = 'abcdefghijklmnopqrstuvwxyz0123456789,ABCEFGHIJKLMOPQRSTUVWXYZ';
    var $tmpDiv;
    var charWidth;
    
    // Global vars
    editLink = '<a href="#" class="editLink" onclick="return editVariable(this);"><img src="'+pmaThemeImage+'b_edit.png" alt="" width="16" height="16"> '+PMA_messages['strEdit']+'</a>';
    saveLink = '<a href="#" class="saveLink"><img src="'+pmaThemeImage+'b_save.png" alt="" width="16" height="16"> '+PMA_messages['strSave']+'</a> ';
    cancelLink = '<a href="#" class="cancelLink"><img src="'+pmaThemeImage+'b_close.png" alt="" width="16" height="16"> '+PMA_messages['strCancel']+'</a> ';


    $.ajaxSetup({
        cache:false
    });
    
    /* Variable editing */
    if(isSuperuser) {
        $('table.data tbody tr td:nth-child(2)').hover(
            function() {
                // Only add edit element if it is the global value, not session value and not when the element is being edited
                if($(this).parent().children('th').length > 0 && ! $(this).hasClass('edit'))
                    $(this).prepend(editLink);
            },
            function() {
                $(this).find('a.editLink').remove();
            }
        );
    }
    
    /*** This code snippet takes care that the table stays readable. It cuts off long strings the table overlaps the window size ***/
    $('table.data').after($tmpDiv=$('<span>'+testString+'</span>'));
    charWidth = $tmpDiv.width() / testString.length;
    $tmpDiv.remove();
    
    $(window).resize(limitTableWidth);
    limitTableWidth();
    
    function limitTableWidth() {
        var fulltext;
        var charDiff;
        var maxTableWidth;
        var $tmpTable;
        
        $('table.data').after($tmpTable=$('<table id="testTable" style="width:100%;"><tr><td>'+testString+'</td></tr></table>'));
        maxTableWidth = $('#testTable').width(); 
        $tmpTable.remove();
        charDiff =  ($('table.data').width()-maxTableWidth) / charWidth;
        
        if($('body').innerWidth() < $('table.data').width()+10 || $('body').innerWidth() > $('table.data').width()+20) {
            var maxChars=0;
            
            $('table.data tbody tr td:nth-child(2)').each(function() {
                maxChars=Math.max($(this).text().length,maxChars);
            });
            
            // Do not resize smaller if there's only 50 chars displayed already
            if(charDiff > 0 && maxChars < 50) return;
            
            $('table.data tbody tr td:nth-child(2)').each(function() {
                if((charDiff>0 && $(this).text().length > maxChars-charDiff) || (charDiff<0 && $(this).find('abbr.cutoff').length>0)) {
                    if($(this).find('abbr.cutoff').length > 0)
                        fulltext = $(this).find('abbr.cutoff').attr('title');
                    else {
                        fulltext = $(this).text();
                        // Do not cut off elements with html in it and hope they are not too long
                        if(fulltext.length != $(this).html().length) return 0;
                    }
                    
                    if(fulltext.length < maxChars-charDiff)
                        $(this).html(fulltext);
                    else $(this).html('<abbr class="cutoff" title="'+fulltext+'">'+fulltext.substr(0,maxChars-charDiff-3)+'...</abbr>');
                }
            });
        }
    }
    
    // Filter options are invisible for disabled js users
    $('fieldset#tableFilter').css('display','');
    
    $('#filterText').keyup(function(e) {
        if($(this).val().length==0) textFilter=null;
        else textFilter = new RegExp("(^| )"+$(this).val().replace('_',' '),'i');
        filterVariables();
    });
    
    function filterVariables() {
        odd_row=false;
        var mark_next=false;
        var firstCell;
        
        $('table.filteredData tbody tr').each(function() {
            firstCell = $(this).children(':first');
            
            if(mark_next || textFilter==null || textFilter.exec(firstCell.text())) {
                // If current row is 'marked', also display next row
                if($(this).hasClass('marked') && !mark_next)
                    mark_next=true;
                else mark_next=false;

                odd_row = !odd_row;                    
                $(this).css('display','');
                if(odd_row) {
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