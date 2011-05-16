$(function() {
    var textFilter=null;
    var alertFilter = false;
    var odd_row=false;
    
    // Filter options are invisible for disabled js users
    $('#serverstatusvars fieldset').css('display','');
    
    $('#serverstatusquerieschart a').first().click(function() {
        $.get('server_status.php',window.parent.common_query+'&query_chart_ajax=1', function(data) {
            $('#serverstatusquerieschart').html(data);
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