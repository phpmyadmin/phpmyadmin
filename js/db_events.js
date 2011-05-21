$(document).ready(function() {
    /**
     * Ajax Event handler for 'Drop Event'
     * 
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * @see     $cfg['AjaxEnable']
     */
    $('.drop_event_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_event_row  Object reference to current event's row
         */
        var $curr_event_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $curr_event_row.children('td').children('.drop_sql').text();

        $(this).PMA_confirm(question, $(this).attr('href') , function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingEvent']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $("#result_query").remove();
                    if ($('#event_list tr').length == 2) {
                        $('#event_list').remove();
                        $('#no_events').show();
                    } else {
                       $curr_event_row.hide("medium").remove();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) // end Drop Event

    /**
     * Ajax Event handler for 'Export Event'
     * 
     * @see     $cfg['AjaxEnable']
     */
    $('.export_event_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var button_options  Object containing options for jQueryUI dialog buttons
         */
        var button_options = {};
        button_options[PMA_messages['strClose']] = function() {$(this).dialog("close").remove();}
        /**
         * @var button_options  The export SQL query to display to the user
         */
        var query = $(this).parents('tr').find('.create_sql').html();

        // show dialog
        $('<div><textarea>'+query+'</textarea></div>').dialog({
            width: 450,
            buttons: button_options
        }).find('textarea').width('100%');
    }); // end Export Procedure
}, 'top.frame_content'); //end $(document).ready() for Drop Event
