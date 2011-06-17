$(function() {
    var textFilter=null;
    var odd_row=false;
    var testString = 'abcdefghijklmnopqrstuvwxyz0123456789,ABCEFGHIJKLMOPQRSTUVWXYZ';
    var $tmpDiv;
    var charWidth;

    /*** This code snippet takes care that the table stays readable. It cuts off long strings when the window is resized ***/
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
        else textFilter = new RegExp("(^| )"+$(this).val(),'i');
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