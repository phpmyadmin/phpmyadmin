$(function() {
    var textFilter=null;
    var alertFilter = false;
    var odd_row=false;
    
    $('#statusTabs').tabs({
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        show: function() { menuResize(); }
    });
    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");
    
    // Filter options are invisible for disabled js users
    $('#serverstatusvars fieldset').css('display','');
    
    $.get('server_status.php',window.parent.common_query+'&query_chart_ajax=1', function(data) {
        $('#serverstatusquerieschart').html(data);
		// Init imagemap again
		imageMap.init();
    });

    $('#serverstatusquerieschart div.notice').css('display','none');
    
    $('#filterAlert').change(function() {
        alertFilter = this.checked;
        filterVariables();
    });
    
    $('#filterText').keyup(function(e) {
        if($(this).val().length==0) textFilter=null;
        else textFilter = new RegExp("(^|_)"+$(this).val(),'i');
        filterVariables();
    });
    
    function filterVariables() {
        odd_row=false;
        $('#serverstatusvariables th.name').each(function() {
            
            if((textFilter==null || textFilter.exec($(this).text()))
                && (!alertFilter || $(this).next().find('span.attention').length>0)) {
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