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
                        $('#event_list').hide("slow", function () {
                            $(this).remove();
                        });
                        $('#no_events').show("slow");
                    } else {
                        $curr_event_row.hide("slow", function () {
                            $(this).remove();
                        });
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) // end Drop Event
}, 'top.frame_content'); //end $(document).ready() for Drop Event
