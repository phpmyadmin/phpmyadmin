$(document).ready(function() {
    /**
     * Ajax Event handler for 'Drop Trigger'
     * 
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * @see     $cfg['AjaxEnable']
     */
    $(".drop_trigger_anchor").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_row    Object reference to the current trigger's <tr>
         */
        var $curr_trig_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $curr_trig_row.children('td').children('.drop_sql').text();

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $("#result_query").remove();
                    if ($('#trigger_list tr').length == 2) {
                        $('#trigger_list').remove();
                        $('#no_triggers').show();
                    } else {
                       $curr_trig_row.hide("medium").remove();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) // end $().live()

    /**
     * Ajax Event handler for 'Export Trigger'
     * 
     * @see     $cfg['AjaxEnable']
     */
    $('.export_trigger_anchor').live('click', function(event) {
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
}, 'top.frame_content'); //end $(document).ready() for Drop Trigger
