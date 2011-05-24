$(function() {
    var textFilter=null;
    var alertFilter = false;
    var categoryFilter='';
    var odd_row=false;
    var text='';
    
    $('#serverStatusTabs').tabs({
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        cookie: { name: 'serverStatusTabs', expires: 1 },
        show: function() { menuResize(); }
    });
    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");
    
    // Filter options are invisible for disabled js users
    $('#serverstatusvars fieldset').css('display','');
    
    // Enable table storing
    $('#serverstatusvariables').tablesorter({sortList: [[0,0]]});
    $('#serverstatusqueriesdetails').tablesorter();
    
    // Load chart asynchronly so the page loads faster
    $.get($('#serverstatusquerieschart a').first().attr('href'),{ajax_request:1}, function(data) {
        $('#serverstatusquerieschart').html(data);
        // Init imagemap again
        imageMap.init();
    });
	
	// Allow ajax reload of varialbes
	$('#statustabs_allvars .statuslinks a').first().click(function() {
		var that = this;
		$(this).find('img').show();
		$.get($(this).attr('href'),{ajax_request:1},function(data) {
			$('#serverstatusvariables').parent().html(data);
			filterVariables();
			$(that).find('img').hide();
		});
		return false;
	});

    $('#serverstatusquerieschart div.notice').css('display','none');
    
    $('#filterAlert').change(function() {
        alertFilter = this.checked;
        filterVariables();
    });
    
    $('#filterText').keyup(function(e) {
        if($(this).val().length==0) textFilter=null;
        else textFilter = new RegExp("(^|_)"+$(this).val(),'i');
        text=$(this).val();
        filterVariables();
    });
    
    $('#filterCategory').change(function() {
        categoryFilter = $(this).val();
        filterVariables();
    });
    
    function filterVariables() {
        var useful_links=0;
        var section = text;
        
        if(categoryFilter.length>0) section = categoryFilter;
        
        if(section.length>1) {
            $('#linkSuggestions span').each(function() {
                if($(this).attr('class').indexOf('status_'+section)!=-1) {
                    useful_links++;
                    $(this).css('display','');
                } else {
                    $(this).css('display','none');
                }
                
                
            });
        }
        
        if(useful_links>0) 
            $('#linkSuggestions').css('display','');
        else $('#linkSuggestions').css('display','none');
        
        odd_row=false;
        $('#serverstatusvariables th.name').each(function() {
            if((textFilter==null || textFilter.exec($(this).text()))
                && (!alertFilter || $(this).next().find('span.attention').length>0)
                && (categoryFilter.length==0 || $(this).parent().hasClass('s_'+categoryFilter))) {
                odd_row = !odd_row;                    
                $(this).parent().css('display','');
                if(odd_row) {
                    $(this).parent().addClass('odd');
                    $(this).parent().removeClass('even');
                } else {
                    $(this).parent().addClass('even');
                    $(this).parent().removeClass('odd');
                }
            } else {
                $(this).parent().css('display','none');
            }
        });
    }
});