$(function() {
    var textFilter=null;
    var odd_row=false;
    
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